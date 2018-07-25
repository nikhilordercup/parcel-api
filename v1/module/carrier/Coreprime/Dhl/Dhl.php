<?php


require_once dirname(dirname(__FILE__)) . "/Carrier_Coreprime_Request.php";

/* implements CarrierInterface */

final class Coreprime_Dhl extends Carrier {

    public $modelObj = null;

    public function __construct() {
        $this->modelObj = new Booking_Model_Booking();
    }

    private function _getLabel($loadIdentity, $json_data) {
        
        $obj = new Carrier_Coreprime_Request();
        $label = $obj->_postRequest("label", $json_data);
        //print_r($label);die;
        $labelArr = json_decode($label);
        if( isset($labelArr->label) ) {
            $pdf_base64 = $labelArr->label->base_encode;
            $labels = explode(",", $labelArr->label->file_url);
            //print_r($label);die;
            //Get File content from txt file
            //$pdf_base64_handler = fopen($pdf_base64,'r');
            //$pdf_content = fread ($pdf_base64_handler,filesize($pdf_base64));
            //fclose ($pdf_base64_handler);
            $label_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/label/';
            $file_url = mkdir($label_path . $loadIdentity . '/dhl/', 0777, true);
            foreach ($labels as $dataFile) {
                $dataFile = explode(".", $dataFile);
                $dataFile = $dataFile[0] . '.pdf';
                //print_r($label_path);die;
                $file_name = $label_path . $loadIdentity . '/dhl/' . $dataFile;
                $data = base64_decode($pdf_base64);
                file_put_contents($file_name, $data);
                header('Content-Type: application/pdf');
            }
            $flabel = explode(".", $labels[0]);
            //echo $file_name;
            $fileUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

            return array("status" => "success", "message" => "label generated successfully", "file_path" => $fileUrl . "/label/" . $loadIdentity . '/dhl/' . $flabel[0] . '.pdf');
        } else {
            return array("status" => "error", "message" => $labelArr->error);
        }       
    }

