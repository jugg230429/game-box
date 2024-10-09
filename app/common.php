<?php
/**
 * [app_auth_value 应用权限 用户相关表tab_user开头  渠道相关表tab_promote  游戏相关表tab_game 充值相关表tab_spend]
 * @return [type] [description]
 * @author [yyh] <[<email address>]>
 */
function app_auth_value()
{
    $permi = json_decode(file_get_contents(WEB_ROOT.'../data/'.'version_code.txt'),true)['system_permi'];
    if(!in_array($permi,array(0,1,2,3))){
        exit(base64_decode('57O757uf5p2D6ZmQ5LiN5ZCI5rOV'));
    }else{
        define(PERMI,$permi);
    }
    $ypermi = json_decode(file_get_contents(WEB_ROOT.'../data/'.'version_code.txt'),true)['system_ypermi'];
    if(!in_array($ypermi,array(0,1))){
        exit(base64_decode('57O757uf5p2D6ZmQ5LiN5ZCI5rOV'));
    }else{
        define(YPERMI,$ypermi);
    }
    $result = cache('permi_tables');
    if(empty($result)){
        $result = think\Db::query('show TABLES');
        cache('permi_tables',$result);
    }
    foreach ($result as $key => $value) {
        $tables[] = reset($value);
    }

    foreach ($tables as $k1 => $v1) {
        if (substr($v1, 0, 1) == 't') {
            $tab[] = $v1;
            //用户相关表tab_user开头
            if (substr($v1, 0, 8) == 'tab_user') {
                $usertab[] = $v1;
            }
            //渠道相关表tab_promote
            if (substr($v1, 0, 11) == 'tab_promote') {
                $promtab[] = $v1;
            }
            //游戏相关表tab_game
            if (substr($v1, 0, 8) == 'tab_game') {
                $gametab[] = $v1;
            }
            //充值相关表tab_spend
            if (substr($v1, 0, 9) == 'tab_spend') {
                $spendtab[] = $v1;
            }
        }
    }
    $user = [
        'tab_user',
        'tab_user_award',
        'tab_user_award_record',
        'tab_user_login_record',
        'tab_user_balance_edit',
        'tab_user_deduct_bind',
        'tab_user_behavior',
        'tab_user_config',
        'tab_user_param',
        'tab_user_mend',
        'tab_user_member',
        'tab_user_day_login',
        'tab_user_game_login',
        'tab_user_play',
        'tab_user_play_info',
        'tab_user_play_record',
        'tab_user_invitation',
        'tab_user_invitation_record',
        'tab_user_transaction',
        'tab_user_transaction_order',
        'tab_user_transaction_profit',
        'tab_user_transaction_tip',
        'tab_user_tplay',
        'tab_user_tplay_record',
        'tab_user_point_record',
        'tab_user_point_shop',
        'tab_user_point_shop_record',
        'tab_user_tplay_withdraw',
        'tab_user_point_type',
        'tab_user_auth_code',
    ];
    $promote = [
        'tab_promote',
        'tab_promote_app',
        'tab_promote_agent',
        'tab_promote_apply',
        'tab_promote_coin',
        'tab_promote_bind',
        'tab_promote_business',
        'tab_promote_behavior',
        'tab_promote_config',
        'tab_promote_deposit',
        'tab_promote_settlement',
        'tab_promote_union',
        'tab_promote_withdraw',
        'tab_promote_bce_package',
    ];
    $game = [
        'tab_game',
        'tab_game_comment',
        'tab_game_comment_follow',
        'tab_game_follow',
        'tab_game_gift_record',
        'tab_game_down_record',
        'tab_game_giftbag',
        'tab_game_server',
        'tab_game_server_notice',
        'tab_game_set',
        'tab_game_share_record',
        'tab_game_source',
        'tab_game_type',
        'tab_game_collect',
        'tab_game_interface'
    ];
    $spend = [
        'tab_spend',
        'tab_spend_balance',
        'tab_spend_bind',
        'tab_spend_distinction',
        'tab_spend_payconfig',
        'tab_spend_promote_coin',
        'tab_spend_provide',
        'tab_spend_rebate',
        'tab_spend_rebate_promote',
        'tab_spend_rebate_record',
        'tab_spend_welfare',
        'tab_spend_welfare_promote',
        'tab_spend_wxparam'
    ];
    $auth_value = 0;
    //用户权限值为1
    if (count($user) <= count($usertab) && is_dir(ROOT_PATH . "/app/member")) {
        $auth_value = $auth_value + pow(2, 0);
        define('AUTH_USER', 1);
    }
    //充值权限值为2
    if (count($spend) <= count($spendtab) && is_dir(ROOT_PATH . "/app/recharge")) {
        $auth_value = $auth_value + pow(2, 1);
        define('AUTH_PAY', 1);
    }
    //游戏权限值为4
    if (count($game) <= count($gametab) && is_dir(ROOT_PATH . "/app/game")) {
        $auth_value = $auth_value + pow(2, 2);
        define('AUTH_GAME', 1);
    }
    //渠道权限值为8
    if (count($promote) <= count($promtab) && is_dir(ROOT_PATH . "/app/promote")) {
        $auth_value = $auth_value + pow(2, 3);
        define('AUTH_PROMOTE', 1);
    }
    //第三方游戏权限
    if(is_dir(ROOT_PATH . "/app/thirdgame")){
        define('AUTH_THIRD_GAME', 1);
    }else{
        define('AUTH_THIRD_GAME', 0);
    }
    //简化版平台权限
    if(is_dir(ROOT_PATH . "/app/webplatform")){
        define('AUTH_WEB_PLATFORM', 1);
    }else{
        define('AUTH_WEB_PLATFORM', 0);
    }
    define('APP_AUTH_VALUE', $auth_value);
}

/**
 * [get_promote_name 获取渠道名称]
 * @return [type] [description]
 * @author [yyh] <[<email address>]>
 */
function get_promote_name($pid = '')
{
    if (empty($pid)) {
        return '官方渠道';
    } else {
        $map['id'] = $pid;
        $data = think\Db::table('tab_promote')->field('account')->where($map)->find();
        if (empty($data)) {
            return '未知渠道';
        } else {
            return $data['account'];
        }
    }
}

/**
 * @函数或方法说明
 * @获取渠道信息
 * @param $id
 *
 * @author: 郭家屯
 * @since: 2019/4/13 9:34
 */
function get_promote_entity($id = 0, $field = '*')
{
    if (empty($id)) {
        return [];
    } else {
        $map['id'] = $id;
        $data = think\Db::table('tab_promote')->field($field)->where($map)->find();
        if (empty($data)) {
            return [];
        } else {
            return $data;
        }
    }
}

/**
 * [获取用户注册类型]
 * @param string $type
 * @return string
 * @author 郭家屯[gjt]
 */
function get_user_register_type($type = '')
{
    switch ($type) {
        case '0':
            return '游客';
            break;
        case '1':
            return '账号';
            break;
        case '2':
            return '手机';
            break;
        case '3':
            return '微信';
            break;
        case '4':
            return 'QQ';
            break;
        case '5':
            return '百度';
            break;
        case '6':
            return '微博';
            break;
        case '7':
            return '邮箱';
            break;
        case '8':
            return '苹果';
            break;
        case '9':
            return '脸书';
            break;
        default:
            return 'error';
            break;
    }
}

/**
 * [获取渠道列表]
 * @param string $select
 * @return false|mixed|PDOStatement|string|\think\Collection
 * @author 郭家屯[gjt]
 * @change yyh 增加map
 */
function get_promote_list($map = [], $field = 'id,account,balance_coin,parent_id,promote_level',$order='id desc')
{
//        $map['status'] = 1;
    $list = think\Db::table('tab_promote')->field($field)->where($map)->order($order)->select()->toarray();
    if (empty($list)) {
        return '';
    }
    return $list;
}

/**
 * [获取支付渠道筛选]
 */
function get_promote_select_options(){
    return [
        ['id'=>888,'name'=>'官方支付'],
        ['id'=>1,'name'=>'鼎盛支付'],
        ['id'=>2,'name'=>'蚂蚁支付'],
        ['id'=>3,'name'=>'彩虹易支付'],
        ['id'=>4,'name'=>'hiPay支付'],
    ];
}

/**
 * [获取游戏列表]
 * @param string $select
 * @return false|mixed|PDOStatement|string|\think\Collection
 * @author yyh
 */
function get_game_list($field = '', $map = [], $group = null, $order = 'id desc',$join=false)
{
    if (empty($field)) {
        $field = true;
    }
    if (AUTH_GAME != 1) {
        return [];
    }
    // 测试中的游戏不显示
    $map['test_game_status'] = 0;
    
    if(false===$join){
        //-增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        $view_game_ids = \think\Db::name('user')->where('id',session('ADMIN_ID'))->value('view_game_ids');
        if(!empty($view_game_ids)){
            $map['id'] = ['IN',$view_game_ids];
        }
        //-增加对登录管理员是否有游戏查看的限制-20210624-byh-end
        $list = think\Db::table('tab_game')->field($field)->where($map)->group($group)->order($order)->select()->toarray();
    }else{
        $view_game_ids = \think\Db::name('user')->where('id',session('ADMIN_ID'))->value('view_game_ids');
        if(!empty($view_game_ids)){
            $map['g.id'] = ['IN',$view_game_ids];
        }
        //-增加对登录管理员是否有游戏查看的限制-20210624-byh-end
        $list = think\Db::table('tab_game')->alias('g')
                ->field($field)
                ->join(['tab_game_attr'=>'a'],'g.id = a.game_id','left')
                ->where($map)
                ->group($group)
                ->order($order)
                ->select()
                ->toarray();
    }


    if (empty($list)) {
        return '';
    }
    return $list;
}
/**
 * [获取游戏列表]
 * 2021-6-3 14:33:27
 * modified by wjd
 */
function get_game_list2($field = '', $map = [], $group = null, $order = 'id desc',$current_promote_id=0)
{
    if (empty($field)) {
        $field = true;
    }
    if (AUTH_GAME != 1) {
        return [];
    }
    $forbid_game_ids = [];
    $allow_game_ids = [];
    $all_games_info = think\Db::table('tab_game')->field('id,game_name,promote_ids2,only_for_promote')->select()->toarray();
    if(!empty($all_games_info)){
        foreach($all_games_info as $k=>$v){
            if($v['only_for_promote'] == 1){  // 0 通用, 1 渠道独占
                $only_for_promote_ids = explode(',', $v['promote_ids2']);
                if(!in_array($current_promote_id, $only_for_promote_ids)){
                    $forbid_game_ids[] = $v['id'];  // 禁止申请的游戏
                }else{
                    $allow_game_ids[] = $v['id']; // 允许申请的游戏
                }
            }
        }
    }
    // $map['id'] = ['notin', $forbid_game_ids];
    // $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

    $list = think\Db::table('tab_game')->field($field)->where($map)->group($group)->order($order)->select()->toarray();
    if (empty($list)) {
        return '';
    }
    return $list;
}

/**
 * [获取登录时间]
 * @param string $login_time
 * @return false|string
 * @author 郭家屯[gjt]
 */
function get_login_time($login_time = '')
{
    if (empty($login_time)) return '--';
    $result = time() - $login_time;
    if ($result < 60) {
        return $result . "秒前";
    } elseif ($result / 3600 < 1) {
        $min = (int)($result / 60);
        return $min . '分钟前';
    } elseif ($result / 86400 < 1) {
        $hour = (int)($result / 3600);
        return $hour . '小时前';
    } else {
        return date('Y-m-d H:i:s', $login_time);
    }
}

/**
 * 获取对应类型的状态信息
 * @param int $group 状态分组
 * @param int $type 状态文字
 * @return string 状态文字 ，false 未获取到
 * @author
 */
function get_info_status($type = null, $group = 0,$is_array = 0)
{

    if (!isset($type) && empty($is_array)) {
        return false;
    }
    $arr = array(
        1 => [1 => '绑币', 2 => "平台币", 3 => "支付宝", 4 => "微信", 6 => '苹果支付'],
        2 => ['0' => '下单未付款', '1' => '充值成功', '2' => '订单异常'],
        3 => ['0' => '通知失败', '1' => '通知成功'],
        4 => ['0' => '已关闭', '1' => '开启'],
        5 => ['1' => '安卓', '2' => '苹果','3' => 'H5','4'=>'PC','1,2'=>'安卓,苹果','1,3'=>'安卓,H5','2,3'=>'苹果,H5','1,2,3'=>'安卓,苹果,H5'],
        6 => ['0' => '已关闭', '1' => '已开启'],
        7 => [0 => "不推荐", 1 => "推荐", 2 => "热门", 3 => "最新"],
        8 => ['0' => '未关联', '1' => '已关联', '2' => '数据错误'],
        9 => [0 => "未充值", 1 => "已充值"],
        10 => [0 => "待审核", 1 => "正常", '-1' => '已锁定'],
        11 => [1 => "锁定", -1 => "解锁", 0 => '审核'],
        12 => [0 => "待审核", 1 => '已审核'],
        13 => ['-1' => '打包失败', 0 => "未打包", 1 => '打包成功', 2 => '准备打包', 3 => '打包中'],
        14 => [1 => '单图', 2 => "多图", 3 => "文字链接", 4 => "代码"],
        15 => ['_blank' => "新页面", '_self' => "本页面"],
        16 => ['0' => '已关闭', '1' => '正常'],
        17 => ['0' => '未参与','1' => '参与', '2' => '不参与'],
        18 => ['1' => '已结算', '0' => '未结算'],
        19 => ['-1' => '未申请', '0' => '待审核', '1' => '已通过', '2' => '已驳回', '3' => '已打款'],
        20 => ['0' => '已锁定', '1' => '开启'],
        21 => ['0' => 'CPS', '1' => 'CPA'],
        22 => ['0' => '无折扣', 1 => '首充', 2=>'续充'],
        23 => ['0' => '进行中', 1 =>'已完成', 2=>'已超时'],
        24 => ['0' => '未发货', 1 =>'已发货'],
        25 => ['0' => '已关闭', '1' => '开启'],
        26 => ['1'=>'手游','2'=>'手游','3'=>'H5'],
        27 => ['0'=>'待审核',1=>'正常',2=>'隐藏'],
        28 => ['0'=>'个人',1=>'公会',2=>'公众号',3=>'其它'],
        29 => ['1'=>'余额到账异常',3=>'大笔订单预警',4=>'代充折扣异常',5=>'账户修改异常',6=>'后台发放异常'],
        30 => ['1'=>'绑币',2=>'平台币',3=>'游戏充值','4'=>'代充绑币'],
        31 => ['0' => '待处理', '1' => '已处理'],
        32 => ['0' => '兑换平台币', '1' => '支付宝提现', '2' => '微信提现'],
        33 => ['0' => '待打款', '1' => '正常','2' => '异常'],
        34 => ['0' => '预付款', '1' => '平台结算'],
        35 => ['1' => '安卓', '2' => 'IOS','3' => 'H5','4'=>'PC','1,2'=>'安卓,IOS','1,3'=>'安卓,H5','2,3'=>'IOS,H5','1,2,3'=>'安卓,IOS,H5'],
        36 => ['0'=>'待审核','1'=>'已通过','2'=>'已驳回','3'=>'已打款']
    );
    if($is_array){
        return $arr[$group];
    }else{
        return $arr[$group][$type];
    }
}

/**
 * [获取用户实体]
 * @param integer $id [description]
 * @param boolean $isAccount [description]
 * @return [yyh]             [description]
 * @change yyh 增加查询字段
 */
function get_user_entity($id = 0, $isAccount = false, $field = '*')
{
    if ($id == '') {
        return false;
    }
    $user = think\Db::table('tab_user');
    if ($isAccount) {
        $map['account'] = $id;
        $data = $user->field($field)->where($map)->find();
    } else {
        $data = $user->field($field)->find($id);
    }
    if (empty($data)) {
        return false;
    }
    return $data;
}

/**
 * [获取对应时间戳]
 * @param $type
 * @return string
 * @author 郭家屯[gjt]
 */
function total($type = 0, $returntype = 0)
{
    switch ($type) {
        case 1:
            { // 今天
                $start = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                $end = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
            };
            break;
        case 2:
            { // 本周
                //当前日期
                $sdefaultDate = date("Y-m-d");
                //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
                $first = 1;
                //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
                $w = date('w', strtotime($sdefaultDate));
                //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
                $week_start = date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days'));
                //本周结束日期
                $week_end = date('Y-m-d', strtotime("$week_start +6 days"));
                //当前日期
                $sdefaultDate = date("Y-m-d");
                //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
                $first = 1;
                //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
                $w = date('w', strtotime($sdefaultDate));
                //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
                $start = strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days');
                //本周结束日期
                $end = $start + 7 * 24 * 60 * 60 - 1;
            };
            break;
        case 3:
            { // 本月
                $start = mktime(0, 0, 0, date('m'), 1, date('Y'));
                $end = mktime(0, 0, 0, date('m') + 1, 1, date('Y')) - 1;
            };
            break;
        case 4:
            { // 本年
                $start = mktime(0, 0, 0, 1, 1, date('Y'));
                $end = mktime(0, 0, 0, 1, 1, date('Y') + 1) - 1;
            };
            break;
        case 5:
            { // 昨天
                $start = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
                $end = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
            };
            break;
        case 6:
            { // 上周
                $start = mktime(0, 0, 0, date("m"), date("d") - date("w") + 1 - 7, date("Y"));
                $end = mktime(23, 59, 59, date("m"), date("d") - date("w") + 7 - 7, date("Y"));
            };
            break;
        case 7:
            { // 上月
                $start = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
                $end = mktime(23, 59, 59, date("m"), 0, date("Y"));
            };
            break;
        case 8:
            { // 上一年
                $start = mktime(0, 0, 0, date('m') - 11, 1, date('Y'));
                $end = mktime(0, 0, 0, date('m') + 1, 1, date('Y')) - 1;
            };
            break;
        case 9:
            { // 前七天
                $start = mktime(0, 0, 0, date('m'), date('d') - 6, date('Y'));
                $end = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
            };
            break;
        case 10:
            { // 前30天
                $start = mktime(0, 0, 0, date('m'), date('d') - 29, date('Y'));
                $end = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
            };
            break;
        case 11:
            { // 前天
                $start = mktime(0, 0, 0, date('m'), date('d') - 2, date('Y'));
                $end = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')) - 1;
            };
            break;
        case 12:
            { // 明天
                $start = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y'));
                $end = mktime(0, 0, 0, date('m'), date('d') + 2, date('Y')) - 1;
            };
            break;
        default:
            $start = '';
            $end = '';
    }
    if ($returntype == 0) {
        return " between $start and $end ";
    } elseif ($returntype == 1) {
        return ['between', [$start, $end]];
    } elseif ($returntype == 2) {
        return [$start, $end];
    }
}

/**
 * 记录管理员操作记录
 * @param $action_name   操作名称
 * @param string $action_url 操作url
 * @return bool
 * @auther <jszsl001@163.com>
 */
