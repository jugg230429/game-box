<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\recharge\controller;

use app\recharge\model\CashOutModel;
use app\recharge\model\SpendBalanceModel;
use app\recharge\model\SpendprovideModel;
use app\recharge\event\PtbsendController;
use app\common\model\DateListModel;
use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use think\Request;
use think\Db;
use think\weixinsdk\Weixin;

//该控制器必须以下权限
class CashOutController extends AdminBaseController
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

        $cash = new CashOutModel();
        $base = new BaseController;
        $map = [];
        $user_account = $this->request->param('user_account', '');
        if ($user_account != '') {
            $map['user_account'] = ['like', '%' . addcslashes($user_account, '%') . '%'];
        }
        $order_no = $this->request->param('order_no', '');
        if ($order_no != '') {
            $map['order_no'] = ['like', "%" . $order_no . '%'];
        }
        $create_ip = $this->request->param('create_ip', '');
        if ($create_ip != '') {
            $map['create_ip'] = ['like', '%' . $create_ip . '%'];
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


        $type = $this->request->param('type', '');
        if ($type != '') {
            $map['type'] = $type;
        }

        $status = $this->request->param('status', '');
        if ($status != '') {
            $map['status'] = $status;
        }

        $exend['order'] = 'id desc';
        $exend['field'] = '*';
        //获取数据
        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        $data = $base->data_list($cash, $map, $exend)->each(function($item, $key) use ($ys_show_admin){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $item['user_account'] = get_ys_string($item['user_account'],$ys_show_admin['account_show_admin']);
            }
        });

        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);

        return $this->fetch();
    }

    /**
     * 审核通过-处理支付宝或者微信转账
     */
    public function review()
    {
        $order_no = $this->request->param('order_no','');
        if(empty($order_no))$this->error('参数错误');
        //先查询申请状态
        $cash = new CashOutModel();
        $map['order_no'] = $order_no;
        $order = $cash->where($map)->find();
        if(empty($order))$this->error('订单非未审核状态,请勿操作',url('lists'));
        $order = $order->toArray();
        if($order['status'] != 0)$this->error('订单非未审核状态,请刷新页面',url('lists'));
        //1.修改状态-已放在对应的提现逻辑中处理

        //2.查询用户平台币余额
        $user = get_user_entity($order['user_id'],false,'balance');
        if(empty($user))$this->error('审核失败,未查询到用户');
        //判断账户余额
//        if($user['balance']<$order['apply_amount'])$this->error('审核失败,用户平台币余额不足!');
        //3.判断订单提现类型
        $return = false;
        switch ($order['type']){
            case 3://支付宝
                $result = $this->alipay_cash_out($order['order_no'],$order);
                break;
            case 4://微信
                $result = $this->weixin_cash_out($order);
                break;
            default:
                $this->error('未知提现类型,审核失败');
        }
        return json($result);

    }



    /**
     * @函数或方法说明
     * @补发提现
     * @author: 郭家屯
     * @since: 2020/9/28 10:44
     */
    public function reissue()
    {
        $order_no = $this->request->param('order_no');
        if(empty($order_no))$this->error('参数错误');
        $cash = new CashOutModel();
        $detail = $cash->where('order_no',$order_no)->where('status','in',[0,-1])->find();
        if(empty($detail)){
            $this->error('订单不存在');
        }
        if($detail['status'] == 1)$this->error('订单已完成打款,请勿重复操作');
        if($detail['status'] == 9)$this->error('订单已驳回,不可操作');
        //再次扣除账户平台币金额
        //查询账户平台币
        $user = get_user_entity($detail['user_id'],false,'balance');
        if($detail['apply_amount']>$user['balance']){
            $this->error('用户账户剩余余额不足,无法进行扣除提现');
        }
        //扣除操作
        $res2 = Db ::table('tab_user')->where('id', $detail['user_id']) -> setDec('balance', $detail['apply_amount']);
        if(!$res2){
            $this->error(1071,'扣除失败');
        }

        if($detail['type'] == 3){
            $result = $this->alipay_cash_out($detail['order_no'],$detail);
        }elseif($detail['type'] == 4){
            $result = $this->weixin_cash_out($detail);
        }else{
            $this->error('订单错误');
        }
        return json($result);
    }


    /**
     * @函数或方法说明
     * @支付宝提现
     */
    protected function alipay_cash_out($orderNo,$order)
    {
        //支付宝提现配置
        $config = get_pay_type_set('zfb_tx');
        $pay = new \think\Pay('alipay', $config['config']);
        $vo   = new \think\pay\PayVo();
        $vo->setOrderNo($orderNo)
            ->setTable('cash_out')
            ->setPayMethod("transfer_cash_out")
            ->setDetailData('平台币提现');
        $res =  $pay->buildRequestForm($vo);
        if($res==10000){
            return ['code'=>1,'msg'=>'打款成功'];
        }else{//打款失败-退还扣除的平台币
            $result = Db ::table('tab_user')->where('id', $order['user_id']) -> setInc('balance', $order['apply_amount']);
            if(!$result){
                file_put_contents("tx_cash_order.txt","打款失败,提现金额退还账户失败!记录时间:".date('Y-m-d H:i:s',time()).".订单信息:".json_encode($order).PHP_EOL,FILE_APPEND | LOCK_EX);
                return ['code'=>0,'msg'=>'打款失败,提现金额退还账户失败!已记录文档'];
            }
            return ['code'=>0,'msg'=>$res];
        }
    }

    /**
     * @函数或方法说明
     * @微信打款
     */
    protected function weixin_cash_out($data=[])
    {
        $openid = get_user_entity($data['user_id'],false,'openid')['openid'];
        $weixin = new Weixin();
        $result = $weixin->weixin_transfers('平台币提现',$data['order_no'],$data['real_amount'],$openid);
//        $result = ['code'=>1,'msg'=>'success'];//test
        //修改状态
        if($result['code'] == 1){
            $save['status'] = 1;
        }else{
            $save['status'] = -1;
        }
        $save['remark'] = $result['msg'];
        if($result['code'] == 1){
        }else{//打款失败-退还扣除的平台币
            $result2 = Db ::table('tab_user')->where('id', $data['user_id']) -> setInc('balance', $data['apply_amount']);
            if(!$result2){
                file_put_contents("tx_cash_order.txt","打款失败,提现金额退还账户失败!记录时间:".date('Y-m-d H:i:s',time()).".订单信息:".json_encode($data).PHP_EOL,FILE_APPEND | LOCK_EX);
                return ['code'=>0,'msg'=>'打款失败,提现金额退还账户失败!已记录文档'];
            }
        }
        Db::table('tab_user_cash_out')->where('order_no',$data['order_no'])->update($save);
        return $result;
    }


    /**
     * 驳回申请
     */
    public function reject()
    {
        $order_no = $this->request->param('order_no','');
        if(empty($order_no))$this->error('参数错误');
        //先查询申请状态
        $cash = new CashOutModel();
        $map['order_no'] = $order_no;
        $order = $cash->where($map)->find();
        if(empty($order))$this->error('订单不存在');
        $order = $order->toArray();
        if($order['status'] == 1)$this->error('订单已成功打款状态,请勿操作',url('lists'));
        Db::startTrans();
        //查询订单状态,如果是审核状态(status=0),则需要退还平台币,其他情况,已经退还了平台币,勿重复操作!
        $res2 = true;
        if($order['status'] == 0){
            //驳回操作退还已扣除的平台币
            $res2 = Db ::table('tab_user')->where('id', $order['user_id']) -> setInc('balance', $order['apply_amount']);
        }
        //驳回修改状态
        $data = [
            'status'=>9,
        ];

        $res = $cash->where('id',$order['id'])->update($data);
        if($res && $res2){
            Db::commit();
            $this->success('操作成功',url('lists'));
        }
        Db::rollback();
        $this->error('操作失败');

    }



}
