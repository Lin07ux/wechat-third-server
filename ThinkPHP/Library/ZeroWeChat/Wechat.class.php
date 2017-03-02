<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2016-07-01
 * Time: 15:36
 * Desc:
 */

namespace ZeroWeChat;

/**
 * Class Wechat 微信基础类
 *
 * @package ZeroWeChat
 */
class Wechat
{
    /**
     * 获取基本access_token
     */
    const API_GET_ACCESS_TOKEN = 'https://api.weixin.qq.com/cgi-bin/token';
    /**
     * 获取网页授权信息
     */
    const API_SNS_OAUTH_INFO   = 'https://api.weixin.qq.com/sns/oauth2/access_token';

    /**
     * @var Wechat 该类的单例实例
     */
    private static $instance;
    /**
     * @var string 微信 AppID
     */
    private $appId;

    /**
     * @var string 微信 APPSecret
     */
    private $appSecret;

    /**
     * @var string 微信 token
     */
    private $token;

    /**
     * Wechat constructor.
     *
     * @param $appId string
     * @param $appSecret string
     * @param $token string
     */
    private function __construct($appId, $appSecret, $token)
    {
        $this->appId     = $appId;
        $this->appSecret = $appSecret;
        $this->token     = $token;
    }

    /**
     * 获取该类的实例对象
     *
     * @param string $appId 微信公众号的 AppID
     * @param string $appSecret 微信公众号的AppSecret
     * @param string $token 微信公众号第三方服务器的 Token
     *
     * @return Wechat
     */
    public static function getInstance($appId, $appSecret, $token = '')
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self($appId, $appSecret, $token);
        }

        return self::$instance;
    }

    /**
     * 防止对象实例被克隆
     *
     * @return void
     */
    public function __clone()
    {
    }

    /**
     * 防止被反序列化
     *
     * @return void
     */
    public function __wakeup()
    {
    }

    /**
     * 刷新 access_token
     *
     * @param bool $forceRefresh 是否强制刷新 access_token
     *
     * @return bool|string
     */
    public function getAccessToken($forceRefresh = false)
    {
        if ($forceRefresh) {
            $token = $this->refreshAccessToken();
        } else {
            $data = Util::get_php_file(dirname(__FILE__).'/php_files/access_token.php');

            if ($data->expires_in <= time()) {
                $token = $this->refreshAccessToken();
            } else {
                $token = $data->access_token;
            }
        }

        return $token;
    }

    /**
     * 刷新 access_token
     *
     * @return bool|string
     */
    protected function refreshAccessToken()
    {
        $params = array(
            'grant_type' => 'client_credential',
            'appid'      => $this->appId,
            'secret'     => $this->appSecret,
        );

        $res = Util::httpGet(self::API_GET_ACCESS_TOKEN, $params);
        if (!$res['access_token']) {
            return false;
        }

        // 计算 access_token 的有效期,并写入到文件中
        $res['expires_in'] = time() + $res['expires_in'] - 1800;
        Util::set_php_file(dirname(__FILE__).'/php_files/access_token.php', $res);

        return $res['access_token'];
    }

    /**
     * 获取网页授权的信息
     *
     * @param string $code 网页授权返回的code
     *
     * @return bool|array
     * 成功: ["access_token" => "ACCESS_TOKEN", "expires_in" => 7200,
     *       "refresh_token" => "REFRESH_TOKEN",
     *       "openid" => "OPENID", "scope" => "SCOPE"]
     */
    public function snsOauth2($code)
    {
        $params = array(
            'appid'      => $this->appId,
            'secret'     => $this->appSecret,
            'code'       => $code,
            'grant_type' => 'authorization_code'
        );

        $r = Util::httpGet(self::API_SNS_OAUTH_INFO, $params);

        if (isset($r['errcode'])) {
            \Think\Log::record('[操作错误]获取微信网页授权失败: '.$r['errcode'].': '.$r['msg'], 'ERR');
            $r = false;
        }

        return $r;
    }
}