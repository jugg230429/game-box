<?php
/**
 * @属性说明
 *
 * @var ${TYPE_HINT}
 */

namespace app\issuesy\logic;

use app\common\controller\BaseController;
use app\game\model\GameModel;
use app\issue\model\IssueGameModel;
use cmf\controller\HomeBaseController;
use think\Db;

class GameLogic extends HomeBaseController
{


    public function page($request)
    {
        $base = new BaseController();
        $model = new IssueGameModel();
        $map = [];
        $map['sdk_version'] = ['in', '1,2'];

        if (!empty($request['id'])) {
            $map['id'] = $request['id'];
        }
        if (!empty($request['game_name'])) {
            $map['game_name'] = $request['game_name'];
        }

        $where_str = '';//游戏类型查询更改
        if (!empty($request['game_type_id'])) {
            $where_str = "FIND_IN_SET('".$request['game_type_id']."',game_type_id)";
        }
        if (!empty($request['sdk_version'])) {
            $map['sdk_version'] = $request['sdk_version'];
        }
        if ($request['status'] === '0' || $request['status'] === '1') {
            $map['status'] = $request['status'];
        }
        $start_time = $request['start_time'];
        $end_time = $request['end_time'];
        if ($start_time && $end_time) {
            $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['create_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['create_time'] = ['egt', strtotime($start_time)];
        }


        //查询字段
        $exend['field'] = '*';
        //排序优先，ID在后
        $exend['order'] = 'sort desc,create_time desc,id desc';
        //关联游戏类型表
        $data = $base -> data_list($model, $map, $exend,$where_str);
        return $data;
    }


    /**
     * @查询未导入游戏列表
     *
     * @author: zsl
     * @since: 2020/7/10 19:46
     */
    public function xt_page($request)
    {
        $base = new BaseController();
        $model = new GameModel();
        $map = [];
        if (!empty($request['game_ids'])) {
            $map['tab_game.id'] = ['in', $request['game_ids']];
        } else {
            $ids = Db ::table('tab_issue_game') -> column('game_id');
            $map['tab_game.id'] = ['not in', $ids];
        }
        $map['sdk_version'] = ['in', '1,2'];
        $exend['row'] = 50;
        //查询字段
        $exend['field'] = 'tab_game.id as game_id,tab_game.sdk_version,game_name,game_type_id,game_type_name,game_appid,short,login_notify_url,pay_notify_url,material_url,icon,cover,screenshot,features,introduction,screen_type,game_key,access_key,agent_id,tab_game.dev_name';
        $exend['join1'] = [['tab_game_set' => 'set'], 'tab_game.id = set.game_id'];
        //排序优先，ID在后
        $exend['order'] = 'create_time desc,tab_game.id desc';
        $exend['row'] = 1000000;

        $data = $base -> data_list_join($model, $map, $exend);
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

            $value['game_name'] = str_replace('(苹果版)','',$value['game_name']);
            $value['game_name'] = str_replace('(安卓版)','',$value['game_name']);

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
            $issuegamemodel -> dev_name = $value['dev_name'];
            $issuegamemodel -> status = 1;
            $issuegamemodel -> save();
        }
        Db ::commit();
        return json(['code' => 200, 'msg' => '导入成功']);
    }


    public function edit($param)
    {
        $mGame = new IssueGameModel();
        $param['game_type_name'] = get_game_type_name_str($param['game_type_id']);//游戏类型名称修改
        $param['screenshot'] = $param['screenshot'] ? implode(',', $param['screenshot']) : '';
        $res = $mGame -> allowField(true) -> save($param, ['id' => $param['id']]);
        return $res;
    }


    public function info($id)
    {
        $mGame = new IssueGameModel();
        $field = '';
        $where = [];
        $where['id'] = $id;
        $info = $mGame -> field($field) -> where($where) -> find();
        return $info;
    }


}