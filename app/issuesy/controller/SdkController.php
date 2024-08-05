<?php

namespace app\issuesy\controller;

use app\issue\model\IssueGameApplyModel;
use app\issue\model\PlatformModel;
use app\issue\model\SpendModel;
use app\issuesy\logic\sdk\BaseLogic;
use app\issuesy\logic\sdk\PlatformLogic;
use think\weixinsdk\Weixin;
use think\Request;
use think\Db;

class SdkController extends BaseLogic
{
    private $sdk_data;
    private $game_data;
    private $plat_data;
    private $api_key = 'mengchuang';
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $data = json_decode(file_get_contents("php://input"), true);
        $this->sdk_data = empty($data)?$this->request->param():$data;
        #判断数据是否为空
        if (empty($this->sdk_data)) {
            $this->set_message(0,"操作数据或渠道打包ID不能为空");
        }
        $actions = ['pay_way','weixin_pay','alipay','pay_success2','pay_success'];
        if (!$this->sdk_data['sdk_os'] && !in_array($request->action(),$actions)){
            $this->set_message(0,  "sdk系统版本不能为空");
        }
        if (!empty($this->sdk_data['game_channel_id']) && is_numeric($this->sdk_data['game_channel_id'])) {//游戏渠道包
            $modelGameApply = new IssueGameApplyModel();
            $modelGameApply->field('tab_issue_game_apply.ratio,tab_issue_game_apply.game_id,game.game_name,game.sdk_version,tab_issue_game_apply.status as apply_status,tab_issue_game_apply.enable_status as apply_enable_status,game.status as game_status,platform_id,open_user_id,tab_issue_game_apply.platform_config,tab_issue_game_apply.service_config,game.game_key');
            $modelGameApply->where('tab_issue_game_apply.id','=',$this->sdk_data['game_channel_id']);
            $modelGameApply->join(['tab_issue_game'=>'game'],'game.id=tab_issue_game_apply.game_id');
            $this->game_data = $gameData = $modelGameApply->find();
            if (!empty($gameData)) {
                if($gameData['apply_status']!=1||$gameData['game_status']!=1||$gameData['apply_enable_status']!=1){
                    $this->set_message(0, "游戏状态已关闭");
                }
                $modelPlatForm = new PlatformModel();
                $modelPlatForm->field('tab_issue_open_user_platform.status as plat_status,user.status as user_status,balance,min_balance,sdk_config_name,sdk_config_version,controller_name_sy,user.settle_type,tab_issue_open_user_platform.order_notice_url_sy');
                $modelPlatForm->where('open_user_id','=',$gameData['open_user_id']);
                $modelPlatForm->where('tab_issue_open_user_platform.id','=',$gameData['platform_id']);
                $modelPlatForm->join(['tab_issue_open_user'=>'user'],'user.id=tab_issue_open_user_platform.open_user_id');
                $this->plat_data = $platformData = $modelPlatForm->find();
                if($platformData['plat_status']!=1||$platformData['user_status']!=1){
                    $this->set_message(0, "平台状态已关闭");
                }
            } else {
                $this->set_message(0, "游戏数据不存在");
            }
        }else {
            if(!in_array($request->action(),$actions)){
                $this->set_message(0, "游戏参数不正确");
            }
        }

