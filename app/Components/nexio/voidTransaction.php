<?php
namespace App\Components\nexio;
use App\Components\Api\nexioApi;
use App\Components\Api\Base;

class voidTransaction extends Base {

    protected $uri = "void";


    /**
     * Active Countries Caller it calls the GET Request to DS to get the active countries
     * returns the formated response in array format
     * @return array the active countries returned in the form of array.
    */
    public function void($Authorization,$paymentDetail){
     
         $response = $this->makeNexioTokenRequest("{$this->uri}", "POST",$paymentDetail);
         return $this->formatResponse($response);
    }

}
