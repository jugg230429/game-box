<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\channelsite\controller;

use app\channelsite\logic\WelfareLogic;
use app\common\controller\BaseHomeController;
use app\common\logic\PromoteLogic;
use app\game\model\GameAttrModel;
use app\game\model\GameModel;
use app\portal\service\ApiService;
use app\promote\logic\PromoteLevelLogic;
use app\promote\model\PromoteapplyModel;
use app\promote\model\PromoteunionModel;
use app\promote\model\PromoteUserBindDiscountModel;
use app\promote\model\PromoteUserWelfareModel;
use cmf\controller\HomeBaseController;
use think\Db;
use think\Request;
use app\promote\validate\PromoteValidate;
use app\promote\model\PromotedepositModel;
use app\promote\model\PromotebindModel;
use app\member\model\UserPlayModel;
use app\promote\model\PromotecoinModel;
use app\promote\model\PromoteModel;
use app\site\service\PostService;
use app\datareport\event\PromoteController as Promote;
use app\game\model\ServerModel;
use app\promote\model\PromotePrepaymentDeductRecordModel;
use app\promote\model\PromotePrepaymentRechargeModel;
use app\site\model\PortalPostModel;
use cmf\paginator\Bootstrap;

class PromoteController extends BaseController
{
    // 后台首页
    public function index()
    {
        // // 新闻公告
        $this->article();
        // //运营数据
        $this->promotedata();
        // //昨日图表详情(今日数据)
        $this->yesterdaytab();
        // //合作规则说明
        $this->cooperationRules();

        // 弹窗显示资讯
        $this->getFirstArticle();

        // 渠道等级
        $this->getMyLevel();

        return $this->fetch();
    }

    /**
     * 获取渠道当前等级
     *
     * @author: Juncl
     * @time: 2021/09/11 15:51
     */
    private function getMyLevel()
    {
       $PromoteLevel = new PromoteLevelLogic();
       $myLevel = $PromoteLevel->getPromoteLevel(PID);
       if(empty($myLevel)){
           $myLevel['level'] = 0;
           $myLevel['level_name'] = '暂无';
       }
       $levels = cmf_get_option('promote_level_set');
       $levelCount = count($levels);
       if(!empty($levels)){
           $GameAttrModel = new GameAttrModel();
           foreach ($levels as $key => $val)
           {
               $count = $GameAttrModel->where('promote_level_limit','elt',$val['level'])->count('game_id');
               $levels[$key]['game_count'] = $count;
               if($key == ($levelCount -1)){
                   $levels[$key]['sum_moneys'] = $val['sum_money'] . '及以上';
               }else{
                   $levels[$key]['sum_moneys'] = $val['sum_money'] . '~' . ($levels[$key+1]['sum_money']-1);
               }
           }
       }
       $this->assign('levels',$levels);
       $this->assign('myLevel',$myLevel);
    }

    /*
        开服预告
        created by wjd
        2021-5-14 20:12:09
    */
    // myserverinfo
    public function myserver_foreshow(Request $request)
    {
        $promote_id = PID;
        $param = $request->param();
        $serverModel = new ServerModel();
        $map = [];
        $type = $param['type'] ?? 0;
        $d_time_int = time();
        $today_time_int = strtotime(date('Y-m-d'));
        $order="start_time asc";
        if ($type == 1) {
            // 新服预告
            $map['start_time'] = ['between', [$today_time_int + 86400, $today_time_int + 86400 * 8 - 1]];  // 后七日开服信息
            $order = "start_time asc";
        } else {
            //今日开服
            $map['start_time'] = ['between', [$today_time_int, $today_time_int + 86399]];
        }
        $server_id = (int) $param['server_id'] ?? 0;
        if($server_id > 0){
            $map['s.id'] = $server_id;
        }

        $row = $param['row'] ?? 10;
        // 获取渠道申请通过的游戏得到游戏id
        $already_applied_game_id = get_promote_apply_game_id($promote_id);
        $map['game_id'] = ['in', $already_applied_game_id];

        // 渠道独占------------START
        $onlyForPromote = game_only_for_promote($promote_id);
        $allowGameids = $onlyForPromote['allow_game_ids'];
        // 合并游戏id
        $tmp_game_id1 = array_merge($already_applied_game_id, $allowGameids);
        $map['game_id'] = ['in', $tmp_game_id1];

        // 渠道独占------------END

        $server_info = $serverModel->getserver_page($map,$row,$order);
        // var_dump($map);
        // return json($server_info);
        $page = $server_info->render();
        $this->assign("data_lists", $server_info);
        $this->assign("page", $page);

        $this->assign('promote_id', $promote_id);
        return $this->fetch();
    }

    // 根据游戏名称获取区服列表

    public function get_server(Request $request)
    {
        $game_id = $request->param('game_id');
        $result = ['code' => 1, 'msg' => '', 'data' => []];
        if (empty($game_id)) {
            $result['code'] = 0;
            $result['msg'] = '参数错误';
            return $result;
        }
        $where = [];
        $where['game_id'] = $game_id;
        $lists = Db ::table('tab_game_server') -> field('id,server_name,server_num') -> where($where) -> order('id desc') -> select();
        if (!empty($lists)) {
            $result['data'] = $lists;
        }
        // return $result;
        return json($result);
    }

    private function cooperationRules()
    {
        $articleapi = new ApiService;
        $param['where']['post.id'] = 17;
        $articles = $articleapi -> articles($param);
        $cooperation = empty($articles) ? [] : $articles['articles'] -> toarray();
        $this -> assign('cooperation', $cooperation[0]);
        $this -> assign('promote_id', PID);
    }

    private function article()
    {
        $postService = new PostService();
        $map['category'] = '2,4';
        $map['post_status'] = 1;
        $articles = $postService->ArticleList($map, false, 6);
        $this->assign('articles', $articles);
    }

