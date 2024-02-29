<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Components\Directscale\Address;

use App\Models\Country;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\CustomVat;

use Session;
use DB;
use App\Components\Directscale\Customers as CustomerComponent;

class ProductController extends Controller
{ 
    protected $addressApi;
    protected $StoreId;
    protected $retail_products_cat_ids = [ 4, 5, 9, 23, 21, 22, 24, 25 ];
    protected $customer_API;
    //config constants
    protected $ORDER_TYPE;
    protected $SUBS_ORDER_TYPE;
    protected $STORE_ID;

    function __construct(){
        $this->addressApi = new Address();
        $this->StoreId = 5;
        $this->customer_API = new CustomerComponent();
        $this->ORDER_TYPE = config( 'global-constants.PRODUCT_FETCH.ORDER_TYPE' );
        $this->SUBS_ORDER_TYPE = config( 'global-constants.PRODUCT_FETCH.SUBS_ORDER_TYPE' );
        $this->STORE_ID = config( 'global-constants.PRODUCT_FETCH.STORE_ID' );
    }
    
	/**
     * This function mainly used to the collection page in frontend
    * This function fetches all the retail products from DB(from checkout app) and return them
    * @return [Array] | returns the array of all the available retial products
    */
    public function get_all_retail_products(){

        $prd_fetch_info = get_product_fetch_variables();
        // dd($prd_fetch_info);

        $state_code = $prd_fetch_info[ 'state_code' ];
        $country_code = $prd_fetch_info[ 'country_code' ];
        $language_code = $prd_fetch_info[ 'language_code' ];

        $region_id = $this->addressApi->get_region_by_id( $country_code, $state_code );
        $price_currency = Country::where( [ "country_code" => $country_code ] )->pluck( 'currency_code' )->first() ;

        if( $region_id) $region_id = $region_id;
        else $region_id = 1;

        $country_info = Country::where([ "country_code"=>get_current_country_code()])->first() ;
        $CurrencyCode = get_currency();

        $order_type = $this->ORDER_TYPE;
        $orderby='ASC';


        if(isset( $country_info) && !empty( $country_info) && $country_info->add_tax == "1")
        {
            $product = ProductCategory::select( 'products.*','product_languages.*','product_categories.category_id','product_categories.name','product_categories.image_url','product_categories.parent_id','product_categories.has_children','product_categories.sub_categories','product_prices.*','product_stores.*','product_regions.*','product_order_types.*',DB::raw( $country_info->tax_rate.' * `product_prices`.`price` as tax_rate' ) )
            ->join( 'products','products.category_id', '=', 'product_categories.category_id' )
            ->join( 'product_languages','product_languages.product_id', '=', 'products.product_id' )
            ->join( 'product_prices','product_prices.product_id', '=', 'products.product_id' )
            ->join( 'product_stores','product_stores.price_id', '=', 'product_prices.price_id' )
            ->join( 'product_regions','product_regions.price_id', '=', 'product_prices.price_id' )
            ->join( 'product_order_types','product_order_types.price_id', '=', 'product_prices.price_id' )
            ->where( 'product_languages.language_code', $language_code)
			->where('products.disabled', 0)
            ->where( 'product_stores.store_id', $this->STORE_ID)
            ->where( 'product_prices.start_date', '<=',  date( 'Y-m-d' ) ) 
            ->where( 'product_prices.end_date', '>=',  date( 'Y-m-d' ) ) 
         //   ->where( 'product_categories.category_id', $category_id)
            ->where( 'product_order_types.order_type', $order_type)
            ->where( 'product_regions.region_id', $region_id)
            ->where( 'product_prices.price_currency', $CurrencyCode)
            ->whereNotIn( 'products.product_id', DB::connection( 'mysql2' )->table( 'product_options_maps' )->pluck( 'item_id' ) )
            ->orderBy( 'products.product_id',$orderby)
            ->get()->toArray();     

        }else{ 
         
            $product = ProductCategory::select( 'products.*','product_languages.*','product_categories.category_id','product_categories.name','product_categories.image_url','product_categories.parent_id','product_categories.has_children','product_categories.sub_categories','product_prices.*','product_stores.*','product_regions.*','product_order_types.*',DB::raw( $country_info->tax_rate.' as tax_rate' ) )
            ->join( 'products','products.category_id', '=', 'product_categories.category_id' )
            ->join( 'product_languages','product_languages.product_id', '=', 'products.product_id' )
            ->join( 'product_prices','product_prices.product_id', '=', 'products.product_id' )
            ->join( 'product_stores','product_stores.price_id', '=', 'product_prices.price_id' )
            ->join( 'product_regions','product_regions.price_id', '=', 'product_prices.price_id' )
            ->join( 'product_order_types','product_order_types.price_id', '=', 'product_prices.price_id' )
            ->where( 'product_languages.language_code', $language_code)
			->where('products.disabled', 0)
            ->where( 'product_stores.store_id', $this->STORE_ID)
            ->where( 'product_prices.start_date', '<=', date( 'Y-m-d' ) )
            ->where( 'product_prices.end_date', '>=', date( 'Y-m-d' ) ) 
            //->whereIn( 'product_categories.category_id', $this->retail_products_cat_ids)
            ->where( 'product_categories.list_products', 1)
            ->where( 'product_order_types.order_type', $order_type)
            ->where( 'product_regions.region_id', $region_id)
            ->where( 'product_prices.price_currency', $CurrencyCode)
            ->whereNotIn( 'products.product_id', DB::connection( 'mysql2' )->table( 'product_options_maps' )->pluck( 'item_id' ) )
            ->orderBy( 'products.product_id',$orderby)
            ->get()->toArray();          
        }
    
        if(!empty( $product) ){
            return $product;
        }else{
            return array();
        }
    }


