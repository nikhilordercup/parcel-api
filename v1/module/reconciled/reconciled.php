<?php
class Module_Reconciled_Reconciled extends Icargo{
    public $modelObj = null;
    
    public
    function __construct($data){
	    $this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token));
        $this->modelObj   = new Reconciled_Model();
	}
    
    public function setReconsiledData($param){
       $resultArray = array();
       if(count($param->data)>1){
          foreach($param->data as $key=>$valueData){
           if($key==0){ 
            $resultArray[] = array_keys((array)$valueData);
            $resultArray[] = array_values((array)$valueData);
           }else{
             $resultArray[] = array_values((array)$valueData);
           }
        }
          $this->createCsvFile($resultArray,$param->filename,$param->selectedCarrier);
          return array("status"=>"success", "message"=>"Uploading csv has been done!!");
       }else{
           return array("status"=>"error", "message"=>"Please upload csv with header and data");
           
       }
    }
    
    public function createCsvFile($data,$file,$carrier){
        $file = str_replace('.csv','__'.date('Ymd').rand(300,3000).'.csv',$file);
        $storagepathPdf = realpath(dirname(dirname(dirname(dirname(__FILE__))))).'/assets/reconciled/'.strtolower($carrier).'/request/';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.   $file.'"');
        $fp = fopen($storagepathPdf.$file, 'w');
        foreach ($data as $line ) {
            fputcsv($fp, $line);
        }
        fclose($fp);    
     }    
}
?>
