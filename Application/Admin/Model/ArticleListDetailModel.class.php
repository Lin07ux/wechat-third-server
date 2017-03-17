<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2017-01-13
 * Time: 14:44
 * Desc: 文章列表与文章的映射表
 */

namespace Admin\Model;

use Think\Model;

class ArticleListDetailModel extends Model
{
    /**
     * 获取文章列表中的文章
     *
     * @param int $list 文章列表ID
     * @param int $page 当前页数
     * @param int $rows 每页行数
     *
     * @return array
     */
    public function articlesOfList($list, $page = 1, $rows = 15)
    {
        $where = ['list' => $list];

        $lists = $this->alias('d')
            ->join('LEFT JOIN __ARTICLES__ a ON a.id = d.article')
            ->field('a.id,title,cover,desc,d.id as detail,DATE_FORMAT(publish_time, "%Y年%m月%d日%H:%i") AS publish_time')
            ->where($where)->order('a.publish_time desc, d.id desc')
            ->page($page, $rows)->select();

        $count = $lists ? count($lists) : 0;
        if (($count < $rows && $count > 0) || ($page == 1 && $count == 0)) {
            $total = ($page - 1) * $rows + $count;
        } else {
            $total = $this->where($where)->count();
        }

        return [
            'total' => (int)$total,
            'lists' => (array)$lists,
            'page'  => (int)$page,
            'rows'  => (int)$rows,
            'pages' => ceil($total / $rows)
        ];
    }

    /**
     * 向列表中添加文章
     *
     * @param int $list    列表ID
     * @param int $article 文章ID
     *
     * @return bool|mixed
     */
    public function addArticle2List($list, $article)
    {
        $data = ['list' => $list, 'article' => $article];

        $isExist = (bool)$this->where($data)->count();
        if ($isExist) {
            $this->error = '文章已存在于该列表中';
            return false;
        }

        return $this->add($data);
    }

    /**
     * 从文章列表中删除文章
     *
     * @param int $id 关联表ID
     *
     * @return bool
     */
    public function delArticleFromList($id)
    {
        return (bool)$this->where(['id' => $id])->delete();
    }
}