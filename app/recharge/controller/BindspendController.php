<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\recharge\controller;

use app\recharge\model\SpendBindModel;
use app\recharge\model\SpendprovideModel;
use app\recharge\event\PtbsendController;
use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use think\Request;
use think\Db;

//该控制器必须以下权限
class BindspendController extends AdminBaseController
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
    }

    public function lists()
    {
        $spend = new SpendBindModel;
        $base = new BaseController;
        $account = $this->request->param('user_account', '');
        if ($account != '') {
            $map['user_account'] = ['like', '%' . addcslashes($account, '%') . '%'];
        }
        $pay_order_number = $this->request->param('pay_order_number', '');
        if ($pay_order_number != '') {
            $map['pay_order_number'] = ['like', "%" . addcslashes($pay_order_number, '%') . '%'];
        }
        $game_id = $this->request->param('game_id', '');
        if($game_id){
            $map['game_id'] = $game_id;
        }
        $spend_ip = $this->request->param('spend_ip', '');
        if ($spend_ip != '') {
            $map['pay_ip'] = ['like', '%' . addcslashes($spend_ip, '%') . '%'];
        }
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time && $end_time) {
            $map['pay_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['pay_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['pay_time'] = ['egt', strtotime($start_time)];
        }
        $promote_id = $this->request->param('promote_id', '');
        if ($promote_id != '') {
            $map['promote_id'] = $promote_id;
        }
        $pay_way = $this->request->param('pay_way', '');
        if ($pay_way != '') {
            $map['pay_way'] = $pay_way;
        }
        $pay_status = $this->request->param('pay_status', '');
        if ($pay_status != '') {
            $map['pay_status'] = $pay_status;
        }
        $exend['order'] = 'pay_time desc';
        $exend['field'] = '*';
        $data = $base->data_list($spend, $map, $exend);
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            // if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
            //     $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            // }
            $data[$k5]['user_account'] = get_user_entity2($v5['user_id'],false,'account')['account'];
        }

        $exend['field'] = 'sum(pay_amount) as total';
        //累计充值
        $map['pay_status'] = 1;
        $total = $base->data_list_select($spend, $map, $exend);
        $today[0] = 0;
        $yestoday[0] = 0;
        if ((empty($start_time) || ($start_time <= (date('Y-m-d')))) && (empty($end_time) || ($end_time >= (date('Y-m-d'))))) {
            //今日充值
            $map['pay_time'] = ['between', total(1, 2)];
            $today = $base->data_list_select($spend, $map, $exend);
        }

        if ((empty($start_time) || ($start_time <= date("Y-m-d", strtotime("-1 day")))) && (empty($end_time) || ($end_time >= date("Y-m-d", strtotime("-1 day"))))) {
            //昨日充值
            $map['pay_time'] = ['between', total(5, 2)];
            $yestoday = $base->data_list_select($spend, $map, $exend);
        }
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        $this->assign("total", $total[0]);//累计充值
        $this->assign("today", $today[0]);//今日充值
        $this->assign("yestoday", $yestoday[0]);//昨日充值
        $this->assign("page", $page);
        return $this->fetch();
    }

    public function senduserlists()
    {
        $base = new BaseController();
        $spend = new SpendprovideModel;
        $map['coin_type'] = 1;
        $account = $this->request->param('user_account', '');
        if ($account != '') {
            $usermap['account'] = ['like', '%' . addcslashes($account, '%') . '%'];
            $user_lists = Db::table('tab_user')->where($usermap)->column('id');
            $user_ids = empty($usermap) ? -1 : implode(',', $user_lists);
            $map['user_id'] = ['in', $user_ids];
        }
        $game_id = $this->request->param('game_id');
        if($game_id){
            $map['game_id'] = $game_id;
        }
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time && $end_time) {
            $map['update_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['update_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['update_time'] = ['egt', strtotime($start_time)];
        }

        $op_id = $this->request->param('admin_id', '');
        if ($op_id != '') {
            $map['op_id'] = $op_id;
        }
        $status = $this->request->param('status', '');
        if ($status != '') {
            $map['status'] = $status;
        }
        $map['type'] = 1;
        $exend['order'] = 'create_time desc';
        $exend['field'] = 'id,pay_order_number,user_id,user_account,cost,amount,status,op_id,op_account,create_time,update_time,game_name';
        $data = $base->data_list($spend, $map, $exend);
        foreach($data as &$itme){
            $itme['user_account'] = get_user_entity2($itme['user_id'],false,'account')['account'];
        }

        $exend['field'] = 'sum(amount) as total';
        //累计充值
        $map['status'] = 1;
        $total = $base->data_list_select($spend, $map, $exend);
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
        }
        if ((empty($start_time) || ($start_time <= (date('Y-m-d')))) && (empty($end_time) || ($end_time >= (date('Y-m-d'))))) {
            //今日充值
            $map['create_time'] = ['between', total(1, 2)];
            $today = $base->data_list_select($spend, $map, $exend);
        }
        if ((empty($start_time) || ($start_time <= date("Y-m-d", strtotime("-1 day")))) && (empty($end_time) || ($end_time >= date("Y-m-d", strtotime("-1 day"))))) {
            //昨日充值
            $map['create_time'] = ['between', total(5, 2)];
            $yestoday = $base->data_list_select($spend, $map, $exend);
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
     * [recharge 批量充值]
     * @return [type] [description]
     */
    public function recharge()
    {
        $data = $this->request->param();
        $ids = $data['ids'];
        static $err = 0;
        static $ok = 0;
        if (!$ids) $this->error('请选择你要操作的数据');
        $map['id'] = ['in', $ids];
        Db::startTrans();
        foreach ($ids as $key => $value) {
            $save['status'] = 1;
            $save['update_time'] = time();
            $save['op_id'] = session('ADMIN_ID');
            $save['op_account'] = get_admin_name(session('ADMIN_ID'));
            $pdata = Db::table('tab_spend_provide')->where(['id' => $value, 'status' => ['neq', 1]])->update($save);
            if (empty($pdata)) {
                $err++;
                // 回滚事务
                Db::rollback();
            } else {
                $user_id = Db::table('tab_spend_provide')->field('id,user_id,user_account,pay_order_number,amount,game_id')->where(['id' => $value])->find();
                //异常预警提醒
                if($user_id['amount'] >= 500){
                    $warning = [
                            'type'=>6,
                            'user_id'=>$user_id['user_id'],
                            'user_account'=>$user_id['user_account'],
                            'pay_order_number'=>$user_id['pay_order_number'],
                            'target'=>1,
                            'record_id'=>$user_id['id'],
                            'unusual_money'=>$user_id['amount'],
                            'create_time'=>time()
                    ];
                    Db::table('tab_warning')->insert($warning);
                }
                if (empty($user_id['user_id']) || $user_id['amount'] < 0) {
                    $err++;
                    // 回滚事务
                    Db::rollback();
                } else {
                    $userinfo = Db::table('tab_user_play')->where(['user_id' => $user_id['user_id'],'game_id'=>$user_id['game_id']])->find();
                    if(empty($userinfo)){
                        $err++;
                        // 回滚事务
                        Db::rollback();
                    }else{
                        $res = Db::table('tab_user_play')->where(['user_id' => $user_id['user_id'],'game_id'=>$user_id['game_id']])->setInc('bind_balance', $user_id['amount']);
                        if ($res !== false) {
                            $ok++;
                            // 提交事务
                            Db::commit();
                        } else {
                            $err++;
                            // 回滚事务
                            Db::rollback();
                        }
                    }

                }
            }
        }
        if($err){
            $this->error('充值成功' . $ok . '笔，失败' . $err . '笔');
        }
        $this->success('充值成功' . $ok . '笔，失败' . $err . '笔');
    }

    /**
     * 方法 delete
     *
     * @descript 删除
     *
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/23 0023 15:21
     */
    public function delete()
    {
        $data = $this->request->param();
        $ids = $data['ids'];
        if (!$ids) $this->error('请选择你要操作的数据');
        $map['id'] = ['in', $ids];
        $spend = new SpendprovideModel;
        if ($spend -> where($map) -> delete()) {
            $this -> success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    //给玩家发放 yyh
    public function senduser()
    {
        if ($this->request->isPost()) {
            $type = $this->request->param('type');
            $firstpay = new PtbsendController;
            switch ($type) {
                case 1:
                    $firstpay->send_user_single($this->request->param());
                    break;
                case 2:
                    $firstpay->send_user_more($this->request->param());
                    break;
                case 3:
                    $firstpay->send_user_excle($this->request->param());
                    break;
            }
        } else {
            return $this->fetch();
        }
    }
}