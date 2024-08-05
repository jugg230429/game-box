<?php

namespace app\game\logic;

use app\game\model\GameModel;
use app\member\model\UserModel;
use app\common\controller\BaseHomeController;
use app\promote\model\PromoteUserBindDiscountModel;
use app\promote\model\PromoteUserWelfareModel;
use app\recharge\model\SpendBindDiscountModel;
use app\recharge\model\SpendRebateModel;
use app\recharge\model\SpendWelfareModel;
use think\Db;

class WelfareLogic
{
    public function get_game_welfare($user_id=0,$game_id=0,$type='sdk')
    {
        if($user_id > 0){
            $user = get_user_entity($user_id,false,'promote_id,parent_id');
            $promote_id = $user['promote_id'];
        }else{
            $promote_id = 0;
        }
        $res['rebate_list'] = $this->get_rebate_list($game_id,$promote_id,$type,$user_id);//充值返利
        $res['welfare_list'] = $this->get_first_charge($game_id,$promote_id,$type,$user_id);//首充续充
        $res['balance_list'] = $this->get_balance_list($game_id,$type,$promote_id,$user_id);//绑币
        return $res;
    }

    /**
     * 用户返利列表
     * @param $user_id
     * @param $game_id
     * @return array|string
     */
    public function get_rebate_list($game_id,$promote_id,$type='sdk',$user_id=0)
    {
        if($promote_id>0){
            $data = $this->get_promote_rebate($game_id,$promote_id);
        }else{
            $data = $this->get_unpromote_rebate($game_id);
        }
        //增加部分玩家的返利数据-20210719-byh-start
        if($user_id>0){
            //查询是否有玩家的返利信息
            $user_rebate = $this->get_game_user_rebate($game_id,$user_id);
            if(!empty($user_rebate)){
                $data = $user_rebate;
            }
        }
        //增加部分玩家的返利数据-20210719-byh-end
        if($type=='app'){
            return $data;
        }
        $tips = $this->rebate_list_tip($data);
        return $tips;
    }

    protected function get_game_user_rebate($game_id=0,$user_id=0)
    {
        $map = [];
        if($game_id){
            $map['game_id'] = $game_id;
        }
        $model = new SpendRebateModel();
        $rebate = $model->alias('r')
            ->field('r.id,r.ratio,r.type,r.money,r.status,r.game_name')
            ->join(['tab_spend_rebate_game_user'=>'p'],'r.id=p.rebate_id','left')
            ->where(function($query) use ($user_id){
                $query->where(function($query) use ($user_id){
                    $query->where('p.game_user_id',$user_id)->where('type',5);
                });
                $query->whereor('type','in',[1,2,3,4]);
            })
            ->where($map)
            ->where('start_time', ['<', time()], ['=', 0], 'or')
            ->where('end_time', ['>', time()], ['=', 0], 'or')
            ->select()->toArray();
        return $rebate;
    }

    /**
     * @函数或方法说明
     * @获取推广用户下的返利数据
     * @author: 郭家屯
     * @since: 2020/2/7 11:23
     */
    protected function get_promote_rebate($game_id=0,$promote_id=0)
    {
        if($game_id){
            $map['game_id'] = $game_id;
        }
        $model = new SpendRebateModel();
        $rebate = $model->alias('r')
                ->field('r.id,r.ratio,r.type,r.money,r.status,r.game_name')
                ->join(['tab_spend_rebate_promote'=>'p'],'r.id=p.rebate_id','left')
                ->where(function($query) use ($promote_id){
                    $query->where(function($query) use ($promote_id){
                        $query->where('p.promote_id',$promote_id)->where('type',4);
                    });
                    $query->whereor('type','in',[1,3]);
                })
                ->where($map)
                ->where('start_time', ['<', time()], ['=', 0], 'or')
                ->where('end_time', ['>', time()], ['=', 0], 'or')
                ->select()->toArray();
        return $rebate;
    }

