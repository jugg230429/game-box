<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 9:35
 */

namespace app\media\controller;

use app\common\logic\AppLogic;
use app\site\service\PostService;
use app\site\model\PortalPostModel;
use think\Db;

class DownloadController extends BaseController
{
    /**
     *
     * @author 郭家屯[gjt]
     */
    public function index()
    {
        $promote_id = $this->request->param('promote_id');
        $logic = new AppLogic();
        $res['and'] = $logic->down_app(1,$promote_id);
        $res['ios'] = $logic->down_app(2,$promote_id);
        $this->assign('app_data',$res);
        return $this->fetch();
    }

}