function write_action_log($action_name, $action_url = '')
{
    if (empty($action_url)) {
        $action_url = $_SERVER["REQUEST_URI"];
    }
    if(!cmf_get_current_admin_id()){
        return false;
    }
    $data['action_name'] = $action_name;
    $data['action_url'] = $action_url;
    $data['admin_name'] = think\Db::table('sys_user')->where(['id' => cmf_get_current_admin_id()])->value('user_login')?:'';
    $data['action_time'] = time();
    $data['client_ip'] = get_client_ip();
    $data['is_delete'] = 1;

    $res = \think\Db::table('tab_admin_action_log')->insert($data);

    return !!$res;
}

/**
 * 根据管理员id获取管理员名称
 */
function get_admin_name($id)
{

    $admin_name = think\Db::table('sys_user')->where(['id' => $id])->value('user_login');

    return $admin_name ? $admin_name : '';

}

/**
 * [get_admin_lists 管理员列表]
 * @return [type] [description]
 */
function get_admin_lists()
{
    $admin_name = think\Db::table('sys_user')->field('id,user_login')->select()->toarray();

    return empty($admin_name) ? [] : $admin_name;
}

/**
 * [null_to_0 小数点]
 * @param  [type] $num [description]
 * @return [type]      [description]
 */
function null_to_0($num,$float=2)
{
    if ($num) {
        return sprintf("%.{$float}f", $num);
    } else {
        return sprintf("%.{$float}f", 0);
    }
}

/**
 * [获取用户信息]
 * @param bool $field
 * @param array $map
 * @return array|false|PDOStatement|string|\think\Model
 * @author 郭家屯[gjt]
 */
function get_user_info($field = true, $map = [])
{
    if (!$field) {
        $field = true;
    }
    $data = think\Db::table('tab_user')->field($field)->where($map)->find();
    return $data;
}

/**
 * [get_game_name 获取游戏名称]
 * @param  [type] $game_id [description]
 * @param string $field [description]
 * @return [type]          [description]
 * @author [郭家屯] <[<email address>]>
 */
function get_game_name($game_id = null, $field = 'id')
{
    $map[$field] = $game_id;
    $data = think\Db::table('tab_game')->field('game_name')->where($map)->find();
    if (empty($data)) {
        return ' ';
    }
    return $data['game_name'];
}

/**
 * [获取游戏类型]
 * @author 郭家屯[gjt]
 */
function get_game_type_all($field="id,type_name")
{
    if (AUTH_GAME != 1) {
        return false;
    }
    $map['status'] = 1;
    $data = think\Db::table('tab_game_type')->field($field)->where($map)->order('sort desc,id desc')->select();
    return $data ? $data : [];
}

/**
 *随机生成字符串
 * @param  $len int 字符串长度
 * @return string
 * @author yyh
 */
function sp_random_string($len = 6)
{
    $chars = array(
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
        "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
        "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
        "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
        "3", "4", "5", "6", "7", "8", "9"
    );
    $charsLen = count($chars) - 1;
    shuffle($chars);    // 将数组打乱
    $output = "";
    for ($i = 0; $i < $len; $i++) {
        $output .= $chars[mt_rand(0, $charsLen)];
    }
    return $output;
}

//生成订单号yyh
function build_order_no()
{
    return date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
}

/**
 * [获取游戏实体]
 * @param integer $id [description]
 * @param string $field [description]
 * @return [yyh]             [description]
 */
function get_game_entity($id = 0, $field = '*')
{
    if ($id == '') {
        return false;
    }
    $model = think\Db::table('tab_game');
    $data = $model->field($field)->where('id', $id)->find();
    if (empty($data)) {
        return false;
    }
    return $data;
}

/**
 * 判断字符串是否符合手机号码格式
 * 移动号段: 134,135,136,137,138,139,147,150,151,152,157,158,159,170,178,182,183,184,187,188
 * 联通号段: 130,131,132,145,155,156,170,171,175,176,185,186
 * 电信号段: 133,149,153,170,173,177,180,181,189
 * 2018年
 * @author [yyh] <[<email address>]>
 */
function isMobileNO($value)
{

    if (preg_match("/^[1]([3-9])[0-9]{9}$/", $value)) {
        return true;
    } else {
        return false;
    }

}

/**
 * [获取用户名称]
 * @param int $id
 * @return bool|string
 * @author 郭家屯[gjt]
 */
function get_user_name($id = 0)
{
    if (empty($id)) {
        return '';
    }
    $user = think\Db::table('tab_user');
    $data = $user->field('account')->where('id', $id)->find();
    if (empty($data)) {
        return false;
    }
    return $data['account'];
}

/**
 * @获取用户id
 *
 * @author: zsl
 * @since: 2021/8/11 21:55
 */
function get_user_id($account = '')
{
    if (empty($account)) {
        return '';
    }
    $user = think\Db ::table('tab_user');
    $id = $user -> field('account') -> where('account', $account) -> value('id');
    if (empty($id)) {
        return false;
    }
    return $id;
}


/**
 * 获取苹果包名
 * @param  [type] $game_id [description]
 * @return [type]          [description]
 * @author [yyh] <[<email address>]>
 */
function get_payload_name($game_id = '',$sdk_version=0)
{
    if (!$game_id) return '';
    $map['game_id'] = $game_id;
    if($sdk_version){
        $map['file_type'] = $sdk_version;
    }
    $find = think\Db::table('tab_game_source')->field('bao_name')->where($map)->find();
    return $find['bao_name'];
}

/**
 * [get_pay_way description]
 * @param string $pay_way [description]
 * @return [type]          [description]
 * yyh
 */
function get_pay_way($pay_way = '')
{
    switch ($pay_way) {
        case '1':
            return '绑币';
            break;
        case '2':
            return '平台币';
            break;
        case '3':
            return '支付宝';
            break;
        case '4':
            return '微信';
            break;
        case '5':
            return '金猪';
            break;
        case '6':
            return '苹果内购';
            break;
        default:
            return '支付宝';
            break;
    }

}

/**
 * @根据订单号获取支付方式
 *
 * @param $pay_order_number
 *
 * @author: zsl
 * @since: 2021/1/21 16:39
 */
function get_pay_way_by_order_no($pay_order_number)
{
    $pay_way_code = \think\Db ::table('tab_spend') -> where(['pay_order_number' => $pay_order_number]) -> value('pay_way');
    $pay_way = get_pay_way($pay_way_code);
    if ($pay_way == 'error') {
        return '';
    }
    return $pay_way;
}

/**
 * 模拟post进行url请求
 * @param string $url
 * @param string $param
 * gjt
 */
function request_post($url = '', $param = '')
{
    if (empty($url) || empty($param)) {
        return false;
    }

    $postUrl = $url;
    $curlPost = $param;
    $ch = curl_init();//初始化curl
    curl_setopt($ch, CURLOPT_URL, $postUrl);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//跟随301跳转
    $data = curl_exec($ch);//运行curl
    curl_close($ch);

    return $data;
}

/**
 * [二维数组 按照某字段排序]
 * @param  [type] $arrays     [description]
 * @param  [type] $sort_key   [description]
 * @param  [type] $sort_order [description]
 * @param  [type] $sort_type  [description]
 * @return [type]             [description]
 * @author [yyh] <[<email address>]>
 */
function my_sort($arrays, $sort_key, $sort_order = SORT_ASC, $sort_type = SORT_NUMERIC)
{
    if (is_array($arrays)) {
        foreach ($arrays as $array) {
            if (is_array($array)) {
                $key_arrays[] = $array[$sort_key];
            } else {
                return false;
            }
        }
    } else {
        return false;
    }
    array_multisort($key_arrays, $sort_order, $sort_type, $arrays);
    return $arrays;
}

/**
 * [获取当前网址]
 * @return string
 * @author 郭家屯[gjt]
 */
function getCurUrl()
{
    if (!empty($_SERVER["REQUEST_URI"])) {
        $scriptName = $_SERVER["REQUEST_URI"];
        $nowurl = $scriptName;
    } else {
        $scriptName = $_SERVER["PHP_SELF"];
        if (empty($_SERVER["QUERY_STRING"])) {
            $nowurl = $scriptName;
        } else {
            $nowurl = $scriptName . "?" . $_SERVER["QUERY_STRING"];
        }
    }
    return $nowurl;
}

/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key 加密密钥
 * @param int $expire 过期时间 单位 秒
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function think_encrypt($data, $key = '', $expire = 0)
{
    $key = md5(empty($key) ? config('database.authcode') : $key);
    $data = base64_encode($data);
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    $str = sprintf('%010d', $expire ? $expire + time() : 0);

    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
    }
    return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
}

/**
 * 系统解密方法
 * @param string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
 * @param string $key 加密密钥
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function think_decrypt($data, $key = '')
{
    $key = md5(empty($key) ? config('database.authcode') : $key);
    $data = str_replace(array('-', '_'), array('+', '/'), $data);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $data = base64_decode($data);
    $expire = substr($data, 0, 10);
    $data = substr($data, 10);

    if ($expire > 0 && $expire < time()) {
        return '';
    }
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = $str = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        } else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}

/**
 * [检查用户名格式]
 * @param string $account
 * @return int
 * @author 郭家屯[gjt]
 */
function check_account($account = '')
{
    $pattern = '/^[A-Za-z0-9]+$/';
    if (!preg_match($pattern, $account)) {
        return 1011;
    }
    return 200;

}

/**
 * [检查密码格式]
 * @param string $account
 * @return int
 * @author 郭家屯[gjt]
 */
function check_password($account = '')
{
//    $pattern = '/^[A-Za-z0-9]+$/';
//    if (!preg_match($pattern, $account)) {
//        return 1012;
//    }
    if (!preg_match("/^[A-Za-z0-9]+$/", $account) || strlen($account) < 6 || strlen($account) > 15) {
        return 1012;
    }
    return 200;

}

/**
 * [检查账号名称是否存在]
 * @param $account
 * @author 郭家屯[gjt]
 */
function checkAccount($account = '')
{
    $user = get_user_entity($account, true);
    if (empty($user)) {
        return false;
    } else {
        return true;
    }
}

//获取渠道父类id
function get_fu_id($id = '')
{
    if (empty($id)) {
        return 0;
    }
    $map['id'] = $id;
    $pro = \think\Db::table('tab_promote')->field('id,parent_id')->where($map)->find();
    if (null == $pro || $pro['parent_id'] == 0) {
        return 0;
    } else {
        return $pro['parent_id'];
    }
}

//获取渠道父账号
function get_parent_name($id = '')
{
    if (empty($id)) {
        return '';
    }
    $map['id'] = $id;
    $pro = \think\Db::table('tab_promote')->field('id,parent_id,parent_name')->where($map)->find();
    if (null == $pro || $pro['parent_id'] == 0) {
        return '';
    } else {
        return empty($pro['parent_name']) ? get_promote_name($pro['parent_id']) : $pro['parent_name'];
    }
}

/*
 * 根据身份证号判断是否成年
 * @param   sting  $idcard  身份证号码
 * @author 鹿文学
 */
function is_adult($idcard = '')
{
    $id = substr($idcard, 6, 8);
    $year = substr($id, 0, 4);
    $month = substr($id, 4, 2);
    $day = substr($id, 6, 2);
    $old = (time() - strtotime($year . '-' . $month . '-' . $day)) / 31536000;
    if (intval($old) >= 18) {
        return true;
    } else {
        return false;
    }
}

//对年龄的审核
function age_verify($cardno = '', $name = '', $appcode = '')
{
    $date = age($cardno, $name, $appcode);
    if ($date['resp']['code'] == 0 && $date > 0) {
        $age = floor((time() - strtotime($date['data']['birthday'])) / (60 * 60 * 24 * 365));
        if ($age > 17) {
            return 1;
        } else {
            return 2;
        }
    } elseif ($date['resp']['code'] != 0 && $date > 0) {
        return 0;
    } else {
        return $date;
    }
}

//根据配置向接口发送身份证号和姓名进行验证
function age($cardno = '', $name = '', $appcode = '')
{
    $host = "http://idcard.market.alicloudapi.com";
    $path = "/lianzhuo/idcard";
    $method = "GET";
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . $appcode);
    $querys = "cardno=" . $cardno . "&name=" . $name;
    $bodys = "";
    $url = $host . $path . "?" . $querys;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    if (1 == strpos("$" . $host, "https://")) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    $output = curl_exec($curl);
    if (empty($output)) {
        return -1;//用完
    }
    if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == '200') {
        $headersize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($output, 0, $headersize);
        $body = substr($output, $headersize);
        curl_close($curl);
        return json_decode($body, true);
    } else {
        return -2;//失败
    }
}

/**
 * [客服问题]
 * @return array
 * @author 郭家屯[gjt]
 */
function get_kf_type($map = [])
{
    $map['status'] = 1;
    $type = \think\Db::table('tab_kefuquestion_type')->field('id,name')->where($map)->select();
    if (empty($type)) {
        return [];
    }
    return $type;
}

/*
获取sdk微信参数
@author  gjt
 */
function get_game_login_param($game_id = '', $type = 0)
{
    if (empty($game_id)) {
        return '';
    }
    $map['game_id'] = $game_id;
    $map['type'] = $type;
    $map['status'] = 1;
    $res = \think\Db::table('tab_user_param')->field('openid,wx_appid,appsecret')->where($map)->find();
    if (empty($res)) {
        return '';
    } else {
        return $res;
    }
}

/**
 * 获取qq登录唯一值
 * @param  [type] $access_token [description]
 * @return [type]               [description]
 */
function get_union_id($access_token)
{
    $url = "https://graph.qq.com/oauth2.0/me?access_token=" . $access_token . "&unionid=1";
    $content = file_get_contents($url);
    $packname1 = '/\"unionid\"\:\"(.*?)\"/';
    preg_match($packname1, $content, $packname2);
    $packname = $packname2[1];
    return $packname;
}

/**
 * 生成唯一订单号
 */
function create_out_trade_no($prefix = '')
{
    $out_trade_no = $prefix . date('Ymd') . date('His') . sp_random_string(4);
    if ($prefix == 'PF_') {//玩家充值平台币
        $result = \think\Db::table('tab_spend_balance')->field('id')->where('pay_order_number', $out_trade_no)->find();
    } elseif ($prefix == "SP_") {//玩家游戏订单
        $result = \think\Db::table('tab_spend')->field('id')->where('pay_order_number', $out_trade_no)->find();
    } elseif ($prefix == "PB_") {//玩家充值绑币
        $result = \think\Db::table('tab_spend_bind')->field('id')->where('pay_order_number', $out_trade_no)->find();
    }elseif ($prefix == "TD_") {//推广员充值平台币
        $result = \think\Db::table('tab_promote_deposit')->field('id')->where('pay_order_number', $out_trade_no)->find();
    }elseif ($prefix == "BR_") {//推广员充值绑定平台币
        $result = \think\Db::table('tab_promote_bind')->field('id')->where('pay_order_number', $out_trade_no)->find();
    }elseif($prefix == "TO_"){//购买小号
        $result = \think\Db::table('tab_user_transaction_order')->field('id')->where('pay_order_number', $out_trade_no)->find();
    }elseif($prefix == "FF_"){//联运分发订单
        $result = \think\Db::table('tab_issue_spend')->field('id')->where('pay_order_number', $out_trade_no)->find();
    }elseif($prefix == "LY_"){//联运币订单
        $result = \think\Db::table('tab_issue_open_user_balance')->field('id')->where('pay_order_number', $out_trade_no)->find();
    }elseif($prefix == 'MC_'){//尊享卡
        $result = \think\Db::table('tab_user_member')->field('id')->where('pay_order_number', $out_trade_no)->find();
    }elseif($prefix == 'TX'){//提现
        $result = \think\Db::table('tab_user_tplay_withdraw')->field('id')->where('pay_order_number', $out_trade_no)->find();
    } elseif($prefix == 'PP_'){//渠道充值预付款订单
        $result = \think\Db::table('tab_promote_prepayment_recharge')->field('id')->where('pay_order_number', $out_trade_no)->find();
    }else {
        return '';
    }
    if ($result) {
        create_out_trade_no($prefix);
    } else {
        return $out_trade_no;
    }
}

/**
 * [获取支付设置开关]
 * @param string $name
 * @param string $cnofig
 * @return int
 * @author 郭家屯[gjt]
 */
function pay_type_status($name = '', $config = '')
{
    if (empty($name)) {
        return 0;
    }
    $result = \think\Db::table('tab_spend_payconfig')->where('name', $name)->field('config,status')->find();
    if (empty($result)) {
        return 0;
    }
    if (empty($config)) {
        return $result['status'];
    } else {
        $result['config'] = json_decode($result['config'], true);
        if ($result['status'] == 1 && $result['config'][$config] == 1) {
            return 1;
        } else {
            return 0;
        }
    }
}

/**
 * [获取支付设置]
 * @param string $name
 * @author 郭家屯[gjt]
 */
function get_pay_type_set($name = '')
{
    if (empty($name)) {
        return '';
    }
    $result = \think\Db::table('tab_spend_payconfig')->where('name', $name)->field('config,status')->find();
    if (empty($result)) {
        return '';
    }
    $result['config'] = json_decode($result['config'], true);
    return $result;
}

/**
 * 获取平台币或绑币的充值状态
 * by:byh-20210701
 */
function get_ptb_bind_recharge_status($name ='')
{
    if(empty($name) || ($name != 'ptb_pay' && $name != 'bind_pay')){
        return '';
    }
    $result = \think\Db::table('tab_spend_payconfig')->where('name', $name)->field('config,status')->find();
    if (empty($result)) {
        return '';
    }
    if($result['status'] == 0){
        return '0';
    }
    $result['config'] = json_decode($result['config'], true);
    //处理平台币或绑币的充值字段状态
    if($result['config']['recharge_status'] == 'off'){
        return '0';
    }else{//未配置或不为'off'时都是默认开启
        return '1';
    }


}

/**
 * 判断是否SSL协议
 * @return boolean
 */
function is_ssl()
{
    if (isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))) {
        return true;
    } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
        return true;
    }
    return false;
}

/**
 * [将XML转为数组]
 * @param $xml
 * @return mixed
 * @author 郭家屯[gjt]
 */
function xmlToArray($xml = '')
{
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
}

// 渠道是否登录
// yyh
function is_p_login()
{
    return session('PID');
}
function is_issue_login()
{
    return session('issue_open_user_info');
}

//签名字符串方法
//YYH
function sortData($data)
{
    ksort($data);
    foreach ($data as $k => $v) {
        $tmp[] = $k . '=' . urlencode($v);
    }
    $str = implode('&', $tmp);
    return $str;
}

/**
 * [seo显示]
 * @param string $str
 * @param array $array
 * @param string $site
 * @param string $meat
 * @return mixed|null|string|string[]
 * @author 郭家屯[gjt]
 */
