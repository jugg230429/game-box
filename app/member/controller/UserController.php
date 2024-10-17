<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\member\controller;

use app\common\controller\BaseController;
use app\common\controller\BaseHomeController;
use app\common\logic\AntiaddictionLogic;
use app\common\model\UserAgeRecordModel;
use app\common\model\UserFeedbackModel;
use app\common\task\HandleUserStageTask;
use app\extend\model\MsgModel;
use app\member\logic\UserLogic;
use app\member\model\UserBalanceEditModel;
use app\member\model\UserLoginRecordModel;
use app\member\model\UserModel;
use app\member\model\UserPlayInfoModel;
use app\member\model\UserPlayModel;
use app\promote\model\PromoteapplyModel;
use app\user\model\UserUnsubscribeModel;
use cmf\controller\AdminBaseController;
use think\Cache;
use think\Db;
use think\Request;
use think\xigusdk\Xigu;

class UserController extends AdminBaseController
{

    //判断权限
    public function __construct()
    {
        parent::__construct();
        if (AUTH_USER != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买用户权限');
            } else {
                $this->error('请购买用户权限', url('admin/main/index'));
            };
        }
    }

    //用户信息
    public function userinfo()
    {
        $user = new UserModel;
        $base = new BaseController;
        $user_id = $this->request->param('user_id', 0, 'intval');
        if ($user_id > 0) {
            $map['tab_user.id'] = $user_id;
        }
        $account = $this->request->param('account', '');
        if ($account != '') {
            $map['account'] = ['like', '%' . $account . '%'];
        }
        $phone = $this->request->param('phone', '');
        if ($phone != '') {
            $map['tab_user.phone'] = trim($phone);
        }
        //注册游戏id
        $fgame_id = $this->request->param('fgame_id', 0, 'intval');
        if ($fgame_id > 0) {
            $map['tab_user.fgame_id'] = $fgame_id;
        }
        $small_nickname = $this->request->param('small_nickname', '');
        if ($small_nickname != '') {
            $small = get_user_lists_info('puid',['puid'=>['gt',0],'nickname'=>['like', '%' . $small_nickname . '%']]);
            if(!empty($small)){
                $puid = implode(',',array_unique(array_column($small,'puid')));
                $map['tab_user.id'] = ['in',$puid];
            }else{
                $map['tab_user.id'] = 0;//返回空数据
            }
        }
        $equipment_num = trim($this->request->param('equipment_num'));
        if($equipment_num != ''){
            $map['equipment_num'] = ['like', '%' . $equipment_num . '%'];
        }
        $register_ip = trim($this->request->param('register_ip'));
        if($register_ip != ''){
            $map['register_ip'] = ['like', '%' . $register_ip . '%'];
        }
        $user_status = $this->request->param('user_status', '');
        if ($user_status==='1' || $user_status==='0') {
            $map['lock_status'] = $user_status;
        }
        if ($user_status === '2') {
            $map['un.status'] = 1;
        }
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time && $end_time) {
            $map['register_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['register_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['register_time'] = ['egt', strtotime($start_time)];
        }
        $login_start_time = $this->request->param('login_start_time', '');
        $login_end_time = $this->request->param('login_end_time', '');
        if ($login_start_time && $login_end_time) {
            $map['login_time'] = ['between', [strtotime($login_start_time), strtotime($login_end_time) + 86399]];
        } elseif ($login_end_time) {
            $map['login_time'] = ['lt', strtotime($login_end_time) + 86400];
        } elseif ($login_start_time) {
            $map['login_time'] = ['egt', strtotime($login_start_time)];
        }
        $register_type = $this->request->param('register_type', '');
        if ($register_type != '') {
            if($register_type=='catch_create'){
                $map['is_batch_create'] = 1;
            }else{
                $map['register_type'] = $register_type;
                $map['is_batch_create'] = 0;
            }
        }
        $age_status = $this->request->param('age_status', '');
        if ($age_status != '') {
            $map['age_status'] = $age_status;
        }
        $viplevel = $this->request->param('viplevel', '');
        if ($viplevel != '') {
            $map['vip_level'] = $viplevel;
        }
        $cumulative = $this->request->param('cumulative');
        if($cumulative > 0){
            switch ($cumulative){
                case 1:
                    $map['cumulative'] = ['elt',100];
                    break;
                case 2:
                    $map['cumulative'] = ['between',[101,1000]];
                    break;
                case 3:
                    $map['cumulative'] = ['between',[1001,10000]];
                    break;
                case 4:
                    $map['cumulative'] = ['between',[10001,50000]];
                    break;
                case 5:
                    $map['cumulative'] = ['between',[50001,100000]];
                    break;
                case 6:
                    $map['cumulative'] = ['gt',100000];
                    break;
            }
        }

        //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        $view_game_ids = get_admin_view_game_ids(session('ADMIN_ID'));
        if(!empty($view_game_ids)){
            $map['tab_user.fgame_id'] = ['IN',$view_game_ids];
        }
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-end

        $map['puid'] = 0;//只显示大号
        $sort_type = $this->request->param('sort_type', '');
        $sort = $this->request->param('sort', 1, 'intval');
        if (AUTH_PAY == 1) {
            //排序
            if ($sort == 1) {
                $exend['order'] = 'id desc';
            } elseif ($sort == 2) {
                $exend['order'] = "$sort_type desc";
            } else {
                $exend['order'] = "$sort_type asc";
            }
            $promote_id = $this->request->param('promote_id', '');
            if ($promote_id != '') {
                $promote_ids = get_zi_promote_id($promote_id);
                array_push($promote_ids,$promote_id);
                $map['tab_user.promote_id'] = ['in',$promote_ids];
            }
            $extend['row'] = $this->request->param('page');
            $exend['field'] = 'tab_user.id,count(s.user_id) as count,max(s.pay_time) as last_pay_time,point,account,phone,
            cumulative,tab_user.promote_id,tab_user.promote_account,balance,register_type,vip_level,register_time,
            login_time,login_ip,lock_status,equipment_num,fgame_name,member_days,end_time,register_ip,device_name,
            age_status,is_unsubscribe,un.status as unsubscribe_status,gold_coin,tab_user.is_batch_create,tab_user.login_equipment_num,tab_user.head_img,tab_user.email';
            $exend['group'] = 'tab_user.id';
            $exend['join1'] = [['tab_spend' => 's'], 's.user_id=tab_user.id and s.pay_status=1', 'left'];
            $exend['join2'] = [['tab_user_unsubscribe' => 'un'], 'un.user_id=tab_user.id', 'left'];
            $data = $base->data_list_join($user, $map, $exend)->each(function ($item, $key) {
                $smallfield = 'id';
                $smallmap['puid'] = $item['id'];
                $smallorder = 'id desc';
                $item['small_count'] = count(get_user_lists_info($smallfield,$smallmap,$smallorder));
                //$item['count_day'] = $item['last_pay_time'] ? (int)((time() - $item['last_pay_time']) / 86400) : '--';
                $item['login_time'] = get_login_time($item['login_time']);
                $item['last_pay_time'] = $item['last_pay_time'] ? date('Y-m-d', $item['last_pay_time']) : '--';
                if($item['end_time'] > time()){
                    $item['valid_days'] = get_days(date('Y-m-d',$item['end_time']),date('Y-m-d'));
                }else{
                    $item['valid_days'] = 0;
                }
                $item['head_img'] = empty($item['head_img']) ? '' : cmf_get_image_url($item['head_img']);

                return $item;
            });
            // 获取分页显示
            $page = $data->render();
        } else {
            $promote_id = $this->request->param('promote_id', '');
            if ($promote_id != '') {
                $map['promote_id'] = $promote_id;
            }
            $exend['order'] = 'id desc';
            $exend['field'] = 'id,account,cumulative,promote_id,promote_account,balance,register_type,vip_level,register_time,login_time,login_ip,lock_status,is_batch_create';
            $exend['group'] = 'id';
            $data = $base->data_list($user, $map, $exend)->each(function ($item, $key) {
                $item['login_time'] = get_login_time($item['login_time']);
                return $item;
            });
            // 获取分页显示
            $page = $data->render();
        }

        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();

        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['account'] = get_ys_string($v5['account'],$ys_show_admin['account_show_admin']);
            }

        }

        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * [用户编辑页面]
     * @author 郭家屯[gjt]
     */
    public function edit(Request $request)
    {
        $user_id = $this->request->param('id', 0, 'intval');
        if (empty($user_id)) $this->error('参数错误');
        if ($request->isPost()) {
            $data = $request->post();
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                if (!preg_match("/^[A-Za-z0-9]+$/", $data['password']) || strlen($data['password']) < 6 || strlen($data['password']) > 15) {
                    $this->error('密码为6-15位字母或数字组合');
                }
                $save['password'] = cmf_password($data['password']);
            }
            $save['lock_status'] = $data['lock_status'];
            $save['sso'] = $data['sso'];
            $member = new UserModel;
            $res = $member->where('id', $user_id)->update($save);
            if ($res !== false) {
                write_action_log("修改玩家【" . get_user_name($user_id) . "】信息");
                $this->success("修改成功", url('userinfo'));
            } else {
                $this->error("修改失败");
            }
        }
        if (empty($user_id)) $this->error('参数错误');
        //消费记录
        if (AUTH_PAY == 1) {
            $map['user_id'] = $user_id;
            $map['pay_status'] = 1;
            $spend = Db ::table('tab_spend') -> field('count(id) as count,max(pay_time) as last_pay_time')
                    -> where($map)
                    -> find();
            $this -> assign('count', $spend['count']);
            $this->assign('last_pay_time', $spend['last_pay_time']);
            $this->assign('count_day', $spend['last_pay_time'] ? (int)((time() - $spend['last_pay_time']) / 86400) : '--');
        }
        //获取用户信息
        $user = get_user_entity2($user_id);
        $user['receive_address'] = implode(' ', explode('|!@#%-|', $user['receive_address']));
        $user['head_img'] = cmf_get_image_url($user['head_img']);
        $user['bind_balance'] = Db::table('tab_user_play')->where('user_id', $user_id)->sum('bind_balance');
        $this->assign('data', $user);
        //登录信息
        $mUserLoginRecord = new UserLoginRecordModel();
        $where['user_id'] = $user_id;
        $where['login_time'] = ['gt', 0];
        $where['game_id'] = ['gt', 0];
        $login_count = $mUserLoginRecord
                -> field('id')
                -> where($where)
                -> count();
        $this -> assign('login_count', $login_count);
        return $this->fetch();
    }

    /**
     * [ban 禁用用户]
     * @return [type] [description]
     * @author [yyh] <[<email address>]>
     */
    public function ban()
    {
        $id = $this->request->param('id');
        if (empty($id)) $this->error('数据传入失败');
        $ids = explode(',', $id);
        $error_num = 0;
        foreach ($ids as $key => $value) {
            $result = Db::table('tab_user')->where(["id" => $value])->setField('lock_status', '0');
            if ($result !== false) {
                write_action_log("锁定玩家【" . get_user_name($value) . "】");
            } else {
                // 计算错误行数
                $error_num++;
            }
        }
        if ($error_num == 0) {
            $this->success('操作成功');
        } else {
            $this->success('有'.$error_num.'条数据操作失败');
        }
    }

    /**
     * [cancelBan 启用用户]
     * @return [type] [description]
     * @author [yyh] <[<email address>]>
     */
    public function cancelBan()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::table('tab_user')->where(["id" => $id])->setField('lock_status', '1');
            if ($result !== false) {
                write_action_log("解锁玩家【" . get_user_name($id) . "】");
                $this->success("用户解锁成功！", url("user/userinfo"));
            } else {
                $this->error('用户解锁失败！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * [mend 补链页面]
     */
    public function mend()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            return $this->fetch();
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * [用户消费记录]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function spendrecord()
    {
        $user_id = $this->request->param('user_id', 0, 'intval');
        if (empty($user_id)) $this->error('参数错误');
        if (AUTH_PAY == 1) {
            $map['user_id'] = $user_id;
            $map['pay_status'] = 1;
            $base = new BaseController();
            $extend['field'] = 'pay_order_number,game_name,server_name,game_player_name,pay_way,pay_amount,pay_time,spend_ip';
            $model = new \app\recharge\model\SpendModel;
            $data = $base->data_list($model, $map, $extend);
            $page = $data->render();
            //今日充值
            $todaytotal = $model->where($map)->where('pay_time', 'BETWEEN', total(1, 2))->sum('pay_amount');
            $this->assign('todaytotal', $todaytotal);
            //昨日充值
            $yestodaytotal = $model->where($map)->where('pay_time', 'BETWEEN', total(5, 2))->sum('pay_amount');
            $this->assign('yestodaytotal', $yestodaytotal);
            //总充值
            $total = $model->where($map)->sum('pay_amount');
            //平台币总充值
            $map['pay_way'] = 2;
            $deposit_total = $model->where($map)->sum('pay_amount');
            $this->assign('total', $total);
            $this->assign('deposit_total', $deposit_total);
            $this->assign('page', $page);
            $this->assign('data_lists', $data);
        }
        $user = get_user_entity($user_id);
        $this->assign('user', $user);
        return $this->fetch();

    }

    /**
     * [修改手机号码]
     * @author 郭家屯[gjt]
     */
    public function changephone()
    {
        $phone = $this->request->param('phone');
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error("参数错误");
        }
        if (!cmf_check_mobile($phone)) {
            $this->error("请输入11位手机号码");
        }
        //重复判断
        $where['phone'] = $phone;
        $user = Db::table('tab_user')->where($where)->field('id,account')->find();
        if ($user) {
            $this->error("手机号已被使用");
        }
        $map['id'] = $id;
        $pro = Db::table('tab_user')->where($map)->setField('phone', $phone);
        if ($pro !== false) {
            $this->success("手机修改成功");
        } else {
            $this->error("手机修改失败");
        }
    }


    /**
     * [修改邮箱]
     * @author 郭家屯[gjt]
     */
    public function changeemail()
    {
        $email = $this -> request -> param('email');
        $id = $this -> request -> param('id', 0, 'intval');
        if (empty($id)) {
            $this -> error("参数错误");
        }
        if (!cmf_check_email($email)) {
            $this -> error("请输入正确的邮箱地址");
        }
        //重复判断
        $where['email'] = $email;
        $user = Db ::table('tab_user') -> where($where) -> field('id,account') -> find();
        if ($user) {
            $this -> error("邮箱已被使用");
        }
        $map['id'] = $id;
        $pro = Db ::table('tab_user') -> where($map) -> setField('email', $email);
        if ($pro !== false) {
            $this -> success("邮箱修改成功");
        } else {
            $this -> error("邮箱修改失败");
        }
    }

    /**
     * [验证密码]
     * @author 郭家屯[gjt]
     */
    public function checkpwd()
    {
        $password = $this->request->param('second_pwd');
        $admin = Db::name('user')->field('second_pass')->where(['id' => session('ADMIN_ID')])->find();
        if (!xigu_compare_password($password, $admin['second_pass'])) {
            $this->error('二级密码错误');
        } else {
            $this->success('成功');
        }
    }

    /**
     * [修改平台币余额]
     * @author 郭家屯[gjt]
     */
    public function balance_edit()
    {

        $password = $this->request->param('second_pwd');
        $admin = Db::name('user')->field('second_pass')->where(['id' => session('ADMIN_ID')])->find();
        if (!xigu_compare_password($password, $admin['second_pass'])) {
            $this->error('二级密码错误');
        }
        $id = $this->request->param('id', 0, 'intval');
        $map['id'] = $id;
        $balance = $this->request->param('balance/f');
        $user = Db::table('tab_user')->where($map)->find();
        Db::startTrans();
        try{
            Db::table('tab_user')->where($map)->setfield('balance', $balance);
            $data = array(
                    'user_id' => $id,
                    'user_account' => $user['account'],
                    'promote_id' =>$user['promote_id'],
                    'promote_account' =>$user['promote_account'],
                    'game_id' => '',
                    'game_name' => '',
                    'prev_amount' => $user['balance'],
                    'amount' => $balance,
                    'type' => 0,
                    'op_id' => session('ADMIN_ID'),
                    'op_account' => get_admin_name(session('ADMIN_ID')),
                    'create_time' => time()
            );
            $record_id = Db::table('tab_user_balance_edit')->insertGetId($data);
            //异常预警提醒
            if(abs($balance-$user['balance']) >= 500){
                $warning = [
                        'type'=>5,
                        'user_id'=>$id,
                        'user_account'=>$user['account'],
                        'target'=>2,
                        'record_id'=>$record_id,
                        'unusual_money'=>abs($balance-$user['balance']),
                        'create_time'=>time()
                ];
                Db::table('tab_warning')->insert($warning);
            }
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error("失败");
        }
        $this->success("成功");
    }


    /**
     * [balance_update_lists 账户修改记录]
     * @return [type] [description]
     */
    public function balance_update_lists()
    {
        $model = new UserBalanceEditModel;
        $base = new BaseController;
        $account = $this->request->param('user_account', '');
        if ($account != '') {
            $map['user_account'] = ['like', '%' . addcslashes($account, '%') . '%'];
        }
        $promote_id = $this->request->param('promote_id');
        if($promote_id != ''){
            $map['promote_id'] = $promote_id;
        }
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time && $end_time) {
            $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['create_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['create_time'] = ['egt', strtotime($start_time)];
        }
        $type = $this->request->param('type', '');
        if ($type != '') {
            $map['type'] = $type;
        }
        $op_id = $this->request->param('admin_id', '');
        if ($op_id != '') {
            $map['op_id'] = $op_id;
        }
        $exend['order'] = 'create_time desc';
        $data = $base->data_list($model, $map, $exend);
        $page = $data->render();
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
        }
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * [登录记录表]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function denglu()
    {
        $user_id = $this->request->param('user_id', 0, 'intval');
        if (empty($user_id)) $this->error('参数错误');
        $base = new BaseController();
        $where['user_id'] = $user_id;
        $where['login_time'] = ['gt', 0];
        $where['game_id'] = ['gt', 0];
        $extend['field'] = 'game_name,server_name,game_player_name,login_time,login_ip';
        $model = new UserLoginRecordModel();
        $data = $base->data_list($model, $where, $extend);
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('data_lists', $data);
        $user = get_user_entity($user_id);
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * [实名认证]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function age()
    {
        $base = new BaseController();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            //判断一下name,如果是防沉迷,开启官方时,要判断实名认证必须开启-20210906-byh-s
            if(!$base->judgeOfficialAntiAddiction($data)){
                $this->error('开启官方防沉迷必须先开启实名认证!');
            }
            //判断一下name,如果是防沉迷,开启官方时,要判断实名认证必须开启-20210906-byh-e
            $name = $data['name'];
            $result = $base->saveConfig($name, $data);
            if ($result!==false) {
                if ($name == 'age') {
                    write_action_log("实名认证设置修改");
                } else {
                    write_action_log("防沉迷设置修改");
                }
                //清除缓存
                cmf_clear_cache();
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        }
        $name = "age,age_prevent";
        $base->BaseConfig($name);
        return $this->fetch();
    }

    /**
     * [游戏角色列表]
     * @author 郭家屯[gjt]
     */
    public function role()
    {
        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('admin/main/index'));
            };
        }
        $model = new UserPlayInfoModel();
        //添加搜索条件
        $data = $this->request->param();
        $page = intval($data['p']);
        $page = $page ? $page : 1; //默认显示第一页数据
        $row = ($this->request->param('row') ?: config('paginate.list_rows'));//每页数量
        $user_account = $data['user_account'];
        if ($user_account != '') {
            //此处更改为模糊查询-20210628-byh
            $map['user_account'] = ['LIKE','%'.$user_account.'%'];
            $where = '';
        }
        $game_id = $data['game_id'];
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        $game_id  = get_admin_view_game_ids(session('ADMIN_ID'),$game_id);
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-end

        if ($game_id) {
            $map['game_id'] = ['IN',$game_id];
        }
        $server_name = $data['server_name'];
        if ($server_name != '') {
            $map['server_name'] = ['like', '%' . $server_name . '%'];
        }
        if ($data['promote_id']!='') {
            $map['promote_id'] = $data['promote_id'];
        }
        $start_time = $data['start_time'];
        $end_time = $data['end_time'];
        if ($start_time && $end_time) {
            $map['play_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['play_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['play_time'] = ['egt', strtotime($start_time)];
        }
        $role_name = $data['role_name'];
        if ($role_name != '') {
            $map['role_name'] = ['like', '%' . $role_name . '%'];;
        }
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();

        $data = $model
            ->field('id,user_id,user_account,game_name,server_name,role_id,server_id,role_name,role_level,play_time,play_ip,update_time,combat_number,puid,promote_id,promote_account,nickname,player_reserve')
            ->where($map)
            ->where($where)
            ->order('id desc')
            ->paginate($row,false,['query' => $this->request->param()])
            ->each(function($item,$key) use ($ys_show_admin){
                if($item['puid']>0){
                    $item['user_account'] = get_user_entity2($item['puid'],false,'account')['account'].'（' . $item['nickname'] . '）';
                }else{

                    if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                        $item['user_account'] = get_ys_string($item['user_account'],$ys_show_admin['account_show_admin']);
                    }

                }

                //增加处理角色查看隐私情况
                if($ys_show_admin['role_show_admin_status'] == 1){//开启了角色查看隐私
                    $item['role_name'] = get_ys_string($item['role_name'],$ys_show_admin['role_show_admin']);
                }
                if($item['promote_id'] == 0){
                    $item['promote_account'] = '官方渠道';
                }
            });
        // 获取分页显示
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign("data_lists", $data);
        return $this->fetch();
    }

    /**
     * [游戏登录记录]
     * @author 郭家屯[gjt]
     */
    public function login_record()
    {
        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('admin/main/index'));
            };
        }
        $base = new BaseController();
        $model = new UserLoginRecordModel();
        //添加搜索条件
        $data = $this->request->param();
        $user_id = $data['user_id'];
        if ($user_id != '') {
            $map['user_id'] = ['like', '%' . $user_id . '%'];
        }
        $user_account = $data['user_account'];
        if ($user_account != '') {
            $map['user_account'] = ['like', '%' . $user_account . '%'];
        }
        $game_id = $data['game_id'];

        //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        $game_id = get_admin_view_game_ids(session('ADMIN_ID'),$game_id);
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-end

        if ($game_id) {
            $map['game_id'] = ['IN',$game_id];
        }else{
            $map['game_id'] = ['gt', 0];
        }
        $start_time = $data['start_time'];
        $end_time = $data['end_time'];
        if ($start_time && $end_time) {
            $map['login_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['login_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['login_time'] = ['egt', strtotime($start_time)];
        }
        $login_ip = $data['login_ip'];
        if ($login_ip != '') {
            $map['login_ip'] = ['like','%'.addcslashes($login_ip, '%').'%'];
        }
        //查询字段
        $exend['field'] = 'id,game_name,login_time,user_account,user_id,login_ip,puid';
        //更新时间倒序
        $exend['order'] = 'login_time desc';
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();

        $data = $base->data_list($model, $map, $exend)->each(function($item) use ($ys_show_admin){
            if($item['puid']){
                $item['user_account'] = get_user_name2($item['puid']).'('.get_user_entity2($item['user_id'],false,'account')['account'].')';
            }else{
                if($ys_show_admin['account_show_admin_status'] == 1){  //开启了账号查看隐私
                    $item['user_account'] = get_ys_string($item['user_account'],$ys_show_admin['account_show_admin']);
                }
            }
        });
        // $aa = Db::getLastSql();
        // var_dump($aa); // exit;
        // 获取分页显示
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign("data_lists", $data);
        return $this->fetch();
    }

    /**
     * [VIP设置]
     * @author 郭家屯[gjt]
     */
    public function vip_set()
    {
        $data = cmf_get_option('vip_set');
        $data['vip'] = $data['vip'] ? explode(',',$data['vip']) : [];
        $this->assign('data', $data);
        $this->assign("name", 'vip_set');
        return $this->fetch();
    }

    /**
     * [站点设置保存]
     * @author 郭家屯[gjt]
     */
    public function sitePost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            sort($data['vip']);
            $data['vip'] = array_filter($data['vip']);
            $data['vip'] = empty($data['vip'])?'':implode(',',$data['vip']);
            $set_type = $data['set_type'];
            cmf_set_option($set_type, $data);
            write_action_log("VIP设置");
            $this->success("保存成功！");
        }
    }

    /**
     * @函数或方法说明
     * @绑币余额列表
     * @author: 郭家屯
     * @since: 2019/10/11 11:37
     */
    public function user_bind_balance()
    {
        $user_id = $this->request->param('user_id', 0, 'intval');
        if (empty($user_id)) $this->error('参数错误');
        $map['user_id'] = $user_id;
        $map['bind_balance'] = ['gt',0];
        $base = new BaseController();
        $extend['field'] = 'game_name,bind_balance';
        $model = new UserPlayModel;
        $data = $base->data_list($model, $map, $extend);
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('data_lists', $data);
        $user = get_user_entity($user_id,false,'account');
        $this->assign('user', $user);
        return $this->fetch();
    }

    public function user_small_lists()
    {
        $user_id = $this->request->param('user_id', 0, 'intval');
        if (empty($user_id)) $this->error('参数错误');
        $map['puid'] = $user_id;
        $base = new BaseController();
        $extend['field'] = 'id,account,nickname,fgame_id,fgame_name,puid,cumulative,register_time,lock_status';
        $model = new UserModel();
        $data = $base->data_list($model, $map, $extend);
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('data_lists', $data);
        $user = get_user_entity2($user_id,false,'account');
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @ 定时获取玩家实名认证请求状态
     * @param game_id
     * @return \think\response\Json
     * @author: Juncl
     * @time: 2021/03/15 20:10
     */
    public function get_user_age_result()
    {
        $user_id = session('member_auth.user_id');
        if (!$user_id) {
            return json(['user_id' => 0]);
        }
        $game_id = $this -> request -> param('game_id');
        if (!$game_id) {
            return json(['game_id' => 0]);
        }
        if (get_game_age_type($game_id) != 2) {
            return json(['status' => 0]);
        }
        $model = new UserAgeRecordModel();
        $info = $model -> where('user_id', $user_id) -> field('request_status,last_request_time') -> where('game_id', $game_id) -> find();
        if(empty($info)){
            $user_data = get_user_entity($user_id,false,'idcard,real_name');
            if(!empty($user_data['idcard']) && !empty($user_data['real_name'])){
                $logic = new AntiaddictionLogic($game_id);
                $res = $logic->checkUser($user_data['real_name'],$user_data['idcard'],$user_id,$game_id);
                return json($res);
            }
        }else {
            if ($info['request_status'] != 2) {
                return json(['request_status' => 0]);
            }
            if ($info['last_request_time'] > 0 && (time() - $info['last_request_time'] > 3600)) {
                $logic = new AntiaddictionLogic($game_id);
                $result = $logic->queryUser($user_id, $game_id);
                return json($result);
            } else {
                return json(['last_request_time' => 0]);
            }
        }
    }

    /**
     * @投诉反馈
     *
     * @author: zsl
     * @since: 2021/4/21 15:32
     */
    public function feedback()
    {

        $param = $this -> request -> param();
        $map = [];
        if (!empty($param['user_account'])) {
            $map['user_account'] = ['like', '%' . $param['user_account'] . '%'];
        }
        if (!empty($param['game_name'])) {
            $map['game_name'] = ['like', '%' . $param['game_name'] . '%'];
        }
        if (!empty($param['report_type'])) {
            $map['report_type'] = ['like', '%' . $param['report_type'] . '%'];
        }
        $base = new BaseController();
        $extend['field'] = 'id,user_id,user_account,game_id,game_name,qq,tel,report_type,remark,images,admin_remark,mobile_type,status,create_time,update_time';
        $model = new UserFeedbackModel();
        $data = $base -> data_list($model, $map, $extend);
        $page = $data -> render();
        $this -> assign('page', $page);
        $data = $data -> toarray();
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();

        foreach ($data['data'] as &$v) {
            $v['report_type'] = implode(',', json_decode($v['report_type'], true));
            $v['images'] = json_encode(array_map('cmf_get_image_url',json_decode($v['images'], true)));

            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $v['user_account'] = get_ys_string($v['user_account'],$ys_show_admin['account_show_admin']);
            }
        }
        $this -> assign('data_lists', $data['data']);
        return $this -> fetch();
    }


    /**
     * @输入投诉反馈备注
     *
     * @author: zsl
     * @since: 2021/4/21 17:03
     */
    public function feedbackRemark()
    {
        if ($this -> request -> isPost()) {
            $param = $this -> request -> param();
            $mUserFeedback = new UserFeedbackModel();
            $res = $mUserFeedback -> where(['id' => $param['id']]) -> setField('admin_remark', $param['admin_remark']);
            if (false === $res) {
                $this -> error('修改失败');
            }
            $this -> success('修改成功');
        }
        $this -> error('发生错误');
    }


    /**
     * @用户注销列表
     *
     * @author: zsl
     * @since: 2021/5/14 9:23
     */
    public function unsubscribe()
    {
        $user = new UserModel();
        $model = new UserUnsubscribeModel();
        $base = new BaseController;
        $map = [];
        $start_time = $this -> request -> param('start_time', '');
        $end_time = $this -> request -> param('end_time', '');
        if ($start_time && $end_time) {
            $map['unsubscribe_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['unsubscribe_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['unsubscribe_time'] = ['egt', strtotime($start_time)];
        }
        $account = $this -> request -> param('user_account', '');
        if ($account != '') {
            $map['user_account'] = ['like', '%' . addcslashes($account, '%') . '%'];
        }
        $map['status'] = 2; // 已注销
        //查询字段
        $exend['field'] = 'id,user_id,user_account,user_account_alias,unsubscribe_time';
        //更新时间倒序
        $exend['order'] = 'id desc';
        $data = $base -> data_list($model, $map, $exend) -> each(function($item) use ($user){
            $item['register_time'] = $user -> where(['id' => $item['user_id']]) -> value('register_time');
        });
        // 获取分页显示
        $page = $data -> render();
        $this -> assign('page', $page);

        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach ($data as $k5 => $v5) {
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $new_account = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
                $data[$k5]['user_account_alias'] = str_replace($data[$k5]['user_account'], $new_account, $data[$k5]['user_account_alias']);
                $data[$k5]['user_account'] = $new_account;
            }
        }

        $this -> assign("data_lists", $data);
        return $this -> fetch();
    }
    /**
     * 用户管理->安全中心
     * created by wjd, 2021-6-17 16:14:28
    */
    public function safecenter(Request $request)
    {
        if($request->isPost()){
            // 进行修改
            $param = $request->param();
            $adminConfig = [
                'account_show_admin'        => $param['account_show_admin'] ?? 0,
                'account_show_admin_status' => $param['account_show_admin_status'] ?? 0,//新增账号隐私开关
                'phone_show_admin'          => $param['phone_show_admin'] ?? 0,
                'phone_show_admin_status'   => $param['phone_show_admin_status'] ?? 0,//新增手机隐私开关
                'role_show_admin'           => $param['role_show_admin'] ?? 0,//新增角色显示类型
                'role_show_admin_status'    => $param['role_show_admin_status'] ?? 0,//新增角色隐私开关
            ];
            $admin_ids = empty($param['admin_id']) ? '' : json_encode($param['admin_id']);

            $adminData = [
                'config'=>json_encode($adminConfig, JSON_UNESCAPED_UNICODE),
                'ids'=>$admin_ids,
            ];

            $promoteConfig = [
                'account_show_promote'          =>  $param['account_show_promote'] ?? 0,
                'account_show_promote_status'   =>  $param['account_show_promote_status'] ?? 0,//新增账号隐私开关
                'phone_show_promote'            =>  $param['phone_show_promote'] ?? 0,
                'phone_show_promote_status'     =>  $param['phone_show_promote_status'] ?? 0,//新增手机隐私开关
                'role_show_promote'             =>  $param['role_show_promote'] ?? 0,//新增角色显示类型
                'role_show_promote_status'      =>  $param['role_show_promote_status'] ?? 0,//新增角色隐私开关
            ];
            $promote_ids = empty($param['promote_id']) ? '' : json_encode($param['promote_id']);

            $promoteData = [
                'config'=>json_encode($promoteConfig, JSON_UNESCAPED_UNICODE),
                'ids'=>$promote_ids,
            ];
            $promote_ids = $param['promote_id'];
            $update1= $adminUpdate = Db::table('tab_safe_center')->where(['id'=>1])->update($adminData);
            $update2 = $promoteUpdate = Db::table('tab_safe_center')->where(['id'=>2])->update($promoteData);
            // 清空缓存
            $tag = 'promote_or_admin_two_value';
            $cache_ = new Cache;
            $cache_->clear($tag);
            return $this->success('操作成功!');

        }
        // 展示
        $safecenterInfo_admin = Db::table('tab_safe_center')->where(['id'=>1])->find();
        $safecenterInfo_promote = Db::table('tab_safe_center')->where(['id'=>2])->find();
        $config_admin = $safecenterInfo_admin['config'];
        $config_promote = $safecenterInfo_promote['config'];
        $config_admin_arr = json_decode($config_admin, true);
        $config_promote_arr = json_decode($config_promote, true);
        $returnConfig = [
            'account_show_admin'=>$config_admin_arr['account_show_admin'] ?? 0,
            'account_show_admin_status'=>$config_admin_arr['account_show_admin_status'] ?? 0,
            'phone_show_admin'=>$config_admin_arr['phone_show_admin'] ?? 0,
            'phone_show_admin_status'=>$config_admin_arr['phone_show_admin_status'] ?? 0,
            'role_show_admin'=>$config_admin_arr['role_show_admin'] ?? 0,
            'role_show_admin_status'=>$config_admin_arr['role_show_admin_status'] ?? 0,

            'account_show_promote'=>$config_promote_arr['account_show_promote'] ?? 0,
            'account_show_promote_status'=>$config_promote_arr['account_show_promote_status'] ?? 0,
            'phone_show_promote'=>$config_promote_arr['phone_show_promote'] ?? 0,
            'phone_show_promote_status'=>$config_promote_arr['phone_show_promote_status'] ?? 0,
            'role_show_promote'=>$config_promote_arr['role_show_promote'] ?? 0,
            'role_show_promote_status'=>$config_promote_arr['role_show_promote_status'] ?? 0,
        ];
        $admin_ids = $safecenterInfo_admin;
        if(!empty($admin_ids['ids'])){
            $admin_ids_arr = json_decode($admin_ids['ids'], true);
        }else{
            $admin_ids_arr = [];
        }
        $promote_ids = $safecenterInfo_promote;
        if(!empty($promote_ids['ids'])){
            $promote_ids_arr = json_decode($promote_ids['ids'], true);
        }else{
            $promote_ids_arr = [];
        }

        $this->assign('admin_id', $admin_ids_arr);
        $this->assign('promote_id', $promote_ids_arr);
        $this->assign('data',  $returnConfig);
        return $this->fetch();
    }
    /**
     * 用户管理->安全中心->单端登录
     * created by wjd 2021-6-17 16:19:38
     */
    public function single_end_login(Request $request)
    {
        if($request->isPost()){
            // 进行修改
            $param = $request->param();
            $adminConfig = [
                'single_end_login_admin'=>$param['single_end_login_admin'] ?? 0,
            ];
            $admin_ids = empty($param['admin_id']) ? '' : json_encode($param['admin_id']);

            $adminData = [
                'config'=>json_encode($adminConfig, JSON_UNESCAPED_UNICODE),
                'ids'=>$admin_ids,
            ];

            $promoteConfig = [
                'single_end_login_promote'=>$param['single_end_login_promote'] ?? 0,
            ];
            $promote_ids = empty($param['promote_id']) ? '' : json_encode($param['promote_id']);

            $promoteData = [
                'config'=>json_encode($promoteConfig, JSON_UNESCAPED_UNICODE),
                'ids'=>$promote_ids,
            ];
            // var_dump($adminData);exit;
            $promote_ids = $param['promote_id'];
            $update1= $adminUpdate = Db::table('tab_safe_center')->where(['id'=>3])->update($adminData);
            $update2 = $promoteUpdate = Db::table('tab_safe_center')->where(['id'=>4])->update($promoteData);
            return $this->success('操作成功!');

        }
        // 展示
        $safecenterInfo_admin = Db::table('tab_safe_center')->where(['id'=>3])->find();
        $safecenterInfo_promote = Db::table('tab_safe_center')->where(['id'=>4])->find();
        $config_admin = $safecenterInfo_admin['config'];
        $config_promote = $safecenterInfo_promote['config'];
        $config_admin_arr = json_decode($config_admin, true);
        $config_promote_arr = json_decode($config_promote, true);
        $returnConfig = [
            'single_end_login_admin'=>$config_admin_arr['single_end_login_admin'] ?? 0,
            'single_end_login_promote'=>$config_promote_arr['single_end_login_promote'] ?? 0,
        ];
        $admin_ids = $safecenterInfo_admin;
        if(!empty($admin_ids['ids'])){
            $admin_ids_arr = json_decode($admin_ids['ids'], true);
        }else{
            $admin_ids_arr = [];
        }
        $promote_ids = $safecenterInfo_promote;
        if(!empty($promote_ids['ids'])){
            $promote_ids_arr = json_decode($promote_ids['ids'], true);
        }else{
            $promote_ids_arr = [];
        }

        $this->assign('admin_id', $admin_ids_arr);
        $this->assign('promote_id', $promote_ids_arr);
        $this->assign('data',  $returnConfig);
        return $this->fetch();
        return $this->fetch();
    }


    /**
     * @批量创建用户账号
     *
     * @author: zsl
     * @since: 2021/7/9 21:41
     */
    public function batchCreate()
    {
        if ($this -> request -> isPost()) {
            $param = $this -> request -> param();
            $lUser = new UserLogic();
            $result = $lUser -> batchCreate($param);
            if ($result['code'] == 0) {
                $this -> error($result['msg']);
            }
            $this -> success($result['msg'], url('member/user/userinfo'));
        }
        return $this -> fetch();
    }


    /**
     * @获取推广员已申请游戏
     *
     * @author: zsl
     * @since: 2021/7/13 17:21
     */
    public function promoteGame()
    {
        $param = $this -> request -> param();
        $model = new PromoteapplyModel;
        $base = new BaseHomeController;
        $data = $this -> request -> param();
        $map['status'] = $data['type'] ? 0 : 1;
        $promote = get_promote_entity($param['promote_id']);
        if ($promote['parent_id'] == 0) {
            $promote_level = 1;
        } else {
            $promote_level = $promote['promote_level'];
        }
        //渠道禁止申请游戏
        if ($promote_level == 1) {
            if ($promote['game_ids']) {
                $map['game_id'] = ['notin', explode(',', $promote['game_ids'])];
            }
            $flag = 1;
        } else {
            $promote_id = $promote['parent_id'];
            $ids = get_promote_apply_game_id($promote_id);//可申请游戏
            //禁止申请游戏
            if ($promote['game_ids']) {
                $top_ids = explode(',', $promote['game_ids']);
                $ids = array_diff($ids, $top_ids);
            }
            $map['game_id'] = ['in', $ids];
            if ($ids) {
                $map['game_id'] = ['in', $ids];
            } else {
                $map['game_id'] = - 1;
            }
            $flag = 2;
        }
        $map['g.game_status'] = 1;
        $map['g.sdk_area'] = 0; // 不显示海外游戏
        $map['promote_id'] = $promote['id'];
        $map['g.down_port'] = ['in', [1, 3]];
        $map['g.third_party_url'] = '';
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        // 渠道独占 ----------------------------- START
        $current_promote_id = $promote['promote_id'];
        $forbid_game_ids = [];
        $allow_game_ids = [];
        $tmp_game_info = Db ::table('tab_game') -> select() -> toArray();
        if (!empty($tmp_game_info)) {
            foreach ($tmp_game_info as $k => $v) {
                if ($v['only_for_promote'] == 1) {  // 0 通用, 1 渠道独占
                    $only_for_promote_ids = explode(',', $v['promote_ids2']);
                    if (!in_array($current_promote_id, $only_for_promote_ids)) {
                        $forbid_game_ids[] = $v['id'];
                    } else {
                        $allow_game_ids[] = $v['id'];
                    }
                }
            }
        }
        if (empty($ids)) {
            $ids = [];
        }
        if ($flag == 1) {
            $ids = array_merge($ids, $forbid_game_ids);
            $map['game_id'] = ['notin', $ids];
        }
        if ($flag == 2 && !empty($allow_game_ids)) {
            if ($map['id'] != - 1) {
                $ids = array_intersect($ids, $allow_game_ids);
                $map['game_id'] = ['in', $ids];
            }
        }
        // 渠道独占 ----------------------------- END
        $exend['field'] = 'game_id as id,game_name,icon';
        $exend['order'] = 'tab_promote_apply.id desc';
        $exend['join1'][] = ['tab_game' => 'g'];
        $exend['join1'][] = 'g.id = tab_promote_apply.game_id';
        $data = $base -> data_list_join($model, $map, $exend) -> each(function($item, $key){});

        $this -> success('获取成功', '', $data);
    }

    /***
     * 玩家阶段管理
     * created by wjd 2021-7-26 15:31:47
     */
    public function userstage(Request $request)
    {
        $user = new UserModel;
        $base = new BaseController;
        $map['user_stage_id'] = ['>', 0]; // 必须条件
        $map['puid'] = 0;//只显示大号
        $map['is_unsubscribe'] = 0;

        $user_id = $this->request->param('user_id', 0, 'intval');
        if ($user_id > 0) {
            $map['tab_user.id'] = $user_id;
        }
        $account = $this->request->param('account', '');
        if ($account != '') {
            $map['account'] = ['like', '%' . $account . '%'];
        }
        // user_stage_id
        $user_stage_id = $this->request->param('user_stage_id', 0);
        if(!empty($user_stage_id) && $user_stage_id != 0){
            $map['user_stage_id'] = $user_stage_id;
        }
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        $view_game_ids = get_admin_view_game_ids(session('ADMIN_ID'));
        if(!empty($view_game_ids)){
            $map['tab_user.fgame_id'] = ['IN',$view_game_ids];
        }
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-end
        $promote_id = $this->request->param('promote_id', '');
        if ($promote_id != '') {
            $map['promote_id'] = $promote_id;
        }
        $user_score_1 = $this->request->param('user_score_1', ''); // 最低评分
        $user_score_2 = $this->request->param('user_score_2', ''); // 最高评分
        if(!empty($user_score_1)) { $map['user_score'] = ['>=', $user_score_1]; }
        if(!empty($user_score_2)) { $map['user_score'] = ['<=', $user_score_2]; }
        if(!empty($user_score_1) && !empty($user_score_2)){
            $map['user_score'] = ['between', [$user_score_1, $user_score_2]];
        }

        $exend['order'] = 'user_score desc,id desc';
        $exend['field'] = 'tab_user.id,account,promote_id,promote_account,lock_status,head_img,register_time,user_score,user_stage_id,user_stage_remark,us.name as user_stage_name';
        // $exend['group'] = 'id'; // cmf_get_image_url($user['head_img']);
        $exend['join1'][] = ['tab_user_stage'=>'us'];
        $exend['join1'][] = ['tab_user.user_stage_id=us.id'];
        // $exend['join1'][] = ['tab_user_stage'=>'us'];
        // ,'tab_user.user_stage_id=us.id','left'];
        $data = $base->data_list_join($user, $map, $exend)->each(function ($item, $key) {
            if(!empty($item['head_img'])){
                $item['head_img'] = cmf_get_image_url($item['head_img']);
            }else{
                $item['head_img'] = '';
            }
            return $item;
        });
        // 获取分页显示
        $page = $data->render();
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['account'] = get_ys_string($v5['account'],$ys_show_admin['account_show_admin']);
            }
        }
        // 未设置玩家玩家统计总数
        $unsetUserCount = Db::table('tab_user')->where(['user_stage_id'=>0, 'is_unsubscribe'=>0, 'puid'=>0])->count();
        $this->assign("unset_user_count", $unsetUserCount);
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        return $this->fetch();
    }
    /**
     * 添加玩家至指定的阶段
     * created by wjd 2021-7-26 15:31:47
    */
    public function addUserStage(Request $request)
    {
        // var_dump($request->param()); //exit;
        if($request->isPost()){
            $param = $request->param();
            $userIdsArr = $param['user_ids'];
            $userStageId = $param['user_stage_id'];
            if(empty($userIdsArr) || empty($userStageId)){
                return json(['code'=>-1,'msg'=>'数据选择不完成整!','data'=>[]]);
            }
            // 添加至指定阶段
            $updateRes = Db::table('tab_user')
                ->where(['id'=>['in', $userIdsArr]])
                ->update(['user_stage_id'=>$userStageId]);

            if($updateRes){
                return json(['code'=>1,'msg'=>'操作成功!','data'=>[]]);
            }else{
                return json(['code'=>-1,'msg'=>'系统繁忙,请稍后再试!','data'=>[]]);
            }

        }
        $user = new UserModel;
        $base = new BaseController;
        $map['user_stage_id'] = 0; // 必须条件
        $map['puid'] = 0;//只显示大号
        $map['is_unsubscribe'] = 0;

        $account = $this->request->param('account', '');
        if ($account != '') {
            $map['account'] = ['like', '%' . $account . '%'];
        }
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        $view_game_ids = get_admin_view_game_ids(session('ADMIN_ID'));
        if(!empty($view_game_ids)){
            $map['tab_user.fgame_id'] = ['IN',$view_game_ids];
        }
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-end
        $promote_id = $this->request->param('promote_id', '');
        if ($promote_id != '') {
            $map['promote_id'] = $promote_id;
        }

        $exend['order'] = 'user_score desc,id desc';
        $exend['field'] = 'tab_user.id,account,promote_id,promote_account,lock_status,head_img,cumulative,register_time,user_score,user_stage_id,user_stage_remark';
        // $exend['group'] = 'id'; // cmf_get_image_url($user['head_img']);
        // $exend['join1'][] = ['tab_user_stage'=>'us'];
        // $exend['join1'][] = ['tab_user.user_stage_id=us.id'];
        // $exend['join1'][] = ['tab_user_stage'=>'us'];
        // ,'tab_user.user_stage_id=us.id','left'];
        // var_dump($map);
        $data = $base->data_list_join($user, $map, $exend)->each(function ($item, $key) {
            if(!empty($item['head_img'])){
                $item['head_img'] = cmf_get_image_url($item['head_img']);
            }else{
                $item['head_img'] = '';
            }
            return $item;
        });
        // 获取分页显示
        $page = $data->render();
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['account'] = get_ys_string($v5['account'],$ys_show_admin['account_show_admin']);
            }
        }
        // 未设置玩家玩家统计总数
        $unsetUserCount = Db::table('tab_user')->where(['user_stage_id'=> 0, 'puid'=>0, 'is_unsubscribe'=>0])->count();
        $this->assign("unset_user_count", $unsetUserCount);
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        return $this->fetch();
    }
    /**
     * 阶段展示列表
     * created by wjd 2021-7-26 15:30:16
    */
    public function setUserStage(Request $request)
    {
        if($request->isPost()){

        }

        $userStage = Db::table('tab_user_stage')->order('only_for_sort desc')->select()->toArray();
        $count = count($userStage);
        $returnData = [];
        foreach($userStage as $k=>$v){
            $v['upper'] = 1;
            $v['down'] = 1;
            if($k == 0){
                $v['upper'] = 0;
                $v['down'] = 1;
            }
            if($k == $count - 1){
                $v['upper'] = 1;
                $v['down'] = 0;
            }
            if($count <= 1){
                $v['upper'] = 0;
                $v['down'] = 0;
            }
            $follow_remind_arr = json_decode($v['follow_remind'], true);
            $v['follow_remind'] = $follow_remind_arr;

            $returnData[] = $v;
        }
        // 处理显示流程图的数据
        if($count > 1){
            $userStageReverse = array_reverse($userStage);
            $lastStage = $userStageReverse[$count-1];
            unset($userStageReverse[$count-1]);
            $this->assign("userStageReverse", $userStageReverse);
            $this->assign("numbers2", $count-2);
            $this->assign("lastStage", $lastStage);
        }else{
            $userStageReverse = array_reverse($userStage);
            $lastStage = $userStageReverse[$count-1];
            unset($userStageReverse[$count-1]);
            $this->assign("userStageReverse", $userStageReverse);
            $this->assign("numbers2", $count-2);
            $this->assign("lastStage", $lastStage);
        }


        // var_dump($userStageReverse);
        // var_dump($lastStage);
        // exit;
        // var_dump($returnData);exit;
        $this->assign("data_lists", $returnData);

        return $this->fetch();

    }

    /**
     * 设置设置阶段 添加阶段
     * created by wjd 2021-7-26 15:30:16
    */
    public function addStage(Request $request)
    {
        $param = $request->param();
        $name = $param['name'] ?? ''; // 阶段名称
        if(empty($name)){
            return json(['code'=>-1, 'msg'=>'阶段名称不能为空!', 'data'=>[]]);
        }
        $description = $param['description'] ?? '';  // 阶段描述
        $user_score = $param['user_score'] ?? 0;  // 阶段描述
        if($user_score <=0){return json(['code'=>-1, 'msg'=>'玩家评分不能为0', 'data'=>[]]);}
        if($user_score >100){return json(['code'=>-1, 'msg'=>'玩家评分不能大于10', 'data'=>[]]);}

        $other_setting_arr = [
            'total_consume'=>$param['total_consume'] ?? 0.00,  // 累计消费
            'consume_day'=>$param['consume_day'] ?? 0,  //  消费频次 - 近多少天
            'consume_times'=>$param['consume_times'] ?? 0,  //  消费频次 - 消费多少次
            'active_day'=>$param['active_day'] ?? 0,  //活跃情况 - 近多少天
            'active_times'=>$param['active_times'] ?? 0,  // 活跃情况 - 活跃次数
            'upgrade_type'=>$param['upgrade_type'] ?? 0,  // 进阶方式 1:自动进入 2:手动添加
            'user_score'=>$user_score,  // 进阶方式 1:自动进入 2:手动添加
        ];
        $other_setting_str = json_encode($other_setting_arr, JSON_UNESCAPED_UNICODE);
        $d_time_int = time();
        // 处理循序
        $only_for_sort = 1;
        $tmp_user_stage_info = Db::table('tab_user_stage')->where(['id'=>['>', 0]])->order('only_for_sort desc')->find();
        if(!empty($tmp_user_stage_info)){
            $only_for_sort = $tmp_user_stage_info['only_for_sort'] + 1;
        }
        $follow_remind_arr = [
            'follow_remind_switch'=> 0,  // 跟进提醒 开关 0 关闭 1 开启
            'remind_type'=>1,  //  提醒方式 1:手机短信 2:邮箱短信
            'remind_admin_ids'=>'',  //  提醒对象
            'not_login_days'=>7,  // 登录停留 - 未登录超过天数
            'not_login_remind_time' => '', // 登录停留 - 次日几点提醒
            'not_recharge_days'=>7,  // 付费停留 - 未付费超过天数
            'not_recharge_remind_time'=>'',  // 付费停留 - 次日几点提醒
        ];

        $saveData = [
            'name'=>$name,
            'description'=>$description,
            'other_setting'=>$other_setting_str,
            'follow_remind'=>json_encode($follow_remind_arr),
            'only_for_sort'=>$only_for_sort,
            'create_time'=>$d_time_int,
            'update_time'=>$d_time_int
        ];

        $saveRes = Db::table('tab_user_stage')->insert($saveData);
        if($saveRes){
            return json(['code'=>1, 'msg'=>'添加成功', 'data'=>[]]);
        }else{
            return json(['code'=>-1, 'msg'=>'系统繁忙,请稍后再试~~', 'data'=>[]]);
        }
    }
    /**
     * 设置设置阶段 编辑 阶段
     * created by wjd 2021-7-26 15:30:16
    */
    public function editStage(Request $request)
    {
        $param = $request->param();
        $id = $param['id'] ?? 0;
        if($id <= 0){
            return json(['code'=>-1, 'msg'=>'请传入合适的id值', 'data'=>[]]);
        }
        // 保存
        if($request->isPost()){
            $name = $param['name'] ?? ''; // 阶段名称
            $description = $param['description'] ?? '';  // 阶段描述

            $user_score = $param['user_score'] ?? 0;  // 阶段描述
            if($user_score <=0){return json(['code'=>-1, 'msg'=>'玩家评分不能为0', 'data'=>[]]);}
            if($user_score >100){return json(['code'=>-1, 'msg'=>'玩家评分不能大于10', 'data'=>[]]);}

            $other_setting_arr = [
                'total_consume'=>$param['total_consume'] ?? 0.00,  // 累计消费
                'consume_day'=>$param['consume_day'] ?? 0,  //  消费频次 - 近多少天
                'consume_times'=>$param['consume_times'] ?? 0,  //  消费频次 - 消费多少次
                'active_day'=>$param['active_day'] ?? 0,  //活跃情况 - 近多少天
                'active_times'=>$param['active_times'] ?? 0,  // 活跃情况 - 活跃次数
                'upgrade_type'=>$param['upgrade_type'] ?? 0,  // 进阶方式 1:自动进入 2:手动添加
                'user_score'=>$user_score,
            ];
            $other_setting_str = json_encode($other_setting_arr, JSON_UNESCAPED_UNICODE);
            $d_time_int = time();

            $saveData = [
                'name'=>$name,
                'description'=>$description,
                'other_setting'=>$other_setting_str,
                'create_time'=>$d_time_int,
                'update_time'=>$d_time_int
            ];
            $updateRes = Db::table('tab_user_stage')->where(['id'=>$id])->update($saveData);
            if($updateRes){
                return json(['code'=>1, 'msg'=>'编辑成功', 'data'=>[]]);
            }else{
                return json(['code'=>-1, 'msg'=>'系统繁忙,请稍后再试~~', 'data'=>[]]);
            }
        }
        // 展示
        $stageInfo = Db::table('tab_user_stage')->where(['id'=>$id])->find();
        $other_setting_arr = json_decode(($stageInfo['other_setting'] ?? ''), true);

        $returnData = [
            'name'=>$stageInfo['name'],
            'description'=>$stageInfo['description'],
            'total_consume'=>$other_setting_arr['total_consume'] ?? 0.00,  // 累计消费
            'consume_day'=>$other_setting_arr['consume_day'] ?? 0,  //  消费频次 - 近多少天
            'consume_times'=>$other_setting_arr['consume_times'] ?? 0,  //  消费频次 - 消费多少次
            'active_day'=>$other_setting_arr['active_day'] ?? 0,  //活跃情况 - 近多少天
            'active_times'=>$other_setting_arr['active_times'] ?? 0,  // 活跃情况 - 活跃次数
            'upgrade_type'=>$other_setting_arr['upgrade_type'] ?? 0,  // 进阶方式 1:自动进入 2:手动添加
            'user_score'=>$other_setting_arr['user_score'],
            'create_time'=>$stageInfo['create_time'],
            'update_time'=>$stageInfo['update_time'],
        ];

        return json(['code'=>1, 'msg'=>'获取成功', 'data'=>$returnData]);

    }

    /**
     * 编辑跟进提醒
     * created by wjd 2021-7-26 15:30:16
    */
    public function editFollowRemind(Request $request)
    {
        $param = $request->param();
        $id = $param['id'] ?? 0;
        if ($id <= 0) {
            return json(['code'=>-1, 'msg'=>'请传入合适的id值', 'data'=>[]]);
        }
        // 保存
        if ($request->isPost()) {
            // exit;
            $admin_ids = $param['admin_id'];
            // $remind_admin_ids = json_encode($admin_ids);
            $follow_remind_arr = [
                'follow_remind_switch'=>$param['follow_remind_switch'] ?? 0,  // 跟进提醒 开关 0 关闭 1 开启
                'remind_type'=>$param['remind_type'] ?? 1,  //  提醒方式 1:手机短信 2:邮箱短信
                'remind_admin_ids'=>$admin_ids,  //  提醒对象
                'not_login_days'=>$param['not_login_days'] ?? 0,  // 登录停留 - 未登录超过天数
                'not_login_remind_time' => $param['not_login_remind_time'] ?? 0, // 登录停留 - 次日几点提醒
                'not_recharge_days'=>$param['not_recharge_days'] ?? 0,  // 付费停留 - 未付费超过天数
                'not_recharge_remind_time'=>$param['not_recharge_remind_time'] ?? 0,  // 付费停留 - 次日几点提醒
            ];
            $follow_remind_str = json_encode($follow_remind_arr, JSON_UNESCAPED_UNICODE);
            $d_time_int = time();
            $updateRes = Db::table('tab_user_stage')->where(['id'=>$id])->update(['follow_remind'=>$follow_remind_str, 'update_time'=>$d_time_int]);
            if ($updateRes) {
                // $this->success('保存成功!', url('user/setUserStage'));
                // $this->success('保存成功!');
                return json(['code'=>1, 'msg'=>'编辑成功', 'data'=>[]]);
            } else {
                // $this->error('系统繁忙,请稍后再试!', url('user/setUserStage'));
                // $this->error('系统繁忙,请稍后再试!');
                return json(['code'=>-1, 'msg'=>'系统繁忙,请稍后再试~~', 'data'=>[]]);
            }
        }
        // 展示


        $stageInfo = Db::table('tab_user_stage')->where(['id'=>$id])->find();
        $follow_remind_arr = json_decode(($stageInfo['follow_remind'] ?? ''), true);

        // $follow_remind_arr = [
        //     'follow_remind_switch'=>$follow_remind_str['follow_remind_switch'] ?? 0.00,  // 跟进提醒 开关
        //     'remind_type'=>$follow_remind_str['remind_type'] ?? 1,  //  提醒方式 1:手机短信 2:邮箱短信
        //     'remind_admin_ids'=>json_decode($follow_remind_str[]),  //  提醒对象
        //     'not_login_days'=>$follow_remind_str['not_login_days'] ?? 0,  // 登录停留 - 未登录超过天数
        //     'not_login_remind_time' => $follow_remind_str['not_login_remind_time'] ?? 0, // 登录停留 - 次日几点提醒
        //     'not_recharge_days'=>$follow_remind_str['not_recharge_days'] ?? 0,  // 付费停留 - 未付费超过天数
        //     'not_recharge_remind_time'=>$follow_remind_str['not_recharge_remind_time'] ?? 0,  // 付费停留 - 次日几点提醒
        // ];

        $returnData = [
            'id'=>$id,
            'follow_remind_switch'=>$follow_remind_arr['follow_remind_switch'] ?? 0,  // 跟进提醒 开关
            'remind_type'=>$follow_remind_arr['remind_type'] ?? 1,  //  提醒方式 1:手机短信 2:邮箱短信
            'remind_admin_ids'=>$follow_remind_arr['remind_admin_ids'],  //  提醒对象
            'not_login_days'=>$follow_remind_arr['not_login_days'] ?? 0,  // 登录停留 - 未登录超过天数
            'not_login_remind_time' => $follow_remind_arr['not_login_remind_time'] ?? 0, // 登录停留 - 次日几点提醒
            'not_recharge_days'=>$follow_remind_arr['not_recharge_days'] ?? 0,  // 付费停留 - 未付费超过天数
            'not_recharge_remind_time'=>$follow_remind_arr['not_recharge_remind_time'] ?? 0,  // 付费停留 - 次日几点提醒
        ];
        $this->assign('data', $returnData);
        return $this->fetch();

        return json(['code'=>1, 'msg'=>'获取成功', 'data'=>$returnData]);
    }

    /**
     * 更改用户评分
     * created by wjd 2021-7-26 17:32:51
    */
    public function changeUserScore(Request $request)
    {
        $param = $request->param();
        $user_id = $param['user_id'] ?? 0;
        $score = (float) ($param['score'] ?? 0);
        $score = sprintf("%.1f",$score);
        if($user_id > 0){
            $updateRes = Db::table('tab_user')->where(['id'=>$user_id])->update(['user_score'=>$score]);
            if($updateRes){
                return json(['code'=>1,'msg'=>'修改评分成功!','data'=>[]]);
            }
        }

        // return json(['code'=>-1,'msg'=>'服务器繁忙,请稍后再试!','data'=>[]]);
        return json(['code'=>-1,'msg'=>'修改评分成功!','data'=>[]]);

    }

    /**
     * 更改用户阶段的备注
     * created by wjd 2021-7-26 17:32:51
    */
    public function changeUserStageRemark(Request $request)
    {
        $param = $request->param();
        $user_id = $param['user_id'] ?? 0;
        $remark = $param['user_stage_remark'] ?? '';

        if($user_id > 0){
            $updateRes = Db::table('tab_user')->where(['id'=>$user_id])->update(['user_stage_remark'=>$remark]);
            if($updateRes){
                return json(['code'=>1,'msg'=>'修改备注成功!','data'=>[]]);
            }
        }
        // return json(['code'=>-1,'msg'=>'服务器繁忙,请稍后再试!','data'=>[]]);
        return json(['code'=>-1,'msg'=>'修改备注成功!','data'=>[]]);

    }

    /**
     * 更改阶段顺序(根据箭头)
     * created by wjd 2021-7-26 17:32:51  (注: 可以整合一下)
    */
    public function changeStageOrder(Request $request)
    {
        $param = $request->param();
        $id = $param['id'] ?? 0;
        if($id <= 0){
            return json(['code'=>-1, 'msg'=>'请传入合适的id值', 'data'=>[]]);
        }

        $type = $param['type']; // upper 往上调, down 往下调
        $tmp_key = 0;
        $user_stage_info = Db::table('tab_user_stage')->where(['id'=>['>', 0]])->order('only_for_sort asc')->select()->toArray();
        if($type == "upper"){
            foreach($user_stage_info as $key=>$val){
                if($val['id'] == $id){
                    $tmp_key = $key;
                }
            }
            $need_sort1_id = $user_stage_info[$tmp_key + 1]['id'];
            $need_sort2_id = $user_stage_info[$tmp_key]['id'];

            $need_sort1_info = Db::table('tab_user_stage')->where(['id'=>$need_sort1_id])->field('id,only_for_sort')->find();
            $need_sort2_info = Db::table('tab_user_stage')->where(['id'=>$need_sort2_id])->field('id,only_for_sort')->find();

            if(!empty($need_sort1_info) && !empty($need_sort2_info)){
                Db::startTrans();
                try{
                    Db::table('tab_user_stage')->where(['id'=>$need_sort1_id])->update(['only_for_sort'=>$need_sort2_info['only_for_sort']]);
                    Db::table('tab_user_stage')->where(['id'=>$need_sort2_id])->update(['only_for_sort'=>$need_sort1_info['only_for_sort']]);
                    // 提交事务
                    Db::commit();
                    return json(['code'=>1, 'msg'=>'顺序修改成功!', 'data'=>[]]);

                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }

            }
            return json(['code'=>-1, 'msg'=>'系统繁忙,请稍后再试!', 'data'=>[]]);
        }
        if($type == "down"){
            foreach($user_stage_info as $key=>$val){
                if($val['id'] == $id){
                    $tmp_key = $key;
                }
            }
            $need_sort1_id = $user_stage_info[$tmp_key]['id'];
            $need_sort2_id = $user_stage_info[$tmp_key - 1]['id'];
            $need_sort1_info = Db::table('tab_user_stage')->where(['id'=>$need_sort1_id])->field('id,only_for_sort')->find();
            $need_sort2_info = Db::table('tab_user_stage')->where(['id'=>$need_sort2_id])->field('id,only_for_sort')->find();
            if(!empty($need_sort1_info) && !empty($need_sort2_info)){
                Db::startTrans();
                try{
                    Db::table('tab_user_stage')->where(['id'=>$need_sort1_id])->update(['only_for_sort'=>$need_sort2_info['only_for_sort']]);
                    Db::table('tab_user_stage')->where(['id'=>$need_sort2_id])->update(['only_for_sort'=>$need_sort1_info['only_for_sort']]);
                    // 提交事务
                    Db::commit();
                    return json(['code'=>1, 'msg'=>'顺序修改成功!', 'data'=>[]]);

                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }

            }
            return json(['code'=>-1, 'msg'=>'系统繁忙,请稍后再试!', 'data'=>[]]);
        }
        return json(['code'=>-1, 'msg'=>'请传入正确的类型!', 'data'=>[]]);
    }
    /**
     * 删除阶段 需返回的提示
     * created by wjd 2021-7-26 17:32:51
    */
    public function deleteStage(Request $request)
    {
        $param = $request->param();
        $id = $param['id'] ?? 0;
        if($id <= 0){
            return json(['code'=>-1, 'msg'=>'请传入合适的id值', 'data'=>[]]);
        }
        $stageUserNum = Db::table('tab_user')->where(['user_stage_id'=>$id])->count();
        return json(['code'=>1, 'msg'=>'当前阶段下的用户数', 'data'=>['stage_user_num'=>$stageUserNum]]);
    }
    /**
     * 删除阶段
     * created by wjd 2021-7-26 17:32:51
    */
    public function doDeleteStage(Request $request)
    {
        $param = $request->param();
        $id = $param['id'] ?? 0;
        if($id <= 0){
            return json(['code'=>-1, 'msg'=>'请传入合适的id值', 'data'=>[]]);
        }
        $stageUserNum = Db::table('tab_user')->where(['user_stage_id'=>$id])->count();
        if($stageUserNum > 0){
            return json(['code'=>-1, 'msg'=>'当前阶段下还有用户数,禁止删除', 'data'=>[]]);

        }
        $deleteRes = Db::table('tab_user_stage')->where(['id'=>$id])->delete();
        if($deleteRes){
            return json(['code'=>1, 'msg'=>'删除成功!', 'data'=>[]]);
        }else{
            return json(['code'=>-1, 'msg'=>'系统繁忙,请稍后再试!', 'data'=>[]]);
        }

    }

    // 计划任务将统计的信息写入文件 一个管理员每日一条信息 (通知所属管理员 每天执行)
    public function generateMagForAdmin()
    {
        // $tmp_user_ids = Db::table('tab_user')
        // ->where(['lock_status'=>1])
        // ->column('id');
        // var_dump($tmp_user_ids);exit;
        // $userStageInfo = Db::table('tab_user_stage')->select()->toArray();
        // foreach ($userStageInfo as $k1=>$v1) {
        //     $remindInfo = json_decode($v1['follow_remind'],true);
        //     var_dump($remindInfo);
        // }

        // exit;


        $todayTimeInt = strtotime(date('Y-m-d'));
        $userStageInfo = Db::table('tab_user_stage')->select()->toArray();
        $i = 0;
        foreach($userStageInfo as $k1=>$v1){
            $remindInfo = json_decode($v1['follow_remind'], true);
            $follow_remind_switch = $remindInfo['follow_remind_switch'] ?? 0;
            if($follow_remind_switch == 1){
                $remindMsg = '';
                $not_login_num = 0;
                $not_recharge_num = 0;

                $stage_id = $v1['id'];
                // 统计需要提醒的数据
                $not_login_days = $remindInfo['not_login_days'] ?? 0;
                if($not_login_days > 0){
                    $not_login_num = Db::table('tab_user')
                        ->where(['user_stage_id'=>$stage_id, 'lock_status'=>1, 'login_time'=>['<', $todayTimeInt-$not_login_days*86400]])
                        ->count();
                }
                $not_recharge_days = $remindInfo['not_recharge_days'] ?? 0;
                if($not_recharge_days > 0){
                    $tmp_user_ids = Db::table('tab_user')
                        ->where(['user_stage_id'=>$stage_id, 'lock_status'=>1])
                        ->column('id');
                    foreach($tmp_user_ids as $k2=>$v2){
                        $spend_info = Db::table('tab_spend')
                            ->where(['user_id'=>$v2])
                            ->order('pay_time desc')
                            ->field('id,user_id,pay_time')
                            ->find();
                        $pay_time = $spend_info['pay_time'] ?? 0;
                        if($pay_time > $todayTimeInt-$not_recharge_days*86400){
                            unset($tmp_user_ids[$k2]);
                        }
                    }
                    $not_recharge_num = count($tmp_user_ids);
                }
                // var_dump($not_login_num);
                // var_dump($not_login_days);
                // var_dump($not_recharge_days);
                // var_dump($not_recharge_num);
                // exit;
                if($not_login_num !=0 || $not_recharge_num !=0){
                    // $remindInfo['remind_time'] = empty($remindInfo['remind_time']) ? '9:00' : $remindInfo['remind_time'];
                    $remindInfo['remind_time'] = '9:00';
                    // var_dump($remindInfo['remind_time']);exit;
                    $insertData = [
                        'user_stage_id'=>$stage_id,
                        'remindtime'=>$remindInfo['remind_time'],
                        'admin_ids'=>json_encode($remindInfo['remind_admin_ids']),
                        'remind_msg'=>"【溪谷软件】".$v1['name']."目前有".$not_login_num."个用户超过".$not_login_days."天未登录, 有".$not_recharge_num."个用户超过".$not_recharge_days."天未付费, 如有异常请及时处理。",
                        'stage_name'=>$v1['name'],
                        'not_login_num'=>$not_login_num,
                        'not_login_days'=>$not_login_days,
                        'not_recharge_num'=>$not_recharge_num,
                        'not_recharge_days'=>$not_recharge_days,
                        'remind_status'=>0,
                        'remind_fail_admin_ids'=>'',
                        'remind_type'=>$remindInfo['remind_type'],
                        'create_time'=>time(),
                    ];
                    $b = Db::table('tab_admin_remind_msg')->insert($insertData);

                    $i ++;
                }
            }
        }
        echo '处理了'.$i.'条数据';
        exit;

    }
    // 发送短信或者邮件给管理员 (废弃, abandon)
    public function sendMsgToAdmin_abandon()
    {
        $allStage = Db::table('tab_user_stage')->select()->toArray();
        foreach($allStage as $k=>$v){
            if(empty($allStage['follow_remind'])){
                unset($allStage[$k]);
            }else{
                $follow_remind_arr_tmp = json_decode($allStage['follow_remind'], true);
                if($follow_remind_arr_tmp['follow_remind_switch'] == 0){
                    unset($allStage[$k]);
                }
            }
        }

    }

    // 计划任务 发送短信或者邮件给管理员
    public function sendMsgToAdmin()
    {
        $todayTimeInt = strtotime(date('Y-m-d'));
        $d_time_int = time();
        $needRemind = Db::table('tab_admin_remind_msg')->where(['remind_status'=>0, 'create_time'=>['<', $d_time_int]])->select()->toArray();
        if(empty($needRemind)){
            exit('没有要处理的信息!');
        }
        foreach($needRemind as $k=>$v){
            $adminPhoneNums = '';
            $adminEmails = '';
            $admin_ids_arr = json_decode($v['admin_ids'], true);

            if(!empty($admin_ids_arr)){
                // 给管理员发送短信 / 邮件
                foreach($admin_ids_arr as $k2=>$v2){
                    $adminInfoTmp = Db::table('sys_user')->where(['id'=>$v2])->field('id,mobile,user_email')->find();
                    $adminPhone = $adminInfoTmp['mobile'] ?? '';
                    if(!empty($adminPhone)){
                        $adminPhoneNums .= $adminPhone.',';
                    }
                    $adminEmailTmp = $adminInfoTmp['user_email'] ?? '';
                    if(!empty($adminEmailTmp)){
                        $adminEmails .= $adminEmailTmp.',';
                    }
                }
                $adminPhoneNums = rtrim($adminPhoneNums, ",");
                $adminEmails = rtrim($adminEmails, ",");
            }

            $msgVariate = [
                'stage_name'=>$v['stage_name'],
                'not_login_num'=>$v['not_login_num'],
                'not_login_days'=>$v['not_login_days'],
                'not_recharge_num'=>$v['not_recharge_num'],
                'not_recharge_days'=>$v['not_recharge_days'],
            ];

            if($v['not_login_num'] > 0 ||  $v['not_recharge_num'] > 0){
                // 发送短信
                if($v['remind_type'] == 1 && !empty($adminPhoneNums)){
                    $msgRes = $this->sendMsg($adminPhoneNums, $msgVariate);
                    if($msgRes['send_status'] == "000000"){
                        Db::table('tab_admin_remind_msg')->where(['id'=>$v['id']])->update(['remind_status'=>1, 'update_time'=>time()]);
                    }else{
                        // Db::table('tab_admin_remind_msg')->where(['id'=>$v['id']])->update(['remind_status'=>2, 'update_time'=>time()]);
                    }
                    var_dump($msgRes);
                }
                // 发用邮件
                if($v['remind_type'] == 2 && !empty($adminEmails)){
                    $emailsArr = explode(',', $adminEmails);
                    $content = $v['remind_msg'];
                    $emailRes = $this->sendEmail($emailsArr, $content);
                    if($emailRes['error'] == 0){
                        Db::table('tab_admin_remind_msg')->where(['id'=>$v['id']])->update(['remind_status'=>1, 'update_time'=>time()]);
                    }else{
                        // Db::table('tab_admin_remind_msg')->where(['id'=>$v['id']])->update(['remind_status'=>2, 'update_time'=>time()]);
                    }
                    var_dump($emailRes);
                }
            }

        }

        exit('处理成功!');

    }

    // 发送短信
    private function sendMsg($phoneNums, $param)
    {
        try {
            $msg = new MsgModel();
            $data = $msg ::get(1);
            if (empty($data)) {
                return false;
            }
            $xigu = new Xigu($data);
            $result = json_decode($xigu -> sendSM($phoneNums, $data['user_stage_tid'], $param), true);
            return $result;
        } catch (\Exception $e) {
            return false;
        }

    }
    // 发送邮件
    private function sendEmail($mailArr, $content)
    {
        try {
            $result = cmf_send_batch_email($mailArr, '玩家阶段提醒', $content);
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    // 测试用户进阶
    public function testUserStage()
    {
        $user_id = '2618';
        $paramTmp = ['user_id'=>$user_id];
        $a = new HandleUserStageTask();
        $b = $a->doChangeUserStage1($paramTmp);
        var_dump($b);exit;
    }

    /**
     * @防刷设置
     *
     * @author: zsl
     * @since: 2021/7/27 17:32
     */
    public function defense()
    {
        $data = cmf_get_option('admin_set');
        $this -> assign('data', $data);
        return $this -> fetch();
    }

    /**
     * 玩家数据分析 (玩家画像)
     * created by wjd 2021-9-2 19:23:47
    */
    public function user_data_analyze(Request $request)
    {
        $user_id = $this->request->param('id', 0, 'intval');
        if (empty($user_id)) $this->error('参数错误');
        //消费记录
        if (AUTH_PAY == 1) {
            $map['user_id'] = $user_id;
            $map['pay_status'] = 1;
            $spend = Db ::table('tab_spend') -> field('count(id) as count,max(pay_time) as last_pay_time')
                    -> where($map)
                    -> find();
            $this -> assign('count', $spend['count']);
            $this->assign('last_pay_time', $spend['last_pay_time']);
            $this->assign('count_day', $spend['last_pay_time'] ? (int)((time() - $spend['last_pay_time']) / 86400) : '--');
        }
        //获取用户信息
        $user = get_user_entity2($user_id);
        $user['receive_address'] = implode(' ', explode('|!@#%-|', $user['receive_address']));
        $user['head_img'] = cmf_get_image_url($user['head_img']);
        $user['bind_balance'] = Db::table('tab_user_play')->where('user_id', $user_id)->sum('bind_balance');
        $this->assign('data', $user);
        //登录信息
        $mUserLoginRecord = new UserLoginRecordModel();
        $where['user_id'] = $user_id;
        $where['login_time'] = ['gt', 0];
        $where['game_id'] = ['gt', 0];
        $login_count = $mUserLoginRecord
                -> field('id')
                -> where($where)
                -> count();
        $this -> assign('login_count', $login_count);

        // 时间
        // if (empty($request['rangepickdate'])) {
            $date = date("Y-m-d", strtotime("-6 day")) . '至' . date("Y-m-d");
        // } else {
            // $date = $request['rangepickdate'];
        // }
        $dateexp = explode('至', $date);
        $starttime = $dateexp[0];
        $endtime = $dateexp[1];
        
        $this->assign('start', $starttime);
        $this->assign('end', $endtime);

        return $this->fetch();
    }

    /**
     * 修改用户信息
     * created by wjd 2021-9-7 17:28:17
    */
    public function change_user_info(Request $request)
    {
        $user_id = $request->param('id', 0, 'intval');
        if (empty($user_id)) return json(['code'=>-1, 'msg'=>'缺少参数!']);
        $option =  $request->param('option');
        // 修改密码
        if($option == 'password'){
            $password =  $request->param('password');
        
            if (!preg_match("/^[A-Za-z0-9]+$/", $password) || strlen($password) < 6 || strlen($password) > 15) {
                // $this->error('密码为6-15位字母或数字组合');
                return json(['code'=>-1, 'msg'=>'密码为6-15位字母或数字组合!']);
            }
            $save['password'] = cmf_password($password);

            $member = new UserModel;
            $res = $member->where('id', $user_id)->update($save);
            if ($res !== false) {
                write_action_log("修改玩家【" . get_user_name($user_id) . "】信息");
                // $this->success("修改成功", url('userinfo'));
                return json(['code'=>1, 'msg'=>'修改成功!']);
            } else {
                return json(['code'=>-1, 'msg'=>'修改失败,请稍后再试!']);
            }
        }
        // 修改APP单端登录 状态
        if($option == 'sso'){
            $sso =  $request->param('sso_value');
        
            $save['sso'] = $sso;
            $member = new UserModel;
            $res = $member->where('id', $user_id)->update($save);
            if ($res !== false) {
                write_action_log("修改玩家【" . get_user_name($user_id) . "】信息");
                // $this->success("修改成功", url('userinfo'));
                return json(['code'=>1, 'msg'=>'修改成功!']);
            } else {
                return json(['code'=>-1, 'msg'=>'修改失败,请稍后再试!']);
            }
        }
        
        
    }

    /**
     * 指定用户 数据分析 -- 雷达图
     * created by wjd 2021-9-8 10:14:08
    */
    public function radarChart(Request $request)
    {
        $user_id = $this->request->param('id', 0, 'intval');
        if (empty($user_id)){
            // $this->error('参数错误');
            return json(['code'=>-1, 'msg'=>'参数错误!', 'data'=>[]]);
        } 
        // 当前用户数据分析 --------------------   START
        $userModel = new UserModel();
        $d_user_info = $userModel
            ->where(['id'=>$user_id])
            ->field('id,account,cumulative,vip_level,user_stage_id,sex,user_score')
            ->find();

        #雷达图 -- 付费排名 (支向辐射范围)
        $user_info_tmp = $userModel->where(['lock_status'=>['=', 1]])->order('cumulative DESC')->field('id,account,cumulative')->find();
        
        $pay_ = ($user_info_tmp['cumulative'] == 0) ? 0 : ($d_user_info['cumulative'] / $user_info_tmp['cumulative']);
        // $pay_ = $d_user_info['cumulative'] / $user_info_tmp['cumulative'];

        #雷达图 -- VIP
        $user_info_tmp = $userModel->where(['lock_status'=>['=', 1]])->order('vip_level DESC')->field('id,account,vip_level')->find();
        if($user_info_tmp['vip_level'] == 0){
            $vip_ = 0;
        }else{
            $vip_ = $d_user_info['vip_level'] / $user_info_tmp['vip_level'];
        }
        
        #雷达图 -- 游戏活跃
        $user_info_tmp = Db::table('tab_user_login_record')
            ->where(['game_id'=>['>', 0]])
            ->field('user_id,count(id) as times')
            ->group('user_id')
            ->order('times DESC')
            ->find();
        $user_info_times = Db::table('tab_user_login_record')
            ->where(['user_id'=>$user_id, 'game_id'=>['>', 0]])
            ->count();
        $game_active_ = ($user_info_tmp['times'] == 0) ? 0: ($user_info_times / $user_info_tmp['times']);
        #雷达图 -- 游戏评分X.X
        $user_info_tmp = $userModel->where(['lock_status'=>['=', 1]])->order('user_score DESC')->field('id,account,user_score')->find();
        $user_score_ = ($user_info_tmp['user_score'] == 0) ? 0 : ($d_user_info / $user_info_tmp['user_score']);
        #雷达图 -- 玩家阶段
        $user_stage = Db::table('tab_user_stage')->field('id,name,only_for_sort')->order('only_for_sort DESC')->select();
        $j = 0; 
        $i = 0; // 计数器 1,2,3,4...
        $m = 0; // 总阶段数之和 1+2+3+4...
        foreach($user_stage as $k=>$v){
            $i++;
            if($d_user_info['user_stage_id'] == $v['id']){
                $j = $i;
            }
            $m = $m + $i;
        }
        $user_stage_ = $j / $m;
        #雷达图 -- 性别
        $sex_ = $d_user_info['sex']; // 0 男 1 女
        #雷达图 -- 夜猫子
        $user_info_times = Db::table('tab_user_login_record')
            ->field('id,game_id,game_name,user_id,user_account,login_time')
            ->where(['user_id'=>$user_id, 'game_id'=>['>', 0]])
            ->select()->toArray();

        // 22:00~02:00 的时间段登录游戏次数
        $total_login_game_times = 0;
        $needed_login_game_time = 0;
        $a = [];
        foreach($user_info_times as $k2=>$v2){
            // $user_info_times[$k2]['hour'] = date('G', $v2['login_time']);
            $tmp_hour = date('G', $v2['login_time']);
            if($tmp_hour >= 22 || $tmp_hour <= 2){
                $needed_login_game_time ++;
            }
            $total_login_game_times ++;
        }
        $game_login_times_ = ($total_login_game_times == 0) ? 0 :($needed_login_game_time / $total_login_game_times);

        // 默认 max 都为1
        return json([
            'code'=>1,
            'msg'=>'请求成功',
            'data'=>[
                'pay_'=>$pay_,
                'vip_'=>$vip_,
                'game_active_'=>$game_active_,
                'user_score_'=>$user_score_,
                'user_stage_'=>$user_stage_,
                // 'sex_'=>0.5,
                'game_login_times_'=>$game_login_times_,
                'd_user_vip_level'=>$d_user_info['vip_level'],
            ]
        ]);
        
        // exit;

        // 当前用户数据分析 --------------------   END
    }

    /**
     * 指定用户 数据分析 -- 付费分析
     * created by wjd 2021-9-8 10:14:08
    */
    public function d_user_data_analyze(Request $request)
    {
        $user_id = $this->request->param('id', 0, 'intval');
        if (empty($user_id)){
            // $this->error('参数错误');
            return json(['code'=>-1, 'msg'=>'参数错误!', 'data'=>[]]);
        }
        $param = $this->request->param();
        $start_time = strtotime($param['start_time']);
        $end_time = strtotime($param['end_time']);
        if($start_time > $end_time){
            return json(['code'=>-1, 'msg'=>'开始时间不能大于结束时间!', 'data'=>[]]);
        }
        $end_time = $end_time + 86399;
        // 查询当前用户下面的小号
        $small_info = Db::table('tab_user')->where(['puid'=>$user_id])->field('id')->select();
        $user_ids = [];
        foreach($small_info as $k3=>$v3){
            $user_ids[] = $v3['id'];
        }
        $user_ids[] = $user_id;

        $user_spend_list = Db::table('tab_spend')
            ->field('id,user_id,game_id,pay_amount,pay_time')
            ->where(['user_id'=>['in',$user_ids],'game_id'=>['>',0],'pay_status'=>1])
            ->where(['pay_time'=>['between', [$start_time, $end_time]]])
            ->select()->toArray();
        // var_dump( $user_spend_list); // exit;
        $pay_times_02 = $pay_times_24 = $pay_times_46 = $pay_times_68 = $pay_times_810 = $pay_times_1012 = $pay_times_1214 = $pay_times_1416 = $pay_times_1618 = $pay_times_1820 = $pay_times_2022 = $pay_times_2224 = $total_times = 0;

        foreach($user_spend_list as $k=>$v){
            $tmp_hour = date('G', $v['pay_time']);
            // var_dump($tmp_hour);
            // var_dump($v['pay_time']);

            if($tmp_hour >= 0 && $tmp_hour < 2){
                $pay_times_02 ++;
            }
            if($tmp_hour >= 2 && $tmp_hour < 4){
                $pay_times_24 ++;
            }
            if($tmp_hour >= 4 && $tmp_hour < 6){
                $pay_times_46 ++;
            }
            if($tmp_hour >= 6 && $tmp_hour < 8){
                $pay_times_68 ++;
            }
            if($tmp_hour >= 8 && $tmp_hour < 10){
                $pay_times_810 ++;
            }
            if($tmp_hour >= 10 && $tmp_hour < 12){
                $pay_times_1012 ++;
            }
            if($tmp_hour >= 12 && $tmp_hour < 14){
                $pay_times_1214 ++;
            }
            if($tmp_hour >= 14 && $tmp_hour < 16){
                $pay_times_1416 ++;
            }
            if($tmp_hour >= 16 && $tmp_hour < 18){
                $pay_times_1618 ++;
            }
            if($tmp_hour >= 18 && $tmp_hour < 20){
                $pay_times_1820 ++;
            }
            if($tmp_hour >= 20 && $tmp_hour < 22){
                $pay_times_2022 ++;
            }
            if($tmp_hour >= 22 && $tmp_hour < 24){
                $pay_times_2224 ++;
            }
            $total_times ++ ;
        }
    
        if($total_times == 0){
            $returnData = [0,0,0,0,0,0,0,0,0,0,0,0];
        }else{
            $returnData = [$pay_times_02,$pay_times_24,$pay_times_46,$pay_times_68,$pay_times_810,$pay_times_1012,$pay_times_1214,$pay_times_1416,$pay_times_1618,$pay_times_1820,$pay_times_2022,$pay_times_2224];
        }

        $pay_amount_0_6 = $pay_amount_6_10 = $pay_amount_10_50 = $pay_amount_50_100 = $pay_amount_100_200 = $pay_amount_200_300 = $pay_amount_300_500 = $pay_amount_500_800 = $pay_amount_800_1000 = $pay_amount_1000_3000 = $pay_amount_3000_5000 = $pay_amount_5000_10000 = $pay_amount_10000_ = $total_pay_times = 0;
        foreach($user_spend_list as $k2=>$v2){
            if($v2['pay_amount'] > 0 && $v2['pay_amount'] <= 6){
                $pay_amount_0_6 ++;
            }
            if($v2['pay_amount'] > 6 && $v2['pay_amount'] <= 10){
                $pay_amount_6_10 ++;
            }
            if($v2['pay_amount'] > 10 && $v2['pay_amount'] <= 50){
                $pay_amount_10_50 ++;
            }
            if($v2['pay_amount'] > 50 && $v2['pay_amount'] <= 100){
                $pay_amount_50_100 ++;
            }
            if($v2['pay_amount'] > 100 && $v2['pay_amount'] <= 200){
                $pay_amount_100_200 ++;
            }
            if($v2['pay_amount'] > 200 && $v2['pay_amount'] <= 300){
                $pay_amount_200_300 ++;
            }
            if($v2['pay_amount'] > 300 && $v2['pay_amount'] <= 500){
                $pay_amount_300_500 ++;
            }
            if($v2['pay_amount'] > 500 && $v2['pay_amount'] <= 800){
                $pay_amount_500_800 ++;
            }
            if($v2['pay_amount'] > 800 && $v2['pay_amount'] <= 1000){
                $pay_amount_800_1000 ++;
            }
            if($v2['pay_amount'] > 1000 && $v2['pay_amount'] <= 3000){
                $pay_amount_1000_3000 ++;
            }
            if($v2['pay_amount'] > 3000 && $v2['pay_amount'] <= 5000){
                $pay_amount_3000_5000 ++;
            }
            if($v2['pay_amount'] > 5000 && $v2['pay_amount'] <= 10000){
                $pay_amount_5000_10000 ++;
            }
            if($v2['pay_amount'] > 10000){
                $pay_amount_10000_ ++;
            }
            $total_pay_times ++;
        }
        if($total_pay_times == 0){
            $returnData2 = [0,0,0,0,0,0,0,0,0,0,0,0,0];
        }else{
            $returnData2 = [$pay_amount_0_6,$pay_amount_6_10,$pay_amount_10_50,$pay_amount_50_100,$pay_amount_100_200,$pay_amount_200_300,$pay_amount_300_500,$pay_amount_500_800,$pay_amount_800_1000,$pay_amount_1000_3000,$pay_amount_3000_5000,$pay_amount_5000_10000,$pay_amount_10000_];
        }

        // var_dump( $returnData); // exit;
        $finalData = ['period_times'=>$returnData, 'total_times'=>$total_times, 'pay_times'=>$returnData2, 'total_pay_times'=>$total_pay_times];

        return json(['code'=>1, 'msg'=>'请求成功!', 'data'=>$finalData]);

    }

    /**
     * 柱状图表导出
     * created by wjd 2021年9月16日15:02:10
    */
    public function expHistogramTable(Request $request)
    {
        $user_id = $this->request->param('id', 0, 'intval');
        if (empty($user_id)){
            // $this->error('参数错误');
            return json(['code'=>-1, 'msg'=>'参数错误!', 'data'=>[]]);
        }
        $param = $this->request->param();
        $start_time = strtotime($param['start_time']);
        $end_time = strtotime($param['end_time']);
        if($start_time > $end_time){
            return json(['code'=>-1, 'msg'=>'开始时间不能大于结束时间!', 'data'=>[]]);
        }
        $end_time = $end_time + 86399;

        // 查询当前用户下面的小号
        $small_info = Db::table('tab_user')->where(['puid'=>$user_id])->field('id')->select();
        $user_ids = [];
        foreach($small_info as $k3=>$v3){
            $user_ids[] = $v3['id'];
        }
        $user_ids[] = $user_id;

        $user_spend_list = Db::table('tab_spend')
            ->field('id,user_id,game_id,pay_amount,pay_time')
            ->where(['user_id'=>$user_ids,'game_id'=>['>',0],'pay_status'=>1])
            ->where(['pay_time'=>['between', [$start_time, $end_time]]])
            ->select()->toArray();
        // var_dump( $user_spend_list); // exit;
        $pay_times_02 = $pay_times_24 = $pay_times_46 = $pay_times_68 = $pay_times_810 = $pay_times_1012 = $pay_times_1214 = $pay_times_1416 = $pay_times_1618 = $pay_times_1820 = $pay_times_2022 = $pay_times_2224 = $total_times = 0;

        foreach($user_spend_list as $k=>$v){
            $tmp_hour = date('G', $v['pay_time']);
            // var_dump($tmp_hour);
            // var_dump($v['pay_time']);

            if($tmp_hour >= 0 && $tmp_hour < 2){
                $pay_times_02 ++;
            }
            if($tmp_hour >= 2 && $tmp_hour < 4){
                $pay_times_24 ++;
            }
            if($tmp_hour >= 4 && $tmp_hour < 6){
                $pay_times_46 ++;
            }
            if($tmp_hour >= 6 && $tmp_hour < 8){
                $pay_times_68 ++;
            }
            if($tmp_hour >= 8 && $tmp_hour < 10){
                $pay_times_810 ++;
            }
            if($tmp_hour >= 10 && $tmp_hour < 12){
                $pay_times_1012 ++;
            }
            if($tmp_hour >= 12 && $tmp_hour < 14){
                $pay_times_1214 ++;
            }
            if($tmp_hour >= 14 && $tmp_hour < 16){
                $pay_times_1416 ++;
            }
            if($tmp_hour >= 16 && $tmp_hour < 18){
                $pay_times_1618 ++;
            }
            if($tmp_hour >= 18 && $tmp_hour < 20){
                $pay_times_1820 ++;
            }
            if($tmp_hour >= 20 && $tmp_hour < 22){
                $pay_times_2022 ++;
            }
            if($tmp_hour >= 22 && $tmp_hour < 24){
                $pay_times_2224 ++;
            }
            $total_times ++ ;
        }
    
        if($total_times == 0){
            $returnData = [0,0,0,0,0,0,0,0,0,0,0,0];
        }else{
            $returnData = [$pay_times_02,$pay_times_24,$pay_times_46,$pay_times_68,$pay_times_810,$pay_times_1012,$pay_times_1214,$pay_times_1416,$pay_times_1618,$pay_times_1820,$pay_times_2022,$pay_times_2224];
        }
        // 导出
        $xlsName = '充值时段';
        $xlsCell = array(
            array('period', '时段'),
            array('times', "次数"),
            array('proportion', "占比"),
        );
        $expData = [];
        $i = 0;
        if($total_times == 0){
            foreach($returnData as $k5=>$v5){
                $expData[] = ['period'=>''.($i*2).'-'.($i*2 + 2).'', 'times'=>$v5, 'proportion'=>'0 %'];
                $i += 1;
            }
        }else{
            foreach($returnData as $k5=>$v5){
                $expData[] = ['period'=>''.($i*2).'-'.($i*2 + 2).'', 'times'=>$v5, 'proportion'=>($v5/$total_times).' %'];
                $i += 1;
            }
        }
        
        // var_dump($expData);exit;
        $xlsData = $expData;
        write_action_log("导出用户柱状图");
        
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
        $this->exportExcel($xlsName, $xlsCell, $xlsData);
        
        // var_dump( $returnData); // exit;
        // $finalData = ['period_times'=>$returnData, 'total_times'=>$total_times, 'pay_times'=>$returnData2, 'total_pay_times'=>$total_pay_times];

        // return json(['code'=>1, 'msg'=>'请求成功!', 'data'=>$finalData]);

    }
    /**
     * 折线图表导出
     * created by wjd 2021年9月16日15:02:10
    */
    public function expLineChartTable(Request $request)
    {
        $user_id = $this->request->param('id', 0, 'intval');
        if (empty($user_id)){
            // $this->error('参数错误');
            return json(['code'=>-1, 'msg'=>'参数错误!', 'data'=>[]]);
        }
        $param = $this->request->param();
        $start_time = strtotime($param['start_time']);
        $end_time = strtotime($param['end_time']);
        if($start_time > $end_time){
            return json(['code'=>-1, 'msg'=>'开始时间不能大于结束时间!', 'data'=>[]]);
        }
        $end_time = $end_time + 86399;

        // 查询当前用户下面的小号
        $small_info = Db::table('tab_user')->where(['puid'=>$user_id])->field('id')->select();
        $user_ids = [];
        foreach($small_info as $k3=>$v3){
            $user_ids[] = $v3['id'];
        }
        $user_ids[] = $user_id;

        $user_spend_list = Db::table('tab_spend')
            ->field('id,user_id,game_id,pay_amount,pay_time')
            ->where(['user_id'=>$user_ids,'game_id'=>['>',0],'pay_status'=>1])
            ->where(['pay_time'=>['between', [$start_time, $end_time]]])
            ->select()->toArray();

        $pay_amount_0_6 = $pay_amount_6_10 = $pay_amount_10_50 = $pay_amount_50_100 = $pay_amount_100_200 = $pay_amount_200_300 = $pay_amount_300_500 = $pay_amount_500_800 = $pay_amount_800_1000 = $pay_amount_1000_3000 = $pay_amount_3000_5000 = $pay_amount_5000_10000 = $pay_amount_10000_ = $total_pay_times = 0;
        foreach($user_spend_list as $k2=>$v2){
            if($v2['pay_amount'] > 0 && $v2['pay_amount'] <= 6){
                $pay_amount_0_6 ++;
            }
            if($v2['pay_amount'] > 6 && $v2['pay_amount'] <= 10){
                $pay_amount_6_10 ++;
            }
            if($v2['pay_amount'] > 10 && $v2['pay_amount'] <= 50){
                $pay_amount_10_50 ++;
            }
            if($v2['pay_amount'] > 50 && $v2['pay_amount'] <= 100){
                $pay_amount_50_100 ++;
            }
            if($v2['pay_amount'] > 100 && $v2['pay_amount'] <= 200){
                $pay_amount_100_200 ++;
            }
            if($v2['pay_amount'] > 200 && $v2['pay_amount'] <= 300){
                $pay_amount_200_300 ++;
            }
            if($v2['pay_amount'] > 300 && $v2['pay_amount'] <= 500){
                $pay_amount_300_500 ++;
            }
            if($v2['pay_amount'] > 500 && $v2['pay_amount'] <= 800){
                $pay_amount_500_800 ++;
            }
            if($v2['pay_amount'] > 800 && $v2['pay_amount'] <= 1000){
                $pay_amount_800_1000 ++;
            }
            if($v2['pay_amount'] > 1000 && $v2['pay_amount'] <= 3000){
                $pay_amount_1000_3000 ++;
            }
            if($v2['pay_amount'] > 3000 && $v2['pay_amount'] <= 5000){
                $pay_amount_3000_5000 ++;
            }
            if($v2['pay_amount'] > 5000 && $v2['pay_amount'] <= 10000){
                $pay_amount_5000_10000 ++;
            }
            if($v2['pay_amount'] > 10000){
                $pay_amount_10000_ ++;
            }
            $total_pay_times ++;
        }
        if($total_pay_times == 0){
            $returnData2 = [0,0,0,0,0,0,0,0,0,0,0,0,0];
        }else{
            $returnData2 = [$pay_amount_0_6,$pay_amount_6_10,$pay_amount_10_50,$pay_amount_50_100,$pay_amount_100_200,$pay_amount_200_300,$pay_amount_300_500,$pay_amount_500_800,$pay_amount_800_1000,$pay_amount_1000_3000,$pay_amount_3000_5000,$pay_amount_5000_10000,$pay_amount_10000_];
        }
        // 导出
        $xlsName = '充值档位';
        $xlsCell = array(
            array('period', '时段'),
            array('times', "次数"),
            array('proportion', "占比"),
        );
        $expData = [];

        if($total_pay_times == 0){
            $expData[0] = ['period'=>'0 - 6', 'times'=>$returnData2[0], 'proportion'=>($total_pay_times).' %'];
            $expData[1] = ['period'=>'6 - 10', 'times'=>$returnData2[1], 'proportion'=>($total_pay_times).' %'];
            $expData[2] = ['period'=>'10 - 50', 'times'=>$returnData2[2], 'proportion'=>($total_pay_times).' %'];
            $expData[3] = ['period'=>'50 - 100', 'times'=>$returnData2[3], 'proportion'=>($total_pay_times).' %'];
            $expData[4] = ['period'=>'100 - 200', 'times'=>$returnData2[4], 'proportion'=>($total_pay_times).' %'];
            $expData[5] = ['period'=>'200 - 300', 'times'=>$returnData2[5], 'proportion'=>($total_pay_times).' %'];
            $expData[6] = ['period'=>'300 - 500', 'times'=>$returnData2[6], 'proportion'=>($total_pay_times).' %'];
            $expData[7] = ['period'=>'500 - 800', 'times'=>$returnData2[7], 'proportion'=>($total_pay_times).' %'];
            $expData[8] = ['period'=>'800 - 1000', 'times'=>$returnData2[8], 'proportion'=>($total_pay_times).' %'];
            $expData[9] = ['period'=>'1000 - 3000', 'times'=>$returnData2[9], 'proportion'=>($total_pay_times).' %'];
            $expData[10] = ['period'=>'3000 - 5000', 'times'=>$returnData2[10], 'proportion'=>($total_pay_times).' %'];
            $expData[11] = ['period'=>'5000 - 10000', 'times'=>$returnData2[11], 'proportion'=>($total_pay_times).' %'];
            $expData[12] = ['period'=>'10000及以上', 'times'=>$returnData2[12], 'proportion'=>($total_pay_times).' %'];

        }else{
            
            $expData[0] = ['period'=>'0 - 6', 'times'=>$returnData2[0], 'proportion'=>($returnData2[0]/$total_pay_times).' %'];
            $expData[1] = ['period'=>'6 - 10', 'times'=>$returnData2[1], 'proportion'=>($returnData2[1]/$total_pay_times).' %'];
            $expData[2] = ['period'=>'10 - 50', 'times'=>$returnData2[2], 'proportion'=>($returnData2[2]/$total_pay_times).' %'];
            $expData[3] = ['period'=>'50 - 100', 'times'=>$returnData2[3], 'proportion'=>($returnData2[3]/$total_pay_times).' %'];
            $expData[4] = ['period'=>'100 - 200', 'times'=>$returnData2[4], 'proportion'=>($returnData2[4]/$total_pay_times).' %'];
            $expData[5] = ['period'=>'200 - 300', 'times'=>$returnData2[5], 'proportion'=>($returnData2[5]/$total_pay_times).' %'];
            $expData[6] = ['period'=>'300 - 500', 'times'=>$returnData2[6], 'proportion'=>($returnData2[6]/$total_pay_times).' %'];
            $expData[7] = ['period'=>'500 - 800', 'times'=>$returnData2[7], 'proportion'=>($returnData2[7]/$total_pay_times).' %'];
            $expData[8] = ['period'=>'800 - 1000', 'times'=>$returnData2[8], 'proportion'=>($returnData2[8]/$total_pay_times).' %'];
            $expData[9] = ['period'=>'1000 - 3000', 'times'=>$returnData2[9], 'proportion'=>($returnData2[9]/$total_pay_times).' %'];
            $expData[10] = ['period'=>'3000 - 5000', 'times'=>$returnData2[10], 'proportion'=>($returnData2[10]/$total_pay_times).' %'];
            $expData[11] = ['period'=>'5000 - 10000', 'times'=>$returnData2[11], 'proportion'=>($returnData2[11]/$total_pay_times).' %'];
            $expData[12] = ['period'=>'10000及以上', 'times'=>$returnData2[12], 'proportion'=>($returnData2[12]/$total_pay_times).' %'];
        }
        
        // var_dump($expData);exit;
        $xlsData = $expData;
        write_action_log("导出用户折线图");
        
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
        $this->exportExcel($xlsName, $xlsCell, $xlsData);
    }

}
