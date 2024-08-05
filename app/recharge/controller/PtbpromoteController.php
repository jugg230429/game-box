<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\recharge\controller;

use app\recharge\model\SpendBalanceModel;
use app\recharge\model\SpendpromotecoinModel;
use app\recharge\event\PtbsendController;
use app\common\model\DateListModel;
use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use think\Request;
use think\Db;

//该控制器必须以下权限
class PtbpromoteController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_PAY != 1) {
            $this->error('请购买充值权限', url('admin/main/index'));
        }
        if (AUTH_PROMOTE != 1) {
            $this->error('请购买渠道权限', url('admin/main/index'));
        }
    }


    public function sendpromotelists()
    {
        $base = new BaseController();
        $spend = new SpendpromotecoinModel;

        $account = $this->request->param('account', '');
        if ($account != '') {
            $usermap['account'] = ['like', '%' . addcslashes($account, '%') . '%'];
            $user_lists = Db::table('tab_promote')->where($usermap)->column('id');
            $user_ids = empty($usermap) ? -1 : implode(',', $user_lists);
            $map['promote_id'] = ['in', $user_ids];
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

        $op_id = $this->request->param('admin_id', '');
        if ($op_id != '') {
            $map['op_id'] = $op_id;
        }

        $map['type'] = 1;
        $exend['order'] = 'create_time desc';
        $exend['field'] = '*';
        $data = $base->data_list($spend, $map, $exend);
        $exend['field'] = 'sum(num) as total';
        //累计充值
        $total = $base->data_list_select($spend, $map, $exend);
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

    /**
     * [sendpromote 给渠道发放]
     * @return [type] [description]
     * @author [yyh] <[<email address>]>
     */
    public function sendpromote()
    {
        if ($this->request->isPost()) {
            $type = $this->request->param('type');
            $promote_id = $this->request->param('promote_id');
            if (empty($promote_id)) {
                $this->error('请选择渠道');
            }
            $promote = get_promote_list(['id' => $promote_id]);
            if (empty($promote[0])) {
                $this->error('没有该渠道');
            }
            $amount = $this->request->param('num/d');
            if ($amount == 0) {
                $this->error("请输入发放数量！");
            }
            if (!is_numeric($amount) || $amount < 0) {
                $this->error("发放数量不正确！");
            }

            $quota = cmf_get_option('ptb_channel_send_quota');
            if (!empty($quota['value'])) {
                if ($quota['value'] < $amount) {
                    $this -> error("单笔发放金额超限，请重新输入！");
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

            $pay = new SpendpromotecoinModel;
            $res = $pay->sendcoin($this->request->param());
            if ($res) {
                $this->success('发放成功', url('sendpromotelists'));
            }
        } else {
            $this->assign("type", $this->request->param('type'));
            return $this->fetch();
        }
    }

    /**
     * [get_promote_coin 查询余额]
     * @return [type] [description]
     */
    public function get_promote_coin()
    {
        if ($this->request->isAjax()) {
            $promote_id = $this->request->param('pid');
            $promote = get_promote_list(['id' => $promote_id]);
            if (empty($promote[0])) {
                $this->error('数据错误');
            } else {
                echo json_encode(['code' => 1, 'coin' => $promote[0]['balance_coin']]);
                exit;
            }
        } else {
            $this->error('请求类型错误');
        }
    }
}
