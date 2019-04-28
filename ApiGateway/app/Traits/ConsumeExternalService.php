<?php

namespace App\Traits;

use GuzzleHttp\Client;

trait ConsumeExternalService
{
    /**
     * Send request to any service
     * @param $method
     * @param $requestUrl
     * @param array $formParams
     * @param array $headers
     * @return string
     * @throws
     */
    public function performRequest($method, $requestUrl, $formParams = [], $headers = [])
    {
        $setup = [
            'base_uri'  =>  $this->baseUri,
        ];

        if(app()->environment(['local','staging'])) {
            $setup['verify'] = false; //only verify in production
        }

        $client = new Client($setup);

        if(isset($this->secret))
        {
            $headers['Authorization'] = $this->secret;
        }

        $response = $client->request($method, $requestUrl, [
            'form_params' => $formParams,
            'headers'     => $headers,
        ]);
        return $response->getBody()->getContents();
    }
}