    /**
     * Created By: GouravM
     * Created on: 12-dec-2022
     * Last Updated By: GouravM
     * Last Updated on: 12-dec-2022
     * 
     * This function is used to get the category details of the retails products
     * @return [array]
     */
    public function get_all_retail_products_categories($catIds=[]){
        $prd_fetch_info = get_product_fetch_variables();
        $state_code = $prd_fetch_info[ 'state_code' ];
        $country_code = $prd_fetch_info[ 'country_code' ];
        $region_id = $this->addressApi->get_region_by_id( $country_code, $state_code );
        if( $region_id) $region_id = $region_id;
        else $region_id = 1;

        $product = ProductCategory::select('products.category_id')
            ->join( 'products','products.category_id', '=', 'product_categories.category_id' )
            ->join( 'product_prices','product_prices.product_id', '=', 'products.product_id' )
            ->join( 'product_stores','product_stores.price_id', '=', 'product_prices.price_id' )
            ->join( 'product_regions','product_regions.price_id', '=', 'product_prices.price_id' )
            ->where( 'product_stores.store_id', 3)
            ->where( 'products.disabled', 0)
            ->where( 'product_prices.start_date', '<=', date( 'Y-m-d' ) )
            ->where( 'product_prices.end_date', '>=', date( 'Y-m-d' ) )
            ->where( 'product_regions.region_id', $region_id)
            //->whereIn( 'product_categories.category_id',$this->retail_products_cat_ids)
            ->where( 'product_categories.list_products',1);
        if(count($catIds)>0) {
            $product = $product->whereIn('product_categories.category_id', $catIds);
        }
        $product = $product->distinct()
            ->get()->toArray();
        $product_category_id = array_column($product, 'category_id');

        $categories = ProductCategory::whereIn( 'category_id', $product_category_id)->get()->toArray();
        //$categories = ProductCategory::where( 'list_products', 1)->orderBy('name', 'asc')->get()->toArray();
        //$categories = ProductCategory::whereIn( 'category_id', $this->retail_products_cat_ids )->get()->toArray();    
        return $categories;
    }

	/**
     * Retrieves the product by product id from the database, if it is present,
     * and returns it to the caller.
     * If it is not present, a default set the empty array, is then returned to the caller.
     * @param $product_id the id of the product
     * @param $language_code the two lowercase character long language code
     * @param $region_id the integer value
     * @return array product information returned in the form of a array.
    */

