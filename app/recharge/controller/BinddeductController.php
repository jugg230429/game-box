<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\recharge\controller;

use app\recharge\validate\BindDeductValidate;
use app\member\model\UserPlayModel;
use app\member\model\UserDeductBindModel;
use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use think\Request;
use think\Db;

//该控制器必须以下权限
class BinddeductController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_PAY != 1) {
            $this->error('请购买充值权限', url('admin/main/index'));
        }
        if (AUTH_USER != 1) {
            $this->error('请购买用户权限', url('admin/main/index'));
        }
        if (AUTH_PROMOTE != 1) {
            $this->error('请购买渠道权限', url('admin/main/index'));
        }
    }


    public function lists()
    {
        $base = new BaseController();
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time && $end_time) {
            $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['create_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['create_time'] = ['egt', strtotime($start_time)];
        }
        $user_account = $this->request->param('user_account', '');
        if($user_account){
            $map['user_account'] = ['like','%'.$user_account.'%'];
        }
        $exend['order'] = 'create_time desc';
        $exend['field'] = '*';
        $model = new UserDeductBindModel();
        $data = $base->data_list($model, $map, $exend);
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            // if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
            //     $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            // }
            $data[$k5]['user_account'] = get_user_entity2($v5['user_id'],false,'account')['account'];
        }
        
        $exend['field'] = 'sum(amount) as total';
        //累计充值
        $total = $base->data_list_select($model, $map, $exend);
        $today[0] = 0;
        $yestoday[0] = 0;
        if ((empty($start_time) || ($start_time <= (date('Y-m-d')))) && (empty($end_time) || ($end_time >= (date('Y-m-d'))))) {
            //今日充值
            $map['create_time'] = ['between', total(1, 2)];
            $today = $base->data_list_select($model, $map, $exend);
        }
        if ((empty($start_time) || ($start_time <= date("Y-m-d", strtotime("-1 day")))) && (empty($end_time) || ($end_time >= date("Y-m-d", strtotime("-1 day"))))) {
            //昨日充值
            $map['create_time'] = ['between', total(5, 2)];
            $yestoday = $base->data_list_select($model, $map, $exend);
        }
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        $this->assign("total", $total[0]);//累计充值
        $this->assign("today", $today[0]);//今日充值
        $this->assign("yestoday", $yestoday[0]);//昨日充值
        return $this->fetch();
    }


    /**
     * @函数或方法说明
     * @绑币回收
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/10/14 10:32
     */
    public function deduct()
    {
        $data = $this->request->param();
        if ($this->request->isPost()) {
            $validate = new BindDeductValidate();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $userplaymodel = new UserPlayModel();
            $userplay = $userplaymodel->field('id,user_id,user_account,game_id,game_name,bind_balance')->where('user_account',$data['account'])->where('game_id',$data['game_id'])->find();
            if(!$userplay){
                $this->error('用户未玩过该游戏');
            }
            $userplay = $userplay->toArray();
            if($userplay['bind_balance']<$data['amount']){
                $this->error('收回金额大于账户余额');
            }
            $data['user_id'] = $userplay['user_id'];
            $data['user_account'] = $userplay['user_account'];
            $data['game_name'] = $userplay['game_name'];
            $data['op_id'] = cmf_get_current_admin_id();
            $data['op_account'] = cmf_get_current_admin_name();
            $data['create_time'] = time();
            $model = new UserDeductBindModel();
            Db::startTrans();
            try{
                $model->field(true)->insert($data);
                $userplaymodel->where('id',$userplay['id'])->setDec('bind_balance',$data['amount']);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error('回收失败');
            }
            $this->success('回收成功',url('Binddeduct/lists'));
        }
        return $this->fetch();
    }

    /**
     * [get_user_balance 查询余额]
     * @return [type] [description]
     */
    public function get_user_balance()
    {
        if ($this->request->isAjax()) {
            $account = $this->request->param('account');
            $game_id = $this->request->param('game_id');
            $userplaymodel = new UserPlayModel();
            $userplay = $userplaymodel->field('id,user_id,user_account,game_id,game_name,bind_balance')->where('user_account',$account)->where('game_id',$game_id)->find();
            if(!$userplay){
                $this->error('用户未玩过该游戏');
            }
            $userplay = $userplay->toArray();
            echo json_encode(['code' => 1, 'bind_balance' => $userplay['bind_balance']]);
            exit;
        } else {
            $this->error('请求类型错误');
        }
    }

    public function get_user_game(){
        $account = $this->request->param('account');
        if(empty($account)){
            $this->error('用户名不能为空');
        }
        $model = new UserPlayModel();
        $data = $model->where('user_account',$account)->field('game_id,game_name')->select()->toArray();
        return json(['code'=>1,'data'=>$data]);
    }
}