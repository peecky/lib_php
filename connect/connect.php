<?php
// return: response of target URL. might be empty string, 'OK' or something
// $options = array(
//	'url' => 'http://example.org/storeData.php'
//	'dataID' =>
//	'data' =>
//	'salt' =>
// );
function sendData($options) {
	$dataID = $options['dataID'];
	$data = json_encode($options['data']);
	$data = gzcompress($data);
	$validToken = sha1($data . $options['salt'] . $dataID, TRUE);
	$postParams = array(
		'dataID' => $dataID,
		'data' => $data,
		'type' => 'JSON',
		'verify' => $validToken
	);
	$httpOptions = array(
		'header' => "Content-type: application/x-www-form-urlencoded\r\n",
		'method' => 'POST',
		'content' => http_build_query($postParams)
	);
	$context = stream_context_create(array('http' => $httpOptions));
	$result = file_get_contents($options['url'], FALSE, $context);
	return $result;
}
?>
