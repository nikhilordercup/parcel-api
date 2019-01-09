<?php
require_once dirname(dirname(__FILE__)) . "/Carrier_Coreprime_Request.php";

final class Coreprime_Ukmail extends Carrier /* implements CarrierInterface */
{

    public $modelObj = null;
    public function __construct()
    {
        $this->modelObj = new Booking_Model_Booking();
        $this->libObj = new Library();
    }



	private function _getLabel($loadIdentity,$json_data,$child_account_data)
    {
        $obj = new Carrier_Coreprime_Request();
        $label = $obj->_postRequest("label", $json_data);
        $labelArr = json_decode($label);


        $this->loadIdentity = $loadIdentity;
        if (isset($labelArr->label)) {
            $pdf_base64 = $labelArr->label->base_encode;
            $this->allImagesString = explode(',', $pdf_base64);
            $this->files     = explode(",", $labelArr->label->file_url);

            $label_path = "../label";
			$this->labelPath = "$label_path/$loadIdentity/ukmail";
			$fileUrl = $this->libObj->get_api_url();//.LABEL_URL;
			//$fileUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST']."/".LABEL_URL;

            if (!file_exists("$label_path/$loadIdentity/ukmail/")) {
                $oldmask = umask(0);
                mkdir("$label_path/$loadIdentity/ukmail/", 0777, true);
                umask($oldmask);
            }

			$labelStatus = $this->saveImages()->joinImages();
			if($labelStatus){
				unset($labelArr->label->base_encode);
				return array(
					"status" => "success",
					"message" => "label generated successfully",
					"file_path" => $fileUrl . "/label/" . $this->loadIdentity . '/ukmail/' . $this->loadIdentity . '.pdf',
					"label_tracking_number" => $labelArr->label->tracking_number,
					"label_files_png" => implode(',', $this->images),
					"label_file_pdf" => $fileUrl . "/label/" . $this->loadIdentity . '/ukmail/' . $this->loadIdentity . '.pdf',
					"label_json" => json_encode($labelArr),
					"child_account_data" =>$child_account_data
				);
			}else{
				return array(
					"status" => "error",
					"message" => "Label generation error, pdf not created successfully"
				);
			}
        } else {
            return array(
                "status" => "error",
                "message" => $labelArr->error
            );
        }

    }

	public function saveImages(){
		$this->images = array();
        foreach($this->files as $key=>$file){
            $file = str_replace('pdf','png',$file);
            $img = str_replace('data:image/png;base64,', '', $this->allImagesString[$key]);
            $img = str_replace(' ', '+', $img);
            $data = base64_decode($img);

            $fileName = uniqid().".png";

            $file = "$this->labelPath/$fileName";
            $success = file_put_contents($file, $data);
			      header('Content-Type: application/image');
            array_push($this->images, $file);
        }
        return $this;
    }

    private function joinImages(){
        $config = array(
            'mode' => 'c',
			'margin_left' => 15,
            'margin_right' => 0,
            'margin_top' => 5,
            //'margin_bottom' => 25,
            //'margin_header' => 16,
            //'margin_footer' => 13,
			//'format' => 'A4',
			'format' => array(101,152),
			'orientation' => 'L'
        );
        $mpdf = new \Mpdf\Mpdf($config);
        foreach($this->images as $image) {
            $size = getimagesize ($image);
            $width = $size[0];
            $height = $size[1];
            $mpdf->Image($image,250,140,$width,$height,'png','',true, true);
        }
        try{
            $mpdf->Output("$this->labelPath/$this->loadIdentity.pdf","F");
            return true;
        }catch(Exception $e){
            return false;
        }
  }
  
  
  /* private function joinImages(){
        $config = array(
            'mode' => 'c',
			'format' => 'A4-L',
			'debug' => true
        );
        $mpdf = new \Mpdf\Mpdf($config);
        foreach($this->images as $image) {
            $size = getimagesize ($image);
            $width = $size[0];
            $height = $size[1];
            $mpdf->Image($image,60,50,$width,$height,'png','',true, true);
        }
        try{
            $mpdf->Output("$this->labelPath/$this->loadIdentity.pdf","F");die;
            return true;
        }catch(Exception $e){
            return false;
        }
  } */