function seo_replace($str = '', $array = array(), $site = 'media', $meat = 'title')
{
    switch ($site) {
        case 'channel':
            $config = cmf_get_option('promote_set');
            switch ($meat) {
                case 'title':
                    $title = $config['ch_set_title'];
                    break;
                case 'keywords':
                    $title = $config['ch_set_meta_key'];
                    break;
                case 'description':
                    $title = $config['ch_set_meta_desc'];
                    break;
                default:
                    $title = $config['ch_set_title'];
                    break;
            }
            break;
        case 'media':
            $config = cmf_get_option('media_set');
            switch ($meat) {
                case 'title':
                    if (session('union_host')) {
                        $union_set = json_decode(session('union_host')['union_set'], true);
                        $title = $union_set['site_name'] ? $union_set['site_name'] : $config['pc_set_title'];
                    } else {
                        $title = $config['pc_set_title'];
                    }
                    break;
                case 'description':
                    $title = $config['pc_set_meta_desc'];
                    break;
                default:
                    $title = $config['pc_set_title'];
                    break;
            }
            break;
        case 'wap':
            $config = cmf_get_option('wap_set');
            switch ($meat) {
                case 'title':
                    if (session('union_host')) {
                        $union_set = json_decode(session('union_host')['union_set'], true);
                        $title = $union_set['site_name'] ? $union_set['site_name'] : $config['wap_set_title'];
                    } else {
                        $title = $config['wap_set_title'];
                    }
                    break;
                default:
                    $title = $config['wap_set_title'];
                    break;
            }
            break;
        default:
            $config = cmf_get_option('media_set');
            $title = $config['pc_set_title'];
            break;
    }

    if (empty($str)) {
        return $title;
    }
    $find = array('%webname%', '%gamename%', '%newsname%', '%giftname%', '%gametype%', '%catetitle%', '%gamedevice%');
    $replace = array($title, $array['game_name'], $array['news_title'], $array['giftbag_name'], $array['game_type_name'], $array['cate_title'], $array['game_device']);
    $str = str_replace($find, $replace, $str);
    return preg_replace('/((-|_)+)?((%[0-9A-Za-z_]*%)|%+)((-|_)+)?/', '', $str);
}


//验证短信
function sdkchecksendcode($phone, $limit, $type = 1)
{
    //$number = \think\Db::table('tab_sms_log')->where(array('create_ip' => get_client_ip(), 'send_status' => '000000', 'send_time' => array(array('egt', strtotime('today')), array('elt', strtotime('tomorrow')))))->count();
    //send_time条件之前未写入,$number=0,if语句一直未生效,此处修改为create_time条件-20210423-byh
    $number = \think\Db::table('tab_sms_log')->where(array('create_ip' => get_client_ip(), 'send_status' => '000000', 'create_time' => array(array('egt', strtotime('today')), array('elt', strtotime('tomorrow')))))->count();
    if (!empty($limit) && $number >= $limit) {
        if(request()->module()=='sdk' || request()->module()=='sdksimplify' || request()->module()=='app'){
            $msg = array(
                "code" => 1072,
                "msg" => '短信验证已达上限，请明天再试',
                "data" => []
            );
            if(request()->module()=='app'){
                return '短信验证已达上限，请明天再试';
            }
            echo base64_encode(json_encode($msg));exit;
        }else{
            return 1072;
        }

    }
    $request_time = time();
    $map = array('phone' => $phone);
    $map['create_time'] = array(array('egt', ($request_time - 60)), array('elt', $request_time));
    $number = $time = \think\Db::table('tab_sms_log')->where($map)->count();
    if ($number > 0) {
        if(request()->module()=='sdk' || request()->module()=='sdksimplify' || request()->module()=='app'){
            $msg = array(
                "code" => 1073,
                "msg" => '请一分钟后再次尝试',
                "data" => []
            );
            if(request()->module()=='app'){
                return '请一分钟后再次尝试';
            }
            echo base64_encode(json_encode($msg));exit;
        }else {
            return 1073;
        }
    }
}

/**
 * 短信验证码数值
 */
function sms_rand($session_name){
    $sms_code = session($session_name);
    /*检查session中是否存在有效分钟内的短信验证码信息，有则直接使用，无需生成新的验证码*/
    if ($sms_code && (time() - $sms_code['time']) / 60 < $sms_code['delay']) {
        $res['rand'] = $sms_code['code'];
        $res['new_rand'] = false;
    } else {
        request()->session([$session_name => null]);
        $res['rand'] = rand(100000, 999999);
        $res['new_rand']  = true;
    }
    return $res;
}
/**
 * @函数或方法说明
 * @获取分类名称
 * @param string $category_id
 *
 * @return string
 *
 * @author: 郭家屯
 * @since: 2019/4/26 14:26
 */
function get_category_name($category_id = '')
{
    if (empty($category_id)) {
        return '';
    }
    $data = \think\Db::table('sys_portal_category')->field('name')->where('id', $category_id)->find();
    if (empty($data)) {
        return '';
    } else {
        return $data['name'];
    }
}

/**
 * [时间格式化]
 * @author 郭家屯[gjt]
 */
function show_time($time = '', $type = 1)
{
    if (empty($time)) {
        return '永久';
    } else {
        if ($type == 1) {
            return date('Y-m-d H:i:s', $time);
        } else {
            return date('Y-m-d', $time);
        }
    }
}

//判断客户端是否是在微信客户端打开
function is_weixin()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return true;
    }
    return false;
}

//用户名、邮箱、手机账号中间字符串以*隐藏
function hideStar($str = '')
{
    if (empty($str)) return '';
    if (strpos($str, '@')) {
        $email_array = explode("@", $str);
        $prevfix = substr($str, 0, 1); //邮箱前缀
        $prevfix_hou = substr($email_array[0], -1); //邮箱前缀
        $str = $email_array[1];
        $rs = $prevfix . '****' .$prevfix_hou. "@" . $str;
    } else {
        $pattern = '/(1[3456789]{1}[0-9])[0-9]{4}([0-9]{4})/i';
        if (preg_match($pattern, $str)) {
            $rs = preg_replace($pattern, '$1****$2', $str); // substr_replace($name,'****',3,4);
        } else {
            $rs = substr($str, 0, 1) . "****************" . substr($str, -1);
        }
    }
    return $rs;
}

/**
 * @函数或方法说明
 * @隐藏真实名称
 * @param string $str
 *
 * @return string
 *
 * @author: 郭家屯
 * @since: 2019/4/26 14:28
 */
function hideRealName($str = '')
{
    if (empty($str)) {
        return '';
    }
    $len = mb_strlen($str, 'utf-8') - 1;
    $subfix = '';
    while ($len > 0) {
        $subfix .= '*';
        $len--;
    }
    return mb_substr($str, 0, 1, 'utf-8') . '**';
}

/**
 * [获取支付时间]
 * @param string $pay_time
 * @return false|string
 * @author 郭家屯[gjt]
 */
function get_pay_time($pay_time = '')
{
    if (empty($pay_time)) return '--';
    // 今天最大时间
    $todayLast   = strtotime(date('Y-m-d 23:59:59'));
    $agoTimeTrue = time() - $pay_time;
    $agoTime     = $todayLast - $pay_time;
    $agoDay      = floor($agoTime / 86400);
    if ($agoDay == 0) {
        $msg = '今天';
    } elseif ($agoDay == 1) {
        $msg = '昨天';
    } else {
        $msg = date('m-d', $pay_time);
    }
    return $msg . '  ' . date('H:i', $pay_time);
}

/**
 * [判断手机类型]
 * @return int|string
 * @author 郭家屯[gjt]
 */
function get_devices_type()
{
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $type = '';
    if (strpos($agent, 'iphone') || strpos($agent, 'ipad') || strpos($agent, 'Mac OS')) {
        $type = 2;
    } elseif (strpos($agent, 'android')) {
        $type = 1;
    }
    return $type;
}

/**
 * @获取真实设备名称
 *
 * @author: zsl
 * @since: 2021/5/19 10:20
 */
function get_real_devices_name($device = '')
{
    $map = [
            "iPhone3,1" => "iPhone 4",
            "iPhone3,2" => "iPhone 4",
            "iPhone3,3" => "iPhone 4",
            "iPhone4,1" => "iPhone 4S",
            "iPhone5,1" => "iPhone 5",
            "iPhone5,2" => "iPhone 5 (GSM+CDMA)",
            "iPhone5,3" => "iPhone 5c (GSM)",
            "iPhone5,4" => "iPhone 5c (GSM+CDMA)",
            "iPhone6,1" => "iPhone 5s (GSM)",
            "iPhone6,2" => "iPhone 5s (GSM+CDMA)",
            "iPhone7,1" => "iPhone 6 Plus",
            "iPhone7,2" => "iPhone 6",
            "iPhone8,1" => "iPhone 6s",
            "iPhone8,2" => "iPhone 6s Plus",
            "iPhone8,4" => "iPhone SE",
            "iPhone9,1" => "iPhone 7",
            "iPhone9,2" => "iPhone 7 Plus",
            "iPhone9,3" => "iPhone 7",
            "iPhone9,4" => "iPhone 7 Plus",
            "iPhone10,1" => "iPhone_8",
            "iPhone10,4" => "iPhone_8",
            "iPhone10,2" => "iPhone_8_Plus",
            "iPhone10,5" => "iPhone_8_Plus",
            "iPhone10,3" => "iPhone X",
            "iPhone10,6" => "iPhone X",
            "iPhone11,8" => "iPhone XR",
            "iPhone11,2" => "iPhone XS",
            "iPhone11,6" => "iPhone XS Max",
            "iPhone11,4" => "iPhone XS Max",
            "iPhone12,1" => "iPhone 11",
            "iPhone12,3" => "iPhone 11 Pro",
            "iPhone12,5" => "iPhone 11 Pro Max",
            "iPhone12,8" => "iPhone SE2",
            "iPhone13,1" => "iPhone 12 mini",
            "iPhone13,2" => "iPhone 12",
            "iPhone13,3" => "iPhone 12 Pro",
            "iPhone13,4" => "iPhone 12 Pro Max",
            "iPod1,1" => "iPod Touch 1G",
            "iPod2,1" => "iPod Touch 2G",
            "iPod3,1" => "iPod Touch 3G",
            "iPod4,1" => "iPod Touch 4G",
            "iPod5,1" => "iPod Touch (5 Gen)",
            "iPad1,1" => "iPad",
            "iPad1,2" => "iPad 3G",
            "iPad2,1" => "iPad 2 (WiFi)",
            "iPad2,2" => "iPad 2",
            "iPad2,3" => "iPad 2 (CDMA)",
            "iPad2,4" => "iPad 2",
            "iPad2,5" => "iPad Mini (WiFi)",
            "iPad2,6" => "iPad Mini",
            "iPad2,7" => "iPad Mini (GSM+CDMA)",
            "iPad3,1" => "iPad 3 (WiFi)",
            "iPad3,2" => "iPad 3 (GSM+CDMA)",
            "iPad3,3" => "iPad 3",
            "iPad3,4" => "iPad 4 (WiFi)",
            "iPad3,5" => "iPad 4",
            "iPad3,6" => "iPad 4 (GSM+CDMA)",
            "iPad4,1" => "iPad Air (WiFi)",
            "iPad4,2" => "iPad Air (Cellular)",
            "iPad4,4" => "iPad Mini 2 (WiFi)",
            "iPad4,5" => "iPad Mini 2 (Cellular)",
            "iPad4,6" => "iPad Mini 2",
            "iPad4,7" => "iPad Mini 3",
            "iPad4,8" => "iPad Mini 3",
            "iPad4,9" => "iPad Mini 3",
            "iPad5,1" => "iPad Mini 4 (WiFi)",
            "iPad5,2" => "iPad Mini 4 (LTE)",
            "iPad5,3" => "iPad Air 2",
            "iPad5,4" => "iPad Air 2",
            "iPad6,3" => "iPad Pro 9.7",
            "iPad6,4" => "iPad Pro 9.7",
            "iPad6,7" => "iPad Pro 12.9",
            "iPad6,8" => "iPad Pro 12.9",
            "AppleTV2,1" => "Apple TV 2",
            "AppleTV3,1" => "Apple TV 3",
            "AppleTV3,2" => "Apple TV 3",
            "AppleTV5,3" => "Apple TV 4",
            "i386" => "Simulator",
            "x86_64" => "Simulator",
    ];
    if (array_key_exists($device, $map)) {
        return $map[$device];
    }
    return $device;

}

/**
 * [检查链接地址是否有效]
 * @param $url
 * @return bool
 * @author 郭家屯[gjt]
 */
function varify_url($url)
{
    if(strpos($url,'http') === false){
        return false;
    }
    return true;
}

/**
 * 获取联盟站游戏列表
 * 郭家屯
 */
function get_promote_game_id($promote_id = 0)
{
    if (empty($promote_id)) return [];
    $promote = get_promote_entity($promote_id,'game_ids');
    $ungame_ids = empty($promote['game_ids']) ? [] : explode(',',$promote['game_ids']);
    if($ungame_ids){
        $map['game_id'] = ['notin',$ungame_ids];
    }
    $map['g.down_port'] = ['in',[1,3]];
    $map['g.test_game_status'] = 0;
    $list = think\Db::table('tab_promote_apply')
            ->field('game_id')
            ->alias('pa')
            ->join(['tab_game' => 'g'], 'g.id=pa.game_id', 'left')
            ->where('g.game_status', 1)
            //->where('g.down_port',1)
            ->where('pa.status', 1)
            //->where('pa.enable_status',1)
            ->where($map)
            ->where('pa.promote_id', $promote_id)
            ->select()->toArray();
    return $list ? array_column($list, 'game_id') : ['-1'];
}

/**
 * @函数或方法说明
 * @获取游戏下载状态
 * @param int $relation_game_id
 * @param int $sdk_version
 *
 * @return bool
 *
 * @author: 郭家屯
 * @since: 2019/3/27 19:23
 */
