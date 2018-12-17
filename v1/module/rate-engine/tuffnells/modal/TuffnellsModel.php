<?php
class Rate_Engine_Modal{

    public static $_dbObj = NULL;

    public function __construct()
    {
        if(self::$_dbObj==null){
            self::$_dbObj = new DbHandler();
        }
        $this->_db = self::$_dbObj;
    }

    /*public function insertData($postData, $columnname, $tbname)
    {
        $insertStmt = $this->_db->insertIntoTable($postData, $columnname, $tbname);
        return $insertStmt;
    }*/

    public function getDeliveryDepotCode($postcode){
        $sql = "SELECT post_code, delivery_depot_code, delivery_round, delivery_depot_number FROM " . DB_PREFIX ."rateengine_gazetteer WHERE post_code LIKE '$postcode'";
        $result = $this->_db->getAllRecords($sql);
        return $result[0];
    }

    public function paperManifestByDate($date){
        $date_val = $date;
        $sql = "SELECT * FROM ".DB_PREFIX ."rateengine_labels WHERE date(created_date) = '$date_val' ORDER BY label_id ASC";
        $results = $this->_db->getAllRecords($sql);
        try{
            $data = array();
            foreach ( $results as $key => $result ){

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
            }

            return $data;
        }catch (PDOException $exception){
            echo $exception->getMessage();
            return false;
        }

    }

    public function genrateSequenceNumber($account_number, $current_date, $label_id){
        $sql = "SELECT count(`account_number`) as account FROM " . DB_PREFIX ."rateengine_labels WHERE account_number = '$account_number' AND date(`created_date`) = '$current_date' AND label_id <='$label_id'";
        try{
            $stmt = $this->_db->getAllRecords($sql);
            return $stmt[0];
        }catch(PDOException $exception){
            echo $exception->getMessage();
            return false;
        }
    }

    public function checkChildAcoountNumber($account_number, $courier_id){
        $sql = "SELECT id, account_number, customer_id, user_id, company_id, courier_id FROM ". DB_PREFIX ."child_account WHERE account_number IN('$account_number') AND courier_id = '$courier_id'";
        try{
             $stmt = $this->_db->getAllRecords($sql);
             $rowcount = count($stmt);
             $accountArr = [];
             if($rowcount > 0){
                 foreach ($stmt as $key => $val){
                     $accountArr[$key]['account_number'] = $val['account_number'];
                 }
                 return array('status' => 'Account exist', 'account_numbers' => $accountArr);
             }else{
                 return array('status' => 'Not exist');
             }
        }catch (PDOException $exception){
            echo $exception->getMessage();
            return false;
        }
    }

}