<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/26
 * Time: 19:13
 */

namespace app\datareport\controller;

use app\common\controller\BaseController as Base;
use cmf\org\RedisSDK\RedisController as Redis;
use app\datareport\event\DatasqlsummaryController as Sqlsummary;
use app\datareport\event\PromoteController as Promote;
use think\Db;

class PromoteController extends Base
{
    //数据报表 基础数据
    public function promote_data()
    {
        $request = $this->request->param();
        //时间
        if (empty($request['datetime'])) {
            $date = date("Y-m-d", strtotime("-6 day")) . '~' . date("Y-m-d");
        } else {
            $date = $request['datetime'];
        }
        $dateexp = explode('~', $date);
        $starttime = $dateexp[0];
        $endtime = $dateexp[1];
        $this->assign('start', $starttime);
        $this->assign('end', $endtime);
        $promote_id = $this->request->param('promote_id', '');
        $game_id = $this->request->param('game_id', '');
        $promoteevent = new Promote();
        $new_data = $promoteevent->promote_base($starttime, $endtime, $promote_id, $game_id);
        //排序
        $new_data = parent::array_order($new_data, $request['sort_type']?:'total_pay', $request['sort']);
        parent::array_page($new_data, $request);
        return $this->fetch();
    }

    //渠道排行
    public function promote_rank()
    {
        $request = $this->request->param();
        $lastday = $request['type'] == 0 ? 0 : $request['type']-1;
        $starttime = date("Y-m-d", strtotime("-$lastday day"));
        $endtime = date("Y-m-d");
        $promoteevent = new Promote();
        $new_data = $promoteevent->promote_base($starttime, $endtime);
        $data['register_rank'] = array_slice(parent::array_order($new_data, 'count_new_register_user', 3), 0, 15);
        $data['active_user'] = array_slice(parent::array_order($new_data, 'count_active_user', 3), 0, 15);
        $data['total_pay'] = array_slice(parent::array_order($new_data, 'total_pay', 3), 0, 15);
        $this->assign('data', $data);
        return $this->fetch();
    }
}