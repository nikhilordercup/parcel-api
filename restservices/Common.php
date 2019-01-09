<?php
final class Common extends Booking
{
    public function __construct(){
       
    }
    public function getMergeRecords($sameRecords,$nextRecords){
         $returnService = array();
        if($sameRecords['status']=='success' and $sameRecords['rate']['quotation_ref'] !=''){
            if(!key_exists('services',$sameRecords['rate'])){
               $sameRecords['rate']['services'] = array(); 
            }
            if(!empty($nextRecords) and $nextRecords['status']=='success' and  count($nextRecords['rate']['services'])>0){
                foreach($nextRecords['rate']['services'] as $value){
                    $sameRecords['rate']['services'][] = $value;
                }
            }
            $returnService = $sameRecords;
        }else{
             $returnService = $nextRecords;
        }
      return $returnService;
  
    }
}
?>