<?php

namespace app\issue\logic;

use app\issue\model\SpendModel;
use app\issue\model\UserDayLoginModel;
use app\issue\model\UserModel;
use think\helper\Time;
use cmf\paginator\Bootstrap;

class DataLogic
{


    /**
     * @数据总览
     *
     * @author: zsl
     * @since: 2021/8/14 13:55
     */
    public function overview($param)
    {
        if(empty($param['days_num'])){
            $param['days_num'] = 14;
        }

        //默认获取15天数据
        $dateList = get_date_list(Time ::daysAgo($param['days_num']), time(), 1);
        $overviewData = [];
        $overviewDataLists = [];
        $todayData = [];
        $yesterdayData  = [];
        foreach ($dateList as $date) {
            $start = strtotime($date);
            $end = strtotime($date) + 86400 - 1;
            //新增用户
            $overviewData[$date]['new_user'] = $this -> newUserCount($start, $end);
            $overviewDataLists['new_user'][] = $overviewData[$date]['new_user'];
            //新增设备
            $overviewData[$date]['new_equipment'] = $this -> newEquipmentCount($start, $end);
            $overviewDataLists['new_equipment'][] = $overviewData[$date]['new_equipment'];
            //活跃用户
            $overviewData[$date]['active_user'] = $this -> activeUserCount($start, $end);
            $overviewDataLists['active_user'][] = $overviewData[$date]['active_user'];
            //付费用户
            $overviewData[$date]['pay_user'] = $this -> payUserCount($start, $end);
            $overviewDataLists['pay_user'][] = $overviewData[$date]['pay_user'];
            //付费额
            $overviewData[$date]['pay_amount'] = $this -> payAmount($start, $end);
            $overviewDataLists['pay_amount'][] = $overviewData[$date]['pay_amount'];
            //ARPPU
            if (empty($overviewData[$date]['pay_amount']) || empty($overviewData[$date]['pay_user'])) {
                $overviewData[$date]['arppu'] = 0.00;
            } else {
                $overviewData[$date]['arppu'] = round($overviewData[$date]['pay_amount'] / $overviewData[$date]['pay_user'], 2);
            }
            $overviewDataLists['arppu'][] = $overviewData[$date]['arppu'];
            //ARPU
            if (empty($overviewData[$date]['pay_amount']) || empty($overviewData[$date]['active_user'])) {
                $overviewData[$date]['arpu'] = 0.00;
            } else {
                $overviewData[$date]['arpu'] = round($overviewData[$date]['pay_amount'] / $overviewData[$date]['active_user'], 2);
            }
            $overviewDataLists['arpu'][] = $overviewData[$date]['arpu'];

            //统计其他数据
            switch ($date){
                case date('Y-m-d'):
                    //今日数据
                    $todayData['new_user'] = $overviewData[$date]['new_user'];
                    $todayData['new_equipment'] = $overviewData[$date]['new_equipment'];
                    $todayData['active_user'] = $overviewData[$date]['active_user'];
                    $todayData['pay_user'] = $overviewData[$date]['pay_user'];
                    $todayData['pay_amount'] = $overviewData[$date]['pay_amount'];
                    $todayData['arppu'] = $overviewData[$date]['arppu'];
                    $todayData['arpu'] = $overviewData[$date]['arpu'];
                    break;
                case date('Y-m-d',strtotime('-1 day')):
                    //昨日数据
                    $yesterdayData['new_user'] = $overviewData[$date]['new_user'];
                    $yesterdayData['new_equipment'] = $overviewData[$date]['new_equipment'];
                    $yesterdayData['active_user'] = $overviewData[$date]['active_user'];
                    $yesterdayData['pay_user'] = $overviewData[$date]['pay_user'];
                    $yesterdayData['pay_amount'] = $overviewData[$date]['pay_amount'];
                    $yesterdayData['arppu'] = $overviewData[$date]['arppu'];
                    $yesterdayData['arpu'] = $overviewData[$date]['arpu'];
                    break;
                default:
                    break;
            }
        }
        $total = [];
        $total['new_user'] = $this -> newUserCount();
        $total['new_equipment'] = $this -> newEquipmentCount();
        $total['active_user'] = $this -> activeUserCount();
        $total['pay_user'] = $this -> payUserCount();
        $total['pay_amount'] = $this -> payAmount();
        if (empty($total['pay_amount']) || empty($total['pay_user'])) {
            $total['arppu'] = 0.00;
        } else {
            $total['arppu'] = round($total['pay_amount'] / $total['pay_user'], 2);
        }
        if (empty($total['pay_amount']) || empty($total['active_user'])) {
            $total['arpu'] = 0.00;
        } else {
            $total['arpu'] = round($total['pay_amount'] / $total['active_user'], 2);
        }
        $data = [];
        $data['dateList'] = json_encode($dateList);
        $data['overview_data'] = $overviewData;
        $data['total'] = $total;
        $data['overview_data_lists'] = json_encode($overviewDataLists);
        $data['todayData'] = $todayData;
        $data['yesterdayData'] = $yesterdayData;
        return $data;

    }


