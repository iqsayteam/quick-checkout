<?php
namespace App\Components\nexio;
use App\Components\Api\nexioApi;
use App\Components\Api\Base;

class SaveCard extends Base {

    protected $uri = "saveCard";
  

   public function getIframe($token){
   
    $response = $this->makeNexioIframeRequest("https://api.nexiopaysandbox.com/pay/v3/saveCard?token=".$token."&shouldReturnHtml=false", "GET"); 
    return $this->formatNexioResponse($response);
      }
}
