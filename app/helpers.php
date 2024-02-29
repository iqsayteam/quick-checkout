<?php

use App\Components\Directscale\Customers;
use App\Components\Directscale\Address;
use App\Services\CommonApiCallService;
use App\Models\CountryLanguages;
use App\Models\Currency;
use App\Models\Country;
use App\Models\State;
use App\Models\ProductCategory;
use App\Models\HomepageSlider;
use App\Models\CategoryTranslations;
use App\Models\Store;
use App\Models\CustomField;
use App\Models\Product;
use Carbon\Carbon;
use App\Models\ProductLanguage;
use App\Models\ProductOptions;
use App\Models\ProductOptionData;
use App\Models\ProductPrice;
use App\Models\ProductStore;
use App\Models\CustomVat;
use App\Models\RecomendedContent;
use App\Models\Recommended; 
use App\Models\Page;
use App\Models\admin\MainCategory;
use App\Models\admin\AdditionalMenu;
use App\Models\admin\SubCategory;
use App\Models\HideEntities;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route; 
use App\Components\Directscale\Customers as CustomerComponent;

//use DB;

/**
 * Created By: Raju
 * Created On: 25-07-2023
 * Last Updated By: Raju
 * Last Updated On: 25-07-2023
 * 
 * This function will return the order tracking link
 */

function getTrackLink($order_id)
{ 
	$track_order = DB::table('track_order')->where('order_id',$order_id)->get()->toArray();
	$track_link = 'javascript:void(0)';
	if(!empty($track_order))
	{
		$track_link = $track_order[0]->track_link;
	}
	return $track_link;
}
 
 
 function getQuantityCheck($productId) {
	$qtyCheck = 1;
	$customFields = CustomField::where('product_id', $productId)->pluck('field3')->first(); 
	if($customFields != null) {
		$customFieldData = json_decode($customFields, true);
		if(isset($customFieldData['qty_check'])){
			$qtyCheck = $customFieldData['qty_check'];
		}
	}
	return $qtyCheck;
}
/* This function clears user data saved session and logout user */
function clearUserData() {
	Session::forget('user_country_code');
	Session::forget('user_language_code');
	Session::forget('user_id');
}

/* This function returns all data saved in product's custom field 3based on product id */
function getCustomField3Data ($productId) {
	$customFields = CustomField::where('product_id', $productId)->pluck('field3')->first();
	$customFieldData = "";
	if($customFields != null) {
		$customFieldData = json_decode($customFields, true);
	}
	return $customFieldData;
}
/**
 * this funciton is used for get category translation according to current langauge
 * return string
 */
function categoryTranslate($categoryName){
    if( isset( $_COOKIE['lang_slug'] ) ){
        $trans_lang = $_COOKIE['lang_slug'];
        $str1=explode("-",strtolower($trans_lang));
        $langauge_code = $str1[0];
    }else{
        $langauge_code = 'en';
    }  
	 $categoryId = ProductCategory::where('name', $categoryName)->pluck('category_id')->first();
   $category = CategoryTranslations::where(['category_id'=>$categoryId,'language_code'=>$langauge_code] )->first();
   if(isset($category['name'])){
        return $category['name'];
    }
    return $categoryName;
}


/** get product's default DS image **/
function getProductDsImage($productId) {
	$product = Product::where('product_id', $productId)->first();
		if(str_contains($product['product_sku'], '_S') && str_contains($product['default_image'], 'no_image')) {
			 $sku = str_replace('_S', '',$product['product_sku']); 
			$productDetail = Product::where('product_sku', $sku)->first();
			$image = "";
			if(isset($productDetail['default_image']) && $productDetail['default_image'] != "") {
				 $image = $productDetail['default_image'];
			}
	   } else {
		    $image = $product['default_image'];
	   }
	if($image != "" ) {
		return  env( 'DIRECT_SCALE_IMAGE_URL' ). $image;
	} else {
		return asset( 'frontend/images/product/Invision-Logo.jpg' );
	}
	
}

/** function will return autoship type based on autoshipCustomField 4 **/

function getAutoshipPackType($autoshipField4) {
	if(str_contains($autoshipField4, 10)){
		return 'bronze';
	} else if(str_contains($autoshipField4, 20)){
		return 'silver';
	} else if(str_contains($autoshipField4, 30)){
		return 'gold';
	} else if(str_contains($autoshipField4,  40)){
		return 'founders';
	} else {
		return "";
	}
}

/** fetch upgradable products **/
function fetchUpgradableProducts () {
	$userId = get_current_user_id();
    if(get_current_user_id()) {
        $customer_API = new Customers();
        $user_data = $customer_API->get_customer_stats( $userId );
        return $user_data['KitLevel'];
    } 
}

/**
 * @param $userID
 * @return array
 * This function will return all the unexpired services users have in Account
 */

 function getServicesIds($userID){
	$customer_API = new Customers();
	$allServices = $customer_API->get_customer_services( $userID );
	$serviceIdsArray = [];
	$today = Carbon::today()->format('Y-m-d');
	foreach($allServices  as $key=>$service) {
		$expirationDate = Carbon::parse($service['ExpirationDate'])->format('Y-m-d');
		if($expirationDate > $today) {
			$serviceIdsArray[] = $service['ServiceId'];
		}
	}
	return $serviceIdsArray;
}