    /**
     * @日报数据
     *
     * @author: zsl
     * @since: 2021/8/16 9:25
     */
    public function daily($param, $is_export = false)
    {
        //获取要查询的时间
        list($start_time, $end_time) = array_map('trim', explode('~', $param['datetime']));
        $start_time = empty($start_time) ? date("Y-m-d", Time ::daysAgo(9)) : $start_time;
        $end_time = empty($end_time) ? date("Y-m-d") : $end_time;
        $dateList = Time ::getDates($start_time, $end_time);
        $dateList = array_reverse($dateList);
        //查询统计数据
        $lists = $this -> _getDailyData($dateList, $param);
        if ($is_export) {
            return $lists;
        }
        $page = $this -> _getPage($dateList, $lists, $param);
        //获取汇总数据
        $totalTime = [$start_time];
        $total = $this -> _getDailyData($totalTime, $param, (strtotime($end_time) - strtotime($start_time) + 86399));
        $result = [
                'lists' => $lists,
                'total' => $total[$start_time],
                'page' => $page,
        ];
        return $result;
    }


    /**
     * @日报数据(时)
     *
     * @author: zsl
     * @since: 2021/8/16 20:24
     */
    public function dailyHour($param, $is_export=false)
    {
        $param['start_time'] = empty($param['start_time']) ? date("Y-m-d") : $param['start_time'];
        $hourList = Time ::getHours($param['start_time']);
        $hourList = array_reverse($hourList);
        //查询统计数据
        $lists = $this -> _getDailyData($hourList, $param, (3600 - 1));
        if ($is_export) {
            return $lists;
        }
        $page = $this -> _getPage($hourList, $lists, $param);
        //获取汇总数据
        $totalTime = [$param['start_time']];
        $total = $this -> _getDailyData($totalTime, $param);
        $result = [
                'lists' => $lists,
                'page' => $page,
                'total' => $total[$param['start_time']],
        ];
        return $result;
    }


    /**
     * @获取统计数据
     *
     * @param $dateList
     * @param $param
     * @param int $offset
     *
     * @return array
     *
     * @author: zsl
     * @since: 2021/8/16 21:25
     */
    private function _getDailyData($dateList, $param, $offset = 86399)
    {
        $lists = [];
        foreach ($dateList as $date) {

            $start = strtotime($date);
            $end = strtotime($date) + $offset;
            //新增用户
            $lists[$date]['new_user'] = $this -> newUserCount($start, $end, $param);
            //新增设备
            $lists[$date]['new_equipment'] = $this -> newEquipmentCount($start, $end, $param);
            //注册转化率
            if (empty($lists[$date]['new_user']) || empty($lists[$date]['new_equipment'])) {
                $lists[$date]['CR'] = '0%';
            } else {
                $lists[$date]['CR'] = (round($lists[$date]['new_user'] / $lists[$date]['new_equipment'], 2) * 100) . '%';
            }
            //活跃用户
            $lists[$date]['active_user'] = $this -> activeUserCount($start, $end, $param);
            //活跃设备
            $lists[$date]['active_equipment'] = $this -> activeEquipmentCount($start, $end, $param);
            //付费用户
            $lists[$date]['pay_user'] = $this -> payUserCount($start, $end, $param);
            //付费总额
            $lists[$date]['pay_amount'] = $this -> payAmount($start, $end, $param);
            //订单数
            $lists[$date]['order_count'] = $this -> orderCount($start, $end, $param);
            //付费率
            if (empty($lists[$date]['pay_user']) || empty($lists[$date]['active_user'])) {
                $lists[$date]['pay_rate'] = '0%';
            } else {
                $lists[$date]['pay_rate'] = (round($lists[$date]['pay_user'] / $lists[$date]['active_user'], 2) * 100) . '%';
            }
            if (empty($lists[$date]['new_user'])) {
                //新增付费用户
                $lists[$date]['new_pay_user_count'] = 0;
                //新增付费额
                $lists[$date]['new_pay_amount'] = 0;
                //新增付费率
                $lists[$date]['new_pay_rate'] = '0%';
            } else {
                //新增付费用户
                $lists[$date]['new_pay_user_count'] = $this -> newPayUserCount($start, $end, $param);
                //新增付费额
                $lists[$date]['new_pay_amount'] = $this -> newPayAmount($start, $end, $param);
                //新增付费率
                if (empty($lists[$date]['new_user']) || empty($lists[$date]['new_pay_user_count'])) {
                    $lists[$date]['new_pay_rate'] = '0%';
                } else {
                    $lists[$date]['new_pay_rate'] = (round($lists[$date]['new_pay_user_count'] / $lists[$date]['new_user'], 2) * 100) . '%';
                }
            }
            //新增ARPU
            if (empty($lists[$date]['new_pay_amount']) || empty($lists[$date]['new_user'])) {
                $lists[$date]['new_arpu'] = 0;
            } else {
                $lists[$date]['new_arpu'] = round($lists[$date]['new_pay_amount'] / $lists[$date]['new_user'], 2);
            }
            //活跃ARPU
            if (empty($lists[$date]['pay_amount']) || empty($lists[$date]['active_user'])) {
                $lists[$date]['active_arpu'] = 0;
            } else {
                $lists[$date]['active_arpu'] = round($lists[$date]['pay_amount'] / $lists[$date]['active_user'], 2);
            }
            //付费ARPU
            if (empty($lists[$date]['pay_amount']) || empty($lists[$date]['pay_user'])) {
                $lists[$date]['pay_arpu'] = 0;
            } else {
                $lists[$date]['pay_arpu'] = round($lists[$date]['pay_amount'] / $lists[$date]['pay_user'], 2);
            }
            //新增付费ARPU
            if (empty($lists[$date]['new_pay_amount']) || empty($lists[$date]['new_pay_user_count'])) {
                $lists[$date]['new_pay_arpu'] = 0;
            } else {
                $lists[$date]['new_pay_arpu'] = round($lists[$date]['new_pay_amount'] / $lists[$date]['new_pay_user_count'], 2);
            }
        }
        return $lists;
    }


