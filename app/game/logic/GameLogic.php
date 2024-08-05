<?php

namespace app\game\logic;

use app\game\model\GameAttrModel;
use app\game\model\GameChangeNameLogModel;
use app\game\model\GameModel;
use app\game\validate\GameValidateField;

class GameLogic
{


    /**
     * @修改游戏名称
     *
     * @author: zsl
     * @since: 2021/7/30 21:09
     */
    public function changeGameName($param)
    {
        $result = ['code' => 1, 'msg' => '修改成功', 'data' => []];
        // 验证请求参数
        if (empty($param['id'])) {
            $result['code'] = 0;
            $result['msg'] = '请选择游戏';
            return $result;
        }
        if (empty($param['game_name'])) {
            $result['code'] = 0;
            $result['msg'] = '请输入新游戏名称';
            return $result;
        }
        if (mb_strlen($param['game_name']) > 30) {
            $result['code'] = 0;
            $result['msg'] = '请输入正确的游戏名称';
            return $result;
        }
        //获取拼接后的名称
        $param['game_name'] = $this -> _getRealGameName($param['id'], $param['game_name']);
        //修改游戏名称
        $mGame = new GameModel();
        $res = $mGame -> changeGameName($param);
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '修改失败,请重试';
            return $result;
        }
        return $result;
    }


    /**
     * @获取真实修改名称
     *
     * @author: zsl
     * @since: 2021/7/30 21:16
     */
    private function _getRealGameName($game_id, $game_name)
    {
        $mGame = new GameModel();
        $sdk_version = $mGame -> where(['id' => $game_id]) -> value('sdk_version');
        switch ($sdk_version) {
            case 1:
                $real_game_name = $game_name . '(安卓版)';
                break;
            case 2:
                $real_game_name = $game_name . '(苹果版)';
                break;
            default:
                $real_game_name = $game_name;
                break;
        }
        return $real_game_name;
    }


    /**
     * @修改游戏名称记录
     *
     * @author: zsl
     * @since: 2021/7/31 11:58
     */
    public function changeGameNameLog($param)
    {
        $mChangeName = new GameChangeNameLogModel();
        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        $where = [];
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        $lists = $mChangeName -> where($where) -> order('create_time desc,id desc') -> paginate($row, false, ['query' => $param]);
        return $lists;
    }

    /**
     * 修改游戏字段
     *
     * @param int $id
     * @param string $name
     * @param string $value
     * @author: Juncl
     * @time: 2021/08/14 13:44
     */
    public function setGameField($id=0, $name='', $value='')
    {
        $GameModel = new GameModel();
        if(is_array($id)){
            if(check_game_attr($name)){
                $map['game_id'] = array('in',$id);
            }else{
                $map['id'] = array('in',$id);
            }
            $attrMap['game_id'] = array('in',$id);
        }elseif(is_numeric($id)){
            if(check_game_attr($name)) {
                $map['game_id'] = $id;
            }else{
                $map['id'] = $id;
            }
            $attrMap['game_id'] = $id;
        }else{
           return ['status'=>0,'msg'=>'参数异常'];
        }
        $GameValidate = new GameValidateField();
        $data = [];
        $data[$name] = $value;
        if(!$GameValidate->scene('setField')->check($data)){
            return ['status'=>0,'msg'=>$GameValidate->getError()];
        }
        $result = $GameModel->setField($map,$name,$value);
        if($result === false){
            return ['status'=>0,'msg'=>'修改失败'];
        }else{
            $GameAttrModel = new GameAttrModel();
            // 修改分成比例和注册单间更新时间
            if ($name == 'ratio') {
                $GameAttrModel -> where($attrMap) -> setField('ratio_begin_time', time());
            } else if ($name == 'money') {
                $GameAttrModel -> where($attrMap) -> setField('money_begin_time', time());
            }
            return ['status' => 1, 'msg' => '修改成功'];
        }
    }


    /**
     * @下载对接文件
     *
     * @author: zsl
     * @since: 2021/9/22 9:21
     */
    public function downDockingFile($param)
    {
        $result = ['code' => 1, 'msg' => '获取成功', 'data' => []];
        $mGame = new GameModel();
        $field = "g.id,g.game_name,g.game_appid,g.sdk_version,s.access_key,s.game_key";
        $where = [];
        $where['g.id'] = $param['game_id'];
        $gameInfo = $mGame -> alias('g')
                -> field($field)
                -> join(['tab_game_set' => 's'], 'g.id = s.game_id', 'left')
                -> where($where)
                -> find();
        if (empty($gameInfo)) {
            $result['code'] = 0;
            $result['msg'] = '游戏不存在';
            return $result;
        }
        switch ($gameInfo['sdk_version']) {
            case "1"://安卓游戏参数
                $data = [
                        'gameid' => $gameInfo['id'],
                        'gamename' => $gameInfo['game_name'],
                        'gameappid' => $gameInfo['game_appid'],
                        'access_key' => get_ss($gameInfo['access_key']),
                        'gameurl' => cmf_get_domain(),
                ];
                file_put_contents(RUNTIME_PATH . 'xggameinfo.txt', json_encode($data, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE));
                $result['data'] = [
                        'file_dir' => RUNTIME_PATH,
                        'file_name' => 'xggameinfo.txt',
                ];
                return $result;
                break;
            case "2"://IOS游戏参数
                $xmlDemo = <<<XMLDEMO
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
	<key>game_name</key>
	<string>game_name</string>
	<key>game_appid</key>
	<string>game_appid</string>
	<key>game_id</key>
	<string>game_id</string>
	<key>access_key</key>
	<string>access_key</string>
	<key>requestURL</key>
	<string>requestURL</string>
</dict>
</plist>
XMLDEMO;
                $doc = new \DOMDocument('1.0', 'UTF-8');//引入类并且规定版本编码
                $doc -> loadXML($xmlDemo);
                $online = $doc -> getElementsByTagName('dict');//查找节点
                $itemArr = $online -> item(0) -> getElementsByTagName('string');
                foreach ($itemArr as $k => $v) {
                    switch ($v -> textContent) {
                        case 'game_name':
                            $v -> nodeValue = $gameInfo['game_name'];
                            break;
                        case 'game_appid':
                            $v -> nodeValue = $gameInfo['game_appid'];
                            break;
                        case 'game_id':
                            $v -> nodeValue = $gameInfo['id'];
                            break;
                        case 'access_key':
                            $v -> nodeValue = get_ss($gameInfo['access_key']);
                            break;
                        case 'requestURL':
                            $v -> nodeValue = cmf_get_domain();
                            break;
                    }
                }
                $doc -> save(RUNTIME_PATH . 'xggameinfo.plist');//保存代码
                $result['data'] = [
                        'file_dir' => RUNTIME_PATH,
                        'file_name' => 'xggameinfo.plist',
                ];
                return $result;
                break;
            default:
                $result['code'] = 0;
                $result['msg'] = '该游戏没有对接文件';
                return $result;
                break;
        }

    }


}
