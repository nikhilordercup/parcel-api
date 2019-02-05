<?php

namespace v1\module\UserNotes\model;

class UserNotesModel{

    private static $db = NULL;

    public function __construct()
    {
        if (self::$db == NULL) {
            self::$db = new \DbHandler();
        }
        $this->_db = self::$db;
    }

    public function getAllUserNotesByJobIdentity($job_identity){
        $sqlStmt = "SELECT PT.*, CP.name as user_name FROM ". DB_PREFIX ."user_notes AS PT LEFT JOIN ". DB_PREFIX ."users AS CP
                    ON PT.created_by = CP.id WHERE PT.job_identity = '$job_identity' ORDER BY PT.note_id DESC";
        $user_notes = $this->_db->getAllRecords($sqlStmt);
        return $user_notes;
    }

}