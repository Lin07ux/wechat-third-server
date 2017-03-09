<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2017-01-16
 * Time: 15:40
 * Desc:
 */

namespace Admin\Model;

use Think\Model;

class ReplyModel extends Model
{
    /**
     * @var array 回复类型
     */
    protected $replyType = [
        'subscribe' => 0,  // 关注回复
        'auto' => 1,       // 自动回复
        'keyword' => 2,    // 关键字回复
        'click' => 3,      // 菜单点击回复
    ];

    /**
     * @var array 消息类型
     */
    protected $msgType = [
        'text' => 0,  // 文本消息
        'image' => 1,  // 图片消息
        'news' => 2,  // 图文消息
    ];

    /**
     * @var array 自动验证
     */
    protected $_validate = array(
        ['content', 'require', '请填写回复的文本内容', 1, '', 4],
        ['news', 'require', '请设置回复的图文消息', 1, '', 6],
    );

    /**
     * 检查数据
     * 首先检查消息类别是否是设置了，且是否是预设类别
     * 不是的话就设置错误信息，并返回false
     * 然后根据消息类别进行数据验证
     *
     * @param array $data 进行操作的数据
     *
     * @return bool|mixed
     */
    protected function checkData(array $data)
    {
        // 检查回复类别
        if (!isset($data['type'])) {
            $this->error = '请设置回复类型';
            return false;
        }

        $type = $data['type'];
        if (!in_array($type, $this->replyType)) {
            $this->error = '回复类别设置错误';
            return false;
        }

        // 检查关键词回复
        if ($type == $this->replyType['keyword']) {
            if (!isset($data['keyword']) || empty($data['keyword'])) {
                $this->error = '请设置关键词';
                return false;
            }
        }

        // 检查消息类别
        if (!isset($data['msg_type'])) {
            $this->error = '请选择消息的类别';
            return false;
        }

        $msg_type = $data['msg_type'];
        if (!in_array($msg_type, $this->msgType)) {
            $this->error = '该消息类别暂不支持';
            return false;
        }

        if ($msg_type == $this->msgType['news']) {
            $data['news'] = implode(',', $data['news']);
        }

        // 自动验证中的验证时间和消息类别相关
        return $this->create($data, $msg_type + 4);
    }

    /**
     * 设置微信回复信息
     *
     * @param array $data 回复信息的数据
     *
     * @return bool|mixed
     */
    public function setReply(array $data)
    {
        $result = $this->checkData($data);
        if (!$result) return false;

        // 清空其他的非必须数据
        $emptyReply = [
            'keyword' => null,
            'content' => null,
            'media_id' => null,
            'title' => null,
            'music' => null,
            'hq_music' => null,
            'thumb' => null,
            'news' => null,
        ];
        $result = array_merge($emptyReply, $result);

        if (isset($result['id']) && $result['id'] > 0) {
            return false !== $this->save($result);
        }

        unset($result['id']);

        return $this->add($result);
    }

    /**
     * 设置微信菜单点击回复
     *
     * @param array $data 回复消息的数据
     *
     * @return bool|mixed
     */
    public function setClickReply(array $data)
    {
        $data['type'] = $this->replyType['click'];

        return $this->setReply($data);
    }

    /**
     * 删除微信回复设置(关注、自动、关键词回复)
     *
     * @param int    $id       回复记录的ID
     * @param string $type     回复类别
     *
     * @return bool
     */
    public function delReply($id, $type)
    {
        if (!in_array($type, $this->replyType)) {
            $this->error = '回复类型不存在';
            return false;
        }

        $where = ['id' => $id, 'type' => $type];

        return false !== $this->where($where)->delete();
    }

    /**
     * 获取关注或自动回复的信息
     *
     * @param string $type    回复类型的格式
     * @param bool   $default 是否返回默认值(没有设置的时候)
     *
     * @return array|mixed
     */
    public function info($type, $default = false)
    {
        $type = strtolower($type);
        if (!isset($this->replyType[$type])) return false;

        $type = $this->replyType[$type];

        // 关键词回复可以有多个,需要特别处理
        if ($this->replyType['keyword'] == $type) {
            return $this->keywords();
        }

        $info = $this->field('id,type,msg_type,content,media_id,news')
            ->where(['type' => $type])->order('id asc')->find();

        if (is_null($info)) {
            // 设置默认值
            if ($default) {
                $info = [
                    'id' => 0,
                    'type' => $type,
                    'msg_type' => 0,
                    'content' => null,
                    'media_id' => null,
                    'news' => [],
                ];
            }
        } else {
            // 处理图文消息
            if ($info['msg_type'] == $this->msgType['news']) {
                $news = D('Articles')->news($info['news']);
                $info['news'] = $news ?: [];
            } else {
                $info['news'] = [];
            }
        }

        return $info;
    }