    private function promotedata()
    {
        $promoteevent = new Promote();
        $endtime = date('Y-m-d');

        $starttime1 = date('Y-m-d', strtotime(date('Y-m-d')) - 1 * 24 * 3600);
        $starttime7 = date('Y-m-d', strtotime(date('Y-m-d')) - 7 * 24 * 3600);
        $starttime30 = date('Y-m-d', strtotime(date('Y-m-d')) - 30 * 24 * 3600);

        if(PID_LEVEL == 1){
            //今日
            $new_data = $promoteevent->promote_base(date('Y-m-d'), date('Y-m-d'), PID); // 原
            // var_dump($new_data);exit;
            // $new_data = $promoteevent->promote_base(date('Y-m-d'), $endtime22, PID);
            $data[0] = $new_data[PID];
            //昨日
            $new_data = $promoteevent->promote_base($starttime1, $starttime1, PID);
            $data[1] = $new_data[PID];
            //7天
            $new_data = $promoteevent->promote_base($starttime7, $endtime, PID);
            $data[7] = $new_data[PID];
            //30天
            $new_data = $promoteevent->promote_base($starttime30, $endtime, PID);
            $data[30] = $new_data[PID];
            //all
            $new_data = $promoteevent->promote_base('2019-01-01', $endtime, PID);
            $data['all'] = $new_data[PID];
        }else{
            //今日
            $sonids = array_column(get_song_promote_lists(PID,2),'id');
            $son_data = [];
            $new_data = $promoteevent->promote_data(date('Y-m-d'), date('Y-m-d'), [PID])['data'];
            if(!empty($sonids)){
                $son_data = $promoteevent->promote_data(date('Y-m-d'), date('Y-m-d'), $sonids)['data'];
            }
            $new_data = $this->son_data_merge(reset($new_data),$son_data);
            $data[0] = $new_data;
            //昨日
            $sonids = array_column(get_song_promote_lists(PID,2),'id');
            $son_data = [];
            $new_data = $promoteevent->promote_data($starttime1, $starttime1, [PID])['data'];
            if(!empty($sonids)){
                $son_data = $promoteevent->promote_data($starttime1, $starttime1, $sonids)['data'];
            }
            $new_data = $this->son_data_merge(reset($new_data),$son_data);
            $data[1] = $new_data;
            //7天
            $new_data = $promoteevent->promote_data($starttime7, $endtime, [PID])['data'];
            if(!empty($sonids)){
                $son_data = $promoteevent->promote_data($starttime7, $endtime, $sonids)['data'];
            }
            $new_data = $this->son_data_merge(reset($new_data),$son_data);
            $data[7] = $new_data;
            //30天
            $new_data = $promoteevent->promote_data($starttime30, $endtime, [PID])['data'];
            if(!empty($sonids)){
                $son_data = $promoteevent->promote_data($starttime30, $endtime, $sonids)['data'];
            }
            $new_data = $this->son_data_merge(reset($new_data),$son_data);
            $data[30] = $new_data;
            //all
            $new_data = $promoteevent->promote_data('2019-01-01', $endtime, [PID])['data'];
            if(!empty($sonids)){
                $son_data = $promoteevent->promote_data('2019-01-01', $endtime, $sonids)['data'];
            }
            $new_data = $this->son_data_merge(reset($new_data),$son_data);
            $data['all'] = $new_data;
        }
        // var_dump($data);exit;
        $this->assign('operat_data', $data);
    }
    private function son_data_merge($parent_data,$son_data){
        if(empty($son_data)){
            return $parent_data;
        }
        foreach ($parent_data as $llk=>$llv){
            if(in_array($llk,['new_register_user','active_user','pay_user','new_pay_user','total_pay'])){
                if($llk == 'total_pay'){
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
    private function yesterdaytab()
    {
        $databaseevent = new \app\datareport\event\DatabaseController();
        $data = $databaseevent->date_detail(date("Y-m-d"), PID, 1);
        // var_dump($data);exit;
        for ($i = 1; $i <= 24; $i++) {
            if ($i < 10) {
                $keys[] = '0' . $i . ':00';
            } else {
                $keys[] = $i . ':00';
            }
        }
        // 单独计算出今日付费用户数 -- by wjd---------------START
        $paycount22 = 0;
        foreach($data as $v2){
            $paycount22 += $v2['paycount'];

        }
        // 单独计算出今日付费用户数 -- by wjd---------------END

        $this -> assign('keys', json_encode($keys));
        $this -> assign('usercount', json_encode(fix_number_precision(array_column($data, 'usercount'))));
        $this -> assign('activecount', json_encode(fix_number_precision(array_column($data, 'activecount'))));
        $this -> assign('paycount', json_encode(fix_number_precision(array_column($data, 'paycount'))));
        $this -> assign('count_pay_user22', $paycount22);  // 新增
        $this -> assign('paytotal', json_encode(fix_number_precision(array_column($data, 'paytotal'))));
    }

    // 账户信息
    public function base_info()
    {
        if (request()->isPost()) {
            $data = $this->request->param();
            $data['id'] = PID;
            switch ($data['tab']) {
                case 1:
                    if(strlen($data['mobile_phone']) != 11){
                        $this->error('请输入11位手机号码');
                    }
                    break;
                case 2:
                    $data['settment_type'] = empty($data['settment_type'])?0:1; // 0 银行卡 1 支付宝
                    if($data['settment_type'] == 1){
                        $data['tab'] = 5;
                    }
                    // var_dump($data);exit;
                    if(empty($data['settment_type'])){
                        if(empty($data['bank_card'])){
                            $this->error('银行卡号不能为空');
                        }
                        $data['bank_area'] = $data['province'] . ',' . $data['city'] . ',' . $data['county'];
                        unset($data['province'], $data['city'], $data['county']);
                        if ($data['bank_area'] == '省,市,区/县') {
                            $data['bank_area'] = '';
                        }
                        if(strlen($data['bank_phone']) != 11){
                            $this->error('请输入11位手机号码');
                        }
                        $data['bank_card'] = intval($data['bank_card']);
                    }

                    break;
                case 3:
                    if (!$data['old_password']) {
                        $this->error('原密码不能为空');
                    } else {
                        $password = Db::table('tab_promote')->field('password')->find($data['id']);
                        $compare_password = xigu_compare_password($data['old_password'], $password['password']);
                        if (!$compare_password) {
                            $this->error('原密码错误');
                        }
                    }
                    break;
                case 4:
                    $second_pwd = Db::table('tab_promote')->where(['id'=>$data['id']])->value('second_pwd');
                    if(!empty($second_pwd)){
                        if(empty($data['old_second_pwd'])){
                            $this->error('原密码不能为空');
                        }
                        $compare_second_pwd = xigu_compare_password($data['old_second_pwd'], $second_pwd);
                        if (!$compare_second_pwd) {
                            $this->error('原二级密码错误');
                        }
                    }
                    break;
            }
            $rule_info = $this->get_rule($data);
            $validate = new PromoteValidate($rule_info['rule'], $rule_info['msg']);
            if (!$validate->scene('proinfo' . $data['tab'])->check($data)) {
                $this->error($validate->getError());
            }
            unset($data['tab']);
            if (!empty($data['password'])) {
                $data['password'] = cmf_password($data['password']);
                unset($data['old_password']);
                unset($data['password_confirm']);
            }
            if (!empty($data['second_pwd'])) {
                $data['second_pwd'] = cmf_password($data['second_pwd']);
                unset($data['second_pwd_confirm']);
            }


            $saveData = [];
            $saveData['id'] = PID;
            if($this->request->param('tab')=='1'){
                $saveData['real_name'] = $data['real_name'];
                $saveData['mobile_phone'] = $data['mobile_phone'];
                $saveData['email'] = $data['email'];
            }elseif($this->request->param('tab')=='2'){
                $saveData['settment_type'] = $data['settment_type'];
                //支付宝
                $saveData['alipay_account'] = $data['alipay_account'];
                $saveData['alipay_name'] = $data['alipay_name'];
                //银行卡
                $saveData['bank_phone'] = $data['bank_phone'];
                $saveData['bank_card'] = $data['bank_card'];
                $saveData['bank_name'] = $data['bank_name'];
                $saveData['bank_account'] = $data['bank_account'];
                $saveData['bank_area'] = $data['bank_area'];
                $saveData['account_openin'] = $data['account_openin'];
            }elseif($this->request->param('tab')=='3'){
                $saveData['password'] = $data['password'];
            }else{
                $saveData['second_pwd'] = $data['second_pwd'];
            }
            $res = Db::table('tab_promote')->field(true)->update($saveData);


            if ($res !== false) {
                $this->success('修改成功');
            } else {
                $this->error('修改失败');
            }
        } else {
            $data = $this->promote;
            $data['bank_area'] = explode(',', $data['bank_area']);
            $this->assign('data', $data);
            return $this->fetch();
        }
    }


    private function get_rule($data=[]){
        switch ($data['tab']) {
            case 1:
                $rule = [
                        'real_name' => 'require|checkChineseName',
                        'mobile_phone' => 'require|isMobileNO',
                        'email' => 'unique'
                ];
                $msg = [
                        'real_name.require' => '姓名不能为空',
                        'mobile_phone.require' => '手机号不能为空',
                        'mobile_phone.isMobileNO' => '请输入正确的手机号',
                        'email.require' => '邮箱地址不能为空',
                        'email.email' => '邮箱地址错误',
                        'email.unique' => '邮箱地址已被使用',
                ];
                break;
            case 2:
                $rule = [
                        'bank_phone' => 'require|length:11|isMobileNO',
                        'bank_card' => 'require|number|length:10,19',
                        'bank_name' => 'require',
                        'bank_account' => 'require|checkChineseName',
                        'account_openin' => 'require|min:2',
                ];
                $msg = [
                        'bank_phone.require' => '请输入正确手机号',
                        'bank_phone.length' => '请输入11位手机号码',
                        'bank_phone.isMobileNO' => '请输入正确手机号',
                        'bank_card.require' => '银行卡号不能为空',
                        'bank_card.number' => '银行卡号格式错误',
                        'bank_card.length' => '银行卡号格式错误',
                        'bank_name.require' => '收款银行不能为空',
                        'bank_account.require' => '持卡人不能为空',
                        'bank_account.checkChineseName' => '姓名长度需要在2-25个字符之间',
                        'account_openin.require' => '开户网点不能为空',
                        'account_openin.min' => '开户网点错误',
                        'account_openin.checkChineseName' => '开户网点错误'
                ];
                break;
            case 3:
                $rule = [
                        'old_password' => 'require',
                        'password' => 'require|min:6|max:30|confirm|regex:^[A-Za-z0-9]+$',
                ];
                $msg = [
                        'old_password.require' => '原密码不能为空',
                        'password.require' => '新密码不能为空',
                        'password.confirm' => '两次输入的密码不一致',
                ];
                break;
            case 4:
                $rule = [
                        'second_pwd' => 'require|min:6|max:30|confirm|regex:^[A-Za-z0-9]+$',
                ];
                $msg = [
                        'second_pwd.require' => '新密码不能为空',
                        'second_pwd.confirm' => '两次输入的密码不一致',
                ];
                break;
            case 5:
                $rule = [
                        'alipay_account' => 'require',
                        'alipay_name' => 'require|min:2|max:25|checkChineseName',
                ];
                $msg = [
                        'alipay_account.require' => '请输入支付宝账号',
                        'alipay_name.require' => '请输入支付宝姓名',
                        'alipay_name.min' => '姓名长度需要在2-25个字符之间',
                        'alipay_name.max' => '姓名长度需要在2-25个字符之间',
                        'alipay_name.checkChineseName' => '姓名格式错误',
                ];
                break;
            default:
                $this->error('数据错误');
                break;
        }
        $return['rule'] = $rule;
        $return['msg'] = $msg;
        return $return;
    }

    // 账户余额
    public function balance()
    {
        if (AUTH_PAY != 1) {
            $this->redirect('shift');
        }
        $paytype = Db::table('tab_spend_payconfig')->field('name')->where(['status' => 1])->select()->toarray();
        $this->assign('balance', null_to_0($this->promote['balance_coin']));
        $this->assign('paytype', $paytype);
        return $this->fetch();
    }
    //检查平台币充值结果
    //yyh
    public function check_order_status()
    {
        $order_no = $this->request->param('order_no');
        if (empty($order_no)) {
            $this->error('无订单数据');
        }
        $res = Db::table('tab_promote_deposit')->field('pay_order_number,pay_status,promote_id,to_id,pay_amount,pay_way,create_time')->where(['promote_id' => PID, 'pay_order_number' => $order_no])->find();
        if (request()->isAjax()) {
            if ($res['pay_status'] == 1) {
                $this->success('充值成功', cmf_get_domain() . '/channelsite/Promote/check_order_status/order_no/' . $order_no);
            } else {
                $this->error('未充值成功');
            }
        } else {
            $this->assign('data', $res);
            if (empty($res)) {
                $this->error('数据错误');
            } elseif ($res['pay_status'] == 1) {
                return $this->fetch('pay_success');
            } else {
                return $this->fetch('pay_failure');
            }
        }
    }
    //检查代充平台币充值结果
    //yyh
    public function check_bind_order_status()
    {
        $order_no = $this->request->param('order_no');
        if (empty($order_no)) {
            $this->error('无订单数据');
        }
        $res = Db::table('tab_promote_bind')->field('pay_order_number,user_account,game_name,pay_status,pay_amount,pay_way,1 as is_bind_pay,cost')->where(['promote_id' => PID, 'pay_order_number' => $order_no])->find();
        if (request()->isAjax()) {
            if ($res['pay_status'] == 1) {
                $this->success('充值成功', cmf_get_domain() . '/channelsite/Promote/check_bind_order_status/order_no/' . $order_no);
            } else {
                $this->error('未充值成功');
            }
        } else {
            $this->assign('data', $res);
            if (empty($res)) {
                $this->error('数据错误');
            } elseif ($res['pay_status'] == 1) {
                return $this->fetch('pay_success');
            } else {
                return $this->fetch('pay_failure');
            }
        }
    }

    //检测账号是否存在
    public function checkAccount()
    {
        $data = $this->request->param();
        if (empty($data['account'])) {
            $this->error('请输入账号');
        } else {
            $res = Db::table('tab_promote')->field('id,parent_id,status')->where(['account' => $data['account']])->find();
            if ($data['validate'] == 1) {//js验证 存在为true
                if (empty($res)) {
                    return false;
                } elseif ($res['status'] != 1) {
                    return false;
                } else {
                    return true;
                }
            }
            if ($data['validate'] == 2) {//js验证 不存在为true
                if (empty($res)) {
                    return true;
                } else {
                    return false;
                }
            }
            if (empty($res)) {
                $this->error('账号不存在');
            } else {
                echo json_encode(['code' => 1, 'msg' => '账号存在', 'data' => $res]);
            }
        }
    }

    // 给下级充值
    public function shift()
    {
        if(PID_LEVEL == 3){
            $this->redirect('balance');
        }
        $map['parent_id'] = PID;
        $pdata = Db::table('tab_promote')->field('id,account,balance_coin')->where($map)->select();
        if (request()->isPost()) {
            $data = $this->request->param();
            if (!$data['promotezi']) {
                $this->error('请选择子渠道');
            }
            if (!preg_match('/^[1-9]\d*$/', $data['amount'])) {
                $this->error('金额错误，请输入正整数');
            }
            //验证二级密码
            if(empty($data['second_pwd'])){
                $this->error('请输入二级密码');
            }
            if(empty($this->promote['second_pwd'])){
                $this->error('请先设置二级密码');
            }
            if (!xigu_compare_password($data['second_pwd'], $this->promote['second_pwd'])) {
                $this->error('二级密码错误');
            }
            if ($this->promote['balance_coin'] < $data['amount']) {
                $this->error('账户余额不足');
            }
            $model = new PromotecoinModel();
            $data['pid'] = PID;
            $result = $model->transfer($data);
            if($result){
                $this->success('转移成功');
            }else{
                $this->error('转移失败');
            }
        }
        $this->assign('pdata', $pdata);
        return $this->fetch();
    }

    // 平台币记录
    public function record()
    {
        $dpmodel = new PromotedepositModel();
        $czmap['pay_status'] = 1;
        $czmap['to_id'] = PID;
        $cz = $dpmodel->lists($czmap)->toarray();//平台币充值
        foreach ($cz as $key=>$v){
            if($v['promote_id'] == $v['to_id']){
                $cz[$key]['promote_id'] = '自己';
                $cz[$key]['type'] = 1;
            }else{
                $cz[$key]['to_id'] = $v['promote_id'];
                $cz[$key]['type'] = 2;
            }
        }
        //平台币转移扣除
        $zymodel = new PromotecoinModel();
        $zymap['promote_id'] = PID;
        $zymap['type'] = 1;
        $zy = $zymodel->lists($zymap, 'promote_id,source_id as to_id,"--" as pay_order_number,num as pay_amount,"--" as pay_way, create_time, 1 as ptbtype')->toarray();
        //平台币转移获得
        $zymodel = new PromotecoinModel();
        $zymap['promote_id'] = PID;
        $zymap['type'] = 2;
        $zyj = $zymodel->lists($zymap, 'promote_id,source_id as to_id,"--" as pay_order_number,num as pay_amount,"--" as pay_way, create_time, 2 as ptbtype')->toarray();

        //后台发放
        $pcmodel = new \app\recharge\model\SpendpromotecoinModel;
        $pcmap['promote_id'] = PID;
        $pcmap['type'] = 1;
        $pc = $pcmodel->lists($pcmap, '"自己" as promote_id,"自己" as to_id,"--" as pay_order_number,num as pay_amount,"--" as pay_way, create_time, 2 as ptbtype')->toarray();

        //后台回收
        $hsmap['promote_id'] = PID;
        $hsmap['type'] = 2;
        $hs = $pcmodel->lists($hsmap, '"自己" as promote_id,"自己" as to_id,"--" as pay_order_number,num as pay_amount,"--" as pay_way, create_time, 3 as ptbtype')->toarray();
        $type = $this->request->param('type');

        switch ($type){
            case "5"://回收
                $alldata = $data = my_sort(array_merge($hs), 'create_time', SORT_DESC);
                break;
            case "3"://转移
                $alldata = $data = my_sort(array_merge($zy,$zyj), 'create_time', SORT_DESC);
                break;
            case "1"://充值渠道
                $alldata = $data = my_sort(array_merge($cz), 'create_time', SORT_DESC);
                break;
            case "4"://发放/转移
                $alldata = $data = my_sort(array_merge($pc), 'create_time', SORT_DESC);
                break;
            default:
                $alldata = $data = my_sort(array_merge($cz, $zy, $zyj,$pc,$hs), 'create_time', SORT_DESC);
                break;
        }

        // 搜索开始
        foreach ($data as $key => &$value) {
            //数据整理
            if($value['ptbtype'] == 1) {
                $alldata[$key]['type'] = $data[$key]['type'] = 3;
            } elseif ($value['ptbtype'] == 2) {
                $alldata[$key]['type'] = $data[$key]['type'] = 4;
            }elseif ($value['ptbtype'] == 3) {
                $alldata[$key]['type'] = $data[$key]['type'] = 5;
            }
            $pay_order_number = $this->request->param('pay_order_number');
            if ($pay_order_number != '') {
                if ($value['pay_order_number'] != $pay_order_number) {
                    unset($data[$key]);
                }
            }
            $to_id = $this->request->param('to_id');
            if ($to_id != '' && $to_id != '自己') {
                if ($value['to_id'] != $to_id) {
                    unset($data[$key]);
                }
            } elseif ($to_id == '自己') {
                if ($value['promote_id'] != $to_id) {
                    unset($data[$key]);
                }
            }

            if ($type != '') {
                if ($value['type'] != $type) {
                    unset($data[$key]);
                }
            }
        }

        $tmp_promote_id = [];
        foreach($alldata as $k1=>$v1){
            if(in_array($v1['to_id'], $tmp_promote_id)){
                unset($alldata[$k1]);
            }
            $tmp_promote_id[] = $v1['to_id'];
        }
        $this->assign('alldata', $alldata);

        $curpage = input('page') ? input('page') : 1;
        $listRow = $this->request->param('row') ?: config('paginate.list_rows');
        $dataTo = array_chunk($data, $listRow);
        if ($dataTo) {
            $showdata = $dataTo[$curpage - 1];
        } else {
            $showdata = null;
        }
        $p = Bootstrap::make($showdata, $listRow, $curpage, count($data), false, [
            'var_page' => 'page',
            'path' => url('record'),
            'query' => [],
            'fragment' => '',
                   ]);
        $p->appends($_GET);
        $page = $p->render();
        $this->assign("page", $page);
        $this->assign('data', $p);
        return $this->fetch();
    }
    public function bind_record()
    {
        //条件
        $account = $this->request->param('user_account', '');
        if ($account != '') {
            $map['user_account'] = ['like', '%' . addcslashes($account, '%') . '%'];
        }
        $game_id = $this->request->param('game_id', 0, 'intval');
        if ($game_id > 0) {
            $map['game_id'] = $game_id;
        }
        $pay_way = $this->request->param('pay_way', '');
        if ($pay_way != '') {
            $map['pay_way'] = $pay_way;
        }
        $rangepickdate = $this->request->param('rangepickdate');
        if($rangepickdate) {
            $dateexp = explode('至', $rangepickdate);
            $starttime = $dateexp[0];
            $endtime = $dateexp[1];
            $this -> assign('start', $starttime);
            $this -> assign('end', $endtime);
            $map['pay_time'] = ['between', [strtotime($starttime), strtotime($endtime) + 86399]];
        }else{
            $this -> assign('start', date("Y-m-d", strtotime("-7 day")));
            $this -> assign('end', date("Y-m-d", strtotime("-1 day")));
        }
        $gdata = $this->promote_game_lists();
        $model = new PromotebindModel();
        $base = new \app\common\controller\BaseHomeController();
        $map['pay_status'] = 1;
        $map['promote_id'] = PID;
        $exend['field'] = 'game_id,game_name,user_id,user_account,cost,discount,pay_amount,pay_time,pay_way,pay_order_number';
        $exend['order'] = 'pay_time desc';
        $data = $base->data_list_join($model, $map, $exend);
        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_promote = get_promote_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_promote['account_show_promote']);
            }
        }

        //累计充值
        $exend['order'] = null;
        $exend['field'] = 'sum(cost) as scost,sum(pay_amount) as spay_amount';
        $total = $base->data_list_join_select($model, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        $this->assign("gdata", $gdata);
        $this->assign("total", $total[0]);
        return $this->fetch();
    }
    private function promote_game_lists()
    {
        //渠道禁止申请游戏
        $promote = $this->promote;
        if ($promote['game_ids']) {
            $map['g.id'] = ['notin', explode(',', $promote['game_ids'])];
        }
        $model = new PromoteapplyModel;
        $base = new BaseHomeController;
        $map['g.game_status'] = 1;
        $map['g.test_game_status'] = 0;
        $map['tab_promote_apply.promote_id'] = PID;
//        $exend['field'] = 'g.id,g.game_name,IF(a.promote_discount>0,a.promote_discount,g.discount) as discount';
        $exend['field'] = 'g.id,g.game_name';
        $exend['order'] = 'g.id asc';
        $exend['join1'] = [['tab_game' => 'g'],'g.id = tab_promote_apply.game_id','left'];
        $exend['join2'] = [['tab_promote_agent' => 'a'],'a.game_id = tab_promote_apply.game_id and tab_promote_apply.promote_id = a.promote_id','left'];
        $gdata = $base->data_list_join_select($model, $map, $exend);
        return $gdata;
    }
    // 二级渠道
    public function mychlid()
    {

        if(PID_LEVEL==3){
            $this->redirect('channelsite/index/index');
        }

        $base = new \app\common\controller\BaseHomeController;
        $model = new PromoteModel;
        $account = $this->request->param('account', '');
        if ($account != '') {
            $map['account'] = ['like', '%' . addcslashes($account, '%') . '%'];
        }
        $map['parent_id'] = PID;
        $exend['order'] = 'create_time desc';
        $exend['field'] = 'id,account,status,create_time';
        $data = $base->data_list($model, $map, $exend);
        // 联盟站设置
        $mUnion = new PromoteunionModel();
        foreach ($data as &$v) {
            $v['union'] = $mUnion -> field('id,union_id,status,domain_url') ->where(['union_id'=>$v['id']]) -> find();
        }
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    // 添加渠道
    public function add_chlid()
    {

        if(PID_LEVEL==3){
            $this->redirect('channelsite/index/index');
        }

        if (request()->isPost()) {
            $data = $this->request->param();
            $rule = [
            ];
            $msg = [
                'account.require' => '账号不能为空',
                'account.unique' => '账号已存在',
                'account.regex' => '账号为6-30位字母或数字组合',
                'account.min' => '账号为6-30位字母或数字组合',
                'account.max' => '账号为6-30位字母或数字组合',
                'password.require' => '密码不能为空',
                'password.min' => '密码为6-30位字母或数字组合',
                'password.max' => '密码为6-30位字母或数字组合',
                'password.regex' => '密码为6-30位字母或数字组合',
                'password.confirm' => '两次输入密码不一致',
            ];
            $validate = new PromoteValidate($rule, $msg);
            if (!$validate->scene('addzipromote')->check($data)) {
                $this->error($validate->getError());
            } else {
                $model = new PromoteModel();
                $data['parent_id'] = PID;
                $data['parent_name'] = PNAME;
                $data['promote_level'] = PID_LEVEL+1;
                $top_id = get_top_promote_id(PID);
                $data['top_promote_id'] = $top_id;
                $top_pattern = get_promote_entity($top_id,'pattern')['pattern'];
                $data['pattern'] = $top_pattern;
                $res = $model->add_child($data);
                if ($res != false) {
                    $this->success('子渠道添加成功');
                } else {
                    $this->error('子渠道添加失败');
                }
            }
        } else {
            return $this->fetch();
        }
    }

    public function changeStatus()
    {
        $data = $this->request->param();
        $map['parent_id'] = PID;
        $map['id'] = $data['zid'];
        $res = Db::table('tab_promote')->field('id,status')->where($map)->find();
        $return = false;
        if (empty($res)) {
            $this->error('渠道不存在');
        } else {
            $save['status'] = $data['value'] < 1 ? 1 : -1;
            $save['id'] = $res['id'];
            $return = Db::table('tab_promote')->update($save);
        }
        if ($return !== false) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    // 修改渠道
    public function edit_child()
    {
        if(PID_LEVEL==3){
            $this->redirect('channelsite/index/index');
        }

        $data = $this->request->param();
        $zidata = Db::table('tab_promote')->field('id,real_name,mobile_phone,email,bank_phone,bank_card,bank_name,bank_account,account_openin,bank_area,mark1')->where(['parent_id' => PID, 'id' => $data['id']])->find();
        if (empty($zidata)) {
            $this->error('该渠道不属于您的子渠道');
        }
        if (request()->isPost()) {
            switch ($data['tab']) {
                case 1:
                    $rule = [
                        'password' => 'min:6|max:30|regex:^[A-Za-z0-9]+$',
                        'real_name' => 'require|checkChineseName',
                        'mobile_phone' => 'require|isMobileNO',
                        'email' => 'unique'
                    ];
                    $msg = [
                        'password.min' => '密码为6-30位字母或数字组合',
                        'password.max' => '密码为6-30位字母或数字组合',
                        'password.regex' => '密码为6-30位字母或数字组合',
                        //'real_name.require'   => '姓名不能为空',
                        'real_name.min' => '姓名长度需要在2-25个字符之间',
                        'real_name.checkChineseName' => '姓名格式错误',
                        //'mobile_phone.require'   => '手机号不能为空',
                        'mobile_phone.isMobileNO' => '请输入正确的手机号',
                        //'email.require'   => '邮箱不能为空',
                        'email.email' => '邮箱地址错误',
                        'email.unique' => '邮箱地址已被使用',
                    ];
                    if($data['mobile_phone'] && strlen($data['mobile_phone']) != 11){
                        $this->error('请输入11位手机号码');
                    }
                    $validate = new PromoteValidate($rule, $msg);
                    $valires = $validate->scene('editzipromote' . $data['tab'])->check($data);
                    break;
                case 2:
                    $data['bank_area'] = $data['province'] . ',' . $data['city'] . ',' . $data['county'];
                    unset($data['province'], $data['city'], $data['county']);
                    if ($data['bank_area'] == '省,市,区/县') {
                        $data['bank_area'] = '';
                    }
                    if($data['bank_phone'] && strlen($data['bank_phone']) != 11){
                        $this->error('请输入11位手机号码');
                    }
                    $rule = [
                        'bank_phone' => 'require|isMobileNO',
                        'bank_card' => 'require|number|max:19',
                        'bank_name' => 'require',
                        'bank_account' => 'require|checkChineseName',
                        'account_openin' => 'require|min:2',
                    ];
                    $msg = [
                        'bank_phone.require' => '预留手机号不能为空',
                        'bank_phone.isMobileNO' => '预留手机号格式不正确',
                        'bank_card.require' => '银行卡号不能为空',
                        'bank_card.number' => '银行卡号格式不正确',
                        'bank_card.max' => '银行卡号长度不正确',
                        'bank_name.require' => '请选择收款银行',
                        'bank_account.require' => '持卡人不能为空',
                        'bank_account.checkChineseName' => '持卡人格式不正确',
                        'account_openin.require' => '开户网点不能为空',
                        'account_openin.min' => '开户网点错误',
                        'account_openin.checkChineseName' => '开户网点错误',
                    ];
                    $validate = new PromoteValidate($rule, $msg);
                    $valires = $validate->scene('proinfo' . $data['tab'])->check($data);
                    break;
                default:
                    $this->error('数据错误');
                    break;
            }
            if (!$valires) {
                $this->error($validate->getError());
            }
            unset($data['tab']);
            if (!empty($data['password'])) {
                $data['password'] = cmf_password($data['password']);
            } else {
                unset($data['password']);
            }
            if (!empty($data['second_pwd'])) {
                $data['second_pwd'] = cmf_password($data['second_pwd']);
            } else {
                unset($data['second_pwd']);
            }
            $saveData = [];
            if(isset($data['password'])){
                $saveData['password'] = $data['password'];
            }
            if(isset($data['second_pwd'])){
                $saveData['second_pwd'] = $data['second_pwd'];
            }
            $saveData['id'] = $data['id'];
            if($this->request->param('tab')==1){
                $saveData['real_name'] =$data['real_name'];
                $saveData['mobile_phone'] =$data['mobile_phone'];
                $saveData['email'] =$data['email'];
                $saveData['mark1'] =$data['mark1'];
            }else{
                $saveData['bank_area'] =$data['bank_area'];
                $saveData['bank_phone'] =$data['bank_phone'];
                $saveData['bank_card'] =$data['bank_card'];
                $saveData['bank_name'] =$data['bank_name'];
                $saveData['bank_account'] =$data['bank_account'];
                $saveData['account_openin'] =$data['account_openin'];
            }
            $res = Db::table('tab_promote')->update($saveData);

            if ($res !== false) {
                $this->success('修改成功',url('mychlid'));
            } else {
                $this->error('修改失败');
            }
        } else {
            $zidata['bank_area'] = explode(',', $zidata['bank_area']);
            $this->assign('data', $zidata);
            return $this->fetch();
        }
    }
    public function bind_balance()
    {
        $paytype = Db::table('tab_spend_payconfig')->field('name')->where(['status' => 1])->select()->toarray();
        $gdata = $this->promote_game_lists();
        session('bind_recharge_game'.PID,$gdata);
        $this->assign('paytype', $paytype);
        $this->assign('gdata',$gdata);
        return $this->fetch();
    }
    public function get_user_play()
    {
        if($this->request->isAjax()){
            $model = new UserPlayModel;
            $base = new BaseHomeController;
            $game_id = $this->request->param('game_id');
            $map['u.promote_id'] = PID;
            $map['game_id'] = $game_id;
            $exend['field'] = 'user_id,user_account';
            $exend['order'] = 'user_id asc';
            $exend['join1'][] = ['tab_user' => 'u'];
            $exend['join1'][] = 'u.id = tab_user_play.user_id';
            $data = $base->data_list_join_select($model, $map, $exend);
            // 判断当前渠道是否有权限显示完成整手机号或完整账号
            $ys_show_promote = get_promote_privicy_two_value();
            foreach($data as $k5=>$v5){
                if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                    $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_promote['account_show_promote']);
                }
            }
            session('bind_recharge_user'.PID,$data);
            if(empty($data)){
                return json(['code'=>0,'msg'=>'无玩家数据']);
            }
            return json(['code'=>1,'msg'=>'玩家获取成功','data'=>$data]);
        }
    }

    /**
     *  * 获取首充或者续充的折扣
     *  * by:byh-20210429
     * @return \think\response\Json
     * 获取首充或者续充的折扣2(根据用户查询是否为首充) modified by wjd
     */
    public function get_discount()
    {
        $game_id = $this->request->param('game_id',0);
        $user_id = $this->request->param('user_id',0);
        if($game_id<1){
            return json(['code'=>0,'msg'=>'参数错误']);
        }
        // $discount = get_promote_dc_discount(PID,$game_id,$user_id);
        $discount = get_promote_dc_discount(PID,$game_id,$user_id);

        return json(['code'=>1,'msg'=>'获取成功','data'=>['discount'=>$discount]]);
    }

    /**
     * @函数或方法说明
     * @我的福利
     * @author: 郭家屯
     * @since: 2020/2/10 10:50
     */
    public function welfare()
    {
        $request = $this->request->param();
        $logic = new PromoteLogic();
        $result = $logic->welfare(PID,$request['game_id']);
        // $list_data = parent::array_order($result, 'game_id', $request['sort']); // 原
        $list_data = parent::array_order($result, 'game_id'); // 去掉排序 wjd
        // foreach($list_data as $kk=>$vv){
        //     $list_data[$kk]['ratio'] = $vv['$ratio'];
        //     $list_data[$kk]['money'] = $vv['$money'];
        //     $list_data[$kk]['discount'] = $vv['$discount'];
        //     $list_data[$kk]['promote_discount_first'] = $vv['$promote_discount_first'];
        //     $list_data[$kk]['promote_discount_continued'] = $vv['$promote_discount_continued'];
        //     $list_data[$kk]['first_discount'] = $vv['$first_discount'];
        //     $list_data[$kk]['continue_discount'] = $vv['$continue_discount'];
        // }
        // var_dump($list_data);exit;
        parent::array_page($list_data, $request);
        return $this->fetch();
    }

    //生成二维码
    function create_qrcode($url,$level=3,$size=4)
    {
        $errorCorrectionLevel =intval($level) ;//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        vendor('phpqrcode');//导入类库
        $QRcode = new \cmf\phpqrcode\QRcode();//实例化，注意加\
        ob_clean();
        echo $QRcode->png(base64_decode(base64_decode($url)), false, $errorCorrectionLevel, $matrixPointSize);
    }

    // 预付款----------------------===================================================-----------------------START


    private function getPromoteInfo(){

    }
    // 充值预付款(保证金)
    public function prepayment(){

        // echo '预付款信息';exit;
        // 渠道信息
        $promote_id = PID;
        $map['id'] = $promote_id;
        $promote_info = Db::table('tab_promote')->where($map)->find();
        // 判断只有 一级渠道 且后台开启了自定义支付通道 才给显示 is_custom_pay  0:关闭 1:开启
        $is_custom_pay = $promote_info['is_custom_pay'] ?? 0;

        if(PID_LEVEL != 1 || $is_custom_pay != 1){
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/channelsite';
            // header($url);
            header('Location: '.$url);
            exit;
        }

        $promote_account = $promote_info['account'] ?? '';
        // 支付方式
        $paytype = Db::table('tab_spend_payconfig')->field('name')->where(['status' => 1])->select()->toarray();
        $this->assign('balance', null_to_0($this->promote['balance_coin']));
        $this->assign('paytype', $paytype);
        $this->assign('promote_account',$promote_account);
        return $this->fetch();
    }

    //检查预付款充值结果
    public function check_pp_order_status()
    {
        $order_no = $this->request->param('order_no');
        if (empty($order_no)) {
            $this->error('无订单数据');
        }
        $res = Db::table('tab_promote_prepayment_recharge')->field('pay_order_number,pay_status,promote_id,pay_amount,pay_way,create_time')->where(['promote_id' => PID, 'pay_order_number' => $order_no])->find();
        if (request()->isAjax()) {
            if ($res['pay_status'] == 1) {
                $this->success('充值成功', cmf_get_domain() . '/channelsite/Promote/check_pp_order_status/order_no/' . $order_no);
            } else {
                $this->error('未充值成功');
            }
        } else {
            $this->assign('data', $res);
            if (empty($res)) {
                $this->error('数据错误');
            } elseif ($res['pay_status'] == 1) {
                return $this->fetch('pay_success2');
            } else {
                return $this->fetch('pay_failure2');
            }
        }
    }
    // 预付款充值记录
    public function prepayment_record(Request $request){
        // echo '预付款充值记录';exit;
        // 渠道信息
        $promote_id = PID;
        $map['id'] = $promote_id;
        $promote_info = Db::table('tab_promote')->where($map)->find();
        // 判断只有 一级渠道 且后台开启了自定义支付通道 才给显示 is_custom_pay  0:关闭 1:开启
        $is_custom_pay = $promote_info['is_custom_pay'] ?? 0;

        if(PID_LEVEL != 1 || $is_custom_pay != 1){
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/channelsite';
            // header($url);
            header('Location: '.$url);
            exit;
        }

        $ppr_m = new PromotePrepaymentRechargeModel();
        $req = $request->param();
        $row = $req['row'] ?? 10;
        $search = [];
        $order_number = $req['order_number'] ?? '';
        $start_time = $req['start_time'] ?? '';
        $end_time = $req['end_time'] ?? '';
        $pay_way = $req['pay_way'] ?? '';
        $pay_status = $req['pay_status'] ?? -1;
        if(!empty($order_number)){
            $search['pay_order_number'] = ['like','%'.$order_number.'%'];
        }
        if(!empty($start_time)){
            $start_time1 = strtotime($start_time);
            $search['create_time'] = ['gt', $start_time1];
        }
        if(!empty($end_time)){
            $end_time1 = strtotime($end_time);
            $search['create_time'] = ['lt',$end_time1+86399];
        }
        if(!empty($start_time) && !empty($end_time)){
            $search['create_time'] = ['between', [strtotime($start_time), strtotime($end_time)+86399]];
        }
        if(!empty($pay_way)){
            $search['pay_way'] = $pay_way;
        }
        if($pay_status === '0'){
            $search['pay_status'] = $pay_status;
        }
        if($pay_status == 1){
            $search['pay_status'] = $pay_status;
        }
        $promote_id = PID;
        $search['promote_id'] = $promote_id;

        $order = 'id desc';
        $data = $ppr_m->lists($search,$row,$order,$req);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data", $data);
        $this->assign("page", $page);
        // return json($data);
        return $this->fetch();
    }
    // 预付款消费记录
    public function prepayment_deduct_record(Request $request){
        // echo '预付款充值记录';exit;
        // 渠道信息
        $promote_id = PID;
        $map['id'] = $promote_id;
        $promote_info = Db::table('tab_promote')->where($map)->find();
        // 判断只有 一级渠道 且后台开启了自定义支付通道 才给显示 is_custom_pay  0:关闭 1:开启
        $is_custom_pay = $promote_info['is_custom_pay'] ?? 0;

        if(PID_LEVEL != 1 || $is_custom_pay != 1){
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/channelsite';
            // header($url);
            header('Location: '.$url);
            exit;
        }

        $ppdr_m = new PromotePrepaymentDeductRecordModel();
        $req = $request->param();
        $row = $req['row'] ?? 10;
        $search = [];
        $pay_order_number = $req['pay_order_number'] ?? '';
        $start_time = $req['start_time'] ?? '';
        $end_time = $req['end_time'] ?? '';

        if(!empty($pay_order_number)){
            $search['pay_order_number'] = ['like','%'.$pay_order_number.'%'];
        }
        if(!empty($start_time)){
            $start_time1 = strtotime($start_time);
            $search['create_time'] = ['gt', $start_time1];
        }
        if(!empty($end_time)){
            $end_time1 = strtotime($end_time);
            $search['create_time'] = ['lt',$end_time1 + 86399];
        }
        if(!empty($start_time) && !empty($end_time)){
            $search['create_time'] = ['between', [strtotime($start_time), strtotime($end_time)+86399]];
        }
        $promote_id = PID;
        $search['promote_id'] = $promote_id;

        $order = 'id desc';

        $data = $ppdr_m->lists($search,$row,$order,$req);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data", $data);
        $this->assign("page", $page);
        // return json($data);
        return $this->fetch();
    }
    // 预付款---------------------===================================================------------------------END

    // 渠道刚登录进入首页,每天展示一次资讯信息
    protected function getFirstArticle()
    {
        $promote_id = PID;
        // 获取当前渠道所申请的游戏
        $promoteGameInfo = Db::table('tab_promote_apply')->alias('pa')
            ->field('g.id as game_gid,g.relation_game_id,g.promote_ids,g.test_game_status,g.promote_ids2,g.only_for_promote,g.game_status,g.apply_status,pa.promote_id')
            ->where(['g.test_game_status'=>0, 'g.game_status'=>['=', 1], 'g.apply_status'=>1, 'pa.promote_id'=>$promote_id])
            ->join(['tab_game' => 'g'], 'g.id=pa.game_id','right')
            ->select()->toArray();

        $promoteGameIds = [];
        if(!empty($promoteGameInfo)){
            foreach($promoteGameInfo as $k10=>$v10){
                if($v10['only_for_promote'] == 1){
                    $forbid2_promote_ids = explode(',', $v10['promote_ids2']);
                    if(!in_array($promote_id, $forbid2_promote_ids)){
                        unset($promoteGameInfo[$k10]);
                    }
                }
            }
            foreach($promoteGameInfo as $k11=>$v11){
                $promoteGameIds[] = $v11['relation_game_id'];
            }

        }
        // 如果未指定游戏的 将游戏 id = 0 的条件拼接到渠道申请的游戏列表中
        $promoteGameIds[] = 0;
        // echo json_encode($promoteGameIds);
        // exit;
        $portalPostModel = new PortalPostModel();
        $gonggaoInfo = $portalPostModel
                ->alias('p')
                ->field('p.id,p.sort,p.post_title,p.update_time,p.create_time,p.post_content,p.game_id,p.thumbnail,cp.category_id,cp.post_id')
                // ->where(['cp.category_id'=>['in',[2,4]],'p.delete_time'=>0,'website'=>17])
                ->where(['cp.category_id'=>['in',[2,4]],'p.delete_time'=>0, 'website'=>8])
                ->where(['p.game_id'=>['in',$promoteGameIds]])
                ->where(['p.post_status'=>1])
                // ->join(['tab_game' => 'g'], 'g.relation_game_id=p.game_id','left')
                ->join(['sys_portal_category_post' => 'cp'], 'cp.post_id=p.id','left')
                ->group('cp.post_id')// 去重
                ->order('p.sort desc,p.id desc')
                ->limit(0,2)
                ->select()->toArray();

        $this->assign('gonggao_info', $gonggaoInfo);
        //每日显示一次

    }

    // 获取文章的上一篇下一篇 by wjd 2021-7-13 10:13:34
    public function getPreviousNextArticle(Request $request)
    {
        $param = $request->param();
        $promote_id = PID;
        // 获取当前渠道所申请的游戏
        $promoteGameInfo = Db::table('tab_promote_apply')->alias('pa')
            ->field('g.id as game_gid,g.relation_game_id,g.promote_ids,g.test_game_status,g.promote_ids2,g.only_for_promote,g.game_status,g.apply_status,pa.promote_id')
            ->where(['g.test_game_status'=>0, 'g.game_status'=>['=', 1], 'g.apply_status'=>1, 'pa.promote_id'=>$promote_id])
            ->join(['tab_game' => 'g'], 'g.id=pa.game_id','right')
            ->select()->toArray();

        $promoteGameIds = [];
        if(!empty($promoteGameInfo)){
            foreach($promoteGameInfo as $k10=>$v10){
                if($v10['only_for_promote'] == 1){
                    $forbid2_promote_ids = explode(',', $v10['promote_ids2']);
                    if(!in_array($promote_id, $forbid2_promote_ids)){
                        unset($promoteGameInfo[$k10]);
                    }
                }
            }
            foreach($promoteGameInfo as $k11=>$v11){
                $promoteGameIds[] = $v11['relation_game_id'];
            }

        }
        // 如果未指定游戏的 将游戏 id = 0 的条件拼接到渠道申请的游戏列表中
        $promoteGameIds[] = 0;

        $post_id = $param['post_id'];
        $portalPostModel = new PortalPostModel();
        $d_gonggaoInfo = $portalPostModel
                ->alias('p')
                ->field('p.id,p.sort,p.post_title,p.update_time,p.create_time,p.post_content,p.game_id,p.thumbnail,cp.category_id,cp.post_id')
                ->where(['p.delete_time'=>0, 'p.id'=>$post_id])
                // ->join(['tab_game' => 'g'], 'g.relation_game_id=p.game_id','left')
                ->join(['sys_portal_category_post' => 'cp'], 'cp.post_id=p.id','left')
                // ->order('p.sort desc')
                ->find();

        $all_gonggao = $portalPostModel
                ->alias('p')
                ->field('p.id,p.sort,p.post_title,p.update_time,p.create_time,p.post_content,p.game_id,p.thumbnail,cp.category_id,cp.post_id')
                ->where(['cp.category_id'=>['in',[2,4]],'p.delete_time'=>0, 'website'=>8])
                ->where(['p.game_id'=>['in',$promoteGameIds]])
                ->where(['p.post_status'=>1])
                // ->join(['tab_game' => 'g'], 'g.relation_game_id=p.game_id','left')
                ->join(['sys_portal_category_post' => 'cp'], 'cp.post_id=p.id','left')
                ->order('p.sort desc,p.id desc')
                ->select()->toArray();

        // 去重
        $tmp_post_ids22 = [];
        foreach($all_gonggao as $k2=>$v2){
            if(in_array($v2['id'], $tmp_post_ids22)){
                unset($all_gonggao[$k2]);
            }
            $tmp_post_ids22[] = $v2['id'];
        }

        $all_gonggao = array_values($all_gonggao);
        foreach($all_gonggao as $k=>$v){
            if ($d_gonggaoInfo['id'] == $v['id']) {
                //获取下一篇
                if (isset($all_gonggao[$k+1])) {
                    $next_id = $all_gonggao[$k+1]['id'];
                } else {
                    $next_id = '';
                }
                //获取上一篇
                if (isset($all_gonggao[$k-1])) {
                    $pre_id = $all_gonggao[$k-1]['id'];
                } else {
                    $pre_id = '';
                }
            }
        }

        if($next_id != ''){
            $next_info = $portalPostModel
                ->alias('p')
                ->field('p.id,p.sort,p.post_title,p.update_time,p.create_time,p.post_content,p.game_id,p.thumbnail')
                ->where(['p.id'=>$next_id])
                ->find();
        }else{
            $next_info = '';
        }

        if($pre_id != ''){
            $pre_info = $portalPostModel
                ->alias('p')
                ->field('p.id,p.sort,p.post_title,p.update_time,p.create_time,p.post_content,p.game_id,p.thumbnail')
                ->where(['p.id'=>$pre_id])
                ->find();
        }else{
            $pre_info = '';
        }
        $mixData = [
            'd_gonggaoInfo'=>$d_gonggaoInfo,
            'next_gonggaoInfo'=>$pre_info,
            'previous_gonggaoInfo'=>$next_info,
        ];
        return json(['code'=>1, 'msg'=>'获取成功', 'data'=>$mixData]);

    }

    /**
     * 设置修改渠道开启或关闭短信通知状态
     * by:byh 2021-7-15 10:40:15
     */
    public function set_sms_notice_status()
    {
        //判断后台总开关是否开启
        $zong_switch = cmf_get_option('admin_set')['promote_sms_notice_switch']??0;
        if($zong_switch != 1) return json(['code'=>0, 'msg'=>'不可操作!']);
        //查询渠道手机号是否存在和渠道开关状态
        $promote = Db::table('tab_promote')->field('mobile_phone,sms_notice_switch')->where('id',PID)->find();
        if(empty($promote['mobile_phone']))  return json(['code'=>0, 'msg'=>'账号未绑定手机号!']);
        if($promote['sms_notice_switch'] == 1){
            $change['sms_notice_switch'] = 0;
            $msg = "短信通知关闭成功!";
        }else{
            $change['sms_notice_switch'] = 1;
            $msg = "短信通知开通成功!";
        }
        $res = Db::table('tab_promote')->where('id',PID)->update($change);
        if(!$res){
            return json(['code'=>0, 'msg'=>'操作失败!']);
        }
        return json(['code'=>1, 'msg'=>$msg]);

    }

    /**
     * 玩家福利列表-默认展示游戏首冲续充配置列表
     * by:byh 2021-8-26 20:04:00
     */
    public function user_welfare()
    {
        $request = $this->request->param();

        $logic = new PromoteLogic();
        $list_data = $logic->get_user_welfare_list(PID,$request);

        $page = $list_data->render();
        $this->assign("data_lists", $list_data);
        $this->assign("page", $page);
        return $this->fetch();

    }
    /**
     * 玩家福利-首充续充新增
     * by:byh 2021-8-27 13:48:04
     */
    public function add_user_welfare()
    {
        //判断是否为一级渠道
        $level = get_promote_entity(PID,'promote_level')['promote_level']??0;
        if($level !=1)$this->error('当前渠道不可操作!',url('user_welfare'));
        if($this->request->isPost()){
            $data=  $this->request->param();
            if(empty($data))$this->error('参数错误');
            if(empty($data['game_id']))$this->error('请选择游戏');
            $data['first_discount'] = $data['first_discount']?$data['first_discount']:10;
            $data['continue_discount'] = $data['continue_discount']?$data['continue_discount']:10;
            $logic = new PromoteLogic();
            //判断折扣配置是否正常
            $gf_limit = $logic->get_promote_game_user_limit_discount($data['game_id'],PID);
            if($data['first_discount']<$gf_limit || $data['continue_discount']<$gf_limit){
                $this->error('折扣值不可低于平台官方折扣');
            }
            $result = $logic->add_user_welfare($data);
            switch ($result){
                case -1:
                    $this->error('该游戏已设置折扣');
                    break;
                case -2:
                    $this->error('未查询到玩家');
                    break;
                case 1001:
                    $this->error('存在玩家不属于当前渠道,不可设置');
                    break;
                case 200:
                    $this->success('添加成功',url('user_welfare'));
                    break;
                default:
                    $this->error('添加失败');
            }
        }
        return $this->fetch();
    }
    /**
     * 编辑渠道自定义玩家福利-首充续充
     * by:byh 2021-8-27 19:11:01
     */
    public function edit_user_welfare()
    {
        //判断是否为一级渠道
        $level = get_promote_entity(PID,'promote_level')['promote_level']??0;
        if($level !=1)$this->error('当前渠道不可操作!',url('user_welfare'));

        $id = $this -> request -> param('id',0,'intval');
        $logic = new PromoteLogic();
        if ($this -> request -> isPost()) {
            $param = $this -> request -> param();
            if(empty($param['game_id'])|| empty($param['id']))$this->error('数据错误');
            $param['first_discount'] = $param['first_discount']?$param['first_discount']:10;
            $param['continue_discount'] = $param['continue_discount']?$param['continue_discount']:10;
            //判断折扣配置是否正常

            $gf_limit = $logic->get_promote_game_user_limit_discount($param['game_id'],PID);
//            dump($this -> request -> param());die;
            if($param['first_discount']<$gf_limit || $param['continue_discount']<$gf_limit){
                $this->error('折扣值不可低于平台官方折扣');
            }
            $result = $logic->edit_user_welfare($param);
            switch ($result){
                case -2:
                    $this->error('未查询到玩家');
                    break;
                case 1001:
                    $this->error('存在玩家不属于当前渠道,不可设置');
                    break;
                case 200:
                    $this->success('编辑成功',url('user_welfare'));
                    break;
                default:
                    $this->error('编辑失败');
            }
        }
        if(empty($id))$this->error('参数错误');
        $info = $logic->get_user_welfare_detail($id);//编辑时的原数据详情
//        dump($info);die;
        $this -> assign('info', $info);
        return $this -> fetch();
    }

    /**
     * 玩家福利-绑币充值首充续充折扣-渠道自定义配置列表
     * by:byh 2021-8-30 10:24:52
     */
    public function user_bind_discount()
    {
        $request = $this->request->param();

        $logic = new PromoteLogic();
        $list_data = $logic->get_user_bind_discount_list(PID,$request);
        $page = $list_data->render();
        $this->assign("data_lists", $list_data);
        $this->assign("page", $page);
        return $this->fetch();
    }
    /**
     * 玩家福利-渠道自定义绑币充值新增
     * by:byh 2021-8-27 13:48:04
     */
    public function add_user_bind_discount()
    {
        //判断是否为一级渠道
        $level = get_promote_entity(PID,'promote_level')['promote_level']??0;
        if($level !=1)$this->error('当前渠道不可操作!',url('user_welfare'));
        if($this->request->isPost()){
            $data=  $this->request->param();
            if(empty($data))$this->error('参数错误');
            if(empty($data['game_id']))$this->error('请选择游戏');
            $data['first_discount'] = $data['first_discount']?$data['first_discount']:10;
            $data['continue_discount'] = $data['continue_discount']?$data['continue_discount']:10;
            $logic = new PromoteLogic();
            //判断折扣配置是否正常
            $gf_limit = $logic->get_promote_game_user_limit_discount($data['game_id'],PID);
            if($data['first_discount']<$gf_limit || $data['continue_discount']<$gf_limit){
                $this->error('折扣值不可低于平台官方折扣');
            }
            $result = $logic->add_user_bind_discount($data);
            switch ($result){
                case -1:
                    $this->error('该游戏已设置折扣');
                    break;
                case -2:
                    $this->error('未查询到玩家');
                    break;
                case 1001:
                    $this->error('存在玩家不属于当前渠道,不可设置');
                    break;
                case 200:
                    $this->success('添加成功',url('user_bind_discount'));
                    break;
                default:
                    $this->error('添加失败');
            }
        }
        return $this->fetch();
    }
    /**
     * 编辑渠道自定义玩家绑币充值-首充续充
     * by:byh 2021-8-27 19:11:01
     */
    public function edit_user_bind_discount()
    {
        //判断是否为一级渠道
        $level = get_promote_entity(PID,'promote_level')['promote_level']??0;
        if($level !=1)$this->error('当前渠道不可操作!',url('user_welfare'));

        $id = $this -> request -> param('id',0,'intval');
        $logic = new PromoteLogic();
        if ($this -> request -> isPost()) {
            $param = $this -> request -> param();
            if(empty($param['game_id'])|| empty($param['id']))$this->error('数据错误');
            $param['first_discount'] = $param['first_discount']?$param['first_discount']:10;
            $param['continue_discount'] = $param['continue_discount']?$param['continue_discount']:10;
            //判断折扣配置是否正常

            $gf_limit = $logic->get_promote_game_user_limit_discount($param['game_id'],PID);
            if($param['first_discount']<$gf_limit || $param['continue_discount']<$gf_limit){
                $this->error('折扣值不可低于平台官方折扣');
            }
            $result = $logic->edit_user_bind_discount($param);
            switch ($result){
                case -2:
                    $this->error('未查询到玩家');
                    break;
                case 1001:
                    $this->error('存在玩家不属于当前渠道,不可设置');
                    break;
                case 200:
                    $this->success('编辑成功',url('user_bind_discount'));
                    break;
                default:
                    $this->error('编辑失败');
            }
        }
        if(empty($id))$this->error('参数错误');
        $info = $logic->get_user_bind_discount_detail($id);//编辑时的原数据详情
        $this -> assign('info', $info);
        return $this -> fetch();
    }
    /**
     * 新增-编辑页面显示选择游戏的官方配置的最低折扣
     * by:byh 2021-9-17 11:50:23
     */
    public function getGameGfLimitDiscount($game_id=0)
    {
        if(empty($game_id)){
            return json(['gf_limit_discount'=>'10.00']);
        }
        //
        $logic = new PromoteLogic();
        $gf_limit = $logic->get_promote_game_user_limit_discount($game_id,PID);
        return json(['gf_limit_discount'=>$gf_limit]);
    }
    /**
     * 新增-选择游戏的时候就判断是否已经设置过折扣
     * by:byh 2021-9-17 14:53:34
     * @param int $game_id 游戏id
     * @param int $type 查询类型 1=充值折扣 2=绑币充值折扣
     */
    public function judgeGameIsSetDiscount($game_id=0,$type=0)
    {
        $logic = new PromoteLogic();
        return $logic->judgeGameIsSetDiscount($game_id,$type);

    }



}
