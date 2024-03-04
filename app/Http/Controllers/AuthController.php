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
                'product_type_vbkit'=>$product['product_type'],
                'vbkit'=>($product['regular_product_details']['vbkit'] == null)? 0 : $product['regular_product_details']['vbkit'],
                'userId'=>$userId,
                'user_country'=> $user_country,
                'user_lang'=>$user_lang,
              ];
                
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
                return redirect(env('collectionPage'));
              }
            } else
               {
                $error = "Something went wrong."; 
                // return redirect()->back()->withErrors(['login_error'=>$error])->withInput($request->input());
                return redirect(env('collectionPage'));
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
  
 
}
