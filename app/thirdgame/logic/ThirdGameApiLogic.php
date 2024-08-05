<?php

namespace app\thirdgame\logic;

use app\thirdgame\logic\PlatformLogic;
use app\thirdgame\model\GameGiftbagModel;
use app\thirdgame\model\GameModel;
use app\thirdgame\model\GameServerModel;
use app\thirdgame\model\GameSourceModel;
use app\thirdgame\model\SpendModel;

class ThirdGameApiLogic
{
    private $platform_id = 0; //平台ID
    private $platform_url = '';//平台域名
    private $api_key = '';     //平台加密秘钥
    private $marking = '';     //平台标识
    private $select_game_url = '';//可导入游戏接口地址
    private $import_game_url = '';//导入游戏接口地址
    private $import_gift_url = '';//导入礼包接口地址
    private $import_server_url = '';//导入区服接口地址
    private $import_source_url = '';//导入原包接口地址
    private $import_spend_url = '';//导入订单接口地址
    private $import_pay_url = '';//补单接口地址

    public function __construct($platform_id=0)
    {
        $PlatformLogic = new PlatformLogic();
        $platform_info = $PlatformLogic->detail($platform_id)->toArray();
        $this->platform_id = $platform_id;
        $this->platform_url = $platform_info['platform_url'];
        $this->api_key = $platform_info['api_key'];
        $this->marking = $platform_info['marking'];
        $this->select_game_url = $platform_info['select_game_url'];
        $this->import_game_url = $platform_info['import_game_url'];
        $this->import_server_url = $platform_info['import_server_url'];
        $this->import_gift_url = $platform_info['import_gift_url'];
        $this->import_source_url = $platform_info['import_source_url'];
        $this->import_spend_url = $platform_info['import_spend_url'];
        $this->import_pay_url = $platform_info['import_pay_url'];
    }

    /**
     * 获取可导入游戏数量
     *
     * @return bool
     * @author: Juncl
     * @time: 2021/08/20 11:54
     */
    public function getSelectGame()
    {
          $GameModel = new GameModel();
          $data = $this->getSignData();
          // 获取第三方游戏ID
          $game_ids = $GameModel->where('platform_id',$this->platform_id)->column('cp_game_id');
          $data['game_ids'] = $game_ids;
          $result = $this->post($data,$this->platform_url . '/' .$this->select_game_url);
          $result = json_decode($result,true);
          if($result['status'] == 200){
              return $result['data'];
          }else{
              return false;
          }
    }

    /**
     * 一键导入游戏
     *
     * @author: Juncl
     * @time: 2021/08/20 11:54
     */
    public function importGame()
    {
        $GameModel = new GameModel();
        $data = $this->getSignData();
        // 获取第三方游戏ID
        $game_ids = $GameModel->where('platform_id',$this->platform_id)->column('cp_game_id');
        $data['game_ids'] = $game_ids;
        $result = $this->post($data,$this->platform_url . '/' .$this->import_game_url);
        $result = json_decode($result,true);
        if($result['status'] == 200 && !empty($result['data'])){
            $res = $GameModel->importGame($this->platform_id,$result['data']);
            return $res;
        }else{
            return false;
        }
    }

    /**
     * 一键导入区服
     *
     * @author: Juncl
     * @time: 2021/08/23 9:12
     */
    public function importServer($game_id=0)
    {
         $game_info = get_game_entity($game_id,'platform_id,cp_game_id');
         if(!$game_info){
             return false;
         }
         $data = $this->getSignData();
         //获取已导入区服ID
         $GameServerModel = new GameServerModel();
         $server_ids = $GameServerModel->where('game_id',$game_id)->column('third_server_id');
         $data['game_id'] = $game_info['cp_game_id'];
         $data['server_ids'] = $server_ids;
         $result = $this->post($data,$this->platform_url . '/' .$this->import_server_url);
         $result = json_decode($result,true);
         if($result['status'] == 200 && !empty($result['data'])){
             $res = $GameServerModel->importServer($game_id,$result['data']);
             return $res;
         }else{
             return false;
         }
    }

    /**
     * 一键导入礼包
     *
     * @author: Juncl
     * @time: 2021/08/23 9:12
     */
    public function importGift($game_id=0)
    {
        $game_info = get_game_entity($game_id,'platform_id,cp_game_id');
        if(!$game_info){
            return false;
        }
        $data = $this->getSignData();
        //获取已导入区服ID
        $GameGiftModel = new GameGiftbagModel();
        $gift_ids = $GameGiftModel->where('game_id',$game_id)->column('third_gift_id');
        $data['game_id'] = $game_info['cp_game_id'];
        $data['gift_ids'] = $gift_ids;
        $result = $this->post($data,$this->platform_url . '/' .$this->import_gift_url);
        $result = json_decode($result,true);
        if($result['status'] == 200 && !empty($result['data'])){
            $res = $GameGiftModel->importGift($game_id,$result['data']);
            return $res;
        }else{
            return false;
        }

    }

    /**
     * 更新游戏原包
     *
     * @author: Juncl
     * @time: 2021/08/23 9:12
     */
    public function importSource($game_id=0)
    {
        $game_info = get_game_entity($game_id,'platform_id,cp_game_id');
        if(!$game_info){
            return false;
        }
        $data = $this->getSignData();
        //获取已导入游戏ID
        $GameSourceModel = new GameSourceModel();
        $data['game_id'] = $game_info['cp_game_id'];
        $result = $this->post($data,$this->platform_url . '/' .$this->import_source_url);
        $result = json_decode($result,true);
        if($result['status'] == 200 && !empty($result['data'])){
            $res = $GameSourceModel->importSource($game_id,$result['data']);
            return $res;
        }else{
            return false;
        }
    }

    /**
     * 导入游戏订单
     *
     * @author: Juncl
     * @time: 2021/08/31 9:30
     */
    public function importOrder($platform_id=0)
    {
        $SpendModel = new SpendModel();
        $data = $this->getSignData();
        //获取最后一条订单的时间
        $lastPayTime = $SpendModel->where('platform_id',$platform_id)->order('pay_time desc')->value('pay_time');
        $data['last_pay_time'] = $lastPayTime?:'1';
        $data['limit'] = 10;
        $result = $this->post($data,$this->platform_url . '/' .$this->import_spend_url);
        $result = json_decode($result,true);
        if($result['status'] == 200 && !empty($result['data'])){
            //按照支付时间升序排序
            $payData = my_sort($result['data'],'pay_time',SORT_ASC);
            $res = $SpendModel->addSpend($platform_id,$payData);
            return $res;
        }else{
            return [];
        }
    }

    /**
     * 补单接口
     *
     * @param string $order
     * @return bool
     * @author: Juncl
     * @time: 2021/09/03 19:49
     */
    public function updatePayStatus($order='')
    {
        $data = $this->getSignData();
        $data['trade_no'] = $order;
        $result = $this->post($data,$this->platform_url . '/' .$this->import_pay_url);
        $result = json_decode($result,true);
        if($result['status'] == 200 && $result['data']['pay_game_status'] == 1){
            return true;
        }else{
            return false;
        }
    }

    /**
     *post提交数据
     */
    protected function post($param, $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置等待时间
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * 获取sign值
     *
     * @param int $time
     * @return array
     * @author: Juncl
     * @time: 2021/08/19 16:49
     */
    protected function getSignData()
    {
        $time = time();
        $data = [];
        $data['timestamp'] = $time;
        $data['platform'] = $this->marking;
        $data['sign'] = md5( $this->marking . $time . $this->api_key);
        return $data;
    }
}