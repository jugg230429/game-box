<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */
namespace app\common\logic;

use app\channelsite\logic\WelfareLogic;
use app\game\model\GameModel;
use app\promote\model\PromoteUserBindDiscountModel;
use app\promote\model\PromoteUserWelfareModel;
use app\recharge\model\CouponModel;
use app\common\controller\BaseHomeController;
use app\recharge\model\CouponRecordModel;
use app\recharge\model\PromoteBindRecordModel;
use app\recharge\model\SpendBindDiscountModel;
use app\recharge\model\SpendWelfareModel;
use app\recharge\validate\CouponValidate;
use think\Db;

/**
 * 文档模型逻辑层公共模型
 * 所有逻辑层模型都需要继承此模型
 */
class PromoteLogic{

    /**
     * @函数或方法说明
     * #福利列表
     * @author: 郭家屯
     * @since: 2020/11/4 15:02
     */
    public function welfare($promote_id=0,$game_id=0)
    {
        $logic = new WelfareLogic();
        //返利数据
        $rebate = $logic->get_promote_rebate($promote_id,$game_id);
        //折扣数据-可以作废
//        $welfare = $logic->get_promote_welfare($promote_id,$game_id);
        //绑币充值折扣数据-可以作废
//        $bind = $logic->get_promote_bind_discount($promote_id,$game_id);
        //代充数据
        $agent = $logic->get_promote_agent($promote_id,$game_id);
//        $data = array_merge_recursive($rebate,$welfare,$agent,$bind);
        $data = array_merge_recursive($rebate,$agent);
        $result= array();
        foreach ($data as $key => $info) {
            $result[$info['game_id']][] = $info;
        }
        $result = array_values($result);
        foreach ($result as $key=>$v){
            if($v){
                $result[$key] = array_reduce($v, 'array_merge', array());
            }
        }
        return $result;
    }

    /**
     * @函数或方法说明
     * @获取代金券列表
     * @author: 郭家屯
     * @since: 2020/11/4 15:03
     */
    public function get_coupon_lists($promote_id=0,$request=[])
    {
        $coupon = new CouponModel();
        $base = new BaseHomeController();
        $map['is_delete'] = 0;
        $coupon_name = $request['coupon_name'];
        if($coupon_name != ''){
            $map['coupon_name'] = ['like','%'.$coupon_name.'%'];
        }
        $game_id = $request['game_id'];
        if ($game_id > 0) {
            $map['pgame_id'] = $game_id;
        }
        $limit_money = $request['limit_money'];
        if($limit_money != '' ){
            if($limit_money == 0){
                $map['limit_money'] = 0;
            }else{
                $map['limit_money'] = ['gt',0];
            }
        }
        $map['pid'] = $promote_id;
        $map['coupon_type'] = 6;

        // 排除测试中的游戏
        $mGame = new GameModel();
        $testGameIds = $mGame 
            -> where(['test_game_status' => '1'])
            ->whereOr(['only_for_promote' => '1'])  // 渠道独占的游戏不显示
            -> column('id');
        if (!empty($testGameIds)) {
            $map['pgame_id'] = ['not in', $testGameIds];
        }

        $data = $base->data_list($coupon, $map, []);
        return $data;
    }

