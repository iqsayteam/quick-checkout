<?php

/**
 * @Author: IQSAY
 * @Date: 2022-07-21
 * @Last Modified by: Karan
 * @Last Modified time: 2022-07-21 15:13
 */
namespace App\Components\Directscale;

use App\Components\Api\Directscale;

class Address extends Directscale{

    protected $uri = "address";

    /**
     * Active Countries Caller it calls the GET Request to DS to get the active countries
     * returns the formated response in array format
     * @return array the active countries returned in the form of array.
    */
    public function get_active_countries(){
        $response = $this->makeRequest("{$this->uri}/active-countries", "GET");
        return $this->formatResponse($response);
    }

    /**
     * States Caller it calls the GET request to DS to get the states based on country code
     * returns the states in array format
     * @param $country_code | the two lowwercase character long country code
     * @return array the states returned in the form of array.
    */
    public function get_all_state($country_code){
        $response = $this->makeRequest("{$this->uri}/".$country_code."/states", "GET");
        return $this->formatResponse($response);
    }

    /**
     * Regions Caller it calls the GET request to DS to get the region id based on country code and state code
     * @param string $country_code | the two lowwercase character long country code
     * @param string $state_code | the two lowwercase character long state code
     * @return integer  the region id returned in the form of a integer.
    */
    public function get_region_by_id($country_code = 'us', $state_code = 'fl'){
        $response = $this->makeRequest("{$this->uri}/regions?countryCode=".$country_code."&stateCode=".$state_code, "GET");
        return $this->formatResponse($response);
    }
}
