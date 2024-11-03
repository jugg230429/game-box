<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\recharge\controller;

use app\recharge\model\SpendBalanceModel;
use app\recharge\model\SpendpromotecoinModel;
use app\recharge\model\SpendprovideModel;
use app\recharge\event\PtbsendController;
use app\common\model\DateListModel;
use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use think\Request;
use think\Db;

//该控制器必须以下权限
class PtbdeductController extends AdminBaseController
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
        if ($this->request->param('type') != 2) {
            echo 1;
            $spend = new SpendprovideModel;
            $account = $this->request->param('user_account', '');
            if ($account != '') {
                $usermap['account'] = ['like', '%' . addcslashes($account, '%') . '%'];
                $user_lists = Db::table('tab_user')->where($usermap)->column('id');
                $user_ids = empty($usermap) ? -1 : implode(',', $user_lists);
                $map['user_id'] = ['in', $user_ids];
            }
        } else {
            $spend = new SpendpromotecoinModel;
            $promote_id = $this->request->param('promote_id', '');
            if ($promote_id) {
                $map['promote_id'] = $promote_id;
            }
            $level = $this->request->param('level', '');//渠道等级
            if ($level != '') {
                $map['promote_type'] = $level;
            }
        }
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time && $end_time) {
            $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['create_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['create_time'] = ['egt', strtotime($start_time)];
        }
        $map['type'] = 2;
        $exend['order'] = 'create_time desc';
        $exend['field'] = '*';
        $data = $base->data_list($spend, $map, $exend);
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        if($this->request->param('type') != 2){
            $ys_show_admin = get_admin_privicy_two_value();
            foreach($data as $k5=>$v5){
                // if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                //     $data[$k5]['user_account'] = get_ys_string($v5['account'],$ys_show_admin['account_show_admin']);
                // }
                $data[$k5]['user_account'] = get_user_entity2($v5['user_id'],false,'account')['account'];
            }
        }

        if ($this->request->param('type') != 2) {
            $exend['field'] = 'sum(amount) as total';
        } else {
            $exend['field'] = 'sum(num) as total';
        }
        //累计充值
        $total = $base->data_list_select($spend, $map, $exend);
        $map['type'] = 2;
        $today[0] = 0;
        $yestoday[0] = 0;
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


    public function deduct()
    {
        $data = $this->request->param();
        $this->assign('type', $data['type']);
        return $this->fetch();
    }

    /**
     * [deductPost 收回]
     * @return [type] [description]
     * @author [yyh] <[<email address>]>
     */
    public function deductPost()
    {
        if ($this->request->isPost()) {
            $type = $this->request->param('type');
            $promote_id = $this->request->param('promote_id');
            if ($type == 2) {
                if (empty($promote_id)) {
                    $this->error('请选择渠道');
                }
                $promote = get_promote_list(['id' => $promote_id]);
                if (empty($promote[0])) {
                    $this->error('没有该渠道');
                }
                $promote_type = $promote[0]['promote_level'];
            } else {
                $account = $this->request->param('account');
                if (empty($account)) $this->error('请输入玩家账号');
                $user = get_user_info('balance', ['account' => $account]);
                if (empty($user)) {
                    $this->error('玩家账号不存在');
                }
            }
            $amount = $this->request->param('num/d');
            if ($amount == 0) {
                $this->error("请输入收回数量！");
            }
            if (!is_numeric($amount) || $amount <= 0) {
                $this->error("收回数量不正确！");
            }
            if ($type == 2) {
                if ($promote[0]['balance_coin'] < $amount) {
                    $this->error("账户余额不足！");
                }
            } else {
                if ($user['balance'] < $amount) {
                    $this->error("账户余额不足！");
                }
            }
            $pwd = $this->request->param('password');
            if ($pwd == '') {
                $this->error("请输入二级密码！");
            }
            $result = Db::name('user')->field('second_pass')->where(['id' => cmf_get_current_admin_id()])->find();
            if (!xigu_compare_password($pwd, $result['second_pass'])) {
                $this->error('二级密码错误');
            }
            if ($type == 2) {
                $pay = new SpendpromotecoinModel;
                $res = $pay->deductcoin(['promote_id' => $promote_id, 'type' => $promote_type, 'num' => $amount]);
                if ($res) {
                    $this->success('收回成功', url('ptbdeduct/lists', ['type' => $type]));
                }
            } else {
                $firstpay = new PtbsendController;
                $firstpay->deduct_user_single($this->request->param());
            }
        } else {
            $this->assign("type", $this->request->param('type'));
            return $this->fetch();
        }
    }

    /**
     * [get_user_balance 查询余额]
     * @return [type] [description]
     */
    public function get_user_balance()
    {
        if ($this->request->isAjax()) {
            $account = $this->request->param('account');
            $user = get_user_info('balance', ['account' => $account]);
            if (empty($user)) {
                $this->error('用户不存在');
            } else {
                echo json_encode(['code' => 1, 'coin' => $user['balance']]);
                exit;
            }
        } else {
            $this->error('请求类型错误');
        }
    }
}