<?php
namespace app\common\logic;

use app\common\model\UserAgeRecordModel;
use app\member\model\UserModel;
use think\db;

class AntiaddictionLogic{

    private $appId = '';
    private $secretKey = '';
    private $bizId = '';
    private $checkUrl = 'https://api.wlc.nppa.gov.cn/idcard/authentication/check';
    private $queryUrl = 'http://api2.wlc.nppa.gov.cn/idcard/authentication/query';
    private $collectUrl = 'http://api2.wlc.nppa.gov.cn/behavior/collection/loginout';

    public function __construct($game_id=0)
    {
        $gameData = get_game_entity($game_id,'age_appid,age_bizid,age_key');
        $this->appId = $gameData['age_appid'];
        $this->secretKey = $gameData['age_key'];
        $this->bizId = $gameData['age_bizid'];
    }



    /**
     * @函数或方法说明
     * @玩家身份证信息首次查询
     * @author: Juncl
     * @time: 2021/03/01 14:35
     * @param string $name
     * @param string $idNum
     * @param $user_id
     * @param $game_id
     */
    public function checkUser($name='',$idNum='',$user_id=0,$game_id=0){
        $model = new UserAgeRecordModel();
        $userData = array(
            'ai'    => $user_id,
            'name'  =>$name,
            'idNum' =>$idNum,
        );
        //当前时间戳
        $time = $this->milistime();
        //获取data数据
        $dataString = $this->getData($userData,$this->secretKey);
        //生成sign
        $sign = $this->getSign($dataString,$time);
        //设置header头
        $headers = $this->getHeader($sign,$time);
        //接口请求
        $result =  $this->CurlRequest($this->checkUrl,$dataString,$headers,true);
        if($result['errcode'] == 0 && $result['errmsg'] == 'OK'){
            //插入实名认证数据
            $record = $model->field('id')->where('user_id',$user_id)->where('game_id',$game_id)->find();
            $add['user_id'] = $user_id;
            $add['game_id'] = $game_id;
            $add['name'] = $name;
            $add['idcard'] = $idNum;
            $add['last_request_time'] = time();
            $resData = $result['data']['result'];
            switch ($resData['status']){
                //认证成功
                case 0:
                    $add['age_status'] = 1;
                    $add['request_status'] = 1;
                    $add['pi'] = $resData['pi'];
                    if(!$record){
                        $model->insert($add);
                    }else{
                        $model->where('id',$record['id'])->update($add);
                    }
                    $return = $this->setMessage(1,'认证成功');
                    break;
                //认证中
                case 1:
                    $add['age_status'] =2;
                    $add['request_status'] = 2;
                    if(!$record){
                        $model->insert($add);
                    }else{
                        $model->where('id',$record['id'])->update($add);
                    }
                    $return = $this->setMessage(2,'认证已提交，请耐心等待审核');
                    break;
                //认证失败
                case 2:
                    $add['age_status'] =3;
                    $add['request_status'] = 3;
                    if(!$record){
                        $model->insert($add);
                    }else{
                        $model->where('id',$record['id'])->update($add);
                    }
                    $return = $this->setMessage(3,'认证失败');
                    break;
            }
        }else{
            $return = $this->setMessage(3,'认证失败');
        }
        return $return;
    }

    /**
     * @函数或方法说明
     * @等待审核时候二次查询
     * @param string $user_id
     * @author: Juncl
     * @time: 2021/03/01 14:41
     */
    public function queryUser($user_id='',$game_id=''){
        //判断玩家请求记录
        $userModel = new UserModel();
        $recModel = new UserAgeRecordModel();
        $record = $recModel->where('user_id',$user_id)->where('game_id',$game_id)->find();
        if(empty($record['idcard']) || empty($record['name'])){
            $return = $this->setMessage(3,'认证失败');
            return $return;
        }
        if($record['request_status'] == 1 && $record['age_status'] == 1){
            $return = $this->setMessage(1,'已认证成功');
            return $return;
        }
        $data = array(
            'ai' => $user_id,
        );
        //当前时间戳（毫秒）
        $time = $this->milistime();
        //生成sign
        $signStr = $this->secretKey . 'ai'.$data['ai']. 'appId' . $this->appId . 'bizId' . $this->bizId . 'timestamps' . $time ;
        $sign = hash("sha256", $signStr);
        //header头
        $headers = $this->getHeader($sign,$time);
        //请求
        $result = $this->CurlRequest($this->queryUrl,$data,$headers,false);
        if($result['errcode'] == 0 && $result['errmsg'] == 'OK'){
            //修改上次请求时间
            $saveRec['last_request_time'] = $time;
            $recModel->where('id',$user_id)->where('game_id',$game_id)->update($saveRec);
            $resData = $result['data']['result'];
            if($resData['status'] == 0){
                //修改玩家实名认证信息
                if (is_adult($record['idcard'])) {
                    $saveData['age_status'] = 2;
                    $saveData['anti_addiction'] = 1;
                } else {
                    $saveData['age_status'] = 3;
                }
                $saveData['idcard'] = $record['idcard'];
                $saveData['real_name'] = $record['name'];
                $res = $userModel->where('id',$user_id)->update($saveData);
                if($res){
                    $saveRec['request_status'] = 1;
                    $saveRec['age_status'] = 1;
                    $recModel->where('id',$user_id)->where('game_id',$game_id)->update($saveRec);
                    $return = $this->setMessage(1,'认证成功');
                }else{
                    $return = $this->setMessage(3,'认证失败');
                }
            }elseif($resData['status'] == 1){
                $return = $this->setMessage(2,'等待认证');
            }else{
                $saveData['age_status'] = 0;
                $res = $userModel->where('id',$user_id)->update($saveData);
                $return = $this->setMessage(3,'认证失败');
            }
        }else{
            $return = $this->setMessage(3,'认证失败');
        }
        return $return;
    }