    public function getProductById($status=null, $product_id, $language_code = 'en', $region_id = 1, $order_type = 1 , $store_id = 5, $price_currency=null, $country_code="us", $group_id = 4 )
    {

        if( $product_id && $language_code && $region_id && $order_type ){
            if( $price_currency == null ){
                $price_currency = 'USD';
            }

            $productTaxClassId = Product::where(["product_id"=>$product_id])->pluck('TaxClassId')->first();
            $CustomVat = CustomVat::where(["countryCode"=>$country_code,"taxClassID"=>$productTaxClassId])->first();
            $country = Country::where("country_code",$country_code)->pluck('add_tax')->first();

            if(isset( $CustomVat) && !empty( $CustomVat)  && !empty( $country))
            {
                $price_with_tax = DB::raw( $CustomVat->taxRate.' * `product_prices`.`price` + product_prices.price  as price_included_tax' ) ;
                $tax_rate = $CustomVat->taxRate;
            }
            else {
                $price_with_tax = 'product_prices.price  as price_included_tax';
                $tax_rate = 0;
            }

            $product = Product::select('products.*','product_languages.*','product_prices.*','product_stores.*','product_regions.*',$price_with_tax,'product_order_types.*',DB::connection( 'mysql2' )->raw($tax_rate.' * `product_prices`.`price` as tax_rate'))
            ->join('product_languages','product_languages.product_id', '=', 'products.product_id')
            ->join('product_prices','product_prices.product_id', '=', 'products.product_id')
            ->join('product_stores','product_stores.price_id', '=', 'product_prices.price_id')
            ->join('product_regions','product_regions.price_id', '=', 'product_prices.price_id')
            ->join('product_order_types','product_order_types.price_id', '=', 'product_prices.price_id')
            ->join('price_groups','price_groups.price_id', '=', 'product_prices.price_id')
            ->where('product_languages.language_code', $language_code)
			->where('products.disabled', 0)
            ->where('products.status', $status)
            ->where('product_stores.store_id', $store_id)
            ->where('product_prices.price_currency', $price_currency )
            ->where('product_prices.start_date', '<=', date('Y-m-d') ) 
            ->where('product_prices.end_date', '>=', date('Y-m-d') )
            ->where('product_order_types.order_type', $order_type)
            ->where('product_regions.region_id', $region_id);
            $product = $product->where( 'products.product_id', $product_id);

            $product = $product->where('price_groups.group_id', $group_id)
            ->first();
            if( !empty( $product) && $product != null ){
                $field3 = getCustomField3Data($product['product_id']);
                if(isset($field3['bfx_check']) && $field3['bfx_check']  == 1) {
                    $userID = get_current_user_id();
                    if($userID != null) {
                        $user_data = $this->customer_API->get_customer_custom_fields( $userID );
                        if(isset($user_data['Field6']) && $user_data['Field6'] != "") {
                            if(!str_ends_with($user_data['Field6'], 'BOG')){
                                return array();
                            }
                        }
                    } else {
                        return array();
                    }
                }
                return $product->toArray(); 
            }else{
                return array();
            }
        }else{
            return array();
        }
    }



