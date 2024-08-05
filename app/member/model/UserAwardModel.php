<?php

namespace app\member\model;

use think\Model;

class UserAwardModel extends Model
{

    protected $table = 'tab_user_award';

    protected $autoWriteTimestamp = true;

    /**
     * @函数或方法说明
     * @获取奖品列表
     * @author: 郭家屯
     * @since: 2020/8/12 17:25
     */
    public function getaward()
    {
        $award = $this
                ->field('*')
                ->order('id asc')
                ->select()->toArray();
        return $award;
    }

    /**
     * @函数或方法说明
     * @获取奖品信息
     * @author: 郭家屯
     * @since: 2020/8/13 11:08
     */
    public function get_award_info($award_id=0)
    {
        if(empty($award_id))return [];
        $data = $this
                ->where('id',$award_id)
                ->find();
        return $data ? $data->toArray() : [];
    }













}