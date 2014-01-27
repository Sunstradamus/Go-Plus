<?PHP
$LOGINURL = 'https://go.sfu.ca/psp/paprd/EMPLOYEE/EMPL/h/?tab=PAPP_GUEST';
$SIMSURL = 'https://sims-prd.sfu.ca/psc/csprd/EMPLOYEE/HRMS/c/COMMUNITY_ACCESS.SSS_BROWSE_CATLG.GBL';
$USERAGENT = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.76 Safari/537.36';
$COOKIEJAR = 'cookies.txt';
$HEADERS = array('Connection: keep-alive', 'Origin: https://sims-prd.sfu.ca');

function createpostdata($icsid, $icstatenum, $icaction){
	return 'ICAJAX=1&ICNAVTYPEDROPDOWN=0&ICType=Panel&ICElementNum=0&ICStateNum='.$icstatenum.'&ICAction='.$icaction.'&ICXPos=0&ICYPos=0&ResponsetoDiffFrame=-1&TargetFrameName=None&GSrchRaUrl=None&FacetPath=None&ICFocus=&ICSaveWarningFilter=0&ICChanged=-1&ICResubmit=0&ICSID='.$icsid.'&ICActionPrompt=false&ICTypeAheadID=&ICFind=&ICAddCount=';
}

unlink($COOKIEJAR);

// Na-na-na come on...
$curlhandle = curl_init();

