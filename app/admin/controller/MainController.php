<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\admin\controller;

use app\datareport\event\DatabaseController;
use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\Menu;
use cmf\org\RedisSDK\RedisController as Redis;
use app\datareport\event\DatasqlsummaryController as Sqlsummary;
use app\game\model\ServerModel;

class MainController extends AdminBaseController
{

    /**
     *  后台欢迎页
     */
    public function index()
    {
        //查询判断登录用户是否有指定页面-20210624-byh-start
        $login_show_page = Db::name('user')->where('id',session('ADMIN_ID'))->value('login_show_page');
        if(!empty($login_show_page) && $login_show_page != 'admin/main/index'){
            $this->redirect(url($login_show_page));
        }
        //查询判断登录用户是否有指定页面-20210624-byh-end
        $dashboardWidgets = [];
        $widgets = cmf_get_option('admin_dashboard_widgets');

        $defaultDashboardWidgets = [
            '_SystemCmfHub' => ['name' => 'CmfHub', 'is_system' => 1],
            '_SystemCmfDocuments' => ['name' => 'CmfDocuments', 'is_system' => 1],
            '_SystemMainContributors' => ['name' => 'MainContributors', 'is_system' => 1],
            '_SystemContributors' => ['name' => 'Contributors', 'is_system' => 1],
            '_SystemCustom1' => ['name' => 'Custom1', 'is_system' => 1],
            '_SystemCustom2' => ['name' => 'Custom2', 'is_system' => 1],
            '_SystemCustom3' => ['name' => 'Custom3', 'is_system' => 1],
            '_SystemCustom4' => ['name' => 'Custom4', 'is_system' => 1],
            '_SystemCustom5' => ['name' => 'Custom5', 'is_system' => 1],
        ];

        if (empty($widgets)) {
            $dashboardWidgets = $defaultDashboardWidgets;
        } else {
            foreach ($widgets as $widget) {
                if ($widget['is_system']) {
                    $dashboardWidgets['_System' . $widget['name']] = ['name' => $widget['name'], 'is_system' => 1];
                } else {
                    $dashboardWidgets[$widget['name']] = ['name' => $widget['name'], 'is_system' => 0];
                }
            }

            foreach ($defaultDashboardWidgets as $widgetName => $widget) {
                $dashboardWidgets[$widgetName] = $widget;
            }


        }

        $dashboardWidgetPlugins = [];

        $hookResults = hook('admin_dashboard');

        if (!empty($hookResults)) {
            foreach ($hookResults as $hookResult) {
                if (isset($hookResult['width']) && isset($hookResult['view']) && isset($hookResult['plugin'])) { //验证插件返回合法性
                    $dashboardWidgetPlugins[$hookResult['plugin']] = $hookResult;
                    if (!isset($dashboardWidgets[$hookResult['plugin']])) {
                        $dashboardWidgets[$hookResult['plugin']] = ['name' => $hookResult['plugin'], 'is_system' => 0];
                    }
                }
            }
        }

        $smtpSetting = cmf_get_option('smtp_setting');

        $this->assign('dashboard_widgets', $dashboardWidgets);
        $this->assign('dashboard_widget_plugins', $dashboardWidgetPlugins);
        $this->assign('has_smtp_setting', empty($smtpSetting) ? false : true);
        if (AUTH_USER == 1) {
            //昨日新增人数及总人数
            $map['register_time'] = total(5, 1);
            $map['puid'] = 0;
            $addUserNum['yesRegNum'] = Db::table('tab_user')->where($map)->count();
            //前天新增人数
            $qian['register_time'] = total(11, 1);
            $qian['puid'] = 0;
            $qianRegNum = Db::table('tab_user')->where($qian)->count();

//            $addUserNum['totalRegNum'] = Db::table('tab_user')->count();
            $addUserNum['todayRegNum'] = Db::table('tab_user')->where(['register_time' => total(1, 1)])->where('puid',0)->count();
            $addUserNum['regRange'] = $addUserNum['yesRegNum'] == 0 ? "--" : (round(($addUserNum['todayRegNum'] - $addUserNum['yesRegNum']) / $addUserNum['yesRegNum'], 2) * 100) . "%";
            $this->assign('addUserNum', $addUserNum);

            //活跃统计
            $active['login_time'] = total(5, 1);
            $result = Db::table('tab_user_day_login')->distinct(true)->field('user_id')->where($active)->select();
            $addActiveNum['yesActiveNum'] = empty($result) ? 0 : count($result);
            //本周活跃统计
//            $activetotle['login_time'] = total(2,1);
//            $result = Db::table('tab_user_day_login')->distinct(true)->field('user_id')->where($activetotle)->select();
            $result = Db::table('tab_user_day_login')->distinct(true)->field('user_id')->where(['login_time' => total(1, 1)])->select();
//            $addActiveNum['totalActiveNum'] = empty($result) ? 0 : count($result);
            $addActiveNum['todayActiveNum'] = empty($result) ? 0 : count($result);
            $this->assign('addActiveNum', $addActiveNum);
        }
        if (AUTH_PAY == 1 && AUTH_USER == 1) {
            //付费人数统计
            $spend['pay_status'] = 1;
            $spend['pay_time'] = total(5, 1);
            $result = Db::table('tab_spend')->distinct(true)->field('user_id')->where($spend)->select();
            $payUserNum['yesNum'] = empty($result) ? 0 : count($result);
//            $result = Db::table('tab_spend')->distinct(true)->field('user_id')->where('pay_status',1)->select();
            $result = Db::table('tab_spend')->distinct(true)->field('user_id')->where('pay_status', 1)->where(['pay_time' => total(1, 1)])->select();
            $payUserNum['todayNum'] = empty($result) ? 0 : count($result);
            $this->assign('payUserNum', $payUserNum);
            //付费金额统计
            $result = Db::table('tab_spend')->field('pay_amount')->where($spend)->select();
            $payAmount['yesNum'] = empty($result) ? '0.00' : array_sum(array_column($result->toArray(), 'pay_amount'));
            $result = Db::table('tab_spend')->field('pay_amount')->where('pay_status', 1)->where(['pay_time' => total(1, 1)])->select();
            $payAmount['todayNum'] = empty($result) ? '0.00' : array_sum(array_column($result->toArray(), 'pay_amount'));
            $this->assign('payAmount', $payAmount);
        }
        if (AUTH_GAME == 1) {
            //礼包数量不足列表
            $agendaData['gift'] = Db::table('tab_game_giftbag')->field('id,giftbag_name,game_name')->where('remain_num', '<', 10)->count();
        }
        if (AUTH_GAME == 1 && AUTH_PROMOTE == 1) {
            //渠道分包待打包数
            $agendaData['promote_game_pack_android_num'] = Db::table('tab_promote_apply')->where('sdk_version', 1)->where('enable_status', 0)->count();
            $agendaData['promote_game_pack_ios_num'] = Db::table('tab_promote_apply')->where('sdk_version', 2)->where('enable_status', 0)->count();
            //长时间未打包成功
            $agendaData['promote_game_pack_error_long_num'] = Db::table('tab_promote_apply')->where(['pack_time'=>['lt',time()-7200]])->where(['enable_status'=>['in',[-1,2,3]]])->count();
        }
        if (AUTH_GAME == 1 && AUTH_USER == 1 && AUTH_PAY == 1) {
            //游戏充值待补单数
            $agendaData['pay_game_status_num'] = Db::table('tab_spend')->where('pay_status', 1)->where('pay_game_status', 0)->count();
            //提现待审核数量
            $orderInvoiceCount = Db::table('tab_promote_withdraw')->where('status', 0)->count();
            $agendaData['orderInvoiceCount'] = $orderInvoiceCount;
            //返利过期
            $rebateOutCount = Db::table('tab_spend_rebate')->where('end_time',[['<',time()],['gt',0]])->count();
            $agendaData['rebateOutCount'] = $rebateOutCount;
            //获取领取礼包数据
            $time['create_time'] = total(1, 1);
            $time1['create_time'] = total(5, 1);
            $time2['pay_time'] = total(1, 1);
            $time3['pay_time'] = total(5, 1);
            if ($addUserNum['yesRegNum'] == 0) {
                $newUserInfo['gift']['ratio'] = "0";
                $newUserInfo['gift']['number'] = "0";
                $newUserInfo['gift']['range'] = "0";
                $newUserInfo['down_game']['ratio'] = "0";
                $newUserInfo['down_game']['number'] = "0";
                $newUserInfo['down_game']['range'] = "0";
                $newUserInfo['share']['ratio'] = "0";
                $newUserInfo['share']['number'] = "0";
                $newUserInfo['share']['range'] = "0";
                $newUserInfo['pay']['ratio'] = "0";
                $newUserInfo['pay']['number'] = "0";
                $newUserInfo['pay']['range'] = "0";
            } else {
                $map_today['register_time'] = total(1, 1);
                $map_today['puid'] = 0;
                $qian_yes['register_time'] = total(5, 1);
                $qian_yes['puid'] = 0;
                $yes_user = Db::table('tab_user')->where($map_today)->field('id')->select();
                $yes_user = array_column($yes_user->toArray(), 'id')?:[-1];
                $qian_user = Db::table('tab_user')->where($qian_yes)->field('id')->select();
                $qian_user = array_column($qian_user->toArray(), 'id')?:[-1];
                //礼包
                $yesgift = Db::table('tab_game_gift_record')->where($time)->group('user_id')->column('user_id');
                $yesgift = count(array_intersect($yes_user, $yesgift));
                $qiangift = Db::table('tab_game_gift_record')->where($time1)->group('user_id')->column('user_id');
                $qiangift = count(array_intersect($qian_user, $qiangift));
                $newUserInfo['gift']['ratio'] = $yesgift == 0 ? 0 : round($yesgift / $addUserNum['todayRegNum'], 2) * 100;
                $newUserInfo['gift']['ratio'] = $this->formatValue($newUserInfo['gift']['ratio']);
                $newUserInfo['gift']['number'] = $yesgift;
                $newUserInfo['gift']['range'] = $qianRegNum == 0 ? '--' : (round($yesgift / $addUserNum['todayRegNum'], 2) - round($qiangift / $qianRegNum, 2)) * 100;
                $newUserInfo['gift']['range'] = $this->formatValue($newUserInfo['gift']['range']);
                //打开游戏 昨天注册的用户  同一用户打开多个游戏计为1
                $yessql = 'select * from tab_user_day_login where login_time ' . total(1) . ' and game_id >0 and user_id in (' . implode(',', $yes_user) . ') group by user_id';
                $todayopen = array_column(Db::query($yessql), 'user_id');
                $todayopen = count(array_intersect($yes_user, $todayopen));
                $qiansql = 'select * from tab_user_day_login where login_time ' . total(5) . ' and game_id >0 and user_id in (' . implode(',', $qian_user) . ') group by user_id';
                $qianopen = array_column(Db::query($qiansql), 'user_id');
                $qianopen = count(array_intersect($qian_user, $qianopen));
                $newUserInfo['first_open_game']['ratio'] = $todayopen == 0 ? 0 : round($todayopen / $addUserNum['todayRegNum'], 2) * 100;
                $newUserInfo['first_open_game']['ratio'] = $this->formatValue($newUserInfo['first_open_game']['ratio']);
                $newUserInfo['first_open_game']['number'] = $todayopen;
                $newUserInfo['first_open_game']['range'] = $qianRegNum == 0 ? '--' : (round($todayopen / $addUserNum['todayRegNum'], 2) - round($qianopen / $qianRegNum, 2)) * 100;
                $newUserInfo['first_open_game']['range'] = $this->formatValue($newUserInfo['first_open_game']['range']);
                //分享游戏
                $yesshare = Db::table('tab_game_share_record')->where($time)->group('user_id')->column('user_id'); // 今日数据
                $yesshare = count(array_intersect($yes_user, $yesshare)); // 统计 数组键值的交集个数
                $qianshare = Db::table('tab_game_share_record')->where($time1)->group('user_id')->column('user_id'); // 昨日数据
                $qianshare = count(array_intersect($qian_user, $qianshare));
                $newUserInfo['share']['ratio'] = $yesshare == 0 ? 0 : round($yesshare / $addUserNum['todayRegNum'], 2) * 100;
                $newUserInfo['share']['ratio'] = $this->formatValue($newUserInfo['share']['ratio']);
                $newUserInfo['share']['number'] = $yesshare;
                $newUserInfo['share']['range'] = $qianRegNum == 0 ? '--' : (round($yesshare / $addUserNum['todayRegNum'], 2) - round($qianshare / $qianRegNum, 2)) * 100;
                $newUserInfo['share']['range'] = $this->formatValue($newUserInfo['share']['range']);
                //充值游戏
                $yesspend = Db::table('tab_spend')->where($time2)->group('user_id')->column('user_id');
                $yesspend = count(array_intersect($yes_user, $yesspend));
                $qianspend = Db::table('tab_spend')->where($time3)->group('user_id')->column('user_id');
                $qianspend = count(array_intersect($qian_user, $qianspend));
                $newUserInfo['pay']['ratio'] = $yesspend == 0 ? 0 : round($yesspend / $addUserNum['todayRegNum'], 2) * 100;
                $newUserInfo['pay']['ratio'] = $this->formatValue($newUserInfo['pay']['ratio']);
                $newUserInfo['pay']['number'] = $yesspend;
                $newUserInfo['pay']['range'] = $qianRegNum == 0 ? '--' : (round($yesspend / $addUserNum['todayRegNum'], 2) - round($qianspend / $qianRegNum, 2)) * 100;
                $newUserInfo['pay']['range'] = $this->formatValue($newUserInfo['pay']['range']);
            }

            $redis = Redis::getInstance(['host' => '127.0.0.1', 'port' => 6379], []);
            $datearr = Db::name('date_list')->where(['time' => ['between', [date("Y-m-d", strtotime("-6 day")), date("Y-m-d")]]])->column('time');
            foreach ($datearr as $k => $v) {
//                if($v == date('Y-m-d')){
//                    $event = new DatabaseController();
//                    $event->basedata_today();
//                }

                $key = 'datareporttoppid_' . $v;
                $keyarr = $redis->hKeys($key);//得到该日期所有域值
                //redis数据丢失时 实时取sql重新写入
                if (empty($keyarr)) {
                    $sqlsummary = new Sqlsummary();
                    $sqlsummary->basedata_pid_everyday($v);
                    $keyarr = $redis->hKeys($key);//得到该日期所有域值
                }
                $keystr = implode(',', $keyarr);
                $field = empty($keystr) ? '' : $keystr;
                $redisdata = array_values($redis->hMget($key, $field));
                $data = [];
                array_map(function ($item) use (& $data) {
                    $data[] = json_decode($item, true);
                }, $redisdata);
                $array_keys = empty($array_keys) ? ['time', 'new_register_user', 'total_pay'] : $array_keys;
                $new_data[$v] = [];
                foreach ($array_keys as $kk) {
                    if ($kk == 'total_pay' || $kk == 'new_total_pay') {
                        $unique = 0;
                    } else {
                        $unique = 1;
                    }
                    $new_data[$v][$kk] = str_unique(trim(array_key_value_link($kk, $data), ','), $unique);//每个游戏的数据合并
                    $new_data[$v]['count_' . $kk] = arr_count($new_data[$v][$kk]);
                    if ($kk == 'total_pay' || $kk == 'new_total_pay') {
                        $new_data[$v][$kk] = str_arr_sum($new_data[$v][$kk]);
                    }
                    $total_data[$kk] = '';//汇总数据数组
                }
                $new_data[$v]['time'] = date('m/d', strtotime($v));
            }
//            缓存处理
            $this->assign('database_time', json_encode(array_column($new_data, 'time')));
            $this->assign('database_register', json_encode(array_column($new_data, 'count_new_register_user')));
            $this->assign('database_pay', json_encode(array_column($new_data, 'total_pay')));
        }

        //待办事项推广平台待办
        $event = new \app\admin\event\adminIndexController;
        $promotewait = $event->promote_wait();
        //获取系统配置信息
        $version = Db::query('SELECT VERSION() AS ver');
        $config = [
            'server_os' => PHP_OS,
            'server_port' => $_SERVER['SERVER_PORT'],
            'server_ip' => $_SERVER['SERVER_ADDR'],
            'server_soft' => $_SERVER['SERVER_SOFTWARE'],
            'php_version' => PHP_VERSION,
            'mysql_version' => $version[0]['ver'],
            'max_upload_size' => ini_get('upload_max_filesize')
        ];
        $this->assign('newUserInfo', $newUserInfo);
        $this->assign('agendaData', $agendaData);
        $this->assign('promote_wait', $promotewait);
        $this->assign('config', $config);


        $url = get_upgrade_domain()."/api/web_site/ip";
        $servers_ip = trim(file_get_contents($url));
        $this -> assign('servers_ip', $servers_ip);
        $domain = cmf_get_domain();
        $this -> assign('domain', $domain);
        $web_site_title = cmf_get_option('admin_set')['web_site_title'];
        $this->assign('web_site_title',$web_site_title);

        return $this->fetch();
    }