//kushal
function getRetailProducts($packType="",$status=null, $hideFromProductList, $productGroupId, $product_categ, $orderby, $group_id=4, $orderByColumn="display_order",$callback) {
 
    if(!is_array($product_categ))
				$product_categ = [$product_categ];
      
     
	  $routeName =  Route::currentRouteName();
	  $userId = get_current_user_id();
	  $prd_fetch_info = get_product_fetch_variables();
	  $state_code = $prd_fetch_info[ 'state_code' ];
	  $country_code = $prd_fetch_info[ 'country_code' ];
	  $language_code = $prd_fetch_info[ 'language_code' ];
	  
    if($callback == true && $packType == "")
    {
        $country_code = 'us';
	    $language_code = 'en';
    }

	  if($userId != null && in_array($routeName, ['profile', 'services', 'billing-methods', 'orderHistory','manageSub'])) {
		    $customer_API = new Customers();
            $user_data = $customer_API->get_customer_by_id( $userId );
			if(isset($user_data['DefaultShippingAddress']['CountryCode'])) {
				$country_code = $user_data['DefaultShippingAddress']['CountryCode'];
			}
			if(isset($user_data['DefaultShippingAddress']['Region'])) {
				$state_code = $user_data['DefaultShippingAddress']['Region'];
			}
	  } 
	  
	  
	  $address_API = new Address();
      $region_id = $address_API->get_region_by_id( $country_code, $state_code );
      $price_currency = Country::where( [ "country_code" => $country_code ] )->pluck( 'currency_code' )->first() ;
	  if( $region_id) $region_id = $region_id;
      else $region_id = 1;

      $CurrencyCode = $price_currency;

      $order_type = 1;

    /*$access_hidden_code =  request()->access_hidden_code;
    $hide_live_entities = env('hide_live_entities');
    $nvu_access_code_for_hidden_data = env('nvu_access_code_for_hidden_data');
    $hidden_product_ids = hidden_product_ids();  
    
    $cms_hidden_product = [1];*/
    $storeId = 3;
    $products = ProductCategory::select( 'products.*','product_languages.*','product_categories.category_id','product_categories.name','product_categories.image_url','product_categories.parent_id','product_categories.has_children','product_categories.sub_categories','product_prices.*','product_stores.*','product_regions.*','product_order_types.*', 'product_prices.price  as price_included_tax' ,'product_order_types.*')
			->join( 'products','products.category_id', '=', 'product_categories.category_id' )
            ->join( 'product_languages','product_languages.product_id', '=', 'products.product_id' )
            ->join( 'product_prices','product_prices.product_id', '=', 'products.product_id' )
             ->join( 'product_stores','product_stores.price_id', '=', 'product_prices.price_id' )
             ->join( 'product_regions','product_regions.price_id', '=', 'product_prices.price_id' )
             ->join( 'product_order_types','product_order_types.price_id', '=', 'product_prices.price_id' )
             ->join('price_groups','price_groups.price_id', '=', 'product_prices.price_id')
			 ->join('custom_fields','custom_fields.product_id', '=', 'products.product_id');
		if($packType != "" && $packType != 0) {
			if($packType >= 2) {
				$storeId = 4;
			}
			$products = $products->where("custom_fields.field3->pack_type",$packType);
		} else {
			//$products = $products->where("custom_fields.field3->pack_type",'<>',1);
		}

         $products = $products->where( 'product_languages.language_code', $language_code)
            ->where( 'product_stores.store_id', $storeId)//3
			->where( 'products.disabled', 0)
			//->whereIn( 'products.hide_from_product_list', $hideFromProductListArray)
            ->where( 'products.status', $status)
            ->where( 'product_prices.start_date', '<=', date( 'Y-m-d' ) )
            ->where( 'product_prices.end_date', '>=', date( 'Y-m-d' ) )
            ->where( 'product_categories.list_products', 1 )
            ->where( 'product_order_types.order_type', $order_type)
            ->where( 'product_regions.region_id', $region_id);
            if(isset($productGroupId) && $productGroupId == ""){
                /*if($hide_live_entities){
                    if($access_hidden_code == $nvu_access_code_for_hidden_data) {
                        $products = $products->orWhereIn( 'products.product_id', $hidden_product_ids)->orWhereIn( 'products.hide_from_product_list', $cms_hidden_product);
                    } else {
                        $products = $products->whereNotIn( 'products.product_id', $hidden_product_ids);
                    }

                }else{
                    $products = $products->orWhereIn( 'products.product_id', $hidden_product_ids);
                }*/
            }
            $products = $products->where( 'product_prices.price_currency', $CurrencyCode)
            ->where( 'price_groups.group_id', $group_id);
			if($productGroupId != "" && $productGroupId != 0) { 
				 $products =  $products->where( 'products.group_id', $productGroupId)->whereNotNull('products.group_id')
						 ->where('products.group_id', '<>', 0);
			}
           if($hideFromProductList == '0') {
				 $products =  $products->where( 'products.hide_from_product_list', 0);
			}
           
			$products =  $products->groupBy('products.slug')
            ->orderBy( 'products.'.$orderByColumn,'ASC')
            ->get()->toArray();
        $country_code = get_current_country_code();
		$userID = get_current_user_id();

		foreach($products as $key => $product){
			$vat_rates = CustomVat::where("countryCode",$country_code)->where("taxClassID",$product['TaxClassId'])->first();

            $country = Country::where("country_code",$country_code)->pluck('add_tax')->first();

            if(!empty($vat_rates) && !empty($country)){
                $products[$key]['price_included_tax'] = ($vat_rates->taxRate * $product['price'])+$product['price'];
                $products[$key]['tax_rate'] = $vat_rates->taxRate * $product['price'];
            }else{
                $products[$key]['tax_rate'] = 0;
            }
			$field3 = getCustomField3Data($product['product_id']);
			if(isset($field3['bfx_check']) && $field3['bfx_check']  == 1) { //dd($field3 );
				if($userID != null) {
					$customer_API = new Customers();
					$user_data = $customer_API->get_customer_custom_fields($userID);
					if(isset($user_data['Field6']) && $user_data['Field6'] != "") {
						if(!str_ends_with($user_data['Field6'], 'BOG')){
							unset($products[$key]);
						}
					} else{
						unset($products[$key]);
					}
				} else {
					unset($products[$key]);
				}
			}

			if(isset($field3['kit_level_check']) && $field3['kit_level_check']  != "") {
				if($userID != null) {
					if( get_customer_kit_level() < $field3['kit_level_check']) {
						unset($products[$key]);
					}
				} else{
					unset($products[$key]);
				}
			}
			if(isset($field3['if_service_available']) && $field3['if_service_available']  != "") {
				if($userID != null) {
					$checkIfServicesBought = explode(',', $field3['if_service_available']);
					if(count($checkIfServicesBought) > 0) {
						$serviceIdsArray = getServicesIds($userID);
						$elementsFound = array_intersect($checkIfServicesBought, $serviceIdsArray);
						if(count($elementsFound) < count($checkIfServicesBought)){
							unset($products[$key]);
						}
					} else{
						unset($products[$key]);
					}
				} else{
					unset($products[$key]);
				}
			}
        }
 		$finalProducts = [];
      if(!empty( $products) ){
		  $finalProducts = array_merge($finalProducts, $products);
          return $finalProducts;
      }else{
          return array();
      }
}

function get_newRetailProducts($packType="",$status=null, $hideFromProductList, $productGroupId, $product_categ, $orderby, $group_id=4, $orderByColumn="display_order") {
    $routeName = Route::currentRouteName();
	  $userId = get_current_user_id();
	  $prd_fetch_info = get_product_fetch_variables();
	  $state_code = $prd_fetch_info[ 'state_code' ];
	  $country_code = $prd_fetch_info[ 'country_code' ];
	  $language_code = $prd_fetch_info[ 'language_code' ];
	  
	  if($userId != null && in_array($routeName, ['profile', 'services', 'billing-methods', 'orderHistory','manageSub'])) {
		    $customer_API = new Customers();
            $user_data = $customer_API->get_customer_by_id( $userId );
			if(isset($user_data['DefaultShippingAddress']['CountryCode'])) {
				$country_code = $user_data['DefaultShippingAddress']['CountryCode'];
			}
			if(isset($user_data['DefaultShippingAddress']['Region'])) {
				$state_code = $user_data['DefaultShippingAddress']['Region'];
			}
	  } 
	  
	  
	  $address_API = new Address();
      $region_id = $address_API->get_region_by_id( $country_code, $state_code );
      $price_currency = Country::where( [ "country_code" => $country_code ] )->pluck( 'currency_code' )->first() ;

      if( $region_id) $region_id = $region_id;
      else $region_id = 1;

      $CurrencyCode = $price_currency;

      $order_type = 1;
     
        $products = ProductCategory::select( 'products.*','product_languages.*','product_categories.category_id','product_categories.name','product_categories.image_url','product_categories.parent_id','product_categories.has_children','product_categories.sub_categories','product_prices.*','product_stores.*','product_regions.*','product_order_types.*', 'product_prices.price  as price_included_tax' ,'product_order_types.*')
			->join( 'products','products.category_id', '=', 'product_categories.category_id' )
            ->join( 'product_languages','product_languages.product_id', '=', 'products.product_id' )
            ->join( 'product_prices','product_prices.product_id', '=', 'products.product_id' )
             ->join( 'product_stores','product_stores.price_id', '=', 'product_prices.price_id' )
             ->join( 'product_regions','product_regions.price_id', '=', 'product_prices.price_id' )
             ->join( 'product_order_types','product_order_types.price_id', '=', 'product_prices.price_id' )
             ->join('price_groups','price_groups.price_id', '=', 'product_prices.price_id')
			 ->join('custom_fields','custom_fields.product_id', '=', 'products.product_id');
            
		if($packType == 1) {	 
			$products = $products->where("custom_fields.field3->pack_type",1);
		}
       
         $products = $products->where( 'product_languages.language_code', $language_code)
            ->where( 'product_stores.store_id', 3)
			->where( 'products.disabled', 0)
			 ->where( 'products.status', $status)
            ->where( 'product_prices.start_date', '<=', date( 'Y-m-d' ) )
            ->where( 'product_prices.end_date', '>=', date( 'Y-m-d' ) ) 
           // ->where( 'product_categories.list_products',1 )
            ->where( 'product_order_types.order_type', $order_type)
            ->where( 'product_regions.region_id', $region_id)
            ->where( 'product_prices.price_currency', $CurrencyCode)
            ->where( 'price_groups.group_id', $group_id);
           
			if($productGroupId != "" && $productGroupId != 0) {
				 $products =  $products->where( 'products.group_id', $productGroupId);
			} 
			if($hideFromProductList == '0') {
				 $products =  $products->where( 'products.hide_from_product_list', '0');
			}
            
			$products =  $products->groupBy('products.slug')
            ->orderBy( 'products.'.$orderByColumn,'ASC')
            ->get()->toArray();          
           
        $country_code = get_current_country_code();
        
        foreach($products as $key => $product){
            $vat_rates = CustomVat::where("countryCode",$country_code)->where("taxClassID",$product['TaxClassId'])->first();
          
            $country = Country::where("country_code",$country_code)->pluck('add_tax')->first();

            if(!empty($vat_rates) && !empty($country)){
                $products[$key]['price_included_tax'] = ($vat_rates->taxRate * $product['price'])+$product['price'];
                $products[$key]['tax_rate'] = $vat_rates->taxRate * $product['price'];
            }else{
                $products[$key]['tax_rate'] = 0;
            }
        }
      
      if(!empty( $products) ){
          return $products;
      }else{
          return array();
      }
}

