<?php
/*
 * @Author: IQSAY
 * @Date: 2022-07-20
 * @Last Modified by: Karan
 * @Last Modified time: 2022-07-23 12:00
 */
namespace App\Components\Api;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Psr7\Message;
use Session;
class Base {

    protected $PRIMARY_API_key;
    protected $SECONDARY_API_key;
    protected $LIVE_PRIMARY_API_key;
    protected $LIVE_SECONDARY_API_key;
    protected $http_client;
    protected $live_http_client;
    protected $nexio_base_url;
    protected $checkout_base_url;


    /*
     * @return void
    */
    public function __construct(){
    }

    protected function setCredentials() {
        $this->PRIMARY_API_key = env('DIRECT_SCALE_PRIMARY_API_KEY');
        $this->SECONDARY_API_key = env('DIRECT_SCALE_SECONDARY_API_KEY');
        $this->LIVE_PRIMARY_API_key = env('DIRECT_SCALE_LIVE_PRIMARY_API_KEY');
        $this->LIVE_SECONDARY_API_key = env('DIRECT_SCALE_LIVE_SECONDARY_API_KEY');
        $this->nexio_base_url = env('NEXIO_BASE_URL');
        $this->checkout_base_url = env('checkout_baseurl');

    }

    protected function setClient()
    {
        if (empty($this->PRIMARY_API_key) && empty($this->LIVE_SECONDARY_API_key)) {
            throw new \Exception("In order to use DirectScale Api, please set DIRECT_SCALE_LIVE_SECONDARY_API_KEY, DIRECT_SCALE_LIVE_PRIMARY_API_KEY via env");
        }
        $this->http_client = new Client(['base_uri' => $this->base_url]);
    }

    protected function setLiveClient()
    {
        if (empty($this->LIVE_PRIMARY_API_key) && empty($this->SECONDARY_API_key)) {
            throw new \Exception("In order to use DirectScale Api, please set DIRECT_SCALE_PRIMARY_API_KEY, DIRECT_SCALE_SECONDARY_API_KEY via env");
        }
        $this->live_http_client = new Client(['base_uri' => $this->live_base_url]);
    }

    /**
     * @param $uri URI path
     * @param $request_type GET|POST
     * @param $params array
    */
    protected function makeRequest($uri, $request_type = "GET", $params = []){
        $this->setCredentials();
        $this->setClient();

        $param['headers'] = array(
            'Content-Type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => ($this->PRIMARY_API_key) ?? $this->SECONDARY_API_key
        );
        $params = array_merge($param, $params);
        try{
            $response = $this->http_client->request($request_type, $uri, $params);
            return $response;
        }catch (ClientException | \GuzzleHttp\Exception\ServerException | \GuzzleHttp\Exception\ConnectException $e) {

            if($e->getCode() != 0){
                return $e->getResponse();
            }else{
                return "";
            }

        }
    }

    /**
     * Some API's doesn't worked on Stage
     * Make Request on Live API
     * @param $uri URI path
     * @param $request_type GET|POST
     * @param $params array
    */
    protected function makeRequestLiveAPI($uri, $request_type = "GET", $params = []){
        $this->setCredentials();
        $this->setLiveClient();

        $params['headers'] = array(
                    'Content-Type' => 'application/json',
                    'Ocp-Apim-Subscription-Key' => ($this->LIVE_PRIMARY_API_key) ?? $this->LIVE_SECONDARY_API_key
                );
        try{
            $response = $this->live_http_client->request($request_type, $uri, $params);
            return $response;
        }catch (ClientException | \GuzzleHttp\Exception\ServerException $e) {
            return $e->getResponse();
        }
    }