function get_down_status($relation_game_id = 0, $sdk_version = 0, $promote_id = 0)
{
    if (empty($relation_game_id)) {
        return false;
    }
    if(empty($sdk_version)){
        $sdk_version = 1;
    }
    $game = think\Db::table('tab_game')
        ->field('and_dow_address,ios_dow_plist,add_game_address,ios_game_address,down_port,only_for_promote,promote_ids2')
        ->where('game_status', 1)
        ->where('dow_status', 1)
        ->where('relation_game_id', $relation_game_id)
        ->where('sdk_version', $sdk_version)
        ->find();
    if (empty($game)) {
        return false;
    }
    // 如果是渠道独占游戏,查看当前渠道是否在渠道独占中
    if ($game['only_for_promote'] == 1) {
        $promote_ids2 = explode(',', $game['promote_ids2']);
        if (!in_array($promote_id,$promote_ids2)) {
            return false;
        }
    }
    //第三方链接下载
    if ($game['down_port'] == 2) {
        if ($sdk_version == 1 && $game['add_game_address']) {
            return true;
        } elseif ($sdk_version == 2 && $game['ios_game_address']) {
            return true;
        } else {
            return false;
        }
    }elseif( $game['down_port'] == 3){
        //超级签下载状态
        if ($sdk_version == 2 && $game['ios_game_address']) {
            return true;
        } else {
            return false;
        }
    } else {//官方下载地址
        if ($sdk_version == 1 && varify_url(cmf_get_file_download_url($game['and_dow_address']))) {
            return true;
        } elseif ($sdk_version == 2 &&  $game['ios_dow_plist']&&varify_url(cmf_get_domain() . '/upload/' . $game['ios_dow_plist'])) {
            return true;
        } else {
            return false;
        }
    }

}
// 获取游戏下载状态 加上条件 测试游戏 不显示在页面上 by wjd 2021-5-17 17:29:35
function get_down_status2($relation_game_id = 0, $sdk_version = 0)
{
    if (empty($relation_game_id)) {
        return false;
    }
    if(empty($sdk_version)){
        $sdk_version = 1;
    }

    $map_tmp = [];
    $map_tmp['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
    $map_tmp['only_for_promote'] = 0;  // 渠道独占的游戏不显示

    $game = think\Db::table('tab_game')
        ->field('and_dow_address,ios_dow_plist,add_game_address,ios_game_address,down_port')
        ->where('game_status', 1)
        ->where('dow_status', 1)
        ->where('relation_game_id', $relation_game_id)
        ->where('sdk_version', $sdk_version)
        ->where($map_tmp)  // 测试游戏不显示 // 渠道独占的游戏不显示
        ->find();
    if (empty($game)) {
        return false;
    }
    //第三方链接下载
    if ($game['down_port'] == 2) {
        if ($sdk_version == 1 && $game['add_game_address']) {
            return true;
        } elseif ($sdk_version == 2 && $game['ios_game_address']) {
            return true;
        } else {
            return false;
        }
    }elseif( $game['down_port'] == 3){
        //超级签下载状态
        if ($sdk_version == 2 && $game['ios_game_address']) {
            return true;
        } else {
            return false;
        }
    } else {//官方下载地址
        if ($sdk_version == 1 && varify_url(cmf_get_file_download_url($game['and_dow_address']))) {
            return true;
        } elseif ($sdk_version == 2 &&  $game['ios_dow_plist']&&varify_url(cmf_get_domain() . '/upload/' . $game['ios_dow_plist'])) {
            return true;
        } else {
            return false;
        }
    }

}

/**
 * @函数或方法说明
 * @获取推广员文件下载地址
 * @param $file
 * @param int $downlocal
 *
 * @return string
 *
 * @author: 郭家屯
 * @since: 2019/8/16 13:14
 */
function promote_game_get_file_download_url($file, $downlocal = 0)
{
    if ($downlocal == 0) {
        if(strpos($file,'http') !== false){
            $url = $file;
        }else{
            $url = cmf_get_domain() . '/upload/' . $file;
        }
    } else {
        $url = cmf_get_file_download_url($file);
    }
    return $url;
}

/**
 * @设置获取用户会话信息
 *
 * @param $value
 *
 * @return mixed
 *
 * @author: fyj301415926@126.com
 * @since: 2019\3\29 0029 11:22
 */
function userInfo($value = '')
{
    $modules = request()->module();

    if($modules=='media'||$modules=='mobile'){
        $key = 'member_auth';
    }else{
        $key = request()->module() . '_member_auth';
    }

    if (is_null($value) || $value) {
        session($key, null);
        session($key, $value);
    }

    return session($key);
}

/**
 * @设置官网获取用户会话信息
 *
 * @param $value
 *
 * @return mixed
 *
 * @author: yyh
 * @since 19-08-06
 */
function mediaUserInfo($value = '')
{

    $key = 'media_member_auth';

    if (is_null($value) || $value) {
        session($key, null);
        session($key, $value);
    }

    return session($key);
}

/**
 * [array_group_by ph]
 * @param  [type] $arr [二维数组]
 * @param  [type] $key [键名]
 * @return [type]      [新的二维数组]
 * yyh
 */
function array_group_by($arr, $key)
{
    $grouped = [];
    foreach ($arr as $value) {
        $grouped[$value[$key]][] = $value;
    }
    // Recursively build a nested grouping if more parameters are supplied
    // Each grouped array value is grouped according to the next sequential key
    if (func_num_args() > 2) {
        $args = func_get_args();
        foreach ($grouped as $key => $value) {
            $parms = array_merge([$value], array_slice($args, 2, func_num_args()));
            $grouped[$key] = call_user_func_array('array_group_by', $parms);
        }
    }
    return $grouped;
}

// 去除特殊字符
// yyh
function replace_specialChar($strParam, $replace = "", $regex = "/\/|\～|\，|\。|\！|\？|\“|\”|\【|\】|\『|\』|\：|\；|\《|\》|\’|\‘|\ |\·|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|/")
{
    return preg_replace($regex, $replace, $strParam);
}

/**
 * @函数或方法说明
 * @获取上级推荐人id
 * @param int $id
 *
 * @author: 郭家屯
 * @since: 2019/4/8 15:58
 */
function get_parent_promote_id($id = 0)
{
    if (empty($id)) {
        return 0;
    }
    $promote = think\Db::table('tab_promote')->field('parent_id')->where('id', $id)->find();
    if (empty($promote)) {
        return 0;
    }
    return $promote['parent_id'];
}

/**
 * 获取联盟站已申请游戏列表
 * 郭家屯
 */
function get_promote_apply_game_id($promote_id = 0,$type = 0)
{
    if (empty($promote_id)) return [];
    if(empty($type)){
        $map['pa.status'] = 1;
    }
    $map['g.test_game_status'] = 0;
    $list = think\Db::table('tab_promote_apply')
        ->field('game_id')
        ->alias('pa')
        ->join(['tab_game' => 'g'], 'g.id=pa.game_id', 'left')
        ->where('g.game_status', 1)
        ->where($map)
        ->where('pa.promote_id', $promote_id)
        ->select();
    if (empty($list)) {
        return [];
    }
    return array_column($list->toArray(), 'game_id');
}

/**
 * 获取联盟站已申请游戏列表
 * created by wjd
 * 2021-5-14 20:47:58
 */
function get_promote_apply_game_info($promote_id = 0,$type = 0)
{
    if (empty($promote_id)) return [];
    if(empty($type)){
        $map['pa.status'] = 1;
    }
    $list = think\Db::table('tab_promote_apply')
        ->field('game_id,g.game_name')
        ->alias('pa')
        ->join(['tab_game' => 'g'], 'g.id=pa.game_id', 'left')
        ->where('g.game_status', 1)
        ->where($map)
        ->where('pa.promote_id', $promote_id)
        ->select()->toArray();
    if (empty($list)) {
        return [];
    }
    return $list;
}


/**
 * @函数或方法说明
 * @获取用户定义参数开启状态
 * @param string $name
 *
 * @author: 郭家屯
 * @since: 2019/4/9 17:19
 */
function get_user_config($name = '')
{
    if (empty($name)) {
        return 0;
    }
    $config = think\Db::table('tab_user_config')->where('name', $name)->find();
    if (empty($config)) {
        return 0;
    }
    return $config['status'];
}

/**
 * @函数或方法说明
 * @获取用户定义参数
 * @param string $name
 *
 * @author: 郭家屯
 * @since: 2019/4/9 17:19
 */
function get_user_config_info($name = '')
{
    if (empty($name)) {
        return 0;
    }
    $config = think\Db::table('tab_user_config')->where('name', $name)->find();
    if (empty($config)) {
        return 0;
    }
    return json_decode($config['config'], true);
}

/**
 *[读取文件的json数据]
 * @param $dirname
 * @return mixed
 * @author chen
 */
function auto_get_access_token($dirname)
{
    $access_token_validity = file_get_contents($dirname);
    if ($access_token_validity) {
        $access_token_validity = json_decode($access_token_validity, true);
        $is_validity = $access_token_validity['expires_in_validity'] - 1000 > time() ? true : false;
    } else {
        $is_validity = false;
    }
    $result['is_validity'] = $is_validity;
    $result['access_token'] = $access_token_validity['access_token'];
    return $result;
}

/**
 *[写入文件]
 * @param $txt
 * @param $name
 * @author chen
 */
function wite_text($txt, $name)
{
    $myfile = fopen($name, "w") or die("Unable to open file!");
    fwrite($myfile, $txt);
    fclose($myfile);
}

/**
 *[获取微信ticket]
 * @param $dirname
 * @return mixed
 * @author chen
 */
function auto_get_ticket($dirname)
{

    $access_token_validity = file_get_contents($dirname);
    if ($access_token_validity) {
        $access_token_validity = json_decode($access_token_validity, true);
        $is_validity = $access_token_validity['expires_in_validity'] - 1000 > time() ? true : false;
    } else {
        $is_validity = false;
    }
    $result['is_validity'] = $is_validity;
    $result['ticket'] = $access_token_validity['ticket'];
    return $result;
}

#---------------------------------------------------------------
/**
 * 获取某一天开始结束时间戳
 * @param int $day
 * @param bool $str
 * @return array|string
 * yyh
 */
function date_time($day = 0, $str = true)
{
    $start = strtotime($day);
    $end = strtotime($day) + 24 * 60 * 60 - 1;
    if ($str) {
        return " between $start and $end ";
    } else {
        return ['between', [$start, $end]];
    }
}

/**
 * [更新VIP等级和总充值金额]
 * @param $user
 * @author 郭家屯[gjt]
 */
function set_vip_level($user_id = 0, $pay_amount = 0,$cumulative = 0)
{
    $userData = (new \app\member\model\UserModel())->field('vip_level,cumulative')->where('id','=',$user_id)->find();
    if (empty($user_id)||empty($userData)) {
        return '';
    }
    $config = cmf_get_option('vip_set');
    $cumulative = $userData->cumulative + $pay_amount;
    $userData->cumulative = $cumulative;
    if(!$config['vip']){//删除vip设置  等级不变
        $userData->save();
        return true;
    }
    $vip = explode(',',$config['vip']);
    sort($vip);
    $uservip = $userData->vip_level;
    $nextlevelmoney = $vip[$uservip];
    if($cumulative<$nextlevelmoney){//不满足下一等级要求  等级不变
        $userData->save();
        return true;
    }
    foreach ($vip as $key=>$v){
        if($cumulative >= end($vip)){
            $uservip = count($vip);
            break;
        }
        if($cumulative >= $v&&$cumulative<$vip[$key+1]){
            $uservip = $key+1;
            break;
        }
    }
    $userData->vip_level = $uservip;
    $userData->save();
}

/**
 * @param string $str
 * @return string
 * @author yyh
 */
function str_unique($str = '', $unique = 1)
{
    if (empty($str)) {
        return '';
    }
    $arr = explode(',', $str);
    if ($unique == 1) {
        $arr = array_unique(array_filter($arr));//去空 去重
    } else {
        $arr = array_filter($arr);//去空
    }
    $str = implode(',', $arr);
    return $str;
}

/**
 * 计算数组个数
 * @param array $arr
 * @return int
 * @author yyh
 */
function arr_count($arr = [])
{
    if (!is_array($arr)) {
        if (empty($arr)) {
            $arr = [];
        } else {
            $arr = explode(',', $arr);
        }
    }
    return count($arr);
}

/**
 * 递归 拼接二维数组相同key的值
 * @param string $k
 * @param array $v
 * @param int $i
 * @return string
 * @author yyh
 */
function array_key_value_link($k = '', $v = [], $i = 0)
{
    $data = array_column($v, $k);
    $data = implode(',', $data);
    return $data;
}

/**
 * 字符串转数组 求和
 * @param string $str
 * @return string
 * @author  yyh
 */
function str_arr_sum($str = '')
{
    if (empty($str)) {
        return '0.00';
    } else {
        $arr = explode(',', $str);
        return null_to_0(array_sum($arr));
    }
}

/**
 * [获取多条用户信息]
 * @param bool $field
 * @param array $map
 * @return array|false|PDOStatement|string|\think\Model
 * @author yyh[gjt]
 */
function get_user_lists_info($field = true, $map = [],$order=null)
{
    if (!$field) {
        $field = true;
    }
    $data = think\Db::table('tab_user')->field($field)->where($map)->order($order)->select()->toArray();
    return $data;
}

/**
 * @函数或方法说明
 * @保存账号
 * @param string $account
 * @param string $password
 *
 * @author: 郭家屯
 * @since: 2019/5/18 14:47
 */
function save_cookie_account($account = '', $password = '')
{
    $cookie_account = json_decode(cookie('cookie_account'), true);
    $is_set = 0;
    foreach ($cookie_account as $key => $v) {
        if ($v['account'] == $account) {
            $is_set = 1;
            $cookie_account[$key]['time'] = time();
        }
    }
    if ($is_set == 0) {
        $cookie_account[] = [
            'account' => $account,
            'password' => simple_encode($password),
            'time' => time()
        ];
    }
    array_multisort(array_column($cookie_account, 'time'), SORT_DESC, $cookie_account);
    if(count($cookie_account)>5){
        unset($cookie_account[5]);
    }
    cookie('cookie_account', json_encode($cookie_account), 36000000);
}

/**
 * 简单对称加密算法之加密
 * @param String $string 需要加密的字串
 * @param String $skey 加密EKY
 * @return String
 * @author yyh
 */
function simple_encode($string = '')
{
    $skey = config('database.authcode');
    $strArr = str_split(base64_encode($string));
    $strCount = count($strArr);
    foreach (str_split($skey) as $key => $value)
        $key < $strCount && $strArr[$key] .= $value;
    return str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), join('', $strArr));
}

/**
 * 简单对称加密算法之解密
 * @param String $string 需要解密的字串
 * @param String $skey 解密KEY
 * @return String
 * @author yyh
 */
function simple_decode($string = '')
{
    $skey = config('database.authcode');
    $strArr = str_split(str_replace(array('O0O0O', 'o000o', 'oo00o'), array('=', '+', '/'), $string), 2);
    $strCount = count($strArr);
    foreach (str_split($skey) as $key => $value)
        $key <= $strCount && isset($strArr[$key]) && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
    return base64_decode(join('', $strArr));
}

/**
 * @函数或方法说明
 * @添加游戏订单
 * @param array $param
 * @param array $user_entity
 *
 * @author: 郭家屯
 * @since: 2019/8/14 10:03
 */
function add_spend($param=[],$user_entity=[]){
    $data_spend['user_id'] = $user_entity["id"]?:$param['user_id'];
    $data_spend['user_account'] = $user_entity["account"];
    $data_spend['game_id'] = $param["game_id"];
    $data_spend['game_name'] = $param['game_name'];
    $data_spend['server_id'] = $param["server_id"] ? : 0;
    $data_spend['server_name'] = $param["server_name"] ? : '';
    $data_spend['game_player_id'] = $param['game_player_id']?:0;
    $data_spend['game_player_name'] = $param["game_player_name"] ? : '';
    $data_spend['role_level'] = $param["role_level"] ? : 0;
    $data_spend['promote_id'] = $user_entity["promote_id"];
    $data_spend['promote_account'] = $user_entity["promote_account"];
    $data_spend['order_number'] = $param["order_number"] ? : '';
    $data_spend['pay_order_number'] = $param["pay_order_number"];
    $data_spend['props_name'] = $param["title"] ? : '';
    $data_spend['discount_type'] = $param['discount_type']?:0;
    $data_spend['discount'] = $param['discount']?($param['discount']*10):10;
    $data_spend['cost'] = $param["cost"];//原价
    $data_spend['pay_time'] = time();
    $data_spend['pay_status'] = $param["pay_status"] ? $param["pay_status"] : 0;
    $data_spend['pay_game_status'] = 0;
    $data_spend['extra_param'] = $param['extra_param'] ? $param['extra_param']:'';
    $data_spend['extend'] = $param['extend']?:'';
    $data_spend['pay_way'] = $param["pay_way"];
    $data_spend['pay_amount'] = $param["pay_amount"];
    $data_spend['spend_ip'] = get_client_ip();
    $data_spend['sdk_version'] = $param["sdk_version"];
    $data_spend['small_id'] = $param["small_id"]?:0;
    $data_spend['small_nickname'] = $param["small_nickname"]?:'';
    $data_spend['coupon_record_id'] = $param['coupon_id']?:0;
    $data_spend['is_weiduan'] = $param['is_weiduan']?:0;
    //支付角色信息额外参数
    $data_spend['goods_reserve'] = $param["goods_reserve"]?:'';
    $data_spend['product_id'] = !empty($param['product_id'])?$param['product_id']:'';
    $data_spend['currency_code'] = !empty($param['currency_code'])?$param['currency_code']:'CNY';
    if (!empty($param['us_cost'])) {
        $data_spend['us_cost'] = $param['us_cost'];
    }
    if (!empty($param['currency_cost'])) {
        $data_spend['currency_cost'] = $param['currency_cost'];
    }
    if (!empty($param['area'])) {
        $data_spend['area'] = $param['area'];
    }
    $data_spend['update_time'] = $param['update_time']?:0;
    $data_spend['pay_promote_id'] = $param['pay_promote_id'] ?: 0;
    // 简化版用户ID
    if(is_third_platform($data_spend['promote_id'])){
        $data_spend['webplatform_user_id'] = get_third_user_id($data_spend['user_id']);
    }
    $res = think\Db::table('tab_spend')->insertGetId($data_spend);
    return $res;
}

/**
 * @函数或方法说明
 * @写入平台币订单
 * @param array $param
 * @param array $user_entity
 *
 * @return int|string
 *
 * @author: 郭家屯
 * @since: 2019/8/14 17:04
 */
function add_deposit($param=[],$user_entity=[]){
    $data_deposit['order_number'] = $param["order_number"] ? $param["order_number"] : '';
    $data_deposit['pay_order_number'] = $param["pay_order_number"];
    $data_deposit['user_id'] = $param["user_id"];
    $data_deposit['promote_id'] = $user_entity["promote_id"];
    $data_deposit['pay_amount'] = $param["pay_amount"];
    $data_deposit['cost'] = $param["cost"];
    $data_deposit['pay_status'] = $param["pay_status"] ? $param["pay_status"] : 0;
    $data_deposit['pay_way'] = $param["pay_way"];
    $data_deposit['pay_ip'] = get_client_ip();
    $data_deposit['pay_time'] = time();
    $data_deposit['pay_id'] = session('member_auth.user_id')?:$param['user_id'];
    $data_deposit['pay_account'] = session('member_auth.account')?:$user_entity['account'];
    $data_deposit['small_id'] = $param["small_id"]?:0;
    $data_deposit['small_nickname'] = $param["small_nickname"]?:'';
    $res = think\Db::table('tab_spend_balance')->insertGetId($data_deposit);
    return $res;
}

/**
 * @函数或方法说明
 * @绑币充值
 * @param array $param
 * @param array $user_entity
 *
 * @author: 郭家屯
 * @since: 2019/10/15 9:18
 */
function add_bind($param=[],$user_entity=[]){
    $bind_data['order_number'] = "";
    $bind_data['pay_order_number'] = $param['pay_order_number'];
    $bind_data['game_id'] = $param['game_id'];
    $bind_data['game_name'] = $param['game_name'];
    $bind_data['user_id'] = $param['user_id'];
    $bind_data['user_account'] = $user_entity['account'];
    $bind_data['promote_id'] = $user_entity['promote_id'];
    $bind_data['promote_account'] = $user_entity['promote_account'];
    $bind_data['pay_amount'] = $param['pay_amount'];
    $bind_data['cost'] = $param['cost'];
    $bind_data['pay_status'] = $param['pay_status']?:0;
    $bind_data['discount'] = $param['discount'];
    $bind_data['discount_type'] = $param['discount_type']?:0;
    $bind_data['pay_way'] = $param['pay_way'];
    $bind_data['pay_ip'] = $param['spend_ip'];
    $bind_data['pay_time'] = time();
    $bind_data['pay_id'] = session('member_auth.user_id')?:$param['user_id'];
    $bind_data['pay_account'] = session('member_auth.account')?:$user_entity['account'];
    $result = think\Db::table('tab_spend_bind')->insert($bind_data);
    return $result;
}

/**
 * @函数或方法说明
 * @获取角色信息
 * @param int $user_id
 * @param int $game_id
 *
 * @author: 郭家屯
 * @since: 2019/10/15 16:12
 */
function get_user_play($user_id=0,$game_id=0){
    if(empty($user_id) || empty($game_id)){
        return [];
    }
    $user_play = think\Db::table('tab_user_play')->field('id,user_id,user_account,promote_id,promote_account,bind_balance')->where('user_id',$user_id)->where('game_id',$game_id)->find();
    return $user_play ? $user_play : [];
}
/**
 * @函数或方法说明
 * @生成充值结算单
 * @param array $data
 *
 * @author: 郭家屯
 * @since: 2019/6/26 14:50
 */
 function set_promote_radio($data = [],$user=[])
{
    // 统计渠道等级
    try{
        $PromoteLevelLogic = new \app\promote\logic\PromoteLevelLogic();
        $PromoteLevelLogic->setPromoteLevel($user['promote_id'],$data['pay_amount']);
    }catch (\Exception $e){}

    $promote_data = get_promote_entity($user['promote_id'],'id,pattern,parent_id,promote_level');//查看当前渠道的信息
    $top_promote_id = get_top_promote_id($user['promote_id']);//记录顶级渠道 一级即自己
    $promote_data['top_promote_id'] = $top_promote_id;//兼容老客户
    $settment = [
        'promote_id' => $user['promote_id'],
        'promote_account' => $user['promote_account'],
        'parent_id' => $user['parent_id'] ? $user['parent_id'] : $user['promote_id'],
        'parent_name' => $user['parent_name'] ? $user['parent_name'] : $user['promote_account'],
        'top_promote_id' => $top_promote_id,//管理后台结算一级使用
        'game_id' => $data['game_id'],
        'game_name' => $data['game_name'],
        'pattern' => 0,
        'user_id' => $user['id'],
        'user_account' => $user['account'],
        'pay_way'=>$data['pay_way'],
        'pay_order_number'=>$data['pay_order_number'],
        'pay_amount'=>$data['pay_amount'],
        'role_name'=>$data['game_player_name'],
        'cost'=>$data['cost'],
        'create_time' => !empty($data['pay_time']) ? $data['pay_time'] : time()
    ];
    for ($i=1;$i<=$promote_data['promote_level'];$i++){
        $game = [];
        $j = $i==1?'':$i;
        if($i == $promote_data['promote_level']){
            $promote_id = $user['promote_id'];
        }else{
            if($i==1){//当前渠道不是超过一级会进入判断
                $promote_id = $top_promote_id;
            }else{//超过二级会进入 支持三级以上渠道
                $infotmp = think\Db::table('tab_promote')->field('id')->where(['top_promote_id'=>$top_promote_id,'promote_level'=>$i])->select()->toArray();
                foreach ($infotmp as $k=>$v){
                    $infotmp =  think\Db::table('tab_promote')->field('id')->where(['parent_id'=>$v['id'],'id'=>$user['promote_id']])->find();
                    if(!empty($infotmp)){
                        $promote_id = $v['id'];
                        unset($infotmp);
                        break;
                    }
                }
            }
        }
        $new_ratio = 'ratio'.$j;
        $new_money = 'money'.$j;
        $new_sum_money = 'sum_money'.$j;
        $apply = think\Db::table('tab_promote_apply')
                ->field('id,promote_money,promote_ratio')
                ->where('game_id',$data['game_id'])
                ->where('promote_id',$promote_id)
                ->where('status',1)
                ->find();
//        $game = think\Db::table('tab_game')->alias('g')
//            ->field('g.game_name,IF(promote_money>0,promote_money,money) as money,IF(promote_ratio>0,promote_ratio,ratio) as ratio')
//            ->join(['tab_promote_apply' => 'a'], 'g.id=a.game_id and promote_id=' . $promote_id, 'left')
//            ->where('g.id', $data['game_id'])
//            ->find();
        $settment[$new_ratio] = $apply['promote_ratio']?:0;
        $settment[$new_money] = $apply['promote_money']?:0;
        $settment[$new_sum_money] = $apply['promote_ratio'] ? round($data['pay_amount']*$apply['promote_ratio']/100,2) : 0;
    }
    think\Db::startTrans();
    try {
        think\Db::table('tab_promote_settlement')->insert($settment);
        think\Db::table('tab_spend')->where('id', $data['id'])->setField('is_check', 0);
        think\Db::commit();
        return true;
    } catch (\Exception $e) {
        // 回滚事务
        think\Db::rollback();
        return false;
    }
}
/**
 * @函数或方法说明
 * @验证注册ip是否在黑名单
 * @return bool
 *
 * @author: 郭家屯
 * @since: 2019/10/30 14:13
 */
