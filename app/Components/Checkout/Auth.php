<?php
namespace App\Components\Checkout;
use App\Components\Api\Checkout;
use App\Components\Api\Base;

class Auth extends Base {

    protected $uri = "payments";


    /**
     * Active Countries Caller it calls the GET Request to DS to get the active countries
     * returns the formated response in array format
     * @return array the active countries returned in the form of array.
    */
    public function Authorization($paymentDetail){
     
        $response = $this->makeCheckoutAuth("{$this->uri}","POST",$paymentDetail);
        return $this->formatResponse($response);
   }
   
    public function capturePayment($paymentId,$data){
     
          $response = $this->makeCheckoutAuth("{$this->uri}/".$paymentId."/captures","POST",$data);
          return $this->formatResponse($response);
     }

   public function getPaymentStatus($paymentId){
     
    $response = $this->makeCheckoutAuth("{$this->uri}/".$paymentId,"GET");
    return $this->formatResponse($response);
   }
   
   public function voidPayment($paymentId,$data){
     
    $response = $this->makeCheckoutAuth("{$this->uri}/".$paymentId."/voids","POST",$data);
    return $this->formatResponse($response);
   }


}
