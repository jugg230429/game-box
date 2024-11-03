<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */
namespace app\recharge\logic;

use app\common\controller\BaseController;
use app\promote\model\PromoteagentModel;
use app\promote\model\PromoteapplyModel;
use app\recharge\model\CouponModel;
use app\recharge\model\CouponRecordModel;
use app\recharge\model\SpendBindDiscountModel;
use app\recharge\model\SpendRebateModel;
use app\recharge\model\SpendRebateRecordModel;
use app\recharge\model\SpendWelfareModel;
use think\Db;

/**
 * 文档模型逻辑层公共模型
 * 所有逻辑层模型都需要继承此模型
 */
class RebateLogic{

    /**
     * @函数或方法说明
     * @获取列表
     * @param array $post
     *
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/1/9 11:24
     */
  public function getLists($request=[]){
      $spend = new SpendRebateModel;
      $base = new BaseController;
      $type = $request['type'];
      if($type > 0){
          $map['type'] = $type;
      }
      $game_id = $request['game_id'];
      if ($game_id != '') {
          $map['game_id'] = $game_id;
      }
      $status = $request['status'];
      if ($status != '') {
          $map['status'] = $status;
      }
      if($request['end_time']!=''){
          $map['end_time'] = [['lt',$request['end_time']],['neq',0]];
      }
      $promote_id = $request['promote_id'];
      if ($type == 4 && $promote_id != '') {
          $map['rp.promote_id'] = $promote_id;
          $exend['join1'] = [['tab_spend_rebate_promote' => 'rp'], 'tab_spend_rebate.id=rp.rebate_id', 'left'];
          $exend['field'] = 'tab_spend_rebate.id,type,game_name,money,ratio,status,start_time,end_time,tab_spend_rebate.create_time,rp.promote_id,bind_status';
          $data = $base->data_list_join($spend, $map, $exend)->each(function ($item, $key) {
              $item['cycle_time'] = ($item['start_time'] == 0 ? '永久':date('Y-m-d H:i:s',$item['start_time']))." 至 ".($item['end_time'] == 0 ? '永久':date('Y-m-d H:i:s',$item['end_time']));
              return $item;
          });
      }else{
          $exend['field'] = 'id,game_name,type,money,ratio,status,start_time,end_time,create_time,bind_status';
          $data = $base->data_list($spend, $map, $exend)->each(function ($item, $key) {
              $item['cycle_time'] = ($item['start_time'] == 0 ? '永久':date('Y-m-d H:i:s',$item['start_time']))." 至 ".($item['end_time'] == 0 ? '永久':date('Y-m-d H:i:s',$item['end_time']));
              return $item;
          });
      }

      return $data;
  }

    /**
     * @函数或方法说明
     * @返利列表
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/1/10 17:37
     */
  public function getRecordLists($request=[])
  {
      $model = new SpendRebateRecordModel();
      $base = new BaseController;
      $game_id = $request['game_id'];
      if ($game_id != '') {
          $map['game_id'] = $game_id;
      }
      $user_account = $request['user_account'];
      if ($user_account != '') {
          $map['user_account'] = ['like','%'.$user_account.'%'];
      }
      $start_time = $request['start_time'];
      $end_time = $request['end_time'];
      if ($start_time && $end_time) {
          $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
      } elseif ($end_time) {
          $map['create_time'] = ['lt', strtotime($end_time) + 86400];
      } elseif ($start_time) {
          $map['create_time'] = ['egt', strtotime($start_time)];
      }
      $exend['field'] = 'id,pay_order_number,game_name,user_account,pay_amount,ratio,ratio_amount,promote_account,create_time';
      $data = $base->data_list($model, $map, $exend);
      return $data;
  }

    /**
     * @函数或方法说明
     * @添加返利设置
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/1/9 13:52
     */
  public function addRebate($data=[])
  {
      if(empty($data['start_time'])){
        $data['start_time'] = time();
      }else{
        $data['start_time'] = strtotime($data['start_time']);
      }
      $data['end_time'] = $data['end_time'] ? strtotime($data['end_time']) : 0;
      if($data['status'] == 1){
          $data['money'] = implode('/',$data['money']);
          $data['ratio'] = implode('/',$data['ratio']);
      }
      $model = new SpendRebateModel();
      $result = $model->field(true)->insertGetId($data);
      if($result){
          if($data['type'] == 4){
              foreach ($data['promote_ids'] as $key=>$v){
                  $arr['promote_id'] = $v;
                  $arr['rebate_id'] = $result;
                  $rebate_promote[] = $arr;
              }
              $result1 = Db::table('tab_spend_rebate_promote')->insertAll($rebate_promote);
              if(!$result1){
                  $model->where('id',$result)->delete();
                  return false;
              }
          }elseif ($data['type'] == 5){
              foreach ($data['game_user_ids'] as $key=>$v){
                  $arr['game_user_id'] = $v;
                  $arr['rebate_id'] = $result;
                  $rebate_game_user[] = $arr;
              }
              $result2 = Db::table('tab_spend_rebate_game_user')->insertAll($rebate_game_user);
              if(!$result2){
                  $model->where('id',$result)->delete();
                  return false;
              }
          }
          return true;
      }else{
          return false;
      }
  }