function checkregiserip(){
    $ip = get_client_ip();
    $blank_ip = cmf_get_option('admin_set')['reg_blank_ip'];
    if($blank_ip){
        $blank_ips = explode(',',$blank_ip);
        if(in_array($ip,$blank_ips)){
            return false;
        }
    }
    // 增加IP每日注册限制
    $admin_set = cmf_get_option('admin_set');
    if (empty($admin_set['ip_day_limit'])) {
        return true;
    }
    // 增加注册不受限ip白名单
    $no_limit_ip = $admin_set['no_limit_ip'];
    $ips = explode(PHP_EOL ,$no_limit_ip);
    $ips = array_map('trim',$ips);
    // 需要验证
    if (!in_array($ip,$ips)) {
        $ip_day_limit = $admin_set['ip_day_limit'];
        $today = \think\helper\Time ::today();
        $where = [];
        $where['register_time'] = ['between', [$today[0], $today[1]]];
        $where['register_ip'] = $ip;
        $where['puid'] = 0;
        $reg_count = \think\Db ::table('tab_user') -> where($where) -> count();
        if ($reg_count >= $ip_day_limit) {
            return false;
        } else {
            return true;
        }
    } else {
        return true;
    }
}

/**
 * @函数或方法说明
 * @验证邮箱格式
 * @param string $email
 *
 * @return bool|false|int
 *
 * @author: 郭家屯
 * @since: 2019/12/30 16:09
 */
//function cmf_check_email($email=''){
//    if(empty($email)){
//        return false;
//    }
//    $regex= '/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/';
//    $result = preg_match($regex,$email);
//    return $result;
//}

/**
 * 方法 cmf_check_email
 *
 * @descript 描述
 *
 * @param string $email
 * @return bool true 对， false 错
 *
 * @author 鹿文学 fyj301415926@126.com
 * @since 2021/4/28 0028 15:39
 */
function cmf_check_email($email='') {
    if(empty($email)){
        return false;
    }
    $regex = "/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i";
    return !!preg_match($regex,$email);
}

/**
 * @函数或方法说明
 * @获取返利，折扣渠道类型
 * @param int $type
 *
 * @return string
 *
 * @author: 郭家屯
 * @since: 2020/1/9 16:27
 */
function get_promote_type($type=0){
    $data = [
            1=>'全站玩家',
        2=>'官方渠道',
        3=>'推广渠道',
        4=>'部分渠道',
        5=>'部分玩家'
    ];
    return $data[$type]?:'未知渠道';
}

/**
 * @从身份证号中取生日
 *
 * @author: gjt
 * @since: 2019/9/18 17:16
 */
function get_birthday_by_idcard($idNum,$formart="Ymd")
{
    if (empty($idNum)) {
        return '';
    }
    $birth = date($formart, strtotime(substr($idNum, 6, 8)));
    return $birth;

}

/**
 * 商务专员列表
 * @param $map
 * @param string $field
 * @return array
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 * @author yyh
 * @since  2020-2-17
 */
function get_business_lists($map=[],$field='*')
{
    $data = think\Db::table('tab_promote_business')->field($field)->where($map)->select()->toArray();
    if(empty($data)){
        return [];
    }else{
        return $data;
    }
}

/**
 * 商务专员实体
 * @param $id
 * @param string $field
 * @return array|bool|false|PDOStatement|string|\think\Model
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function get_business_entity($id,$field='account')
{
    $data = think\Db::table('tab_promote_business')->field($field)->find($id);
    if(empty($data)){
        return false;
    }else{
        return $data;
    }
}

// 商户是否登录
// yyh
function is_b_login()
{
    return session('BID');
}
/**
 *根据商务专员id 查找推广员
 */
function get_busier_promote_list($map=[]) {
    $list = think\Db::table("tab_promote")->field('id,account,balance_coin')->where($map)->select()->toArray();
    if (empty($list)){return [];}
    return $list;
}


/**
 * 获取推广员顶级渠道
 * @param $id
 * @return bool
 * @since 2020-03-01
 */
function get_top_promote_id($id)
{
    if(!$id){
        return false;
    }else{
        $data = get_promote_entity($id,'id,parent_id,top_promote_id');
        if($data['top_promote_id']==0&&$data['parent_id']==0){
            return $data['id'];
        }elseif($data['top_promote_id']==0){//兼容老版本二级推广员 top_promote_id = 0
            return $data['parent_id'];
        }else{
            return $data['top_promote_id'];
        }
    }
}

/**
 * @函数或方法说明
 * @获取相差天数
 * @param string $time 日期格式
 *
 * @author: 郭家屯
 * @since: 2020/3/4 19:19
 */
function get_days($time1='',$time2='')
{
    $date1 = date_create($time1);
    $date2 = date_create($time2);
    $diff=date_diff($date1,$date2);
    $days = $diff->days;
    return $days;
}
/**
 * [get_relation_game_name 获取游戏真实名称]
 * @param  [type] $game_id [description]
 * @param string $field [description]
 * @return [type]          [description]
 * @author [郭家屯] <[<email address>]>
 */
function get_relation_game_name($game_id = null, $field = 'id')
{
    $map[$field] = $game_id;
    $data = think\Db::table('tab_game')->field('relation_game_name')->where($map)->find();
    if (empty($data)) {
        return ' ';
    }
    return $data['relation_game_name'];
}

/**
 * @函数或方法说明
 * @获取文章的id
 * @param array $game_ids
 *
 * @author: 郭家屯
 * @since: 2020/4/13 19:54
 */
function get_promote_relation_game_id($game_ids=[]){
    if(empty($game_ids))return [];
    $map['id'] = ['in',$game_ids];
    $data = think\Db::table('tab_game')->field('relation_game_name')->where($map)->group('relation_game_name')->select()->toArray();
    if (empty($data)) {
        return [];
    }
    $game_name = array_column($data,'relation_game_name');
    $condition['relation_game_name'] = ['in',$game_name];
    $data = think\Db::table('tab_game')->field('relation_game_id')->where($condition)->select()->toArray();
    return array_column($data,'relation_game_id');
}

/**
 * @函数或方法说明
 * @获取距离下一级还差多少
 * @param $cumulative
 *
 * @author: 郭家屯
 * @since: 2020/4/16 13:53
 */
function get_next_vip_level($cumulative=0){
    $config = cmf_get_option('vip_set');
    $viplevel = explode(',',$config['vip']);
    $amount = 0;
    foreach ($viplevel as $key=>$v){
        if($cumulative < $v){
            $amount = $v - $cumulative ;
            break;
        }
    }
    return sprintf("%.2f",$amount);
}

/**
 * @函数或方法说明
 * @获取VIP等级列表
 * @author: 郭家屯
 * @since: 2020/4/24 11:59
 */
function get_vip_level(){
    $config = cmf_get_option('vip_set');
    return $config['vip'] ? explode(',',$config['vip']) : [];
}

/**
 * @函数或方法说明
 * @获取积分方式
 * @author: 郭家屯
 * @since: 2020/4/29 19:33
 */
function get_point_type(){
    $data = \think\Db::table('tab_user_point_type')->field('id,name')->select()->toArray();
    return $data;
}

/**
 * @函数或方法说明
 * @获取积分方式
 * @author: 郭家屯
 * @since: 2020/4/29 19:33
 */
function get_point_use_type(){
    $data = \think\Db::table('tab_user_point_use_type')->field('id,name')->select()->toArray();
    return $data;
}

/**
 * 是否充值过
 */
function user_is_paied(int $user_id){
    $data = \think\Db::table('tab_spend')->field('pay_amount')->where('user_id',$user_id)->where('pay_status','=',1)->find();
    if(empty($data)){
        return 0;
    }else{
        return $data['pay_amount'];
    }
}

/**
 * @函数或方法说明
 * 通过真实名称找游戏id
 * @param array $game_name
 *
 * @author: 郭家屯
 * @since: 2020/6/11 20:08
 */
function get_relation_game_id($game_name=[]){
    if(empty($game_name)){
        return [];
    }
    $map['relation_game_name'] = ['in',$game_name];
    $data = \think\Db::table('tab_game')->field('relation_game_id')->where($map)->select()->toArray();
    return $data ? array_column($data,'relation_game_id') : [];
}

/**
 * @函数或方法说明
 * @获取微端下载状态
 * @param int $game_id
 * @param int $sdk_version
 * @param int $promote_id
 *
 * @return bool
 *
 * @author: 郭家屯
 * @since: 2020/7/30 10:27
 */
function get_weiduan_down_status($game_id=0,$sdk_version=1,$promote_id=0){
    if (empty($game_id)) {
        return false;
    }
    if(empty($sdk_version)){
        $sdk_version = 1;
    }
    if($promote_id>0){
        $apply = think\Db::table('tab_promote_apply')
        ->where('promote_id',$promote_id)
        ->where('game_id',$game_id)
        ->find();
        if(!$apply){
            return false;
        }
        if($sdk_version == 1 && $apply['and_status'] == 1){
            return true;
        }elseif($sdk_version == 2 && $apply['ios_status'] == 1){
            return true;
        }else{
            return false;
        }
    }else{
        $game = think\Db::table('tab_game')
                ->field('and_dow_address,ios_dow_plist,add_game_address,ios_game_address')
                ->where('game_status', 1)
                ->where('relation_game_id', $game_id)
                ->find();
        if (empty($game)) {
            return false;
        }
        if ($sdk_version == 1 && varify_url(cmf_get_file_download_url($game['and_dow_address']))) {
            //官方下载
            return true;
        } elseif ($sdk_version == 2 &&  $game['ios_dow_plist']&&varify_url(cmf_get_domain() . '/upload/' . $game['ios_dow_plist'])) {
            //官方下载
            return true;
        }elseif ($sdk_version == 1 && $game['add_game_address']) { //第三方链接下载
            return true;
        } elseif ($sdk_version == 2 && $game['ios_game_address']) { //第三方链接下载
            return true;
        } else {
            return false;
        }
    }
}

/**
 * @函数或方法说明
 * @优化时间显示
 * @param $time
 *
 * @author: 郭家屯
 * @since: 2020/7/1 17:45
 */
function get_time($time){
    //明天开始的时间
    $tday = strtotime(date('Ymd',strtotime("+1 day")));
    //今天时间
    $day = strtotime(date('Ymd'));
    if($time > $tday && $time <($tday+86399)){
        return '明天:'.date(' H:i',$time);
    }elseif($time > $day && $time<$tday){
        return '今天:'.date(' H:i',$time);
    }else{
        return date('m.d H:i',$time);
    }
}

/**
 * @获取分发用户列表
 *
 * @since: 2020/7/10 11:57
 * @author: zsl
 */
function get_issue_open_user($field = '*', $map = [], $order = 'id desc')
{
    $list = think\Db ::table('tab_issue_open_user') -> field($field) -> where($map) -> order($order) -> select() -> toarray();
    if (empty($list)) {
        return '';
    }
    // 判断当前管理员是否有权限显示完成整手机号或完整账号
    $ys_show_admin = get_admin_privicy_two_value();
    foreach($list as $k5=>$v5){
        if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
            $list[$k5]['account'] = get_ys_string($v5['account'],$ys_show_admin['account_show_admin']);
        }
    }
    return $list;
}

/**
 * @获取分发用户名称
 *
 * @author: zsl
 * @since: 2020/7/10 11:59
 */
function get_issue_open_useraccount($open_user_id)
{
    $account = \think\Db ::table('tab_issue_open_user') -> where(['id' => $open_user_id]) -> value('account');
    // 判断当前管理员是否有权限显示完成整手机号或完整账号
    $ys_show_admin = get_admin_privicy_two_value();
    if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
        $account = get_ys_string($account,$ys_show_admin['account_show_admin']);
    }

    return $account ? $account : '';
}


/**
 * 生成微端打包token
 * @param $uid
 * @param $account
 * @param int $day  超时时间
 * @return string
 * author: xmy 280564871@qq.com
 */
function get_h5_token($uid,$account,$password,$day=56){
    $end_time = 60 * 60 * 24 * $day;
    $info['user_id'] = $uid;
    $info['account'] = $account;
    $info['password'] = $password;
    $result = $token = think_encrypt(json_encode($info),false,$end_time);
    session("user_info",$info);
    return $result;
}

/**
 * @descript 获取数据实例
 * @param \think\Model $table
 * @param array $map
 * @param string $field
 * @return array
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\DbException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 * @author yyh
 * @since 2020-07-10
 */
function get_table_entity(\think\Model $table,array $map,$field='*',$is_one=1):array
{
    $data = [];
    $table->field($field)->where($map);
    if($is_one){
        $data = $table->find();
        $data = empty($data)?[]:$data->toArray();
    }else{
        $data = $table->select();
    }
    return $data;
}


/**
 * [获取文件名称]
 * @param string $name
 * @author 郭家屯[gjt]
 */
function get_file_name($name = '')
{
    if (empty($name)) {
        return '';
    }
    $file = think\Db::name('asset')->where('file_path', $name)->field('id,filename')->find();
    if (empty($file)) {
        return '';
    } else {
        return $file['filename'];
    }
}

/**
 * @函数或方法说明
 * @简化数字为万
 * @param $number
 *
 * @author: 郭家屯
 * @since: 2020/7/16 14:34
 */
function get_simple_number($number){
    $number = (int)$number;
    if($number > 9999){
        $number = round($number/10000,1).'W';
    }
    return $number;
}
function get_issue_game_entity($map,$field='*',$is_one = 1,$group='')
{
    if($is_one==1){
        $data = think\Db::table('tab_issue_game')->field($field)->where($map)->group($group)->find();
    }else{
        $data = think\Db::table('tab_issue_game')->field($field)->where($map)->group($group)->select();
    }
    return $data;
}

/**
 * @函数或方法说明
 * @获取代金券记录实体信息
 * @param int $coupon_id
 * @param string $field
 *
 * @author: 郭家屯
 * @since: 2020/7/20 16:56
 */
function get_coupon_entity($coupon_id=0,$field="*"){
    if(empty($coupon_id)){
        return [];
    }
    $coupon = think\Db::table('tab_coupon_record')->field($field)->where('id',$coupon_id)->find();
    return $coupon?:[];
}

/* 获取日期间隔数组 @author 鹿文学 */
function get_date_list($d1='',$d2='',$flag=1) {
    if ($flag == 1){/* 天 形如：array('2017-03-10','2017-03-11','2017-03-12','2017-03-13')*/
        $d1 = $d1?$d1:mktime(0,0,0,date('m'),date('d')-6,date('Y'));
        $d2 = $d2?$d2:mktime(0,0,0,date('m'),date('d'),date('Y'));
        $date = range($d1,$d2,86400);
        $date = array_map(create_function('$v','return date("Y-m-d",$v);'),$date);
    } elseif ($flag == 2) {/* 月 形如：array('2017-01','2017-02','2017-03','2017-04')*/
        $d1 = $d1?$d1:mktime(0,0,0,date('m')-5,1,date('Y'));
        $d2 = $d2?$d2:mktime(0,0,0,date('m'),date('d'),date('Y'));
        $i = false;
        while($d1<$d2) {
            $d1 = !$i?$d1:strtotime('+1 month',$d1);
            $date[]=date('Y-m',$d1);$i=true;
        }
        array_pop($date);
    } elseif ($flag == 3) {/* 周 形如：array('2017-01','2017-02','2017-03','2017-04')*/
        $d1 = $d1?$d1:mktime(0,0,0,date('m')-2,1,date('Y'));
        $d2 = $d2?$d2:mktime(0,0,0,date('m'),date('d'),date('Y'));

        $i = false;
        while($d1<$d2) {
            $d1 = !$i?$d1:strtotime('+1 week',$d1);
            $date[]=date('Y-W',$d1);$i=true;
        }
    } elseif ($flag == 4) {
        $d1 = $d1?date('Y-m',$d1):date('Y-m',strtotime('-5 month'));
        $d2 = $d2?date('Y-m',$d2):date('Y-m');
        $temp = explode('-',$d1);
        $year = $temp[0];
        $month = $temp[1];
        while(strtotime($d1)<=strtotime($d2)) {
            $date[]=$d1;$month++;if($month>12) {$month=1;$year+=1;}
            $t = strlen($month.'')==1?'0'.$month:$month;
            $d1=$year.'-'.$t;
        }

    }

    return $date;
}
/* 获取日期(周)间隔数组-分发-改-20210707-byh  */
function get_date_list2($d1='',$d2='',$flag=3) {
    //d1为开始时间,计算d1所在周的周一时间
    $_st =  date('N', $d1);
    if($_st == 7){//周一为一周第七天,更改为当前周第0天
        $_st = 0;
    }
    $d1 = strtotime("-{$_st} days", $d1);//计算当前周的周一时间
    $d1 = $d1?$d1:mktime(0,0,0,date('m')-2,1,date('Y'));
    $d2 = $d2?$d2:mktime(0,0,0,date('m'),date('d'),date('Y'));

    while($d1<$d2) {
        $s = strtotime('+1 day',$d1);
        $d1 = strtotime('+1 week',$d1);
        $date[]=date('Y.m.d',$s).'~'.date('Y.m.d',$d1);
    }

    return $date;
}

/**
 * @获取分发游戏版本
 *
 * @author: zsl
 * @since: 2020/7/30 9:22
 */
function get_issue_game_sdk_version($game_id)
{

    $sdk_version = \think\Db ::table('tab_issue_game') -> where('id', '=', $game_id) -> value('sdk_version');
    return $sdk_version;
}

/**
 * @函数或方法说明
 * @判断是否为https请求
 * @return bool
 *
 * @author: 郭家屯
 * @since: 2020/7/30 17:04
 */
function is_https() {
    if ( !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    } elseif ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
        return true;
    } elseif ( !empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
        return true;
    } elseif ( !empty($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) === 'https') {
        return true;
    }
    return false;
}


/**
 * @限制IP请求次数
 *
 * @param int $limit    //限制请求次数
 * @param int $expire   //秒数
 *
 * @author: zsl
 * @since: 2020/7/31 13:22
 */
