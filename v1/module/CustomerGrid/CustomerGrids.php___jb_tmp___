<?php

namespace v1\module\CustomerGrid;

class CustomerGrids extends \Icargo
{
    public function __construct($data = array())
    {
        parent::__construct(array("email" => $data->email, "access_token" => $data->access_token));
    }

    private function getInstance()
    {
        return new  \v1\module\CustomerGrid\model\CustomerGridModel();
    }


    public function saveGridStatus($postdata)
    {
        $checkGridStatus = $this->checkUserGrid($postdata->user_id);
        if($checkGridStatus["status"] == "true"){
            $updateSmt = $this->updateUserGrid($postdata);
            if($updateSmt){
                return array('status' => 'success', 'message' => 'update successfully');
            }
        }else{
           $insertStmt = $this->saveUserGrids($postdata);
           if($insertStmt){
               return array('status' => 'success', 'message' => 'saved successfully');
           }
        }
    }

    public function getGridStatus($data)
    {
        $gridStatus = $this->getInstance()->getUserGridByID($data->user_id);
        if(empty($gridStatus)){
            return array('status' => 'false', 'data' => $gridStatus);
        }else{
            return array('status' => 'success', 'data' => $gridStatus);
        }

    }

    private function checkUserGrid($userid)
    {
        $checkUserGrid = $this->getInstance()->getUserGridByID($userid);
        if (empty($checkUserGrid)) {
            return array('status' => 'false');
        } else {
            return array('status' => 'true', 'user_id' => $checkUserGrid[0]["user_id"]);
        }

    }

    private function updateUserGrid($postData)
    {
        $update_args = array('grid_state' => json_encode($postData->column));
        $updateStmt = $this->db->update("customer_grid", $update_args, "user_id='$postData->user_id'");
        return $updateStmt;

    }

    private function saveUserGrids($data)
    {
        $tb_name = DB_PREFIX . "customer_grid";
        $args = array('user_id' => $data->user_id, 'grid_state' => json_encode($data->column), 'company_id' => $data->company_id, 'created_date' => date("Y-m-d H:i:s"));
        $dbColumn = array('user_id', 'grid_state', 'company_id', 'created_date');
        $insertStmt = $this->db->insertIntoTable($args, $dbColumn, $tb_name);
        return $insertStmt;
    }

}
