<?php
class Carrier{
    protected static $_environment = NULL;
    private $_postParam = array();
	public $modelObj = null;


	public function __construct(){
        $this->modelObj = new Booking_Model_Booking();
    }
	
	/* public function getShipmentInfo($loadIdentity, $rateDetail, $allData = array()){
		$carrierObj = null;
		$response = array();
		$shipmentInfo = $this->modelObj->getDeliveryShipmentData($loadIdentity);
		$deliveryCarrier = $shipmentInfo['carrier_code'];
        global $_GLOBAL_CONTAINER;
        if(class_exists('Coreprime_' . ucfirst(strtolower($deliveryCarrier)))) {
            $coreprimeCarrierClass = 'Coreprime_' . ucfirst(strtolower($deliveryCarrier));
        }else{
            $coreprimeCarrierClass = v1\module\carrier\Coreprime\Common\LabelProcessor::class;
        }
		$carrierObj = new $coreprimeCarrierClass($this);

		if( strtolower($deliveryCarrier) == 'dhl' ) {
			$shipmentInfo = $carrierObj->getShipmentDataFromCarrier($loadIdentity, $rateDetail, $allData);
		} else {
			$shipmentInfo = $carrierObj->getShipmentDataFromCarrier($loadIdentity,$allData);
		}

		if( $shipmentInfo['status'] == 'success' ) {
			$invoice_created = (isset($shipmentInfo['invoice_created'])) ? $shipmentInfo['invoice_created'] : 0;
			if(isset($shipmentInfo['child_account_data'])){
				return array("status"=>"success","file_path"=>$shipmentInfo['file_path'],"label_tracking_number"=>$shipmentInfo['label_tracking_number'],"label_files_png"=>$shipmentInfo['label_files_png'],"label_json"=>$shipmentInfo['label_json'],"child_account_data"=>$shipmentInfo['child_account_data'],'invoice_created'=>$invoice_created);
			}else{
				return array("status"=>"success","file_path"=>$shipmentInfo['file_path'],"label_tracking_number"=>$shipmentInfo['label_tracking_number'],"label_files_png"=>$shipmentInfo['label_files_png'],"label_json"=>$shipmentInfo['label_json'],'invoice_created'=>$invoice_created);
			}
			
		} else {
			return array("status"=>$shipmentInfo['status'],"message"=>$shipmentInfo['message']);
		}
	} */
	
	public function getShipmentInfo($loadIdentity, $rateDetail, $allData = array()){
		$carrierObj = null;
		$response = array();
		$shipmentInfo = $this->modelObj->getDeliveryShipmentData($loadIdentity);
		$deliveryCarrier = $shipmentInfo['carrier_code']; 
        $providerInfo = $this->modelObj->getProviderInfo('LABEL',ENV,'PROVIDER',$shipmentInfo['carrier_code']);         
        global $_GLOBAL_CONTAINER;
        
        if($providerInfo['endpoint']=='Coreprime'){
            $coreprimeCarrierClass = 'Coreprime_' . ucfirst(strtolower($deliveryCarrier));
        }else{ 
            $coreprimeCarrierClass = v1\module\carrier\Coreprime\Common\LabelProcessor::class;
            $allData->providerInfo = array(
                    'provider' => $providerInfo['provider'],
                    'endPointUrl' => $providerInfo['label_endpoint'], //url to hit rate api controller
                );
        }
		$carrierObj = new $coreprimeCarrierClass($this); 
        $shipmentInfo = $carrierObj->getShipmentDataFromCarrier($loadIdentity,$rateDetail,$allData);
        
		if( $shipmentInfo['status'] == 'success' ) {
			$invoice_created = (isset($shipmentInfo['invoice_created'])) ? $shipmentInfo['invoice_created'] : 0;
			if(isset($shipmentInfo['child_account_data'])){
				return array("status"=>"success","file_path"=>$shipmentInfo['file_path'],"label_tracking_number"=>$shipmentInfo['label_tracking_number'],"label_files_png"=>$shipmentInfo['label_files_png'],"label_json"=>$shipmentInfo['label_json'],"child_account_data"=>$shipmentInfo['child_account_data'],'invoice_created'=>$invoice_created);
			}else{
				return array("status"=>"success","file_path"=>$shipmentInfo['file_path'],"label_tracking_number"=>$shipmentInfo['label_tracking_number'],"label_files_png"=>$shipmentInfo['label_files_png'],"label_json"=>$shipmentInfo['label_json'],'invoice_created'=>$invoice_created);
			}
			
		} else {
			return array("status"=>$shipmentInfo['status'],"message"=>$shipmentInfo['message']);
		}
	}

