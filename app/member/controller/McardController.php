<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/25
 * Time: 14:35
 */

namespace app\member\controller;

use app\member\model\UserMemberModel;
use app\recharge\model\CouponModel;
use cmf\controller\AdminBaseController;
use app\common\controller\BaseController;
use think\Db;
use think\Request;

class McardController extends AdminBaseController
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

    /**
     * @函数或方法说明
     * @会员购买记录
     * @author: 郭家屯
     * @since: 2020/2/15 13:54
     */
    public function lists()
    {
        $base = new BaseController();
        $model = new UserMemberModel();
        $map['pay_status'] = 1;
        $account = $this->request->param('user_account/s');
        if ($account) {
            $map['user_account'] = ['like', '%' . $account . '%'];
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
        $extend['field'] = 'pay_order_number,user_account,promote_account,pay_amount,pay_way,member_name,days,free_days,spend_ip,create_time,end_time';
        $extend['order'] = 'id desc';
        $data = $base->data_list($model, $map, $extend);
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
        }
        // 获取分页显示
        $page = $data->render();
        $this->assign('data_lists', $data);
        $this->assign("page", $page);
        $total = $model->where($map)->sum('pay_amount');
        $this->assign('total', $total);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @尊享卡设置
     * @author: 郭家屯
     * @since: 2020/8/10 20:04
     */
    public function set()
    {
        $data = cmf_get_option('mcard_set');
        $this->assign('data',$data);
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
            if($data['status'] == 1 && empty($data['coupon_id'])){
                $this->error('请选择代金券');
            }
            //修改代金券属性
            if($data['coupon_id']){
                Db::table('tab_coupon')->where('coupon_type',4)->setField('coupon_type',0);
                Db::table('tab_coupon')->where('id',$data['coupon_id'])->setField('coupon_type',4);
            }
            $set_type = $data['set_type'];
            cmf_set_option($set_type, $data);
            write_action_log("尊享卡设置");
            $this->success("保存成功！");
        }
    }




}