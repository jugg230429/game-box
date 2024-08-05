<?php
/**
 * Created by gjt.
 * User: Administrator
 * Date: 2019/1/23
 * Time: 15:34
 */

namespace app\business\controller;

use app\common\controller\BaseHomeController;
use app\promote\model\PromoteModel;
use app\promote\model\PromotewithdrawModel;
use app\promote\model\PromotesettlementModel;
use app\recharge\model\SpendModel;
use app\member\model\UserModel;
use app\datareport\event\PromoteController as Promote;
use think\Request;
use think\Db;


class ExportController extends BaseController
{
    /**
     * @函数或方法说明
     * @导出方法
     * @author: 郭家屯
     * @since: 2019/3/29 9:35
     */
    function expUser()
    {
        $id = $this->request->param('id', 0, 'intval');
        $pid = BID;
        $base = new BaseHomeController();
        if (!is_numeric($pid)) {
            $this->error('请登录商务平台');
        }
        $param = $this->request->param();
        switch ($id) {
            case 1://数据汇总
                $xlsCell = array(
                    array('promote_id', '渠道ID'),
                    array('promote_account', "渠道帐号"),
                    array('count_new_register_user', "新增用户"),
                    array('count_active_user', "活跃用户"),
                    array('count_pay_user', "付费用户"),
                    array('count_register_ip', "新增注册IP"),
                    array('count_new_pay_user', "新增付费用户"),
                    array('total_pay', "总付费额"),
                    array('rate', "总付费率")
                );
                //时间
                $start_time = $this->request->param('begtime', '');
                $end_time = $this->request->param('endtime', '');
                if ($start_time && $end_time) {
                    $date = $start_time. '至' . $end_time;
                } elseif ($end_time) {
                    $date = '2019-08-01' . '至' . $end_time;
                } elseif ($start_time) {
                    $date = $start_time. '至' . date("Y-m-d", strtotime("-1 day"));
                }else{
                    $date = '2019-08-01' . '至' . date("Y-m-d", strtotime("-1 day"));
                }
                $xlsName = '数据汇总_'.$date;
                $data_cache = unserialize(file_get_contents(dirname(dirname(__FILE__)).'/data/data_summary_'.BID));
                $data_cache = $data_cache?:[];
                $xlsData = $data_cache[0];
                $xlsData = parent::array_order($xlsData, $param['sort_type']?:'', $param['sort']);
                $total_data = $data_cache[1];
                $total_data['promote_id'] = '汇总';
                $total_data['promote_account'] = '--';
                $total_data['count_new_register_user'] = $total_data['new_register_user'];
                $total_data['count_active_user'] = $total_data['active_user'];
                $total_data['count_pay_user'] = $total_data['pay_user'];
                $total_data['count_register_ip'] = $total_data['count_register_ip'];
                $total_data['count_new_pay_user'] = $total_data['new_pay_user'];
                $total_data['total_pay'] = $total_data['total_pay'];
                $total_data['rate'] = $total_data['new_register_user']==0?'0.00':null_to_0($total_data['pay_user']/$total_data['new_register_user']*100).'%';
                $xlsData[] = $total_data;
                $xlsData = array_values($xlsData);
                break;
        }

        foreach ($xlsData as $key => $val) {
            foreach ($xlsCell as $k => $v) {
                if (isset($v[2])) {
                    $ar_k = array_search('*', $v);
                    if ($ar_k !== false) {
                        $v[$ar_k] = $val[$v[0]];
                    }
                    $fun = $v[2];
                    $param = $v;
                    unset($param[0], $param[1], $param[2]);
                    $xlsData[$key][$v[0]] = call_user_func_array($fun, $param);
                }
            }
        }
        $base->exportExcel($xlsName, $xlsCell, $xlsData);
    }

}
