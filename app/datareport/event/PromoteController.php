<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/26
 * Time: 19:13
 * sql查询
 */

namespace app\datareport\event;

use cmf\controller\HomeBaseController;
use cmf\org\RedisSDK\RedisController as Redis;
use app\datareport\event\DatasqlsummaryController as Sqlsummary;
use think\Db;

class PromoteController extends HomeBaseController
{
    public function promote_base($starttime = '', $endtime = '', $promote_id = '', $game_id = '')
    {
        $redis = Redis::getInstance(['host' => '127.0.0.1', 'port' => 6379], []);
        //搜索条件
        if ($promote_id > 0) {
            $promotemap['id'] = $promote_id;
        }else{
            $promotemap['parent_id'] = ['eq',0];//只显示一级
        }
        $promoteidss = $promoteids = Db::table('tab_promote')->where($promotemap)->order('id asc')->column('id,account');
        $datearr = Db::name('date_list')->where(['time' => ['between', [$starttime, $endtime]]])->order('time asc')->column('time');
        $first_data = Db::table('tab_datareport_every_pid')->field('time')->order('id asc')->find();
        //每天数据
        foreach ($datearr as $dk => $dv) {
//            if($dv == date('Y-m-d')){
//                $event = new DatabaseController();
//                $event->basedata_today();
//            }
            $key = 'datareporttoppid_' . $dv;
            $keyarr = $redis->hKeys($key);//得到该日期所有域值

            //redis数据丢失时 实时取sql重新写入
            if (empty($keyarr) && $dv >= $first_data['time'] && !empty($first_data['time'])) {
                $sqlsummary = new Sqlsummary();
                $sqlsummary->basedata_pid_everyday($dv);
                $keyarr = $redis->hKeys($key);//得到该日期所有域值
            }
            //搜索条件 移除不符合key
            if ($promote_id > 0 || $promote_id ===0 || $game_id > 0) {
                foreach ($keyarr as $kak => $kav) {
                    if ($promote_id > 0) {
                        if (strstr($kav, '_', true) != $promote_id) {
                            unset($keyarr[$kak]);
                        }
                    }
                    if ($promote_id === 0) {
                        if (strstr($kav, '_', true) === false || (int)strstr($kav, '_', true) !== $promote_id) {
                            unset($keyarr[$kak]);
                        }
                    }
                    if ($game_id > 0) {
                        if (ltrim(strstr($kav, '_', false), '_') != $game_id) {
                            unset($keyarr[$kak]);
                        }
                    }
                }
            }
            $keystr = implode(',', $keyarr);
            $field = empty($keystr) ? '' : $keystr;
            $redisdataarr[] = array_values($redis->hMget($key, $field));
        }
        $redisdata = array_reduce($redisdataarr, function ($result, $item = []) {
            if (empty($item)) {
                $item = [];
            }
            $result = array_merge($result, $item);
            return $result;
        }, []);
        array_map(function ($item) use (& $dataarr) {
            $dataarr[] = json_decode($item, true);
        }, $redisdata);
        $_data = [];
        foreach ($dataarr as $v) {
            $_data[$v['promote_id']][] = $v;//相同id放到一起
        }
        ksort($_data);//根据推id排序
        unset($_data[0]);//去除官方数据
        foreach ($_data as $_k => $_v) {
            unset($promoteids[$_k]);
        }
        $mergedata = $_data + $promoteids;
        foreach ($mergedata as $mk => $mv) {
            $data[$mk] = $mv;
            if (!is_array($mv)) {
                $data[$mk] = [];
            }
        }
        $array_keys = ['new_register_user', 'active_user', 'pay_user', 'new_pay_user', 'total_pay', 'rate'];//需要的字段
        $new_data = [];
        //渠道的每个数据合并
        foreach ($data as $dk => $dv) {
            foreach ($array_keys as $kk) {
                if ($kk == 'total_pay' || $kk == 'new_total_pay') {
                    $unique = 0;
                } else {
                    $unique = 1;
                }
                $new_data[$dk][$kk] = str_unique(array_key_value_link($kk, $dv), $unique);
                $new_data[$dk]['count_' . $kk] = arr_count($new_data[$dk][$kk]);
                if ($kk == 'total_pay') {
                    $new_data[$dk][$kk] = str_arr_sum($new_data[$dk][$kk]);
                }
                if ($kk == 'rate') {
                    $new_data[$dk][$kk] = empty($new_data[$dk]['new_register_user']) ? '0.00%' : (empty($new_data[$dk]['pay_user']) ? '0.00%' : null_to_0(count(explode(',', $new_data[$dk]['pay_user'])) / count(explode(',', $new_data[$dk]['new_register_user'])) * 100) . '%');
                }
                $total_data[$kk] = '';//汇总数据数组
            }
            unset($new_data[$dk]['count_total_pay']);
            unset($new_data[$dk]['count_rate']);
            $new_data[$dk]['promote_id'] = $dk;
            $new_data[$dk]['promote_account'] = $promoteidss[$dk];
        }
        //计算汇总
        foreach ($total_data as $kk => $vv) {
            $total_data[$kk] = array_filter(array_column($new_data, $kk));
            if ($kk == 'total_pay' || $kk == 'new_total_pay') {
                $total_data[$kk] = null_to_0(array_sum($total_data[$kk]));
            } else {
                $new = implode(',', $total_data[$kk]);
                $total_data[$kk] = count(array_filter(array_unique(explode(',', $new))));
            }
        }
        unset($total_data['rate']);
        $this->assign('total_data', $total_data);
        return $new_data;
    }

