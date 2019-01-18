<?php
namespace v1\module\RateEngine\ukmail\src; 

use v1\module\RateEngine\ukmail\src\Singleton\Singleton;
use v1\module\RateEngine\ukmail\src\Model\UkMailModel;
            
class UkmailBookCollection
{

    private $_db;
    private $_app;
    private $_requestParams;
	private $_wsdlUrl;
	private $_closedForLunch;
    
    /**
     * FormConfiguration constructor.
     */
    private function __construct($app) {
        $this->_db = new DbHandler();
        $this->_app = $app;
        $this->_requestParams=json_decode($this->_app->request->getBody());
		if(ENV == 'dev')
			$this->_wsdlUrl = 'https://qa-api.ukmail.com/Services/UKMCollectionServices/UKMCollectionService.svc?wsdl';
		else
			$this->_wsdlUrl = 'https://api.ukmail.com/Services/UKMCollectionServices/UKMCollectionService.svc?wsdl';
		
		$this->_closedForLunch = false;
    }
	
	
	public static function staticBookCollectionRequest($AuthenticationToken){
		$request = new \stdClass();
		$request->AuthenticationToken = $AuthenticationToken;//"035A32EE-1692-45E7-A2DD-8F6D74A697AF"; /*type Alpha Mandatory - Y */
        $request->Username = "nikhil.kumar@ordercup.com"; /*type Alpha Mandatory - Y */
		$request->AccountNumber = "K906430"; /*type Alpha(10) Mandatory - Y */
		$request->ClosedForLunch = "false"; /*type boolean Mandatory - Y */
        $request->BusinessName = "PCS"; /*type Alpha(40) Mandatory - N */
		$request->EarliestTime = "2019-01-18T11:48:00.000"; /*type DateTime Mandatory - Y */
		$request->LatestTime = "2019-01-18T11:48:00.000"; /*type DateTime Mandatory - Y */
		$request->RequestedCollectionDate = "2019-01-18T11:48:00.000"; /*type DateTime Mandatory - Y */
        $request->SpecialInstructions = "Test"; /*type Alpha(20) Mandatory - N */
		return $request;
	}
	
    public static function bookCollection($app){
		//$app = json_decode($app->request->getBody());       
        /* $request = new stdClass();
        $request->AuthenticationToken = $app->AuthenticationToken;
        $request->Username = $app->username;
		$request->AccountNumber = $app->account_number;
        $request->ClosedForLunch = $this->_closedForLunch;
		$request->EarliestTime = $app->earliest_time;
        $request->LatestTime = $app->latest_time;
		$request->RequestedCollectionDate = $app->collection_date;
		$request->SpecialInstructions = $app->special_instruction; */
		$request = self::staticBookCollectionRequest($app->AuthenticationToken);
        $BookCollection = new \stdClass();
        $BookCollection->request = $request;
		$wsdlUrl = 'https://qa-api.ukmail.com/Services/UKMCollectionServices/UKMCollectionService.svc?wsdl';
        $soapClient = new \SoapClient($wsdlUrl);
        $BookCollectionResponse = $soapClient->BookCollection($BookCollection); 
		if(isset($BookCollectionResponse->BookCollectionResult->Errors->UKMWebError)){
			return array("status"=>"error","book_collection_error_code"=>$BookCollectionResponse->BookCollectionResult->Errors->UKMWebError->Code,"book_collection_error_message"=>$BookCollectionResponse->BookCollectionResult->Errors->UKMWebError->Description,"message"=>$BookCollectionResponse->BookCollectionResult->Errors->UKMWebError->Description);
		}else{
			$CollectionJobNumber = $BookCollectionResponse->BookCollectionResult->CollectionJobNumber; 
			return array("status"=>"success","message"=>$BookCollectionResponse->BookCollectionResult->BookingMessage,"collection_job_number"=>$CollectionJobNumber);
		}
    }
}