    /**
     * Get product data by product IDs, language code, region ID, order type, and other criteria.
     *
     * @param string|null $status The status of the products to retrieve.
     * @param int|array $product_id Single product ID or an array of product IDs.
     * @param string $language_code The language code.
     * @param int $region_id The region ID.
     * @param int $order_type The order type.
     * @param int $store_id The store ID.
     * @param string|null $price_currency The price currency.
     * @param string $country_code The country code.
     * @param int $group_id The group ID.
     *
     * @return array An array containing product data based on the given criteria.
     */
    public function getProductByIds($status=null, $product_id, $language_code = 'en', $region_id = 1, $order_type = 1 , $store_id = 5, $price_currency=null, $country_code="us", $group_id = 4 ){   
        
        if( $product_id && $language_code && $region_id && $order_type ){
            if( $price_currency == null ){
                $price_currency = 'USD';
            }
           
            $products = Product::select('products.*','product_languages.*','product_prices.*','product_stores.*','product_regions.*')
            ->join('product_languages','product_languages.product_id', '=', 'products.product_id')
            ->join('product_prices','product_prices.product_id', '=', 'products.product_id')
            ->join('product_stores','product_stores.price_id', '=', 'product_prices.price_id')
            ->join('product_regions','product_regions.price_id', '=', 'product_prices.price_id')
            ->join('product_order_types','product_order_types.price_id', '=', 'product_prices.price_id')
            ->join('price_groups','price_groups.price_id', '=', 'product_prices.price_id')
            ->where('product_languages.language_code', $language_code)
			->where('products.disabled', 0)
            ->where('products.status', $status)
            ->where('product_stores.store_id', $store_id)
            ->where('product_prices.price_currency', $price_currency )
            ->where('product_prices.start_date', '<=', date('Y-m-d') ) 
            ->where('product_prices.end_date', '>=', date('Y-m-d') )
            ->where('product_order_types.order_type', $order_type)
            ->where('product_regions.region_id', $region_id)
            ->whereIn( 'products.product_id', $product_id)
            ->where('price_groups.group_id', $group_id)
            ->groupBy('products.product_sku')
            ->get()->toArray();

           

            if( !empty( $products) && $products != null ){
                
                foreach($products as $key => $product){
                    $vat_rates = CustomVat::where("countryCode",$country_code)->where("taxClassID",$product['TaxClassId'])->first();
                    $country = Country::where("country_code",$country_code)->pluck('add_tax')->first();

                    if(!empty($vat_rates) && !empty($country)){
                        $products[$key]['price_included_tax'] = ($vat_rates->taxRate * $product['price'])+$product['price'];
                        $products[$key]['tax_rate'] = $vat_rates->taxRate * $product['price'];
                    }else{
                        $products[$key]['tax_rate'] = 0;
                        $products[$key]['price_included_tax'] = $product['price'];
                    }
                    
                    $field3 = getCustomField3Data($product['product_id']);

                    if(isset($field3['bfx_check']) && $field3['bfx_check']  == 1) {
                        $userID = get_current_user_id();
                        if($userID != null) {
                            $user_data = $this->customer_API->get_customer_custom_fields( $userID );
                            if(isset($user_data['Field6']) && $user_data['Field6'] != "") {
                                if(!str_ends_with($user_data['Field6'], 'BOG')){
                                    unset($products[$key]);
                                }
                            }else{
                                unset($products[$key]);
                            }
                        } else {
                            unset($products[$key]);
                        }
                    }
                }
                return $products; 
            }else{
                return array();
            }
        }else{
            return array();
        }

    }

    /**
     * Get the Subscription Detail
     * @param $request | array
     * @var $product_id | Product ID
     * @return response | array
     * returns the json response
    */
    public function getSubscriptionDetailWithProductId($status, $product_cat_id, $product_id, $country_code, $state_code, $language_code)
    {   
        
        $price_group = get_logged_in_customer_type();
     	$product_sku = Product::where([ "product_id" => $product_id])->select( 'product_sku' )->first(); 

        if(!empty($product_sku['product_sku'])){
     	  $product_sku = $product_sku['product_sku'];
        }
     

        $region_id = $this->addressApi->get_region_by_id( $country_code, $state_code);

        if( $region_id) $region_id = $region_id;
        else $region_id = 1;

      
       	$subscriptionProduct = Product::where([ "product_sku" => $product_sku])->first();
         $price_currency = Country::where( [ "country_code"=>$country_code ] )->pluck( 'currency_code' )->first();

        if(in_array($product_cat_id, subscriptionProductByCategoriesId())){
            $data = $this->getProductById($status, $product_id, $language_code, $region_id, $this->SUBS_ORDER_TYPE, 5, $price_currency, $country_code, $price_group);
        }else{
            if(isset( $subscriptionProduct->product_id )){
                $data = $this->getProductById($status, $subscriptionProduct->product_id, $language_code, $region_id, $this->ORDER_TYPE, 5, $price_currency, $country_code, $price_group);
            }else{
                return array();
            }
        }
		
       
        if(isset( $data ) && !empty( $data)){
            return $data;
        }
        else{
            return array();                 
        }   
   
	}



