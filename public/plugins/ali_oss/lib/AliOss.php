<?php

namespace plugins\ali_oss\lib;


use OSS\OssClient;
use think\Controller;

class AliOss extends Controller
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
        $pluginClass = cmf_get_plugin_class('AliOss');

        $this->plugin = new $pluginClass();
        $this->config = $this->plugin->getConfig();
        $this->config['protocol'] = request()->server()['REQUEST_SCHEME']?:'http';
        $this->storageRoot = $this->config['protocol'] . '://' . $this->config['bucket'] .'.'. $this->config['domain'] . '/';
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
        try{
            $accessKeyId = $this->config['accesskeyid'];
            $accessKeySecret = $this->config['accesskeysecret'];
            $endpoint = $this->config['domain_upload'];
            $bucket = $this->config['bucket'];
            $internal = $this->config['internal'];
            if($internal){//开启内网上传
                $updomain = explode('.',$endpoint);
                $updomain[0] = $updomain[0].'-internal';
                $endpoint = implode('.',$updomain);
            }
            // 文件名称
            $object = $file;

            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->uploadFile($bucket, $object, $filePath);
            $flag = 1;
        }catch(\Exception $e){
            // $e->getMessage();
            $flag = 0;
            // $this->error('阿里云参数配置不正确');
            // exit;
        }
        if($flag == 1){
            // 上传成功
            $previewUrl = $fileType == 'image' ? $this->getPreviewUrl($file) : $this->getFileDownloadUrl($file);
            $url        = $fileType == 'image' ? $this->getImageUrl($file) : $this->getFileDownloadUrl($file);
            
        }else{
            // 上传失败
            if($tmp_flag == 1){
                $return_data = [
                    'code'=>-1,
                    'msg'=>'阿里云参数配置错误',
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
                    'msg'=>'阿里云参数配置错误',
                    'data'=>'',
                    'url'=>'',
                    'wait'=>3,
                ];
                return $return_data;
                exit;
            }
            $this->error('阿里云参数配置错误');
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
     * @函数或方法说明
     * @删除oss文件
     * @param $object
     *
     * @author: 郭家屯
     * @since: 2020/8/7 15:00
     */
    public function file_del($object)
    {
        $accessKeyId = $this->config['accesskeyid'];
        $accessKeySecret = $this->config['accesskeysecret'];
        $endpoint = $this->config['domain'];
        $bucket = $this->config['bucket'];
        $internal = $this->config['internal'];
        if($internal){//开启内网上传
            $updomain = explode('.',$endpoint);
            $updomain[0] = $updomain[0].'-internal';
            $endpoint = implode('.',$updomain);
        }
        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $ossClient->deleteObject($bucket, $object);
    }
}