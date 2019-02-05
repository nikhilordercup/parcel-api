<?php

class dbConnect {

    private $conn;

    function __construct() {        
    }

    /**
     * Establishing database connection
     * @return database connection handler
     */
    function connect() {
        include_once '../config.php';

        // Connecting to mysql database
       //try{
            $this->conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        //}catch(Exception $e){
           // print_r($e->getMessage());die;
        //}

        // Check for database connection error
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        mysqli_set_charset($this->conn,"utf8");
        // returing connection resource
        return $this->conn;
    }
    public static function bootGlobal()
    {   if(!class_exists ('\Illuminate\Database\Capsule\Manager'))return true;
        if(!defined('DB_HOST')) {
            require_once '../config.php';
        }
        $connectionManager = new \Illuminate\Database\Capsule\Manager();
        $connectionManager->addConnection([
            'driver' => 'mysql',
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USERNAME,
            'password' => DB_PASSWORD,
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => DB_PREFIX,
        ]);
        $connectionManager->setEventDispatcher(new \Illuminate\Events\Dispatcher(new \Illuminate\Container\Container()));
        $connectionManager->setAsGlobal();
        $connectionManager->bootEloquent();
    }
}
dbConnect::bootGlobal();