    /**
     * @数据分页
     *
     * @author: zsl
     * @since: 2021/8/16 21:26
     */
    private function _getPage($dateList, &$lists, $param)
    {
        $page = empty($param['page']) ? 1 : $param['page'];
        $row = empty($param['row']) ? 10 : $param['row'];
        $lists = array_slice($lists, $row * ($page - 1), $row);
        $pageBootstrap = new Bootstrap($lists, $row, $page, count($dateList), false, ['query' => $param, 'path' => url('')]);
        $page = $pageBootstrap -> render();
        return $page;
    }


    /**
     * @新增用户
     *
     * @author: zsl
     * @since: 2021/8/14 13:58
     */
    public function newUserCount($start = 0, $end = 0, $param = [])
    {

        $mUser = new UserModel();
        $where = [];
        if (!empty($start) && !empty($end)) {
            $where['create_time'] = ['between', [$start, $end]];
        }
        if (!empty($param['open_user_id'])) {
            $where['open_user_id'] = $param['open_user_id'];
        }
        if (!empty($param['platform_id'])) {
            $where['platform_id'] = $param['platform_id'];
        }
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        $newUserCount = $mUser -> where($where) -> count();
        return $newUserCount;

    }

    /**
     * @新增设备数
     *
     * @author: zsl
     * @since: 2021/8/14 14:30
     */
    public function newEquipmentCount($start = 0, $end = 0, $param = [])
    {
        $mUser = new UserModel();
        $where = [];
        if (!empty($start) && !empty($end)) {
            $where['create_time'] = ['between', [$start, $end]];
        }
        if (!empty($param['open_user_id'])) {
            $where['open_user_id'] = $param['open_user_id'];
        }
        if (!empty($param['platform_id'])) {
            $where['platform_id'] = $param['platform_id'];
        }
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        $where['equipment_num'] = ['neq', ''];
        $newEquipmentCount = $mUser -> where($where) -> group('equipment_num') -> count();
        return $newEquipmentCount;
    }

    /**
     * @活跃用户数
     *
     * @author: zsl
     * @since: 2021/8/14 14:34
     */
    public function activeUserCount($start = 0, $end = 0, $param = [])
    {
        $mUserDayLogin = new UserDayLoginModel();
        $where = [];
        if (!empty($start) && !empty($end)) {
            $where['login_time'] = ['between', [$start, $end]];
        }
        if (!empty($param['open_user_id'])) {
            $where['open_user_id'] = $param['open_user_id'];
        }
        if (!empty($param['platform_id'])) {
            $where['platform_id'] = $param['platform_id'];
        }
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        $activeUserCount = $mUserDayLogin -> where($where) -> group('user_id') -> count();
        return $activeUserCount;
    }

