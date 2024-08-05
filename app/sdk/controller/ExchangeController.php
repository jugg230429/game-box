<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/1
 * Time: 14:38
 */

namespace app\sdk\controller;

use app\recharge\model\SpendModel;
use app\member\model\UserModel;
use app\api\GameApi;
use think\Db;

class ExchangeController extends BaseController
{
    /**
     * [苹果支付接口]
     * @author 郭家屯[gjt]
     */
    public function exchange()
    {
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($request['promote_id'],$request['game_id'],$request['user_id'],$request['equipment_num'],get_client_ip(),$type=3)){
            $this -> set_message(1061, "当前被禁止充值，请联系客服");
        }
        $game = get_game_entity($request['game_id'],'appstatus,game_name');
        $request['game_name'] = $game['game_name'];
        $user_entity = get_user_entity($request['user_id'],false,'account,nickname,age_status,promote_id,promote_account');
        if(empty($user_entity['age_status']) && get_user_config_info('age')['real_pay_status']==1){
            $this->set_message(1075, get_user_config_info('age')['real_pay_msg']?:'根据国家关于《网络游戏管理暂行办法》要求，平台所有玩家必须完成实名认证后才可以进行游戏充值！');
        }
        $usermodel = new UserModel();
        //判断是否是所属小号
        $isusersmall = $usermodel->is_user_small($request['user_id'],$request['small_id']);
        if(!$isusersmall){
            $this -> set_message(1080, "小号不属于该账户");
        }else{
            $request['small_nickname'] = get_user_entity($request['small_id'],false,'nickname')['nickname'];
        }
        $prefix = $request['code'] == 1 ? "SP_" : "PF_";
        $out_trade_no = create_out_trade_no($prefix);
        //第三方支付验证订单号
        if ($request['code'] == 1) {
            $model = new SpendModel();
            if ($request['extend']) {
                $extend_data = $model->field('id')->where('extend', $request['extend'])->where('pay_status', 1)->where('game_id', $request['game_id'])->find();
                if ($extend_data) {
                    $this->set_message(1055, "订单号重复，请关闭支付页面重新支付");
                }
            }
        }
        //写入ios scheme信息
        if (!empty($request['scheme'])) {
            $game_scheme = Db ::table('tab_game') -> where(['id' => $request['game_id']]) -> value('sdk_scheme');
            if ($game_scheme != $request['scheme']) {
                Db ::table('tab_game') -> where(['id' => $request['game_id']]) -> setField('sdk_scheme', $request['ios_wxpay_scheme']);
            }
        }

