DROP INDEX `unique_index_users_openid` ON `wts_users`;
DROP INDEX `unique_index_users_phone` ON `wts_users`;

DROP TABLE `wts_admin`;
DROP TABLE `wts_users`;

CREATE TABLE `wts_admin` (
	`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '管理员 ID',
	`account` varchar(255) NOT NULL COMMENT '登录账户',
	`password` varchar(255) NOT NULL COMMENT '登录密码 sha1',
	`name` varchar(255) NOT NULL COMMENT '用户姓名',
	`super_admin` tinyint(1) UNSIGNED NOT NULL COMMENT '是否是超级管理员(1是，0不是)',
	`crt_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
	`upd_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
	PRIMARY KEY (`id`)
)
	COMMENT = '管理员用户表';

CREATE TABLE `wts_users` (
	`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户的 ID',
	`openid` char(28) NOT NULL COMMENT '微信openid',
	`name` varchar(255) NULL COMMENT '用户姓名',
	`phone` varchar(20) NULL COMMENT '用户手机号码',
	`nickname` varchar(255) NOT NULL COMMENT '微信昵称',
	`subscribe` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户是否关注(0未关注，1关注)',
	`sex` enum('0','1','2') NOT NULL COMMENT '用户性别(0未知，1男性，2女性)',
	`country` varchar(255) NULL COMMENT '用户所在国家',
	`province` varchar(255) NULL COMMENT '用户所在省份',
	`city` varchar(255) NULL COMMENT '用户所在城市',
	`language` varchar(255) NULL COMMENT '用户所用语言',
	`headimgurl` varchar(255) NULL COMMENT '微信头像',
	`subscribe_time` int(11) UNSIGNED NULL COMMENT '用户关注的时间',
	`unionid` char(50) NULL COMMENT '用户的 unionid',
	`remark` varchar(255) NULL COMMENT '公众号运营者对粉丝的备注',
	`groupid` int(11) UNSIGNED NULL COMMENT '用户所在的分组ID',
	`tagid_list` varchar(255) NULL COMMENT '用户被打上的标签ID列表',
	`privilege` varchar(255) NULL COMMENT '用户特权信息(json数据)',
	`crt_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
	`upd_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
	PRIMARY KEY (`id`) ,
	UNIQUE INDEX `unique_index_users_openid` (`openid` ASC) COMMENT 'openid 保持唯一' ,
	UNIQUE INDEX `unique_index_users_phone` (`phone` ASC) COMMENT '用户手机号唯一索引'
)
	COMMENT = '用户表';