    /**
     * 获取官方对当前游戏,当前渠道的配置首冲续充折扣数据
     * by:byh   2021-8-26 20:06:12
     * @param $promote_id 渠道id
     * @param $game_id 游戏id
     */
    public function gf_game_promote_welfare($promote_id=0,$game_id=0)
    {
        if(empty($promote_id) || empty($game_id)){
            return [];
        }
        $res = [];
        $map['type'] = ['in',[1,3,4,5]];
        $model = new SpendWelfareModel();
        $welfare = $model
            ->field('id,game_id,type,first_discount,continue_discount,first_status,continue_status')
            ->where($map)
            ->where('game_id',$game_id)
            ->find();
        if(!$welfare){
            return $res;
        }
        //部分渠道处理
        if($welfare['type'] == 4){
            $where['p.promote_id'] = $promote_id;
            //查找部分渠道数据
            $welfare = $model->alias('w')
                ->field('w.id,w.type,game_id,first_discount,continue_discount,first_status,continue_status')
                ->join(['tab_spend_welfare_promote'=>'p'],'w.id=p.welfare_id','left')
                ->where($where)
                ->where('w.game_id',$game_id)
                ->find();
            if(!$welfare){
                return $res;
            }
        }
        //20210915更改-部分玩家处理-查询折扣玩家中是否有当前渠道的,有,返回折扣,无,则没有折扣
        if($welfare['type']==5){
            //查询出玩家的ids
            $user_data = Db::table('tab_spend_welfare_game_user')->where('welfare_id',$welfare['id'])->select();
            $user_flag = false;
            if(!empty($user_data)){
                foreach ($user_data as $k => $v){
                    //循环判断玩家的渠道是否等于当前渠道,属于,跳出循环
                    $user_pid = get_user_entity($v['game_user_id'],false,'promote_id')['promote_id']??0;
                    if($user_pid == $promote_id){
                        $user_flag = true;
                        break;
                    }
                }
            }
            if($user_flag == false){
                return $res;
            }
        }

        if($welfare['first_status'] == 1 && $welfare['first_discount']<10 && $welfare['first_discount']>0){//首充判断
            $res['first_discount'] = $welfare['first_discount'];
        }
        if($welfare['continue_status'] == 1 && $welfare['continue_discount']<10 && $welfare['continue_discount']>0){//续充判断
            $res['continue_discount'] = $welfare['continue_discount'];
        }
        return $res;
    }
    /**
     * 渠道自定义添加游戏首充续充数据
     * by:byh 2021-8-27 17:12:53
     * @param $data 保存的参数
     */
    public function add_user_welfare($data)
    {
        if(empty($data)){
            return false;
        }
        $model = new PromoteUserWelfareModel();
        //判断游戏是否已经配置过
        $info = $model->field('id')->where(['game_id'=>$data['game_id'],'promote_id'=>PID])->find();
        if($info){
            return -1;
        }
        $save = [
            'promote_id'=>PID,
            'promote_account'=>get_promote_name(PID),
            'game_id'=>$data['game_id'],
            'game_name'=>get_game_name($data['game_id']),
            'first_discount'=>$data['first_discount']??'10.00',
            'continue_discount'=>$data['continue_discount']??'10.00',
            'first_status'=>$data['first_status']??0,
            'continue_status'=>$data['continue_status']??0,
            'create_time'=>time(),
        ];
        //判断处理玩家数据和类型
        if(empty($data['game_user_account'])){
            $save['type'] = 1;//全部玩家
        }else{
            $save['type'] = 2;//部分玩家
        }
        $model->startTrans();
        $res1 = $model->insertGetId($save);
        //处理部分玩家账号
        $res2 = true;
        //处理玩家账号
        if(!empty($data['game_user_account'])){
            $user_arr = explode(PHP_EOL, $data['game_user_account']);
            $user_arr = array_map('trim', array_filter($user_arr));
            $user_ids = array_map('get_user_id', $user_arr);
            $data['game_user_ids'] = array_values(array_flip(array_flip($user_ids)));
            if(empty($data['game_user_ids'])){
                return -2;
            }
            //根据获取的玩家账号,查询判断玩家的信息,并获取id保存
            foreach ($data['game_user_ids'] as $key=>$v){
                //判断玩家的顶级渠道是否是当前渠道
                $top_promote_id = get_user_entity($v,false,'promote_id')['promote_id']??0;
                if($top_promote_id != 0){
                    $top_promote_id = get_top_promote_id($top_promote_id);
                }
                if($top_promote_id != PID){
                    return 1001;//存在玩家不属于当前渠道,不可对玩家设置折扣
                }
                $arr['game_user_id'] = $v;
                $arr['user_welfare_id'] = $res1;
                $welfare_game_user[] = $arr;
            }
            $res2 = Db::table('tab_promote_user_welfare_game_user')->insertAll($welfare_game_user);
        }
        if($res1 && $res2){
            $model->commit();
            return 200;
        }
        $model->rollback();
        return false;
    }
    /**
     * 查询当前游戏对应的渠道包设置的玩家最低折扣
     * by:byh 2021-8-27 18:28:06
     */
    public function get_promote_game_user_limit_discount($game_id,$promote_id)
    {
        $limit_discount = 10;//默认无折扣
        if(empty($game_id) || empty($promote_id)){
            return $limit_discount;
        }
        $map['game_id'] = $game_id;
        $map['promote_id'] = $promote_id;
        $limit = Db::table('tab_promote_apply')->where($map)->value('user_limit_discount');
        $limit_discount = empty($limit)?$limit_discount:$limit;
        return $limit_discount;
    }
    /**
     * 获取渠道游戏自定义首充续充详情
     * by:byh 2021-8-27 19:23:14
     * @param $id 渠道对游戏自定义首充续充数据的id
     */
    public function get_user_welfare_detail($id)
    {
        if(empty($id)){
            return [];
        }
        $model = new PromoteUserWelfareModel();
        $data = $model->where('id',$id)->find();
        //判断类型,如果是部分玩家,处理部分玩家数据
        if(!empty($data)){
            $data = $data->toArray();
            if($data['type'] == 2){
                //获取玩家ids转账号
                $game_user_id_arr = Db::table('tab_promote_user_welfare_game_user')->field('game_user_id')->where('user_welfare_id',$id)->select()->toArray();
                $game_user_id = array_column($game_user_id_arr,'game_user_id');
                $game_user_list = implode(PHP_EOL,array_map('get_user_name',$game_user_id));
                $data['game_user_list'] = $game_user_list;
            }else{
                $data['game_user_list'] = '';
            }
        }
        return $data;
    }
    /**
     * 编辑保存渠道游戏自定义首充续充数据
     * by:byh 2021-8-27 20:39:28
     * @param $data 修改保存的参数
     */
    public function edit_user_welfare($data)
    {
        if(empty($data)){
            return false;
        }
        $model = new PromoteUserWelfareModel();
        $save = [
            'first_discount'=>$data['first_discount']??'10.00',
            'continue_discount'=>$data['continue_discount']??'10.00',
            'first_status'=>$data['first_status']??0,
            'continue_status'=>$data['continue_status']??0,
            'update_time'=>time(),
        ];
        //判断处理玩家数据和类型
        if(empty($data['game_user_account'])){
            $save['type'] = 1;//全部玩家
        }else{
            $save['type'] = 2;//部分玩家
        }
        $model->startTrans();
        $where['id'] = $data['id'];
        $res1 = $model->where($where)->update($save);
        //处理部分玩家账号
        $res2 = $res3 = true;
        //处理玩家账号
        if(!empty($data['game_user_account'])){
            $user_arr = explode(PHP_EOL, $data['game_user_account']);
            $user_arr = array_map('trim', array_filter($user_arr));
            $user_ids = array_map('get_user_id', $user_arr);
            $data['game_user_ids'] = array_values(array_flip(array_flip($user_ids)));
            if(empty($data['game_user_ids'])){
                return -2;
            }
            //查询当前数据关联的玩家id,已存在的去除
            $oldgame_user = Db::table('tab_promote_user_welfare_game_user')->field('game_user_id')->where('user_welfare_id',$data['id'])->select()->toArray();
            $oldgame_user = array_column($oldgame_user,'game_user_id');
            $delgame_user = array_diff($oldgame_user,$data['game_user_ids']);
            $addgame_user = array_diff($data['game_user_ids'],$oldgame_user);
            if($delgame_user){
                Db::table('tab_promote_user_welfare_game_user')->where('user_welfare_id',$data['id'])->where('game_user_id','in',$delgame_user)->delete();
            }
            if($addgame_user){
                foreach ($addgame_user as $key=>$v){
                    //判断玩家的顶级渠道是否是当前渠道
                    $top_promote_id = get_user_entity($v,false,'promote_id')['promote_id']??0;
                    if($top_promote_id != 0){
                        $top_promote_id = get_top_promote_id($top_promote_id);
                    }
                    if($top_promote_id != PID){
                        return 1001;//存在玩家不属于当前渠道,不可对玩家设置折扣
                    }
                    $arr['game_user_id'] = $v;
                    $arr['user_welfare_id'] = $data['id'];
                    $welfare_game_user[] = $arr;
                }
                Db::table('tab_promote_user_welfare_game_user')->insertAll($welfare_game_user);
            }
            //根据获取的玩家账号,查询判断玩家的信息,并获取id保存
//            foreach ($data['game_user_ids'] as $key=>$v){
//                $arr['game_user_id'] = $v;
//                $arr['user_welfare_id'] = $res1;
//                $welfare_game_user[] = $arr;
//            }
//            //删除
//            $res3 = Db::table('tab_promote_user_welfare_game_user')->insertAll($welfare_game_user);
        }else{//更改为全部玩家,删除之前可能存在的部分玩家数据
            Db::table('tab_promote_user_welfare_game_user')->where('user_welfare_id',$data['id'])->delete();
        }
        if($res1 && $res2){
            $model->commit();
            return 200;
        }
        $model->rollback();
        return false;
    }