    /*
     * @param $response array
     * @return array
     */
    protected function formatResponse($response){
        $status_code = 0;
        if($response != "") {
            $status_code = $response->getStatusCode();
        }

        if ($status_code == 200 || $status_code == 201) {
            Session::put('website_down', 0);
            $response = json_decode($response->getBody()->getContents(), true);
                return $response;
        } else {
            if($response != "" && $response->getHeaders()) {
                Session::put('website_down', 0);
                $error = $response->getBody()->getContents();
                return ['isError' => true, "error" => $error, 'headers' => $response->getHeaders(), "status_code" => $status_code];
            } else{
                clearUserData();
                if(Session::get('website_down') == 0){
                    Session::put('website_down', 1);
                    //return redirect()->back();
                }

                return ['isError' => true, "error" => "A scheduled maintenance is taking place right now. If you are facing issues placing your order, please try again in one hour.", 'headers' => '', "status_code" => $status_code];
            }

        }
    }

    protected function setNexioClient()
    {
  
        $this->http_client = new Client(['base_uri' => $this->nexio_base_url]);
    }

    protected function makeNexioPayment($uri, $request_type = "GET", $params = [])
    {
       $this->setCredentials();
       $this->setNexioClient();
       $param['headers'] = array(
           'accept' => 'application/json',
           'Authorization' => 'Basic '.env('NEXIO_SANDBOX_AUTHORIZATION'),
           'content-type' => 'application/json',
 
       );
   
       $params = array_merge($param, $params);
       try{
           $response = $this->http_client->request($request_type, $uri, $params);
           return $response;
       }catch (ClientException | \GuzzleHttp\Exception\ServerException $e) {
           return $e->getResponse();
       }
    }

     /*
     * @param $response array
     * @return array
     */
    protected function formatNexioResponse($response){
        $status_code = $response->getStatusCode();
        if ($status_code == 200 || $status_code == 201) {
            $response = $response->getBody()->getContents();
                return $response;
        } else {
            $error = $response->getBody()->getContents();
            return ['isError' => true, "error" => $error, 'headers' => $response->getHeaders(), "status_code" => $status_code];
        }
    }

        /**
     * @param $uri URI path
     * @param $request_type GET|POST
     * @param $params array
    */
    protected function makeNexioTokenRequest($uri, $request_type = "GET", $params = []){
       
        $this->setCredentials();
        $this->setNexioClient();
        $param['headers'] = array(
            'Accept' => 'application/json',
            'Authorization' => 'Basic '.env('NEXIO_SANDBOX_AUTHORIZATION'),
            'Content-Type' => 'application/json',
     
        );
     
        $params = array_merge($param, $params);
        try{
            $response = $this->http_client->request($request_type, $uri, $params);
            return $response;
        }catch (ClientException | \GuzzleHttp\Exception\ServerException $e) {
            return $e->getResponse();
        }
    }
    

          /**
     * @param $uri URI path
     * @param $request_type GET|POST
     * @param $params array
    */
    protected function makeNexioIframeRequest($uri, $request_type = "GET", $params = []){
        $this->setCredentials();
        $this->setNexioClient();
        $param['headers'] = array(
            'Accept' => 'application/json',
            'displaySubmitButton' => true,

        );
    
        $params = array_merge($param, $params);
        try{
            $response = $this->http_client->request($request_type, $uri, $params);
            return $response;
        }catch (ClientException | \GuzzleHttp\Exception\ServerException $e) {
            return $e->getResponse();
        }
    }
    //  checkout.com
    /**
     * @param $uri URI path
     * @param $request_type GET|POST
     * @param $params array
    */
    protected function makeCheckoutAuth($uri, $request_type = "GET", $params = []){
        $this->setCredentials();
        $this->setCheckoutClient();
        $param['headers'] = array(
            'Authorization' => 'Bearer '.env('CHECKOUT_SANDBOX_AUTHORIZATION'),
            'Content-Type' => 'application/json',
     
        );
        $params = array_merge($param, $params);
        try{
            $response = $this->http_client->request($request_type, $uri, $params);
            return $response;
        }catch (ClientException | \GuzzleHttp\Exception\ServerException $e) {
            return $e->getResponse();
        }
    } 
    protected function setCheckoutClient()
    {
      $this->http_client = new Client(['base_uri' => $this->checkout_base_url]);
    }
}
