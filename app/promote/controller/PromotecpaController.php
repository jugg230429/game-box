<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\promote\controller;

use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use app\promote\model\PromoteapplyModel;
use think\Db;

class PromotecpaController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_PROMOTE != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买渠道权限');
            } else {
                $this->error('请购买渠道权限', url('admin/main/index'));
            };
        }
        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('admin/main/index'));
            };
        }
    }

     /**
     * [cpa单独显示列表]
     * @return mixed
     */
    public function lists()
    {
//        if(PERMI <1 ){
//            return $this->redirect('ylists');
//        }elseif(PERMI == 2){
//            return $this->redirect('hlists');
//        }
        $base = new BaseController();
        $model = new PromoteapplyModel();
        //添加搜索条件
        $data = $this->request->param();
        $promote_id = $data['promote_id'];
        if ($promote_id) {
            $map['promote_id'] = $promote_id;
        }
        $game_id = $data['game_id'];
        if ($game_id) {
            $map['game_id'] = $game_id;
        }

        // 新加搜索条件 是否是cpa单独显示
        $map['cpa_alone_show'] = 1;
        $exend['order'] = 'add_cpa_alone_time desc';  // 排序按照单独设置cps的时间倒序
        // $exend['group'] = 'tab_promote_apply.id';
        $exend['field'] = 'tab_promote_apply.id,tab_promote_apply.game_id,g.game_name,pack_mark,tab_promote_apply.promote_id,apply_time,status,enable_status,dispose_time,promote_ratio,add_cps_alone_time,cps_alone_show,cpa_alone_show,add_cpa_alone_time,promote_money,g.dow_status,g.ratio,g.money,tab_promote_apply.pack_type,dow_url,relation_game_id';  //total_cost 支付流水

        $exend['join1'][] = ['tab_game' => "g"];
        $exend['join1'][] = 'g.id = tab_promote_apply.game_id';

        // $exend['join2'][] = ['tab_spend' => "s"];
        // $exend['join2'][] = 's.game_id = tab_promote_apply.game_id';
        $data = $base->data_list_join($model, $map, $exend);
        // 获取实付金额// 订单流水
        foreach($data as $key=>$val){
            $promote_id = $val->promote_id;
            $game_id = $val->game_id;
            $actual_pay_amount = Db::table('tab_promote_settlement')->where("game_id=$game_id AND top_promote_id=$promote_id AND status=1")->sum('pay_amount');
            $val->actual_pay_amount = sprintf("%.2f",$actual_pay_amount);
            $total_cost =  Db::table('tab_promote_settlement')->where("game_id=$game_id AND top_promote_id=$promote_id")->sum('cost');
            $val->total_cost = sprintf("%.2f",$total_cost);
        }

        // 获取分页显示
        // return json($data);
        // var_dump($data);exit;
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        //自动审核
        $autostatus = Db::table('tab_promote_config')->where(array('name' => 'promote_auto_audit_apply'))->value('status');
        $this->assign('autostatus', $autostatus);
        return $this->fetch();
    }

    //添加cps单独显示记录(实际是修改promote_apply表中的cps_alone_show,add_cps_alone_time 这两个字段)
    //
    public function add(){
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $promote_ids = $data['promote_id'] ?? [];
            $game_id = $data['game_id'] ?? 0;
            if(empty($promote_ids) || empty($game_id)){
                $this->error('缺少参数!');
            }
            $promote_apply_m = new PromoteapplyModel();
            $update_data = [
                'cpa_alone_show'=>1,
                'add_cpa_alone_time'=>time(),
                'promote_money'=>$data['single_money'],
            ];
            $update_res = $promote_apply_m->where("game_id=$game_id")->where('promote_id','in',$promote_ids)->update($update_data);
            if($update_res){
                // 更新成功
                $this->success('添加成功!', url('lists'));
            }else{
                $this->error('请稍后重试!');
            }
        }
        return $this->fetch();
        // echo '添加cps单独显示记录(实际是修改promote_apply表中的cpa_alone_show,add_cpa_alone_time 这两个字段)';exit;
    }
    // 删除实际上是修改 promote_apply表中的cpa_alone_show,promote_money这两个字段
    public function del(){
        $promote_apply_ids = $this->request->param('ids/a');
        $promote_apply_id = $this->request->param('id');
        $unify_money = $this->request->param('unify_money');
        if($promote_apply_id <= 0 && empty($promote_apply_ids)){
            $this->error('缺少参数!');
        }
        $promote_apply_m = new PromoteapplyModel();
        $update_data = [
            'cpa_alone_show'=>0,
            'promote_money'=>$unify_money, // 统一分成比例
        ];
        if($promote_apply_ids){
            $update_res = $promote_apply_m->where("id",'in',$promote_apply_ids)->update($update_data);
        }else{
            $update_res = $promote_apply_m->where("id=$promote_apply_id")->update($update_data);
        }
        if($update_res){
            // 更新成功
            $this->success('操作成功!', url('lists'));
        }else{
            $this->error('请稍后重试!');
        }

    }

    // 1. 获取游戏的分成比例
    // 2. 获取申请过游戏的渠道商列表
    public function get_game_ratio(){
        $data = $this->request->param();
        $game_id = intval($data['game_id']);
        if ($game_id) {
            $money = Db::table('tab_game')->where("id=$game_id")->column('money');  // 注册单价

            $list = Db::table('tab_promote_apply')
            ->alias('pa')
            ->field('pa.id,pa.promote_id,p.account')
            ->join(['tab_promote' => 'p'], 'pa.promote_id=p.id', 'INNER')
            ->where("game_id=$game_id AND p.parent_id=0 AND p.pattern=1")
            // ->where("game_id=$game_id")
            ->select()
            ->toarray();
            return json(['money'=>$money[0],'list'=>$list]);
        }
    }

}