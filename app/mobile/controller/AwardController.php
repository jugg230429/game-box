<?php
/**
 *
 * @author: 鹿文学
 * @Datetime: 2019-03-25 10:41
 */

namespace app\mobile\controller;
use app\common\logic\AwardLogic;
use app\member\model\UserAwardModel;
use think\Db;


class AwardController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        //登录验证
    }

    /**
     * @函数或方法说明
     * @抽奖页面
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/2/6 16:56
     */
    public function index()
    {
        if(cmf_get_option('award_set')['status'] != 1){
            $this->error('抽奖未开启');
        }
        $logic = new AwardLogic();
        $award = $logic->getaward();//奖品列表
        $this->assign('award',$award);
        //获取抽奖次数
        $award_count = $logic->get_award_count(UID);
        $this->assign('award_count',$award_count<0?0:$award_count);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @抽奖方法
     * @author: 郭家屯
     * @since: 2020/8/12 15:44
     */
    public function draw()
    {
        $this->isLogin();
        if($this->request->isPost()){
            $logic = new AwardLogic();
            $result = $logic->draw(UID);
        }else{
            $result['code'] = 0;
            $result['msg'] = '请求失败';
        }
        return json($result);
    }


}