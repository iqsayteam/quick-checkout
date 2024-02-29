<?php

namespace App\Components\Directscale;

use App\Components\Api\Directscale;
use Carbon\Carbon;

class Customers extends Directscale{

    protected $uri = "customers";

    protected $uniquelink = "uniquelink";


    /**
     * Search distributor Caller it calls the GET request to DS to get the distributor information
     * @param $keyword | string | integer | BackOfficeId | Email Address
     * @return array
    */
    public function search_distributor($keyword){
        $response = $this->makeRequestLiveAPI("{$this->uri}/search/".$keyword, "GET");
		return $this->formatResponse($response);
    }
	
    public function get_customer_by_email($keyword){
        $response = $this->makeRequestLiveAPI("{$this->uri}/?email=".$keyword, "GET");
		return $this->formatResponse($response);
    }

    /**
     * @param $webalias | string | integer | Webalias
     * @return array
    */
    public function get_distributor_by_webalias($webalias){
        $response = $this->makeRequest("{$this->uri}/websiteInfo2?webAlias=".$webalias, "GET");
        return $this->formatResponse($response);
    }
    /**
     * @param $webalias | string | integer | Webalias
     * @return array
     */
    public function get_distributor_by_webalias_active($webalias)
    {
        $response = $this->makeRequest("{$this->uri}/websiteInfo2?webAlias=" . $webalias, "GET");

        $data = json_decode($response->getBody()->getContents(), true);
        if (isset($data['CustomerId'])) {
            $response2 = $this->makeRequest("{$this->uri}/" . $data['CustomerId'], "GET");
            $Associate = json_decode($response2->getBody()->getContents(), true);
            if ($Associate['CustomerStatus'] == 1 && ($Associate['CustomerType'] == 1 or $Associate['CustomerType'] == 2)) {

                return $data;
            } else {
                return null;
            }
        } else {
            return null;
        }

    }
        /**
     * @param array  user_details| string | integer | Webalias
     * @param array  user_details| string | integer | Webalias
     * @return user_id | integer
    */
    public function create_customer($userdata){
        $response = $this->makeRequest("{$this->uri}", "POST", $userdata);
       
        return $this->formatResponse($response);
    }


     /**
     * @param  user_id|integer
     * @return user details array| object
    */
    public function update_customer($user_id, $userdata){
        $response = $this->makeRequest("{$this->uri}/".$user_id, "PUT", $userdata);
        return $this->formatResponse($response);
    }

         /**
     * @param  user_id|integer
     * @return user details array| object
    */
    public function create_unique_link($user_id){
        $response = $this->uniqueLinkCreation(env("TRADINGMIDDLEWARE")."{$this->uniquelink}/", "GET", $user_id);
        return $this->formatResponse($response);
    }

    /**
     * @param  user_id|integer
     * @return user details array| object
    */
    public function update_webalias($user_id, $webalias_data){
        $response = $this->makeRequest("{$this->uri}/".$user_id, "PATCH", $webalias_data);
        return $this->formatResponse($response);
    }

    /**
     * This function reset the user password in 4th step of enrollment, where user provides his webalias and password,
     * @param  [int] $user_id
     * @param  [string] $new_password
     */
    public function reset_password($user_id, $new_password){
        $response = $this->makeRequest("{$this->uri}/".$user_id."/reset-password", "PUT", $new_password);
        return $this->formatResponse($response);
    }

     /**
     * @param  user_id|integer
     * @return user details array| object
    */
    public function update_customer_by_parameter($user_id, $userdata){
        $response = $this->makeRequest("{$this->uri}/".$user_id, "PATCH", $userdata);
        return $this->formatResponse($response);
    }

    /**
     * @param  user_id|integer
     * @param array  cart_details| array object
     * @return Order details array| object
    */
    public function calculate_customer_order_total($user_id, $userdata){
        $response = $this->makeRequest("{$this->uri}/".$user_id."/orders/calculate", "POST", $userdata);
        return $this->formatResponse($response);
    }

      /**
     * @param  user_id|integer
     * @return user details array| object
    */
    public function get_customer_by_id($user_id){
        $response = $this->makeRequest("{$this->uri}/".$user_id, "GET");
        return $this->formatResponse($response);
    }
         /**
     * @param  user_id|integer
     * @return user details array| object
    */
    public function get_customer_kit_level($user_id){
        $response = $this->makeRequest("{$this->uri}/".$user_id."/stats", "GET");
        return $this->formatResponse($response);
    }

      /**
     * fetching Payment Iframe code from Directscale API by using Ifrme required data like
     * if required parametor is not correct return false
     * @param $customer_id | integer
     * @param  $iframe_id|integer
     * @param  $store_id|integer
     * @param  $region_id|integer
     * @param  $language_code|string
     * @param  $country_code|string
     * @return Iframe array| object
    */
    public function get_payment_iframe($customer_id, $iframe_id, $store_id, $region_id, $language_code, $country_code)
    {
        $response = $this->makeRequestLiveAPI("{$this->uri}/".$customer_id."/paymentmethods/iframe?iframeid=".$iframe_id."&storeid=".$store_id."&countrycode=".$country_code."&region=".$region_id."&languagecode=".$language_code, "GET");
        return $this->formatResponse($response);
    }

