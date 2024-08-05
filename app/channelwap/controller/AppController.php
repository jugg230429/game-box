<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yyh
// +----------------------------------------------------------------------
namespace app\channelwap\controller;

use app\promote\model\PromoteappModel;
use app\site\model\AppModel;
use think\Db;
use think\Request;

class AppController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('index/index'));
            };
        }
    }

    /**
     * @函数或方法说明
     * @APP申请列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/2/24 16:38
     */
    public function app_lists()
    {
        $model = new AppModel();
        $data = $model->getPromotelist(PID);
        $this->assign('data_lists',$data);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @申请链接
     * @author: 郭家屯
     * @since: 2020/2/24 17:23
     */
    public function apply()
    {
        if($this->request->isPost()){
            $app_id = $this->request->param('app_id');
            $model = new PromoteappModel();
            $app_apply = $model->where('app_id',$app_id)->where('promote_id',PID)->find();
            if($app_apply){
                $this->error('您已申请过了');
            }
            $result = $model->apply($app_id,PID);
            if($result){
                $config = Db::table('tab_promote_config')->field('status')->where(array('name' => 'promote_auto_audit_app'))->find();
                if($config['status'] == 1){
                    $this->success('申请成功');
                }else{
                    $this->success('申请提交成功，请等待审核');
                }
            }else{
                $this->error('申请失败');
            }
        }else{
            $this->error('申请失败');
        }
    }
}