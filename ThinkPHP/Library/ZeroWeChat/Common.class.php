<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2017-01-28
 * Time: 22:39
 * Desc:
 */

namespace ZeroWeChat;

/**
 * Class Common 通用类
 * @package ZeroWeChat
 */
class Common
{
    /**
     * @var string 错误描述
     */
    protected $error = '';

    /**
     * 获取错误描述信息
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
}