    /**
     * Create customer order basis on the customer cart data and user
     * details like shipping details as well as payment details if all
     * required param is correct return user with their Order ID and other order details.
     * @param  $customer_id|integer
     * @param request order parametor | json object
     * @return $user details array| object
    */
    public function create_customer_order($customer_id, $orderdata){
        $response = $this->makeRequest("{$this->uri}/".$customer_id."/orders", "POST", $orderdata);
        return $this->formatResponse($response);
    }

     /**
     * Create customer autoship order basis on the customer cart data and user
     * details like shipping details as well as payment details if all
     * required param is correct return user with their Order ID and other order details.
     * @param  $customer_id|integer
     * @param request order parametor | json object
     * @return $user details array| object
    */
    public function create_customer_autoship_order($customer_id, $orderdata){
        $response = $this->makeRequest("{$this->uri}/".$customer_id."/autoship", "POST", $orderdata);
        return $this->formatResponse($response);
    }

    public function getBillingMethods($customer_id, $storeId){
        $response = $this->makeRequest("{$this->uri}/".$customer_id."/paymentmethods/?storeid=".$storeId, "GET");
        return $this->formatResponse($response);
    }

	public function userLogin($userCredential=null)
	{
		$response = $this->makeRequest("validate/login", "POST", $userCredential);
		return $this->formatResponse($response);
	}

	public function ssoLink($customerId=null)
	{
		$response = $this->makeRequest("sso/".$customerId, "GET");
		return $this->formatResponse($response);
	}

    public function VisionLifeStyleLink($customerId=null)
	{
        $url = env("STAGE_TRADING");
        $createData = array( "AssociateId" => $customerId);
      
        $data = json_encode($createData);
   
       $response = $this->curlpostfunction($url, $data);
        return  $response;
	}
	
	public function get_customer_orders($userId)
    {
        $response = $this->makeRequest("{$this->uri}/".$userId."/orders", "GET");
        return $this->formatResponse($response);   
    }
	
	public function get_customer_services($customer_id){
        $response = $this->makeRequest("{$this->uri}/".$customer_id."/services", "GET");
        return $this->formatResponse($response);
    }
	
	public function get_customer_autoships($customer_id){
        $response = $this->makeRequest("{$this->uri}/".$customer_id."/autoships?includeServiceAutoships=true&includeActiveAutoships=true&includeCanceledAutoships=false", "GET");
        return $this->formatResponse($response);
    }
	
	public function get_customer_old_autoships($customer_id){
        $response = $this->makeRequest("{$this->uri}/".$customer_id."/autoships?includeServiceAutoships=true&includeActiveAutoships=false&includeCanceledAutoships=true", "GET");
        return $this->formatResponse($response);
    }

	public function pause_subscription($subscriptionId, $data){
        $response = $this->makeRequest("{$this->uri}/autoship/".$subscriptionId, "PUT", $data);
        return $this->formatResponse($response);
    }

    public function deletePaymentMethod($customerID, $paymentmethodID, $merchantID)
    {
        $response = $this->makeRequest("{$this->uri}/".$customerID."/paymentmethods/".$paymentmethodID."?merchantid=".$merchantID, "DELETE    ");
        return $this->formatResponse($response); 
    }

    // public function create_payment_method($customerid,$orderdata){ 
    //     $response = $this->makeRequest("{$this->uri}/".$customerid."/paymentmethods/", "POST", $orderdata);
    //     return $this->formatResponse($response);
    // }

    public function get_customer_merchants($customer_id,$storeid,$countrycode){
        $response = $this->makeRequest("{$this->uri}/".$customer_id."/merchants?storeid=".$storeid."&countrycode=".$countrycode, "GET");
        return $this->formatResponse($response);
     } 

     public function get_payment_merchants($customer_id, $store_id, $country_code)
     {
         $response = $this->makeRequest("{$this->uri}/".$customer_id."/merchants?storeid=".$store_id."&countrycode=".$country_code, "GET");
         return $this->formatResponse($response);
     }

     public function save_payment_method($customer_id, $card_info)
     {
        $response = $this->makeRequest("{$this->uri}/".$customer_id."/paymentmethods", "POST", $card_info);
        return $this->formatResponse($response);
     }

     public function get_customer_custom_fields($customer_id)
     {
        $response = $this->makeRequest("{$this->uri}/".$customer_id."/customFields");
        return $this->formatResponse($response);
     }

     public function get_customer_data_point($postdata)
     { 
        $response = $this->makeRequest("{$this->uri}/customers-datapoints", "POST", $postdata);
        return $this->formatResponse($response);
     }


