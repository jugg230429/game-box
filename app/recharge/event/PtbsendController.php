<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\recharge\event;

use app\common\model\DateListModel;
use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use think\Request;
use think\Db;
use cmf\lib\Upload;

//该控制器必须以下3个权限
class PtbsendController extends AdminBaseController
{
    //收回单玩家
    function deduct_user_single($data)
    {
        $account = trim($data['account']);
        $amount = $data['num'];
        if ($account == "") {
            $this->error("请输入玩家账号！");
        }
        $u = get_user_info('id,account,nickname', ['account' => $account]);
        if (empty($u)) {
            $this->error('玩家不存在');
        }
        if ($amount == '') {
            $this->error("请输入发放数量！");
        }
        if (!is_numeric($amount) || $amount <= 0) {
            $this->error("发放数量不正确！");
        }
        $add['user_id'] = $u['id'];
        $add['user_account'] = $account;
        $add['op_id'] = cmf_get_current_admin_id();
        $add['op_account'] = get_admin_name($add['op_id']);
        $add['pay_order_number'] = sp_random_string(4) . build_order_no();
        $add['order_number'] = "PT_" . build_order_no();
        $add['amount'] = $amount;
        $add['status'] = 1;
        $add['type'] = 2;
        $add['create_time'] = time();
        Db::table('tab_spend_provide')->insert($add);
        $res2 = Db::table('tab_user')->where(['id' => $u['id']])->setDec('balance', $data['num']);
        $this->success("提交成功", url('ptbdeduct/lists'));

    }

    //发放单玩家
    function send_user_single($data)
    {
        $account = trim($data['account']);
        $amount = (int)$data['num'];
        if ($account == "") {
            $this->error("请输入玩家账号！");
        }
        $u = get_user_info('id,account,nickname', ['account' => $account]);
        if (empty($u)) {
            $this->error('玩家不存在');
        }
        if($data['coin_type'] == 1){
            if(empty($data['game_id'])){
                $this->error('请选择游戏');
            }
            $add['game_id'] = $data['game_id'];
            $add['game_name'] = get_game_name($data['game_id']);
            $add['coin_type'] = $data['coin_type'];
            $user_play = Db::table('tab_user_play')->field('id')->where('user_id',$u['id'])->where('game_id',$data['game_id'])->find();
            if(!$user_play){
                $this->error('用户未玩过该游戏');
            }
        }else{

            $quota = cmf_get_option('ptb_send_quota');
            if(!empty($quota['value'])){
                if($quota['value']<$amount){
                    $this->error('单笔发放金额超限，请重新输入');
                }
            }

        }
        if ($amount == '') {
            $this->error("请输入发放数量！");
        }
        if (!is_numeric($amount) || $amount <= 0) {
            $this->error("发放数量不正确！");
        }
        $add['user_id'] = $u['id'];
        $add['user_account'] = $account;
        $add['op_id'] = cmf_get_current_admin_id();
        $add['op_account'] = get_admin_name($add['op_id']);
        $add['pay_order_number'] = sp_random_string(4) . build_order_no();
        $add['order_number'] = "PT_" . build_order_no();
        $add['amount'] = $amount;
        $add['status'] = 0;
        $add['create_time'] = time();
        Db::table('tab_spend_provide')->insert($add);
        if($data['coin_type'] == 1){
            $this->success("提交成功", url('bindspend/senduserlists'));
        }else{
            $this->success("提交成功", url('ptbspend/senduserlists'));
        }


    }

    // 批量发放 yyh
    function send_user_more($data)
    {
        $account = $data['accounts'];
        $amount = $data['nums'];
        if (empty($account)) {
            $this->error("请输入玩家账号");
        }
        if ($amount == '') {
            $this->error("请输入发放数量！");
        }
        if ($amount <= 0||!preg_match("/^[1-9][0-9]*$/" ,$amount)) {
            $this->error("发放数量不正确！");
        }
        if($data['coin_type'] == 1){
            if(empty($data['game_id'])){
                $this->error('请选择游戏');
            }

        }else{

            $quota = cmf_get_option('ptb_send_quota');
            if (!empty($quota['value'])) {
                if ($quota['value'] < $amount) {
                    $this -> error('单笔发放金额超限，请重新输入');
                }
            }

        }
        $namearr = explode("\n", $account);
        $successlist = [];
        for ($i = 0; $i < count($namearr); $i++) {
            $acc = str_replace(array("\r\n", "\r", "\n"), "", $namearr[$i]);
            $user = get_user_info('id,account,nickname', ['account' => $acc]);
            if (!empty($user)) {
                if($data['coin_type'] == 1){
                    $user_play = Db::table('tab_user_play')->field('id')->where('user_id',$user['id'])->where('game_id',$data['game_id'])->find();
                    if(!$user_play){
                        $this->error($acc.'用户未玩过该游戏');
                    }
                    $add['game_id'] = $data['game_id'];
                    $add['game_name'] = get_game_name($data['game_id']);
                    $add['coin_type'] = $data['coin_type'];
                }
                $add['user_account'] = $acc;
                $add['user_id'] = $user['id'];
                $add['pay_order_number'] = sp_random_string(4) . build_order_no();
                $add['order_number'] = "PT_" . build_order_no();
                $add['amount'] = $amount;
                $add['status'] = 0;
                $add['op_id'] = cmf_get_current_admin_id();
                $add['op_account'] = get_admin_name($add['op_id']);
                $add['create_time'] = time();
                $successlist[] = $add;
            }else{
               $this->error($acc.'用户不存在');
            }
        }
        $result = Db::table('tab_spend_provide')->insertAll($successlist);
        if($result){
            if($data['coin_type'] == 1){
                $url = url('bindspend/senduserlists');
            }else{
                $url = url('ptbspend/senduserlists');
            }
            $this->success('提交成功',$url);
        }else{
            $this->error('提交失败');
        }
    }


