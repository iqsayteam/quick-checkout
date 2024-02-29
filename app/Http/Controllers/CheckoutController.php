<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Components\Directscale\SSO as SSOComponent;
use App\Components\Directscale\Customers as CustomerComponent;
use App\Components\Directscale\Products; 
use App\Models\Quicklinks;
use Illuminate\Support\Facades\Http; 
use App\Models\Country; 
use App\Models\Product; 
use App\Models\CustomField; 
use Illuminate\Support\Facades\Session;
use App\Components\Directscale\Address;
use App\Components\Directscale\CartController;
use Illuminate\Http\Response;

   
class CheckoutController extends Controller
{
    protected $SSO_API;
    protected $Products;
    
     protected $customer_API; 
     protected $addressApi; 
     protected $retail_products_cat_ids = array( 4, 5, 24, 25 ); // 4 is 'Digital Packages' and 5 is 'Product Packages' category
   
     protected $CartController;
     protected $ProductController;
     protected $SUBS_ORDER_TYPE;
     const ADDED_BY_ADMIN = 1;
     // const DIGITAL_PRODUCT_CAT_ID = 4;
     // const PHYSICAL_PRODUCT_CAT_ID = 5;
     // const SPRAY_PRODUCT_CAT_ID = 9;
     // const VBKIT_PRODUCT_CAT_ID = 3;
     // const SILVER_ADDON_CAT_ID = 6;
     // const SILVER_ADDON_PRD_ID = 33;
  
     //config constants
     protected $ORDER_TYPE;
     protected $STORE_ID;
     protected $DIGITAL_PRODUCT_CAT_ID;
     protected $PHYSICAL_PRODUCT_CAT_ID;
     protected $SPRAY_PRODUCT_CAT_ID;
     protected $VBKIT_PRODUCT_CAT_ID;
     protected $SILVER_ADDON_CAT_ID;
     protected $SILVER_ADDON_PRD_ID;
     protected $INT_PACK_PRD_ID;
     protected $NEW_PACK_PRD_ID;
     protected $DIGITAL_SILVER;
     protected $HEALTH_WEALTH_PACK_PRD_ID;
     protected $HEALTH_PACK_PRD_ID;
     protected $DIGITAL_SILVER_PRODUCT_ID;
     protected $PROMO_PACK_CAT_ID;
     protected $EVENT_PACKS_CAT_ID;
     protected $COURSES_CAT_ID;
     protected $HEALTH_CAT_ID;
     protected $WEALTH_CAT_ID;
     protected $SIMPLE_PRODUCT;
     protected $VARIABLE_PRODUCT;
     protected $GROUP_PRODUCT;
     protected $ERROR_PATH;
      protected $CORE;
      protected $ESSENTIAL;
      protected $PREMIER;
  
