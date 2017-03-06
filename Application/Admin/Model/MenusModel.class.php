<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2017-01-25
 * Time: 23:41
 * Desc:
 */

namespace Admin\Model;

use Think\Model;

class MenusModel extends Model
{
    /**
     * @var array 菜单类型
     */
    protected $type = array(
        'parent' => 0,  // 父级菜单
        'view' => 1,    // 跳转网址
        'reply' => 2,   // 回复消息
    );

    /**
     * @var array 自动验证
     */
    protected $_validate = array(
        ['ordering', 'number', '菜单次序需为数值', 1],
        ['name', 'require', '请设置菜单名称', 1],
        ['view', 'url', '请设置菜单的跳转网址', 1, '', 4],
        ['reply', 'is_array', '请设置点击菜单后的回复信息', 1, 'function', 5],
    );

    /**
     * 检查菜单数据
     *
     * @param array $data 菜单的数据信息
     *
     * @return bool|mixed
     */
    protected function checkData(array $data)
    {
        if (!isset($data['type'])) {
            $this->error = '请设置菜单类型';
            return false;
        }

        $type = (int)$data['type'];
        if (!in_array($type, $this->type)) {
            $this->error = '菜单类型不正确';
            return false;
        }

        return $this->create($data, $type + 3);
    }

    /**
     * 添加或更新菜单
     *
     * @param array $data 菜单信息
     *
     * @return bool|mixed
     */
    public function setMenu(array $data)
    {
        $menuData = $this->checkData($data);
        if (!$menuData) return false;

        // 如果是回复类型的菜单,则需要先创建回复信息
        if ($menuData['type'] == $this->type['reply']) {
            $replyData = $data['reply'];

            $this->startTrans();

            $Reply = D('Reply');
            $reply = $Reply->setClickReply($replyData);
            if (!$reply) {
                $this->rollback();
                $this->error = $Reply->getError();
                return false;
            }

            if (!isset($replyData['id']) || $replyData['id'] <= 0) {
                $menuData['reply'] = $reply;
            }

            $result = $this->doSet($menuData);
            $result ? $this->commit() : $this->rollback();

            return $result;
        } else {
            return $this->doSet($menuData);
        }
    }

    /**
     * 执行添加或更新菜单的操作
     *
     * @param array $data 写入到数据库中的菜单信息
     *
     * @return bool|mixed
     */
    protected function doSet(array $data)
    {
        if (!isset($data['id']) || $data['id'] <= 0) {
            return $this->add($data);
        }

        return false !== $this->save($data);
    }

    /**
     * 获取全部的菜单信息
     *
     * @param bool   $forReply 是否用于微信回复接口
     *
     * @return array
     */
    public function getMenus($forReply = false)
    {
        $field = $forReply
            ? 'id,name,type,`view` as `url`,`reply` as `key`'
            : 'id,ordering,name,type,view,reply';

        $menus = $this->field($field)
            ->where(['parent' => ['exp', 'is NULL']])
            ->order('ordering asc, id asc')
            ->select();

        if (!$menus) return [];

        foreach ($menus as &$m) {
            if ($m['type'] == $this->type['parent']) {

                $m['sub_button'] = $this->field($field)
                    ->where(['wx_appid' => $wx_appid, 'parent' => $m['id']])
                    ->order('ordering asc, id asc')
                    ->select();

                if (!$m['sub_button']) $m['sub_button'] = [];

            } elseif ($m['type'] == $this->type['reply'] && !$forReply) {

                $m['reply'] = D('Reply')->getClickInfo($m['reply'], $wx_appid);
            }
        }

        return $menus;
    }

    /**
     * 获取菜单设置的回复消息
     *
     * @param int    $id
     * @param string $wx_appid 微信公众号APPID
     *
     * @return bool|mixed
     */
    public function getMenuReply($id, $wx_appid)
    {
        $where = ['id' => $id, 'wx_appid' => $wx_appid, 'type' => $this->type['reply']];
        $replyId = $this->where($where)->getField('reply');
        if (!$replyId) {
            $this->error = '菜单回复信息不存在';
            return false;
        }

        $reply = D('WxReply')->getClickInfo($replyId, $wx_appid);
        if (!$reply) {
            $this->error = '菜单回复信息不存在';
            return false;
        }

        $reply['id'] = $replyId;

        return $reply;
    }

    /**
     * 删除菜单
     *
     * @param int    $id       菜单的ID
     * @param string $wx_appid 微信公众号APPID
     *
     * @return bool
     */
    public function remove($id, $wx_appid)
    {
        $where = ['id' => $id, 'wx_appid' => $wx_appid];
        $info = $this->field('id, type')->where($where)->find();

        if (!$info) {
            $this->error = '该菜单不存在';
            return false;
        }

        // 判断是否是父级菜单,是的话就要删除对应的子菜单,否则是直接删除
        if ($info['type'] == $this->type['parent']) {
            $this->startTrans();

            $sub = $this->where(['wx_appid'=>$wx_appid, 'parent'=>$id])->delete();
            if ($sub && $this->where($where)->delete()) {
                $this->commit();
                return true;
            }

            $this->rollback();
            return false;

        } else {
            return false !== $this->where($where)->delete();
        }
    }
}