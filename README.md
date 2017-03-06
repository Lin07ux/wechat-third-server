## 微信公众号第三方服务器

该项目用于实现一个最基本的微信公众号第三方服务器开发的模板。

### 功能

- 微信用户访问的前端模块
- 后台管理模块：登陆、微信公众号菜单设置、微信自动回复设置等
- 微信服务器通讯模块：接收并解析微信服务器的推送信息、发送消息给微信服务器等

### 数据库

- 使用 MySQL 5.6
- 基本结构见`./SQL`目录

### Nginx 配置

```conf
server {
    listen       80;
    server_name  domain.com;
    root         /usr/share/nginx/html/domain.com/Public;

    charset      utf-8;

    access_log  /var/log/nginx/lin07ux.access.log  main;
    
    #  默认主页
    location / {
        index index.html index.htm index.php;

        try_files $uri /index.php/$uri;
    }

    # 管理员页面
    location = /admin {
        rewrite ^.*$ /admin.php last;
    }
    location ^~ /admin/ {
        rewrite ^\/admin\/(.*)$ /admin.php/$1 last;
    }
     
    # 执行 php 脚本
    location ~ .+\.php(/|$) {
        # fastcgi_pass   127.0.0.1:9000;
        fastcgi_pass   unix:/var/run/php-fpm/php-cgi.sock;
        fastcgi_index  index.php;
        include        fastcgi.conf;
        include        pathinfo.conf;
    }
        
    location ~ /\.ht {
        deny  all;
    }

    location ~* \.(gif|jpg|jpeg|png|ico)$ {                                            
        # expires     30d;
        # access_log  on;                                                  
    }
    
    location ~ .*\.(js|css)$ {
        # expires     30d;
        # access_log  off;
    }
}  
```

> 注1：将`server_name`的值改成自己的网站的域名。
> 注2：将`root`改成自己网站的根目录中的`Public/`文件夹的路径。


### Ajax 返回代码

- 0    操作成功
- 1    未登录或登录失效

- 10   参数错误

- 100  操作失败
- 101  获取数据失败
- 102  添加数据失败
- 103  更新数据失败
- 104  删除数据失败

- 150  上传文件失败

- 404  Not Found