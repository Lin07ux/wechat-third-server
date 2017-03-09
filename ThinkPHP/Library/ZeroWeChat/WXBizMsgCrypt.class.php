<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2016-09-14
 * Time: 14:28
 * Desc: 微信消息加解密
 */

namespace ZeroWeChat;


class WXBizMsgCrypt
{
    private $app_id;
    private $token;
    private $aes_key;
    private $errCode = 0;

    /**
     * 构造函数
     *
     * @param string $appId          公众平台的appId
     * @param string $token          公众平台上，开发者设置的token
     * @param string $encodingAesKey 公众平台上，开发者设置的EncodingAESKey
     */
    public function __construct($appId, $token, $encodingAesKey)
    {
        $this->app_id  = $appId;
        $this->token   = $token;
        $this->aes_key = $encodingAesKey;
    }

    /**
     * 获取错误代码
     *
     * @return int
     */
    public function getErrCode()
    {
        return $this->errCode;
    }

    /**
     * 将公众平台回复用户的消息加密打包.
     *    1. 对要发送的消息进行AES-CBC加密
     *    2. 生成安全签名
     *    3. 将消息密文和安全签名打包成xml格式
     *
     * @param string $replyMsg  公众平台待回复用户的消息，xml格式的字符串
     * @param string $timeStamp 时间戳，可以自己生成，也可以用URL参数的timestamp
     * @param string $nonce     随机串，可以自己生成，也可以用URL参数的nonce
     *
     * @return string|false
     */
    public function encryptMsg($replyMsg, $timeStamp, $nonce)
    {
        // 加密
        if (! ($encrypt = $this->encrypt($replyMsg)) ) {
            return false;
        }

        if (!$timeStamp) $timeStamp = time();

        // 生成安全签名
        $signature = $this->signature($this->token, $timeStamp, $nonce, $encrypt);
        if (!$signature) {
            return false;
        }

        // 生成发送的xml
        return $this->generateEncryptXML($encrypt, $signature, $timeStamp, $nonce);
    }

    /**
     * 检验消息的真实性，并且获取解密后的明文.
     *
     * 1. 利用收到的密文生成安全签名，进行签名验证
     * 2. 若验证通过，则提取xml中的加密消息
     * 3. 对消息进行解密
     *
     * @param string $postData     密文，对应POST请求的数据
     * @param string $msgSignature 签名串，对应URL参数的msg_signature
     * @param string $nonce        随机串，对应URL参数的nonce
     * @param int    $timestamp    时间戳 对应URL参数的timestamp
     *
     * @return string|bool
     */
    public function decryptMsg($postData, $msgSignature, $nonce, $timestamp = 0)
    {
        if (strlen($this->aes_key) != 43) {
            $this->errCode = ErrorCode::AES_KEY_ILLEGAL;
            return false;
        }

        // 提取密文
        $encrypt = $this->extractEncryptXML($postData);
        if (!$encrypt) return false;

        if (!$timestamp) $timestamp = time();

        // 验证安全签名
        $signature = $this->signature($this->token, $timestamp, $nonce, $encrypt);
        if (!$signature) {
            return false;
        } elseif ($signature !== $msgSignature) {
            $this->errCode = ErrorCode::SIGNATURE_VALIDATE_ERR;
            return false;
        }

        // 解出密文
        if ($result = $this->decrypt($encrypt)) {
            $this->errCode = ErrorCode::OK;
            return $result;
        }

        return false;
    }

    /**
     * 提取出加密过的xml数据包中的加密消息
     *
     * @param string $xml_text 待提取的xml字符串
     *
     * @return string|null
     */
    protected function extractEncryptXML($xml_text)
    {
        try {
            $xml = new \DOMDocument();
            $xml->loadXML($xml_text);

            return $xml->getElementsByTagName('Encrypt')->item(0)->nodeValue;

        } catch (\Exception $e) {
            $this->errCode = ErrorCode::XML_PARSE_ERR;
            return null;
        }
    }

    /**
     * 生成xml消息
     *
     * @param string $encrypt   加密后的消息密文
     * @param string $signature 安全签名
     * @param string $timestamp 时间戳
     * @param string $nonce     随机字符串
     *
     * @return string 生成的xml字符串
     */
    protected function generateEncryptXML($encrypt, $signature, $timestamp, $nonce)
    {
        $format = "<xml><Encrypt><![CDATA[%s]]></Encrypt><MsgSignature><![CDATA[%s]]></MsgSignature><TimeStamp>%s</TimeStamp><Nonce><![CDATA[%s]]></Nonce></xml>";

        return sprintf($format, $encrypt, $signature, $timestamp, $nonce);
    }

