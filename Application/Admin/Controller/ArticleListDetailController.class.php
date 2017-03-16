<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2017-03-15
 * Time: 21:44
 * Desc:
 */

namespace Admin\Controller;


class ArticleListDetailController extends CommonController
{
    /**
     * 获取列表中的文章
     */
    public function articles()
    {
        $list = I('get.list');

        if ($list) {
            $page = I('get.page', 1);
            $rows = 10;
            $articles = D('ArticleListDetail')->articlesOfList($list, $page, $rows);

            $res = ['code' => 0, 'msg' => '获取文章列表成功', 'data' => $articles];

        } else {
            $res = ['code' => 10, 'msg' => '请提供文章列表ID'];
        }

        $this->ajaxReturn($res);
    }

    /**
     * 向列表中添加文章
     */
    public function add()
    {
        $Detail = D('ArticleListDetail');
        $result = $Detail->addArticle2List(I('post.list'), I('post.article'));

        if ($result) {
            $res = ['code' => 0, 'msg' => '添加成功', 'data' => $result];
        } else {
            $err = $Detail->getError();
            $res = ['code' => 102, 'msg' => $err ?: '添加失败，请稍后重试！'];
        }

        $this->ajaxReturn($res);
    }

    /**
     * 从列表中删除文章
     */
    public function remove()
    {
        $Detail = D('ArticleListDetail');
        $result = $Detail->delArticleFromList(I('post.id'));

        if ($result) {
            $res = ['code' => 0, 'msg' => '删除成功'];
        } else {
            $res = ['code' => 102, 'msg' => '删除失败，请稍后重试！'];
        }

        $this->ajaxReturn($res);
    }
}