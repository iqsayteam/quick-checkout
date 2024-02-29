<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Components\Directscale\SSO as SSOComponent;
use App\Components\Directscale\CustomApi ;
use App\Components\Directscale\Customers as CustomerComponent;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use App\Models\CartDataHome;
use App\Models\Country;
use App\Models\CartHome;
use Illuminate\Support\Facades\Cookie;
use App\Http\Controllers\ProductController;
use App\Models\CustomField;
use App\Models\Product; 
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use App\Components\CurlApi\Normalcurl;

class AuthController extends Controller
{
  protected $customer_API; 
  protected $Custom_API; 
    
    protected $SSO_API;  
    protected $ProductController;  
    protected $ORDER_TYPE;
    protected $SUBS_ORDER_TYPE;
    protected $STORE_ID;
    protected $addressApi;
    protected $normalcurl;
    

    function __construct()
    {
    $this->SSO_API = new SSOComponent();
		$this->customer_API = new CustomerComponent(); 
    $this->ProductController= new ProductController();
    $this->ORDER_TYPE = config( 'global-constants.PRODUCT_FETCH.ORDER_TYPE' );
    $this->SUBS_ORDER_TYPE = config( 'global-constants.PRODUCT_FETCH.SUBS_ORDER_TYPE' );
    $this->STORE_ID = config( 'global-constants.PRODUCT_FETCH.STORE_ID' );
    $this->normalcurl = new Normalcurl();
    $this->Custom_API = new CustomApi();
    }

/**
     * Created By:Raju
     * Created on: 28-02-2024
     * Last Updated By:Raju
     * Last Updated on: 28-02-2024
     * 
     * This function used to authenticate the user with credentials and link from which user reached here
     * @return [type]
    */
  	

