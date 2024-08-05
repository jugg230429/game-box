<?php

namespace app\member\logic;

use app\member\model\PointRecordModel;
use app\member\model\PointShopModel;
use app\member\model\PointShopRecordModel;
use app\member\model\PointUseModel;
use app\member\model\PointUseTypeModel;
use app\member\model\UserModel;
use app\member\validate\PointShopValidate;
use think\Db;

class PointShopLogic
{
    public function lists(array $param):array
    {
        $model = new PointShopModel();
        $model->field('id,good_name,price,number,type,thumbnail');
        $model->where('status','=',1);
        $model->where('number','>',0);
        if(!empty($param['type'])){
            $model->where('type','=',$param['type']);
        }
        if(!empty($param['price'])){
            strstr($param['price'],'以上',true)?$model->where('price','>',strstr($param['price'],'以上',true)):$model->where('price','between',explode('-',$param['price']));
        }
        $model->order('sort desc,id desc');
        $modelData = $model->select()->toArray();
        $virtual = [];
        $really = [];
        foreach ($modelData as $key=>$value){
            $modelData[$key]['thumbnail'] = cmf_get_image_preview_url($value['thumbnail']);
            if($value['type']==1){
                $really[] = $value;
            }else{
                $virtual[] = $value;
            }
        }
        return ['really'=>$really,'virtual'=>$virtual];
    }

    public function detail(array $param){
        $model = new PointShopModel();
        $modelData = $model->find($param['id']);
        if(empty($modelData)){
            return [];
        }
        $modelData->thumbnail = cmf_get_image_preview_url($modelData->thumbnail);
        $modelData->max_vip_discount_price = $modelData->price;
        if(!empty($modelData->vip_discount)){
            $max_vip_discount = explode('/',$modelData->vip_discount);
            $modelData->max_vip_discount_price = round($modelData->price*end($max_vip_discount)/100);
        }
        $data = $modelData->toArray();
        return $data;
    }
    public function mall_exchange_detail(array $param,int $user_id){
        $model = new PointShopModel();
        $recordmodel = new PointShopRecordModel();
        $modelData = $model->field('id,good_name,type,price,vip_level,vip_discount,number,limit')->where('status','=',1)->where('id','=',$param['id'])->find();
        if(empty($modelData)){
            return 0;//无商品数据
        }
        $buycount = $recordmodel->where('user_id',$user_id)->where('good_id',$modelData->id)->SUM('number');
        $buycount = $buycount?:0;
        if(($param['num'] + $buycount) > $modelData->limit ){
            return -5;
        }
        if($modelData->number < $param['num']){
            return -1;//库存不足
        }
        $vip_level = explode('/',$modelData->vip_level);
        $userData = get_user_entity($user_id,false,'vip_level,point,receive_address');
        switch (count($vip_level)){
            case 0:
                $match = -1;
                break;
            case 1:
                $match = $userData['vip_level']>=$vip_level[0]?0:-1;
                break;
            default:
                $match = -1;
                foreach ($vip_level as $key=>$value){
                    if($userData['vip_level']>=$value){
                        $match = $key;
                    }
                }
                break;

        }
        $modelData->vip_discount = explode('/',$modelData->vip_discount)[$match]?:100;
        $modelData->vip_level = $vip_level[$match]?:0;
        $modelData->vip_discount_price = round($modelData->price*$param['num']*$modelData->vip_discount/100);
        if($modelData->vip_discount_price>$userData['point']){
            return -2;//积分余额不足
        }
        //if($userData['vip_level']<reset($vip_level)){
            //return -3;//等级不足
        //}
        $userData['receive_address'] = explode('|!@#%-|',$userData['receive_address']);
        $modelData->receive_address = count($userData['receive_address'])?$userData['receive_address']:[];
        $data = $modelData->toArray();
        return $data;
    }
    public function mall_exchange(array $param,int $user_id)
    {
        $data = $this->mall_exchange_detail($param,$user_id);
        if(!is_array($data)){
            return $data;
        }
        if(empty($data['receive_address'])&&$data['type']==1){
            return -4;//地址不能为空
        }
        Db::startTrans();
        $modelUser = new UserModel();
        $modelUser->where('id','=',$user_id)->setDec('point',$data['vip_discount_price']);
        $shopModel = new PointShopModel();
        $shopModel->where('id','=',$data['id'])->setDec('number',$param['num']);
        $recordModel = new PointShopRecordModel();
        $recordModel->user_id = $user_id;
        $recordModel->user_account = get_user_name($user_id);
        $recordModel->good_id = $data['id'];
        $recordModel->good_name = $data['good_name'];
        $recordModel->good_type = $data['type'];
        $recordModel->pay_amount = $data['vip_discount_price'];
        $recordModel->number = $param['num'];
        $recordModel->vip = $data['vip_level'];
        $recordModel->discount = $data['vip_discount'];
        $recordModel->receive_address = implode(',',$data['receive_address']);
        $recordModel->save();
        $useTypeModel  = new PointUseTypeModel();
        $useTypeData = $useTypeModel->where('key','=','exchange')->find();
        $useModel  = new PointUseModel();
        $useModel->type_id = $useTypeData->id;
        $useModel->item_id = $recordModel->id;
        $useModel->type_name = $useTypeData->name;
        $useModel->user_id = $recordModel->user_id;
        $useModel->user_account = $recordModel->user_account;
        $useModel->point = $data['vip_discount_price'];
        $useModel->good_name = $data['good_name'];
        $useModel->save();
        Db::commit();
        return 1;
    }
}