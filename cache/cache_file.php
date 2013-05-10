<?PHP
function isStillCacheTime($cacheFileName, $cacheTime) {
	return file_exists($cacheFileName) && ((filemtime($cacheFileName) + $cacheTime) >= time());
}

function loadCacheFileAnyway($cacheFileName) {
	if(!file_exists($cacheFileName)) return false;

	$handle = fopen($cacheFileName, "r");
	if($handle === false) return false;

	$data = unserialize(fread($handle, filesize($cacheFileName)));
	fclose($handle);
	return $data;
}

function loadCacheFile($cacheFileName, $cacheTime) {
	if(isStillCacheTime($cacheFileName, $cacheTime))
		return loadCacheFileAnyway($cacheFileName);
	else return false;
}

function saveCacheFile($data, $cacheFileName) {
	$handle = fopen($cacheFileName, "w");
	if($handle === false) return false;

	$result = fwrite($handle, serialize($data));
	fclose($handle);
	return $result !== false;
}
?>
