<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2016-08-07
 * Time: 12:21
 * Desc:
 */

namespace ZeroWeChat;


class Jsapi
{
    /**
     * @var string 微信公众号appId
     */
    private $appId;

    /**
     * @var string 微信公众号appSecret
     */
    private $appSecret;

    /**
     * Jsapi constructor.
     *
     * @param string $appId
     * @param string $appSecret
     */
    public function __construct($appId, $appSecret) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

    /**
     * 获取jssdk的签名包
     *
     * @return array|bool
     */
    public function signPackage() {
        $jsapiTicket = $this->ticket();
        if (!$jsapiTicket) return false;

        // 注意 URL 一定要动态获取
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $timestamp = time();
        $nonceStr  = Util::createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        return [
            "appId"     => $this->appId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        ];
    }

    /**
     * 获取jssdk的ticket
     *
     * @return string|bool
     */
    private function ticket() {
        $data = Util::getPhpFile(dirname(__FILE__).'/php_files/jsapi_ticket.php');

        if ($data['expires_in'] < time()) {
            $ticket = $this->refresh();
        } else {
            $ticket = $data['jsapi_ticket'];
        }

        return $ticket;
    }

    /**
     * 刷新jssdk的ticket
     *
     * @return string|bool
     */
    private function refresh()
    {
        $accessToken = Wechat::getInstance($this->appId, $this->appSecret)->getAccessToken();

        // 如果是企业号用以下 URL 获取 ticket
        // "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket";
        $res = Util::httpGet($url, ['type' => 'jsapi', 'access_token' => $accessToken]);

        if (!$res) return false;

        if ($res['errcode']) {
            \Think\Log::record('[获取jsapi错误]'.$res['errcode'].': '.$res['errmsg'], 'ERR');

            return false;
        }

        $data = [
            'jsapi_ticket' => $res['ticket'],
            'expires_in'   => time() + $res['expires_in'] - 1800,
        ];
        Util::setPhpFile(dirname(__FILE__).'/php_files/jsapi_ticket.php', $data);

        return $res['ticket'];
    }
}