<?php
class MysqlUtil {
	// parameter: array(
	//		"server_address" =>
	//		"user_name" =>
	//		"user_password" =>
	//		"db_name" =>
	//	)
	public function __construct($connection_info) {
		$this->dbServerAddress = $connection_info["server_address"];
		$this->userName = $connection_info["user_name"];
		$this->password = $connection_info["user_password"];
		$this->dbName = $connection_info["db_name"];
	}

	// $sql_:	query string with replaceable_bind_strings
	// $bindData:	assoc. array with replaceable_bind_string and value
	// example:
	//	query("select col1, col2, :value1 from table1 where key1 = :value1", array(":value1" => 123));
	public function query($sql_, $bindData = FALSE) {
		$bindStringPattern = '/:[a-zA-Z0-9_]+/';
		preg_match_all($bindStringPattern, $sql_, $matches);
		$bindCount = count($matches[0]);

		$this->lastQuery = $sql = preg_replace($bindStringPattern, "?", $sql_);
		$mysqli = &$this->getMysqli();
		$stmt = $mysqli->prepare($sql);
		if(!$stmt) {
			$this->lastErrorNo = $mysqli->errno;
			$this->lastErrorMessage = $mysqli->error;
			return FALSE;
		}

		$ret = TRUE;
		$paramBind = array("");
		for($i = 0; $i < $bindCount; $i++) {
			if(!$bindData || !isset($bindData[$matches[0][$i]])) {
				echo "undefined bind data: ". $matches[0][$i];
				$ret = FALSE;
				break;
			}
			$value = $bindData[$matches[0][$i]];
			$paramBind[0] .= self::getMysqlBindType($value);
			$paramBind[] = $value;
		}
		if($ret) {
			if(!empty($paramBind[0])) call_user_func_array(array($stmt, 'bind_param'), self::refValues($paramBind));
			if($stmt->execute()) {
				$this->affectedRows = $mysqli->affected_rows;
				$this->lastInsertId = $mysqli->insert_id;
				$result = $stmt->result_metadata();
				if($result) {
					$ret = array();
					$row = array();
					$fields = array();
					$count = 0;
					while($field = $result->fetch_field()) {
						$fields[$count] = &$row[$field->name];
						$count++;
					}
					call_user_func_array(array($stmt, 'bind_result'), $fields);
					while($stmt->fetch()) {
						$row_ = array();
						foreach($row as $key => $value) $row_[$key] = $value;
						$ret[] = $row_;
					}
					$result->free_result();
				}
			}
		}
		$stmt->close();
		return $ret;
	}

	public function getLastInsertId() {
		return $this->lastInsertId;
	}

	public function getLastErrorNo() {
		return $this->lastErrorNo;
	}

	public function getLastErrorMessage() {
		return $this->lastErrorMessage;
	}

	public function getAffectedRows() {
		return $this->affectedRows;
	}

	public function getLastActualQuery() {
		return $this->lastQuery;
	}

	private static function getMysqlBindType(&$value) {
		return is_int($value)? "i": (
				is_float($value)? "d": "s");
	}

	public function &getMysqli() {
		if($this->mysqli === FALSE) {
			$mysqli = new mysqli($this->dbServerAddress, $this->userName, $this->password, $this->dbName);
			if($mysqli->connect_error) {
				exit("Connect Error (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
			}
			$this->mysqli = &$mysqli;
		}
		return $this->mysqli;
	}

	private static function refValues($arr){
		if (version_compare(PHP_VERSION, '5.3.0') >= 0) {	// Reference is required for PHP 5.3+
			$refs = array();
			foreach($arr as $key => $value)
			$refs[$key] = &$arr[$key];
			return $refs;
		}
		return $arr;
	}

	private $dbServerAddress;
	private $userName;
	private $password;
	private $dbName;
	private $mysqli = FALSE;
	private $lastInsertId = 0;
	private $lastErrorNo;
	private $lastErrorMessage;
	private $affectedRows;
	private $lastQuery = "";
}