    /**
     * @函数或方法说明
     * @修改返利设置
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/1/9 13:52
     */
    public function editRebate($data=[])
    {
        if(empty($data['start_time'])){
            $data['start_time'] = time();
        }else{
            $data['start_time'] = strtotime($data['start_time']);
        }
        $data['end_time'] = $data['end_time'] ? strtotime($data['end_time']) : 0;
        if($data['status'] == 1){
            $data['money'] = implode('/',$data['money']);
            $data['ratio'] = implode('/',$data['ratio']);
        }
        $model = new SpendRebateModel();
        $result = $model->field(true)->where('id',$data['id'])->update($data);
        if($result !== false){
            if($data['type'] == 4){
                $oldpromote = Db::table('tab_spend_rebate_promote')->field('promote_id')->where('rebate_id',$data['id'])->select()->toArray();
                $oldpromote = array_column($oldpromote,'promote_id');
                $delpromote = array_diff($oldpromote,$data['promote_ids']);
                $addpromote = array_diff($data['promote_ids'],$oldpromote);
                if($delpromote){
                    Db::table('tab_spend_rebate_promote')->where('rebate_id',$data['id'])->where('promote_id','in',$delpromote)->delete();
                }
                if($addpromote){
                    foreach ($addpromote as $key=>$v){
                        $arr['promote_id'] = $v;
                        $arr['rebate_id'] = $data['id'];
                        $rebate_promote[] = $arr;
                    }
                    Db::table('tab_spend_rebate_promote')->insertAll($rebate_promote);
                }
            }
            if($data['type'] == 5){
                $oldgameuser = Db::table('tab_spend_rebate_game_user')->field('game_user_id')->where('rebate_id',$data['id'])->select()->toArray();
                $oldgameuser = array_column($oldgameuser,'game_user_id');
                $delgameuser = array_diff($oldgameuser,$data['game_user_ids']);
                $addgameuser = array_diff($data['game_user_ids'],$oldgameuser);
                if($delgameuser){
                    Db::table('tab_spend_rebate_game_user')->where('rebate_id',$data['id'])->where('game_user_id','in',$delgameuser)->delete();
                }
                if($addgameuser){
                    foreach ($addgameuser as $key=>$v){
                        $arr['game_user_id'] = $v;
                        $arr['rebate_id'] = $data['id'];
                        $rebate_gameuser[] = $arr;
                    }
                    Db::table('tab_spend_rebate_game_user')->insertAll($rebate_gameuser);
                }
            }
            return true;
        }else{
            return false;
        }
    }


    /**
     * @函数或方法说明
     * @获取详情
     * @param int $id
     *
     * @author: 郭家屯
     * @since: 2020/1/10 9:42
     */
  public function get_detail($id=0){
      $model = new SpendRebateModel();
      $data = $model->where('id',$id)->find()->toArray();
      return $data;
  }
    /**
     * @函数或方法说明
     * @获取折扣详情
     * @param int $id
     *
     * @author: 郭家屯
     * @since: 2020/1/10 9:42
     */
    public function get_welfare_detail($id=0){
        $model = new SpendWelfareModel();
        $data = $model->where('id',$id)->find()->toArray();
        return $data;
    }


    /**
     * @函数或方法说明
     * @部分渠道获取可添加渠道信息
     * @param int $game_id
     *
     * @return array|false|mixed|\PDOStatement|string|\think\Collection
     *
     * @author: 郭家屯
     * @since: 2020/1/9 17:59
     */
  public function getPromoteLists($condition=[],$type=0)
  {
      $map['status'] = 1;
      //$map['parent_id'] = 0;//一级推广员
      $promote_list = get_promote_list($map,"id,account,promote_level");
      if($type){
          $rebate = Db::table('tab_spend_welfare')->alias('w')
                  ->field('wp.promote_id')
                  ->join(['tab_spend_welfare_promote'=>'wp'],'wp.welfare_id=w.id','left')
                  ->where('w.type',4)
                  ->where($condition)
                  ->select();
      }else{
          $rebate = Db::table('tab_spend_rebate')->alias('sr')
                  ->field('srp.promote_id')
                  ->join(['tab_spend_rebate_promote'=>'srp'],'srp.rebate_id=sr.id','left')
                  ->where('sr.type',4)
                  ->where($condition)
                  ->select();
      }
      if($rebate){
          $rebate = $rebate->toArray();
          $newrebate = array_flip(array_column($rebate,'promote_id'));
          foreach ($promote_list as $key=>$v){
              if(isset($newrebate[$v['id']])){
                  unset($promote_list[$key]);
              }
          }
      }
      return $promote_list;
  }

