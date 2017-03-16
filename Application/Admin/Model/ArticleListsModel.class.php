<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2017-01-13
 * Time: 14:44
 * Desc:
 */

namespace Admin\Model;

use Think\Model;

class ArticleListsModel extends Model
{
    /**
     * @var int 限制创建的文章列表数量
     */
    protected $limit = 20;

    /**
     * @var array 自动验证
     */
    protected $_validate = array(
        ['id', 'number', '文章列表ID错误'],
        ['name', 'require', '请填写文章列表名称', 1],
    );

    /**
     * 获取文章列表
     *
     * @param bool $limit 是否获取限制数量
     *
     * @return array
     */
    public function lists($limit = true)
    {
        $lists = $this->field('id,name')->order('id desc')
            ->limit($this->limit)->select();

        return $limit ? ['lists' => $lists, 'limit' => $this->limit] : $lists;
    }

    /**
     * 添加或修改文章列表
     *
     * @param array $data 文章列表的数据
     *
     * @return bool|mixed
     */
    public function addOrEdit(array $data)
    {
        $isEdit = $data['id'] > 0;

        if (!$isEdit && $this->count() >= $this->limit) {
            $this->error = '最多可以创建'.$this->limit.'个文章列表';
            return false;
        }

        if (!$this->create($data)) return false;

        return $isEdit ? false !== $this->save() : $this->add();
    }

    /**
     * 删除文章列表
     *
     * @param int $id 文章的 ID
     *
     * @return bool
     */
    public function remove($id)
    {
        return (bool)$this->where(['id' => $id])->delete();
    }
}