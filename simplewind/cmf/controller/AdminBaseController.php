<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +---------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace cmf\controller;

use think\Db;
use Think\Think;

class AdminBaseController extends BaseController
{
    protected function initialize()
    {
        // 监听admin_init
        hook('admin_init');
        parent::initialize();
        //IP地址添加访问限制
        $config = cmf_get_option('admin_set');
        if(!empty($config['admin_allow_ip'])){
            $ips = explode(PHP_EOL ,$config['admin_allow_ip']);
            $ip = get_client_ip();
            foreach ($ips as $key => $value) {
                $ips[$key] = trim($value);
            }
            if($ips && !in_array($ip,$ips)){
                exit('IP地址访问受限，请联系管理处理！');
            }
        }
        // 验证 管理员登录单端登录
        $admin_id = session('ADMIN_ID');
        $flag_single_end_login_admin = 0; // 当前管理员是否开启单端登录 0 未开启, 1 已开启
        $safe_center_info = Db::table('tab_safe_center')->where(['id'=>3])->field('id,config,ids')->find();
        $safe_center_config = $safe_center_info['config'] ?? '';
        if(!empty($safe_center_config)){
            $safe_center_config_arr = json_decode($safe_center_config, true);
            $single_end_login_admin_switch = $safe_center_config_arr['single_end_login_admin']; // 0:关闭 1:开启
            // var_dump($single_end_login_admin_switch);
            if($single_end_login_admin_switch == 1){
                $forbid_admin_ids = json_decode($safe_center_info['ids'], true);
                if(in_array($admin_id, $forbid_admin_ids)){
                    $flag_single_end_login_admin = 1;
                }
            }
        }
        if($flag_single_end_login_admin == 1){
            $latest_session_id = Db::table('sys_user')->where(['id'=>$admin_id])->field('id,latest_session_id')->find()['latest_session_id'];
            $session_id = $this->getSessionId();
            if($session_id != $latest_session_id){
                $admin_id = session('ADMIN_ID', null);
                $this->error("您的登录已过期,请重新登录!", url("admin/public/login"));
                // echo 'not equal';
            }
        }

        //导出id为11的文档 (分发后台统计) 不验证管理后台是否登录
        if($this->request->param('id')!='11' && $this->request->action()!='get_user_age_result'){
            $session_admin_id = session('ADMIN_ID');
            if (!empty($session_admin_id)) {
                $user = Db::name('user')->where(['id' => $session_admin_id])->find();

                if (!$this->checkAccess($session_admin_id)) {
                    $this->error("您没有访问权限！");
                }
                $this->assign("admin", $user);
            } else {
                if ($this->request->isPost()) {
                    $this->error("您还没有登录！", url("admin/public/login"));
                } else {
                    return $this->redirect(url("admin/Public/login"));
                }
            }


        }

    }
    // 获取当前的session_id
    protected function getSessionId()
    {
        if (PHP_SESSION_ACTIVE != session_status()) {
            session_start();
        }
        return session_id();
    }
    public function _initializeView()
    {
        $cmfAdminThemePath    = config('template.cmf_admin_theme_path');
        $cmfAdminDefaultTheme = cmf_get_current_admin_theme();

        $themePath = "{$cmfAdminThemePath}{$cmfAdminDefaultTheme}";

        $root = cmf_get_root();

        //使cdn设置生效
        $cdnSettings = cmf_get_option('cdn_settings');
        if (empty($cdnSettings['cdn_static_root'])) {
            $viewReplaceStr = [
                '__ROOT__'     => $root,
                '__TMPL__'     => "{$root}/{$themePath}",
                '__STATIC__'   => "{$root}/static",
                '__WEB_ROOT__' => $root
            ];
        } else {
            $cdnStaticRoot  = rtrim($cdnSettings['cdn_static_root'], '/');
            $viewReplaceStr = [
                '__ROOT__'     => $root,
                '__TMPL__'     => "{$cdnStaticRoot}/{$themePath}",
                '__STATIC__'   => "{$cdnStaticRoot}/static",
                '__WEB_ROOT__' => $cdnStaticRoot
            ];
        }

        config('template.view_base', WEB_ROOT . "$themePath/");
        config('view_replace_str', $viewReplaceStr);
    }
//    public function _empty()
//    {
//        config('error_message','菜单不存在');
//        return $this->fetch();
//    }

