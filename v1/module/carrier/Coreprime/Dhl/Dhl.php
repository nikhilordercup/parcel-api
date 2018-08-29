<?php


require_once dirname(dirname(__FILE__)) . "/Carrier_Coreprime_Request.php";
use Dompdf\Dompdf;

/* implements CarrierInterface */

final class Coreprime_Dhl extends Carrier {

    public $modelObj = null;

    public function __construct() {
        $this->modelObj = new Booking_Model_Booking();
    }

    private function _getLabel($loadIdentity, $json_data) {
        
        //echo "$json_data"; die;
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
            $file_url = mkdir($label_path . $loadIdentity .'/dhl/', 0777, true);
            foreach ($labels as $dataFile) {
                //$dataFile = explode(".", $dataFile);
                $dataFile = $loadIdentity . '.pdf';
                //print_r($label_path);die;
                $file_name = $label_path . $loadIdentity .'/dhl/'. $dataFile;
                $data = base64_decode($pdf_base64);
                file_put_contents($file_name, $data);
                header('Content-Type: application/pdf');
            }
            //echo $file_name;
            $fileUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'].LABEL_URL;

            //return array("status" => "success", "message" => "label generated successfully", 'label_detail'=> $labelArr, "file_loc"=>$file_name, "file_path" => $fileUrl . "/label/" . $loadIdentity . '/dhl/' . $flabel[0] . '.pdf');
            unset($labelArr->label->base_encode);            
            
            return array(
                    "status" => "success",
                    "message" => "label generated successfully",
                    "label_detail" => $labelArr, 
                    "file_loc"=>$file_name, 
                    "file_path" => $fileUrl . "/label/" . $loadIdentity . '/dhl/' . $loadIdentity . '.pdf',
                    "label_tracking_number"=>$labelArr->label->tracking_number,
                    "label_files_png" => '',
                    "label_json" =>json_encode($labelArr)
            );
            
        } else {
            return array("status" => "error", "message" => $labelArr->error);
        }       
    }

    public function getShipmentDataFromCarrier($loadIdentity, $rateDetail, $allData = array()) {        
        //print_r($allData); die;
        $response = array();
        $shipmentInfo = $this->modelObj->getShipmentDataByLoadIdentity($loadIdentity);        
        $paperLessTrade = false;
        foreach ($allData->delivery as $deliver) {
            $paperLessTrade = ($deliver->country->paperless_trade) ? true : false;
        }
        
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
            'paperless_trade' => "$paperLessTrade",           // flag that delivery country support paperless trade
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
        
        $response['insurance'] = array('value' => ( isset($allData->is_insured) ? $allData->insurance_amount : 0 ) , 'currency' => $response['currency'], 'insurer' => '');
        
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
        $totalValue = $totalWeight = 0;
                
        if(isset($allData->items)) {            
            $key = 0;
            foreach ( $allData->items as $item ) {                         
                $items[$key]['item_description'] = $item->item_description;
                $items[$key]["item_quantity"] = $item->item_quantity;
                $items[$key]["country_of_origin"] = $item->country_of_origin->alpha2_code;
                $items[$key]["item_value"] = $item->item_value;                
                $items[$key]["hs_code"] = '';
                $items[$key]["item_code"] = '';
                $items[$key]["item_weight"] = $item->item_weight;
                                
                $totalValue = $totalValue + $item->item_value * $item->item_quantity;
                $totalWeight = $totalWeight + $item->item_weight * $item->item_quantity;
                $key++;
            }
        } else {
            $totalValue = ( isset($allData->is_insured) ? $allData->insurance_amount : 0 ) ;
        }
        
        $response['customs'] = array( 
            'items' => $items, 
            'declared_value' => "$totalValue", 
            'total_weight' => $totalWeight, 
            'terms_of_trade' => isset($allData->terms_of_trade) ? $allData->terms_of_trade : '', 
            'contents' => ($contents) ? implode(', ', $contents) : ''
        );
        
        $response['extra']['contents'] = ($contents) ? implode(', ', $contents) : $response['extra']['contents'];
        $response['extra']['customs_form_declared_value'] = "$totalValue";
        
        /**********end of static data from requet json ************** */
        $response = $this->_getLabel($loadIdentity, json_encode($response));
        if( !$paperLessTrade && ($response['status'] != 'error') && $allData->dutiable ) {
            $customResp = $this->_getCustomInvoice($allData, $loadIdentity, $response);
        } else {
            unset($response['label_detail']);
        }
        return $response;
    }

    
    private function _getCustomInvoice($allData, $loadIdentity, $labelDetail) {        
        $label = $labelDetail['label_detail']->label;
        //$allData = '{"collection":{"0":{"geo_position":{"latitude":"0.00000000","longitude":"0.00000000"},"address_origin":"local","country":{"id":"235","short_name":"United Kingdom","alpha2_code":"GB","alpha3_code":"GBR","numeric_code":"826","currency_code":"GBP","weight_dutiable_limit":"2","paperless_trade":"0","postal_type":"1","job_type":"0"},"name":"kavitatest","phone":"+4499999","company_name":"PCS-Test","address_line1":"H-140","address_line2":"5th Floor","city":"Oxford","state":"Oxfordshire","postcode":"OX39RL","notification":true,"address_list":{"id":"333","address":"H-140, 5th Floor, OX39RL"},"carrier_code":"DHL"}},"delivery":{"0":{"country":{"id":"236","short_name":"United States","alpha2_code":"US","alpha3_code":"USA","numeric_code":"840","currency_code":"","weight_dutiable_limit":"2","paperless_trade":"0","postal_type":"1","job_type":"0"},"city":"New York","postcode":"10013","address_line1":"92 Lafayette St, New York, NY 10013, USA","address_line2":"","notification":true,"geo_position":{"latitude":40.7175676,"longitude":-74.0015897},"carrier_code":"DHL","state":"","name":"Amita Pandey","phone":"9412091082"}},"dutiable":true,"is_document":false,"customer_id":"375","customer_user_id":"375","collection_user_id":"375","collection_date":"2018-07-29 13:45","parcel":{"0":{"quantity":1,"weight":3,"length":22,"width":20,"height":20,"name":"custom package (DHL\/UKMAIL)","package_code":"CP"}},"booked_by":"92","warehouse_id":"1","carrier":"all","flow_type":"","email":"testcontroller123@gmail.com","access_token":"OTMwMS01YjVjMGE4MGI4MTUxLTky","company_id":"10","endPointUrl":"bookNextDayJob","collected_by_carrier":{"0":{"carrier":[{"carrier_code":"DHL","account_number":"420714888","is_internal":"0","name":"DHL","icon":"assets\/images\/carrier\/dhl.png","pickup_surcharge":"2.00","collection_date_time":"2018-07-30 09:00","collection_start_at":"09:00","collection_end_at":"17:00","is_regular_pickup":"yes","carrier_id":"3","pickup":"1","surcharges":{"fuel_surcharge":{"original_price":"0.00","surcharge_value":"2.00","operator":"FLAT","price":"2.00","company_surcharge_code":"fuel_surcharge","company_surcharge_name":"Fuel Surcharge","courier_surcharge_code":"fuel_surcharge","courier_surcharge_name":"Fuel Surcharge","level":"level 1","surcharge_id":"5","price_with_ccf":"2.00","carrier_id":"3"},"remote_area_delivery":{"original_price":"0.00","surcharge_value":"3.00","operator":"FLAT","price":"3.00","company_surcharge_code":"remote_area_delivery","company_surcharge_name":"Remote Area Delivery","courier_surcharge_code":"remote_area_delivery","courier_surcharge_name":"Remote Area Delivery","level":"level 1","surcharge_id":"6","price_with_ccf":"3.00","carrier_id":"3"},"insurance_charge":{"original_price":"0.00","surcharge_value":"10.00","operator":"FLAT","price":"10.00","company_surcharge_code":"insurance_charge","company_surcharge_name":"Insurance Charge","courier_surcharge_code":"insurance_charge","courier_surcharge_name":"\r\nInsurance Charge","level":"level 1","surcharge_id":"7","price_with_ccf":"10.00","carrier_id":"3"},"over_weight_charge":{"original_price":"0.00","surcharge_value":"11.00","operator":"FLAT","price":"11.00","company_surcharge_code":"over_weight_charge","company_surcharge_name":"Over Weight Charge","courier_surcharge_code":"over_weight_charge","courier_surcharge_name":"Over Weight Charge","level":"level 1","surcharge_id":"9","price_with_ccf":"11.00","carrier_id":"3"}},"carrier_price_info":{"price":"183.24","surcharges":"0.00","taxes":0,"grand_total":"183.24"},"customer_price_info":{"price":"188.74","surcharges":"26.00","taxes":"0.00","grand_total":"214.74"}}]},"1":{"carrier":[{"carrier_code":"DHL","account_number":"420714888","is_internal":"0","name":"DHL","icon":"assets\/images\/carrier\/dhl.png","pickup_surcharge":"2.00","collection_date_time":"2018-07-30 09:00","collection_start_at":"09:00","collection_end_at":"17:00","is_regular_pickup":"yes","carrier_id":"3","pickup":"1","surcharges":{"fuel_surcharge":{"original_price":"0.00","surcharge_value":"2.00","operator":"FLAT","price":"2.00","company_surcharge_code":"fuel_surcharge","company_surcharge_name":"Fuel Surcharge","courier_surcharge_code":"fuel_surcharge","courier_surcharge_name":"Fuel Surcharge","level":"level 1","surcharge_id":"5","price_with_ccf":"2.00","carrier_id":"3"},"remote_area_delivery":{"original_price":"0.00","surcharge_value":"3.00","operator":"FLAT","price":"3.00","company_surcharge_code":"remote_area_delivery","company_surcharge_name":"Remote Area Delivery","courier_surcharge_code":"remote_area_delivery","courier_surcharge_name":"Remote Area Delivery","level":"level 1","surcharge_id":"6","price_with_ccf":"3.00","carrier_id":"3"},"insurance_charge":{"original_price":"0.00","surcharge_value":"10.00","operator":"FLAT","price":"10.00","company_surcharge_code":"insurance_charge","company_surcharge_name":"Insurance Charge","courier_surcharge_code":"insurance_charge","courier_surcharge_name":"\r\nInsurance Charge","level":"level 1","surcharge_id":"7","price_with_ccf":"10.00","carrier_id":"3"},"over_weight_charge":{"original_price":"0.00","surcharge_value":"11.00","operator":"FLAT","price":"11.00","company_surcharge_code":"over_weight_charge","company_surcharge_name":"Over Weight Charge","courier_surcharge_code":"over_weight_charge","courier_surcharge_name":"Over Weight Charge","level":"level 1","surcharge_id":"9","price_with_ccf":"11.00","carrier_id":"3"}},"carrier_price_info":{"price":"183.24","surcharges":"0.00","taxes":0,"grand_total":"183.24"},"customer_price_info":{"price":"195.24","surcharges":"26.00","taxes":"0.00","grand_total":"221.24"}}]},"2":{"carrier":[{"carrier_code":"DHL","account_number":"420714888","is_internal":"0","name":"DHL","icon":"assets\/images\/carrier\/dhl.png","pickup_surcharge":"2.00","collection_date_time":"2018-07-30 09:00","collection_start_at":"09:00","collection_end_at":"17:00","is_regular_pickup":"yes","carrier_id":"3","pickup":"1","surcharges":{"fuel_surcharge":{"original_price":"0.00","surcharge_value":"2.00","operator":"FLAT","price":"2.00","company_surcharge_code":"fuel_surcharge","company_surcharge_name":"Fuel Surcharge","courier_surcharge_code":"fuel_surcharge","courier_surcharge_name":"Fuel Surcharge","level":"level 1","surcharge_id":"5","price_with_ccf":"2.00","carrier_id":"3"},"remote_area_delivery":{"original_price":"0.00","surcharge_value":"3.00","operator":"FLAT","price":"3.00","company_surcharge_code":"remote_area_delivery","company_surcharge_name":"Remote Area Delivery","courier_surcharge_code":"remote_area_delivery","courier_surcharge_name":"Remote Area Delivery","level":"level 1","surcharge_id":"6","price_with_ccf":"3.00","carrier_id":"3"},"insurance_charge":{"original_price":"0.00","surcharge_value":"10.00","operator":"FLAT","price":"10.00","company_surcharge_code":"insurance_charge","company_surcharge_name":"Insurance Charge","courier_surcharge_code":"insurance_charge","courier_surcharge_name":"\r\nInsurance Charge","level":"level 1","surcharge_id":"7","price_with_ccf":"10.00","carrier_id":"3"},"over_weight_charge":{"original_price":"0.00","surcharge_value":"11.00","operator":"FLAT","price":"11.00","company_surcharge_code":"over_weight_charge","company_surcharge_name":"Over Weight Charge","courier_surcharge_code":"over_weight_charge","courier_surcharge_name":"Over Weight Charge","level":"level 1","surcharge_id":"9","price_with_ccf":"11.00","carrier_id":"3"}},"carrier_price_info":{"price":"189.24","surcharges":"0.00","taxes":0,"grand_total":"189.24"},"customer_price_info":{"price":"201.24","surcharges":"26.00","taxes":"0.00","grand_total":"227.24"}}]}},"service_opted":{"rate":{"weight_charge":183.24,"fuel_surcharge":0,"remote_area_delivery":0,"insurance_charge":0,"over_sized_charge":0,"over_weight_charge":0,"price":"188.74","info":{"original_price":"183.24","ccf_value":"5.50","operator":"FLAT","price":"5.50","company_service_code":"","company_service_name":"","courier_service_code":"express_ww","courier_service_name":"Express Worldwide (2-4 days)","level":"level 4","service_id":"49","price_with_ccf":"188.74"},"currency":"GBP","rate_type":"Weight"},"collected_by":[{"carrier_code":"DHL","account_number":"420714888","is_internal":"0","name":"DHL","icon":"assets\/images\/carrier\/dhl.png","pickup_surcharge":"2.00","collection_date_time":"2018-07-30 09:00","collection_start_at":"09:00","collection_end_at":"17:00","is_regular_pickup":"yes","carrier_id":"3","pickup":"1","surcharges":{"fuel_surcharge":{"original_price":"0.00","surcharge_value":"2.00","operator":"FLAT","price":"2.00","company_surcharge_code":"fuel_surcharge","company_surcharge_name":"Fuel Surcharge","courier_surcharge_code":"fuel_surcharge","courier_surcharge_name":"Fuel Surcharge","level":"level 1","surcharge_id":"5","price_with_ccf":"2.00","carrier_id":"3"},"remote_area_delivery":{"original_price":"0.00","surcharge_value":"3.00","operator":"FLAT","price":"3.00","company_surcharge_code":"remote_area_delivery","company_surcharge_name":"Remote Area Delivery","courier_surcharge_code":"remote_area_delivery","courier_surcharge_name":"Remote Area Delivery","level":"level 1","surcharge_id":"6","price_with_ccf":"3.00","carrier_id":"3"},"insurance_charge":{"original_price":"0.00","surcharge_value":"10.00","operator":"FLAT","price":"10.00","company_surcharge_code":"insurance_charge","company_surcharge_name":"Insurance Charge","courier_surcharge_code":"insurance_charge","courier_surcharge_name":"\r\nInsurance Charge","level":"level 1","surcharge_id":"7","price_with_ccf":"10.00","carrier_id":"3"},"over_weight_charge":{"original_price":"0.00","surcharge_value":"11.00","operator":"FLAT","price":"11.00","company_surcharge_code":"over_weight_charge","company_surcharge_name":"Over Weight Charge","courier_surcharge_code":"over_weight_charge","courier_surcharge_name":"Over Weight Charge","level":"level 1","surcharge_id":"9","price_with_ccf":"11.00","carrier_id":"3"}},"carrier_price_info":{"price":"183.24","surcharges":"0.00","taxes":0,"grand_total":"183.24"},"customer_price_info":{"price":"188.74","surcharges":"26.00","taxes":"0.00","grand_total":"214.74"}},{"carrier_code":"PNP","account_number":"21232123","is_internal":"0","name":"PNP","icon":"assets\/images\/carrier\/default.png","pickup_surcharge":0,"collection_date_time":"2018-07-30 14:30","collection_start_at":"14:30","collection_end_at":"15:00","is_regular_pickup":"yes","carrier_id":"1","pickup":"1","surcharges":{"fuel_surcharge":{"original_price":"0.00","surcharge_value":"2.00","operator":"FLAT","price":"2.00","company_surcharge_code":"fuel_surcharge","company_surcharge_name":"Fuel Surcharge","courier_surcharge_code":"fuel_surcharge","courier_surcharge_name":"Fuel Surcharge","level":"level 1","surcharge_id":"5","price_with_ccf":"2.00","carrier_id":"3"},"remote_area_delivery":{"original_price":"0.00","surcharge_value":"3.00","operator":"FLAT","price":"3.00","company_surcharge_code":"remote_area_delivery","company_surcharge_name":"Remote Area Delivery","courier_surcharge_code":"remote_area_delivery","courier_surcharge_name":"Remote Area Delivery","level":"level 1","surcharge_id":"6","price_with_ccf":"3.00","carrier_id":"3"},"insurance_charge":{"original_price":"0.00","surcharge_value":"10.00","operator":"FLAT","price":"10.00","company_surcharge_code":"insurance_charge","company_surcharge_name":"Insurance Charge","courier_surcharge_code":"insurance_charge","courier_surcharge_name":"\r\nInsurance Charge","level":"level 1","surcharge_id":"7","price_with_ccf":"10.00","carrier_id":"3"},"over_weight_charge":{"original_price":"0.00","surcharge_value":"11.00","operator":"FLAT","price":"11.00","company_surcharge_code":"over_weight_charge","company_surcharge_name":"Over Weight Charge","courier_surcharge_code":"over_weight_charge","courier_surcharge_name":"Over Weight Charge","level":"level 1","surcharge_id":"9","price_with_ccf":"11.00","carrier_id":"3"}},"carrier_price_info":{"price":"183.24","surcharges":"0.00","taxes":0,"grand_total":"183.24"},"customer_price_info":{"price":"188.74","surcharges":"26.00","taxes":"0.00","grand_total":"214.74"}}],"surcharges":{"fuel_surcharge":0,"remote_area_delivery":0,"insurance_charge":0,"over_weight_charge":0},"carrier_info":{"carrier_id":"3","name":"DHL","icon":"assets\/images\/carrier\/dhl.png","code":"DHL","description":"courier information goes here","account_number":"420714888","is_internal":"0"},"service_info":{"code":"express_ww","name":"Express Worldwide (2-4 days)"},"collection_carrier":{"carrier_code":"DHL","account_number":"420714888","is_internal":"0","name":"DHL","icon":"assets\/images\/carrier\/dhl.png","pickup_surcharge":"2.00","collection_date_time":"2018-07-30 09:00","collection_start_at":"09:00","collection_end_at":"17:00","is_regular_pickup":"yes","carrier_id":"3","pickup":"1","surcharges":{"fuel_surcharge":{"original_price":"0.00","surcharge_value":"2.00","operator":"FLAT","price":"2.00","company_surcharge_code":"fuel_surcharge","company_surcharge_name":"Fuel Surcharge","courier_surcharge_code":"fuel_surcharge","courier_surcharge_name":"Fuel Surcharge","level":"level 1","surcharge_id":"5","price_with_ccf":"2.00","carrier_id":"3"},"remote_area_delivery":{"original_price":"0.00","surcharge_value":"3.00","operator":"FLAT","price":"3.00","company_surcharge_code":"remote_area_delivery","company_surcharge_name":"Remote Area Delivery","courier_surcharge_code":"remote_area_delivery","courier_surcharge_name":"Remote Area Delivery","level":"level 1","surcharge_id":"6","price_with_ccf":"3.00","carrier_id":"3"},"insurance_charge":{"original_price":"0.00","surcharge_value":"10.00","operator":"FLAT","price":"10.00","company_surcharge_code":"insurance_charge","company_surcharge_name":"Insurance Charge","courier_surcharge_code":"insurance_charge","courier_surcharge_name":"\r\nInsurance Charge","level":"level 1","surcharge_id":"7","price_with_ccf":"10.00","carrier_id":"3"},"over_weight_charge":{"original_price":"0.00","surcharge_value":"11.00","operator":"FLAT","price":"11.00","company_surcharge_code":"over_weight_charge","company_surcharge_name":"Over Weight Charge","courier_surcharge_code":"over_weight_charge","courier_surcharge_name":"Over Weight Charge","level":"level 1","surcharge_id":"9","price_with_ccf":"11.00","carrier_id":"3"}},"carrier_price_info":{"price":"183.24","surcharges":"0.00","taxes":0,"grand_total":"183.24"},"customer_price_info":{"price":"188.74","surcharges":"26.00","taxes":"0.00","grand_total":"214.74"}}},"reason_for_export":"Purchase","tax_status":"Company - Not VAT Registered","terms_of_trade":"DAD","items":{"1":{"country_of_origin":{"id":"4","short_name":"Algeria","alpha2_code":"DZ","alpha3_code":"DZA","numeric_code":"12","currency_code":"","weight_dutiable_limit":"0","paperless_trade":"0","postal_type":"1","job_type":"0"},"item_value":23,"item_description":"Shirt","item_quantity":7,"item_weight":0.02},"2":{"country_of_origin":{"id":"3","short_name":"Albania","alpha2_code":"AL","alpha3_code":"ALB","numeric_code":"8","currency_code":"","weight_dutiable_limit":"0","paperless_trade":"0","postal_type":"1","job_type":"0"},"item_value":31,"item_description":"Paint","item_quantity":8,"item_weight":0.04},"[object Object]":{"item_description":"Book","country_of_origin":{"id":"5","short_name":"American Samoa","alpha2_code":"AS","alpha3_code":"ASM","numeric_code":"16","currency_code":"","weight_dutiable_limit":"0","paperless_trade":"0","postal_type":"1","job_type":"0"},"item_value":21,"item_quantity":5,"item_weight":0.4}},"service_request_string":"eyJjYXJyaWVycyI6W3sibmFtZSI6IlVLTUFJTCIsImFjY291bnQiOlt7ImNyZWRlbnRpYWxzIjp7InVzZXJuYW1lIjoiZGV2ZWxvcGVyc0BvcmRlcmN1cC5jb20iLCJwYXNzd29yZCI6IkIwNjk4MDciLCJhY2NvdW50X251bWJlciI6IkQ5MTkwMjIifSwic2VydmljZXMiOiIxLDIsMyw0LDUsOSw3IiwicGlja3VwX3NjaGVkdWxlZCI6IjAifV19LHsibmFtZSI6IkRITCIsImFjY291bnQiOlt7ImNyZWRlbnRpYWxzIjp7InVzZXJuYW1lIjoia3ViZXJ1c2luZm9zIiwicGFzc3dvcmQiOiJHZ2ZyQnl0VkR6IiwiYWNjb3VudF9udW1iZXIiOiI0MjA3MTQ4ODgifSwic2VydmljZXMiOiJESEw1V0VPTjEyMDAsREhMNVdFT04wOTAwLGV4cHJlc3NfZG9tZXN0aWMsZXhwcmVzc19kb21lc3RpY18xMixleHByZXNzX2RvbWVzdGljXzksbWVkaWNhbF9leHByZXNzLGV4cHJlc3Nfd3csZXhwcmVzc193d19pbXBvcnQsREhMNVdFSU5URVhQLERITDVXRUlOVEVYUE5ELERITDVXRUlOVEVYUEVVLERITDVXRUlOVEVYUEQsREhMSUVYUDEyMDBELERITElFWFAxMDMwTkQsREhMSUVYUDEwMzBELERITElFWFAwOTAwTkQsREhMSUVYUDA5MDBELERITEVDTyxESExFQ09ELGVjb25vbXlfc2VsZWN0LGV4cHJlc3NfOTAwLGV4cHJlc3NfMTAzMCxleHByZXNzXzEyMDAsREhMSUVYUDEyMDBORCIsInBpY2t1cF9zY2hlZHVsZWQiOiIwIn1dfV0sImZyb20iOnsibmFtZSI6IiIsImNvbXBhbnkiOiIiLCJwaG9uZSI6IiIsInN0cmVldDEiOiJILTE0MCIsInN0cmVldDIiOiI1dGggRmxvb3IiLCJjaXR5IjoiT3hmb3JkIiwic3RhdGUiOiJPeGZvcmRzaGlyZSIsInppcCI6Ik9YMzlSTCIsImNvdW50cnkiOiJHQiIsImNvdW50cnlfbmFtZSI6IlVuaXRlZCBLaW5nZG9tIn0sInRvIjp7Im5hbWUiOiIiLCJjb21wYW55IjoiIiwicGhvbmUiOiIiLCJzdHJlZXQxIjoiOTIgTGFmYXlldHRlIFN0LCBOZXcgWW9yaywgTlkgMTAwMTMsIFVTQSIsInN0cmVldDIiOiIiLCJjaXR5IjoiTmV3IFlvcmsiLCJzdGF0ZSI6IiIsInppcCI6IjEwMDEzIiwiY291bnRyeSI6IlVTIiwiY291bnRyeV9uYW1lIjoiVW5pdGVkIFN0YXRlcyJ9LCJzaGlwX2RhdGUiOiIyMDE4LTA3LTI5IiwiZXh0cmEiOnsiaXNfZG9jdW1lbnQiOiJmYWxzZSIsImN1c3RvbXNfZm9ybV9kZWNsYXJlZF92YWx1ZSI6IjAifSwiY3VycmVuY3kiOiJHQlAiLCJwYWNrYWdlIjpbeyJwYWNrYWdpbmdfdHlwZSI6IkNQIiwid2lkdGgiOjIwLCJsZW5ndGgiOjIyLCJoZWlnaHQiOjIwLCJkaW1lbnNpb25fdW5pdCI6IkNNIiwid2VpZ2h0IjozLCJ3ZWlnaHRfdW5pdCI6IktHIn1dLCJ0cmFuc2l0IjpbeyJ0cmFuc2l0X2Rpc3RhbmNlIjowLCJ0cmFuc2l0X3RpbWUiOjAsIm51bWJlcl9vZl9jb2xsZWN0aW9ucyI6MCwibnVtYmVyX29mX2Ryb3BzIjowLCJ0b3RhbF93YWl0aW5nX3RpbWUiOjB9XSwic3RhdHVzIjoic3VjY2VzcyJ9","service_response_string":"eyJyYXRlIjogeyJESEwiOiBbeyI0MjA3MTQ4ODgiOiBbeyJleHByZXNzX3d3IjogW3sicmF0ZSI6IHsid2VpZ2h0X2NoYXJnZSI6IDE4My4yNCwiZnVlbF9zdXJjaGFyZ2UiOiAwLCJyZW1vdGVfYXJlYV9kZWxpdmVyeSI6IDAsImluc3VyYW5jZV9jaGFyZ2UiOiAwLCJvdmVyX3NpemVkX2NoYXJnZSI6IDAsIm92ZXJfd2VpZ2h0X2NoYXJnZSI6IDB9fV19LCB7ImV4cHJlc3NfZG9tZXN0aWMiOiBbeyJyYXRlIjogeyJ3ZWlnaHRfY2hhcmdlIjogMTgzLjI0LCJmdWVsX3N1cmNoYXJnZSI6IDAsInJlbW90ZV9hcmVhX2RlbGl2ZXJ5IjogMCwiaW5zdXJhbmNlX2NoYXJnZSI6IDAsIm92ZXJfc2l6ZWRfY2hhcmdlIjogMCwib3Zlcl93ZWlnaHRfY2hhcmdlIjogMH19XX0seyJleHByZXNzX2RvbWVzdGljXzEyIjogW3sicmF0ZSI6IHsid2VpZ2h0X2NoYXJnZSI6IDE4OS4yNCwiZnVlbF9zdXJjaGFyZ2UiOiAwLCJyZW1vdGVfYXJlYV9kZWxpdmVyeSI6IDAsImluc3VyYW5jZV9jaGFyZ2UiOiAwLCJvdmVyX3NpemVkX2NoYXJnZSI6IDAsIm92ZXJfd2VpZ2h0X2NoYXJnZSI6IDB9fV19XX1dfX0="}';

        $html = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        $html .= '<title>Commercial Invoice - '.$loadIdentity.'</title><style type="text/css"> .sender-receiver { width:500px; border-top-width: 0px; border-right-width: 0px; border-bottom-width: 0px; border-left-width: 0px; -webkit-border-horizontal-spacing: 0px; -webkit-border-vertical-spacing: 0px; font-family: arial; font-size: 14px; }</style></head><body>';
        $html .= '<table border="1" cellpadding="0" cellspacing="0" style="margin:0 auto;"><tr><td><table cellpadding="0" cellspacing="0" style="border-collapse:collapse;">';
        $html .= '<tr><td colspan="2" align="center" height="25" style="padding:5px;  font-family: arial; font-size:25px; font-weight:bold;">Commercial Invoice</td></tr><tr><td style="width:500px;">';
        $html .= '<table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-top-width: 0px; border-right-width: 0px; border-bottom-width: 0px; border-left-width: 0px; -webkit-border-horizontal-spacing: 0px; -webkit-border-vertical-spacing: 0px; font-family: arial; font-size: 14px;">';

        $collection = $delivery = '';
        $tortalW = $totalPrice = $totalQ = 0;
        $collectionDate = date('d M Y', strtotime($allData->collection_date));
        $shipItems = $allData->items;            
        $currencyCode = $allData->service_opted->rate->currency;            
        $carrier = $allData->service_opted->collected_by[0]->carrier_code;             
        $wayBillNo = $label->license_plate_number[0];

        foreach ($allData->collection as $coll) {
            $collection = $coll;            
        }                         
        foreach ($allData->delivery as $coll) {
            $delivery = $coll;            
        }     

        $sender  = '<tr><th align="left" style="padding:2px; height:25px;">Sender:</th></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.$collection->company_name.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.$collection->name.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.$collection->address_line1.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.$collection->address_line2.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.$collection->city.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.$collection->state.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.$collection->postcode.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.$collection->country->short_name.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">'.(isset($collection->email) ? $collection->email:'').'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;">Phone Number: '.$collection->phone.'</td></tr>';
        $sender .= '<tr><td style="padding:2px; height:25px;"></td></tr></table></td>';

        $receiver = '<td style="width:500px;"><table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-top-width: 0px; border-right-width: 0px; border-bottom-width: 0px; border-left-width: 0px; -webkit-border-horizontal-spacing: 0px; -webkit-border-vertical-spacing: 0px; font-family: arial; font-size: 14px;">';
        $receiver .= '<tr><th align="left" style="padding:2px; height:25px;"> Recipient:</th></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.(isset($delivery->company_name) ? 'testdeliver@gmail.com':'').'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.$delivery->name.'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.$delivery->address_line1.'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.$delivery->address_line2.'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.$delivery->city.'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.( isset($delivery->state) ? $delivery->state : '').'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.$delivery->postcode.'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.$delivery->country->short_name.'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">'.(isset($delivery->email) ? $delivery->email:'').'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;">Phone Number: '.$delivery->phone.'</td></tr>';
        $receiver .= '<tr><td style="padding:2px; height:25px;"></td></table></td></tr></table></td></tr>';

        $invoice = '<tr><td><table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;  font-family: arial; font-size: 14px;">';
        $invoice .= '<tr><td style="width:500px;"><table width="100%" border="1" cellpadding="0" cellspacing="0" style=" font-family: arial;font-size: 14px;">';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Invoice Date:</th><td align="left" style="padding:2px; height:25px;"> '.$collectionDate.'</td></tr>';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">DHL Waybill Number: </th><td align="left" style="padding:2px; height:25px;"> '.$wayBillNo.' </td></tr>';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Carrier: </th><td align="left" style="padding:2px; height:25px;"> '.$carrier.'</td></tr>';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Type of Export:</th><td align="left" style="padding:2px; height:25px;"> '.$allData->reason_for_export.' </td></tr>';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Reason for Export: </th><td align="left" style="padding:2px; height:25px;"> '.$allData->reason_for_export.' </td></tr>';
        $invoice .= '</table></td><td style="width:500px;"><table width="100%" border="1" cellpadding="0" cellspacing="0" style=" font-family: arial; font-size: 14px;">';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Invoice Number:</th><td align="left" style="padding:2px; height:25px;"></td></tr>';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Sender\'s Reference: </th><td align="left" style="padding:2px; height:25px;">'.( isset($collection->recipient_ref) ? $collection->recipient_ref : '' ).'</td></tr>';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Recipient\'s Reference: </th><td align="left" style="padding:2px; height:25px;">'.( isset($delivery->sender_ref) ? $delivery->sender_ref : '' ).'</td></tr>';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Type of Export:</th><td align="left" style="padding:2px; height:25px;"> '.$allData->terms_of_trade.' </td></tr>';
        $invoice .= '<tr><th align="left" style="padding:2px; height:25px;">Tax Id/VAT/EIN#: </th><td align="left" style="padding:2px; height:25px;">'.$allData->tax_status.'</td></tr>';
        $invoice .= '</table></td></tr></table></td></tr>';

        $gNotes = '<tr><th style="padding:2px; height:25px; text-align:left; font-family:arial; font-size:20px;" colspan="2">General Notes:</th></tr><tr><td style="padding:2px; height:25px;" colspan="2"></td></tr><tr><td>';

        $items = '<table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-top-width: 0px; border-right-width: 0px; border-bottom-width: 0px; border-left-width: 0px; -webkit-border-horizontal-spacing: 0px; -webkit-border-vertical-spacing: 0px; font-family: arial; font-size: 14px;">';
        $items .= '<tr><th align="left" style="padding:2px; height:25px;"> Quantity</th><th align="left" style="padding:2px; height:25px;"> Country of Origin</th><th align="left" style="padding:2px; height:25px;"> Description of Contents</th><th align="left" style="padding:2px; height:25px;"> Harmonised Code </th>';
        $items .= '<th align="left" style="padding:2px; height:25px;"> Unit Weight</th><th align="left" style="padding:2px; height:25px;"> Unit Value </th><th align="left" style="padding:2px; height:25px;"> SubTotal </th></tr>';

        foreach ($shipItems as $item) {
            //Loop start
            $items .= '<tr><td align="right" style="padding:2px; height:25px;">'.$item->item_quantity.'</td><td align="left" style="padding:2px; height:25px;">'.$item->country_of_origin->short_name.'</td>';
            $items .= '<td align="left" style="padding:2px; height:25px;">'.$item->item_description.'</td><td align="left" style="padding:2px; height:25px;"></td>';
            $items .= '<td align="right" style="padding:2px; height:25px;">'.$item->item_weight.' kgs</td><td align="right" style="padding:2px; height:25px;">'.$item->item_value.'</td>';
            $items .= '<td align="right" style="padding:2px; height:25px;">'.($item->item_quantity * $item->item_value).'</td></tr>';
            //Loop end here
            $tortalW += $item->item_quantity * $item->item_weight;
            $totalPrice += $item->item_quantity * $item->item_value;
            $totalQ += $item->item_quantity;

        }

        $otherChanrges = ( $label->fuel_surcharge +$label->remote_area_delivery + $label->over_sized_charge + $label->over_weight_charge );

        $items .= '<tr><td align="left" style="padding:2px; height:25px;"><strong>Total Net Weight:</strong></td><td align="right" style="padding:2px; height:25px;"> '.$tortalW.' kgs </td>';
        $items .= '<td align="left" style="padding:2px; height:25px;"><strong>Total Declared Value:</strong> ('.$currencyCode.')</td><td colspan="4" align="right" style="padding:2px; height:25px;">'.$totalPrice.'</td></tr>';
        $items .= '<tr><td align="left" style="padding:2px; height:25px;"><strong> Total Gross Weight:</strong></td><td align="right" style="padding:2px; height:25px;"> '.$tortalW.' kgs </td>';
        //"total_cost": 32.64, "weight_charge": 32.64, "fuel_surcharge": 0,"remote_area_delivery": 0,"insurance_charge": 0,"over_sized_charge": 0,"over_weight_charge": 0,"discounted_rate": 32.64,"product_content_code": "WPX","license_plate_number": ["JD011000000002811859"],"chargeable_weight": "0.5","service_area_code": "LON",
        $charges = '<td align="left" style="padding:2px; height:25px;"><strong>Freight & Insurance Charges:</strong> ('.$currencyCode.')</td><td colspan="4" align="right" style="padding:2px; height:25px;">'.$label->insurance_charge.'</td></tr>';
        $charges .= '<tr><td align="left" style="padding:2px; height:25px;"><strong>Total Shipment Pieces:</strong></td><td align="right" style="padding:2px; height:25px;"> '.$totalQ.' </td>';
        $charges .= '<td align="left" style="padding:2px; height:25px;"><strong> Other Charges: </strong> ('.$currencyCode.')</td><td colspan="4" align="right" style="padding:2px; height:25px;">'.$otherChanrges.'</td></tr>';
        $charges .= '<tr><td align="left" style="padding:2px; height:25px;"><strong>Currency Code:</strong></td><td align="left" style="padding:2px; height:25px;"> '.$currencyCode.' </td>';
        $charges .= '<td align="left" style="padding:2px; height:25px;"><strong> Total Invoice Amount: </strong> ('.$currencyCode.')</td><td colspan="4" align="right" style="padding:2px; height:25px;">'.$label->total_cost.'</td></tr></table></td></tr></table>';

        $div = '<div style="width:1000px; margin:0 auto; font-family:arial; font-size:14px; padding:35px 0 0 0px; line-height:20px;"> These commodities, technology or software were exported from United States Of America in accordance with the Export Administration Regulations. Diversion contrary to United States Of America law is prohibited. </div>';
        $div .= '<div style="width:1000px; margin:0 auto; font-family:arial; font-size:14px; padding:35px 0px; line-height:20px;"> I/We hereby certify that the information on this invoice is true and correct and that the contents of this shipment are as stated above. </div>';
        $div .= '<div style="width:1000px; margin:0 auto; font-family:arial; font-size:14px;"><div style="width:50%; float:left;"><h4 style="float: left; font-size: 16px; width: 50px; ">Signature:</h4>';
        $div .= '<p style="float: left; height: 2px; width: 200px; background-color: #000; margin-top: 35px; margin-left: 85px;"></p></div>';
        $div .= '<div style="width:25%; float:right;">;<h4 style="float: left; font-size: 16px; width: 50px; ">Date:</h4><p style="float: right; height: 2px; width: 200px; background-color: #000; margin-top: 35px;"></p></div>';
        $div .= '<div style="clear: both;"></div><div style="width:500px; margin-bottom:4px; float:left;"><span style="float: left; margin:0px; width: 20%; font-size: 16px; font-weight:bold;"> Name: </span>';
        $div .= '<span style="float: left; margin:0 10px 0 70px; width: 117px;">'.$delivery->name.'</span></div><div style="clear: both;"></div>';
        $div .= '<div style="width:100%; margin-bottom:30px; float:left;"><p style="float:left; margin:0px; width: 117px; font-size: 16px; font-weight:bold;"> Title: </p><p style="float: left; margin:0 0 0 70px; width: 117px;"> Sr S/W Engg</p></div></div></body></html>';


        $pdfHtml = $html . $sender . $receiver . $invoice . $gNotes . $items . $charges . $div;
        //echo $pdfHtml; die;
        // instantiate and use the dompdf class
        $dompdf = new Dompdf();
        $dompdf->loadHtml($pdfHtml);

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'landscape');

        $dompdf->render();
        
        $label_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/label/';         
                
        $invoiceName = $label_path . $loadIdentity . '/dhl/' .$loadIdentity.'-custom.pdf';
        
        file_put_contents($invoiceName, $dompdf->output());

        unset($dompdf);
        
        $labelFilePath = $labelDetail['file_loc'];
        
        $pdf = new ConcatPdf();
        $pdf->setFiles(array( $labelFilePath, $invoiceName));
        $pdf->concat();
        $pdf->Output( $label_path . $loadIdentity . '/dhl/' . $loadIdentity.'.pdf','F');
        $fileUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

        return array("status" => "success", "message" => "label generated successfully", "file_path" => $fileUrl . "/label/" . $loadIdentity . '/dhl/' . $loadIdentity.'.pdf');
     
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
    
    public function getCredentialInfo($carrierAccountNumber, $loadIdentity)
    {
        $credentialData = array();
        $credentialData = $this->modelObj->getCredentialDataByLoadIdentity($carrierAccountNumber, $loadIdentity);

        $credentialInfo["username"] = $credentialData["username"];
        $credentialInfo["password"] = $credentialData["password"];
        $credentialInfo["authentication_token"] = $credentialData["authentication_token"];
        $credentialInfo["authentication_token_created_at"] = $credentialData["authentication_token_created_at"];
        $credentialInfo["token"] = $credentialData["token"];
        $credentialInfo["account_number"] = $carrierAccountNumber; 
        $credentialInfo["master_carrier_account_number"] = "";
        $credentialInfo["latest_time"] = "17:00:00";
        $credentialInfo["earliest_time"]="14:00:00";
        $credentialInfo["carrier_account_type"] = array("1");    

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