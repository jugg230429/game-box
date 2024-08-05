<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */
namespace app\common\logic;

use app\promote\model\PromoteunionModel;
use think\Db;

/**
 * 文档模型逻辑层公共模型
 * 所有逻辑层模型都需要继承此模型
 */
class AppLogic{

    public function down_app($device,$promote_id=0)
    {
        $map['version'] = $device==2?2:1;
        $map['file_url'] = ['neq',''];
//        if($device==2){
//            $map['tab_app.plist_url'] = ['neq',''];
//        }
        $field = 'tab_app.*';
        if (AUTH_PROMOTE == 1) {
            $serverhost = $_SERVER['HTTP_HOST'];
            $serverhostarr = [$serverhost, "http://" . $serverhost, "https://" . $serverhost];
            $union_model = new PromoteunionModel();
            $host = $union_model->where('domain_url', 'in', $serverhostarr)->find();
            $promote_id = $host['union_id'] ? $host['union_id'] : $promote_id;
        }
        $join = null;
        if($promote_id){
            $field = $field.',pa.plist_url as paplist_url,pa.dow_url,pa.type as patype';
            $join[] = ['tab_promote_app'=>'pa'];
            $join[] = 'tab_app.id = pa.app_id';
            $join[] = 'left';
            $map['pa.promote_id'] = $promote_id;
            $map['dow_url'] = ['neq',''];
//            if($device==2){
//                $map['pa.plist_url'] = ['neq',''];
//            }
            $map['pa.status'] = 1;
            $map['pa.enable_status'] = 1;
        }
        $appdata = Db::table('tab_app')
                    ->field($field)
                    ->where($map)
                    ->join($join[0],$join[1],$join[2])
                    ->find();
        return ['app_data'=>$appdata,'promote_id'=>$promote_id];
    }

}