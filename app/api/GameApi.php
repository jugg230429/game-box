<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */

namespace app\api;

use app\issue\model\IssueGameModel;
use app\recharge\model\SpendModel;
use think\Db;

class GameApi
{
    /**
     * 平台支付结果通知cp
     * @param array $param
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function game_pay_notify($param = [])
    {
        $spendmodel = new SpendModel();
        $pay_map['pay_status'] = 1;
        $pay_map['pay_game_status'] = 0;
        $pay_map['pay_order_number'] = $param['out_trade_no'];
        $pay_data = $spendmodel->field('id,game_id,extend,pay_order_number,cost,user_id,small_id,sdk_version,server_id')->where($pay_map)->find();
        $pay_data = empty($pay_data)?[]:$pay_data->toArray();
        if (empty($pay_data)) {
            // 记录回调信息
            $this->write_callback_info($pay_data['id'],'','','游戏已通知或未找到相关数据');
            $this->error_record("游戏已通知或未找到相关数据");
            return false;
        }
        //页游通知
        if($pay_data['sdk_version'] == '4'){
            $result = $this->yySignData($pay_data);
            return $result;
        }
        //获取游戏设置信息
        $map['game_id'] = $pay_data['game_id'];
        $game_data = Db::table('tab_game_set')->field('game_key,pay_notify_url')->where($map)->find();
        if (empty($game_data)) {
            // 记录回调信息
            $this->write_callback_info($pay_data['id'],'','','未找到指定游戏数据');
            $this->error_record("未找到指定游戏数据");
            return false;
        }
        if (empty($game_data['pay_notify_url'])) {
            // 记录回调信息
            $this->write_callback_info($pay_data['id'],'','','未设置游戏支付通知地址');
            $this->error_record("未设置游戏支付通知地址");
            return false;
        }
        $gameInfo = explode('_xgsdk_',$pay_data['extend']);
        $data = array(
            "game_order" => $gameInfo[0],
            "out_trade_no" => $pay_data['pay_order_number'],
            'pay_extra' => request()->host(),
            "pay_status" => 1,
            "price" => $pay_data['cost'],
            "user_id" => $pay_data['small_id']?:$pay_data['user_id'],
        );
//        ksort($data);
        if($pay_data['sdk_version'] == '3'){
            $data['sign'] = $this->h5SignData($data,$game_data['game_key']);
        }else{
            $datasign = implode($data);
            $md5_sign = md5($datasign.$game_data['game_key']);
            $data['sign'] = $md5_sign;
            if(!empty($gameInfo[1])){
                $data['game_extend'] = $gameInfo[1];
            }
        }
        $result = $this->post($data, $game_data['pay_notify_url']);
        // 记录回调信息
        $this->write_callback_info($pay_data['id'],$data,$game_data['pay_notify_url'],$result);
        if ($result == "success"||json_decode($result,true)['status'] == "success") {
            $this->update_game_pay_status($pay_data['pay_order_number']);
            return 'success';
        } else {
            \think\Log::record("通知参数：" .json_encode($data) . ";通知返回值：" . $result . ";通知地址：" . $game_data['pay_notify_url']);
            return false;
        }
    }


    /**
     * 平台支付结果通知cp
     * @param array $param
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function game_ff_pay_notify($param = [])
    {
        $spendmodel = new \app\issue\model\SpendModel();
        $pay_map['pay_status'] = 1;
        $pay_map['pay_game_status'] = 0;
        $pay_map['pay_order_number'] = $param['out_trade_no'];
        $pay_data = $spendmodel->field('game_id,extend,pay_order_number,pay_amount,user_id,sdk_version,server_id')->where($pay_map)->find();
        $pay_data = empty($pay_data)?[]:$pay_data->toArray();
        if (empty($pay_data)) {
            $this->error_record("游戏已通知或未找到相关数据");
            return false;
        }
        //页游通知
        if($pay_data['sdk_version'] == '4'){
            $result = $this->ff_yySignData($pay_data);
            return $result;
        }
        //获取游戏设置信息
        $map['id'] = $pay_data['game_id'];
        $game_data = Db::table('tab_issue_game')->field('game_key,pay_notify_url')->where($map)->find();
        if (empty($game_data)) {
            $this->error_record("未找到指定游戏数据");
            return false;
        }
        if (empty($game_data['pay_notify_url'])) {
            $this->error_record("未设置游戏支付通知地址");
            return false;
        }
        $gameInfo = explode('_xgsdk_',$pay_data['extend']);
        $data = array(
                "game_order" => $gameInfo[0],
                "out_trade_no" => $pay_data['pay_order_number'],
                'pay_extra' => request()->host(),
                "pay_status" => 1,
                "price" => $pay_data['pay_amount'],
                "user_id" => 'sue_'.$pay_data['user_id'],
        );
//        ksort($data);
        if($pay_data['sdk_version'] == '3'){
            $data['sign'] = $this->h5SignData($data,$game_data['game_key']);
        }else{
            $datasign = implode($data);
            $md5_sign = md5($datasign.$game_data['game_key']);
            $data['sign'] = $md5_sign;
            if(!empty($gameInfo[1])){
                $data['game_extend'] = $gameInfo[1];
            }
        }
        $result = $this->post($data, $game_data['pay_notify_url']);
        if ($result == "success"||json_decode($result,true)['status'] == "success") {
            return true;
        } else {
            \think\Log::record("游戏支付通知信息：" . $result . ";游戏通知地址：" . $game_data['pay_notify_url']);
            return false;
        }
    }

    /**
     * @函数或方法说明
     * @页游通知结果
     * @param array $spend
     *
     * @author: 郭家屯
     * @since: 2020/9/25 9:58
     */
    protected function yySignData($spend=[])
    {
        $game = get_game_entity($spend['game_id'],'interface_id,cp_game_id');
        $interface = Db::table('tab_game_interface')->field('tag')->where('id',$game['interface_id'])->find();
        if(empty($interface)){
            $this->write_callback_info($spend['id'],'','','游戏接口文件不存在');
            return false;
        }
        $server = Db::table('tab_game_server')->field('server_num')->where('id',$spend['server_id'])->find();
        if(empty($server)){
            $this->write_callback_info($spend['id'],'','','游戏区服不存在');
            return false;
        }
        $controller_name = "\app\sdkyy\api\\".$interface['tag'];
        $gamecontroller = new $controller_name;
        $pay_order_number = $spend['pay_order_number'];
        $result = $gamecontroller->pay($pay_order_number,$spend['cost'],$game['cp_game_id'],$server['server_num'],$spend['user_id']);
        $this->write_callback_info($spend['id'],'','',$result);
        if($result['status'] == 1){
            $this->update_game_pay_status($spend['pay_order_number']);
            return 'success';
        }
        return false;
    }

