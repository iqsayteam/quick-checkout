<?php

/**
 * @Author: IQSAY
 * @Date: 2022-07-20
 * @Last Modified by: Karan
 * @Last Modified time: 2022-07-20 15:13
 */
namespace App\Components\Api;
use App\Components\Api\Base;

class Checkout extends Base {
    protected $base_url;
    protected $live_base_url;

    public function __construct(){  
        $this->base_url = env('checkout_baseurl');
        $this->live_base_url = env('checkout_baseurl');
    }

    public function get(){
        $response = $this->makeRequest($this->uri, "GET");
        return $this->formatResponse($response);
    }
}
