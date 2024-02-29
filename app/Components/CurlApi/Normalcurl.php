<?php
/*
 * @Author: IQSAY
 * @Date: 2022-10-14
 * @Last Modified by: Gourav
 * @Last Modified time: 2022-10-14
 */
namespace App\Components\CurlApi;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Psr7\Message;
class Normalcurl{

    protected $http_client;

    /*
     * @return void
    */
    public function __construct(){
    }

    public function curlRequest($uri, $request_type = "GET", $params = []) {

        $param['headers'] = array(
            'Content-Type' => 'application/json',
        );
        $params = array_merge($param, $params);
        // dd($params);
        try{

            $this->http_client = new Client(['base_uri' => $uri]);
            $response = $this->http_client->request($request_type, $uri, $params);
            return $this->formatResponse($response);
        }catch (ClientException | \GuzzleHttp\Exception\ServerException $e) {

            return $this->formatResponse($e->getResponse());
        }
    }

    /*
     * @param $response array
     * @return array
     */
    protected function formatResponse($response){

        // dd($response->getBody()->getContents());
        $status_code = $response->getStatusCode();
        if ($status_code == 200 || $status_code == 201) {
            $response = json_decode($response->getBody()->getContents(), true);
                return $response;
        } else {
            $error = $response->getBody()->getContents();
            return ['isError' => true, "error" => $error, 'headers' => $response->getHeaders(), "status_code" => $status_code];
        }
    }

    
}
