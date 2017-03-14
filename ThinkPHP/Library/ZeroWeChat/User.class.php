<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2016-07-04
 * Time: 15:15
 * Desc: 微信用户管理
 */

namespace ZeroWeChat;

use Think\Log;

class User
{
    /**
     * 获取用户基本信息
     */
    const API_GET = 'https://api.weixin.qq.com/cgi-bin/user/info';

    /**
     * 批量获取用户信息
     */
    const API_BATCH_GET = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget';

    /**
     * 获取用户 openid 列表
     */
    const API_LIST = 'https://api.weixin.qq.com/cgi-bin/user/get';

    /**
     * 获取用户的组
     */
    const API_GROUP = 'https://api.weixin.qq.com/cgi-bin/groups/getid';

    /**
     * 更新用户的标签
     */
    const API_REMARK = 'https://api.weixin.qq.com/cgi-bin/user/info/updateremark';

    /**
     * 通过网页授权方式获取用户信息
     */
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
     * 通过网页授权方式获取用户的信息
     *
     * @param string $access_token 网页授权的到的 access_token
     * @param string $openid       用户的openid
     * @param string $lang         用户的语言
     *
     * @return array|bool
     */
    public static function oAuthUserInfo($access_token, $openid, $lang = 'zh_CN')
    {
        $params = [
            'access_token' => $access_token,
            'openid' => $openid,
            'lang' => $lang,
        ];

        $user = Util::httpGet(self::API_OAUTH_GET, $params);

        if (isset($user['errcode'])) {
            $msg = '获取授权用户信息失败:[openid]'.$openid.' [msg]'.$user['msg'];
            Log::record($msg, 'ERR');

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