// Set up cookies n shit
curl_setopt($curlhandle, CURLOPT_URL, $LOGINURL);
curl_setopt($curlhandle, CURLOPT_USERAGENT, $USERAGENT);
curl_setopt($curlhandle, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curlhandle, CURLINFO_HEADER_OUT, TRUE);
//curl_setopt($curlhandle, CURLOPT_HEADER, TRUE);
curl_setopt($curlhandle, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($curlhandle, CURLOPT_COOKIEJAR, $COOKIEJAR);
curl_setopt($curlhandle, CURLOPT_COOKIEFILE, $COOKIEJAR);
$o = curl_exec($curlhandle);
$fh = fopen('o.txt', 'w');
fwrite($fh, $o);
$o = curl_getinfo($curlhandle, CURLINFO_HEADER_OUT);
fwrite($fh, $o);

// Actually connect to SIMS
curl_setopt($curlhandle, CURLOPT_URL, $SIMSURL);
curl_setopt($curlhandle, CURLOPT_HTTPHEADER, $HEADERS);
$html = curl_exec($curlhandle);
$fh = fopen('o0.txt', 'w');
fwrite($fh, $html);
$o = curl_getinfo($curlhandle, CURLINFO_HEADER_OUT);
fwrite($fh, $o);

// Use DOM Parser and grab form data	
$doc = new DOMDocument;
@$doc->loadHTML($html);
$icsid = $doc->getElementById('ICSID')->getAttribute('value');
$icstatenum = $doc->getElementById('ICStateNum')->getAttribute('value');

echo $icsid.' '.$icstatenum.'<br />';
$icsid = urlencode($icsid);
$icstatenum = urlencode($icstatenum);

$postdata = createpostdata($icsid, $icstatenum++, urlencode('DERIVED_SSS_BCC_SSR_EXPAND_COLLAPS$0')); // Open first dropdown menu

curl_setopt($curlhandle, CURLOPT_POST, TRUE);
curl_setopt($curlhandle, CURLOPT_REFERER, $SIMSURL);
curl_setopt($curlhandle, CURLOPT_FOLLOWLOCATION, FALSE);
curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
$o = curl_exec($curlhandle);
$fh = fopen('o1.txt', 'w');
fwrite($fh, $o);
$o = curl_getinfo($curlhandle, CURLINFO_HEADER_OUT);
fwrite($fh, $o);
var_dump($postdata);

$postdata = createpostdata($icsid, $icstatenum++, urlencode('CRSE_TITLE$0')); // Select first course
curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
$o = curl_exec($curlhandle);
$fh = fopen('o2.txt', 'w');
fwrite($fh, $o);
$o = curl_getinfo($curlhandle, CURLINFO_HEADER_OUT);
fwrite($fh, $o);
var_dump($postdata);

$postdata = createpostdata($icsid, $icstatenum++, urlencode('DERIVED_SAA_CRS_SSR_PB_GO')); // Select current term if possible, else select first possible term
curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
$o = curl_exec($curlhandle);
$fh = fopen('o3.txt', 'w');
fwrite($fh, $o);
$o = curl_getinfo($curlhandle, CURLINFO_HEADER_OUT);
fwrite($fh, $o);
var_dump($postdata);

$postdata = createpostdata($icsid, $icstatenum++, urlencode('DERIVED_SAA_CRS_SSR_PB_GO&DERIVED_SAA_CRS_TERM_ALT=1151')); // 1YYS|YY = last 2 digits of year|S = semester (1,4,7 => spr, sum, fall)
curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
$o = curl_exec($curlhandle);
$fh = fopen('o4.txt', 'w');
fwrite($fh, $o);
$o = curl_getinfo($curlhandle, CURLINFO_HEADER_OUT);
fwrite($fh, $o);
var_dump($postdata);

$postdata = createpostdata($icsid, $icstatenum++, urlencode('CLASS_TBL_VW5$fviewall$0&DERIVED_SAA_CRS_TERM_ALT=1151')); //View all
curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
$o = curl_exec($curlhandle);
$fh = fopen('o5.txt', 'w');
fwrite($fh, $o);
$o = curl_getinfo($curlhandle, CURLINFO_HEADER_OUT);
fwrite($fh, $o);
var_dump($postdata);

//Search for: CLASS_SECTION$0 (Section code, e.g. D100-LEC (1235))
// CLASS_MTGPAT$scroll$0 (Secion table header)
// PSEDITBOX_DISPONLY (Day Start End Dates)
// PSLONGEDITBOX (Room Instructor)
// win0divCLASS_STATUS$0 -> div -> img -> alt=Open/Closed
// PSLEVEL1GRIDNBO (Counter var for sections, divide by 2)
// DERIVED_SSS_BCC_SSR_ALPHANUM_A
// DERIVED_SAA_CRS_RETURN_PB$167$ (ret to browse) DERIVED_SAA_CRS_RETURN_PB

curl_close($curlhandle);




class Scraper {

	private $ch; // cURL handle
	private $dom; // DOM reference
	private $html; // Retrieved HTML
	private $ph; // POST headers
	private $sid; // Session ID
	private $statenum; // IC State Number
	private $state;

	function __construct() {
		$this->ch = curl_init();
		$this->dom = new DOMDocument;
		curl_setopt($this->ch, CURLOPT_URL, $GLOBALS["LOGINURL"]);
		curl_setopt($this->ch, CURLOPT_USERAGENT, $GLOBALS["USERAGENT"]);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($this->ch, CURLINFO_HEADER_OUT, TRUE);
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, $GLOBALS["COOKIEJAR"]);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, $GLOBALS["COOKIEJAR"]);
		$this->html = curl_exec($this->ch);
		$this->state = 'LOAD_GO';
		
		curl_setopt($this->ch, CURLOPT_URL, $GLOBALS['SIMSURL']);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $GLOBALS["HEADERS"]);
		$this->html = curl_exec($this->ch);
		$this->state = 'LOAD_SIS';
		@$this->dom->loadHTML($this->html);
		$this->sid = urlencode($this->dom->getElementById('ICSID')->getAttribute('value'));
		$this->statenum = $this->dom->getElementById('ICStateNum')->getAttribute('value');
		curl_setopt($this->ch, CURLOPT_POST, TRUE);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, FALSE);
		curl_setopt($this->ch, CURLOPT_REFERER, $GLOBALS['SIMSURL']);
		$this->state = 'CATALOG';
	}

	function genPost($action, $value = NULL) {
		$action = urlencode($action);
		return $value==NULL ? 'ICAJAX=1&ICNAVTYPEDROPDOWN=0&ICType=Panel&ICElementNum=0&ICStateNum='.$this->statenum.'&ICAction='.$action.'&ICXPos=0&ICYPos=0&ResponsetoDiffFrame=-1&TargetFrameName=None&GSrchRaUrl=None&FacetPath=None&ICFocus=&ICSaveWarningFilter=0&ICChanged=-1&ICResubmit=0&ICSID='.$this->sid.'&ICActionPrompt=false&ICTypeAheadID=&ICFind=&ICAddCount=' : 'ICAJAX=1&ICNAVTYPEDROPDOWN=0&ICType=Panel&ICElementNum=0&ICStateNum='.$this->statenum.'&ICAction='.$action.$value.'&ICXPos=0&ICYPos=0&ResponsetoDiffFrame=-1&TargetFrameName=None&GSrchRaUrl=None&FacetPath=None&ICFocus=&ICSaveWarningFilter=0&ICChanged=-1&ICResubmit=0&ICSID='.$this->sid.'&ICActionPrompt=false&ICTypeAheadID=&ICFind=&ICAddCount=';
	}
	
	function innerHTML(DOMNode $element) { 
		$innerHTML = ""; 
		$children  = $element->childNodes;
		foreach ($children as $child) { 
			$innerHTML .= $element->ownerDocument->saveHTML($child);
		}
		return $innerHTML;
	} 
	
	function returnToCatalog() {
		$this->ph = $this->genPost('DERIVED_SAA_CRS_RETURN_PB');
		//var_dump($this->ph);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->ph);
		$this->html = curl_exec($this->ch);
		$this->statenum++;
		$this->state = 'CATALOG';
		return $this->html;
	}

	function toggleMenu($index) {
		$this->ph = $this->genPost('DERIVED_SSS_BCC_SSR_EXPAND_COLLAPS$', $index);
		//var_dump($this->ph);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->ph);
		$this->html = curl_exec($this->ch);
		$this->statenum++;
		$this->state = 'TOGGLE_MENU';
		return $this->html;
	}
	
	function selectIndex($letter) {
		$this->ph = $this->genPost('DERIVED_SSS_BCC_SSR_ALPHANUM_', $letter);
		//var_dump($this->ph);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->ph);
		$this->html = curl_exec($this->ch);
		$this->statenum++;
		$this->state = 'OPEN_LETTER';
		return $this->html;
	}

	function selectCourse($index) {
		$this->ph = $this->genPost('CRSE_TITLE$', $index);
		//var_dump($this->ph);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->ph);
		$this->html = curl_exec($this->ch);
		$this->statenum++;
		$this->state = 'COURSE';
		return $this->html;
	}

	function selectSession($term = NULL, $viewall = false) {
		if($term == NULL) {
			$this->ph = $this->genPost('DERIVED_SAA_CRS_SSR_PB_GO');
		} elseif($viewall == true) {
			$this->ph = $this->genPost('CLASS_TBL_VW5$fviewall$0&DERIVED_SAA_CRS_TERM_ALT=', $term);
		} else {
			$this->ph = $this->genPost('DERIVED_SAA_CRS_SSR_PB_GO&DERIVED_SAA_CRS_TERM_ALT=', $term);
		}
		//var_dump($this->ph);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->ph);
		$this->html = curl_exec($this->ch);
		$this->statenum++;
		$this->state = 'SESSION';
		return $this->html;
	}

	function parse() {
		if($this->state != 'SESSION'){
			return 1;
		}
		@$this->dom->loadHTML($this->html);
		$finder = new DOMXPath($this->dom);
		$classname = 'PSLEVEL1GRIDNBO';
		$sessionnodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
		$sessioncount = $sessionnodes->length;
		if($sessioncount % 2 == 1){
			return 2;
		}
		$sessioncount /= 2;
		$numrows = 0;
		$currentrow = 0;
		for($i = 0; $i < $sessioncount; $i++) {
			$rawsession[$i]['title'] = $this->dom->getElementById('CLASS_SECTION$'.$i)->nodeValue;
			$rawsession[$i]['raw'] = $this->innerHTML($this->dom->getElementById('CLASS_MTGPAT$scroll$'.$i));
			$tmp = new DOMDocument();
			$tmp->loadHTML($rawsession[$i]['raw']);
			$numrows = $tmp->getElementsByTagName('tr')->length - 1;
			for($j = 0; $j < $numrows; $j++){
				$rawsession[$i]['day'][$j] = $tmp->getElementById('MTGPAT_DAYS$'.$currentrow)->nodeValue;
				$rawsession[$i]['start'][$j] = $tmp->getElementById('MTGPAT_START$'.$currentrow)->nodeValue;
				$rawsession[$i]['end'][$j] = $tmp->getElementById('MTGPAT_END$'.$currentrow)->nodeValue;
				$rawsession[$i]['room'][$j] = $tmp->getElementById('MTGPAT_ROOM$'.$currentrow)->nodeValue;
				$rawsession[$i]['prof'][$j] = $tmp->getElementById('MTGPAT_INSTR$'.$currentrow)->nodeValue;
				$rawsession[$i]['dates'][$j] = $tmp->getElementById('MTGPAT_DATES$'.$currentrow)->nodeValue;
				$currentrow++;				
			}
		}
		return $rawsession;
	}

	function __destruct() {
		curl_close($this->ch);
	}
}