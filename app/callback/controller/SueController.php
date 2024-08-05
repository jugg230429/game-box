<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */

namespace app\callback\controller;


use app\api\GameApi;
use app\issue\model\PlatformModel;
use app\issue\model\IssueGameApplyModel;
use think\Request;
use think\Db;
class SueController extends BaseController
{

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    /**
     * @函数或方法说明
     * @创建订单
     * @author: 郭家屯
     * @since: 2020/10/22 15:20
     */
    public function callback()
    {
        if ($this->request->isPost()) {
            $notify = $this->request->post();
        } elseif ($this->request->isGet()) {
            $notify = $this->request->get();
        } else {
            $notify = file_get_contents("php://input");
            if (empty($notify)) {
                $this->record_logs("Access Denied");
                exit('Access Denied');
            }
        }
        $model = new \app\issue\model\SpendModel();
        $model->where('pay_order_number',$notify['game_order']);
        $spend = $model->find();
        if(!$spend || $spend->pay_status == 0){
            echo '非法订单';die;
        }
        //判断平台状态
        $mPlatform = new  PlatformModel();
        $platformData = $mPlatform
                ->alias('plat')
                ->field('plat.id,plat.open_user_id,plat.platform_config_h5,platform_config_sy,platform_config_yy,plat.service_config,plat.controller_name_h5,plat.controller_name_yy,plat.controller_name_sy,plat.status,user.balance,min_balance,user.settle_type,plat.order_notice_url_h5,plat.order_notice_url_yy')
                ->where('plat.id','=',$spend->platform_id)
                ->join(['tab_issue_open_user'=>'user'],'user.id=plat.open_user_id')
                ->find();
        if($spend->sdk_version == 3) {
            if(!file_exists(APP_PATH.'issueh5/logic/pt/'.$platformData['controller_name_h5'].'Logic.php')){
                echo '平台接口文件错误';die;
            }
            $class = '\app\\issueh5\\logic\\pt\\'.$platformData['controller_name_h5'].'Logic';
        }elseif($spend->sdk_version == 4) {
            if(!file_exists(APP_PATH.'issueyy/logic/pt/'.$platformData['controller_name_yy'].'Logic.php')){
                echo '平台接口文件错误';die;
            }
            $class = '\app\\issueyy\\logic\\pt\\'.$platformData['controller_name_yy'].'Logic';
        }else{
            if(!file_exists(APP_PATH.'issuesy/logic/sdk/Sdk_'.$platformData['controller_name_sy'].'Logic.php')){
                echo '平台接口文件错误';die;
            }
            $class = '\app\\issuesy\\logic\\sdk\\Sdk_'.$platformData['controller_name_sy'].'Logic';
        }
        $logic = new $class();
        $platformapplymodel = new IssueGameApplyModel();
        $platformapplymodel->field('platform_config');
        $platformapplymodel->where('game_id',$spend['game_id']);
        $platformapplymodel->where('platform_id',$spend['platform_id']);
        $platformapplymodel->where('open_user_id',$spend['open_user_id']);
        $game_data = $platformapplymodel->find();
        $game_data = json_decode($game_data->platform_config,true);

        $params = $notify;
        unset($params['sign']);
        if($spend->sdk_version == '3'){
            $game_key = $logic->get_game_key($game_data);
            $md5_sign = $this->h5SignData($params,$game_key);
        }elseif($spend->sdk_version == '4'){
            $pay_key = $logic->get_pay_key($game_data);
            $md5_sign = md5($notify['game_order'].$notify['user_id'].$notify['game_id'].$notify['server_id'].$notify['money'].$notify['pid'].$notify['time'].$pay_key);
        }else{
            $game_key = $logic->get_game_key($game_data);
            $datasign = implode($params);
            $md5_sign = md5($datasign.$game_key);
        }
        if($md5_sign != $notify['sign']){
            echo '验签失败';die;
        }
        if($spend->pay_game_status == 0){
            $game = new GameApi();
            $param['out_trade_no'] = $notify['game_order'];
            $result = $game->game_ff_pay_notify($param);
            if($result){
                $spend->pay_game_status = 1;
            }
        }
        if($spend->sdk_version == 3){
            //通知分发
            $logic = new \app\issueh5\logic\PayLogic();
        }else{
            //通知分发
            $logic = new \app\issuesy\logic\PayLogic();
        }
        $notice_status = $logic->pay_ff_notice($spend->toArray());
        if($notice_status){
            $spend->pay_ff_status = 1;
        }
        $result = $spend->save();
        if($result !== false){
            echo 'success';die;
        }else{
            echo 'fail';die;
        }
    }



    //H5加密方式
    protected function h5SignData($data, $game_key)
    {
        ksort($data);
        foreach ($data as $k => $v) {
            $tmp[] = $k . '=' .urlencode($v);
        }
        $str = implode('&', $tmp) . $game_key;
        //file_put_contents(dirname(__FILE__)."/check.txt",$str);
        return md5($str);
    }

}