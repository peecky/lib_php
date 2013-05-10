<?PHP
/*
 * checkBrowserCacheAndExit();
 * parseGetParams($_GET);
 * override $defaultHost
 * override $httpHeaderInfo
 * open(true|false, true|false);
 *		read them as you wish
 *			$httpHeaderInfo2
 *			$receivedBody
 *	or downloadTo(path)
 */
class Lion {
	public $uri = "/";	// readonly
	public $defaultHost = "localhost";
	public $port = 80;
	public $method = "GET";
	public $sendingBody = "";
	public $httpHeaderInfo;	// sending header
	public $httpHeaderInfo2;	// received header(array, readonly)
	public $receivedBody;	// received body(string, readonly)
	private $iLastPrinted;
	private $strBodyBuffer;

	public function checkBrowserCacheAndExit() {
		if(isset($_SERVER["HTTP_IF_MODIFIED_SINCE"]) || isset($_SERVER["HTTP_IF_NONE_MATCH'"])) {
			header('HTTP/1.0 304 Not Modified');
			exit;
		}
	}

	public function parseGetParams($get) {
		$values = array();
		if(isset($get["url"])) {
			$strTemp = $get["url"];
			$pos = strpos($strTemp, "://");
			$pos2 = strpos($strTemp, "/", $pos+3);
			$values["host"] = substr($strTemp, $pos+3, $pos2-$pos-3);
			$values["uri"] = substr($strTemp, $pos2);
		}
		else {
			if(isset($get["host"]))
				$values["host"] = $get["host"];
			if(isset($get["uri"]))
				$values["uri"] = $get["uri"];
		}

		$this->httpHeaderInfo = array();
		if(isset($values["host"])) $this->httpHeaderInfo["Host"] = $values["host"];
		if(isset($values["uri"])) $this->uri = $values["uri"];

		if(isset($get["cookie"])) {
			$values["cookie"] = $get["cookie"];
			//$this->httpHeaderInfo["Cookie"] = $get["cookie"];
		}
		if(isset($get["referer"])) {
			$values["referer"] = $get["referer"];
			$this->httpHeaderInfo["Referer"] = $get["referer"];
		}
	}

	public function open($bPrintHeader = true, $bPrintBody = true) {
		$fp = $this->request();
		if(!$fp) return FALSE;

		// receive response
		$rec = "";
		while(!feof($fp)) {
			//$rec .= fgets($fp, 5120);
			$rec .= fread($fp, 5120);
			$posHeaderEnd = strpos($rec, "\r\n\r\n");	// waiting for headers
			if($posHeaderEnd !== false) break;
		}
		$strHeader = substr($rec, 0, $posHeaderEnd);
		$this->parseHeader($strHeader);

		// print headers
		if($bPrintHeader) {
			if(isset($this->httpHeaderInfo2["Content-Type"]))
				header("Content-Type: " . $this->httpHeaderInfo2["Content-Type"]);
			if(isset($this->httpHeaderInfo2["Last-Modified"]))
				header("Last-Modified: " . $this->httpHeaderInfo2["Last-Modified"]);
			if(isset($this->httpHeaderInfo2["Etag"]))
				header("Etag: " . $this->httpHeaderInfo2["Etag"]);
		}

		$this->receivedBody = "";
		$this->strBodyBuffer = substr($rec, $posHeaderEnd+4);
		$this->processChunked();
		if($bPrintBody) {
			$this->iLastPrinted = 0;
			$this->printBody();
		}
		while(!feof($fp)) {	// recieve remain
			//$this->strBodyBuffer .= fgets($fp, 5120);
			$this->strBodyBuffer .= fread($fp, 5120);
			$this->processChunked();
			if($bPrintBody) $this->printBody();
		}
		fclose($fp);
	}

	public function downloadTo($destPath) {
		$fp = $this->request();
		if(!$fp) return FALSE;

		// process HTTP header
		$rec = "";
		while(!feof($fp)) {
			$rec .= fread($fp, 5120);
			$posHeaderEnd = strpos($rec, "\r\n\r\n");	// waiting for headers
			if($posHeaderEnd !== false) break;
		}
		$strHeader = substr($rec, 0, $posHeaderEnd);
		$this->parseHeader($strHeader);

		// store the content to disk
		$fplocal = fopen($destPath, "wb");
		if($fplocal) {
			$this->receivedBody = "";
			$this->strBodyBuffer = substr($rec, $posHeaderEnd+4);
			$this->processChunked();
			fwrite($fplocal, $this->receivedBody);
			$this->receivedBody = "";
			while(!feof($fp)) {	// recieve remain
				$this->strBodyBuffer .= fread($fp, 5120);
				$this->processChunked();
				fwrite($fplocal, $this->receivedBody);
				$this->receivedBody = "";
			}
			fclose($fplocal);
		}

		fclose($fp);
	}

	private function request() {
		// connect
		$fp = fsockopen($this->getHostAddress(), $this->port, $errno, $errstr, 10);
		if(!$fp) {
			echo "open error: " . $this->getHostAddress(). "\n";
			echo $errstr;
			exit;
		}

		// request
		$out = $this->method. " " . $this->uri . " HTTP/1.1\r\n";
		if(!isset($this->httpHeaderInfo["Host"]))
			$out .= "Host: " . $this->defaultHost . "\r\n";
		foreach($this->httpHeaderInfo as $key => $value) {
			$out .= $key . ": " . $value . "\r\n";
		}
		if($this->method == "POST" && strlen($this->sendingBody) > 0) {
			$out .= "Content-type: application/x-www-form-urlencoded\r\n";
			$out .= "Content-Length: ". strlen($this->sendingBody). "\r\n";
		}
		if(!isset($this->httpHeaderInfo["Connection"]))
			$out .= "Connection: Close\r\n";
		$out .= "\r\n";
		if($this->method == "POST" && strlen($this->sendingBody) > 0) {
			$out .= $this->sendingBody;
		}
		fwrite($fp, $out);

		return $fp;
	}

	private function getHostAddress() {
		if($this->httpHeaderInfo["Host"]) return $this->httpHeaderInfo["Host"];
		else return $this->defaultHost;
	}

	private function parseHeader($strResponseHeader) {
		$arrHeaders = split("\r\n", $strResponseHeader);
		$this->httpHeaderInfo2 = array();
		foreach($arrHeaders as $value) {
			$header = split(": ", $value, 2);
			if(!isset($header[1])) continue;
			$this->httpHeaderInfo2[$header[0]] = $header[1];
		}
	}

	private function processChunked() {
		if(isset($this->httpHeaderInfo2["Transfer-Encoding"]) && $this->httpHeaderInfo2["Transfer-Encoding"] == "chunked") {
			if(1 != sscanf($this->strBodyBuffer, "%x\r\n", $nSize)) return;

			while($nSize != 0) {
				$strBody = substr($this->strBodyBuffer, strpos($this->strBodyBuffer, "\r\n")+2);
				if(strlen($strBody) < $nSize+2) return;
				$this->receivedBody .= substr($strBody, 0, $nSize);
				$this->strBodyBuffer = substr($strBody, $nSize+2);
				if(1 != sscanf($this->strBodyBuffer, "%x\r\n", $nSize)) return;
			}
		}
		else {
			$this->receivedBody .= $this->strBodyBuffer;
			$this->strBodyBuffer = "";
		}
	}

	private function printBody() {
		$strBody = substr($this->receivedBody, $this->iLastPrinted);
		echo $strBody;
		$this->iLastPrinted += strlen($strBody);
	}
}
?>
