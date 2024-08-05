<?php

namespace app\promote\logic;

use app\promote\model\PromoteModel;
use app\business\model\PromoteBusinessModel;
use think\Db;

class PromoteLogic
{
    /**
     * 移除商户推广员id
     * @param $busier
     * @param $promote_id
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function remove_business_promote_ids($busier,$top_id){
        $model = new PromoteBusinessModel();
        $promote_ids = $model->field('promote_ids')->find($busier)->toArray();
        $promote_ids = explode(',',$promote_ids['promote_ids']);
        //一级及二级渠道
        $child_ids = Db::table("tab_promote")->where(['top_promote_id|parent_id|id'=>$top_id])->column('id');
        $promote_id = $child_ids;
        foreach ($promote_id as $k=>$v){
            $key = array_search($v,$promote_ids);
            unset($promote_ids[$key]);
        }
        $promote_ids = implode(',',$promote_ids);
        $model->where('id',$busier)->update(['promote_ids'=>$promote_ids]);
        return true;
    }

    /**
     * 新增商户推广员id
     * @param $busier
     * @param $promote_id
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_business_promote_ids($busier,$top_id){
        $model = new PromoteBusinessModel();
        $promote_ids = $model->field('*')->find($busier)->toArray();
        $promote_ids = explode(',',$promote_ids['promote_ids']);
        //一级及二级渠道
        $child_ids = Db::table("tab_promote")->where(['top_promote_id|parent_id|id'=>$top_id])->column('id');
        $promote_ids = array_merge($promote_ids,$child_ids);
        $promote_ids = array_unique($promote_ids);
        $promote_ids = implode(',',$promote_ids);
        $promote_ids = trim($promote_ids,',');
        $model->where('id',$busier)->update(['promote_ids'=>$promote_ids]);
        return true;
    }

    /**
     * 商务下渠道注册总和
     * @param $promote_ids
     * @param $map
     * @return mixed
     */
    public function get_total_register($promote_ids,$map){
        $map['promote_id'] = ['in',$promote_ids];
        $map['puid'] = 0;//大号
        $count = Db::table('tab_user')->where($map)->count();
        return $count;
    }

    public function get_total_pay($promote_ids,$map)
    {
        $map['promote_id'] = ['in',$promote_ids];
        $map['pay_status'] = 1;
        $data = Db::table('tab_spend')->where($map)->sum('pay_amount');
        return $data;
    }

    public function get_promote_user_info($map,$field='*'){
        $data = Db::table('tab_user')->field($field)->where($map)->select()->toArray();
        if(empty($data)){
            return [];
        }else{
            return $data;
        }
    }

    /**
     * @获取推广员信息
     *
     * @author: zsl
     * @since: 2021/2/1 16:37
     */
    public function getInfo($id)
    {
        $mPromote = new PromoteModel();
        $info = $mPromote -> where(['id' => $id]) -> find();
        return $info ? $info : [];
    }

}