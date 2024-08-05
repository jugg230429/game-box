<?php

namespace app\member\logic;

use app\member\model\PointRecordModel;
use think\Db;

class PointShopRecordLogic
{
    public function lists(int $user_id)
    {
        $modelPointRecord = new PointRecordModel();
        $modelPointRecord->field('id,type_id,type_name,point,create_time,from_unixtime(create_time,"%Y-%m-%d %H:%i") as create_date');
        $modelPointRecord->where('user_id','=',$user_id);
        $modelPointRecordData = $modelPointRecord->order('create_time desc')->select()->toArray();
        return $modelPointRecordData;
    }

    /**
     * @函数或方法说明
     * @获取记录分页
     * @param int $user_id
     * @param int $p
     * @param int $limit
     *
     * @author: 郭家屯
     * @since: 2020/6/2 15:21
     */
    public function lists_page($user_id=0,$p=1,$limit=10)
    {
        if(empty($user_id)){
            return [];
        }
        $model = new PointRecordModel();
        $map['user_id'] = $user_id;
        $list = $model
                ->field('id,type_id,type_name,point,create_time,from_unixtime(create_time,"%Y/%m/%d %H:%i") as create_time')
                ->where($map)
                ->order('id desc')
                ->page($p, $limit)
                ->select()->toArray();
        $sum  = $model->where($map)->sum('point');
        $data['data'] = $list ?  : [];
        $data['sum'] = $sum ? : 0;
        return $data;
    }
}