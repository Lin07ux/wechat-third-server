<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2016-07-04
 * Time: 15:15
 * Desc:
 */

namespace ZeroWeChat;


use Think\Log;

class User
{
    const API_GET       = 'https://api.weixin.qq.com/cgi-bin/user/info';
    const API_BATCH_GET = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget';
    const API_LIST      = 'https://api.weixin.qq.com/cgi-bin/user/get';
    const API_GROUP     = 'https://api.weixin.qq.com/cgi-bin/groups/getid';
    const API_REMARK    = 'https://api.weixin.qq.com/cgi-bin/user/info/updateremark';
    const API_OAUTH_GET = 'https://api.weixin.qq.com/sns/userinfo';

    /**
     * @var string 微信的 access_token
     */
    protected $access_token;

    /**
     * User constructor.
     *
     * @param string $access_token 微信的 access_token
     */
    public function __construct($access_token)
    {
        $this->access_token = $access_token;
    }

    /**
     * 获取用户的基本信息
     *
     * @param string $openid 用户的 openid
     * @param string $lang   区域语言,默认为 zh_CN
     * @param bool   @oauth  是否为通过授权获取用户信息,默认为false
     *
     * @return bool|array
     */
    public function get($openid, $lang = 'zh_CN')
    {
        $params = [
            'access_token' => $this->access_token,
            'openid' => $openid,
            'lang' => $lang,
        ];

        $user = Util::httpGet(self::API_GET, $params);

        if (isset($user['code']) && $user['code']) {
            Log::record('[操作错误]获取 '.$openid.' 的用户信息失败。'.$user['msg'], 'WARN');

            return false;
        }

        return $user;
    }

    /**
     * 获取授权用户的信息
     *
     * @param string $openid 用户的openid
     * @param string $lang   用户的语言
     *
     * @return array|bool
     */
    public function getOauth($openid, $lang = 'zh_CN')
    {
        $params = [
            'access_token' => $this->access_token,
            'openid' => $openid,
            'lang' => $lang,
        ];

        $user = Util::httpGet(self::API_OAUTH_GET, $params);

        if (isset($user['errcode'])) {
            \Think\Log::record('[操作错误]获取授权用户 '.$openid.' 的用户信息失败。'.$user['msg'], 'ERR');

            return false;
        }

        return $user;
    }

    /**
     * 获取用户列表
     *
     * @param string $nextOpenId
     *
     * @return boolean|array
     */
    public function lists($nextOpenId = null)
    {
        $params['access_token'] = $this->access_token;

        if ($nextOpenId) $params['next_openid'] = $nextOpenId;

        $users = Util::httpGet(self::API_LIST, $params);

        if ($users['errcode']) {
            \Think\Log::record('获取用户列表失败。下一个Openid为: '. $nextOpenId . '。失败信息: ' . $users['errmsg'], 'ERR');

            return false;
        }

        return $users;
    }
}