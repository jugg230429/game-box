<?php

namespace app\promote\logic;

use app\game\controller\PlistController;
use app\promote\model\PromoteappModel;

class PromoteapplyLogic
{


    /**
     * @获取渠道APP申请信息
     *
     * @since: 2020/11/12 14:58
     * @author: zsl
     */
    public function info($id)
    {
        $mPromoteApply = new PromoteappModel();
        $field = "";
        $where = [];
        $where['id'] = $id;
        $where['status'] = 1;
        $info = $mPromoteApply -> field($field) -> where($where) -> find();
        return $info;
    }


    /**
     * @更新渠道APP申请信息
     *
     * @author: zsl
     * @since: 2020/11/12 16:29
     */
    public function updateInfo($param)
    {

        $info = $this -> info($param['id']);
        if ($param['is_user_define'] == '0') {
            // 官方包
            if ($info['is_user_define'] == '1') {
                //若是从自定义更改为官方包,则准备重新打包
                $info -> dow_url = '';
                $info -> plist_url = '';
                $info -> enable_status = 2;
            }
            $info -> is_user_define = 0;
        } else {
            // 自定义包
            $ios_flag = $param['ios'] ?? 0;
            if($ios_flag == 1){
                // ios盒子包自定义
                $info -> is_user_define = 1;
                // $info -> app_new_name = $param['app_new_name'];
                $info -> dow_url = $param['dow_url'];
                $info -> type = $param['type'];
                $info -> file_size2 = $param['file_size2'];
                $info -> app_new_icon = $param['app_new_icon'];
                $info -> super_url = $param['super_url'];
                $info -> ios_version = $param['ios_version'];
                $info -> yidun_business_id = $param['yidun_business_id'];
                $info -> enable_status = 1;//打包成功

                if($param['type'] == 0){
                    // 原包上传
                    $info -> file_size2 = cmf_file_size_format(filesize("./upload/".$param['dow_url']));
                    // 生成plist文件  plist_url
                    $plist = new PlistController();
                    // $tmp_plist = $plist->create_plist_app(cmf_get_option('app_set')['app_version'],$request['id'],0,$request['bao_name'],$request['file_url']);
                    $promote_id = $info -> promote_id;
                    $app_id = 2;  // 2: ios
                    if($promote_id > 0 && !empty($param['dow_url'])){
                        $single_img = $param['app_new_icon'];
                        // var_dump($param['dow_url']);exit;
                        $tmp_plist = $plist->create_plist_app($version='',$app_id, $promote_id,'',$param['dow_url'],$single_img);

                        $info -> plist_url = $tmp_plist;
                    }
                }

            }else{
                // 安卓包自定义 (判断内容有没有改变 如果有改变则 自动重新打包)
                $change_flag = 0;
                if($info -> app_new_name != $param['app_new_name']){
                    $change_flag = 1;
                }
                if($info -> app_new_icon != $param['app_new_icon']){
                    $change_flag = 1;
                }
                if($info -> start_img1 != $param['start_img1']){
                    $change_flag = 1;
                }
                if($info -> start_img2 != $param['start_img2']){
                    $change_flag = 1;
                }
                if($change_flag == 1){
                    // 则重新打包
                    $info -> enable_status = 2;
                }

                $info -> is_user_define = 1;
                $info -> app_new_name = $param['app_new_name'];
                $info -> app_new_icon = $param['app_new_icon'];
                $info -> start_img1 = $param['start_img1'];
                $info -> start_img2 = $param['start_img2'];
            }

        }
        $res = $info -> isUpdate(true) -> allowField(true) -> save();
        return $res;
    }


}
