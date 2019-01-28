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
        $tb_name = DB_PREFIX . "customer_grid";
        $args = array('user_id' => $postdata->user_id, 'column' => $postdata->column, 'company_id' => $postdata->company_id, 'created_date' => date("Y-m-d H:i:s"));
        $dbColumn = array('user_id', 'grid_state', 'company_id', 'created_date');
        $insertStmt = $this->db->insertIntoTable($args, $dbColumn, $tb_name);
        if ($insertStmt) {
            return array('status' => 'sucess', 'message' => 'Saved sucessfully');
        } else {
            return array('status' => 'false', 'message' => '!!!OOPS Something went wrong');
        }
    }

}