function ip_request_limit($limit = 100, $expire = 60, $db = 0)
{
    $key = get_client_ip();
    $redis = new \cmf\org\RedisSDK\RedisController(['host' => '127.0.0.1', 'port' => 6379]);
    $redis -> select($db);
    $check = $redis -> exists($key);
    if ($check) {
        $redis -> incr($key);
        $count = $redis -> get($key);
        if ($count > $limit) {
            return false;  //超出了限制次数
        }
    } else {
        $redis -> incr($key);
        //设置过期时间
        $redis -> expire($key, $expire);
    }
    $count = $redis -> get($key); //单位时间请求次数
    return $count;
}

/**
 * 判断是否为微信访问
 * @return boolean
 */
function cmf_is_company_wechat()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false && strpos($_SERVER['HTTP_USER_AGENT'], 'wxwork') !== false) {
        return true;
    }
    return false;
}

/**
 * @函数或方法说明
 * @获取代金券列表
 * @author: 郭家屯
 * @since: 2020/8/6 11:52
 */
function get_coupon_list($filed="*",$map=[],$type=0,$pid=0){
    if(empty($type)){
        $map['status'] = 1;
    }
    $data = \think\Db::table('tab_coupon')
            ->field($filed)
            ->where('is_delete',0)
            ->where('pid',$pid)
            ->where($map)
            ->select()->toArray();
    return $data ? : [];
}

/**
 * @函数或方法说明
 * @获取代金券实体信息
 * @param int $coupon_id
 * @param string $field
 *
 * @author: 郭家屯
 * @since: 2020/8/6 16:56
 */
function get_coupon_info($coupon_id=0,$field="*"){
    if(empty($coupon_id)){
        return [];
    }
    $coupon = think\Db::table('tab_coupon')->field($field)->where('id',$coupon_id)->find();
    return $coupon?:[];
}

/**
 * @函数或方法说明
 * @创建尊享卡订单
 * @param array $data
 * @param array $user
 *
 * @author: 郭家屯
 * @since: 2020/8/17 15:53
 */
function add_member($data=[],$user=[]){
    $member_data['pay_order_number'] = $data['pay_order_number'];
    $member_data['user_id'] = $user['id'];
    $member_data['user_account'] = $user['account'];
    $member_data['promote_id'] = $user['promote_id'];
    $member_data['promote_account'] = $user['promote_account'];
    $member_data['pay_amount'] = $data['pay_amount'];
    $member_data['pay_status'] = 0;
    $member_data['pay_way'] = $data['pay_way'];
    $member_data['spend_ip'] = get_client_ip();
    $member_data['create_time'] = time();
    $member_data['member_name'] = $data['member_name'];
    $member_data['days'] = $data['days'];
    $member_data['free_days'] = $data['free_days']?:0;
    $result = \think\Db::table('tab_user_member')->insert($member_data);
    return $result;
}

/**
 * @函数或方法说明
 * @获取所有接口
 * @author: 郭家屯
 * @since: 2020/9/14 13:39
 */
function get_interface_all($field="*",$map=[]){
    $data = \think\Db::table('tab_game_interface')->field($field)->where($map)->select()->toArray();
    return $data;
}

/**
 * @函数或方法说明
 * @获取接口详细信息
 * @author: 郭家屯
 * @since: 2020/9/14 13:44
 */
function get_interface_info($interface_id=0,$field="*"){
    if(empty($interface_id)){
        return [];
    }
    $data = \think\Db::table('tab_game_interface')->field($field)->where('id',$interface_id)->find();
    return $data;
}

/**
 * @函数或方法说明
 * @验证支付参数
 * @author: 郭家屯
 * @since: 2020/9/18 15:29
 */
function check_h5pay_auth($params=[]){
    $data['amount'] = $params['pay_amount']*100;
    $data['props_name'] = $params['props_name'];
    $data['trade_no'] = $params['trade_no'];
    $data['user_id'] = $params['user_id'];
    $data['game_appid'] = $params['game_appid'];
    $data['channelExt'] = $params['channelExt'];
    $data['timestamp'] = $params['timestamp'];
    $lGame = new \app\sdkh5\logic\GameLogic();
    //获取游戏扩展信息
    $gameInfo = \think\Db::table('tab_game') -> field('id') -> where('game_appid',$params['game_appid']) -> find();
    $gameSetInfo = \think\Db ::table('tab_game_set') -> field('game_id,game_key') -> where(['game_id' => $gameInfo['id']]) -> find();
    $data['sign'] = $lGame -> h5SignData($data, $gameSetInfo['game_key']);
    if($data['sign'] == $params['sign']){
        return true;
    }else{
        return false;
    }
}

/**
 * [获取游戏区服名称]
 * @param  [type] $id [description]
 * @return [type]     [description]
 */
function get_server_name($id = '')
{
    if ($id == '') {
        return false;
    }
    $server = think\Db::table("tab_game_server")->field('server_name')->where("id", $id)->find();
    return $server['server_name'];
}

/**
 * @函数或方法说明
 * @获取充值平台币
 * @author: 郭家屯
 * @since: 2020/9/25 9:32
 */
function get_game_coin($game_id,$amount){
    $game = get_game_entity($game_id,'currency_ratio');
    $ratio = $game['currency_ratio'] ? : 100;
    return $amount*$ratio;
}

/**
 * 判断是否支付宝内置浏览器访问
 * @return bool
 */
function cmf_is_alipay()
{
    return strpos($_SERVER['HTTP_USER_AGENT'], 'Alipay') !== false;
}

/**
 * @函数或方法说明
 * @获取当前子渠道id
 * @author: 郭家屯
 * @since: 2020/9/28 19:44
 */
function get_zi_promote_id($id=0){
    if(empty($id)){
        return [];
    }
    $map['parent_id|top_promote_id'] = $id;
    $list = \think\Db::table('tab_promote')->field('id')->where($map)->select()->toArray();
    return $list ? array_column($list,'id'):[];
}

/**
 * [发起http请求]
 * @param $url
 * @return mixed
 * @author gjt
 */
function curl($url){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    //如果是https协议
    if (stripos($url, "https://") !== FALSE) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        //CURL_SSLVERSION_TLSv1
        curl_setopt($curl, CURLOPT_SSLVERSION, 1);
    }
    //超时时间
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //返回内容
    $callbcak = curl_exec($curl);
    //关闭,释放资源
    curl_close($curl);
    //返回内容JSON_DECODE
    return $callbcak;
}

// 获取所有CP商列表  by wjd
// param1:
// by wjd
function get_cp_list($field = '', $map = [], $group = null, $order = 'id desc'){
    if (empty($field)) {
        $field = true;
    }
    $list = think\Db::table('tab_game_cp')->field($field)->where($map)->group($group)->order($order)->select()->toarray();
    if (empty($list)) {
        return '';
    }
    return $list;
}

/**
 * @获取游戏所属CP
 *
 * @author: zsl
 * @since: 2021/4/28 15:25
 */
function get_game_cp_name($game_id)
{
    $cp_name = \think\Db ::table('tab_game') -> alias('g')
            -> join(['tab_game_cp' => 'c'], 'g.cp_id = c.id', 'left')
            -> where(['g.id' => $game_id])
            -> value('cp_name');
    return $cp_name;
}


/**
 * @获取cp下游戏id
 *
 * @author: zsl
 * @since: 2021/4/20 11:38
 */
function get_cp_game_ids($cp_id)
{
    if (empty($cp_id)) {
        return [];
    }
    $gameIds = \think\Db ::table('tab_game') -> where(['cp_id' => $cp_id]) -> column('id');
    return $gameIds ? $gameIds : [];
}

// 根据游戏id获取已申请的渠道账号
// by wjd
function get_promote_list_by_game_id($game_id){
    $list = think\Db::table('tab_promote_apply')
            ->field('id,p.account')
            ->alias('pa')
            ->join(['tab_promote' => 'p'], 'pa.promote_id=p.id', 'left')
            ->where("game_id=$game_id")
            ->select()
            ->toarray();
    if (empty($list)) {
        return '';
    }
    return $list;
}
/**
 * @函数或方法说明
 * @获取绑币余额总计
 * @param int $user_id
 *
 * @return float|int
 *
 * @author: 郭家屯
 * @since: 2020/11/9 10:58
 */
function get_user_bind_total($user_id=0)
{
    if(empty($user_id)){
        return 0;
    }
    $total = \think\Db::table('tab_user_play')->where('user_id',$user_id)->sum('bind_balance');
    return $total;
}
// 根据当前用户id获取用户的未读消息数
// by wjd
function get_unread_msg($user_id){
    if(empty($user_id)){
        return 0;
    }
    // 没有类型的不计入我的消息数 (type=0的不计入)
    $unread_num = \think\Db::table('tab_tip')->where("user_id=$user_id AND read_or_not=1 AND type>0")->count();
    return $unread_num;
}

/**
 * @函数或方法说明
 * @获取渠道session_id
 * @author: 郭家屯
 * @since: 2020/11/18 11:27
 */
function cmf_get_current_channel_id(){
    $sessionUserId =  session('PID');
    if (empty($sessionUserId)) {
        return 0;
    }
    return $sessionUserId;
}



//获取所有子渠道列表
/**
 * @param int $id 要查询子渠道的渠道id
 * @param int $next_count 查询相对下几级子渠道
 * @return array
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function get_song_promote_lists($id = 0,$next_count = 1)
{
    $data = $new_data = [];
    $data = think\Db::table("tab_promote")->field('id,account,promote_level,balance_coin')->where("parent_id", $id)->select()->toarray();//2级
    if($next_count>1){
        foreach ($data as $k=>$v) {
            $new_data = think\Db::table("tab_promote")->field('id,account,promote_level,balance_coin')->where("parent_id", $v['id'])->select()->toarray();//3级
            $data = array_merge($data,$new_data);
        }
    }
    if (empty($data)) {
        return [];
    } else {
        return $data;
    }
}

/**
 * @检测IP访问频率
 *
 * @param int $time : 请求时间间隔
 * @param int $limit : 请求次数
 *
 * @author: zsl
 * @since: 2020/12/7 16:52
 */
function check_ip_access_frequency($time = 60, $limit = 1)
{
    $redis = new \cmf\org\RedisSDK\RedisController(['host' => '127.0.0.1']);
    $redis -> select(0);
    $ip = get_client_ip();
    $check = $redis -> exists($ip);
    //如果存在请求记录,则判断请求次数
    if ($check) {
        $redis -> incr($ip);
        $count = $redis -> get($ip);
        if ($count > $limit) {
            //请求频繁
            return false;
        }
        return true;
    }
    $redis -> incr($ip);
    //设置限制时间
    $redis -> expire($ip, $time);
    return true;
}

// 获取配置文件
// by wjd
function get_version_code_value(){
    $version_code_value = json_decode(file_get_contents(WEB_ROOT.'../data/'.'version_code.txt'),true);
    $return_data['is_buy_lianyun_fenfa'] = $version_code_value['is_buy_lianyun_fenfa'] ?? 0;
    $return_data['system_permi'] = $version_code_value['system_permi'] ?? 0;
    $return_data['system_ypermi'] = $version_code_value['system_ypermi'] ?? 0;
    return $return_data;
}

/**
 * @获取超级签游戏版本
 *
 * @author: zsl
 * @since: 2021/1/6 11:57
 */
function super_sign_version($game_id)
{
    if (empty($game_id)) {
        return false;
    }
    $super_version = \think\Db ::table('tab_game') -> where(['id' => $game_id]) -> value('super_version');
    return $super_version;
}

/**
 * @获取bt福利道具名称
 *
 * @author: zsl
 * @since: 2021/1/14 14:00
 */
function get_bt_prop_name($id)
{
    $name = \think\Db ::table('tab_bt_prop') -> where(['id' => $id]) -> value('prop_name');
    return $name;
}

/**
 * @检查用户是否属于自定义支付渠道
 *
 * @author: zsl
 * @since: 2021/1/28 17:52
 */
function check_user_is_custom_pay_channel($user_id, $is_account = false)
{
    $user_info = get_user_entity($user_id, $is_account, 'id,promote_id');
    if (empty($user_info)) {
        return false;
    }
    $promote_id = get_top_promote_id($user_info['promote_id']);
    $info = get_promote_entity($promote_id, 'id,is_custom_pay');
    return !!$info['is_custom_pay'];
}

/**
 * @json_encode丢失float数据精度修复
 *
 * @param $data
 * @param int $precision 保留几位小数
 *
 * @return array|string
 */
function fix_number_precision($data, $precision = 2)
{
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = fix_number_precision($value, $precision);
        }
        return $data;
    }
    if (is_numeric($data)) {
        $precision = is_float($data) ? $precision : 0;
        return number_format($data, $precision, '.', '');
    }
    return $data;
}


/**
 * 密码比较方法,所有涉及密码比较的地方都用这个方法
 * @param string $password     要比较的密码
 * @param string $passwordInDb 数据库保存的已经加密过的密码
 * @return boolean 密码相同，返回true
 */
function xigu_compare_password($password, $passwordInDb)
{
    if (strpos($passwordInDb, "###") === 0) {
        return cmf_password($password) == $passwordInDb;
    } elseif (strpos($passwordInDb, "##$##") === 0) {
        return '##$##' . password_migrate($password) == $passwordInDb;
    } else {
        return cmf_password_old($password) == $passwordInDb;
    }
}

/**
 * @数据迁移来的密码加密规则
 *
 * @author: zsl
 * @since: 2021/2/5 12:01
 */
function password_migrate($pw, $authCode = '')
{
    if (empty($authCode)) {
        $authCode = 'UmtW6-Z(S^8xvwDn;B:J{X7FG9z2+Np.|C#~QRY"';
    }
    return '' === $pw ? '' : md5(sha1($pw) . $authCode);
}

/**
 * [获取用户实名认证状态]
 * @param string $type
 * @return string
 * @author Juncl
 */
function get_user_age_status($type = '')
{
    switch ($type) {
        case '0':
            return '未认证';
            break;
        case '1':
            return '认证失败';
            break;
        case '2':
            return '已成年';
            break;
        case '3':
            return '未成年';
            break;
        case '4':
            return '审核中';
            break;
        default:
            return 'error';
            break;
    }
}
/**
 * @获取游戏实名认证方式
 * @author: Juncl
 * @time: 2021/02/27 10:36
 * @param int $id 游戏id
 * @return 1：国家实名认证系统 2平台认证
 */
function get_game_age_type($id=0){
    $type = \think\Db ::table('tab_game') -> where(['id' => $id]) -> value('age_type');
    return $type;
}

function get_age_last_time($user_id=0,$game_id=0){
    $data = \think\Db::table('tab_user_age_record') -> where('user_id',$user_id)->where('game_id',$game_id)-> value('last_request_time');
    return $data;
}

/**
 * @获取手机品牌
 *
 * @author: zsl
 * @since: 2021/4/21 10:30
 */
function get_phone_brand()
{
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    if (stripos($user_agent, "iPhone") !== false) {
        $brand = 'iPhone';
    } else if (stripos($user_agent, "SAMSUNG") !== false || stripos($user_agent, "Galaxy") !== false || strpos($user_agent, "GT-") !== false || strpos($user_agent, "SCH-") !== false || strpos($user_agent, "SM-") !== false) {
        $brand = '三星';
    } else if (stripos($user_agent, "Huawei") !== false || stripos($user_agent, "Honor") !== false || stripos($user_agent, "H60-") !== false || stripos($user_agent, "H30-") !== false) {
        $brand = '华为';
    } else if (stripos($user_agent, "Lenovo") !== false) {
        $brand = '联想';
    } else if (strpos($user_agent, "MI-ONE") !== false || strpos($user_agent, "MI 1S") !== false || strpos($user_agent, "MI 2") !== false || strpos($user_agent, "MI 3") !== false || strpos($user_agent, "MI 4") !== false || strpos($user_agent, "MI-4") !== false || strpos($user_agent, "POCO") !== false || strpos($user_agent, "Redmi") !== false || strpos($user_agent, "Mi 10") !== false || strpos($user_agent, "XiaoMi") !== false) {
        $brand = '小米';
    } else if (strpos($user_agent, "HM NOTE") !== false || strpos($user_agent, "HM201") !== false) {
        $brand = '红米';
    } else if (stripos($user_agent, "Coolpad") !== false || strpos($user_agent, "8190Q") !== false || strpos($user_agent, "5910") !== false) {
        $brand = '酷派';
    } else if (stripos($user_agent, "ZTE") !== false || stripos($user_agent, "X9180") !== false || stripos($user_agent, "N9180") !== false || stripos($user_agent, "U9180") !== false) {
        $brand = '中兴';
    } else if (stripos($user_agent, "OPPO") !== false || strpos($user_agent, "X9007") !== false || strpos($user_agent, "X907") !== false || strpos($user_agent, "X909") !== false || strpos($user_agent, "R831S") !== false || strpos($user_agent, "R827T") !== false || strpos($user_agent, "R821T") !== false || strpos($user_agent, "R811") !== false || strpos($user_agent, "R2017") !== false) {
        $brand = 'OPPO';
    } else if (strpos($user_agent, "HTC") !== false || stripos($user_agent, "Desire") !== false) {
        $brand = 'HTC';
    } else if (stripos($user_agent, "vivo") !== false) {
        $brand = 'vivo';
    } else if (stripos($user_agent, "K-Touch") !== false) {
        $brand = '天语';
    } else if (stripos($user_agent, "Nubia") !== false || stripos($user_agent, "NX50") !== false || stripos($user_agent, "NX40") !== false) {
        $brand = '努比亚';
    } else if (strpos($user_agent, "M045") !== false || strpos($user_agent, "M032") !== false || strpos($user_agent, "M355") !== false) {
        $brand = '魅族';
    } else if (stripos($user_agent, "DOOV") !== false) {
        $brand = '朵唯';
    } else if (stripos($user_agent, "GFIVE") !== false) {
        $brand = '基伍';
    } else if (stripos($user_agent, "Gionee") !== false || strpos($user_agent, "GN") !== false) {
        $brand = '金立';
    } else if (stripos($user_agent, "HS-U") !== false || stripos($user_agent, "HS-E") !== false) {
        $brand = '海信';
    } else if (stripos($user_agent, "Nokia") !== false) {
        $brand = '诺基亚';
    } else {
        $brand = '其他';
    }
    return $brand;
}