    /**
     * 获取对应游戏的玩家信息-更改为获取全部玩家
     */
    public function getGameUserLists($map = [],$game_id,$flag=0)
    {
        $where = [
            'puid'=>0,//只显示大号
            'lock_status'=>1,
//            'fgame_id'=>$game_id
        ];
        $user_list = Db::table('tab_user')->field('id,account')
            ->where($where)->select();
        $user_list = empty($user_list)?[]:$user_list->toArray();
        if($flag){
            $rebate = Db::table('tab_spend_welfare')->alias('w')
                ->field('wgu.game_user_id')
                ->join(['tab_spend_welfare_game_user'=>'wgu'],'wgu.welfare_id=w.id','left')
                ->where('w.type',4)
                ->where($map)
                ->select();
        }else{
            $rebate = Db::table('tab_spend_rebate')->alias('sr')
                ->field('srgu.game_user_id')
                ->join(['tab_spend_rebate_game_user'=>'srgu'],'srgu.rebate_id=sr.id','left')
                ->where('sr.type',5)
                ->where($map)
                ->select();
        }
        if($rebate){
            $rebate = $rebate->toArray();
            $newrebate = array_flip(array_column($rebate,'game_user_id'));
            foreach ($user_list as $key=>$v){
                if(isset($newrebate[$v['id']])){
                    unset($user_list[$key]);
                }
            }
        }
        return $user_list;


    }

    /**
     * @函数或方法说明
     * @获取折扣列表数据
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/1/11 10:08
     */
  public function getWelfareLists($request=[])
  {
      $spend = new SpendWelfareModel();
      $base = new BaseController;
      $type = $request['type'];
      if($type > 0){
          $map['type'] = $type;
      }
      $game_id = $request['game_id'];
      if ($game_id != '') {
          $map['game_id'] = $game_id;
      }
      $first_status = $request['first_status'];
      if ($first_status != '') {
          $map['first_status'] = $first_status;
      }
      $continue_status = $request['continue_status'];
      if ($continue_status != '') {
          $map['continue_status'] = $continue_status;
      }
      $promote_id = $request['promote_id'];
      if ($type == 4 && $promote_id != '') {
          $map['wp.promote_id'] = $promote_id;
          $exend['join1'] = [['tab_spend_welfare_promote' => 'wp'], 'tab_spend_welfare.id=wp.welfare_id', 'left'];
          $exend['field'] = 'tab_spend_welfare.id,type,game_name,first_discount,continue_discount,first_status,continue_status,op_id,tab_spend_welfare.create_time,wp.promote_id';
          $data = $base->data_list_join($spend, $map, $exend);
      }else{
          $exend['field'] = 'id,game_name,type,first_discount,continue_discount,first_status,continue_status,op_id,create_time';
          $data = $base->data_list($spend, $map, $exend);
      }
      return $data;
  }

    /**
     * @函数或方法说明
     * @获取返利折扣推广员
     * @author: 郭家屯
     * @since: 2020/1/11 10:25
     */
    public function get_rebate_promote()
    {
        $map['status'] = 1;
        $map['parent_id'] = 0;
        $data = get_promote_list($map,"id,account");
        return $data?:[];
    }

    /**
     * @函数或方法说明
     * @修改折扣状态
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/1/11 11:17
     */
    public function changeWelfareStatus($request=[]){
        $model = new SpendWelfareModel();
        $status = $request['status'] == 1 ? 0 : 1;
        $result = $model->where('id',$request['id'])->setField($request['name'],$status);
        return $result;
    }

    /**
     * @函数或方法说明
     * @添加折扣设置
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/1/9 13:52
     */
    public function addWelfare($data=[])
    {
        $model = new SpendWelfareModel();
        $data['create_time'] = time();
        $data['op_id'] = cmf_get_current_admin_id();
        $result = $model->field(true)->insertGetId($data);
        if($result){
            if($data['type'] == 4){
                foreach ($data['promote_ids'] as $key=>$v){
                    $arr['promote_id'] = $v;
                    $arr['welfare_id'] = $result;
                    $welfare_promote[] = $arr;
                }
                $result1 = Db::table('tab_spend_welfare_promote')->insertAll($welfare_promote);
                if(!$result1){
                    $model->where('id',$result)->delete();
                    return false;
                }
            }
            if($data['type'] == 5){
                foreach ($data['game_user_ids'] as $key=>$v){
                    $arr['game_user_id'] = $v;
                    $arr['welfare_id'] = $result;
                    $welfare_game_user[] = $arr;
                }
                $result2 = Db::table('tab_spend_welfare_game_user')->insertAll($welfare_game_user);
                if(!$result2){
                    $model->where('id',$result)->delete();
                    return false;
                }
            }
            return true;
        }else{
            return false;
        }
    }

