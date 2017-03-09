<?php
namespace Wechat\Controller;

use Think\Controller;
use Think\Log;
use ZeroWeChat\Reply;
use ZeroWeChat\User;
use ZeroWeChat\Wechat;
use ZeroWeChat\XML;

class IndexController extends Controller
{
    /**
     * @var \Admin\Model\ReplyModel 微信回复模型实例
     */
    private $reply = null;

    /**
     * 微信服务器通讯接口
     */
    public function index()
    {
        // 验证是否是微信发过来的请求,不是则直接回复空
        if(! $this->checkSignature()) $this->sendEmpty();

        // 首次接入服务器的时候进行验证
        if (IS_GET) {
            echo $_GET["echostr"];
            exit();
        }

        /**
         * 将微信服务器推送过来的信息处理后生成数组
         * ToUserName   -> 开发者微信号
         * FromUserName -> 发送方帐号（一个OpenID）
         * CreateTime   -> 消息创建时间 （整型）
         * MsgType      -> 消息类型:text/image/voice/video/shortvideo/location/link/event
         * MsgId	    -> 消息id，64位整型
         */
        $msg = $this->processMsg();
        if (!$msg) $this->sendEmpty();

        // 根据推送消息的事件类型得到对应的回调方法名
        $msgType = ucfirst($msg['MsgType']);
        $callback = 'handle' . $msgType;

        // 如果是事件,则对应的处理方法为 handleEvent<EventType> 格式
        if ('Event' == $msgType) $callback .= ucfirst($msg['Event']);

        // 如果不存在回调方法则回复空
        if (!method_exists($this, $callback)) $this->sendEmpty();

        $this->reply = D('Admin/Reply');
        $response = $this->$callback($msg);

        if (!$response) $this->sendEmpty();

        // 生成回复消息的 XML 字符串
        $Reply = new Reply($msg['FromUserName'], $msg['ToUserName']);
        $reply = $Reply->genReplyXML($response);

        if (!$reply) $this->sendEmpty();

        Log::record('[生成的消息]'.$reply, 'DEBUG');

        // 如果需要加密则加密生成的 XML 内容
        if (C('WECHAT.ENCRYPT')) {
            // TODO
        }

        echo $reply;
    }

    /**
     * 验证服务器是否是微信的
     *
     * @return bool
     */
    private function checkSignature()
    {
        $tmpArr = [C('WECHAT.TOKEN'), $_GET["timestamp"], $_GET["nonce"]];
        sort($tmpArr, SORT_STRING);

        return sha1(implode($tmpArr)) == $_GET["signature"];
    }

    /**
     * 处理微信推送过来的信息
     *
     * @return array|bool
     */
    protected function processMsg()
    {
        $postStr = file_get_contents("php://input");

        // 如果设置了消息加密则需要进行解密
        if (C('WECHAT.ENCRYPT')) {
            // TODO
        }

        if (empty($postStr)) return false;

        return XML::parse($postStr);
    }

    /**
     * 获取公众号的 access_token
     *
     * @return bool|string
     */
    protected function getAccessToken()
    {
        $wechat = Wechat::getInstance(C('WECHAT.APPID'), C('WECHAT.SECRET'));

        return $wechat->getAccessToken();
    }

    /**
     * 回复空信息
     */
    protected function sendEmpty()
    {
        echo "";
        exit();
    }

    /**
     * 处理用户关注事件
     *
     * @param array $msg 推送过来的解密过的信息数组
     *
     * @return bool|array
     */
    protected function handleEventSubscribe(array $msg)
    {
        $openid = $msg['FromUserName'];

        // 获取该公众号的access_token以便获取用户信息
        if ($access_token = $this->getAccessToken()) {
            $User = new User($access_token);
            $info = $User->get($openid);

            // 获取用户信息成功则写入数据库中
            if ($info) D('Users')->syncUserInfo($info, false);
        }

        return $this->reply->getReply('subscribe');
    }

    /**
     * 处理用户取消关注事件
     *
     * @param array $msg 推送过来的解密过的信息数组
     *
     * @return null
     */
    protected function handleEventUnsubscribe(array $msg)
    {
        $openid = $msg['FromUserName'];
        D('Users')->unsubscribe($openid);

        return null;
    }

    /**
     * 处理用户点击菜单的事件
     *
     * @param array $msg 推送过来的解密过的信息数组
     *
     * @return bool|mixed
     */
    protected function handleEventClick(array $msg)
    {
        return $this->reply->getClickInfo($msg['EventKey'], true);
    }

    /**
     * 处理用户发送的文本信息
     *
     * @param array $msg 推送过来的解密过的信息数组
     *
     * @return bool|array
     */
    protected function handleText(array $msg)
    {
        $reply = $this->reply->getReply('keyword', $msg['Content']);

        if (!$reply) $reply = $this->autoReply();

        return $reply;
    }

    /**
     * 获取自动回复 当用户的输入没有对应的回复时使用
     *
     * @return array|bool
     */
    protected function autoReply()
    {
        return $this->reply->getReply('auto');
    }


    /**
     * 图片消息 TODO
     *
     * @param array $msg 推送来的消息数组
     */
    protected function msg_image($msg)
    {
        // PicUrl 图片链接
        // MediaId 图片消息媒体id，可以调用多媒体文件下载接口拉取数据。
    }

    /**
     * 音频消息 TODO
     *
     * @param array $msg 推送来的消息数组
     */
    protected function msg_voice($msg)
    {
        // MediaId 语音消息媒体id，可以调用多媒体文件下载接口拉取数据。
        // Format  语音格式，如amr，speex等
    }

    /**
     * 视频消息 TODO
     *
     * @param array $msg 推送来的消息数组
     */
    protected function msg_video($msg)
    {
        // MediaId 视频消息媒体id，可以调用多媒体文件下载接口拉取数据。
        // ThumbMediaId 视频消息缩略图的媒体id，可以调用多媒体文件下载接口拉取数据。
    }

    /**
     * 短视频消息 TODO
     *
     * @param array $msg 推送来的消息数组
     */
    protected function msg_shortvideo($msg)
    {
        // MediaId 视频消息媒体id，可以调用多媒体文件下载接口拉取数据。
        // ThumbMediaId 视频消息缩略图的媒体id，可以调用多媒体文件下载接口拉取数据。
    }

    /**
     * 位置信息 TODO
     *
     * @param array $msg 推送来的消息数组
     */
    protected function msg_location($msg)
    {
        // Location_X 地理位置维度
        // Location_Y 地理位置经度
        // Scale      地图缩放大小
        // Label      地理位置信息
    }

    /**
     * 链接信息 TODO
     *
     * @param array $msg 推送来的消息数组
     */
    protected function msg_link($msg)
    {
        // Title       消息标题
        // Description 消息描述
        // Url         消息链接
    }

    /**
     * 扫描事件 TODO
     *
     * @param array $msg 推送来的消息数组
     */
    private function msg_event_scan()
    {
        // 用户已关注时的事件推送
        // EventKey  事件KEY值，是一个32位无符号整数，即创建二维码时的二维码scene_id
        // Ticket    二维码的ticket，可用来换取二维码图片
    }

    /**
     * 位置事件 TODO
     *
     * @param array $msg 推送来的消息数组
     */
    private function msg_event_location()
    {
        // Latitude  地理位置纬度
        // Longitude 地理位置经度
        // Precision 地理位置精度
    }
}