    /**
     * @函数或方法说明
     * @玩家登录登出行为上报
     * @param int $user_id
     * @param string $talk_code 会话标识
     * @param int $login_tpe  1登录 2登出
     * @param string $code  设备码
     * @author: Juncl
     * @time: 2021/03/01 15:45
     */
    public function collectUser($user_id=0,$talk_code='',$login_tpe=1,$code='',$game_id=0){
        $time = time();
        //判断玩家认证状态
        $userData = get_user_entity($user_id,0,'age_status');
        if($userData['age_status'] == 2 || $userData['age_status'] == 3){
            $ct = 0;
        }else{
            if(empty($code)){
                $return = $this->setMessage(3,'上报失败');
                return $return;
            }
            $ct = 2;
        }
        $recModel = new UserAgeRecordModel();
        $userRecord = $recModel->field('pi')->where('user_id',$user_id)->where('game_id',$game_id)->find();
        if(!empty($userRecord['pi'])){
            $pi = $userRecord['pi'];
        }else{
            $pi = $user_id;
        }
        $data = array(
            'no'  => 1,
            'si'  => $talk_code,
            'bt'  => $login_tpe,
            'ot'  => (int)$time,
            'ct'  => $ct,
            'di'  => $code,
            'pi'  => $pi,
        );
        $collections = array(
            'collections'=>array($data),
        );
        $string = json_encode($collections);
        //生成data
        $dataString = $this->getData($string,$this->secretKey);
        //生成sign
        $sign = $this->getSign($dataString,$time);
        //获取header
        $headers = $this->getHeader($sign,$time);
        //请求
        $result =  $this->CurlRequest($this->collectUrl,$dataString,$headers,true);
        if($result['errcode'] == 0 && $result['errmsg'] == 'OK') {
            $return = $this->setMessage(1,'上报成功');
        }else{
            $return = $this->setMessage(3,'上报失败');
        }
        return $return;
    }


    /**
     * @函数或方法说明
     * @
     * @param int $status
     * @param string $msg
     * $param time  int
     * @return array
     * @author: Juncl
     * @time: 2021/03/04 9:37
     */
     public function setMessage($status=0,$msg=''){
        $data = [];
        $data['status'] = $status;
        $data['msg'] = $msg;
        return $data;
     }


    /**
     * @函数或方法说明
     * @获取当前时间戳 毫秒级
     * @return string
     * @author: Juncl
     * @time: 2021/02/25 16:48
     */
    private function milistime()
    {
        static $milistime = NULL ;
        if(is_null($milistime))
        {
            $timestr = explode(' ', microtime());
            $milistime = strval(sprintf('%d%03d',$timestr[1],$timestr[0] * 1000));
        }
        return $milistime;
    }

    /**
     * @函数或方法说明
     * @post请求
     * @param $url 请求接口地址
     * @param $data 请求数据
     * @param $header 设置header头
     * @return string
     * @author: Juncl
     * @time: 2021/02/25 16:47
     */
    private function CurlRequest($url,$data=null,$header=null,$isPost=true){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置等待时间
        //POST请求
        if($isPost){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        //设置header头
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $str = curl_exec($ch);
        curl_close($ch);
        return json_decode($str,true);
    }

    /**
     * @函数或方法说明
     * @获取data数据
     * @param $data 发送参数
     * @param $keyStr secretKey
     * @return string
     * @author: Juncl
     * @time: 2021/02/25 16:47
     */
    private function getData($data='',$keyStr=''){
        $key = hex2bin($keyStr);
        $cipher = "aes-128-gcm";
        if(is_array($data)){
            $string = json_encode($data);
        }else{
            $string = $data;
        }
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $tag = NULL;
        $encrypt = openssl_encrypt($string, $cipher, $key, OPENSSL_RAW_DATA,$iv,$tag);
        $str = bin2hex($iv) .  bin2hex($encrypt) . bin2hex($tag);
        $dataRes = base64_encode(hex2bin($str));
        $dataArr = array('data'=>$dataRes);
        return json_encode($dataArr);
    }

    /**
     * @函数或方法说明
     * @获取加密sign
     * @param $data 发送参数
     * @param $time 时间戳
     * @return string
     * @author: Juncl
     * @time: 2021/02/25 16:47
     */
    private function getSign($data='',$time=''){
        $signStr = $this->secretKey . 'appId' . $this->appId . 'bizId' . $this->bizId . 'timestamps' . $time . $data;
        $sign = hash("sha256", $signStr);
        return $sign;
    }

    /**
     * @函数或方法说明
     * @获取header头
     * @param $sign sign值
     * @param $time 时间戳
     * @return array
     * @author: Juncl
     * @time: 2021/02/25 16:47
     */
    private function getHeader($sign='',$time=''){
        $headers = array(
            "Content-Type: application/json;charset=utf-8",
            "appId:" . $this->appId . "",//自定义参数
            "bizId:" . $this->bizId . "",//自定义参数
            "timestamps:" . $time . "",//自定义参数
            "sign:" . $sign . "",//自定义参数
        );
        return $headers;
    }


}
