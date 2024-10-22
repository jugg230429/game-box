<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\admin\controller;

use app\admin\model\RouteModel;
use cmf\controller\AdminBaseController;
use cmf\org\RedisSDK\RedisController as Redis;
use think\Db;
use app\datareport\event\DatasummaryController as Summary;
use think\console\Output;
use think\Request;

/**
 * Class SettingController
 * @package app\admin\controller
 * @adminMenuRoot(
 *     'name'   =>'设置',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 0,
 *     'icon'   =>'cogs',
 *     'remark' =>'系统设置入口'
 * )
 */
class SettingController extends AdminBaseController
{

    /**
     * 网站信息
     * @adminMenu(
     *     'name'   => '网站信息',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '网站信息',
     *     'param'  => ''
     * )
     */
    public function site()
    {
        $content = hook_one('admin_setting_site_view');

        if (!empty($content)) {
            return $content;
        }

        $noNeedDirs = [".", "..", ".svn", 'fonts'];
        $adminThemesDir = config('template.cmf_admin_theme_path') . config('template.cmf_admin_default_theme') . '/public/assets/themes/';
        $adminStyles = cmf_scan_dir($adminThemesDir . '*', GLOB_ONLYDIR);
        $adminStyles = array_diff($adminStyles, $noNeedDirs);
        $cdnSettings = cmf_get_option('cdn_settings');
        $cmfSettings = cmf_get_option('cmf_settings');
        $adminSettings = cmf_get_option('admin_settings');

        $this->assign('site_info', cmf_get_option('site_info'));
        $this->assign("admin_styles", $adminStyles);
        $this->assign("templates", []);
        $this->assign("cdn_settings", $cdnSettings);
        $this->assign("admin_settings", $adminSettings);
        $this->assign("cmf_settings", $cmfSettings);

        return $this->fetch();
    }

    /**
     * 网站信息设置提交
     * @adminMenu(
     *     'name'   => '网站信息设置提交',
     *     'parent' => 'site',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '网站信息设置提交',
     *     'param'  => ''
     * )
     */
    public function sitePost()
    {
        if ($this->request->isPost()) {
//            $result = $this->validate($this->request->param(), 'SettingSite');
//            if ($result !== true) {
//                $this->error($result);
//            }

            $options = $this->request->param('options/a');
            cmf_set_option('site_info', $options);

            $cmfSettings = $this->request->param('cmf_settings/a');

            $bannedUsernames = preg_replace("/[^0-9A-Za-z_\\x{4e00}-\\x{9fa5}-]/u", ",", $cmfSettings['banned_usernames']);
            $cmfSettings['banned_usernames'] = $bannedUsernames;
            cmf_set_option('cmf_settings', $cmfSettings);

            $cdnSettings = $this->request->param('cdn_settings/a');
            cmf_set_option('cdn_settings', $cdnSettings);

            $adminSettings = $this->request->param('admin_settings/a');

            $routeModel = new RouteModel();
            if (!empty($adminSettings['admin_password'])) {
                $routeModel->setRoute($adminSettings['admin_password'] . '$', 'admin/Index/index', [], 2, 5000);
            } else {
                $routeModel->deleteRoute('admin/Index/index', []);
            }

            $routeModel->getRoutes(true);

            cmf_set_option('admin_settings', $adminSettings);

            $this->success("保存成功！", '');

        }
    }

    /**
     * 上传限制设置界面
     * @adminMenu(
     *     'name'   => '上传设置',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '上传设置',
     *     'param'  => ''
     * )
     */
    public function upload()
    {
        $uploadSetting = cmf_get_upload_setting();
        $this->assign('upload_setting', $uploadSetting);
        return $this->fetch();
    }

    /**
     * 上传限制设置界面提交
     * @adminMenu(
     *     'name'   => '上传设置提交',
     *     'parent' => 'upload',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '上传设置提交',
     *     'param'  => ''
     * )
     */
    public function uploadPost()
    {
        if ($this->request->isPost()) {
            //TODO 非空验证
            $uploadSetting = $this->request->post();

            cmf_set_option('upload_setting', $uploadSetting);
            $this->success('保存成功！');
        }

    }

    /**
     * 清除缓存
     * @adminMenu(
     *     'name'   => '清除缓存',
     *     'parent' => 'default',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '清除缓存',
     *     'param'  => ''
     * )
     */
    public function clearCache()
    {
        $content = hook_one('admin_setting_clear_cache_view');

        if (!empty($content)) {
            return $content;
        }

        cmf_clear_cache();
        //清楚其他sdk服务器上的文件缓存
        $sdkUrl = 'https://pay.sadgsdag.com/sdk/login_notify/clear_cache';
        $this->httpJsonPost($sdkUrl,[]);
        $sdkUrl = 'https://login.fsadgfad.com/sdk/login_notify/clear_cache';
        $this->httpJsonPost($sdkUrl,[]);
        return $this->fetch();
    }

