<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */
namespace app\mobile\controller;

use app\common\controller\BaseHomeController;
use app\common\controller\SmsController;
use app\common\logic\PayLogic;
use app\common\logic\TradeLogic;
use app\member\model\UserModel;
use app\member\model\UserTransactionModel;
use app\member\model\UserTransactionOrderModel;
use app\member\model\UserTransactionProfitModel;
use app\member\validate\UserTransactionValidate;
use think\captcha\Captcha;
use think\weixinsdk\Weixin;
use think\Db;

class TradeController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        $action = request()->action();
        $array = ['index','get_sell_list','detail','rule'];
        if(!in_array($action,$array)){
            $this->isLogin();
        }
    }

    /**
     * @函数或方法说明
     * @交易首页
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/3/3 10:33
     */
    public function index() {
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @获取首页加载商品列表
     * @return \think\response\Json
     *
     * @author: 郭家屯
     * @since: 2020/3/3 10:32
     */
    public function get_sell_list()
    {
        $request = $this->request->param();
        if($request['key'] != ''){
            $map['tab_user_transaction.title|g.game_name'] = ['like','%'.addslashes($request['key']).'%'];
        }
        if($request['order'] == 1){
            $order = 'money desc';
        }elseif($request['order'] == 2){
            $order = 'money asc';
        }else{
            $order = 'id desc';
        }
        $map['status'] = 1;
        $map['g.game_status'] = 1;
        $base = new BaseHomeController();
        $model = new UserTransactionModel();
        $extend['field'] = 'tab_user_transaction.id,title,g.game_name,g.icon,server_name,tab_user_transaction.create_time,tab_user_transaction.money,cumulative';
        $extend['join1'] = [['tab_game'=>'g'],'g.id=tab_user_transaction.game_id','left'];
        $extend['row'] = $request['limit'];
        $extend['order'] = $order;
        $list = $base->data_list_join($model,$map,$extend)->each(function($item,$key){
            $item['url'] = url('detail',['id'=>$item['id']]);
            $item['icon'] = cmf_get_image_url($item['icon']);
            $item['days'] = get_days(date('Y-m-d'),date('Y-m-d',$item['create_time']));
            //判断是否互通
//            if($item['is_interflow'] == 1){
//                $item['game_name'] = str_replace(['(安卓版)','(苹果版)'],'',$item['game_name']);//修改游戏名称-去除(苹果版)/(安卓版)-20210624-byh
//            }
            return $item;
        });
        return json($list);
    }


    /**
     * @函数或方法说明
     * @我要卖号
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/3/3 10:47
     */
    public function sale() {
        $logic = new TradeLogic();
        $data = $logic->get_samll_list(UID);
        //修改游戏名称-去除(苹果版)/(安卓版)-20210624-byh
//        foreach ($data as $k => &$v){
//            //查询游戏是否小号互通
//            $is_ht = get_game_entity($v[0]['fgame_id'],'is_interflow')['is_interflow']??0;
//            if($is_ht){
//                $v[0]['fgame_name'] = str_replace(['(安卓版)','(苹果版)'],'',$v[0]['fgame_name']);
//            }
//        }
        $this->assign('data',$data);
        $user = get_user_entity(UID,false,'phone,email,is_sell_prompt');
        $this->assign('user',$user);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @检查是否存在公共账号
     * @author: 郭家屯
     * @since: 2020/3/13 15:37
     */
    public function check_public_account()
    {
        //获取公共账户
        $model = new UserModel();
        $platform = $model->get_public_account();
        if(!$platform){
            $this->error('小号出售暂未开通，请联系客服');
        }else{
            $this->success('通过');
        }
    }

    /**
     * @函数或方法说明
     * @修改弹框
     * @author: 郭家屯
     * @since: 2020/3/17 16:52
     */
    public function is_popup()
    {
        $field = $this->request->param('field');
        if(!in_array($field,['is_prompt','is_sell_prompt'])){
            $this->error('参数错误');
        }
        $model = new UserModel();
        $result = $model->where('id',UID)->setField($field,1);
        if($result){
            $this->success('成功');
        }else{
            $this->error('失败');
        }
    }

    /**
     * @函数或方法说明
     * @选择小号出售
     * @author: 郭家屯
     * @since: 2020/3/3 11:51
     */
    public function transaction()
    {
        $small_id = $this->request->param('small_id');
        //修改信息
        $prompt = $this->request->param('prompt');
        if($prompt == 1){
            Db::table('tab_user')->where('id',UID)->setField('is_sell_prompt',1);
        }
        $fee = cmf_get_option('transaction_set')['fee']?:0;
        $min_dfee = cmf_get_option('transaction_set')['min_dfee']?:0;
        $min_price = cmf_get_option('transaction_set')['min_price']?:0;
        $user = get_user_entity(UID,false,'id,account,phone,email');
        if($this->request->isPost()){
            $data = $this->request->param();
            $utmodel = new UserTransactionModel();
            $order = $utmodel->field('id')->where('user_id',UID)->where('small_id',$data['small_id'])->where('status','in',[-1,0,1,2])->find();
            if(!empty($order)){
                $this->error('请勿重复出售');
            }
            // 判断当前用户出售的小号是否属于当前用户
            $this_puid = get_user_puid($data['small_id'],false);
            if ($this_puid != UID) {
                $this->error('出售小号非您所有，不可出售');
            }
            $data['title'] = trim($data['title']);
            $data['server_name'] = trim($data['server_name']);
            if($data['money'] < ($min_dfee+$min_price) || $data['money']<=0){
                $this->error('请填写正确的售价');
            }
            $validate = new UserTransactionValidate();
            if(!$validate->check($data)){
                $this->error($validate->getError());
            }
            // var_dump($data);exit;
            //获取公共账户
            $model = new UserModel();
            $platform = $model->get_public_account();
            if(!$platform){
                $this->error('小号出售暂未开通，请联系客服');
            }
            $data['platform_id'] = $platform['id'];
            if($data['send_type'] == 1){
                $this->verify_sms($user['phone'],$data['code']);
            }else{
                $this->verify_email($user['email'],$data['code'],2);
            }
            $logic = new TradeLogic();
            $result = $logic->add_sell($user,$data);
            if($result){
                $this->success('提交成功',url('apply_success'));
            }else{
                $this->error('提交失败');
            }
        }
        //出售小号信息
        $sell_account = get_user_entity($small_id,false,'id,account,phone,email,nickname,fgame_id,fgame_name,cumulative');
        //查询游戏是否小号互通
//        $is_interflow = get_game_entity($sell_account['fgame_id'],'is_interflow')['is_interflow']??0;
//        if($is_interflow == 1){
//            $sell_account['fgame_name'] = str_replace(['(安卓版)','(苹果版)'],'',$sell_account['fgame_name']);//修改游戏名称-去除(苹果版)/(安卓版)-20210624-byh
//        }
        $this->assign('sell_account',$sell_account);
        //价格设置
        $this->assign('fee',$fee);
        $this->assign('min_dfee',$min_dfee);
        $this->assign('min_price',$min_price);
        $this->assign('user',$user);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @申请出售成功
     * @author: 郭家屯
     * @since: 2020/3/12 10:31
     */
    public function apply_success()
    {
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @编辑交易信息
     * @author: 郭家屯
     * @since: 2020/3/10 13:34
     */
    public function edit()
    {
        $id = $this->request->param('id');
        $model = new UserTransactionModel();
        $transaction = $model->where('id',$id)->where('user_id',UID)->find();
        if(!$transaction){
            $this->error('交易不存在');
        }
        $transaction = $transaction->toArray();
        $fee = cmf_get_option('transaction_set')['fee']?:0;
        $min_dfee = cmf_get_option('transaction_set')['min_dfee']?:0;
        $min_price = cmf_get_option('transaction_set')['min_price']?:0;
        $user = get_user_entity(UID,false,'id,account,phone,email');
        if($this->request->isPost()){

            $utmodel = new UserTransactionModel();
            $order = $utmodel -> field('id') -> where('user_id', UID) -> where('small_id', $transaction['small_id']) -> where('status', 'in', [- 1, 0, 1, 2, 3]) -> find();
            if (!empty($order)) {
                $this -> error('请勿重复出售');
            }

            $data = $this->request->param();
            $data['title'] = trim($data['title']);
            $data['server_name'] = trim($data['server_name']);
            $data['small_id'] = $transaction['small_id'];
            if($data['money'] < ($min_dfee+$min_price) || $data['money']<=0){
                $this->error('小号出售暂未开通，请联系客服');
            }
            $validate = new UserTransactionValidate();
            if(!$validate->scene('edit')->check($data)){
                $this->error($validate->getError());
            }
            //获取公共账户
            $model = new UserModel();
            $platform = $model->get_public_account();
            if(!$platform){
                $this->error('请联系管理员创建公共账号');
            }
            $data['platform_id'] = $platform['id'];
            $logic = new TradeLogic();
            $result = $logic->edit_sell($transaction,$data);
            if($result){
                $this->success('提交成功',url('apply_success'));
            }else{
                $this->error('提交失败');
            }
        }
        //出售小号信息
        $sell_account = get_user_entity($transaction['small_id'],false,'id,account,phone,email,nickname,fgame_name,cumulative');
        $this->assign('sell_account',$sell_account);
        //价格设置
        $this->assign('fee',$fee);
        $this->assign('min_dfee',$min_dfee);
        $this->assign('min_price',$min_price);
        $this->assign('user',$user);
        $this->assign('data',$transaction);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @订单详情
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/3/5 14:52
     */
    public function order_info() {
        $id = $this->request->param('id/d');
        $logic = new TradeLogic();
        $data =  $logic->get_order_info($id);
//        if($data['is_interflow'] == 1){
//            $data['game_name'] = str_replace(['(安卓版)','(苹果版)'],'',$data['game_name']);//修改游戏名称-去除(苹果版)/(安卓版)-20210624-byh
//        }
        $data['small_account'] = get_user_entity($data['small_id'],false,'nickname')['nickname'];
        $data['screenshot'] = $data['screenshot'] ? explode(',',$data['screenshot']):[];
        if ($data['password'] == '') {
            $data['password'] = get_user_transaction($data['transaction_id'],'password')['password'];
        }
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @删除订单
     * @author: 郭家屯
     * @since: 2020/3/5 14:59
     */
    public function del_order()
    {
        $id = $this->request->param('id/d');
        $logic = new TradeLogic();
        $result =  $logic->del_order(UID,$id);
        if($result){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    /**
     * @函数或方法说明
     * @取消订单
     * @author: 郭家屯
     * @since: 2020/3/5 15:10
     */
    public function cancel_order()
    {
        $id = $this->request->param('id/d');
        $model = new UserTransactionOrderModel();
        $info = $model -> field('pay_status') -> where('user_id', UID) -> where('id', $id) -> find()->toArray();
        if($info['pay_status'] == 1){
            $this->error('订单支付成功，请勿取消');
        }
        $logic = new TradeLogic();
        $result =  $logic->set_order_status(UID,$id);
        if($result){
            $this->success('取消成功');
        }else{
            $this->error('取消失败');
        }
    }

    public function record() {// 交易记录
        // 支付成功同步通知 结果
        $tmp_req = request()->param();
        $tmp_flag = $tmp_req['tmp_flag'] ?? 0;
        $this->assign('tmp_flag',$tmp_flag);

        $logic = new TradeLogic();
        //购买订单
        $order_data = $logic->get_order(UID);
        foreach ($order_data as $key=>$v){
            $order_data[$key]['time'] = 300 - (time()-$v['pay_time']);
        }
        //出售记录
        $sell_data = $logic->get_sell_list(UID);
        $this->assign('order_data',$order_data);
        $this->assign('sell_data',$sell_data);
        //收益记录
        $model = new UserTransactionProfitModel();
        $base = new BaseHomeController();
        $extend['field'] = 'id,game_id,game_name,small_account,amount,create_time';
        $extend['order'] = "id desc";
        $map['user_id'] = UID;
        $profit = $base->data_list_select($model,$map,$extend);
        //修改游戏名称-去除(苹果版)/(安卓版)-20210624-byh
//        foreach ($profit as $k => &$v){
//            //查询游戏是否小号互通
//            $is_ht = get_game_entity($v['game_id'],'is_interflow')['is_interflow']??0;
//            if($is_ht){
//                $v['game_name'] = str_replace(['(安卓版)','(苹果版)'],'',$v['game_name']);
//            }
//        }
        $this->assign('profit',$profit);
        //设置最低价格
        $min_dfee = cmf_get_option('transaction_set')['min_dfee'];
        $min_price = cmf_get_option('transaction_set')['min_price'];
        $min_fee = 0;
        if($min_price){
            $min_fee += $min_price;
        }
        if($min_dfee){
            $min_fee += $min_dfee;
        }
        $this->assign('min_fee',$min_fee);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @修改出售状态
     * @author: 郭家屯
     * @since: 2020/3/5 15:36
     */
    public function cancel_sell()
    {
        $id = $this->request->param('id/d');
        $logic = new TradeLogic();
        $transaction = Db::table('tab_user_transaction')->field('id,status')->where('id',$id)->find();
        if($transaction['status'] == -1){
            $result = $logic->add_transaction_tip($id,false,2);
            if($result){
                $this->success('当前商品正在出售，出售失败会自动给您下架，请稍候！');
            }else{
                $this->error('设置失败');
            }
        }
        $result =  $logic->cancel_sell(UID,$id,4);
        if($result){
            $this->success('设置成功');
        }else{
            $this->error('设置失败');
        }
    }

    /**
     * @函数或方法说明
     * @删除出售记录
     * @author: 郭家屯
     * @since: 2020/3/6 15:03
     */
    public function del_sell()
    {
        $id = $this->request->param('id/d');
        $logic = new TradeLogic();
        $result =  $logic->del_sell(UID,$id);
        if($result){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    /**
     * @函数或方法说明
     * @设置出售价格
     * @author: 郭家屯
     * @since: 2020/3/6 14:53
     */
    public function set_sell_price()
    {
        $id = $this->request->param('id/d');
        $price = $this->request->param('price');
        $min_price = cmf_get_option('transaction_set')['min_price'] + cmf_get_option('transaction_set')['min_dfee'] ;
        if($price < $min_price){
            $this->error("价格设置错误");
        }
        $transaction = Db::table('tab_user_transaction')->field('id,status')->where('id',$id)->find();
        $logic = new TradeLogic();
        if($transaction['status'] == -1){
            $result = $logic->add_transaction_tip($id,$price,1);
            if($result){
                $this->success('当前商品正在出售，出售失败会自动给您改价，请稍候！');
            }else{
                $this->error('设置失败');
            }
        }else{
            $result =  $logic->set_sell_price(UID,$id,$price);
        }
        if($result){
            $this->success('设置成功');
        }else{
            $this->error('设置失败');
        }


    }


    /**
     * @函数或方法说明
     * @出售详情
     * @author: 郭家屯
     * @since: 2020/3/5 15:47
     */
    public function sell_info()
    {
        $id = $this->request->param('id/d');
        $logic = new TradeLogic();
        $data =  $logic->get_sell_info($id);
//        if($data['is_interflow'] == 1){
//            $data['game_name'] = str_replace(['(安卓版)','(苹果版)'],'',$data['game_name']);//互通修改游戏名称-去除(苹果版)/(安卓版)-20210624-byh
//        }
        $data['days'] = get_days(date('Y-m-d'),date('Y-m-d',$data['create_time']));
        $data['small_account'] = get_user_entity($data['small_id'],false,'nickname')['nickname'];
        $data['screenshot'] = $data['screenshot'] ? explode(',',$data['screenshot']):[];
        $this->assign('data',$data);
        return $this->fetch();
    }

    public function trends() {// 成交动态
        return $this->fetch();
    }

    public function rule() {// 交易须知
        return $this->fetch();
    }

    public function detail() {// 小号详情
        $id = $this->request->param('id');
        if(empty($id))$this->redirect('index');
        $logic = new TradeLogic();
        $detail = $logic->get_transaction_detail($id);
        if(empty($detail))$this->redirect('index');
        $detail['small_account'] = substr_cut($detail['small_account']);
//        if($detail['is_interflow'] == 1){
//            $detail['game_name'] = str_replace(['(安卓版)','(苹果版)'],'',$detail['game_name']);//修改游戏名称-去除(苹果版)/(安卓版)-20210624-byh
//        }
        $this->assign('detail',$detail);
        $other_game = $logic->get_other_game($detail['game_id'],$id,3);
        //修改游戏名称-去除(苹果版)/(安卓版)-20210624-byh
//        foreach ($other_game['data'] as $k => &$v){
//            if($v['is_interflow'] == 1){
//                $v['game_name'] = str_replace(['(安卓版)','(苹果版)'],'',$v['game_name']);
//            }
//        }
        $this->assign('other_game',$other_game['data']);
        $this->assign('other_count',$other_game['count']);
        $user = get_user_entity(UID,false,'is_prompt');
        $this->assign('user',$user);
        return $this->fetch();
    }

    /**
     * 发送验证码
     *
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * @since: 2019\3\27 0027 11:18
     * @author: fyj301415926@126.com
     */
    public function sendCode()
    {
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $user = get_user_entity(UID,false,'phone,email');
            if($request['send_type'] == 1){

                //验证图片验证码
                if (empty($request['img_verify_code'])) {
                    $this->error('图片验证码不能为空');
                }
                $captcha = new Captcha();
                if (!$captcha -> check($request['img_verify_code'])) {
                    $this->error('验证码错误');
                }

                if(empty($user['phone'])){
                    $this->error('请绑定手机');
                }
                $this->sendsms($user['phone']);
            }else{
                if(empty($user['email'])){
                    $this->error('请绑定邮箱');
                }
                $this->sendemail($user['email']);
            }

        } else {
            $this->error('请求失败');
        }

    }

    /**
     * [验证手机]
     * @author 郭家屯[gjt]
     */
    public function verify_sms($phone, $code)
    {
        $sms = new SmsController;
        $smsData = $sms->verifySmsCode($phone, $code, false, false);
        if ($smsData['code'] != SmsController::$error_info['success']) {
            switch ($smsData['code']) {
                case SmsController::$error_info['code_empty']:
                    $return = $smsData['msg'];
                    break;
                case SmsController::$error_info['code_input_error']:
                    $return = '短信验证码错误或已过期';
                    break;
                case SmsController::$error_info['code_overtime']:
                    $return = '短信验证码错误或已过期';
                    break;
            }
            $this->error($return);
        }
    }
    /**
     * [验证邮箱]
     * @author 郭家屯[gjt]
     */
    public function verify_email($email, $code, $type = 2)
    {
        $code_result = $this->emailVerify($email, $code);
        if ($code_result == 1) {
            if ($type == 1) {
                $this->success("验证成功");
            } else {
                return true;
            }
        } else {
            if ($code_result == 2) {
                $this->error("请先获取验证码");
            } elseif ($code_result == -98) {
                $this->error("验证码超时");
            } elseif ($code_result == -97) {
                $this->error("验证码不正确，请重新输入");
            }
        }
    }

    /**
     * @param $email
     * @param $code
     * @param int $time
     * @return int
     * 验证 邮箱验证码
     */
    protected function emailVerify($email, $code, $time = 30)
    {
        $session = session($email);
        if (empty($session)) {
            return 2;
        } elseif ((time() - $session['create_time']) > $time * 60) {
            return -98;
        } elseif ($session['code'] != $code) {
            return -97;
        }
        session($email, null);
        return 1;
    }


    /**
     * @函数或方法说明
     * @发送短信
     * @author: 郭家屯
     * @since: 2020/3/5 10:28
     */
    protected function sendsms($phone='')
    {
        $sms = new SmsController();
        $data = $sms->sendSellSmsCode($phone, 10, false);
        if ($data['code'] == 200) {
            $this->success($data['msg'], '', $data['data']);
        } else {
            $this->error($data['msg']);
        }
    }

    /**
     * @函数或方法说明
     * @发送邮件
     * @param string $email
     *
     * @author: 郭家屯
     * @since: 2020/3/5 10:32
     */
    protected function sendemail($email='')
    {
        if ($this->request->isPost()) {
            $session = session($email);
            if (!empty($session) && (time() - $session['create_time']) < 60) {
                $this->error("验证码发送过于频繁，请稍后再试");
            }
            $code = rand(100000, 999999);
            $smtpSetting = cmf_get_option('email_template_verification_code');
            $smtpSetting['template'] = str_replace('{$code}', $code, htmlspecialchars_decode($smtpSetting['template']));
            $result = cmf_send_email($email, str_replace('注册', "出售小号", $smtpSetting['subject']), $smtpSetting['template']);
            if ($result['error'] == 0) {
                session($email, ['code' => $code, 'create_time' => time()]);
                $this->success("验证码发放成功，请注意查收");
            } else {
                $this->error("发送失败，请检查邮箱地址是否正确");
            }
        } else {
            $this->error('提交失败');
        }
    }

    /**
     * @函数或方法说明
     * @支付
     * @author: 郭家屯
     * @since: 2020/3/5 17:36
     */
    public function buy()
    {
        $request = $this->request->param();
        $model = new UserTransactionModel();
        $transaction = $model->where('id',$request['transaction_id'])->where('status','in',[-1,1])->find();
        if(!$transaction){
            $this->error('商品已出售或者已下架');
        }
        //锁定交易
        if($transaction['status'] == 1){
            $save['status'] = -1;
            $save['lock_time'] = time();
            $model->where('id',$request['transaction_id'])->update($save);
        }else{
            $ordermodel = new UserTransactionOrderModel();
            $order = $ordermodel->where('transaction_id',$request['transaction_id'])->field('id,user_id')->order('id desc')->find();
            if($order['user_id'] != UID){
                $this->error('当前商品已被锁定，可购买其他商品');
            }else{
                $model->where('id',$request['transaction_id'])->setField('lock_time',time());
                $ordermodel->where('id',$order['id'])->setField('pay_status',2);
            }
        }
        $user = get_user_entity(UID,false,'id,account,balance');
        if($request['is_balance']){
            if($user['balance'] >= $transaction['money']){
                $this->balance_pay($user,$transaction);
            }
        }
        if($request['pay_way'] == 1){
            // if(cmf_is_wechat()){
            //     // 返回跳转链接
            //     $tmp_data = [
            //         'user'=>$user,
            //         'transaction'=>$transaction,
            //         'request'=>$request
            //     ];
            //     $url = url('alipay2',$tmp_data,true,true);
            //     return json([
            //             'code'=>1,
            //             'url'=>$url,
            //         ]
            //     );
            //     // return $this->fetch();
            // }else{
                //增加安卓版APP支付配置的处理和返回-20210628-byh-s
                $config = get_pay_type_set('zfb');
                if($config['config']['type'] == 1 && get_devices_type()==1){
                    $app_data = $this->alipay($user,$transaction,$request,true);//app标识为true
                    $app_data['ali_app'] = 1;
                    $this->success('成功','',$app_data);
                }
                //增加安卓版APP支付配置的处理和返回-20210628-byh-e

                $url = $this->alipay($user,$transaction,$request);
            // }
        }elseif ($request['pay_way'] == 2){
            if(get_devices_type() == 2 && session('app_user_login')==1 && pay_type_status('wxapp') == 1){
                $data = $this->weixinpay($user,$transaction,$request);
                return json($data);
            }else{
                $url = $this->weixinpay($user,$transaction,$request);
            }
        }else{
            //解除商品锁定
            $save['status'] = 1;
            $save['lock_time'] = 0;
            $model->where('id',$request['transaction_id'])->update($save);
            $this->error('请选择支付方式');
        }
        $this->success('成功',$url);
    }

    /**
     * @函数或方法说明
     * @平台币支付
     * @param array $user
     * @param array $tranaction
     *
     * @author: 郭家屯
     * @since: 2020/3/6 17:15
     */
    protected function balance_pay($user=[],$transaction=[])
    {
        $logic = new PayLogic();
        $data['status'] = 1;
        $data['pay_way'] = 2;
        $data['pay_order_number'] = create_out_trade_no('TO_');
        $fee = cmf_get_option('transaction_set')['fee'];
        $min_dfee = cmf_get_option('transaction_set')['min_dfee'];
        $fee_money = 0;
        if($fee){
            $fee_money = $transaction['money'] * $fee/100;
        }
        if($min_dfee){
            if($min_dfee > $fee_money ){
                $fee_money = $min_dfee;
            }
        }
        $data['fee'] = $fee_money;
        $data['balance_money'] = $transaction['balance_money'];
        Db::startTrans();
        try {
            //写入订单
            $result0 = $logic->add_transaction($user,$transaction,$data);
            //扣除平台币
            $result = Db::table('tab_user')->where('id',$user['id'])->setDec('balance',$transaction['money']);
            //修改交易状态
            $result1=Db::table('tab_user_transaction')->where('id',$transaction['id'])->setField('status',3);
            //增加金额
            $result2=Db::table('tab_user')->where('id',$transaction['user_id'])->setInc('balance',$transaction['money']-$fee_money);
            //修改小号归属
            $result3=Db::table('tab_user')->where('id',$transaction['small_id'])->setField('puid',$user['id']);
            $result5 = Db ::table('tab_user_play_info')->where('user_id', $transaction['small_id'])->setField('puid', $user['id']);
            //增加收益记录
            $save['user_id'] = $transaction['user_id'];
            $save['user_account'] = $transaction['user_account'];
            $save['game_id'] = $transaction['game_id'];
            $save['game_name'] = $transaction['game_name'];
            $save['amount'] = $transaction['money']-$fee_money;
            $save['small_id'] = $transaction['small_id'];
            $save['small_account'] = get_user_entity($transaction['small_id'],false,'nickname')['nickname'];
            $save['create_time'] = time();
            $result4=Db::table('tab_user_transaction_profit')->insert($save);
            //更改交易状态
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('支付失败');
        }
        $this->success('支付成功',url('Trade/record'));
    }

    /**
     * @函数或方法说明
     * @确认订单
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/3/9 17:27
     */
    public function order()
    {
        //设置提示信息状态
        $prompt = $this->request->param('prompt');
        if($prompt == 1){
            Db::table('tab_user')->where('id',UID)->setField('is_prompt',1);
        }
        //交易信息
        $transaction_id = $this->request->param('transaction_id');
        $logic = new TradeLogic();
        $data = $logic->get_sell_info($transaction_id);
        if(!$data)$this->error('商品不存在');
        if($data['user_id'] == UID)$this->error('出售小号为您所有，不可购买');
//        if($data['is_interflow'] == 1){
//            $data['game_name'] = str_replace(['(安卓版)','(苹果版)'],'',$data['game_name']);//修改游戏名称-去除(苹果版)/(安卓版)-20210624-byh
//        }
        $data['days'] = get_days(date('Y-m-d'),date('Y-m-d',$data['create_time']));
        $data['small_account'] = get_user_entity($data['small_id'],false,'nickname')['nickname'];
        $data['screenshot'] = $data['screenshot'] ? explode(',',$data['screenshot']):[];
        $this->assign('data',$data);
        //用户信息
        $user = get_user_entity(UID,false,'id,account,balance');
        $this->assign('user',$user);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @使用说明
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/3/10 15:43
     */
    public function record_instructions()
    {
        return $this->fetch();
    }

    /**
     * @支付宝支付
     *
     * @author: zsl
     * @since: 2019/3/29 14:56
     */
    public function alipay($user=[],$transaction=[],$request=[],$and_app_flag = false)
    {
        $data['pay_type'] = 'alipay';
        $data['config'] = "alipay";
        $data['service'] = "alipay.wap.create.direct.pay.by.user";
        $data['pay_way'] = 3;
        $config = get_pay_type_set('zfb');
        if ($config['status'] != 1) {
            $this->error('支付宝支付未开启');
        }
        $body = "购买小号";
        $title = "交易订单支付";
        $table = "transaction";
        $data['pay_order_number'] = create_out_trade_no("TO_");
        if($request['is_balance']){
            $pay_amount = $transaction['money'] - $user['balance'];
        }else{
            $pay_amount = $transaction['money'];
        }
        $fee = cmf_get_option('transaction_set')['fee'];
        $min_dfee = cmf_get_option('transaction_set')['min_dfee'];
        $fee_money = 0;
        if($fee){
            $fee_money = $transaction['money'] * $fee/100;
        }
        if($min_dfee){
            if($min_dfee > $fee_money ){
                $fee_money = $min_dfee;
            }
        }

        //增加对安卓APP支付的处理判断-20210628-byh
        $pay_method = 'transaction';
        if($and_app_flag === true){
            $pay_method = 'mobile';
            $data['service'] = "mobile.securitypay.pay";
        }

        $lock_time = date('Y-m-d H:i:s', strtotime('+5minute'));
        $pay = new \think\Pay($data['pay_type'], $config['config']);
        $vo = new \think\pay\PayVo();
        $vo->setBody($body)
                ->setFee($pay_amount)//支付金额
                ->setTitle($title)
                ->setOrderNo($data['pay_order_number'])
                ->setService($data['service'])
                ->setSignType("MD5")
                ->setPayMethod($pay_method)
                ->setTable($table)
                ->setPayWay($data['pay_way'])
                ->setUserId($user['id'])
                ->setAccount($user['account'])
                ->setFeeMoney($fee_money)
                ->setTransactionId($transaction['id'])
                ->setLockTime($lock_time)
                ->setBalanceStatus($request['is_balance']);
        return $pay->buildRequestForm($vo);
    }


    /**
     * @微信支付
     *
     * @return mixed
     *
     * @author: zsl
     * @since: 2019/3/29 14:57
     */
    public function weixinpay($user=[],$transaction=[],$request=[])
    {
        if (pay_type_status('wxscan') != 1) {
            $this->error('微信支付未开启');
        }
        $data['pay_order_number'] = create_out_trade_no("TO_");
        $data['pay_amount'] = $transaction['money'];
        $data['pay_status'] = 0;
        $data['pay_way'] = 4;
        $data['user_id'] = $user['id'];
        $fee = cmf_get_option('transaction_set')['fee'];
        $min_dfee = cmf_get_option('transaction_set')['min_dfee'];
        $fee_money = 0;
        if($fee){
            $fee_money = $transaction['money'] * $fee/100;
        }
        if($min_dfee){
            if($min_dfee > $fee_money ){
                $fee_money = $min_dfee;
            }
        }
        $data['fee'] = $fee_money;
        if($request['is_balance']){
            $data['pay_amount'] = $transaction['money'] - $user['balance'];
            $data['balance_money'] = $user['balance'];
        }
        $data['time'] = time();
        $data['time_expire'] = date('YmdHis',$data['time'])+301;
        $logic = new PayLogic();
        if(get_devices_type() == 2 && session('app_user_login')==1){
            //APP支付
            $weixn = new Weixin();
            $is_pay = json_decode($weixn->weixin_pay("交易订单支付", $data['pay_order_number'], $data['pay_amount'], 'APP',3,0,$data['time_expire']), true);
            if ($is_pay['status'] === 1) {
                $logic->add_transaction($user,$transaction,$data);
                $json_data['appid'] = $is_pay['appid'];
                $json_data['partnerid'] = $is_pay['mch_id'];
                $json_data['out_trade_no'] = $is_pay['prepay_id'];
                $json_data['prepayid'] = $is_pay['prepay_id'];
                $json_data['noncestr'] = $is_pay['noncestr'];
                $json_data['timestamp'] = $is_pay['time'];
                $json_data['package'] = "Sign=WXPay";
                $json_data['sign'] = $is_pay['sign'];
                $json_data['status'] = 4;
                $json_data['return_msg'] = "下单成功";
                $json_data['wxtype'] = "wx";
                return $json_data;
            } else {
                $this->error('创建订单失败');
            }
        }else{
            //H5支付
            $weixn = new Weixin();
            $is_pay = json_decode($weixn->weixin_pay("交易订单支付", $data['pay_order_number'], $data['pay_amount'], 'MWEB',1,$data['time_expire']), true);
            if ($is_pay['status'] == 1) {
                $logic->add_transaction($user,$transaction,$data);
            } else {
                $this->error('创建订单失败');
            }
            if (!empty($is_pay['mweb_url'])) {
                $url = '//' . cmf_get_option('admin_set')['web_site'] . '/mobile/pay/wechatJumpPage' . "?jump_url=" . urlencode($is_pay['mweb_url'] . "&redirect_url=" . urlencode(url('Trade/record', ['tmp_flag'=>1], true, true)));
                return $url;
            } else {
                $this->error('支付发生错误,请重试');
            }
        }
    }

//    /**
//     * @函数或方法说明
//     * @支付
//     * @author: 郭家屯
//     * @since: 2020/3/5 17:36
//     */
//    public function get_wx_code()
//    {
//        $request = $this->request->param();
//        $model = new UserTransactionModel();
//        $transaction = $model->where('id',$request['transaction_id'])->where('status','in',[-1,1])->find();
//        if(!$transaction){
//            $this->error('商品已出售或者已下架');
//        }
//        //锁定交易
//        if($transaction['status'] == 1){
//            $save['status'] = -1;
//            $save['lock_time'] = time();
//            $model->where('id',$request['transaction_id'])->update($save);
//        }else{
//            $ordermodel = new UserTransactionOrderModel();
//            $order = $ordermodel->where('transaction_id',$request['transaction_id'])->field('id,user_id')->order('id desc')->find();
//            if($order['user_id'] != UID){
//                $this->error('当前商品已被锁定，可购买其他商品');
//            }else{
//                $model->where('id',$request['transaction_id'])->setField('lock_time',time());
//                $ordermodel->where('id',$order['id'])->setField('pay_status',2);
//            }
//        }
//        $user = get_user_entity(UID,false,'id,account,balance');
//        if (pay_type_status('wxscan') != 1) {
//            $this->error('微信支付未开启');
//        }
//        $data['pay_order_number'] = create_out_trade_no("TO_");
//        $data['pay_amount'] = $transaction['money'];
//        $data['pay_status'] = 0;
//        $data['pay_way'] = 4;
//        $data['user_id'] = $user['id'];
//        $fee = cmf_get_option('transaction_set')['fee'];
//        $min_dfee = cmf_get_option('transaction_set')['min_dfee'];
//        $fee_money = 0;
//        if($fee){
//            $fee_money = $transaction['money'] * $fee/100;
//        }
//        if($min_dfee){
//            if($min_dfee > $fee_money ){
//                $fee_money = $min_dfee;
//            }
//        }
//        $data['fee'] = $fee_money;
//        if($request['is_balance']){
//            $data['pay_amount'] = $transaction['money'] - $user['balance'];
//            $data['balance_money'] = $user['balance'];
//        }
//        $data['time'] = time();
//        $data['time_expire'] = date('YmdHis',$data['time'])+301;
//        if (cmf_is_wechat()) {
//            $logic = new PayLogic();
//            //微信内部支付
//            $logic -> add_transaction($user, $transaction, $data);
//            Vendor("wxPayPubHelper.WxPayPubHelper");
//            // 使用jsapi接口
//            $pay_set = get_pay_type_set('wxscan');
//            $wx_config = get_user_config_info('wechat');
//            $jsApi = new \JsApi_pub($wx_config['appsecret'], $pay_set['config']['appid'], $pay_set['config']['key']);
//            //获取code码，以获取openid
//            $openid = session("wechat_token.openid");
//            $weixn = new Weixin();
//            $amount = $data['pay_amount'];
//            $out_trade_no = $data['pay_order_number'];
//            $is_pay = $weixn -> weixin_jsapi("交易订单支付", $out_trade_no, $amount, $jsApi, $openid, $data['time_expire']);
//            $this -> assign('jsApiParameters', $is_pay);
//            $this -> assign('hostdomain', $_SERVER['HTTP_HOST']);
//            return $this -> fetch();
//        }else{
//            $this->error('请使用微信客户端');
//        }
//    }


    /**
     * @函数或方法说明
     * @再次支付
     * @author: 郭家屯
     * @since: 2020/3/11 14:30
     */
    public function pay()
    {
        $id = $this->request->param('id');
        $model = new UserTransactionOrderModel();
        $order = $model->where('id',$id)->where('pay_status',0)->find();
        if(!$order || (time()-$order['pay_time'])>300){
            $this->error('订单已失效');
        }else{
            //更新锁定时间
            $model = new UserTransactionModel();
            $model->where('id',$order['transaction_id'])->setField('lock_time',time());
        }
        if($order['pay_way'] == 3){
            //增加安卓版APP支付配置的处理和返回-20210628-byh-s
            $config = get_pay_type_set('zfb');
            if($config['config']['type'] == 1 && get_devices_type()==1){
                $app_data =  $this->continue_alipay($order,true);//app标识为true
                $app_data['ali_app'] = 1;
                $this->success('成功','',$app_data);
            }
            //增加安卓版APP支付配置的处理和返回-20210628-byh-e
            $url = $this->continue_alipay($order);
        }elseif($order['pay_way'] == 4){
            $url=$this->continue_weixinpay($order);
        }else{
            $this->error('支付错误');
        }
        $this->success('成功',$url);
    }

    /**
     * @支付宝支付
     *
     * @author: zsl
     * @since: 2019/3/29 14:56
     */
    public function continue_alipay($order=[],$and_app_flag = false)
    {
        $data['pay_type'] = 'alipay';
        $data['config'] = "alipay";
        $data['service'] = "alipay.wap.create.direct.pay.by.user";
        $data['pay_way'] = 3;
        $config = get_pay_type_set('zfb');
        if ($config['status'] != 1) {
            $this->error('支付宝支付未开启');
        }
        $body = "购买小号";
        $title = "交易订单支付";
        $table = "transaction_continue";
        $lock_time = date('Y-m-d H:i:s', strtotime("+5minute"));

        //增加对安卓APP支付的处理判断-20210628-byh
        $pay_method = 'transaction';
        if($and_app_flag === true){
            $pay_method = 'mobile';
            $data['service'] = "mobile.securitypay.pay";
        }


        $pay = new \think\Pay($data['pay_type'], $config['config']);
        $vo = new \think\pay\PayVo();
        $vo->setBody($body)
                ->setFee($order['pay_amount']-$order['balance_money'])//支付金额
                ->setTitle($title)
                ->setOrderNo($order['pay_order_number'])
                ->setService($data['service'])
                ->setSignType("MD5")
                ->setLockTime($lock_time)
                ->setPayMethod($pay_method)
                ->setTable($table);
        return $pay->buildRequestForm($vo);
    }

    /**
     * @微信支付
     *
     * @return mixed
     *
     * @author: zsl
     * @since: 2019/3/29 14:57
     */
    protected function continue_weixinpay($order=[])
    {
        if (pay_type_status('wxscan') != 1) {
            $this->error('微信支付未开启');
        }
        $data['pay_amount'] = $order['pay_amount']-$order['balance_money'];
        $data['time_expire'] = date('YmdHis')+301;
        //H5支付
        $weixn = new Weixin();
        $is_pay = json_decode($weixn->weixin_pay("交易订单支付", $order['pay_order_number'], $data['pay_amount'], 'MWEB',1,$data['time_expire']), true);
        if (!empty($is_pay['mweb_url'])) {
            $url = '//' . cmf_get_option('admin_set')['web_site'] . '/mobile/pay/wechatJumpPage' . "?jump_url=" . urlencode($is_pay['mweb_url'] . "&redirect_url=" . urlencode(url('Trade/record', [], true, true)));
            return $url;
        } else {
            $this->error('支付发生错误,请重试');
        }
    }

//    /**
//     * @函数或方法说明
//     * @订单中心支付
//     * @author: 郭家屯
//     * @since: 2020/3/11 20:02
//     */
//    public function continue_get_wx_code()
//    {
//        $id = $this->request->param('id');
//        $model = new UserTransactionOrderModel();
//        $order = $model->where('id',$id)->where('pay_status',0)->find();
//        if(!$order || (time()-$order['pay_time'])>300){
//            $this->error('订单已失效');
//        }else{
//            //更新锁定时间
//            $model = new UserTransactionModel();
//            $model->where('id',$order['transaction_id'])->setField('lock_time',time());
//        }
//        if (pay_type_status('wxscan') != 1) {
//            $this->error('微信支付未开启');
//        }
//        $data['pay_amount'] = $order['pay_amount']-$order['balance_money'];
//        $data['time_expire'] = date('YmdHis',$order['create_time'])+301;
//        if (cmf_is_wechat()) {
//            Vendor("wxPayPubHelper.WxPayPubHelper");
//            // 使用jsapi接口
//
//            $pay_set = get_pay_type_set('wxscan');
//            $wx_config = get_user_config_info('wechat');
//
//            $jsApi = new \JsApi_pub($wx_config['appsecret'], $pay_set['config']['appid'], $pay_set['config']['key']);
//            //获取code码，以获取openid
//            $openid = session("wechat_token.openid");
//            $weixn = new Weixin();
//            $amount = $data['pay_amount'];
//            $out_trade_no = $order['pay_order_number'];
//            $is_pay = $weixn->weixin_jsapi("交易订单支付", $out_trade_no, $amount, $jsApi, $openid,$data['time_expire']);
//            $this->assign('jsApiParameters', $is_pay);
//            $this->assign('hostdomain', $_SERVER['HTTP_HOST']);
//            return $this->fetch();
//        }else{
//            $this->error('请使用微信客户端');
//        }
//    }


    /**
     * @获取图片验证码
     *
     * @author: zsl
     * @since: 2021/7/29 9:59
     */
    public function imageVerify()
    {
        $captcha = new Captcha();
        $captcha -> length = 4;
        $captcha -> codeSet = '0123456789';
        return $captcha -> entry();
    }

}
