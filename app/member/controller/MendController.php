<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/25
 * Time: 14:35
 */

namespace app\member\controller;

use app\member\model\UserMendModel;
use app\recharge\controller\SpendController;
use app\recharge\model\SpendModel;
use cmf\controller\AdminBaseController;
use app\common\controller\BaseController;
use app\member\model\UserModel;
use think\Db;
use think\Request;

class MendController extends AdminBaseController
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
        if (AUTH_PROMOTE != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买渠道权限');
            } else {
                $this->error('请购买渠道权限', url('admin/main/index'));
            };
        }
    }

    /**
     * [补链记录]-修改-增加对管理员游戏查看限制的关联判断
     * @param Request $request
     * @return mixed
     * @author byh-20210624
     */
    public function index(Request $request)
    {
        $base = new BaseController();
        $model = new UserMendModel();
        $account = $request->param('account/s');
        if ($account) {
            $map['tab_user_mend.user_account'] = ['like', '%' . $account . '%'];
        }
        //定制-增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        $view_game_ids = get_admin_view_game_ids(session('ADMIN_ID'));
        if(!empty($view_game_ids)){
            $map['u.fgame_id'] = ['IN',$view_game_ids];
        }
        //定制-增加对登录管理员是否有游戏查看的限制-20210624-byh-end
        $extend['field'] = 'tab_user_mend.user_account,tab_user_mend.promote_account,tab_user_mend.promote_account_to,tab_user_mend.create_time,tab_user_mend.remark,tab_user_mend.op_account,tab_user_mend.cut_time';
        $extend['order'] = 'tab_user_mend.create_time desc';
        $extend['join1'] = [['tab_user' => 'u'], 'u.id=tab_user_mend.user_id', 'left'];
        $data = $base->data_list_join($model, $map, $extend);
        // 获取分页显示
        $page = $data->render();
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $admin_id = session('ADMIN_ID');
        $safe_center_info = Db::table('tab_safe_center')->where('id = 1')->field('id,config,ids')->find();
        $safe_center_info_arr = json_decode($safe_center_info['config'], true);
        $privicy_mode_admin = $safe_center_info_arr['privicy_mode_admin'] ?? 0;
        $account_show_admin = 1024; // 0:账号****显示, 1:账号X****X显示
        $phone_show_admin = 1024; // 0:手机号****显示, 1:手机号X****X显示
        if($privicy_mode_admin == 1){ // 管理员隐私模式已经开启
            $forbid_ids = json_decode($safe_center_info['ids'], true);
            if(in_array($admin_id, $forbid_ids)){
                $account_show_admin = $safe_center_info_arr['account_show_admin'];
                $phone_show_admin = $safe_center_info_arr['phone_show_admin'];
            }
            foreach($data as $k5=>$v5){
                if($account_show_admin == 1){
                    $firstStr = mb_substr($v5['user_account'], 0, 1, 'utf-8');
                    $lastStr = mb_substr($v5['user_account'], - 1, 1, 'utf-8');
                    $data[$k5]['user_account'] = $firstStr.'****'.$lastStr;
                }
                if($account_show_admin == 0){
                    $data[$k5]['user_account'] = '****';
                }
    
            }
        }
        $this->assign('data_lists', $data);
        $this->assign("page", $page);
        $user_list = $model->field('user_id,user_account')->select();
        $this->assign('user_list', $user_list);
        return $this->fetch();
    }

    /**
     * [补链]
     * @author 郭家屯[gjt]
     */
    public function add()
    {
        $id = $this->request->param('id');
        if (empty($id)) $this->error('参数错误');
        $ids = explode(',', $id);
        $usermodel = new UserModel();
        $map['id'] = ['in', $ids];
        $user_info = $usermodel->where($map)->field('GROUP_CONCAT(account SEPARATOR "，") as user_account,promote_account')->group('promote_id')->select();
        if (empty($user_info)) $this->error('用户不存在');
        if ($this->request->isPost()) {
            $model = new UserMendModel();
            $promote_id_to = $this->request->param('promote_id_to');
            if ($promote_id_to == '') $this->error('请选择修改后渠道');
            $promote_account_to = get_promote_name($promote_id_to);
            $remark = $this->request->param('remark');
            if ($promote_account_to == '未知渠道') {
                $this->error('渠道不存在');
            }
            $success = 0;
            $error = 0;
            foreach ($ids as $key => $vo) {
                $user = get_user_entity($vo);
                if (!$user) {
                    $error++;
                    continue;
                }
                if($user['puid']!=0){//小号不参与补链
                    $error++;
                    continue;
                }
                if ($user['promote_id'] == $promote_id_to) {
                    $error++;
                    continue;
                }
                $data['user_id'] = $vo;
                $data['user_account'] = $user['account'];
                $data['promote_id'] = $user['promote_id'];
                $data['promote_account'] = get_promote_name($user['promote_id']);
                $data['promote_id_to'] = $promote_id_to;
                $data['promote_account_to'] = $promote_account_to;
                $data['remark'] = $remark;
                $data['create_time'] = time();
                $data['op_id'] = cmf_get_current_admin_id();
                $data['op_account'] = get_admin_name($data['op_id']);
                //补单切割时间
                $cut_time = $this -> request -> param('cut_time', 0, 'strtotime');
                $data['cut_time'] = $cut_time;
                $result = $model->insertGetId($data);
                if ($result) {
                    Db::startTrans();
                    try {
                        $save['promote_id'] = $promote_id_to;
                        $save['promote_account'] = $promote_account_to;
                        $promote_id_to_info = get_promote_entity($promote_id_to);
                        if ($promote_id_to_info['parent_id']) {
                            $save['parent_id'] = $promote_id_to_info['parent_id'];
                            $save['parent_name'] = get_promote_name($promote_id_to_info['parent_id']);
                        } else {
                            $save['parent_id'] = 0;
                            $save['parent_name'] = '';
                        }
                        //用户表
                        Db::table('tab_user')->where('id', $user['id'])->update($save);
                        Db::table('tab_user_play')->field(true)->where('user_id',$user['id'])->update($save);
                        Db::table('tab_user_play_info')->field(true)->where('puid',$user['id'])->update($save);

                        //根据分割时间补单订单
                        if (!empty($cut_time)) {
                            $base = new BaseController;
                            $spend = new SpendModel();
                            $map = [];
                            $map['tab_spend.pay_time'] = ['gt', $cut_time];
                            $map['tab_spend.pay_status'] = 1;
                            $map['tab_spend.user_id'] = $user['id'];
                            $map['ps.status'] = 0;
                            $exend['field'] = 'tab_spend.id as spend_id, ps.id as ps_id,tab_spend.promote_id,tab_spend.promote_account,
                            tab_spend.pay_order_number,ps.status as ps_status,tab_spend.pay_status';
                            $exend['join1'][] = ['tab_promote_settlement' => 'ps'];
                            $exend['join1'][] = 'ps.pay_order_number = tab_spend.pay_order_number and ps.status=0';
                            $exend['join1'][] = 'left';
                            $orderLists = $base -> data_list_join_select($spend, $map, $exend);
                            if (!empty($orderLists)) {
                                $cSpend = new SpendController();
                                foreach ($orderLists as $orderKey => $orderVal) {
                                    if($orderVal['ps_status']!='1'){
                                        $cSpend -> deal_promote_info($promote_id_to, $orderVal);
                                    }
                                }
                            }
                        }

                        // 提交事务
                        Db::commit();
                        $success++;

                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $model->where('id', $result)->delete();
                        $error++;
                    }
                } else {
                    $error++;
                }
            }
            $this->success("成功：" . $success . "，失败：" . $error, url('index'));

        }
        $this->assign('user', $user_info);
        $this->assign('id', $id);
        return $this->fetch();
    }


}
