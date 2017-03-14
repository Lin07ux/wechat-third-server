<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2017-03-06
 * Time: 09:14
 * Desc: 自定义函数集
 */

/**
 * 获取当前域名
 *
 * @return string
 */
function get_domain () {
    // 协议
    $protocol = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443'
        ? 'https://' : 'http://';

    // 域名
    $host = isset($_SERVER['HTTP_HOST'])
        ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];

    return $protocol . $host;
}

/**
 * 获取当前 url
 *
 * @return string
 */
function get_current_url () {
    // 协议
    $protocol = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443'
        ? 'https://' : 'http://';

    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

    return $protocol . $host . __SELF__;
}