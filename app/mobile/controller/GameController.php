<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 13:54
 */

namespace app\mobile\controller;

use app\common\lib\Util\HolidayUtil;
use app\common\logic\GameLogic;
use app\common\logic\PayLogic;
use app\game\model\GameModel;
use app\game\model\ServerNoticeModel;
use app\member\model\UserConfigModel;
use app\member\model\UserModel;
use app\game\logic\WelfareLogic;
use app\site\service\PostService;
use app\game\model\ServerModel;
use app\game\model\GiftbagModel;
use app\common\controller\BaseHomeController;
use app\game\model\GamecollectModel;
use app\site\model\AdvModel;
use app\union\logic\Game;
use think\Db;

class GameController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('index/index'));
            };
        }
    }

    /**
     *[游戏分类]
     * @return mixed
     * @author chen
     */
    public function game_center()
    {
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $where_str = '';//查询更改
        if (!empty(input('type_id'))) {
            $where_str = "FIND_IN_SET('".input('type_id')."',game_type_id)";
        }
        if (MOBILE_PID > 0) {
            $game_ids = get_promote_game_id(MOBILE_PID);
            $map['id'] = ['in', $game_ids];
        }
        $model = new GameModel();
        if(MODEL == 1){
            $map['sdk_version'] = get_devices_type();
        }else{
            $map['sdk_version'] = 3;
        }
        $data = $model->getRecommendGameLists($map, 99,$where_str)->each(function ($item, $key) {
            $item['icon'] = cmf_get_image_url($item['icon']);
            return $item;
        });
        $this->assign('data', $data);

        return $this->fetch();
    }

    /**
     *[新上架，推荐，热门游戏]
     * @return mixed
     * @author chen
     */
    public function lists()
    {
        if ($this->request->isAjax()) {
            $type = $this->request->param('type', 1, 'intval');
            if (empty($type)) $type = 1;
            $p = $this->request->param('p', 1, 'intval');
            $row = 10;
            $map['recommend_status'] = ['like', '%' . $type . '%'];
            $model = new GameModel();
            $map['sdk_version'] = get_devices_type();
            if (MOBILE_PID > 0) {
                $game_ids = get_promote_game_id(MOBILE_PID);
                $map['id'] = ['in', $game_ids];
            }
            $data = $model->getRecommendGameLists1($map, $p, $row)->each(function ($item, $key) {
                $item['icon'] = cmf_get_image_url($item['icon']);
                $item['tag_name'] = explode(',', $item['tag_name']);
                $item['dow_num'] =get_simple_number($item['dow_num']);
                $item['down_status'] = get_down_status2($item['relation_game_id'], get_devices_type()) ? url('Downfile/down', ['game_id' => $item['relation_game_id'], 'sdk_version' => get_devices_type()]) : 0;
                return $item;
            });
            $data = $data->toArray();
            $count = $model->getGameCount($map);
            if (empty($count)) {
                return json(['code' => 0, 'msg' => '']);
            } else {
                $count_p = ceil($count / $row);  //总页数
                return json(['code' => 1, 'data' => $data, 'count_p' => $count_p]);
            }
        }
        return $this->fetch();
    }


    /**
     * [游戏列表]
     * @author 郭家屯[gjt]
     */
    public function getGameList($map = '')
    {

        $model = new GameModel();
        $map['game_status'] = 1;
        $map['dow_status'] = 1;

        //联盟游戏
        if (MOBILE_PID > 0) {
            $map['id'] = ['in', get_promote_game_id(MOBILE_PID)];
        }
        $extend['field'] = 'relation_game_id,sdk_version,relation_game_name as game_name,icon,game_type_name,count(id) as sdk_type,game_size,create_time';
        $extend['row'] = 20;
        $extend['order'] = 'sort desc,id desc';
        $base = new BaseHomeController();
        $lists = $base->data_list($model, $map, $extend)->each(function ($item, $key) {
            $url = cmf_get_domain() . "/mobile/game/detail/game_id/" . $item['relation_game_id'];
            return $item;
        });

        $this->assign('data', $lists);
    }

    /**
     * [详情页]
     * @author chen
     */
    public function detail()
    {
        $out_trade_no = $this->request->param('out_trade_no');
        if(!empty($out_trade_no)){
            $ios_pay_to_download_order_info = Db::table('tab_game_ios_pay_to_download_order')->where(['pay_order_number'=>$out_trade_no])->find();
            $game_id = $ios_pay_to_download_order_info['game_id'];
            $promote_id = $ios_pay_to_download_order_info['promote_id'];
        }else{
            $game_id = $this->request->param('game_id');
            $promote_id = $this->request->param('pid',0,'intval');
        }

        if (empty($game_id)) $this->error('游戏不存在');
        $model = new GameModel();
        $data  = $model->getGameDetail(['id'=>$game_id]);
        if($data['sdk_version'] != 3 && $data['sdk_version'] != get_devices_type()){
            $sdk_version = get_devices_type();
            $map['relation_game_id'] = $data['relation_game_id'];
            $map['sdk_version'] = $sdk_version;
            $data = $model->getGameDetail($map);
        }
        //20210428-byh-start
        if(empty($data)){//如果data为空,判断查询get_devices_type和当前ID对应关联的游戏是否存在
            $data = $model
                ->field('id,relation_game_id,relation_game_name as game_name,cover,features,icon,game_type_name,game_score,groom,game_type_name,game_size,screenshot,introduction,sdk_version,tag_name,dow_num,game_type_id,down_port,and_dow_address,ios_dow_plist,add_game_address,ios_game_address,down_port,game_address_size,screen_type,fullscreen,vip_table_pic,sdk_scheme')
                ->where('relation_game_id',$game_id)
                ->where('sdk_version',get_devices_type())
                ->where('game_status',1)->find();
            if(!empty($data)){
                $data = $data->toArray();
                $attr_data = get_game_attr_entity($game_id,'bind_recharge_discount,bind_continue_recharge_discount');
                $data = array_merge($data,(array)$attr_data);
            }
        }
        //20210428-byh-start
        if (empty($data)) {
            $this->error('游戏不存在',url('Index/index'));
        }
        $game_id = $data['id'];
        $data['screenshot'] = empty($data['screenshot']) ? '' : explode(',', $data['screenshot']);
        $data['tag_name'] = empty($data['tag_name']) ? '' : explode(',', $data['tag_name']);
        $data['introduction'] = empty($data['introduction']) ? '' : explode(PHP_EOL, trim($data['introduction']));
        $this->assign('data', $data);
        $this->huodong([$data['relation_game_id']]);//活动
        $this->gift($game_id);//礼包
        $this->server($game_id);//区服
        $this->likeGame($game_id, $data['game_type_id']);//喜欢的游戏
        //获取收藏状态
        if (UID) {
            $collectmodel = new GamecollectModel();
            $collect = $collectmodel->where('user_id', UID)->where('game_id', $game_id)->where('status', 1)->find();
            if ($collect) {
                $this->assign('is_collect', 1);
            }
        }
        $this->assign('promote_id',$promote_id);
        $this->assign('user_agent_md5',md5($this->request->server('HTTP_USER_AGENT')));
        // 获取开关
        $showStatus = get_game_show_status($game_id);
        $this->assign('discount_show_status',$showStatus['discount_show_status']);
        $this->assign('coupon_show_status',$showStatus['coupon_show_status']);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @收藏游戏
     * @author: 郭家屯
     * @since: 2019/7/9 11:12
     */
    public function collect()
    {
        $game_id = $this->request->param('game_id', 0, 'intval');
        if (empty($game_id)) {
            $this->error('操作失败');
        }
        if (!session('member_auth')) {
            $this->error('操作失败');
        }
        $logic = new GameLogic();
        $result = $logic->collect_option(UID,$game_id);
        if ($result['result']) {
            if($result['status']==1){
                return json(['code'=>1,'msg'=>'已收藏','collect'=>1]);
            }else{
                return json(['code'=>1,'msg'=>'已取消收藏','collect'=>0]);
            }
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * [某个游戏区服显示]
     * @author chen
     */
    public function server($game_id = '')
    {
        $model = new ServerModel();
        $map['game_id'] = $game_id;

        $data_last = $model->getGameServer($map);//今日和即将开服
        $data_before = $model->getBeforeGameServer($map);//已经开服
        $data = array_merge($data_last, $data_before);
        foreach ($data as $k => $v) {
            $today = date('m-d');
            $server_time = date('m-d', $v['start_time']);
            $data[$k]['today'] = $today == $server_time ? '今日' : '';
        }
        $this->assign('server', $data);
    }

    /**
     * [区服列表显示]
     * @author chen
     */
    public function server_lists()
    {
        $model = new ServerModel();
        if(MODEL == 1){
            $map['s.sdk_version'] = get_devices_type();
        }else{
            $map['s.sdk_version'] = 3;
        }
        if (MOBILE_PID > 0) {
            $game_ids = get_promote_game_id(MOBILE_PID);
            $map['game_id'] = ['in', $game_ids];
        }
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示
        $data = $model->getWapServerLists($map,session('member_auth.user_id'));
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * [礼包列表]
     * @author 郭家屯[gjt]
     */
    public function gift($game_id = '')
    {
        $model = new GiftbagModel();
        $map['and_id|ios_id|h5_id'] = $game_id;
        $data = $model->getGiftIndexLists(session('member_auth.user_id'), $map, 50);
        foreach ($data as $k => $v) {
            $start = $v['start_time'] == 0 ? '永久' : date('Y-m-d', $v['start_time']);
            $end = $v['end_time'] == 0 ? '永久' : date('Y-m-d', $v['end_time']);
            $data[$k]['time'] = $start . ' 至 ' . $end;
            if ($v['surplus'] == 0 && $v['received'] == 0) {
                unset($data[$k]);
            }
        }
        $this->assign('gift', $data);
    }

    /**
     * [攻略]
     * @author 郭家屯[gjt]
     */
    public function gonglue($relation_game_id = '')
    {
        $postService = new PostService();
        $map['category'] = [5];
        $map['game_id'] = get_promote_relation_game_id($relation_game_id);
        $map['post_status'] = 1;
        $data = $postService->GameArticleList($map, false, 10);
        $this->assign('gonglue', $data);
    }

    /**
     * [活动公告]
     * @author 郭家屯[gjt]
     */
    public function huodong($game_id = '')
    {
        $postService = new PostService();
        $map['category'] = [2,3, 4,5];
        $map['game_id'] = get_promote_relation_game_id($game_id);
        $map['post_status'] = 1;
        $data = $postService->GameArticleList($map, false, 50)->toArray();
        $gg = [];
        $hd = [];
        foreach ($data as $k=>$v){
            if($v['category_id'] == 4){
                $gg[] = $v;
            }else{
                $hd[] = $v;
            }
        }
        $new_data = array_merge($hd,$gg);
        $this->assign('article', $new_data);
    }

    /**
     * [你可能喜欢的游戏]
     * @author cb
     */
    public function likeGame($game_id, $game_type_id = '', $page = 1)
    {
        $model = new GameModel();
        if (MOBILE_PID > 0) {
            $game_ids = get_promote_game_id(MOBILE_PID);
            $game_ids[] = $game_id;
            $map['id'] = ['in', $game_ids];
        }else{
            $map['id'] = ['neq', $game_id];
        }
        $map['game_type_id'] = $game_type_id;
        if(MODEL == 1){
            $map['sdk_version'] = get_devices_type();
        }else{
            $map['sdk_version'] = 3;
        }
        $gamepage = $model->where($map)->field('id')->order('sort desc')->paginate(4, false, ['query' => $this->request->param()])->toArray();
        if($page>$gamepage['last_page']){
            $page = 1;
        }
        $game_ids = $model->where($map)->field('id')->order('sort desc')->page($page,4)->select()->toArray();
        $game_ids = array_column($game_ids, 'id');

        $game_ids = array_slice($game_ids, 0, 4);
        $map1['id'] = ['in', $game_ids];
        $data = $model->getRecommendGameLists($map1, 4)->each(function ($item, $key) {
            $item['icon'] = cmf_get_image_url($item['icon']);
            $item['url'] = url('Game/detail', ['game_id' => $item['id']]);
            return $item;
        });
        if ($this->request->isAjax()) {
            if (empty($data)) {
                return json(['code' => 0, 'msg' => '']);
            } else {
                return json(['code' => 1, 'data' => $data,'page'=>$page+1]);
            }
        } else {
            $this->assign('like', $data);
        }
    }

    /**
     * [搜索]
     * @author chen
     */
    public function search()
    {
        $this->hotGame();//热门游戏
        return $this->fetch();
    }

    /**
     * [热门游戏]
     * @author chen
     */
    public function hotGame($map = [])
    {
        $model = new \app\game\model\GameModel();
        $map['recommend_status'] = ['like', '%2%'];
        $wap_model = cmf_get_option('wap_set')['model_wap']?:3;
        if(PERMI == 3 && $wap_model == 3){
            $map['sdk_version'] = ['in',[get_devices_type(),3]];
        }else{
            if(PERMI == 3){
                $map['sdk_version'] = $wap_model == 1 ? get_devices_type() : 3;
            }else{
                $map['sdk_version'] = PERMI == 1 ? get_devices_type() : 3;
            }
        }
        //联盟游戏
        if (MOBILE_PID > 0) {
            $map['id'] = ['in', get_promote_game_id(MOBILE_PID)];
        }
        $data = $model->getRecommendGameLists($map, 7)->each(function ($item, $key) {
            $item['icon'] = cmf_get_image_url($item['icon']);
            $item['tag_name'] = explode(',', $item['tag_name']);
            return $item;
        });
        $this->assign('hot_game', $data->toArray());
    }

    /**
     * [搜索游戏]
     * @author chen
     */
    public function searchGame($game_name = '')
    {
        if (empty($game_name)) {
            return json(['code' => 0, 'msg' => '']);
        }
        $model = new \app\game\model\GameModel();
        $wap_model = cmf_get_option('wap_set')['model_wap']?:3;
        if(PERMI == 3 && $wap_model == 3){
            $map['sdk_version'] = ['in',[get_devices_type(),3]];
        }else{
            if(PERMI == 3){
                $map['sdk_version'] = $wap_model == 1 ? get_devices_type() : 3;
            }else{
                $map['sdk_version'] = PERMI == 1 ? get_devices_type() : 3;
            }
        }
        $map['game_name'] = ['like', '%' . $game_name . '%'];
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示

        //联盟游戏
        if (MOBILE_PID > 0) {
            $map['id'] = ['in', get_promote_game_id(MOBILE_PID)];
        }
        $data = $model->getRecommendGameLists($map, 10)->each(function ($item, $key) {
            $item['url'] = url('game/detail', ['game_id' => $item['id']]);
            return $item;
        });
        if (empty($data->toArray())) {
            return json(['code' => 0, 'msg' => '']);
        } else {
            return json(['code' => 1, 'data' => $data]);
        }
    }

    /**
     *[获取下载状态]
     * @param int $relation_game_id
     * @param int $sdk_version
     * @author chen
     */
    function get_down_status($relation_game_id = 0, $sdk_version = 0)
    {
        $status = get_down_status($relation_game_id, $sdk_version);
        if ($status) {
            $down_url = url('Downfile/down', ['game_id' => $relation_game_id, 'sdk_version' => $sdk_version]);
            return json(['code' => 1, 'msg' => $down_url]);
        } else {
            return json(['code' => 0, 'msg' => '']);
        }
    }

    /**
     * @函数或方法说明
     * @游戏排行榜
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/10/16 16:30
     */
    public function rank_lists(){
        //热门游戏
        $hotgame = $this->get_first_game(1);
        $this->assign('hotgame',$hotgame);
        //推荐游戏
        $resgame = $this->get_first_game(3);
        $this->assign('resgame',$resgame);
        //最新游戏
        $newgame = $this->get_first_game(2);
        $this->assign('newgame',$newgame);
        //下载游戏
        $downgame = $this->get_first_game(4);
        $this->assign('downgame',$downgame);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @获取首页游戏
     * @param int $type
     *
     * @return \think\response\Json
     *
     * @author: 郭家屯
     * @since: 2019/10/16 17:12
     */
    public function get_first_game($type=1,$p=1,$limit=8)
    {
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $order = "sort desc,id desc";
        if(MODEL == 1){
            $map['sdk_version'] = get_devices_type();
        }else{
            $map['sdk_version'] = 3;
        }
        if(MOBILE_PID > 0){
            $game_ids = get_promote_game_id(MOBILE_PID);
            $map['id'] = ['in',$game_ids];
        }
        switch ($type){
            case 1:
                $map['recommend_status'] = ['like', '%2%'];
                break;
            case 2:
                $map['recommend_status'] = ['like', '%3%'];
                break;
            case 3:
                $map['recommend_status'] = ['like', '%1%'];
                break;
            case 4:
                $order = 'dow_num desc,id desc';
                break;
            default:
                break;
        }
        $model = new GameModel();
        $data = $model->getMoreGame($map,$order,$p,$limit)->each(function($item,$key){
            $item['icon'] = cmf_get_image_url($item['icon']);
            $item['url'] = url('Game/detail',['game_id'=>$item['id']]);
        });
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取更多游戏
     * @author: 郭家屯
     * @since: 2019/10/16 16:30
     */
    public function get_more_game($type=1)
    {
        $data = $this->request->param();
        $p = $data['p'];
        $limit = $data['limit'];
        $order = "sort desc,id desc";
        if(MODEL == 1){
            $map['sdk_version'] = get_devices_type();
        }else{
            $map['sdk_version'] = 3;
        }
        if(MOBILE_PID > 0){
            $game_ids = get_promote_game_id(MOBILE_PID);
            $map['id'] = ['in',$game_ids];
        }
        switch ($data['type']){
            case 1:
                $map['recommend_status'] = ['like', '%2%'];
                break;
            case 2:
                $map['recommend_status'] = ['like', '%3%'];
                break;
            case 3:
                $map['recommend_status'] = ['like', '%1%'];
                break;
            case 4:
                $order = 'dow_num desc,id desc';
                break;
            default:
                break;
        }
        $model = new GameModel();
        $list = $model->getMoreGame($map,$order,$p,$limit)->each(function($item,$key){
            $item['icon'] = cmf_get_image_url($item['icon']);
            $item['url'] = url('Game/detail',['game_id'=>$item['id']]);
            $item['dow_num'] =get_simple_number($item['dow_num']);
        });
        return json($list);
    }
    //折扣返利游戏
    public function discount_game(){
        $data = [];
        //绑币折扣游戏
        $bind_game = $this->get_bind();
        foreach ($bind_game as $key=>$v){
            $data[$v['game_id']][] = $v;
        }
        //首冲续充游戏
        $recharge_game = $this->get_recharge();
        foreach ($recharge_game as $key=>$v){
            $data[$v['game_id']][] = $v;
        }
        //返利游戏
        $rebate_game = $this->get_rebate();
        foreach ($rebate_game as $key=>$v){
            $data[$v['game_id']][] = $v;
        }
        foreach ($data as $key=>$v){
            $data[$key] = array_reduce($v, 'array_merge', array());
        }
        array_multisort(array_column($data,'sort'),SORT_DESC,SORT_NUMERIC,$data);
        $this->assign('data',$data);
//        dump($data);die;
        return $this->fetch();
    }
    //代金券
    public function game_detail_coupon(){
        $relation_game_id = $this->request->param('relation_game_id');
        if(empty($relation_game_id))$this->error('参数错误');
        $user_id = session('member_auth.user_id');
        if($user_id > 0){
            $user = get_user_entity($user_id,false,'promote_id,parent_id');
            $promote_id = $user['promote_id'];
        }else{
            $promote_id = 0;
        }
        $paylogic = new PayLogic();
        //优惠券信息
        $coupon = $paylogic->get_detail_coupon($user_id,$relation_game_id,$promote_id);
        $this->assign('coupon',$coupon);
        return $this->fetch();
    }
    //游戏详情--折扣返利
    public function game_detail_discount(){
        $game_id = $this->request->param('id');
        if(empty($game_id))$this->error('参数错误');
        $user_id = session('member_auth.user_id');
        if($user_id > 0){
            $user = get_user_entity($user_id,false,'promote_id,parent_id');
            $promote_id = $user['promote_id'];
        }else{
            $promote_id = 0;
        }
        $paylogic = new PayLogic();
        //返利信息
        $rebate = $paylogic->get_detail_ratio($game_id,$promote_id);
        $this->assign('rebate',$rebate);
        //折扣信息
        $discount = $paylogic->get_detail_discount($game_id,$promote_id,$user_id);
        $this->assign('discount',$discount);
        //获取折扣-绑币充值折扣新增-byh-20210825-start
        $lPay = new \app\common\logic\PayLogic();
        $data = $lPay -> get_detail_bind_discount($game_id, $promote_id,$user_id);
        //处理一下因移动端返回的数据变动,开关关闭,删除
        if(empty($data['first_status'])){
            unset($data['first_status']);
            unset($data['first_discount']);
        }
        if(empty($data['continue_status'])){
            unset($data['continue_status']);
            unset($data['continue_discount']);
        }
        //获取折扣-绑币充值折扣新增-byh-20210825-end
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @领取优惠券
     * @author: 郭家屯
     * @since: 2020/2/5 11:19
     */
    public function getcoupon()
    {
        $coupon_id = $this->request->param('coupon_id');
        if(empty($coupon_id))$this->error('参数错误');
        $user_id = session('member_auth.user_id');
        if(empty($user_id))$this->error('请先登录');
        $logic = new GameLogic();
        $result = $logic->getCoupon($user_id,$coupon_id);
        if($result){
            $this->success('领取成功');
        }else{
            $this->error('领取失败');
        }
    }

    /**
     * @函数或方法说明
     * @获取返利游戏
     * @return \think\response\Json
     *
     * @author: 郭家屯
     * @since: 2020/2/7 15:24
     */
    protected function get_rebate()
    {
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示
        $logic = new WelfareLogic();
        $user_id = session('member_auth.user_id')?:0;
        if($user_id > 0){
            $user = get_user_entity($user_id,false,'promote_id,parent_id');
            $promote_id = $user['promote_id'];
        }else{
            $promote_id = 0;
        }
        if(MODEL == 1){
            $map['g.sdk_version'] = get_devices_type();
        }else{
            $map['g.sdk_version'] = 3;
        }
        if(MOBILE_PID > 0){
            $game_ids = get_promote_game_id(MOBILE_PID);
            $map['g.id'] = ['in',$game_ids];
        }
        $data = $logic->get_game_rebate_list($promote_id,$map);
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取首冲续充游戏
     * @return \think\response\Json
     *
     * @author: 郭家屯
     * @since: 2020/2/7 15:24
     */
    protected function get_recharge()
    {
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示
        $logic = new WelfareLogic();
        $user_id = session('member_auth.user_id')?:0;
        if($user_id > 0){
            $user = get_user_entity($user_id,false,'promote_id,parent_id');
            $promote_id = $user['promote_id'];
        }else{
            $promote_id = 0;
        }
        if(MODEL == 1){
            $map['g.sdk_version'] = get_devices_type();
        }else{
            $map['g.sdk_version'] = 3;
        }
        if(MOBILE_PID > 0){
            $game_ids = get_promote_game_id(MOBILE_PID);
            $map['g.id'] = ['in',$game_ids];
        }
        $data = $logic->get_recharge_list($promote_id,$map,$user_id);
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取绑币充值游戏
     * @return \think\response\Json
     *
     * @author: 郭家屯
     * @since: 2020/2/7 15:24
     */
    protected function get_bind()
    {
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示
        $logic = new WelfareLogic();
        $user_id = session('member_auth.user_id')?:0;
        if($user_id > 0){
            $user = get_user_entity($user_id,false,'promote_id,parent_id');
            $promote_id = $user['promote_id'];
        }else{
            $promote_id = 0;
        }
        if(MODEL == 1){
            $map['g.sdk_version'] = get_devices_type();
        }else{
            $map['g.sdk_version'] = 3;
        }
        if(MOBILE_PID > 0){
            $game_ids = get_promote_game_id(MOBILE_PID);
            $map['g.id'] = ['in',$game_ids];
        }
        $data = $logic->get_bind_list($map,$promote_id,$user_id);
        return $data;
    }

    /**
     * @函数或方法说明
     * @打开游戏
     * @author: 郭家屯
     * @since: 2020/6/15 10:33
     */
    public function open_game()
    {
        $id = $this->request->param('game_id');
        $small_id = $this->request->param('small_id',0,'intval');
        $model = new GameModel();
        $game = $model->field('id,screen_type,game_name,sdk_version,game_status,third_party_url,is_https,load_cover,is_force_real,platform_id')->find($id);
        if($game['sdk_version'] !=3){
            $this->error('游戏不存在');
        }
        if($game['game_status'] != 1){
            $this->error('游戏未开启');
        }
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($this->request->param('pid'),$id,session('member_auth.user_id'),'',get_client_ip(),$type=1)){
            $this -> error( "当前被禁止登录，请联系客服");
        }
        if($game['is_https'] == 1 && !is_https()){
            $url = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            return redirect($url);
        }
        if($game['is_https'] == 0 && is_https()){
            $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            //header($url);
             return redirect($url);
        }
        // 简化版平台判断登录用户
        $promote_id = $this->request->param('pid',0);
        $userToken = $this->request->param('user_token','');
        if(is_third_platform($promote_id) && !empty($userToken)){
            $UserLogic = new \app\webplatform\logic\UserLogic($promote_id);
            $user_id = $UserLogic->h5_user_login($userToken,$id);
            if($user_id>0){
                $userInfo = get_user_entity($user_id);
                $userInfo['user_id'] = $user_id;
                session('member_auth',$userInfo);
                self::$userSession = $session = array(
                    'login_auth' => session('member_auth') ? 1 : 0,
                    'login_user_id' => session('member_auth.user_id'),
                    'login_account' => session('member_auth.account'),
                    'login_head_img' => cmf_get_image_url(session('member_auth.head_img'))
                );
                $platform_url = $UserLogic->get_platform_url();
                $this->assign('platform_url',$platform_url);
                $this->assign('session', $session);
                $this->assign('third_user_id',$user_id);
                $this->assign('is_third_platform', 1);
            }
        }else{
            $user_id = session('member_auth.user_id');
        }
        if($user_id){
            //是否选择小号进入游戏
            if (!empty($small_id) && $user_id != $small_id && cmf_get_option('wap_set')['is_open_sub_account']=='1') {
                //验证是否为当前的登录账号下的小号
                $smallInfo = get_h5_small_info($user_id, $small_id, $id);
                if (!empty($smallInfo)) {
                    $user_id = $smallInfo['id'];
                }
            }
            session('member_auth.small_id',$user_id);
            $usermodel = new UserModel();
            //游戏登录记录
            $user = get_user_entity($user_id,false,'id,account,promote_id,promote_account,puid,fgame_id,parent_id,parent_name,register_type');
            $data['type'] = 1;
            $data['game_id'] = $id;
            $data['game_name'] = $game['game_name'];
            $login_record = $usermodel->user_login_record($user,$data);
            //微端登录账号信息
            $password = get_user_entity(session('member_auth.user_id'),false,'password');
            $wd_token = get_h5_token($user_id, session('member_auth.account'),$password['password']);
            $this->assign("wd_token", $wd_token);
            // 简化版跳转第三方游戏连接
            if($game['platform_id'] > 0){
                $playcontroller = new \app\thirdgame\logic\UserLogic();
                $game_url = $playcontroller->get_play_info($user_id,$id,MEDIA_PID);
                if($game_url === false){
                    $this->error('游戏地址错误');
                }
                return $this->redirect($game_url);
            }
            if($game['third_party_url']){
                $login_url = $game['third_party_url'];
            }else{
                $playcontroller  = new \app\sdkh5\controller\GameController();
                $play_info = $playcontroller->get_play_info($user_id,$id,PID);
                $login_url = $play_info['game_url'];
            }
            $this->assign('login_url',$login_url);
            //官方防沉迷
            $configmodel = new UserConfigModel();
            $age_prevent = $configmodel->where('name', 'age_prevent')->field('config,status')->find()->toArray();
            $age_prevent_config = json_decode($age_prevent['config'], true);
            $prevent_way = empty($age_prevent_config['way']) ? '2':$age_prevent_config['way'];
            $this->assign('prevent_way',$prevent_way);//是否走官方防沉迷规则 1:官方 2:自定义
            //查询未成年玩家剩余在线时间
            $surplusSecond = HolidayUtil ::checkPlayGameStatus();
            if (false === $surplusSecond) {
                $surplus_second = 0;
            } else {
                $surplus_second = $surplusSecond;
            }
            $this -> assign('surplus_second', $surplus_second);
        }
        // 获取H5退出游戏广告图
        $advModel = new AdvModel();
        $h5_game_logout_adv = $advModel->getAdv('h5_close_account');
        $h5_game_logout_adv_pic = $h5_game_logout_adv['data'] ?? '';
        // var_dump($h5_game_logout_adv_pic);exit;
        $this->assign('h5_game_logout_adv_pic',$h5_game_logout_adv_pic);

        $this->assign('screen_type',$game['screen_type']);
        $this->assign('load_cover',$game['load_cover']);
        $this->assign('game_id',$game['id']);
        $this->assign('game_name',$game['game_name']);
        $this->assign('is_force_real',$game['is_force_real']);
        return $this->fetch();
    }

    /*
       微端包自动登录
    */
    public function auto_login(){
        $token = $this->request->param('token');
        if($token){
            $this->h5_write_session($token);
            return json(array('status'=>1,'msg'=>'登录成功'));
        }
    }

    protected function h5_write_session($token){
        $token = think_decrypt($token);
        if(empty($token)){
            cookie::delete('cookie_account');
            return json(array('status'=>0,'msg'=>'信息过期，请重新登录'));
        }
        $info = json_decode($token,true);
        $user = get_user_entity($info['user_id'],false);
        if(empty($user)){
            cookie::delete('cookie_account');
            return json(array('status'=>0,'msg'=>'用户不存在'));
        }
        if($user['password'] != $info['password']){
            cookie::delete('cookie_account');
            return json(array('status'=>0,'msg'=>'密码错误，请重新登录'));
        }
        /* 记录登录SESSION和COOKIES */
        $user['user_id'] = $user['id'];
        session('member_auth', $user);
    }

    /**
     * @函数或方法说明
     * @区服关注
     * @author: 郭家屯
     * @since: 2020/10/9 11:11
     */
    public function server_notice()
    {
        $server_id = $this->request->param('server_id');
        $user_id = session('member_auth.user_id');
        if(empty($user_id)){
            $this->error('请先登录');
        }
        if(empty($server_id)){
            $this->error('参数错误');
        }
        $model = new ServerNoticeModel();
        $result = $model->set_notice($user_id,$server_id);
        if($result){
            $this->success('设置成功');
        }else{
            $this->error('设置失败');
        }
    }


    /**
     * @领券中心
     *
     * @author: zsl
     * @since: 2020/12/9 16:03
     */
    public function coupon()
    {

        $param = $this -> request -> param();
        //获取代金券游戏列表
        $mGame = new GameModel();
        $where = [];
        if (!empty($param['game_name'])) {
            $where['g.game_name'] = ['like', '%' . $param['game_name'] . '%'];
        }

        $user_id = session('member_auth.user_id');
        if($user_id > 0){
            $user = get_user_entity($user_id,false,'promote_id,parent_id');
            $promote_id = $user['promote_id'];
        }else{
            $promote_id = 0;
        }

        $gameLists = $mGame -> couponGame($where,$promote_id);
        $this -> assign('game_lists', $gameLists);
        return $this -> fetch();
    }
    /**
     * 判断游戏是否封禁状态-ajax请求-登录
     * by:byh-2021-7-14 10:35:00
     */
    public function get_ban_status()
    {
        $data = $this->request->param();
        //判断类型
        if($data['type'] != 1) return json(['code'=>0,'msg'=>'类型错误']);
        //判断游戏
        if(empty($data['game_id'])) return json(['code'=>0,'msg'=>'数据错误']);
        //判断版本对应的游戏id
        $game_id = \think\Db::table('tab_game')->where(['relation_game_id'=>$data['game_id']])->value('id');
        $res = judge_user_ban_status($data['promote_id'],$game_id,session('member_auth.user_id'),$data['equipment_num'],get_client_ip(),$data['type']);
        if(!$res){
            return json(['code'=>0,'msg'=>'您当前被禁止登录游戏，请联系客服']);
        }
        return json(['code'=>1,'msg'=>'success']);

    }

}
