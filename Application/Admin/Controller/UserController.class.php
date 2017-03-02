<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2016-09-13
 * Time: 15:18
 * Desc: 用户控制器
 */

namespace Admin\Controller;

class UserController extends CommonController
{
    /**
     * 预操作: 对于非login/register/logout的操作都执行已登录验证
     */
    public function _initialize()
    {
        $unCheck = ['login', 'register', 'logout'];

        if (!in_array(strtolower(ACTION_NAME), $unCheck))
            parent::_initialize();
    }

    /**
     * 个人信息
     */
    public function index()
    {
         $this->assign('title', '个人信息')->display();
    }

    /**
     * 用户登录
     */
    public function login()
    {
        if (IS_GET) {
            $redirect = I('get.from') ? urldecode(I('get.from')) : U('Index/index');

            if ($this->checkLogin()) {
                redirect($redirect);
                exit();
            }

            $this->assign('redirect', $redirect)
                ->assign('title', '登录')
                ->display();

        } elseif (IS_POST) {

            if ($this->checkLogin()) {
                $res = ['code' => 0, 'msg' => '已登录'];
            } else {
                $info = D('Admin')->login(I('post.account'), $_POST['password']);

                if ($info) {
                    $info['expiry'] = time() + $this->login_expiry;
                    session('user', $info);
                    $res = ['code' => 0, 'msg' => '登录成功'];
                } else {
                    $res = ['code' => 1002, 'msg' => '登录失败！账号和密码信息不匹配！'];
                }
            }

            $this->ajaxReturn($res);
        }
    }

    /**
     * 注册 TODO
     */
    protected function register()
    {
        if (IS_GET) {
            $redirect = I('get.from') ? urldecode(I('get.form')) : '/';

            $this->assign('redirect', $redirect)
                ->assign('title', '注册')
                ->display('login');
        }
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        session('user', null);

        redirect(U('User/login'));
    }

    /**
     * 账号管理
     */
    public function account()
    {
        if (session('user.super_admin') <= 0) $this->send404();

        $this->assign('title', '账户管理')
            ->display();
    }

    /**
     * 修改密码
     */
    public function resetPwd()
    {
        if (!IS_POST) $this->send404();

        $user = session('user.id');
        $result = D('Admin')->resetPwd($user, $user, I('post.oldPwd'), I('post.newPwd'));

        if ($result) {
            $res = ['code' => 0, 'msg' => '密码更新成功',];
        } else {
            $res = ['code' => 1, 'msg' => '原始密码不正确'];
        }

        $this->ajaxReturn($res);
    }

    /**
     * 添加或更新用户信息
     */
    public function handle()
    {
        if (!IS_POST) $this->send404();

        $isAdd = !I('post.id');
        $Admin = D('Admin');
        $result = $Admin->addOrUpdate(session('user.id'), I('post.'));

        if ($result) {
            if ($isAdd) {
                $res = ['code' => 0, 'msg' => '添加用户成功', 'id' => $result];
            } else {
                $res = ['code' => 0, 'msg' => '修改用户信息成功'];
            }
        } else {
            $err = $Admin->getError();
            $res = ['code' => 1501, 'msg' => $err ?: '操作失败！请稍后重试！',];
        }

        $this->ajaxReturn($res);
    }

    /**
     * 删除用户
     */
    public function remove()
    {
        if (!IS_POST) $this->send404();

        $Admin = D('Admin');
        $result = $Admin->deleteUser(session('user.id'), I('post.id'));

        if ($result) {
            $res = ['code' => 0, 'msg' => '用户删除成功'];
        } else if (0 === $result) {
            $res = ['code' => 1003, 'msg' => '该用户不存在'];
        } else {
            $err = $Admin->getError();
            $res = ['code' => 1502, 'msg' => $err ?: '用户删除失败！请稍后重试'];
        }

        $this->ajaxReturn($res);
    }
}