    /**
     * @函数或方法说明
     * @获取官方下的返利数据
     * @author: 郭家屯
     * @since: 2020/2/7 11:23
     */
    protected function get_unpromote_rebate($game_id=0)
    {
        if($game_id){
            $map['game_id'] = $game_id;
        }
        $model = new SpendRebateModel();
        $rebate = $model->alias('r')
                ->field('id,ratio,type,money,status,game_name')
                ->where('type','in',[1,2])
                ->where('start_time', ['<', time()], ['=', 0], 'or')
                ->where('end_time', ['>', time()], ['=', 0], 'or')
                ->where($map)
                ->select()->toArray();
        return $rebate;
    }

    /**
     * 首充续充
     * @param $user_id
     * @param $game_id
     * @return array
     */
    public function get_first_charge($game_id, $promote_id, $type="sdk", $user_id=0)
    {
        if($promote_id>0){
            $data = $this->get_promote_first($game_id,$promote_id,$user_id);
        }else{
            $data = $this->get_unpromote_first($game_id,$user_id);
        }

        if($type=='app'){
            return $data;
        }
        $tips = $this->welfare_list_tip($data);
        return $tips;
    }

    protected function get_game_user_first($game_id=0,$user_id=0)
    {
        if($game_id){
            $map['game_id'] = $game_id;
        }
        $model = new SpendWelfareModel();
        $first = $model->alias('w')
            ->field('w.id,game_name,type,first_discount,continue_discount,first_status,continue_status')
            ->join(['tab_spend_welfare_game_user'=>'p'],'w.id=p.welfare_id','left')
            ->where(function($query) use ($user_id){
                $query->where(function($query) use ($user_id){
                    $query->where('p.game_user_id',$user_id)->where('type',5);
                });
                $query->whereor('type','in',[1,2,3,4]);
            })
            ->where($map)
            ->select()->each(function($item,$key){
                if($item['first_status'] == 0){
                    $item['first_discount'] = 10;
                }
                if($item['continue_status'] == 0){
                    $item['continue_discount'] = 10;
                }
                return $item;
            })->toArray();
        return $first;
    }

