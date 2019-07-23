<?php
include_once ROOT_PATH . '/extends/sms_sdk/vendor/autoload.php';
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

// Download：https://github.com/aliyun/openapi-sdk-php
// Usage：https://github.com/aliyun/openapi-sdk-php/blob/master/README.md

class sendMsg
{
    /**
     * 短信验证码
     * @param string $phone
     * @param string $code
     * @return array
     */
    public function sendSms(string $phone, string $code):array
    {
        AlibabaCloud::accessKeyClient('LTAIsxBUb2aUU65F', 'KzdDKtqBOKZvdWVga8qZM54siEeXUq')
            ->regionId('cn-guangzhou')
            ->asDefaultClient();
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->options([
                    'query' => [
                        'RegionId' => "cn-guangzhou",
                        'PhoneNumbers' => "{$phone}",
                        'TemplateCode' => "SMS_170330855",
                        'SignName' => "myblog",
                        'TemplateParam' => "{\"code\":\"{$code}\"}",
                    ],
                ])
                ->request();
            $res_arr = $result->toArray();
            $return_data = array();
            if ($res_arr['Code'] == "OK"){
                $return_data['code'] = 200;
                $return_data['data'] = '发送成功';
            }
        } catch (ClientException $e) {
            $return_data['code'] = 500;
            $return_data['data'] = $e->getErrorMessage();
        } catch (ServerException $e) {
            $return_data['code'] = 500;
            $return_data['data'] = $e->getErrorMessage();
        }
        return $return_data;
    }
}