//only used in account management
function getAutoshipProducts($orderby, $group_id=4, $orderByColumn="display_order", $storeId="", $autoship=1) {
	  $status = "publish";
	  $routeName = Route::currentRouteName();
	  $userId = get_current_user_id();
	  $prd_fetch_info = get_product_fetch_variables();
	  $state_code = $prd_fetch_info[ 'state_code' ];
	  $country_code = $prd_fetch_info[ 'country_code' ];
	   $language_code = $prd_fetch_info[ 'language_code' ];
	  //$hidden_product_ids = hidden_product_ids();
	  
	  if($userId != null ) {
		    $customer_API = new Customers();
            $user_data = $customer_API->get_customer_by_id( $userId );
			if(isset($user_data['DefaultShippingAddress']['CountryCode'])) {
				$country_code = $user_data['DefaultShippingAddress']['CountryCode'];
			}
			if(isset($user_data['DefaultShippingAddress']['Region'])) {
				$state_code = $user_data['DefaultShippingAddress']['Region'];
			}
	  } 

	  $address_API = new Address();
      $region_id = $address_API->get_region_by_id( $country_code, $state_code );
      $price_currency = Country::where( [ "country_code" => $country_code ] )->pluck( 'currency_code' )->first() ;

      if( $region_id) $region_id = $region_id;
      else $region_id = 1;

      $CurrencyCode = $price_currency;

      $order_type = 2;
     
        //$products = ProductCategory::select( 'products.*','product_languages.*','product_categories.category_id','product_categories.name','product_categories.image_url','product_categories.parent_id','product_categories.has_children','product_categories.sub_categories','product_prices.*','product_stores.*','product_regions.*','product_order_types.*', 'product_prices.price  as price_included_tax' ,'product_order_types.*')
        $products = ProductCategory::select( 'products.product_id','products.product_sku','products.group_id','products.default_image','products.TaxClassId','product_languages.product_name',
				'product_languages.description','product_prices.price','product_prices.cv','product_prices.qv','product_prices.price_currency',
				'product_prices.price  as price_included_tax', 'custom_fields.field3', 'product_categories.category_id', 'product_categories.name as category_name' )
			->join( 'products','products.category_id', '=', 'product_categories.category_id' )
            ->join( 'product_languages','product_languages.product_id', '=', 'products.product_id' )
            ->join( 'product_prices','product_prices.product_id', '=', 'products.product_id' )
             ->join( 'product_stores','product_stores.price_id', '=', 'product_prices.price_id' )
             ->join( 'product_regions','product_regions.price_id', '=', 'product_prices.price_id' )
             ->join( 'product_order_types','product_order_types.price_id', '=', 'product_prices.price_id' )
             ->join('price_groups','price_groups.price_id', '=', 'product_prices.price_id')
			 ->join('custom_fields','custom_fields.product_id', '=', 'products.product_id')
			->where("custom_fields.field3->autoship",'!=', '')

			->where(function($query){
				$query->where("custom_fields.field3->pack_type", null);
				$query->orWhere("custom_fields.field3->pack_type", '<>', 1);
			})
			
			->where( 'product_languages.language_code', $language_code);
			if($autoship == 1) {
				$products =  $products->where("custom_fields.field3->autoship",'!=', '0');
			}

			if($storeId != "") {
				$products =  $products->where( 'product_stores.store_id', $storeId);
			} else {
				$products =  $products->whereIn( 'product_stores.store_id', [2,3,4]);
			}
			$products =  $products->where( 'products.disabled', 0)
			->where( 'products.status', $status)
            ->where( 'product_prices.start_date', '<=', date( 'Y-m-d' ) )
            ->where( 'product_prices.end_date', '>=', date( 'Y-m-d' ) ) 
            //->whereIn( 'product_categories.category_id' )
            ->where( 'product_order_types.order_type', $order_type)
            ->where( 'product_regions.region_id', $region_id)
            ->where( 'product_prices.price_currency', $CurrencyCode)
            ->where( 'price_groups.group_id', $group_id)
			->where('products.product_sku', 'not like', "UPG%");;
			//->whereNotIn('products.product_id', $hidden_product_ids);
			$products =  $products->groupBy('products.slug')
            ->orderBy( 'products.'.$orderByColumn,'ASC')
            ->get()->toArray();          

        $country_code = get_current_country_code();
        
        foreach($products as $key => $product){
            $vat_rates = CustomVat::where("countryCode",$country_code)->where("taxClassID",$product['TaxClassId'])->first();
            $country = Country::where("country_code",$country_code)->pluck('add_tax')->first();
 
            if(!empty($vat_rates) && !empty($country)){
                $products[$key]['price_included_tax'] = ($vat_rates->taxRate * $product['price'])+$product['price'];
                $products[$key]['tax_rate'] = $vat_rates->taxRate * $product['price'];
            }else{
                $products[$key]['tax_rate'] = 0;
            }

			$field3 = getCustomField3Data($product['product_id']);
			if(isset($field3['bfx_check']) && $field3['bfx_check']  == 1) {
				$userID = get_current_user_id();
				if($userID != null) {
					$customer_API = new Customers();
					$user_data = $customer_API->get_customer_custom_fields( $userID );
					if(isset($user_data['Field6']) && $user_data['Field6'] != "") {
						if(!str_ends_with($user_data['Field6'], 'BOG')){
							unset($products[$key]);
						}
					} else{
						unset($products[$key]);
					}
				} else {
					unset($products[$key]);
				}
			}
        }
       
      if(!empty( $products) ){
          return $products;
      }else{
          return array();
      }
}


/** function is used check if we can update quantity or not*/

function checkIfUserCanUpdateQuantity ($productId) {
	$response = 1;
	$categoryId = Product::where('product_id', $productId)->pluck('category_id')->first();
	if($categoryId == config( 'global-constants.PRODUCT_CATS_MAIN.DIGITAL' ) || $categoryId == config( 'global-constants.PRODUCT_CATS_MAIN.DIGITAL_SUB' )) {
		return 0;
	} else if($categoryId == config( 'global-constants.PRODUCT_CATS_MAIN.HEALTH' )) {
		return 1;
	}
	$customFields = CustomField::where('product_id', $productId)->pluck('field3')->first();
	if($customFields != null) {
		$customFieldData = json_decode($customFields, true);
		if(isset($customFieldData['qty_update'])){
			$response= $customFieldData['qty_update'];
		}
	}
	return $response;
		
}