    /**
     * @函数或方法说明
     * @渠道获取数据
     * @param string $starttime
     * @param string $endtime
     * @param string $promote_id
     * @param string $game_id
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2019/8/8 15:24
     */
    public function promote_data($starttime = '', $endtime = '', $promote_id = [],$table='datareporteverypid_')
    {
        $redis = Redis::getInstance(['host' => '127.0.0.1', 'port' => 6379], []);
        //搜索条件
        $promotemap['id'] = ['in',$promote_id];
        $promoteidss = $promoteids = Db::table('tab_promote')->where($promotemap)->order('id asc')->column('id,account');
        if(!$endtime){
            $endtime = date('Y-m-d');
        }
        if($starttime){
            $datearr = Db::name('date_list')->where(['time' => ['between', [$starttime, $endtime]]])->order('time asc')->column('time');
        }else{
            $first_record = Db::table('tab_datareport_every_pid')->field('time')->order('time asc')->find();
            $starttime = $first_record['time']?:0;
            $datearr = Db::name('date_list')->where(['time' => ['between', [$starttime, $endtime]]])->order('time asc')->column('time');
        }
        $first_data = Db::table('tab_datareport_every_pid')->field('time')->order('id asc')->find();
        //每天数据
        foreach ($datearr as $dk => $dv) {
//            if($dv == date('Y-m-d')){
//                $event = new DatabaseController();
//                $event->basedata_today();
//            }
            $key = $table . $dv;
            $keyarr = $redis->hKeys($key);//得到该日期所有域值
            //redis数据丢失时 实时取sql重新写入
            if (empty($keyarr) && $first_data && $dv>$first_data['time']) {
                $sqlsummary = new Sqlsummary();
                $sqlsummary->basedata_pid_everyday($dv);
                $keyarr = $redis->hKeys($key);//得到该日期所有域值
            }
            //搜索条件 移除不符合key
            if ($promote_id) {
                foreach ($keyarr as $kak => $kav) {
                    if ($promote_id > 0) {
                        if ( !in_array(strstr($kav, '_', true),$promote_id)) {
                            unset($keyarr[$kak]);
                        }
                    }
                }
            }
            $keystr = implode(',', $keyarr);
            $field = empty($keystr) ? '' : $keystr;
            $redisdataarr[] = array_values($redis->hMget($key, $field));
        }
        $redisdata = array_reduce($redisdataarr, function ($result, $item = []) {
            if (empty($item)) {
                $item = [];
            }
            $result = array_merge($result, $item);
            return $result;
        }, []);
        array_map(function ($item) use (& $dataarr) {
            $dataarr[] = json_decode($item, true);
        }, $redisdata);
        $_data = [];
        foreach ($dataarr as $v) {
            $_data[$v['promote_id']][] = $v;//相同id放到一起
        }
        ksort($_data);//根据推id排序
        unset($_data[0]);//去除官方数据
        foreach ($_data as $_k => $_v) {
            unset($promoteids[$_k]);
        }
        $mergedata = $_data + $promoteids;
        foreach ($mergedata as $mk => $mv) {
            $data[$mk] = $mv;
            if (!is_array($mv)) {
                $data[$mk] = [];
            }
        }
        $array_keys = ['new_register_user', 'active_user', 'pay_user', 'new_pay_user', 'total_pay', 'rate'];//需要的字段
        $new_data = [];
        //渠道的每个数据合并
        foreach ($data as $dk => $dv) {
            foreach ($array_keys as $kk) {
                if ($kk == 'total_pay' || $kk == 'new_total_pay') {
                    $unique = 0;
                } else {
                    $unique = 1;
                }
                $new_data[$dk][$kk] = str_unique(array_key_value_link($kk, $dv), $unique);
                $new_data[$dk]['count_' . $kk] = arr_count($new_data[$dk][$kk]);
                if ($kk == 'total_pay') {
                    $new_data[$dk][$kk] = str_arr_sum($new_data[$dk][$kk]);
                }
                if ($kk == 'rate') {
                    $new_data[$dk][$kk] = empty($new_data[$dk]['new_register_user']) ? '0.00%' : (empty($new_data[$dk]['pay_user']) ? '0.00%' : null_to_0(count(explode(',', $new_data[$dk]['pay_user'])) / count(explode(',', $new_data[$dk]['new_register_user'])) * 100) . '%');
                }
                $total_data[$kk] = '';//汇总数据数组
            }
            unset($new_data[$dk]['count_total_pay']);
            unset($new_data[$dk]['count_rate']);
            $new_data[$dk]['promote_id'] = $dk;
            $new_data[$dk]['promote_account'] = $promoteidss[$dk];
        }
        //计算汇总
        foreach ($total_data as $kk => $vv) {
            $total_data[$kk] = array_filter(array_column($new_data, $kk));
            if ($kk == 'total_pay' || $kk == 'new_total_pay') {
                $total_data[$kk] = null_to_0(array_sum($total_data[$kk]));
            } else {
                $new = implode(',', $total_data[$kk]);
                $total_data[$kk] = count(array_filter(array_unique(explode(',', $new))));
            }
        }
        unset($total_data['rate']);
        $info['data'] = $new_data;
        $info['total_data'] = $total_data;
        return $info;
    }

