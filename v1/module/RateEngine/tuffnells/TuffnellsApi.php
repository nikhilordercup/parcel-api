<?php

namespace v1\module\RateEngine\tuffnells;

use v1\module\Database\Model\RateEngineLabelsModel;
use v1\module\RateEngine\RateEngineModel;

class TuffnellsApi
{
    private $_db;
    private $_app;
    private $_requestParams;

    private function __construct($app)
    {
        $this->_app = $app;
        $this->_requestParams = json_decode($this->_app->request->getBody());
    }


    public static function rateEngineRoutes($app)
    {
        $app->post('/saveLabels', function () use ($app) {
            $r = json_decode($app->request->getBody());
            $obj = new TuffnellsLabels($r);
            $responce = $obj->tuffnellLabelData($r);
            echoResponse(200, $responce);
        });

        $app->post('/paperManifest', function () use ($app) {
            $r = json_decode($app->request->getBody());
            $obj = new TuffnellsLabels($r);
            $resp = $obj->paperManifestLabel($r);
            echoResponse(200, $resp);
        });

        $app->post('/addParentAccount', function () use ($app) {
            $r = json_decode($app->request->getBody());
            $obj = new TuffnellsLabels($r);
            $res = $obj->childAccounts($r);
            echoResponse(200, $res);
        });
        $app->get('/generateEdi', function () use ($app) {
            $r = json_decode($app->request->getBody());
            $labels = RateEngineLabelsModel::query()
                ->whereDate('created_date', '=', date('Y-m-d'))
                ->where('load_identity','!=','')
                ->get()
                ->toArray();
            $tuffnallesLabel=new TuffnellsLabels([]);
            $service_args = $tuffnallesLabel->tuffnelServiceType();
            $service_key = array_combine(array_keys($service_args), array_column($service_args, 'desc'));
            $data = [];
            $string="";
            foreach ($labels as $key => $result) {

                $data[$key]['credential_info'] = json_decode($result['credential_info']);
                $data[$key]['collection_info'] = json_decode($result['collection_info']);
                $data[$key]['delivery_info'] = json_decode($result['delivery_info']);
                $data[$key]['package_info'] = json_decode($result['package_info']);
                $data[$key]['extra_info'] = json_decode($result['extra_info']);
                $data[$key]['insurance_info'] = json_decode($result['insurance_info']);
                $data[$key]['constants_info'] = json_decode($result['constants_info']);
                $data[$key]['billing_coounts'] = json_decode($result['billing_coounts']);
                $data[$key]['total_parcel'] = count($data[$key]['package_info']);
                $totalWeight = array_column($data[$key]['package_info'], 'weight');
                $data[$key]['total_weight'] = array_sum($totalWeight);
                $data[$key]['dispatch_date'] = $result['dispatch_date'];
                $data[$key]['currency'] = $result['currency'];
                $data[$key]['carrier'] = $result['carrier'];
                $data[$key]['service_type'] = $result['service_type'];
                $data[$key]['labels'] = isset($result['labels']) ? $result['labels'] : $result['labels'];
                $data[$key]['custom'] = isset($result['custom']) ? $result['custom'] : $result['custom'];
                $data[$key]['account_number'] = $result['account_number'];
                $data[$key]['reference_id'] = $result['reference_id'];
                $data[$key]['created_date'] = $result['created_date'];
                $data[$key]['load_identity'] = $result['load_identity'];
                //Consignment type 1 char (Not Required)
                $string .=" ";
                //8 Digit Account number with Tuffnells (Required)
                $string .=$data[$key]['credential_info']->account_number;
                //Load Identity Max 9 Char (Required)
                $string .=str_pad(substr($data[$key]['load_identity'],0,9),9," ",STR_PAD_RIGHT);
                //2 Space Reserved for TPE Use (Required)
                $string .= "  ";
                //Delivery Address Company Name 30 Char Max (Required)
                $string .=str_pad(substr($data[$key]['delivery_info']->company,0,30),30," ",STR_PAD_RIGHT);
                //Delivery Address Line 1 30 Char Max (Required)
                $string .=str_pad(substr($data[$key]['delivery_info']->street1,0,30),30," ",STR_PAD_RIGHT);
                //Delivery Address Line 2 30 Char Max (Required)
                $string .=str_pad(substr($data[$key]['delivery_info']->street2,0,30),30," ",STR_PAD_RIGHT);
                //Delivery Address Town 30 Char Max (Required)
                $string .=str_pad(substr($data[$key]['delivery_info']->city,0,30),30," ",STR_PAD_RIGHT);
                //Delivery Address Country 30 Char Max (Required)
                $string .=str_pad(substr($data[$key]['delivery_info']->country_name,0,30),30," ",STR_PAD_RIGHT);

                $keyval = "";
                foreach ($service_key as $k=>$v){
                    if(strtolower($v)==strtolower(trim($data[$key]['service_type']))){
                        $keyval=$k;
                        break;
                    }
                }
                //Delivery Address Service Type 2 Char Max (Required)
                $string .=str_pad($service_args[$keyval]['service'],2," ",STR_PAD_RIGHT);
                //Delivery Surcharge 2 Char Max (Not Required)
                $string .=str_pad($service_args[$keyval]['surcharge'],2," ",STR_PAD_RIGHT);

                //Package weight 7 Char Max (Required)
                $string .=str_pad($data[$key]['total_weight'],7,"0",STR_PAD_LEFT);
                //Delivery Surcharge 3 Char Max (Required)
                $string .=str_pad($service_args[$keyval]['surcharge'],2," ",STR_PAD_RIGHT);

                //Package Type Field 1 3 Char Max (Required)
                $packageType=$data[$key]['package_info'][0]->custom_package_type??'CAR';
                $packageType=($packageType!="")?$packageType:'CAR';
                $string .=str_pad($packageType,3," ",STR_PAD_RIGHT);
                //Package Type Field 2 3 Char Max (Not Required)
                $string .=str_pad('',3," ",STR_PAD_RIGHT);
                //Package Count Field 1 3 Char Max (Required)
                $string .=str_pad(count($data[$key]['package_info']),5," ",STR_PAD_RIGHT);
                //Package Count Field 2 3 Char Max (Not Required)
                $string .=str_pad('',5," ",STR_PAD_RIGHT);

                //Delivery Postcode 8 Char Max ( Required)
                $string .=str_pad($data[$key]['delivery_info']->zip,8," ",STR_PAD_RIGHT);

                //Contact Name 30 Char Max (Not Required)
                $string .=str_pad($data[$key]['delivery_info']->name,30," ",STR_PAD_RIGHT);

                //Delivery Phone  31 Char Max (Not Required)
                $string .=str_pad($data[$key]['delivery_info']->phone,31," ",STR_PAD_RIGHT);

                $sequenceNumber=RateEngineLabelsModel::query()
                    ->where('account_number','=',$data[$key]['credential_info']->account_number)
                    ->whereDate('created_date','=',date('Y-m-d'))
                    ->where('label_id','<=',$result['label_id'])->get()->count();

                //Package Sequence number 3 Char Max (Not Required)
                $string .=str_pad($sequenceNumber,4,"0",STR_PAD_LEFT);
                //Empty Info 356 Char Max (Not Required)
                $string .=str_pad('',356," ",STR_PAD_RIGHT);
                //Special Instruction 210 Char Max (Not Required)
                $string .=str_pad($data[$key]['extra_info']->special_instruction,210," ",STR_PAD_RIGHT);
                //Empty Info 116 Char Max (Not Required)
                $string .=str_pad('',116," ",STR_PAD_RIGHT);
                $string .= "\r\n";
            }
            file_put_contents(LABEL_PATH.DIRECTORY_SEPARATOR.'test.txt',$string);
            exit('Success');
        });
    }
}