     public function get_customer_all_autoships($customer_id){
        $response = $this->makeRequest("{$this->uri}/".$customer_id."/autoships?includeServiceAutoships=true&includeActiveAutoships=true", "GET");
        return $this->formatResponse($response);
    }

	 /**
     * Created By: Raju
     * Created on: 31-jan-2023
     * Last Updated By: Raju
     * Last Updated on: 31-jan-2023
     * This function is used to find the vb kit of current user.
     * @param mixed $customer_id
    */
    public function get_vb_kit($customer_id)
    {
     
        $response = $this->makeRequest("{$this->uri}/".$customer_id."/services", "GET");
        return $this->formatResponse($response);   
    }

	/**
     * Created By: Aman
     * Created on: 20-feb-2023
     * Last Updated By: Aman
     * Last Updated on: 20-feb-2023
     * This function is used to find the custom stats of current user.
     * @param mixed $customer_id
    */
	public function get_customer_stats($customerId){
        $date = Carbon::today();
        $response = $this->makeRequest("{$this->uri}/".$customerId."/stats?date=".$date, "GET");
		return $this->formatResponse($response);
    }

    /**
     * Created By: Aman
     * Created on: 02-aug-2023
     * Last Updated By: Aman
     * Last Updated on: 02-aug-2023
     * This function is used to fetch all customer ids.
     */
    public function get_customer_ids(){
        $response = $this->makeRequest("{$this->uri}/customer-ids", "GET");
        return $this->formatResponse($response);
    }
	
	/**
     * Created By: kushal
	 * Last updated: 23-apr-2023
	 * Last Updated By: kushal
     * 
     * this function is used to call vocuher from the DS database 
     * @params rowdata in array
     * return curl result in array
     */
    public function getVoucher($user_id)
    { 
        $url ="https://nvisionu.clientextension.directscale.com/api/CustomApi/GetGiftCardData";
        $createData = array( "AssociateId" => $user_id);
        $data = json_encode($createData);
        return $this->curlfunction($url, $data); 
    }

    /**
     * Created By: kushal
	 * Last updated: 26-apr-2023
	 * Last Updated By: kushal
     * 
     * this function is used to assign the gift voucher
     * $user_id is the user ID to which we are assigning the card
     * $cardnumber is the card number which is going to be assigned
     * $cardpin is the pin of the card
     * return curl result in array
     */
    public function AssignVoucher($user_id, $cardnumber, $cardpin)
    {
        $url ="https://nvisionu.clientextension.directscale.com/api/CustomApi/AssignGiftCard";
        $createData = array( "AssociateId" => $user_id,
                             "cardNumber"=> "$cardnumber",
                             "cardPin"=>$cardpin );
        $data = json_encode($createData);
      $response =   $this->curlfunction($url, $data); 
      return $response; 
    }
      /**
     * Created By: kushal
	 * Last updated: 26-apr-2023
	 * Last Updated By: kushal
     * 
     * this function hits the api to get token from tradingmiddleware
     */
    public function GetToken($user_id)
    {
        $url ="https://trading-middleware.nvu-dev.com/Customer";
        $createData = array( "CustomerId" => $user_id ); 
        $data =json_encode( $createData );
        $response =   $this->curlfunction($url, $data);  
      return $response; 
    }
	
     /**
     * Created By: kushal
	 * Last updated: 26-apr-2023
	 * Last Updated By: kushal
     * 
     * common curl function 
     */
    public function curlfunction($url, $data)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization:Bearer b3BzQGhjcC50ZWFtOkhDUDRVUlNvdWwjSENQNFVSU291bCM='
        ),
        ));

        $response = curl_exec($curl);
        
        curl_close($curl);
       
        return  $Data = json_decode( $response, true ); 
    }

       /**
     * Created By: kushal
	 * Last updated: 26-apr-2023
	 * Last Updated By: kushal
     * 
     * common curl function 
     */
    public function curlpostfunction($url, $data)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);
        
        curl_close($curl); 
        return $response   ; 
    }

        /**
     * Created By: kushal
	 * Last updated: 26-apr-2023
	 * Last Updated By: kushal
     * 
     * common curl function 
     */
    public function curlGetfunction($url, $data)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);
        
        curl_close($curl);
        $Data = json_decode( $response, true ); 
        return  $Data ;
    }
    
    /**
     * Delete customer autoship order basis on the customer cart data and user
     * details like shipping details as well as payment details if all
     * required param is correct return user with their Order ID and other order details.
     * @param  $customer_id|integer
     * @param  $autoshipId |string
     * @return $user details array| object
    */
    public function delete_customer_autoship_order($customer_id, $autoshipId){
        $response = $this->makeRequest("{$this->uri}/autoship/".$autoshipId, "DELETE");
        return $this->formatResponse($response);
    }   


}