    /**
     * 生成安全签名
     *
     * @param string $token       票据
     * @param string $timestamp   时间戳
     * @param string $nonce       随机字符串
     * @param string $encrypt_msg 密文消息
     *
     * @return string|null
     */
    protected function signature($token, $timestamp, $nonce, $encrypt_msg)
    {
        try {
            $array = array($token, $timestamp, $nonce, $encrypt_msg);
            sort($array, SORT_STRING);
            $str = implode($array);

            return sha1($str);

        } catch (\Exception $e) {
            $this->errCode = ErrorCode::SIGNATURE_COMPUTE_ERR;
            return null;
        }
    }

    /**
     * 对明文进行加密
     *
     * @param string $text 需要加密的明文
     *
     * @return string|null 加密后的密文
     */
    protected function encrypt($text)
    {
        try {
            //获得16位随机字符串，填充到明文之前
            $random = Util::createNonceStr();
            $text   = $random . pack("N", strlen($text)) . $text . $this->app_id;

            //使用自定义的填充方式对明文进行补位填充
            $text = $this->padding($text);

            // 网络字节序
            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            $key    = base64_decode($this->aes_key . '=');
            $iv     = substr($key, 0, 16);
            mcrypt_generic_init($module, $key, $iv);

            // 加密
            $encrypted = mcrypt_generic($module, $text);
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);

            // 使用 BASE64 对加密后的字符串进行编码
            return base64_encode($encrypted);

        } catch (\Exception $e) {
            $this->errCode = ErrorCode::AES_ENCRYPT_ERROR;

            return null;
        }
    }

    /**
     * 对密文进行解密
     *
     * @param string $encrypted 需要解密的密文
     *
     * @return string|null 解密得到的明文
     */
    protected function decrypt($encrypted)
    {
        try {
            // 使用Base64对需要解密的字符串进行解码
            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            $key    = base64_decode($this->aes_key . "=");

            mcrypt_generic_init($module, $key, substr($key, 0, 16));

            // 解密
            $decrypted = mdecrypt_generic($module, base64_decode($encrypted));

            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);

        } catch (\Exception $e) {
            $this->errCode = ErrorCode::BASE64_DECODE_ERR;
            return null;
        }

        try {
            // 去除补位字符
            $result = $this->removePad($decrypted);
            if (strlen($result) < 16) {
                return null;
            }

            // 去除16位随机字符串,网络字节序和AppId
            $content     = substr($result, 16);
            $len_list    = unpack("N", substr($content, 0, 4));
            $xml_len     = $len_list[1];
            $xml_content = substr($content, 4, $xml_len);
            $from_appid  = substr($content, $xml_len + 4);

        } catch (\Exception $e) {
            $this->errCode = ErrorCode::BUFFER_ILLEGAL;
            return null;
        }

        if ($from_appid != $this->app_id) {
            $this->errCode = ErrorCode::APPID_VALIDATE_ERR;
            return null;
        }

        return $xml_content;
    }

    /**
     * 对需要加密的明文进行填充补位
     *
     * @param string $text       需要进行填充补位操作的明文
     * @param int    $block_size 补位长度
     *
     * @return string
     */
    protected function padding($text, $block_size = 32)
    {
        //计算需要填充的位数
        $amount_to_pad = $block_size - (strlen($text) % $block_size);
        if ($amount_to_pad == 0) {
            $amount_to_pad = $block_size;
        }

        //获得补位所用的字符
        $pad_chr = chr($amount_to_pad);
        $tmp     = "";
        for ($index = 0; $index < $amount_to_pad; $index++) {
            $tmp .= $pad_chr;
        }

        return $text . $tmp;
    }

    /**
     * 对解密后的明文进行补位删除
     *
     * @param string $decrypted 解密后的明文
     *
     * @return string 删除填充补位后的明文
     */
    protected function removePad($decrypted)
    {

        $pad = ord(substr($decrypted, -1));
        if ($pad < 1 || $pad > 32) $pad = 0;

        return substr($decrypted, 0, (strlen($decrypted) - $pad));
    }
}