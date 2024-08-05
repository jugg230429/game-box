<?php

namespace app\oa\controller;

use app\member\model\UserModel;
use app\oa\model\PromoteModel;
use app\promote\model\PromoteapplyModel;
use app\promote\validate\PromoteValidate;
use app\recharge\model\SpendModel;
use think\Db;
use think\Request;

class ApiController extends ApiBaseController
{

    protected $studio;

    public function __construct(Request $request = null)
    {
        parent ::__construct($request);
        $param = $this -> request -> param();
        //验证请求是否超时
//        if (!$this -> vTimeOut($param['t'])) {
//            $result['code'] = 0;
//            $result['msg'] = '请求超时';
//            $result['data'] = [];
//            $this -> response($result);
//        }
        //获取平台信息
        $studio = $this -> getStudioInfo($param['appid']);
        if (empty($studio)) {
            $result['code'] = 0;
            $result['msg'] = '平台不存在或被禁用';
            $result['data'] = [];
            $this -> response($result);
        }
        $this -> studio = $studio;

    }


    /**
     * @创建推广员
     *
     * @author: zsl
     * @since: 2021/3/2 19:05
     */
    public function createPromote()
    {
        $result = ['code' => 1, 'msg' => '请求成功', 'data' => []];
        $param = $this -> request -> param();
        //验证签名
        $postData = [];
        $postData['appid'] = $param['appid'];
        $postData['promote_account'] = $param['promote_account'];
        $postData['promote_password'] = $param['promote_password'];
        $postData['t'] = $param['t'];
        $sign = $this -> vSign($postData, $this -> studio['api_key']);
        if ($param['sign'] != $sign) {
            $result['code'] = 0;
            $result['msg'] = '验签失败';
            return json($result);
        }
        $data = [];
        $data['account'] = $param['promote_account'];
        $data['password'] = $param['promote_password'];
        // 验证数据
        $validate = new PromoteValidate();
        if (!$validate -> scene('api_add') -> check($data)) {
            // 验证失败 输出错误信息
            $result['code'] = 0;
            $result['msg'] = $validate -> getError();
            return json($result);
        }
        // 创建推广员
        $model = new PromoteModel();
        $data['password'] = cmf_password($data['password']);
        $data['oa_studio_id'] = $this -> studio['id'];
        $data['create_time'] = time();
        $res = $model -> isUpdate(false) -> save($data);
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '创建失败';
            return json($result);
        }
        return json($result);
    }

    /**
     * @验证推广员
     *
     * @author: zsl
     * @since: 2021/3/2 20:19
     */
    public function verifyPromote()
    {
        $result = ['code' => 1, 'msg' => '验证成功', 'data' => []];
        $param = $this -> request -> param();
        //验证签名
        $postData = [];
        $postData['appid'] = $param['appid'];
        $postData['promote_account'] = $param['promote_account'];
        $postData['promote_password'] = $param['promote_password'];
        $postData['t'] = $param['t'];
        $sign = $this -> vSign($postData, $this -> studio['api_key']);
        if ($param['sign'] != $sign) {
            $result['code'] = 0;
            $result['msg'] = '验签失败';
            return json($result);
        }
        //获取推广员密码
        $model = new PromoteModel();
        $where = [];
        $where['account'] = $param['promote_account'];
        $where['status'] = 1;
        $promotePassword = $model -> where($where) -> value('password');
        if (empty($promotePassword)) {
            $result['code'] = 0;
            $result['msg'] = '推广员不存在或被禁用';
            return json($result);
        }
        //验证推广员密码
        if (cmf_password($param['promote_password']) != $promotePassword) {
            $result['code'] = 0;
            $result['msg'] = '推广员密码验证失败';
            return json($result);
        }
        //绑定成功,更新公会id
        $model -> where(['account' => $param['promote_account']]) -> setField('oa_studio_id', $this -> studio['id']);
        return json($result);
    }

    /**
     * @获取可申请游戏
     *
     * @author: zsl
     * @since: 2021/3/10 14:33
     */
    public function game()
    {
        $result = ['code' => 1, 'msg' => '请求成功', 'data' => []];
        $param = $this -> request -> param();
        //验证签名
        $postData = [];
        $postData['appid'] = $param['appid'];
        $postData['promote_account'] = $param['promote_account'];
        $postData['t'] = $param['t'];
        $sign = $this -> vSign($postData, $this -> studio['api_key']);
        if ($param['sign'] != $sign) {
            $result['code'] = 0;
            $result['msg'] = '验签失败';
            return json($result);
        }
        //获取推广员信息
        $mPromote = new PromoteModel();
        $field = "id,account,parent_id,status,promote_level,game_ids";
        $promoteWhere = [];
        $promoteWhere['account'] = $param['promote_account'];
        $promoteWhere['status'] = 1;
        $promoteInfo = $mPromote -> field($field) -> where($promoteWhere) -> find();
        if (empty($promoteInfo)) {
            $result['code'] = 0;
            $result['msg'] = '推广员不存在或被禁用';
            return json($result);
        }
        $where = [];
        if ($promoteInfo['promote_level'] == 1) {
            $ids = get_promote_apply_game_id($promoteInfo['id']);
            //渠道禁止申请游戏
            if ($promoteInfo['game_ids']) {
                $ids = array_merge($ids, explode(',', $promoteInfo['game_ids']));
            }
            $game_ids = get_promote_apply_game_id($promoteInfo['id'], 1);//已申请游戏
            if ($game_ids) {
                $ids = array_merge($ids, $game_ids);
            }
            $where['id'] = ['notin', $ids];
        } else {
            $promote_id = $promoteInfo['parent_id'];
            $ids = get_promote_apply_game_id($promote_id);//可申请游戏
            if ($promoteInfo['game_ids']) {
                $top_ids = explode(',', $promoteInfo['game_ids']);
                $ids = array_diff($ids, $top_ids);
            }
            $game_ids = get_promote_apply_game_id($promoteInfo['id'], 1);//已申请游戏
            if ($game_ids) {
                $ids = array_diff($ids, $game_ids);
            }
            if ($ids) {
                $where['id'] = ['in', $ids];
            } else {
                $where['id'] = - 1;
            }
        }
        //获取可申请游戏
        $gamemodel = new \app\game\model\GameModel;
        $page = empty($param['page']) ? 1 : $param['page'];
        $limit = empty($param['limit']) ? 1 : $param['limit'];
        $where['game_status'] = 1;
        $where['down_port'] = ['in', [1, 3]];
        $where['third_party_url'] = '';
        if ($param['sys_type'] == '1') {
            //手游
            $where['sdk_version'] = ['in', [1, 2]];
        } elseif ($param['sys_type'] == '2') {
            //H5
            $where['sdk_version'] = 3;
        } else {
            //页游
            $where['sdk_version'] = 4;
        }
        if (!empty($param['game_name'])) {
            $where['game_name'] = ['like', '%' . $param['game_name'] . '%'];
        }
        $result['count'] = $gamemodel -> where($where) -> count();
        $result['page'] = $page;
        $result['limit'] = $limit;
        $gameField = "id,game_name,sdk_version,game_type_name,ratio,money,sort,icon";
        $gameLists = $gamemodel -> field($gameField)
                -> where($where)
                -> page($page, $limit)
                -> order('sort desc')
                -> select() -> each(function($item){
                    $item -> icon = cmf_get_image_url($item -> icon);
                });
        $result['data'] = $gameLists;
        return json($result);
    }


    /**
     * @推广员申请游戏
     *
     * @author: zsl
     * @since: 2021/3/11 10:37
     */
    public function apply()
    {
        $result = ['code' => 1, 'msg' => '申请成功', 'data' => []];
        $param = $this -> request -> param();
        //验证签名
        $postData = [];
        $postData['appid'] = $param['appid'];
        $postData['game_id'] = $param['game_id'];
        $postData['promote_account'] = $param['promote_account'];
        $postData['t'] = $param['t'];
        $sign = $this -> vSign($postData, $this -> studio['api_key']);
        if ($param['sign'] != $sign) {
            $result['code'] = 0;
            $result['msg'] = '验签失败';
            return json($result);
        }
        //获取推广员信息
        $mPromote = new PromoteModel();
        $field = "id,account,parent_id,status,promote_level,game_ids";
        $promoteWhere = [];
        $promoteWhere['account'] = $param['promote_account'];
        $promoteWhere['status'] = 1;
        $promoteInfo = $mPromote -> field($field) -> where($promoteWhere) -> find();
        if (empty($promoteInfo)) {
            $result['code'] = 0;
            $result['msg'] = '推广员不存在或被禁用';
            return json($result);
        }
        //申请游戏
        $map['game_id'] = $param['game_id'];
        $map['promote_id'] = $promoteInfo['id'];
        $res = Db ::table('tab_promote_apply') -> field('id') -> where($map) -> find();
        if (!empty($res)) {
            $result['code'] = 0;
            $result['msg'] = '已申请过，请不要重复申请';
            return json($result);
        } else {
            $game = Db ::table('tab_game') -> field('game_name,sdk_version,money,ratio,down_port,ios_game_address') -> find($param['game_id']);
            if (empty($game)) {
                $result['code'] = 0;
                $result['msg'] = '申请失败，游戏不存在';
                return json($result);
            }
            $add['game_id'] = $param['game_id'];
            $add['promote_id'] = $promoteInfo['id'];
            $add['sdk_version'] = $game['sdk_version'];
            $add['promote_money'] = $promoteInfo['promote_level'] == 1 ? $game['money'] : 0;
            $add['promote_ratio'] = $promoteInfo['promote_level'] == 1 ? $game['ratio'] : 0;
            $add['down_port'] = $game['down_port'];
            $add['ios_game_address'] = $game['ios_game_address'];
            $model = new PromoteapplyModel;
            $apply = $model -> apply($add);
            if (!$apply) {
                $result['code'] = 0;
                $result['msg'] = '申请失败';
                return json($result);
            }
        }
        return json($result);
    }


    /**
     * @推广员游戏
     *
     * @author: zsl
     * @since: 2021/3/11 11:44
     */
    public function my()
    {
        $result = ['code' => 1, 'msg' => '请求成功', 'data' => []];
        $param = $this -> request -> param();
        //验证签名
        $postData = [];
        $postData['appid'] = $param['appid'];
        $postData['promote_account'] = $param['promote_account'];
        $postData['is_pass'] = $param['is_pass'];
        $postData['t'] = $param['t'];
        $sign = $this -> vSign($postData, $this -> studio['api_key']);
        if ($param['sign'] != $sign) {
            $result['code'] = 0;
            $result['msg'] = '验签失败';
            return json($result);
        }
        //获取推广员信息
        $mPromote = new PromoteModel();
        $field = "id,account,parent_id,status,promote_level,game_ids";
        $promoteWhere = [];
        $promoteWhere['account'] = $param['promote_account'];
        $promoteWhere['status'] = 1;
        $promoteInfo = $mPromote -> field($field) -> where($promoteWhere) -> find();
        if (empty($promoteInfo)) {
            $result['code'] = 0;
            $result['msg'] = '推广员不存在或被禁用';
            return json($result);
        }
        //获取我的游戏
        $model = new PromoteapplyModel;
        $field = "a.id,game_id,g.game_name,g.game_type_name,g.sdk_version,g.icon,a.promote_ratio,a.promote_money,
                a.enable_status,g.relation_game_id";
        $where = [];
        $where['a.status'] = $param['is_pass'] ? 1 : 0;
        if ($promoteInfo['promote_level'] == 1) {
            if ($promoteInfo['game_ids']) {
                $where['a.game_id'] = ['notin', explode(',', $promoteInfo['game_ids'])];
            }
        } else {
            $promote_id = $promoteInfo['parent_id'];
            $ids = get_promote_apply_game_id($promote_id);//可申请游戏
            //禁止申请游戏
            if ($promoteInfo['game_ids']) {
                $top_ids = explode(',', $promoteInfo['game_ids']);
                $ids = array_diff($ids, $top_ids);
            }
            $where['a.game_id'] = ['in', $ids];
        }
        if (!empty($param['game_name'])) {
            $where['g.game_name'] = ['like', "%" . $param['game_name'] . "%"];
        }
        if (!empty($param['sdk_version'])) {
            $where['g.sdk_version'] = $param['sdk_version'];
        }
        $where['g.game_status'] = 1;
        $where['a.promote_id'] = $promoteInfo['id'];
        $where['g.down_port'] = ['in', [1, 3]];
        $where['g.third_party_url'] = '';
        $page = empty($param['page']) ? 1 : $param['page'];
        $limit = empty($param['limit']) ? 1 : $param['limit'];
        $result['count'] = $model -> alias('a')
                -> join(['tab_game' => 'g'], 'g.id=a.game_id')
                -> where($where)
                -> count();
        $result['page'] = $page;
        $result['limit'] = $limit;
        $data = $model -> alias('a')
                -> field($field)
                -> join(['tab_game' => 'g'], 'g.id=a.game_id')
                -> where($where)
                -> order('a.id desc')
                -> page($page, $limit)
                -> select() -> each(function($item) use ($promoteInfo){
                    $item -> icon = cmf_get_image_url($item -> icon);
                    if ($item -> sdk_version == '4') {
                        // 页游
                        $item -> channel_page_url = url('@media/game/ydetail', ['game_id' => $item -> relation_game_id, 'pid' => $promoteInfo['id']], true, true);
                    } elseif ($item -> sdk_version == '3') {
                        // H5游戏
                        $item -> channel_page_url = cmf_get_domain() . "/mobile/Downfile/indexh5?gid=" . $item -> relation_game_id . "&pid=" . $promoteInfo['id'];
                    } else {
                        // 手游
                        $item -> channel_page_url = cmf_get_domain() . "/mobile/Downfile/index?gid=" . $item -> relation_game_id . "&pid=" . $promoteInfo['id'];
                    }
                });
        $result['data'] = $data;
        return json($result);
    }


    /**
     * @游戏渠道打包
     *
     * @author: zsl
     * @since: 2021/3/11 16:33
     */
    public function package()
    {
        $result = ['code' => 1, 'msg' => '操作成功，已加入打包队列，请耐心等待', 'data' => []];
        $param = $this -> request -> param();
        //验证签名
        $postData = [];
        $postData['appid'] = $param['appid'];
        $postData['promote_account'] = $param['promote_account'];
        $postData['game_id'] = $param['game_id'];
        $postData['t'] = $param['t'];
        $sign = $this -> vSign($postData, $this -> studio['api_key']);
        if ($param['sign'] != $sign) {
            $result['code'] = 0;
            $result['msg'] = '验签失败';
            return json($result);
        }
        //获取推广员信息
        $mPromote = new PromoteModel();
        $field = "id,account,parent_id,status,promote_level,game_ids";
        $promoteWhere = [];
        $promoteWhere['account'] = $param['promote_account'];
        $promoteWhere['status'] = 1;
        $promoteInfo = $mPromote -> field($field) -> where($promoteWhere) -> find();
        if (empty($promoteInfo)) {
            $result['code'] = 0;
            $result['msg'] = '推广员不存在或被禁用';
            return json($result);
        }
        //渠道打包
        $where = [];
        $where['game_id'] = $param['game_id'];
        $where['promote_id'] = $promoteInfo['id'];
        $res = Db ::table('tab_promote_apply') -> field('id,status,sdk_version') -> where($where) -> find();
        if (empty($res)) {
            $result['code'] = 0;
            $result['msg'] = '您还未申请该游戏，请先去申请游戏';
            return json($result);
        } elseif ($res['status'] == 0) {
            $result['code'] = 0;
            $result['msg'] = '游戏还未审核通过，请耐心等待';
            return json($result);
        } else {
            $game = Db ::table('tab_game_source') -> field('id,game_name,file_url') -> where(['game_id' => $param['game_id']]) -> find();
            if (empty($game) || $game['file_url'] == '') {
                $result['code'] = 0;
                $result['msg'] = '申请失败，游戏原包不存在';
                return json($result);
            }
            $model = new PromoteapplyModel;
            if ($res['sdk_version'] == 3 || $res['sdk_version'] == 4) {
                $result['code'] = 0;
                $result['msg'] = '该游戏暂不支持渠道打包';
                return json($result);
            } else {
                $apply = $model -> where(['id' => $res['id']]) -> update(['enable_status' => 2, 'pack_type' => 1]);//准备渠道打包
            }
            if (false === $apply) {
                $result['code'] = 0;
                $result['msg'] = '操作失败';
                return json($result);
            }
        }
        return json($result);
    }


    /**
     * @获取用户注册信息
     *
     * @author: zsl
     * @since: 2021/3/3 11:52
     */
    public function register()
    {
        $result = ['code' => 1, 'msg' => '请求成功', 'data' => []];
        $param = $this -> request -> param();
        //验证签名
        $postData = [];
        $postData['t'] = $param['t'];
        $postData['appid'] = $param['appid'];
        $postData['promote_account'] = $param['promote_account'];
        $postData['last_id'] = $param['last_id'];
        $sign = $this -> vSign($postData, $this -> studio['api_key']);
        if ($param['sign'] != $sign) {
            $result['code'] = 0;
            $result['msg'] = '验签失败';
            return json($result);
        }
        //查询下级所有推广员id
        $mPromote = new PromoteModel();
        $promote_id = $mPromote -> where(['account' => $param['promote_account']]) -> value('id');
        $promote_ids = get_zi_promote_id($promote_id);
        $promote_ids[] = $promote_id;
        //获取数据
        $mUser = new UserModel();
        $field = "id,account,promote_id,promote_account,fgame_id,fgame_name,register_time,register_ip,equipment_num";
        $where = [];
        $where['promote_id'] = ['in', $promote_ids];
        $where['id'] = ['gt', $param['last_id']];
        $where['puid'] = 0;
        $data = $mUser -> field($field) -> where($where) -> order('id asc') -> limit('10000') -> select();
        if (!empty($data)) {
            foreach ($data as &$v) {
                $v['promote_id'] = $promote_id;
                $v['promote_account'] = $param['promote_account'];
            }
            unset($v);
            $result['data'] = $data;
        }
        return json($result);
    }

    /**
     * @获取用户注册信息
     *
     * @author: zsl
     * @since: 2021/3/3 11:52
     */
    public function recharge()
    {
        $result = ['code' => 1, 'msg' => '请求成功', 'data' => []];
        $param = $this -> request -> param();
        //验证签名
        $postData = [];
        $postData['t'] = $param['t'];
        $postData['appid'] = $param['appid'];
        $postData['promote_account'] = $param['promote_account'];
        $postData['last_id'] = $param['last_id'];
        $sign = $this -> vSign($postData, $this -> studio['api_key']);
        if ($param['sign'] != $sign) {
            $result['code'] = 0;
            $result['msg'] = '验签失败';
            return json($result);
        }
        //查询下级所有推广员id
        $mPromote = new PromoteModel();
        $promote_id = $mPromote -> where(['account' => $param['promote_account']]) -> value('id');
        $promote_ids = get_zi_promote_id($promote_id);
        $promote_ids[] = $promote_id;
        //获取数据
        $mSpend = new SpendModel();
        $field = "id,user_id,user_account,game_id,game_name,server_id,server_name,game_player_id,game_player_name,role_level,
        promote_id,promote_account,pay_order_number,props_name,pay_amount,cost,pay_time,pay_way,spend_ip";
        $where = [];
        $where['promote_id'] = ['in', $promote_ids];
        $where['id'] = ['gt', $param['last_id']];
        $where['pay_status'] = 1;
        $data = $mSpend -> field($field) -> where($where) -> order('id asc') -> limit('10000') -> select();
        if (!empty($data)) {

            foreach ($data as &$v) {
                $v['pay_way_str'] = get_pay_way($v['pay_way']);
                $v['promote_id'] = $promote_id;
                $v['promote_account'] = $param['promote_account'];
            }
            unset($v);
            $result['data'] = $data;
        }
        return json($result);
    }


}