        if ($game['appstatus'] == 0 && $request['code'] == 1) {  /* 苹果内购 */
            #获取订单信息
            $request['pay_order_number'] = $out_trade_no;
            $request['pay_way'] = 6;
            $request['title'] = $request['productId'];
            $request['pay_amount'] = $request['cost'] = $request['price'];
            add_spend($request,$user_entity);
            $data = array("out_trade_no" => $out_trade_no);
            $this->set_message(200, "获取成功",$data);
        } else { /* 平台币充值或第三方支付 */
            file_put_contents(ROOT_PATH . "/app/sdk/scheme/" . $request['game_id'] . ".txt", $request['scheme']);
            $request['pay_order_number'] = $out_trade_no;
            file_put_contents(ROOT_PATH . "/app/sdk/orderno/" . $request['user_id'] . "-" . $request['game_id'] . ".txt", think_encrypt(json_encode($request)));
            $data = array("out_trade_no" => $out_trade_no,'img' => cmf_get_domain() . '/sdk/pay/pay_way/user_id/' . $request['user_id'] . '/game_id/' . $request['game_id'] . '/type/1');
            $this->set_message(200, "获取成功",$data);
        }
    }

    /**
     *苹果支付验证
     */
    public function exchangeVerify()
    {
        #获取SDK上POST方式传过来的数据 然后base64解密 然后将json字符串转化成数组
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $model = new SpendModel();
        //开始执行验证
        try {
            $data = $this->getSignVeryfy($request, 1);
            $info = json_decode($data, true);
            if ($info['status'] == 21007) {//沙盒
                $data = $this->getSignVeryfy($request, 2);
                $info = json_decode($data, true);
            }
            if (empty($info['receipt']['in_app'][0]['transaction_id'])) {
                $this -> set_message(1056, '凭证不存在');
            }
            if ($info['status'] == 0) {
                $paperVerify = $model->field('id,order_number')->where(array('pay_way' => 6, 'order_number' => $info['receipt']['in_app'][0]['transaction_id']))->find();
                if ($paperVerify) {
                    $this->set_message(1056, '凭证重复');
                }
                //查询订单
                $out_trade_no = $request['out_trade_no'];
                $map['pay_order_number'] = $out_trade_no;
                $payamountVerify = $model->where($map)->find()->toArray();

                //验证product_id是否一致
                $product_id   = !empty($info['receipt']['product_id']) ? $info['receipt']['product_id'] : $info['receipt']['in_app'][0]['product_id'];
                if ($payamountVerify['product_id'] != $product_id) {
                    return 1056;
                }

                //对比金额加入异常订单
                if ($payamountVerify['pay_amount'] != $request['price']) {
                    $disdata = array();
                    $disdata['spend_id'] = $payamountVerify['id'];
                    $disdata['pay_order_number'] = $payamountVerify['pay_order_number'];
                    $disdata['extend'] = $payamountVerify['extend'];
                    $disdata['last_amount'] = $request['price'];
                    $disdata['currency'] = $request['currency'];
                    $disdata['create_time'] = time();
                    $pay_distinction = Db::table('tab_spend_distinction')->insert($disdata);
                    if (!$pay_distinction) {
                        \think\Log::record('数据插入失败 pay_order_number' . $payamountVerify['pay_order_number']);
                    }
                }
                //更新订单数据
                $field = array(
                    "pay_status" => 1,
                    "pay_amount" => $request['price'],
                    "receipt" => $data,
                    "order_number" => $info['receipt']['in_app'][0]['transaction_id']
                );
                if ($payamountVerify['promote_id']) {
                    $promote = get_promote_entity($payamountVerify['promote_id'],'pattern');
                    if ($promote['pattern'] == 0) {
                        $field['is_check'] = 1;
                    }
                }
                $user = get_user_entity($payamountVerify['user_id'],false,'id,account,promote_id,promote_account,parent_name,parent_id,cumulative');
                //更新VIP等级和充值总金额
                Db::startTrans();
                try {
                    set_vip_level($payamountVerify['user_id'], $payamountVerify['pay_amount'],$user['cumulative']);
                    $model->where($map)->update($field);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $this->set_message(1057, "支付状态修改失败");
                }
                //结算分成
                if ($payamountVerify['promote_id']) {
                    if (isset($promote) && $promote['pattern'] == 0) {
                        set_promote_radio($payamountVerify,$user);
                    }
                }
                // 用户阶段更改
                try{

                    $systemSet = cmf_get_option('admin_set');
                    if (empty($systemSet['task_mode'])) {
                        (new \app\common\task\HandleUserStageTask()) -> changeUserStage1(['user_id' => $payamountVerify['user_id']]);
                    } else {
                        (new \app\common\task\HandleUserStageTask()) -> saveOperation('', $payamountVerify['user_id'], 0);
                    }

                }catch(\Exception $e){

                }
                $game = new GameApi();
                $param['out_trade_no'] = $out_trade_no;
                $game->game_pay_notify($param);
                $this->set_message(200, "支付成功");
            } else {
                $this->set_message(1058, "支付失败");
            }
        } //捕获异常
        catch (\Exception $e) {
            $this->set_message(1058, $e->getMessage());
        }

    }

    /**
     * @函数或方法说明
     * @苹果订单异常接口
     * @author: 郭家屯
     * @since: 2019/8/14 16:57
     */
    public function exchange_abnormal()
    {
        #获取SDK上POST方式传过来的数据 然后base64解密 然后将json字符串转化成数组
        $request = json_decode(base64_decode(file_get_contents("php://input")), true);
        $request = get_real_promote_id($request);
        $orderno = explode(',', $request['orderno']);
        $model = new SpendModel();
        $result = $model->where('pay_order_number', 'in', $orderno)->where('pay_way', 6)->setField('pay_status', 2);
        if ($result) {
            $this->set_message(200, "支付状态修改成功");
        } else {
            $this->set_message(1057, "支付状态修改失败");
        }
    }

    /**
     * [苹果验证参数]
     * @param $receipt
     * @param int $isSandbox
     * @return mixed
     * @author 郭家屯[gjt]
     */
    private function getSignVeryfy($receipt, $isSandbox = 1)
    {
        if ($isSandbox == 2) {
            $endpoint = 'https://sandbox.itunes.apple.com/verifyReceipt';
        } else {
            $endpoint = 'https://buy.itunes.apple.com/verifyReceipt';
        }
        $postData = json_encode(
            array('receipt-data' => $receipt["paper"])
        );
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  //这两行一定要加，不加会报SSL 错误
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $errmsg = curl_error($ch);
        curl_close($ch);
        //判断时候出错，抛出异常
        if ($errno != 0) {
            throw new \Exception($errmsg, $errno);
        }
        return $response;
    }
    
}