    /**
     * @函数或方法说明
     * @获取推广用户下的首冲数据
     * @author: 郭家屯
     * @since: 2020/2/7 11:23
     * 更改逻辑-byh
     * 备注:渠道配置>后台配置,即使配置后开关关闭,不继承享受低等级的配置
     */
    protected function get_promote_first($game_id=0,$promote_id=0,$user_id=0)
    {
        //先查询渠道是否有自定义设置
        $top_promote_id = get_top_promote_id($promote_id);
        $qd_model = new PromoteUserWelfareModel();
        $qd_where = [
            'game_id'=>$game_id,
            'promote_id'=>$top_promote_id,
        ];
        $welfare = $qd_model->where($qd_where)->find();
        if(!empty($welfare)){
            $welfare = $welfare->toArray();
            //判断渠道折扣类型和玩家是否享受折扣
            if($welfare['type'] == 2 && $user_id>0){
                $qd_user = Db::table('tab_promote_user_welfare_game_user')
                    ->field('id')
                    ->where(['user_welfare_id'=>$welfare['id'],'game_user_id'=>$user_id])
                    ->find();
                if(empty($qd_user)){//玩家不在折扣内,则不享受
                    return [];
                }
            }
            if($welfare['first_status'] != 1 && $welfare['continue_status'] != 1){
                return [];
            }
            $res['first_status'] = $welfare['first_status'];
            $res['first_discount'] = $welfare['first_discount'];
            $res['continue_status'] = $welfare['continue_status'];
            $res['continue_discount'] = $welfare['continue_discount'];
            return [$res];
        }

        $map['game_id'] = $game_id;
        $model = new SpendWelfareModel();
        $welfare = $model
            ->field('type,first_discount,continue_discount,first_status,continue_status')
            ->where($map)
            ->find();
        if(!empty($welfare)){
            $welfare = $welfare->toArray();
            //部分渠道处理
            if($welfare['type'] == 4){
                $where['p.promote_id'] = $promote_id;
                //查找部分渠道数据
                $welfare = $model->alias('bd')
                    ->field('bd.id,game_id,first_discount,continue_discount,first_status,continue_status')
                    ->join(['tab_spend_welfare_promote'=>'p'],'bd.id=p.welfare_id','left')
                    ->where($where)
                    ->where('bd.game_id',$game_id)
                    ->find();
                if(empty($welfare)){//不存在,则不享受
                    return [];
                }
            }
            //部分玩家处理-byh-20210629
            if($welfare['type'] == 5  && (session('member_auth.user_id') || $user_id>0)){
                if(empty($user_id)){
                    $where2['gu.game_user_id'] = session('member_auth.user_id');
                }else{
                    $where2['gu.game_user_id'] = $user_id;
                }
                //查找部分玩家数据
                $welfare = $model->alias('bd')
                    ->field('bd.id,game_id,first_discount,continue_discount,first_status,continue_status')
                    ->join(['tab_spend_welfare_game_user'=>'gu'],'bd.id=gu.welfare_id','left')
                    ->where($where2)
                    ->where('bd.game_id',$game_id)
                    ->find();
                if(empty($welfare)){//不存在,则不享受
                    return [];
                }
            }
            if($welfare['first_status'] != 1 && $welfare['continue_status'] != 1){
                return [];
            }
            $res['first_status'] = $welfare['first_status'];
            $res['first_discount'] = $welfare['first_discount'];
            $res['continue_status'] = $welfare['continue_status'];
            $res['continue_discount'] = $welfare['continue_discount'];
            return [$res];

        }else{
            return [];
        }
        //注释源代码,后期没问题删除
//        $first = $model->alias('w')
//                ->field('w.id,game_name,type,first_discount,continue_discount,first_status,continue_status')
//                ->join(['tab_spend_welfare_promote'=>'p'],'w.id=p.welfare_id','left')
//                ->where(function($query) use ($promote_id){
//                    $query->where(function($query) use ($promote_id){
//                        $query->where('p.promote_id',$promote_id)->where('type',4);
//                    });
//                    $query->whereor('type','in',[1,3,5]);
//                })
//                ->where($map)
//                ->select()->each(function($item,$key){
//                    if($item['first_status'] == 0){
//                        $item['first_discount'] = 10;
//                    }
//                    if($item['continue_status'] == 0){
//                        $item['continue_discount'] = 10;
//                    }
//                    return $item;
//                })->toArray();
//        return $first;
    }

    /**
     * @函数或方法说明
     * @获取官方下的首冲数据
     * @author: 郭家屯
     * @since: 2020/2/7 11:23
     */
    protected function get_unpromote_first($game_id)
    {
        if($game_id){
            $map['game_id'] = $game_id;
        }
        $model = new SpendWelfareModel();
        $first = $model
                ->field('id,game_name,type,first_discount,continue_discount,first_status,continue_status')
                ->where('type','in',[1,2])
                ->where($map)
                ->select()->toArray();
        foreach ($first as $key=>$v){
            if($v['first_status'] == 0){
                $first[$key]['first_discount'] = 10;
            }
            if($v['continue_status'] == 0){
                $first[$key]['continue_discount'] = 10;
            }
        }
        return $first;
    }

