<?php return array (
  'sdk_config' => 
  array (
    'oppo' => 
    array (
      'name' => 'oppo',
      'version' => 
      array (
        0 => 'oppo',
      ),
      'param_key' => 
      array (
        'client' => 
        array (
          0 => 'appid',
          1 => 'appkey',
          2 => 'appsecret',
          3 => 'baoming',
        ),
        'server' => 
        array (
          0 => 'appid',
          1 => 'appkey',
          2 => 'appsecret',
        ),
      ),
      'controller_name' => 'oppo',
      'resource_version' => '1',
    ),
    'vivo' => 
    array (
      'name' => 'vivo',
      'version' => 
      array (
        0 => 'vivo',
      ),
      'param_key' => 
      array (
        'client' => 
        array (
          0 => 'CP_ID',
          1 => 'APP_ID',
          2 => 'baoming',
        ),
        'server' => 
        array (
          0 => 'CP_ID',
          1 => 'APP_ID',
          2 => 'APP_KEY_ACCOUNT',
          3 => 'APP_KEY_PAY',
        ),
      ),
      'controller_name' => 'vivo',
      'resource_version' => '1',
    ),
    'qqyyb' => 
    array (
      'name' => 'qqyyb',
      'version' => 
      array (
        0 => 'qqyyb',
      ),
      'param_key' => 
      array (
        'client' => 
        array (
          0 => 'QQ_APP_ID',
            1 => 'WX_APP_ID',
          2 => 'baoming',
        ),
        'server' => 
        array (
          0 => 'APP_ID',
          1 => 'APP_KEY',
          2 => 'WX_AppSecret',
          3 => 'zoneid',
          4 => 'login_type|登录方式:只能填写qq或wx',
        ),
      ),
      'controller_name' => 'qqyyb',
      'resource_version' => '1',
    ),
    'xiaomi' => 
    array (
      'name' => 'xiaomi',
      'version' => 
      array (
        0 => 'xiaomi',
      ),
      'param_key' => 
      array (
        'client' => 
        array (
          0 => 'AppID',
          1 => 'AppKey',
          2 => 'baoming',
        ),
        'server' => 
        array (
          0 => 'AppID',
          1 => 'AppKey',
          2 => 'AppSecret',
        ),
      ),
      'controller_name' => 'xiaomi',
      'resource_version' => '1',
    ),
      '360' => [
          'name' => '360',
          'version' => ['360'],
          'param_key'=>[
              'client'=>['QHOPENSDK_APPID','QHOPENSDK_APPKEY', 'baoming'],
              'server'=>['appid','appkey','appsecret'],
          ],
          'controller_name' => '360',
      ],
      'huawei' => [
          'name' => 'huawei',
          'version' => ['huawei'],
          'param_key'=>[
              'client'=>[],
              'server'=>['APP_ID','SecretKey','publicKey'],
          ],
          'controller_name' => 'huawei',
      ],
      'bilibili' => [//哔哩哔哩
          'name' => 'bilibili',
          'version' => ['bilibili'],
          'param_key'=>[
              'client'=>['merchant_id','appid','appkey','serverid', 'baoming'],
              'server'=>['merchant_id','app_id','server_id','server_name','app_key','secret_key'],
          ],
          'controller_name' => 'bilibili',
      ],
      'xigu' => [
          'name' => '溪谷',
          'version' => ['xigu'],
          'param_key' => [
              'client' => ['gameid', 'gamename', 'gameappid', 'access_key', 'gameurl', 'baoming'],
              'server' => ['game_key'],
          ],
          'controller_name' => 'xiguqj',
      ],
      '4399' => [//4399
          'name' => '4399',
          'version' => ['4399'],
          'param_key'=>[
              'client'=>[],
              'server'=>['app_key','secret'],
          ],
          'controller_name' => '4399',
      ],
      'baidu' => [
          'name' => 'baidu',
          'version' => ['baidu'],
          'param_key'=>[
              'client'=>[],
              'server'=>['APPID','APPKEY','SECRETKEY'],
          ],
          'controller_name' => 'baidu',
      ],
  ),
);