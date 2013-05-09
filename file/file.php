<?php
function rm_r($path) {
	if(is_file($path)) return unlink($path);
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST);
	foreach($iterator as $subpath) {
		$filename = $subpath->getFilename();
		if($filename != "." && $filename != "..") {
			if($subpath->isDir() && !$subpath->isLink()) rmdir($subpath->getPathname());
			else unlink($subpath->getPathname());
		}
	}
	rmdir($path);
}

function getFileSize($path) {
	if(!(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')) {
		// linux & mac
		//return trim(shell_exec('stat -c %s '.escapeshellarg($path)));
		//return trim(shell_exec('stat -c %s '."'".addcslashes($path, "'")."'"));
		return trim(shell_exec("stat -c %s '". str_replace("'", "'\\''", $path) . "'"));
	}
	else {
		// use .Net
		$fsobj = new COM("Scripting.FileSystemObject");
		$f = $fsobj->GetFile($path);
		return $f->Size();
	}
}

function getPathSize($path) {
	if(is_file($path)) return filesize($path);

	$size = 0;
	foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST) as $subpath) {
		$filename = $subpath->getFilename();
		if($filename != "." && $filename != ".." && !$subpath->isLink()) {
			//$size += $subpath->getSize();
			$size += getFileSize($subpath->getPathname());
		}
	}
	return $size;
}
?>
