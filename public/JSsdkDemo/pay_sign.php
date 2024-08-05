<?php

function check_pay(){
    $spen_data['par']['amount']=$_POST['pay_amount']*100;//金额 *100
	$spen_data['par']['props_name']='钻石';
	$spen_data['par']['trade_no']='SP_'.time().rand(1000, 9999); //cp订单号 cp生成
	$spen_data['par']['user_id'] = $_POST['user_id']; //cp订单号 cp生成
	$spen_data['par']['game_appid']=$_POST['game_appid'];//game_appld 运营方获取
    $spen_data['par']['channelExt']=$_POST['channelExt']; //登录时运营方返回
    $spen_data['par']['timestamp']=$_POST['timestamp']; //登录时运营方返回

    ksort($spen_data['par']);//字典排序
    foreach ($spen_data['par'] as $k => $v) {
        $tmp[] = $k . '=' . urlencode($v);
    }
    $str = implode('&', $tmp) . 'xiguff';
    //file_put_contents(dirname(__FILE__).'/order.txt',json_encode($str));
    $spen_data['par']['sign'] = md5($str);
   echo json_encode(['code' => 200, 'msg' => '成功', 'data' => ['spen_data' => $spen_data['par']]]);
}
echo check_pay();

?>

