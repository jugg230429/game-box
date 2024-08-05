<?php

namespace plugins\qcloud\lib;

use Qcloud\Cos\Client;
use think\Controller;

class Qcloud extends Controller
{

    private $config;

    private $storageRoot;

    /**
     * @var \plugins\oss\OssPlugin
     */
    private $plugin;

    /**
     * Oss constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $pluginClass = cmf_get_plugin_class('Qcloud');
        $this->plugin = new $pluginClass();
        $this->config = $this->plugin->getConfig();
        if($this->config['domain']){
            $this->storageRoot = $this->config['schema'] . '://' . $this->config['domain'] . '/';
        }else{
            $this->storageRoot = $this->config['schema'] . '://' . $this->config['bucket'] .'.cos.'. $this->config['region'].'.myqcloud.com' . '/';
        }
    }

    /**
     * 文件上传
     * @param string $file 上传文件路径
     * @param string $filePath 文件路径相对于upload目录
     * @param string $fileType 文件类型,image,video,audio,file
     * @param array $param 额外参数
     * @return mixed
     */
    public function upload($file, $filePath, $fileType = 'image', $param = null,$tmp_flag)
    {   
        $flag = 0;  // 上传标识
        // 文件名称
        $conf['schema'] = $this->config['schema'];
        $conf['region'] = $this->config['region'];
        $conf['credentials']['secretId'] = $this->config['secretId'];
        $conf['credentials']['secretKey'] = $this->config['secretKey'];
        $object = $file;
        $client = new Client($conf);
        try {
            $client->upload($this->config['bucket'],$object,fopen($filePath, 'rb'));
            $flag = 1;
        } catch (\Exception $e) {
            // ---------原 START
            // trace('error', $e -> getFile() . '[' . $e -> getLine() . ']' . PHP_EOL . $e -> getMessage());
            // abort(401, $e -> getFile() . '[' . $e -> getLine() . ']' . PHP_EOL . $e -> getMessage());
            // return false;
            // ---------原 END
            $flag = 0;
        }
        //第一个参数：bucket名字、第二个参数：文件名字、第三个参数：文件路径。

        if($flag == 1){
            // 上传成功
            $previewUrl = $fileType == 'image' ? $this->getPreviewUrl($file) : $this->getFileDownloadUrl($file);
            $url        = $fileType == 'image' ? $this->getImageUrl($file) : $this->getFileDownloadUrl($file);
            
        }else{
            // 上传失败
            if($tmp_flag == 1){
                $return_data = [
                    'code'=>-1,
                    'msg'=>'腾讯云参数配置错误',
                    'data'=>'',
                    'url'=>'',
                    'wait'=>3,
                ];
                return $return_data;
                exit;
            }
            if($tmp_flag == 2){
                $return_data = [
                    'code'=>-2,
                    'msg'=>'腾讯云参数配置错误',
                    'data'=>'',
                    'url'=>'',
                    'wait'=>3,
                ];
                return $return_data;
                exit;
            }
            $this->error('腾讯云参数配置错误');
            exit;
        }
        
        return [
            'preview_url' => $previewUrl,
            'url'         => $url,
        ];
    }

    /**
     * 获取图片预览地址
     * @param string $file
     * @param string $style
     * @return mixed
     */
    public function getPreviewUrl($file, $style = '')
    {
        $url = $this->getUrl($file, $style);

        return $url;
    }

    /**
     * 获取图片地址
     * @param string $file
     * @param string $style
     * @return mixed
     */
    public function getImageUrl($file, $style = '')
    {
        $config = $this->config;
        $url    = $this->storageRoot . $file;

        if (!empty($style)) {
            $url = $url . $config['style_separator'] . $style;
        }

        return $url;
    }

    /**
     * 获取文件地址
     * @param string $file
     * @param string $style
     * @return mixed
     */
    public function getUrl($file, $style = '')
    {
        $config = $this->config;
        $url    = $this->storageRoot . $file;

        if (!empty($style)) {
            $url = $url . $config['style_separator'] . $style;
        }

        return $url;
    }

    /**
     * 获取文件下载地址
     * @param string $file
     * @param int $expires
     * @return mixed
     */
    public function getFileDownloadUrl($file, $expires = 3600)
    {
        return $this->storageRoot .$file;
    }

    /**
     * 获取云存储域名
     * @return mixed
     */
    public function getDomain()
    {
        return $this->config['domain'];
    }

    /**
     * 获取文件相对上传目录路径
     * @param string $url
     * @return mixed
     */
    public function getFilePath($url)
    {
        $parsedUrl = parse_url($url);

        if (!empty($parsedUrl['path'])) {
            $url            = ltrim($parsedUrl['path'], '/\\');
            $config         = $this->config;
            $styleSeparator = $config['style_separator'];

            $styleSeparatorPosition = strpos($url, $styleSeparator);
            if ($styleSeparatorPosition !== false) {
                $url = substr($url, 0, strpos($url, $styleSeparator));
            }
        } else {
            $url = '';
        }

        return $url;
    }
    /**
     * 删除百度云存储文件
     * @access public
     * @param  string $object 文件名字
     * @param  string $bucket BucketName
     * @return false|File
     */

    public function file_del($object)
    {
        $conf['schema'] = $this->config['schema'];
        $conf['region'] = $this->config['region'];
        $conf['credentials']['secretId'] = $this->config['secretId'];
        $conf['credentials']['secretKey'] = $this->config['secretKey'];
        $cosClient = new Client($conf);
        $data['Bucket'] = $this->config['bucket'];
        $data['Key'] = $object;
        $cosClient->deleteObject($data);
    }
}