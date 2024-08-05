<?php

namespace app\site\model;

use think\Model;

class AppSupersignOrderModel extends Model
{

    protected $table = 'tab_app_supersign_order';

    protected $autoWriteTimestamp = true;


    /**
     * @超级签付费下载记录后台列表
     *
     * @since: 2021/7/12 15:42
     * @author: zsl
     */
    public function adminLists($param)
    {
        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        $where = [];
        $start_time = $param['start_time'];
        $end_time = $param['end_time'];
        if ($start_time && $end_time) {
            $where['pay_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $where['pay_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $where['pay_time'] = ['egt', strtotime($start_time)];
        }
        if (!empty($param['pay_way'])) {
            $where['pay_way'] = $param['pay_way'];
        }
        if (isset($param['pay_status']) && ($param['pay_status'] === '0' || $param['pay_status'] === '1')) {
            $where['pay_status'] = $param['pay_status'];
        }
        $lists = $this -> where($where) -> order('create_time desc,id desc') -> paginate($row, false, ['query' => $param]);
        return $lists;
    }


}