        $md5Sign = $this->sdk_data['md5_sign'];
        unset($this->sdk_data['md5_sign']);
        $md5_sign = $this->encrypt_md5($this->sdk_data, $this->api_key);
        if ($md5Sign !== $md5_sign && !in_array($request->action(),$actions)) {
            $this->set_message(0,  "验签失败");
        }
    }

    //初始化，检测登录支付状态
    public function channel_pack_status(PlatformLogic $lplat)
    {
        $res = $lplat->channel_pack_status($this->sdk_data,$this->game_data,$this->plat_data);
        if($res['code']==200){
            $this->set_message(200,  "请求成功",['login'=>$res['login'],'pay'=>$res['pay'],'channel_data'=>$res['channel_data']]);
        }else{
            $this->set_message(0,$res['msg']);
        }
    }

    //用户登录
    public function user_login(PlatformLogic $lplat)
    {
        $res = $lplat->user_login($this->sdk_data,$this->game_data,$this->plat_data);
        if($res['code']==200){
            $this->set_message(200,  "请求成功",[
                    'user_id'=>$res['user_id'],
                    'channel_data'=>$res['channel_data'],
                    'token'=>$res['token'],
                    'user_certification'=>$this->sdk_data['user_certification'],
                    'user_birthday'=>$this->sdk_data['user_birthday']
            ]);
        }else{
            $this->set_message(0,$res['msg']);
        }
    }
    //用户角色上报 仅做记录，第三方平台移动端通知
    public function user_role_record(PlatformLogic $lplat)
    {
        $res = $lplat->user_role_record($this->sdk_data,$this->game_data,$this->plat_data);
        $this->set_message($res['code'],$res['msg']);
    }

    //创建订单
    public function pay_create(PlatformLogic $lplat)
    {
        $res = $lplat->pay_create($this->sdk_data,$this->game_data,$this->plat_data);
        if($res['code']==200){
            $this->set_message(200,  "请求成功",['ff_order'=>$res['data']['ff_order'],'login_extend'=>$res['data']['login_extend'],'callbalc_url'=>$res['data']['callbalc_url'],'channel_data'=>$res['data']['channel_data']??[],'type'=>$res['data']['type']]);
        }else{
            $this->set_message(0,$res['msg']);
        }
    }

    /**
     * @函数或方法说明
     * @支付页面
     * @author: 郭家屯
     * @since: 2020/10/26 14:24
     */
    public function pay_way()
    {
        $order_no = $this->request->param('order_no');
        $scheme = $this->request->param('scheme');
        $spendmodel = new SpendModel();
        $spendmodel->where('pay_order_number',$order_no);
        $spenddata = $spendmodel->find();
        $this->assign('scheme',$scheme);
        $this->assign('data',$spenddata);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @微信支付
     * @author: 郭家屯
     * @since: 2020/10/26 14:50
     */
    public function weixin_pay()
    {
        $order_no = $this->request->param('order_no');
        $scheme = $this->request->param('scheme');
        $device_type = get_devices_type();
        $spendmodel = new SpendModel();
        $spendmodel->where('pay_order_number',$order_no);
        $spenddata = $spendmodel->find();
        if($spenddata['pay_status'] == 1){
            return json(['status'=>0,'msg'=>'重复支付']);
        }
        if (pay_type_status('wxscan') == 1) {
            $weixn = new Weixin();
            $is_pay = json_decode($weixn -> weixin_pay($spenddata['props_name']?:'充值', $order_no, $spenddata['pay_amount'], 'MWEB'), true);
            if ($is_pay['status'] == 1) {
                $json_data['status'] = 1;
                if ($device_type == 1) {
                    $json_data['url'] = $is_pay['mweb_url'];
                } else {
                    $json_data['url'] = $is_pay['mweb_url'] . '&redirect_url=' . urlencode(url('sdk/pay_success2',['orderno'=>$order_no,'scheme'=>$scheme],true,true));
                }
            } else {
                $json_data['status'] = 0;
                $json_data['msg'] = '支付失败';
            }
            return json($json_data);
        }else{
            return json(['status'=>0,'msg'=>'支付未开启']);
        }
    }

    /**
     * @函数或方法说明
     * @支付宝支付
     * @author: 郭家屯
     * @since: 2020/10/26 14:50
     */
    public function alipay()
    {
        $order_no = $this->request->param('order_no');
        $spendmodel = new SpendModel();
        $spendmodel->where('pay_order_number',$order_no);
        $spenddata = $spendmodel->find();
        if($spenddata['pay_status'] == 1){
            return json(['status'=>0,'msg'=>'重复支付']);
        }
        if(pay_type_status('zfb') == 1){
            $config = get_pay_type_set('zfb');
            $pay = new \think\Pay("alipay", $config['config']);
            $vo = new \think\pay\PayVo();
        }else{
            return json(['status'=>0,'msg'=>'支付未开启']);
        }
        $vo->setBody($spenddata['props_name']?:'充值')
                ->setFee($spenddata['pay_amount'])//支付金额
                ->setTitle($spenddata['props_name']?:'充值')
                ->setOrderNo($spenddata['pay_order_number'])
                ->setSignType('MD5')
                ->setPayMethod('wap')
                ->setService('alipay.wap.create.direct.pay.by.user')
                ->setModule('issuesy')
                ->setTable("sue_spend");
        $url = $pay->buildRequestForm($vo);
        return json(['status'=>1,'url'=>$url]);
    }

    /**
     * [苹果支付成功通知]
     * @author 郭家屯[gjt]
     */
    public function pay_success2()
    {
        $orderno = $this->request->param('orderno');
        $out_trade_no = $this->request->param('out_trade_no');
        $orderno = empty($orderno) ? $out_trade_no : $orderno;
        $Scheme = $this->request->param('scheme');
        $spendmodel = new SpendModel();
        $spendmodel->field('pay_status');
        $spendmodel->where('pay_order_number',$orderno);
        $result = $spendmodel->find();
        $this->assign('paystatus', $result['pay_status']);
        $this->assign('Scheme', $Scheme);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @获取支付结果
     * @author: 郭家屯
     * @since: 2020/10/29 20:20
     */
    public function pay_result()
    {
        $orderno = $this->sdk_data['orderno'];
        $spendmodel = new SpendModel();
        $spendmodel->field('pay_status');
        $spendmodel->where('pay_order_number',$orderno);
        $result = $spendmodel->find();
        if ($result['pay_status'] == 1) {
            $this->set_message(200, '支付成功');
        } else {
            $this->set_message(1058, '支付失败');
        }
    }

    /**
     * [支付成功提示]
     * @author 郭家屯[gjt]
     */
    public function pay_success()
    {
        $orderno = $this->request->param('orderno');
        $out_trade_no = $this->request->param('out_trade_no');
        $orderno = empty($orderno) ? $out_trade_no : $orderno;
        $spendmodel = new SpendModel();
        $spendmodel->field('pay_status');
        $spendmodel->where('pay_order_number',$orderno);
        $result = $spendmodel->find();
        $this->assign('paystatus', $result['pay_status']);
        return $this->fetch('pay_success');
    }
}