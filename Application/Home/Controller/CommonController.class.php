<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2016-09-27
 * Time: 09:47
 * Desc:
 */

namespace Home\Controller;

use Think\Controller;
use Think\Log;
use ZeroWeChat\Jsapi;
use ZeroWeChat\User;
use ZeroWeChat\Wechat;

class CommonController extends Controller
{
    /**
     * 显示404页面
     */
    protected function send404()
    {
        header("HTTP/1.1 404 Not Found");
        if (IS_GET) {
            $this->display('Public/error404');
        } elseif (IS_POST) {
            $this->ajaxReturn(['code' => 404, 'msg' => 'Not Found',]);
        }

        die('404 Not Found');
    }

    /**
     * 显示网络错误页面
     */
    protected function sendNetErr()
    {
        header("HTTP/1.1 400 Net Error");
        $this->display('Public/errorNet');
        exit();
    }

    /**
     * 获取jsapi的签名信息
     *
     * @return array|bool
     */
    protected function jsSignPackage()
    {
        $jsapi = new Jsapi(C('WECHAT.APPID'), C('WECHAT.SECRET'));

        return $jsapi->signPackage();
    }

    /**
     * 获取 Wechat 实例
     *
     * @return Wechat
     */
    protected function wechat()
    {
        return Wechat::getInstance(C('WECHAT.APPID'), C('WECHAT.SECRET'));
    }

    /**
     * 进行微信网页授权处理
     *
     * 静默授权可以确切用户是否关注公众号
     * 非静默授权不确定是否关注,但是无论是否关注都能获得用户信息
     *
     * @param string $type 授权方式:base静默授权/userinfo用户授权
     */
    protected function oAuth($type = 'base')
    {
        if ($code = I('get.code')) {
            $user = $this->userinfo($type, $code);

            if (!$user) $this->sendNetErr();

            session('user', $user);

        } else {
            if ('userinfo' !== $type) $type = 'base';

            $oauthUrl = $this->wechat()->getOauthUrl(get_current_url(), $type);

            redirect($oauthUrl);
            die('授权跳转');
        }
    }

    /**
     * 通过网页授权获取用户信息
     *
     * @param string $type 网页授权方式
     * @param string $code 授权码code
     *
     * @return array|bool
     */
    protected function userinfo($type, $code)
    {
        $result = $this->wechat()->getOauthResult($code);

        if (!$result) {
            Log::record('[网页授权信息获取失败]' . json_encode($result), 'DEBUG');
            return false;
        }

        if ('userinfo' == $type) {
            // 非静默授权(用户授权)
            $info = User::oAuthUserInfo($result['access_token'], $result['openid']);
        } else {
            // 静默授权
            $token = $this->wechat()->getAccessToken();
            $User  = new User($token);
            $info  = $User->get($result['openid']);
        }


        if (!$info) return false;

        return D('Users')->syncUserInfo($info);
    }
}