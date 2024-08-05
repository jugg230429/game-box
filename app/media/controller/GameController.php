<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 13:54
 */

namespace app\media\controller;

use app\common\lib\Util\HolidayUtil;
use app\common\logic\AntiaddictionLogic;
use app\common\logic\GameLogic;
use app\common\logic\PayLogic;
use app\common\logic\PointLogic;
use app\common\logic\PointTypeLogic;
use app\game\model\GameModel;
use app\game\model\GameTypeModel;
use app\member\model\UserConfigModel;
use app\member\model\UserModel;
use app\site\model\AdvModel;
use app\site\service\PostService;
use app\game\model\ServerModel;
use app\game\model\GiftbagModel;
use app\common\controller\BaseHomeController;
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
    public function game_ycenter()
    {
        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map2['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示
        $map2['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        //推荐游戏
        $this->recomment_ygame($map);
        //游戏列表
        $this->ygame_list($map);
        //区服列表
        $this->yserver($map2);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @手游列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/10/30 15:11
     */
    public function game_center()
    {
        $this->slide('slider_game');//轮播图
        $map['sdk_version'] = ['in',[1,2]];

        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示

            $this->getGameList($map);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @H5列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/10/30 15:12
     */
    public function game_hcenter()
    {
        $this->slide('slider_game');//轮播图

        $map['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $map['sdk_version'] = 3;
        $this->getGameList($map);
        return $this->fetch();
    }
    /**
     * [热门游戏]
     * @author 郭家屯[gjt]
     */
    public function hotGame($map = [])
    {
        $model = new GameModel();
        $map['recommend_status'] = ['like', '%2%'];
        $data = $model->getGameLists($map, 10);
        $this->assign('hot_game', $data);
    }

    /**
     * [热门游戏]
     * @author 郭家屯[gjt]
     */
    public function recomment_ygame($map = [])
    {
        $model = new GameModel();
        $map['recommend_status'] = ['like', '%1%'];
        $map['sdk_version'] = 4;
        //联盟游戏
        if (MEDIA_PID > 0) {
            $map['id'] = ['in', get_promote_game_id(MEDIA_PID)];
        }
        $data = $model->getGameLists($map, 3);
        $this->assign('recommend_game', $data);
    }

    /**
     * [游戏列表]
     * @author 郭家屯[gjt]
     */
    public function getGameList($map=[])
    {
        $data = $this->request->param();
        $model = new GameModel();
        $map['game_status'] = 1;
        $map['sdk_area'] = 0; //不显示海外游戏
        //$map['dow_status'] = 1;
        if ($data['game_name']) {
            $map['game_name'] = ['like', '%' . $data['game_name'] . '%'];
        }
        if ($data['sdk_version']) {
            $map['sdk_version'] = $data['sdk_version'];
        }
        $where_str = '';//查询更改
        if ($data['game_type_id']) {
            $where_str = "FIND_IN_SET('".$data['game_type_id']."',game_type_id)";
        }
        if ($data['short']) {
            $map['short'] = ['like', $data['short'] . '%'];
        }
        //联盟游戏
        if (MEDIA_PID > 0) {
            $map['id'] = ['in', get_promote_game_id(MEDIA_PID)];
        }
        $extend['field'] = 'relation_game_id as id,relation_game_id,dow_num,sdk_version,relation_game_name as game_name,icon,game_type_name,count(id) as sdk_type,if(down_port=1,game_size,game_address_size) as game_size,down_port,create_time';
        $extend['row'] = 15;
        $extend['order'] = 'sort desc,id desc';
        $extend['group'] = 'relation_game_id';
        $base = new BaseHomeController();
        $lists = $base->data_list($model, $map, $extend,$where_str)->each(function ($item, $key) {
            if($item['sdk_version'] == 3){
                $url = url('@mobile/Game/detail', ['game_id' => $item['relation_game_id']],true,true);
            }else{
                $url = url('Downfile/index', ['gid' => $item['relation_game_id']],true,true);
            }
            $item['qrcode'] = url('Index/qrcode', ['url' => base64_encode(base64_encode($url))]);
            $item['game_size'] = $item['down_port'] == 2 ? $item['game_size'].'MB':$item['game_size'];
            return $item;
        });
        $page = $lists->render();
        $action = $map['sdk_version'] == 3 ? 'Game/game_hcenter' : 'Game/game_center';
        //版本类型
        $version = array(
            array(
                'name' => 'Andriod',
                'value' => 1,
                'url' => url('Game/game_center', ['sdk_version' => 1, 'game_type_id' => $data['game_type_id'], 'short' => $data['short']])
            ),
            array(
                'name' => 'IOS',
                'value' => 2,
                'url' => url('Game/game_center', ['sdk_version' => 2, 'game_type_id' => $data['game_type_id'], 'short' => $data['short']])
            ),
        );
        //角色类型
        $typemodel = new GameTypeModel();
        $game_type = $typemodel->field('id,type_name')->where('status', 1)->order('sort desc')->select();
        foreach ($game_type as $key => $v) {
            $game_type[$key]['url'] = url($action, ['sdk_version' => $data['sdk_version'], 'game_type_id' => $v['id'], 'short' => $data['short']]);
        }
        $this->assign('game_type', $game_type);
        //首字母查询
        $short_type = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        foreach ($short_type as $key => $v) {
            $short[] = array(
                'name' => $v,
                'value' => $v,
                'url' => url($action, ['game_type_id' => $data['game_type_id'], 'short' => $v, 'sdk_version' => $data['sdk_version']])
            );
        }
        $this->assign('short', $short);
        $this->assign('version', $version);
        $this->assign('page', $page);
        $this->assign('data', $lists);
        // echo json_encode($lists);exit;
    }

    /**
     * [页游游戏列表]
     * @author 郭家屯[gjt]
     */
    public function ygame_list($map = [])
    {
        $data = $this->request->param();
        $model = new GameModel();
        $map['game_status'] = 1;
        $map['sdk_version'] = 4;
        //$map['dow_status'] = 1;
        if ($data['game_name']) {
            $map['game_name'] = ['like', '%' . $data['game_name'] . '%'];
        }
        $where_str = '';//查询更改
        if ($data['game_type_id']) {
            $where_str = "FIND_IN_SET('".$data['game_type_id']."',game_type_id)";
        }
        if ($data['short']) {
            $map['short'] = ['like', $data['short'] . '%'];
        }
        //联盟游戏
        if (MEDIA_PID > 0) {
            $map['id'] = ['in', get_promote_game_id(MEDIA_PID)];
        }
        $extend['field'] = 'relation_game_id,dow_num,sdk_version,relation_game_name as game_name,icon,game_type_name,count(id) as sdk_type,if(down_port=1,game_size,game_address_size) as game_size,down_port,create_time,hot_cover,features';
        $extend['row'] = 10;
        $extend['order'] = 'sort desc,id desc';
        $extend['group'] = 'id';
        $base = new BaseHomeController();
        $lists = $base->data_list($model, $map, $extend,$where_str);
        $page = $lists->render();
        //版本类型
        $version = array(
                array(
                        'name' => 'Andriod',
                        'value' => 1,
                        'url' => url('Game/game_ycenter', ['sdk_version' => 1, 'game_type_id' => $data['game_type_id'], 'short' => $data['short']])
                ),
                array(
                        'name' => 'IOS',
                        'value' => 2,
                        'url' => url('Game/game_ycenter', ['sdk_version' => 2, 'game_type_id' => $data['game_type_id'], 'short' => $data['short']])
                ),
        );
        //角色类型
        $typemodel = new GameTypeModel();
        $game_type = $typemodel->field('id,type_name')->where('status', 1)->order('sort desc')->select();
        foreach ($game_type as $key => $v) {
            $game_type[$key]['url'] = url('Game/game_ycenter', ['sdk_version' => $data['sdk_version'], 'game_type_id' => $v['id'], 'short' => $data['short']]);
        }
        $this->assign('game_type', $game_type);
        //首字母查询
        $short_type = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        foreach ($short_type as $key => $v) {
            $short[] = array(
                    'name' => $v,
                    'value' => $v,
                    'url' => url('Game/game_ycenter', ['game_type_id' => $data['game_type_id'], 'short' => $v, 'sdk_version' => $data['sdk_version']])
            );
        }
        $this->assign('short', $short);
        $this->assign('page', $page);
        $this->assign('data', $lists);
    }

    /**
     * @函数或方法说明
     * @页游详情页
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/9/19 10:29
     */
    public function ydetail()
    {
        $game_id = $this->request->param('game_id');
        if (empty($game_id)) $this->error('游戏不存在');
        $model = new GameModel();
        $game = $model->getRelationGameDetail($game_id);
        if (empty($game)) $this->error('游戏不存在');
        $model = new ServerModel();
        $map['game_id'] = $game_id;
        $server = $model->getlists($map,'start_time desc');
        $this->assign('server',$server);
        $this->assign('game',$game);
        return $this->fetch();
    }

    /**
     * [详情页]
     * @author 郭家屯[gjt]
     */
    public function detail()
    {
        $relation_game_id = $this->request->param('game_id');
        if (empty($relation_game_id)) $this->error('游戏不存在');
        $model = new GameModel();
        $data = $model->getRelationGameDetail($relation_game_id);
        if (empty($data)) $this->error('游戏不存在');
        //联盟游戏
        if (MEDIA_PID > 0) {
            $game_ids = get_promote_game_id(MEDIA_PID);
            $map['id'] = ['in', $game_ids];
        }

        $data['url'] = url('Game/detail', ['game_id' => $data['relation_game_id']],true,true);
        $data['screenshot'] = empty($data['screenshot']) ? '' : explode(',', $data['screenshot']);
        $data['introduction'] = str_replace(PHP_EOL,'<br/>',$data['introduction']);
        $data['introduction'] = str_replace(' ','&nbsp;',$data['introduction']);
        $this->assign('data', $data);
        $map22['test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map22['only_for_promote'] = 0;  // 渠道独占的游戏不显示
        if ($data['sdk_type'] == 2) {
            $games = $model->field('id,sdk_version')
                ->where('relation_game_id', $relation_game_id)
                ->where($map22)
                ->select()
                ->toArray();
            foreach ($games as $key => $vo) {
                if ((MEDIA_PID > 0 && in_array($vo['id'], $game_ids)) || MEDIA_PID == 0) {
                    $this->server($vo['id'], $vo['sdk_version']);//区服
                    $this->zhekou($vo['id'],$relation_game_id,$vo['sdk_version']);
                }
            }
        } else {
            if ((MEDIA_PID > 0 && in_array($data['id'], $game_ids)) || MEDIA_PID == 0) {
                $this->server($data['id'], $data['sdk_version']);//区服
                $this->zhekou($data['id'],$relation_game_id,$data['sdk_version']);
            }
        }
        $this->gift($data['game_name'],$data['sdk_version']);//礼包
        $this->huodong($relation_game_id);//活动
        $map['sdk_version'] = ['in',[1,2]];
        $this->hotGame($map);//热门游戏
        $map1['game_id'] = get_promote_relation_game_id($relation_game_id);
        $this->gonglue($map1);//攻略
        // 获取开关
        $showStatus = get_game_show_status($relation_game_id);
        $this->assign('discount_show_status',$showStatus['discount_show_status']);
        $this->assign('coupon_show_status',$showStatus['coupon_show_status']);
        return $this->fetch();
    }

    /**
     * [H5详情页]
     * @author 郭家屯[gjt]
     */
    public function hdetail()
    {
        $relation_game_id = $this->request->param('game_id');
        if (empty($relation_game_id)) $this->error('游戏不存在');
        $model = new GameModel();
        $data = $model->getRelationGameDetail($relation_game_id);
        if (empty($data)) $this->error('游戏不存在');
        //联盟游戏
        if (MEDIA_PID > 0) {
            $game_ids = get_promote_game_id(MEDIA_PID);
            $map['id'] = ['in', $game_ids];
        }
        $data['url'] = url('Game/detail', ['game_id' => $data['relation_game_id']],true,true);
        $data['screenshot'] = empty($data['screenshot']) ? '' : explode(',', $data['screenshot']);
        $data['introduction'] = str_replace(PHP_EOL,'<br/>',$data['introduction']);
        $data['introduction'] = str_replace(' ','&nbsp;',$data['introduction']);
        $this->assign('data', $data);
        if ($data['sdk_type'] == 2) {
            $games = $model->field('id,sdk_version,bind_recharge_discount')->where('relation_game_id', $relation_game_id)->select()->toArray();
            foreach ($games as $key => $vo) {
                if ((MEDIA_PID > 0 && in_array($vo['id'], $game_ids)) || MEDIA_PID == 0) {
                    $this->server($vo['id'], $vo['sdk_version']);//区服
                    $this->zhekou($vo['id'],$relation_game_id,$vo['sdk_version']);
                }

            }
        } else {
            if ((MEDIA_PID > 0 && in_array($data['id'], $game_ids)) || MEDIA_PID == 0) {
                $this->server($data['id'], $data['sdk_version']);//区服
                $this->zhekou($data['id'],$relation_game_id,$data['sdk_version']);
            }

        }
        $this->gift($data['game_name'],$data['sdk_version']);//礼包
        $this->huodong($relation_game_id);//活动
        $map['sdk_version'] = 3;
        $this->hotGame($map);//热门游戏
        $map1['game_id'] = get_promote_relation_game_id($relation_game_id);
        $this->gonglue($map1);//攻略
        // 获取开关
        $showStatus = get_game_show_status($relation_game_id);
        $this->assign('discount_show_status',$showStatus['discount_show_status']);
        $this->assign('coupon_show_status',$showStatus['coupon_show_status']);
        return $this->fetch();
    }
    /**
     * @函数或方法说明
     * @获取折扣返利信息
     * @author: 郭家屯
     * @since: 2020/1/11 16:12
     */
    protected function zhekou($game_id=0,$relation_game_id=0,$sdk_version='')
    {
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
        $this->assign('rebate'.$sdk_version,$rebate);
        //折扣信息
        $discount = $paylogic->get_detail_discount($game_id,$promote_id,$user_id);
        $this->assign('discount'.$sdk_version,$discount);
        //优惠券信息
        $coupon = $paylogic->get_detail_coupon($user_id,$relation_game_id,$promote_id);
        $this->assign('coupon',$coupon);
        //新增绑币充值折扣信息-byh
        $bind_discount = $paylogic->get_detail_bind_discount($game_id,$promote_id,$user_id);
        //处理一下因移动端返回的数据变动,开关关闭,删除
        if(empty($bind_discount['first_status'])){
            unset($bind_discount['first_status']);
            unset($bind_discount['first_discount']);
        }
        if(empty($bind_discount['continue_status'])){
            unset($bind_discount['continue_status']);
            unset($bind_discount['continue_discount']);
        }
        $this->assign('bind_discount'.$sdk_version,$bind_discount);
    }

    /**
     * [首页区服显示]
     * @author 郭家屯[gjt]
     */
    public function server($game_id = '', $sdk_version = '')
    {
        $model = new ServerModel();
        $map['game_id'] = $game_id;
        $data_last = $model->getGameServer($map);//今日和即将开服
        $data_before = $model->getBeforeGameServer($map);//已经开服
        $data = array_merge($data_last, $data_before);
        $this->assign('server' . $sdk_version, $data);
    }

    /**
     * [页游区服显示]
     * @author 郭家屯[gjt]
     */
    public function yserver($map = [])
    {
        $model = new ServerModel();
        $map['s.sdk_version'] = 4;
        //联盟游戏
        if (MEDIA_PID > 0) {
            $game_ids = get_promote_game_id(MEDIA_PID);
            $map['g.id'] = ['in', $game_ids];
        }
        $map['start_time'] = ['gt',time()];
        $data = $model->getserver($map,1,10,'start_time asc');
        $this->assign('server', $data);
    }

    /**
     * [礼包列表]
     * @author 郭家屯[gjt]
     */
    public function gift($game_name = '',$sdk_version=1)
    {
        $model = new GiftbagModel();
        $map['gb.game_name'] = $game_name;
        if($sdk_version == 3){
            $map['gb.giftbag_version'] = ['like','%3%'];
        }else{
            $map['gb.giftbag_version'] = [['like','%1%'],['like','%2%'],'or'];
        }
        $data = $model->getGiftIndexLists(session('member_auth.user_id'), $map, 50);
        $this->assign('gift' , $data);
    }

    /**
     * [攻略]
     * @author 郭家屯[gjt]
     */
    public function gonglue($map = [])
    {
        $postService = new PostService();
        $map['category'] = [5];
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
        $map['category'] = [2, 3, 4, 5];
        $map['game_id'] = get_promote_relation_game_id($game_id);
        $map['post_status'] = 1;
        $data = $postService->GameArticleList($map, false, 50);
        $this->assign('article', $data);
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
     * @开始玩游戏
     * @author: 郭家屯
     * @since: 2020/6/13 9:37
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
            // echo '后台设置了https 非 https请求';exit;
            $url = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            return redirect($url);
        }
        if($game['is_https'] == 0 && is_https()){
            // echo '后台未设置https 是 https请求';exit;
            $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
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
                //写入登录记录
                (new \app\sdkh5\controller\GameController()) -> add_user_play($user_id, $id);
            }else{
                $playcontroller  = new \app\sdkh5\controller\GameController();
                $play_info = $playcontroller->get_play_info($user_id,$id,MEDIA_PID);
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
        $this->assign('game_name',$game['game_name']);
        $this->assign('is_force_real',$game['is_force_real']);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @打开页游游戏
     * @author: 郭家屯
     * @since: 2020/9/19 10:30
     */
    public function open_ygame()
    {
        $id = $this->request->param('game_id');
        $server_id = $this->request->param('server_id');
        $model = new GameModel();
        $game = $model->field('id,interface_id,cp_game_id,game_name,sdk_version,game_status,load_cover,icon,is_force_real,third_party_url,platform_id')->find($id);
        if($game['sdk_version'] != 4){
            $this->error('游戏不存在');
        }
        if(empty($server_id)){
            $this->error('请选择区服');
        }
        if($game['game_status'] != 1){
            $this->error('游戏未开启');
        }
        if(empty($game['interface_id'])){
            $this->error('游戏接口错误');
        }
        //封禁判断-20210713-byh
        if(!judge_user_ban_status($this->request->param('promote_id'),$id,session('member_auth.user_id'),$this->request->param('equipment_num'),get_client_ip(),$type=1)){
            $this -> error( "当前被禁止登录，请联系客服");
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
        if ($user_id) {
            $usermodel = new UserModel();
            //游戏登录记录
            $user = get_user_entity($user_id, false, 'id,account,promote_id,promote_account,puid,fgame_id,parent_id,parent_name,register_type,vip_level');
            $data['type'] = 1;
            $data['game_id'] = $id;
            $data['game_name'] = $game['game_name'];
            $login_record = $usermodel -> user_login_record($user, $data);
            $this -> assign('user_info', $user);
            //支付二维码
            $url = url('Wappay/pay', ['user_id' => $user_id, 'game_id' => $id, 'server_id' => $server_id], true, true);
            $pay_url_img = url('Index/qrcode', ['url' => base64_encode(base64_encode($url))], true, true);
            // 简化版游戏跳转
            if($game['platform_id'] > 0){
                $playcontroller = new \app\thirdgame\logic\UserLogic();
                $game_url = $playcontroller->get_play_info($user_id,$id,MEDIA_PID);
                if($game_url === false){
                    $this->error('游戏地址错误');
                }
                return $this->redirect($game_url);
            }
            $playcontroller = new \app\sdkyy\controller\GameController();
            $login_url = $playcontroller -> get_login_url($user_id, $id, $game['cp_game_id'], $game['interface_id'], $server_id, $pay_url_img,$url);
            $this -> assign('login_url', $login_url);
            //获取游戏公告
            $postService = new PostService();
            $map['category'] = [4];
            $map['game_id'] = get_promote_relation_game_id($id);
            $map['post_status'] = 1;
            $ggList = $postService -> GameArticleList($map, false, 50);
            $this -> assign('ggList', $ggList);
            //页游列表
            $game_lists = get_game_list('id,game_name', ['sdk_version' => '4', 'game_status' => '1'], '', 'sort desc');
            $this -> assign('game_lists', $game_lists);

            //是否签到
            $logicPoint = new PointLogic();
            $signDetail = $logicPoint->sign_detail($user_id);
            $this->assign('signDetail',$signDetail);

            //用户VIP等级
            $res = (new PointTypeLogic())->user_vip(session('member_auth.user_id'));
            $this->assign('vip_upgrade',$res['vip_upgrade']);
            $this->assign('next_pay',$res['next_pay']);
            $this->assign('vip_data',$res['data']);

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
        $this->assign('load_cover',$game['load_cover']);
        //$this->assign('icon',$game['icon']);
        $this->assign('icon',cmf_get_option('media_set')['pc_set_logo_foot']);
        $this->assign('game_name',$game['game_name']);
        $this->assign('is_force_real',$game['is_force_real']);
        return $this->fetch();
    }

}
