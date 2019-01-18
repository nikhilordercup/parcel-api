<?php
namespace v1\module\RateEngine\ukmail\src; 
            
class UkmailMaster
{

    private $_db;
    private $_app;
    private $_requestParams;
	private $_wsdlUrl;
    
    /**
     * FormConfiguration constructor.
     */
    public function __construct() {
        /* $this->_db = new DbHandler();
        $this->_app = $app;
        $this->_requestParams=json_decode($this->_app->request->getBody()); */
		if(ENV == 'dev')
			$this->_wsdlUrl = 'https://qa-api.ukmail.com/Services/UKMAuthenticationServices/UKMAuthenticationService.svc?wsdl';
		else
			$this->_wsdlUrl = 'https://api.ukmail.com/Services/UKMAuthenticationServices/UKMAuthenticationService.svc?wsdl';	
    }
	
	public static function initRoutes($app){               
        $loginResp = UkmailLogin::doLogin($app);
		if($loginResp['status']=='success'){
			$app->AuthenticationToken = $loginResp['authentication_token'];
			$bookCollectionResp = UkmailBookCollection::bookCollection($app);
			if($bookCollectionResp['status']=='success'){
				$app->CollectionJobNumber = $bookCollectionResp['collection_job_number'];
				$createLabel = UkmailGenerateLabel::generateLabel($app);
				print_r($createLabel);die;
			}else{
				//book collection failed
				return array("status"=>"error","message"=>$bookCollectionResp['message']);
			}
		}else{
			//login failed
			return array("status"=>"error","message"=>$loginResp['message']);
		}
		
    }
}