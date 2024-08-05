<?php

namespace app\thirdgame\model;

use think\Model;
use think\Db;

class SpendModel extends Model
{
      Protected $table = 'tab_spend';

      protected $autoWriteTimestamp = true;

    /**
     * 批量插入订单
     *
     * @param int $platform_id
     * @param array $param
     * @author: Juncl
     * @time: 2021/08/31 10:01
     */
    public function addSpend($platform_id=0, $param=[])
    {
        $spendIds = [];
        foreach ($param as $key => $val)
        {
            // 获取平台用户信息
            $userInfo = get_user_entity($val['user_id'],false,'id,account,promote_id,promote_account');
            if(!$userInfo){
                $userInfo['id'] = 0;
                $userInfo['account'] = '未知用户';
                $userInfo['promote_id'] = 0;
                $userInfo['promote_account'] = '官方渠道';
            }
            // 获取平台游戏信息
            $gameInfo = get_third_game_entity($platform_id,$val['game_id'],'id,game_name,sdk_version');
            if(!$gameInfo){
                $gameInfo['id'] = 0;
                $gameInfo['game_name']= '未知游戏';
                $gameInfo['sdk_version']= 0;
            }
            //生成平台订单号
            $payOrderNumber = 'SP_' . cmf_get_order_sn();
            $data_spend['user_id'] = $userInfo['id'];
            $data_spend['user_account'] = $userInfo['account'];
            $data_spend['game_id'] = $gameInfo["id"];
            $data_spend['game_name'] = $gameInfo['game_name'];
            $data_spend['server_id'] = $val["server_id"] ? : 0;
            $data_spend['server_name'] = $val["server_name"] ? : '';
            $data_spend['game_player_id'] = $val['game_player_id']?:0;
            $data_spend['game_player_name'] = $val["game_player_name"] ? : '';
            $data_spend['role_level'] = $val["role_level"] ? : 0;
            $data_spend['promote_id'] = $userInfo["promote_id"];
            $data_spend['promote_account'] = $userInfo["promote_account"];
            $data_spend['order_number'] = $val['order_number'];
            $data_spend['pay_order_number'] = $payOrderNumber;
            $data_spend['props_name'] = $val["props_name"] ? : '';
            $data_spend['discount_type'] = $val['discount_type']?:0;
            $data_spend['discount'] = $val['discount']?:10;
            $data_spend['cost'] = $val["cost"];//原价
            $data_spend['pay_time'] = $val['pay_time'];
            $data_spend['pay_status'] =  $val['pay_status'];
            $data_spend['pay_game_status'] = $val['pay_game_status'];
            $data_spend['extra_param'] = '';
            $data_spend['pay_way'] = $val["pay_way"];
            $data_spend['pay_amount'] = $val["pay_amount"];
            $data_spend['spend_ip'] = $val['spend_ip'];
            $data_spend['sdk_version'] = $gameInfo['sdk_version'];
            $data_spend['small_id'] = 0;
            $data_spend['small_nickname'] = '';
            $data_spend['coupon_record_id'] = 0;
            $data_spend['is_weiduan'] = 0;
            $data_spend['update_time'] = time();
            //第三方平台信息
            $data_spend['extend'] = $val['trade_no']?:'';
            $data_spend['platform_id'] = $platform_id;
            $data_spend['platform_pay_type'] = $val["pay_type"];
            //支付角色信息额外参数
            if(!$userInfo || !$gameInfo){
                $data_spend['goods_reserve'] = "第三方平台传值user_id:".$val['user_id'].",game_id:".$val['game_id'];
            }else{
                $data_spend['goods_reserve'] = '';
            }
            $data_spend['product_id'] = '';
            $data_spend['currency_code'] = 'CNY';
            $data_spend['update_time'] = 0;
            $data_spend['pay_promote_id'] = 0;
            $sid = $this->insertGetId($data_spend);
            if($sid > 0){
                //生成订单ID数组
                array_push($spendIds,$sid);
            }else{
                //订单存入失败则终止订单插入
                break;
            }
        }
        return $spendIds;
    }

    /**
     * 存入一条订单
     *
     * @param array $param
     * @param array $user_entity
     * @return mixed
     * @author: Juncl
     * @time: 2021/08/31 11:54
     */
      public function add_spend($param=[],$user_entity=[])
      {
          $data_spend['user_id'] = $user_entity["id"]?:$param['user_id'];
          $data_spend['user_account'] = $user_entity["account"];
          $data_spend['game_id'] = $param["game_id"];
          $data_spend['game_name'] = $param['game_name'];
          $data_spend['server_id'] = $param["server_id"] ? : 0;
          $data_spend['server_name'] = $param["server_name"] ? : '';
          $data_spend['game_player_id'] = $param['game_player_id']?:0;
          $data_spend['game_player_name'] = $param["game_player_name"] ? : '';
          $data_spend['role_level'] = $param["role_level"] ? : 0;
          $data_spend['promote_id'] = $user_entity["promote_id"];
          $data_spend['promote_account'] = $user_entity["promote_account"];
          $data_spend['order_number'] = '';
          $data_spend['pay_order_number'] = $param["pay_order_number"];
          $data_spend['props_name'] = $param["props_name"] ? : '';
          $data_spend['discount_type'] = $param['discount_type']?:0;
          $data_spend['discount'] = $param['discount']?($param['discount']*10):10;
          $data_spend['cost'] = $param["cost"];//原价
          $data_spend['pay_time'] = time();
          $data_spend['pay_status'] =  0;
          $data_spend['pay_game_status'] = 0;
          $data_spend['extra_param'] = '';
          $data_spend['pay_way'] = $param["pay_way"];
          $data_spend['pay_amount'] = $param["pay_amount"];
          $data_spend['spend_ip'] = $param['spend_ip'];
          $data_spend['sdk_version'] = $param["sdk_version"];
          $data_spend['small_id'] = 0;
          $data_spend['small_nickname'] = '';
          $data_spend['coupon_record_id'] = 0;
          $data_spend['is_weiduan'] = 0;
          //第三方平台信息
          $data_spend['extend'] = $param['trade_no']?:'';
          $data_spend['platform_id'] = $param["platform_id"];
          $data_spend['platform_pay_type'] = $param["pay_type"];
          //支付角色信息额外参数
          $data_spend['goods_reserve'] = '';
          $data_spend['product_id'] = '';
          $data_spend['currency_code'] = 'CNY';
          $data_spend['update_time'] = 0;
          $data_spend['pay_promote_id'] = 0;
          $res = $this->insertGetId($data_spend);
          return $res;
      }
}