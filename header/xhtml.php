<?PHP
if(strpos($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml") !== false) {
	header("Content-Type: application/xhtml+xml; charset=UTF-8");
	//header("X-PAD:");   // to remove 'X-Pad: avoid browser bug'
}
//else if(strpos($_SERVER["HTTP_ACCEPT"], "application/xml") !== false) {
//	header("Content-Type: application/xml; charset=UTF-8");
//}
// added at 2009-02-10, but not use
