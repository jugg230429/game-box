<?php
function check_role(){
    $spen_data['par']['server_id']=$_POST['server_id'];
	$spen_data['par']['server_name']=$_POST['server_name'];
	$spen_data['par']['user_id'] = $_POST['user_id'];
	$spen_data['par']['game_appid']=$_POST['game_appid'];
	$spen_data['par']['role_id'] = $_POST['role_id']; 
	$spen_data['par']['role_name']=$_POST['role_name']; 
	$spen_data['par']['role_level']=$_POST['role_level'];
    $spen_data['par']['combat_number']=$_POST['combat_number'];
    $spen_data['par']['channelExt']=$_POST['channelExt']; //登录时运营方返回
    $spen_data['par']['timestamp']=$_POST['timestamp'];
	ksort($spen_data['par']);//字典排序
	foreach ($spen_data['par'] as $k => $v) {
	    $tmp[] = $k . '=' . urlencode($v);
	}
	$str = implode('&', $tmp);
	$spen_data['par']['sign'] = MD5($str.'xiguff');
   echo json_encode(['code' => 200, 'msg' => '成功', 'data' => ['spen_data' => $spen_data['par']]]);
}
echo check_role();

