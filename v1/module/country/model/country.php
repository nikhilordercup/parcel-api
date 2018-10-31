<?php
/**
 * Class: Using for maintaining the functionality of country Master table
 * Extends the master class Icargo for getting db object using user's access token
 */
class Country extends Icargo
{
	private $_user_id;
	protected $_parentObj;
	
	private function _setUserId($v){
		$this->_user_id = $v;
	}
	
	private function _getUserId(){
		return $this->_user_id;
	}
	
	public function __construct($data){                    
            $this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token));
	}	
	/**
         * Author: Amita Pandey
         * Date: 2-July-2018
         * Purpose: Updating the country detail
         * @param array $countryData         
         * @return type
         */
        public function updateCountry($countryData)
        {                        
            if(isset($countryData->id) && $countryData->id) 
            {
                $countryId = $countryData->id;
                $data['short_name'] = $countryData->short_name;
                $data['alpha2_code'] = $countryData->alpha2_code;
                $data['alpha3_code'] = $countryData->alpha3_code;
                $data['numeric_code'] = $countryData->numeric_code;
                $data['currency_code'] = $countryData->currency_code;
                $data['weight_dutiable_limit'] = $countryData->weight_dutiable_limit;
                $data['paperless_trade'] = $countryData->paperless_trade;
                $data['postal_type'] = $countryData->postal_type;
                $data['job_type'] = $countryData->job_type;                
            
                return $this->_parentObj->db->update("countries", $data, "id='$countryId'");
            } 
            else 
            {
                return false;
            }                       
        }
	/**
         * Author: Amita Pandey
         * Date: 2-July-2018
         * Purpose: Master function for getting non-dutiable country and dutiable country for country specific
         * @param array $searchData         
         * @return type
         */
        public function loadNonDuitableCountry($searchData)
        {            
            $nonDutiable = $this->nonDuitableCountryList($searchData);
            //print_r($nonDutiable); die;
            $ndID = array() ; //[] = $searchData->id;
            foreach ($nonDutiable as $nonDutiCountry) 
            {
                $ndID[] = $nonDutiCountry['nonduty_id'];
            }
            
            $countryList = $this->duitableCountryList($searchData, $ndID);
            $result = array('countryList' => $countryList, 'nonDutiableList' => $nonDutiable);
            return $result;
        }
         /**
         * Author: Amita Pandey
         * Date: 2-July-2018
         * Purpose: For getting non dutiable country list of the specific country
         * @param array $searchData         
         * @return type
         */
        public function nonDuitableCountryList($searchData = array())
        {
            $cond = 'where ICND.status=1 AND ICND.country_id='.$searchData->id;
            $sql = "SELECT IC.id, IC.short_name, ICND.id as ndc_id, ICND.nonduty_id FROM `" . DB_PREFIX . "country_non_duitable` AS ICND LEFT JOIN `" . DB_PREFIX . "countries` AS IC ON IC.id = ICND.nonduty_id ".$cond;
                        
            $records = $this->_parentObj->db->getAllRecords($sql);
            return $records;
        }
        /**
         * Author: Amita Pandey
         * Date: 2-July-2018
         * Purpose: For getting dutiable country list
         * @param array $searchData
         * @param array $ndID
         * @return type
         */        
        public function duitableCountryList($searchData = array(), $ndID = array())
        {
            $cond = ( $ndID ) ? 'where `id` NOT IN ('. implode(', ', $ndID) .')' : '';
            $sql = "SELECT * FROM `" . DB_PREFIX . "countries` ".$cond;
            
            $records = $this->_parentObj->db->getAllRecords($sql);                        
            return $records;
        }
        /**
         * Author: Amita Pandey
         * Date: 3-July-2018
         * Purpose: For disabling non dutiable country
         * @param array $obj
         * @return type
         */
        public function updateNonDutiable($obj = array())
        {
            $nonDutyId = $obj->nonduty_id;            
            $cond = ( trim($nonDutyId) ) ? " id IN ($nonDutyId) " : '';
            $resp = false;
            if ( $cond ) {                
                $data['status'] = '0';                
                $data['updated'] = date('Y-m-d H:i:s');                
                $resp = $this->_parentObj->db->update( "country_non_duitable", $data, $cond);                                        
            }
            return $resp;
        }
        /**
         * Author: Amita Pandey
         * Date: 3-July-2018
         * Purpose: For adding nondutiable country
         * @param array $obj
         * @return type
         */
        public function addNonDutiable($obj = array())
        {
            
            $country_id = $obj->country_id;
            $nonduty_id = $obj->nondutiable_id;            
            $status = 1;
            $created = date('Y-m-d H:i:s');            
            $resp = false;
            
            if($country_id) 
            {
                $query = "INSERT INTO `" . DB_PREFIX . "country_non_duitable` VALUES ('0', $country_id, $nonduty_id, $status, '$created', '$created') ON DUPLICATE KEY UPDATE status = $status, updated='$created'";
                //echo $query; die;
                //$resp = $this->_parentObj->db->save('country_non_duitable', $data);                            
                $resp = $this->_parentObj->db->executeQuery($query);                            
            }
            return $resp;
        }
}
?>