<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2017-01-13
 * Time: 14:01
 * Desc:
 */

namespace Admin\Controller;


class ArticleListController extends CommonController
{
    /**
     * 文章列表数据
     */
    public function lists()
    {
        $limit = !isset($_GET['no-limit']);

        $result = D('ArticleLists')->lists($limit);
        $res = ['code' => 0, 'msg' => '获取列表成功', 'data' => $result];

        $this->ajaxReturn($res);
    }

    /**
     * 添加或修改文章列表
     */
    public function handle()
    {
        if (!IS_POST) $this->send404();

        $WxArticle = D('ArticleLists');
        $result = $WxArticle->addOrEdit(I('post.'));
        $isEdit = I('post.id') > 0;

        if ($result) {
            if ($isEdit) {
                $res = ['code' => 0, 'msg' => '更新文章列表信息成功'];
            } else {
                $res = ['code' => 0, 'msg' => '添加文章列表成功', 'data' => (int)$result];
            }
        } else {
            $err = $WxArticle->getError();
            $msg = $isEdit ? '更新列表信息失败，请稍后重试！' : '添加文章列表失败，请稍后重试！';
            $res = ['code' => 100, 'msg' => $err ?: $msg];
        }

        $this->ajaxReturn($res);
    }

    /**
     * 删除文章列表
     */
    public function remove()
    {
        if (!IS_POST) $this->send404();

        $result = D('ArticleLists')->remove(I('post.id'));

        if ($result) {
            $res = ['code' => 0, 'msg' => '删除成功',];
        } else {
            $res = ['code' => 104, 'msg' => '删除失败，请稍后重试！'];
        }

        $this->ajaxReturn($res);
    }
}