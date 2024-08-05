<?php
//获取渠道推广用户游戏消费总流水 yyh
function get_promote_user_pay($id = 0)
{
    if (!$id) return 'error';
    $map['promote_id'] = $id;
    $map['puid'] = array('eq',0);
    $uids = think\Db::table("tab_user")->field('cumulative')->where($map)->select()->toarray();
    $data = array_column($uids, 'cumulative');
    $total = array_sum($data);
    return null_to_0($total);
}

/**
 * 根据渠道ID获取该渠道注册用户
 *
 * @param int $id
 * @return int|string
 * @author: Juncl
 * @time: 2021/09/22 14:40
 */
function get_promote_user($id=0)
{
    if (!$id) return '--';
    $map['promote_id'] = $id;
    $uids = think\Db::table("tab_user")->where($map)->count();
    return $uids;
}

/**
 * 获取渠道推广等级
 *
 * @param int $id
 * @return string
 * @author: Juncl
 * @time: 2021/09/22 14:45
 */
function get_promote_level_name($id=0)
{
    if(!$id) return '--';
    $promoteLevel = think\Db::table("tab_promote_level")->where('promote_id',$id)->value('promote_level');
    if(!$promoteLevel) return '--';
    $levels = cmf_get_option('promote_level_set');
    if(!$levels) return '--';
    $data = '--';
    foreach ($levels as $key => $val)
    {
        if($promoteLevel == $val['level'])
        {
            $data = $val['level_name'];
            break;
        }
    }
    return $data;
}