<?php
/**
 * @Author: IQSAY
 * @Date: 2023-3-10
 * @Last Modified by: Kreso Vargec
 * @Last Modified time: 2023-3-15
 */

namespace App\Components\Precisely;

use App\Components\Api\Precisely;

class EmailVerification extends Precisely
{
    protected $uri = '/emailverification/v1/validateemailaddress/results.json';

    /**
     * @param $email
     * @return array|mixed
     */
    public function validate_email($email)
    {

        $row[0] = ["atc" => "a", "bogus" => true, "complain" => true, "disposable" => true, "language" => true,"emps"=>true,"emailAddress"=>$email];
        $options = ["Alias" => "N"];
        $input = ["Input" => ["Row" => $row]];
        $data['json'] = ["Alias" => "N", "Input" => ["Row" => $row]];
        //dd($data);
        $response = $this->makeRequest("{$this->uri}", "POST", $data);
        return $this->formatResponse($response);
    }
}