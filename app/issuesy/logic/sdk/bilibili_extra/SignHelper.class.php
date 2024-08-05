<?php

class SignHelper
{

    /**
     * 验证签名逻辑
     * @param array $data
     * @param $secret_key
     * @param $sign
     * @return bool
     */
    public static function checkSign(array $data = array(), $secret_key, $sign)
    {
        return $sign == SignHelper::sign($data, $secret_key);
    }

    /**
     * 签名逻辑
     * @param array $data
     * @param $secret_key
     * @return string
     */
    public static function sign(array $data = array(), $secret_key)
    {
        $str = "";
        ksort($data);
        foreach ($data as $key => $val) {
            if ("item_name" != strtolower($key)
                && "item_desc" != strtolower($key)
                && "token" != strtolower($key)
                && "sign" != strtolower($key)) {

                $str .= $val;
            }
        }

        return md5($str . $secret_key);
    }

}

?>