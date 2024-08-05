<?php
/**
 * [去除数组汇总状态不为1的键]
 * @param $status
 * @param $param
 * @param array $array
 * @return array
 */
function array_status2value($status, $param, $array = array())
{
    foreach ($array as $key => $value) {
        if ($value[$status] != 1) {
            unset($array[$key]);
        }
    }
    return $array;
}
