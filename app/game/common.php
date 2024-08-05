<?php

use think\Db;

/**
 * [游戏是否已有关联]
 * @param $id
 * @param $relation_id
 * @return bool|int
 * @author 郭家屯[gjt]
 */
function get_relation_game($id, $relation_id)
{
    if ($id == $relation_id) {
        $gdata = think\Db::table('tab_game')->where(array('relation_game_id' => $relation_id, 'id' => array('neq', $id)))->find();
        if (!$gdata) {
            return false;//未关联游戏  即没有例外一个版本
        } else {
            return true;
        }
    } else {
        //再次确认关联的游戏
        $gdata = think\Db::table('tab_game')->where(array('relation_game_id' => $relation_id, 'id' => $relation_id))->find();
        if ($gdata) {
            return true;
        } else {
            return -1;  //数据出错
        }
    }
}

//sdk加密方法
function get_ss($key)
{
    $verfy_key = "gnauhcgnem";
    $len = strlen($key);
    $res = "";
    for ($i = 0; $i < $len; $i++) {
        if ($i < 11) {
            $a = 0;
        } else {
            $a = -1;
        }
        $res .= chr(ord($key[$i]) ^ ord($verfy_key[$i % 10 + $a]));
    }
    return base64_encode($res);
}

/**
 * 生成唯一的APPID
 * @param  $str_key 加密key
 * @return string
 * @author 小纯洁
 */
function generate_game_appid($str_key = "")
{
    $guid = '';
    $data = $str_key;
    $data .= $_SERVER ['REQUEST_TIME'];
    $data .= $_SERVER ['HTTP_USER_AGENT'];
    $data .= $_SERVER ['SERVER_ADDR'];
    $data .= $_SERVER ['SERVER_PORT'];
    $data .= $_SERVER ['REMOTE_ADDR'];
    $data .= $_SERVER ['REMOTE_PORT'];
    $hash = strtoupper(hash('MD4', $guid . md5($data))); //ABCDEFZHIJKLMNOPQISTWARY
    $guid .= substr($hash, 0, 9) . substr($hash, 17, 8);
    return $guid;
}

/**
 * [获取游戏类型]
 * @param null $type
 * @return string
 * @author 郭家屯[gjt]
 */
function get_game_type_name($type = null)
{
    if (!isset($type) || empty($type)) {
        return '';
    }
    $cl = think\Db::table("tab_game_type")->where("id", $type)->find();
    return $cl['type_name'];
}

/**
 * 判断是否是游戏配置表参数
 *
 * @param array $name
 * @return bool
 * @author: Juncl
 * @time: 2021/08/14 15:08
 */
function check_game_attr($name=[]){
    $attr = ['cp_ratio','cp_pay_ratio'];
    if(in_array($name,$attr)){
        return true;
    }else{
        return false;
    }
}

/**
 * 获取表里面的指定参数值 (单个值)
 * created by wjd
 * 2021-8-19 15:53:56
*/
function getTableValue($table, $condition_field, $condition_value, $field)
{   
    $useful_value = '';
    try{
        $useful_value = Db::table($table)->where([$condition_field=>$condition_value])->value($field);
    } catch (\Exception $e) {
        $useful_value = -1;
    }
    return $useful_value;
}

/**
 * 获取表里面的指定参数值 (多个值)
 * created by wjd
 * 2021-8-19 15:53:56
*/
function getTableValues($table, $condition_field, $condition_value, $field)
{   
    $useful_value = [];
    try{
        $useful_value = Db::table($table)->where([$condition_field=>$condition_value])->field($field)->find();
    } catch (\Exception $e) {
        $useful_value = [-1];
    }
    return $useful_value;
}

/**
 * 修改 tab_game_attr (修改数据 或者更新)
 * created by wjd
 * 2021-8-19 15:53:56
*/
function setTableGameAttr($data=[], $table='')
{   
    
    $operateRes = -1;
    $table = 'tab_game_attr';
    
    try{
        $game_attr_id = Db::table($table)->where('game_id',$data['game_id'])->value('id');
        if($game_attr_id > 0){
            // 更新
            $a = Db::table($table)->where('game_id',$data['game_id'])->update($data);
        }else{
            // 插入
            $gameAttrData['promote_declare'] = $data['promote_declare']?:'';
            $gameAttrData['cp_ratio'] = $data['cp_ratio']?:0;
            $gameAttrData['is_control'] = $data['is_control']?:0;
            $gameAttrData['cp_pay_ratio'] = $data['cp_pay_ratio']?:0;
            $gameAttrData['support_url'] = $data['support_url']?:0;
            $gameAttrData['promote_level_limit'] = $data['promote_level_limit']?:0;
            $gameAttrData['discount_show_status'] = $data['discount_show_status']?:1;
            $gameAttrData['coupon_show_status'] = $data['coupon_show_status']?:1;
            $gameAttrData['game_id'] = $data['game_id'];
            $a = Db::table($table)->insert($gameAttrData);
        }
        if($a){
            $operateRes = 1;
        }

    } catch (\Exception $e) {
        $operateRes = -1;
    }
    return $operateRes;
}