    //excle发放
    public function send_user_excle($data)
    {

        header("Content-Type:text/html;charset=utf-8");
        $file = request()->file('excelData');
        if (empty($file)) $this->error('请上传文件');
        $upload = new Upload();
        $upload->setFormName('excelData');
        $upload->setApp('recharge');// 充值模块excelData
        // 上传文件
        $info = $upload->upload();
        if (!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        } else {// 上传成功
            if($data['coin_type'] == 1){
                $this->charge_import_bind('./upload/' . $info['filepath'], $info['exts']);
            }else{
                $this->charge_import('./upload/' . $info['filepath'], $info['exts']);
            }

        }
    }

    //导入数据方法
    protected function charge_import($filename, $exts = 'xls')
    {
        //创建PHPExcel对象，注意，不能少了\
        $PHPExcel = new \PHPExcel();
        //如果excel文件后缀名为.xls，导入这个类
        if ($exts == 'xls') {
            //import("Org.Util.PHPExcel.Reader.Excel5");
            $PHPReader = new \PHPExcel_Reader_Excel5();
        } else if ($exts == 'xlsx') {
            //import("Org.Util.PHPExcel.Reader.Excel2007");
            $PHPReader = new \PHPExcel_Reader_Excel2007();
        }
        //载入文件
        $PHPExcel = $PHPReader->load($filename);
        //获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
        $currentSheet = $PHPExcel->getSheet(0);
        //获取总列数
        $allColumn = $currentSheet->getHighestColumn();
        //获取总行数
        $allRow = $currentSheet->getHighestRow();
        //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
        for ($currentRow = 1; $currentRow <= $allRow; $currentRow++) {
            //从哪列开始，A表示第一列
            for ($currentColumn = 'A'; $currentColumn <= $allColumn; $currentColumn++) {
                //数据坐标
                $address = $currentColumn . $currentRow;
                //读取到的数据，保存到数组$arr中
                $data[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();
            }

        }
        $this->save_import($data);
    }

    //保存导入数据并返回错误信息
    public function save_import($data)
    {
        unset($data[1]);
        $succNum = 0;
        $errorList = '';//存储错误数据;
        $lists = array();
        foreach ($data as $kk=>$vv){
            if(empty($vv['A'])&&empty($vv['B'])){
                unset($data[$kk]);
            }
        }
        if(empty($data)){
            $this->error('模板无用户数据');
        }
        foreach ($data as $k => $v) {
            $errorNum = 0;
            if (empty($v['A'])) {//用户名为空
                $errorList=$errorList."用户名=' '数据错误</br>";
                $errorNum++;
            }
            $u = get_user_info('id,account,nickname', ['account' => $v['A']]);
            if (empty($u)) {//用户名不存在
                $errorList=$errorList.'用户名='.$v['A']."数据错误</br>";
                $errorNum++;
            }
            if ($v['B'] <= 0||!preg_match("/^[1-9][0-9]*$/" ,$v['B'])) {//金额有问题
                if($v['B']==0){
                    $errorList=$errorList."金额=' '数据错误</br>";
                }else{
                    $errorList=$errorList.'金额='.$v['B']."数据错误</br>";
                }
                $errorNum++;
            }

            $quota = cmf_get_option('ptb_send_quota');
            if (!empty($quota['value'])) {
                if ($quota['value'] < $v['B']) {
                    $errorList = $errorList . "单笔发放金额超限，请重新输入</br>";
                    $errorNum ++;
                }
            }

            if($errorNum){
                continue;//显示出表格所有错误
            }
            $succNum++;
            $arr['user_id'] = $u['id'];
            $arr['user_account'] = $u['account'];
            $arr['op_id'] = cmf_get_current_admin_id();
            $arr['op_account'] = get_admin_name($arr['op_id']);
            $arr['pay_order_number'] = sp_random_string(4) . build_order_no();
            $arr['order_number'] = "PT_" . build_order_no();
            $arr['amount'] = (double)$v['B'];
            $arr['status'] = 0;
            $arr['create_time'] = time();
            $lists[] = $arr;
        }
        Db::startTrans();
        $result = Db::table('tab_spend_provide')->insertAll($lists);
        if($result===false){
            // 回滚事务
            Db::rollback();
        }else{
            Db::commit();
        }
        if(!$errorList){
            $errorList = '发放成功';
            $this->success($errorList, url('ptbspend/senduserlists'));
        }else{
            $this->error($errorList);
        }
    }

    //导入数据方法
    protected function charge_import_bind($filename, $exts = 'xls')
    {
        //创建PHPExcel对象，注意，不能少了\
        $PHPExcel = new \PHPExcel();
        //如果excel文件后缀名为.xls，导入这个类
        if ($exts == 'xls') {
            //import("Org.Util.PHPExcel.Reader.Excel5");
            $PHPReader = new \PHPExcel_Reader_Excel5();
        } else if ($exts == 'xlsx') {
            //import("Org.Util.PHPExcel.Reader.Excel2007");
            $PHPReader = new \PHPExcel_Reader_Excel2007();
        }
        //载入文件
        $PHPExcel = $PHPReader->load($filename);
        //获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
        $currentSheet = $PHPExcel->getSheet(0);
        //获取总列数
        $allColumn = $currentSheet->getHighestColumn();
        //获取总行数
        $allRow = $currentSheet->getHighestRow();
        //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
        for ($currentRow = 1; $currentRow <= $allRow; $currentRow++) {
            //从哪列开始，A表示第一列
            for ($currentColumn = 'A'; $currentColumn <= $allColumn; $currentColumn++) {
                //数据坐标
                $address = $currentColumn . $currentRow;
                //读取到的数据，保存到数组$arr中
                $data[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();
            }

        }
        $this->save_import_bind($data);
    }

    //保存导入数据并返回错误信息
    public function save_import_bind($data)
    {
        unset($data[1]);
        $lists = array();
        foreach ($data as $kk=>$vv){
            if(empty($vv['A'])&&empty($vv['B'])&&empty($vv['C'])){
                unset($data[$kk]);
            }
        }
        if(empty($data)){
            $this->error('模板无用户数据');
        }
        foreach ($data as $k => $v) {
            if (empty($v['A'])) {//用户名为空
                $this->error('用户名为空，数据错误');
            }
            $u = get_user_info('id,account,nickname', ['account' => $v['A']]);
            if (empty($u)) {//用户名不存在
                $this->error('用户名='.$v['A']."用户不存在数据错误");
            }
            if(empty($v['B'])){
                $this->error('游戏为空，数据错误');
            }
            $g = get_game_entity($v['B'],'id,game_name');
            $user_play = Db::table('tab_user_play')->field('id')->where('user_id',$u['id'])->where('game_id',$v['B'])->find();
            if(empty($user_play)){
                $this->error('用户名='.$v['A']."未玩过".$g['game_name']."游戏数据错误");
            }
            if(empty($g)){
                $this->error('游戏ID='.$v['B']."游戏不存在数据错误");
            }
            if ($v['C'] <= 0||!preg_match("/^[1-9][0-9]*$/" ,$v['C'])) {//金额有问题
                $this->error('金额='.$v['C']."数据错误");
            }
            $arr['user_id'] = $u['id'];
            $arr['user_account'] = $u['account'];
            $arr['op_id'] = cmf_get_current_admin_id();
            $arr['op_account'] = get_admin_name($arr['op_id']);
            $arr['pay_order_number'] = sp_random_string(4) . build_order_no();
            $arr['order_number'] = "PT_" . build_order_no();
            $arr['amount'] = (double)$v['C'];
            $arr['coin_type'] = 1;
            $arr['game_id'] = $v['B'];
            $arr['game_name'] = $g['game_name'];
            $arr['status'] = 0;
            $arr['create_time'] = time();
            $lists[] = $arr;
        }
        Db::startTrans();
        $result = Db::table('tab_spend_provide')->insertAll($lists);
        if($result===false){
            // 回滚事务
            Db::rollback();
            $this->error('提交失败');
        }else{
            Db::commit();
        }
        $this->success('提交成功', url('bindspend/senduserlists'));
    }
}
