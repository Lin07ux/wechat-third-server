<?php
namespace Wechat\Controller;

use Think\Controller;

class IndexController extends Controller
{
    const TOKEN      = "huataiyimei";
    const TYPE_FOCUS = 0;
    const TYPE_AUTO  = 1;
    const TYPE_KEY   = 2;

    /**
     * 存放将微信服务器推送过来的信息处理后生成的对象
     * ToUserName   -> 开发者微信号
     * FromUserName -> 发送方帐号（一个OpenID）
     * CreateTime   -> 消息创建时间 （整型）
     * MsgType      -> 消息类型:text/image/voice/video/shortvideo/location/link/event
     * MsgId	    -> 消息id，64位整型
     */
    private $msg = null;

    // 存放数据库中设置的回复信息
    private $reply = null;

    // 存放返回信息
    private $response = null;



    public function index(){
        // 仅在启用微信第三方服务器的时候使用一次,之后就要注释掉
        // $this->valid();
        // exit();

        // 验证是否是微信发过来的请求,不是则直接回复空
        if(! $this->checkSignature())
            $this->reply_empty();


        // 处理微信服务器的推送信息,写入到$this->msg
        $this->processMsg();

        // 根据推送消息的事件类型调用方法,获取数据,写入到$this->reply
        $msg_fun = 'msg_' . strtolower( trim($this->msg->MsgType) );
        $this->$msg_fun();

        // 如果没有设置对应的关键词回复,则获取自动回复
        if (! $this->reply)
            $this->auto_reply();

        // 如果没有对应的回复,也没有设置自动回复,则直接回复空信息
        if (! $this->reply) {
            $this->reply_empty();
        } else {
            // 根据回复类型调用相应的函数,生成返回数据,写入到$this->response
            $reply_fun   = 'reply_' . strtolower( trim($this->reply['MsgType']) );
            $this->$reply_fun();
        }

        // $this->show($this->response, 'utf-8', 'text/xml');
        echo $this->response;
        exit();
    }

