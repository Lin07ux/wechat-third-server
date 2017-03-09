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
    protected $basic_fields = 'id,openid,wx_appid,subscribe,realname,phone,nickname';

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
     * @param int|string  $id       用户的ID或者openid
     * @param null|string $wx_appid 用户关注的微信公众号appid
     *
     * @return bool
     */
    public function isSubscribe($id, $wx_appid = null)
    {
        if (is_null($wx_appid)) {
            $where = ['id' => $id];
        } else {
            $where = ['openid' => $id, 'wx_appid' => $wx_appid];
        }

        return (bool)$this->where($where)->getField('subscribe');
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

    /**
     * 获取用户基础代谢的信息
     *
     * @param int $id 用户ID
     *
     * @return mixed
     */
    public function getBMRInfo($id)
    {
        return $this->field('sex,age,height,weight,BMR')
            ->where(['id' => $id])->find();
    }

    /**
     * 更新用户 BMR 信息
     *
     * @param int   $id   用户ID
     * @param array $data 更新的信息
     *
     * @return int|bool
     */
    public function updBMRInfo($id, array $data)
    {
        if (!$this->checkBMRInfo($data)) return false;

        $data['BMR'] = $this->calculateBMR(
            $data['age'],
            $data['height'],
            $data['weight'],
            $data['sex'] == $this->sex['male']
        );

        $result = $this->where(['id' => $id])->save($data);

        // 如果更新成功就返回计算得到的 BMR 的值
        return $result !== false ? $data['BMR'] : false;
    }

    /**
     * 检查 BMR 数据是否符合要求
     *
     * @param array $data BMR相关的数据
     *
     * @return bool
     */
    public function checkBMRInfo(array $data)
    {
        $error = '';

        if (!isset($data['sex']) || $data['sex'] < 1 || $data['sex'] > 2) {
            $error = '请设置性别';
        }

        if (!isset($data['age']) || $data['age'] <= 0 || $data['age'] > 150) {
            $error = '请设置正确的周岁年龄';
        }

        if (!isset($data['height']) || $data['height'] <= 10 || $data['height'] > 250) {
            $error = '请设置身高，单位厘米(cm)';
        }

        if (!isset($data['weight']) || $data['weight'] <= 10 || $data['weight'] > 300) {
            $error = '请设置正确的体重，单位公斤(kg)';
        }

        if ($error) {
            $this->error = $error;
            return false;
        }

        return true;
    }

    /**
     * 计算得到 BMR 数据
     *
     * @param int  $age    年龄
     * @param int  $height 身高
     * @param int  $weight 体重
     * @param bool $isMale 是否为男性
     *
     * @return int
     */
    protected function calculateBMR($age, $height, $weight, $isMale = true)
    {
        $basic = 4.92 * $age + 6.25 * $height + 9.99 * $weight;

        return round($basic + ($isMale ? 5 : -161));
    }
}