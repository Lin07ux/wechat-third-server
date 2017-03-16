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
    protected $actions = [
        'index' => '微信信息概览',
        'menu'  => '自定义菜单',
        'reply' => '自动回复',
        'user'  => '用户管理',
        'article' => '文章管理',
        'list'  => '文章列表',
    ];
}