    public function getShipmentDataFromCarrier($loadIdentity, $rateDetail, $allData = array()) {        
        $response = array();
        $shipmentInfo = $this->modelObj->getShipmentDataByLoadIdentity($loadIdentity);        

        foreach ($shipmentInfo as $key => $data) {
            if ($data['shipment_service_type'] == 'P') {
                $response['from'] = array(
                    'name' => isset($data['shipment_customer_name']) ? $data['shipment_customer_name'] : '',
                    'company' => isset($data['shipment_companyName']) ? $data['shipment_companyName'] : '',
                    'phone' => isset($data['shipment_customer_phone']) ? $data['shipment_customer_phone'] : '',
                    'street1' => isset($data['shipment_address1']) ? $data['shipment_address1'] : '',
                    'street2' => isset($data['shipment_address2']) ? $data['shipment_address2'] : '',
                    'city' => isset($data['shipment_customer_city']) ? $data['shipment_customer_city'] : $data['shipment_customer_city'],
                    'state' => isset($data['shipment_county']) ? $data['shipment_county'] : '',
                    'zip' => isset($data['shipment_postcode']) ? $data['shipment_postcode'] : '',
                    'zip_plus4' => '',
                    'country' => isset($data['alpha2_code']) ? $data['alpha2_code'] : '',
                    'country_name' => isset($data['shipment_customer_country']) ? $data['shipment_customer_country'] : '',
                    'is_apo_fpo' => ''
                );
                $response['ship_date'] = $data['shipment_required_service_date'];
            } elseif ($data['shipment_service_type'] == 'D') {
                $response['carrier'] = $data['carrier_code'];
                $response['to'] = array(
                    'name' => isset($data['shipment_customer_name']) ? $data['shipment_customer_name'] : '',
                    'company' => isset($data['shipment_companyName']) ? $data['shipment_companyName'] : '',
                    'phone' => isset($data['shipment_customer_phone']) ? $data['shipment_customer_phone'] : '',
                    'street1' => isset($data['shipment_address1']) ? $data['shipment_address1'] : '',
                    'street2' => isset($data['shipment_address2']) ? $data['shipment_address2'] : '',
                    'city' => isset($data['shipment_customer_city']) ? $data['shipment_customer_city'] : $data['shipment_customer_city'],
                    'state' => isset($data['shipment_county']) ? $data['shipment_county'] : '',
                    'zip' => isset($data['shipment_postcode']) ? $data['shipment_postcode'] : '',
                    'zip_plus4' => '',
                    'country' => isset($data['alpha2_code']) ? $data['alpha2_code'] : '',
                    'country_name' => isset($data['shipment_customer_country']) ? $data['shipment_customer_country'] : '',
                    'email' => isset($data['shipment_customer_email']) ? $data['shipment_customer_email'] : '',
                    'is_apo_fpo' => '',
                    'is_residential' => ''
                );
                $carrierAccountNumber = $data['carrier_account_number'];
            }
        }
                        
        $packageCode = '';
        $contents = array(); 
        foreach ($allData->parcel as $parcel) {
           $packageCode = $parcel->package_code;
           $contents[] = $parcel->name;
        }
        
        $response['credentials'] = $this->getCredentialInfo($carrierAccountNumber, $loadIdentity);
        $response['package'] = $this->getPackageInfo($loadIdentity);
        $serviceInfo = $this->getServiceInfo($loadIdentity);
        $response['currency'] = isset($serviceInfo['currency']) && !empty($serviceInfo['currency']) ? $serviceInfo['currency'] : 'GBP';
        $response['service'] = $serviceInfo['service_code'];
        $isDutiable = ( isset($allData->dutiable) && !empty($allData->dutiable) ) ?  "1" : "0";

        /*         * ********start of static data from requet json ************** */
        $response['extra'] = array(
            'reference_id' => "$loadIdentity",              // icargo order number  load identity
            'reference_id2' => '',                  // customer identification number
            'contents' => 'test description',       // contents of the parcel field, (qn for multiple ?)
            'terms_of_trade' => isset($allData->terms_of_trade) ? $allData->terms_of_trade : '',                 // ask arvind (this is only applicable for duitable shipment)
            'neutral_delivery' => "false",          // ?
            'paperless_trade' => "false",           // flag that delivery country support paperless trade
            'inxpress' => '',                       // not in use
            'region_code' => 'EU',                  // ?
            'confirmation' => '',                   // ? delivery has been done or not, confirm with Akshar?
            'inbound' => 'false',                   // import shipment, if it is coming from another country
            'is_document' => isset($allData->is_document) && !empty($allData->is_document) ? "true" : "false",               // doc or non doc
            'customs_form_declared_value' => '',    // duitable related value 
            'other_reseller_account' => '',         // not in use
            'gnd_payment_type' => '0',              // not in use
            'dutiable' => $isDutiable,              // true or false
            'residential_boolean' => '',            // yes or no (from customer)
            'itn' => '',                            // ?
            'auto_return' => '',                    // ?
            'return_service_id' => '',              // ?
            'return' => '',                         // ? 
            'package_id' => '',                     // icargo order no 
            'dry_ice_weight' => '',                 // specific value for content (ignore that)
            'dangerous_goods' => '',                // same as dry ice
            'order_number_barcode_format' => '',    // ?
            'order_number' => '',                   // ? (may be order number of icargo)
            'delivery_instruction' => '',           // print in form
            'home_delivery_premium_type' => '',     // ?
            'future_day_shipment' => '',            // ?
            'saturday_delivery' => '',              // not applicable
            'fedex_one_rate' => '',                 // works only for fedex
            'dry_ice' => '',                        // specific value for content (ignore that)
            'international' => '',                  // ?
            'image_type' => '',                     // ? can say label file type
            'print_to_screen' => 'false',           // no idea
            'mask_account_number' => '',            // not needed
            'intra_eu_shipping' => '',              // not needed
            'package_type' => "$packageCode",                   // package type ( Asked for multiple
            'payer_of_duties' => '',                // ask receiver will pay or sender will pay
            'dropoff_type' => '',                   // ?
            'thermal_image' => '',                  // ?
            'invoice_number' => ''                  // ?
        );
        
        $response['insurance'] = array('value' => '', 'currency' => $response['currency'], 'insurer' => '');
        
        if ($rateDetail) {
            $rateDetail = (array) $rateDetail;
            unset($rateDetail['price']);
            unset($rateDetail['info']);
            unset($rateDetail['currency']);
            unset($rateDetail['rate_type']);
        }
        
        $response['constants'] = $rateDetail;
        $response['label_options'] = array('format' => 'EPL2', 'size' => '', 'rotation' => '');
        $response['customs'] = '';
        $response['billing_account'] = array('payor_type' => '', 'billing_account' => '', 'billing_country_code' => '', 'billing_person_name' => '', 'billing_email' => '');
        $response['label'] = array();
        $response['method_type'] = 'post';
        
        $items = array(); 
        $totalValue = 0;
                
        if(isset($allData->items)) {            
            $key = 0;
            foreach ( $allData->items as $item ) {                
                $items[$key]['item_description'] = $item->item_description;
                $items[$key]["item_quantity"] = $item->item_quantity;
                $items[$key]["country_of_origin"] = $item->country_of_origin->alpha2_code;
                $items[$key]["item_value"] = $item->item_value;                
                $items[$key]["hs_code"] = '';
                $items[$key]["item_code"] = '';
                $items[$key]["item_weight"] = '';
                                
                $totalValue = $totalValue + $item->item_value;
                $key++;
            }
        }
        
        $response['customs'] = array( 
            'items' => $items, 
            'declared_value' => "$totalValue", 
            'total_weight' => '', 
            'terms_of_trade' => isset($allData->terms_of_trade) ? $allData->terms_of_trade : '', 
            'contents' => ($contents) ? implode(', ', $contents) : ''
        );
        
        $response['extra']['contents'] = ($contents) ? implode(', ', $contents) : $response['extra']['contents'];
        $response['extra']['customs_form_declared_value'] = "$totalValue";
        
        /**********end of static data from requet json ************** */
        //print_r($response);die;
        return $this->_getLabel($loadIdentity, json_encode($response));
        //return $response;
    }

