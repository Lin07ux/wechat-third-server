<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2017-03-16
 * Time: 17:09
 * Desc:
 */

namespace Home\Controller;


class ArticleListController extends CommonController
{
    /**
     * 展示文章列表
     *
     * @param int $id 列表 ID
     */
    public function _empty($id)
    {
        if ($id <= 0) $this->send404();

        // 判断列表是否存在
        $name = D('Admin/ArticleLists')->listName($id);
        if (!$name) $this->send404();

        $rows = 10;
        $ListDetail = D('Admin/ArticleListDetail');

        if (!IS_AJAX) {
            $result = $ListDetail->articlesOfList($id, 1, $rows);

            $this->assign('title', $name)
                ->assign('list', $id)
                ->assign('articles', $result)
                ->display('Article/list');

        } else {
            $page = I('get.page', 1);
            $result = $ListDetail->articlesOfList($id, $page, $rows);
            $res = ['code' => 0, 'msg' => '获取文章数据成功', 'data' => $result];

            $this->ajaxReturn($res);
        }
    }
}