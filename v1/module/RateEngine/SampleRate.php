<?php
/**
 * Created by CLI.
 * User: Mandeep Singh Nain
 * Date: 04-02-2019
 * Time: 12:41 PM
 */

namespace v1\module\RateEngine;


class SampleRate
{

    private $_rate = "{
    'rate': {
        '__CARRIER__': [
            {
                '__ACCOUNT__': [
                    {
                        '__SERVICE__': [
                            {
                                'rate': {
                                    'id': '966',
                                    'carrier_name': '__CARRIER__NAME__',
                                    'service_name': '__SERVICE__NAME__',
                                    'service_code': '__SERVICE__',
                                    'rate_type': 'Distance',
                                    'rate_unit': 'Miles',
                                    'price': 371.085,
                                    'act_number': '__ACCOUNT__'
                                },
                                'surcharges': {
                                    'long_length_surcharge': 0,
                                    'remote_area_surcharge': 0,
                                    'manual_handling_surcharge': 0,
                                    'fuel_surcharge': 0,
                                    'collection_pickup': 0,
                                    'bookin_surcharge': 0,
                                    'insurance_surcharge': 0,
                                    'timed_services_surcharge': 0,
                                    'return_surcharge': 0,
                                    'isle_weight_surcharge': 0,
                                    'isle_scilly_surcharge': 0,
                                    'saturday_delivery_surcharge': 0,
                                    'pobox_surcharge': 0,
                                    'congestion_surcharge': 0,
                                    'same_day_drop_surcharge': 0,
                                    'same_day_waiting_surcharge': 0,
                                    'overweight_surcharge': 0,
                                    'extrabox_surcharge': 0,
                                    'residential_surcharge': 0
                                },
                                'service_options': {
                                    'dimensions': {
                                        'length': '',
                                        'width': '',
                                        'height': '',
                                        'unit': ''
                                    },
                                    'weight': {
                                        'weight': '',
                                        'unit': ''
                                    },
                                    'time': {
                                        'max_waiting_time': '',
                                        'unit': ''
                                    }
                                },
                                'taxes': {
                                    'total_tax': 74.217,
                                    'tax_percentage': '20'
                                }
                            }
                        ]
                    }
                ]
            }
        ]
    }
}";
    private $_req=null;

    /**
     * SampleRate constructor.
     */
    public function __construct($data)
    {
        $this->_req=$data;
        $carrierName="";
        $account="";
        $service="";
        foreach ($data['carriers'] as $c) {
            $carrierName=$c['name'];
            foreach ($c['account'] as $a){
                $account=$a["credentials"]["account_number"];
                $service=explode(',',$a["services"])[0];
            }
        }
        $this->_rate=str_replace("__ACCOUNT__",$account,$this->_rate);
        $this->_rate=str_replace("__CARRIER__",$carrierName,$this->_rate);
        $this->_rate=str_replace("__SERVICE__",$service,$this->_rate);
        $this->_rate=str_replace("__CARRIER__NAME__",$carrierName,$this->_rate);
        $this->_rate=str_replace("__SERVICE__NAME__",$service,$this->_rate);
    }

    /**
     * @return string
     */
    public function getRates(){
        return $this->_rate;
    }

}