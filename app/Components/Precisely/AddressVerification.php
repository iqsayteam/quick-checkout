<?php
/**
 * @Author: IQSAY
 * @Date: 2023-3-10
 * @Last Modified by: Kreso Vargec
 * @Last Modified time: 2023-3-15
 */

namespace App\Components\Precisely;

use App\Components\Api\Precisely;

class AddressVerification extends Precisely
{
    protected $uri = '/addressverification/v1/validatemailingaddresspremium/results.json';
    protected $validate_address;
    /**
     * @param $addressline1
     * @param $city
     * @param $country
     * @param $state
     * @param $postal
     * @return array|mixed
     */
    public function __construct()
    {
        parent::__construct();
        $this->validate_address=env('VALIDATE',false);
    }
    public function validate_address($addressline1, $city, $country, $state, $postal)
    {

        $row[0] = ["AddressLine1" => $addressline1, "City" => $city, "Country" => $country, "StateProvince" => $state, "PostalCode" => $postal];
        $options = ["OutputCasing" => "M"];
        $input = ["Input" => ["Row" => $row]];
        $data['json'] = ["OutputCasing" => "M", "Input" => ["Row" => $row]];
        $response = $this->makeRequest("{$this->uri}", "POST", $data);
        $response=$this->formatResponse($response);
        $response=$response['Output'][0];
        if($this->validate_address){
            if(isset($response['Status']) && $response['Status']=='F'){

                if($response['Confidence']>70){
                    $message='Address valid 2';
                    return json_encode(array('success'=>true,'Message'=>$message));
                }elseif ($response['Confidence']>20){
                    $message="Please check your address.";
                    return json_encode(array('success'=>false,'Message'=>$message));
                }else{
                    $message="Address not found";
                    return json_encode(array('success'=>false,'Message'=>$message));
                }

            }else{
                $message='Address valid';
                return json_encode(array('success'=>true,'Message'=>$message));
            }
        }else{
            $message='Address valid';
            return json_encode(array('success'=>true,'Message'=>$message));
        }

    }
}