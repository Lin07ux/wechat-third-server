<?php
return array(
    //'配置项'=>'配置值'


    // 数据库
    'DB_TYPE'    => 'mysql',   // 数据库类型
    'DB_HOST'    => '127.0.0.1', // 服务器地址
    'DB_NAME'    => 'wechat',    // 数据库名
    'DB_USER'    => 'root',      // 用户名
    'DB_PWD'     => '', // 密码
    'DB_PORT'    => 3306,        // 端口
    'DB_PREFIX'  => 'wts_',      // 数据库表前缀
    'DB_CHARSET' => 'utf8mb4',   // 数据库字符集
    'DB_PARAMS'  => [
        \PDO::ATTR_CASE => \PDO::CASE_NATURAL,  // 区分大小写
    ],


    // 文件上传路径
    'UPLOAD_PATH' => __ROOT__ . 'Uploads/',



    // 微信公众号信息
    // 这些信息均可从微信公众号后台中找到，路径如下：
    // 开发 --> 基本配置
    'WECHAT' => [
        'NAME'    => '零传媒服务号', // 公众号名称
        'APPID'   => 'wx61a4e3671f43b947', // 公众号 AppID
        'SECRET'  => 'd960b13a81a110213bb088b375e4af5c', // 公众号 AppSecret
        'TOKEN'   => 'zero_net_chair',  // 公众号 Token
        'ENCRYPT' => true,      // 消息加解密方式：false 明文模式  true 安全模式
        'AESKey'  => 'kPD80ijA96ISIO06EYcnQtjY7s5iTG482KHb0lDoWtX', // 公众号 EncodingAESKey
    ],
);