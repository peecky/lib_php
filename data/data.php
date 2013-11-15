<?php
function storeValueToFile($path, $value) {
	$tempFilePath = $tempnam(sys_get_temp_dir(), 'tmp');
	$f = fopen($tempFilePath, "w");
	if (!$f) return FALSE;

	$data = serialize($value);
	$writtenBytes = fwrite($f, $data);
	fclose($f);

	if ($writtenBytes !== FALSE && strlen($data) == $writtenBytes) {
		$written = rename($tempFilePath, $path);
		if ($written) return TRUE;
	}

	// fail to store data but something remains
	unlink($tempFilePath);
	return FALSE;
}

function loadValueFromFile($path, $defaultValue = FALSE) {
	if (!file_exists($path)) return $defaultValue;

	$data = file_get_contents($path);
	if ($data === FALSE) return $defaultValue;
	$value = unserialize($data);
	if ($value === FALSE && $data != serialize(FALSE)) return $defaultValue;
	else return $value;
}
?>
