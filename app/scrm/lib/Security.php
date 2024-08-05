<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-12-14
 */

namespace app\scrm\lib;

class Security
{
    /**
     * var string $method 加解密方法，可通过openssl_get_cipher_methods()获得
     */
    protected $method;

    /**
     * var string $secret_key 加解密的密钥
     */
    protected $secret_key;

    /**
     * var string $iv 加解密的向量，有些方法需要设置比如CBC
     */
    protected $iv;

    /**
     * var string $options （不知道怎么解释，目前设置为0没什么问题）
     */
    protected $options;

    /**
     * 构造函数
     *
     * @param string $key 密钥
     * @param string $method 加密方式
     * @param string $iv iv向量
     * @param mixed $options 还不是很清楚
     *
     */
    public function __construct($key, $method = 'aes-128-ecb', $iv = '', $options = OPENSSL_RAW_DATA)
    {
        // key是必须要设置的
        $this -> secret_key = isset($key) ? $key : exit('参数不存在');
        $this -> method = $method;
        $this -> iv = $iv;
        $this -> options = $options;
    }

    /**
     * 加密方法，对数据进行加密，返回加密后的数据
     *
     * @param string $data 要加密的数据
     *
     * @return string
     *
     */
    public function encrypt($data)
    {
        $data = openssl_encrypt($data, $this -> method, base64_decode($this -> secret_key), $this -> options, $this -> iv);
        return base64_encode($data);
    }

    /**
     * 解密方法，对数据进行解密，返回解密后的数据
     *
     * @param string $data 要解密的数据
     *
     * @return string
     *
     */
    public function decrypt($data)
    {
        $encrypted = base64_decode($data);
        return openssl_decrypt($encrypted, $this -> method, base64_decode($this -> secret_key), $this -> options, $this -> iv);
    }
}
