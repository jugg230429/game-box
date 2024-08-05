<?php

namespace app\btwelfare\api;

class BaseApi
{


    /**
     * @生成sign
     *
     * @author: zsl
     * @since: 2021/1/14 20:37
     */
    public function vSign($data, $k)
    {
        ksort($data);
        $str = '';
        foreach ($data as $key => $value) {
            if ($key == 'sign') {
                continue;
            }
            $str .= $key . '=' . urlencode($value) . '&';
        }
        $str .= 'key=' . $k;
        return md5($str);
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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置等待时间
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }


}