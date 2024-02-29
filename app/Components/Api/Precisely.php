<?php

/**
 * @Author: IQSAY
 * @Date: 2022-11-18
 * @Last Modified by: Kreso Vargec
 * @Last Modified time: 2022-11-18 12:13
 */

namespace App\Components\Api;

use App\Components\Api\Base;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Precisely extends Base
{
    protected $ship_key;
    protected $ship_secret;
    protected $subs_key;
    protected $base_url;
    protected $token;

    public function __construct()
    {
        $this->base_url = env('PRECISELY_API_URL');
        $this->ship_key = env('PRECISELY_API_KEY');
        $this->ship_secret = env('PRECISELY_API_SECRET');
    }

    public function get()
    {
        $response = $this->makeRequest($this->uri, "GET");
        return $this->formatResponse($response);
    }

    protected function makeRequest($uri, $request_type = "GET", $params = [])
    {
        $this->setClient();
        $this->setCredentials();
        $token=$this->get_token();


        $param['headers'] = array(//'Content-Type' => 'application/json',
            "Authorization" => "Bearer  " . $token);
        $params = array_merge($param, $params);
        try {
            $response = $this->http_client->request($request_type, $uri, $params);
            return $response;
        } catch (ClientException|\GuzzleHttp\Exception\ServerException $e) {
            return $e->getResponse();
        }
    }

    protected function setCredentials()
    {
        $this->ship_key = env('PRECISELY_API_KEY');
        $this->ship_secret = env('PRECISELY_API_SECRET');
        $this->subs_key = base64_encode($this->ship_key . ':' . $this->ship_secret);
        //$this->token = $this->get_token();

    }

    protected function setClient()
    {
        $this->http_client = new Client(['base_uri' => $this->base_url]);

    }

    private function get_token()
    {
        $uri = $this->base_url . 'oauth/token';

        $params['headers'] = array('Content-Type' => 'application/x-www-form-urlencoded', "Authorization" => "Basic " . $this->subs_key);

        $params['form_params'] = ['grant_type' => 'client_credentials'];
        $response = $this->http_client->request("POST", $uri, $params);
        $forToken = $this->formatResponse($response);
        $this->token = $forToken['access_token'];
        return $this->token;
    }

    /*
        * @param $response array
        * @return array
        */

    protected function formatResponse($response)
    {
        $status_code = $response->getStatusCode();
        // Should check all valid codes, added 204
        if ($status_code == 200 || $status_code == 201 || $status_code == 204) {
            $response = json_decode($response->getBody()->getContents(), true);
            return $response;
        } else {
            $error = $response->getBody()->getContents();
            return ['isError' => true, "error" => $error, 'headers' => $response->getHeaders(), "status_code" => $status_code];
        }
    }
}