    public function clear_redis()
    {
        $dbid = cmf_get_option('site_info')['dbid']?:10;
        $redis = Redis::getInstance(['host' => '127.0.0.1', 'port' => 6379], []);
        $key= $redis->keys('datareport*');
        $redis->del($key);
        $this->success('清除redis缓存成功');
    }

    /**
     * @系统设置
     *
     * @author: zsl
     * @since: 2021/4/21 20:16
     */
    public function system()
    {
        $data = cmf_get_option('system_set');
        $this -> assign('data', $data);
        $this -> assign("name", 'system_set');
        return $this -> fetch();
    }

    /**
     * 清除数据统计的缓存
     * created by wjd
     * 2021-5-18 17:15:13
     * 没用到 使用了上面的 clear_redis22()
     */

    public function delRedisHash()
    {
        $redis = Redis::getInstance(['host' => '127.0.0.1', 'port' => 6379], []);
        // 删除统计的数据
        $datearr = Db::name('date_list')->column('time');
        foreach ($datearr as $k => $v) {
            $key = 'datareporttoppid_' . $v;
            $redis->hDel($key);
            $key2 = 'datareporteverypid_'.$v;
            $redis->hDel($key);
            $redis->hDel($key2);
        }
    }

    public function clear_redis22(Request $request)
    {   
        $param = $request->param();
        $type = $param['type'] ?? 0;
        if($type == 1){
            // 执行删除
        }else{
            // 仅返回页面展示
        }
        // 删除redis数据
        $dbid = cmf_get_option('site_info')['dbid']?:10;
        $redis = Redis::getInstance(['host' => '127.0.0.1', 'port' => 6379], []);
        $key= $redis->keys('datareport*');
        $redis->del($key);

        // 删除统计表数据
        $lock_switch1 = Db::table('tab_datareport_help')->where(['id'=>1])->find();
        $lock_switch = $lock_switch1['lock_switch'] ?? 1; // 0 未上锁, 1 已经上锁
        if($lock_switch == 1){
            var_dump('已上锁,请稍后重试!!!');exit;
        }
        // 查出原本的统计数据表开始时间 结束时间
        $end_time1 = Db::table('tab_datareport_every_pid')->field('time')->order('id desc')->find();
        $end_date = $end_time1['time'];
        $start_time1 = Db::table('tab_datareport_every_pid')->field('time')->order('id asc')->find();
        $start_date = $start_time1['time'];

        $date_arr = Db::name('date_list')->where(['time' => ['between', [$start_date, $end_date]]])->order('time asc')->column('time');
        if (empty($date_arr)) {
            echo json_encode(['code'=>1,'msg'=>'无日期参数','data'=>[]], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $save_data = [];
        $d_time = time();
        foreach($date_arr as $k=>$v){
            $save_data[] = [
                'time_string'=>$v,
                'time_int'=>strtotime($v),
                'status'=>0,
                'create_time'=>$d_time,
                'update_time'=>$d_time,
            ];
        }
        $insert_all_res = Db::table('tab_datareport_help')->insertAll($save_data);
        if($insert_all_res > 0){
            Db::table('tab_datareport_help')->where(['id'=>1])->setField('lock_switch',1);
            $msg = '已插入'.$insert_all_res.'条数据等待执行; 注: 已经上锁,本次执行完之前不允许再次执行!';
        }else{
            $msg = '无需执行任何操作!';
        }
        // 删除两张表数据
        Db::table('tab_datareport_top_pid')->where("id > 0")->delete();
        Db::table('tab_datareport_every_pid')->where("id > 0")->delete();
        var_dump($msg);
        
        // exit;
        // var_dump($end_date);
        // var_dump($start_date);
        // var_dump($save_data);
        // var_dump($insert_all_res);
        exit;

        // $date_arr = Db::name('date_list')->where(['time' => ['between', [$start_date, $end_date]]])->order('time asc')->column('time');
        return $this->fetch();
    }

    /**
     * 生成统计数据 
     * copy 计划任务统计数据
     * modified by wjd
     * 2021-5-19 13:45:00
     */

    // public function create_statistic_data($start_time, $end_time)
    public function create_statistic_data(Output $output)
    {
        echo '暂时暂停统计!!';
        exit;
        set_time_limit(0);
        // 处理时间
        $start_date = '';
        $end_date = '';
        $datareport_help_info = Db::table('tab_datareport_help')->where(['status'=>0])->order('id asc')->limit(1)->select()->toArray();
        if(empty($datareport_help_info)){
            // 去除锁
            Db::table('tab_datareport_help')->where(['id'=>1])->setField('lock_switch',0);
            echo json_encode(['code'=>1,'msg'=>'已执行完毕!','data'=>[]], JSON_UNESCAPED_UNICODE);
            exit;
        }else{
            $start_date = $datareport_help_info[0]['time_string'];
            foreach($datareport_help_info as $tmp_val){
                $end_date = $tmp_val['time_string'];
            }
        }

        $event = new Summary();
        //时间范围
        $end_date = empty($end_date) ? $start_date : $end_date;
        $todaytime = strtotime(date("Y-m-d"));
        if (empty($start_date)) {
            $start_date = $end_date = date('Y-m-d', $todaytime - 86400);
        }
        $date_arr = Db::name('date_list')->where(['time' => ['between', [$start_date, $end_date]]])->order('time asc')->column('time');
        if (empty($date_arr)) {
            $output->newLine();
            $output->writeln('日期参数错误');
            return false;
        }

        //获取渠道数据
        $promote_data = Db::table('tab_promote')->order('id asc')->column('id');
        if (empty($promote_data)) {
            $promote_data = [];
        }
        array_unshift($promote_data, 0);//添加0的官方渠道
        $top_promote_data = Db::table('tab_promote')->where(['parent_id' => 0,'promote_level'=>1])->order('id asc')->column('id');
        if (empty($top_promote_data)) {
            $top_promote_data = [];
        }
        array_unshift($top_promote_data, 0);
        // 每日循环统计
        foreach ($date_arr as $dk => $dv) {
            $date = $dv;
            //今日及以后不可汇总
            if ($todaytime <= strtotime($date)) {
                $output->newLine();
                $output->writeln($date . "该日期不可汇总");
                continue;
            }
            $is_exist = Db::table('tab_datareport_every_pid')->field('id')->where(['time' => $date])->find();
            //判断是否重复汇总
            if (!empty($is_exist)) {
                $output->newLine();
                $output->writeln($date . "已汇总，不用重复汇总");
                continue;
            }
            // 汇总开始
            $output->newLine();
            $output->writeln($date . '汇总开始' . time());
            $data = [];
            foreach ($promote_data as $k => $v) {
                //每日汇总
                $basedata = $event->basedata_every_pid($v, $date);
                if (!empty($basedata)) {
                    $data[] = $basedata;
                }
            }
            $new_data = [];
            foreach ($data as $kk => $vv) {
                foreach ($vv as $k => $v) {
                    $redisdata['datareporteverypid_' . $date][$k] = $new_data[] = $v;
                }
            }
            //记录数据库
            $res = Db::table('tab_datareport_every_pid')->insertAll($new_data);
            $redis = Redis::getInstance(['host' => '127.0.0.1', 'port' => 6379], []);
            $redis->multi();
            foreach ($redisdata as $key => $value) {
                foreach ($value as $kk => $vv) {
                    $redis->hSet($key, $kk, json_encode($vv));
                }
            }
            $redis->exec();
            //上级渠道记录
            foreach ($top_promote_data as $k => $v) {
                //每日汇总
                $redistopdata = $topdata = $event->basedata_top_pid($v, $date);
                $topdata = array_values($topdata);
                if (empty($topdata)) {
                    continue;
                }
                //记录数据库
                $res = Db::table('tab_datareport_top_pid')->insertAll($topdata);
                $redis->multi();
                foreach ($redistopdata as $kk => $vv) {
                    $redis->hSet('datareporttoppid_' . $date, $kk, json_encode($vv));
                }
                $redis->exec();
            }
            $output->newLine();
            $output->writeln($date . '汇总结束' . time());
        }
        // 将操作过的数据状态标记为1
        $operate_nums = Db::table('tab_datareport_help')->where(['time_string' => ['between', [$start_date, $end_date]]])->setField('status',1);

        echo json_encode(['code'=>1,'msg'=>'汇总结束','data'=>[]], JSON_UNESCAPED_UNICODE);
        // $lasts_time = time() - $time111;
        // echo $lasts_time;
        // echo $j;
        exit;
    }



    function httpJsonPost($url, $paramArray){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($paramArray),
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/json"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return $err;
        }
        return $response;
    }

}
