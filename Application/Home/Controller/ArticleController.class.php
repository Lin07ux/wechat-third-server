<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2017-03-16
 * Time: 17:08
 * Desc: 文章详情
 */

namespace Home\Controller;


class ArticleController extends CommonController
{
    /**
     * 展示文章内容
     *
     * @param int $id 文章ID
     */
    public function _empty($id)
    {
        if ($id <= 0) $this->send404();

        $Article = D('Admin/Articles');
        $detail = $Article->detail($id);
        $isLink = $Article->isLink($detail['type']);

        if ($isLink && $detail['link']) {
            redirect($detail['link']);
        } else {
            $this->assign('title', $detail['title'])
                ->assign('detail', $detail)
                ->display('Article/detail');
        }
    }
}