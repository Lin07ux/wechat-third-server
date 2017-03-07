<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2017-01-16
 * Time: 14:11
 * Desc: 微信公众号自动回复
 */

namespace Admin\Controller;

class ReplyController extends CommonController
{
    protected $keywordRows = 15;

    /**
     * 设置回复消息
     */
    public function setReply()
    {
        if (!IS_POST) $this->send404();

        $Reply = D('Reply');
        $data = I('post.');
        if (isset($data['content'])) $data['content'] = $_POST['content'];

        $result = $Reply->setReply($data);

        if ($result) {
            $res = ['code' => 0, 'msg' => '设置回复消息成功', 'data' => $result];
        } else {
            $err = $Reply->getError();
            $res = ['code' => 1502, 'msg' => $err ?: '设置回复消息失败！请稍后重试。'];
        }

        $this->ajaxReturn($res);
    }

    /**
     * 删除回复消息
     */
    public function delReply()
    {
        if (!IS_POST) $this->send404();

        $Reply = D('Reply');
        $result = $Reply->delReply(I('post.id'), I('post.type'));

        if ($result) {
            $res = ['code' => 0, 'msg' => '删除回复消息成功'];
        } else {
            $err = $Reply->getError();
            $res = ['code' => 1502, 'msg' => $err ?: '删除回复消息失败！请稍后重试。'];
        }

        $this->ajaxReturn($res);
    }

    /**
     * 获取关键词回复列表
     */
    public function keywords()
    {
        if (!IS_GET) $this->send404();

        $page = I('get.page', 1);
        $keywords = D('Reply')->keywords($page, $this->keywordRows, true);

        $res = ['code' => 0, 'msg' => '获取关键词回复规则列表成功', 'data' => $keywords];

        $this->ajaxReturn($res);
    }

    /**
     * 获取关键词回复详情
     */
    public function keywordDetail()
    {
        $id = I('get.id');

        if (!IS_GET || !$id) $this->send404();

        $detail = D('Reply')->keywordDetail($id);

        if ($detail) {
            $res = ['code' => 0, 'msg' => '获取关键词规则详情成功', 'data' => $detail];
        } else {
            $res = ['code' => 101, 'msg' => '获取关键词规则详情失败，请稍后重试',];
        }

        $this->ajaxReturn($res);
    }

    /**
     * 获取关注回复、自动回复和关键词回复的内容
     */
    public function all()
    {
        $Reply = D('Reply');
        $subscribe = $Reply->info('subscribe', true);
        $auto = $Reply->info('auto', true);
        $keywords = $Reply->keywords(1, $this->keywordRows, true);

        $res = [
            'code' => 0,
            'msg' => '获取关注回复和自动回复成功',
            'data' => [
                'subscribe' => $subscribe,
                'auto' => $auto,
                'keywords' => $keywords
            ]
        ];

        $this->ajaxReturn($res);
    }
}