<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */

namespace app\issue\logic;

use app\game\model\GameModel;
use app\issue\model\IssueGameApplyModel;
use app\issue\model\IssueGameModel;
use think\Request;
use think\Db;
use app\common\controller\BaseController;

class GameLogic
{
    public function xt_page($request)
    {
        $base = new BaseController();
        $model = new GameModel();
        $map = [];
        if (!empty($request['game_ids'])) {
            $map['tab_game.id'] = ['in', $request['game_ids']];
        }
        $map['sdk_version'] = 3;
        $map['issue_game_id'] = 0;
        // 查询字段
        $exend['field'] = 'tab_game.id as game_id,tab_game.sdk_version,game_name,game_type_id,game_type_name,game_appid,short,login_notify_url,pay_notify_url,material_url,icon,cover,screenshot,features,introduction,screen_type,game_key,access_key,agent_id';
        $exend['join1'] = [['tab_game_set' => 'set'], 'tab_game.id = set.game_id'];
        // 排序优先，ID在后
        $exend['order'] = 'create_time desc,tab_game.id desc';
        $data = $base -> data_list_join($model, $map, $exend);
        return $data;
    }

    public function page()
    {
        $base = new BaseController();
        $model = new IssueGameModel();
        $map = [];
        $map['sdk_version'] = 3;
        //查询字段
        $exend['field'] = '*';
        //排序优先，ID在后
        $exend['order'] = 'sort desc,id desc';
        $data = $base -> data_list($model, $map, $exend);
        return $data;
    }

    public function add($request)
    {
        if (empty($request['game_ids'])) {
            return json(['code' => 0, 'msg' => '请选择游戏数据']);
        }
        $gameData = $this -> xt_page(['game_ids' => $request['game_ids']]) -> toarray()['data'];
        if (empty($gameData)) {
            return json(['code' => 0, 'msg' => '游戏数据错误']);
        }
        Db ::startTrans();
        foreach ($gameData as $key => $value) {
            $gamemodel = new GameModel();
            $issuegamemodel = new IssueGameModel();
            $issuegamemodel -> game_id = $value['game_id'];
            $issuegamemodel -> game_name = $value['game_name'];
            $issuegamemodel -> sdk_version = $value['sdk_version'];
            $issuegamemodel -> game_type_id = $value['game_type_id'];
            $issuegamemodel -> game_type_name = $value['game_type_name'];
            $issuegamemodel -> game_appid = $value['game_appid'];
            $issuegamemodel -> game_key = $value['game_key'];
            $issuegamemodel -> access_key = $value['access_key'];
            $issuegamemodel -> agent_id = $value['agent_id'];
            $issuegamemodel -> short = $value['short'];
            $issuegamemodel -> login_notify_url = $value['login_notify_url'];
            $issuegamemodel -> pay_notify_url = $value['pay_notify_url'];
            $issuegamemodel -> material_url = $value['material_url'];
            $issuegamemodel -> icon = $value['icon'];
            $issuegamemodel -> cover = $value['cover'];
            $issuegamemodel -> screenshot = $value['screenshot'];
            $issuegamemodel -> features = $value['features'];
            $issuegamemodel -> introduction = $value['introduction'];
            $issuegamemodel -> screen_type = $value['screen_type'];
            $issuegamemodel -> save();
            $gameData = $gamemodel ::get($value['game_id']);
            $gameData -> issue_game_id = $issuegamemodel -> id;
            $gameData -> allowField(true) -> save();
        }
        Db ::commit();
        return json(['code' => 200, 'msg' => '导入成功']);
    }


    /**
     * @获取可申请游戏列表
     *
     * @author: zsl
     * @since: 2020/7/13 20:02
     */
    public function platformGameLists($param)
    {
        //获取平台禁止游戏
        $lPlatform = new PlatformLogic();
        $banGameIds = $lPlatform -> banGameIds($param['platform_id']);
        //获取可申请游戏
        $mGame = new IssueGameModel();
        $field = 'g.id,g.game_id,g.game_name,g.sdk_version,g.icon,g.ff_ratio,g.material_url,a.id as apply_id,a.platform_config,a.status as apply_status,a.enable_status,a.ratio,a.dispose_time';
        $query = [];
        $where = [];
        $where['g.status'] = 1;
        $where['g.sdk_version'] = $param['sdk_version']; // ['in',[1,2]]
        if ($param['status'] === '0') {
            $where['a.status'] = $param['status'];
            $query['status'] = $param['status'];
        }
        if($param['status'] === '1'){
            $where['a.status'] = 1;
            $where['a.enable_status'] = 1;
            $query['status'] = $param['status'];
        }
        if ($param['status'] === '2') {
            $where['a.status'] = 1;
            $where['a.enable_status'] = 0;
            $query['status'] = $param['status'];

        }
        if ($param['status'] === '3') {
            $where['a.id'] = null;
            $query['status'] = $param['status'];
        }
        if (!empty($param['game_id'])) {
            $where['g.game_id'] = $param['game_id'];
            $query['game_id'] = $param['game_id'];
        } else {
            $where['g.id'] = ['not in', $banGameIds];
        }
        $gameLists = $mGame -> alias('g')
                -> field($field)
                -> join(['tab_issue_game_apply' => 'a'], 'a.game_id=g.id and a.platform_id=' . $param['platform_id'] . '', 'left')
                -> where($where)
                -> order('g.sort desc,id desc')
                -> paginate($param['row'] ? $param['row'] : 10,false,['query'=>$query]);
        return $gameLists;
    }


