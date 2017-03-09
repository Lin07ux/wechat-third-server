<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2016-09-20
 * Time: 23:33
 * Desc: 生成回复信息
 */

namespace ZeroWeChat;


class Reply
{
    /**
     * @var string 发送消息的公众号APPID
     */
    private $from_user;

    /**
     * @var string 接收消息的用户的openid
     */
    private $to_user;

    /**
     * Reply constructor.
     *
     * @param string $to   接收者openid(用户)
     * @param string $from 发送者appid(或原始ID)
     */
    public function __construct($to, $from)
    {
        $this->to_user   = $to;
        $this->from_user = $from;
    }

    /**
     * 生成回复用户的 XML 消息
     *
     * @param array|string $data 回复的内容数据
     *
     * @return string
     */
    public function genReplyXML($data)
    {
        $type = is_array($data) && isset($data['msg_type'])
            ? strtolower($data['msg_type'])
            : 'text';

        if (!method_exists($this, $type)) return false;

        $reply = $this->$type($data);
        if (!$reply) return false;

        $common = [
            'FromUserName' => $this->from_user,
            'ToUserName' => $this->to_user,
            'CreateTime' => time(),
        ];
        $reply = array_merge($common, $reply);

        return XML::build($reply);
    }

    /**
     * 组装文本回复的数组
     *
     * @param array|string $data 回复的信息数组或文本
     *
     * @return array
     */
    protected function text($data)
    {
        if (is_array($data)) {
            $content = $data['content'];
        } elseif (is_string($data)) {
            $content = $data;
        } else {
            return false;
        }

        return [
            'MsgType' => 'text',
            'Content' => $content,
        ];
    }

    /**
     * 组装图文消息回复
     *
     * @param array $data 图文消息的内容数组
     *
     * @return array|bool
     */
    protected function news(array $data)
    {
        $news = $data['news'];
        $count = count($news);

        if ($count > 8 || $count <= 0) return false;

        $domain = get_domain();
        $articles = [];
        foreach ($news as $key => $new) {
            $articles[] = [
                'Title' => $new['title'],
                'Description' => $new['desc'],
                'PicUrl' => $domain . ($key > 0 ? $new['thumb'] : $new['cover']),
                'Url' => $new['link'],
            ];
        }

        return [
            'MsgType' => 'news',
            'ArticleCount' => $count,
            'Articles' => $articles,
        ];
    }
}