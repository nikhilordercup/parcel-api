<?php
require_once  __DIR__ . '/../../../../vendor/autoload.php';    
include_once __DIR__ .'/../../../module/ukmail_api/src/Singleton/Singleton.php';
include_once __DIR__ .'/model/UkMailModel.php';
            
class UkmailGenerateLabel
{

    private $_db;
    private $_app;
    private $_requestParams;
	private $_bookingType;
    
    /**
     * FormConfiguration constructor.
     */
    private function __construct($app) {
        $this->_db = new DbHandler();
        $this->_app = $app;
        $this->_requestParams=json_decode($this->_app->request->getBody());
		$this->_bookingType = "DOMESTIC";//"INTERNATIONAL","PACKET";
		$this->wsdl_url = UKMAIL_URL.UKMAIL_LABEL_ENDPOINT_URL;
    }
	
	
	public static function generateLabel($data){
		verifyRequiredParams(array('username','authentication_token','account_number','address_1','city','postcode','alpha3_code','collection_job_number','weight'),$app);
		$commonRequestParams = self::commonParams($data);
		
		switch ($this->_bookingType)
        {
		case 'DOMESTIC':
			verifyRequiredParams(array('contact_name','business_name','email','parcel_quantity','service_code','insurance_amount'),$app);
			self::getDomesticConsignmentRequest($carrier_code, $lists);
			break;

		case 'INTERNATIONAL':
			verifyRequiredParams(array('contact_name','business_name','email','parcel_quantity','service_code','insurance_amount'),$app);
			self::getInternationalConsignmentRequest($carrier_code, $lists);
			break;

		case 'PACKET':
			self::getPacketConsignmentRequest($carrier_code, $lists);
			break;	
		}
		
	}
	
	public createDomesticConsignment($data){
		verifyRequiredParams(array('username','authentication_token','collection_job_number','account_number','contact_name','business_name','address_1','city','postcode','alpha3_code','email','parcel_quantity','weight','service_code','insurance_amount'),$app);
	}
	
    


}