        /**
     * 初始化后台菜单
     */
    public function initMenu()
    {
    }

    /**
     *  检查后台用户访问权限
     * @param int $userId 后台用户id
     * @return boolean 检查通过返回true
     */
    private function checkAccess($userId)
    {
        // 如果用户id是1，则无需判断
        if ($userId == 1) {
            return true;
        }

        $module     = $this->request->module();
        $controller = $this->request->controller();
        $action     = $this->request->action();
        $rule       = $module . $controller . $action;

        $notRequire = ["adminIndexindex", "adminMainindex"];
        if (!in_array($rule, $notRequire)) {
            return cmf_auth_check($userId);
        } else {
            return true;
        }
    }

    /**
     * [统一调用生成excel类]
     * @param $expTitle
     * @param $expCellName
     * @param $expTableData
     * @author 郭家屯[gjt]
     */
    public function exportExcel($expTitle, $expCellName, $expTableData)
    {
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        //        $fileName = session('user_auth.username').date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $fileName = $expTitle;
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        $objPHPExcel = new \PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');//合并单元格
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        // 金额小数点
        if($expTitle == '推广兑换'){
            $objPHPExcel->getActiveSheet()->getStyle ('B')->getNumberFormat()->setFormatCode ("0.00");
            $objPHPExcel->getActiveSheet()->getStyle ('D')->getNumberFormat()->setFormatCode ("0.00");
            $objPHPExcel->getActiveSheet()->getStyle ('F')->getNumberFormat()->setFormatCode ("0.00");
        }elseif($expTitle == '后台发放平台币（玩家）'){
            $objPHPExcel->getActiveSheet()->getStyle ('D')->getNumberFormat()->setFormatCode ("0.00");
        }elseif($expTitle == '未结算订单' || $expTitle == '已结算订单' || $expTitle == '不参与结算订单'){
            $objPHPExcel->getActiveSheet()->getStyle ('F')->getNumberFormat()->setFormatCode ("0.00");
            $objPHPExcel->getActiveSheet()->getStyle ('E')->getNumberFormat()->setFormatCode ("0.00");
            $objPHPExcel->getActiveSheet()->getStyle ('H')->getNumberFormat()->setFormatCode ("0.00");
        }elseif($expTitle == '推广提现'){
            $objPHPExcel->getActiveSheet()->getStyle ('B')->getNumberFormat()->setFormatCode ("0.00");
            $objPHPExcel->getActiveSheet()->getStyle ('C')->getNumberFormat()->setFormatCode ("0.00");
            $objPHPExcel->getActiveSheet()->getStyle ('D')->getNumberFormat()->setFormatCode ("0.00");
        }elseif($expTitle == '页游分包记录'){
            $objPHPExcel->getActiveSheet()->getStyle ('G')->getNumberFormat()->setFormatCode ("0.00");
            $objPHPExcel->getActiveSheet()->getStyle ('H')->getNumberFormat()->setFormatCode ("0.00");
        }


        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
        }
        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]]);
            }
        }
        ob_end_clean();//清除缓冲区,避免乱码
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
    //保存excel到本地
    private function sliceExcel($expTitle, $expCellName, $expTableData,$excel_i=0)
    {
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        //        $fileName = session('user_auth.username').date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $fileName = $expTitle;
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        $objPHPExcel = new \PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');//合并单元格
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
        }
        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]]);
            }
        }
        ob_end_clean();//清除缓冲区,避免乱码
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $file = WEB_ROOT.'upload/dataexcel/'.$fileName.$excel_i.'.xlsx';
        $objWriter->save($file);
        return $file;
    }

}
