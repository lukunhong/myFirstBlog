<?php
require __DIR__ . '/vendor/autoload.php';

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

try {
    // Create Client
    AlibabaCloud::accessKeyClient('foo', 'bar')->asDefaultClient();
    // Chain calls and send RPC request
    $result = AlibabaCloud::rpc()
                          ->regionId('cn-hangzhou')
                          ->product('Cdn')
                          ->version('2014-11-11')
                          ->action('DescribeCdnService')
                          ->method('POST')
                          ->request();
} catch (ClientException $exception) {
    // Get client error message
    print_r($exception->getErrorMessage());
} catch (ServerException $exception) {
    // Get server error message
    print_r($exception->getErrorMessage());
}
