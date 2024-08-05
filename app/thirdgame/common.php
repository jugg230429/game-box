<?php
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
 * 获取第一个游戏分类 没有则为0
 *
 * @return array
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 * @author: Juncl
 * @time: 2021/08/20 16:46
 */
function get_first_type()
{
    $game_type = ['id'=>0,'type_name'=>''];
    $data = think\Db::table("tab_game_type")->field('id,type_name')->order('id asc')->find();
    if($data['id'] > 0){
        $game_type['id'] = $data['id'];
        $game_type['type_name'] = $data['type_name'];
    }
    return $game_type;
}

/**
 * 生成随机游戏APPID
 *
 * @param string $str_key
 * @return string
 * @author: Juncl
 * @time: 2021/08/25 21:40
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
 * 根据游戏名称获取游戏详情
 *
 * @param string $name
 * @return bool
 * @author: Juncl
 * @time: 2021/08/25 21:40
 */
function get_game_by_name($name='')
{
    if(empty($name)){
        return false;
    }
    $data = think\Db::table("tab_game")->where('game_name',$name)->value('id');
    if($data > 0){
        return false;
    }else{
        return true;
    }
}

