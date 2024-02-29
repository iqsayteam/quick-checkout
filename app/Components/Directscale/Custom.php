<?php

/**
 * @Author: IQSAY
 * @Date: 2022-07-21
 * @Last Modified by: Karan
 * @Last Modified time: 2022-07-21 15:13
 */
namespace App\Components\Directscale;

use App\Components\Api\Directscale;
use Session;
class Custom extends Directscale{

    protected $uri = "custom";

    /**
     * Active Countries Caller it calls the GET Request to DS to get the active countries
     * returns the formated response in array format
     * @return array the active countries returned in the form of array.
    */
    public function finalizeOrder($orderId){
        $response = $this->makeRequest(env('CUSTOM_DIRECT_SCALE_LINK')."Order/FinalizeOrder", "POST",$orderId);
        return $this->formatResponse($response);
    }

     public function getOrderPayments($orderId)
       {
        $response = $this->makeRequest(env('CUSTOM_DIRECT_SCALE_LINK')."Order/GetPaymentsByOrderId?OrderId=".$orderId, "POST");
        return $this->formatResponse($response);
       }

       public function updateOrder($details)
         {
            $response = $this->makeRequest(env('CUSTOM_DIRECT_SCALE_LINK')."Order/UpdatePaymentStatusByPaymentId", "POST",$details);
            return $this->formatResponse($response);
         }
        public function removeAppSession()
           {
            $appUrl =  "http://uat-enrollment.nvu-dev.com";;
            if(null !== Session::get('from_app') && Session::get('from_app'))
            {
               switch (Session::get('from_app')) {
                   case 'am':
                       $appUrl =  env('am_url');
                     break;
                   case 'shop':
                    $appUrl =  env('shop_url');
                     break;
                   case 'enroll':
                    $appUrl =  env('enroll_url');
                     break;
                     case 'voucher':
                      $appUrl =  env('voucher_url');
                       break;
                   default:
                   $appUrl =  env('voucher_url');
                 }
            }

             $response = $this->makeRequest("{$appUrl}/api/flush-session", "get");
              return $this->formatResponse($response);
           }
    /**
     * This function  is use for empty the card from Ecommerce after placed successfull order
     * returns the formated response in array format
     * @return array the active countries returned in the form of array.
    */
    public function emptyEcommerceCart($userId,$user_country, $from_app)
      {
        $response = $this->makeRequest(env('ecomm_api_url')."/delete-ecomm-cart/".$userId.'/'.$user_country.'?from_app='.$from_app, "POST");
        return $this->formatResponse($response);
      }
    public function get_gift_cards($data){
      $response = $this->makeRequest(env('CUSTOM_DIRECT_SCALE_LINK')."CustomApi/GetGiftCardDatabyAssignedToAssociateId", "POST", $data);
      return $this->formatResponse($response);
      }
    public function deduct_money_gift_cards($data){
      $response = $this->makeRequest(env('CUSTOM_DIRECT_SCALE_LINK')."CustomApi/DeductMoneyGiftCard", "POST", $data);
      return $this->formatResponse($response);
      }
    public function refund_add_money_gift_cards($data){
      $response = $this->makeRequest(env('CUSTOM_DIRECT_SCALE_LINK')."CustomApi/AddMoneyToGiftCard", "POST", $data);
      return $this->formatResponse($response);
      }

    public function CustomCheckoutApi($data)
     {
      $response = $this->makeRequest(env('CUSTOM_DIRECT_SCALE_LINK')."CustomApi/CustomCheckoutApi", "GET", $data);
      return $this->formatResponse($response);
     }

}
?>
