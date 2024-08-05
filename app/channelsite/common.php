<?php

//金额换位
function huanwei($total)
{
    $total = empty($total) ? '0' : trim($total . ' ');
    $zhengshu = round($total);
    $len = strlen($zhengshu);
    if ($len > 8) {
        // 亿
        $len = $len - 12;
        $total = $len > 0 ? (round(($total / 1e12), 4) . '万亿') : round(($total / 1e8), 4) . '亿';
    } else if ($len > 4) {
        // 万
        $total = (round(($total / 10000), 4)) . '万';
    } else {
        $total = round($total, 4);
    }
    return $total;
}

/**
 * 获取原包信息
 * @return [type] [description]
 * yyh
 */
function get_game_source_info($id = 0)
{
    $map["game_id"] = $id;
    $data = \think\Db::table('tab_game_source')->where($map)->find();
    if (empty($data)) {
        $result = [];
    } else {
        $result = $data;
    }
    return $result;

}

/**
 * 获取原包信息
 * @return [type] [description]
 * yyh
 */
function get_game_apply_info($game_id = 0, $promote_id = 0)
{
    $map["game_id"] = $game_id;
    $map["promote_id"] = $promote_id;
    $data = \think\Db::table('tab_promote_apply')->where($map)->find();
    if (empty($data)) {
        $result = [];
    } else {
        $result = $data;
    }
    return $result;

}

//处理平台币收入和支出
function deposit_record($data='',$type=1){
    foreach ($data as $k=>$v){
        if($type==1&&in_array($v['type'],[1,2,4])){
            $res[] = $v['pay_amount'];
        }
        if($type==2&&in_array($v['type'],[3,5])){
            $res[] = $v['pay_amount'];
        }
    }
    return array_sum($res);
}