     function __construct(){
        $this->addressApi = new Address();
        $this->customer_API = new CustomerComponent();
   
        $this->ProductController = new ProductController();
  
        $this->DIGITAL_PRODUCT_CAT_ID = config( 'global-constants.PRODUCT_CATS_MAIN.DIGITAL' );
        $this->PHYSICAL_PRODUCT_CAT_ID = config( 'global-constants.PRODUCT_CATS_MAIN.PRODUCT' );
        $this->SPRAY_PRODUCT_CAT_ID = config( 'global-constants.PRODUCT_CATS_MAIN.PRODUCT_SPRAY' );
        $this->VBKIT_PRODUCT_CAT_ID = config( 'global-constants.PRODUCT_CATS_MAIN.VBKIT' );
        $this->SILVER_ADDON_CAT_ID = config( 'global-constants.PRODUCT_CATS_MAIN.ADDON' );
        $this->SILVER_ADDON_PRD_ID = config( 'global-constants.PRODUCT_IDS.ADDON_PRD_ID' );
        $this->DIGITAL_SILVER_PRODUCT_ID = config( 'global-constants.PRODUCT_IDS.DIGITAL_SILVER' );
        $this->INT_PACK_PRD_ID = config( 'global-constants.PRODUCT_IDS.NVULPINT' );
        $this->NEW_PACK_PRD_ID = config( 'global-constants.PRODUCT_IDS.NVU4NS' );
        $this->HEALTH_WEALTH_PACK_PRD_ID = config( 'global-constants.PRODUCT_IDS.NVUHW' );
        $this->HEALTH_PACK_PRD_ID = config( 'global-constants.PRODUCT_IDS.NVUH' );
        $this->HEALTH_CAT_ID = config( 'global-constants.PRODUCT_CATS_MAIN.HEALTH' );
        $this->WEALTH_CAT_ID = config( 'global-constants.PRODUCT_CATS_MAIN.WEALTH' );
        $this->PROMO_PACK_CAT_ID = config( 'global-constants.PRODUCT_CATS_MAIN.PROMO_PACK' );
        $this->EVENT_PACKS_CAT_ID = config( 'global-constants.PRODUCT_CATS_MAIN.EVENT_PACKS' );
        $this->COURSES_CAT_ID = config( 'global-constants.PRODUCT_CATS_MAIN.COURSES' );
          
        $this->ORDER_TYPE = config( 'global-constants.PRODUCT_FETCH.ORDER_TYPE' );
        $this->SUBS_ORDER_TYPE = config( 'global-constants.PRODUCT_FETCH.SUBS_ORDER_TYPE' );
          
        //$this->DIGITAL_SILVER_PRODUCT_ID = config( 'global-constants.PRODUCT_CATS_MAIN.DIGITAL' );
        $this->ORDER_TYPE = config( 'global-constants.PRODUCT_FETCH.ORDER_TYPE' );
        $this->STORE_ID = config( 'global-constants.PRODUCT_FETCH.STORE_ID' );
        
        $this->SIMPLE_PRODUCT = config( 'global-constants.PRODUCT_TYPES.SIMPLE_PRODUCT' );
        $this->VARIABLE_PRODUCT = config( 'global-constants.PRODUCT_TYPES.VARIABLE_PRODUCT' );
        $this->GROUP_PRODUCT = config( 'global-constants.PRODUCT_TYPES.GROUP_PRODUCT' );
        $this->ERROR_PATH = config( 'global-constants.DEFAULT.ERROR_PATH' );
         $this->CORE = config( 'global-constants.PACK_TYPE.CORE' );
         $this->ESSENTIAL = config( 'global-constants.PACK_TYPE.ESSENTIAL' );
         $this->PREMIER = config( 'global-constants.PACK_TYPE.PREMIER' );
         $this->SSO_API = new SSOComponent();
         $this->customer_API = new CustomerComponent(); 
         $this->Products = new Products(); 
     }
  
     /**
* Created By: Raju
* Created on: 28-02-2024
* Last Updated By: Raju
* Last Updated on: 28-02-2024
*
* This function Create the unique link for products

* @return [type]
*/
 