    /**
     * Get subscription product IDs based on various criteria.
     *
     * @param string $status The status of the products to retrieve.
     * @param int $product_cat_id The product category ID to filter by.
     * @param array $product_ids Array of product IDs.
     * @param string $country_code The country code for the region.
     * @param string $state_code The state code for the region.
     * @param string $language_code The language code.
     *
     * @return array An array containing subscription product IDs based on the given criteria.
     */
    public function getSubscriptionProductIds($status, $product_cat_id, $product_ids, $country_code, $state_code, $language_code)
    {   
        
        $price_group = get_logged_in_customer_type();
        $product_skus = Product::whereIn("product_id", $product_ids)->pluck('product_sku')->toArray();
        $region_id = $this->addressApi->get_region_by_id($country_code, $state_code) ?? 1;
        $price_currency = Country::where("country_code", $country_code)->pluck('currency_code')->first();
    
        if (in_array($product_cat_id, subscriptionProductByCategoriesId())) {
            $data = $this->getProductByIds($status, $product_ids, $language_code, $region_id, $this->SUBS_ORDER_TYPE, 5, $price_currency, $country_code, $price_group);
        } else {
            $subscription_product_ids = Product::whereIn("product_sku", $product_skus)->pluck('product_id')->toArray();//dd($subscription_product_ids);
            $data = $this->getProductByIds($status, $subscription_product_ids, $language_code, $region_id, $this->ORDER_TYPE, 5, $price_currency, $country_code, $price_group);
        }
    
        return $data ?? []; 
   
	}


        /**
     * Get Products from database By using join based on category ID, Region ID, Language Code and Order Type
     * @param $language_code  | string
     * @param $region_id  | string
     * @param $category_id  | integer
     * @param $category_id  | integer
     * @param $store_id  | integer
     * @param $order_type  | integer | 1 = Standard | 2 = Autoship
     * @return $product | array
     * Returns the array of products
    */

    public function getProduct($language_code = 'en', $region_id = 1, $category_id = 3, $orderby = 'ASC' ,$order_type = 1)
    {
        $language_code = "en";
        $country_info = Country::where(["country_code"=>get_current_country_code()])->first() ;
        $CurrencyCode = get_currency();
         if(isset($country_info) && !empty($country_info) && $country_info->add_tax == "1")
           {
                $product = ProductCategory::select('products.*','product_languages.*','product_categories.category_id','product_categories.name','product_categories.image_url','product_categories.parent_id','product_categories.has_children','product_categories.sub_categories','product_prices.*','product_stores.*','product_regions.*','product_order_types.*',DB::connection( 'mysql2' )->raw($country_info->tax_rate.' * `product_prices`.`price` as tax_rate'))
                ->join('products','products.category_id', '=', 'product_categories.category_id')
                ->join('product_languages','product_languages.product_id', '=', 'products.product_id')
                ->join('product_prices','product_prices.product_id', '=', 'products.product_id')
                ->join('product_stores','product_stores.price_id', '=', 'product_prices.price_id')
                ->join('product_regions','product_regions.price_id', '=', 'product_prices.price_id')
                ->join('product_order_types','product_order_types.price_id', '=', 'product_prices.price_id')
                ->where('product_languages.language_code', $language_code)
                ->where('product_stores.store_id', $this->StoreId)
				->where('products.disabled', 0)
                ->where('product_prices.start_date', '<=',  date('Y-m-d') )
                ->where('product_prices.end_date', '>=',  date('Y-m-d') )
                ->where('product_categories.category_id', $category_id)
                ->where('product_order_types.order_type', $order_type)
                ->where('product_regions.region_id', $region_id)
                ->where('product_prices.price_currency', $CurrencyCode)
                ->whereNotIn('products.product_id', DB::connection( 'mysql2' )->table('product_options_maps')->pluck('item_id'))
                ->orderBy('products.product_id',$orderby)
                // ->groupBy('products.product_id')
                // s
                ->get()->toArray();


           }
           else
            {
                $product = ProductCategory::select('products.*','product_languages.*','product_categories.category_id','product_categories.name','product_categories.image_url','product_categories.parent_id','product_categories.has_children','product_categories.sub_categories','product_prices.*','product_stores.*','product_regions.*','product_order_types.*',DB::connection( 'mysql2' )->raw($country_info->tax_rate.' as tax_rate'))
                ->join('products','products.category_id', '=', 'product_categories.category_id')
                ->join('product_languages','product_languages.product_id', '=', 'products.product_id')
                ->join('product_prices','product_prices.product_id', '=', 'products.product_id')
                ->join('product_stores','product_stores.price_id', '=', 'product_prices.price_id')
                ->join('product_regions','product_regions.price_id', '=', 'product_prices.price_id')
                ->join('product_order_types','product_order_types.price_id', '=', 'product_prices.price_id')
                ->where('product_languages.language_code', $language_code)
				->where('products.disabled', 0)
                ->where('product_stores.store_id', $this->StoreId)
                ->where('product_prices.start_date', '<=', date('Y-m-d') )
                ->where('product_prices.end_date', '>=', date('Y-m-d') )
                ->where('product_categories.category_id', $category_id)
                ->where('product_order_types.order_type', $order_type)
                ->where('product_regions.region_id', $region_id)
                ->where('product_prices.price_currency', $CurrencyCode)
                ->whereNotIn('products.product_id', DB::connection( 'mysql2' )->table('product_options_maps')->pluck('item_id'))
                ->orderBy('products.product_id',$orderby)
                // ->groupBy('products.product_id')
               // ->toSql();
                ->get()->toArray();

            }

      if(!empty($product)){
            return $product;
        }else{
            return array();
        }
    }