    public function loggedinUser(Request $request)
    {
          // Validation rules
          $rules = [
            'email' => 'required|email',
            'password' => 'required|min:8', // Change min:8 to your desired minimum length
          ]; 

          // Custom messages
          $messages = [
            'email.required' => 'Email is required',
            'email.email' => 'Please enter a valid email address',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least :min characters',
          ];
         
          // Validate the request
          $validator = Validator::make($request->all(), $rules, $messages);
 
          // Check if validation fails
          if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
          }
          $credential['json'] = [
            "username" => $request->email,
            "password" => $request->password,
        ];  
        $user_respose = $this->customer_API->userLogin($credential);   
      if(isset($user_respose['isError']))
      {
        return redirect()->back()->withErrors(['login_error'=>"Authentication Failed"])->withInput($request->input());
      }
          if(isset($user_respose['CustomerId']) && $user_respose['CustomerId'] == Session::get('userfromtoken') ) {
              $accountDisableStatus=config( 'global-constants.DEFAULT_PARAMS.ACCOUNT_DISABLE_STATUS' );
              $customerType=config( 'global-constants.DEFAULT_PARAMS.CUSTOMER_TYPE_GUEST' );
              if((isset($user_respose['CustomerStatus']) && in_array($user_respose['CustomerStatus'], $accountDisableStatus)) || $user_respose['CustomerType'] == $customerType) {
                  $error = trans('login.invalid_user_guest');
                  if(isset($request->login_ajax)) {
                      return ['status'=>false, 'statusCode'=>500,'error'=>$error];
                  } else {
                      return redirect()->back()->withErrors(['login_error'=>$error])->withInput($request->input());
                  }
              }
  
               Cookie::queue( Cookie::forget('distributor'));
              
              $userId = $user_respose['CustomerId'];
              $userEmail = $user_respose['EmailAddress'];
              
              $webAlias = $user_respose['WebAlias'];
              $request->session()->put('user_id', $userId);
              $request->session()->put('user_email', $userEmail);
              $request->session()->put('loggedin_first_name', $user_respose['FirstName']);
              $request->session()->put('loggedin_last_name', $user_respose['LastName']);
              $request->session()->put('user_webalias', $webAlias);
              $request->session()->put('DefaultShippingAddress', $user_respose['DefaultShippingAddress']); 
            
              if(isset($user_respose['PrimaryAddress']['CountryCode'])) {
  
                  $user_country =  $user_respose['PrimaryAddress']['CountryCode'];
              }
              if(isset($user_respose['LanguageCode'])) {
                  $user_lang = $user_respose['LanguageCode'];
              }
              
             $price_group= Session::get('price_group');
              $productarray = json_decode($request->products,true);  
              foreach($productarray as $product)
              {
                $productstoaddtocart  = [ 'product_id'=>$product['product_id_slug'],
                'product_subscription'=>1,
                'product_type'=>$product['type'],
                'quantity'=>1,
                'group'=>$price_group,
                'autoship_ids'=>$product['autoship_ids'],
                'price_currency'=>$product['regular_product_details']['price_currency'],
                'vbkit'=>($product['regular_product_details']['vbkit'] == null)? 0 : $productarray['regular_product_details']['vbkit']];
                // $this->main_add_to_cart( $productstoaddtocart );
                $responsecartapi = Http::post(env('nvisionu')."/api/main_add_to_cart",$productstoaddtocart ); 
              
              }
              $responsecartapi = json_decode($responsecartapi,true);
    
              if(isset($responsecartapi['success']))
              {
                $data = ['userid'=> $userId,'user_lang'=>  $user_lang,'user_country'=> $user_country] ;
               
                $sendcarttocheckout = Http::get(env('nvisionu')."/api/send_cart_to_checkout",$data); 
                $sendcarttocheckout =  json_decode($sendcarttocheckout,true); 
                if($sendcarttocheckout['success'])
              {
                return redirect()->away($sendcarttocheckout['link']);
              }else
              {
                return $sendcarttocheckout;
              }
            } else
               {
                $error = "Something went wrong."; 
                return redirect()->back()->withErrors(['login_error'=>$error])->withInput($request->input());
               }
               
          } else {
         
            if($user_respose['CustomerId'] != Session::get('userfromtoken'))
            {
              $error = "User Mismatched"; 
            }else
            {
              $error = "Wrong Password"; 
            }
        
        return redirect()->back()->withErrors(['login_error'=>$error])->withInput($request->input());
      } 
    }
   /**
     * Created By: Raju
     * Created on: 28-02-2024
     * Last Updated By: Raju
     * Last Updated on: 28-02-2024
     * 
     * This function is used to get new details  
     * @return [statuscode]
    */
    public function getnewInputs($inputs)
    {  
        $product_id =$inputs['product_id'];
            $customFields =  CustomField::where('product_id', $product_id)->pluck('field3')->first();
            $customFieldData = json_decode($customFields, true);
            if(isset($customFieldData['fl2_cart_items'])){
				 $fl2_cart_items = $customFieldData['fl2_cart_items']; 
                 $fl2_cart_items = explode(',',$fl2_cart_items);
                  $count_of_fl2_cart_items =count( $fl2_cart_items);
                  if(isset($customFieldData['fl2_cart_services'])){
                    
                    $fl2_cart_services = $customFieldData['fl2_cart_services']; 
                    $fl2_cart_services = explode(',',$fl2_cart_services);
                  }
                                        
                  for($i=0; $i < $count_of_fl2_cart_items; $i++)
                  { 
                      $product_id =  isset( $fl2_cart_items[$i]) ?  $fl2_cart_items[$i] : '';
                      $AutoShips =  isset($fl2_cart_services[$i]) ? $fl2_cart_services[$i] : '';
   
                      $product_data = Product::where( 'product_id', $product_id )->where('disabled', 0)->first();

                    

                      $prd_fetch_info = get_product_fetch_variables();  
                      $country_code = $prd_fetch_info[ 'country_code' ]; 

                      if(isset($inputs['vbkit']) and $inputs['vbkit'] != "" ){
                      $language_code = $prd_fetch_info[ 'language_code' ]; 
                      $state_code = $prd_fetch_info[ 'state_code' ]; 
                      $region_id = $this->addressApi->get_region_by_id( $country_code, $state_code );
                      if( $region_id == null) { $region_id = 1;}
                      $price_currency = Country::where( [ "country_code"=>$country_code ] )->pluck( 'currency_code' )->first();
                      $price_group = get_logged_in_customer_type();
                      //  $vbkit['vbkit'] = $customFieldData['vbkit'];
                      $status=   Session::get('product_status');   
                      $product_type =  Session::get('product_type_vbkit');

                  
                      $arr_prodct_itr=   $i;
                      $productOptions = getOptions($product_id);
                      if($customFields != null && $inputs['vbkit'] != "0") {

                    $customFields_from_custom_product =  CustomField::where('product_id',  $inputs['vbkit'])->pluck('field3')->first();
                    $customFields_from_custom_product = json_decode($customFields_from_custom_product,true);
                  

                        if(isset($customFieldData['options'])){ 
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
				
						if(isset($customFields_from_custom_product['autoship']) && $customFields_from_custom_product['autoship'] != "" &&  $customFields_from_custom_product['autoship'] != 0){
							$data[ $product_type.'_packages' ][ $arr_prodct_itr ]['vbkit']['autoship_ids'] = $autoshipId =  $customFields_from_custom_product['autoship']; 
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
                        $data['product_packages'][0]['vbkit']['product_subscription'] =  $inputs['product_subscription'];
                        $data['product_packages'][0]['vbkit']['product_type'] =  "1";
                        }

                    } 
                      $group_id = $product_data['group_id'];
   
                      if(!isset($inputs['quantity'])) {
                          $inputs['quantity'] = "1";
                      }
                       
                      $product_type = "1"; 

                      $response[] = [  
                      'product_id'=> $product_id ,
                      'product_subscription'=>$inputs['product_subscription'],
                      'product_type'=> "$product_type" ,
                      'quantity'=> $inputs['quantity'],
                      'group'=> $group_id,
                      'autoship_ids'=> $AutoShips, 
                      'price_currency'=>   $price_currency,
                      'vbkit'=> (isset($data)) ? $data['product_packages'][0]['vbkit'] : '0'];
                  }
			}
            else
            {
                $customFields_from_custom_product_new =  CustomField::where('product_id',  $inputs['product_id'])->pluck('field3')->first();
                $customFields_from_custom_product_new =json_decode($customFields_from_custom_product_new,true);

                if(isset($customFields_from_custom_product_new['fl2_cart_services']))
                {
                    $inputs['autoship_ids'] =  $customFields_from_custom_product_new['fl2_cart_services'];
                }
       
                $response[] = $inputs;
            }
    
           
        return json_encode($response);
    }
 
/**
     * Created By:Raju
     * Created on: 28-02-2024
     * Last Updated By:Raju
     * Last Updated on: 28-02-2024
     * 
     * This function used to add products to cart
     * @return [type]
    */
    public function main_add_to_cart($inputs) { 
    
      $inputs=$this->getnewInputs($inputs); 
      $inputsArr = json_decode($inputs,true);

     
  foreach($inputsArr as $inputs) // This loop was Added by Raju 
  {

      if($inputs['product_id'] == 2287)
      {
          $searchupsertuser =   SearchUpseartUser();
          if($searchupsertuser == 2)
          {
              $response = array('response'=>2,'error'=>'true','message'=>'Login first to add this product to your cart');
              break;
        return $response;
          }
          if($searchupsertuser == 1)
          {
              $response = array('response'=>1,'error'=>'true','message'=>'Your previous subscription expiered. Please contact Support.');
              break;
        return $response;
          }
        
      }

  if(!isset($inputs['product_options'])) {
    $inputs['product_options'] = [];
  }

  if(!isset($inputs['product_subscription']) || $inputs['product_subscription'] == 0) {
    $inputs['autoship_ids'] = "";
  }
  if(!isset($inputs['quantity'])) {
    $inputs['quantity'] = 1;
  }
  if(!isset($inputs['is_pack'])) {
    $inputs['is_pack'] = 0;
  }
  
  if(isset($inputs['product_subscription']) && $inputs['product_subscription'] == 1 && $inputs['autoship_ids'] == "") {
    $inputs['autoship_ids'] = $inputs['product_id'];
  }

  $serviceIdsProduct = [];
  if(!empty($inputs['product_options'])) {
    $serviceIdsProduct = $inputs['product_options'];
  }
  $serviceIds = $serviceIdsProduct; 
  $includedCourse = getIncludedCourses($inputs['product_id']);
  if(count($includedCourse) > 0) {
    $serviceIds = array_merge($serviceIds, $includedCourse);
    $inputs['product_options'] = array_merge($inputs['product_options'], $includedCourse);
  }
  
  if($inputs['product_subscription'] == 1) {
    $autoshipCourses = autoshipSubscriptionCourse($inputs['product_id']);
    if(count($autoshipCourses) > 0) {
      $serviceIds = array_merge($serviceIds, $autoshipCourses);
    }
  } 
  $productArray[$inputs['product_id']] = [
    'product_id'=>$inputs['product_id'], 'quantity'=>$inputs['quantity'],  'price_currency'=>$inputs['price_currency'], 
    'included_products'=>$inputs['product_options'], 'autoships'=>$inputs['autoship_ids'],'is_membership'=>$inputs['product_subscription'],
    'service_ids'=>$serviceIds, 'order_type'=>1,'product_type'=>$inputs['product_type'], 'is_pack'=>$inputs['is_pack']];
    
    if(isset($inputs['voucher_amount'])) {
              $productArray[$inputs['product_id']]['voucher_amount'] = $inputs['voucher_amount'];
          }
        
  $response = $this->addProductsToCart($productArray, $inputs['product_id']);

  if(isset($inputs['vbkit']) and $inputs['vbkit'] != 0){
    if(isset($inputs['vbkit']['order_type']) && $inputs['vbkit']['order_type'] == 2) {
      $inputs['vbkit']['order_type'] = "2";
    } else {
      $inputs['vbkit']['order_type'] = "1";
    }

    $vbKitArray[$inputs['vbkit']['product_id']] = [
    'product_id'=>$inputs['vbkit']['product_id'], 'quantity'=>1,  'price_currency'=>$inputs['vbkit']['price_currency'], 
    'included_products'=>[], 'autoships'=>$inputs['vbkit']['autoship_ids'],'is_membership'=>$inputs['vbkit']['product_subscription'],
    'service_ids'=>[], 'order_type'=>$inputs['vbkit']['order_type'], 'product_type'=>$inputs['vbkit']['product_type'], 'is_pack'=>0];
     $response[] = $this->addProductsToCart($vbKitArray, $inputs['vbkit']['product_id']);
  } else{
          $response[] = $response;
  }
  }
  return $response;
}

/**
     * Created By:Raju
     * Created on: 28-02-2024
     * Last Updated By:Raju
     * Last Updated on: 28-02-2024
     * 
     * This function used to add products to cart
     * @return [type]
    */
 
public function addProductsToCart($productArray, $productId) {
  $userId =  get_current_user_id();
  $inputs = $productArray[$productId]; 
  $country_code = get_current_country_code();
  $userID =  get_current_user_id();
  $preferred_language = get_current_language_code();//dd( $product);
  $currency = $inputs['price_currency'];
  $cart_data = CartHome::firstOrCreate(['user_id' => $userID,'country_code'=>$country_code],
  ['user_id' => $userID,'country_code'=>$country_code, 'language'=>$preferred_language, 'price_currency'=>$currency] );
  unset($productArray[$inputs['product_id']]['price_currency']);
  $response = $this->addToCartDB($productArray[$inputs['product_id']], $cart_data->id);
  return $response;
}
/**
     * Created By:Raju
     * Created on: 28-02-2024
     * Last Updated By:Raju
     * Last Updated on: 28-02-2024
     * 
     * This function used to add Cart to  database
     * @return [type]
    */ 
public function addToCartDB($product, $cartId) {
  $customField4 = "";
  $product['show_badge'] = "0"; 
  if($product['autoships'] != "" && $product['autoships'] != null) { 
    $customField4 = getAutoshipPackLevel($product['autoships']);
    $product['show_badge'] = "1"; 
  }
  $product['custom_field4'] = $customField4;

      $status = Session::get('product_status');
      if(!isset($status)){
          $status = config( 'global-constants.PRODUCT_STATUS.PUBLISH');
      }
  
  $product['created_at'] =  date("Y-m-d h:i:s");
      $product['updated_at'] =  date("Y-m-d h:i:s");
      $product['status'] =  $status;
    
      if(isset($product['service_ids'])){
          if(is_array($product['service_ids'])) {

              $product['service_ids'] =  implode(',', array_unique($product['service_ids']));            
          } else {
              $product['service_ids'] =  $product['service_ids'];       
          }
    
  }
      $product['included_one_month_subscription'] = getOneMonthCoursesSubscription($product['product_id']);
      if(isset($product['included_one_month_subscription'])){
          if($product['included_one_month_subscription'] != "") {
              if(!is_array($product['included_products'])) {
                  $product['included_products'] = explode(',', $product['included_products']);
              }
              $product['included_products'] = array_merge($product['included_products'], $product['included_one_month_subscription']);
          }
         unset($product['included_one_month_subscription']);
      }
  if(isset($product['included_products'])){
          if(is_array($product['included_products'])) {

              $product['included_products'] = implode(',',array_unique($product['included_products']));         
          } else {
              $product['included_products'] =  $product['included_products'];       
          }
  }

  $categoryId = Product::where('product_id', $product['product_id'])->pluck('category_id')->first();

  if($categoryId == config( 'global-constants.PRODUCT_CATS_MAIN.DIGITAL' )) {
    CartDataHome::where(['cart_id'=>$cartId, 'category_id'=>$categoryId])->delete();
  }		
  $itemExits = CartDataHome::where(['cart_id'=>$cartId, 'product_id'=>$product['product_id']])->first();

  $customFields = CustomField::where('product_id', $product['product_id'])->pluck('field3')->first(); 
  $customFieldData = [];
  if($customFields != null) {	
    $customFieldData = json_decode($customFields, true);
  }	
  
  $userId =  get_current_user_id();
  if(isset($customFieldData['kit_level']) && $customFieldData['kit_level'] != 0 && isset($customFieldData['qty_limit'])){
    $customerStats = $this->customer_API->get_customer_stats( $userId );  
    $customerCustomFields = $this->customer_API->get_customer_custom_fields( $userId );
  }
  if($itemExits){
    if(isset($customFieldData['kit_level']) && $customFieldData['kit_level'] != 0 && isset($customFieldData['qty_limit'])){
      if($customFieldData['kit_level'] == $customerStats["KitLevel"] && $customerCustomFields['Field1'] < $customFieldData['qty_limit'] && $customFieldData['qty_limit'] >= $itemExits['quantity'] + (int)$customerCustomFields['Field1'] + 1) {
        $product['quantity'] = $itemExits['quantity'] + 1;
      } else{
        return $response = [ 'success' => true, 'message' => 'Cart is updated successfully' ];
      }
    } else{
      if(checkIfUserCanUpdateQuantity($product['product_id']) == config( 'global-constants.DEFAULT_PARAMS.ZERO' ) || $categoryId == config( 'global-constants.PRODUCT_CATS_MAIN.DIGITAL' )) {
        $product['quantity'] = 1;
      } else{
        $product['quantity'] = $itemExits['quantity'] + $product['quantity'];
      }
    }
          if(getField5Data($product['product_id']) == "JC") {
              if(isset($product['voucher_amount'])) {
                  $product['voucher_amount'] = $itemExits['voucher_amount'] + $product['voucher_amount'];
              }
          }
          if(isset($product['categoryId'])) {
              $product['category_id'] = $product['categoryId'];
              unset($product['categoryId']);
          }
    CartDataHome::where(['cart_id'=>$cartId, 'product_id'=>$product['product_id']])->update($product);
    $response = [ 'success' => true, 'message' => 'Cart is updated successfully' ];
  } else{
    $product['cart_id'] = $cartId;
    if($categoryId == null){
      $categoryId  = 0;
    }
    $product['category_id'] = $categoryId;
    if(isset($product['is_pack']) && $product['is_pack'] == 1) {
      $product['is_product_group'] = 1;
    } else {
      $product['is_product_group'] = $categoryId;
    }
 
     CartDataHome::create($product);
    $response = [ 'success' => true, 'message' => 'Product added into cart'];                            
  }
  

  if( $categoryId == config( 'global-constants.PRODUCT_CATS_MAIN.DIGITAL' ) ){
          $existing_services = $this->existing_user_subscriptions();
          if(in_array( $categoryId, $existing_services )){
              CartDataHome::where( 'product_id', $product['product_id'] )->delete();
          }

      }
  return $response;
}



  /**
     * Created By: Raju
     * Created on: 28-02-2024
     * Last Updated By: Raju
     * Last Updated on: 28-02-2024
     * >> add ecommerce to fromapp query parameter
     * 
     * This function fetches the logged in user cart product and send to the checkout app
     * @return [type]
     */
    public function send_cart_to_checkout(){
      $country_code = $_COOKIE['country_code'];
      $language_code = $_COOKIE['language_code'];
      $userId = session()->get( 'userfromtoken' );
      
      
      $cart_data = CartHome::where( [ 'user_id' => $userId,'country_code'=>$country_code] )->first();
     
      //checking if user cart is empty, and if not then getting the cart products and send to checkout app
      if( empty( $cart_data ) || $cart_data == null ){
          return json_encode( array( 'success' => false, 'message' => 'No Products In cart.' ) );
      }else{
          $default_date_check = config('global-constants.DEFAULT_PARAMS.DEFAULT_SMARTSHIP_DATE');

          $user_cart = $cart_data->toArray();
          $cartId = $cart_data[ 'id' ];
          $user_cart_products = CartDataHome::where( [ 'cart_id' => $cartId ] )
          ->get()
          ->toArray();
          $autoships = [];
          foreach($user_cart_products as $key => $user_cart_products_filter)
    {
              $field3 = getCustomField3Data($user_cart_products_filter['product_id']);
              $date_check = "";
              if(isset($field3['date_check']) && ($user_cart_products_filter['is_membership'] == 1)) {
                  $date_check = $field3['date_check'];
              }
      if($user_cart_products_filter['product_type'] == 5) {
        $product_id = $user_cart_products_filter['product_id'];
        $voucher_price = $user_cart_products[$key]['voucher_amount'];
        $user_cart_products[$key]['voucher_status']='1';
        $user_cart_products[$key]['voucher_price']=$voucher_price;
        $user_cart_products[$key]['quantity']=$voucher_price;
      } else {
        $user_cart_products[$key]['voucher_status']='0';
      }
              $today = Carbon::today()->format('d');
              $autoship_date = $today;
              //if($user_cart_products_filter['is_membership'] == 1){
                  if($today > $default_date_check) {
                      $autoship_date = $default_date_check;
                  }
                  if(isset($date_check)) {
                      if($today > $date_check && $date_check != "") {
                          $autoship_date = $date_check;
                      }
                  }

                  if(isset($field3['automatic_autoships'])) {
                      if($field3['automatic_autoships'] != "") {
                          $autoship_dateFinal = date('Y-m-'.$autoship_date, strtotime('next month'));
                          $automatic_autoships = explode(',',$field3['automatic_autoships']);
                          foreach($automatic_autoships as $automatic_autoship) {
                              $categoryId = Product::where('product_id', $automatic_autoship)->pluck('category_id')->first();
                              $autoships[] = [
                                  'product_id'=>$automatic_autoship, 'quantity'=>1,'category_id'=>$categoryId,
                                  'included_products'=>"", 'autoships'=>$automatic_autoship,'is_membership'=>"1",
                                  'is_product_group'=>$categoryId.str_replace('-','',$autoship_dateFinal),
                                  'service_ids'=>"", 'order_type'=>2, 'is_pack'=>0, 'next_process_date'=>$autoship_dateFinal
                              ];
                          }
                      }
                  }

                  if(isset($field3['prelaunch_date']) && isset($field3['prelaunch_autoship'])) {
                      if($field3['prelaunch_date'] != "") {
                          $autoship_date = $default_date_check;
                          $serviceIds = "";
                          if(isset($field3['prelaunch_asc']) && $field3['prelaunch_asc']) {
                              $serviceIds = $field3['prelaunch_asc'];
                          }
                          $categoryId = Product::where('product_id', $field3['prelaunch_autoship'])->pluck('category_id')->first();
                          $autoships[] = [
                              'product_id'=>$field3['prelaunch_autoship'], 'quantity'=>1,'category_id'=>$categoryId,
                              'included_products'=>"", 'autoships'=>$field3['prelaunch_autoship'],'is_membership'=>"1",
                              'is_product_group'=>$categoryId.str_replace('-','',$field3['prelaunch_date']),'custom_field4'=>'prelaunch',
                              'service_ids'=>$serviceIds, 'order_type'=>2, 'is_pack'=>0, 'next_process_date'=>$field3['prelaunch_date']
                          ];
                          $user_cart_products[$key]['show_badge'] = "0";

                      }
                  }



                  $autoship_dateFinal = date('Y-m-'.$autoship_date, strtotime('next month'));
                  $user_cart_products[$key]['next_process_date'] = $autoship_dateFinal;
                  $user_cart_products[$key]['is_product_group'] = $user_cart_products_filter['is_product_group'].$autoship_date;
                  //$user_cart_products[$key]['next_process_date'] = Carbon::parse($autoship_dateFinal)->format('Y-m-d');
              //}

    }

        //dd($user_cart_products);
          if(count($autoships) > 0) {
              $user_cart_products = array_merge($user_cart_products, $autoships);
          }
          $new_array = [];
          foreach ($user_cart_products as $_item) {
      if (strpos($_item['autoships'], ',') !== false) {
        $autoships = explode(',', $_item['autoships']);
        foreach ($autoships as $autoship) {
          $new_item = $_item;
          $new_item['autoships'] = $autoship;
            $new_array[] = $new_item;
        }
      } else {
        $new_array[] = $_item;
      }
    }
          $user_cart_products = $new_array;
     
          $productDetials[ 'user_cart_products' ] = $user_cart_products;
          $productDetials[ 'user_cart' ] = $user_cart;
          $productDetials[ 'user_cart' ]['from_app'] = "ecommerce";
          $productdata[ 'json' ] = $productDetials;
         
          // $uri = env( 'ADD_TO_CART_URI' )."api/add-to-cart";
          $uri = env( 'ADD_TO_CART_URI' )."api/ecommerce-add-to-cart";
          //echo json_encode($productdata);die;
     $add_to_checkout_cart = $this->normalcurl->curlRequest( $uri, "POST", $productdata ); 
        
          
          // $add_to_checkout_cart = $this->add_ecom_products_to_cart( $user_cart, $user_cart_products );

          if(isset($add_to_checkout_cart[ 'success' ])){

              $distributor_session = json_decode( json_encode( Session::get('distributorDetailsSession') ) );

              if( ( isset( $distributor_session ) ) && ( $distributor_session != 'empty' ) && ( $distributor_session != null ) ){
                  $distributor = $distributor_session;
              }else{
                  $distributor = json_decode(Cookie::get('distributor'));
              }

              if( !empty( $distributor ) ){
                  $distributor_lg_lnk = $distributor->WebAlias;
              }else{
                  $distributor_lg_lnk = "";
              }
              // dd($distributor_lg_lnk);
              
             $response = $this->customer_API->ssoLink( $userId );
      $parts = parse_url( $response );
      parse_str( $parts[ 'query' ], $query );
      $authToken = str_replace( '+', '{plus}', $query[ 'auth' ] ); 
      $authToken = str_replace( '/', '{slash}', $authToken );
              $link = env( 'ADD_TO_CART_URI' )."login-with-token?from_app=ecommerce&store_id=".$this->STORE_ID."&token=".$authToken."&distributor=".$distributor_lg_lnk."&country_code=".$country_code."&language_code=".$language_code;
      //$link = env( 'ADD_TO_CART_URI' )."login-with-token?from_app=ecommerce&store_id=".$this->STORE_ID."&token=".$authToken."&distributor=".$distributor_lg_lnk."&country_code=".$country_code."&language_code=".$language_code."&autoship_date=".$autoship_dateFinal;
      return array( "success" => true, "link" => $link );
          }else{
              // dd("failr");
              return array( 'success' => false, 'message' => 'Something went wrong at checkout api side'.json_encode($add_to_checkout_cart) );
          }
      }
 
  }
 
}
