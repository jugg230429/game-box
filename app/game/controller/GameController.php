<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\game\controller;

use app\game\logic\GameLogic;
use app\game\model\GameAttrModel;
use app\game\model\GameModel;
use app\game\model\ServerModel;
use app\common\controller\BaseController;
use app\promote\controller\PromoteapplyController;
use app\sdkh5\logic\H5Logic;
use app\sdk\logic\SyLogic;
use app\sdkyy\logic\YyLogic;
use cmf\controller\AdminBaseController;
use app\game\validate\GameValidate;
use app\thirdgame\logic\GameLogic as ThirdGameLogic;
use think\Request;
use think\Db;

class GameController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('admin/main/index'));
            };
        }
        $action = request()->action();
        $array = ['login_record', 'role'];
        if (in_array($action, $array)) {
            if (AUTH_USER != 1) {
                if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                    $this->error('请购买用户权限');
                } else {
                    $this->error('请购买用户权限', url('admin/main/index'));
                };
            }
        }
    }
    /**
     * 关联提示判断
     */
    function get_relation_tip()
    {
        $relation_tip = cookie('relation_tip');
        $relation_tip_game = cookie('relation_tip_game');
        $relation_tip_game_id = cookie('relation_tip_game_id');
        $relation_tip_game_another = cookie('relation_tip_game_another');
        cookie(null,'relation_tip');
        return json(['code'=>200,'relation_tip'=>$relation_tip,'relation_tip_game'=>$relation_tip_game,'relation_tip_game_id'=>$relation_tip_game_id,'relation_tip_game_another'=>$relation_tip_game_another]);
    }
    /**
     * [游戏列表]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function lists()
    {
        if(PERMI == 2){
            return $this->redirect('hlists');
        }elseif(PERMI == 0){
            return $this->redirect('ylists');
        }
        $base = new BaseController();
        $model = new GameModel();
        //添加搜索条件
        $data = $this->request->param();
        $map['sdk_version'] = ['in',[1,2]];
        $map['game_status'] = 1;
        $map['platform_id'] = 0;
        $game_id = $data['game_id'];
        if ($game_id) {
            $map['tab_game.id'] = $game_id;
        }
        // 添加按照cp_id 查询
        $cp_id = $data['cp_id'];
        if($cp_id){
            $map['tab_game.cp_id'] = $cp_id;
        }

        $game_name = $data['game_name'];
        if ($game_name) {
            $map['game_name'] = $game_name;
        }
        $sdk_version = $data['sdk_version'];
        if ($sdk_version) {
            $map['sdk_version'] = $sdk_version;
        }
        $type = $data['type'];
        $where_str = '';//查询更改
        if ($type) {
            $where_str = "FIND_IN_SET('".$type."',game_type_id)";
        }
        $recommend_status = $data['recommend_status'];
        if ($recommend_status != '') {
            $map['recommend_status'] = ['like', '%' . $recommend_status . '%'];
        }
        $game_status = $data['game_status'];
        if ($game_status != '') {
            $map['game_status'] = $game_status;
        }
        //查询字段
        $exend['field'] = 'tab_game.id,game_name,marking,game_type_name,sdk_version,game_appid,recommend_status,game_status,dow_num,tab_game.sort,relation_game_id,relation_game_name,promote_ids,cp_id,tab_game.create_time,cp.cp_name,gs.game_key,gs.access_key,gs.pay_notify_url,t.type_name,tab_game.game_score,tab_game.pay_status,tab_game.ratio,tab_game.money';
        //排序优先，ID在后
        $exend['order'] = 'tab_game.sort desc,id desc';
        //关联游戏类型表
        $exend['join1'] = [['tab_game_type' => 't'], 'tab_game.game_type_id=t.id', 'left'];
        $exend['join2'] = [['tab_game_cp' => 'cp'], 'tab_game.cp_id=cp.id', 'left'];
        $exend['join3'] = [['tab_game_set' => 'gs'], 'tab_game.id=gs.game_id', 'left'];
        // var_dump($map);exit;
        $status_arr =  ['0' => '已下架', '1' => '上架中'];
        $data = $base->data_list_join($model, $map, $exend,$where_str)->each(function ($item, $key) use($status_arr) {
            $recommend = explode(',', $item['recommend_status']);
            $recommend_status = '';
            foreach ($recommend as $kk => $vo) {
                $recommend_status .= get_info_status($vo, 7).'/';
            }

            if(!empty($recommend_status)){
               $recommend_status = rtrim($recommend_status, "/");
            }
            // var_dump($item['recommend_status']);
            $item['recommend_status'] = $recommend_status;
            $item['relation_status'] = get_relation_game($item['id'], $item['relation_game_id']) === -1 ?: (get_relation_game($item['id'], $item['relation_game_id']) ? 1 : 0);
            // $item['status_name'] = get_info_status($item['game_status'], 4);
            $item['status_name'] = $status_arr[$item['game_status']];
            if($item['promote_ids']){
                $item['promote_ids'] = count(array_filter(explode(',',$item['promote_ids'])));
            }else{
                $item['promote_ids'] = 0;
            }
            $item['game_score'] = sprintf("%.1f",$item['game_score']);
            // 获取封禁设置
            $ban_set = $this->get_game_ban($item['id']);
            $item['ban_types'] = $ban_set['ban_types'] ?? -1; // (array) 1 禁止登录 2 禁止注册 3 禁止充值 4 禁止下载
            $cp_rate_info = getTableValues('tab_game_attr', 'game_id', $item['id'], 'cp_ratio,cp_pay_ratio');
            // var_dump($cp_rate_info); // exit;
            $item['cp_ratio'] = $cp_rate_info['cp_ratio'] ?? 0;
            $item['cp_pay_ratio'] = $cp_rate_info['cp_pay_ratio'] ?? 0;

            return $item;
        });
        // 获取分页显示
        // var_dump($data);
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign("data_lists", $data);
        $game_type = Db::table('tab_game_type')->field('id,type_name')->where('status', 1)->select();
        $this->assign('game_type', $game_type);
        return $this->fetch();
    }

    /**
     * [修改游戏状态]
     * @author 郭家屯[gjt]
     */
    public function changeStatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('请选择要操作的数据');
        $status = $this->request->param('status', 0, 'intval');
        $save['game_status'] = $status == 0 ? 1 : 0;
        $map['id'] = $id;
        $model = new GameModel();
        $result = $model->where($map)->update($save);
        if ($result) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * [修改下载人数和排序值]
     * @author 郭家屯[gjt]
     */
    public function setGameDataField()
    {
        $relation_game_id = $this->request->param('game_id', 0, 'intval');
        $field = $this->request->param('fields');
        $value = $this->request->param('value');
        $model = new GameModel();
        $result = $model->where('relation_game_id', $relation_game_id)->setField($field, $value);
        if ($result !== false) {
            $this->success('设置成功');
        } else {
            $this->error('设置失败');
        }
    }

    /**
     * 修改游戏其他参数
    */
    public function setGameOtherParam(Request $request)
    {
        $GameLogic = new GameLogic();
        $request = $this->request->param();
        $ids = $request['ids'];
        $name = $request['name'];
        $value = $request['value'];
        $result = $GameLogic ->setGameField($ids,$name,$value);
        if($result['status'] == 1){
            $this->success('修改成功');
        }else{
            $this->error($result['msg']);
        }


    }

    /**
     * [获取对接参数]
     * @author 郭家屯[gjt]
     */
    public function get_game_set()
    {
        $map["game_id"] = $this->request->param('game_id');
        $find = Db::table('tab_game_set')->where($map)->find();
        $find['mdaccess_key'] = get_ss($find['access_key']);
        return json_encode(array("status" => 1, "data" => $find));
    }

    /**
     * [新增游戏]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function add()
    {
        if(PERMI == 2){
            $this->error('权限不足');
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            //处理游戏类型-20210820-byh-s
            if(!empty($data['game_type_id'])){
                if(count($data['game_type_id'])>3){
                    $this->error('最多可以选择三个游戏类型');
                }
                $data['game_type_id'] = implode(',',$data['game_type_id']);
            }else{
                $this->error('最少选择一个游戏类型');
            }
            if(empty($data['cp_id'])){
                $this->error('所属CP不能为空');
            }
            //处理游戏类型-20210820-byh-e
            //处理上传的视频数据-20210624-byh-s
            if(!empty($data['video'])){
                $num = 0;
                foreach ($data['video'] as $k => $v){
                    if(empty($v) || $v =='[]'){
                        unset($data['video'][$k]);
                    } else {
                        $num++;
                    }
                }
                if($num > 0) {
                    $data['video'] = array_values($data['video']);//键从0开始处理
                    $data['video'] = json_encode($data['video']);
                } else {
                    $data['video'] ='';
                }
            }
            //处理上传的视频数据-20210624-byh-e
            //处理溪谷客服数据-20210707-byh-start
            if(!empty($data['xg_kf_url'])){
                foreach ($data['xg_kf_url'] as $k => $v){
                    if(empty($v)){
                        unset($data['xg_kf_url'][$k]);
                    }
                }
                $data['xg_kf_url'] = array_values($data['xg_kf_url']);//键从0开始处理
                $data['xg_kf_url'] = json_encode($data['xg_kf_url']);
            }
            //处理溪谷客服数据-20210707-byh-end
            $logic = new SyLogic();
            // 没传入CP商,则置为0
            if(empty($data['cp_id'])){
                $data['cp_id'] = 0;
            }
            //模拟器开关
            if(empty($data['simulator_official_status'])){
                $data['simulator_official_status'] = 0;
            }
            if(empty($data['simulator_channel_status'])){
                $data['simulator_channel_status'] = 0;
            }

            if ($data['apple_in_app_set']) {
                $data['apple_in_app_set'] = json_encode(array_merge($data['apple_in_app_set']));
            }
            $result = $logic->add($data);  // return ['status'=>1,'game_id'=>$id];
            if($result['status'] == 1){  // 添加游戏成功
                $game_id = $result['game_id'];
                $d_admin_id = cmf_get_current_admin_id();
                $d_promote_account = cmf_get_current_admin_name();
                $key = 'only_for_promote'.$d_admin_id.'-'.$d_promote_account.'-app';
                $db = 0;
                $redis = new \cmf\org\RedisSDK\RedisController(['host' => '127.0.0.1', 'port' => 6379]);
                $redis -> select($db);
                $aa = $redis -> get($key);
                $promote_ids2 = json_decode($aa);
                // 修改 渠道独占 显示
                $this->save_visible_only_for_promote($game_id, $promote_ids2);
            }

            if ($result['status']) {
                if($data['discount']<3 || $data['continue_discount']<3){
                    $this->warning($data['discount'],$data['continue_discount'],$result['game_id']);
                }
                $this->save_game_ban($data['ban_arr'],$result['game_id']);//编辑成功后,保存封禁设置的数据-20210709
                $this->success('添加成功', url('Game/lists'));
            } else {
                $this->error($result['msg']);
            }
        }
        //获取玩家页面
//        $game_ban_user_choose = $this->get_game_ban_user_choose(0);
////        $this->assign('game_user_choose',$game_ban_user_choose);
        return $this->fetch();
    }

    /**
     * [编辑游戏]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function edit()
    {
        if(PERMI == 2){
            $this->error('权限不足');
        }
        $model = new GameModel();
        if ($this->request->post()) {
            $data = $this->request->param();
            //处理游戏类型-20210820-byh-s
            if(!empty($data['game_type_id'])){
                if(count($data['game_type_id'])>3){
                    $this->error('最多可以选择三个游戏类型');
                }
                $data['game_type_id'] = implode(',',$data['game_type_id']);
            }else{
                $this->error('最少选择一个游戏类型');
            }
            if(empty($data['cp_id'])){
                $this->error('所属CP不能为空');
            }
            //处理游戏类型-20210820-byh-e
            //处理上传的视频数据-20210624-byh-s
            if(!empty($data['video'])){
                $num = 0;
                foreach ($data['video'] as $k => $v){
                    if(empty($v) || $v =='[]'){
                        unset($data['video'][$k]);
                    } else {
                        $num++;
                    }
                }
                if($num > 0) {
                    $data['video'] = array_values($data['video']);//键从0开始处理
                    $data['video'] = json_encode($data['video']);
                } else {
                    $data['video'] ='';
                }
            }
            //处理上传的视频数据-20210624-byh-e
            //处理溪谷客服数据-20210707-byh-start
            if(!empty($data['xg_kf_url'])){
                foreach ($data['xg_kf_url'] as $k => $v){
                    if(empty($v)){
                        unset($data['xg_kf_url'][$k]);
                    }
                }
                $data['xg_kf_url'] = array_values($data['xg_kf_url']);//键从0开始处理
                $data['xg_kf_url'] = json_encode($data['xg_kf_url']);
            }
            //处理溪谷客服数据-20210707-byh-end


            $only_for_promote = $data['only_for_promote'];
            if (empty($data['id'])) $this->error('游戏不存在');
            $logic = new SyLogic();
            if ($data['apple_in_app_set']) {
                $data['apple_in_app_set'] = json_encode(array_merge($data['apple_in_app_set']));
            } else {
                $data['apple_in_app_set'] = '';
            }
            //模拟器开关
            if(empty($data['simulator_official_status'])){
                $data['simulator_official_status'] = 0;
            }
            if(empty($data['simulator_channel_status'])){
                $data['simulator_channel_status'] = 0;
            }
            // 获取原有渠道等级限制
            $GameAttrModel = new GameAttrModel();
            $oldLevel = $GameAttrModel->where('game_id',$data['id'])->value('promote_level_limit');
            // 获取原有的分成比例和注册单价，如果修改 则更新开始时间
            $game_info = get_game_entity($data['id'],'ratio,money');
            $data['old_ratio'] = $game_info['ratio'];
            $data['old_money'] = $game_info['money'];
            $result = $logic->edit($data);
            if ($result['status']) {
                // 删除渠道等级小于现在等级的所有已申请游戏
                $newLevel = $GameAttrModel->where('game_id',$data['id'])->value('promote_level_limit');
                if($newLevel > 0 && $newLevel > $oldLevel){
                    $applyRecord = new PromoteapplyController();
                    $applyRecord->delApplyRecord($newLevel,$data['id']);
                }
                //编辑成功后修改已有的会长代充关联游戏折扣
                update_promote_agent_discount($data['id'],$data['discount'],$data['continue_discount']);
                $this->edit_warning($data['discount'],$data['continue_discount'],$data['id']);

                $this->save_game_ban($data['ban_arr'],$data['id']);//编辑成功后,保存封禁设置的数据-20210709
                $this->success('编辑成功', url('Game/lists'));
            } else {
                $this->error($result['msg']);
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $game = $model
            ->alias('g')
            ->field('g.*,login_notify_url,pay_notify_url,game_key,access_key,agent_id,game_pay_appid,apk_pck_name,
            apk_pck_sign,apple_in_app_set,promote_ids2,only_for_promote,at.is_control,at.xg_kf_url,at.sdk_type,at.bind_recharge_discount,
            at.bind_continue_recharge_discount,at.discount,at.continue_discount,at.support_introduction,at.promote_level_limit,at.promote_declare,at.discount_show_status,at.coupon_show_status')
            ->Join(['tab_game_set' => 's'], 'g.id=s.game_id', 'left')
            ->Join(['tab_game_attr' => 'at'], 'g.id=at.game_id', 'left')
            ->where('g.id', $id)->find();
        if (!$game) $this->error('游戏不存在或是已删除');
        $game = $game->toArray();
        $tag_name = explode(',', $game['tag_name']);
        $game['tag_name_one'] = $tag_name[0];
        $game['tag_name_two'] = $tag_name[1];
        $game['tag_name_three'] = $tag_name[2];
        $game['apple_in_app_set'] = json_decode($game['apple_in_app_set'], true);
        // 游戏是否超级签付费下载
        if($game['sdk_version'] == 2){
            $pay_download_info = get_ios_pay_to_download($id);
            $game['pay_download'] = $pay_download_info['pay_download'];
            $game['pay_price'] = $pay_download_info['pay_price'];
        }else{
            $game['pay_download'] = 0;
            $game['pay_price'] = 0;
        }

        //处理游戏类型-20210820-byh
        $game['game_type_id'] = explode(',',$game['game_type_id']);
        //video视频处理-20210624-byh-s
        if(!empty($game['video'])){
            $_video = json_decode($game['video'], true);
            if(empty($_video)){//为null则是原单个数据
                $_video[] = $game['video'];
            }
            $game['video'] = $_video;
        }
        //video视频处理-20210624-byh-e
        //增加判断获取溪谷客服数据-20210707-byh-start
        if(!empty($game['xg_kf_url'])){
            $game['xg_kf_url'] = json_decode($game['xg_kf_url'], true);
        }
        //增加判断获取溪谷客服数据-20210707-byh-end
        $this->assign('data', $game);
        //返回游戏封禁数据
        $ban_data = $this->get_game_ban($id);
        $this->assign('ban_data',$ban_data);
        //更改-处理游戏的选择页面和数据
//        //获取玩家页面
//        $game_ban_user_choose = $this->get_game_ban_user_choose($id);
//        $this->assign('game_user_choose',$game_ban_user_choose);


        return $this->fetch();
    }

    /**
     * [检查游戏名称]
     * @author 郭家屯[gjt]
     */
    public function checkGameName()
    {
        $game_name = $this->request->param('game_name');
        $sdk_version = $this->request->param('sdk_version');
        $model = new GameModel();
        $result = $model->checkGameName($game_name,$sdk_version);
        return json_encode($result);
    }

    /**
     * [关联游戏]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function relation()
    {
        if(PERMI == 2){
            $this->error('权限不足');
        }
        $model = new GameModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (!empty($data['apple_in_app_set'])) {
                $data['apple_in_app_set'] = json_encode(array_merge($data['apple_in_app_set']));
            }
            //处理游戏类型-20210820-byh-s
            if(!empty($data['game_type_id'])){
                if(count($data['game_type_id'])>3){
                    $this->error('最多可以选择三个游戏类型');
                }
                $data['game_type_id'] = implode(',',$data['game_type_id']);
            }else{
                $this->error('最少选择一个游戏类型');
            }
            //处理游戏类型-20210820-byh-e
            $logic = new SyLogic();
            $result = $logic->relation($data);

            if($result['status'] == 1){  // 添加游戏成功
                $game_id = $result['game_id'];
                $d_admin_id = cmf_get_current_admin_id();
                $d_promote_account = cmf_get_current_admin_name();
                $key = 'only_for_promote'.$d_admin_id.'-'.$d_promote_account.'-app';
                $db = 0;
                $redis = new \cmf\org\RedisSDK\RedisController(['host' => '127.0.0.1', 'port' => 6379]);
                $redis -> select($db);
                $aa = $redis -> get($key);
                $promote_ids2 = json_decode($aa);
                // 修改 渠道独占 显示
                $this->save_visible_only_for_promote($game_id, $promote_ids2);
            }

            if ($result['status']) {
                $this->save_game_ban($data['ban_arr'],$result['game_id']);//编辑成功后,保存封禁设置的数据-20210709
                $this->success('关联成功', url('Game/lists'));
            } else {
                $this->error($result['msg']);
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('游戏不存在');
        $map['relation_game_id'] = $id;
        $game = $model->where($map)->find();
        if (!$game) $this->error('游戏不存在');
        $game = $game->toArray();
        $game['game_name'] = $game['relation_game_name'];
        $game['sdk_version'] = $game['sdk_version'] == 1 ? 2 : 1;
        $game['apple_in_app_set'] = json_decode($game['apple_in_app_set'], true);
        $game['only_for_promote'] = $game['only_for_promote'];
        $game['game_type_id'] = explode(',',$game['game_type_id']);//处理游戏类型-20210820-byh
        $this->assign('data', $game);
        // 渠道独占
        $promote_ids2 = explode(',', $game['promote_ids2']);
        $game_type = 'app';

        $d_admin_id = cmf_get_current_admin_id();
        $d_promote_account = cmf_get_current_admin_name();
        $key = 'only_for_promote'.$d_admin_id.'-'.$d_promote_account.'-'.$game_type;

        $db = 0;
        $redis = new \cmf\org\RedisSDK\RedisController(['host' => '127.0.0.1', 'port' => 6379]);
        $redis -> select($db);
        // $redis->del($key);

        $tmp_value = json_encode($promote_ids2, JSON_UNESCAPED_UNICODE);
        // $redis->set($key, $tmp_value);
        $redis->setex($key, 3600, $tmp_value);

        $this->assign('relation_game_id', $id);
        //返回游戏封禁数据
        $ban_data = $this->get_game_ban($id);
        $this->assign('ban_data',$ban_data);
//        //获取玩家页面
//        $game_ban_user_choose = $this->get_game_ban_user_choose(0);
//        $this->assign('game_user_choose',$game_ban_user_choose);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @获取不可推广渠道信息
     * @author: 郭家屯
     * @since: 2019/10/9 11:06
     */
    public function getpromote()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('游戏不存在');
        $game_info = get_game_entity($id,'id,game_name,promote_ids');
        if (empty($game_info)) $this->error('游戏不存在');
        $promote = get_promote_list(['promote_level'=>1],'id,account','id asc');
        if (!empty($promote)) {
            foreach ($promote as &$v) {
                $v['short'] = strtoupper(substr($v['account'], 0, 1));
            }
        }

        return json(['code'=>1,'game'=>$game_info,'promote_list'=>$promote]);
    }

    /**
     * @函数或方法说明
     * @保存不可推广渠道信息
     * @author: 郭家屯
     * @since: 2019/10/10 19:02
     */
    public function savepromote()
    {
        $game_id = $this->request->param('game_id', 0, 'intval');
        if (empty($game_id)) $this->error('游戏不存在');
        $game_info = get_game_entity($game_id,'id,game_name,promote_ids');
        if (empty($game_info)) $this->error('游戏不存在');
        $promote_ids = $this->request->param('promote_ids/a');
        if(empty($promote_ids)){
            $save['promote_ids'] = '';
        }else{
            $save['promote_ids'] = count($promote_ids) > 1 ?implode(',',$promote_ids) : $promote_ids[0];
        }
        if(empty($promote_ids)){
            $promote_ids = [];
        }
        $old_promote_ids = $game_info['promote_ids'] ? explode(',',$game_info['promote_ids']) : [];
        $change = array_merge(array_diff($promote_ids,$old_promote_ids),array_diff($old_promote_ids,$promote_ids));
        Db::startTrans();
        try{
            Db::table('tab_game')->where('id',$game_id)->setField('promote_ids',$save['promote_ids']);
            foreach ($change as $key=>$v){
                $promote = get_promote_entity($v,'id,game_ids');
                $game_ids = $promote['game_ids'] ? explode(',',$promote['game_ids']) : [];
                if($game_ids && in_array($game_id,$game_ids)){
                   unset($game_ids[array_search($game_id,$game_ids)]);
                }else{
                    $game_ids[] = $game_id;
                }
                $game_ids = count($game_ids) > 0 ? implode(',',$game_ids) : '';
                Db::table('tab_promote')->where('id|parent_id|top_promote_id',$v)->setField('game_ids',$game_ids);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('设置失败');
        }
        $this->success('设置成功');
    }
    /**
     * 增加 仅对渠道显示的设置,设置了一级渠道, 二三级跟从
     * 仅对指定渠道显示的游戏
     */
    public function visible_only_to_promote()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('游戏不存在');
        $game_info = get_game_entity($id,'id,game_name,promote_ids2');
        if (empty($game_info)) $this->error('游戏不存在');
        $promote = get_promote_list(['promote_level'=>1],'id,account','id asc');
        if (!empty($promote)) {
            foreach ($promote as &$v) {
                $v['short'] = strtoupper(substr($v['account'], 0, 1));
            }
        }

        return json(['code'=>1,'game'=>$game_info,'promote_list'=>$promote]);
    }
    /**
     * 增加 仅对渠道显示的设置,设置了一级渠道, 二三级跟从
     * 仅对指定渠道显示的游戏
     */
    public function save_visible_only_to_promote()
    {
        $game_id = $this->request->param('game_id', 0, 'intval');
        if (empty($game_id)) $this->error('游戏不存在');
        $game_info = get_game_entity($game_id,'id,game_name,promote_ids2');
        if (empty($game_info)) $this->error('游戏不存在');
        $promote_ids2 = $this->request->param('promote_ids/a');
        if(empty($promote_ids2)){
            $save['promote_ids2'] = '';
        }else{
            $save['promote_ids2'] = count($promote_ids2) > 1 ?implode(',',$promote_ids2) : $promote_ids2[0];
        }
        if(empty($promote_ids2)){
            $promote_ids2 = [];
        }
        $old_promote_ids = $game_info['promote_ids2'] ? explode(',',$game_info['promote_ids2']) : [];
        $change = array_merge(array_diff($promote_ids2,$old_promote_ids),array_diff($old_promote_ids,$promote_ids2));
        Db::startTrans();
        try{
            Db::table('tab_game')->where('id',$game_id)->setField('promote_ids2',$save['promote_ids2']);
            foreach ($change as $key=>$v){
                $promote = get_promote_entity($v,'id,game_ids2');
                $game_ids2 = $promote['game_ids2'] ? explode(',',$promote['game_ids2']) : [];
                if($game_ids2 && in_array($game_id,$game_ids2)){
                   unset($game_ids2[array_search($game_id,$game_ids2)]);
                }else{
                    $game_ids2[] = $game_id;
                }
                $game_ids2 = count($game_ids2) > 0 ? implode(',',$game_ids2) : '';
                Db::table('tab_promote')->where('id|parent_id|top_promote_id',$v)->setField('game_ids2',$game_ids2);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error('设置失败');
        }
        $this->success('设置成功');
    }
    // 添加的时候使用
    public function visible_only_to_promote22()
    {
        $id = $this->request->param('id', 0, 'intval');
        $game_type = $this->request->param('game_type');

        $d_admin_id = cmf_get_current_admin_id();
        $d_promote_account = cmf_get_current_admin_name();
        $key = 'only_for_promote'.$d_admin_id.'-'.$d_promote_account.'-'.$game_type;
        $db = 0;

        $redis = new \cmf\org\RedisSDK\RedisController(['host' => '127.0.0.1', 'port' => 6379]);
        $redis -> select($db);
        $promote_ids_tmp = $redis -> get($key);
        if(!empty($promote_ids_tmp)){
            $promote_ids2 = implode(',', json_decode($promote_ids_tmp));
        }else{
            $promote_ids2 = '';
        }
        // var_dump($promote_ids2);  exit;
        $game_info = ['id'=>-1, 'game_name'=>'未添加的游戏', 'promote_ids2'=>$promote_ids2];
        if (empty($game_info)) $this->error('游戏不存在');
        $promote = get_promote_list(['promote_level'=>1],'id,account','id asc');
        if (!empty($promote)) {
            foreach ($promote as &$v) {
                $v['short'] = strtoupper(substr($v['account'], 0, 1));
            }
        }

        return json(['code'=>1,'game'=>$game_info,'promote_list'=>$promote]);
    }
    /**
     * 添加的时候使用
     * 增加 仅对渠道显示的设置,设置了一级渠道, 二三级跟从
     * 仅对指定渠道显示的游戏
     */
    public function save_visible_only_to_promote22()
    {
        $game_id = $this->request->param('game_id', 0, 'intval');
        $promote_ids2 = $this->request->param('promote_ids/a');
        $game_type = $this->request->param('game_type');

        $d_admin_id = cmf_get_current_admin_id();
        $d_promote_account = cmf_get_current_admin_name();
        $key = 'only_for_promote'.$d_admin_id.'-'.$d_promote_account.'-'.$game_type;

        $db = 0;
        $redis = new \cmf\org\RedisSDK\RedisController(['host' => '127.0.0.1', 'port' => 6379]);
        $redis -> select($db);
        $tmp_value = json_encode($promote_ids2, JSON_UNESCAPED_UNICODE);
        // $redis->set($key, $tmp_value);
        $redis->setex($key, 3600, $tmp_value);
        $this->success('设置成功');
        exit;
    }
    // 类里面调用 保存
    private function save_visible_only_for_promote($game_id=0, $promote_ids2=[])
    {
        if (empty($game_id)) $this->error('游戏不存在');
        $game_info = get_game_entity($game_id,'id,game_name,promote_ids2');
        if (empty($game_info)) $this->error('游戏不存在');
        if(empty($promote_ids2)){
            $save['promote_ids2'] = '';
        }else{
            $save['promote_ids2'] = count($promote_ids2) > 1 ?implode(',',$promote_ids2) : $promote_ids2[0];
        }
        if(empty($promote_ids2)){
            $promote_ids2 = [];
        }
        $old_promote_ids = $game_info['promote_ids2'] ? explode(',',$game_info['promote_ids2']) : [];
        $change = array_merge(array_diff($promote_ids2,$old_promote_ids),array_diff($old_promote_ids,$promote_ids2));
        Db::startTrans();
        try{
            Db::table('tab_game')->where('id',$game_id)->setField('promote_ids2',$save['promote_ids2']);
            foreach ($change as $key=>$v){
                $promote = get_promote_entity($v,'id,game_ids2');
                $game_ids2 = $promote['game_ids2'] ? explode(',',$promote['game_ids2']) : [];
                if($game_ids2 && in_array($game_id,$game_ids2)){
                   unset($game_ids2[array_search($game_id,$game_ids2)]);
                }else{
                    $game_ids2[] = $game_id;
                }
                $game_ids2 = count($game_ids2) > 0 ? implode(',',$game_ids2) : '';
                Db::table('tab_promote')->where('id|parent_id|top_promote_id',$v)->setField('game_ids2',$game_ids2);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            // $this->error('设置失败');
        }
        // $this->success('设置成功');
    }

    /**
     * [H5游戏列表]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function hlists()
    {
        $base = new BaseController();
        $model = new GameModel();
        //添加搜索条件
        $data = $this->request->param();
        $map['sdk_version'] = 3;
        $map['game_status'] = 1;
        $map['platform_id'] = 0;
        $game_id = $data['game_id'];
        if ($game_id) {
            $map['tab_game.id'] = $game_id;
        }
        // 添加按照cp_id 查询
        $cp_id = $data['cp_id'];
        if($cp_id){
            $map['tab_game.cp_id'] = $cp_id;
        }
        $game_name = $data['game_name'];
        if ($game_name) {
            $map['game_name'] = $game_name;
        }
        $type = $data['type'];
        $where_str = '';//查询更改
        if ($type) {
            $where_str = "FIND_IN_SET('".$type."',game_type_id)";
        }
        $recommend_status = $data['recommend_status'];
        if ($recommend_status != '') {
            $map['recommend_status'] = ['like', '%' . $recommend_status . '%'];
        }
        $game_status = $data['game_status'];
        if ($game_status != '') {
            $map['game_status'] = $game_status;
        }
        //查询字段
        $exend['field'] = 'tab_game.id,game_name,game_type_name,sdk_version,game_appid,recommend_status,game_status,dow_num,tab_game.sort,relation_game_id,relation_game_name,promote_ids,cp_id,cp.cp_name,tab_game.create_time,gs.game_key,gs.login_notify_url,gs.pay_notify_url,game_score,tab_game.ratio,tab_game.money';
        //排序优先，ID在后
        $exend['order'] = 'tab_game.sort desc,id desc';
        //关联游戏类型表
        $exend['join1'] = [['tab_game_type' => 't'], 'tab_game.game_type_id=t.id', 'left'];
        $exend['join2'] = [['tab_game_cp' => 'cp'], 'tab_game.cp_id=cp.id', 'left'];
        $exend['join3'] = [['tab_game_set' => 'gs'], 'tab_game.id=gs.game_id', 'left'];

        $status_arr =  ['0' => '已下架', '1' => '上架中'];
        $data = $base->data_list_join($model, $map, $exend,$where_str)->each(function ($item, $key) use($status_arr) {
            $recommend = explode(',', $item['recommend_status']);
            $recommend_status = '';
            foreach ($recommend as $kk => $vo) {
                $recommend_status .= " " . get_info_status($vo, 7);
            }
            $item['recommend_status'] = $recommend_status;
            $item['status_name'] = $status_arr[$item['game_status']];
            if($item['promote_ids']){
                $item['promote_ids'] = count(array_filter(explode(',',$item['promote_ids'])));
            }else{
                $item['promote_ids'] = 0;
            }

            $item['game_score'] = sprintf("%.1f",$item['game_score']);
            // 获取封禁设置
            $ban_set = $this->get_game_ban($item['id']);
            $item['ban_types'] = $ban_set['ban_types'] ?? -1; // (array) 1 禁止登录 2 禁止注册 3 禁止充值 4 禁止下载
            $cp_rate_info = getTableValues('tab_game_attr', 'game_id', $item['id'], 'cp_ratio,cp_pay_ratio');
            // var_dump($cp_rate_info); // exit;
            $item['cp_ratio'] = $cp_rate_info['cp_ratio'] ?? 0;
            $item['cp_pay_ratio'] = $cp_rate_info['cp_pay_ratio'] ?? 0;

            $item['open_url'] = url('@media/game/open_game',[],true,true).'?game_id='.$item['id'];
            return $item;
        });
        // 获取分页显示
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign("data_lists", $data);
        $game_type = Db::table('tab_game_type')->field('id,type_name')->where('status', 1)->select();
        $this->assign('game_type', $game_type);
        return $this->fetch();
    }

    /**
     * [新增H5游戏]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function hadd()
    {
        if(PERMI == 1){
            $this->error('权限不足');
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            //处理游戏类型-20210820-byh-s
            if(!empty($data['game_type_id'])){
                if(count($data['game_type_id'])>3){
                    $this->error('最多可以选择三个游戏类型');
                }
                $data['game_type_id'] = implode(',',$data['game_type_id']);
            }else{
                $this->error('最少选择一个游戏类型');
            }
            if(empty($data['cp_id'])){
                $this->error('所属CP不能为空');
            }
            //处理游戏类型-20210820-byh-e
            //处理上传的视频数据-20210624-byh-s
            if(!empty($data['video'])){
                $num = 0;
                foreach ($data['video'] as $k => $v){
                    if(empty($v) || $v =='[]'){
                        unset($data['video'][$k]);
                    } else {
                        $num++;
                    }
                }
                if($num > 0) {
                    $data['video'] = array_values($data['video']);//键从0开始处理
                    $data['video'] = json_encode($data['video']);
                } else {
                    $data['video'] ='';
                }
            }
            //处理上传的视频数据-20210624-byh-e
            //处理溪谷客服数据-20210707-byh-start
            if(!empty($data['xg_kf_url'])){
                foreach ($data['xg_kf_url'] as $k => $v){
                    if(empty($v)){
                        unset($data['xg_kf_url'][$k]);
                    }
                }
                $data['xg_kf_url'] = array_values($data['xg_kf_url']);//键从0开始处理
                $data['xg_kf_url'] = json_encode($data['xg_kf_url']);
            }
            //处理溪谷客服数据-20210707-byh-end
            $logic = new H5Logic();
            $result = $logic->add($data);

            if($result['status'] == 1){  // 添加游戏成功
                $game_id = $result['game_id'];
                $d_admin_id = cmf_get_current_admin_id();
                $d_promote_account = cmf_get_current_admin_name();
                $key = 'only_for_promote'.$d_admin_id.'-'.$d_promote_account.'-h5';
                $db = 0;
                $redis = new \cmf\org\RedisSDK\RedisController(['host' => '127.0.0.1', 'port' => 6379]);
                $redis -> select($db);
                $aa = $redis -> get($key);
                $promote_ids2 = json_decode($aa);
                // 修改 渠道独占 显示
                $this->save_visible_only_for_promote($game_id, $promote_ids2);
            }

            if ($result['status']) {
                if($data['discount']<3 || $data['continue_discount']<3){
                    $this->warning($data['discount'],$data['continue_discount'],$result['game_id']);
                }
                $this->save_game_ban($data['ban_arr'],$result['game_id']);//编辑成功后,保存封禁设置的数据-20210709
                $this->success('添加成功', url('Game/hlists'));
            } else {
                $this->error($result['msg']);
            }
        }
        //获取玩家页面
        $game_ban_user_choose = $this->get_game_ban_user_choose(0);
        $this->assign('game_user_choose',$game_ban_user_choose);
        return $this->fetch();
    }

    /**
     * [编辑H5游戏]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function hedit()
    {
        if(PERMI == 1){
            $this->error('权限不足');
        }
        $model = new GameModel();
        if ($this->request->post()) {
            $data = $this->request->param();
            if (empty($data['id'])) $this->error('游戏不存在');
            //处理游戏类型-20210820-byh-s
            if(!empty($data['game_type_id'])){
                if(count($data['game_type_id'])>3){
                    $this->error('最多可以选择三个游戏类型');
                }
                $data['game_type_id'] = implode(',',$data['game_type_id']);
            }else{
                $this->error('最少选择一个游戏类型');
            }
            if(empty($data['cp_id'])){
                $this->error('所属CP不能为空');
            }
            //处理游戏类型-20210820-byh-e
            //处理上传的视频数据-20210624-byh-s
            if(!empty($data['video'])){
                $num = 0;
                foreach ($data['video'] as $k => $v){
                    if(empty($v) || $v =='[]'){
                        unset($data['video'][$k]);
                    } else {
                        $num++;
                    }
                }
                if($num > 0) {
                    $data['video'] = array_values($data['video']);//键从0开始处理
                    $data['video'] = json_encode($data['video']);
                } else {
                    $data['video'] ='';
                }
            }
            //处理上传的视频数据-20210624-byh-e
            //处理溪谷客服数据-20210707-byh-start
            if(!empty($data['xg_kf_url'])){
                foreach ($data['xg_kf_url'] as $k => $v){
                    if(empty($v)){
                        unset($data['xg_kf_url'][$k]);
                    }
                }
                $data['xg_kf_url'] = array_values($data['xg_kf_url']);//键从0开始处理
                $data['xg_kf_url'] = json_encode($data['xg_kf_url']);
            }
            //处理溪谷客服数据-20210707-byh-end
            $logic = new H5Logic();
            // 获取原有渠道等级限制
            $GameAttrModel = new GameAttrModel();
            $oldLevel = $GameAttrModel->where('game_id',$data['id'])->value('promote_level_limit');
            // 获取原有的分成比例和注册单价，如果修改 则更新开始时间
            $game_info = get_game_entity($data['id'],'ratio,money');
            $data['old_ratio'] = $game_info['ratio'];
            $data['old_money'] = $game_info['money'];
            $result = $logic->edit($data);
            if ($result['status']) {
                // 删除渠道等级小于现在等级的所有已申请游戏
                $newLevel = $GameAttrModel->where('game_id',$data['id'])->value('promote_level_limit');
                if($newLevel > 0 && $newLevel > $oldLevel){
                    $applyRecord = new PromoteapplyController();
                    $applyRecord->delApplyRecord($newLevel,$data['id']);
                }
                update_promote_agent_discount($data['id'],$data['discount'],$data['continue_discount']);
                $this->edit_warning($data['discount'],$data['continue_discount'],$data['id']);
                $this->save_game_ban($data['ban_arr'],$data['id']);//编辑成功后,保存封禁设置的数据-20210709
                $this->success('编辑成功', url('Game/hlists'));
            } else {
                $this->error($result['msg']);
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $game = $model
                ->alias('g')
                ->field('g.*,login_notify_url,pay_notify_url,game_key,access_key,agent_id,game_pay_appid,apk_pck_name,
                apk_pck_sign,at.xg_kf_url,at.is_control,at.bind_recharge_discount,at.bind_continue_recharge_discount,
                at.discount,at.continue_discount,at.support_introduction,at.promote_level_limit,at.promote_declare,at.discount_show_status,at.coupon_show_status')
                ->Join(['tab_game_set' => 's'], 'g.id=s.game_id', 'left')
                ->Join(['tab_game_attr' => 'at'], 'g.id=at.game_id', 'left')
                ->where('g.id', $id)->find();
        if (!$game) $this->error('游戏不存在或是已删除');
        $game = $game->toArray();
        $tag_name = explode(',', $game['tag_name']);
        $game['tag_name_one'] = $tag_name[0];
        $game['tag_name_two'] = $tag_name[1];
        $game['tag_name_three'] = $tag_name[2];

        //处理游戏类型-20210820-byh
        $game['game_type_id'] = explode(',',$game['game_type_id']);
        //video视频处理-20210624-byh-s
        if(!empty($game['video'])){
            $_video = json_decode($game['video'], true);
            if(empty($_video)){//为null则是原单个数据
                $_video[] = $game['video'];
            }
            $game['video'] = $_video;
        }
        //video视频处理-20210624-byh-e
        //增加判断获取溪谷客服数据-20210707-byh-start
        if(!empty($game['xg_kf_url'])){
            $game['xg_kf_url'] = json_decode($game['xg_kf_url'], true);
        }
        //增加判断获取溪谷客服数据-20210707-byh-end

        $this->assign('data', $game);
        //返回游戏封禁数据
        $ban_data = $this->get_game_ban($id);
        $this->assign('ban_data',$ban_data);
        //更改-处理游戏的选择页面和数据
        //获取玩家页面
        $game_ban_user_choose = $this->get_game_ban_user_choose($id);
        $this->assign('game_user_choose',$game_ban_user_choose);

        return $this->fetch();
    }


    /**
     * [页游游戏列表]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function ylists()
    {
        $base = new BaseController();
        $model = new GameModel();
        //添加搜索条件
        $data = $this->request->param();
        $map['sdk_version'] = 4;
        $map['game_status'] = 1;
        $map['platform_id'] = 0;
        $game_id = $data['game_id'];
        if ($game_id) {
            $map['tab_game.id'] = $game_id;
        }
        // 添加按照cp_id 查询
        $cp_id = $data['cp_id'];
        if($cp_id){
            $map['tab_game.cp_id'] = $cp_id;
        }

        $game_name = $data['game_name'];
        if ($game_name) {
            $map['game_name'] = $game_name;
        }
        $sdk_version = $data['sdk_version'];
        if ($sdk_version) {
            $map['sdk_version'] = $sdk_version;
        }
        $type = $data['type'];
        $where_str = '';//查询更改
        if ($type) {
            $where_str = "FIND_IN_SET('".$type."',game_type_id)";
        }
        $recommend_status = $data['recommend_status'];
        if ($recommend_status != '') {
            $map['recommend_status'] = ['like', '%' . $recommend_status . '%'];
        }
        $game_status = $data['game_status'];
        if ($game_status != '') {
            $map['game_status'] = $game_status;
        }
        //查询字段
        $exend['field'] = 'tab_game.id,game_name,game_type_name,sdk_version,game_appid,recommend_status,game_status,dow_num,tab_game.sort,relation_game_id,relation_game_name,promote_ids,cp_id,cp.cp_name,tab_game.create_time,gs.game_key,marking,interface_id,tab_game.ratio,tab_game.money';
        //排序优先，ID在后
        $exend['order'] = 'tab_game.sort desc,id desc';
        //关联游戏类型表
        $status_arr =  ['0' => '已下架', '1' => '上架中'];

        $exend['join1'] = [['tab_game_type' => 't'], 'tab_game.game_type_id=t.id', 'left'];
        $exend['join2'] = [['tab_game_cp' => 'cp'], 'tab_game.cp_id=cp.id', 'left'];
        $exend['join3'] = [['tab_game_set' => 'gs'], 'tab_game.id=gs.game_id', 'left'];

        $data = $base->data_list_join($model, $map, $exend,$where_str)->each(function ($item, $key) use($status_arr) {
            $recommend = explode(',', $item['recommend_status']);
            $recommend_status = '';
            foreach ($recommend as $kk => $vo) {
                $recommend_status .= " " . get_info_status($vo, 7);
            }
            $item['recommend_status'] = $recommend_status;
            $item['relation_status'] = get_relation_game($item['id'], $item['relation_game_id']) === -1 ?: (get_relation_game($item['id'], $item['relation_game_id']) ? 1 : 0);
            $item['status_name'] = $status_arr[$item['game_status']];
            if($item['promote_ids']){
                $item['promote_ids'] = count(array_filter(explode(',',$item['promote_ids'])));
            }else{
                $item['promote_ids'] = 0;
            }
            // 获取页游接口信息
            $yy_interface_info = $this->get_yy_interface_info($item['interface_id']);
            $item['yy_tag'] = $yy_interface_info['tag'] ?? '';  // 页游接口标签
            $item['yy_unid'] = $yy_interface_info['unid'] ?? ''; // 页游接口标识
            // $item['interface_id'] = $yy_interface_info['id'] ?? ''; // 页游接口id
            // 获取封禁设置
            $ban_set = $this->get_game_ban($item['id']);
            $item['ban_types'] = $ban_set['ban_types'] ?? -1; // (array) 1 禁止登录 2 禁止注册 3 禁止充值 4 禁止下载
            $cp_rate_info = getTableValues('tab_game_attr', 'game_id', $item['id'], 'cp_ratio,cp_pay_ratio');
            // var_dump($cp_rate_info); // exit;
            $item['cp_ratio'] = $cp_rate_info['cp_ratio'] ?? 0;
            $item['cp_pay_ratio'] = $cp_rate_info['cp_pay_ratio'] ?? 0;

            return $item;
        });
        // 获取分页显示
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign("data_lists", $data);
        $game_type = Db::table('tab_game_type')->field('id,type_name')->where('status', 1)->select();
        $this->assign('game_type', $game_type);
        return $this->fetch();
    }
    // 获取页游接口信息 by wjd 2021-8-27 19:07:31
    private function get_yy_interface_info($y_interface_id=0)
    {
        if($y_interface_id != 0){
            $y_game_info = Db::table('tab_game_interface')->where(['id'=>$y_interface_id])->field('id,name,tag,unid')->find();  // 接口名称, 标签, 标识
            return $y_game_info;
        }else{
            return [];
        }
    }

    /**
     * [新增网页游戏]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function yadd()
    {
        if(YPERMI == 0){
            $this->error('权限不足');
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            //处理游戏类型-20210820-byh-s
            if(!empty($data['game_type_id'])){
                if(count($data['game_type_id'])>3){
                    $this->error('最多可以选择三个游戏类型');
                }
                $data['game_type_id'] = implode(',',$data['game_type_id']);
            }else{
                $this->error('最少选择一个游戏类型');
            }
            if(empty($data['cp_id'])){
                $this->error('所属CP不能为空');
            }
            //处理游戏类型-20210820-byh-e
            //处理溪谷客服数据-20210707-byh-start
            if(!empty($data['xg_kf_url'])){
                foreach ($data['xg_kf_url'] as $k => $v){
                    if(empty($v)){
                        unset($data['xg_kf_url'][$k]);
                    }
                }
                $data['xg_kf_url'] = array_values($data['xg_kf_url']);//键从0开始处理
                $data['xg_kf_url'] = json_encode($data['xg_kf_url']);
            }
            //处理溪谷客服数据-20210707-byh-end
            $logic = new YyLogic();
            $result = $logic->add($data);

            if($result['status'] == 1){  // 添加游戏成功
                $game_id = $result['game_id'];
                $d_admin_id = cmf_get_current_admin_id();
                $d_promote_account = cmf_get_current_admin_name();
                $key = 'only_for_promote'.$d_admin_id.'-'.$d_promote_account.'-yy';
                $db = 0;
                $redis = new \cmf\org\RedisSDK\RedisController(['host' => '127.0.0.1', 'port' => 6379]);
                $redis -> select($db);
                $aa = $redis -> get($key);
                $promote_ids2 = json_decode($aa);
                // 修改 渠道独占 显示
                $this->save_visible_only_for_promote($game_id, $promote_ids2);
            }

            if ($result['status']) {
                if($data['discount']<3 || $data['continue_discount']<3){
                    $this->warning($data['discount'],$data['continue_discount'],$result['game_id']);
                }
                $this->save_game_ban($data['ban_arr'],$result['game_id']);//编辑成功后,保存封禁设置的数据-20210709
                $this->success('添加成功', url('Game/ylists'));
            } else {
                $this->error($result['msg']);
            }
        }
        //获取玩家页面
        $game_ban_user_choose = $this->get_game_ban_user_choose(0);
        $this->assign('game_user_choose',$game_ban_user_choose);
        return $this->fetch();
    }

    /**
     * [编辑网页游戏]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function yedit()
    {
        if(PERMI == 1){
            $this->error('权限不足');
        }
        $model = new GameModel();
        if ($this->request->post()) {
            $data = $this->request->param();
            if (empty($data['id'])) $this->error('游戏不存在');
            //处理游戏类型-20210820-byh-s
            if(!empty($data['game_type_id'])){
                if(count($data['game_type_id'])>3){
                    $this->error('最多可以选择三个游戏类型');
                }
                $data['game_type_id'] = implode(',',$data['game_type_id']);
            }else{
                $this->error('最少选择一个游戏类型');
            }
            if(empty($data['cp_id'])){
                $this->error('所属CP不能为空');
            }
            //处理游戏类型-20210820-byh-e
            //处理溪谷客服数据-20210707-byh-start
            if(!empty($data['xg_kf_url'])){
                foreach ($data['xg_kf_url'] as $k => $v){
                    if(empty($v)){
                        unset($data['xg_kf_url'][$k]);
                    }
                }
                $data['xg_kf_url'] = array_values($data['xg_kf_url']);//键从0开始处理
                $data['xg_kf_url'] = json_encode($data['xg_kf_url']);
            }
            //处理溪谷客服数据-20210707-byh-end
            $logic = new YyLogic();
            // 获取原有渠道等级限制
            $GameAttrModel = new GameAttrModel();
            $oldLevel = $GameAttrModel->where('game_id',$data['id'])->value('promote_level_limit');
            // 获取原有的分成比例和注册单价，如果修改 则更新开始时间
            $game_info = get_game_entity($data['id'],'ratio,money');
            $data['old_ratio'] = $game_info['ratio'];
            $data['old_money'] = $game_info['money'];
            $result = $logic->edit($data);
            if ($result['status']) {
                // 删除渠道等级小于现在等级的所有已申请游戏
                $newLevel = $GameAttrModel->where('game_id',$data['id'])->value('promote_level_limit');
                if($newLevel > 0 && $newLevel > $oldLevel){
                    $applyRecord = new PromoteapplyController();
                    $applyRecord->delApplyRecord($newLevel,$data['id']);
                }
                update_promote_agent_discount($data['id'],$data['discount'],$data['continue_discount']);
                $this->edit_warning($data['discount'],$data['continue_discount'],$data['id']);
                $this->save_game_ban($data['ban_arr'],$data['id']);//编辑成功后,保存封禁设置的数据-20210709
                $this->success('编辑成功', url('Game/ylists'));
            } else {
                $this->error($result['msg']);
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $game = $model
                ->alias('g')
                ->field('g.*,login_notify_url,pay_notify_url,game_key,access_key,agent_id,game_pay_appid,apk_pck_name,
                apk_pck_sign,a.issue,a.sue_pay_type,a.xg_kf_url,a.is_control,a.bind_recharge_discount,
                a.bind_continue_recharge_discount,a.discount,a.continue_discount,a.support_introduction,a.promote_level_limit,a.promote_declare,a.discount_show_status,a.coupon_show_status')
                ->Join(['tab_game_set' => 's'], 'g.id=s.game_id', 'left')
                ->join(['tab_game_attr'=>'a'],'g.id=a.game_id','left')
                ->where('g.id', $id)->find();
        if (!$game) $this->error('游戏不存在或是已删除');
        $game = $game->toArray();
        $tag_name = explode(',', $game['tag_name']);
        $game['tag_name_one'] = $tag_name[0];
        $game['tag_name_two'] = $tag_name[1];
        $game['tag_name_three'] = $tag_name[2];
        //处理游戏类型-20210820-byh
        $game['game_type_id'] = explode(',',$game['game_type_id']);
        //增加判断获取溪谷客服数据-20210707-byh-start
        if(!empty($game['xg_kf_url'])){
            $game['xg_kf_url'] = json_decode($game['xg_kf_url'], true);
        }
        //增加判断获取溪谷客服数据-20210707-byh-end
        $this->assign('data', $game);
        //返回游戏封禁数据
        $ban_data = $this->get_game_ban($id);
        $this->assign('ban_data',$ban_data);
        //更改-处理游戏的选择页面和数据
        //获取玩家页面
        $game_ban_user_choose = $this->get_game_ban_user_choose($id);
        $this->assign('game_user_choose',$game_ban_user_choose);

        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @代充折扣预警
     * @author: 郭家屯
     * @since: 2020/9/28 10:04
     */
    protected function warning($discount,$continue_discount,$game_id)
    {
        //异常预警提醒
        $warning = [
                'type'=>4,
                'game_id'=>$game_id,
                'target'=>1,
                'record_id'=>$game_id,
                'discount_first'=>$discount,
                'discount_continued'=>$continue_discount,
                'create_time'=>time()
        ];
        Db::table('tab_warning')->insert($warning);
    }

    /**
     * @函数或方法说明
     * @代充折扣预警处理
     * @param $discount
     * @param $continue_discount
     * @param $game_id
     *
     * @author: 郭家屯
     * @since: 2020/9/28 10:12
     */
    protected function edit_warning($discount,$continue_discount,$game_id)
    {
        $warning = Db::table('tab_warning')->field('id')->where('game_id',$game_id)->where('record_id',$game_id)->where('type',4)->where('status',0)->find();
        if($warning){
            if($discount > 3 && $continue_discount>3){
                $save['op_id'] = cmf_get_current_admin_id();
                $save['op_account'] = cmf_get_current_admin_name();
                $save['status'] = 1;
                $save['op_time'] = time();
                Db::table('tab_warning')->where('id',$warning['id'])->update($save);
            }else{
//                Db::table('tab_warning')->where('id',$warning['id'])->setField('discount',$discount);
                Db::table('tab_warning')->where('id',$warning['id'])
                    ->setField(['discount_first'=>$discount,'discount_continued'=>$continue_discount]);
            }
        }else{
            if($discount <= 3 || $continue_discount  <= 3){
                $warning_data = [
                        'type'=>4,
                        'game_id'=>$game_id,
                        'target'=>1,
                        'record_id'=>$game_id,
//                        'discount'=>$discount,
                        'discount_first'=>$discount,
                        'discount_continued'=>$continue_discount,
                        'create_time'=>time()
                ];
                Db::table('tab_warning')->insert($warning_data);
            }
        }
    }


    /**
     * @更新超级签版本
     *
     * @author: zsl
     * @since: 2021/1/6 10:23
     */
    public function addSupserVersion()
    {
        $id = $this -> request -> param('id');
        $model = new GameModel();
        $gameInfo = $model -> where(['id' => $id]) -> find();
        if (empty($gameInfo)) {
            $this -> error('数据不存在');
        }
        if ($gameInfo['down_port'] != 3) {
            $this -> error('该游戏不是超级签');
        }
        $gameInfo -> super_version = $gameInfo -> super_version + 1;
        $result = $gameInfo -> save();
        if (false === $result) {
            $this -> error('更新失败');
        }
        //渠道包重新打包
        if (AUTH_PROMOTE == 1) {
            $appmodel = new \app\promote\model\PromoteapplyModel();
            $where = [];
            $where['status'] = 1;
            $where['enable_status'] = 1;
            $where['game_id'] = $gameInfo['id'];
            $where['pack_type'] = 4;
            $app_data = $appmodel->field('id,promote_id,status,enable_status,game_id,pack_type,pack_url,dow_url') -> where($where) -> select();
            if (!empty($app_data)) {
                foreach ($app_data as $k => $v) {
                    $info = [];
                    $info['MCHPromoteID'] = (string) $v['promote_id'];
                    $info['XiguSuperSignVersion'] = (string)super_sign_version($v['game_id']);
                    $url = $gameInfo['ios_game_address'] . '?appenddata=' . urlencode(json_encode($info));
                    $v -> pack_url = $url;
                    $v -> dow_url = $url;
                    $v -> save();
                }
            }

        }
        write_action_log("更新游戏【" . $gameInfo['game_name'] . "】超级签版本");
        $this -> success('更新成功');
    }


    /**
     * @已隐藏游戏
     *
     * @author: zsl
     * @since: 2021/3/19 11:05
     */
    public function banlists()
    {

        $base = new BaseController();
        $model = new GameModel();
        //添加搜索条件
        $data = $this -> request -> param();
        $map['game_status'] = 0;
        $map['platform_id'] = 0;
        $game_id = $data['game_id'];
        if ($game_id) {
            $map['tab_game.id'] = $game_id;
        }
        // 添加按照cp_id 查询
        $cp_id = $data['cp_id'];
        if ($cp_id) {
            $map['tab_game.cp_id'] = $cp_id;
        }
        $game_name = $data['game_name'];
        if ($game_name) {
            $map['game_name'] = $game_name;
        }
        $sdk_version = $data['sdk_version'];
        if ($sdk_version) {
            $map['sdk_version'] = $sdk_version;
        }
        $type = $data['type'];
        $where_str = '';//查询更改
        if ($type) {
            $where_str = "FIND_IN_SET('".$type."',game_type_id)";
        }
        $recommend_status = $data['recommend_status'];
        if ($recommend_status != '') {
            $map['recommend_status'] = ['like', '%' . $recommend_status . '%'];
        }
        $game_status = $data['game_status'];
        if ($game_status != '') {
            $map['game_status'] = $game_status;
        }
        //查询字段
        $exend['field'] = 'tab_game.id,game_name,game_type_name,sdk_version,game_appid,recommend_status,game_status,dow_num,tab_game.sort,relation_game_id,relation_game_name,promote_ids,cp_id';
        //排序优先，ID在后
        $exend['order'] = 'tab_game.sort desc,id desc';
        //关联游戏类型表
        $exend['join1'] = [['tab_game_type' => 't'], 'tab_game.game_type_id=t.id', 'left'];
        // var_dump($map);exit;
        $status_arr =  ['0' => '已下架', '1' => '上架中'];

        $data = $base -> data_list_join($model, $map, $exend,$where_str) -> each(function($item, $key) use($status_arr){
            $recommend = explode(',', $item['recommend_status']);
            $recommend_status = '';
            foreach ($recommend as $kk => $vo) {
                $recommend_status .= " " . get_info_status($vo, 7);
            }
            $item['recommend_status'] = $recommend_status;
            $item['relation_status'] = get_relation_game($item['id'], $item['relation_game_id']) === - 1 ?: (get_relation_game($item['id'], $item['relation_game_id']) ? 1 : 0);
            $item['status_name'] = $status_arr[$item['game_status']];
            if ($item['promote_ids']) {
                $item['promote_ids'] = count(array_filter(explode(',', $item['promote_ids'])));
            } else {
                $item['promote_ids'] = 0;
            }
            return $item;
        });
        // 获取分页显示
        $page = $data -> render();
        $this -> assign('page', $page);
        $this -> assign("data_lists", $data);
        $game_type = Db ::table('tab_game_type') -> field('id,type_name') -> where('status', 1) -> select();
        $this -> assign('game_type', $game_type);
        return $this -> fetch();
    }

    /**
     * 根据渠道和游戏数据获取用户----作废
     * by:byh-2021-7-8 19:25:09
     */
    public function get_promote_user()
    {
        $request = $this->request->param();
        $map['fgame_id'] = $request['game_id'];
        if($request == 'all'){//查询游戏的全部玩家
        }else{//查询对应游戏和渠道下的玩家
            $map['promote_id'] = ['IN',$request['promote_ids']];
        }
        $data = Db::table('tab_user')->field('id,account')->where($map)->select();
        if(empty($data)){
            $result = [
                'code'=>0,
                'data'=>[]
            ];
        }else{
            $result = [
                'code'=>200,
                'data'=>$data->toArray()
            ];
        }
        return json($result);
    }

    /**
     * 获取游戏的所有玩家-选择页面和选择数据
     * by:byh 2021-7-9 21:12:33
     */
    public function get_game_ban_user_choose($game_id=0)
    {
        //查询出当前游戏的所有玩家
//        if(empty($game_id)){
//            $this->assign('ban_user_ids',[]);
//            $this->assign('game_user_list',[]);
//            return $this->fetch("game_user_choose");
//        }
//        $map['fgame_id'] = $game_id;
        $map['puid'] = 0;//[排除小号
        $game_all_user = Db::table('tab_user')->field('id,account,promote_account')->where($map)->select();
        //如果已有封禁的玩家,查询出已封禁的玩家
        $where['game_id'] = $game_id;
        $ban_data = Db::table('tab_game_ban_set')->field('ban_user_ids')->where($where)->find();
        if(!empty($ban_data) && !empty($ban_data['ban_user_ids'])){
            $ban_user_ids = json_decode($ban_data['ban_user_ids'],true);
        }else{
            $ban_user_ids = [];
        }
        $this->assign('ban_user_ids',$ban_user_ids);
        $this->assign('game_user_list',$game_all_user);
        return $this->fetch("game_user_choose");
    }

    /**
     * 保存游戏的封禁设置信息
     * by:byh 2021-7-9 11:52:08
     */
    public function save_game_ban($data,$game_id)
    {
        //查询设置是否存在
        $ban_info = Db::table('tab_game_ban_set')->field('id')->where('game_id',$game_id)->find();
        //判断需要封禁的数据是否有值,都为空且之前未设置则不写入
        if(empty($data['ban_promote_ids']) && empty($data['ban_user_ids']) && empty($data['ban_devices']) && empty($data['ban_ips']) && empty($ban_info)){
            return true;
        }
        //封禁有数据 判断封禁类型
//        if((!empty($data['ban_promote_ids']) || !empty($data['ban_user_ids']) || !empty($data['ban_devices']) || !empty($data['ban_ips'])) ){
//            $this -> error('未设置封禁内容');
//        }
        //整理数据结构
        if(empty($data['ban_promote_ids'])){
            $ban_promote_ids = '';
        }else{
            $ban_promote_ids = json_encode($data['ban_promote_ids']);
        }
        if(empty($data['ban_user_ids'])){
            $ban_user_ids = '';
        } else {
            // 20210811 修改为textarea传递过来用户账号
            $banUserArr = explode(PHP_EOL, $data['ban_user_ids']);
            $banUserArr = array_map('trim', array_filter($banUserArr));
            $banUserIds = array_map('get_user_id', $banUserArr);
            $banUserIds = array_values(array_flip(array_flip($banUserIds)));
            $ban_user_ids = json_encode($banUserIds,JSON_NUMERIC_CHECK);
        }
        if(empty($data['ban_devices'])){
            $ban_devices = '';
        }else{
            $ban_devices = json_encode(explode("\r\n",$data['ban_devices']));
        }
        if(empty($data['ban_ips'])){
            $ban_ips = '';
        }else{
            $ban_ips = json_encode(explode("\r\n",$data['ban_ips']))??'';
        }
        $ban_start_time = empty($data['ban_start_time'])?0:strtotime($data['ban_start_time']);
        $ban_end_time = empty($data['ban_end_time'])?0:strtotime($data['ban_end_time']);
        if(empty($data['ban_types'])){
            $ban_types = '';
        }else{
            $ban_types = json_encode($data['ban_types']);
        }
        $save_data = [
            'ban_promote_ids'   => $ban_promote_ids,
            'ban_user_ids'      => $ban_user_ids,
            'ban_devices'       => $ban_devices,
            'ban_ips'           => $ban_ips,
            'ban_start_time'    => $ban_start_time,
            'ban_end_time'      => $ban_end_time,
            'ban_types'         => $ban_types,
        ];
        if(empty($ban_info)){//不存在,写入
            $save_data['game_id']     = $game_id;
            $save_data['create_time'] = time();
            $save_data['update_time'] = time();
            $res = Db::table('tab_game_ban_set')->insertGetId($save_data);
        }else{//存在,修改数据
            $save_data['update_time'] = time();
            $res = Db::table('tab_game_ban_set')->where('id',$ban_info['id'])->update($save_data);
        }
        if(!$res){
            $this -> error('封禁设置失败!');
        }
    }

    /**
     * 获取游戏的封禁设置信息
     * by:byh 2021-7-9 13:52:47
     */
    public function get_game_ban($game_id)
    {
        $data = Db::table('tab_game_ban_set')->where('game_id',$game_id)->find();
        if(empty($data)){
            return [];
        }
        //整理数据格式
        $data['ban_promote_ids'] = json_decode($data['ban_promote_ids'],true);
        $data['ban_user_ids'] = json_decode($data['ban_user_ids'],true);
        $data['ban_devices'] = implode("\r\n",json_decode($data['ban_devices'],true));
        $data['ban_ips'] = implode("\r\n",json_decode($data['ban_ips'],true));
        $data['ban_types'] = json_decode($data['ban_types'],true);
        return $data;
    }


    /**
     * @修改游戏名称
     *
     * @author: zsl
     * @since: 2021/7/30 21:05
     */
    public function changeGameName()
    {
        if ($this -> request -> isPost()) {
            $param = $this -> request -> param();
            $lGame = new GameLogic();
            $result = $lGame -> changeGameName($param);
            if ($result['code'] == '1') {
                $this -> success('修改成功');
            }
            $this -> error($result['msg']);
        }
        $this -> error('非法请求');
    }


    /**
     * @修改游戏名称记录
     *
     * @author: zsl
     * @since: 2021/7/31 11:57
     */
    public function changeGameNameLog()
    {
        $param = $this -> request -> param();
        $lGame = new GameLogic();
        $lists = $lGame -> changeGameNameLog($param);
        $this -> assign('data_lists', $lists);
        return $this -> fetch();
    }
    /**
     * 设置游戏配置信息
    */
    public function setGameConfig(Request $request)
    {
        $param = $request->param();
        $game_id = $param['game_id'];
        if(empty($game_id)){
            $this->error("请求缺少参数!");
        }
        if($request->post()){
            // 更新游戏参数配置
            $marking = $param['marking'] ?? '';
            $game_key = $param['game_key'] ?? '';
            $pay_notify_url = $param['pay_notify_url'] ?? '';
            $access_key = $param['access_key'] ?? '';
            $agent_id = $param['agent_id'] ?? '';
            $share_domain = $param['share_domain'] ?? '';  //分享域名
            $ccustom_service_qq = $param['ccustom_service_qq'] ?? '';  // ccustom_service_qq
            if(empty($marking) || empty($game_key) || empty($pay_notify_url) || empty($access_key)){
                return json(['code'=>-1, 'msg'=>'缺少必要参数!', 'data'=>[]]);
            }
            $updateData = [
                'game_key'=>$game_key,
                'pay_notify_url'=>$pay_notify_url,
                'access_key'=>$access_key,
                'agent_id'=>$agent_id,
                'share_domain'=>$share_domain
            ];

            $updateData2 = [
                'marking'=>$marking,
                'ccustom_service_qq'=>$ccustom_service_qq,
            ];
            Db::startTrans();
            try{
                Db::table('tab_game')->where(['id'=>$game_id])->update($updateData2);

                Db::table('tab_game_set')->where(['game_id'=>$game_id])->update($updateData);

                Db::commit();
                return json(['code'=>1, 'msg'=>'修改成功!', 'data'=>[]]);
            }catch (\Exception $e){
                Db::rollback();
                return json(['code'=>-1, 'msg'=>'服务器繁忙,请稍后重试!', 'data'=>[]]);
            }
        }
        $data = Db::table('tab_game')
            ->alias('g')
            ->field('g.id as game_id,g.marking,g.ccustom_service_qq,s.game_key,s.pay_notify_url,s.access_key,s.agent_id,s.share_domain')
            ->join(['tab_game_set' => 's'], 'g.id=s.game_id', 'left')
            ->where(['g.id'=>$game_id])
            ->find();
        $this->assign('data', $data);

        return $this->fetch();
    }
    /**
     * 设置游戏评分
    */
    public function setGameScore(Request $request)
    {
        $relation_game_id = $this->request->param('game_id', 0, 'intval');
        $value = $this->request->param('value');
        $model = new GameModel();
        $result = $model->where('relation_game_id', $relation_game_id)->setField('game_score', $value);
        if ($result !== false) {
            $this->success('设置成功');
        } else {
            $this->error('设置失败');
        }
    }
    /**
     * 设置H5游戏评分 setH5GameConfig
    */
    public function setH5GameConfig(Request $request)
    {
        $param = $request->param();
        $game_id = $param['game_id'];
        if(empty($game_id)){
            $this->error("请求缺少参数!");
        }
        if($request->post()){
            // 更新游戏参数配置
            $login_notify_url = $param['login_notify_url'] ?? '';
            $game_key = $param['game_key'] ?? '';
            $pay_notify_url = $param['pay_notify_url'] ?? '';
            $add_game_address = $param['add_game_address'] ?? '';
            $ios_game_address = $param['ios_game_address'] ?? '';
            $third_party_url = $param['third_party_url'] ?? '';  // ccustom_service_qq
            if(empty($game_key) || empty($pay_notify_url) || empty($login_notify_url)){
                return json(['code'=>-1, 'msg'=>'缺少必要参数!', 'data'=>[]]);
            }
            $updateData = [
                'game_key'=>$game_key,
                'pay_notify_url'=>$pay_notify_url,
                'login_notify_url'=>$login_notify_url,
            ];

            $updateData2 = [
                'add_game_address'=>$add_game_address,
                'ios_game_address'=>$ios_game_address,
                'third_party_url'=>$third_party_url,
            ];

            Db::startTrans();
            try{
                Db::table('tab_game')->where(['id'=>$game_id])->update($updateData2);

                Db::table('tab_game_set')->where(['game_id'=>$game_id])->update($updateData);

                Db::commit();
                return json(['code'=>1, 'msg'=>'修改成功!', 'data'=>[]]);
            }catch (\Exception $e){
                Db::rollback();
                return json(['code'=>-1, 'msg'=>'服务器繁忙,请稍后重试!', 'data'=>[]]);
            }
        }
        $data = Db::table('tab_game')
            ->alias('g')
            ->field('g.id as game_id,s.login_notify_url,s.pay_notify_url,s.game_key,g.add_game_address,g.ios_game_address,g.third_party_url')
            ->join(['tab_game_set' => 's'], 'g.id=s.game_id', 'left')
            ->where(['g.id'=>$game_id])
            ->find();
        $this->assign('data', $data);

        return $this->fetch();
    }
    // created by wjd 2021-8-30 14:18:18
    // 设置页游接口 abandon (弃用)
    public function setyyGameInterfaceInfo(Request $request)
    {
        $param = $request->param();
        $game_id = $param['game_id'];
        if(empty($game_id)){
            $this->error("请求缺少参数!");
        }
        if($request->post()){
            // 更新游戏参数配置
            $interface_name = $param['interface_name'] ?? '';
            $interface_tag = $param['interface_tag'] ?? '';
            $interface_unid = $param['interface_unid'] ?? '';
            $interface_login_url = $param['interface_login_url'] ?? '';
            $interface_pay_url = $param['interface_pay_url'] ?? '';
            $interface_role_url = $param['interface_role_url'] ?? '';
            $interface_login_key = $param['interface_login_key'] ?? '';
            $interface_pay_key = $param['interface_pay_key'] ?? '';
            $interface_remark = $param['interface_remark'] ?? '';

            $operateData = [
                'name'=>$interface_name,
                'tag'=>$interface_tag,
                'unid'=>$interface_unid,
                'login_url'=>$interface_login_url,
                'pay_url'=>$interface_pay_url,
                'role_url'=>$interface_role_url,
                'login_key'=>$interface_login_key,
                'pay_key'=>$interface_pay_key,
            ];
            foreach($operateData as $k=>$v){
                if(empty($v)){
                    return json(['code'=>-1, 'msg'=>'缺少必要参数!', 'data'=>[]]);
                }
            }
            $operateData['remark'] = $interface_remark;
            $game_info = Db::table('tab_game')->where(['id'=>$game_id])->field('id,game_name,sdk_version,interface_id')->find();
            $interface_id = $game_info['interface_id'] ?? '';
            if(empty($interface_id)){  // 该游戏未设置过页游借口, 添加页游接口, 修改游戏表关联
                Db::startTrans();
                try{
                    $interface_id_tmp = Db::table('tab_game_interface')->insertGetId($operateData);
                    Db::table('tab_game')->where(['id'=>$game_id])->update(['interface_id'=>$interface_id_tmp]);

                    Db::commit();
                    return json(['code'=>1, 'msg'=>'修改成功!', 'data'=>[]]);
                }catch (\Exception $e){
                    Db::rollback();
                    return json(['code'=>-1, 'msg'=>'服务器繁忙,请稍后重试!', 'data'=>[]]);
                }
            }else{ // 该游戏已经设置过页游接口, 直接修改页游接口
                $update_res = Db::table('tab_game_interface')->where(['id'=>$interface_id])->update($operateData);
                if($update_res){
                    return json(['code'=>1, 'msg'=>'修改成功!', 'data'=>[]]);
                }

            }

            return json(['code'=>-1, 'msg'=>'服务器繁忙,请稍后重试!', 'data'=>[]]);
            // Db::table('tab_game_interface')->where(['id'=>$game_id])->update($updateData2);

        }

        $data = Db::table('tab_game')
            ->alias('g')
            ->field('g.id as game_id,g.interface_id,inter.*')
            ->join(['tab_game_interface' => 'inter'], 'g.interface_id=inter.id', 'left')
            ->where(['g.id'=>$game_id])
            ->find();
        $this->assign('data', $data);

        return $this->fetch();
    }


    /**
     * @下载游戏对接文件
     *
     * @author: zsl
     * @since: 2021/9/22 9:17
     */
    public function downDockingFile()
    {
        $param = $this -> request -> param();
        $lGame = new GameLogic();
        $result = $lGame -> downDockingFile($param);
        if ($result['code'] == '0') {
            $this -> error($result['msg']);
        }
        //响应下载文件
        $file_dir = $result['data']['file_dir'];
        $file_name = $result['data']['file_name'];
        if (!file_exists($file_dir . $file_name)) {
            header('HTTP/1.1 404 NOT FOUND');
        } else {
            //以只读和二进制模式打开文件
            $file = fopen($file_dir . $file_name, "rb");
            //告诉浏览器这是一个文件流格式的文件
            Header("Content-type: application/octet-stream");
            //请求范围的度量单位
            Header("Accept-Ranges: bytes");
            //Content-Length是指定包含于请求或响应中数据的字节长度
            Header("Accept-Length: " . filesize($file_dir . $file_name));
            //用来告诉浏览器，文件是可以当做附件被下载，下载后的文件名称为$file_name该变量的值。
            Header("Content-Disposition: attachment; filename=" . $file_name);
            //读取文件内容并直接输出到浏览器
            echo fread($file, filesize($file_dir . $file_name));
            fclose($file);
            exit ();
        }
    }


}
