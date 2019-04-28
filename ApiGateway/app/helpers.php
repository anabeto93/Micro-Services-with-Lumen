<?php

use Illuminate\Support\Facades\Log;

/**
 * Send a POST request
 *
 * @param string $url
 * @param array $body
 * @param array $headers
 * @param string $type
 * @param string $method
 *
 * @return mixed
 */
function sendPost($url,array $body=[], array $headers=[], $type='json',$method='POST')
{
    $client = new \GuzzleHttp\Client();

    $resp = 'default';

    try{
        $head = [
            'verify' => false
        ];

        if($type==='json') {
            $head['json'] = $body;
        }else {
            $head[$type] = $body;
        }

        if(!empty($headers) && count($headers) > 0) {
            $head['headers'] = $headers;
        }

        Log::info('Sending '.$method.' Request to '.$url);
        Log::debug($head);

        $resp = $client->request($method,$url,$head);
    } catch (\GuzzleHttp\Exception\GuzzleException $e) {
        Log::info('Error in sending post request');
        Log::debug($e->getMessage());

        $init = explode('resulted in a',$e->getMessage());
        Log::info('Init Guzzle Error'); Log::debug($init);
        $init = count($init) && array_key_exists(1,$init) > 0 ? $init[1] : $init[0];
        $msg = explode('response:',$init)[0];
        //remove the back ticks
        $b_ticks = explode('`',$msg);
        $msg = count($b_ticks)>0 && array_key_exists(1,$b_ticks) ? $b_ticks[1] : $b_ticks[0];

        $result = [
            'status' => 'Internal Error.',
            'code' => '500',
            'reason' => $msg,
            //'url' => $url
        ];

        //also check for the special messages or string returned as a reason for the failure
        $init = explode('resulted in a',$e->getMessage());
        if(is_array($init) && count($init)>1) {
            if(array_key_exists(1,$init)) {
                $temp = explode('response:',$init[1]);
                if(count($temp)>1) {
                    $temp = array_key_exists(1,$temp) ? $temp[1] : $temp[0];
                    if(isJson($temp)) {
                        $temp = json_decode($temp,true);
                        Log::info('Guzzle Error response is in JSON format');
                        Log::debug($temp);
                        if(is_array($temp)) {
                            $result['data'] = $temp;
                        }
                    }
                }
            }
        }

        Log::info('Reconstructed error message');
        Log::debug($result);

        $resp = 'error';// so it skips processing
    }

    if($resp==='default') {
        $result = 'Error occurred';
    }elseif ($resp!=='error'){
        $result = $resp->getBody()->getContents();

        Log::info('Immediate response ');
        Log::debug($result);

        if(is_string($result) && is_null(json_decode($result,true))) {
            Log::info('Internal Error from External side');
            $result = errorResponse([],'Error occurred while processing. Please try again later!',500);
        }else{
            $result = json_decode($result,true);
        }
    }

    Log::info('Response from sending '.$method.' to '.$url);
    Log::debug($result);

    return $result;
}

/**
 * Default Success Response
 * @param array $data
 * @param string $status
 * @param int|string $code
 * @param string $reason
 * @return array
 */
function successResponse($data=[],$status='Success',$code='000', $reason='')
{
    $status = is_null($status) ? 'Success' : $status;
    $data = is_null($data) ? [] : $data;
    $code = is_null($code) ? '000' : $code;

    $result =['status'=>$status, 'code'=>$code];
    if(is_string($reason) && $reason !=='') {
        $result['reason'] = $reason;
    }
    if(is_array($data) && !empty($data)) {
        $result['data'] = $data;
    }
    return $result;
}

/**
 * Default Error Response
 * @param array $data
 * @param string $status
 * @param int|string $code
 * @param string $reason
 * @return array
 */
function errorResponse($data=[],$status='Error', $code='900', $reason='')
{
    $status = is_null($status) ? 'Error' : $status;
    $data = is_null($data) ? [] : $data;
    $code = is_null($code) ? '900' : $code;

    $result =['status'=>$status, 'code'=>$code];
    if(is_string($reason) && $reason !=='') {
        $result['reason'] = $reason;
    }
    if(is_array($data) && !empty($data)) {
        $result['data'] = $data;
    }
    return $result;
}

/**
 * Convert Minor Units to Float
 * @param string $amount
 *
 * @return float number
 */
function minorToFloat($amount)
{
    $number = "The minor units should be numeric of type string";
    if (is_string($amount)) {
        $number = ( (int)$amount/100);
        //$number = round((float) $number, 2);
        $number = number_format( (float) $number, 2, '.','' );
        return $number;
    }

    return $number;
}

