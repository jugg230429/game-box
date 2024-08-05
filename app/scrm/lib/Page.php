<?php

namespace app\scrm\lib;

class Page
{

    /**
     * 数组分页函数  核心函数  array_slice
     * 用此函数之前要先将数据库里面的所有数据按一定的顺序查询出来存入数组中
     * $array   查询出来的所有数组
     * $limit   每页多少条数据，默认20条
     * $page   当前第几页，默认 第1页
     * order desc - 降序     asc - 升序
     * $field: 以哪个字段进行排序
     */
    public static function pageByArr($arr = [], $limit = 20, $page = 1, $order = 'asc', $field = "")
    {
        $page = (empty($page)) ? '1' : $page; #判断当前页面是否为空 如果为空就表示为第一页面
        $start = ($page - 1) * $limit; #计算每次分页的开始位置
        $order = strtoupper($order);
        if (empty($field)) {
            if ($order == 'DESC') {
                $arr = array_reverse($arr);
            }
        } else {
            $arr = self :: arraySortByField($arr, $field, $order);
        }
        $totals = count($arr);
        $countpage = ceil($totals / $limit); #计算总页面数
        $pagedata = [];
        $pagedata = array_slice($arr, $start, $limit);
        return $pagedata; #返回查询数据
    }


    /**
     * 对数组进行排序
     * $field: 以哪个字段进行排序
     * $order: 排序方式 asc/desc
     */
    public static function arraySortByField($arr = [], $field = "", $order = 'asc')
    {
        $order = strtoupper($order);
        if ('ASC' == $order) {
            array_multisort(array_column($arr, $field), SORT_ASC, $arr);
        }
        if ('DESC' == $order) {
            array_multisort(array_column($arr, $field), SORT_DESC, $arr);
        }
        return $arr;
    }

}