    public function createUniqueLink(Request $request)
    {
        $userlist = array(['user_id'=>142559,'item_id'=>"1555,1554"], ['user_id'=>1015190,'item_id'=>"1555,1554"]); //api data to be configured here with which we will recieve customer_id and item_id(s)
        foreach($userlist as $userid)
        { 
            $user_respose = $this->customer_API->get_customer_by_id($userid['user_id']);  
            if(!empty( $user_respose))
            {
                $item=$userid['item_id'];
                $userid=$userid['user_id'];
                $stringToEncode = $item.'&&'.$userid; 
                $encryptedData = base64_encode($stringToEncode); 
                Quicklinks::updateOrCreate(
                    ['customer_id' => $userid],
                    [ 
                        'email' => $user_respose['EmailAddress'], 
                        'items' => $item,
                        'quicklink' => $encryptedData,
                        'status' =>1, 
                        ]
                    );
                 
            }
             
        } 
        return ['status'=>'success', 'message'=>'Unique links created'];
    }
  
/**
* Created By: Raju
* Created on: 28-02-2024
* Last Updated By: Raju
* Last Updated on: 28-02-2024
*
* This function is used to validate if encoded string is valid or not

* @return [type]
*/
public function isValidBase64($encodedString) {
    $decoded = base64_decode($encodedString, true);
    // Check if $decoded is not false and if the decoded string is the same as the original
    return ($decoded !== false) && (base64_encode($decoded) === $encodedString);
}
 /**
* Created By: Raju
* Created on: 28-02-2024
* Last Updated By: Raju
* Last Updated on: 28-02-2024
*
* This function show the login page with the products 

* @return [type]
*/
    public function login_page(Request $request)
    { 
        $quicklinks = Quicklinks::where('quicklink',$request->token)->first();
        if ($this->isValidBase64($request->token) && $quicklinks !== null) { 
            $data =explode("&&", base64_decode( $request->token)); 
            $item=$data[0];
            $user=$data[1];  
            $explodeitems = explode(',',$item);
            $product=[]; 
            $user_respose = $this->customer_API->get_customer_by_id( $user);
            
            Session::put('userfromtoken',$user); 
            Session::put('price_group',get_price_group_id_for_user_type($user_respose['CustomerType']));
            $price_group = get_price_group_id_for_user_type($user_respose['CustomerType']);
            $response = new Response();
            $response->cookie('country_code', $user_respose['PrimaryAddress']['CountryCode'] ); // 'country_code' is the name of the cookie
            $response->cookie('language_code', $user_respose['LanguageCode'] ); // 'language_code' is the name of the cookie
            $user_respose['currency_code'] = Http::get(env('nvisionu')."/api/get_currency",['countryCode'=>$user_respose['PrimaryAddress']['CountryCode']] ); 
           
            $user_respose['currency_symbol'] = $user_respose['currency_code']['currency_symbol'];
            $user_respose['currency_code'] = $user_respose['currency_code']['currency_code'];
            
            // $user_respose['currency_code'] = get_currency();  
                foreach($explodeitems as $items)
                {  
                    // $response = $this->getDirectProductbyId($items,$user_respose['PrimaryAddress']['CountryCode'],$user_respose['LanguageCode'],$price_group); 
                    $datatosend = ['items'=>$items,'CountryCode'=>$user_respose['PrimaryAddress']['CountryCode'],'LanguageCode'=>$user_respose['LanguageCode'],'price_group'=>$price_group];
                $response = Http::post(env('nvisionu')."/api/getdirectproductbyid",$datatosend);   
              
                $response =json_decode($response,true);
              
                    if($response['productFoundForSelectedLocation'] && $response['regular_product_details']['status'] && $response['regular_product_details']['stock'] && $response['regular_product_details']['stock']  && $response['regular_product_details']['disabled'] == 0)
                    { 
                        $products[] =$response ; 
                    } 
                // $products[] = $this->Products->get_product_by_id($items);  
                }   
                
           $product = $products;  
           if(count($product) == 0)
           {
            return redirect(env('collectionPage'));
           }
         
            return view('loginview',compact('product','user_respose'));
        } else {
            return redirect(env('collectionPage'));
        }
    }

    
/**
     * Created By:Raju
     * Created on: 28-02-2024
     * Last Updated By:Raju
     * Last Updated on: 28-02-2024
     * 
     * This function is used to return the product data to display the products at login page
     * @return [type]
    */
  	
