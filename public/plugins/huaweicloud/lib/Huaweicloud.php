<?php

namespace plugins\Huaweicloud\lib;

use Obs\ObsClient;
use Obs\ObsException;
use think\Controller;

class Huaweicloud extends Controller
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
        require_once ROOT_PATH .'simplewind'.DS. 'vendor' . DS.'huaweicloud'.DS.'vendor'.DS.'autoload.php';
        vendor('huaweicloud.obs-autoloader');

        $pluginClass = cmf_get_plugin_class('Huaweicloud');
        // var_dump('华为云存储');exit;
        $this->plugin = new $pluginClass();
        $this->config = $this->plugin->getConfig();
        // 华为云中终端节点 就是所谓的 domain
        $this->config['domain'] = $this->config['endpoint'];
        $this->config['protocol'] = request()->server()['REQUEST_SCHEME']?:'https';
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
        // 创建ObsClient实例
        $config_tmp = [
            'key' => $this->config['key'],
            'secret' => $this->config['secret'],
            'endpoint' => $this->config['endpoint'],
            // 'ssl_verify' => false,
            // 'max_retry_count' => 1,
            // 'socket_timeout' => 20,
            // 'connect_timeout' => 20,
            // 'chunk_size' => 8196
        ];
        $obsClient = new ObsClient($config_tmp);
        
        $flag = 0;  // 上传标识
        new ObsException();
        try{
            $resp = $obsClient -> putObject([
                'Bucket' => $this->config['bucket'],
                'Key' => $file,
                // 'Metadata' => ['meta1' => 'value1', 'meta2' => 'value2'],
                'SourceFile' => $filePath,
                // 'Body' => 'Hello OBS',
                // 'ContentType' => 'text/plain'
            ]);
            $flag = 1; // 上传成功
            // printf("RequestId:%s\n", $resp['RequestId']);
            // printf("VersionId:%s\n", $resp['VersionId']);
            // printf("StorageClass:%s\n", $resp['StorageClass']);
            // printf("ETag:%s\n", $resp['ETag']);
        }catch (ObsException $obsException){
            $flag = 0;
            // printf("ExceptionCode:%s\n", $obsException->getExceptionCode());
            // printf("ExceptionMessage:%s\n", $obsException->getExceptionMessage());
        }
        if($flag == 1){
            // 上传成功
            $previewUrl = $fileType == 'image' ? $this->getPreviewUrl($file) : $this->getFileDownloadUrl($file);
            $url        = $fileType == 'image' ? $this->getImageUrl($file) : $this->getFileDownloadUrl($file);
            // $previewUrl = $resp['ObjectURL'];
            // $url        = $resp['ObjectURL'];
        }else{
            // 上传失败
            if($tmp_flag == 1){
                $return_data = [
                    'code'=>-1,
                    'msg'=>'华为云参数配置错误',
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
                    'msg'=>'华为云参数配置错误',
                    'data'=>'',
                    'url'=>'',
                    'wait'=>3,
                ];
                return $return_data;
                exit;
            }
            
            // return json($return_data);
            $this->error('华为云参数配置错误');
            // return json($return_data);
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

    // 删除 obs 文件
    public function file_del($object)
    {
        $obsClient = new ObsClient([
            'key' => $this->config['key'],
            'secret' => $this->config['secret'],
            'endpoint' => $this->config['endpoint'],
            // 'ssl_verify' => false,
            // 'max_retry_count' => 1,
            // 'socket_timeout' => 20,
            // 'connect_timeout' => 20,
            // 'chunk_size' => 8196
        ]);

        $resp = $obsClient->deleteObject ([
            'Bucket' => $this->config['bucket'],
            'Key' => $object,
        ]);
    }

    // 生成唯一的名称
    public function create_name(){
        // $a = uniqid(mt_rand(),1);  // eg:1798956441600f6f0cbbde28.43473864
        $a = uniqid(mt_rand());
        $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $length = strlen($str) - 1; 
        $start=rand(0,$length-6);
        $count = 6; 
        $use_str = str_shuffle($str);
        $b = substr($use_str, $start, $count);
        $final_name = date('Ymd').$a.$b;
        return $final_name;
    }
}