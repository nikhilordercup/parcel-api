<?php
class Carrier{
    protected static $_environment = NULL;
    private $_postParam = array();
	public $modelObj = null;


	public function __construct(){
        $this->modelObj = new Booking_Model_Booking();
    }
	public function getShipmentInfo($loadIdentity, $rateDetail, $allData = array()){
		$carrierObj = null;
		$response = array();
		$shipmentInfo = $this->modelObj->getDeliveryShipmentData($loadIdentity);
		$deliveryCarrier = $shipmentInfo['carrier_code'];
		/* foreach($shipmentInfo as $key=>$data){
			if($data['shipment_service_type']=='P'){
				$response['from'] = array("name"=>$data["shipment_customer_name"],"company"=>$data["shipment_companyName"],"phone"=>$data["shipment_customer_phone"],"street1"=>$data["shipment_address1"],"street2"=>$data["shipment_address2"],"city"=>$data["shipment_customer_city"],"state"=>$data["shipment_county"],"zip"=>$data["shipment_postcode"],"country"=>$data["shipment_country_code"],"country_name"=>$data["shipment_customer_country"],"is_apo_fpo"=>"");
				$response['ship_date'] = $data['shipment_required_service_date'];

			}elseif($data['shipment_service_type']=='D'){
				$response['to'] = array("name"=>$data["shipment_customer_name"],"company"=>$data["shipment_companyName"],"phone"=>$data["shipment_customer_phone"],"street1"=>$data["shipment_address1"],"street2"=>$data["shipment_address2"],"city"=>$data["shipment_customer_city"],"state"=>$data["shipment_county"],"zip"=>$data["shipment_postcode"],"zip_plus4"=>"","country"=>$data["shipment_country_code"],"country_name"=>$data["shipment_customer_country"],"email"=>$data["shipment_customer_email"],"is_apo_fpo"=>"","is_residential"=>"");

				$response['carrier'] = $data['carrier_code'];
				//$response['ship_date'] = $data['shipment_required_service_date'];
			}
		} */

		$coreprimeCarrierClass = 'Coreprime_'.ucfirst(strtolower($deliveryCarrier));

		$carrierObj = new $coreprimeCarrierClass();

                if( strtolower($deliveryCarrier) == 'dhl' ) {
                    $shipmentInfo = $carrierObj->getShipmentDataFromCarrier($loadIdentity, $rateDetail, $allData);
                } else {
                    $shipmentInfo = $carrierObj->getShipmentDataFromCarrier($loadIdentity,$allData);
                }


                if( $shipmentInfo['status'] == 'success' ) {
                    return array("status"=>"success","file_path"=>$shipmentInfo['file_path'],"label_tracking_number"=>$shipmentInfo['label_tracking_number'],"label_files_png"=>$shipmentInfo['label_files_png'],"label_json"=>$shipmentInfo['label_json']);
                } else {
                    return array("status"=>$shipmentInfo['status'],"message"=>$shipmentInfo['message']);
                }
		//$finalRequestArr = json_encode(array_merge($response,$shipmentInfo));

		/* $response['package'] = $this->getPackageInfo($loadIdentity);
		$serviceInfo = $this->getServiceInfo($loadIdentity);
		$response['currency'] = $serviceInfo['currency'];
		$response['service'] = $serviceInfo['service_code'];
		$response['credentials'] = $this->getCredentialInfo($loadIdentity); */

		/**********start of static data from requet json ***************/
		/* $response['extra'] = array("service_key"=>"1","long_length"=>"","bookin"=>"","exchange_on_delivery"=>"","reference_id"=>"","region_code"=>"","confirmation"=>"","is_document"=>"","auto_return"=>"","return_service_id"=>"","special_instruction"=>"","custom_desciption"=>"","custom_desciption2"=>"","custom_desciption3"=>"","customs_form_declared_value"=>"","document_only"=>"","no_dangerous_goods"=>"","in_free_circulation_eu"=>"","extended_cover_required"=>"","invoice_type"=>"");
		$response['insurance'] = array("value"=>"","currency"=>"","insurer"=>"");
		$response['constants'] = array("shipping_charge"=>"","weight_charge"=>"","fuel_surcharge"=>"","remote_area_delivery"=>"","insurance_charge"=>"","over_sized_charge"=>"","over_weight_charge"=>"","discounted_rate"=>"");
		$response['label_options'] = "";
		$response['customs'] = "";
		$response['billing_account'] = array("payor_type"=>"","billing_account"=>"","billing_country_code"=>"","billing_person_name"=>"","billing_email"=>"");
		$response['label'] = array();
		$response['method_type'] = "post"; */
		/**********end of static data from requet json ***************/

		//print_r(json_encode($response));die;

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

	/*public function getCredentialInfo($loadIdentity){
		$credentialData = array();
		$credentialInfo = $this->modelObj->getCredentialDataByLoadIdentity('',$loadIdentity);
		$credentialInfo["master_carrier_account_number"] = "";
		$credentialInfo["latest_time"] = "";
		$credentialInfo["earliest_time"] = "";
		$credentialInfo["carrier_account_type"] = array();
		return $credentialInfo;
	}*/

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
				if (!file_exists($outFile)) {
					$handle = fopen($outFile, 'w');
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
		$shipmentInfo = $this->modelObj->getShipmentDataByLoadIdentity($param->load_identity);
		if( isset($labelInfo[0]['label_json']) && $labelInfo[0]['label_json'] != '' ){
			$labelArr = json_decode($labelInfo[0]['label_json']);

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
