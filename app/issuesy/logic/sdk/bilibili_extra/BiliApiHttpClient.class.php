<?php

class BiliApiHttpClient
{

    /** bili api post function
     * @param $path
     * @param $params
     * @param $biliConfig
     * @return result
     */
    public function post($path, $params, $biliConfig)
    {
        $params['game_id'] = $biliConfig['game_id'];
        $params['server_id'] = $biliConfig['server_id'];
        $params['merchant_id'] = $biliConfig['merchant_id'];
        $params['version'] = '1';
        $params['timestamp'] = time();
        $params['sign'] = SignHelper::sign($params, $biliConfig['secret_key']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $path);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //设置user-agent
        curl_setopt($ch, CURLOPT_USERAGENT, $biliConfig['user_agent']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

}
