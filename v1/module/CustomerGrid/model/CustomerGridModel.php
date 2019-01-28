<?php

namespace v1\module\CustomerGrid\model;
class CustomerGridModel
{

    private static $db = NULL;

    public function __construct()
    {
        if (self::$db == NULL) {
            self::$db = new \DbHandler();
        }
        $this->_db = self::$db;
    }

    public function getUserGridByID($user_id)
    {
        $sqlStmt = "SELECT ";
    }
}