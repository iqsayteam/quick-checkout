<?php
namespace App\Components\nexio;
use App\Components\Api\nexioApi;
use App\Components\Api\Base;

class Process extends Base {

    protected $uri = "process";


    /**
     * Active Countries Caller it calls the GET Request to DS to get the active countries
     * returns the formated response in array format
     * @return array the active countries returned in the form of array.
    */
    public function makePayment($paymentDetail){
     
         $response = $this->makeNexioPayment("{$this->uri}", "POST",$paymentDetail);
         return $this->formatResponse($response);
    }

}
