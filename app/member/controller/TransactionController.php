<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\member\controller;


use app\common\controller\BaseController;
use app\member\logic\UserLogic;
use app\member\model\UserConfigModel;
use app\member\model\UserModel;
use app\member\model\UserTransactionModel;
use app\member\model\UserTransactionOrderModel;
use app\member\validate\UserValidate3;
use cmf\controller\AdminBaseController;
use think\Checkidcard;
use think\Request;
use think\Db;

//该控制器必须以下3个权限
class TransactionController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_USER != 1) {
            $this->error('请购买用户权限', url('admin/main/index'));
        }
        if (AUTH_PAY != 1) {
            $this->error('请购买充值权限', url('admin/main/index'));
        }
        if (AUTH_GAME != 1) {
            $this->error('请购买游戏权限', url('admin/main/index'));
        }
    }

    public function lists()
    {
        $transaction = new UserTransactionModel();
        $base = new BaseController;
        $account = $this->request->param('user_account', '');
        if ($account != '') {
            $map['user_account'] = ['like', '%' . addcslashes($account, '%') . '%'];
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
        $game_id = $this->request->param('game_id', 0, 'intval');
        if ($game_id > 0) {
            $map['game_id'] = $game_id;
        }
        $status = $this->request->param('status', '');
        if ($status != '') {
            $map['status'] = $status;
            if($status == 1){
                $map['status'] = ['in',[-1,1]];
            }
        }
        $exend['order'] = 'create_time desc';
        $exend['field'] = 'id,user_id,user_account,game_id,game_name,create_time,status,order_number,money,server_name,title,cumulative,small_id';
        $data = $base->data_list($transaction, $map, $exend);
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
        }

        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @修改状态
     * @author: 郭家屯
     * @since: 2020/3/2 9:51
     */
    public function setStatus()
    {
        $id = $this -> request -> param('id/d');
        $ids = $this -> request -> param('ids/a');
        if (empty($id) && empty($ids)) $this -> error('请选择要操作的数据');
        $status = $this -> request -> param('status/d');
        $field = $this -> request -> param('field');
        $msg = $this -> request -> param('msg');
        if ($field) {
            $save[$field] = $msg;
        }
        $save['status'] = $status;
        $model = new UserTransactionModel();
        if (!empty($id)) {

            $transaction = $model -> field('id,status,small_id,user_id') -> where('id', $id) -> find();
            //获取公共账户
            $usermodel = new UserModel();
            $platform = $usermodel -> get_public_account();
            Db ::startTrans();
            try {
                //修改小号归属
                if ($status == 1 && $transaction['status'] == 4) {
                    $usermodel -> where('id', $transaction['small_id']) -> setField('puid', $platform['id']);
                    Db ::table('tab_user_play_info') -> where('user_id', $transaction['small_id']) -> setField('puid', $platform['id']);
                }
                if ($status == 4) {
                    $usermodel -> where('id', $transaction['small_id']) -> setField('puid', $transaction['user_id']);
                    Db ::table('tab_user_play_info') -> where('user_id', $transaction['small_id']) -> setField('puid', $transaction['user_id']);
                }
                $model -> where('id', $id) -> update($save);
                //更改交易状态
                Db ::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db ::rollback();
                $this -> error('操作失败');
            }
            $this -> success('操作成功');

        }
        if (!empty($ids)) {
            //批量操作
            $usermodel = new UserModel();
            //获取公共账户
            $platform = $usermodel -> get_public_account();
            $transactionLists = $model -> field('id,status,small_id,user_id') -> where(['id' => ['in', $ids]]) -> select();
            try {
                Db ::startTrans();
                foreach ($transactionLists as $transaction) {
                    if ($transaction['status'] == '3' || $transaction['status'] == '-1') {
                        continue;
                    }
                    //修改小号归属
                    if ($status == 1 && $transaction['status'] == 4) {
                        $usermodel -> where('id', $transaction['small_id']) -> setField('puid', $platform['id']);
                        Db ::table('tab_user_play_info') -> where('user_id', $transaction['small_id']) -> setField('puid', $platform['id']);
                    }
                    if ($status == 4) {
                        $usermodel -> where('id', $transaction['small_id']) -> setField('puid', $transaction['user_id']);
                        Db ::table('tab_user_play_info') -> where('user_id', $transaction['small_id']) -> setField('puid', $transaction['user_id']);
                    }
                    //更改交易状态
                    $model -> where('id', $transaction['id']) -> update($save);
                }
                Db ::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db ::rollback();
            }
            $this -> success('操作成功');
        }
    }

    /**
     * @函数或方法说明
     * @查看详情
     * @author: 郭家屯
     * @since: 2020/3/2 9:55
     */
    public function detail()
    {
        $id = $this->request->param('id/d');
        if(empty($id))$this->error('参数错误');
        $model = new UserTransactionModel();
        $data = $model->getdetail($id);
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();

        if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
            $data['user_account'] = get_ys_string($data['user_account'],$ys_show_admin['account_show_admin']);
        }
        if($ys_show_admin['phone_show_admin_status'] == 1){//开启了账号查看隐私
            $data['phone'] = get_ys_string($data['phone'],$ys_show_admin['phone_show_admin']);
        }

        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @商品订单
     * @author: 郭家屯
     * @since: 2020/3/2 11:16
     */
    public function order()
    {
        $transaction = new UserTransactionOrderModel();
        $base = new BaseController;
        $pay_order_number = $this->request->param('pay_order_number', '');
        if ($pay_order_number != '') {
            $map['pay_order_number'] = ['like', '%' . addcslashes($pay_order_number, '%') . '%'];
        }
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time && $end_time) {
            $map['pay_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['pay_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['pay_time'] = ['egt', strtotime($start_time)];
        }
        $game_id = $this->request->param('game_id', 0, 'intval');
        if ($game_id > 0) {
            $map['game_id'] = $game_id;
        }
        $pay_status = $this->request->param('pay_status', '');
        if ($pay_status != '') {
            $map['pay_status'] = $pay_status;
        }
        $exend['order'] = 'pay_time desc';
        $exend['field'] = 'id,pay_order_number,user_id,user_account,phone,game_id,game_name,pay_time,pay_status,pay_amount,fee,server_name,title,sell_account,small_id';
        $data = $base->data_list($transaction, $map, $exend);
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
                $data[$k5]['sell_account'] = get_ys_string($v5['sell_account'],$ys_show_admin['account_show_admin']);
            }
            if($ys_show_admin['phone_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['phone'] = get_ys_string($v5['phone'],$ys_show_admin['phone_show_admin']);
            }

        }
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @公共账号
     * @author: 郭家屯
     * @since: 2020/3/2 13:56
     */
    public function public_account()
    {
        $model = new UserModel();
        $data = $model->get_public_account();
        if($data){
            $smallfield = 'id';
            $smallmap['puid'] = $data['id'];
            $smallorder = 'id desc';
            $data['small_count'] = count(get_user_lists_info($smallfield,$smallmap,$smallorder));
        }
        if($this->request->isPost()){
            $request = $this->request->param();
            $logic = new UserLogic();
            $validate = new UserValidate3();
            if($data){
                $request['id'] = $data['id'];
                if(!$validate->scene('password')->check($request)){
                    $this->error($validate->getError());
                }
            }else{
                if(!$validate->check($request)){
                    $this->error($validate->getError());
                }
                $request = $this->check_auth($request);
            }
            $result = $logic->set_public_account($request);
            if($result){
                $this->success('设置成功');
            }else{
                $this->error('设置失败');
            }
        }

        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @修改密码
     * @author: 郭家屯
     * @since: 2020/3/2 19:47
     */
    public function password_edit()
    {
        $request = $this->request->param();
        $validate = new UserValidate3();
        if(!$validate->scene('password')->check($request)){
            $this->error($validate->getError());
        }
        $model = new UserModel();
        $result = $model->where('id',$request['id'])->setField('password',cmf_password($request['password']));
        if($result !== false){
            $this->success('修改成功');
        }else{
            $this->error('修改失败');
        }
    }

    protected function check_auth($data=[])
    {
        if (!preg_match('/^([\xe4-\xe9][\x80-\xbf]{2}){2,25}$/', $data['real_name'])) {
            $this->error('姓名格式错误');
        }
        $len = mb_strlen($data['real_name']);
        if ($len < 2 || $len > 25) {
            $this->error('姓名长度需要在2-25个字符之间');
        }
        $data['idcard'] = strtolower($data['idcard']);
        $checkidcard = new Checkidcard();
        $invidcard = $checkidcard->checkIdentity($data['idcard']);
        if (!$invidcard) {
            $this->error("证件号码错误！");
        }
        $userconfig = new UserConfigModel();
        //实名认证设置
        $config = $userconfig -> getSet('age');
        $userModel = new UserModel();
        if ($config['config']['can_repeat'] != '1') {
            $cardd = $userModel -> where('idcard', $data['idcard']) -> field('id') -> find();
            if ($cardd) {
                $this -> error("身份证号码已被使用！");
            }
        }

        if (($config['status'] == 0) || ($config['status'] == 1 && $config['config']['ali_status'] == 0)) {
            //判断年龄是否大于16岁
            if (is_adult($data['idcard'])) {
                $data['age_status'] = 2;
            } else {
                $data['age_status'] = 3;
            }
        } else {
            //真实判断身份证是否有效
            $re = age_verify($data['idcard'], $data['real_name'], $config['config']['appcode']);
            switch ($re) {
                case -1:
                    $this->error("短信数量已经使用完！");
                    break;
                case -2:
                    $this->error("连接接口失败");
                    break;
                case 0:
                    $this->error("认证信息错误");
                    break;
                case 1://成年
                    $data['age_status'] = 2;
                    $data['anti_addiction'] = 1;
                    break;
                case 2://未成年
                    $data['age_status'] = 3;
                    break;
                default:
            }
        }
        return $data;
    }
}
