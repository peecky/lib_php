<?php
require_once("mysqlutil.php");

// connect
$connectionInfo = array(
	"server_address" => "localhost",
	"user_name" => "test_id",
	"user_password" => "password1234",
	"db_name" => "test_db"
);
$mysql = new MysqlUtil($connectionInfo);

// create table
$result = $mysql->query("create table if not exists table1 (
	id INT not null auto_increment primary key,
	name VARCHAR(255) not null,
	email VARCHAR(255) not null)");
if ($result === FALSE) exit($mysql->getLastErrorMessage());

// insert row
$result = $mysql->query("insert into table1 set
	name = :name,
	email = :email", array(
	":name" => "Jonson",
	":email" => "jonson@example.org"
));
if ($result === FALSE) exit($mysql->getLastErrorMessage());
$id = $mysql->getLastInsertId();

// select rows
$result = $mysql->query("select name, email from table1
	where id = :id", array(
	":id" => $id
));
if ($result === FALSE) exit($mysql->getLastErrorMessage());	// beware that $result may be empty array, then ($result == FALSE) is TRUE even no error occured
for ($i = 0, $length = count($result); $i < $length; $i++) {
	echo "name: " . $result[$i]["name"] . ", email: " . $result[$i]["email"];
}

// update rows
$result = $mysql->query("update table1 set
	email = :email
	where id = :id", array(
	":email" => "jonson@example.net",
	":id" => $id
));
if ($result === FALSE) exit($mysql->getLastErrorMessage());
echo $mysql->getAffectedRows() . " rows are changed";
?>