	public function getPackageInfo($loadIdentity){
		$packageData = array();
		$packageInfo = $this->modelObj->getPackageDataByLoadIdentity($loadIdentity);
		foreach($packageInfo as $data){
			$packageData = array("packaging_type"=>$data["package"],"width"=>$data["parcel_width"],"length"=>$data["parcel_length"],"height"=>$data["parcel_height"],"dimension_unit"=>"CM","weight"=>$data["parcel_weight"],"weight_unit"=>"KG");
		}
		return $packageData;
	}

	public function getServiceInfo($loadIdentity){
		$serviceInfo = $this->modelObj->getServiceDataByLoadIdentity($loadIdentity);
		return $serviceInfo;
	}

	public function getLabelByLoadIdentity($loadIdentity){
		$labelInfo = $this->modelObj->getLabelByLoadIdentity($loadIdentity);
		return $labelInfo;
	}
	
	public function mergePdf($labelPdfArr){
		$rootPath = dirname(dirname(dirname(dirname(__FILE__))));
        $outFile = uniqid().'.pdf'; 
		$labelPath = $rootPath. '/label/';
		$filenames = array();		
		foreach ($labelPdfArr as $file) {
			$loadIdentity = $file['load_identity'];
			$carrierCode = strtolower($file['carrier_code']);
			$pathArr = explode('/',$file['label_file_pdf']);
			$filePath = $labelPath.$loadIdentity.'/'.$carrierCode.'/'.$pathArr[ count($pathArr) - 1];
			array_push($filenames,$filePath);
		}		
		if ($filenames) {
			try{
				$config = array('mode' => 'c','margin_left' => 15,'margin_right' => 0,'margin_top' => 5,'format' => array(101,152),'orientation' => 'L');
				$mpdf = new \Mpdf\Mpdf($config);
				$filesTotal = sizeof($filenames);
				$fileNumber = 1;
				$mpdf->SetImportUse();
				if (!file_exists($rootPath.'/temp/'.$outFile)) {
					$handle = fopen($rootPath.'/temp/'.$outFile, 'w');
					fclose($handle);
				}
				foreach ($filenames as $fileName) {
					if (file_exists($fileName)) {
						$pagesInFile = $mpdf->SetSourceFile($fileName);
						for ($i = 1; $i <= $pagesInFile; $i++) {
							$tplId = $mpdf->ImportPage($i);
							$mpdf->UseTemplate($tplId);
							if (($fileNumber < $filesTotal) || ($i != $pagesInFile)) {
								$mpdf->WriteHTML('<pagebreak />');
							}
						}
					}
					$fileNumber++;
				}	
				$mpdf->Output($rootPath.'/temp/'.$outFile);
			}catch(Exception $e){
                print_r($e);die;
            }
			$fileUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST']."/".LABEL_URL;
            return array("status"=>"success","file_path" => $fileUrl."/temp/".$outFile);
		}else{
			return array("status"=>"error","file_path" => "");
		}		
	}
	
	/* public function mergePdf($labelPdfArr){
            try{
                $labelArr = array();
                //print_r($labelPdfArr); die;
                $rootPath = dirname(dirname(dirname(dirname(__FILE__))));
                $labelPath = $rootPath. '/label/';
                foreach($labelPdfArr as $file){
                        $loadIdentity = $file['load_identity'];
                        $carrierCode = strtolower($file['carrier_code']);
                        $pathArr = explode('/',$file['label_file_pdf']);
                        //$file = "/var/www/html/public_html/icargo/api/".$file['label_file_pdf'][3].'/'.$file['label_file_pdf'][4].'/'.$file['label_file_pdf'][5].'/'.$file['label_file_pdf'][6].'/'.$file['label_file_pdf'][7];
                        $filePath = $labelPath.$loadIdentity.'/'.$carrierCode.'/'.$pathArr[ count($pathArr) - 1];
                        array_push($labelArr,$filePath);
                }
                $fileName = uniqid().'.pdf';
                $pdf = new ConcatPdf();
                $pdf->setFiles($labelArr);
                $pdf->concat();
                //$pdf->Output('/var/www/html/public_html/icargo/api/dev/temp/'.$fileName,'F');
                $pdf->Output($rootPath.'/temp/'.$fileName,'F');
            } catch(Exception $e) {
                print_r($e);die;
            }
            $fileUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'].LABEL_URL;
            return array("status"=>"success","file_path" => $fileUrl."/temp/".$fileName);
	} */


