<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\recharge\controller;


use app\common\controller\BaseHomeController;
use app\common\logic\TplayLogic;
use app\member\model\UserTplayModel;
use app\member\model\UserTplayWithdrawModel;
use app\member\model\UserModel;
use think\Request;
use think\Db;
use think\weixinsdk\Weixin;

class TplayController extends BaseHomeController
{
    protected $user_id;
    public function __construct()
    {
        parent::__construct();
        $this->user_id = session('member_auth.user_id');
        $action = \request()->action();
        if(empty($this->user_id) && $action != 'check_auth_code'){
            $this->error('用户丢失登录状态，请刷新游戏重新进入');
        }
    }


    /**
     * @函数或方法说明
     * @拆红包功能
     * @author: 郭家屯
     * @since: 2020/9/27 13:46
     */
    public function task()
    {
        $game_id = $this->request->param('game_id');
        if(empty($game_id)){
            $this->error('参数错误');
        }
        //任务详情
        $model = new UserTplayModel();
        $map1['status'] = 1;
        $map1['start_time'] = ['lt',time()];
        $map1['game_id'] = $game_id;
        $detail = $model->where($map1)->find();
        $detail = $detail ? $detail->toArray() : [];
        //游戏信息
        $game = get_game_entity($detail['game_id'],'relation_game_id,sdk_version');
        if(empty($detail) || $detail['status'] == 0){
            $this->error('任务不存在或已关闭');
        }
        $map['tplay_id'] = $detail['id'];
        $map['user_id'] = $this->user_id;
        //报名详情
        $logic = new TplayLogic();
        $record = $logic->getRecordDetail($map);
        if($record && $record['status'] == 0 && time() > $record['end_time']){
            $record['status'] = 2;
        }
        if($record && $record['status'] == 0){
            $record['remain_time'] = $this->remain_time($record['end_time'] - time());
        }
        if($game['sdk_version'] != 3){
            $detail['down_url'] = url('Downfile/down', ['game_id' => $game['relation_game_id'], 'sdk_version' => get_devices_type()]);
            $detail['down_status'] = get_down_status2($game['relation_game_id'], get_devices_type()) ? 1 : 0;
        }
        $detail['sdk_version'] = $game['sdk_version'];
        $detail['award'] = explode('/',$detail['award']);
        $detail['level'] = explode('/',$detail['level']);
        $detail['cash'] = explode('/',$detail['cash']);
        //报名人数判断
        $sign = Db::table('tab_user_tplay_record')->field('count(id) as totalnumber,sum(cash) as total')->where('tplay_id',$detail['id'])->find();
        if($sign['totalnumber'] >= $detail['quota'] && $detail['quota'] > 0){
            $detail['is_quota'] = 1;
        }
        //获取总奖励
        $detail['total'] = $sign['total'];
        $user = get_user_entity($this->user_id,false,'id,account,tplay_cash,openid,pay_password');
        $this->assign('user',$user);
        $this->assign('detail',$detail);
        $this->assign('record',$record);
        $this->assign('meta_title','拆红包');
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @报名
     * @author: 郭家屯
     * @since: 2020/9/27 14:44
     */
    public function sign()
    {
        $id = $this->request->param('id/d');
        $logic = new TplayLogic();
        $result = $logic->sign($id,$this->user_id);
        if($result){
            $detail = $logic->getDetail($id);
            $this->success('报名成功，距离任务结束还有'.$detail['time_out'].'小时，请尽快完成提交');
        }else{
            switch ($result){
                case -1:
                    $this->error('报名名额已满');
                    break;
                case -2:
                    $this->error('报名已结束');
                    break;
                default:
                    $this->error('报名失败');
            }
        }
    }

    /**
     * @函数或方法说明
     * @完成任务
     * @author: 郭家屯
     * @since: 2020/3/23 13:54
     */
    public function complete()
    {
        $id = $this->request->param('id/d');
        $logic = new TplayLogic();
        $result = $logic->complete($id,$this->user_id);
        if($result > 0){
            $this->success('提交成功，稍后可前往消息中心查收');
        }else{
            switch ($result){
                case -1:
                    $this->error('任务已超时，无法获得奖励');
                    break;
                case -2:
                    $this->error('抱歉，没有获取到角色信息');
                    break;
                case -3:
                    $this->error('抱歉，您还未完成任务');
                    break;
                case -4 :
                    $this->error('请实名认证后再发布');
                    break;
                default:
                    $this->error('提交失败');
            }
        }
    }


    /**
     * @函数或方法说明
     * @获取剩余时间
     * @param int $time
     *
     * @author: 郭家屯
     * @since: 2020/3/23 13:43
     */
    protected function remain_time($time=0)
    {
        $str = '';
        $hour = (int)($time/3600);
        if($hour > 0){
            $str .= $hour."小时";
        }
        $min_time = $time % 3600;
        $min =  (int)($min_time/60);
        if($min > 0){
            $str .= $min."分钟";
        }
        return $str;
    }

    /**
     * @函数或方法说明
     * @提现记录
     * @author: 郭家屯
     * @since: 2020/9/27 15:03
     */
    public function withdraw_record()
    {
        $model = new UserTplayWithdrawModel();
        $user_id = $this->user_id;
        $data = $model->field('money,type,status,create_time')->where('user_id',$user_id)->order('id desc')->select();
        $total = $model->where('user_id',$user_id)->sum('money');
        $this->assign('total',$total);
        $this->assign('data',$data);
        $this->assign('meta_title','提现明细');
        return $this->fetch();
    }

    public function withdraw() {
        $user = get_user_entity($this->user_id,false,'id,account,tplay_cash,openid,wx_nickname');
        $this->assign('user',$user);
        //提现手续费设定
        $config = cmf_get_option('withdraw_set');
        $this->assign('limit_money',$config['limit_money']?:0);
        $this->assign('fee',$config['payment_fee'] ? :0);
        $this->assign('config',$config);
        $this->assign('meta_title','提现');
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @提现
     * @author: 郭家屯
     * @since: 2020/9/27 15:11
     */
    public function do_withdraw()
    {
        $config = cmf_get_option('withdraw_set');
        $param = $this->request->param();
        $money = $param['money'];
        $param['money'] = $money;
        if($money < 0.01 ){
            $this->error('提现金额错误');
        }
        if(empty($money)){
            $this->error('请输入兑换金额');
        }
        if(empty($param['password'])){
            $this->error('请输入支付密码');
        }
        $user = get_user_entity($this->user_id,false,'id,account,tplay_cash,openid,pay_password');
        if($money > $user['tplay_cash']){
            $this->error('提现金额不足，请重新提交');
        }
        if(empty($user['pay_password'])){
            $this->error('您还未设置密码，请至APP内进行设置');
        }
        if (!xigu_compare_password($param['password'], $user['pay_password'])) {
            $this->error('支付密码不正确');
        }
        $pay_way = $param['pay_way'];

        if($pay_way == 1){//支付宝
            if(empty($param['alipay_account'])){
                $this->error('请输入支付宝账号');
            }
            $param['money_account'] = $param['alipay_account'];
        }else{//微信
            $param['pay_way'] = 2;
            if(empty($config['pay_type'])){
                if(empty($param['money_account'])){
                    $this->error('请输入微信号');
                }
            }else{
                if(empty($user['openid'])){
                    $this->error('请先绑定微信');
                }
            }
        }

        if($config['limit_cash'] && $money > $config['limit_cash']){
            $this->error('单笔提现超出限额');
        }
        if($config['limit_count']){
            $map1['create_time'] = total(1,1);
            $map1['user_id'] = $this->user_id;
            $count = Db::table('tab_user_tplay_withdraw')->where($map1)->count();
            if($count >= $config['limit_count']){
                $this->error('超出每日提现次数');
            }
        }
        $param['type'] = 1;
        $param['status'] = 0;
        $data = $this->add_withdraw($user,$param);
        if(!$data){
            $this->error('创建提现订单失败');
        }
        if($config['pay_type'] == 0){
            $this->success('提现成功，请等待打款');
        }else{
            if($pay_way == 1){
                $result = $this->alipay_withdraw($data['pay_order_number']);
            }else{
                $result = $this->weixin_withdraw($data,$user['openid']);
            }
            return json($result);
        }
    }


    /**
     * @函数或方法说明
     * @兑换平台币
     * @author: 郭家屯
     * @since: 2020/9/27 15:15
     */
    public function do_exchange()
    {
        $param = $this->request->param('');
        $money = $param['money'];
        if(empty($money)){
            $this->error('请输入兑换金额');
        }
        if(empty($param['password'])){
            $this->error('请输入支付密码');
        }
        $param['money'] = abs(floatval($money));
        if($money <= 0 ){
            $this->error('提现金额错误');
        }
        $user = get_user_entity($this->user_id,false,'id,account,tplay_cash,pay_password');
        if($money > $user['tplay_cash']){
            $this->error('提现金额不足，请重新提交');
        }
        if(empty($user['pay_password'])){
            if(cmf_is_mobile()){
                $this->error('您还未设置密码，请至APP内进行设置');
            }else{
                $this->error('请前往个人中心设置支付密码');
            }
        }
        if (!xigu_compare_password($param['password'], $user['pay_password'])) {
            $this -> error('支付密码不正确');
        }
        $param['type'] = 0;
        $param['status'] = 1;
        $data = $this->add_withdraw($user,$param);
        if(!$data){
            $this->error('创建提现订单失败');
        }
        return json(['code'=>1,'msg'=>'兑换成功']);
    }

    /**
     * @函数或方法说明
     * @支付宝提现
     * @author: 郭家屯
     * @since: 2020/9/27 15:21
     */
    protected function alipay_withdraw($widthdrawNo)
    {
        //支付宝
        $config = get_pay_type_set('zfb');
        $pay = new \think\Pay('alipay', $config['config']);
        $vo   = new \think\pay\PayVo();
        $vo->setOrderNo($widthdrawNo)
                ->setTable('withdraw')
                ->setPayMethod("transfer")
                ->setDetailData('试玩提现');
        $res =  $pay->buildRequestForm($vo);
        if($res==10000){
            return ['code'=>1,'msg'=>'打款成功'];
        }else{
            return ['code'=>0,'msg'=>$res];
        }
    }

    /**
     * @函数或方法说明
     * @生成提现单
     * @author: 郭家屯
     * @since: 2020/9/27 16:17
     */
    protected function add_withdraw($user=[],$param=[]){
        $config = cmf_get_option('withdraw_set');
        $fee = 0;
        if($param['type'] == 1){
            if($config['payment_fee'] > 0){
                $fee = round($param['money'] * $config['payment_fee']/100,2);
            }
            if($config['limit_money'] > 0 && $fee < $config['limit_money']){
                $fee =  $config['limit_money'];
            }
        }
        if(($param['money'] - $fee) < 0.01){
            return false;
        }
        $add['user_id'] = $user['id'];
        $add['user_account'] = $user['account'];
        $add['pay_order_number'] = create_out_trade_no('TX');
        $add['fee'] = $fee;
        $add['money'] = $param['money'];
        $add['type'] = $param['type']?:0;
        $add['status'] = $param['status']?:0;
        $add['pay_way'] = $param['pay_way']?:0;
        $add['money_account'] = $param['money_account']?:'';
        $add['create_time'] = time();
        Db::startTrans();
        try{
            //扣除试玩现金
            Db::table('tab_user')->where('id',$user['id'])->setDec('tplay_cash',$param['money']);
            if($param['type'] == 0){
                //增加平台币
                Db::table('tab_user')->where('id',$user['id'])->setInc('balance',$param['money']);
            }
            //写入记录
            Db::table('tab_user_tplay_withdraw')->insert($add);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return false;
        }
        return $add;
    }

    /**
     * @函数或方法说明
     * @微信自动打款
     * @author: 郭家屯
     * @since: 2020/9/27 16:53
     */
    protected function weixin_withdraw($data=[],$openid='')
    {
        $weixin = new Weixin();
        $result = $weixin->weixin_transfers('试玩提现',$data['pay_order_number'],($data['money']-$data['fee']),$openid);
        //修改状态
        if($result['code'] == 1){
            $save['status'] = 1;
        }else{
            $save['status'] = 2;
        }
        $save['remark'] = $result['msg'];
        Db::table('tab_user_tplay_withdraw')->where('pay_order_number',$data['pay_order_number'])->update($save);
        return $result;
    }

    /**
     * @函数或方法说明
     * @验签
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/9/30 16:20
     */
    public function check_auth_code()
    {
        $request = $this->request->param();
        if(empty($request)){
            $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        }
        $auth_code = base64_decode($request['auth_code']);
        $redirect_url = base64_decode($request['redirect_url']);

        if(empty($auth_code)||empty($redirect_url)){
            $status = 0;
        }
        $authData = Db::table('tab_user_auth_code')->where('code','=',$auth_code)->find();
        if(empty($authData)){
            $status = -1;//不存在
        }elseif($authData['status']!=0){
            $status = -2;//已使用
        }elseif(time()-8>$authData['create_time']){
            $status = -3;//已过期
        }else{
            Db::startTrans();
            $save['id'] = $authData['id'];
            $save['status'] = 1;
            $save['update_time'] = time();
            $res =  Db::table('tab_user_auth_code')->update($save);
            if($res===false){
                $status = -4;//更新失败
            }else{
                $token = explode('_xigu_',$auth_code)[0];
                $token = json_decode(think_decrypt($token,1), true);
                $modelUser = new UserModel();
                $user = $modelUser->where('id', '=',$token['uid'])->where('puid',0)->find();
                if(empty($user)){
                    $status = -5;
                }else{
                    $user = $user->toArray();
                    $user['user_id'] = $user['id'];
                    session('member_auth', $user);
                    $this->redirect($redirect_url);
                    exit;
                }
            }
        }
        $this->assign('status',$status);
        return $this->fetch();
    }
}