    // 与微信服务器进行互相验证
    protected function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }

        exit();
    }
    // 验证服务器是否是微信的
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce     = $_GET["nonce"];

        $tmpArr = array(self::TOKEN, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }

    // 处理微信推送过来的信息
    protected function processMsg()
    {
        //$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postStr = file_get_contents("php://input");

        //extract post data
        if (!empty( $postStr )) {
            /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
                   the best way is to check the validity of xml by yourself */
            libxml_disable_entity_loader(true);
            $this->msg = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            //$this->msg['fromUsername'] = $postObj->FromUserName;
        } else {
            echo "";
            exit;
        }
    }


    /**********************************************/
    /* 根据消息内容,获取数据库中设置的回复信息 */
    /**********************************************/

    // 获取自动回复,当用户的输入没有对应的回复时使用
    protected function auto_reply()
    {
        $where   = array(
            'type'    => self::TYPE_AUTO,
            'status'  => 1,  // 启用状态
        );

        $this->reply = M('reply')->where($where)->find();
    }

    // 文本消息
    protected function msg_text()
    {
        // Content -> 文本消息内容
        $content = filter_var($this->msg->Content, FILTER_SANITIZE_MAGIC_QUOTES);

        if ('1897' === $content) {
            $winners = M()->table('__LOTTERY_RESULT__ a, __OPENID__ b')->field('a.prize, b.nickname')
                ->where('b.openid=a.openid AND a.prize IS NOT NULL')->select();
            $third  = "三等奖: ";
            $second = "\n二等奖: ";
            $first  = "\n一等奖: ";
            foreach ($winners as $val) {
                if (1 == $val['prize']) {
                    $first  .= "\n" . $val['nickname'];
                } elseif (2 == $val['prize']) {
                    $second .= "\n" . $val['nickname'];
                } elseif (3 == $val['prize']) {
                    $third  .= "\n" . $val['nickname'];
                }
            }

            $res = array(
                'MsgType'=>'text',
                'content'=>$third . "\n" . $second . "\n" . $first,
            );
        } else {
            $where   = array(
                'keyword' => $content,
                'type'    => self::TYPE_KEY,
                'status'  => 1,  // 启用状态
            );
            $res = M('reply')->where($where)->find();
        }

        // 找到对应的回复记录,写入到$this->reply
        $this->reply = $res;
    }

    // 图片消息 TODO
    protected function msg_image()
    {
        // PicUrl 图片链接
        // MediaId 图片消息媒体id，可以调用多媒体文件下载接口拉取数据。
    }

    // 音频消息 TODO
    protected function msg_voice()
    {
        // MediaId 语音消息媒体id，可以调用多媒体文件下载接口拉取数据。
        // Format  语音格式，如amr，speex等
    }

    // 视频消息 TODO
    protected function msg_video()
    {
        // MediaId 视频消息媒体id，可以调用多媒体文件下载接口拉取数据。
        // ThumbMediaId 视频消息缩略图的媒体id，可以调用多媒体文件下载接口拉取数据。
    }

    // 短视频消息 TODO
    protected function msg_shortvideo()
    {
        // MediaId 视频消息媒体id，可以调用多媒体文件下载接口拉取数据。
        // ThumbMediaId 视频消息缩略图的媒体id，可以调用多媒体文件下载接口拉取数据。
    }

    // 位置信息 TODO
    protected function msg_location()
    {
        // Location_X 地理位置维度
        // Location_Y 地理位置经度
        // Scale      地图缩放大小
        // Label      地理位置信息
    }

    // 链接信息 TODO
    protected function msg_link()
    {
        // Title       消息标题
        // Description 消息描述
        // Url         消息链接
    }

    // 事件消息
    protected function msg_event()
    {
        // event  事件类型,需要统一转成小写
        // subscribe(订阅)、unsubscribe(取消订阅)
        // scan(扫描)、LOCATION(上报地理位置)
        // click(点击菜单拉取消息时的事件推送)
        // view(点击菜单跳转链接时的事件推送)

        // 根据事件类型调用对应的方法,生成回复信息,存放在 $this->reply
        $event_fun = 'msg_event_' . strtolower( trim($this->msg->Event) );
        $this->$event_fun();
    }

    // 关注事件
    private function msg_event_subscribe()
    {
        // 用户关注后将openid写入openid数据表中
        $openid = "{$this->msg->FromUserName}";
        D('Openid')->addUser($openid);

        $where   = array(
            'type'    => self::TYPE_FOCUS, // 关注回复
            'status'  => 1,  // 启用状态
        );
        // 查找关注回复消息
        $this->reply = M('reply')->where($where)->find();
    }

    // 取消关注事件
    private function msg_event_unsubscribe()
    {
        // 将用户的openid重新处理下(设置state为0)
        $openid = "{$this->msg->FromUserName}";
        D('Openid')->removeUser($openid);

        // 不回复消息
        $this->reply_empty();
    }

    // 扫描事件 TODO
    private function msg_event_scan()
    {
        // 用户已关注时的事件推送
        // EventKey  事件KEY值，是一个32位无符号整数，即创建二维码时的二维码scene_id
        // Ticket    二维码的ticket，可用来换取二维码图片
    }

    // 位置事件 TODO
    private function msg_event_location()
    {
        // Latitude  地理位置纬度
        // Longitude 地理位置经度
        // Precision 地理位置精度
    }

    // 点击菜单拉取消息事件
    private function msg_event_click()
    {
        // EventKey  与自定义菜单接口中KEY值对应,
        // 目前设置为回复消息的ID,即从reply表中寻找

        $rid = "{$this->msg->EventKey}";
        $where   = array(
            'id'     => $rid,
            'type'   => self::TYPE_KEY, // 关注回复
            'status' => 1,  // 启用状态
        );
        // 查找关注回复消息
        $this->reply = M('reply')->where($where)->find();
    }

    // 点击菜单跳转链接事件
    private function msg_event_view()
    {
        // EventKey  设置的跳转URL

        // 不回复任何消息
        $this->reply_empty();
    }


    /****************************************************/
    /* 回复 */
    /****************************************************/

    // 回复空信息
    protected function reply_empty()
    {
        echo "";
        exit();
    }

    // 回复文本消息
    protected function reply_text()
    {
        $tpl = "<xml>
                  <ToUserName><![CDATA[%s]]></ToUserName>
                  <FromUserName><![CDATA[%s]]></FromUserName>
                  <CreateTime>%s</CreateTime>
                  <MsgType><![CDATA[text]]></MsgType>
                  <Content><![CDATA[%s]]></Content>
                </xml>";

        $this->response = sprintf($tpl,
            $this->msg->FromUserName,
            $this->msg->ToUserName,
            time(),
            $this->reply['content']
        );
    }

    // 回复图片消息
    protected function reply_image()
    {
        $tpl = "<xml>
                  <ToUserName><![CDATA[%s]]></ToUserName>
                  <FromUserName><![CDATA[%s]]></FromUserName>
                  <CreateTime>%s</CreateTime>
                  <MsgType><![CDATA[image]]></MsgType>
                  <Image>
                    <MediaId><![CDATA[%s]]></MediaId>
                  </Image>
                </xml>";

        $this->response = sprintf($tpl,
            $this->msg->FromUserName,
            $this->msg->ToUserName,
            time(),
            $this->reply['MediaId']
        );
    }

    // 回复音频消息 TODO
    protected function reply_voice()
    {
        //
    }

    // 回复视频消息 TODO
    protected function reply_video()
    {
        //
    }

    // 回复音乐消息 TODO
    protected function reply_music()
    {
        //
    }

    // 回复图文消息 - 单条图文
    protected function reply_news()
    {
        $tpl = "<xml>
                  <ToUserName><![CDATA[%s]]></ToUserName>
                  <FromUserName><![CDATA[%s]]></FromUserName>
                  <CreateTime>%s</CreateTime>
                  <MsgType><![CDATA[news]]></MsgType>
                  <ArticleCount>1</ArticleCount>
                  <Articles>
                    <item>
                      <Title><![CDATA[%s]]></Title>
                      <Description><![CDATA[%s]]></Description>
                      <PicUrl><![CDATA[%s]]></PicUrl>
                      <Url><![CDATA[%s]]></Url>
                    </item>
                  </Articles>
                </xml>";

        $this->response = sprintf($tpl,
            $this->msg->FromUserName,
            $this->msg->ToUserName,
            time(),
            $this->reply['title'],
            $this->reply['desc'],
            C('HOST').$this->reply['pic'],
            $this->reply['link'] . 'oid/' . $this->msg->FromUserName . '/k/' . $this->reply['id'] . '/p/' . $this->reply['MediaId']
        );
    }

    // 回复图文-文章消息 - 单条图文
    protected function reply_article()
    {
        $where   = array(
            'id'=>$this->reply['content'],
            'lock'=>0,
        );
        $article = M('article')->where($where)->find();
        if (!$article) {
            $this->reply['content'] = '该文章无法已被冻结,无法查阅';
            $this->reply_text();
        } else {
            $tpl = "<xml>
                  <ToUserName><![CDATA[%s]]></ToUserName>
                  <FromUserName><![CDATA[%s]]></FromUserName>
                  <CreateTime>%s</CreateTime>
                  <MsgType><![CDATA[news]]></MsgType>
                  <ArticleCount>1</ArticleCount>
                  <Articles>
                    <item>
                      <Title><![CDATA[%s]]></Title>
                      <Description><![CDATA[%s]]></Description>
                      <PicUrl><![CDATA[%s]]></PicUrl>
                      <Url><![CDATA[%s]]></Url>
                    </item>
                  </Articles>
                </xml>";

            $link = C('HOST').'/article/read/id/'.$article['id'].'/openid/'.$this->msg->FromUserName;

            $this->response = sprintf($tpl,
                $this->msg->FromUserName,
                $this->msg->ToUserName,
                time(),
                $article['title'],
                $article['desc'],
                C('HOST').$this->reply['pic'],
                $link
            );
        }
    }
}