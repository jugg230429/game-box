<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\promote\controller;

use app\common\controller\BaseController;
use app\game\model\GameAttrModel;
use app\promote\logic\PromoteapplyLogic;
use app\promote\model\PromoteappModel;
use app\promote\model\PromoteLevelModel;
use app\promote\validate\PromoteAppValidate;
use cmf\controller\AdminBaseController;
use app\promote\model\PromoteModel;
use app\promote\model\PromoteapplyModel;
use cmf\lib\Storage;
use think\Request;
use think\Db;

class PromoteapplyController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_PROMOTE != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买渠道权限');
            } else {
                $this->error('请购买渠道权限', url('admin/main/index'));
            };
        }
        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('admin/main/index'));
            };
        }
    }

    /**
     * [渠道列表]
     * @return mixed
     * @author yyh
     */
    public function lists()
    {
        if(PERMI <1 ){
            return $this->redirect('ylists');
        }elseif(PERMI == 2){
            return $this->redirect('hlists');
        }
        $base = new BaseController();
        $model = new PromoteapplyModel();
        //添加搜索条件
        $data = $this->request->param();
        $promote_id = $data['promote_id'];
        if ($promote_id) {
            $map['promote_id'] = $promote_id;
        }
        $parent_id = $data['parent_id'];//上线渠道
        if ($parent_id) {
            $map['p.top_promote_id|p.parent_id|p.id'] = $parent_id;
        }
        $game_id = $data['game_id'];
        if ($game_id) {
            $map['game_id'] = $game_id;
        }

        $status = $data['status'];
        if ($status != '') {
            $map['tab_promote_apply.status'] = $status;
        }

        $enable_status = $data['enable_status'];
        if ($enable_status != '') {
            $map['enable_status'] = $enable_status;
        }

        $dow_status = $data['dow_status'];
        if ($dow_status != '') {
            $map['g.dow_status'] = $dow_status;
        }

        $sdk_version = $data['sdk_version'];
        if ($sdk_version) {
            $map['g.sdk_version'] = $sdk_version;
        } else {
            $map['g.sdk_version'] = 1;
        }
        $exend['order'] = 'apply_time desc';
        $exend['field'] = 'tab_promote_apply.id,tab_promote_apply.user_limit_discount,game_id,g.game_name,pack_mark,promote_id,apply_time,tab_promote_apply.status,enable_status,dispose_time,promote_ratio,promote_money,g.dow_status,g.ratio,g.money,tab_promote_apply.pack_type,dow_url,relation_game_id,is_h5_share_show';
        $exend['join1'][] = ['tab_game' => "g"];
        $exend['join1'][] = 'g.id = tab_promote_apply.game_id';
        $exend['join2'][] = ['tab_promote' => "p"];
        $exend['join2'][] = 'tab_promote_apply.promote_id = p.id';
        $data = $base->data_list_join($model, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        //自动审核
        $autostatus = Db::table('tab_promote_config')->where(array('name' => 'promote_auto_audit_apply'))->value('status');
        $this->assign('autostatus', $autostatus);
        return $this->fetch();
    }

    /**
     * [渠道列表]
     * @return mixed
     * @author yyh
     */
    public function hlists()
    {
        $base = new BaseController();
        $model = new PromoteapplyModel();
        //添加搜索条件
        $data = $this->request->param();
        $promote_id = $data['promote_id'];
        if ($promote_id) {
            $map['promote_id'] = $promote_id;
        }
        $parent_id = $data['parent_id'];//上线渠道
        if ($parent_id) {
            $map['p.top_promote_id|p.parent_id|p.id'] = $parent_id;
        }
        $game_id = $data['game_id'];
        if ($game_id) {
            $map['game_id'] = $game_id;
        }

        $status = $data['status'];
        if ($status != '') {
            $map['tab_promote_apply.status'] = $status;
        }
        $map['g.sdk_version'] = 3;
        $exend['order'] = 'apply_time desc';
        $exend['field'] = 'tab_promote_apply.id,tab_promote_apply.user_limit_discount,game_id,and_status,ios_status,g.game_name,pack_mark,promote_id,apply_time,tab_promote_apply.status,enable_status,dispose_time,promote_ratio,promote_money,g.dow_status,g.ratio,g.money,tab_promote_apply.pack_type,dow_url,relation_game_id,is_h5_share_show';
        $exend['join1'][] = ['tab_game' => "g"];
        $exend['join1'][] = 'g.id = tab_promote_apply.game_id';
        $exend['join2'][] = ['tab_promote' => "p"];
        $exend['join2'][] = 'tab_promote_apply.promote_id = p.id';
        $data = $base->data_list_join($model, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        //自动审核
        $autostatus = Db::table('tab_promote_config')->where(array('name' => 'promote_auto_audit_apply'))->value('status');
        $this->assign('autostatus', $autostatus);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @页游分包列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/9/22 9:45
     */
    public function ylists()
    {
        $base = new BaseController();
        $model = new PromoteapplyModel();
        //添加搜索条件
        $data = $this->request->param();
        $promote_id = $data['promote_id'];
        if ($promote_id) {
            $map['promote_id'] = $promote_id;
        }
        $parent_id = $data['parent_id'];//上线渠道
        if ($parent_id) {
            $map['p.top_promote_id|p.parent_id|p.id'] = $parent_id;
        }
        $game_id = $data['game_id'];
        if ($game_id) {
            $map['game_id'] = $game_id;
        }

        $status = $data['status'];
        if ($status != '') {
            $map['tab_promote_apply.status'] = $status;
        }
        $map['g.sdk_version'] = 4;
        $exend['order'] = 'apply_time desc';
        $exend['field'] = 'tab_promote_apply.id,tab_promote_apply.user_limit_discount,game_id,and_status,ios_status,g.game_name,pack_mark,promote_id,apply_time,tab_promote_apply.status,enable_status,dispose_time,promote_ratio,promote_money,g.dow_status,g.ratio,g.money,tab_promote_apply.pack_type,dow_url,relation_game_id';
        $exend['join1'][] = ['tab_game' => "g"];
        $exend['join1'][] = 'g.id = tab_promote_apply.game_id';
        $exend['join2'][] = ['tab_promote' => "p"];
        $exend['join2'][] = 'tab_promote_apply.promote_id = p.id';
        $data = $base->data_list_join($model, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        //自动审核
        $autostatus = Db::table('tab_promote_config')->where(array('name' => 'promote_auto_audit_apply'))->value('status');
        $this->assign('autostatus', $autostatus);
        return $this->fetch();
    }

    /**
     * [修改审核状态]
     * @author yyh
     */
    public function changeStatus()
    {
        $data = $this->request->param();
        $ids = $data['ids'];
        if (empty($ids)) $this->error('请选择要操作的数据');
        if (!is_array($ids)) {
            $id = $ids;
            $ids = [];
            $ids[] = $id;
        }
        $status = $this->request->param('status', 0, 'intval');
        $save['status'] = $status == 1 ? 0 : 1;
        $save['dispose_id'] = cmf_get_current_admin_id();
        $save['dispose_time'] = time();
        $model = new PromoteapplyModel();
        Db::startTrans();
        foreach ($ids as $key => $value) {
            $apply = Db::table('tab_promote_apply')->field('sdk_version,game_id')->find($value);
            if($apply['sdk_version'] == 3){
                $and_source = Db::table('tab_game_source')->where(['game_id'=>$apply['game_id']])->where('file_type',1)->find();
                if($and_source){
                    $save['and_status'] = 2;
                }
                $ios_source = Db::table('tab_game_source')->where(['game_id'=>$apply['game_id']])->where('file_type',2)->find();
                if($ios_source){
                    $save['ios_status'] = 2;
                }
            }
            $map['id'] = $value;
            $result = $model->where($map)->update($save);
            if (!$result) {
                Db::rollback();
                $this->error('操作失败');
            }
        }
        Db::commit();
        $this->success('操作成功');
    }

    // 修改H5分享页面是否显示
    // 控制H5分享页面是否显示, 0 显示, 1隐藏, (默认0)
    public function changeh5shareshow()
    {
        $data = $this -> request -> param();
        $id = $data['id'];
        $ids = $data['ids'];
        if (empty($id) && empty($ids)) $this -> error('请选择要操作的数据');
        $is_h5_share_show = $data['is_h5_share_show'] ?? - 1;
        if ($is_h5_share_show == - 1) {
            $this -> error('系统繁忙,请销后再试.');
        }
        if (!empty($id)) {
            $applyInfo = Db ::table('tab_promote_apply') -> field('id,promote_id,game_id') -> where('id', $id) -> find();
            $promote_id = $applyInfo['promote_id'];
            $relation_game_id = Db ::table('tab_game') -> where(['id' => $applyInfo['game_id']]) -> value('relation_game_id');
            // 根据 game_id 去查询关联的安卓或者ios的游戏  一并隐藏
            $two_game_info = Db ::table('tab_game') -> where(['relation_game_id' => $relation_game_id]) -> field('id,game_name') -> select();
            $ids = [];
            foreach ($two_game_info as $k1 => $v1) {
                $ids[] = $v1['id'];
            }
            $tmp_map = [];
            $tmp_map['game_id'] = ['in', $ids];
            $tmp_map['promote_id'] = $promote_id;
            $update_res = Db ::table('tab_promote_apply') -> where($tmp_map) -> update(['is_h5_share_show' => $is_h5_share_show]);
            if ($update_res) {
                $this -> success('操作成功');
            }
            $this -> error('操作失败');
        }
        if (!empty($ids)) {

            $error_num = 0;
            $success_num = 0;
            foreach ($ids as $id) {

                $applyInfo = Db ::table('tab_promote_apply') -> field('id,promote_id,game_id') -> where('id', $id) -> find();
                $promote_id = $applyInfo['promote_id'];
                $relation_game_id = Db ::table('tab_game') -> where(['id' => $applyInfo['game_id']]) -> value('relation_game_id');
                $two_game_info = Db ::table('tab_game') -> where(['relation_game_id' => $relation_game_id]) -> field('id,game_name') -> select();
                $gameIds = [];
                foreach ($two_game_info as $k1 => $v1) {
                    $gameIds[] = $v1['id'];
                }
                $tmp_map = [];
                $tmp_map['game_id'] = ['in', $gameIds];
                $tmp_map['promote_id'] = $promote_id;
                $update_res = Db ::table('tab_promote_apply') -> where($tmp_map) -> update(['is_h5_share_show' => $is_h5_share_show]);
                if (false === $update_res) {
                    $error_num += 1;
                } else {
                    $success_num += 1;
                }
            }
            if ($error_num == 0) {
                $this -> success('操作成功');
            } else {
                $this -> success($success_num . '条数据操作成功,' . $error_num . '条数据操作失败');
            }
        }

    }

    /**
     * [set_config_auto_audit 设置渠道]
     * @param string $val [description]
     * @param string $config_key [description]
     * @author [yyh] <[<email address>]>
     */
    public function set_config_auto_audit_apply($status = '', $name = 'promote_auto_audit_apply')
    {
        $config['status'] = $status == 0 ? 1 : 0;
        $res = Db::table('tab_promote_config')->where(array('name' => $name))->update($config);

        if ($res !== false) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 设置分成比例
     */
    public function setRatioMoney()
    {
        $data = $this->request->param();
        $applyModel = new PromoteapplyModel();
        $data['game_id'] = $data['game_id'];
        $data['promote_id'] = $data['promote_id'];
        $data['field'] = $data['field'];
        $data['value'] = $data['value'];
        if ($applyModel->setRatioMoney($data) !== false) {
            $this->success("修改成功");
        } else {
            $this->error("修改失败");
        }

    }

    /**
     * [allpackage 批量渠道打包]
     * @param  [type]  $ids  [description]
     * @param integer $p [description]
     * @param integer $type [description]
     * @return [type]        [description]
     * @author [yyh] <[<email address>]>
     */
    public function allpackage($ids = null, $p = 1, $type = 1)
    {

        $data = $this->request->param();
        $ids = $data['ids'];
        $type = in_array($data['type'], [1, 2, 3,4]) ? $data['type'] : 2;
        if($type==3){
            $storage = cmf_get_option('storage');
            if(strtolower($storage['type'])!='baidubac'){
                $this->error('网站未开启百度云存储');
            }
        }
        if (empty($ids)) $this->error("打包数据不存在", url('promoteapply/lists'));
        $successNum = 0;
        $errorNuem = 0;
        $applyModel = new PromoteapplyModel();
        $tsvi = 0;
        foreach ($ids as $key => $value) {
            $apply_data = $applyModel->where(['enable_status' => ['neq', 2]])->find($value);
            if (empty($apply_data) || $apply_data["status"] != 1) {
                $errorNuem++;
                continue;
            }
            $game = get_game_entity($apply_data["game_id"],'ios_game_address,down_port,platform_id');
            if($type == 4){
                if($game['down_port'] != 3){
                    $errorNuem++;
                    continue;
                }
                $map['id'] = $value;
                $map['status'] = 1;
                $save['pack_type'] = 4;
                $save['enable_status'] = 1;
                $save['pack_id'] = cmf_get_current_admin_id();
                $save['pack_time'] = time();
                // 第三方超级签游戏打包，在渠道ID后面拼接本平台渠道ID
                if($game['platform_id'] > 0){
                    if(empty($game['ios_game_address'])){
                        $errorNuem++;
                        continue;
                    }
                    $third_url = urldecode($game['ios_game_address']);
                    $third_params = explode('?appenddata=',$third_url);
                    $params = json_decode($third_params[1],true);
                    if(empty($params)){
                        $errorNuem++;
                        continue;
                    }
                    $params['MCHPromoteID'] = $params['MCHPromoteID'] . '_XgPid_' . $apply_data['promote_id'];
                    $url = $third_params[0] . '?appenddata=' . urlencode(json_encode($params));
                }else{
                    $info['MCHPromoteID'] = (string)$apply_data['promote_id'];
                    $info['XiguSuperSignVersion'] = (string)super_sign_version($apply_data['game_id']);
                    $url = $game['ios_game_address'] . '?appenddata=' . urlencode(json_encode($info));
                }
                $save['pack_url'] = $save['dow_url'] = $url;
                $applyModel->where($map)->update($save);
                $successNum++;
            }else{
                if($game['down_port'] == 3){
                    $errorNuem++;
                    continue;
                }
                $source_file = $this->game_source($apply_data["game_id"]);
                if (empty($source_file) || !file_exists(ROOT_PATH . 'public/upload/' . $source_file['file_url'])) {//验证原包是否存在
                    $errorNuem++;
                    continue;
                }
                $map['id'] = $value;
                $map['status'] = 1;
                $save['pack_type'] = $type;

                if ($type != 2) {
                    $save['enable_status'] = 2;
                } else {
                    $save['enable_status'] = 1;
                }
                $save['pack_id'] = cmf_get_current_admin_id();
                $save['pack_time'] = time();
                $applyModel->where($map)->update($save);
                $successNum++;
            }

        }

        $msg = "已加入打包队列，刷新此页面可查看当前打包状态;\r\n加入成功：" . $successNum . "个。" . "失败：" . $errorNuem . "个";
        $this->success($msg);
        exit;
    }
    public function delete()
    {
        $data = $this->request->param();
        $ids = $data['ids'];
        if (empty($ids)) $this->error('请选择要操作的数据');
        if (!is_array($ids)) {
            $id = $ids;
            $ids = [];
            $ids[] = $id;
        }
        $success = 0;
        $fail = 0;
        $model = new PromoteapplyModel();
        foreach ($ids as $key => $value) {
            $apply = $model->field('sdk_version,plist_url,pack_url,and_url,ios_url,enable_status,pack_type')->where('id',$value)->find();
            if(!$apply){
                $fail++;
                continue;
            }
            $apply = $apply->toArray();
            $result = $model->delete($value);
            if ($result) {
                if($apply['enable_status'] == 1 && $apply['pack_type'] == 1){
                    $this->delete_file($apply);
                }
                $success++ ;
            }else{
                $fail++;
            }
        }
        $this->success('删除成功：'.$success."条，失败：".$fail."条");
    }

    /**
     * @函数或方法说明
     * @删除包
     * @param array $apply
     *
     * @author: 郭家屯
     * @since: 2020/8/7 16:49
     */
    protected function delete_file($apply=[])
    {
        if($apply['sdk_version'] ==1){
            $file_url = $apply['and_url'] ? : $apply['pack_url'];
        }else{
            $file_url = $apply['ios_url'] ? : $apply['pack_url'];
        }
        $storage = cmf_get_option('storage');
        if ($storage['type'] != 'Local') { //  增加存储驱动
            $storage = new Storage($storage['type'], $storage['storages'][$storage['type']]);
            $storage->delete_file($file_url);
        }else{
            if ($apply['file_url']) {
                unlink('/upload/'.$file_url);
            }
            if ($apply['plist_url']) {
                unlink('/upload/'.$apply['plist_url']);
            }
        }
    }

    protected function game_source($game_id = '')
    {
        // $model = new \app\game\model\GamesourceModel;//不可命名空间前use  防止不购买游戏权限 找不到文件
        $map['game_id'] = $game_id;
        $data = Db::table('tab_game_source')->where($map)->find();
        return $data;
    }

    /**
     * @函数或方法说明
     * @APP分包
     * @author: 郭家屯
     * @since: 2020/2/20 14:02
     */
    public function app_list()
    {
        $base = new BaseController();
        $model = new PromoteappModel();
        //添加搜索条件
        $data = $this->request->param();
        $promote_id = $data['promote_id'];
        if ($promote_id) {
            $map['promote_id'] = $promote_id;
        }

        $status = $data['status'];
        if ($status != '') {
            $map['status'] = $status;
        }

        $enable_status = $data['enable_status'];
        if ($enable_status != '') {
            $map['enable_status'] = $enable_status;
        }

        $app_version = $data['app_version'];
        if ($app_version) {
            $map['app_version'] = $app_version;
        }
        $exend['order'] = 'apply_time desc';
        $exend['field'] = 'id,promote_id,app_version,apply_time,status,enable_status,dispose_time,is_user_define';
        $data = $base->data_list($model, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        //自动审核
        $autostatus = Db::table('tab_promote_config')->where(array('name' => 'promote_auto_audit_app'))->value('status');
        $this->assign('autostatus', $autostatus);
        return $this->fetch();
    }

    /**
     * [set_config_auto_audit 设置渠道]
     * @param string $val [description]
     * @param string $config_key [description]
     * @author [yyh] <[<email address>]>
     */
    public function set_config_auto_audit_app($status = '', $name = 'promote_auto_audit_app')
    {
        $config['status'] = $status == 0 ? 1 : 0;
        $res = Db::table('tab_promote_config')->where(array('name' => $name))->update($config);

        if ($res !== false) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * @函数或方法说明
     * @修改审核状态
     * @author: 郭家屯
     * @since: 2020/2/20 14:41
     */
    public function changeAppStatus()
    {
        $data = $this->request->param();
        $ids = $data['ids'];
        if (empty($ids)) $this->error('请选择要操作的数据');
        if (!is_array($ids)) {
            $id = $ids;
            $ids = [];
            $ids[] = $id;
        }
        $save['dispose_id'] = cmf_get_current_admin_id();
        $save['dispose_time'] = time();
        $save['status'] = 1;
        $model = new PromoteappModel();
        Db::startTrans();
        foreach ($ids as $key => $value) {
            $map['id'] = $value;
            $apply = $model->field('promote_id,app_id,type')->where($map)->find();
            if($apply['type'] == 1){
                $app = Db::table('tab_app')->where('id',$apply['app_id'])->find();
                $save['enable_status'] = 1;
                $info['MCHPromoteID'] = (string)$apply['promote_id'];
                $url = $app['file_url'] . '?appenddata=' . urlencode(json_encode($info));
                $save['dow_url'] = $url;
            }else{
                $save['enable_status'] = 2;
            }
            $result = $model->where($map)->update($save);
            if (!$result) {
                Db::rollback();
                $this->error('操作失败');
            }
        }
        Db::commit();
        $this->success('操作成功');
    }

    /**
     * @函数或方法说明
     * @批量渠道APP打包
     * @param null $ids
     * @param int $p
     * @param int $type
     *
     * @author: 郭家屯
     * @since: 2020/2/20 14:49
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function apppackage()
    {
        $data = $this->request->param();
        $ids = $data['ids'];
        $successNum = 0;
        $errorNuem = 0;
        $appModel = new PromoteappModel();
        foreach ($ids as $key => $value) {
            $apply = $appModel->field('promote_id,app_id,type')->where('id',$value)->find();
            $app = Db::table('tab_app')->where('id',$apply['app_id'])->find();
            if($app['type'] == 1){
                $save['enable_status'] = 1;
                $info['MCHPromoteID'] = (string)$apply['promote_id'];
                $url = $app['file_url'] . '?appenddata=' . urlencode(json_encode($info));
                $save['dow_url'] = $url;
            }else{
                $save['enable_status'] = 2;
            }
            $save['dispose_id'] = cmf_get_current_admin_id();
            $save['dispose_time'] = time();
            $result = $appModel->where('id',$value)->where('status',1)->update($save);
            if($result){
                $successNum++;
            }else{
                $errorNuem++;
            }
        }
        $msg = "已加入打包队列，刷新此页面可查看当前打包状态;\r\n加入成功：" . $successNum . "个。" . "失败：" . $errorNuem . "个";
        $this->success($msg);
        exit;
    }


    /**
     * @自定义渠道APP信息(android)
     *
     * @author: zsl
     * @since: 2020/11/12 14:11
     */
    public function appEdit()
    {
        $param = $this -> request -> param();
        $lPromoteApply = new PromoteapplyLogic();
        if ($this -> request -> isPost()) {

            if ($param['is_user_define'] == '1') {
                $vPromoteApp = new PromoteAppValidate();
                if (!$vPromoteApp -> scene('user_define') -> check($param)) {
                    $this -> error($vPromoteApp -> getError());
                }
            }
            //更新数据
            $saveRes = $lPromoteApply -> updateInfo($param);
            if (false === $saveRes) {
                $this -> error('保存失败');
            }
            $this -> success('保存成功');
        }
        //获取渠道APP申请信息
        $info = $lPromoteApply -> info($param['id']);
        if (empty($info)) {
            $this -> error('暂无信息');
        }
        $this -> assign('info', $info);
        return $this -> fetch();
    }

    // 自定义渠道盒子(ios)
    public function appEdit2()
    {
        $param = $this -> request -> param();
        $lPromoteApply = new PromoteapplyLogic();
        if ($this -> request -> isPost()) {

            if ($param['is_user_define'] == '1') {
                // 自定义
                if($param['type'] == 0){
                    // 原包上传
                    if(empty($param['dow_url'])){
                        $this -> error('请上传原包');
                    }
                    if(empty($param['app_new_icon'])){
                        $this -> error('请上传APP图标');
                    }
                    if (!is_numeric($param['ios_version'])) {
                        $this -> error('请输入正确的版本号');
                    }

                }
                if($param['type'] == 1){
                    // 超级签
                    if(empty($param['super_url'])){
                        $this -> error('请填写超级签地址');
                    }

                }
            }
            //更新数据
            // var_dump($param);exit;
            $param['ios'] = 1; // ios 给个特殊标记
            $saveRes = $lPromoteApply -> updateInfo($param);
            if (false === $saveRes) {
                $this -> error('保存失败');
            }
            $this -> success('保存成功');
        }
        //获取渠道APP申请信息
        $info = $lPromoteApply -> info($param['id']);
        if (empty($info)) {
            $this -> error('暂无信息');
        }
        // return json($info);exit;
        $this -> assign('info', $info);
        return $this -> fetch();
    }

    /**
     * 根据渠道等级限制删除渠道申请记录
     *
     * @param int $level
     * @param int $game_id
     * @return bool
     * @author: Juncl
     * @time: 2021/09/11 14:48
     */
    public function delApplyRecord($level=0,$game_id=0)
    {
        if($level > 0){
             // 查询所有小于当前等级的渠道
            $promoteLevelModel = new PromoteLevelModel();
            $promote_ids = $promoteLevelModel->where('promote_level','lt',$level)->column('promote_id');
            if(empty($promote_ids)){
                return true;
            }
            // 删除所有已申请记录
            $PromoteApplyModel = new PromoteapplyModel();
            foreach ($promote_ids as $key => $value) {
                $apply = $PromoteApplyModel
                    ->field('id,sdk_version,plist_url,pack_url,and_url,ios_url,enable_status,pack_type')
                    ->where('promote_id',$value)
                    ->where('game_id',$game_id)
                    ->find();
                if(!$apply){
                    continue;
                }
                $apply = $apply->toArray();
                $result = $PromoteApplyModel->delete($apply['id']);
                if ($result) {
                    if($apply['enable_status'] == 1 && $apply['pack_type'] == 1){
                        $this->delete_file($apply);
                    }
                }
            }
            return true;
        }else{
            return true;
        }
    }


}
