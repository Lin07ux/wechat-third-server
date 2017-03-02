<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2016-07-01
 * Time: 11:46
 * Desc: 微信类库的基础类,定义了一些常用的方法
 */

namespace ZeroWeChat;


class Util
{

    /**
     * 生成随机字符串
     *
     * @param int $length 生成的随机字符串的长度
     *
     * @return string
     */
    public static function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * curl GET 请求
     *
     * @param string $url 请求的地址
     * @param array  $params 请求的参数
     *
     * @return bool|array
     */
    public static function httpGet($url, $params = array()) {
        $query = '';
        foreach ($params as $key => $val) {
            $query .= $key . '=' . $val . '&';
        }
        // 如果有查询参数,则在首位加上 ?, 并去掉末尾的 &
        // 然后拼合到 $url 中
        if ($query) {
            $query = '?' . $query;
            $query = substr($query, 0, -1);
            $url  .= $query;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_URL, $url);

        if (curl_errno($curl)) {
            // 记录错误日志
            \Think\Log::record('[网络错误]'.curl_errno($curl).': '.curl_error($curl), 'ERR');
            $info = false;
        } else {
            $info = json_decode(curl_exec($curl), true);
        }

        curl_close($curl);

        return $info;
    }

    /**
     * curl POST 请求
     *
     * @param string $url 请求的地址
     * @param array $data 发送的数据
     *
     * @return mixed|string
     */
    public static function httpsPost($url, $data) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if (curl_errno($curl)) {
            $info = array('code' => curl_errno($curl), 'msg' => curl_error($curl),);
        } else {
            $info = json_decode(curl_exec($curl), true);
        }

        curl_close($curl);

        return $info;
    }

    /**
     * curl 安全 POST 请求
     *
     * @param string $url 请求的地址
     * @param array $data 发送的数据
     *
     * @return array
     */
    public static function httpsSafePost($url, $data) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if (curl_errno($curl)) {
            $info = array('code' => curl_errno($curl), 'msg' => curl_error($curl),);
        } else {
            $info = json_decode(curl_exec($curl), true);
        }

        curl_close($curl);

        return $info;
    }

    /**
     * 读取文件内容
     *
     * @param string $filename 文件名
     *
     * @return object
     */
    public static function get_php_file($filename) {
        return json_decode(trim(substr(file_get_contents($filename), 15)));
    }

    /**
     * 修改文件内容
     *
     * @param string $filename 文件名
     * @param array $content  文件内容
     */
    public static function set_php_file($filename, $content) {
        $fp = fopen($filename, "w");
        fwrite($fp, "<?php exit();?>" . json_encode($content));
        fclose($fp);
    }

    /**
     * 将数组转成 xml
     *
     * @param array $arr 要转换的数组
     *
     * @return string
     */
    public static function arrayToXml($arr)
    {
        $xml = '<xml>';

        if (is_array($arr)) {
            foreach ($arr as $key => $val) {
                $xml .= '<' . $key . '>';
                if (is_numeric($val)) {
                    $xml .= $val;
                } else {
                    $xml .= '<![CDATA[' . $val . ']]>';
                }

                $xml .= '</' . $key . '>';
            }
        }

        $xml .= '</xml>';

        return $xml;
    }
}