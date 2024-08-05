<?php

namespace app\member\logic;

use app\member\model\PointShopRecordModel;
use app\member\model\PointUseModel;
use app\member\model\PointUseTypeModel;
use think\Db;

class PointUseLogic
{
    public function lists(int $user_id)
    {
        $modelPointUse = new PointUseModel();
        $modelPointUse->field('id,type_id,type_name,point,good_name,create_time,from_unixtime(create_time,"%Y-%m-%d %H:%i") as create_date,item_id');
        $modelPointUse->where('user_id','=',$user_id);
        $modelPointUseData = $modelPointUse->order('create_time desc')->select()->toArray();
        return $modelPointUseData;
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
        $model = new PointUseModel();
        $map['user_id'] = $user_id;
        $list = $model
                ->field('id,type_id,type_name,point,good_name,from_unixtime(create_time,"%Y/%m/%d %H:%i") as create_time,item_id')
                ->where($map)
                ->order('id desc')
                ->page($p, $limit)
                ->select()->toArray();
        $sum  = $model->where($map)->sum('point');
        $data['data'] = $list ?  : [];
        $data['sum'] = $sum ? : 0;
        return $data;
    }

    public function detail(int $use_id,int $item_id,int $user_id)
    {
        $modelPointUse = new PointUseModel();
        $modelPointUse->where('user_id','=',$user_id);
        $modelPointUse->where('id','=',$use_id);
        $modelPointUse->where('item_id','=',$item_id);
        $useData = $modelPointUse->find();
        if(empty($useData)){
            return false;
        }
        if($useData->type_id==1){
            $model = new PointShopRecordModel();
        }
        $modelData = $model->find($item_id);
        if(empty($modelData)){
            return false;
        }
        return $modelData->toArray();
    }
}