<?php

/**
 * 获取第三方平台数据
 *
 * @param int $id
 * @param string $field
 * @author: Juncl
 * @time: 2021/08/14 9:40
 */
function get_platform_entity($map=[], $field = '*')
{
    $model = think\Db::table('tab_web_platform');
    $data = $model->field($field)->where($map)->find();
    if (empty($data)) {
        return false;
    }
    return $data;
}
