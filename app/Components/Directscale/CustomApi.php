<?php

namespace App\Components\Directscale;

use App\Components\Api\Directscale;
use App\Components\Api\Base;

class CustomApi extends Directscale{

    protected $customTreeApiurl;
    protected $assignParentToSubscriberApiUrl;
    protected $getUnplacedUserListApiUrl;
    protected $unplaceUsersApiurl;
    protected $holdingTank;
    protected $customLegApiurl;
    protected $searchUserTankApiUrl;

    public function __construct()
    {
        // $this->url= "https://nvisionu.clientextension.directscalestage.com/api/CustomApi/GetSubscriberTree";
        $this->customTreeApiurl = "https://nvisionu.clientextension.directscale.com/api/ResidualTree/GetSubscriberTree";
        $this->assignParentToSubscriberApiUrl = "https://nvisionu.clientextension.directscale.com/api/CustomApi/AssignParentToSubscriber?test=3";
        $this->getUnplacedUserListApiUrl = "https://nvisionu.clientextension.directscalestage.com/api/CustomApi/GetUnplacedUsers?test=3";
        $this->unplaceUsersApiurl = "https://nvisionu.clientextension.directscalestage.com/api/CustomApi/GetUnplacedUsers";
        $this->holdingTank="https://nvisionu.clientextension.directscale.com/api/ResidualTree/GetHoldingTank";
        $this->customLegApiurl="https://nvisionu.clientextension.directscale.com/api/ResidualTree/GetNodeLegBottom";
        $this->searchUserTankApiUrl="https://nvisionu.clientextension.directscale.com/api/ResidualTree/GetNodesInsideAssociateTree";
        $this->assignParentToSubscriberEnrollmentTreeApiUrl="https://nvisionu.clientextension.directscale.com/api/CustomApi/AssignParentToSubscriberEnrollmentTree";

    }

    public function getCustomerDetails($data)
    {
        $response = $this->makeRequest(env('CUSTOM_DIRECT_SCALE_LINK')."CustomApi/CustomCheckoutApi", "GET", $data);
        return $this->formatResponse($response);
    }

    /**
     * this function is make header for customTree api
     * with token
     * return header
    */
    private function customTreeheader(){
        $headers = array(
            "Authorization: Bearer MxSq4qVHr6EJCALySG5Wx4F9zfMdelJs9EaHdHeATUvI",
            "Content-Type: application/json",
         );

        return $headers;
    }

    /**
     * this function is used for unplace
     * associate users listing header
    */
    private function unplaceUsersheader(){
        $headers = array(
            "Authorization: Bearer BTJ0TPbDYAv5CdZ0LCbClkW7kQbcTcgmKM4i1yopclQA",
            "Content-Type: application/json",
         );

        return $headers;
    }

    /**
     * this function is make header for assignParentToSubscriber api
     * with token
     * return header
    */
    private function assignParentToSubscriberHeader(){
        $headers = array(
            // "Authorization: Bearer BTJ0TPbDYAv5CdZ0LCbClkW7kQbcTcgmKM4i1yopclQA",
            "Authorization: Bearer MxSq4qVHr6EJCALySG5Wx4F9zfMdelJs9EaHdHeATUvI",
            "Content-Type: application/json",
         );

        return $headers;
    }

    private function getUnplacedUserListHeader(){
        $headers = array(
            "Authorization: Bearer BTJ0TPbDYAv5CdZ0LCbClkW7kQbcTcgmKM4i1yopclQA",
            // "Authorization: Bearer MxSq4qVHr6EJCALySG5Wx4F9zfMdelJs9EaHdHeATUvI",
            "Content-Type: application/json",
         );

        return $headers;
    }

    /**
     * this function is make header to assign parent to subscriber enrollment tree
     * with token
     * return header
     */
    private function assignParentToSubscriberEnrollmentTreeHeader(){
        $headers = array(
            "Authorization: Bearer MxSq4qVHr6EJCALySG5Wx4F9zfMdelJs9EaHdHeATUvI",
            "Content-Type: application/json",
        );

        return $headers;
    }

    /**
	 * curl function
	 * @param rowData url ,header
	 * @return curl responce
	 */