    /**
     * Created By: Gourav M
     * Created On: 8-dec-2022
     * Last Updated By: GouravM
     * Last updated On: 8-dec-2022
     * 
     * This function is used to fetch all the images of a product from DB
     * @param mixed $product_id [integer] | id of product we need to fetch the images of
     * 
     * @return [type]
     */
    public function get_product_images( $product_id ){
        
        $product_images_array = ProductImage::where( 'product_id', $product_id )->get()->toArray();
        return $product_images_array;

    }

    /**
     * Created By: Karan // this function is copied from enrollment APP
     * Created on : N/A 
     * Last Updated By: GouravM
     * Last Updated On: 9-dec-2022
     * 
     * This function fetches the order total of logged in user
     * 
     * @param mixed $Item [array] | array of products in user's cart
     * @param null $Couponcode [string] | CouponCode applied to the cart
     * 
     * @return [array]
     */
    public function calculate_order( $Item, $Couponcode = null ){
        $userID = get_current_user_id();
        if( $userID ){
            $user_cart = Cart::where( 'user_id', $userID )->first();
            if( !empty( $user_cart ) ){
                $user_data = $this->customer_api->get_customer_by_id( $userID );
                if( !isset( $user_data[ 'isError' ] ) && !empty( $Item ) )
                {
                    $shipping_method = $this->orders_api->get_shipping_method();
                    $ship_method_id = $shipping_method[ 0 ][ 'ID' ];
                    $WarehouseID = $shipping_method[ 0 ][ 'WarehouseID' ];
                    $ShipType = $shipping_method[ 0 ][ 'ShipType' ];
                    $StoreId = $shipping_method[ 0 ][ 'StoreIds' ][ 3 ];
                    $country_code = get_current_country_code();
                    $currency = get_currency();
                    //$CouponCodes = (Session::has('coupon_codes')) ? Session::get('coupon_codes') : ""; 
                    if( $Couponcode==null ){
                        $CouponCodes = ( Session::has( 'coupon_codes' ) ) ? Session::get( 'coupon_codes' ) : ""; 
                    }
                    elseif( $Couponcode != null )
                    {
                        $CouponCodes = $Couponcode;
                    }
                    else{
                        $CouponCodes = "";
                    }
                    $userdata[ 'json' ] = array(
                        "WarehouseId" => $WarehouseID,
                        "ShipMethodId" => $ship_method_id,
                        "StoreId" => 4,
                        "CurrencyCode" => $currency,
                        "ShippingAddress" => $user_data[ 'DefaultShippingAddress' ],
                        "PriceGroup"=> get_user_price_group(),
                        "OrderType"=> 1,
                        "Items"=> $Item,
                        "CouponCodes"=> [
                            $CouponCodes
                        ],
                        "CountryCode"=> $country_code
                    );
                      
                    $customer_order = $this->customer_api->calculate_customer_order_total( $userID, $userdata );
                   
                    if( isset( $customer_order[ 'CouponResults' ][ 0 ] ) && !empty( $customer_order[ 'CouponResults' ][ 0 ][ 'Code' ] )  && $customer_order[ 'CouponResults' ][ 0 ][ 'IsValid' ] == false )
                    {
                        return  [ "isError" => true , "message" => $customer_order[ 'CouponResults' ][ 0 ][ 'Message' ] ];
                    }
                    elseif( isset( $customer_order[ 'isError' ] ) && $customer_order[ 'isError' ] == true )
                    {
                        return  [ "isError" => true , "message" => $customer_order[ 'headers' ][ 'X-DirectScale-Message' ] ];                        
                    }
                    else
                    {
                        return $customer_order;
                    }

                }else{
                    return  [ "message" => "Either cart is empty or User are not Login!!" ];
                }

            }else{
                return [ "message" => "Your cart is empty!!" ];
            }
        }else{
            return [ "message" => "Please login first!!" ];
        }
    }