/* function is used to fetch customer kit level*/
function get_customer_kit_level(){
    $userId = get_current_user_id();
    if(get_current_user_id()) {
        $customer_API = new Customers();
        $user_data = $customer_API->get_customer_stats( $userId );
        return $user_data['KitLevel'];
    } 
}

function getCustomerAccountLevel(){
	$userId = get_current_user_id();
    if(get_current_user_id()) {
        $customer_API = new Customers();
        $user_data = $customer_API->get_customer_stats( $userId );
         $kitLevel = $user_data['KitLevel'];
		if($kitLevel == 10) {
			return "Bronze";
		} elseif($kitLevel == 20) {
			return "Silver";
		} elseif($kitLevel == 30) {
			return "Gold";
		} elseif($kitLevel == 40) {
			return "Founders";
		}
    } 
}


function autoshipSubscriptionCourse ($productId) {
	$customFields = CustomField::where('product_id', $productId)->pluck('field3')->first();
	$autoshipCourse = [];
	if($customFields != null) {
		$customFieldData = json_decode($customFields, true);
		if(isset($customFieldData['asc'])){
			$autoshipCourse= explode(',',$customFieldData['asc']);
		}
	}
	return $autoshipCourse;		
}

function getIncludedCourses ($productId) {
	$customFields = CustomField::where('product_id', $productId)->pluck('field3')->first();
	$includedCourse = [];
	if($customFields != null) {
		$customFieldData = json_decode($customFields, true);
		if(isset($customFieldData['included'])){
			$includedCourse= explode(',',$customFieldData['included']);
		}
	}
	return $includedCourse;		
}

function getOneMonthCoursesSubscription ($productId) {
	$customFields = CustomField::where('product_id', $productId)->pluck('field6')->first();
	$included_one_month_subscription = [];
	if($customFields != null) {
		$customFieldData = json_decode($customFields, true);
		if(isset($customFieldData['included_one_month_subscription'])){
			$included_one_month_subscription= explode(',',$customFieldData['included_one_month_subscription']);
		}
	}
	return $included_one_month_subscription;
}

//fetch options from customfield using productId
function getOptions($productId) {
	$lang_code = get_current_language_code();
	$customFields = CustomField::where('product_id', $productId)->pluck('field3')->first(); 
	$data = [];
	if($customFields != null) {
		$customFieldData = json_decode($customFields, true);
		$data[ 'options' ] = [];
		$data['selectable_option_count'] = 0;
		if(isset($customFieldData['options'])){
			$optionIds = explode(',',$customFieldData['options']);
			$included_products = ProductLanguage::whereIn('product_id', $optionIds)->where('language_code', $lang_code)
					->whereNotNull('product_name')->where('product_name', '<>','')->where('product_name', 'not like','DLP%')
					->get()->pluck('product_name', 'product_id');
			$selectableOptions = [];
			foreach($included_products as $included_productId=>$included_product) {
				/*$autoshipId = getAutoshipId($included_productId);
				if($autoshipId != null && $autoshipId != 0) {
					$selectableOptions[$autoshipId] = $included_product;
				}*/
				$selectableOptions[$included_productId] = $included_product;
			}
			$data[ 'options' ] = $selectableOptions;
			$data['field6OptionsData'] = CustomField::whereIn('product_id', $optionIds)->pluck('field6', 'product_id')->toArray();
		}
		if(isset($customFieldData['qty'])) {
			$data['selectable_option_count'] = $customFieldData['qty'];
		}
		$data['field2Data'] = getCustomField2Data($productId);

	}
		return $data;
}

/* This function returns all data saved in product's custom field 2 based on product id */
function getCustomField2Data ($productId) {
	$customFields = CustomField::where('product_id', $productId)->pluck('field2')->first();
	$customFieldData = "";
	if($customFields != null) {
		$customFieldData = json_decode($customFields, true);
	}
	return $customFieldData;
}

/* This function returns all data saved in product's custom field 6 based on product id */
function getCustomField6Data ($productId) {
	$customFields = CustomField::where('product_id', $productId)->pluck('field6')->first();
	$customFieldData = "";
	if($customFields != null) {
		$customFieldData = json_decode($customFields, true);
	}
	return $customFieldData;
}
//fetch addon_autoships from customfield using productId
function getAddonAutoships($productId) {
	$customFields = CustomField::where('product_id', $productId)->pluck('field3')->first(); 
	$addonAutoships = [];
	if($customFields != null) {
		$customFieldData = json_decode($customFields, true);
		if(isset($customFieldData['addon_autoships'])){
			$addonAutoshipIds = explode(',',$customFieldData['addon_autoships']);
		}
	}
	return $addonAutoships;
}

/** function to fetch pack level**/

function getAutoshipPackLevel($autoshipId) { 
	$customField4  = "";
	$autoshipField = CustomField::where('product_id', $autoshipId)->pluck('field3')->first();
	if($autoshipField != null) {
		$autoshipFieldData = json_decode($autoshipField, true);
		if(isset($autoshipFieldData['pack_level'])) {
			$customField4 = $autoshipFieldData['pack_level'];
		}
	}
	return $customField4;
}

//fetch options from customfield using serviceId
function getOptionsByServiceId($serviceId) {
	$lang_code = get_current_language_code();
	$customFields = CustomField::where('field5', $serviceId)->orderBy('product_id', 'ASC')->pluck('field3')->first(); 
	$data = [];
	if($customFields != null) {
		$customFieldData = json_decode($customFields, true);
		if(isset($customFieldData['options'])){
			$optionIds = explode(',',$customFieldData['options']);
			$data[ 'options' ]  = ProductLanguage::whereIn('product_id', $optionIds)->where('language_code', $lang_code)->whereNotNull('product_name')->where('product_name', '<>','')->where('product_name', 'not like','DLP%')->get()->pluck('product_name', 'product_id');
			$data['selectable_option_count'] = $customFieldData['qty']; 
		}
	}
	return $data;
}
//fetch autoship ID from custom fields
function getAutoshipId($productId) {
	if($productId == 10) {
		return 10;
	}
	$customFields = CustomField::where('product_id', $productId)->pluck('field3')->first(); 
	$autoship = 0;
	if($customFields != null) {
		$customFieldData = json_decode($customFields, true);
		if(isset($customFieldData['autoship'])){
			$autoship = $customFieldData['autoship'];
		}
	}
	return $autoship;
}

//fetch autoship ID from custom fields
function checkIsBoughtCheckAdded($productId) {
	$customFields = CustomField::where('product_id', $productId)->pluck('field3')->first();
	$checkIfBought = 0;
	if($customFields != null) {
		$customFieldData = json_decode($customFields, true);
		if(isset($customFieldData['check_if_bought'])){
			$checkIfBought = $customFieldData['check_if_bought'];
		}
	}
	return $checkIfBought;
}

//fetch serviceId from custom fields
function getServiceId($productId) {
	return $serviceId = CustomField::where('product_id', $productId)->pluck('field5')->first(); 
}

//get field5 data
function getField5Data($productId) {
	return $data = CustomField::where('product_id', $productId)->pluck('field5')->first();
}

// get item id based on product's custom field 5
function getItemIdAssociatedWithServiceId($serviceId) {
	$productId = CustomField::where('field5', $serviceId)->pluck('product_id')->first(); 
	if($productId == null) {
		return 0;
	}
	return $productId;
}

function getMaxPhysicalProductQuantity() {
	return config( 'global-constants.DEFAULT_PARAMS.MAXQTY');
}

function pa( $array ){
    echo "<pre>";
    print_r($array);
    echo "<pre>";
}

function read_json($file_name = ''){
    $pointer = base_path('resources/pages_jsons/'.$file_name);
    if(file_exists($pointer)){ 
        $data = json_decode(file_get_contents($pointer));
    }else{
        $data = null;
    }
    return $data; 
}