    private function _getEnvironment(){
        if(self::$_environment===NULL){
            self::$_environment = new Environment();
        }
        return self::$_environment;
    }

    private function _getApiUrl(){
        return $this->_getEnvironment()->getApiUrl();
    }

    private function _getCoreprimeCredentials(){
        $credentials = array_filter($this->_getEnvironment()->getCoreprimeCredentials(), function($val){
            $val = trim($val);
            return $val != '';
        });

        if(count($credentials)>0){
            $this->_setCoreprimeCredentials($credentials);
            return true;
        }else{
            return false;
        }
    }

    private function _setCoreprimeCredentials($credential){
        $credential = (object)$credential;
        $this->_postParam->credentials = new stdClass();

        foreach($credential as $key=>$item){
            $this->_postParam->credentials->$key = $item;
        }
    }

    private function _setCurrency(){
        $this->_postParam->currency = "GBP";
    }

    private function _setMethodType(){
        $this->_postParam->method_type = "post";
    }

    protected function _send($data){
        $this->_postParam = $data;
        if($this->_getCoreprimeCredentials()){
            $this->_setCurrency();
            $this->_setMethodType();

            $data_string = json_encode($this->_postParam);
            //echo $data_string;die;
            $ch = curl_init($this->_getEnvironment()->getApiUrl());
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string))
            );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = json_decode(curl_exec ($ch));
            curl_close ($ch);
            //return $this->_calculateCcf($server_output->rate, $customer_id, $company_id);
            return $server_output->rate;
        }else{
            return array("status"=>"error","message"=>"Credential not found");
        }

    }

	/*****start of cancelling shipment from shipment grid*******/
	public function cancelShipmentByLoadIdentity($param){
		$obj = new Carrier_Coreprime_Request();
		$requestArr = array();
		$labelInfo = $this->getLabelByLoadIdentity($param->load_identity);
		//$shipmentInfo = $this->modelObj->getShipmentDataByLoadIdentity($param->load_identity);
		if( isset($labelInfo[0]['label_json']) && $labelInfo[0]['label_json'] != '' ){
			$labelArr = json_decode($labelInfo[0]['label_json']);
			
			//get credentials for child account
			if($labelInfo[0]['accountkey']!=$labelInfo[0]['parent_account_key'])
				$credentialData = $this->modelObj->getCredentialDataForChildAccount($labelArr->label->accountnumber,$labelInfo[0]['parent_account_key']);
			else //get credentials for parent account
				$credentialData = $this->modelObj->getCredentialDataByLoadIdentity($labelArr->label->accountnumber, $param->load_identity);

			$requestArr['credentials'] = array('username'=>$credentialData["username"],'password'=>$credentialData["password"],'authentication_token'=>'','token'=> $credentialData["token"],'account_number'=>$labelArr->label->accountnumber); 
            //$requestArr['credentials'] = array('username'=>$credentialData["username"],'password'=>$credentialData["password"],'authentication_token'=>$labelArr->label->authenticationtoken,'token'=> $credentialData["token"],'account_number'=>$labelArr->label->accountnumber); 

			$requestArr['carrier'] = strtolower($param->carrier);
			$requestArr['tracking_number'] = $labelArr->label->tracking_number;
			$requestArr['carrier_cancel_return'] = false;
			$requestArr['ship_date'] = $param->ship_date;
			$cancel = $obj->_postRequest("void",json_encode($requestArr));
			return $cancel;
		}

	}
	/*****end of cancelling shipment from shipment grid*******/
}
?>