    /**
     * Created By: Aman
     * Created On: 
     * Last Updated By: Aman
     * Last Updated On: 
     * 
     * @param Request $request
     * 
     * @return [type]
     */
    public function fetchProductBySku(Request $request ) {
		echo $langSlug = strtolower($request->route('lang_slug'));
		echo $productSlug = strtolower($request->route('slug'));
		echo $distributor = strtolower($request->route('distributor'));
	}

    public function getProductAvailableInAnyCountry($product_id, $language_code = 'en', $order_type = 1, $store_id = 5,$group_id = 4 )
    {

        if( $product_id && $language_code && $order_type ){
            $productTaxClassId = Product::where(["product_id"=>$product_id])->pluck('TaxClassId')->first();
            /*$CustomVat = CustomVat::where(["countryCode"=>$country_code,"taxClassID"=>$productTaxClassId])->first();
            $country = Country::where("country_code",$country_code)->pluck('add_tax')->first();

            if(isset( $CustomVat) && !empty( $CustomVat)  && !empty( $country))
            {
                $price_with_tax = DB::raw( $CustomVat->taxRate.' * `product_prices`.`price` + product_prices.price  as price_included_tax' ) ;
                $tax_rate = $CustomVat->taxRate;
            }
            else {
                $price_with_tax = 'product_prices.price  as price_included_tax';
                $tax_rate = 0;
            }*/

            $product = Product::select('products.*','product_languages.*','product_prices.*','product_stores.*','product_regions.*',
                'product_order_types.*', 'product_prices.price  as price_included_tax')//->raw($tax_rate.' * `product_prices`.`price` as tax_rate'))
                ->join('product_languages','product_languages.product_id', '=', 'products.product_id')
                ->join('product_prices','product_prices.product_id', '=', 'products.product_id')
                ->join('product_stores','product_stores.price_id', '=', 'product_prices.price_id')
                ->join('product_regions','product_regions.price_id', '=', 'product_prices.price_id')
                ->join('product_order_types','product_order_types.price_id', '=', 'product_prices.price_id')
                ->join('price_groups','price_groups.price_id', '=', 'product_prices.price_id')
                ->where('product_languages.language_code', $language_code)
                ->where('products.disabled', 0)
                ->where('product_stores.store_id', $store_id)
                ->where('product_prices.start_date', '<=', date('Y-m-d') )
                ->where('product_prices.end_date', '>=', date('Y-m-d') )
                ->where('product_order_types.order_type', $order_type);
            $product = $product->where( 'products.product_id', $product_id);

            $product = $product->where('price_groups.group_id', $group_id)
                ->first();
            if( !empty( $product) && $product != null ){
                $field3 = getCustomField3Data($product['product_id']);
                if(isset($field3['bfx_check']) && $field3['bfx_check']  == 1) {
                    $userID = get_current_user_id();
                    if($userID != null) {
                        $user_data = $this->customer_API->get_customer_custom_fields( $userID );
                        if(isset($user_data['Field6']) && $user_data['Field6'] != "") {
                            if(!str_ends_with($user_data['Field6'], 'BOG')){
                                return array();
                            }
                        }
                    } else {
                        return array();
                    }
                }
                return $product->toArray();
            }else{
                return array();
            }
        }else{
            return array();
        }
    }



}
