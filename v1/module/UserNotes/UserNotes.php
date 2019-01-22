<?php

namespace v1\module\UserNotes;

class UserNotes extends \Icargo
{
    public function __construct($data = array())
    {
        parent::__construct(array("email" => $data->email, "access_token" => $data->access_token));
    }

    private function getInstance()
    {
        return new \v1\module\UserNotes\model\UserNotesModel();
    }

    public function insertUserNotes($data){
        $tbname = DB_PREFIX . 'user_notes';
        $args = array( 'user_notes' => nl2br($data->data->user_notes), 'created_by' => $data->created_by, 'job_identity' => $data->job_identity, 'created_date' => date("Y-m-d H:i:s"));
        $column_args = array('user_notes', 'created_by', 'job_identity', 'created_date');
        $insertStmt = $this->db->insertIntoTable($args, $column_args, $tbname);
        if($insertStmt){
            return array('status' => 'true', 'message' => 'Insert successfully');
        }else{
            return array('status' => 'false', 'message' => '!!!OOPS Something went wrong.');
        }
    }


    public function getAllUserNotesByJobIdentity($data = array()){
        $userNotes = $this->getInstance()->getAllUserNotesByJobIdentity($data->job_identity);
        return array('status' => 'true', 'data' => $userNotes);
    }

}