    /**
     * 获取关键词列表
     *
     * @param int  $page   第几页
     * @param int  $rows   每页行数
     * @param bool $paging 是否需要全部分页信息
     *
     * @return array
     */
    public function keywords($page = 1, $rows = 20, $paging = true)
    {
        $where = ['type' => $this->replyType['keyword']];

        $lists = $this->field('id,type,keyword,msg_type')->where($where)
            ->order('id desc')->page($page, $rows)->select();

        if (!$paging) return $lists;

        $count = $lists ? count($lists) : 0;
        if ($count < $rows && $count > 0) {
            $total = ($page - 1) * $rows + $count;
        } else {
            $total = $this->where($where)->count();
        }

        return [
            'lists' => (array)$lists,
            'total' => (int)$total,
            'pages' => ceil($total / $rows),
            'page'  => (int)$page,
            'rows'  => (int)$rows,
        ];
    }

    /**
     * 获取回复设置的详情
     *
     * @param int $id 回复设置 ID
     *
     * @return bool|mixed
     */
    public function keywordDetail($id)
    {
        $reply = $this->field('id,type,keyword,msg_type,content,news,media_id')
            ->where(['id' => $id, 'type' => $this->replyType['keyword']])
            ->find();

        if ($reply) {
            if ($reply['msg_type'] == $this->msgType['news']) {
                $news = D('Articles')->news($reply['news']);
                $reply['news'] = $news ?: [];
            } else {
                $reply['news'] = [];
            }
        }

        return $reply;
    }

    /**
     * 获取对应的回复信息(主要用于微信接口模块)
     *
     * @param string $type    回复类型
     * @param string $keyword 关键词(可选)
     *
     * @return bool|array
     */
    public function getReply($type, $keyword = '')
    {
        $type = strtolower($type);
        if (!isset($this->replyType[$type])) {
            $this->error = '未知回复类型';
            return false;
        }

        $where = ['type' => $this->replyType[$type]];
        if ('keyword' == $type) {
            if (!$keyword) return false;

            $where['keyword'] = $keyword;
        }

        $reply = $this->field('msg_type,content,media_id,title,music,hq_music,thumb,news')
            ->where($where)->order('id desc')->find();

        if ($reply) {
            if (!in_array($reply['msg_type'], $this->msgType)) {
                $this->error = '消息类型不存在';
                return false;
            }

            foreach ($this->msgType as $key => $val) {
                if ($val == $reply['msg_type']) {
                    $reply['msg_type'] = $key;
                    break;
                }
            }

            // 图文消息处理
            if ('news' == $reply['msg_type']) {
                $news = D('Admin/Articles')->news($reply['news'], true);

                if (!$news) return false;

                $reply['news'] = $news;
            }
        }

        return $reply;
    }

    /**
     * 获取点击菜单时对应的回复消息
     *
     * @param int  $id       回复消息的ID
     * @param bool $forReply 是否是用于微信回复接口
     *
     * @return bool|mixed
     */
    public function getClickInfo($id, $forReply = false)
    {
        $where = ['id' => $id, 'type' => $this->replyType['click'],];

        $reply = $this->field('msg_type,content,media_id,title,music,hq_music,thumb,news')
            ->where($where)->find();

        if ($reply) {
            $msgType = array_flip($this->msgType);

            if (!isset($reply['msg_type'], $msgType)) {
                $this->error = '消息类型不存在';
                return false;
            }

            // 图文消息处理
            if ($this->msgType['news'] == $reply['msg_type']) {
                $news = D('Admin/Articles')->news($reply['news'], $forReply);

                if (!$news) return false;

                $reply['news'] = $news;
            }

            // 用于微信回复的时候则需要将msg_type转成字符串
            if ($forReply) $reply['msg_type'] = $msgType[$reply['msg_type']];
        }

        return $reply;
    }
}