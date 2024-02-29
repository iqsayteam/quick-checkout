<?php

/**
 * @Author: IQSAY
 * @Date: 2022-07-23
 * @Last Modified by: Karan
 * @Last Modified time: 2022-07-23 12:02
 */
namespace App\Components\Directscale;

use App\Components\Api\Directscale;

class Validate extends Directscale {

    protected $uri = "validate";

    /**
     * @param $email | Email Address
     * @return array
    */
    public function validateEmail($email){
        $response = $this->makeRequest("{$this->uri}/email-address/".$email, "GET");
        return $this->formatResponse($response);
    }

    /**
     * @param $username | string | mixed
     * @return array
    */
    public function validateUsername($username){
        $response = $this->makeRequest("{$this->uri}/availability-check/usernames/".$username, "GET");
        return $this->formatResponse($response);
    }

    /**
     * @param $taxId | string
     * @return array
    */
    public function validateTaxId($taxId){
        $response = $this->makeRequest("{$this->uri}/tax-id/".$taxId, "GET");
        return $this->formatResponse($response);
    }
}
