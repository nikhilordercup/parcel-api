<?php
namespace v1\module\RateEngine\ukmail\src; 

use v1\module\RateEngine\ukmail\src\Model\UkMailModel;
            
class UkmailMaster
{
	
	/**
     * This function returns Consignment number for ukmail booking. Function is combination of 3 calls i.e. Login > Book Collection > Add Consignment
	 * Login call can be skipped if authentication token for same username is already in icargo_carrier_user_token for the particular username & ukmail carrier and is not expired. We have to request for book collection and label call everytime.
     * @return Array
     */
	public static function initRoutes($app){
		$labelResp = array();
		$response = array();
		if(ENV == 'dev')
			$wsdlBaseUrl = 'https://qa-api.ukmail.com/Services/';
		else
			$wsdlBaseUrl = 'https://api.ukmail.com/Services/';
		
		$ukMailModel = new UkMailModel();
		
		if(isset($app->doLabelCancel)){
		   return self::cancelLabel($app,$wsdlBaseUrl);
		}
		$app->credentials->username = "nikhil.kumar@ordercup.com";
        $app->credentials->password = "b85op06w";
        $app->credentials->account_number = "K906430";
		
		$authToken = $ukMailModel->getValidAuthTokenByUsernameAndCarrier($app->credentials->username,$app->carrier);
		if($authToken!=''){
			$app->credentials->authenticationToken = $authToken;
		}else{
			$loginResp = UkmailLogin::doLogin($app,$wsdlBaseUrl);
			if($loginResp['status']=='success'){
				$app->credentials->authenticationToken = $loginResp['authentication_token'];
			}else{
				//login failed
				$response['label'] = array("status"=>"error","message"=>$loginResp['message']);
				exit(json_encode($response));
			}
		}
		//book collection request after successfully getting authentication token
		$collectionArr = self::formatCollectionRequest($app);
		$bookCollectionResp = UkmailBookCollection::bookCollection($collectionArr,$wsdlBaseUrl);
		if($bookCollectionResp['status']=='success'){
			$app->collectionjobnumber = $bookCollectionResp['collection_job_number'];
			//format extended cover value
			$app->extended_cover = ($app->extra->extended_cover_required!='') ? self::getExtendedCover($app->extra->extended_cover_required) : 0;
			
			//label generation call after successfully getting collection job number
			$labelResp['label'] = UkmailGenerateLabel::generateLabel($app,$wsdlBaseUrl);
			exit(json_encode($labelResp));
		}else{
			//book collection failed
			$response['label'] = array("status"=>"error","message"=>$bookCollectionResp['message']);
			exit(json_encode($response));
		}
	}
		
	/**
	 * This function returns formatted array data for book collection request.
	 * @return Array
	 */
	public static function formatCollectionRequest($app){
		$collectionArr = new \stdClass(); 
		$collectionArr->AuthenticationToken = $app->credentials->authenticationToken;
		$collectionArr->Username = $app->credentials->username;
		$collectionArr->AccountNumber = $app->credentials->account_number;
		$collectionArr->EarliestTime = $app->credentials->earliest_time;
		$collectionArr->LatestTime = $app->credentials->latest_time;
		$collectionArr->RequestedCollectionDate = $app->credentials->requested_collection_date;
		$collectionArr->ClosedForLunch = "false";
		$collectionArr->SpecialInstructions = $app->extra->pickup_instruction;
		return $collectionArr;
	}
	
	/**
	 * This function returns extended cover value.
	 * @return number
	 */
	public static function getExtendedCover($insurance_amount){
		switch ($insurance_amount)
		{
		case range(1,1000):
			return 1; 
			break;
		case range(1001,2000):
			return 2; 
			break;
		case range(2001,3000):
			return 3; 
			break;
		case range(3001,4000):
			return 4; 
			break;
		case range(4001,5000):
			return 5; 
			break;
		case range(5001,6000):
			return 6; 
			break;
		case range(6001,7000):
			return 7; 
			break;
		case range(7001,8000):
			return 8; 
			break;
		case range(8001,9000):
			return 9; 
			break;
		case range(9001,10000):
			return 10; 
			break;
		}
	}
	
	/**
	 * This function returns label cancellation response.
	 * @return Array
	 */
	public static function cancelLabel($param,$wsdlBaseUrl){
		$labelInfo = (object)$param->labelInfo[0];
		$labelInfo = json_decode($labelInfo->label_json);
	    
		$app = new \stdClass();
		$app->AuthenticationToken = $labelInfo->label->authenticationtoken;
		$app->ConsignmentNumber = $labelInfo->label->tracking_number;
		$app->Username = $param->username;
		
		$cancelLabel = UkmailCancelLabel::voidCall($app,$wsdlBaseUrl);
		exit(json_encode($cancelLabel));
	}
}