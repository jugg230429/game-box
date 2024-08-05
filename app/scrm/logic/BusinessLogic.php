<?php

namespace app\scrm\logic;

use app\business\model\PromoteBusinessModel;
use app\datareport\event\PromoteController;
use app\promote\model\PromoteModel;
use app\scrm\lib\Time;

class BusinessLogic extends BaseLogic
{


    /**
     * @获取商务专员数据
     *
     * @author: zsl
     * @since: 2021/8/5 10:27
     */
    public function lists($param)
    {
        try {
            //验证参数
            $page = empty($param['page']) ? '1' : $param['page'];
            $limit = empty($param['limit']) ? '10' : $param['limit'];
            $mBusiness = new PromoteBusinessModel();
            $field = "id as busier_id,account as busier_account,mobile_phone as mobile,real_name,status,promote_ids";
            $where = [];
            if (!empty($param['busier_id'])) {
                $where['id'] = $param['busier_id'];
            }
            $lists = $mBusiness -> field($field) -> where($where) -> paginate($limit, false, ['page' => $page]);
            if (empty($lists)) {
                $this -> data = $lists;
                return true;
            }
            foreach ($lists as &$v) {
                if ($v['status'] == '-1') {
                    $v['status'] = 0;
                }
            }
            $this -> data = $lists;
            return true;
        } catch (\Exception $e) {
            $this -> errorMsg = $e -> getMessage();
            return false;
        }
    }


    /**
     * @获取商务专员业绩完成度
     *
     * @author: zsl
     * @since: 2021/8/6 15:04
     */
    public function performance($param)
    {
        try {
            $year = $param['year'];
            $business_ids = $param['business_ids'];
            if (empty($year) || empty($business_ids)) {
                $this -> errorMsg = '参数错误';
                return false;
            }
            $monthArr = Time ::everyMonth($year);
            $mPromote = new PromoteModel();
            $ePromote = new PromoteController();
            $data = [];
            foreach ($business_ids as $k => $business_id) {
                //获取商务专员下推广员id
                $promoteIds = $mPromote -> where(['busier_id' => $business_id]) -> column('id');
                $data[$k]['business_id'] = $business_id;
                $data[$k]['year'] = $year;
                if (!empty($promoteIds)) {
                    //获取商务专员每月业绩
                    foreach ($monthArr as $month => $timestamp) {
                        $res = $ePromote -> promote_data(date("Y-m-d", $timestamp['start']), date("Y-m-d", $timestamp['end']), $promoteIds);
                        $data[$k]['achievement_list'][] = $res['total_data']['total_pay'];
                        $data[$k]['invited_list'][] = $res['total_data']['new_register_user'];
                    }
                } else {
                    foreach ($monthArr as $month => $timestamp) {
                        $data[$k]['achievement_list'][] = 0.00;
                        $data[$k]['invited_list'][] = 0;
                    }
                }
            }
            $this -> data = $data;
            return true;
        } catch (\Exception $e) {
            $this -> errorMsg = $e -> getMessage();
            return false;
        }
    }

    /**
     * @获取商务专员业绩完成度
     *
     * @author: zsl
     * @since: 2021/8/6 15:04
     */
    public function achievement($param)
    {
        try {
            $business_ids = $param['business_ids'];
            if (empty($business_ids)) {
                $this -> errorMsg = '参数错误';
                return false;
            }
            $monthArr = Time ::month(date('m'));//本月时间戳
            $todayArr = ['start'=>strtotime(date('Y-m-d')),'end'=>strtotime(date('Y-m-d'))+86399];//今日时间戳
            $mPromote = new PromoteModel();
            $ePromote = new PromoteController();
            $data = [];
            foreach ($business_ids as $k => $business_id) {
                //获取商务专员下推广员id
                $promoteIds = $mPromote -> where(['busier_id' => $business_id]) -> column('id');
                $data[$k]['busier_id'] = $business_id;
                $data[$k]['busier_account'] = get_business_entity($business_id)['account'];
                if (!empty($promoteIds)) {
                    //获取商务专员所有业绩
                    $total_data = $ePromote -> promote_data(0, date("Y-m-d"), $promoteIds);
                    //获取商务专员本月业绩
                    $month_data = $ePromote -> promote_data(date("Y-m-d", $monthArr['start']), date("Y-m-d",
                        $monthArr['end']), $promoteIds);
                    //获取商务专员本日业绩
                    $today_data = $ePromote -> promote_data(date("Y-m-d", $todayArr['start']), date("Y-m-d",
                        $todayArr['end']), $promoteIds);
                    $data[$k]['total_achievement'] = $total_data['total_data']['total_pay'];
                    $data[$k]['total_invited'] = $total_data['total_data']['new_register_user'];
                    $data[$k]['month_achievement'] = $total_data['total_data']['total_pay'];
                    $data[$k]['month_invited'] = $total_data['total_data']['new_register_user'];
                    $data[$k]['today_achievement'] = $total_data['total_data']['total_pay'];
                    $data[$k]['today_invited'] = $total_data['total_data']['new_register_user'];
                } else {
                    $data[$k]['total_achievement'] = 0.00;
                    $data[$k]['total_invited'] = 0;
                    $data[$k]['month_achievement'] = 0.00;
                    $data[$k]['month_invited'] = 0;
                    $data[$k]['today_achievement'] = 0.00;
                    $data[$k]['today_invited'] = 0;
                }
            }
            $this -> data = $data;
            return true;
        } catch (\Exception $e) {
            $this -> errorMsg = $e -> getMessage();
            return false;
        }
    }

}
