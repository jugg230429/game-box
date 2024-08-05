<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/25
 * Time: 14:35
 */

namespace app\member\controller;

use app\member\model\UserInvitationModel;
use app\member\model\UserInvitationRecordModel;
use app\recharge\model\CouponModel;
use cmf\controller\AdminBaseController;
use app\common\controller\BaseController;
use think\Db;
use think\Request;

class InvitationController extends AdminBaseController
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
     * @领取记录
     * @author: 郭家屯
     * @since: 2020/2/15 13:54
     */
    public function record()
    {
        $base = new BaseController();
        $model = new UserInvitationModel();
        $account = $this->request->param('user_account/s');
        if ($account) {
            $map['tab_user_invitation.user_account'] = ['like', '%' . $account . '%'];
            $map1['user_account'] = ['like', '%' . $account . '%'];
        }
        $extend['field'] = 'tab_user_invitation.id,tab_user_invitation.user_account,count(distinct tab_user_invitation.invitation_id) as account_num,group_concat(distinct tab_user_invitation.invitation_account) as invitation_account,count(if(r.type=1,true,null)) as register_num,count(if(r.type=2,true,null)) as spend_num';
        $extend['order'] = 'tab_user_invitation.id desc';
        $extend['join1'] = [['tab_user_invitation_record'=>'r'],'tab_user_invitation.user_id=r.user_id','left'];
        $extend['group'] = 'tab_user_invitation.user_id';
        $data = $base->data_list_join($model, $map, $extend);
        // 获取分页显示
        $page = $data->render();
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
        }
        $this->assign('data_lists', $data);
        $this->assign("page", $page);
        $recordmodel = new UserInvitationRecordModel();
        $totalaccount = $model->where($map)->count();
        $totalregister = $recordmodel->where($map1)->where('type',1)->count();
        $totalspend = $recordmodel->where($map1)->where('type',2)->count();
        $this->assign('totalaccount', $totalaccount);
        $this->assign('totalregister', $totalregister);
        $this->assign('totalspend', $totalspend);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @奖励设置
     * @author: 郭家屯
     * @since: 2020/2/15 15:54
     */
    public function award()
    {
        $model = new CouponModel();
        $data = $model->get_award_lists();
        if(!$data){
            $data[0] = ['id'=>1,'coupon_name'=>'注册奖励代金券','mold'=>0,'game_id'=>0,'game_name'=>'','coupon_type'=>1,'category'=>'邀请注册奖励','status'=>0];
            $data[1] = ['id'=>2,'coupon_name'=>'充值奖励代金券','mold'=>0,'game_id'=>0,'game_name'=>'','coupon_type'=>2,'category'=>'邀请充值奖励','status'=>0];
            $data[2] = ['id'=>3,'coupon_name'=>'被邀请奖励代金券1','mold'=>0,'game_id'=>0,'game_name'=>'','coupon_type'=>3,'category'=>'被邀请奖励','status'=>0];
            $data[3] = ['id'=>4,'coupon_name'=>'被邀请奖励代金券2','mold'=>0,'game_id'=>0,'game_name'=>'','coupon_type'=>3,'category'=>'被邀请奖励','status'=>0];
            $data[4] = ['id'=>5,'coupon_name'=>'被邀请奖励代金券3','mold'=>0,'game_id'=>0,'game_name'=>'','coupon_type'=>3,'category'=>'被邀请奖励','status'=>0];
        }
        $this->assign("data_lists", $data);
        return $this->fetch();
    }

    /**
     * [修改折扣状态]
     * @author 郭家屯[gjt]
     */
    public function changeStatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('请选择要操作的数据');
        $data = $this->request->param();
        $logic = new CouponModel();
        $result = $logic->changeStatus($data);
        if ($result) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * @函数或方法说明
     * @编辑
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/2/15 16:25
     */
    public function edit()
    {
        $id = $this->request->param('id');
        if(empty($id))$this->error('参数错误');
        $model = new CouponModel();
        $detail = $model->detail($id);
        if(empty($detail)){
            $list[0] = ['id'=>1,'limit_money'=>'','money'=>'','start_time'=>0,'end_time'=>0,'coupon_name'=>'注册奖励代金券','coupon_type'=>1,'category'=>'邀请注册奖励','status'=>0,'create_time'=>time(),'mold'=>0,'spend_limit'=>0,'game_id'=>0,'game_name'=>''];
            $list[1] = ['id'=>2,'limit_money'=>'','money'=>'','start_time'=>0,'end_time'=>0,'coupon_name'=>'充值奖励代金券','coupon_type'=>2,'category'=>'邀请充值奖励','status'=>0,'create_time'=>time(),'mold'=>0,'spend_limit'=>0,'game_id'=>0,'game_name'=>''];
            $list[2] = ['id'=>3,'limit_money'=>'','money'=>'','start_time'=>0,'end_time'=>0,'coupon_name'=>'被邀请奖励代金券1','coupon_type'=>3,'category'=>'被邀请奖励','status'=>0,'create_time'=>time(),'mold'=>0,'spend_limit'=>0,'game_id'=>0,'game_name'=>''];
            $list[3] = ['id'=>4,'limit_money'=>'','money'=>'','start_time'=>0,'end_time'=>0,'coupon_name'=>'被邀请奖励代金券2','coupon_type'=>3,'category'=>'被邀请奖励','status'=>0,'create_time'=>time(),'mold'=>0,'spend_limit'=>0,'game_id'=>0,'game_name'=>''];
            $list[4] = ['id'=>5,'limit_money'=>'','money'=>'','start_time'=>0,'end_time'=>0,'coupon_name'=>'被邀请奖励代金券3','coupon_type'=>3,'category'=>'被邀请奖励','status'=>0,'create_time'=>time(),'mold'=>0,'spend_limit'=>0,'game_id'=>0,'game_name'=>''];
            $detail = $list[$id-1];
        }
        if($this->request->isPost()){
            $data = $this->request->param();
            $result = $model->edit($data,$list);
            if($result){
                $this->success('修改成功',url('award'));
            }else{
                $this->error('修改失败');
            }
        }
        $this->assign('data',$detail);
        return $this->fetch();
    }
}