    public function callCurl($url,$header,$rowData)
    {
        $rowData = json_encode($rowData);

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $data = $rowData;

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $resp = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error_msg ='';
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
        }
        curl_close($curl);
        $data = json_decode($resp, true);
        return ['data'=>$data,"status_code"=> $httpcode,"message"=>$error_msg];

    }

    /**
     * this function is used to call customeTree Api
     * return curl result in array
     * @params rowdata in array
     */
    public function customeTreeApi($rowData){
        $header = $this->customTreeheader();
        $curlResult = $this->callCurl($this->customTreeApiurl,$header,$rowData);
        return $curlResult;
    }
    public function largestLeg($rowData){
        $header = $this->customTreeheader();
        $curlResult = $this->callCurl($this->customLegApiurl,$header,$rowData);
        return $curlResult;
    }
    /**
     * this function is used to call customeTree Api
     * return curl result in array
     * @params rowdata in array
     */
    public function holdingTank($rowData){
        $header = $this->customTreeheader();
        $curlResult = $this->callCurl($this->holdingTank,$header,$rowData);
        return $curlResult;
    }

    /**
     * this functio is used to get
     * all unplace user under the residual tree
     * @param rodata and
     * @return all unplace user under selected associate
    */
    public function unplaceUserCustomeTreeApi($rowData)
    {
        $header = $this->unplaceUsersheader();
        $curlResult = $this->callCurl($this->unplaceUsersApiurl,$header,$rowData);
        return $curlResult;
    }

    /**
     * this function is used to call assignParentToSubscriber Api
     * @params rowdata in array
     * return curl result in array
     */
    public function assignParentToSubscriberApi($rowData){
        $header = $this->assignParentToSubscriberHeader();
        $curlResult = $this->callCurl($this->assignParentToSubscriberApiUrl,$header,$rowData);
        return $curlResult;
    }

    /**
     * this function is used to call assignParentToSubscriberEnrollmentTree Api
     * @params rowdata in array
     * return curl result in array
     */
    public function assignParentToSubscriberEnrollmentTreeApi($rowData){
        $header = $this->assignParentToSubscriberEnrollmentTreeHeader();
        $curlResult = $this->callCurl($this->assignParentToSubscriberEnrollmentTreeApiUrl,$header,$rowData);
        return $curlResult;
    }
    /**
     * this function is used to call searchUserTank Api
     * @params rowdata in array
     * return curl result in array
     */
    public function searchUserTankApi($rowData){
        $header = $this->assignParentToSubscriberHeader();
        $curlResult = $this->callCurl($this->searchUserTankApiUrl,$header,$rowData);
        return $curlResult;
    }
    /**
     * this function is used to call getQualifiedPeopleforRefer Api
     * return curl result in array
     */
    public function getQualifiedPeopleforRefer(){
        $header = array(
           // "Authorization: Bearer MxSq4qVHr6EJCALySG5Wx4F9zfMdelJs9EaHdHeATUvI",
            "Content-Type: application/json",
        );
        $url = env('CUSTOM_DIRECT_SCALE_LINK')."CustomApi/GetQualifiedPeopleforRefer3";

        $curlResult = $this->callCurl($url,$header, []);
        return $curlResult;
    }
    /**
     * this function is used to call getQualifiedPeopleforRefer Api
     * return curl result in array
     */
    public function getQualifiedPeopleforRefer3ForWidget($customerId, $date){
        $header = array(
           // "Authorization: Bearer MxSq4qVHr6EJCALySG5Wx4F9zfMdelJs9EaHdHeATUvI",
            "Content-Type: application/json",
        );
        $url = env('CUSTOM_DIRECT_SCALE_LINK')."CustomApi/GetQualifiedPeopleInRefer3forWidget";
        $data = [
            "CustomerId"=>$customerId,
            "Date"=>$date."T00:00:00"
            //"Date"=>$date//"2023-12-01T00:00:00"
        ];
         $curlResult = $this->callCurl($url,$header, $data);
        return $curlResult;
    }
    /**
     * this function is used to call getUnplacedUserList Api
     * @params rowdata in array
     * return curl result in array
     */
    // public function getUnplacedUserListApi($rowData){
    //     $header = $this->getUnplacedUserListHeader();
    //     $curlResult = $this->callCurl($this->getUnplacedUserListApiUrl,$header,$rowData);
    //     return $curlResult;
    // }

}