<?php
namespace v1\module\RateEngine\ukmail\src; 
            
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
		
		$ukMailModel = new model\UkMailModel();
		
		if(isset($app->doLabelCancel)){
		   return self::cancelLabel($app,$wsdlBaseUrl);
		}
		
		$authToken = $ukMailModel->getValidAuthTokenByUsernameAndCarrier($app->credentials->username,$app->carrier);
		if($authToken!=''){
			$app->credentials->authenticationToken = $authToken;
		}else{
			$loginResp = self::getAuthToken($app,$wsdlBaseUrl);
			if($loginResp['status']=='success'){
				$app->credentials->authenticationToken = $loginResp['authenticationtoken'];
			}else{
				//login failed
				$response['label'] = array("status"=>"error","message"=>$loginResp['message']);
				exit(json_encode($response));
			}
		}
		if($app->credentials->collectionjobnumber==''){
			//book collection request after successfully getting authentication token
			$collectionArr = self::formatCollectionRequest($app);
			$bookCollectionResp = UkmailBookCollection::bookCollection($collectionArr,$wsdlBaseUrl);
			if($bookCollectionResp['status']=='success'){
				$app->collectionjobnumber = $bookCollectionResp['collection_job_number'];
				//save pickup
				$saveCollection = $ukMailModel->saveCollection($app);

				
				//format extended cover value
				$app->extended_cover = 0;//($app->extra->extended_cover_required!='') ? self::getExtendedCover($app->extra->extended_cover_required) : 0;
				//label generation call after successfully getting collection job number
				$labelResp['label'] = UkmailGenerateLabel::generateLabel($app,$wsdlBaseUrl);
				exit(json_encode($labelResp));
			}else{
				//re login
				if($bookCollectionResp['book_collection_error_code']=="2050"){
					$LoginResponse = self::getAuthToken($app,$wsdlBaseUrl);
					if($LoginResponse['authenticationtoken']!=''){
						$collectionArr->AuthenticationToken = $LoginResponse['authenticationtoken'];
						$bookCollectionResp = UkmailBookCollection::bookCollection($collectionArr,$wsdlBaseUrl);
						if($bookCollectionResp['status']=='success'){
							$app->collectionjobnumber = $bookCollectionResp['collection_job_number'];
							//save pickup
							$saveCollection = $ukMailModel->saveCollection($app);
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
					}else{
						$response['label'] = array("status"=>"error","message"=>$LoginResponse['message']);
						exit(json_encode($response));
					}
				}else{
					//book collection failed
					$response['label'] = array("status"=>"error","message"=>$bookCollectionResp['message']);
					exit(json_encode($response));
				}
			}
		}else{//collection already created
		     $app->collectionjobnumber = $app->credentials->collectionjobnumber;
			//format extended cover value
			$app->extended_cover = 0;//($app->extra->extended_cover_required!='') ? self::getExtendedCover($app->extra->extended_cover_required) : 0;
			//label generation call after successfully getting collection job number
			$labelResp['label'] = UkmailGenerateLabel::generateLabel($app,$wsdlBaseUrl);
			exit(json_encode($labelResp));
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
		if(($insurance_amount ==  1) || ($insurance_amount <  1000 || $insurance_amount == 1000)){
		}			
		elseif(($insurance_amount ==  1001) || ($insurance_amount <  2000) || ($insurance_amount == 2000)){
			return 2; 
		}
		elseif(($insurance_amount ==  2001) || ($insurance_amount <  3000) || ($insurance_amount == 3000)){
			return 3; 
		}
		elseif(($insurance_amount ==  3001) || ($insurance_amount <  4000) || ($insurance_amount == 4000)){
			return 4; 
		}
		elseif(($insurance_amount ==  4001) || ($insurance_amount <  5000) || ($insurance_amount == 5000)){
			return 5; 
		}
		elseif(($insurance_amount ==  5001) || ($insurance_amount <  6000) || ($insurance_amount == 6000)){
			return 6; 
		}
		elseif(($insurance_amount ==  6001) || ($insurance_amount <  7000) || ($insurance_amount == 7000)){
			return 7; 
		}
		elseif(($insurance_amount ==  7001) || ($insurance_amount <  8000) || ($insurance_amount == 8000)){
			return 8; 
		}
		elseif(($insurance_amount ==  8001) || ($insurance_amount <  9000) || ($insurance_amount == 9000)){
			return 9; 
		}
		elseif(($insurance_amount ==  9001) || ($insurance_amount <  10000) || ($insurance_amount == 10000)){
			return 10; 
		}
	}
	
	/**
	 * This function returns label cancellation response.
	 * @return Array
	 */
	public static function cancelLabel($param,$wsdlBaseUrl){
		//$ukMailModel = new model\UkMailModel();
		$labelInfo = (object)$param->labelInfo[0];
		$labelInfo = json_decode($labelInfo->label_json);
	    
		$app = new \stdClass();
		/* $authToken = $ukMailModel->getValidAuthTokenByUsernameAndCarrier($param->username,'UKMAIL');
		if($authToken!=''){
			$app->AuthenticationToken = $authToken;
		}else{ */ 
			$app->credentials = new \stdClass();
			$app->credentials->username = $param->username;
			$app->credentials->password = $param->password;
			$loginResp = self::getAuthToken($app,$wsdlBaseUrl);
			if($loginResp['status']=='success'){
				$app->AuthenticationToken = $loginResp['authenticationtoken'];
			}else{
				//login failed
				$response['label'] = array("status"=>"error","message"=>$loginResp['message']);
				exit(json_encode($response));
			}
		//}
		
		//$app->AuthenticationToken = $labelInfo->label->authenticationtoken;
		$app->ConsignmentNumber = $labelInfo->label->tracking_number;
		$app->Username = $param->username;
		$cancelLabel = UkmailCancelLabel::voidCall($app,$wsdlBaseUrl);
		exit(json_encode($cancelLabel));
	}
	
	/**
	 * This function returns authentication token after login call.
	 * @return Array
	 */
	public static function getAuthToken($app,$wsdlBaseUrl){
		$response = array();
		$loginResp = UkmailLogin::doLogin($app,$wsdlBaseUrl);
		if($loginResp['status']=='success')
			$authenticationToken = $loginResp['authentication_token'];
		else
			$authenticationToken = "";
		
		$response = array("status"=>$loginResp['status'],"message"=>$loginResp['message'],"authenticationtoken"=>$authenticationToken);
		return $response;
	}
}