<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\recharge\controller;
use app\promote\model\PromoteagentModel;
use app\recharge\logic\RebateLogic;
use app\recharge\model\SpendBindDiscountModel;
use app\recharge\model\SpendRebateModel;
use app\recharge\model\SpendWelfareModel;
use app\recharge\validate\AgentValidate;
use app\recharge\validate\BindDiscountValidate;
use app\recharge\validate\WelfareValidate;
use cmf\controller\AdminBaseController;
use app\recharge\validate\RebateValidate;
use app\common\controller\BaseController;
use app\recharge\model\SpendRebateRecordModel;
use think\Db;

//该控制器必须以下3个权限
class RebateController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_USER != 1) {
            $this->error('请购买用户权限', url('admin/main/index'));
        }
        if (AUTH_PAY != 1) {
            $this->error('请购买充值权限', url('admin/main/index'));
        }
        if (AUTH_GAME != 1) {
            $this->error('请购买游戏权限', url('admin/main/index'));
        }
        if (AUTH_PROMOTE != 1) {
            $this->error('请购买渠道权限', url('admin/main/index'));
        }
    }

    /**
     * @函数或方法说明
     * @返利设置列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/1/10 17:27
     */
    public function lists()
    {
        $logic = new RebateLogic();
        $request = $this->request->param();
        $data = $logic->getLists($request);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        $promote_list = $logic->get_rebate_promote();
        $this->assign('promote_list',$promote_list);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @新增返利设置
     * @author: 郭家屯
     * @since: 2019/12/30 11:06
     */
    public function add()
    {
        if($this->request->isPost()){
            $data = $this->request->param();
            $validate = new RebateValidate();
            if($data['status'] == 1){
                $data['money'] = array_filter($data['money']);
                $data['ratio'] = array_filter($data['ratio']);
                if(count($data['money']) != count($data['ratio'])){
                    $this->error('消费金额和返利比例不匹配');
                }
                if(empty($data['money'])){
                    $this->error('消费金额不能为空');
                }
                if(empty($data['ratio'])){
                    $this->error('返利比例不能为空');
                }
                asort($data['money']);
                asort($data['ratio']);
            }else{
                $data['ratio'] = $data['ratio1'];
                $data['money'] = '';
            }
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }
            $data['create_time'] = time();
            $logic = new RebateLogic();
            $result = $logic->addRebate($data);
            if($result){
                $this->success('添加成功',url('lists'));
            }else{
                $this->error('添加失败');
            }
        }
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @编辑返利列表
     * @author: 郭家屯
     * @since: 2020/1/10 9:39
     */
    public function edit()
    {
        $id = $this->request->param('id');
        if(empty($id))$this->error('参数错误');
        $logic = new RebateLogic();
        $detail = $logic->get_detail($id);
        if($this->request->isPost()){
            $data = $this->request->param();
            $data['type'] = $detail['type'];
            if($data['status'] == 1){
                $data['money'] = array_filter($data['money']);
                $data['ratio'] = array_filter($data['ratio']);
                if(count($data['money']) != count($data['ratio'])){
                    $this->error('消费金额和返利比例不匹配');
                }
                if(empty($data['money'])){
                    $this->error('消费金额不能为空');
                }
                if(empty($data['ratio'])){
                    $this->error('返利比例不能为空');
                }
                asort($data['money']);
                asort($data['ratio']);
            }else{
                $data['ratio'] = $data['ratio1'];
                $data['money'] = '';
            }
            $validate = new RebateValidate();
            if(!$validate->scene('edit')->check($data)){
                $this->error($validate->getError());
            }
            $logic = new RebateLogic();
            $result = $logic->editRebate($data);
            if($result){
                $this->success('修改成功',url('lists'));
            }else{
                $this->error('修改失败');
            }
        }
        if($detail['type'] == 4){
            //获取推广员页面
            $detail['promote_lists'] = Db::table('tab_spend_rebate_promote')->field('promote_id')->where('rebate_id',$detail['id'])->select()->toArray();
            $lists = array_flip(array_column($detail['promote_lists'],'promote_id'));
            $this->assign('lists',$lists);
            $map['sr.game_id'] = $detail['game_id'];
            $map['sr.id'] = ['neq',$detail['id']];
            $promote_list = $logic->getPromoteLists($map);
            $this->assign('promote_list',$promote_list);
            $promote_choose = $this->fetch("promote_choose");
            $this->assign('promote_choose',$promote_choose);
        }
        if($detail['type'] == 5){
            //获取游戏玩家页面
            $detail['game_user_lists'] = Db::table('tab_spend_rebate_game_user')->field('game_user_id')->where('rebate_id',$detail['id'])->select()->toArray();
            $lists = array_flip(array_column($detail['game_user_lists'],'game_user_id'));
            $this->assign('lists',$lists);
            $map['sr.game_id'] = $detail['game_id'];
            $map['sr.id'] = ['neq',$detail['id']];
            $game_user_list = $logic->getGameUserLists($map,$detail['game_id']);

            // 判断当前管理员是否有权限显示完成整手机号或完整账号
            $ys_show_admin = get_admin_privicy_two_value();
            foreach($game_user_list as $k5=>$v5){
                if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                    $game_user_list[$k5]['account'] = get_ys_string($v5['account'],$ys_show_admin['account_show_admin']);
                }
            }
            $this->assign('game_user_list',$game_user_list);
            $game_user_choose = $this->fetch("game_user_choose");
            $this->assign('game_user_choose',$game_user_choose);
        }
        if($detail['status'] == 1){
            $detail['money'] = explode('/',$detail['money']);
            $detail['ratio'] = explode('/',$detail['ratio']);
        }
        $this->assign('data',$detail);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @获取推广员
     * @return \think\response\Json
     *
     * @author: 郭家屯
     * @since: 2020/1/9 16:40
     */
    public function ajaxGetPromote(){
        if($this->request->isAjax()){
            $game_id = $this->request->param('game_id');
            $type = $this->request->param('type');
            if(empty($game_id))$this->error("参数错误");
            $logic = new RebateLogic();
            if($type){
                $map['w.game_id'] = $game_id;
            }else{
                $map['sr.game_id'] = $game_id;
            }
            $promote_list = $logic->getPromoteLists($map,$type);
            $this->assign('promote_list',$promote_list);
            $choose = $this->fetch("promote_choose");
            $this->success($choose);
        }
    }
    /**
     * @函数或方法说明ajaxGetGameUser
     * @获取游戏玩家
     * @return \think\response\Json
     *
     * @author: 郭家屯
     * @since: 2020/1/9 16:40
     */
    public function ajaxGetGameUser(){
        if($this->request->isAjax()){
            $game_id = $this->request->param('game_id');
            $flag = $this->request->param('type',0);
            if(empty($game_id))$this->error("参数错误");
            $logic = new RebateLogic();

            if($flag){
                $game_user_list = $logic->getGameUserLists(['w.game_id'=>$game_id],$game_id,$flag);
            }else{
                $game_user_list = $logic->getGameUserLists(['sr.game_id'=>$game_id],$game_id,$flag);
            }

            // 判断当前管理员是否有权限显示完成整手机号或完整账号
            $ys_show_admin = get_admin_privicy_two_value();
            foreach($game_user_list as $k5=>$v5){
                if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                    $game_user_list[$k5]['account'] = get_ys_string($v5['account'],$ys_show_admin['account_show_admin']);
                }
            }

            $this->assign('game_user_list',$game_user_list);
            $choose = $this->fetch("game_user_choose");
            $this->success($choose);
        }
    }


    /**
     * [删除返利]
     * @author 郭家屯[gjt]
     */
    public function del()
    {
        $ids = $this->request->param('ids/a');
        if (count($ids) < 1) $this->error('请选择要操作的数据');
        $model = new SpendRebateModel();
        $result = $model->where('id', 'in', $ids)->delete();
        if ($result) {
            Db::table('tab_spend_rebate_promote')->where('rebate_id','in',$ids)->delete();
            Db::table('tab_spend_rebate_game_user')->where('rebate_id','in',$ids)->delete();
            if (count($ids) > 1) {
                write_action_log("批量删除返利");
            } else {
                write_action_log("删除返利");
            }
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * @函数或方法说明
     * @返利记录
     * @author: 郭家屯
     * @since: 2020/1/10 17:34
     */
    public function record()
    {
        $logic = new RebateLogic();
        $request = $this->request->param();
        $data = $logic->getRecordLists($request);

        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $data->each(function($item){
            $ys_show_admin = get_admin_privicy_two_value();
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $item->user_account = get_ys_string($item->user_account,$ys_show_admin['account_show_admin']);
            }
        });

        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        $exend['field'] = 'sum(pay_amount) as total,sum(ratio_amount) as rtotal';
        //累计统计
        $model = new SpendRebateRecordModel();
        $base = new BaseController;
        $total = $base->data_list_select($model, [], $exend);
        $today[0] = 0;
        $yestoday[0] = 0;
        if ((empty($start_time) || ($start_time <= (date('Y-m-d')))) && (empty($end_time) || ($end_time >= (date('Y-m-d'))))) {
            //今日统计
            $map['create_time'] = ['between', total(1, 2)];
            $today = $base->data_list_select($model, $map, $exend);
        }

        if ((empty($start_time) || ($start_time <= date("Y-m-d", strtotime("-1 day")))) && (empty($end_time) || ($end_time >= date("Y-m-d", strtotime("-1 day"))))) {
            //昨日统计
            $map['create_time'] = ['between', total(5, 2)];
            $yestoday = $base->data_list_select($model, $map, $exend);
        }
        $this->assign("total", $total[0]);//累计统计
        $this->assign("today", $today[0]);//今日统计
        $this->assign("yestoday", $yestoday[0]);//昨日统计
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @首充续充折扣设置
     * @author: 郭家屯
     * @since: 2020/1/11 10:03
     */
    public function welfare()
    {
        $logic = new RebateLogic();
        $request = $this->request->param();
        $data = $logic->getWelfareLists($request);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        $promote_list = $logic->get_rebate_promote();
        $this->assign('promote_list',$promote_list);
        return $this->fetch();
    }

    /**
     * [修改折扣状态]
     * @author 郭家屯[gjt]
     */
    public function changeStatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('请选择要操作的数据');
        $data = $this->request->param();
        if(!in_array($data['name'],['first_status','continue_status'])){
            $this->error('修改状态错误');
        }
        $logic = new RebateLogic();
        $result = $logic->changeWelfareStatus($data);
        if ($result) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }


    /**
     * [删除折扣]
     * @author 郭家屯[gjt]
     */
    public function wdel()
    {
        $ids = $this->request->param('ids/a');
        if (count($ids) < 1) $this->error('请选择要操作的数据');
        $model = new SpendWelfareModel();
        $result = $model->where('id', 'in', $ids)->delete();
        if ($result) {
            Db::table('tab_spend_welfare_promote')->where('welfare_id','in',$ids)->delete();
            Db::table('tab_spend_welfare_game_user')->where('welfare_id','in',$ids)->delete();
            if (count($ids) > 1) {
                write_action_log("批量删除折扣");
            } else {
                write_action_log("删除折扣");
            }
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * @函数或方法说明
     * @新增折扣设置
     * @author: 郭家屯
     * @since: 2019/12/30 11:06
     */
    public function wadd()
    {
        if($this->request->isPost()){
            $data = $this->request->param();
            $validate = new WelfareValidate();
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }
            $logic = new RebateLogic();
            $result = $logic->addWelfare($data);
            if($result){
                $this->success('添加成功',url('welfare'));
            }else{
                $this->error('添加失败');
            }
        }
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @编辑折扣列表
     * @author: 郭家屯
     * @since: 2020/1/10 9:39
     */
    public function wedit()
    {
        $id = $this->request->param('id');
        if(empty($id))$this->error('参数错误');
        $logic = new RebateLogic();
        $detail = $logic->get_welfare_detail($id);
        if($this->request->isPost()){
            $data = $this->request->param();
            $data['type'] = $detail['type'];
            $validate = new WelfareValidate();
            if(!$validate->scene('edit')->check($data)){
                $this->error($validate->getError());
            }
            $logic = new RebateLogic();
            $result = $logic->editWelfare($data);
            if($result){
                $this->success('修改成功',url('welfare'));
            }else{
                $this->error('修改失败');
            }
        }
        if($detail['type'] == 4){
            //获取推广员页面
            $detail['promote_lists'] = Db::table('tab_spend_welfare_promote')->field('promote_id')->where('welfare_id',$detail['id'])->select()->toArray();
            $lists = array_flip(array_column($detail['promote_lists'],'promote_id'));
            $this->assign('lists',$lists);
            $map['w.game_id'] = $detail['game_id'];
            $map['w.id'] = ['neq',$detail['id']];
            $promote_list = $logic->getPromoteLists($map,1);
            $this->assign('promote_list',$promote_list);
            $promote_choose = $this->fetch("promote_choose");
            $this->assign('promote_choose',$promote_choose);
        }
        if($detail['type'] == 5){
            //获取玩家页面
            $detail['game_user_lists'] = Db::table('tab_spend_welfare_game_user')->field('game_user_id')->where('welfare_id',$detail['id'])->select()->toArray();
            $lists = array_flip(array_column($detail['game_user_lists'],'game_user_id'));
            $this->assign('lists',$lists);
            $map['w.game_id'] = $detail['game_id'];
            $map['w.id'] = ['neq',$detail['id']];
            $game_user_list = $logic->getGameUserLists($map,$detail['game_id'],true);
            $this->assign('game_user_list',$game_user_list);
            $game_user_choose = $this->fetch("game_user_choose");
            $this->assign('game_user_choose',$game_user_choose);
        }
        $this->assign('data',$detail);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @会长代充
     * @author: 郭家屯
     * @since: 2020/1/19 9:24
     */
    public function agent()
    {
        $logic = new RebateLogic();
        $request = $this->request->param();
        $data = $logic->getAgentLists($request);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * [修改会长折扣状态]
     * @author 郭家屯[gjt]
     */
    public function changeAgentStatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('请选择要操作的数据');
        $data = $this->request->param();
        if(!in_array($data['name'],['promote_first_status','promote_continue_status'])){
            $this->error('修改状态错误');
        }
        $logic = new RebateLogic();
        $result = $logic->changeAgentStatus($data);
        if ($result) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }

    }

    /**
     * [删除代充]
     * @author 郭家屯[gjt]
     */
    public function adel()
    {
        $ids = $this->request->param('ids/a');
        if (count($ids) < 1) $this->error('请选择要操作的数据');
        $model = new PromoteagentModel();
        $result = $model->where('id', 'in', $ids)->delete();
        if ($result) {
            if (count($ids) > 1) {
                write_action_log("批量删除代充");
            } else {
                write_action_log("删除代充");
            }
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * @函数或方法说明
     * @新增代充设置
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/1/19 10:22
     */
    public function aadd()
    {
        if($this->request->isPost()){
            $data = $this->request->param();
            $validate = new AgentValidate();
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }
            $logic = new RebateLogic();
            $result = $logic->addAgent($data);
            if($result === -1){
                $this->error('添加失败,当前游戏已存在渠道配置');
            }
            if($result){
                //异常预警提醒
                if($data['promote_discount_first'] <= 3 || $data['promote_discount_continued'] <= 3){
                    foreach ($data['promote_id'] as $k => $v){
                        $warning = [
                            'type'=>4,
                            'promote_id'=>$v,
                            'promote_account'=>get_promote_name($v),
                            'target'=>1,
                            'record_id'=>$result,
                            'discount_first'=>$data['promote_discount_first'],
                            'discount_continued'=>$data['promote_discount_continued'],
                            'create_time'=>time()
                        ];
                        Db::table('tab_warning')->insert($warning);
                    }

                }
                $this->success('添加成功',url('agent'));
            }else{
                $this->error('添加失败');
            }
        }
        //获取游戏
        $map['g.game_status'] = 1;
        $field = 'g.id,g.game_name,a.discount,a.continue_discount,g.sdk_version';
        $game_list = get_game_list($field,$map,'','g.id',true);//true为join联表
        $this->assign('game_list',$game_list);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @编辑代充折扣
     * @author: 郭家屯
     * @since: 2020/1/10 9:39
     */
    public function aedit()
    {
        $id = $this->request->param('id');
        if(empty($id))$this->error('参数错误');
        $logic = new RebateLogic();
        $detail = $logic->get_agent_detail($id);
        if($this->request->isPost()){
            $data = $this->request->param();
            $validate = new AgentValidate();
            if(!$validate->scene('edit')->check($data)){
                $this->error($validate->getError());
            }
            $logic = new RebateLogic();
            $result = $logic->editAgent($data);
            if($result){
                $warning = Db::table('tab_warning')->field('id')->where('promote_id',$detail['promote_id'])->where('record_id',$id)->where('type',4)->where('status',0)->find();
                if($warning){
                    if($data['promote_discount_first'] > 3 && $data['promote_discount_continued'] > 3){
                        $save['op_id'] = cmf_get_current_admin_id();
                        $save['op_account'] = cmf_get_current_admin_name();
                        $save['status'] = 1;
                        $save['op_time'] = time();
                        Db::table('tab_warning')->where('id',$warning['id'])->update($save);
                    }else{
//                        Db::table('tab_warning')->where('id',$warning['id'])->setField('discount',$data['promote_discount']);
                        Db::table('tab_warning')->where('id',$warning['id'])->update(['discount_first'=>$data['promote_discount_first'],'discount_continued'=>$data['promote_discount_continued']]);
                    }
                }else{
                    if($data['promote_discount_first'] <= 3 || $data['promote_discount_continued'] <= 3){
                        $warning_data = [
                                'type'=>4,
                                'promote_id'=>$detail['promote_id'],
                                'promote_account'=>$detail['promote_account'],
                                'target'=>1,
                                'record_id'=>$result,
//                                'discount'=>$data['promote_discount'],
                                'discount_first'=>$data['promote_discount_first'],
                                'discount_continued'=>$data['promote_discount_continued'],
                                'create_time'=>time()
                        ];
                        Db::table('tab_warning')->insert($warning_data);
                    }
                }
                $this->success('修改成功',url('agent'));
            }else{
                $this->error('修改失败');
            }
        }
        $this->assign('data',$detail);
        return $this->fetch();
    }

    /**
     * 改-根据游戏id获取申请游戏的渠道
     * by:byh 2021-9-2 19:06:58
     */
    public function ajaxGetGamePromote()
    {
        if($this->request->isAjax()){
            $data = $this->request->param();
            $logic = new RebateLogic();
            $data = $logic->getPromoteGamePromote($data);
            return json($data);
        }else{
            return json([]);
        }
    }


    /**
     * @函数或方法说明
     * @9.2更新处理 返利，折扣，代金券的旧数据处理
     * @author: 郭家屯
     * @since: 2020/9/17 17:55
     */
    public function updatedata()
    {
        //返利处理
        $rebate = Db::table('tab_spend_rebate_promote')->select();
        $rebate_data = [];
        foreach ($rebate as $key=>$v){
            $map['parent_id|top_promote_id'] = $v['promote_id'];
            $zi_promote = array_column(get_promote_list($map,'id'),'id');
            foreach ($zi_promote as $kk=>$vv){
                $data['promote_id'] = $vv;
                $data['rebate_id'] = $v['rebate_id'];
                $rebate_data[] = $data;
            }
        }
        if($rebate_data){
            Db::table('tab_spend_rebate_promote')->insertAll($rebate_data);
        }
        //折扣处理
        $zhekou = Db::table('tab_spend_welfare_promote')->select();
        $zhekou_data = [];
        foreach ($zhekou as $key=>$v){
            $map['parent_id|top_promote_id'] = $v['promote_id'];
            $zi_promote = array_column(get_promote_list($map,'id'),'id');
            foreach ($zi_promote as $kk=>$vv){
                $data1['promote_id'] = $vv;
                $data1['welfare_id'] = $v['welfare_id'];
                $zhekou_data[] = $data1;
            }
        }
        if($zhekou_data){
            Db::table('tab_spend_welfare_promote')->insertAll($zhekou_data);
        }

        //代金券处理
        $coupon = Db::table('tab_coupon_promote')->select();
        $coupon_data = [];
        foreach ($coupon as $key=>$v){
            $map['parent_id|top_promote_id'] = $v['promote_id'];
            $zi_promote = array_column(get_promote_list($map,'id'),'id');
            foreach ($zi_promote as $kk=>$vv){
                $data2['promote_id'] = $vv;
                $data2['coupon_id'] = $v['coupon_id'];
                $coupon_data[] = $data2;
            }
        }
        if($coupon_data){
            Db::table('tab_coupon_promote')->insertAll($coupon_data);
        }
        echo '处理成功';
        exit;
    }


    /**
     * 绑币充值折扣列表
     * by-byh 2021-8-24 11:55:36
     */
    public function bind_discount()
    {
        $logic = new RebateLogic();
        $request = $this->request->param();
        $data = $logic->getBindDiscountList($request);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        $promote_list = get_promote_list(['status'=>1]);
        $this->assign('promote_list',$promote_list);
        return $this->fetch();
    }
    /**
     * 新增绑币充值折扣
     * by-byh 2021-8-24 13:57:44
     */
    public function bd_add()
    {
        if($this->request->isPost()){
            $data = $this->request->param();
            $validate = new BindDiscountValidate();
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }
            $logic = new RebateLogic();
            $result = $logic->addBindDiscount($data);
            if($result === -1){
                $this->error('未查询到玩家');
            }
            if($result){
                $this->success('添加成功',url('bind_discount'));
            }else{
                $this->error('添加失败');
            }
        }
        return $this->fetch();
    }
    /**
     * 获取选择部分渠道时的信息
     * by-byh 2021-8-24 15:07:08
     */
    public function getBindDiscountPromote(){
        if($this->request->isAjax()){
            $game_id = $this->request->param('game_id');
            $type = $this->request->param('type');
            if(empty($game_id))$this->error("参数错误");
            $logic = new RebateLogic();
            $where['bd.game_id'] = $game_id;
            $promote_list = $logic->getBindDiscountPromoteLists($where);
            $this->assign('promote_list',$promote_list);
            $choose = $this->fetch("promote_choose");
            $this->success($choose);
        }
    }
    /**
     * 编辑绑币充值折扣信息
     * by-byh 2021-8-24 15:34:05
     */
    public function bd_edit()
    {
        $id = $this->request->param('id');
        if(empty($id))$this->error('参数错误');
        $logic = new RebateLogic();
        $detail = $logic->get_bind_discount_detail($id);
        if($this->request->isPost()){
            $data = $this->request->param();
            $data['type'] = $detail['type'];
            $validate = new BindDiscountValidate();
            if(!$validate->scene('edit')->check($data)){
                $this->error($validate->getError());
            }
            $logic = new RebateLogic();
            $result = $logic->editBindDiscount($data);
            if($result === -1){
                $this->error('未查询到玩家');
            }
            if($result){
                $this->success('修改成功',url('bind_discount'));
            }else{
                $this->error('修改失败');
            }
        }
        if($detail['type'] == 4){
            //获取推广员页面
            $res = $logic->deal_promote_user_data($detail['type'],$detail['id']);
            $this->assign('lists',$res['lists']);
            $this->assign('promote_list',$res['promote_list']);
            $promote_choose = $this->fetch("promote_choose");
            $this->assign('promote_choose',$promote_choose);
        }
        if($detail['type'] == 5){
            //获取玩家ids转账号
            $res = $logic->deal_promote_user_data($detail['type'],$detail['id']);
            $this->assign('game_user_list',$res['game_user_list']);
        }
        $this->assign('data',$detail);
        return $this->fetch();
    }

    /**
     * [修改绑币充值折扣状态]
     * by:byh 2021-8-24 16:51:52
     */
    public function change_bind_discount_status()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('请选择要操作的数据');
        $data = $this->request->param();
        if(!in_array($data['name'],['first_status','continue_status'])){
            $this->error('修改状态错误');
        }
        $logic = new RebateLogic();
        $result = $logic->change_bind_discount_status($data);
        if ($result) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }
    /**
     * 删除绑币充值折扣数据
     * by:byh 2021-8-24
     */
    public function bd_del()
    {
        $ids = $this->request->param('ids/a');
        if (count($ids) < 1) $this->error('请选择要操作的数据');
        $model = new SpendBindDiscountModel();
        $result = $model->where('id', 'in', $ids)->delete();
        if ($result) {
            Db::table('tab_spend_bind_discount_promote')->where('bind_discount_id','in',$ids)->delete();
            Db::table('tab_spend_bind_discount_game_user')->where('bind_discount_id','in',$ids)->delete();
            if (count($ids) > 1) {
                write_action_log("批量删除绑币充值折扣");
            } else {
                write_action_log("删除绑币充值折扣");
            }
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }


}
