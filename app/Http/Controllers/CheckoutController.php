<?php

namespace App\Http\Controllers;

use App\Components\Directscale\Address;
use App\Components\Directscale\Customers as CustomerComponent;
use App\Components\Directscale\Products;
use App\Components\Directscale\SSO as SSOComponent;
use App\Models\Product;
use App\Models\Quicklinks;
use App\Models\serviceProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CheckoutController extends Controller
{
    protected $SSO_API;
    protected $Products;

    protected $customer_API;
    protected $addressApi;
    protected $retail_products_cat_ids = array(4, 5, 24, 25); // 4 is 'Digital Packages' and 5 is 'Product Packages' category

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

    public function __construct()
    {
        $this->addressApi = new Address();
        $this->customer_API = new CustomerComponent();

        $this->ProductController = new ProductController();

        $this->DIGITAL_PRODUCT_CAT_ID = config('global-constants.PRODUCT_CATS_MAIN.DIGITAL');
        $this->PHYSICAL_PRODUCT_CAT_ID = config('global-constants.PRODUCT_CATS_MAIN.PRODUCT');
        $this->SPRAY_PRODUCT_CAT_ID = config('global-constants.PRODUCT_CATS_MAIN.PRODUCT_SPRAY');
        $this->VBKIT_PRODUCT_CAT_ID = config('global-constants.PRODUCT_CATS_MAIN.VBKIT');
        $this->SILVER_ADDON_CAT_ID = config('global-constants.PRODUCT_CATS_MAIN.ADDON');
        $this->SILVER_ADDON_PRD_ID = config('global-constants.PRODUCT_IDS.ADDON_PRD_ID');
        $this->DIGITAL_SILVER_PRODUCT_ID = config('global-constants.PRODUCT_IDS.DIGITAL_SILVER');
        $this->INT_PACK_PRD_ID = config('global-constants.PRODUCT_IDS.NVULPINT');
        $this->NEW_PACK_PRD_ID = config('global-constants.PRODUCT_IDS.NVU4NS');
        $this->HEALTH_WEALTH_PACK_PRD_ID = config('global-constants.PRODUCT_IDS.NVUHW');
        $this->HEALTH_PACK_PRD_ID = config('global-constants.PRODUCT_IDS.NVUH');
        $this->HEALTH_CAT_ID = config('global-constants.PRODUCT_CATS_MAIN.HEALTH');
        $this->WEALTH_CAT_ID = config('global-constants.PRODUCT_CATS_MAIN.WEALTH');
        $this->PROMO_PACK_CAT_ID = config('global-constants.PRODUCT_CATS_MAIN.PROMO_PACK');
        $this->EVENT_PACKS_CAT_ID = config('global-constants.PRODUCT_CATS_MAIN.EVENT_PACKS');
        $this->COURSES_CAT_ID = config('global-constants.PRODUCT_CATS_MAIN.COURSES');

        $this->ORDER_TYPE = config('global-constants.PRODUCT_FETCH.ORDER_TYPE');
        $this->SUBS_ORDER_TYPE = config('global-constants.PRODUCT_FETCH.SUBS_ORDER_TYPE');

        //$this->DIGITAL_SILVER_PRODUCT_ID = config( 'global-constants.PRODUCT_CATS_MAIN.DIGITAL' );
        $this->ORDER_TYPE = config('global-constants.PRODUCT_FETCH.ORDER_TYPE');
        $this->STORE_ID = config('global-constants.PRODUCT_FETCH.STORE_ID');

        $this->SIMPLE_PRODUCT = config('global-constants.PRODUCT_TYPES.SIMPLE_PRODUCT');
        $this->VARIABLE_PRODUCT = config('global-constants.PRODUCT_TYPES.VARIABLE_PRODUCT');
        $this->GROUP_PRODUCT = config('global-constants.PRODUCT_TYPES.GROUP_PRODUCT');
        $this->ERROR_PATH = config('global-constants.DEFAULT.ERROR_PATH');
        $this->CORE = config('global-constants.PACK_TYPE.CORE');
        $this->ESSENTIAL = config('global-constants.PACK_TYPE.ESSENTIAL');
        $this->PREMIER = config('global-constants.PACK_TYPE.PREMIER');
        $this->SSO_API = new SSOComponent();
        $this->customer_API = new CustomerComponent();
        $this->Products = new Products();
    }

    /**
     * Created By: Raju
     * Created on: 20-03-2024
     * Last Updated By: Raju
     * Last Updated on: 20-03-2024
     *
     *

     * @return [type]
     */

    public function getServiceIdsFromDB()
    {
        ini_set('max_execution_time', 1000);  
        $now = Carbon::now();
        // Add 7 days to both FromDate and ToDate
        $dates = array(
            "FromDate" => $now->copy()->startOfDay()->addDays(env("SUBSCRIPTION_EXPIRE_IN_DAYS")),
            "ToDate" => $now->copy()->endOfDay()->addDays(env("SUBSCRIPTION_EXPIRE_IN_DAYS")),
        );
        $response = Http::post(env('getitemswithusers'), $dates);

        if (!$response->successful()) {
            return ['Status' => false, 'Message' => "Something Went Wrong", 'API_Response' => $response];
        }
        $respArray = json_decode($response, true); 

        if (!isset($respArray['data'])) {
            return ['Status' => false, "message" => "Something went wrong", "API_Response" => $respArray];
        }


 $response = $this->quickcheckoutserviceitemsapi();
 $data = json_decode($response->body());
 if(!isset($data->data))
{
    return ['Status' => false, "message" => "Something went wrong", "API_Response" => $response];
}
 $service_item_array = json_decode($data->data,true);
 
$i=1; 
        foreach ($respArray['data'] as $userdata) {
            $i++; 
            $filteredArray = array_filter($service_item_array, function($item) use ($userdata) {
                return $item['service_id'] == $userdata['serviceID'];
            });
            $resetfilteredArray = reset($filteredArray); 
          if($resetfilteredArray != false)
          {
            $result = serviceProduct::updateOrCreate(
                [
                    'user_id' => $userdata['associateID'],
                    'service_id' => $userdata['serviceID']
                ],
                [
                    'item_ids' => $resetfilteredArray['item_ids'],
                ]
            );
          }
          
        }  
        return ['status' => true]; 
    }
    /**
     * Created By: Raju
     * Created on: 28-02-2024
     * Last Updated By: Raju
     * Last Updated on: 28-02-2024
     *
     * This function modify the data recieved from gaurav api

     * @return [type]
     */

    public function createUniqueLink(Request $request)
    {
        ini_set('max_execution_time', 1000);  
        $respArray['data'] = $this->getServiceIdsFromDB(); 

        if(isset($respArray['data']['status']) && !$respArray['data']['status'])
        {
            return ['status' => false, 'message' => 'data not added to database', 'response' =>$respArray['data'] ]; 
        }
        $respArray['data'] = serviceProduct::get();
        $userflag = false;
        if(isset($request->user_id)){
             $userflag = true;
        }
        $userlist =[];
        foreach ($respArray['data'] as $user_item) {  
            if($userflag)
            {
                if($user_item['user_id'] == $request->user_id)
                { 
                    $userlist[] = ['user_id' => $user_item['user_id'], 'item_id' => $user_item["item_ids"], 'service_id' => $user_item["service_id"]];
                } 
            }else
            { 
                $userlist[] = ['user_id' => $user_item['user_id'], 'item_id' => $user_item["item_ids"], 'service_id' => $user_item["service_id"]];
            }

        }
      if(count($userlist) == 0)
      {
        return ['No Service Gettig Expired.'];
      }
        // $userlist = array(['user_id'=>142559,'item_id'=>"1555,1554"] ); //api data to be configured here with which we will recieve customer_id and item_id(s)
     
        foreach ($userlist as $userdata) {
          
            $user_respose = Quicklinks::where('customer_id', $userdata['user_id'])->first();
   
            if (($user_respose == null)) {
                $flag = 0;
                $user_respose = $this->customer_API->get_customer_by_id($userdata['user_id']);
            } else {

                $flag = 1;
                $user_respose = $user_respose->toArray();
            }

            $email = ($flag == 1) ? $user_respose['email'] : $user_respose['EmailAddress'];
           
            if (!empty($user_respose)) {
                $item = $userdata['item_id'];
                $userid = $userdata['user_id']; 
                $service_id = $userdata['service_id'];
                $stringToEncode = $item . '&&' . $userid;
                $encryptedData = base64_encode($stringToEncode);
            
         
                $now = Carbon::now();
                $perams = ["EmailAddress" => $email, "UniqueLink" => $encryptedData,"ExpireIn"=> env("SUBSCRIPTION_EXPIRE_IN_DAYS"),"EndDate"=> $now->copy()->endOfDay()->addDays(env("SUBSCRIPTION_EXPIRE_IN_DAYS"))->format('F j, Y')];
          
                $response =  Http::post(env('urlToSendUniqueLink'), $perams );
                if ($response->successful()) {
                     Quicklinks::updateOrCreate(
                        [
                            'customer_id' => $userid,
                            'service_id'=> $service_id
                        ],
                        [
                            'email' => $email,
                            'items' => $item,
                            'quicklink' => $encryptedData,
                            'status' => 1,
                        ]
                    );
                    $url[] = url('login/'.$encryptedData);
                }
                $url[] = url('login/' . $encryptedData);
               
            } 
        }
        return ['status' => 'success', 'message' => 'Unique links created', 'response' => $url];
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
    public function isValidBase64($encodedString)
    {
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
        $quicklinks = Quicklinks::where('quicklink', $request->token)->first();

        if ($this->isValidBase64($request->token) && $quicklinks !== null) {
            $data = explode("&&", base64_decode($request->token));
            $item = $data[0];
            $user = $data[1];
            $explodeitems = explode(',', $item);
            $product = [];
            $user_respose = $this->customer_API->get_customer_by_id($user);
            Session::put('userfromtoken', $user);
            Session::put('price_group', get_price_group_id_for_user_type($user_respose['CustomerType']));
            $price_group = get_price_group_id_for_user_type($user_respose['CustomerType']);
            $response = new Response();
            $response->cookie('country_code', $user_respose['PrimaryAddress']['CountryCode']); // 'country_code' is the name of the cookie
            $response->cookie('language_code', $user_respose['LanguageCode']); // 'language_code' is the name of the cookie
            $user_respose['currency_code'] = Http::get(env('nvisionu') . "/api/get_currency", ['countryCode' => $user_respose['PrimaryAddress']['CountryCode']]);

            $user_respose['currency_symbol'] = $user_respose['currency_code']['currency_symbol'];
            $user_respose['currency_code'] = $user_respose['currency_code']['currency_code'];
            $products = [];
            // $user_respose['currency_code'] = get_currency();
            foreach ($explodeitems as $items) {
                // $response = $this->getDirectProductbyId($items,$user_respose['PrimaryAddress']['CountryCode'],$user_respose['LanguageCode'],$price_group);
                $datatosend = ['items' => $items, 'CountryCode' => $user_respose['PrimaryAddress']['CountryCode'], 'LanguageCode' => $user_respose['LanguageCode'], 'price_group' => $price_group];
                $response = Http::post(env('nvisionu') . "/api/getdirectproductbyid", $datatosend);

                $response = json_decode($response, true);

                if (isset($response['productFoundForSelectedLocation']) && $response['productFoundForSelectedLocation'] && $response['regular_product_details']['status'] && $response['regular_product_details']['stock'] && $response['regular_product_details']['stock'] && $response['regular_product_details']['disabled'] == 0) {
                    $products[] = $response;
                }
                // $products[] = $this->Products->get_product_by_id($items);
            }

            $product = $products;

            if (count($product) == 0) {
                return redirect(env('collectionPage'));
            }

            return view('loginview', compact('product', 'user_respose'));
        } else {
            return redirect(env('collectionPage'));
        }
    }
  /**
     * Created By: Raju
     * Created on: 02-04-2024
     * Last Updated By: Raju
     * Last Updated on: 02-04-2024
     *
     * 
     * @return [type]
     */
    public function quickcheckoutserviceitemsapi()
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json', // Set the Content-Type header
        ])->get(env('nvisionu') . '/api/quickgetservice');
        return  $response;
    }

}
