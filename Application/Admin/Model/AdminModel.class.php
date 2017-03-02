<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2016-11-21
 * Time: 13:28
 * Desc:
 */

namespace Admin\Model;

use Think\Model;

/**
 * Class AdminModel 后台管理员模型
 * @package Admin\Model
 */
class AdminModel extends Model
{
    /**
     * @var int 超级管理员标志的值
     */
    CONST SUPER_ADMIN = 1;

    /**
     * @var array 自动验证: 用户姓名、账号、密码必须填写,而且账号需唯一
     */
    protected $_validate = array(
        ['name', 'require', '用户姓名必须填写'],
        ['account', 'require', '用户登录账号必须填写'],
        ['account', '', '登录账号已存在！请重新设置', 0, 'unique'],
        ['password', 'require', '登录密码必须填写'],
    );

    /**
     * @var array 自动完成: 加密用户密码
     */
    protected $_auto = array(
        ['password', 'encryptPwd', 3, 'callback'],
    );

    /**
     * 用户登录,登录成功返回用户基本信息
     *
     * @param string $account  登录账户
     * @param string $password 登录密码
     *
     * @return bool|mixed
     */
    public function login($account, $password)
    {
        $where = ['account' => $account, 'password' => $this->encryptPwd($password)];

        $info = $this->field('id,name,super_admin')->where($where)->find();
        if ($info) {
            $info['super_admin'] = self::SUPER_ADMIN === (int)$info['super_admin'];
        }

        return $info;
    }

    /**
     * 加密账户密码
     *
     * @param string $password 账户登录的密码
     *
     * @return string
     */
    protected function encryptPwd($password)
    {
        return sha1(md5($password));
    }

    /**
     * 重置用户密码
     *
     * @param int    $user 用户 ID
     * @param int    $id   待修改密码的用户 ID
     * @param string $old  原始密码
     * @param string $new  新密码
     *
     * @return bool
     */
    public function resetPwd($user, $id, $old, $new)
    {
        // 如果修改的不是自己的账号的密码,而且也不是超级管理员
        // 则无法修改密码
        if ((int)$user !== (int)$id && !$this->isSuperAdmin($user)) {
            $this->error = '无权修改该账户密码';
            return false;
        }

        return $this->where(['id' => $id, 'password' => $this->encryptPwd($old)])
            ->save([
                'password' => $this->encryptPwd($new),
                'upd_time' => date('Y-m-d H:i:s')
            ]);
    }

    /**
     * 获取所有的用户
     *
     * @return mixed
     */
    public function getAllUsers()
    {
        return $this->field('id,name,account,super_admin')
            ->order('super_admin desc, id asc')
            ->select();
    }

    /**
     * 判断某个管理员是否是超级管理员
     *
     * @param int $user 管理员ID
     *
     * @return bool
     */
    public function isSuperAdmin($user)
    {
        $where = ['id' => $user, 'super_admin' => self::SUPER_ADMIN];

        return (bool)$this->where($where)->getField('id');
    }

    /**
     * 添加或者更新用户信息
     *
     * @param int   $user 操作用户的 ID
     * @param array $data 用户数据
     *
     * @return bool|mixed
     */
    public function addOrUpdate($user, array$data)
    {
        if (!$this->isSuperAdmin($user)) {
            $this->error = '无权添加或修改用户信息';
            return false;
        }

        if (!$this->create($data)) return false;

        return $data['id'] ? false !== $this->save() : $this->add();
    }

    /**
     * 删除用户
     *
     * @param int $user 超级管理员ID
     * @param int $id   用户ID
     *
     * @return bool|mixed
     */
    public function deleteUser($user, $id)
    {
        if ($user == $id) {
            $this->error = '无法删除您正在使用的账户';
            return false;
        }

        if (!$this->isSuperAdmin($user)) {
            $this->error = '无权限添加或修改用户信息';
            return false;
        }

        return $this->where(['id' => $id])->delete();
    }
}