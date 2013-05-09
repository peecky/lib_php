<?PHP
function echoRSSHead($title, $link, $description, $pubDate) {
	echo getRSSHead($title, $link, $description, $pubDate);
}

function getRSSHead($title, $link, $description, $pubDate) {
	return '<rss version="2.0">
<channel>
	<title>' . $title . '</title>
	<link>' . $link . '</link>
	<description>' . $description . '</description>
	<pubDate>' . $pubDate . '</pubDate>
';
}

function echoRSSItem($title, $link, $description = "", $guid = false, $pubDate = false) {
	echo getRSSItem($title, $link, $description, $guid, $pubDate);
}

function getRSSItem($title, $link, $description = "", $guid = false, $pubDate = false) {
	$str =
'	<item>
		<title>' . $title . '</title>
		<link>' . $link . '</link>
		<description>' . $description . '</description>';
	if($guid !== false) { $str .= '
		<guid>' . $guid . '</guid>'; }
	if($pubDate !== false) { $str .= '
		<pubDate>' . $pubDate . '</pubDate>'; }
	$str .= '
	</item>
';
	return $str;
}

function echoRSSTail() {
	echo getRSSTail();
}

function getRSSTail() {
	return '</channel>
</rss>';
}
?>
