<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 12/04/18
 * Time: 3:15 PM
 */
require_once "model/Package_Model_Index.php";
class Module_Package_Index extends Icargo
{
    private $modelObj = NULL;
    public
    function __construct($param){
        parent::__construct(array("email"=>$param->email,"access_token"=>$param->access_token));
        $this->modelObj  = new Package_Model_Index();
    }

    public
    function savePackage($param)
    {//print_r($param);die;
        try {
            $this->modelObj->startTransaction();
            if (isset($param->display_order) and $param->display_order > 0) {
                $allPackages = $this->modelObj->getAllPackagesByCreatedUserId($param->created_by);
                foreach ($allPackages as $item) {
                    if ($item["display_order"] >= $param->display_order) {
                        //update display order [increment by one]
                        $status = $this->modelObj->updatePackage(array("display_order" => $item["display_order"] + 1), "id=" . $item["id"]);
                    }
                }
            }

            $display_order = (isset($param->display_order)) ? $param->display_order : 0 ;

            $is_internal = ($param->is_internal==1) ? "1" : "0" ;

            //$package_code = ($is_internal==1) ? "Parcels" : "";
			$package_code = ($is_internal==1) ? "CP" : "";

            $package_id = $this->modelObj->savePackage(array(
                "type" => $param->new_type,
                "contents" => (isset($param->content)) ? $param->content : "",
                "description" => (isset($param->content)) ? $param->description : "",
                "commodity_code" => (isset($param->commodity_code)) ? $param->commodity_code : "",
                "weight" => $param->weight,
                "length" => $param->length,
                "width" => $param->width,
                "height" => $param->height,
                "display_order" => $display_order,
                "company_id" => $param->company_id,
                "created_by" => $param->customer_id,
                "customer_user_id" => $param->collection_user_id,
                "allowed_user" => $param->allow_other,
                "is_internal" => $is_internal,
                "package_code" => $package_code
            ));

            $allowedUsers= array();
            $allowedUsers[$param->customer_id] = $param->customer_id;

            if (isset($param->allow_other) and $package_id) {
                $items = $this->modelObj->getUserByCustomerId($param->customer_id);

                foreach($items as $item){
                    $allowedUsers[$item["user_id"]] = $item["user_id"];
                }
            }

            foreach ($allowedUsers as $item) {
                $status = $this->modelObj->saveAllowedUserPackage(array(
                    "user_id" => $item,
                    "package_id" => $package_id
                ));
            }

            $this->modelObj->commitTransaction();

            return array("status"=>"success", "message"=>"Package saved successfully","package_lists"=>$this->_getPackages($param->customer_id));
        }catch(Exception $e){print_r($e);die;
            $this->modelObj->rollBackTransaction();
            return array("status"=>"error", "message"=>"Package not saved. Record rollback.");
        }
    }

    private

    function _getPackages($user_id){
        return $this->modelObj->getParcelPackageByUserId($user_id);
    }

    public
    function getPackages($param){
        return $this->_getPackages($param->user_id);
    }
}