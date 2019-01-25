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
		if(ENV == 'dev')
			$wsdlBaseUrl = 'https://qa-api.ukmail.com/Services/';
		else
			$wsdlBaseUrl = 'https://api.ukmail.com/Services/';
		
		$ukMailModel = new UkMailModel();
		
		$app->credentials->username = "nikhil.kumar@ordercup.com";
        $app->credentials->password = "b85op06w";
        $app->credentials->account_number = "K906430";
		$app->collection_start_date_time = "2019-01-21T11:48:00.000";
		$app->collection_end_date_time = "2019-01-21T11:48:00.000";
		
		$authToken = $ukMailModel->getValidAuthTokenByUsernameAndCarrier($app->credentials->username,$app->carrier);
		if($authToken!=''){
			$app->credentials->authenticationToken = $authToken;
		}else{
			$loginResp = UkmailLogin::doLogin($app,$wsdlBaseUrl);
			if($loginResp['status']=='success'){
				$app->credentials->authenticationToken = $loginResp['authentication_token'];
			}else{
				//login failed
				return array("status"=>"error","message"=>$loginResp['message']);
			}
		}
		//book collection request after successfully getting authentication token
		$collectionArr = self::formatCollectionRequest($app);
		$bookCollectionResp = UkmailBookCollection::bookCollection($collectionArr,$wsdlBaseUrl);
		
		if($bookCollectionResp['status']=='success'){
			$app->collectionjobnumber = $bookCollectionResp['collection_job_number'];
			//label generation call after successfully getting collection job number
			$labelResp['label'] = UkmailGenerateLabel::generateLabel($app,$wsdlBaseUrl);
			exit(json_encode($labelResp));
		}else{
			//book collection failed
			return array("status"=>"error","message"=>$bookCollectionResp['message']);
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
		$collectionArr->EarliestTime = $app->collection_start_date_time;
		$collectionArr->LatestTime = $app->collection_end_date_time;
		$collectionArr->RequestedCollectionDate = $app->collection_start_date_time;
		$collectionArr->ClosedForLunch = "false";
		$collectionArr->SpecialInstructions = $app->extra->pickup_instruction;
		return $collectionArr;
	}
}