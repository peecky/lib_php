<?php
function humanReadableFileSize($size) {
	if($size < 1024) return "$size B";
	if($size < 1048576) {
		$size = $size/1024.0;
		$unit = "kB";
	}
	else if($size < 1073741824) {
		$size = $size/1048576.0;
		$unit = "MB";
	}
	else if($size < 1099511627776) {
		$size = $size/1073741824.0;
		$unit = "GB";
	}
	else {
		$size = $size/1099511627776.0;
		$unit = "TB";
	}
	return round($size, 1, PHP_ROUND_HALF_DOWN) . " $unit";
}

?>
