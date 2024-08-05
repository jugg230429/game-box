<?php
/**
 *
 */

namespace app\mobile\controller;

use app\common\logic\PointTypeLogic;
use app\member\logic\PointShopLogic;
use app\member\logic\PointShopRecordLogic;
use app\member\logic\PointUseLogic;
use app\common\logic\PointLogic;
use app\member\model\PointTypeModel;
use app\member\model\UserModel;
use app\member\validate\PointShopValidate;
use app\recharge\model\SpendModel;
use think\Db;
use think\helper\Time;

class PointController extends BaseController
{
    /**
     * TplayController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        /*
         * 用户权限判断
         */
        if (AUTH_USER != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买用户权限');
            } else {
                $this->error('请购买用户权限', url('admin/main/index'));
            };
        }
        if(!in_array(request()->action(),['index','mall_detail'])){
            $this->isLogin();
        }
    }
    public function index(PointShopLogic $pointShopLogic){
        $getData = request()->param();
        $data = $pointShopLogic->lists($getData);
        $sign_status = (new PointLogic())->sign_detail(session('member_auth.user_id'),1);
        $this->assign('sign_status',$sign_status==1?1:0);
        $this->assign('data',$data);
        return $this->fetch();
    }
    public function mall_detail(PointShopLogic $pointShopLogic){
         $getData = request()->param();
         $result = $this->validate(
             ['id'  => $getData['id']],
             ['id'  => 'require|number']);
         if(true !== $result){
             // 验证失败 输出错误信息
             $this->error('id不合法');
         }
        $data = $pointShopLogic->detail($getData);
        if(empty($data)){
            $this->error('无商品数据');
        }
        $this->assign('data',$data);
        return $this->fetch();
    }
    public function mall_exchange_detail(PointShopLogic $pointShopLogic)
    {
        $getData = request()->param();
        $getData['num'] = intval($getData['num'])?:1;
        $result = $this->validate(
            ['id'  => $getData['id']],
            ['id'  => 'require|number']
        );
        if(true !== $result){
            // 验证失败 输出错误信息
            $this->error('id不合法');
        }
        $data = $pointShopLogic->mall_exchange_detail($getData,session('member_auth.user_id'));
        if(is_array($data)){
            $data['receive_address'] = implode(',',$data['receive_address']);
            return json(['code'=>200,'msg'=>'可兑换','data'=>$data]);
        }else{
            $msg = $this->good_error_status($data);
            return json(['code'=>0,'msg'=>$msg]);
        }
    }
    private function good_error_status($data)
    {
        switch ($data){
            case 0;
                $msg = '无商品数据';
                break;
            case -1;
                $msg = '库存不足';
                break;
            case -2;
                $msg = '积分余额不足';
                break;
            case -3;
                $msg = 'VIP等级不足';
                break;
            case -4;
                $msg = '请选择收货地址';
                break;
            case -5;
                $msg = '超出购买上限';
                break;
            default;
                exit('数据错误');
                break;
        }
        return $msg;
    }

    public function mall_exchange(PointShopLogic $pointShopLogic)
    {
        $postData = request()->param();
        $postData['num'] = intval($postData['num'])?:1;
        $result = $this->validate(
            ['id'  => $postData['id']],
            ['id'  => 'require|number']);
        if(true !== $result){
            // 验证失败 输出错误信息
            $this->error('id不合法');
        }
        $data = $pointShopLogic->mall_exchange($postData,session('member_auth.user_id'));
        if($data>0){
            $this->success('兑换成功');
        }else{
            $msg = $this->good_error_status($data);
            $this->error($msg);
        }
    }
    public function mall_task(PointTypeLogic $logicPointType){
        //更新任务状态
        $logicPointType->update_task(UID);
        //获取任务数据
        $list = $logicPointType->lists(UID);
        /*
            注意:
                tab_user_point_type表中的: 天天签到,充值送积分,每日登录游戏,试玩有奖, 这四个字段 "自动发放"
                的 要单独处理, task_status 值都为空;

        */
        // 单独处理天天签到 签到过后还显示去完成的bug---------------------START
        // tab_user_point_type表中的: 天天签到,充值送积分,每日登录游戏,试玩有奖, 这四个字段 "自动发放" 的 要单独处理, task_status 值都为空;
        if(!empty($list)){
            $todayStartTime = strtotime(date('Y-m-d')); 
            $todayEndTime = strtotime(date('Y-m-d')) + 86399;

            $userTodayRecordTaskId = Db::table('tab_user_point_record')
                ->where(['user_id'=>UID])
                ->where(['create_time'=>['between',[$todayStartTime, $todayEndTime]]])
                ->order('id desc')
                ->column('type_id');
            // 下面的需要优化一下 类似 微信分享任务  QQ分享任务 的写法, 一次查表 再循环中不需要再次查表 by wjd 2021-6-17 14:33:22
            foreach($list as $key1=>$val1){
                // 天天签到
                if($val1['key'] == 'sign_in'){
                    // 判断今天是否已经签到
                    $point_record_info = Db::table('tab_user_point_record')->where(['user_id'=>UID,'type_id'=>$val1['id']])->order('id desc')->find();
                    $tmp_create_time = $point_record_info['create_time'] ?? 0;
                    // $sign_time = strtotime(date($tmp_create_time,'Y-m-d')) + 86400 >  time();
                    $sign_time = strtotime(date('Y-m-d',$tmp_create_time)) + 86400;
                    if($sign_time > time()){
                        // 今日已签到过
                        $list[$key1]['task_status'] = 3; // 已完成
                    }
                }
                // 任务中心修改昵称的 (没问题)
                // 充值送积分
                // 每日登录游戏
                if($val1['key'] == 'game_login'){
                    // 判断今天是否已经签到
                    $point_record_info = Db::table('tab_user_point_record')->where(['user_id'=>UID,'type_id'=>$val1['id']])->order('id desc')->find();
                    $tmp_create_time = $point_record_info['create_time'] ?? 0;
                    // $sign_time = strtotime(date($tmp_create_time,'Y-m-d')) + 86400 >  time();
                    $sign_time = strtotime(date('Y-m-d',$tmp_create_time)) + 86400;
                    if($sign_time > time()){
                        // 今日已签到过
                        $list[$key1]['task_status'] = 3; // 已完成
                    }
                }
                // 试玩有奖
                // 每日首充
                if ($val1['key'] == 'first_pay_every_day') {
                    // 判断今天是否完成首充
                    $spend = new SpendModel();
                    $today = Time ::today();
                    $todayMap = [];
                    $todayMap['user_id'] = UID;
                    $todayMap['pay_status'] = 1;
                    $todayMap['pay_time'] = ['between', [$today[0], $today[1]]];
                    $alreadyPay = $spend -> where($todayMap) -> find();
                    if ($alreadyPay) {
                        // 今日已充值
                        $list[$key1]['task_status'] = 3; // 已完成
                    }
                }
                // 微信分享任务
                if($val1['key'] == 'wx_friends_share'){
                    // $val1['id']; // 任务id
                    if(in_array($val1['id'], $userTodayRecordTaskId)){
                        $list[$key1]['task_status'] = 3; // 已完成
                    }
                }
                // QQ分享任务
                if($val1['key'] == 'qq_zone_share'){
                    if(in_array($val1['id'], $userTodayRecordTaskId)){
                        $list[$key1]['task_status'] = 3; // 已完成
                    }
                }

            }

        }
        // var_dump($list);exit;

        // var_dump($data);exit;
        // var_dump($point_record_info);
        // var_dump($sign_time);
        // var_dump(time());
        // exit;
        // 单独处理天天签到 签到过后还显示去完成的bug---------------------END

        $data = array_group_by($list,'type');
        ksort($data);
        $this->assign('data',$data);
        return $this->fetch();
    }

    public function receive_award(PointTypeLogic $logicPointType)
    {
        $res = $logicPointType->receive_award($this->request->post('key'),UID,$this->request->post('extend'));
        if(is_array($res)){
            $this->success('恭喜您轻松地获得了'.$res['point'].'积分奖励');
        }else{
            $msg='领取失败';
            switch ($res){
                case -1:
                    $msg='奖励通道暂时关闭';
                    break;
                case -2:
                    $msg='数据异常，请联系客服';
                    break;
                case -3:
                    $msg='任务还未完成';
                    break;
                case -4:
                    $msg='奖励已领取，请不要重复操作';
                    break;
            }
            $this->error($msg);
        }
    }
    public function signin(PointLogic $logicPoint){
        $signDetail = $logicPoint->sign_detail(session('member_auth.user_id'));
        if($signDetail==-1){
            $this->error('签到功能暂时关闭，请稍后再试');
        }
        $this->assign('detail',$signDetail);
        $age_status = get_user_entity(session('member_auth.user_id'),false,'age_status')['age_status'];
        $this->assign('age_status',$age_status);
        // 增加判断是哪个端访问的
        $device_type = get_devices_type(); // iphone 是2  安卓是1  其他为空
        $this->assign('device_type',$device_type);

        return $this->fetch();
    }
    public function doSign(PointLogic $logicPoint)
    {
        $res = $logicPoint->sign_in(session('member_auth.user_id'));
        if($res==-1){
            $this->error('签到功能暂时关闭，请稍后再试');
        }elseif($res==-2){
            $this->error('今日已签到，请不要重复签到');
        }elseif($res==-3){
            $this->error('请先进行实名认证');
        }else{
            $this->success('签到成功');
        }
    }

	 public function integral(){
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @消耗积分记录
     * @author: 郭家屯
     * @since: 2020/6/2 14:56
     */
    public function get_upoint()
    {
        $logicUse = new PointUseLogic();
        $p = $this->request->param('p',1);
        $limit = $this->request->param('limit',10);
        $data = $logicUse->lists_page(session('member_auth.user_id'),$p,$limit);
        return json($data);
    }
    /**
     * @函数或方法说明
     * @获取积分记录
     * @author: 郭家屯
     * @since: 2020/6/2 14:56
     */
    public function get_gpoint()
    {
        $p = $this->request->param('p',1);
        $limit = $this->request->param('limit',10);
        $logicGet = new PointShopRecordLogic();
        $data = $logicGet->lists_page(session('member_auth.user_id'),$p,$limit);
        return json($data);
    }
    public function useDetail(){
        $logicUse = new PointUseLogic();
        $postData = request()->post();
        if(empty($postData['id'])||empty($postData['item_id'])){
            $this->error('数据错误');
        }
        $data = $logicUse->detail($postData['id'],$postData['item_id'],session('member_auth.user_id'));
        if(!$data){
            $this->error('数据不存在，请联系客服');
        }else{
            return json(['code'=>200,'data'=>$data]);
        }
    }


    /**
     * @提交微信cdkey
     *
     * @author: zsl
     * @since: 2021/5/15 15:47
     */
    public function putWechatCdk()
    {
        $this -> isLogin();
        $cdkey = $this -> request -> post('cdkey');
        if (empty($cdkey)) {
            $this -> error('请输入兑换码');
        }
        // 获取任务中配置的值
        $mPointType = new PointTypeModel();
        $trueCdkey = $mPointType -> where(['key' => 'subscribe_wechat']) -> value('cdkey');
        if ($cdkey != $trueCdkey) {
            $this -> error('兑换码不正确，请关注公众号获取最新兑换码');
        }
        // 保存用户cdkey
        $mUser = new UserModel();
        $result = $mUser -> where(['id' => UID]) -> setField('wechat_cdkey', $cdkey);
        if (false === $result) {
            $this -> error('提交失败');
        } else {
            $this -> success('提交成功');
        }
    }


}