//获取渠道代充折扣-byh20210902改
function get_promote_dc_discount($promote, $game_id, $user_id=0)
{
    if(empty($promote) || empty($game_id)){
        $use_data_arr['discount'] = 10;
        $use_data_arr['discount_name'] = '代充折扣：';
        return $use_data_arr;
    }
    $top_promote = get_top_promote_id($promote);//查询顶级渠道,折扣只有一级渠道配置
    //查询订单状态
    if(!empty($user_id)){//玩家不存在,默认没有当前游戏和玩家的代充订单
        $map_tmp = [];
        $map_tmp['user_id'] = $user_id;//不存在则为0,显示首充信息
        $map_tmp['pay_status'] = 1; // 支付状态 1 成功  0失败
        $map_tmp['promote_id'] = $promote;
        $map_tmp['game_id'] = $game_id;
        //查询此渠道和此游戏是否有完成的充值数据
        $use_data_arr = [];
        $recharge = \think\Db::table('tab_promote_bind')->field('id')
            ->where($map_tmp)
            ->find();
    }else{
        $recharge = [];
    }

    //查询是否配置折扣信息,如没有配置,返回统一折扣,如配置,再查询渠道是否首充和开关
    $agent_arr = \think\Db::table('tab_promote_agent')
        ->field('game_discount,game_continue_discount,promote_discount_first,promote_discount_continued,promote_first_status,promote_continue_status')
        ->where('promote_id',$top_promote)//一级渠道的id
        ->where('game_id',$game_id)
        ->where('promote_first_status|promote_continue_status',1)
        ->find();
    if(!empty($agent_arr)){//有配置,查询渠道是否首充

        if(!empty($recharge)){//当前游戏-渠道-玩家 存在首充,判断开关返回对应的续充折扣
            if($agent_arr['promote_continue_status']==1){//返回配置的渠道续充折扣
                $discount = $agent_arr['promote_discount_continued'];
                $use_data_arr['discount'] = empty($discount)?10:$discount;
                $use_data_arr['discount_name'] = '续充折扣：';
            }else{//开关关闭,返回游戏配置的续充折扣
                $discount = $agent_arr['game_continue_discount'];
                $use_data_arr['discount'] = empty($discount)?10:$discount;
                $use_data_arr['discount_name'] = '续充折扣：';
            }
        }else{//当前游戏-渠道-玩家 不存在首充,判断开关返回对应的首充折扣
            if($agent_arr['promote_first_status']==1){//返回配置的渠道首充折扣
                $discount = $agent_arr['promote_discount_first'];
                $use_data_arr['discount'] = empty($discount)?10:$discount;
                $use_data_arr['discount_name'] = '首充折扣：';
            }else{//开关关闭,返回游戏配置的首充折扣
                $discount = $agent_arr['game_discount'];
                $use_data_arr['discount'] = empty($discount)?10:$discount;
                $use_data_arr['discount_name'] = '首充折扣：';
            }
        }
        return $use_data_arr;
    }
    //不存在渠道代充配置,则根据游戏中的代充配置判断返回首充续充信息
    $game_discount =get_game_attr_entity($game_id,'discount,continue_discount');
    if(!empty($recharge)){//存在首充订单,返回游戏的续充折扣
        $use_data_arr['discount'] = $game_discount['continue_discount']??10;
        $use_data_arr['discount_name'] = '续充折扣：';
        return $use_data_arr;
    }else{//不存在首充订单,返回游戏统一首充折扣
        $use_data_arr['discount'] = $game_discount['discount']??10;
        $use_data_arr['discount_name'] = '首充折扣：';
        return $use_data_arr;
    }
}
//根据 game_id 和统一折扣discount,修改已有的会长代充关联的游戏折扣信息
//更改-统一折扣更改为同一首充折扣,同一续充折扣-20210902-byh
function update_promote_agent_discount($game_id=0,$discount=0,$continue_discount=0)
{
    if($game_id == 0 || $discount == 0 || $continue_discount == 0 || $discount>10 || $continue_discount>10){
        return false;
    }
    $update = [
        'game_discount'=>$discount,
        'game_continue_discount'=>$continue_discount,
    ];
    \think\Db::table('tab_promote_agent')->where('game_id',$game_id)->update($update);

}


function htmlencode($str){
    if(empty($str)) return;
    if($str=="") return $str;
    $str=trim($str);
    $str=str_replace("&","&",$str);
    $str=str_replace(">",">",$str);
    $str=str_replace("<","<",$str);
    $str=str_replace(chr(32)," ",$str);
    $str=str_replace(chr(9)," ",$str);
    $str=str_replace(chr(9)," ",$str);
    $str=str_replace(chr(34),"&",$str);
    $str=str_replace(chr(39),"'",$str);
    $str=str_replace(chr(13)," 
",$str);
    $str=str_replace("'","''",$str);
    $str=str_replace("select","select",$str);
    $str=str_replace("SCRIPT","SCRIPT",$str);
    $str=str_replace("script","script",$str);
    $str=str_replace("join","join",$str);
    $str=str_replace("union","union",$str);
    $str=str_replace("where","where",$str);
    $str=str_replace("insert","insert",$str);
    $str=str_replace("delete","delete",$str);
    $str=str_replace("update","update",$str);
    $str=str_replace("like","like",$str);
    $str=str_replace("drop","drop",$str);
    $str=str_replace("create","create",$str);
    $str=str_replace("modify","modify",$str);
    $str=str_replace("rename","rename",$str);
    $str=str_replace("alter","alter",$str);
    $str=str_replace("cast","cas",$str);
    return $str;
}
// 根据渠道id 获取推广平台 推广联盟 基本信息 by wjd
function get_promote_union_set($promote_id = 0,$promote_level = 0){
    if($promote_id <= 0 || $promote_level <= 0){
        return [];
    }
    if($promote_level == 3){
        $promote_info3 = \think\Db::table('tab_promote')->where(['id'=>$promote_id])->find();
        $promote_id2 = $promote_info3['parent_id'];
        $promote_union_info = \think\Db::table('tab_promote_union')->where(['union_id'=>$promote_id2])->find();
        $promote_union_set_arr = json_decode($promote_union_info['union_set'], true);
        if(empty($promote_union_set_arr)){
            $promote_id1 =  $promote_info3['top_promote_id'];
            $promote_union_info = \think\Db::table('tab_promote_union')->where(['union_id'=>$promote_id1])->find();
            $promote_union_set_arr = json_decode($promote_union_info['union_set'], true);
        }
    }
    if($promote_level == 2){
        // $promote_union_info = \think\Db::table('tab_promote_union')->where(['union_id'=>$promote_id])->find();
        // $promote_union_set_arr = json_decode($promote_union_info['union_set'], true);
        // if(empty($promote_union_set_arr)){
            $promote_info2 = \think\Db::table('tab_promote')->where(['id'=>$promote_id])->find();
            $promote_id1 = $promote_info2['parent_id'];
            $promote_union_info = \think\Db::table('tab_promote_union')->where(['union_id'=>$promote_id1])->find();
            $promote_union_set_arr = json_decode($promote_union_info['union_set'], true);
        // }
    }
    if($promote_level == 1){
        $promote_union_info = \think\Db::table('tab_promote_union')->where(['union_id'=>$promote_id])->find();
        $promote_union_set_arr = json_decode($promote_union_info['union_set'], true);
    }
    if(empty($promote_union_set_arr)){
        // 渠道未设置
        return [];
    }else{
        // 渠道设置了 返回设置信息
        return $promote_union_set_arr;
    }

}

/**
 * 只保留字符串首尾字符，隐藏中间用*代替（两个字符时只显示第一个）
 */
function substr_cut($srt)
{
    $strlen = mb_strlen($srt, 'utf-8');
    if ($strlen < 2) {
        return $strlen;
    }
    $firstStr = mb_substr($srt, 0, 1, 'utf-8');
    $lastStr = mb_substr($srt, - 1, 1, 'utf-8');
    return $strlen == 2 ? $firstStr . str_repeat('*', mb_strlen($srt, 'utf-8') - 1) : $firstStr . str_repeat("*", $strlen - 2) . $lastStr;
}


/**
 * @获取测试状态中的游戏id
 *
 * @$has_disabled_game : 是否包含禁用游戏
 * 排除测试状态中的游戏 和 渠道独占游戏
 * @author: zsl
 * @since: 2021/6/2 10:52
 */
function get_test_game_ids($has_disabled_game=false)
{
    $mGame = new \app\game\model\GameModel();
    $where = [];
    $where['test_game_status'] = 1;
    if(false!==$has_disabled_game){
        $where['game_status'] = 0;
    }
    $ids = $mGame -> where($where)->whereOr(['only_for_promote'=>1]) -> column('id');  // 增加渠道独占的游戏不显示
    if (empty($ids)) {
        $ids = -1;
    }
    return $ids;
}

/**
 * @提取字符串中的数字
 */
function findNum($str = '')
{
    $str = trim($str);
    if (empty($str)) {
        return '';
    }
    $result = '';
    for ($i = 0; $i < strlen($str); $i ++) {
        if (is_numeric($str[$i])) {
            $result .= $str[$i];
        }
    }
    return $result;
}
/**
 * 渠道独占游戏
 * create by wjd 2021-6-8 09:55:47
 * 传入渠道id, 输出当前渠道允许显示和禁止显示的游戏id
*/
function game_only_for_promote($promote_id=0)
{
    $current_promote_id = $promote_id;
    $forbid_game_ids = [];
    $allow_game_ids = [];
    $gameModel = new \app\game\model\GameModel();
    $tmp_game_info = $gameModel->field('id,promote_ids2,only_for_promote')->select()->toArray();

    if(!empty($tmp_game_info)){
        foreach($tmp_game_info as $k=>$v){
            if($v['only_for_promote'] == 1){  // 0 通用, 1 渠道独占
                $only_for_promote_ids = explode(',', $v['promote_ids2']);
                if(!in_array($current_promote_id, $only_for_promote_ids)){
                    $forbid_game_ids[] = $v['id'];
                }else{
                    $allow_game_ids[] = $v['id'];
                }
            }
        }
    }
    $returnArr = [
        'forbid_game_ids'=>$forbid_game_ids,
        'allow_game_ids'=>$allow_game_ids
    ];

    return $returnArr;
}

function get_admin_list($map = [], $field = 'id,user_type,user_login as account',$order='id desc')
{
    $list = think\Db::table('sys_user')->field($field)->where($map)->order($order)->select()->toArray();
    if (empty($list)) {
        return '';
    }
    return $list;
}

//获取对应管理员查看游戏的权限判断-20210624-get_admin_view_game_ids
function get_admin_view_game_ids($admin_id,$game_id = 0)
{
    //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
    $view_game_ids = \think\Db::name('user')->where('id',$admin_id)->value('view_game_ids');
    if(empty($game_id)){//页面无传值时,使用限制条件
        if(!empty($view_game_ids)){
            $game_id = $view_game_ids;
        }
    }else{
        if(!empty($view_game_ids) && !in_array($game_id,explode(',',$view_game_ids))){
            $game_id = $view_game_ids;
        }
    }
    return $game_id;
}

/**
 * 获取用户信息根据后台设置的隐私模式显示或隐藏用户的账号或手机号
 * created by wjd 2021-6-28 14:00:41
 */
function get_user_entity2($id = 0, $isAccount = false, $field = '*')
{
    if ($id == '') {
        return false;
    }
    $user = think\Db::table('tab_user');
    if ($isAccount) {
        $map['account'] = $id;
        $data = $user->field($field)->where($map)->find();
    } else {
        $data = $user->field($field)->find($id);
    }
    if (empty($data)) {
        return false;
    }
    //更改隐私
    $ys_show_admin = get_admin_privicy_two_value();
    if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
        $data['account'] = get_ys_string($data['account'],$ys_show_admin['account_show_admin']);
    }
    if($ys_show_admin['phone_show_admin_status'] == 1){//开启了手机查看隐私
        $data['phone'] = get_ys_string($data['phone'],$ys_show_admin['phone_show_admin']);
    }

    return $data;
}

/**
 * 获取用户名称根据后台设置的隐私模式显示或隐藏用户的账号
 */
function get_user_name2($id = 0)
{
    if (empty($id)) {
        return '';
    }
    $user = think\Db::table('tab_user');
    $data = $user->field('account')->where('id', $id)->find();
    if (empty($data)) {
        return false;
    }
    //更改隐私
    $ys_show_admin = get_admin_privicy_two_value();
    if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
        $data['account'] = get_ys_string($data['account'],$ys_show_admin['account_show_admin']);
    }

    return $data['account'];
}
/**
 * 获取隐私权限的两个值(管理员端)
 * created by wjd 2021-6-28 15:19:03
 * -改-获取隐私设置功能(账号/手机/角色)的开关和配置-by:byh 2021-9-15
*/
function get_admin_privicy_two_value()
{
    // 判断当前管理员是否有权限显示完成整手机号或完整账号
    $admin_id = session('ADMIN_ID');
    $cache_ = (new think\Cache);
    $tag = 'promote_or_admin_two_value';
    //账号
    $account_show_admin = $cache_->get('account_show_admin_'.$admin_id);
    $account_show_admin_status = $cache_->get('account_show_admin_status_'.$admin_id);
    //手机
    $phone_show_admin = $cache_->get('phone_show_admin_'.$admin_id);
    $phone_show_admin_status = $cache_->get('phone_show_admin_status_'.$admin_id);
    //角色
    $role_show_admin = $cache_->get('role_show_admin_'.$admin_id);
    $role_show_admin_status = $cache_->get('role_show_admin_status_'.$admin_id);

    if($account_show_admin === false || $phone_show_admin === false || $role_show_admin === false){

        $safe_center_info = think\Db::table('tab_safe_center')->where('id = 1')->field('id,config,ids')->find();

        $safe_center_info_arr = json_decode($safe_center_info['config'], true);

        $account_show_admin_status = $safe_center_info_arr['account_show_admin_status'] ?? 0;//账号开关
        $phone_show_admin_status = $safe_center_info_arr['phone_show_admin_status'] ?? 0;//手机开关
        $role_show_admin_status = $safe_center_info_arr['role_show_admin_status'] ?? 0;//角色开关

        $forbid_ids = json_decode($safe_center_info['ids'], true);

        //在设置内写入缓存
        if(in_array($admin_id, $forbid_ids)){
            $account_show_admin = $safe_center_info_arr['account_show_admin']??0;
            $cache_->tag($tag)->set('account_show_admin_'.$admin_id, $account_show_admin);
            $cache_->tag($tag)->set('account_show_admin_status_'.$admin_id, $account_show_admin_status);

            $phone_show_admin = $safe_center_info_arr['phone_show_admin']??0;
            $cache_->tag($tag)->set('phone_show_admin_'.$admin_id, $phone_show_admin);
            $cache_->tag($tag)->set('phone_show_admin_status_'.$admin_id, $phone_show_admin_status);

            $role_show_admin = $safe_center_info_arr['role_show_admin']??0;
            $cache_->tag($tag)->set('role_show_admin_'.$admin_id, $role_show_admin);
            $cache_->tag($tag)->set('role_show_admin_status_'.$admin_id, $role_show_admin_status);
        }
    }
    return [
        'account_show_admin'        => $account_show_admin,
        'account_show_admin_status' => $account_show_admin_status,
        'phone_show_admin'          => $phone_show_admin,
        'phone_show_admin_status'   => $phone_show_admin_status,
        'role_show_admin'           => $role_show_admin,
        'role_show_admin_status'    => $role_show_admin_status,
        ];
}

/**
 * 获取隐私权限的两个值(渠道端)
 * created by wjd 2021-6-28 15:19:03
 * -改-获取隐私设置功能(账号/手机/角色)的开关和配置-by:byh 2021-9-15
*/
function get_promote_privicy_two_value()
{
    if(PID_LEVEL > 1){
        $promote_id = think\Db::table('tab_promote')->where(['id'=>PID])->field('id,top_promote_id')->find()['top_promote_id'];
    }else{
        $promote_id = session('PID');
    }
    // 判断当前渠道是否有权限显示完成整手机号或完整账号
    $cache_ = (new think\Cache);
    $tag = 'promote_or_admin_two_value';
    //账号
    $account_show_promote = $cache_->get('account_show_promote_'.$promote_id);
    $account_show_promote_status = $cache_->get('account_show_promote_status_'.$promote_id);
    //手机
    $phone_show_promote = $cache_->get('phone_show_promote_'.$promote_id);
    $phone_show_promote_status = $cache_->get('phone_show_promote_status_'.$promote_id);
    //角色
    $role_show_promote = $cache_->get('role_show_promote_'.$promote_id);
    $role_show_promote_status = $cache_->get('role_show_promote_status_'.$promote_id);

    if($account_show_promote === false || $phone_show_promote === false || $role_show_promote === false ){

        $safe_center_info = think\Db::table('tab_safe_center')->where('id = 2')->field('id,config,ids')->find();
        $safe_center_info_arr = json_decode($safe_center_info['config'], true);

        $account_show_promote_status = $safe_center_info_arr['account_show_promote_status'] ?? 0;//账号开关
        $phone_show_promote_status = $safe_center_info_arr['phone_show_promote_status'] ?? 0;//手机开关
        $role_show_promote_status = $safe_center_info_arr['role_show_promote_status'] ?? 0;//角色开关

        $forbid_ids = json_decode($safe_center_info['ids'], true);

        if(in_array($promote_id, $forbid_ids)){
            $account_show_promote = $safe_center_info_arr['account_show_promote']??0;
            $cache_->tag($tag)->set('account_show_promote_'.$promote_id, $account_show_promote);
            $cache_->tag($tag)->set('account_show_promote_status_'.$promote_id, $account_show_promote_status);

            $phone_show_promote = $safe_center_info_arr['phone_show_promote']??0;
            $cache_->tag($tag)->set('phone_show_promote_'.$promote_id, $phone_show_promote);
            $cache_->tag($tag)->set('phone_show_promote_status_'.$promote_id, $phone_show_promote_status);

            $role_show_promote = $safe_center_info_arr['role_show_promote']??0;
            $cache_->tag($tag)->set('role_show_admin_'.$promote_id, $role_show_promote);
            $cache_->tag($tag)->set('role_show_admin_status_'.$promote_id, $role_show_promote_status);
        }
    }
    return [
        'account_show_promote'          => $account_show_promote,
        'account_show_promote_status'   => $account_show_promote_status,
        'phone_show_promote'            => $phone_show_promote,
        'phone_show_promote_status'     => $phone_show_promote_status,
        'role_show_promote'             => $role_show_promote,
        'role_show_promote_status'      => $role_show_promote_status,
    ];
}

/**
 * 获取用户信息
 * created by wjd 2021-6-28 17:58:31
 */
function get_user_info2($field = true, $map = [])
{
    if (!$field) {
        $field = true;
    }
    $data = think\Db::table('tab_user')->field($field)->where($map)->find();
    // var_dump($data);exit;
    // 判断当前管理员是否有权限显示完成整手机号或完整账号
    $ys_show_admin = get_admin_privicy_two_value();

    if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
        $data['account'] = get_ys_string($data['account'],$ys_show_admin['account_show_admin']);
    }

    return $data;
}

/**
 * 获取溪谷客服系统客服配置和客服信息
 * by:byh-20210706
 */
