<?php

namespace app\recharge\model;

use think\Model;

/**
 * yyh
 */
class CouponModel extends Model
{

    protected $table = 'tab_coupon';

    protected $autoWriteTimestamp = true;

    public function get_award_lists()
    {
        $coupon = $this
                ->field('id,category,mold,coupon_name,money,limit_money,start_time,end_time,status,create_time,stock')
                ->where('coupon_type','in',[1,2,3])
                ->order('coupon_type asc')
                ->select()->toArray();
        return $coupon;
    }

    /**
     * @函数或方法说明
     * @修改奖励状态
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/1/11 11:17
     */
    public function changeStatus($request=[]){
        $status = $request['status'] == 1 ? 0 : 1;
        $result = $this->where('id',$request['id'])->setField('status',$status);
        return $result;
    }

    /**
     * @函数或方法说明
     * @获取详情
     * @param int $id
     *
     * @author: 郭家屯
     * @since: 2020/1/10 9:42
     */
    public function detail($id=0){
        $data = $this->where('id',$id)->find();
        return $data?$data->toArray():[];
    }

    /**
     * @函数或方法说明
     * @编辑奖励
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/2/15 16:48
     */
    public function edit($request=[],$data=[])
    {
        $request['start_time'] = $request['start_time'] === true ? 0 : strtotime($request['start_time']);
        $request['end_time'] = $request['end_time']=== true ? 0 : strtotime($request['end_time']);
        $request['create_time'] = time();
        $award = $this->where('id',$request['id'])->find();
        if($award){
            if($request['mold'] == 0){
                $request['game_id'] = 0;
                $request['game_name'] = '';
            }
            $resutl = $this->where('id',$request['id'])->update($request);
        }else{
            $data[$request['id']-1]['mold'] = $request['mold'];
            $data[$request['id']-1]['spend_limit'] = $request['spend_limit'];
            $data[$request['id']-1]['game_id'] = $request['mold'] == 1 ?$request['game_id'] : 0;
            $data[$request['id']-1]['game_name'] = $request['mold'] == 1 ?$request['game_name'] : 0;
            $data[$request['id']-1]['limit_money'] = $request['limit_money'];
            $data[$request['id']-1]['money'] = $request['money'];
            $data[$request['id']-1]['start_time'] = $request['start_time'];
            $data[$request['id']-1]['end_time'] = $request['end_time'];
            $data[$request['id']-1]['create_time'] = time();
            $resutl = $this->insertAll($data);
        }
        if($resutl !== false){
            return true;
        }else{
            return false;
        }
    }
}