  public function getShipmentDataFromCarrier($loadIdentity,$allData = array())
    {
        $response     = array();
        $shipmentInfo = $this->modelObj->getShipmentDataByLoadIdentity($loadIdentity);

		/*foreach($allData as $key=>$data){
			if($key=='collection'){
				foreach($data as $collectionData){
					$response['from']      = array(
                    "name" => $collectionData->name,
                    "company" => $collectionData->company_name,
                    "phone" => $collectionData->phone,
                    "street1" => $collectionData->address_line1,
                    "street2" => $collectionData->address_line2,
                    "city" => $collectionData->city,
                    "state" => $collectionData->state,
                    "zip" => $collectionData->postcode,
                    "country" => $collectionData->country->alpha2_code,
                    "country_name" => $collectionData->country->short_name,
                    "is_apo_fpo" => ""
					);
				}
			}elseif($key=='delivery'){
				foreach($data as $deliveryData){
					$response['to'] = array(
						"name" => $deliveryData->name,
						"company" => $deliveryData->company_name,
						"phone" => $deliveryData->phone,
						"street1" => $deliveryData->address_line1,
						"street2" => $deliveryData->address_line2,
						"city" => $deliveryData->city,
						"state" => $deliveryData->state,
						"zip" => $deliveryData->postcode,
						"zip_plus4" => "",
						"country" => $deliveryData->country->alpha2_code,
						"country_name" => $deliveryData->country->short_name,
						"email" => $deliveryData->email,
						"is_apo_fpo" => "",
						"is_residential" => ""
					);
				}
			}
		}
		foreach ($shipmentInfo as $key => $data) {
			$response['carrier'] = $data['carrier_code'];
			$response['ship_date'] = $data['shipment_required_service_date'];
			$carrierAccountNumber = $data["carrier_account_number"];
		}*/


         foreach ($shipmentInfo as $key => $data) {
            if ($data['shipment_service_type'] == 'P') {
                $response['from']      = array(
                    "name" => $data["shipment_customer_name"],
                    "company" => $data["shipment_companyName"],
                    "phone" => $data["shipment_customer_phone"],
                    "street1" => $data["shipment_address1"],
                    "street2" => $data["shipment_address2"],
                    "city" => $data["shipment_customer_city"],
                    "state" => $data["shipment_county"],
                    "zip" => $data["shipment_postcode"],
                    "country" => $data["shipment_country_code"],
                    "country_name" => $data["shipment_customer_country"],
                    "is_apo_fpo" => ""
                );
                $response['ship_date'] = $data['shipment_required_service_date'];

            } elseif ($data['shipment_service_type'] == 'D') {
                $response['carrier'] = $data['carrier_code'];

                $response['to'] = array(
                    "name" => $data["shipment_customer_name"],
                    "company" => $data["shipment_companyName"],
                    "phone" => $data["shipment_customer_phone"],
                    "street1" => $data["shipment_address1"],
                    "street2" => $data["shipment_address2"],
                    "city" => $data["shipment_customer_city"],
                    "state" => $data["shipment_county"],
                    "zip" => $data["shipment_postcode"],
                    "zip_plus4" => "",
                    "country" => $data["shipment_country_code"],
                    "country_name" => $data["shipment_customer_country"],
                    "email" => $data["shipment_customer_email"],
                    "is_apo_fpo" => "",
                    "is_residential" => ""
                );

                $carrierAccountNumber = $data["carrier_account_number"];
                //$response['ship_date'] = $data['shipment_required_service_date'];
            }
        }
        $response['package']     = $this->getPackageInfo($loadIdentity);
        $serviceInfo             = $this->getServiceInfo($loadIdentity);
		$delivery_instruction    = $this->modelObj->getDeliveryInstructionByLoadIdentity($loadIdentity);
        $response['currency']    = $serviceInfo['currency'];
        $response['service']     = $serviceInfo['service_code'];
        $response['credentials'] = $this->getCredentialInfo($carrierAccountNumber, $loadIdentity);
		
		/*start of binding child account data*/
		if($response['credentials']['is_child_account']=='yes'){
			$child_account_data = array("is_child_account"=>$response['credentials']['is_child_account'],
										"parent_account_number"=>$response['credentials']['parent_account_number'],
										"child_account_number"=>$response['credentials']['credentials']['account_number']);
		}else{
			$child_account_data = array();
		}
		/*end of binding child account data*/
		
		$response['credentials'] = $response['credentials']['credentials'];

        $response['extra']           = array(
            "service_key" => $serviceInfo['service_code'],
            "long_length" => "",
            "bookin" => "",
            "exchange_on_delivery" => "",
            "reference_id" => "",
            "region_code" => "",
            "confirmation" => "",
            "is_document" => "",
            "auto_return" => "",
            "return_service_id" => "",
            "special_instruction" => $delivery_instruction['shipment_instruction'] ,
            "custom_desciption" =>$serviceInfo['customer_reference1'],
            "custom_desciption2" =>$serviceInfo['customer_reference2'],
            "custom_desciption3" => "",
            "customs_form_declared_value" => "",
            "document_only" => "",
            "no_dangerous_goods" => "",
            "in_free_circulation_eu" => "",
            "extended_cover_required" => isset($allData->is_insured) ? $allData->insurance_amount : "",
            "invoice_type" => ""
        );

		$response['currency'] = isset($serviceInfo['currency']) && !empty($serviceInfo['currency']) ? $serviceInfo['currency'] : 'GBP';
		$response['insurance'] = array('value' => (isset($allData->is_insured) ? $allData->insurance_amount : 0) , 'currency' => $response['currency'], 'insurer' => '');
        $response['constants']       = array(
            "shipping_charge" => "",
            "weight_charge" => "",
            "fuel_surcharge" => "",
            "remote_area_delivery" => "",
            "insurance_charge" => "",
            "over_sized_charge" => "",
            "over_weight_charge" => "",
            "discounted_rate" => ""
        );
        $response['label_options']   = "";
        $response['customs']         = "";
        $response['billing_account'] = array(
            "payor_type" => "",
            "billing_account" => "",
            "billing_country_code" => "",
            "billing_person_name" => "",
            "billing_email" => ""
        );
        $response['label']           = array();
        $response['method_type']     = "post";
		
        return $this->_getLabel($loadIdentity,json_encode($response),$child_account_data);
        //return $response;

    }