    /**
     * @函数或方法说明
     * @修改返利设置
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/1/9 13:52
     */
    public function editWelfare($data=[])
    {
        $model = new SpendWelfareModel();
        $result = $model->field(true)->where('id',$data['id'])->update($data);
        if($result !== false){
            if($data['type'] == 4){
                $oldpromote = Db::table('tab_spend_welfare_promote')->field('promote_id')->where('welfare_id',$data['id'])->select()->toArray();
                $oldpromote = array_column($oldpromote,'promote_id');
                $delpromote = array_diff($oldpromote,$data['promote_ids']);
                $addpromote = array_diff($data['promote_ids'],$oldpromote);
                if($delpromote){
                    Db::table('tab_spend_welfare_promote')->where('welfare_id',$data['id'])->where('promote_id','in',$delpromote)->delete();
                }
                if($addpromote){
                    foreach ($addpromote as $key=>$v){
                        $arr['promote_id'] = $v;
                        $arr['welfare_id'] = $data['id'];
                        $welfare_promote[] = $arr;
                    }
                    Db::table('tab_spend_welfare_promote')->insertAll($welfare_promote);
                }
            }
            if($data['type'] == 5){
                $oldgame_user = Db::table('tab_spend_welfare_game_user')->field('game_user_id')->where('welfare_id',$data['id'])->select()->toArray();
                $oldgame_user = array_column($oldgame_user,'game_user_id');
                $delgame_user = array_diff($oldgame_user,$data['game_user_ids']);
                $addgame_user = array_diff($data['game_user_ids'],$oldgame_user);
                if($delgame_user){
                    Db::table('tab_spend_welfare_game_user')->where('welfare_id',$data['id'])->where('game_user_id','in',$delgame_user)->delete();
                }
                if($addgame_user){
                    foreach ($addgame_user as $key=>$v){
                        $arr['game_user_id'] = $v;
                        $arr['welfare_id'] = $data['id'];
                        $welfare_game_user[] = $arr;
                    }
                    Db::table('tab_spend_welfare_game_user')->insertAll($welfare_game_user);
                }
            }
            return true;
        }else{
            return false;
        }
    }


    /**
     * @函数或方法说明
     * @获取代充折扣列表数据
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/1/11 10:08
     */
    public function getAgentLists($request=[])
    {
        $spend = new PromoteagentModel();
        $base = new BaseController;
        $promote_id = $request['promote_id'];
        if($promote_id > 0){
            $map['promote_id'] = $promote_id;
        }
        $game_id = $request['game_id'];
        if ($game_id >0) {
            $map['game_id'] = $game_id;
        }
        $status = $request['status'];
        if ($status != '') {
            $map['status'] = $status;
        }
//        $exend['field'] = 'id,game_name,promote_account,game_discount,promote_discount,status,op_id,create_time';
        $exend['field'] = 'id,game_name,promote_account,game_discount,game_continue_discount,op_id,create_time,
                            promote_discount_first,promote_discount_continued,promote_first_status,promote_continue_status';
        $exend['order'] = "id desc";
        $data = $base->data_list($spend, $map, $exend);
        return $data;
    }
    /**
     * @函数或方法说明
     * @修改代充状态
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/1/11 11:17
     */
    public function changeAgentStatus($request=[]){
        $model = new PromoteagentModel();
        $status = $request['status'] == 1 ? 0 : 1;
        $result = $model->where('id',$request['id'])->setField($request['name'],$status);
        return $result;
    }

    /**
     * @函数或方法说明
     * @添加代充设置
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/1/9 13:52
     */
    public function addAgent($data=[])
    {
        if(empty($data['promote_id']))return false;
        $model = new PromoteagentModel();
        $model->startTrans();
        //查询游戏的折扣配置
        $game_arr = get_game_attr_entity($data['game_id'],'discount,continue_discount');
        $save = [];
        foreach ($data['promote_id'] as $k => $v){
            //查询渠道是否申请了游戏
            $map['promote_id'] = $v;
            $map['game_id'] = $data['game_id'];
            $rebate = $model->field('id')->where($map)->find();
            if(!empty($rebate)){
                return -1;
            }
            $arr['promote_id'] = $v;
            $arr['promote_account'] = get_promote_name($v);
            $arr['game_id'] = $data['game_id'];
            $arr['game_name'] = $data['game_name'];
            $arr['game_discount'] = $game_arr['discount']??10;
            $arr['game_continue_discount'] = $game_arr['continue_discount']??10;
            $arr['promote_discount_first'] = $data['promote_discount_first'];
            $arr['promote_discount_continued'] = $data['promote_discount_continued'];
            $arr['create_time'] = time();
            $arr['op_id'] = cmf_get_current_admin_id();
            $save[] = $arr;
        }
        $result = $model->insertAll($save);

        if($result){
            $model->commit();
            return $result;
        }else{
            $model->rollback();
            return false;
        }
    }