    /**
     * @函数或方法说明
     * @分发页游通知结果
     * @param array $spend
     *
     * @author: 郭家屯
     * @since: 2020/9/25 9:58
     */
    protected function ff_yySignData($spend=[])
    {
        $game = get_table_entity(new IssueGameModel(),['id'=>$spend['game_id']],'interface_id,cp_game_id');
        $interface = Db::table('tab_game_interface')->field('tag')->where('id',$game['interface_id'])->find();
        if(empty($interface)){
            return false;
        }
        $server = Db::table('tab_game_server')->field('server_num')->where('id',$spend['server_id'])->find();
        if(empty($server)){
            return false;
        }
        $controller_name = "\app\sdkyy\api\\".$interface['tag'];
        $gamecontroller = new $controller_name;
        $result = $gamecontroller->pay($spend['pay_order_number'],$spend['cost'],$game['cp_game_id'],$server['server_num'],'sue_'.$spend['user_id']);
        if($result['status'] == 1){
           return true;
        }
        return false;
    }

    /**
     *修改游戏支付状态
     */
    private function update_game_pay_status($out_trade_no = "")
    {
        $model = new SpendModel();
        $map['pay_order_number'] = $out_trade_no;
        $result = $model->where($map)->setField('pay_game_status', 1);
        if ($result) {
            return $result;
        } else {
            $this->error_record("修改游戏支付状态失败");
            return false;
        }
    }

    //记录错误日志
    private function error_record($msg = "")
    {
        \think\Log::record($msg);
    }

    /**
     *post提交数据
     */
    protected function post($param, $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置等待时间
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

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

    /**
     * @记录回调信息
     *
     * @author: zsl
     * @since: 2021/2/4 18:11
     */
    private function write_callback_info($order_id, $param, $url, $result)
    {
        $spendmodel = new SpendModel();
        // 记录回调信息
        $callbackInfo = ['data' => $param, 'url' => $url, 'result' => $result];
        $spendmodel -> where(['id' => $order_id]) -> setField('game_notify_info', json_encode($callbackInfo));
    }

}