<?php
/**
 *
 * @author: 鹿文学
 * @Datetime: 2019-03-25 10:41
 */

namespace app\mobile\controller;
use app\common\logic\GameLogic;
use app\common\logic\PayLogic;
use app\recharge\model\CouponRecordModel;
use app\site\model\AdvModel;
use app\site\service\PostService;
use think\Db;


class CouponController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        /*
         * 用户权限判断
         */
        if (AUTH_USER != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买用户权限');
            } else {
                $this->error('请购买用户权限', url('admin/main/index'));
            };
        }
        //登录验证
        $this->isLogin();
    }

    /**
     * @函数或方法说明
     * @代金券列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/2/6 16:56
     */
    public function index()
    {
        $type = $this->request->param('type/d');
        $user_id = session('member_auth.user_id');
        $user = get_user_entity($user_id,false,'promote_id,parent_id');
        $promote_id = $user['promote_id'];
        if(!$type){
            $this->get_reciver_coupon($user_id,$promote_id);
        }else{
            $this->get_my_coupon($user_id,$type);
        }

        $user = get_user_entity(session('member_auth.user_id'),false,'id,account,nickname,head_img,vip_level');
        $this->assign('user', $user);
        return $this->fetch();
    }

    public function coupon_question()
    {
      return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @可领取列表
     * @author: 郭家屯
     * @since: 2020/2/5 16:14
     */
    protected function get_reciver_coupon($user_id=0,$promote_id=0)
    {
        $paylogic = new PayLogic();
        $coupon = $paylogic->get_coupon_lists($user_id,$promote_id);
        $this->assign('coupon',$coupon);
    }

    /**
     * @函数或方法说明
     * @我的优惠券
     * @param int $type
     *
     * @author: 郭家屯
     * @since: 2020/2/5 16:15
     */
    protected function get_my_coupon($user_id=0,$type=1)
    {
        $model = new CouponRecordModel();
        $coupon = $model->get_my_coupon($user_id,$type);
        $this->assign('coupon',$coupon);
    }

    /**
     * @函数或方法说明
     * @领取优惠券
     * @author: 郭家屯
     * @since: 2020/2/5 11:19
     */
    public function getcoupon()
    {
        $coupon_id = $this->request->param('coupon_id');
        if(empty($coupon_id))$this->error('参数错误');
        $user_id = session('member_auth.user_id');
        $logic = new GameLogic();
        $result = $logic->getCoupon($user_id,$coupon_id);
        if($result){
            $this->success('领取成功');
        }else{
            $this->error('领取失败');
        }
    }

}