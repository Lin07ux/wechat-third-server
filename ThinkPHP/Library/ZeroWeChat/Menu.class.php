<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2017-01-28
 * Time: 21:51
 * Desc: 微信自定义菜单
 */

namespace ZeroWeChat;

use Think\Log;

class Menu extends Common
{
    /**
     * 发布菜单的 API
     */
    const PUBLISH_URL = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=%s';

    /**
     * 删除菜单的 API
     */
    const DELETE_URL = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=%s';

    /**
     * @var string 微信公众号的 access_token
     */
    private $access_token;

    /**
     * @var array 菜单类型
     */
    protected $type = array(
        'parent' => 0,  // 父级菜单
        'view' => 1,    // 跳转网址
        'click' => 2,   // 回复消息
    );

    /**
     * Menu constructor.
     * @param $access_token
     */
    public function __construct($access_token)
    {
        $this->access_token = $access_token;
    }

    /**
     * 发布自定义菜单
     *
     * @param array $data 菜单数据
     *
     * @return bool
     */
    public function publish(array $data)
    {
        $menus = $this->formatData($data);
        if (false === $menus) return false;

        if (!count($menus)) return $this->deleteMenus();

        $menus = json_encode(['button' => $menus], JSON_UNESCAPED_UNICODE);
        $url = sprintf(self::PUBLISH_URL, $this->access_token);

        Log::record('【菜单数据】'.$menus, 'DEBUG');

        $result = Util::httpsPost($url, $menus);
        if (!$result) {
            return false;
        } elseif (array_key_exists('errcode', $result) && $result['errcode'] > 0) {
            Log::record('[菜单发布失败]'.$result['errcode'].': '.$result['errmsg'], 'ERR');
            $this->error = $result['errmsg'];

            return false;
        }

        return true;
    }

    /**
     * 格式化菜单数据为微信指定格式
     *
     * @param array $data 菜单数据
     *
     * @return array|bool
     */
    protected function formatData(array $data)
    {
        if (!is_array($data)) {
            $this->error = '缺少菜单数据。请先设置菜单。';
            return false;
        } elseif (!count($data)) {
            return [];
        }

        $menus = [];

        foreach ($data as $d) {
            $tmp = ['name' => $d['name']];

            switch ((int)$d['type']) {
                case $this->type['parent']:  // 父级菜单
                    if (!isset($d['sub_button'])) $d['sub_button'] = [];
                    $sub = $this->formatData($d['sub_button']);
                    if ($sub) {
                        $tmp['sub_button'] = $sub;
                    } else {
                        return false;
                    }
                    break;
                case $this->type['view']:    // 跳转网页菜单
                    $tmp['type'] = 'view';
                    $tmp['url'] = $d['url'];
                    break;
                case $this->type['click']:   // 点击回复菜单
                    $tmp['type'] = 'click';
                    $tmp['key'] = $d['key'];
                    break;
                default :
                    return false;
            }

            $menus[] = $tmp;
        }

        return $menus;
    }

    /**
     * 删除菜单(全部)
     *
     * @return bool
     */
    protected function deleteMenus()
    {
        $url = sprintf(self::DELETE_URL, $this->access_token);
        $result = Util::httpGet($url);

        if (!$result) {
            return false;
        } elseif (array_key_exists('errcode', $result) && $result['errcode'] > 0) {
            Log::record('[菜单删除失败]'.$result['errcode'].': '.$result['errmsg'], 'ERR');
            $this->error = $result['errmsg'];

            return false;
        }

        return true;
    }
}