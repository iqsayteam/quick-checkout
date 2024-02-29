<?php
/**
 * @Author: IQSAY
 * @Date: 2022-07-20
 * @Last Modified by: Karan
 * @Last Modified time: 2022-07-20 11:50
 */
namespace App\Components\Directscale;

use App\Components\Api\Directscale;

class SSO extends Directscale{

    protected $uri = "sso";

 
        /**
     * fetching Payment Merchants code from Directscale API 
     * if required parametor is not correct return false
     * @param  $customer_id | integer
     * @param  $store_id|integer
     * @param  $country_code|string
     * @return Iframe array| object
    */
    public function get_customer_detail_by_token($token)
    {
        $response = $this->makeRequest("{$this->uri}/?token=".$token, "GET");
        return $this->formatResponse($response);
    }

}
