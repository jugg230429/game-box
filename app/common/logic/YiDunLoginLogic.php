<?php


namespace app\common\logic;


use think\Db;

class YiDunLoginLogic
{

    protected $secret_id = "20ca6df870b138972a96f8cec18a0a75";//    /** 产品密钥ID，产品标识 */
    protected $secret_key = "8fdf2ee86c0100bd67fb227bc0da8ac2";//    /** 产品私有密钥，服务端生成签名信息使用，请严格保管，避免泄露 */
    //此处的业务id为变量!!!目前app是固定的,SDK每个游戏都有不同的business_id,需要读取后台的游戏参数配置
    protected $business_id;//    /** 业务ID，易盾根据产品业务特点分配 */
    protected $oneclick_url = "https://ye.dun.163yun.com/v1/oneclick/check";//    /** 易盾短信服务发送接口地址 */
    protected $api_version = "v1";//接口版本,默认填写v1
    protected $api_timeout = 5;

    public function __construct($game_id=0,$app_version=1)
    {

        if($game_id>0){//sdk的
            $gameData = get_game_entity($game_id,'yidun_business_id');
            $this->business_id = $gameData['yidun_business_id'];
        }else{//APP的
            $appData = cmf_get_option('app_set');//获取APP配置中的business_id $this->business_id
            if($app_version==2 && PROMOTE_ID>0){//ios渠道
                $ios_business_id = Db::table('tab_promote_app')->where('promote_id',PROMOTE_ID)->where('app_version',2)->value('yidun_business_id');
                $this->business_id = $ios_business_id??'';
            }else{
                $this->business_id = $appData['yidun_business_id'];//安卓或非渠道使用APP设置中的统一business_id
            }
        }
    }


    /**
     * 易盾本机验证在线检测请求接口简单封装
     * $params 请求参数
     */
    function one_click($data)
    {
        $time = time();
        $params = [
            // 运营商预取号获取到的token
            "accessToken" => $data['accessToken'],
            // 易盾返回的token
            "token" => $data['yidun_token']
        ];
        $params["secretId"] = $this->secret_id;
        $params["businessId"] = $this->business_id;
        $params["version"] = $this->api_version;
        $params["timestamp"] = sprintf("%d", round(microtime(true) * 1000));
//        $params["timestamp"] = $time;
        // time in milliseconds
        $params["nonce"] = substr(md5(time()), 0, 32);
        // random int
        $params = $this->toUtf8($params);
        $params["signature"] = $this->gen_signature($this->secret_key, $params);
        $options = array('http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'timeout' => $this->api_timeout,
            // read timeout in seconds
            'content' => http_build_query($params)
        ));
        $context = stream_context_create($options);
        $result = file_get_contents($this->oneclick_url, false, $context);

        if ($result === FALSE) {
            return array("code" => 500, "msg" => "file_get_contents failed.");
        } else {
            return json_decode($result, true);
        }
    }


    /**
     * 计算参数签名
     * $params 请求参数
     * $secretKey secretKey
     */
    function gen_signature($secretKey, $params)
    {
        ksort($params);
        $buff = "";
        foreach ($params as $key => $value) {
            if ($value !== null) {
                $buff .= $key;
                $buff .= $value;
            }
        }
        $buff .= $secretKey;
        return md5($buff);
    }

    /**
     * 将输入数据的编码统一转换成utf8
     * @params 输入的参数
     */
    function toUtf8($params)
    {
        $utf8s = array();
        foreach ($params as $key => $value) {
            $utf8s[$key] = is_string($value) ? mb_convert_encoding($value, "utf8", INTERNAL_STRING_CHARSET) : $value;
        }
        return $utf8s;
    }


}