    public function dashboardWidget()
    {
        $dashboardWidgets = [];
        $widgets = $this->request->param('widgets/a');
        if (!empty($widgets)) {
            foreach ($widgets as $widget) {
                if ($widget['is_system']) {
                    array_push($dashboardWidgets, ['name' => $widget['name'], 'is_system' => 1]);
                } else {
                    array_push($dashboardWidgets, ['name' => $widget['name'], 'is_system' => 0]);
                }
            }
        }

        cmf_set_option('admin_dashboard_widgets', $dashboardWidgets, true);

        $this->success('更新成功!');

    }

    /**
     * @函数或方法说明
     * @查看系统使用情况
     * @author: 郭家屯
     * @since: 2020/8/7 11:02
     */
    public function used_status()
    {
        header("Content-Type: text/html; charset=UTF-8 ");
        $file = dirname(__FILE__).'/systeminfo.py';
        $cmd = exec("python {$file} 2>&1",$array,$ret);
        foreach ($array as $key=>$v){
            if(in_array($key,[0,4,6]) ){
                echo "<span style='color: blue;font-size: 24px;margin-left: 12px;'>".$v."</span>";
            }else{
                echo "<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$v."</span>";
            }
            echo "<br/><br/>";
        }
    }

    /**
     * 获得CPU使用率
     * @return Number
     */
    protected function getCpuUsage() {
        $path = $this -> getCupUsageVbsPath();
        exec("cscript -nologo $path", $usage);
        return $usage[0];
    }