    /**
     * 获取官方对当前游戏,当前渠道的配置绑币首冲续充折扣数据
     * by:byh   2021-8-26 20:06:12
     * @param $promote_id 渠道id
     * @param $game_id 游戏id
     */
    public function gf_game_promote_bind_discount($promote_id=0,$game_id=0)
    {
        if(empty($promote_id) || empty($game_id)){
            return [];
        }
        //查询游戏中的绑币充值首充续充折扣配置
        $game_ds_arr = get_game_attr_entity($game_id,'bind_recharge_discount,bind_continue_recharge_discount');
        $res = [
            'first_discount'=>$game_ds_arr['bind_recharge_discount']??'10.00',
            'continue_discount'=>$game_ds_arr['bind_continue_recharge_discount']??'10.00'
        ];
        $map['type'] = ['in',[1,3,4,5]];
        $model = new SpendBindDiscountModel();
        $bind = $model
            ->field('id,game_id,type,first_discount,continue_discount,first_status,continue_status')
            ->where($map)
            ->where('game_id',$game_id)
            ->find();
        if(!$bind){
            return $res;
        }
        //部分渠道处理
        if($bind['type'] == 4){
            $where['p.promote_id'] = $promote_id;
            //查找部分渠道数据
            $bind = $model->alias('b')
                ->field('b.id,b.type,game_id,first_discount,continue_discount,first_status,continue_status')
                ->join(['tab_spend_bind_discount_promote'=>'p'],'b.id=p.bind_discount_id','left')
                ->where($where)
                ->where('b.game_id',$game_id)
                ->find();
            if(!$bind){
                return $res;
            }
        }
        //20210915更改-部分玩家处理-查询折扣玩家中是否有当前渠道的,有,返回折扣,无,则没有折扣
        if($bind['type']==5){
            //查询出玩家的ids
            $user_data = Db::table('tab_spend_bind_discount_game_user')->where('bind_discount_id',$bind['id'])->select();
            $user_flag = false;
            if(!empty($user_data)){
                foreach ($user_data as $k => $v){
                    //循环判断玩家的渠道是否等于当前渠道,属于,跳出循环
                    $user_pid = get_user_entity($v['game_user_id'],false,'promote_id')['promote_id']??0;
                    if($user_pid == $promote_id){
                        $user_flag = true;
                        break;
                    }
                }
            }
            if($user_flag == false){
                return $res;
            }
        }
        //有数据,则按照当前处理,不继承游戏编辑中的配置
        if($bind['first_status'] == 1){//首充判断
            $res['first_discount'] = $bind['first_discount'];
        }else{
            $res['first_discount'] = '10.00';
        }
        if($bind['continue_status'] == 1){//续充判断
            $res['continue_discount'] = $bind['continue_discount'];
        }else{
            $res['continue_discount'] = '10.00';
        }
        return $res;
    }
    /**
     * 渠道自定义添加游戏首充续充数据
     * by:byh 2021-8-27 17:12:53
     * @param $data 保存的参数
     */
    public function add_user_bind_discount($data)
    {
        if(empty($data)){
            return false;
        }
        $model = new PromoteUserBindDiscountModel();
        //判断游戏是否已经配置过
        $info = $model->field('id')->where(['game_id'=>$data['game_id'],'promote_id'=>PID])->find();
        if($info){
            return -1;
        }
        $save = [
            'promote_id'=>PID,
            'promote_account'=>get_promote_name(PID),
            'game_id'=>$data['game_id'],
            'game_name'=>get_game_name($data['game_id']),
            'first_discount'=>$data['first_discount']??'10.00',
            'continue_discount'=>$data['continue_discount']??'10.00',
            'first_status'=>$data['first_status']??0,
            'continue_status'=>$data['continue_status']??0,
            'create_time'=>time(),
        ];
        //判断处理玩家数据和类型
        if(empty($data['game_user_account'])){
            $save['type'] = 1;//全部玩家
        }else{
            $save['type'] = 2;//部分玩家
        }
        $model->startTrans();
        $res1 = $model->insertGetId($save);
        //处理部分玩家账号
        $res2 = true;
        //处理玩家账号
        if(!empty($data['game_user_account'])){
            $user_arr = explode(PHP_EOL, $data['game_user_account']);
            $user_arr = array_map('trim', array_filter($user_arr));
            $user_ids = array_map('get_user_id', $user_arr);
            $data['game_user_ids'] = array_values(array_flip(array_flip($user_ids)));
            if(empty($data['game_user_ids'])){
                return -2;
            }
            //根据获取的玩家账号,查询判断玩家的信息,并获取id保存
            foreach ($data['game_user_ids'] as $key=>$v){
                //判断玩家的顶级渠道是否是当前渠道
                $top_promote_id = get_user_entity($v,false,'promote_id')['promote_id']??0;
                if($top_promote_id != 0){
                    $top_promote_id = get_top_promote_id($top_promote_id);
                }
                if($top_promote_id != PID){
                    return 1001;//存在玩家不属于当前渠道,不可对玩家设置折扣
                }
                $arr['game_user_id'] = $v;
                $arr['user_bind_discount_id'] = $res1;
                $welfare_game_user[] = $arr;
            }
            $res2 = Db::table('tab_promote_user_bind_discount_game_user')->insertAll($welfare_game_user);
        }
        if($res1 && $res2){
            $model->commit();
            return 200;
        }
        $model->rollback();
        return false;
    }
    /**
     * 获取渠道游戏自定义首充续充详情
     * by:byh 2021-8-27 19:23:14
     * @param $id 渠道对游戏自定义首充续充数据的id
     */
    public function get_user_bind_discount_detail($id)
    {
        if(empty($id)){
            return [];
        }
        $model = new PromoteUserBindDiscountModel();
        $data = $model->where('id',$id)->find();
        //判断类型,如果是部分玩家,处理部分玩家数据
        if(!empty($data)){
            $data = $data->toArray();
            if($data['type'] == 2){
                //获取玩家ids转账号
                $game_user_id_arr = Db::table('tab_promote_user_bind_discount_game_user')->field('game_user_id')->where('user_bind_discount_id',$id)->select()->toArray();
                $game_user_id = array_column($game_user_id_arr,'game_user_id');
                $game_user_list = implode(PHP_EOL,array_map('get_user_name',$game_user_id));
                $data['game_user_list'] = $game_user_list;
            }else{
                $data['game_user_list'] = '';
            }
        }
        return $data;
    }
    /**
     * 编辑保存渠道游戏自定义首充续充数据
     * by:byh 2021-8-27 20:39:28
     * @param $data 修改保存的参数
     */
    public function edit_user_bind_discount($data)
    {
        if(empty($data)){
            return false;
        }
        $model = new PromoteUserBindDiscountModel();
        $save = [
            'first_discount'=>$data['first_discount']??'10.00',
            'continue_discount'=>$data['continue_discount']??'10.00',
            'first_status'=>$data['first_status']??0,
            'continue_status'=>$data['continue_status']??0,
            'update_time'=>time(),
        ];
        //判断处理玩家数据和类型
        if(empty($data['game_user_account'])){
            $save['type'] = 1;//全部玩家
        }else{
            $save['type'] = 2;//部分玩家
        }
        $model->startTrans();
        $where['id'] = $data['id'];
        $res1 = $model->where($where)->update($save);
        //处理部分玩家账号
        $res2 = $res3 = true;
        //处理玩家账号
        if(!empty($data['game_user_account'])){
            $user_arr = explode(PHP_EOL, $data['game_user_account']);
            $user_arr = array_map('trim', array_filter($user_arr));
            $user_ids = array_map('get_user_id', $user_arr);
            $data['game_user_ids'] = array_values(array_flip(array_flip($user_ids)));
            if(empty($data['game_user_ids'])){
                return -2;
            }
            //查询当前数据关联的玩家id,已存在的去除
            $oldgame_user = Db::table('tab_promote_user_bind_discount_game_user')->field('game_user_id')->where('user_bind_discount_id',$data['id'])->select()->toArray();
            $oldgame_user = array_column($oldgame_user,'game_user_id');
            $delgame_user = array_diff($oldgame_user,$data['game_user_ids']);
            $addgame_user = array_diff($data['game_user_ids'],$oldgame_user);
            if($delgame_user){
                Db::table('tab_promote_user_bind_discount_game_user')->where('user_bind_discount_id',$data['id'])->where('game_user_id','in',$delgame_user)->delete();
            }
            if($addgame_user){
                foreach ($addgame_user as $key=>$v){
                    //判断玩家的顶级渠道是否是当前渠道
                    $top_promote_id = get_user_entity($v,false,'promote_id')['promote_id']??0;
                    if($top_promote_id != 0){
                        $top_promote_id = get_top_promote_id($top_promote_id);
                    }
                    if($top_promote_id != PID){
                        return 1001;//存在玩家不属于当前渠道,不可对玩家设置折扣
                    }
                    $arr['game_user_id'] = $v;
                    $arr['user_bind_discount_id'] = $data['id'];
                    $welfare_game_user[] = $arr;
                }
                Db::table('tab_promote_user_bind_discount_game_user')->insertAll($welfare_game_user);
            }
        }else{//更改为全部玩家,删除之前可能存在的部分玩家数据
            Db::table('tab_promote_user_bind_discount_game_user')->where('user_bind_discount_id',$data['id'])->delete();
        }
        if($res1 && $res2){
            $model->commit();
            return 200;
        }
        $model->rollback();
        return false;
    }