    /**
     * @函数或方法说明
     * @渠道获取数据
     * @param string $starttime
     * @param string $endtime
     * @param string $promote_id
     * @param string $game_id
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2019/8/8 15:24
     */
    public function promote_data_arpu($starttime = '', $endtime = '', $promote_id = [],$game_id=0,$table='datareporteverypid_',$type='pc')
    {
        $sonids = array_column(get_song_promote_lists($promote_id,2),'id');
        $datearr = Db::name('date_list')->where(['time' => ['between', [$starttime, $endtime]]])->order('time asc')->column('time');
        foreach ($datearr as $key=>$v){
            $new_data = reset($this->arpu_data($v,$v,[$promote_id],$game_id))[$promote_id];
            if($sonids){
                $son_data = $this->arpu_data($v, $v, $sonids,$game_id)['data'];
                $new_data = $this->son_data_merge($new_data,$son_data);
            }
            $new_data['time'] = $v;
            $data[] = $new_data;
        }
        foreach ($data as $k => $v) {
            $data[$k]['rate'] = empty($v['new_register_user']) ? '0.00%' : (empty($v['pay_user']) ? '0.00%' : null_to_0(count(explode(',', $v['pay_user'])) / count(explode(',', $v['new_register_user'])) * 100) . '%');
            $data[$k]['arpu'] = empty($v['active_user']) ? '0.00' : null_to_0($v['total_pay'] / count(explode(',', $v['active_user'])));
            $data[$k]['arppu'] = empty($v['pay_user']) ? '0.00' : null_to_0($v['total_pay'] / count(explode(',', $v['pay_user'])));
        }
        $total_data = [
                'new_register_user' => '',
                'active_user' => '',
                'pay_user' => '',
                'total_order' => '',
                'total_pay' => '',
                'new_pay_user' => '',
                'new_total_pay' => '',
                'fire_device' =>'',
        ];
        foreach ($total_data as $kk => $vv) {
            $total_data[$kk] = array_filter(array_column($data, $kk));
            if ($kk == 'total_pay' || $kk == 'new_total_pay') {
                $total_data[$kk] = null_to_0(array_sum($total_data[$kk]));
            } else {
                $new = implode(',', $total_data[$kk]);
                $total_data[$kk] = count(array_filter(array_unique(explode(',', $new))));
            }
        }
        if($type == 'wap'){
            return ['data'=>$data,'total'=>$total_data];
        }else{
            $this->assign('total_data', $total_data);
            return $data;
        }
    }


