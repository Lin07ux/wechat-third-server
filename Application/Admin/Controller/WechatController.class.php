<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2017-03-02
 * Time: 15:17
 * Desc: 后台微信管理页面
 */

namespace Admin\Controller;


class WechatController extends CommonController
{
    /**
     * 公众号信息概览
     */
    public function index()
    {
        $this->assign('title', '微信信息概览')
            ->display();
    }

    /**
     * 微信菜单
     */
    public function menu()
    {
        $this->assign('title', '自定义菜单')
            ->assign('menus', D('Menus')->getMenus())
            ->display();
    }

    /**
     * 自动回复
     */
    public function reply()
    {
        $Reply = D('WxReply');
        $wx_appid = session('user.wx_appid');

        $this->assign('title', '自动回复')
            ->assign('subscribe', $Reply->info($wx_appid, 'subscribe', true))
            ->assign('auto', $Reply->info($wx_appid, 'auto', true))
            ->assign('keywords', $Reply->info($wx_appid, 'keyword', true))
            ->display();
    }

    /**
     * 用户管理
     */
    public function user()
    {
        $this->assign('title', '用户管理')
            ->display();
    }

    /**
     * 文章管理
     */
    public function article()
    {
        $this->assign('title', '文章管理')->display();
    }
}