    public function getPackageInfo($loadIdentity) {
        $packageData = array();
        $packageInfo = $this->modelObj->getPackageDataByLoadIdentity($loadIdentity);
        foreach ($packageInfo as $data) {
            array_push($packageData, array("packaging_type" => $data["package"], "width" => $data["parcel_width"], "length" => $data["parcel_length"], "height" => $data["parcel_height"], "dimension_unit" => "CM", "weight" => $data["parcel_weight"], "weight_unit" => "KG"));
        }
        return $packageData;
    }

    public function getServiceInfo($loadIdentity) {
        $serviceInfo = $this->modelObj->getServiceDataByLoadIdentity($loadIdentity);
        return $serviceInfo;
    }

    public function getCredentialInfo($carrierAccountNumber, $loadIdentity) {
        $credentialData = array();
        //$credentialInfo = $this->modelObj->getCredentialDataByLoadIdentity($carrierAccountNumber, $loadIdentity);

        $credentialInfo["username"] = "kuberusinfos";
        $credentialInfo["password"] = "GgfrBytVDz";
        $credentialInfo["third_party_account_number"] = "";
        $credentialInfo["account_number"] = "420714888";
        $credentialInfo["token"] = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoyLCJlbWFpbCI6InNtYXJnZXNoQGdtYWlsLmNvbSIsImlzcyI6Ik9yZGVyQ3VwIG9yIGh0dHBzOi8vd3d3Lm9yZGVyY3VwLmNvbS8iLCJpYXQiOjE1MDI4MjQ3NTJ9.qGTEGgThFE4GTWC_jR3DIj9NpgY9JdBBL07Hd-6Cy-0";

        /* $credentialInfo["account_number"] = $carrierAccountNumber;
          $credentialInfo["master_carrier_account_number"] = "";
          $credentialInfo["latest_time"] = "";
          $credentialInfo["earliest_time"] = "";
          $credentialInfo["carrier_account_type"] = array("1"); */
        return $credentialInfo;
    }

    private function validate($data) {
        $error = array();
        //call validation function from validation class
        if (!Dhl_Validation::_getInstance()->firstName('first_name')) {
            $error['first_name'] = Dhl_Validation::_getInstance()->errorMsg;
        }
        if (!Dhl_Validation::_getInstance()->lastName('last_name')) {
            $error['last_name'] = Dhl_Validation::_getInstance()->errorMsg;
        }
    }

}

?>