    /**
     * 获得内存使用率数组
     * @return array
     */
    protected function getMemoryUsage() {
        $path = $this -> getMemoryUsageVbsPath();
        exec("cscript -nologo $path", $usage);
        $memory = json_decode($usage[0], true);
        $memory['usage'] = Round((($memory['TotalVisibleMemorySize'] - $memory['FreePhysicalMemory']) / $memory['TotalVisibleMemorySize']) * 100);
        return $memory;
    }
    /**
     * 判断指定路径下指定文件是否存在，如不存在则创建
     * @param string $fileName 文件名
     * @param string $content 文件内容
     * @return string 返回文件路径
     */
    private function getFilePath($fileName, $content) {
        $path = dirname(__FILE__). "\\$fileName";
        if (!file_exists($path)) {
            file_put_contents($path, $content);
        }
        return $path;
    }
    /**
     * 获得cpu使用率vbs文件生成函数
     * @return string 返回vbs文件路径
     */
    private function getCupUsageVbsPath() {
        return $this -> getFilePath(
                'cpu_usage.vbs',
                "On Error Resume Next
    Set objProc = GetObject(\"winmgmts:\\\\.\\root\cimv2:win32_processor='cpu0'\")
    WScript.Echo(objProc.LoadPercentage)"
        );
    }
    /**
     * 获得总内存及可用物理内存JSON vbs文件生成函数
     * @return string 返回vbs文件路径
     */
    private function getMemoryUsageVbsPath() {
        return $this -> getFilePath(
                'memory_usage.vbs',
                "On Error Resume Next
    Set objWMI = GetObject(\"winmgmts:\\\\.\\root\cimv2\")
    Set colOS = objWMI.InstancesOf(\"Win32_OperatingSystem\")
    For Each objOS in colOS
     Wscript.Echo(\"{\"\"TotalVisibleMemorySize\"\":\" & objOS.TotalVisibleMemorySize & \",\"\"FreePhysicalMemory\"\":\" & objOS.FreePhysicalMemory & \"}\")
    Next"
        );
    }

    /**
     * @函数或方法说明
     * @获取磁盘是哟更换情况
     * @author: 郭家屯
     * @since: 2020/8/7 11:42
     */
    private function getdrive()
    {
        $out = '';
        $info = exec('wmic logicaldisk get FreeSpace,size /format:list', $out, $status);
        $hd = '';
        foreach ($out as $vaule) {
            $hd .= $vaule . ' ';;
        }
        $hd_array = explode('   ', trim($hd));
        $key = 'CDEFGHIJKLMNOPQRSTUVWXYZ';
        $size = 0;
        $freespace = 0;
        foreach ($hd_array as $k => $v) {
            $s_array = explode('Size=', $v);
            $fs_array = explode('FreeSpace=', $s_array[0]);
            $size += round(trim($s_array[1]) / (1024 * 1024 * 1024), 1);
            $freespace += round(trim($fs_array[1]) / (1024 * 1024 * 1024), 1);
        }
        $data['use'] = $size - $freespace;
        $data['unuse'] = $freespace;
        return $data;
    }


    private function formatValue($value)
    {
        if ($value == INF || $value == NAN || is_nan($value)) {
            return 0;
        }
        return $value;
    }

}
