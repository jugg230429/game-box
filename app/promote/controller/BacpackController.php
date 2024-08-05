<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/26
 * Time: 19:13
 */

namespace app\promote\controller;

use cmf\controller\HomeBaseController;
use think\Request;
use think\Db;

class BacpackController extends HomeBaseController
{

    public function get_bac_androidpack_callback()
    {
        $request = $this->request->param();
        if (empty($request['jobId'])) {
            exit('no jobId');
        }
        $map['jobids'] = ['like', '%' . addcslashes($request['jobId'], '%') . '%'];
        $data = Db::table('tab_promote_bce_package')->where($map)->find();
        if (!empty($data)) {
            //修改渠道分包数据
            $apply_ids = ltrim($data['apply_id'], ',');
            if (empty($apply_ids)) {
                exit('true');
            }
            $tsvmap['status'] = 1;
            $tsvmap['enable_status'] = ['eq', '2'];//准备打包状态
            $tsvmap['pack_type'] = ['eq', '3'];//百度批量打包方式
            $tsvmap['id'] = ['in', $apply_ids];
            $apply_tsv_data = Db::table('tab_promote_apply')->field('id,game_id,promote_id,sdk_version,pack_type')->where($tsvmap)->order('id asc')->select()->toarray();
            if (empty($apply_tsv_data)) {
                exit('true');
            }
            foreach ($apply_tsv_data as $key => $value) {
                $prmote_pack_name = "android/game_package" . $value['game_id'] . "-" . $value['promote_id'] . '.apk';
                $this->updateinfo($value['id'], $prmote_pack_name, ['game_id' => $value['game_id'], 'promote_id' => $value['promote_id']]);
            }
            $save['id'] = $data['id'];
            if ($request['code'] == 'success') {
                $save['status'] = 1;
                $save['mark'] = $request['message'];
            } else {
                $save['mark'] = $request['message'];
            }
            $save['update_time'] = time();
            $res = Db::table('tab_promote_bce_package')->update($save);
            if ($res !== false) {
                exit('true');
            }
        }
    }

    /**
     *修改申请信息
     */
    protected function updateinfo($id, $pack_url, $promote)
    {
        $data['id'] = $id;
        $data['pack_url'] = $pack_url;
        $data['dow_url'] = '/index.php?s=/Home/Down/down_file/game_id/' . $promote['game_id'] . '/promote_id/' . $promote['promote_id'];
        $data['enable_status'] = 1;
        $data['is_upload'] = 1;
        $res = Db::table('tab_promote_apply')->update($data);
        return $res;
    }

    protected function game_source($game_id = '')
    {
        $map['game_id'] = $game_id;
        $data = Db::table('tab_game_source')->field('id,game_id,game_name,file_url')->where($map)->find();
        return $data;
    }
}