    public function get_balance_list($game_id,$type='sdk',$promote_id=0,$user_id=0)
    {
        //获取折扣-绑币充值折扣更改-byh-20210825
        $lPay = new \app\common\logic\PayLogic();
        $discount_info = $lPay -> get_detail_bind_discount($game_id, $promote_id, $user_id);
        if($type=='app' ){
            if(empty($discount_info)){
                return [];
            }else{
                return [$discount_info];//APP返回的是二维数组
            }
        }
        $bind_tip = [];
        if($discount_info['first_status']==1){
            $bind_tip[] = '首充'.$discount_info['first_discount'].'折';
        }
        if($discount_info['continue_status']==1){
            $bind_tip[] = '续充'.$discount_info['continue_discount'].'折';
        }
        return $bind_tip;
    }
    /**
     * 返利提示
     * @param $data
     * @return array
     */
    public function rebate_list_tip($data)
    {
        $tip = [];
        $data = my_sort($data,'ratio',3);
        foreach ($data as $k=>$v){
            if($v['status']==0){//没有开启档位
                $tip[] = '充值返利'.$v['ratio'].'%';
                continue;
            }
            if($v['status']==1){
                $money = explode('/',$v['money']);
                $ratio = explode('/',$v['ratio']);
                $count = count($money);
                if($count == 1){
                    $tip[] = '单次充值'.$money[0].'元及以上，返利'.$ratio[0].'%';
                    continue;
                }
                for ($i=0;$i<$count;$i++){
                    $tip[] = '单次充值'.$money[$i].'元及以上，返利'.$ratio[$i].'%';
                }
            }
        }
        return $tip;
    }

    public function welfare_list_tip($data)
    {
        $tip = [];
        foreach ($data as $k=>$v){
            if($v['first_status']==1){//开启首充
                $tip[] = '首充'.$v['first_discount'].'折';
            }
            if($v['continue_status']==1){//开启续充
                $tip[] = '续充'.$v['continue_discount'].'折';
            }
        }
        return $tip;
    }

