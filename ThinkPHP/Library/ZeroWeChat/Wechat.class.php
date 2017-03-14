<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2016-07-01
 * Time: 15:36
 * Desc: 微信公众号基础类
 */

namespace ZeroWeChat;

use Think\Log;

class Wechat
{
    /**
     * 获取基本 access_token
     */
    const GET_ACCESS_TOKEN = 'https://api.weixin.qq.com/cgi-bin/token';

    /**
     * 获取网页授权信息
     */
    const WEB_OAUTH_INFO = 'https://api.weixin.qq.com/sns/oauth2/access_token';

    /**
     * 网页授权的 URL
     */
    const WEB_OAUTH_URL = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_%s&state=%s#wechat_redirect';

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
     * @var string 存储 access_token 的文件路径
     */
    private $accessTokenFile = '/php_files/access_token.php';

    /**
     * Wechat constructor.
     *
     * @param string $appId     公众号 AppId
     * @param string $appSecret 公众号 APPSecret
     * @param string $token     公众号 Token
     */
    private function __construct($appId, $appSecret, $token)
    {
        $this->appId     = $appId;
        $this->appSecret = $appSecret;
        $this->token     = $token;
        $this->accessTokenFile = dirname(__FILE__) . $this->accessTokenFile;
    }

    /**
     * 获取该类的实例对象
     *
     * @param string $appId     微信公众号的 AppID
     * @param string $appSecret 微信公众号的AppSecret
     * @param string $token     微信公众号第三方服务器的 Token
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
     * 获取 access_token
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
            $data = Util::getPhpFile($this->accessTokenFile);

            if ($data['expires_in'] <= time()) {
                $token = $this->refreshAccessToken();
            } else {
                $token = $data['access_token'];
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

        $res = Util::httpGet(self::GET_ACCESS_TOKEN, $params);

        if (!$res || !isset($res['access_token'])) return false;

        // 计算 access_token 的有效期,并写入到文件中
        $res['expires_in'] = time() + $res['expires_in'] - 1800;
        Util::setPhpFile($this->accessTokenFile, $res);

        return $res['access_token'];
    }

    /**
     * 获取网页授权的 url
     *
     * @param string $redirect 授权后的回调 url
     * @param string $scope    授权方式('base'、'userinfo')
     * @param string $state    重定向后原样返回的信息([a-zA-Z0-9]{0,128})
     *
     * @return string
     */
    public function getOauthUrl($redirect, $scope, $state = '')
    {
        $state = $state ?: $scope;

        return sprintf(
            self::WEB_OAUTH_URL,
            $this->appId,
            urlencode($redirect),
            $scope,
            $state
        );
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
    public function getOauthResult($code)
    {
        $params = array(
            'appid'      => $this->appId,
            'secret'     => $this->appSecret,
            'code'       => $code,
            'grant_type' => 'authorization_code'
        );

        $result = Util::httpGet(self::WEB_OAUTH_INFO, $params);

        if (!$result) return false;

        if (isset($result['errcode'])) {
            Log::record('[获取微信网页授权失败]: '.json_encode($result), 'ERR');
            return false;
        }

        return $result;
    }
}