    public function getPackageInfo($loadIdentity)
    {
        $packageData = array();
        $packageInfo = $this->modelObj->getPackageDataByLoadIdentity($loadIdentity);
        foreach ($packageInfo as $data) {
            array_push($packageData, array(
                "packaging_type" => $data["package"],
                "width" => $data["parcel_width"],
                "length" => $data["parcel_length"],
                "height" => $data["parcel_height"],
                "dimension_unit" => "CM",
                "weight" => $data["parcel_weight"],
                "weight_unit" => "KG"
            ));
        }
        return $packageData;
    }

    public function getServiceInfo($loadIdentity)
    {
        $serviceInfo = $this->modelObj->getServiceDataByLoadIdentity($loadIdentity);
        return $serviceInfo;
    }

    public function getCredentialInfo($carrierAccountNumber, $loadIdentity)
    {
        $credentialData = array();
		/*start of check if customer have any ukmail child account or not*/
		$getChildAccountData = $this->modelObj->getChildAccountData($carrierAccountNumber,$loadIdentity);
		/*end of check if customer have any ukmail child account or not*/
		if(count($getChildAccountData)>0){
			$credentialData = $getChildAccountData;
			$parent_account_number = $carrierAccountNumber;
			$carrierAccountNumber = $getChildAccountData['account_number'];
			$is_child_account = "yes";
		}else{
			$credentialData = $this->modelObj->getCredentialDataByLoadIdentity($carrierAccountNumber, $loadIdentity);
			$is_child_account = "no";
			$parent_account_number = "";
		}
		
        

        $credentialInfo["username"]                        = $credentialData["username"];
        $credentialInfo["password"]                        = $credentialData["password"];
        $credentialInfo["authentication_token"]            = $credentialData["authentication_token"];
        $credentialInfo["authentication_token_created_at"] = $credentialData["authentication_token_created_at"];
        $credentialInfo["token"]                           = $credentialData["token"];
        $credentialInfo["account_number"]                  = $carrierAccountNumber;
        $credentialInfo["master_carrier_account_number"]   = "";
        $credentialInfo["latest_time"]                     = "17:00:00";
        $credentialInfo["earliest_time"]                   = "14:00:00";
        $credentialInfo["carrier_account_type"]            = array(
            "1"
        );

        /* $credentialInfo["username"] = "info@pedalandpost.co.uk";
        $credentialInfo["password"] = "x65we30pg";//"casi0advent";
        $credentialInfo["authentication_token"] = "";
        $credentialInfo["authentication_token_created_at"] = "";
        $credentialInfo["token"] ="";
        $credentialInfo["account_number"] = "D052411";//"K906430";
        $credentialInfo["master_carrier_account_number"] = "";
        $credentialInfo["latest_time"] = "17:00:00";
        $credentialInfo["earliest_time"]="14:00:00";
        $credentialInfo["carrier_account_type"] = array("1"); */

        return array("credentials"=>$credentialInfo,"is_child_account"=>$is_child_account,"parent_account_number"=>$parent_account_number);
    }

    private function validate($data)
    {
        $error = array();
        //call validation function from validation class
        if (!Ukmail_Validation::_getInstance()->firstName('first_name')) {
            $error['first_name'] = Ukmail_Validation::_getInstance()->errorMsg;
        }
        if (!Ukmail_Validation::_getInstance()->lastName('last_name')) {
            $error['last_name'] = Ukmail_Validation::_getInstance()->errorMsg;
        }
    }

}
?>