function getCountryCookies(){
    $value = 'us';
    if(!isset($_COOKIE['region'])){
        // setcookie('region', 'us', time() + (86400 * 30), "/"); 
       // $ip_info = ip_info();
        $value = 'us';
        // echo "<pre>";
        // print_r($ip_info);
        // echo "</pre>";
        // die();
        // $Country_code = strtolower( $ip_info['country_code'] );
        // setcookie('region', $Country_code, time() + (86400 * 30), "/"); 

        // return  $Country_code;  
    }
    if(isset($_COOKIE['region'])){
        $value = $_COOKIE['region'];
    }
    return  $value;     
}

function getLanguageByCookies(){
    
    $value='en';
    if(!isset($_COOKIE['language'])){
        setcookie('language', 'en', time() + (86400 * 30), "/"); 
    }
    if(isset($_COOKIE['language'])){
        $value=$_COOKIE['language'];
    }
    
    return  $value;

    
}

function fetchCountry(){
    $data =Country::orderBy('country_name', 'desc')->get();
    return $data;
}


/**
 * Created By: kushal
 * Created on: 12-apr-2023
 * Last Updated by: kushal
 * 
 * This function is fetching all country data
 * with desc order with selected county name.
 * 
 * @param mixed $defaul_country_code is country code
 */
function fetchCountryData($defaul_country_code){
    $data =Country::where('shop','!=','0')->orderByRaw("FIELD(country_code , '".$defaul_country_code."') DESC")->get();
    return $data;
}

function fetchCountryLanguage(){
    // $country_code = getCountryCookies();
    // $country_detail = Activecountries::where(["iso2"=>$country_code])->first();
    // if(isset($country_detail->country_id))
    //  {
    //     $data = Countrylanguage::where(["country_id"=>$country_detail->country_id])->get();
    //  }
    //  else
    //   {
    //     $data = [];
    //   }
    
    $data = array();
    return $data;

}

function languagescountries($country_code='us'){
    
    $data = CountryLanguages::join( 'countries', 'country_languages.country_code', '=', 'countries.country_code' )
        ->where( ['country_languages.country_code'=>$country_code ] )
        ->get( ['country_languages.*'] );
    return $data;
    
}

/**
 * This function is used to test if there is lang_country in url
 * @param string url segment
 * @return
 */
function is_lang_slug($segment){
        $co_code=explode("-",$segment);
        if(isset($co_code[1]) and strlen($segment)==5){
            return true;
        }else{
            return false;
        }
}

   
   function getCountrySymbol($currency){
    
    $currency_symbol = Currency::where(["currency_code"=>$currency])->pluck('symbol')->first(); 
  
   return $currency_symbol;
   }

   /**
 * Get current Customer ID from session
 * @param request | null
 * @return $user_id | integer
 */
function get_current_user_id()
{
	 //return 135564; //110729
	 $user_id = session()->get('user_id');
	
    if($user_id){
        return $user_id;
    }else{
        return false;
    }
}

/**
 * get_price_group_id_for_user_type check customer type
 * @param user_type set defaul customer type
 * return customer id
 */
function get_price_group_id_for_user_type($user_type = 4)
{
    $price_groups =  array('1'=>1,'2'=>2,'4'=>4);
    if (array_key_exists($user_type,$price_groups))
    {
        $price_groups_id = $price_groups[$user_type];
    }
    else
    {
        $price_groups_id = config( 'global-constants.PRODUCT_FETCH.DEFAULT_CUSTOMER_TYPE_ID' ); 
    }    
    return $price_groups_id;
}

/**
 * get_logged_in_customer_type method for gettting price id
 * @return price_group id
 */
function get_logged_in_customer_type(){
        if(get_current_user_id()) {
            $userId = get_current_user_id();
            $customer_API = new Customers();
            $user_data = $customer_API->get_customer_by_id( $userId );
            $user_type = $user_data['CustomerType'];
        } else {
            $user_type = config( 'global-constants.PRODUCT_FETCH.DEFAULT_CUSTOMER_TYPE_ID' ); // default customer type
        }

        $price_group = get_price_group_id_for_user_type($user_type);
        return $price_group;
    }


/**
 * check if user have live stream
 * @return boolean
 */
function check_customer_have_live_stream(){
	$serviceId = config( 'global-constants.DEFAULT_PARAMS.SERVICEID' );
	if(get_current_user_id()) {
		
		$userId = get_current_user_id();
		$customer_API = new Customers();
		$searchServiceId = 0;
		$user_services = $customer_API->get_customer_services( $userId );
		if(count($user_services) == 0) {
			return $searchServiceId;
		}

		foreach($user_services as $user_service) {
			if($user_service['ServiceId'] == $serviceId) {
				$searchServiceId = 1;
			}
		}
		return $searchServiceId;
	}
	elseif(session()->get('guest_user_id') != '')
	{ 
		$userId = session()->get('guest_user_id');
		Session::forget('guest_user_id');
		$customer_API = new Customers();
		$searchServiceId = 0;
		$user_services = $customer_API->get_customer_services( $userId );
		if(count($user_services) == 0) {
			return $searchServiceId;
		}

		foreach($user_services as $user_service) {
			if($user_service['ServiceId'] == $serviceId) {
				$searchServiceId = 1;
			}
		}
		
		return $searchServiceId;
	}
	return 0;
}

/**
 * Get Currency Code based on CountryCode
 * @param $countryCode | string
 * @return $currencyCode | string
 */
function get_currency($countryCode=""){
	if($countryCode == "") {
		$countryCode = get_current_country_code();
	} 
    if($countryCode){
        $currencyCode = Country::Where('country_code', $countryCode)->first();
        if(isset($currencyCode->currency_code))
            return $currencyCode->currency_code;
    }else{
        return false;
    }
}

/*
* Retrieves the country code of the user from the cookies, if it is present,
* and returns it to the caller.
* If it is not present, a default country code of US is set and then stored in
* the cookies. The default country, US, is then returned to the caller.
* @var $_COOKIE['country_code']   the two uppercase character long country code
* retrieved from the cookies.
* @return string   the two uppercase character long country code returned in the form of a string.
*/
function get_current_country_code(){
    // if(session()->get('user_id')){
    //     $_COOKIE['country_code'] = session()->get( 'user_country_code' );
    // }
	if(request()->route('lang_slug')) {
		$lang_slug = request()->route('lang_slug');
		$countryLang = explode('-', strtolower($lang_slug));
	}
	if(isset($countryLang[1])) {
		set_countryCode($countryLang[1]);
        $_COOKIE['country_code'] = $countryLang[1];
        return $_COOKIE['country_code'];
	}elseif(isset($_COOKIE['country_code'])){
        return $_COOKIE['country_code'];
    }else{
        set_countryCode();
        $_COOKIE['country_code'] = 'us';
        return $_COOKIE['country_code'];
    }
}

/**
 * Set Country code in Cookie based on selected Country if there is no selected Country by default it set to US
 * @param $country_code string | default = us
* @return string the two uppercase character long language code returned in the form of a string.
 */
function set_countryCode($country_code = 'us'){
    if($country_code){
        setcookie('country_code', $country_code, time() + (86400 * 30), "/");
        return true;
    }else{
        return false;
    }
}

/**
 * Get default state code based on CountryCode
 * @param $countryCode | string
 * @return $stateCode | string
 */
