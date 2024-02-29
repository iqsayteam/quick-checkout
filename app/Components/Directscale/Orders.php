<?php
/**
 * @Author: IQSAY
 * @Date: 2022-07-20
 * @Last Modified by: Karan
 * @Last Modified time: 2022-07-20 11:50
 */
namespace App\Components\Directscale;

use App\Components\Api\Directscale;

class Orders extends Directscale{

    protected $uri = "orders";

    /**
     * @param $keyword | string | integer | BackOfficeId | Email Address
     * @return array
    */
    public function get_shipping_method(){
        $response = $this->makeRequest("{$this->uri}/shipping-methods", "GET");
        return $this->formatResponse($response);
    }

    /**
     * store all available currencies from directscale API
     * @param request |
     * @return array | of all available currencies
    */
    public function get_currencies(){
        $response = $this->makeRequest("{$this->uri}/currencies", "GET");
        return $this->formatResponse($response);
    }
    
    /**
    * This function returns the specific shipping method(if exists) in users directscale account.
     * The data is retreiving in this through the API call to the DirectScale
     * @param  $ship_method_id | ID of Shipping Method we want to fetch the details of, from directscale
     * @return array
     */
    public function get_shipping_method_with_id( $ship_method_id ){
        $response = $this->makeRequest("{$this->uri}/shipping-methods/".$ship_method_id, "GET");
        return $this->formatResponse($response);
    }

    /**
     * This function returns the existing order details from Directscale
     * The data is retreiving in this through the API call to the DirectScale
     * @param  $order_id | ID of order we want to fech the details of, from directscale
     * @return array
     */
    public function get_customer_order_details($order_id)
    {
        $response = $this->makeRequest("{$this->uri}/".$order_id, "GET");
        return $this->formatResponse($response);   
    }
	
	public function cancel_subscription($subscriptionId){
        $response = $this->makeRequest("{$this->uri}/autoship/".$subscriptionId, "DELETE");
        return $this->formatResponse($response);
    }

	public function get_customer_autoship($autoshipId)
    {
        $response = $this->makeRequest("{$this->uri}/autoship/".$autoshipId, "GET");
        return $this->formatResponse($response);   
    }
    
	public function update_subscription($subscriptionId, $data){
        $response = $this->makeRequest("{$this->uri}/autoships/".$subscriptionId, "PUT", $data);
        return $this->formatResponse($response);
    }

    public function insert_payments($payments)
    {
        $response = $this->makeRequest("{$this->uri}/insert-payment", "POST",$payments);
        return $this->formatResponse($response); 
    }

    public function cancel_order($orderId)
    {
        $response = $this->makeRequest("{$this->uri}/".$orderId, "DELETE");
        return $this->formatResponse($response); 
    }
}