    /**
     * 渠道获取自定义首充续充折扣数据
     * by:byh 2021-9-1 20:15:17
     */
    public function get_user_welfare_list($promote_id=0,$request=[])
    {
        if($request['type']){//折扣对象
            $map['type'] = $request['type'];
        }
        if($request['game_id']){
            $map['game_id'] = $request['game_id'];
        }

        if($request['first_status'] != ''){
            $map['first_status'] = $request['first_status'];
        }
        if($request['continue_status'] != ''){
            $map['continue_status'] = $request['continue_status'];
        }
        //查询顶级渠道的id
        $top_promote_id = get_top_promote_id($promote_id);
        $map['promote_id'] = $top_promote_id;
        $model = new PromoteUserWelfareModel();
        $base = new BaseHomeController;
        $exend['order'] = 'id desc';
        $data = $base->data_list($model,$map,$exend)->each(function ($item) {
            $item['create_time_wap'] = date('Y-m-d H:i:s',$item['create_time']);
            //查询管理后台对游戏对当前渠道设置的折扣信息
            $gf_discount = $this->gf_game_promote_welfare(PID,$item['game_id']);
            $item['gf_first_discount'] = $gf_discount['first_discount']??'--';
            $item['gf_continue_discount'] = $gf_discount['continue_discount']??'--';
            return $item;
        });

        return $data;
    }