function get_default_state($countryCode=""){
   
	if($countryCode == "") {
		$countryCode = get_current_country_code();
	}
    if($countryCode){
        if($stateCode = State::Where('country_code', strtoupper($countryCode))->first()->state_code){
            return $stateCode;
        }
    }else{
        $stateCode = State::Where('country_code', 'US')->first()->state_code;
        return $stateCode;
    }
}
/***
             * Created by: Raju
             * Created on: 12-02-2024
             * Last Updated by: Raju
             * Last Updated on: 12-02-2024
             *
             * This functions is used to check if user is allowed to buy visionlifestyle or not 
             *
             * @Parameters: productIds(Array), currencyCode(String)
             * @return boolean value
             */
            function SearchUpseartUser()
            {
                $customer_API = new Customers();
                $userId = get_current_user_id();
                if($userId == false)
                {
                    return 2;
                }
                
        $customer_services =  $customer_API->get_customer_services($userId);
        
        if(array_search(config('global-constants.NUMBERS.29'), array_column($customer_services, 'ServiceId')) !== false) {
            $keys25 = array_keys(array_column($customer_services, 'ServiceId'), config('global-constants.NUMBERS.29'));
            $ExpirationDate = $customer_services[$keys25[0]]['ExpirationDate'];  
            }else {  
                return 0;
           }
            $expirationDateTime = Carbon::parse($ExpirationDate); 
           // Check if $expirationDate is greater than the 3rd of the current month
           $currentDate = Carbon::now();
            if (!$expirationDateTime->greaterThan($currentDate->startOfMonth()->addDays(2))) {
            
                $expiered = 1;
            } else {
            
                $expiered = 0;
            }
        
        
            if($expirationDateTime->isPast() && !$expirationDateTime->isSameDay(Carbon::now()) && $expiered ) { 
                return 1;
            } 
            }
/**
 * Get current User Regions from Directsccale API
 * Just pass User ID to this function.
 * @param $country_code | string
 * @param $state_code | string
 * @return integer | User region
 */
function get_region_id($country_code, $state_code)
{
    if($country_code && $state_code)
    {   
        $Address_api = new Address();
        $region_id = $Address_api->get_region_by_id($country_code, $state_code);
        if(!isset($region_id['isError'])){
            return $region_id;
        }else {
            return array();
        }
    }else{
        return array();
    }
}

/*
* Retrieves the language code of the user from the cookies, if it is present,
* and returns it to the caller.
* If it is not present, a default language code of EN is set and then stored in
* the cookies. The default language, EN, is then returned to the caller.
* @var $_COOKIE['language_code']   the two uppercase character long language code
* retrieved from the cookies.
* @return string the two uppercase character long language code returned in the form of a string.
*/
function get_current_language_code(){
    if(isset($_COOKIE['language_code'])){
        return $_COOKIE['language_code'];
    }else{
        set_langaugeCode();
        $_COOKIE['language_code'] = 'en';
        return $_COOKIE['language_code'];
    }
}
/**
 * This function returns country code if listed
 * @param  [type] $country_code we want to check
 * @return [boolean] true if listed
 */
function if_country_listed($country_code){


	//'enroll' => true,
    $check_if_listed = Country::select('country_code')->where([ 'shop'=>true,'country_code' => $country_code])->get();


    if (sizeof($check_if_listed)>0) {
        return true;
    }else{
        return false;
    }

}
/**
 * Set Language code in Cookie based on selected language if there is no selected laguage by default it set to EN
 * @param $language_code string | default = en
 * @return bool
 */
function set_langaugeCode($langauge_code = 'en'){
    if($langauge_code){
         setcookie('language_code', $langauge_code, time() + (86400 * 30), "/");
         return true;
     }else{
         return false;
     }
 }
 
/**
 * Get all states from database based on country code
 * @param $country_code | string
 * @return array | States array
 */
function get_states($country_code = "US"){
    $states = State::where('country_code', strtoupper($country_code))->get();
    if(!empty($states)){
        return $states;
    }else{ 
        return false;
    }
}


/**
 * Get Countries from Country table where enroll is true
 * @param null
 * @return array
 */
function active_country_codes(){
     $countries  = Country::select('country_code')->where(['enroll' => true])->get();

     if(!empty($countries))
     {
        return $countries;
     }else{
        return null;
     }
}

/**
 * Createsd By: GouravM
 * Created on: 6-dec
 * Last Updated By: GouravM
 * Last Updated On: 6-dec
 * 
 * This function is copied from enrollment helper
 * Return the product options data
 * @param $page_id integer
 * @return array
**/
function getProductOptionsData($product_id = null)
 {
    $data = [];
    $ProductOptions = ProductOptions::where(["product_id"=>$product_id])->select('option_id')->get();
    foreach($ProductOptions as $ProductOption)
    {
        $ProductOption->optionData = ProductOptionData::where(["option_id"=>$ProductOption->option_id])->get();
        $data[] = $ProductOption;
    }

    if(!empty($data))
     {
        return $data;
     }
     else
      {
       return array();
      }
 }

/**
 * Created By: GouravM
 * Create On: 16-dec-2022
 * Last Updated By: GouravM
 * Last Updated OnL 16-dec-2022
 * 
 * This function uses to get the information required for fetching the products base on user logged in state.
 * 
 * @return [array]
 */
function get_product_fetch_variables(){

    $user_id = session()->get( 'user_id' );

    

        $country_code = get_current_country_code();
        $language_code = get_current_language_code();
        $state_code = get_default_state();

        return array(
            'country_code' => $country_code,
            'language_code' => $language_code,
            'state_code' => $state_code,
        );
    // }
}

/**
 * Get all Available Languages based on Country Code
 * if countrycode is not present it return false
 * @param $country_code| string
 * @return array | Available languages
 */
function get_languages($country_code = 'us'){
    if(!empty($country_code)){
        $country_languages = CountryLanguages::where('country_code', $country_code)->get();
        if($country_languages->count()){
            return $country_languages;
        }else{
            return false;
        }
    }else{
        return false;
    }
}

/**
 * Get Distributor (Sponsor) Distributor details from Distributor Cookie if the Distributor is set
 * @param request| call
 * @return array | Distributor details
 */
function get_distributor(){
    $distributor = json_decode(Cookie::get('distributor'));
	if(!$distributor) {
		$distributor_session = json_decode(json_encode(Session::get('distributorDetailsSession')));
		if (isset($distributor_session) && $distributor_session != 'empty' && $distributor_session != null) {
			$distributor = $distributor_session;
		}
	}

	if(!empty($distributor)){
		return $distributor;
	}else{
		return false;
	}
}

/**
 * multipleCategories use of that method for checking cate id for packs products
 * @return return array of cat_id
 */
function CategoriesId(){
    $cat = array(21, 24);
    return $cat;
}

/**
 * productPackWithPredefinedQuantity use of that method for predefined product quntity
 * @return return array of predefined quantity
 */
function productPackWithPredefinedQuantity(){
    $packProduct = array(
                        array("label"=>"3-Pack", "quntity"=>"1"),
                        array("label"=>"6-Pack", "quntity"=>"2"),
                        array("label"=>"12-Pack", "quntity"=>"4")
                    );

    return $packProduct;
}

/**
 * subscriptionProductByCategoriesId use of that function for putting multiple categories in single array
 * @return return array of categories id
 */
function subscriptionProductByCategoriesId(){
    $catgories_id = array(21);
    return $catgories_id;
}

/**
 * use of that function set defaul country code
 * @return return default country code 
 */
function getDefaultCountryCode($language="us"){
    
    if($language=="fr")
    {
        $country_code = config( 'global-constants.DEFAULT_PARAMS.COUNTRY_CODE_FR' );
    }else
    {
        $country_code = config( 'global-constants.DEFAULT_PARAMS.COUNTRY_CODE' );  
    }
 
    return $country_code;
    
}
/**
 * use of that function set defaul Language
 * @return return default Language
 */
