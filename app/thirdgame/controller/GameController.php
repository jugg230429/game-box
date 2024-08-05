<?php

namespace app\thirdgame\controller;

use app\thirdgame\logic\GameLogic;
use app\thirdgame\logic\PlatformLogic;
use app\thirdgame\logic\ThirdGameApiLogic;

class GameController extends BaseController{

    /**
     * @第三方游戏列表
     * @param int $platformId  第三方平台id
     * @return mixed|void
     * @author: Juncl
     * @time: 2021/08/12 9:24
     */
    public function lists($platformId = 0)
    {
        // 获取所有平台
        $pmap['status'] = 1;
        $platLogic = new PlatformLogic();
        $platform_list = $platLogic->get_platform($pmap);
        // 默认选中第一个平台
        if($platformId == 0 && !empty($platform_list)){
            $url = url('lists',array('platformId'=>$platform_list[0]['id']));
            return $this->redirect($url);
        }elseif(empty($platform_list)){
            $platformId = -1;
        }
        $this->assign('platforms',$platform_list);
        // 游戏条件
        $request = $this->request->param();
        $map['platform_id'] = $platformId;
        if($request['game_name'] != ''){
            $map['tab_game.game_name'] = $request['game_name'];
        }
        if($request['sdk_version'] != ''){
            $map['tab_game.sdk_version'] = $request['sdk_version'];
        }
        if($request['recommend_status'] != ''){
            $map['tab_game.recommend_status'] = array('like','%' . $request['recommend_status'] . '%');
        }
        if($request['game_status'] != ''){
            $map['tab_game.game_status'] = $request['game_status'];
        }
        $where_str = '';//游戏类型查询更改
        if ($request['type'] != '') {
            $where_str = "FIND_IN_SET('".$request['type']."',game_type_id)";
        }
        $GameLogic = new GameLogic();
        $data = $GameLogic->lists($map,$where_str);
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign("data_lists", $data);
        //当前选中平台
        if($platformId > 0 ){
            $platformInfo = get_platform_entity_by_map($platformId,'id,platform_name,pay_type');
            if($platformInfo){
                $this->assign('platformInfo',$platformInfo);
            }
        }
        // 游戏类型
        $game_types = $GameLogic->getGameType();
        $this->assign('game_types',$game_types);
        return $this->fetch();
    }

    /**
     * 游戏编辑页面
     *
     * @return mixed
     * @author: Juncl
     * @time: 2021/08/14 15:42
     */
    public function edit(){
        $GameLogic = new GameLogic();
        if($this->request->isPost()){
            $data = $this->request->param();
            if (empty($data['id'])) $this->error('参数错误');
            //$data['discount'] = 10;// 折扣默认为10;
            $result = $GameLogic->editGame($data);
            if($result['status']){
                //编辑成功后修改已有的会长代充关联游戏折扣
                //update_promote_agent_discount($data['id'],$data['discount']);
                $GameLogic->edit_warning($data['discount'],$data['id']);
                $this->success('修改成功',url('lists'));
            }else{
                $this->error($result['msg']);
            }
        }
        $gameId = $this->request->param('id', 0, 'intval');
        if (empty($gameId)) $this->error('参数错误');
        $data = $GameLogic->gameDetail($gameId);
        if (!$data) $this->error('游戏不存在或是已删除');
        //处理游戏类型-20210820-byh
        $data['game_type_id'] = explode(',',$data['game_type_id']);

        $this->assign('data',$data);
        return $this->fetch();
    }


    /**
     * 修改游戏状态
     *
     * @author: Juncl
     * @time: 2021/08/12 13:53
     */
    public function changeStatus()
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
     * 修改平台支付通道
     *
     * @param int $platformId
     * @param int $pay_type
     * @author: Juncl
     * @time: 2021/08/14 9:50
     */
    public function changePayType()
    {
        $param = $this->request->param();
         $platModel = new PlatformLogic();
         $result = $platModel->setStatus($param['id'],'pay_type',$param['pay_type']);
         if($result === false){
             $this->error('修改失败');
         }else{
             $this->success('修改成功');
         }
    }

    /**
     * 获取可导入游戏数量
     *
     * @author: Juncl
     * @time: 2021/08/18 21:31
     */
    public function getSelectGame()
    {
        $platform_id = $this->request->param('platform_id');
        $type =  $this->request->param('type');
        $game_id = $this->request->param('game_id');
        //验证平台
        $this->check_platform($platform_id);
        $GameApi = new ThirdGameApiLogic($platform_id);
        switch ($type){
            case 'selectGame':
                $result = $GameApi->getSelectGame();
                break;
            case 'importGame':
                $result = $GameApi->importGame();
                break;
            case 'importServer':
                $result = $GameApi->importServer($game_id);
                break;
            case 'importGift':
                $result = $GameApi->importGift($game_id);
                break;
            case 'importSource':
                $result = $GameApi->importSource($game_id);
                break;
            default:
                $result = false;
                break;
        }
        if($result !== false){
           $this->success('成功','',$result);
        }else{
            $this->error('接口请求失败');
        }
    }

    /**
     * 第三方平台是否存在
     *
     * @param int $platform_id
     * @author: Juncl
     * @time: 2021/08/21 14:47
     */
    protected function check_platform($platform_id=0){
        if(!$platform_id){
            $this->error('参数异常');
        }
        $platLogic = new PlatformLogic();
        $platform_detail = $platLogic->detail($platform_id);
        if(empty($platform_detail) || $platform_detail['status'] == 0){
            $this->error('平台不存在');
        }
        if(empty($platform_detail['import_game_url']) || empty($platform_detail['import_gift_url']) || empty($platform_detail['import_server_url']) || empty($platform_detail['import_source_url'])){
            $this->error('接口地址url为空');
        }
    }

}