    private function son_data_merge($parent_data,$son_data){
        if(empty($son_data)){
            return $parent_data;
        }
        foreach ($parent_data as $llk=>$llv){
            if(in_array($llk,[ 'new_register_user', 'active_user', 'pay_user', 'total_order', 'total_pay', 'new_pay_user', 'new_total_pay', 'fire_device'])){
                if($llk == 'total_pay' || $llk == 'new_total_pay'){
                    $list_data[$llk] = $llv+array_sum(array_column($son_data,$llk));
                }else{
                    $list_data[$llk] = $llv.','.implode(',',array_column($son_data,$llk));
                    $list_data[$llk] = trim($list_data[$llk],',');//合并
                    $value = str_unique($list_data[$llk], 1);//去重
                    $list_data['count_'.$llk] = arr_count($value);
                }

            }
        }
        return $list_data;
    }

    /**
     * @函数或方法说明
     * @ARPU基础数据
     * @param string $starttime
     * @param string $endtime
     * @param string $promote_id
     * @param string $game_id
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2019/8/8 15:24
     */
    protected function arpu_data($starttime,$endtime, $promote_id = 0,$game_id=0,$table='datareporteverypid_')
    {
        $redis = Redis::getInstance(['host' => '127.0.0.1', 'port' => 6379], []);
        //搜索条件
        $promotemap['id'] = ['in',$promote_id];
        $promoteidss = $promoteids = Db::table('tab_promote')->where($promotemap)->order('id asc')->column('id,account');
        if(!$endtime){
            $endtime = date('Y-m-d');
        }
        if($starttime){
            $datearr = Db::name('date_list')->where(['time' => ['between', [$starttime, $endtime]]])->order('time asc')->column('time');
        }else{
            $first_record = Db::table('tab_datareport_every_pid')->field('time')->order('time asc')->find();
            $starttime = $first_record['time'];
            $datearr = Db::name('date_list')->where(['time' => ['between', [$starttime, $endtime]]])->order('time asc')->column('time');
        }
        //每天数据
        foreach ($datearr as $dk => $dv) {
            $key = $table . $dv;
            $keyarr = $redis->hKeys($key);//得到该日期所有域值
            //redis数据丢失时 实时取sql重新写入
            if (empty($keyarr)) {
                $sqlsummary = new Sqlsummary();
                $sqlsummary->basedata_pid_everyday($dv);
                $keyarr = $redis->hKeys($key);//得到该日期所有域值
            }
            //搜索条件 移除不符合key
            if ($promote_id || $game_id) {
                foreach ($keyarr as $kak => $kav) {
                    if ($promote_id > 0) {
                        if ( !in_array(strstr($kav, '_', true),$promote_id)) {
                            unset($keyarr[$kak]);
                        }
                    }
                    if ($game_id > 0) {
                        if (ltrim(strstr($kav, '_', false), '_') != $game_id) {
                            unset($keyarr[$kak]);
                        }
                    }
                }
            }
            $keystr = implode(',', $keyarr);
            $field = empty($keystr) ? '' : $keystr;
            $redisdataarr[] = array_values($redis->hMget($key, $field));
        }
        $redisdata = array_reduce($redisdataarr, function ($result, $item = []) {
            if (empty($item)) {
                $item = [];
            }
            $result = array_merge($result, $item);
            return $result;
        }, []);
        array_map(function ($item) use (& $dataarr) {
            $dataarr[] = json_decode($item, true);
        }, $redisdata);
        $_data = [];
        foreach ($dataarr as $v) {
            $_data[$v['promote_id']][] = $v;//相同id放到一起
        }
        ksort($_data);//根据推id排序
        unset($_data[0]);//去除官方数据
        foreach ($_data as $_k => $_v) {
            unset($promoteids[$_k]);
        }
        $mergedata = $_data + $promoteids;
        foreach ($mergedata as $mk => $mv) {
            $data[$mk] = $mv;
            if (!is_array($mv)) {
                $data[$mk] = [];
            }
        }
        $array_keys = [ 'new_register_user', 'active_user', 'pay_user', 'total_order', 'total_pay', 'new_pay_user', 'new_total_pay', 'fire_device'];//需要的字段
        $new_data = [];
        //渠道的每个数据合并
        foreach ($data as $dk => $dv) {
            foreach ($array_keys as $kk) {
                if ($kk == 'total_pay' || $kk == 'new_total_pay') {
                    $unique = 0;
                } else {
                    $unique = 1;
                }
                $new_data[$dk][$kk] = str_unique(array_key_value_link($kk, $dv), $unique);
                $new_data[$dk]['count_' . $kk] = arr_count($new_data[$dk][$kk]);
                if ($kk == 'total_pay' || $kk == 'new_total_pay') {
                    $new_data[$dk][$kk] = str_arr_sum($new_data[$dk][$kk]);
                }
                if ($kk == 'rate') {
                    $new_data[$dk][$kk] = empty($new_data[$dk]['new_register_user']) ? '0.00%' : (empty($new_data[$dk]['pay_user']) ? '0.00%' : null_to_0(count(explode(',', $new_data[$dk]['pay_user'])) / count(explode(',', $new_data[$dk]['new_register_user'])) * 100) . '%');
                }
                $total_data[$kk] = '';//汇总数据数组
            }
            unset($new_data[$dk]['count_total_pay']);
            unset($new_data[$dk]['count_rate']);
            $new_data[$dk]['promote_id'] = $dk;
            $new_data[$dk]['promote_account'] = $promoteidss[$dk];
        }
        //计算汇总
        foreach ($total_data as $kk => $vv) {
            $total_data[$kk] = array_filter(array_column($new_data, $kk));
            if ($kk == 'total_pay' || $kk == 'new_total_pay') {
                $total_data[$kk] = null_to_0(array_sum($total_data[$kk]));
            } else {
                $new = implode(',', $total_data[$kk]);
                $total_data[$kk] = count(array_filter(array_unique(explode(',', $new))));
            }
        }
        unset($total_data['rate']);
        $info['data'] = $new_data;
        $info['total_data'] = $total_data;
        return $info;
    }


}