function getDefaultLanguage($country_code){
	$temp=CountryLanguages::where(['country_code'=>$country_code,'default_lang'=>1])->first();
    
	if(isset($temp)){
		return $temp->language_code;
	}elseif($country_code =='fr')
    {
        return config( 'global-constants.DEFAULT_PARAMS.LANGUAGE_CODE_FR' );
    }
    else{
		return config( 'global-constants.DEFAULT_PARAMS.LANGUAGE_CODE' );
	}

}

 

    
    /**
 * validCountryForProductPurchase use of that function valid country code for purchasing products\
 * @return array of country code
 */
 function validCountryForProductPurchase($current_country ){
   
    $login_country_code = session()->get( 'user_country_code' );
    $valid_countries = array('us',$login_country_code);
    if(in_array($current_country,$valid_countries)) {
        return true;
    }else{
        return false;
    }
 
 } 

 /**
  * shareBagLinkAndIcon use of that function for return share bag link also link
  * @return return linkandIcon;
  */

  function shareBagLinkAndIcon(){
    $linkandIcons = array(
                        array('class'=>'whtsapp-bg','mobile-link'=>'whatsapp://send?text=','icon'=>"frontend/images/pixelated_whatsapp.svg",'web-link'=>'https://web.whatsapp.com/send?text=','data-action'=>'share/whatsapp/share'),
                        //array('class'=>'wechat-bg','mobile-link'=>'weixin://dl/chat','icon'=>'frontend/images/wechat.png','web-link'=>'weixin://dl/chat','data-action'=>''),
                        array('class'=>'mail-bg','mobile-link'=>'mailto:?body=','icon'=>'frontend/images/pixelated_email.svg','web-link'=>'mailto:?body=','data-action'=>''),
                       // array('class'=>'viber-bg','mobile-link'=>'viber://forward?text=','icon'=>'frontend/images/viber.png','web-link'=>'viber://forward?text=','data-action'=>''),
                        array('class'=>'twitter-bg','mobile-link'=>'https://twitter.com/intent/tweet?url=','icon'=>'frontend/images/pixelated_twitter.svg','web-link'=>'https://twitter.com/intent/tweet?url=','data-action'=>''),
                        array('class'=>'telegram-bg','mobile-link'=>'tg://msg?text=','icon'=>'frontend/images/pixelated_telegram.svg','web-link'=>'tg://msg?text=','data-action'=>'share/whatsapp/share'),
                        array('class'=>'messanger-bg','mobile-link'=>'fb-messenger://share/?link=','icon'=>'frontend/images/pixelated_facebook_messenger.svg','web-link'=>'https://www.facebook.com/sharer.php?u=','data-action'=>''),
                        array('class'=>'facebook-bg','mobile-link'=>'https://www.facebook.com/sharer.php?u=','icon'=>'frontend/images/pixelated_facebook.svg','web-link'=>'https://www.facebook.com/sharer.php?u=','data-action'=>'')
                    );
    return $linkandIcons;
  }


/**
 * autoshipFrequency use of that method for show autoshipe type
 * @return return array
 */
  function autoshipFrequency() {
    return $frequencies = [
        0=>'Weekly',
        1=>'EveryTwoWeeks',
        2=>'Monthly',
        3=>'BiMonthly',
        4=>'TriMonthly',
        5=>'TwiceAYear',
        6=>'Yearly',
        7=>'Every4weeks',
        8=>'Every6Weeks',
        9=>'Every8Weeks',
        10=>'Every12Weeks'
    ];
    
}

/**
 * MapShareCartProducts use of that method for assign cart index value
 * @return return array of index value
 */
function MapShareCartProducts() {
    $myObj =  new stdClass();
    $myObj ->number_of_chunks = 10;
    $myObj ->voucher_amount = 9;
    $myObj ->product_id = 0;
    $myObj ->quantity = 1;
    $myObj ->autoships = 2;
    $myObj ->is_membership = 3;
    $myObj ->product_type = 4;
    $myObj ->order_type = 5;
    $myObj ->included_products = 6;
    $myObj ->service_ids = 7;
    $myObj ->is_pack = 8;
   
    return $myObj;
  }


  /**
   * Use of that method changed the currency postion
   * @param currency parameter get the currency code
   * @param price get the product price
   * @return return the product price and currency symbol
   */
  function formatCurrency($currency,$price) {
   
    $currency = strtoupper($currency);
    $currency_positions = array(
        'EUR' => 1,
        'MAD' => 2, 
        'USD' => 1,
        'GBP' => 1,
        'CHF' => 1
    ); 
    if(empty($price)){
        return '';
    }
    if(array_key_exists($currency, $currency_positions)) {
        if($currency_positions[$currency] == 0) {
            return number_format($price,2);
        }
        else if($currency_positions[$currency] == 2) { 
            return number_format($price,2). getCountrySymbol($currency);
        }
        else {
            return getCountrySymbol($currency). number_format($price,2);
        }
    } else {
        return getCountrySymbol($currency).number_format($price,2);
    }
}


    /**
     * Use of generateShareCartUrl function for generate share url
     * @param my_cart_items it's all product data which we need to share
     * @return share_cart_params return share url string
     */

    function generateShareCartUrl($my_cart_items){
        $share_cart_params ="";
        foreach($my_cart_items as $cart_item){
            $product_param_array = array();
            $product_param_array[MapShareCartProducts()->product_id] = $cart_item['product_id'];
            $product_param_array[MapShareCartProducts()->quantity] = $cart_item['quantity'];
            $product_param_array[MapShareCartProducts()->autoships] = $cart_item['autoships'];
            $product_param_array[MapShareCartProducts()->is_membership] = $cart_item['is_membership'];
            $product_param_array[MapShareCartProducts()->product_type] = $cart_item['product_type'];
            $product_param_array[MapShareCartProducts()->order_type] = $cart_item['order_type'];

            if( empty($cart_item['included_products'])){
                $product_param_array[MapShareCartProducts()->included_products] = 0;
            }
            elseif(is_array($cart_item['included_products'])){
               
                $product_param_array[MapShareCartProducts()->included_products] = implode(';',$cart_item['included_products']);
            }else{
               
                $product_param_array[MapShareCartProducts()->included_products] = str_replace(',',';',$cart_item['included_products']);
            }

            if( empty($cart_item['service_ids'])){
                $product_param_array[MapShareCartProducts()->service_ids] = 0;
            }
           elseif(is_array($cart_item['service_ids'])){
                $product_param_array[MapShareCartProducts()->service_ids] =  implode(';',$cart_item['service_ids']);
            }else{
                $product_param_array[MapShareCartProducts()->service_ids] =  str_replace(',',';',$cart_item['service_ids']);
            }
			$product_param_array[MapShareCartProducts()->is_pack] = 0;
			if( isset($cart_item['is_pack'])){
                $product_param_array[MapShareCartProducts()->is_pack] = $cart_item['is_pack'];
            }
			$product_param_array[MapShareCartProducts()->voucher_amount] = 0;
			if(isset($cart_item['voucher_amount'])) {
				$product_param_array[MapShareCartProducts()->voucher_amount] = $cart_item['voucher_amount'];
			}
            $product_param_string = implode(':',$product_param_array);
            $share_cart_params .= $product_param_string."-";
        }
      
        $share_cart_params = rtrim($share_cart_params, '-');
        return $share_cart_params;
    }


    /**
     * Use of that function for getting country code
     * @param slug
     * @return return country code
     */
    function get_country_from_slug($slug) {
        $country_code_check = explode("-",strtolower($slug));
        $country = null;
        if(isset($country_code_check[1])){
            if(if_country_listed($country_code_check[1])){
                $country = $country_code_check[1];
            }
        }
        return $country;
    }

    /**
     * Use of that get_language function for get lanuage code
     * @param slug
     * @return return language code
     */
    function get_language_from_slug($slug){
        $language_code_check = explode("-",strtolower($slug));
      
        if(isset($language_code_check[0])){
            $lang = $language_code_check[0]; 
        }else{
			if(isset($language_code_check[1])){
				$lang = getDefaultLanguage($language_code_check[1]);
			}else{
				$lang = 'en';
			}

        }
        return $lang;
    }


    /**
     * Use of that getValidlanguagesForCountry method return array of language code
     * @param country
     * @return array of language codes
     */

    function getValidlanguagesForCountry($country = null ) {
        
        return array("en","es","it","fr","de","ru");
    }

 
  
 
    function get_all_retail_products($packType,$status=null, $hideFromProductList, $productGroupId, $product_categ, $orderby, $group_id=4, $orderByColumn="display_order"){
		return get_newRetailProducts($packType, $status, $hideFromProductList, $productGroupId, $product_categ, $orderby, $group_id, $orderByColumn);
    }
 
    function get_product_withSubCat($subCat_id)
    {
      return  $sub_cat_name = ProductCategory::where('category_id',$subCat_id)->pluck('name');
    }
	
	function getCategories($catIds)
    { 
		if(!is_array($catIds)) {
			$catIds = explode(',', $catIds);
		}
      return  $categories = ProductCategory::whereIn('category_id',$catIds)->get()->pluck('name','id')->toArray();
    }

    /***
	 * Created by: Aman
	 * Last Updated by: Aman
	 * Last Updated on: 2013-Oct-05
	 *
	 * This functions will used to cheked is the product available for wholesale store or not .
	 *
     * @Parameters: productIds(Array), currencyCode(String)
	 * @return boolean value
	 */
    function getStoreIDbyProductID($productIds, $currencyCode) {
        $wholeSaleStoreID  = config( 'global-constants.PRODUCT_FETCH.WHOLESALE_STORE_ID' );
        $productIdsData = Product::whereIn('product_id', $productIds)->where('status', "publish")->where('disabled', 0)->get()->pluck('product_id')->toArray();
        if(!empty($productIdsData)){
            $currencyCode = strtoupper($currencyCode);
            $priceIDs = ProductPrice::whereIn('product_id', $productIdsData)->where('price_currency', $currencyCode)->get()->pluck('price_id')->toArray();
            if(!empty($priceIDs)){
                $storeIDs = ProductStore::whereIn('price_id', $priceIDs)->where('store_id', $wholeSaleStoreID)->get()->pluck('store_id')->toArray();
                if(!empty($storeIDs)){ return true; } else return false; 
            } else  return false; 
        } else  return false; 
    }

      /**
     * Retrieves the URL of the translation file for the Nexio iframe.
     *
     * @param string $languageCode The language code, e.g., "en", "fr", "es", etc.
     * @return string The URL of the translation file.
     * @throws InvalidArgumentException If an invalid language code is provided.
     */
    function getNexioTranslationFileUrl($languageCode)
    {
        $supportedLanguages = ["en", "fr", "es","it"]; // List of supported language codes.

        // Check if the provided language code is valid.
        if (!in_array($languageCode, $supportedLanguages)) {
            throw new InvalidArgumentException("Invalid language code provided.");
        }

        // Build the file name based on the language code.
        $translation_file_url = "https://customerassets.nexiopay.com/nvisionu/{$languageCode}.json";

        return $translation_file_url;
    }

      /**
     * Retrieves the translated link for the Nexio iframe based on the current language.
     *
     * @return string The translated link.
     * @throws RuntimeException If the current language code cannot be determined.
     */
    function getNexioTranslatedLink()
    {
        // Replace this with the appropriate method to get the current language code.
        $languageCode = get_current_language_code(); // Assume this function returns the language code.

        // Get the URL of the translation file based on the language code.
        try {
            $translatedFileUrl = getNexioTranslationFileUrl($languageCode);
        } catch (InvalidArgumentException $e) {
            // Handle the invalid language code error gracefully.
            // You can log the error, provide a fallback language, or take other appropriate actions.
            $translatedFileUrl = getNexioTranslationFileUrl("en"); // Fallback to English.
        }

        // Return the URL of the translated file.
        return $translatedFileUrl;
    }

    /**
     * Added by: Aman
     * Last update date: 2023-OCT-20
     * This code of block will return the curent user LanguageCode for Profile module
     * 
     * */ 
    function get_current_user_language_code_for_profile(){
        $userId = get_current_user_id();
        if(isset($userId)){
            $customer_API = new Customers();
            $user_data = $customer_API->get_customer_by_id( $userId );
            return $user_data['LanguageCode']; 
        } else {
            return 'us';
        }
    }
    /**
     * Added by: Raju
     * Last update date: 2023-12-12
     * this returns customers custom fields
     * 
     * */ 

     function customers_custom_fields($user_id)
     {
         $customer_API = new CustomerComponent();
         $user_respose =   $customer_API->get_customer_custom_fields($user_id);
  
         if(isset( $user_respose['Field1'] ))
         { 
             $Field1 =  $user_respose['Field1'];
   
         } 
         return  $Field1 ;
     } 
    /**
 * use of that function set defaul Language
 * @return return default Language
 */
