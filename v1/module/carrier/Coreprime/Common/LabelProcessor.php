<?php
/**
 * Created by Mandeep Singh Nain.
 * User: Mandeep
 * Date: 18-12-2018
 * Time: 11:10 AM
 */

namespace v1\module\carrier\Coreprime\Common;
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'coreprime' . DIRECTORY_SEPARATOR . 'api.php';

class LabelProcessor
{
    public $modelObj = null;

    public function __construct(\Carrier $carrier)
    {
        $this->modelObj = $carrier->modelObj;
    }

    private function _getLabel($loadIdentity, $json_data,$child_account_data)
    {
        $json_data = json_decode($json_data); 
        $app = new \Slim\Slim();
        $request = json_decode($app->request->getBody());
        $json_data->email = $request->email;
        $json_data->access_token = $request->access_token;
        $obj = new \Module_Coreprime_Api($json_data);

        $label = $obj->_postRequest($json_data);
		
        $labelArr = is_string($label) ? json_decode($label, true) : $label;
        $labelArr = $labelArr['label'];
        if ($labelArr['tracking_number'] != "") {
            $labelArr['status'] = "success";
            $labelArr['file_path'] = $labelArr['file_url'];
            $labelArr['label_tracking_number'] = $labelArr['tracking_number'];
            $labelArr['label_files_png'] = $labelArr['label_json'] = '';
        }
        return $labelArr;
    }

    public function getShipmentDataFromCarrier($loadIdentity, $rateDetail = array(), $allData = array())
    {  
        $response = array();
        $shipmentInfo = $this->modelObj->getShipmentDataByLoadIdentity($loadIdentity);
        foreach ($shipmentInfo as $key => $data) {
            if ($data['shipment_service_type'] == 'P') {
                $response['from'] = array(
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
        $response['package'] = $this->getPackageInfo($loadIdentity);
        $serviceInfo = $this->getServiceInfo($loadIdentity);
        $delivery_instruction = $this->modelObj->getDeliveryInstructionByLoadIdentity($loadIdentity);
        $response['currency'] = $serviceInfo['currency'];
        $response['service'] = $serviceInfo['service_code'];
        $response['credentials'] = $this->getCredentialInfo($carrierAccountNumber, $loadIdentity);

        /*start of binding child account data*/
        if (isset($response['credentials']['is_child_account']) && $response['credentials']['is_child_account'] == 'yes') {
            $child_account_data = array("is_child_account" => $response['credentials']['is_child_account'],
                "parent_account_number" => $response['credentials']['parent_account_number'],
                "child_account_number" => $response['credentials']['credentials']['account_number']);
        } else {
            $child_account_data = array();
        }
        /*end of binding child account data*/

        //$response['credentials'] = $response['credentials']['credentials'];

        $response['extra'] = array(
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
            "special_instruction" => $delivery_instruction['shipment_instruction'],
            "custom_desciption" => $serviceInfo['customer_reference1'],
            "custom_desciption2" => $serviceInfo['customer_reference2'],
            "custom_desciption3" => "",
            "customs_form_declared_value" => "",
            "document_only" => "",
            "no_dangerous_goods" => "",
            "in_free_circulation_eu" => "",
            "extended_cover_required" => isset($allData->is_insured) ? $allData->insurance_amount : "",
            "invoice_type" => ""
        );

        $response['currency'] = isset($serviceInfo['currency']) && !empty($serviceInfo['currency']) ? $serviceInfo['currency'] : 'GBP';
        $response['insurance'] = array('value' => (isset($allData->is_insured) ? $allData->insurance_amount : 0), 'currency' => $response['currency'], 'insurer' => '');
        $response['constants'] = array(
            "shipping_charge" => "",
            "weight_charge" => "",
            "fuel_surcharge" => "",
            "remote_area_delivery" => "",
            "insurance_charge" => "",
            "over_sized_charge" => "",
            "over_weight_charge" => "",
            "discounted_rate" => ""
        );
        $response['label_options'] = "";
        $response['customs'] = "";
        $response['billing_account'] = array(
            "payor_type" => "",
            "billing_account" => "",
            "billing_country_code" => "",
            "billing_person_name" => "",
            "billing_email" => ""
        );
        $response['label'] = array();
        $response['providerInfo'] = $allData->providerInfo;
        $response['method_type'] = "post";

        return $this->_getLabel($loadIdentity, json_encode($response), $child_account_data);
        //return $response;

    }


    public function getPackageInfo($loadIdentity)
    {
        $packageData = array();
        $packageInfo = $this->modelObj->getPackageDataByLoadIdentity($loadIdentity);
        foreach ($packageInfo as $data) {
            array_push($packageData, array("packaging_type" => $data["package"], "width" => $data["parcel_width"], "length" => $data["parcel_length"], "height" => $data["parcel_height"], "dimension_unit" => "CM", "weight" => $data["parcel_weight"], "weight_unit" => "KG"));
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
        $credentialData = $this->modelObj->getCredentialDataByLoadIdentity($carrierAccountNumber, $loadIdentity);

        $credentialInfo["username"] = $credentialData["username"];
        $credentialInfo["password"] = $credentialData["password"];
        $credentialInfo["authentication_token"] = $credentialData["authentication_token"];
        $credentialInfo["authentication_token_created_at"] = $credentialData["authentication_token_created_at"];
        $credentialInfo["token"] = $credentialData["token"];
        $credentialInfo["account_number"] = $carrierAccountNumber;
        $credentialInfo["master_carrier_account_number"] = "";
        $credentialInfo["latest_time"] = "17:00:00";
        $credentialInfo["earliest_time"] = "14:00:00";
        $credentialInfo["carrier_account_type"] = array("1");

        return $credentialInfo;
    }


    private function validate($data)
    {
        $error = array();
    }
}