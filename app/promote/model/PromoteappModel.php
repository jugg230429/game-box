<?php

namespace app\promote\model;

use think\Model;
use think\Db;

class PromoteappModel extends Model
{

    protected $table = 'tab_promote_app';

    protected $autoWriteTimestamp = true;

    /**
     * @函数或方法说明
     * @申请app
     * @param int $app_id
     *
     * @author: 郭家屯
     * @since: 2020/2/24 17:25
     */
    public function apply($app_id=0,$promote_id=0,$param = [])
    {
        $app = Db::table('tab_app')->where('id',$app_id)->find();
        $config = Db::table('tab_promote_config')->field('status')->where(array('name' => 'promote_auto_audit_app'))->find();
        $data['promote_id'] = $promote_id;
        $data['app_id'] = $app['id'];
        $data['app_name'] = $app['name'];
        $data['app_version'] = $app['version'];
        $data['apply_time'] = time();
        if($param['is_user_define']=='1'){
            $data['is_user_define'] = '1';
            $data['app_new_name'] = $param['app_new_name'];
            $data['app_new_icon'] = $param['app_new_icon'];
            $data['start_img1'] = $param['start_img1'];
            $data['start_img2'] = $param['start_img2'];
        }

        if($app['type'] == 1){
            $data['type'] = 1;
        }
        if($config['status'] == 1){
            $data['status'] = 1;
            if($app['type'] == 1){
                $data['enable_status'] = 1;
                $info['MCHPromoteID'] = (string)$promote_id;
                $url = $app['file_url'] . '?appenddata=' . urlencode(json_encode($info));
                $data['dow_url'] = $url;
            }else{
                $data['enable_status'] = 2;
            }
        }
        $result = $this->insert($data);
        if($result){
            return true;
        }else{
            return false;
        }
    }
}