<?php
/**
 * @Author: IQSAY
 * @Date: 2022-07-27
 * @Last Modified by: Karan
 * @Last Modified time: 2022-07-27 16:15
 */
namespace App\Components\Directscale;

use App\Components\Api\Directscale;

class Products extends Directscale{

    protected $uri = "products";

    /**
     * @param $null | null
     * @return array
    */
    public function get_stores(){
        $response = $this->makeRequest("{$this->uri}/stores", "GET");
        return $this->formatResponse($response);
    }

    /**
     * @param $category_id | integer
     * @return array  | category array
    */
    public function get_product_category($category_id){
        $response = $this->makeRequest("{$this->uri}/store/".$category_id."/categories", "GET");
        return $this->formatResponse($response);
    }

    /**
     * @param $null | null
     * @return array  | all available product array
    */
    public function get_all_product(){
        $response = $this->makeRequest("{$this->uri}/items", "GET");
        return $this->formatResponse($response);
    }

    /**
     * @param $sku | string
     * @return array  | product array
    */
    public function get_product_by_sku($sku){
        $response = $this->makeRequest("{$this->uri}/item/sku/".$sku."?CurrencyCode=usd&LanguageCode=en", "GET");
        return $this->formatResponse($response);
    }

      /**
     * @param $product_id | integer
     * @return array  | product array
    */
    public function get_product_by_id($product_id){
        $response = $this->makeRequest("{$this->uri}/item/".$product_id, "GET");
        return $this->formatResponse($response);
    }

    /**
     * Created By: Kushal
     * Created On: 9-may-2023
     * Last Updated By: Kushal
     * Last Updated On: 9-may-2023
     * 
     * This function is used to get product 
     * based upon category id
     * @param mixed $category_id | integer
     */
    public function get_product_by_category($currency_code, $language_code, $category_code){
        $response = $this->makeRequest("{$this->uri}/categories/".$category_code."/items?CurrencyCode=".$currency_code."&LanguageCode=".$language_code."&RegionID=1&PriceGroup=&StoreID=", "GET");
        return $this->formatResponse($response);
    }
    
}
