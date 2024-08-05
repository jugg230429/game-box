<?php

namespace app\thirdgame\controller;

use app\thirdgame\logic\SpendLogic;
use app\thirdgame\logic\UserLogic;
use think\Controller;

class ApiController extends Controller
{
    protected function _initialize()
    {
        app_auth_value();
        if (AUTH_THIRD_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买第三方游戏权限');
            } else {
                $this->error('请购买第三方游戏权限', url('admin/main/index'));
            };
        }
        if($this->request->isPost()){
            $request = $this->request->param();
            if(empty($request['timestamp']) || empty($request['sign']) || empty($request['platform'])){
                $this->set_message(0,'参数异常');
            }
            // 查询平台是否存在
            $map['platform_url'] = $request['platform'];
            $map['status'] = 1;
            $platformInfo = get_platform_entity_by_map($map,'api_key');
            if(empty($platformInfo)){
                $this->set_message(0,'平台不存在或已关闭');
            }
            // 时间戳验证
            if(abs($request['timestamp']- time()) > 60){
                $this->set_message(0,'请求超时');
            }
            // 验签
            $signStr = md5($request['platform'] . $request['timestamp'] . $platformInfo['api_key']);
            if($signStr !== $request['sign']){
                $this->set_message(0,'验签失败');
            }
        }else{
            $this->set_message(0,'非法请求');
        }
    }

    /**
     * 第三方用户登录接口
     *
     * @author: Juncl
     * @time: 2021/08/24 14:02
     */
    public function user_login()
    {
        $request = $this->request->param();
        if(empty($request['account']) || empty($request['password'])){
            $this->set_message(0,'登录信息不能为空',[]);
        }
        $UserLogic = new UserLogic();
        $result = $UserLogic->login($request);
        if($result['status']>0){
            $this->set_message(200,'登录成功',$result['data']);
        }else{
            $this->set_message(0,$result['msg']);
        }
    }

    /**
     * 第三方用户注册接口
     *
     * @author: Juncl
     * @time: 2021/08/24 14:35
     */
    public function user_register()
    {
        $request = $this->request->param();
        if(empty($request['account']) || empty($request['password'])){
            $this->set_message(0,'用户信息不能为空',[]);
        }
        $UserLogic = new UserLogic();
        $result = $UserLogic->register($request);
        if($result['status']>0){
            $this->set_message(200,'注册成功',$result['data']);
        }else{
            $this->set_message(0,$result['msg']);
        }
    }

    /**
     * 获取用户信息
     *
     * @author: Juncl
     * @time: 2021/08/24 20:14
     */
    public function get_user_info()
    {
        $request = $this->request->param();
        if(empty($request['type'])){
            $this->set_message(0,'请求信息不能为空',[]);
        }
        $UserLogic = new UserLogic();
        $result = $UserLogic->getUserInfo($request);
        if($result === false){
            $this->set_message(0,'账号不存在或被禁用',[]);
        }else{
            $this->set_message(200,'成功',$result);
        }
    }

    /**
     * 更新玩家状态（密码，手机号，email，age_status...）
     *
     * @author: Juncl
     * @time: 2021/08/24 20:39
     */
    public function set_user_info()
    {
        $request = $this->request->param();
        if(empty($request['user_id'])){
            $this->set_message(0,'用户信息不能为空',[]);
        }
        if(!isset($request['password']) && !isset($request['phone']) && !isset($request['idcard']) && !isset($request['real_name']) && !isset($request['email']) && !isset($request['nickname'])){
            $this->set_message(0,'修改信息不能为空',[]);
        }
        if(!empty($request['old_password'])){
            $oldPassword = get_user_entity($request['user_id'],false,'password')['password'];
            if(!xigu_compare_password($request['old_password'],$oldPassword)){
                $this->set_message(0,'旧密码错误',[]);
            }
        }
        $UserLogic = new UserLogic();
        $result = $UserLogic->setUserInfo($request);
        if($result){
            $this->set_message(200,'修改成功');
        }else{
            $this->set_message(0,'修改失败');
        }
    }

    /**
     * 订单初始化，返回支付渠道
     *
     * @author: Juncl
     * @time: 2021/08/24 21:14
     */
    public function pay_init()
    {
        $request = $this->request->param();
        $SpendLogic = new SpendLogic();
        $result = $SpendLogic->pay_init($request);
        if($result['status']>0){
            $this->set_message(200,'成功',$result['data']);
        }else{
            $this->set_message(0,$result['msg']);
        }
    }

    /**
     * 存入订单
     *
     * @author: Juncl
     * @time: 2021/08/24 21:51
     */
    public function pay_order()
    {
        $request = $this->request->param();
        $SpendLogic = new SpendLogic();
        $result = $SpendLogic->add_spend($request);
        if($result['status']>0){
            //返回系统订单号
            $data['pay_order_number'] = $result['data'];
            $this->set_message(200,'成功',$data);
        }else{
            $this->set_message(0,$result['msg']);
        }
    }

    /**
     * 修改订单状态
     *
     * @author: Juncl
     * @time: 2021/08/26 11:46\
     */
    public function set_pay_status()
    {
        $request = $this->request->param();
        if(empty($request['pay_order_number'])){
            $this->set_message(0,'订单不能为空');
        }
        $SpendLogic = new SpendLogic();
        if($request['notify_type'] == 1){
            $result = $SpendLogic->set_pay_status($request);
        }elseif($request['notify_type'] == 2){
            $result = $SpendLogic->set_game_status($request);
        }else{
            $this->set_message(0,'订单修改方式异常');
        }
        if($result['status']>0){
            $this->set_message(200,'订单修改成功');
        }else{
            $this->set_message(0,$result['msg']);
        }
    }

    /**
     * 上传角色信息接口
     *
     * @author: Juncl
     * @time: 2021/08/31 17:25
     */
    public function save_user_play()
    {
        $request = $this->request->param();
        $UserLogic = new UserLogic();
        $result = $UserLogic->save_user_play($request);
        if($result['status']){
            $this->set_message(200,'角色信息上传成功');
        }else{
            $this->set_message(0,'角色信息上传失败');
        }
    }

    /**
     * 接口返回
     *
     * @param int $status
     * @param string $msg
     * @param array $data
     * @author: Juncl
     * @time: 2021/08/19 15:47
     */
    protected function set_message($status=0, $msg='', $data=[])
    {
        $return = array(
            'status' => $status,
            'msg'    => $msg,
            'data'   => $data
        );
        echo json_encode($return);exit;
    }

}