   public function getDirectProductbyId($productid,$country_code,$language_code,$price_group)
   {
     if(isset($productid))
     {
        $productid = $productid;
        $country_code = $country_code;
        $language_code = $language_code;

     }  
     $status = config( 'global-constants.PRODUCT_STATUS.PUBLISH');
    Session::put('product_status', $status); 
    $price_group = $price_group; 
    
    $data = [];
    $data[ 'show_purchased_message' ] = 0;
    $data[ 'error_message' ] = "";
    //START checking if slug exists in DB and setting variables according to that
    $slugdata = Product::where( 'product_id',$productid )->where('disabled', 0)->first();
    if($slugdata != null) {
        $product_category = $slugdata[ 'category_id' ];
        $product_id = $slugdata['product_id'];
        $slug_exists = $slugdata['slug'];
    } else {
        $data =[];
        return $data;
    } 
    //START checking if slug from DB and setting variables according to that
    $data[ 'product_id_slug' ] = $product_id;

    //START setting up variables for getProductById function
    $prd_fetch_info = get_product_fetch_variables();
     
    $state_code = $prd_fetch_info[ 'state_code' ];
    $country_code = $country_code;
    $language_code = $language_code;

    $region_id = $this->addressApi->get_region_by_id( $country_code, $state_code );

    if( $region_id == null) { $region_id = 1;}

    $price_currency = Country::where( [ "country_code"=>$country_code ] )->pluck( 'currency_code' )->first();
    //END setting up variables for getProductById function
    
  //checking if product exists in DB
    $product_exists = $this->ProductController->getProductById($status, $product_id, $language_code, $region_id, $this->ORDER_TYPE, $this->STORE_ID, $price_currency, $country_code, $price_group );
    $productFoundForSelectedLocation = 1; 

    if( empty( $product_exists ) || ( (string)$product_exists[ 'category_id' ] != (string)$product_category )) {
        $productFoundForSelectedLocation = 0;
        $defaultState = get_default_state($country_code);
        $region_id = $this->addressApi->get_region_by_id( $country_code, $defaultState );
        $price_currency = Country::where( [ "country_code"=>$country_code ] )->pluck( 'currency_code' )->first();
       
    }
    //checking if product exists in DB
    $product_template = 'regular';
    $data['type'] = 1;
    $storeId = $this->STORE_ID;

    $data[ 'regular_product_details' ] = $this->ProductController->getProductById($status, $product_id, $language_code, $region_id, $this->ORDER_TYPE, $storeId, $price_currency, $country_code, $price_group );
    // this is only for visionary packs
    $data['showJoinNowButton'] = 0;
    if(empty($data[ 'regular_product_details' ]) && $product_category == config( 'global-constants.PRODUCT_CATS_MAIN.VISIONARY_BUILDER_PACKS' )) {
        $enrollStoreID = config( 'global-constants.STOREIDS.ENROLL_STORE_ID' );
        $data[ 'regular_product_details' ] = $this->ProductController->getProductById($status, $product_id, $language_code, $region_id, $this->ORDER_TYPE, $enrollStoreID, $price_currency, $country_code, $price_group );
        if(!(empty($data[ 'regular_product_details' ]))) {
            $data['showJoinNowButton'] = 1;
        }
    }
    if( empty($data['regular_product_details']) ){
        $productFoundForSelectedLocation = 0;
        $data[ 'regular_product_details' ] = $this->ProductController->getProductAvailableInAnyCountry($product_id, $language_code, $this->ORDER_TYPE, $storeId, $price_group ); 
    }  
    $productOptions = getOptions($product_id);

    $data[ 'regular_product_details' ][ 'options_data' ] = [];
    $data['selectable_option_count'] = 0;
    $data['autoship_ids'] = "";
    $data['qty_update'] = 1;
    $data[ 'regular_product_details' ]['vbkit'] = null;
    $data[ 'regular_product_details' ]['vbkit_order_type'] = 1;
    $data[ 'display_product' ] = 1;
    $customFields =  CustomField::where('product_id', $product_id)->pluck('field3')->first();
    $data['addon_subscriptions'] = [];
  
    if($customFields != null) {
       $customFieldData = json_decode($customFields, true);
        if($productOptions != null) {
            $data[ 'regular_product_details' ][ 'options_data' ]  = $productOptions['options'];
            $data['selectable_option_count'] = $productOptions['selectable_option_count']; 
        }
        if(isset($customFieldData['vbkit']) and $customFieldData['vbkit'] != "" ){
           $data[ 'regular_product_details' ]['vbkit'] =  $this->ProductController->getProductById($status, $customFieldData['vbkit'], $language_code, $region_id, $this->ORDER_TYPE, $storeId, $price_currency, $country_code, $price_group );
        }
        if(isset($customFieldData['vbkit_type']) and $customFieldData['vbkit_type'] != "" ){
           $data[ 'regular_product_details' ]['vbkit_order_type'] =  $customFieldData['vbkit_type']; 
        }
        if(isset($customFieldData['autoship'])){
            $data['autoship_ids'] =  $customFieldData['autoship']; 
        }
        if(isset($customFieldData['type'])){
            $data['type'] = $customFieldData['type']; 
        }
        if(isset($customFieldData['qty_update'])){
            $data['qty_update'] = $customFieldData['qty_update']; 
        }
    } 
    $data[ 'regular_product_subscription_details' ] = [];
    if($data['autoship_ids'] != "" && $data['autoship_ids'] != 0) {
        $data[ 'regular_product_subscription_details' ][] = $this->ProductController->getSubscriptionDetailWithProductId($status,$product_category, $data['autoship_ids'], $country_code, $state_code, $language_code );
    } 
    if(isset($customFieldData['addon_autoships']) && $customFieldData['addon_autoships'] != ""){
        $addonAutoshipIds = explode(',', $customFieldData['addon_autoships']);
        $addonSub = $this->ProductController->getSubscriptionProductIds($status,$product_category, $addonAutoshipIds, $country_code, $state_code, $language_code );
        if(count($addonSub) > 0) {
            $data[ 'regular_product_subscription_details' ] = array_merge($data[ 'regular_product_subscription_details' ], $addonSub);
        }
    } 
    $data[ 'regular_product_details' ][ 'r_product_images' ] = $this->ProductController->get_product_images( $product_id );
 
    
    if($product_category == $this->COURSES_CAT_ID || $product_category == $this->EVENT_PACKS_CAT_ID)
    {
        $data['productDetails'][ 'fields_class' ] = 'product-courses';
    }
    else
    {
        $data['productDetails'][ 'fields_class' ] = '';
    }
    
    $data['productFoundForSelectedLocation'] = $productFoundForSelectedLocation;
 
    if( $product_category == $this->PHYSICAL_PRODUCT_CAT_ID ){ 
        $product_template = 'pack';
        $product_type = "product";			
    } elseif( $product_category == $this->DIGITAL_PRODUCT_CAT_ID ){ 
        $product_template = 'pack'; 
        $product_type = "digital";
    } elseif( $product_category == $this->HEALTH_CAT_ID && $slugdata[ 'group_id' ] != null && $slugdata['group_id'] != 0){ 
        $product_template = 'pack'; 
        $product_type = "product";
    }
     elseif ($slugdata[ 'group_id' ] != null && $slugdata['group_id'] != 0){ 
        $product_template = 'pack'; 
        $product_type = "product";
    } 
    else{
        $product_type = "product";
    }
    Session::put('product_type_vbkit',$product_type);
    $cururl = url()->current();
    $arraurl = explode('/',$cururl); 
    if(end($arraurl) == 'power-7-21-pack') {
        $data['productDetails'][ 'Power-7-product' ] = 'Power-7-product';
    }
    else{
        $data['productDetails'][ 'Power-7-product' ] ="";
    }
    if($slugdata[ 'group_id' ] != null && $slugdata['group_id'] != 0 and $data['productFoundForSelectedLocation'] == 1){
        if( empty( $product_exists )) {
            $data[ 'product_id_slug' ] = "";
        }
        if($product_category == $this->DIGITAL_PRODUCT_CAT_ID ) {
            $data[ $product_type.'_packages' ] = $this->get_all_retail_products('', $status,'', $slugdata['group_id'], $product_category,'ASC', $price_group, $orderByColumn="product_id");
        } else {
            $data[ $product_type.'_packages' ] = $this->get_all_retail_products('', $status,'', $slugdata['group_id'],$product_category,'ASC', $price_group);
        } 
        if(!empty($data[ $product_type.'_packages' ])){
            $data['productFoundForSelectedLocation'] = 1;
          //START adding the options and subscription details to product array
            foreach( $data[ $product_type.'_packages' ] as $arr_prodct_itr => $arr_prodct ){
                if($data[ 'product_id_slug' ] == "") {
                    $data[ 'product_id_slug' ] = $arr_prodct[ 'product_id' ];
                }
                $customFields = CustomField::where('product_id', $arr_prodct[ 'product_id' ])->pluck('field3')->first(); 
                $data[ $product_type.'_packages' ][ $arr_prodct_itr ][ 'options_data' ] = [];
                $data[ $product_type.'_packages' ][ $arr_prodct_itr ]['options_count'] = 0;
                $data[ $product_type.'_packages' ][ $arr_prodct_itr ]['autoship_ids'] = $autoshipId = "";
                $data[ $product_type.'_packages' ][ $arr_prodct_itr ]['vbkit'] = 0;
                $data[ $product_type.'_packages' ][ $arr_prodct_itr ]['vbkit_order_type'] = 1;
                $data[ $product_type.'_packages' ][ $arr_prodct_itr ]['qty_update'] = 1;
                $data[ $product_type.'_packages' ][ $arr_prodct_itr ][ 'digital_subscription_details' ] = [];
                $data[ $product_type.'_packages' ][ $arr_prodct_itr ][ 'r_product_images' ] = $this->ProductController->get_product_images( $arr_prodct[ 'product_id' ] );
                $data[ $product_type.'_packages' ][ $arr_prodct_itr ][ 'display_product' ] = 1;
                    
                if($customFields != null) {
                    $customFieldData = json_decode($customFields, true);
                    if(isset($customFieldData['options'])){
                        $productOptions = getOptions( $arr_prodct[ 'product_id' ]);
                        if($productOptions != null) {
                            $data[ $product_type.'_packages' ][ $arr_prodct_itr ][ 'options_data' ]  = $productOptions['options'];
                            $data[ $product_type.'_packages' ][ $arr_prodct_itr ]['options_count'] = $productOptions['selectable_option_count']; 
                        }
                    }
                    $data[ $product_type.'_packages' ][ $arr_prodct_itr ]['vbkit'] = null;
                    $data[ $product_type.'_packages' ][ $arr_prodct_itr ]['autoship_ids'] = null;
                    if(isset($customFieldData['vbkit']) and $customFieldData['vbkit'] != "" ){
                        $data[ $product_type.'_packages' ][ $arr_prodct_itr ]['vbkit'] =  $this->ProductController->getProductById($status, $customFieldData['vbkit'], $language_code, $region_id, $this->ORDER_TYPE, $this->STORE_ID, $price_currency, $country_code, $price_group ); 
                    }
                    if(isset($customFieldData['vbkit_type']) and $customFieldData['vbkit_type'] != "" ){
                       $data[ $product_type.'_packages' ][ $arr_prodct_itr ]['vbkit_order_type'] =  $customFieldData['vbkit_type']; 
                    }
                    
                    if(isset($customFieldData['autoship']) && $customFieldData['autoship'] != "" &&  $customFieldData['autoship'] != 0){
                        $data[ $product_type.'_packages' ][ $arr_prodct_itr ]['autoship_ids'] = $autoshipId =  $customFieldData['autoship']; 
                    }
                    if(isset($customFieldData['qty_update'])){
                        $data[ $product_type.'_packages' ][ $arr_prodct_itr ]['qty_update'] =  $customFieldData['qty_update']; 
                    }
                    
                    if(isset($customFieldData['kit_level']) && $customFieldData['kit_level'] != 0 && isset($customFieldData['qty_limit'])){
                        $data[ $product_type.'_packages' ][ $arr_prodct_itr ][ 'display_product' ] = 0;
                        $userId =  get_current_user_id();
                        if($userId != null) {
                            $customerStats = $this->customer_API->get_customer_stats( $userId );  //dd($customerStats);
                            $customerCustomFields = $this->customer_API->get_customer_custom_fields( $userId );	//dd( $customerCustomFields);
                            if($customFieldData['kit_level'] == $customerStats["KitLevel"] && isset($customerCustomFields['Field1']) &&  $customerCustomFields['Field1'] < $customFieldData['qty_limit'] ) {
                                $data[ $product_type.'_packages' ][ $arr_prodct_itr ][ 'display_product' ] = 1;
                            }
                        }
                        
                    }	
                } 
                if($autoshipId != "") { 
                    $my_key = $product_type.'_packages_'.$arr_prodct_itr.  '_digital_subscription_details';
                    $autoship_product_ids[]=   array('value'=>$autoshipId,'key'=>$my_key,'arr_prodct_itr'=>$arr_prodct_itr);
                    $data[ $product_type.'_packages' ][ $arr_prodct_itr ][ 'digital_subscription_details' ] = $my_key;
                } 
                if(isset($customFieldData['addon_autoships']) && $customFieldData['addon_autoships'] != ""){
                    $addonAutoshipIds = explode(',', $customFieldData['addon_autoships']);
                    foreach($addonAutoshipIds as $addonAutoshipId) {
                        $my_key = $product_type.'_packages_'.$arr_prodct_itr.  '_digital_subscription_details';
                        $autoship_product_ids[] = array('value'=>$addonAutoshipId,'key'=>$my_key,'arr_prodct_itr'=>$arr_prodct_itr);
                        $data[ $product_type.'_packages' ][ $arr_prodct_itr ][ 'digital_subscription_details' ] = $my_key;
                    }
                }
            } 
            if(isset($autoship_product_ids) && !empty($autoship_product_ids)){
                $autoship_ids = array_column($autoship_product_ids,'value');
            
                $data_products = $this->ProductController->getSubscriptionProductIds($status,$product_category, $autoship_ids, $country_code, $state_code, $language_code );
            
                foreach ($data[$product_type.'_packages'] as $arr_prodct_itr => $products_array) {
                    $found_products = [];
                    // Check if the current $products_array has 'digital_subscription_details' set and is in the $autoship_product_ids array
                    if (isset($products_array['digital_subscription_details'])) {
                        foreach ($autoship_product_ids as $autoship_product_id) {
                            if ($products_array['digital_subscription_details'] == $autoship_product_id['key'] &&
                                $arr_prodct_itr == $autoship_product_id['arr_prodct_itr']) {
                                // Find the corresponding products and store them in $found_products array
                                foreach ($data_products as $data_product) {
                                    if ($data_product['product_id'] == $autoship_product_id['value']) {
                                        $found_products[] = $data_product;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    // Assign the found products to the 'digital_subscription_details' key of $products_array
                    $data[$product_type.'_packages'][$arr_prodct_itr]['digital_subscription_details'] = $found_products;

                }

            }
          
        }else{
        //    die('end');
        $data =[];    
        return $data;
        }
    } 
    if( $product_category == $this->DIGITAL_PRODUCT_CAT_ID ){ 
     //START checking if product already purchased by user
        $existing_product = $this->CartController->existing_user_subscriptions();
        if( in_array( $this->DIGITAL_PRODUCT_CAT_ID, $existing_product )){
        //    $data[ 'show_purchased_message' ] = 1;
        //    $data[ 'error_message' ] = "This product can only be purchase once, if you wish to upgrade or downgrade the digital pack then <a href='".env('nvisionulive')."'/manage-subscription''>CLICK HERE.</a>";
        $data =[];
        return $data;
        }
    } 
    $data[ 'product_type' ] = $product_type;  
    if(!isset($data['regular_product_details']['product_id']) && $slugdata[ 'group_id' ] =="0")
    {
        $data =[];
        return $data;
    }
        // if($data['regular_product_details']['hide_view'] == 1)
        // {
        // $data =[];
        //     return $data;
        // }
 
    if( $product_template == "regular" ){
        return $data;// view( 'frontend.ecommerce.product_detail_regular', $data )->with(['headerData'=>$headerData,'footerData'=>$footerData,'default_header_data'=>$default_header_data,'default_footer_data'=>$default_footer_data, "myaccountmenu_data" => $myaccountmenu_data, "default_myaccountmenu_data" => $default_myaccountmenu_data,'user_type' => $userDetails['CustomerType'],'WebAlias'=>$userDetails['WebAlias']]);
    }else{
        if(!empty($data[ $product_type.'_packages' ])) {
            return $data;//view( 'frontend.ecommerce.product_detail_pack', $data )->with(['headerData'=>$headerData,'footerData'=>$footerData,'default_header_data'=>$default_header_data,'default_footer_data'=>$default_footer_data, "myaccountmenu_data" => $myaccountmenu_data, "default_myaccountmenu_data" => $default_myaccountmenu_data,'user_type' => $userDetails['CustomerType'],'WebAlias'=>$userDetails['WebAlias']]);
        } else {
            return $data;//view( 'frontend.ecommerce.product_detail_regular', $data )->with(['headerData'=>$headerData,'footerData'=>$footerData,'default_header_data'=>$default_header_data,'default_footer_data'=>$default_footer_data, "myaccountmenu_data" => $myaccountmenu_data, "default_myaccountmenu_data" => $default_myaccountmenu_data,'user_type' => $userDetails['CustomerType'],'WebAlias'=>$userDetails['WebAlias']]);
        }
     }
   }

}

 