function subscriptionExpiered($user_id){
    $customer_API = new CustomerComponent();
    $user_respose =   $customer_API->get_customer_services($user_id);
    $keys25 = '';
    $ExpirationDate = '';
    if(array_search(29, array_column($user_respose, 'ServiceId')) !== false) {
    $keys25 = array_keys(array_column($user_respose, 'ServiceId'), 29 );
    $ExpirationDate = $user_respose[$keys25[0]]['ExpirationDate'];  
    $expirationDateTime = Carbon::parse($ExpirationDate); 
    session(['expirationDateTime' => $expirationDateTime]);
    if ($expirationDateTime->isPast() && !$expirationDateTime->isSameDay(Carbon::now())) { 
        $response = array('isShow'=>false,"ExpirationDate"=>$expirationDateTime);
        return false   ;
    }else
    {
        $response = array('isShow'=>true,"ExpirationDate"=>$expirationDateTime);
        return true;
    } 
    } else {  
        $response = array('isShow'=>false,"ExpirationDate"=>null);
    return $response;
    }

}

 /**
 * use of that function is to show warning in frontend if it is expiering
 * @return return default Language
 */
function is_expire(){ 
    $dateofexpiery = session('expirationDateTime'); 
    $currentDate = \Carbon\Carbon::now();
	//$currentDate =	Carbon::parse('2023-12-03');
    $fourthDayOfCurrentMonth = \Carbon\Carbon::create($currentDate->year, $currentDate->month, 4, 0, 0, 0); 
    $fourthDayOfNextMonth = $fourthDayOfCurrentMonth->copy()->addMonth(); 
    if($currentDate->greaterThan($dateofexpiery))
    {
        if($dateofexpiery->isSameMonth($fourthDayOfCurrentMonth))
        { 
            

 
            $response = config('global-constants.TRAVLE.MAKE_PAYMENT');
            
        }
		else
		{  
           $monthsDifference = $dateofexpiery->diffInMonths($currentDate) + 1;  
			if($currentDate->lessThan($fourthDayOfCurrentMonth) && $monthsDifference <=1)
			{
				$response = config('global-constants.TRAVLE.MAKE_PAYMENT');
                
			}
			else
			{
				$response = config('global-constants.TRAVLE.CONTACT_SUPPORT');
                
			}
        }
   
    }
	else
    {
        $response = config('global-constants.TRAVLE.NOT_EXPIERED');
    } 
return  $response ;
}
  

 /**
 * use of that function is to check if user has FL2.0 subscription or not.
 * @return return default Language
 */
function has_subscription()
{
	$userId = get_current_user_id();
	$serviceIdToFind = 26;
	if (get_current_user_id()) {
		$customer_API = new Customers();
		$user_data = $customer_API->get_customer_services($userId);
		if (array_search(26, array_column($user_data, 'ServiceId')) !== False) {

			$keys26 = array_keys(array_column($user_data, 'ServiceId'), 26);

			if (!empty($keys26)) {
				$dateexpiery = Carbon::parse($user_data[$keys26[0]]['ExpirationDate']);
				$result = true;
				if ($dateexpiery->isPast()) {
					$result = false;
				}

			}


			return $result;
		}
	}
}


?>
