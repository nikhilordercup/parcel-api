<?php
require_once 'dbConnect.php';
class DbHandler {

    private static $_db = NULL;

    function __construct() {
        if(self::$_db==NULL){
            self::$_db = new dbConnect();
        }
        // opening db connection
        //$db = new dbConnect();
        $this->conn = self::$_db->connect();
    }
    /**
     * Start transaction
     */
    public function startTransaction() {
        $this->conn->begin_transaction();
    }
    /**
     * Start transaction
     */
    public function commitTransaction() {
        $this->conn->commit();
    }
    /**
     * Start transaction
     */
    public function rollBackTransaction() {
        $this->conn->rollBack();
    }
    /**
     * Fetching single record
     */
    public function getOneRecord($query) {
        $r = $this->conn->query($query.' LIMIT 1') or die($this->conn->error.__LINE__);
        return $result = $r->fetch_assoc();    
    }
	/**
     * Fetching all matching records
     */
    public function getAllRecords($query) {
		$result = array();
		$stmt = $this->conn->query($query) or die($this->conn->error.__LINE__);
		//print_r($query);die;
		while ($row = $stmt->fetch_assoc()) {
			array_push($result, $row);
		}
        return $result;//$stmt->fetch_all();
    }
	/**
     * Fetching row matching record
     */
    public function getRowRecord($query) {
		$stmt = $this->conn->query($query) or die($this->conn->error.__LINE__);
        return $stmt->fetch_assoc();
    }
    /**
     * Creating new record
     */
    public function insertIntoTable($obj, $column_names, $table_name) {
        $c = (array) $obj;
        $keys = array_keys($c);
        $columns = '';
        $values = '';
        foreach($column_names as $desired_key){ // Check the obj received. If blank insert blank into the array.
           if(!in_array($desired_key, $keys)) {
                $$desired_key = '';
            }else{
                $$desired_key = $c[$desired_key];
            }
            $columns = $columns.$desired_key.',';
            $values = $values."'".$$desired_key."',";
        }
        $query = "INSERT INTO ".$table_name."(".trim($columns,',').") VALUES(".trim($values,',').")";
        
        //try{
            $r = $this->conn->query($query) or die($this->conn->error.__LINE__);

            if ($r) {
                $new_row_id = $this->conn->insert_id;
                return $new_row_id;
            } else {
                return NULL;
            }
        /*} catch(Exception $e){
            return $e;
        }*/
    }
	
	private function _prepare_sql_col_val_statement($data){
		$stmt = array();
		foreach($data as $key => $value){
			array_push($stmt, "`$key` = '". addslashes($value)."'");
		}
		$stmt = implode(",",$stmt);
		return $stmt;
	}
	
	public function delete($query){
		//$r = $this->conn->query($query) or die($this->conn->error.__LINE__);
        $r = $this->conn->query($query);
		if(!$r){
            throw new Exception($this->conn->error.__LINE__);
		}
		return $r;
	}
	
	public function update($table_name, $data, $condition = ""){
		$query = $this->_prepare_sql_col_val_statement($data);
		$query = "UPDATE `" . DB_PREFIX . "$table_name` SET $query";
		if($condition)
			$query .= " WHERE $condition";
		$query .= ";";
        $r = $this->conn->query($query);
        if($r)
        	return $r;
        else
            throw new Exception($this->conn->error.__LINE__);
		//$r = $this->conn->query($query) or die($this->conn->error.__LINE__);
		//return $r;
	}
	
	public function save($table_name, $data){
		$query = $this->_prepare_sql_col_val_statement($data);
		$query = "INSERT INTO `" . DB_PREFIX . "$table_name` SET $query;";

        $r = $this->conn->query($query);

        if ($r) {
            $new_row_id = $this->conn->insert_id;
            return $new_row_id;
        } else {
        	throw new Exception($this->conn->error.__LINE__." query : $query");
        }

	}
	
	public function getSession(){
		if (!isset($_SESSION)) {
			session_start();
		}
		$sess = array();
		if(isset($_SESSION['id']))
		{
			$sess["id"] = $_SESSION['id'];
			$sess["name"] = $_SESSION['name'];
			$sess["email"] = $_SESSION['email'];
		}
		else
		{
			$sess["id"] = '';
			$sess["name"] = 'Guest';
			$sess["email"] = '';
		}
		return $sess;
	}
	
	public function destroySession(){
		if (!isset($_SESSION)) {
		session_start();
		}
		if(isSet($_SESSION['id']))
		{
			unset($_SESSION['id']);
			unset($_SESSION['name']);
			unset($_SESSION['email']);
			$info='info';
			if(isSet($_COOKIE[$info]))
			{
				setcookie ($info, '', time() - $cookie_time);
			}
			$msg="Logged Out Successfully...";
		}
		else
		{
			$msg = "Not logged in...";
		}
		return $msg;
	}
	
	public function updateAccessTokenById($access_token,$id){
		$query = "UPDATE ".DB_PREFIX."users SET access_token = '".$access_token."' WHERE id = $id";
        $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
		return $r;
	}
	
	public function removeAccessToken($userId){
		$query = "UPDATE ".DB_PREFIX."users SET access_token = '' WHERE id = $userId";
        $r = $this->conn->query($query) or die($this->conn->error.__LINE__);
		return $r;
	}
	
    public function executeQuery($query){
		$r = $this->conn->query($query) or die($this->conn->error.__LINE__);
        return $this->conn->insert_id;//$r;
	}
    
	public function updateData($query){
		$r = $this->conn->query($query) or die($this->conn->error.__LINE__);
		return $r;
	}
    
    public function getAffectedRows(){
		return $this->conn->affected_rows;
	}
	
	public function deleteData($query){
		$r = $this->conn->query($query);
		if(!$r){
            throw new Exception($this->conn->error.__LINE__);
		}
		return $this->conn->affected_rows;
	}
}

?>