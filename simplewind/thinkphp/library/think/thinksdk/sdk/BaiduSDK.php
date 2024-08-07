<?php
// +----------------------------------------------------------------------
// | TOPThink [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi.cn@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
// | BaiduSDK.class.php 2013-03-27
// +----------------------------------------------------------------------
namespace think\thinksdk\sdk;
use think\thinksdk\ThinkOauth;

class BaiduSDK extends ThinkOauth{
	/**
	 * 获取requestCode的api接口
	 * @var string
	 */
	protected $GetRequestCodeURL = 'https://openapi.baidu.com/oauth/2.0/authorize';

	/**
	 * 获取access_token的api接口
	 * @var string
	 */
	protected $GetAccessTokenURL = 'https://openapi.baidu.com/oauth/2.0/token';

	/**
	 * API根路径
	 * @var string
	 */
	protected $ApiBase = 'https://openapi.baidu.com/rest/2.0/';
	
  
  public function __construct($token = null){
      
      parent::__construct($token);
      
      $config = C(strtolower($this->Type)."_login");
        foreach ($config as $k => $v) {
            if (C("{$this->Type}_$k")) {
                $config[$k]=C("{$this->Type}_$k");
            }
        }
      
      if (empty($config['clientid'])){
        E('请配置您申请的clientid');
      } else {
        $this->AppKey    = $config['appid'];
        $this->AppSecret = $config['key'];
        $this->Token     = $token; //设置获取到的TOKEN
        $this->Callback  = $config['callback'];
      }
    }
  
  
	/**
	 * 组装接口调用参数 并调用接口
	 * @param  string $api    百度API
	 * @param  string $param  调用API的额外参数
	 * @param  string $method HTTP请求方法 默认为GET
	 * @return json
	 */
	public function call($api, $param = '', $method = 'GET', $multi = false){		
		/* 百度调用公共参数 */
		$params = array(
			'access_token' => $this->Token['access_token'],
		);
		
		$data = $this->http($this->url($api), $this->param($params, $param), $method);
		return json_decode($data, true);
	}
	
	/**
	 * 解析access_token方法请求后的返回值
	 * @param string $result 获取access_token的方法的返回值
	 */
	protected function parseToken($result, $extend){
		$data = json_decode($result, true);
		if($data['access_token'] && $data['expires_in'] && $data['refresh_token']){
			$this->Token    = $data;
			$data['openid'] = $this->openid();
			return $data;
		} else
			throw new Exception("获取百度ACCESS_TOKEN出错：{$data['error']}");
	}
	
	/**
	 * 获取当前授权应用的openid
	 * @return string
	 */
	public function openid(){
		if(isset($this->Token['openid']))
			return $this->Token['openid'];
		
		$data = $this->call('passport/users/getLoggedInUser');
		if(!empty($data['uid']))
			return $data['uid'];
		else
			throw new Exception('没有获取到百度用户ID！');
	}
	
}