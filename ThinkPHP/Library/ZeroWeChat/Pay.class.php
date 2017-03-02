<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2016-08-11
 * Time: 14:50
 * Desc:
 */

namespace ZeroWeChat;


class Pay
{
    /**
     * 发送现金红包接口
     */
    const API_REDPACK_URL  = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';

    /**
     * 发送列表红包接口
     */
    const API_GROUP_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendgroupredpack';

    /**
     * 企业付款接口
     */
    const API_TRAN_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';

    /**
     * @var string 微信的AppId
     */
    private $appId = '';
    /**
     * @var string 商户号
     */
    private $merchantId = '';
    /**
     * @var string 微信支付的Api安全密钥
     */
    private $apiKey = '';

    /**
     * @var string 商户证书路径
     */
    private $apiCert = '';
    /**
     * @var string 商户证书密钥路径
     */
    private $apiCertKey  = '';

    /**
     * 红包查询接口
     */
    const API_QUERY_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gethbinfo';

    public function __construct($appId, $merchantId, $key)
    {
        $this->appId      = $appId;
        $this->merchantId = $merchantId;
        $this->apiKey        = $key;
    }

    /**
     * 设置商户证书路径
     *
     * @param string $cert 证书路径
     * @param string $key  证书密钥路径
     *
     * @return $this
     */
    public function setCertPath($cert, $key)
    {
        $this->apiCert = $cert;
        $this->apiCertKey = $key;

        return $this;
    }

    protected function doPay($url, $data)
    {
        $data['nonce_str'] = Util::createNonceStr(32);
        $data['sign']      = $this->sign($data);

        $postXml = Util::arrayToXml($data);

        \Think\Log::record($postXml, 'DEBUG');

        return $this->curlPostSsl($url, $postXml);
    }

    /**
     * 生成签名
     *
     * @param array $data 请求数据数组
     *
     * @return string
     */
    protected function sign($data)
    {
        $buff = '';
        ksort($data);

        foreach ($data as $key => $val) {
            if (null != $val && 'null' != $val && 'sign' != $key) {
                $buff .= $key . '=' . $val . '&';
            }
        }

        $buff .= 'key=' . $this->apiKey;

        return strtoupper(md5($buff));
    }

    /**
     * 发送支付请求
     *
     * @param string $url     请求地址
     * @param string $vars    请求参数
     * @param int    $second  超时时间(单位:秒)
     * @param array  $aHeader 附加请求头
     *
     * @return array|bool
     */
    protected function curlPostSsl($url, $vars, $second = 30, $aHeader = array())
    {
        $ch = curl_init();
        // 超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 设置代理，如果有的话
        // curl_setopt($ch, CURLOPT_PROXY, '10.206.30.98');
        // curl_setopt($ch, CURLOPT_PROXYPORT, 8080);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        //以下两种方式需选择一种

        // 第一种方法，cert 与 key 分别属于两个.pem文件
        // 默认格式为 PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $this->apiCert);
        // 默认格式为 PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, $this->apiCertKey);

        // 第二种方式，两个文件合成一个.pem文件
        // curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/all.pem');

        if (count($aHeader) >= 1) curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);

        $data = curl_exec($ch);

        // 操作失败
        if (!$data) {
            \Think\Log::record('cur_post_ssl操作失败。errcode: '.curl_errno($ch).'。errmsg: '.curl_error($ch));
            curl_close($ch);

            return false;
        }

        curl_close($ch);
        libxml_disable_entity_loader(true);
        $res = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        // 解析失败
        if (!$res) return false;

        return (array)$res;
    }

    /**
     * 发送现金红包
     *
     * @param string $openid  用户openid
     * @param int    $amount  红包金额(单位:分)
     * @param string $partner 商户名称
     * @param string $name    活动名称
     * @param string $wish    祝福语
     * @param string $remark  备注
     *
     * @return array|bool
     */
    public function redpack($openid, $amount, $partner, $name, $wish, $remark = '')
    {
        $billNo = substr(microtime(true) * 10000, -10);
        $data = [
            'wxappid'      => $this->appId,      // 公众账号appid
            'mch_id'       => $this->merchantId, // 商户号
            'mch_billno'   => $this->merchantId . date('Ymd') . $billNo, // 订单号
            'send_name'    => $partner,    // 商户名称
            'act_name'     => $name,       // 活动名称
            're_openid'    => $openid,     // 用户openid
            'total_amount' => $amount,     // 付款金额
            'total_num'    => 1,           // 人数
            'wishing'      => $wish,       // 祝福语
            'client_ip'    => '127.0.0.1', // IP地址
            'remark'       => $remark,     // 备注信息
        ];

        $result = $this->doPay(self::API_REDPACK_URL, $data);

        if (!$result) return false;

        if ('SUCCESS' !== strtoupper($result['return_code'])) {
            \Think\Log::record('发送现金红包失败。openid: ' . $openid . '。错误信息: ' . $result['return_msg']);

            return false;
        }

        if ('SUCCESS' !== strtoupper($result['result_code'])) {
            \Think\Log::record('发送现金红包失败。openid: ' . $openid . '。错误代码: ' . $result['err_code'] . '。错误代码描述: ' . $result['err_code_des']);

            return false;
        }

        return $result;
    }
}