    /**
     * 游戏绑币充值列表-更改
     * by:byh 2021-8-25 16:00:45
     */
    public function get_bind_list($map=[],$promote_id=0,$user_id=0)
    {
        if($promote_id > 0){
            $data2 = $this->get_game_promote_bind_discount($map,$promote_id,$user_id);
        }else{
            $data2 = $this->get_game_unpromote_bind_discount($map,$promote_id,$user_id);
        }
        if(!empty($data2) && !empty($data2['new_game_ids'])){
            $map['g.id'] = ['NOTIN',$data2['new_game_ids']];
        }
        //查询出游戏中设置了绑币折扣的游戏
        $map['g.sdk_area'] = 0; // 不显示海外游戏
        $model = new GameModel();
        $data1 = $model->alias('g')
            ->field('g.id as game_id,a.bind_recharge_discount as bind_first_discount,a.bind_continue_recharge_discount as bind_continue_discount,
                        relation_game_name,relation_game_id,icon,game_type_name,dow_num,game_size,sort')
            ->join(['tab_game_attr'=>'a'],'g.id=a.game_id','left')
            ->where('a.bind_recharge_discount|a.bind_continue_recharge_discount','lt',10)
            ->where('g.game_status',1)
            ->where($map)
            ->select()->toArray();
        //根据查询查询出符合要求的绑币充值折扣配置表的游戏数据

        foreach ($data1 as $key=>$v){
            $data1[$key]['bind_first_status'] = 1;
            $data1[$key]['bind_continue_status'] = 1;
            $data1[$key]['icon'] = cmf_get_image_url($v['icon']);
            $data1[$key]['url'] = url("Game/detail",['game_id'=>$v['game_id']]);
        }
        //合并两个数组
        return array_merge($data1,$data2['bind']);
    }


    /**
     * 游戏返利列表
     * @param $user_id
     * @param $game_id
     * @return array|string
     */
    public function get_game_rebate_list($promote_id=0,$map=[])
    {
       if($promote_id > 0){
           $data = $this->get_game_promote_rebate($promote_id,$map);
       }else{
           $data = $this->get_game_unpromote_rebate($map);
       }
       return $data;
    }

    /**
     * @函数或方法说明
     * @获取推广用户下的返利数据
     * @author: 郭家屯
     * @since: 2020/2/7 11:23
     */
    protected function get_game_promote_rebate($promote_id=0,$map=[])
    {
        $map['g.sdk_area'] = 0; // 不显示海外游戏
        $model = new SpendRebateModel();
        $rebate = $model->alias('r')
                ->field('game_id,r.ratio,g.relation_game_name,g.relation_game_id,g.icon,g.game_type_name,g.dow_num,g.game_size,g.sort')
                ->join(['tab_spend_rebate_promote'=>'p'],'r.id=p.rebate_id','left')
                ->join(['tab_game'=>'g'],'r.game_id=g.id','left')
                ->where(function($query) use ($promote_id){
                    $query->where(function($query) use ($promote_id){
                        $query->where('p.promote_id',$promote_id)->where('type',4);
                    });
                    $query->whereor('type','in',[1,3]);
                })
                ->where('g.game_status',1)
                ->where($map)
                ->where('start_time', ['<', time()], ['=', 0], 'or')
                ->where('end_time', ['>', time()], ['=', 0], 'or')
                ->order('g.sort desc,g.id desc')
                ->select()->toArray();
        foreach ($rebate as $key=>$v){
            $rebate[$key]['icon'] = cmf_get_image_url($v['icon']);
            $rebate[$key]['ratio'] = end(explode('/',$v['ratio']));
            $rebate[$key]['url'] = url("Game/detail",['game_id'=>$v['game_id']]);
        }
        return $rebate;
    }

    /**
     * @函数或方法说明
     * @获取官方下的返利数据
     * @author: 郭家屯
     * @since: 2020/2/7 11:23
     */
    protected function get_game_unpromote_rebate($map=[])
    {
        $map['g.sdk_area'] = 0; // 不显示海外游戏
        $model = new SpendRebateModel();
        $rebate = $model->alias('r')
                ->field('game_id,r.ratio,g.relation_game_name,g.relation_game_id,g.icon,g.game_type_name,g.dow_num,g.game_size,g.sort')
                ->join(['tab_game'=>'g'],'r.game_id=g.id','left')
                ->where('type','in',[1,2])
                ->where('g.game_status',1)
                ->where($map)
                ->where('start_time', ['<', time()], ['=', 0], 'or')
                ->where('end_time', ['>', time()], ['=', 0], 'or')
                ->order('g.sort desc,g.id desc')
                ->select()->toArray();
        foreach ($rebate as $key=>$v){
            $rebate[$key]['icon'] = cmf_get_image_url($v['icon']);
            $rebate[$key]['ratio'] = end(explode('/',$v['ratio']));
            $rebate[$key]['url'] = url("Game/detail",['game_id'=>$v['game_id']]);
        }
        return $rebate;
    }

    /**
     * 游戏首冲续充列表
     * @param $user_id
     * @param $game_id
     * @return array|string
     */
    public function get_recharge_list($promote_id=0,$map=[],$user_id=0)
    {
        if($promote_id >0){
            $data = $this->get_promote_recharge($promote_id,$map,$user_id);
        }else{
            $data = $this->get_unpromote_recharge($map,$user_id);
        }
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取推广用户下的首冲续充数据
     * @author: 郭家屯
     * @since: 2020/2/7 11:23
     */
    protected function get_promote_recharge($promote_id=0,$map=[],$user_id=0)
    {
        //根据渠道查询渠道自定义折扣的数据
        $qd_welfare = [];
        $new_game_ids = [];
        if($promote_id>0){
            $top_promote_id = get_top_promote_id($promote_id);//查询顶级渠道 一级即自己
            $qd_model = new PromoteUserWelfareModel();
            $qd_where['promote_id'] = $top_promote_id;
            $qd_welfare = $qd_model->alias('pw')
                ->field('pw.type,pw.id as welfare_id,game_id,first_discount,
                    continue_discount,pw.first_status,pw.continue_status,g.relation_game_name,
                    g.relation_game_id,g.icon,g.game_type_name,g.dow_num,g.game_size,g.sort')
                ->join(['tab_game'=>'g'],'pw.game_id=g.id','left')
                ->where('first_status|continue_status',1)
                ->where($qd_where)->select()->each(function($item,$key){
                    if($item['first_status'] == 0){
                        $item['first_discount'] = '10.00';
                    }
                    if($item['continue_status'] == 0){
                        $item['continue_discount'] = '10.00';
                    }
                    $item['icon'] = cmf_get_image_url($item['icon']);
                    $item['url'] = url("Game/detail",['game_id'=>$item['game_id']]);
                    return $item;
                });
            if(!empty($qd_welfare)){
                $qd_welfare = $qd_welfare->toArray();
                foreach ($qd_welfare as $k=>$v){
                    $new_game_ids[] = $v['game_id'];//判断删除前,保存自定义的游戏ids,后续查询排除
                    if($v['type'] ==2 && $user_id>0){
                        $_where['game_user_id'] = $user_id;
                        $_where['user_welfare_id'] = $v['welfare_id'];
                        $res = Db::table('tab_promote_user_welfare_game_user')->field('id')->where($_where)->find();
                        if(empty($res)){//当前玩家不是折扣对象,删除数据
                            unset($qd_welfare[$k]);
                        }
                    }
                }
            }
        }

        if(empty($new_game_ids)){
            $map['g.id'] = ['NOTIN',$new_game_ids];
        }

        $map['g.sdk_area'] = 0; // 不显示海外游戏
        $model = new SpendWelfareModel();
        $recharge = $model->alias('w')
                ->field('game_id,first_status,continue_status,first_discount,continue_discount,g.relation_game_name,g.relation_game_id,g.icon,g.game_type_name,g.dow_num,g.game_size,g.sort')
                ->join(['tab_spend_welfare_promote'=>'p'],'w.id=p.welfare_id','left')
                ->join(['tab_game'=>'g'],'w.game_id=g.id','left')
                ->where(function($query) use ($promote_id){
                    $query->where(function($query) use ($promote_id){
                        $query->where('p.promote_id',$promote_id)->where('type',4);
                    });
                    $query->whereor('type','in',[1,3,5]);
                })
                ->where('first_status|continue_status',1)
                ->where('g.game_status',1)
                ->where($map)
                ->select()->each(function($item,$key){
                if($item['first_status'] == 0){
                    $item['first_discount'] = '10.00';
                }
                if($item['continue_status'] == 0){
                    $item['continue_discount'] = '10.00';
                }
                $item['icon'] = cmf_get_image_url($item['icon']);
                $item['url'] = url("Game/detail",['game_id'=>$item['game_id']]);
                return $item;
            });
        if(empty($recharge)){
            return $qd_welfare;//返回渠道的数据
        }
        $recharge = $recharge->toArray();
        foreach ($recharge as $key=>$v){
            if($v['type'] ==5 && $user_id>0){
                $_where = [];
                $_where['game_user_id'] = $user_id;
                $_where['welfare_id'] = $v['welfare_id'];
                $res = Db::table('tab_spend_welfare_game_user')->where($_where)->count();
                if(!$res){//当前玩家不是折扣对象,删除数据
                    unset($recharge[$key]);
                }
            }
        }
        return array_merge($recharge,$qd_welfare);
    }

    /**
     * @函数或方法说明
     * @获取官方下的首冲续充数据
     * @author: 郭家屯
     * @since: 2020/2/7 11:23
     */
    protected function get_unpromote_recharge($map=[],$user_id=0)
    {
        $map['g.sdk_area'] = 0; // 不显示海外游戏
        $model = new SpendWelfareModel();
        $recharge = $model->alias('w')
                ->field('game_id,first_status,continue_status,first_discount,continue_discount,g.relation_game_name,g.relation_game_id,g.icon,g.game_type_name,g.dow_num,g.game_size,g.sort')
                ->join(['tab_game'=>'g'],'w.game_id=g.id','left')
                ->where('type','in',[1,2,5])
                ->where('g.game_status',1)
                ->where($map)
                ->where('first_status|continue_status',1)
                ->select()->each(function($item,$key){
                if($item['first_status'] == 0){
                    $item['first_discount'] = '10.00';
                }
                if($item['continue_status'] == 0){
                    $item['continue_discount'] = '10.00';
                }
                $item['icon'] = cmf_get_image_url($item['icon']);
                $item['url'] = url("Game/detail",['game_id'=>$item['game_id']]);
                return $item;
            });
        //根据查询出的数据处理渠道或者玩家是否符合折扣,判断并返回
        if(empty($recharge)){
            return [];
        }
        $recharge = $recharge->toArray();
        foreach ($recharge as $key=>$v){
            if($v['type'] ==5 && $user_id>0){
                $_where['game_user_id'] = $user_id;
                $_where['welfare_id'] = $v['welfare_id'];
                $res = Db::table('tab_spend_welfare_game_user')->field('id')->where($_where)->find();
                if(empty($res)){//当前玩家不是折扣对象,删除数据
                    unset($recharge[$key]);
                }
            }
        }
        return $recharge;
    }

    /**
     * 获取渠道下的绑币充值折扣数据
     * by:byh 2021-8-25 16:28:41
     * 备注:渠道配置>后台配置>游戏配置,即使配置后开关关闭,不继承享受低等级的配置
     */
    protected function get_game_promote_bind_discount($map=[],$promote_id=0,$user_id=0)
    {
        //根据渠道查询渠道自定义折扣的数据
        $qd_bind = [];
        $new_game_ids = [];
        if($promote_id>0){
            $top_promote_id = get_top_promote_id($promote_id);//查询顶级渠道 一级即自己
            $qd_model = new PromoteUserBindDiscountModel();
            $qd_where['promote_id'] = $top_promote_id;
            $qd_bind = $qd_model->alias('pb')
                ->field('pb.type,pb.id as bind_discount_id,game_id,first_discount as bind_first_discount,
                    continue_discount as bind_continue_discount,pb.first_status as bind_first_status,pb.continue_status as bind_continue_status,
                    g.relation_game_name,g.relation_game_id,g.icon,g.game_type_name,g.dow_num,g.game_size,g.sort')
                ->join(['tab_game'=>'g'],'pb.game_id=g.id','left')
                ->where('first_status|continue_status',1)
                ->where($qd_where)->select()->each(function($item,$key){
                    if($item['bind_first_status'] == 0){
                        $item['bind_first_discount'] = '10.00';
                    }
                    if($item['bind_continue_status'] == 0){
                        $item['bind_continue_discount'] = '10.00';
                    }
                    $item['icon'] = cmf_get_image_url($item['icon']);
                    $item['url'] = url("Game/detail",['game_id'=>$item['game_id']]);
                    return $item;
                });
            if(!empty($qd_bind)){
                $qd_bind = $qd_bind->toArray();

                foreach ($qd_bind as $k=>$v){
                    $new_game_ids[] = $v['game_id'];//在判断玩家是否享受折扣是否删除数据之前获取渠道自定义折扣的游戏id,因为只要配置了,玩家必须按照当前配置情况走
                    if($v['type'] ==2 && $user_id>0){
                        $_where['game_user_id'] = $user_id;
                        $_where['user_bind_discount_id'] = $v['bind_discount_id'];
                        $res = Db::table('tab_promote_user_bind_discount_game_user')->field('id')->where($_where)->find();
                        if(empty($res)){//当前玩家不是折扣对象,删除数据
                            unset($qd_bind[$k]);
                        }
                    }
                }
            }
        }
        //去除渠道自定义配置的游戏ids
        if(empty($new_game_ids)){
            $map['g.id'] = ['NOTIN',$new_game_ids];
        }

        $map['g.sdk_area'] = 0; // 不显示海外游戏
        $model = new SpendBindDiscountModel();
        $bind = $model->alias('bd')
            ->field('bd.type,bd.id as bind_discount_id,game_id,first_discount as bind_first_discount,continue_discount as bind_continue_discount,bd.first_status as bind_first_status,bd.continue_status as bind_continue_status,g.relation_game_name,g.relation_game_id,g.icon,g.game_type_name,g.dow_num,g.game_size,g.sort')
            ->join(['tab_spend_bind_discount_promote'=>'p'],'bd.id=p.bind_discount_id','left')
            ->join(['tab_game'=>'g'],'bd.game_id=g.id','left')
            ->where(function($query) use ($promote_id){
                $query->where(function($query) use ($promote_id){
                    $query->where('p.promote_id',$promote_id)->where('type',4);
                });
                $query->whereor('type','in',[1,3,5]);
            })
            ->where('g.game_status',1)
            ->where($map)
            ->where('first_status|continue_status',1)
            ->select()->each(function($item,$key){
                if($item['bind_first_status'] == 0){
                    $item['bind_first_discount'] = '10.00';
                }
                if($item['bind_continue_status'] == 0){
                    $item['bind_continue_discount'] = '10.00';
                }
                $item['icon'] = cmf_get_image_url($item['icon']);
                $item['url'] = url("Game/detail",['game_id'=>$item['game_id']]);
                return $item;
            });
        //根据查询出的数据处理渠道或者玩家是否符合折扣,判断并返回
        if(empty($bind)){
            return $qd_bind;//返回渠道的数据
        }
        $bind = $bind->toArray();
        foreach ($bind as $key=>$v){
            $new_game_ids[] = $v['game_id'];//在判断玩家是否享受折扣是否删除数据之前获取渠道自定义折扣的游戏id,因为只要配置了,玩家必须按照当前配置情况走
            if($v['type'] ==5 && $user_id>0){
                $_where = [];
                $_where['game_user_id'] = $user_id;
                $_where['bind_discount_id'] = $v['bind_discount_id'];
                $res = Db::table('tab_spend_bind_discount_game_user')->where($_where)->count();
                if(!$res){//当前玩家不是折扣对象,删除数据
                    unset($bind[$key]);
                }
            }
        }
        $new_bind =  array_merge($bind,$qd_bind);
        return [
            'bind'=>$new_bind,
            'new_game_ids'=>$new_game_ids
        ];

    }

    /**
     * 获取官方的绑币充值折扣数据
     * by:byh 2021-8-25
     */
    protected function get_game_unpromote_bind_discount($map=[],$promote_id=0,$user_id=0)
    {
        $new_game_ids = [];
        $model = new SpendBindDiscountModel();
        $bind = $model->alias('bd')
            ->field('bd.type,bd.id as bind_discount_id,game_id,first_discount as bind_first_discount,continue_discount as bind_continue_discount,first_status as bind_first_status,continue_status as bind_continue_status,g.relation_game_name,g.relation_game_id,g.icon,g.game_type_name,g.dow_num,g.game_size,g.sort')
            ->join(['tab_game'=>'g'],'bd.game_id=g.id','left')
            ->where('type','in',[1,2,5])
            ->where('g.game_status',1)
            ->where('bd.first_status|bd.continue_status',1)
            ->where($map)
            ->select()->each(function($item,$key){
                if($item['bind_first_status'] == 0){
                    $item['bind_first_discount'] = '10.00';
                }
                if($item['bind_continue_status'] == 0){
                    $item['bind_continue_discount'] = '10.00';
                }
                $item['icon'] = cmf_get_image_url($item['icon']);
                $item['url'] = url("Game/detail",['game_id'=>$item['game_id']]);
                return $item;
            });
        //根据查询出的数据处理渠道或者玩家是否符合折扣,判断并返回
        if(empty($bind)){
            return [];
        }
        $bind = $bind->toArray();
        foreach ($bind as $key=>$v){
            $new_game_ids[] = $v['game_id'];//在判断玩家是否享受折扣是否删除数据之前获取当前折扣的游戏id,因为只要配置了,玩家必须按照当前配置情况走
            if($v['type'] ==5 && $user_id>0){
                $_where['game_user_id'] = $user_id;
                $_where['bind_discount_id'] = $v['bind_discount_id'];
                $res = Db::table('tab_spend_bind_discount_game_user')->field('id')->where($_where)->find();
                if(empty($res)){//当前玩家不是折扣对象,删除数据
                    unset($bind[$key]);
                }
            }
        }
        return [
            'bind'=>$bind,
            'new_game_ids'=>$new_game_ids
        ];
    }


}
