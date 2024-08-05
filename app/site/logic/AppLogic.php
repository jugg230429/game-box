<?php
/**
 * @Copyright (c) 2019  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 */
namespace app\site\logic;


use app\game\controller\PlistController;
use app\promote\model\PromoteappModel;
use app\site\model\AppModel;
use think\Db;

/**
 * 文档模型逻辑层公共模型
 * 所有逻辑层模型都需要继承此模型
 */
class AppLogic{

    /**
     * @函数或方法说明
     * @更新app原包
     * @param array $request
     *
     * @author: 郭家屯
     * @since: 2020/2/20 10:53
     */
    public function update_source($request=[])
    {
        if(empty($request['type'])){
            if($request['id'] == 2){
                $plist = new PlistController();
                $request['plist_url'] = $plist->create_plist_app(cmf_get_option('app_set')['app_version'],$request['id'],0,$request['bao_name'],$request['file_url']);
            }
            $request['file_size'] = cmf_file_size_format(filesize("./upload/".$request['file_url']));
        }else{
            $request['file_size'] =  $request['file_size']."MB";
        }
        $request['op_id'] = cmf_get_current_admin_id();
        $request['op_account'] = cmf_get_current_admin_name();
        $request['create_time'] = time();
        $model = new AppModel();
        $app = $model->field(true)->where('id',$request['id'])->find();
        if($app){
            $result = $model->field(true)->where('id',$request['id'])->update($request);
        }else{
            $result = $model->field(true)->insert($request);
        }
        if($result !== false){
            if(empty($request['type'])) {
                $pamodel = new PromoteappModel();
                $pamodel -> where('app_id', $request['id']) -> where('status', 1) -> setField('enable_status', 2);
            }
            return true;
        }else{
            return false;
        }
    }










}
