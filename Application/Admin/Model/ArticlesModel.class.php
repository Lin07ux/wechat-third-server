<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2017-01-20
 * Time: 11:52
 * Desc:
 */

namespace Admin\Model;

use Think\Model;

class ArticlesModel extends Model
{
    /**
     * @var array 文章类型
     */
    protected $type = [
        'link' => 0,     // 外链类文章
        'content' => 1,  // 原创类文章
    ];

    /**
     * @var array 自动验证
     */
    protected $_validate = array(
        ['title', 'require', '请填写文章名称', 1],
        ['desc', 'require', '请填写文章描述', 1],
        ['cover', 'require', '请上传文章封面图片', 1],
        ['link', 'url', '文章链接需要设置为完整的url', 1, 'regex', 4],
        ['content', 'require', '请填写文章内容', 1, '',  5],
    );

    /**
     * 文章列表
     *
     * @param int  $page   页数
     * @param int  $rows   每页行数
     * @param bool $paging 是否需要分页信息
     *
     * @return array|mixed
     */
    public function lists($page = 1, $rows = 20, $paging = true)
    {
        if ($page < 1) $page = 1;
        if ($rows < 1) $rows = 20;

        $lists = $this->field('id,type,title,url')->order('id desc')
            ->page($page, $rows)->select();

        // 如果查询失败或者不需要分页就直接返回查询结果
        if (!$paging) return $lists;

        $count = $lists ? count($lists) : 0;
        if ($count < $rows && $count > 0) {
            $total = ($page - 1) * $rows + $count;
        } else {
            $total = $this->count();
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
     * 获取文章的详细信息
     *
     * @param int $id 文章ID
     *
     * @return mixed
     */
    public function detail($id)
    {
        return $this->field('crt_time,upd_time', true)->where(['id' => $id])->find();
    }

    /**
     * 检查文章的数据
     *
     * @param array $data 文章数据
     *
     * @return bool|mixed
     */
    public function checkData(array $data)
    {
        if (!isset($data['type'])) {
            $this->error = '请设置文章类别';
            return false;
        }

        $type = $data['type'];
        if (!in_array($type, $this->type)) {
            $this->error = '文章类别错误。仅支持链接类型和原创文章类型';
            return false;
        }

        if ($type == $this->type['link']) {
            $data['content'] = '';
        } else {
            $data['link'] = '';
        }

        return $this->create($data, (int)$data['type'] + 4);
    }

    /**
     * 新增文章
     *
     * @param array  $data   文章数据
     * @param string $domain 域名(用于生成文章的 url)
     *
     * @return array|bool
     */
    public function addArticle(array $data, $domain)
    {
        $checked = $this->checkData($data);

        if (!$checked) return false;

        $this->startTrans();

        $id = $this->add($checked);
        if ($id) {
            $url = $domain . '/Article/' . $id;
            $result = $this->where(['id' => $id])->setField('url', $url);

            if ($result) {
                $this->commit();

                return ['id' => $id, 'url' => $url];
            }
        }

        $this->rollback();

        return false;
    }

    /**
     * 更新文章内容
     *
     * @param array $data 文字的内容数据
     *
     * @return bool
     */
    public function editArticle(array $data)
    {
        $checked = $this->checkData($data);

        if (!$checked) return false;

        unset($checked['url']);

        return false !== $this->save($checked);
    }

    /**
     * 删除文章
     *
     * @param int $id 文章的ID
     *
     * @return bool
     */
    public function remove($id)
    {
        return (bool)$this->where(['id' => $id])->delete();
    }

    /**
     * 获取图文消息中指定的文章信息
     *
     * @param string|array $ids  文章的id
     * @param bool         $link 是否需要获取link
     *
     * @return bool|mixed
     */
    public function news($ids, $link = false)
    {
        if (!is_string($ids) && !is_array($ids)) return false;

        if (is_array($ids)) $ids = implode(',', $ids);

        $fields = 'id,title,desc,cover,thumb';

        if ($link) {
            $fields .= sprintf(
                ', (CASE WHEN type = %d THEN `link` ELSE `url` END) AS link',
                $this->type['link']
            );
        }

        return $this->field($fields)->where(['id' => ['in', $ids]])
            ->order("FIND_IN_SET(id, '{$ids}')")->limit(8)->select();
    }

    /**
     * 查询文章
     *
     * @param string $title 查询的文章的标题
     *
     * @return mixed
     */
    public function search($title)
    {
        $title = trim($title);
        $results =  $this->field('id,title,desc,cover,thumb')
            ->where(['title' => ['like', "%{$title}%"]])
            ->limit(10)->order('id desc')->select();

        if (is_null($results)) $results = [];

        return $results;
    }
}