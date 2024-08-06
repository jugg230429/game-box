<?php

namespace think\xigusdk;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class Xigu  {

    /**
     * 参数数据
     */
    private $sm_data;

    /**
     * @param $options 数组参数必填
     * $options = array(
     *
     * )
     * @throws Exception
     */
    public function  __construct($config)
    {
        if (!empty($config)) {
            $this->sm_data = $config;
        } else {
            throw new Exception("非法参数");
        }
    }

    public function sendSM($to,$templateId,$param=null,$type = 'json'){
        return $this->alibabSm($to,$templateId,$param);
    }
    public function alibabSm($to,$templateId,$param=null)
    {
        AlibabaCloud::accessKeyClient($this->sm_data['appid'], $this->sm_data['secret'])
            ->regionId('cn-hangzhou')
            ->asDefaultClient();
        try {

            $query = [
                    'RegionId' => "cn-hangzhou",
                    'PhoneNumbers' => $to,
                    'SignName' => $this->sm_data['public_tid'],
                    'TemplateCode' => $templateId,
                    // 'TemplateParam' => json_encode(['code'=>explode(',',$param)[0]]),
                ];
            if(is_array($param)){//目前每日通知短信为数组20210715
                $query['TemplateParam'] = json_encode($param, JSON_UNESCAPED_UNICODE);
            }else{
                $query['TemplateParam'] = json_encode(['code'=>explode(',',$param)[0]]);
            }
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')//固定
                // ->scheme('https') // https | http
                ->version('2017-05-25')//固定
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')//固定
                ->options(['query'=>$query])
                ->request();
            \think\Log::record($result);
            if($result['Code']=='OK'){
                $code['send_status']='000000';
            }else{
                $code['send_status']='111111';
                $code['error_msg'] = $result['Code'];//sdk简化版使用字段信息-20210420-byh
            }
            return json_encode($code);
        } catch (ClientException $e) {
            throw new \think\Exception($e->getErrorMessage());
        } catch (ServerException $e) {
            throw new \think\Exception($e->getErrorMessage());
        }
    }
    /**
     * 群发短信 批量发短信
     * created by wjd
     * 2021-8-2 11:58:58
     * 传入手机号数组
    */
    public function sendBatchSM($to,$templateId,$param=null,$type = 'json'){
        AlibabaCloud::accessKeyClient($this->sm_data['appid'], $this->sm_data['secret'])
            ->regionId('cn-hangzhou')
            ->asDefaultClient();
        try {

            $query = [
                    'RegionId' => "cn-hangzhou",
                    'PhoneNumbers' => $to,
                    'SignName' => $this->sm_data['public_tid'],
                    'TemplateCode' => $templateId,
                    // 'TemplateParam' => json_encode(['code'=>explode(',',$param)[0]]),
                ];

            $query = [
                'PhoneNumberJson' => "{13083538583,18151879885}",
                'SignNameJson' => "adfsfdfsdfsd",
                'TemplateCode' => "dsadsadadsdsadas",
                'TemplateParamJson' => "sadasdsadafdsfdf",
            ];

            // if(is_array($param)){//目前每日通知短信为数组20210715
            //     $query['TemplateParam'] = json_encode($param);
            // }else{
            //     $query['TemplateParam'] = json_encode(['code'=>explode(',',$param)[0]]);
            // }
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')//固定
                // ->scheme('https') // https | http
                ->version('2017-05-25')//固定
                ->action('SendBatchSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')//固定
                ->options(['query'=>$query])
                ->request();
            \think\Log::record($result);
            if($result['Code']=='OK'){
                $code['send_status']='000000';
            }else{
                $code['send_status']='111111';
                $code['error_msg'] = $result['Code'];//sdk简化版使用字段信息-20210420-byh
            }
            return json_encode($code);
        } catch (ClientException $e) {
            throw new \think\Exception($e->getErrorMessage());
        } catch (ServerException $e) {
            throw new \think\Exception($e->getErrorMessage());
        }    
    }
} 