    /**
     * @申请游戏
     *
     * @author: zsl
     * @since: 2020/7/14 17:08
     */
    public function applyGame($param)
    {
        $result = ['code' => 1, 'msg' => '申请成功', 'data' => []];
        //获取平台禁止游戏
        $lPlatform = new PlatformLogic();
        $banGameIds = $lPlatform -> banGameIds($param['platform_id']);
        //获取游戏详情
        $mGame = new IssueGameModel();
        $where = [];
        $where['id'] = $param['id'];
        $where['game_id'] = ['not in', $banGameIds];
        $where['status'] = 1;
        $gameInfo = $mGame -> where($where) -> find() -> toArray();
        if (empty($gameInfo)) {
            $result['code'] = 0;
            $result['msg'] = '游戏不存在';
            return $result;
        }
        //验证是否申请过
        $mGameApply = new IssueGameApplyModel();
        $where = [];
        $where['game_id'] = $param['id'];
        $where['platform_id'] = $param['platform_id'];
        $isApply = $mGameApply -> where($where) -> count();
        if ($isApply) {
            $result['code'] = 0;
            $result['msg'] = '不能重复申请';
            return $result;
        }
        //新增申请数据
        $res = $mGameApply -> add($gameInfo, PID, OID);
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '申请失败';
            return $result;
        }
        return $result;

    }


    /**
     * @获取游戏地址
     *
     * @author: zsl
     * @since: 2020/7/15 11:35
     */
    public function getGameUrl($param)
    {
        $result = ['code' => 1, 'msg' => '获取成功', 'data' => []];
        $mIssueGameApply = new IssueGameApplyModel();
        $where = [];
        $where['id'] = $param['apply_id'];
        $where['platform_id'] = $param['platform_id'];
        $where['open_user_id'] = $param['open_user_id'];
        $where['status'] = 1;
        $applyInfo = $mIssueGameApply -> where($where) -> find();
        if (empty($applyInfo)) {
            $result['code'] = 0;
            $result['msg'] = '记录不存在';
            return $result;
        }
        if ($applyInfo['sdk_version'] == '4') {
            //页游游戏
            $urls = [
                    'game_url' => url('issueyy/game/play', ['channel_code' => $applyInfo['platform_id'], 'game_id' => $applyInfo['game_id']], true, true),
                    'role_url' => url('issueyy/role/role', ['channel_code' => $applyInfo['platform_id'], 'game_id' => $applyInfo['game_id']], true, true),
                    'callback_url' => url('issueyy/pay/callback', ['channel_code' => $applyInfo['platform_id'], 'game_id' => $applyInfo['game_id']], true, true),
            ];
        }elseif ($applyInfo['sdk_version'] == '3') {
            //H5游戏
            $urls = [
                    'game_url' => url('issueh5/game/play', ['channel_code' => $applyInfo['platform_id'], 'game_id' => $applyInfo['game_id']], true, true),
                    'callback_url' => url('issueh5/pay/callback', ['channel_code' => $applyInfo['platform_id'], 'game_id' => $applyInfo['game_id']], true, true),
            ];
        } else {
            //手游
            $urls = [
                    'game_url' => '',
                    'callback_url' => url('issuesy/callback/pay_callback', ['channel_code' => $applyInfo['platform_id'], 'game_id' => $applyInfo['game_id']], true, true),
            ];
        }
        if($param['settle_type'] > 0){
            $urls['callback_url'] = url('@callback/sue/callback',[],true,true);
        }
        $result['data'] = $urls;
        return $result;
    }

    /**
     * @获取申请记录平台配置
     *
     * @author: zsl
     * @since: 2020/7/15 15:55
     */
    public function getApplyPlatformConfig($param)
    {
        if (empty($param['apply_id'])) {
            return false;
        }
        $mApply = new IssueGameApplyModel();
        $platformConfig = $mApply -> where(['id' => $param['apply_id']]) -> value('platform_config');
        $config = json_decode($platformConfig, true);
        $config = array_map('htmlspecialchars_decode',$config);
        $config = array_map('htmlspecialchars_decode',$config);
        return $config;
    }

    public function getApplyServiceConfig($param)
    {
        if (empty($param['apply_id'])) {
            return false;
        }
        $mApply = new IssueGameApplyModel();
        $platformConfig = $mApply -> where(['id' => $param['apply_id']]) -> value('service_config');
        $config = json_decode($platformConfig, true);
        return $config;
    }

}
