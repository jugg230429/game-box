<?php
/**
 * @Copyright (c) 2021  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License 江苏溪谷网络科技有限公司版权所有
 * @since 2021-04-21
 */
namespace app\sdkw\logic;

use app\api\GameApi;
use app\recharge\model\SpendModel;
use think\Db;
use think\Exception;

class ExchangeLogic extends BaseLogic
{
    /**
     * 方法 exchange
     *
     * @descript 苹果内购下单
     *
     * @param $request
     * @return array|int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/25 0025 11:26
     */
    public function exchange($request)
    {
        try {
            //封禁判断-20210713-byh
            if(!judge_user_ban_status(0,$request['game_id'],$request['user_id'],$request['equipment_num'],get_client_ip(),$type=3)){
                return 1126;//当前被禁止充值，请联系客服
            }
            $user_entity = get_user_entity(
                $request['user_id'],
                false,
                'account,nickname,age_status,promote_id,promote_account');
            $out_trade_no = create_out_trade_no("SP_");

            $game = get_game_entity($request['game_id']);
            $apple_in_app_set = $game['apple_in_app_set'];
            $product_id = $request['product_id'];
            if (empty($apple_in_app_set) || empty($product_id)) {
                return 1122;
            }
            $request['pay_amount'] = $request['cost'] = 0;
            $apple_in_app_set = json_decode($apple_in_app_set, true);
            foreach($apple_in_app_set as $key => $value) {
                if ($product_id == $value['product_id']) {
                    $request['us_cost'] = $value['us'];
                    $request['pay_amount'] = $request['cost'] = $value['cn'];
                    break;
                }
            }
            if (empty($request['pay_amount']) || empty($request['cost'])) {
                return 1122;
            }

            //第三方支付验证订单号
            $model = new SpendModel();
            if ($request['extend']) {
                $extend_data = $model->field('id')
                    ->where('extend', $request['extend'])
                    ->where('pay_status', 1)
                    ->where('game_id', $request['game_id'])
                    ->find();
                if ($extend_data) {
                    return 1055;
                }
            }

            #获取订单信息
            $request['pay_order_number'] = $out_trade_no;
            $request['pay_way'] = 6;
            $request['currency_cost'] = $request['price'];
            $request['area'] = 1;
            add_spend($request, $user_entity);
            $data = array("out_trade_no" => $out_trade_no);
            return [
                'code' => 202,
                'data' => $data
            ];
        } catch (Exception $e) {
            return 1111;
        }
    }

    /**
     * 方法 verify
     *
     * @descript 苹果支付验证
     *
     * @param $request
     * @return array|int
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/23 0023 9:16
     */
    public function verify($request)
    {
        $model = new SpendModel();
        try {
            $data = $this->validate($request, 1);
            $info = json_decode($data, true);
            if ($info['status'] == 21007) {//沙盒
                $data = $this->validate($request, 2);
                $info = json_decode($data, true);
            }
//            if (empty($info['receipt']['in_app'][0]['transaction_id'])) {
//                return 1125;
//            }
            if ($info['status'] == 0) {
                $transaction_id = !empty($info['receipt']['transaction_id'])?$info['receipt']['transaction_id']:$info['receipt']['in_app'][0]['transaction_id'];
                //$transaction_id = $info['receipt']['in_app'][0]['transaction_id'];
                $paperVerify = $model->field('id,order_number')
                    ->where(array('pay_way' => 6, 'order_number' => $transaction_id))
                    ->find();
                if ($paperVerify) {
                    return 1056;
                }
                //查询订单
                $out_trade_no = $request['out_trade_no'];
                $map['pay_order_number'] = $out_trade_no;
                $payamountVerify = $model->where($map)->find()->toArray();


                //验证product_id是否一致
                $product_id = !empty($info['receipt']['product_id']) ? $info['receipt']['product_id'] : $info['receipt']['in_app'][0]['product_id'];
                if ($payamountVerify['product_id'] != $product_id) {
                    return 1056;
                }

                //对比金额加入异常订单
                if ($payamountVerify['currency_cost'] != $request['price']) {
                    $disdata = array();
                    $disdata['spend_id'] = $payamountVerify['id'];
                    $disdata['pay_order_number'] = $payamountVerify['pay_order_number'];
                    $disdata['extend'] = $payamountVerify['extend'];
                    $disdata['last_amount'] = $request['price'];
                    $disdata['currency'] = $request['currency_code'];
                    $disdata['create_time'] = time();
                    $pay_distinction = Db::table('tab_spend_distinction')->insert($disdata);
                    if (!$pay_distinction) {
                        \think\Log::record('数据插入失败 pay_order_number' . $payamountVerify['pay_order_number']);
                    }
                }
                //更新订单数据
                $field = array(
                    "pay_status" => 1,
                    "currency_cost" => $request['price'],
                    "receipt" => $data,
                    "order_number" => $transaction_id
                );
                if ($payamountVerify['promote_id']) {
                    $promote = get_promote_entity($payamountVerify['promote_id'],'pattern');
                    if ($promote['pattern'] == 0) {
                        $field['is_check'] = 1;
                    }
                }
                $user = get_user_entity(
                    $payamountVerify['user_id'],
                    false,
                    'id,account,promote_id,promote_account,parent_name,parent_id,cumulative');
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
                    return 1057;
                }
                //结算分成
                if ($payamountVerify['promote_id']) {
                    if (isset($promote) && $promote['pattern'] == 0) {
                        set_promote_radio($payamountVerify,$user);
                    }
                }
                $game = new GameApi();
                $param['out_trade_no'] = $out_trade_no;
                $game->game_pay_notify($param);
                
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

                return 207;
            } else {
                return 1058;
            }
        } catch (\Exception $e) {
            return ['code' => 1058, 'msg' => $e->getMessage(),'data'=>''];
        }
    }

    /**
     * 方法 validate
     *
     * @descript 苹果验证参数
     *
     * @param $receipt
     * @param int $isSandbox
     * @return bool|string
     * @throws \Exception
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/21 0021 17:18
     */
    private function validate($receipt, $isSandbox = 1)
    {
        if ($isSandbox == 2) {
            $endpoint = 'https://sandbox.itunes.apple.com/verifyReceipt';
        } else {
            $endpoint = 'https://buy.itunes.apple.com/verifyReceipt';
        }
        $postData = json_encode(
            array('receipt-data' => $receipt["receipt"])
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