function get_xgkf_info($flag = 1,$game_id=0,$user_id=0)
{
    //判断是否查询游戏客服
    if(empty($game_id)){//无游戏id
        if($flag == 1){//flag为1时,返回客服系统的跳转链接
            //从后台配置的客服中随机返回一个
            $config = cmf_get_option('admin_set');
            //获取随机客服URL返回
            $kf_urls = $config['xg_kf_url'];
            if(empty($kf_urls)) return '';
            shuffle($kf_urls);//数组随机打乱
            return $kf_urls[0];//取出一个返回

        }else{//查询客服配置开关
            $admin_config = cmf_get_option('admin_set');
            return $admin_config['kf_system_switch']??0;//默认还是官方QQ
        }
    }else{//存在游戏id
        //查询游戏对应配置的客服URL链接,如未配置,返回随机客服服务链接
        $xgkf_url_str = \think\Db::table('tab_game_attr')->where('game_id',$game_id)->value('xg_kf_url');//更改为游戏关联表查询
        $xgkf_url_data = json_decode($xgkf_url_str,true);
        if(empty($xgkf_url_str) || empty($xgkf_url_data)){//未配置

            //从后台配置的客服中随机返回一个
            $config = cmf_get_option('admin_set');
            //获取随机客服URL返回
            $kf_urls = $config['xg_kf_url'];
            if(empty($kf_urls)) return '';
            shuffle($kf_urls);//数组随机打乱
            return $kf_urls[0];//取出一个返回
        }else{
            shuffle($xgkf_url_data);//数组随机打乱
            $xgkf_url = $xgkf_url_data[0];//取出一个
        }
        return $xgkf_url;

    }
}

/**
 * 获取游戏的一级渠道数据
 * by:byh-2021-7-8 16:31:29
 */
function game_head_promote_list($game_id=0)
{
    if(empty($game_id)){//不存在游戏id时,查询所有的一级渠道数据
        $map['promote_level'] = 1;
        $map['status'] = 1;
        $data = \think\Db::table('tab_promote')->field('id as promote_id,account')->where($map)->select();

    }else{//存在游戏id,联表查询申请当前游戏的所有一级渠道
        $map['p.promote_level'] = 1;
        $map['pa.game_id'] = $game_id;
        $data = \think\Db::table('tab_promote_apply')->alias('pa')
            ->field('pa.promote_id,p.account')
            ->join(['tab_promote' => 'p'],'p.id = pa.promote_id','left')
            ->where($map)
            ->select();
    }
    $data = empty($data)?[]:$data->toArray();
    //追加官方渠道
    $gf_ = ['promote_id'=>0,'account'=>'官方渠道'];
    array_unshift($data,$gf_);
    return $data;

}
/**
 * 判断玩家是否为封禁状态
 * by:byh-2021-7-9 16:37:05
 */
function judge_user_ban_status($promote_id='',$game_id=0,$user_id=0,$device='',$ip='',$type=0)
{
    //判断参数
    if(empty($game_id) || empty($type)){//游戏和判断查询类型不可为空
        return true;
    }
    if(empty($user_id) && empty($device) && empty($ip) && $promote_id==''){//需要判断是参数不可都为空
        return true;
    }
    //查询游戏的封禁信息 封禁类型 1=禁止登录 2=禁止注册 3=禁止充值 4=禁止下载
    $ban = \think\Db::table('tab_game_ban_set')->where('game_id',$game_id)->find();
    $ban_types = json_decode($ban['ban_types'],true);
    $ban_user_ids = json_decode($ban['ban_user_ids'],true);
    $ban_promote_ids = json_decode($ban['ban_promote_ids'],true);
    //判断是否在封禁的类型中,不在则直接返回
    if(!in_array($type,$ban_types)){
        return true;
    }
    //封禁时间判断
    $time = time();
//    if($ban['ban_end_time'] > 0){
//        if($ban['ban_start_time']> $time || $ban['ban_end_time']<$time){
//            return true;
//        }
//    }else{
//        if($ban['ban_start_time']> $time){
//            return true;
//        }
//    }
    //有点晕,先全部列举四种时间情况,有时间对比合并代码:开始0结束1;开始1结束0;开始1结束1;开始0结束0(这个不需要判断,必在封禁时间内,直接下一步判断)
    if($ban['ban_start_time']==0 && $ban['ban_end_time']>0 && $ban['ban_end_time']<$time){
        return true;//开始0结束1时,结束时间小于当前时间,不封禁
    }elseif ($ban['ban_start_time']>0 && $ban['ban_end_time']==0 && $ban['ban_start_time']>$time){
        return true;//开始1结束0,开始时间大于当前时间,不封禁
    }elseif ($ban['ban_start_time']>0 && $ban['ban_end_time']>0 && ($ban['ban_end_time']<$time || $ban['ban_start_time']>$time)){
        return true;//开始1结束1,开始时间大于当前时间,或者结束时间小于当前时间,不封禁
    }
    
    if(empty($ban)){//没有数据的话,则不存在封禁
        return true;
    }
    //渠道判断
    if($promote_id !== '' || $promote_id !== null){//不全等空
        //查询渠道一级id
        $top_pid = get_promote_entity($promote_id,'top_promote_id')['top_promote_id']??0;
        if($top_pid>0){
            $promote_id = $top_pid;
        }
        if(in_array($promote_id,$ban_promote_ids)){
            return false;
        }
    }
    //玩家判断
    if(!empty($user_id)){
        //判断是否封禁该玩家ID
        if(in_array($user_id,$ban_user_ids)){
            return false;
        }
        //查询玩家的渠道,是否封禁状态
        $u_promote_id = get_user_entity($user_id,false,'promote_id')['promote_id'];
        $u_top_pid = get_promote_entity($u_promote_id,'top_promote_id')['top_promote_id']??0;
        if($u_top_pid>0){
            $u_promote_id = $u_top_pid;
        }
        if(in_array($u_promote_id,$ban_promote_ids)){
            return false;
        }
    }
    //设备判断
    if(!empty($device)){
        //处理封禁的设备数据
        if(!empty($ban['ban_devices'])){
            $ban_devices = json_decode($ban['ban_devices'],true);
            if(in_array($device,$ban_devices)){//存在封禁
                return false;
            }
        }
    }
    //ip地址判断
    if(!empty($ip)){
        //处理封禁的ip数据
        if(!empty($ban['ban_ips'])){
            $ban_ips = json_decode($ban['ban_ips'],true);
            if(in_array($ip,$ban_ips)){//存在封禁
                return false;
            }
        }
    }
    return true;
}

/**
 * 判断并获取溪谷客服系统的客服链接-暂时作废-使用get_xgkf_info()
 */
function get_xgkf_url()
{
    //判断溪谷客服是否开启
    $config = cmf_get_option('admin_set');
    if($config['kf_system_switch'] != 1) return false;
    //获取随机客服URL返回
    $kf_urls = $config['xg_kf_url'];
    if(empty($kf_urls)) return '';
    shuffle($kf_urls);//数组随机打乱
    return $kf_urls[0];//取出一个返回


}

/**
 * @渠道是否全部允许模拟器登录
 *
 * @author: zsl
 * @since: 2021/7/22 11:05
 */
function get_promote_allow_simulator($promote_id)
{
    $allow_simulator = \think\Db ::table('tab_promote') -> where(['id' => $promote_id]) -> value('allow_simulator');
    return $allow_simulator;
}
/**
 * 判断当前玩家(用户)是否满足进阶条件(更改用户的阶段, (更改玩家阶段))
 * created by wjd 2021-8-2 20:06:13
*/
function user_move_stage($user_id)
{
    if(empty($user_id) || $user_id == 0){
        return false;
    }


}

/**
 * @调试函数
 *
 * @param mixed ...$args
 *
 * @author: zsl
 * @since: 2021/8/4 21:19
 */
function dd(...$args)
{
    foreach ($args as $v) {
        dump($v);
    }
    die();
}

function send_request (
    string $url, $param, string $method = 'GET', array $header = [],
    int $timeout = 10
) {
    if (!function_exists('curl_exec')) {
        return 'please install curl extend';
    }
    if ($method == 'GET') {
        if (is_array($param) && count($param)) {
            $url .= (strpos($url, '?') === false ? '?' : '') .
                http_build_query($param);
        }
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    if (!empty($header)) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    }
    if (empty($_SERVER['HTTP_USER_AGENT'])) {
        curl_setopt(
            $curl, CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36'
        );
    } else {
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    }

    if ($method == 'POST') {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
    }
    if ($method == 'PUT') {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
    }
    if (stripos($url, 'https') !== false) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    $result    = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return ['code' => $http_code, 'content' => $result];
}

/**
 * 隐藏手机号码
 * by:byh-2021-8-16 15:20:50
 * @param string $mobile 11位手机号
 */
function hidden_mobile($mobile='')
{
    if(empty($mobile) || !isMobileNO($mobile)){
        return $mobile;
    }
    return substr($mobile, 0, 3).'****'.substr($mobile, 7);

}
// 2021-08-18-qsh--根据商品id查询商品信息,并把create_time转为日期时间格式
function get_user_transaction($id = 0, $field = '*')
{
    if ($id == '') {
        return false;
    }
    $transaction = think\Db::table('tab_user_transaction')
        ->field($field)
        ->find($id);
    if (empty($transaction)) {
        return false;
    } else {
        if (strpos($field,'create_time') !== false) {
            $transaction['create_time'] = date('m月d日 H:i', $transaction['create_time']);
        }
    }
    return $transaction;
}

/**
 * 调整,根据游戏id获取当前游戏对接的SDK类型sdk_type:1=旗舰 2=简化,3=海外
 * by:byh 2021-8-19 21:03:22
 */
function get_game_sdk_type($game_id)
{
    if(empty($game_id)){
        return 0;
    }
    $type = \think\Db::table('tab_game_attr')->where('game_id',$game_id)->value('sdk_type');
    return empty($type)?1:$type;//默认旗舰
}

/**
 * 根据游戏id获取游戏关联表的数据信息
 * by:byh 2021-8-19 21:03:34
 * @param int $game_id
 * @param string $field
 * @return array|bool|false|PDOStatement|string|\think\Model
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function get_game_attr_entity($game_id = 0, $field = '*')
{
    if (empty($game_id)) {
        return false;
    }
    $model = think\Db::table('tab_game_attr');
    $data = $model->field($field)->where('game_id', $game_id)->find();
    if (empty($data)) {
        return false;
    }
    return $data;
}

/**
 * @获取H5游戏小号信息
 *
 * @author: zsl
 * @since: 2021/8/20 17:53
 */
function get_h5_small_info($user_id, $small_id, $game_id)
{
    $mUser = new  app\member\model\UserModel();
    $where = [];
    $where['id'] = $small_id;
    $where['puid'] = $user_id;
    //判断是否互通游戏
    $lInterflow = new app\game\logic\InterflowLogic();
    $game_ids = $lInterflow -> getInterflowGameIds(['game_id' => $game_id]);
    if (empty($game_ids)) {
        $where['fgame_id'] = $game_id;
    } else {
        $where['fgame_id'] = ['in', $game_ids];
    }
    $smallInfo = $mUser -> field('id,puid,fgame_id') -> where($where) -> find();
    if (empty($smallInfo)) {
        return false;
    }
    return $smallInfo;
}

/**
 * @获取用户大号id
 *
 * @param $return_self : 如果为大号是否返回id
 *
 * @author: zsl
 * @since: 2021/8/20 19:49
 */
function get_user_puid($user_id, $return_self = true)
{
    $mUser = new  app\member\model\UserModel();
    $puid = $mUser -> where(['id' => $user_id]) -> value('puid');
    if (empty($puid)) {
        if ($return_self) {
            return $user_id;
        } else {
            return 0;
        }
    }
    return $puid;
}


/**
 * [获取游戏类型-多类型数据]
 * by-byh 2021-8-20 12:03:34
 */
function get_game_type_name_str($types = null)
{
    if (!isset($types) || empty($types)) {
        return '';
    }
    $map['id'] = ['IN',$types];
    $cl = think\Db::table("tab_game_type")->where($map)->order('sort desc,id desc')->column('type_name');
    //转字符串
    if(empty($cl)){
        return '';
    }
    return implode(',',$cl);
}

/**
 * 获取所有简化版平台
 *
 * @return array
 * @author: Juncl
 * @time: 2021/08/23 15:16
 */
function get_webplatform_lists()
{
    $data = think\Db::table('tab_web_platform')
        ->field('id,platform_name')
        ->where('status',1)
        ->select()->toarray();;
    return $data;
}

/**
 * 获取第三方游戏渠道ID
 *
 * @author: Juncl
 * @time: 2021/08/27 14:40
 */
function get_real_promote_id($param=[])
{
    // 第三方游戏渠道判断
    if(!empty($param['promote_id']) && strpos($param['promote_id'],'_XgPid_')!==false){
        $promote_list = explode('_XgPid_',$param['promote_id']);
        $param['promote_id'] = $promote_list[0];
        $param['third_promote_id'] = $promote_list[1];
        return $param;
    }else{
        return $param;
    }
}

/**
 * 判断是否是第三方渠道
 *
 * @param int $promote_id
 * @author: Juncl
 * @time: 2021/08/27 15:41
 */
function is_third_platform($promote_id=0)
{
    if($promote_id == 0){
        return false;
    }
    $id = think\Db::table('tab_web_platform')->where('promote_id',$promote_id)->value('id');
    if($id > 0){
        return true;
    }else{
        return false;
    }
}

/**
 * 获取简化版用户ID
 *
 * @param int $id
 * @return int|mixed
 * @author: Juncl
 * @time: 2021/08/31 16:14
 */
function get_third_user_id($id=0)
{
    if($id == 0){
        return 0;
    }
    $userId = think\Db::table('tab_web_platform_user')->where('user_id',$id)->value('third_user_id');
    if(!$userId){
        return 0;
    }
    return $userId;
}

/**
 * 游戏是否超级签付费下载
 * created by wjd 2021-8-30 16:09:56
*/
function get_ios_pay_to_download($game_id=0)
{
    if($game_id <= 0){
        return ['pay_download'=>0, 'pay_price'=>0];
    }
    $pay_download_info = think\Db::table('tab_game_ios_pay_to_download')->where(['game_id'=>$game_id])->field('game_id,pay_download,pay_price')->find();
    if(!empty($pay_download_info)){
        return ['pay_download'=>$pay_download_info['pay_download'], 'pay_price'=>$pay_download_info['pay_price']];
    }else{
        return ['pay_download'=>0, 'pay_price'=>0];
    }
}

/**
 * 查询平台详情
 *
 * @param array $map
 * @param string $field
 * @author: Juncl
 * @time: 2021/08/25 21:40
 */
function get_platform_entity_by_map($where='', $field = '*')
{
    if(is_numeric($where)){
        $map['id'] = $where;
    }elseif(is_array($where)){
        $map = $where;
    }else{
        return false;
    }
    $model = think\Db::table('tab_platform');
    $data = $model->field($field)->where($map)->find();
    if (empty($data)) {
        return false;
    }
    return $data;
}

/**
 * 根据传参字符串更改成隐私****或者X****X的形式
 * by:byh 2021年9月3日21:26:52
 * @param $string 需要处理的字符串
 * @param $type 处理类型 0=**** 1=X****X
 */
function get_ys_string($string='',$type=0)
{
    if(empty($string)){
        return '';
    }
    switch ($type){
        case 1:
            $firstStr = mb_substr($string, 0, 1, 'utf-8');
            $lastStr = mb_substr($string, - 1, 1, 'utf-8');
            $new_str = $firstStr.'****'.$lastStr;
            break;
        default://0或false
            $new_str = '****';
    }
    return $new_str;

}

/**
 * 根据平台ID和平台游戏ID获取游戏详情
 *
 * @param int $platform_id
 * @param int $cp_game_id
 * @param string $field
 * @return array|bool|false|PDOStatement|string|\think\Model
 * @author: Juncl
 * @time: 2021/08/25 21:43
 */
function get_third_game_entity($platform_id=0,$cp_game_id=0,$field='*')
{
    $map['platform_id'] = $platform_id;
    $map['cp_game_id'] = $cp_game_id;
    $data = think\Db::table('tab_game')->field($field)->where($map)->find();
    if (empty($data)) {
        return false;
    }
    return $data;
}

/**
 * 获取游戏的渠道等级限制
 *
 * @param int $game_id
 * @param int $type
 * @return int|mixed|string
 * @author: Juncl
 * @time: 2021/09/13 10:03
 */
function get_game_level($game_id=0, $type=1)
{
    $GameAttr = think\Db::table('tab_game_attr')->field('promote_level_limit')->where('game_id',$game_id)->find();
    if(empty($GameAttr)){
        return $type == 1 ? '--' : 0;
    }
    // 返回渠道等级限制
    if($type == 0){
        return $GameAttr['promote_level_limit'];
    }
    // 返回等级名称
    if($GameAttr['promote_level_limit'] == 0){
        return '--';

    }
    $promoteLevel = cmf_get_option('promote_level_set');
    if(empty($promoteLevel)){
        return '--';
    }
    $data = '--';
    foreach ($promoteLevel as $key => $val){
        if($GameAttr['promote_level_limit'] == $val['level']){
            $data = $val['level_name'];
        }
    }
    return $data;
}

/**
 * 返回游戏开关折扣和代金券开关
 *
 * @param int $game_id
 * @author: Juncl
 * @time: 2021/09/15 14:57
 */
function get_game_show_status($game_id=0)
{
    $model = think\Db::table('tab_game_attr');
    $showStatus = $model->field('coupon_show_status,discount_show_status')->where('game_id', $game_id)->find();
    $wapShowStatus = cmf_get_option('wap_set');
    if($showStatus['coupon_show_status'] == 1 && $wapShowStatus['coupon_entry'] == 1){
        $data['coupon_show_status'] = 1;
    }else{
        $data['coupon_show_status'] = 0;
    }
    if($showStatus['discount_show_status'] == 1 && $wapShowStatus['discount_entry'] == 1){
        $data['discount_show_status'] = 1;
    }else{
        $data['discount_show_status'] = 0;
    }
    return $data;
}

/**
 * 获取游戏分成比例/注册单间最后生效时间
 *
 * @param int $game_id
 * @param string $field
 * @return false|string
 * @author: Juncl
 * @time: 2021/09/16 11:32
 */
function get_game_begin_time($game_id = 0,$field='')
{
    if($field != 'ratio' && $field != 'money'){
        return '--';
    }
    $attr = $field . '_begin_time';
    $model = think\Db::table('tab_game_attr');
    $beginTime = $model->where('game_id', $game_id)->value($attr);
    if($beginTime > 0){
        return date('Y-m-d H:i:s',$beginTime);
    }else{
        return '--';
    }
}
/**
 * 需要隐藏的游戏id集合
 * created by wjd 2021-9-23 20:39:55
*/
function get_hidden_game_ids(){
    $map = [];
    $map['test_game_status'] = 1;
    $hidden_game_ids = [];
    $game_infos = think\Db::table('tab_game')->where($map)->field('id,game_name')->select();
    foreach($game_infos as $k=>$v){
        $hidden_game_ids[] = $v['id'];
    }
    return $hidden_game_ids;
}

/**
 * 处理广告列表模块类型名称
 * by:byh 2021-9-24 13:39:28
 * @param string $module 模块字段值
 */
function getAdvModuleName($module='')
{
    switch ($module){
        case 'sdk':
            $name = '手游SDK';
            break;
        case 'app':
            $name = 'APP';
            break;
        case 'wap':
            $name = 'WAP站';
            break;
        case 'media':
            $name = 'PC官网';
            break;
        case 'h5game':
            $name = 'H5游戏';
            break;
        case 'simple_sdk':
            $name = '简化版SDK';
            break;
        default:
            $name = '未知';
    }
    return $name;
}