    /**
     * @活跃设备数
     *
     * @author: zsl
     * @since: 2021/8/16 10:56
     */
    public function activeEquipmentCount($start = 0, $end = 0, $param = [])
    {
        $mUserDayLogin = new UserDayLoginModel();
        $where = [];
        if (!empty($start) && !empty($end)) {
            $where['login_time'] = ['between', [$start, $end]];
        }
        if (!empty($param['open_user_id'])) {
            $where['open_user_id'] = $param['open_user_id'];
        }
        if (!empty($param['platform_id'])) {
            $where['platform_id'] = $param['platform_id'];
        }
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        $activeEquipmentCount = $mUserDayLogin -> where($where) -> group('equipment_num') -> count();
        return $activeEquipmentCount;
    }

    /**
     * @付费用户数
     *
     * @author: zsl
     * @since: 2021/8/14 14:39
     */
    public function payUserCount($start = 0, $end = 0, $param = [])
    {

        $mSpend = new SpendModel();
        $where = [];
        if (!empty($start) && !empty($end)) {
            $where['pay_time'] = ['between', [$start, $end]];
        }
        if (!empty($param['open_user_id'])) {
            $where['open_user_id'] = $param['open_user_id'];
        }
        if (!empty($param['platform_id'])) {
            $where['platform_id'] = $param['platform_id'];
        }
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        $where['pay_status'] = 1;
        $payUserCount = $mSpend -> where($where) -> group('user_id') -> count();
        return $payUserCount;
    }

    /**
     * @新增付费用户数
     *
     * @author: zsl
     * @since: 2021/8/16 11:23
     */
    public function newPayUserCount($start = 0, $end = 0, $param = [])
    {
        $mUser = new UserModel();
        $where = [];
        if (!empty($start) && !empty($end)) {
            $where['create_time'] = ['between', [$start, $end]];
        }
        if (!empty($param['open_user_id'])) {
            $where['open_user_id'] = $param['open_user_id'];
        }
        if (!empty($param['platform_id'])) {
            $where['platform_id'] = $param['platform_id'];
        }
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        $newUserIds = $mUser -> where($where) -> column('id');
        if (empty($newUserIds)) {
            return 0;
        }
        $mSpend = new SpendModel();
        $where = [];
        if (!empty($start) && !empty($end)) {
            $where['pay_time'] = ['between', [$start, $end]];
        }
        $where['user_id'] = ['in', $newUserIds];
        $where['pay_status'] = 1;
        $payUserCount = $mSpend -> where($where) -> group('user_id') -> count();
        return $payUserCount;
    }


    /**
     * @付费额
     *
     * @author: zsl
     * @since: 2021/8/14 15:13
     */
    public function payAmount($start = 0, $end = 0, $param = [])
    {
        $mSpend = new SpendModel();
        $where = [];
        if (!empty($start) && !empty($end)) {
            $where['pay_time'] = ['between', [$start, $end]];
        }
        if (!empty($param['open_user_id'])) {
            $where['open_user_id'] = $param['open_user_id'];
        }
        if (!empty($param['platform_id'])) {
            $where['platform_id'] = $param['platform_id'];
        }
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        $where['pay_status'] = 1;
        $payAmount = $mSpend -> where($where) -> sum('pay_amount');
        return $payAmount;
    }


    /**
     * @新增付费额
     *
     * @author: zsl
     * @since: 2021/8/16 13:51
     */
    public function newPayAmount($start = 0, $end = 0, $param = [])
    {
        $mUser = new UserModel();
        $where = [];
        if (!empty($start) && !empty($end)) {
            $where['create_time'] = ['between', [$start, $end]];
        }
        if (!empty($param['open_user_id'])) {
            $where['open_user_id'] = $param['open_user_id'];
        }
        if (!empty($param['platform_id'])) {
            $where['platform_id'] = $param['platform_id'];
        }
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        $newUserIds = $mUser -> where($where) -> column('id');
        if (empty($newUserIds)) {
            return 0;
        }
        $mSpend = new SpendModel();
        $where = [];
        if (!empty($start) && !empty($end)) {
            $where['pay_time'] = ['between', [$start, $end]];
        }
        $where['user_id'] = ['in', $newUserIds];
        $where['pay_status'] = 1;
        $payAmount = $mSpend -> where($where) -> sum('pay_amount');
        return $payAmount;
    }

    /**
     * @订单数
     *
     * @author: zsl
     * @since: 2021/8/16 11:07
     */
    public function orderCount($start = 0, $end = 0, $param = [])
    {
        $mSpend = new SpendModel();
        $where = [];
        if (!empty($start) && !empty($end)) {
            $where['pay_time'] = ['between', [$start, $end]];
        }
        if (!empty($param['open_user_id'])) {
            $where['open_user_id'] = $param['open_user_id'];
        }
        if (!empty($param['platform_id'])) {
            $where['platform_id'] = $param['platform_id'];
        }
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        $where['pay_status'] = 1;
        $orderCount = $mSpend -> where($where) -> count();
        return $orderCount;
    }


}
