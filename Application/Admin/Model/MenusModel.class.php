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
     * 获取全部的菜单信息
     *
     * @param bool   $forReply 是否用于微信回复接口
     *
     * @return array
     */
    public function menus($forReply = false)
    {
        $field = $forReply
            ? 'id,name,type,`view` as `url`,`reply` as `key`'
            : 'id,ordering,name,type,view,reply';

        // 查找所有一级菜单, 最多只有三个
        $menus = $this->field($field)
            ->where(['parent' => ['exp', 'is NULL']])
            ->order('ordering asc, id asc')
            ->limit(3)->select();

        if (!$menus) return [];

        foreach ($menus as &$m) {
            if ($m['type'] == $this->type['parent']) {
                // 如果是父级菜单则获取其子菜单,最多5个子菜单
                $sub_button = $this->field($field)
                    ->where(['parent' => $m['id']])
                    ->order('ordering asc, id asc')
                    ->limit(5)->select();

                $m['sub_button'] = $sub_button ?: [];

            } elseif ($m['type'] == $this->type['reply'] && !$forReply) {
                // 如果是回复菜单则获取其回复的信息
                $m['reply'] = D('Reply')->getClickInfo($m['reply']);
            }
        }

        return $menus;
    }

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

        // 如果是回复类型的菜单则需要先创建回复信息
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
            } else {
                $menuData['reply'] = $replyData['id'];
            }

            $result = $this->saveMenu($menuData);
            $result ? $this->commit() : $this->rollback();

            return $result;
        }

        unset($menuData['reply']);

        return $this->saveMenu($menuData);
    }

    /**
     * 执行添加或更新菜单的操作
     *
     * @param array $data 写入到数据库中的菜单信息
     *
     * @return bool|mixed
     */
    protected function saveMenu(array $data)
    {
        if (!isset($data['id']) || $data['id'] <= 0) {
            return $this->add($data);
        }

        return false !== $this->save($data);
    }

    /**
     * 获取菜单设置的回复消息
     *
     * @param int $id 菜单 ID
     *
     * @return bool|mixed
     */
    public function getMenuReply($id)
    {
        $where = ['id' => $id, 'type' => $this->type['reply']];
        $replyId = $this->where($where)->getField('reply');
        if (!$replyId) {
            $this->error = '菜单回复信息不存在';
            return false;
        }

        $reply = D('Reply')->getClickInfo($replyId);
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
     * @param int $id 菜单的ID
     *
     * @return bool
     */
    public function remove($id)
    {
        $where = ['id' => $id];
        $info = $this->field('id, type')->where($where)->find();

        if (!$info) {
            $this->error = '该菜单不存在';
            return false;
        }

        // 判断是否是父级菜单,是的话就要删除对应的子菜单,否则是直接删除
        if ($info['type'] == $this->type['parent']) {
            $this->startTrans();

            $sub = $this->where(['parent' => $id])->delete();
            if ($sub !== false && $this->where($where)->delete()) {
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