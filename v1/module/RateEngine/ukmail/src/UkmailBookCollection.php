<?php
namespace v1\module\RateEngine\ukmail\src; 

use v1\module\RateEngine\ukmail\src\Model\UkMailModel;
            
class UkmailBookCollection
{
		
	public static function staticBookCollectionRequest($AuthenticationToken){
		$request = new \stdClass();
		$request->AuthenticationToken = $AuthenticationToken;/*type Alpha Mandatory - Y */
        $request->Username = "nikhil.kumar@ordercup.com"; /*type Alpha Mandatory - Y */
		$request->AccountNumber = "K906430"; /*type Alpha(10) Mandatory - Y */
		$request->ClosedForLunch = "false"; /*type boolean Mandatory - Y */
		$request->EarliestTime = "2019-01-18T11:48:00.000"; /*type DateTime Mandatory - Y */
		$request->LatestTime = "2019-01-18T11:48:00.000"; /*type DateTime Mandatory - Y */
		$request->RequestedCollectionDate = "2019-01-18T11:48:00.000"; /*type DateTime Mandatory - Y */
        $request->SpecialInstructions = "Test"; /*type Alpha(20) Mandatory - N */
		return $request;
	}
	
    public static function bookCollection($data,$wsdlBaseUrl){
		$wsdlUrl = $wsdlBaseUrl.'UKMCollectionServices/UKMCollectionService.svc?wsdl';	
        $BookCollection = new \stdClass();
        $BookCollection->request = $data;
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