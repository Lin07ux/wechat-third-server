<?php
class JSSDK {
    private $appId;
    private $appSecret;

    public function __construct($appId, $appSecret) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

    public function getSignPackage() {
        $jsapiTicket = $this->getJsApiTicket();

        // 注意 URL 一定要动态获取
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
          "appId"     => $this->appId,
          "nonceStr"  => $nonceStr,
          "timestamp" => $timestamp,
          "url"       => $url,
          "signature" => $signature,
          "rawString" => $string
        );
        return $signPackage;
    }

    public function addMaterial($file, $type)
    {
        $acc_token = $this->getAccessToken();
        $mate_url  = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token={$acc_token}&type={$type}";
        $data['media']    = '@'.$file;

        return $this->httpsSafePost($mate_url, $data);
    }

    /**
     * 自定义设置底部菜单
     * @param $menu 自定义菜单数据
     * @return mixed|string
     */
    public function createMenu($menu) {
        $acc_token = $this->getAccessToken();
        $menu_url  ="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$acc_token;
        $result    = $this->httpsPost($menu_url, $menu);

        return $result;
    }
    /**
     * 删除菜单
     */
    public function deleteMenu()
    {
        $acc_token = $this->getAccessToken();
        $menu_url  ="https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=".$acc_token;
        $result    = $this->httpGet($menu_url);

        return $result;
    }

    /**
     * 下载临时媒体文件
     */
    public function getMedia($media_id, $folder, $file)
    {
        $acc_token = $this->getAccessToken();
        $url  = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=".$acc_token.'&media_id='.$media_id;

        if (!file_exists($_SERVER['DOCUMENT_ROOT'].__ROOT__."/Upload/".$folder)) {
            mkdir($_SERVER['DOCUMENT_ROOT'].__ROOT__."/Upload/".$folder, 0777, true);
        }

        $targetName = __ROOT__.'/Upload/'.$folder.'/'.$file;
        //$fp = fopen($_SERVER['DOCUMENT_ROOT'].$targetName, 'wb'); // 打开写入

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        //curl_setopt($ch, CURLOPT_FILE, $fp); // 设置输出文件的位置，值是一个资源类型
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $data = curl_exec($ch);
        curl_close($ch);

        if (strpos($data, 'errcode') !== false)
            $res = json_decode($data, true);
        else {
            $fp  = fopen($_SERVER['DOCUMENT_ROOT'].$targetName, 'wb');
            $res = fwrite($fp, $data) ? $targetName : false;
            fclose($fp);
        }

        return $res;
    }

    /**
     * 获取用户基本信息
     * @param int $length
     * @return string
     */
    public function getUserInfo($openid)
    {
        $acc_token = $this->getAccessToken();
        $url  = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$acc_token.'&openid='.$openid.'&lang=zh_CN ';

        return json_decode( $this->httpGet($url), true );
    }


    /* 辅助方法 */

    // 获取随机字符串
    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
          $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    // 获取jssdk令牌
    private function getJsApiTicket() {
        // jsapi_ticket 放在 COMMON_PATH 目录中
        $JsApiPath = COMMON_PATH."jsapi_ticket.php";
        $data      = json_decode($this->get_php_file($JsApiPath));
        if ($data->expire_time < time()) {
            $accessToken = $this->getAccessToken();

            // 如果是企业号用以下 URL 获取 ticket
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->httpGet($url));
            $ticket = $res->ticket;
            if ($ticket) {
                $data->expire_time = time() + 6000;
                $data->jsapi_ticket = $ticket;
                $this->set_php_file($JsApiPath, json_encode($data));
            }
        } else {
            $ticket = $data->jsapi_ticket;
        }

        return $ticket;
    }
    // 获取access_token
    private function getAccessToken() {
        // access_token 放在 COMMON_PATH 目录
        $AccTokenpath = COMMON_PATH."access_token.php";
        $data = json_decode($this->get_php_file($AccTokenpath));
        if ($data->expire_time < time()) {
            // 如果是企业号用以下URL获取access_token
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = json_decode($this->httpGet($url));
            $access_token = $res->access_token;
            if ($access_token) {
                $data->expire_time = time() + 6000;
                $data->access_token = $access_token;
                $this->set_php_file($AccTokenpath, json_encode($data));
            }
        } else {
            $access_token = $data->access_token;
        }
        return $access_token;
    }
    // curl请求:get/post/安全post
    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }
    private function httpsPost($url, $data) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $info = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }

        curl_close($curl);
        return $info;
    }
    private function httpsSafePost($url, $data) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $info = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }

        curl_close($curl);
        return $info;
    }
    // 默认将 access_token.php 文件 和 jsapi_ticket.php 放在 COMMON_PATH 目录中
    private function get_php_file($filename) {
        return trim(substr(file_get_contents($filename), 15));
    }
    private function set_php_file($filename, $content) {
        $fp = fopen($filename, "w");
        fwrite($fp, "<?php exit();?>" . $content);
        fclose($fp);
    }
}