/**
 * Convert Float to Minor Units
 * @param float $amount
 *
 * @return string $minor
 */
function floatToMinor($amount)
{
    $minor = "The number should be of type float";
    //Log::info('Received amount is '.$amount.' which is '.gettype($amount));

    if(is_float($amount) || is_double($amount)) {
        $number = $amount * 100;
        $zeros = 12 - strlen($number);
        $padding = '';
        //Log::info('The number of zeros to use is '.$zeros);
        for($i=0; $i<$zeros; $i++) {
            $padding .= '0';
        }
        //Log::info('Padding is '.$padding);
        $minor = $padding.$number;
    }elseif (strlen($amount)==12) {
        //Received an actual minor unit
        $minor = $amount;
    }

    //Log::info('Minor is '.$minor);
    return $minor;
}

/**
 * Send Get Request
 * @param string $url
 * @param array $body
 * @param array $headers
 * @return array
 */
function sendGetRequest($url,$body=null, $headers=[]) : array
{
    $client = new \GuzzleHttp\Client();

    try{
        if($body !== null && $body !=='') {
            $params = [
                'query' => $body,
                'verify' => false
            ];

            if(is_array($headers) && count($headers)>0) {
                $params['headers'] = $headers;
                Log::info('SendGETRequest Params');
                Log::debug($params);
            }

            $resp = $client->request('GET',$url,$params);
        }else {
            $resp = $client->request('GET',$url,[
                'verify' => false
            ]);
        }

    } catch (\GuzzleHttp\Exception\GuzzleException $e) {
        Log::info('Error in sending GET request');
        Log::debug($e->getMessage());

        $init = explode('resulted in a',$e->getMessage());
        Log::info('Init Guzzle Error'); Log::debug($init);
        $init = count($init)>0 && array_key_exists(1,$init) > 0 ? $init[1] : $init[0];
        $msg = explode('response:',$init)[0];
        //remove the back ticks
        $b_ticks = explode('`',$msg);
        $msg = count($b_ticks)>0 && array_key_exists(1,$b_ticks) ? $b_ticks[1] : $b_ticks[0];

        $result = [
            'status' => 'Internal Error.',
            'code' => '500',
            'reason' => $msg
        ];

        //also check for the special messages or string returned as a reason for the failure
        $init = explode('resulted in a',$e->getMessage());
        if(is_array($init) && count($init)>1) {
            if(array_key_exists(1,$init)) {
                $temp = explode('response:',$init[1]);
                if(count($temp)>1) {
                    $temp = array_key_exists(1,$temp) ? $temp[1] : $temp[0];
                    if(isJson($temp)) {
                        $temp = json_decode($temp,true);
                        Log::info('Guzzle Error response is in JSON format');
                        Log::debug($temp);
                        if(is_array($temp)) {
                            $result['data'] = $temp;
                        }
                    }
                }
            }
        }

        Log::info('Reconstructed error message');
        Log::debug($result);

        $resp = 'error';// so it skips processing
    }

    if($resp==='default') {
        $result = 'Error occurred';
    }elseif ($resp!=='error'){
        $result = $resp->getBody()->getContents();
        Log::info('Immediate response ');
        Log::debug($result);

        if(is_string($result) && is_null(json_decode($result,true))) {
            Log::info('Internal Error from External side');
            $result = errorResponse([],'Error occurred while processing. Please try again later!',500);
        }else{
            $result = json_decode($result,true);
        }
    }

    Log::info('Response from sending GET to '.$url);
    Log::debug($result);

    return $result;
}

/**
 * Create a random string
 * @param int $length
 * @return string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * simple method to encrypt or decrypt a plain text string
 * initialization vector(IV) has to be the same when encrypting and decrypting
 *
 * @param string $action: can be 'encrypt' or 'decrypt'
 * @param string $string: string to encrypt or decrypt
 * @param string $broker_key
 * @param string $broker_secret
 *
 * @return string
 */
function encrypt_decrypt($action, $string,$broker_key='Richard',$broker_secret='Hajara') {
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_key = $broker_key;
    $secret_iv = $broker_secret;
    // hash
    $key = hash('sha256', $secret_key);

    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if( $action == 'decrypt' ) {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
}

/**
 * Fastest way of simply checking if a string is JSON formatted
 * @param string $json
 * @return mixed
 */
function isJson($json) {
    json_decode($json);
    return (json_last_error() == JSON_ERROR_NONE);
}