<?php
/**
 * Created by PhpStorm.
 * Author: Lin07ux
 * Date: 2016-09-27
 * Time: 17:21
 * Desc:
 */

namespace Common\Model;

use Think\Model;

class UsersModel extends Model
{
    /**
     * @var string 用户基本信息字段
     */
    protected $basic_fields = 'id,openid,subscribe,name,phone,nickname';

    /**
     * @var array 用户性别
     */
    protected $sex = ['unknown' => 0, 'male' => 1, 'female' => 2];

    /**
     * 同步用户信息
     *
     * @param array $info   用户信息数组
     * @param bool  $return 是否返回更新后的信息
     *
     * @return bool|mixed
     */
    public function syncUserInfo(array $info, $return = true)
    {
        if (isset($info['id']) && $info['id'] > 0) {
            $where = ['id' => $info['id']];
        } elseif (isset($info['openid'])) {
            $where = ['openid' => $info['openid']];
        } else {
            $this->error = '缺少用户标识';
            return false;
        }

        foreach ($info as $key => $val) {
            if (is_array($val)) $info[$key] = json_encode($val);
        }

        $info['upd_time'] = date('Y-m-d H:i:s');

        $res = $this->where($where)->save($info);
        if (!$res) {
            unset($info['upd_time']);
            $res = $this->add($info);
        }

        return $return && $res
            ? $this->field($this->basic_fields)->where($where)->find()
            : $res;
    }

    /**
     * 用户取消关注公众号
     *
     * @param string $openid 用户的openid
     *
     * @return bool
     */
    public function unsubscribe($openid)
    {
        return false !== $this->where(['openid' => $openid])->setField('subscribe', 0);
    }

    /**
     * 检查用户是否关注了对应的公众号
     *
     * @param int|string $id 用户的ID或者openid
     *
     * @return bool
     */
    public function isSubscribe($id)
    {
        return (bool)$this->where(['id' => $id])->getField('subscribe');
    }

    /**
     * 更新用户的信息
     *
     * @param int   $id   用户ID
     * @param array $info 更新的信息数组
     *
     * @return bool
     */
    public function setUserInfo($id, array $info)
    {
        return $this->where(['id' => $id])->save($info);
    }
}