<?PHP
if(!defined("LIB_PATH")) define("LIB_PATH", "./");
if(!defined("DEF_PATH")) define("DEF_PATH", "./");

require_once(LIB_PATH . "database/mysqlutil.php");
include_once(DEF_PATH . "dbcache.php");

class DBCache {
	public function __construct() {
		if(defined("CACHE_DB_ADDRESS") && defined("CACHE_DB_USER_NAME") &&
			defined("CACHE_DB_PASSWORD") && defined("CACHE_DB_DB_NAME") &&
			defined("CACHE_TABLE_NAME")) {
			$this->setDatabaseInfo(array(
				"server_address" => CACHE_DB_ADDRESS,
				"user_name" => CACHE_DB_USER_NAME,
				"user_password" => CACHE_DB_PASSWORD,
				"db_name" => CACHE_DB_DB_NAME,
				"table_name" => CACHE_TABLE_NAME
			));
		}
	}

	// parameter: array(
	//		"server_address" =>
	//		"user_name" =>
	//		"user_password" =>
	//		"db_name" =>
	//		"table_name" =>
	//	)
	public function setDatabaseInfo($info) {
		$this->tableName = $info["table_name"];
		$this->mysqlUtil = new MysqlUtil($info);
	}

	public function isStillCacheTime($cacheID, $cacheTime) {
		$ret = $this->mysqlUtil->query("select cachedTime from " . $this->tableName . " where cacheID = :cacheID",
			array(":cacheID" => $cacheID));
		return isset($ret[0]) &&
			($ret[0]["cachedTime"] + $cacheTime) > time();
	}

	public function loadCacheAnyway($cacheID) {
		$ret = $this->mysqlUtil->query("select data from " . $this->tableName . " where cacheID = :cacheID",
			array(":cacheID" => $cacheID));
		if(isset($ret[0]) && !empty($ret[0]["data"]))
			return unserialize($ret[0]["data"]);
		else return FALSE;
	}

	public function loadCache($cacheID, $cacheTime) {
		if($this->isStillCacheTime($cacheID, $cacheTime))
			return $this->loadCacheAnyway($cacheID);
		else return FALSE;
	}

	public function cachedTime($cacheID) {
		$ret = $this->mysqlUtil->query("select cachedTime from " . $this->tableName . " where cacheID = :cacheID",
			array(":cacheID" => $cacheID));
		if(isset($ret[0]) && !empty($ret[0]["cachedTime"]))
			return $ret[0]["cachedTime"];
		else return FALSE;
	}

	public function saveCache($cacheID, $data) {
		$serializedData = serialize($data);

		$result = FALSE;
		$triedAgain = FALSE;
		do {
			$ret = $this->mysqlUtil->query("insert into " . $this->tableName . " set data = :serializedData, cachedTime = :now
				on duplicate key update data = :serializedData, cachedTime = :now",
				array(":serializedData" => $serializedData, ":now" => time()));
			if ($triedAgain) break;
			if(!$ret) {
				// create table
				$sql = "create table if not exists " . $this->tableName . " (
					cacheID VARCHAR(255) not null primary key,
					cachedTime BIGINT unsigned not null default 0,
					data MEDIUMBLOB not null default '')";	// debug: I want LONGBLOB, but php can't read data (return empty string, only storing works).
				$result = $this->mysqlUtil->query($sql);
				$triedAgain = TRUE;
			}
		} while ($result);
		return $ret;
	}

	private $tableName;
	private $mysqlUtil = FALSE;
}
?>