    /**
     * @函数或方法说明
     * @修改代充折扣设置
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/1/9 13:52
     */
    public function editAgent($data=[])
    {
        $model = new PromoteagentModel();
//        $result = $model->field(true)->where('id',$data['id'])->setField('promote_discount',$data['promote_discount']);
        $result = $model->field(true)->where('id',$data['id'])->update(['promote_discount_first'=>$data['promote_discount_first'],'promote_discount_continued'=>$data['promote_discount_continued']]);
        if($result !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     *改-根据游戏id获取申请当前游戏的一级渠道
     */
    public function getPromoteGamePromote($request=[])
    {
        $game_id = $request['game_id'];
        if(empty($game_id))return [];
        $model = new PromoteapplyModel();
        $data = $model->alias('ga')
                ->field('ga.promote_id,p.account')
                ->join(['tab_promote'=>'p'],'p.id=ga.promote_id','left')
                ->where('ga.status',1)
                ->where('ga.game_id',$game_id)
                ->where('p.promote_level',1)//一级渠道
                ->where('p.status',1)//渠道状态
                ->select()->toArray();
        return $data?:[];
    }

    /**
     * @函数或方法说明
     * @获取代充折扣详情
     * @param int $id
     *
     * @author: 郭家屯
     * @since: 2020/1/10 9:42
     */
    public function get_agent_detail($id=0){
        $model = new PromoteagentModel();
        $data = $model->where('id',$id)->find()->toArray();
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取代金券列表数据
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/1/11 10:08
     */
    public function getCouponLists($request=[])
    {
        $coupon = new CouponModel();
        $base = new BaseController;
        $map['is_delete'] = 0;
        $coupon_name = $request['coupon_name'];
        if($coupon_name != ''){
            $map['coupon_name'] = ['like','%'.$coupon_name.'%'];
        }
        $game_id = $request['game_id'];
        if ($game_id != '') {
            $map['game_id'] = $game_id;
        }
        $status = $request['status'];
        if ($status != '') {
            $map['status'] = $status;
        }
        $mold = $request['mold'];
        if ($mold != '') {
            $map['mold'] = $mold;
        }
        $limit_money = $request['limit_money'];
        if($limit_money != '' ){
            if($limit_money == 0){
                $map['limit_money'] = 0;
            }else{
                $map['limit_money'] = ['gt',0];
            }
        }
        $map['coupon_type'] = ['in',[0,4,5]];
        $data = $base->data_list($coupon, $map, []);
        return $data;
    }

    /**
     * @函数或方法说明
     * @添加代金券设置
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/1/9 13:52
     */
    public function addCoupon($data = [])
    {
        $model = new CouponModel();
        if ($data['mold'] == '1') {

            //游戏支持多选,批量添加代金券
            foreach ($data['game_id'] as $game_id) {
                $data['game_id'] = $game_id;
                $data['game_name'] = get_game_name($game_id);
                $result = $model -> field(true) -> insertGetId($data);
                if ($result) {
                    if ($data['type'] == 4) {
                        foreach ($data['promote_ids'] as $key => $v) {
                            $arr['promote_id'] = $v;
                            $arr['coupon_id'] = $result;
                            $rebate_promote[] = $arr;
                        }
                        $result1 = Db ::table('tab_coupon_promote') -> insertAll($rebate_promote);
                        if (!$result1) {
                            $model -> where('id', $result) -> delete();
                        }
                    }
                }
            }
            return true;
        } else {

            $result = $model -> field(true) -> insertGetId($data);
            if ($result) {
                if ($data['type'] == 4) {
                    foreach ($data['promote_ids'] as $key => $v) {
                        $arr['promote_id'] = $v;
                        $arr['coupon_id'] = $result;
                        $rebate_promote[] = $arr;
                    }
                    $result1 = Db ::table('tab_coupon_promote') -> insertAll($rebate_promote);
                    if (!$result1) {
                        $model -> where('id', $result) -> delete();
                        return false;
                    }
                }
                return true;
            } else {
                return false;
            }

        }

    }

    /**
     * @函数或方法说明
     * @修改代金券状态
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/1/11 11:17
     */
    public function changeCouponStatus($request=[]){
        $model = new CouponModel();
        $status = $request['status'] == 1 ? 0 : 1;
        $result = $model->where('id',$request['id'])->setField('status',$status);
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
    public function get_coupon_detail($id=0){
        $model = new CouponModel();
        $data = $model->where('id',$id)->find()->toArray();
        return $data;
    }
    /**
     * @函数或方法说明
     * @修改代金券设置
     * @param array $data
     *
     * @author: 郭家屯
     * @since: 2020/1/9 13:52
     */
    public function editCoupon($data=[])
    {
        $model = new CouponModel();

        $saveData = [];
        $saveData['status'] = $data['status'];
        $saveData['coupon_name'] = $data['coupon_name'];
        $saveData['limit_money'] = $data['limit_money'];
        $saveData['money'] = $data['money'];
        $saveData['stock'] = $data['stock'];
        $saveData['limit'] = $data['limit'];
        $saveData['receive_start_time'] = strtotime($data['receive_start_time']);
        $saveData['receive_end_time'] = strtotime($data['receive_end_time']);
        $saveData['start_time'] = strtotime($data['start_time']);
        $saveData['end_time'] = strtotime($data['end_time']);
        $result = $model -> field(true) -> where('id', $data['id']) -> update($saveData);
        if ($result !== false) {
            if($data['type'] == 4){
                $oldpromote = Db::table('tab_coupon_promote')->field('promote_id')->where('coupon_id',$data['id'])->select()->toArray();
                $oldpromote = array_column($oldpromote,'promote_id');
                $delpromote = array_diff($oldpromote,$data['promote_ids']);
                $addpromote = array_diff($data['promote_ids'],$oldpromote);
                if($delpromote){
                    Db::table('tab_coupon_promote')->where('coupon_id',$data['id'])->where('promote_id','in',$delpromote)->delete();
                }
                if($addpromote){
                    foreach ($addpromote as $key=>$v){
                        $arr['promote_id'] = $v;
                        $arr['coupon_id'] = $data['id'];
                        $coupon_promote[] = $arr;
                    }
                    Db::table('tab_coupon_promote')->insertAll($coupon_promote);
                }
            }
            return true;
        } else {
            return false;
        }
    }


    /**
     * @函数或方法说明
     * @代金券领取列表
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/1/10 17:37
     */
    public function getCouponRecord($request=[],$map=[])
    {
        $model = new CouponRecordModel();
        $base = new BaseController;
        $user_account = $request['user_account'];
        if ($user_account != '') {
            $map['user_account'] = ['like','%'.$user_account.'%'];
        }
        $coupon_name = $request['coupon_name'];
        if ($coupon_name != '') {
            $map['coupon_name'] = ['like','%'.$coupon_name.'%'];
        }
        $game_id = $request['game_id'];
        if ($game_id != '') {
            $map['game_id'] = $game_id;
        }
        $status = $request['status'];
        if($status != ''){
            if($status == 3){
               $map['is_delete'] = 1;
            }elseif($status == 2){
                $map['status'] = 0;
                $map['end_time'] = [['lt',time()],['neq',0]];
            }elseif($status == 1){
                $map['status'] = $status;
            }else{
                $map['is_delete'] = 0;
                $map['status'] = 0;
                $map['end_time'] = [['gt',time()],['eq',0],'or'];
            }
        }
        $get_way = $request['get_way'];
        if($get_way != ''){
            $map['get_way'] = $get_way;
        }
        $exend['field'] = 'id,user_id,user_account,coupon_name,game_name,mold,money,limit_money,status,create_time,update_time,cost,pay_amount,get_way,end_time,is_delete';
        $data = $base->data_list($model, $map, $exend);
        return $data;
    }

    /**
     * @函数或方法说明
     * @累计汇总
     * @author: 郭家屯
     * @since: 2020/2/4 9:16
     */
    public function get_coupon_total($request=[],$map=[])
    {
        //累计统计
        $model = new CouponRecordModel();
        $base = new BaseController;
        $user_account = $request['user_account'];
        if ($user_account != '') {
            $map['user_account'] = ['like','%'.$user_account.'%'];
        }
        $coupon_name = $request['coupon_name'];
        if ($coupon_name != '') {
            $map['coupon_name'] = ['like','%'.$coupon_name.'%'];
        }
        $game_id = $request['game_id'];
        if ($game_id != '') {
            $map['game_id'] = $game_id;
        }
        $status = $request['status'];
        if($status != ''){
            if($status == 2){
                $map['status'] = 0;
                $map['end_time'] = ['lt',time()];
            }else{
                $map['status'] = $status;
            }
        }
        $get_way = $request['get_way'];
        if($get_way != ''){
            $map['get_way'] = $get_way;
        }
        $exend['field'] = 'sum(pay_amount) as total,sum(cost) as totalcost';
        $total = $base->data_list_select($model, $map, $exend);
        return $total;
    }

    /**
     * @函数或方法说明
     * @发放代金券
     * @author: 郭家屯
     * @since: 2020/2/4 13:43
     */
    public function grant($request=[])
    {
        $model = new CouponModel();
        $coupon_id = $request['coupon_id'];
        $coupon = $model->where('id',$coupon_id)->find()->toArray();
        $stock = count($request['user_id']);
        if($stock > $coupon['stock']) {
            return ['status'=>false,'msg'=>'库存不足'];
        }
        $add = [];
        $add['coupon_id'] = $coupon['id'];
        $add['coupon_name'] = $coupon['coupon_name'];
        $add['game_id'] = $coupon['game_id'];
        $add['game_name'] = $coupon['game_name'];
        $add['mold'] = $coupon['mold'];
        $add['money'] = $coupon['money'];
        $add['limit_money'] = $coupon['limit_money'];
        $add['create_time'] = time();
        $add['update_time'] = 0;
        $add['start_time'] = $coupon['start_time'];
        $add['end_time'] = $coupon['end_time'];
        $add['get_way'] = 1;
        $user = get_user_entity($request['user_id'],false,'id,account');
        $add['user_id'] = $user['id'];
        $add['user_account'] = $user['account'];
        $recordmodel = new CouponRecordModel();
        Db::startTrans();
        try{
            $recordmodel->save($add);
            $model->where('id',$coupon_id)->setDec('stock',$stock);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ['status'=>false,'msg'=>'更新错误'];
        }
        return ['status'=>true];
    }

    /**
     * @函数或方法说明
     * @获取优惠券列表
     * @author: 郭家屯
     * @since: 2020/2/4 16:33
     */
    public function getCoupon($type=0)
    {
        if(empty($type)){
            $map['status'] = 1;
        }
        $model = new CouponModel();
        $coupon = $model
                ->field('id,coupon_name,money,mold,game_name,end_time,stock')
                ->where($map)
                ->where('pid',0)
                ->where('end_time',[['eq',0],['gt',time()]],'or')
                ->select()->each(function ($item, $key) {
                    $item['end_time'] = $item['end_time'] == 0 ? "永久" : date('m-d',$item['end_time']);
                    $item['mold'] = $item['mold'] == 0 ? "通用" : "游戏";
                    return $item;
                })->toArray();
        return $coupon;
    }

    /**
     * 获取绑币充值折扣列表
     * by-byh 2021-8-24 13:35:23
     */
    public function getBindDiscountList($request = [],$map=[])
    {
        $spend = new SpendBindDiscountModel();
        $base = new BaseController;
        $type = $request['type'];
        if($type > 0){
            $map['type'] = $type;
        }
        $game_id = $request['game_id'];
        if ($game_id != '') {
            $map['game_id'] = $game_id;
        }
        $first_status = $request['first_status'];
        if ($first_status != '') {
            $map['first_status'] = $first_status;
        }
        $continue_status = $request['continue_status'];
        if ($continue_status != '') {
            $map['continue_status'] = $continue_status;
        }
        $promote_id = $request['promote_id'];
        if ($type == 4 && $promote_id != '') {
            $map['bdp.promote_id'] = $promote_id;
            $exend['join1'] = [['tab_spend_bind_discount_promote' => 'bdp'], 'tab_spend_bind_discount.id=bdp.bind_discount_id', 'left'];
            $exend['field'] = 'tab_spend_bind_discount.id,type,game_name,first_discount,continue_discount,first_status,continue_status,op_id,tab_spend_bind_discount.create_time,bdp.promote_id';
            $data = $base->data_list_join($spend, $map, $exend);
        }else{
            $exend['field'] = 'id,game_name,type,first_discount,continue_discount,first_status,continue_status,op_id,create_time';
            $data = $base->data_list($spend, $map, $exend);
        }
        return $data;
    }
    /**
     * 绑币充值折扣获取渠道信息
     * by-byh 2021-8-24 15:06:54
     */
    public function getBindDiscountPromoteLists($where=[])
    {
        $map['status'] = 1;
        $promote_list = get_promote_list($map,"id,account,promote_level");

        $rebate = Db::table('tab_spend_bind_discount')->alias('bd')
            ->field('bdp.promote_id')
            ->join(['tab_spend_bind_discount_promote'=>'bdp'],'bdp.bind_discount_id=bd.id','left')
            ->where('bd.type',4)
            ->where($where)
            ->select();
        if($rebate){
            $rebate = $rebate->toArray();
            $newrebate = array_flip(array_column($rebate,'promote_id'));
            foreach ($promote_list as $key=>$v){
                if(isset($newrebate[$v['id']])){
                    unset($promote_list[$key]);
                }
            }
        }
        return $promote_list;
    }
    /**
     * 新增绑币充值折扣
     * by-byh 2021-8-24 15:25:49
     */
    public function addBindDiscount($data=[])
    {
        $model = new SpendBindDiscountModel();
        $data['create_time'] = time();
        $data['op_id'] = cmf_get_current_admin_id();
        $model->startTrans();
        $result = $model->field(true)->insertGetId($data);
        $result1 = $result2 = true;
        if($data['type'] == 4){
            foreach ($data['promote_ids'] as $key=>$v){
                $arr['promote_id'] = $v;
                $arr['bind_discount_id'] = $result;
                $bind_discount_promote[] = $arr;
            }
            $result1 = Db::table('tab_spend_bind_discount_promote')->insertAll($bind_discount_promote);
        }
        if($data['type'] == 5){
            //处理玩家账号
            if($data['type'] == 5 && !empty($data['game_user_account'])){
                $user_arr = explode(PHP_EOL, $data['game_user_account']);
                $user_arr = array_map('trim', array_filter($user_arr));
                $user_ids = array_map('get_user_id', $user_arr);
                $data['game_user_ids'] = array_values(array_flip(array_flip($user_ids)));
                if(empty($data['game_user_ids'])){
                    return -1;
                }
            }
            //根据获取的玩家账号,查询判断玩家的信息,并获取id保存
            foreach ($data['game_user_ids'] as $key=>$v){
                $arr['game_user_id'] = $v;
                $arr['bind_discount_id'] = $result;
                $bind_discount_game_user[] = $arr;
            }
            $result2 = Db::table('tab_spend_bind_discount_game_user')->insertAll($bind_discount_game_user);
        }
        if($result && $result1 && $result2){
            $model->commit();
            return true;
        }else{
            $model->rollback();
            return false;
        }
    }
    /**
     * 获取绑币充值数据详情
     */
    public function get_bind_discount_detail($id=0){
        $model = new SpendBindDiscountModel();
        $data = $model->where('id',$id)->find();
        return empty($data)?[]:$data->toArray();
    }
    /**
     * 修改绑币充值折扣数据
     * by:byh  2021-8-24 17:43:05
     */
    public function editBindDiscount($data=[])
    {
        $model = new SpendBindDiscountModel();
        $_save = [
            'first_discount'=>$data['first_discount'],
            'continue_discount'=>$data['continue_discount'],
        ];
        $result = $model->where('id',$data['id'])->update($_save);
        if($result !== false){
            if($data['type'] == 4){
                $oldpromote = Db::table('tab_spend_bind_discount_promote')->field('promote_id')->where('bind_discount_id',$data['id'])->select()->toArray();
                $oldpromote = array_column($oldpromote,'promote_id');
                $delpromote = array_diff($oldpromote,$data['promote_ids']);
                $addpromote = array_diff($data['promote_ids'],$oldpromote);
                if($delpromote){
                    Db::table('tab_spend_bind_discount_promote')->where('bind_discount_id',$data['id'])->where('promote_id','in',$delpromote)->delete();
                }
                if($addpromote){
                    foreach ($addpromote as $key=>$v){
                        $arr['promote_id'] = $v;
                        $arr['bind_discount_id'] = $data['id'];
                        $welfare_promote[] = $arr;
                    }
                    Db::table('tab_spend_bind_discount_promote')->insertAll($welfare_promote);
                }
            }
            if($data['type'] == 5){
                //处理玩家账号
                if($data['type'] == 5 && !empty($data['game_user_account'])){

                    $user_arr = explode(PHP_EOL, $data['game_user_account']);
                    $user_arr = array_map('trim', array_filter($user_arr));
                    $user_ids = array_map('get_user_id', $user_arr);
                    $data['game_user_ids'] = array_values(array_flip(array_flip($user_ids)));
                    if(empty($data['game_user_ids'])){
                        return -1;
                    }
                }
                $oldgame_user = Db::table('tab_spend_bind_discount_game_user')->field('game_user_id')->where('bind_discount_id',$data['id'])->select()->toArray();
                $oldgame_user = array_column($oldgame_user,'game_user_id');
                $delgame_user = array_diff($oldgame_user,$data['game_user_ids']);
                $addgame_user = array_diff($data['game_user_ids'],$oldgame_user);
                if($delgame_user){
                    Db::table('tab_spend_bind_discount_game_user')->where('bind_discount_id',$data['id'])->where('game_user_id','in',$delgame_user)->delete();
                }
                if($addgame_user){
                    foreach ($addgame_user as $key=>$v){
                        $arr['game_user_id'] = $v;
                        $arr['bind_discount_id'] = $data['id'];
                        $welfare_game_user[] = $arr;
                    }
                    Db::table('tab_spend_bind_discount_game_user')->insertAll($welfare_game_user);
                }
            }
            return true;
        }else{
            return false;
        }
    }
    /**
     * 修改绑币充值折扣的状态
     * by:byh 2021-8-24 17:01:58
     */
    public function change_bind_discount_status($request=[]){
        $model = new SpendBindDiscountModel();
        $status = $request['status'] == 1 ? 0 : 1;
        $result = $model->where('id',$request['id'])->setField($request['name'],$status);
        return $result;
    }

    /**
     * 处理绑币充值折扣类型为部分渠道(type=4)或部分玩家(type=5)的数据
     * @param $type 折扣类型
     * @param $id 绑币充值折扣数据id
     * by:byh 2021-8-25 09:29:55
     */
    public function deal_promote_user_data($type,$id)
    {
        $res = [];
        if($type == 4){
            //获取推广员页面
            $detail['promote_lists'] = Db::table('tab_spend_bind_discount_promote')->field('promote_id')->where('bind_discount_id',$id)->select()->toArray();
            $lists = array_flip(array_column($detail['promote_lists'],'promote_id'));
            $map['bd.game_id'] = $detail['game_id'];
            $map['bd.id'] = ['neq',$detail['id']];
            $promote_list = $this->getBindDiscountPromoteLists($map);
            $res = [
                'lists'=>$lists,
                'promote_list'=>$promote_list,
            ];

        }
        if($type == 5){
            //获取玩家ids转账号
            $detail['game_user_lists'] = Db::table('tab_spend_bind_discount_game_user')->field('game_user_id')->where('bind_discount_id',$id)->select()->toArray();
            $game_user_id = array_column($detail['game_user_lists'],'game_user_id');
            $game_user_list = implode(PHP_EOL,array_map('get_user_name',$game_user_id));
            $res = [
                'game_user_list'=>$game_user_list,
            ];
        }
        return $res;
    }



}