    /**
     * 渠道获取渠道自定义绑币首充续充折扣数据
     * by:byh 2021-9-1 19:21:05
     */
    public function get_user_bind_discount_list($promote_id=0,$request=[])
    {
        if($request['type']){//折扣对象
            $map['type'] = $request['type'];
        }
        if($request['game_id']){
            $map['game_id'] = $request['game_id'];
        }

        if($request['first_status'] != ''){
            $map['first_status'] = $request['first_status'];
        }
        if($request['continue_status'] != ''){
            $map['continue_status'] = $request['continue_status'];
        }
        //查询顶级渠道的id
        $top_promote_id = get_top_promote_id($promote_id);
        $map['promote_id'] = $top_promote_id;
        $model = new PromoteUserBindDiscountModel();
        $base = new BaseHomeController;
        $exend['order'] = 'id desc';
        $data = $base->data_list($model,$map,$exend)->each(function ($item) {
            $item['create_time_wap'] = date('Y-m-d H:i:s',$item['create_time']);
            //查询管理后台对游戏对当前渠道设置的折扣信息
            $gf_discount = $this->gf_game_promote_bind_discount(PID,$item['game_id']);
            $item['gf_first_discount'] = $gf_discount['first_discount']??'--';
            $item['gf_continue_discount'] = $gf_discount['continue_discount']??'--';
            return $item;
        });
        return $data;
    }

    /**
     * 新增-选择游戏的时候就判断是否已经设置过折扣
     * by:byh 2021-9-17 14:53:34
     * @param int $game_id 游戏id
     * @param int $type 查询类型 1=充值折扣 2=绑币充值折扣
     */
    public function judgeGameIsSetDiscount($game_id=0,$type=0)
    {
        if(empty($game_id)){
            return json(['code'=>1]);
        }
        if(!in_array($type,[1,2])){
            return json(['code'=>0,'msg'=>'参数错误']);
        }
        switch ($type){
            case 1://折扣
                $model = new PromoteUserWelfareModel();
                break;
            case 2://绑币折扣
                $model = new PromoteUserBindDiscountModel();
                break;
        }
        //判断游戏是否已经配置过
        $info = $model->field('id')->where(['game_id'=>$game_id,'promote_id'=>PID])->find();
        if($info){
            return json(['code'=>0,'msg'=>'该游戏已设置折扣']);
        }
        return json(['code'=>1,'msg'=>'success']);
    }

}
