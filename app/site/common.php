<?php


/**
 * @函数或方法说明
 * @显示站点
 * @param int $webiste
 *
 * @author: 郭家屯
 * @since: 2019/6/24 10:25
 */
function get_website($webiste = 0)
{
    switch ($webiste) {
        case 0 :
            return '不显示';
        case 1 :
            return 'PC官网';
        case 2 :
            return 'WAP站';
        case 3 :
            return 'PC官网/WAP站';
        case 4 :
            return '游戏盒子';
        case 5 :
            return 'PC官网/游戏盒子';
        case 6 :
            return 'WAP站/游戏盒子';
        case 7 :
            return 'PC官网/WAP站/游戏盒子';
        case 8 :
            return '推广平台';
        case 9 :
            return 'PC官网/WAP站/SDK/APP';
        case 10 :
            return 'SDK';
        case 16 :
            return '联运分发平台';
        case 17 :
            return '管理后台';
        default:
            return '未知站点';
    }
}


function language_list()
{
    return [
        0 => '中文',
        1 => '英文',
        2 => '中文繁体',
        3 => '日文',
        4 => '韩文'
    ];
}

function get_language($type=0)
{
    $list = language_list();

    return $list[$type];
}

// 获取管理员的所有角色
function get_role_list($map = [], $field = '*',$order='id desc')
{
    $list = think\Db::table('sys_role')->field($field)->where($map)->where(['status'=>1])->order($order)->select()->toArray();
    if (empty($list)) {
        return '';
    }
    return $list;
}
