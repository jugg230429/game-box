<?php

/**
 * 根据管理员id获取角色名称
 * @param $id  管理员id
 * @return string 管理员名称
 */
function get_role_name($id)
{

    if ($id == '1') {
        $role_name = '超级管理员';
    } else {
        $role_id = db('role_user')->where(['user_id' => $id])->value('role_id');
        $role_name = db('role')->where(['id' => $role_id])->value('name');
    }

    return $role_name ? $role_name : '';
}

/**
 * 根据角色id获取角色名称
 * @param $id
 */
function get_role_name_by_id($id)
{

    $role_name = db('role')->where(['id' => $id])->value('name');

    return $role_name ? $role_name : '';
}
