<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\recharge\controller;

use app\promote\model\PromotebindModel;
use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use think\Request;
use think\Db;

//该控制器必须以下权限
class BindagentController extends AdminBaseController
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
        $spend = new PromotebindModel;
        $base = new BaseController;
        $promote_id = $this->request->param('promote_id', '');
        if ($promote_id != '') {
            $map['promote_id'] = $promote_id;
        }
        $account = $this->request->param('user_account', '');
        if ($account != '') {
            $map['user_account'] = ['like', '%' . addcslashes($account, '%') . '%'];
        }
        $game_id = $this->request->param('game_id', '');
        if($game_id){
            $map['game_id'] = $game_id;
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

        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
        }

        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        $this->assign("total", $total[0]);//累计充值
        $this->assign("today", $today[0]);//今日充值
        $this->assign("yestoday", $yestoday[0]);//昨日充值
        $this->assign("page", $page);
        return $this->fetch();
    }


}
