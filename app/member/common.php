<?php
/**
 * 获取用户阶段列表
 * member/common.php
 * created by wjd 2021-7-26 15:43:34
*/
function get_user_stage()
{
    $map = [];
    $field = '*';
    $list = think\Db::table('tab_user_stage')->field($field)->where($map)->select()->toArray();
    if (empty($list)) {
        return '';
    }
    return $list;
}
