<?php

/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\admin\event;

use app\promote\model\PromoteapplyModel;
use app\promote\model\PromoteappModel;
use app\promote\model\PromotewithdrawModel;
use cmf\controller\BaseController;
use think\Db;

class adminIndexController extends BaseController
{
    public function promote_wait()
    {
        $res = [];
        if (AUTH_PROMOTE == 1) {
            //渠道申请待审核数
            $res['promote_pass'] = Db::table('tab_promote')->where('status', 0)->count();
            //联盟申请
            $res['union_pass'] = Db::table('tab_promote_union')->where('status', 0)->count();
            $map['promote_level'] = 1;
            //收益提现
            $model = new PromotewithdrawModel();
            $map['type'] = 1;
            $map['status'] = 0;
            $res['withdrawal'] = $model->where($map)->count();
            //收益兑换
            $map['type'] = 2;
            $map['status'] = 0;
            $res['exchange'] = $model->where($map)->count();
            //APP分包
            $res['app_packge'] = ( new PromoteappModel())->where('enable_status','=',0)->whereOr('status','=',0)->count();
        }
        return $res;
    }
}
