<?PHP
$LOGINURL = 'https://go.sfu.ca/psp/paprd/EMPLOYEE/EMPL/h/?tab=PAPP_GUEST';
$SIMSURL = 'https://sims-prd.sfu.ca/psc/csprd/EMPLOYEE/HRMS/c/COMMUNITY_ACCESS.SSS_BROWSE_CATLG.GBL';
$USERAGENT = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.76 Safari/537.36';
$COOKIEJAR = 'cookies.txt';
$HEADERS = array('Connection: keep-alive', 'Origin: https://sims-prd.sfu.ca');

class Scraper {

	private $ch; // cURL handle
	private $dom; // DOM reference
	private $html; // Retrieved HTML
	private $ph; // POST headers
	private $sid; // Session ID
	private $statenum; // IC State Number
	private $state; // Current state of the scraper

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

		@$this->dom->loadHTML($this->html); // Surpresses the 2456234578 warnings from invalid HTML
		$this->sid = urlencode($this->dom->getElementById('ICSID')->getAttribute('value'));
		$this->statenum = $this->dom->getElementById('ICStateNum')->getAttribute('value');
		curl_setopt($this->ch, CURLOPT_POST, TRUE);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, FALSE);
		curl_setopt($this->ch, CURLOPT_REFERER, $GLOBALS['SIMSURL']);
		$this->state = 'CATALOG';
	}

	/**
	 * @return string Correct POST headers for SIS
	 */
	function genPost($action, $value = NULL) {
		$action = urlencode($action);
		return $value==NULL ? 'ICAJAX=1&ICNAVTYPEDROPDOWN=0&ICType=Panel&ICElementNum=0&ICStateNum='.$this->statenum.'&ICAction='.$action.'&ICXPos=0&ICYPos=0&ResponsetoDiffFrame=-1&TargetFrameName=None&GSrchRaUrl=None&FacetPath=None&ICFocus=&ICSaveWarningFilter=0&ICChanged=-1&ICResubmit=0&ICSID='.$this->sid.'&ICActionPrompt=false&ICTypeAheadID=&ICFind=&ICAddCount=' : 'ICAJAX=1&ICNAVTYPEDROPDOWN=0&ICType=Panel&ICElementNum=0&ICStateNum='.$this->statenum.'&ICAction='.$action.$value.'&ICXPos=0&ICYPos=0&ResponsetoDiffFrame=-1&TargetFrameName=None&GSrchRaUrl=None&FacetPath=None&ICFocus=&ICSaveWarningFilter=0&ICChanged=-1&ICResubmit=0&ICSID='.$this->sid.'&ICActionPrompt=false&ICTypeAheadID=&ICFind=&ICAddCount=';
	}

	/**
	 * @return string HTML contained inside a DOM element
	 */
	function innerHTML(DOMNode $element) { 
		$innerHTML = ""; 
		$children  = $element->childNodes;
		foreach ($children as $child) { 
			$innerHTML .= $element->ownerDocument->saveHTML($child);
		}
		return $innerHTML;
	} 

	/**
	 * @return string HTML of the new page
	 */
	function returnToCatalog() {
		$this->ph = $this->genPost('DERIVED_SAA_CRS_RETURN_PB');
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->ph);
		$this->html = curl_exec($this->ch);
		$this->statenum++;
		$this->state = 'CATALOG';
		return $this->html;
	}

	/**
	 * @return string HTML of the new page
	 */
	function toggleMenu($index) {
		$this->ph = $this->genPost('DERIVED_SSS_BCC_SSR_EXPAND_COLLAPS$', $index);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->ph);
		$this->html = curl_exec($this->ch);
		$this->statenum++;
		$this->state = 'TOGGLE_MENU';
		return $this->html;
	}

	/**
	 * @return string HTML of the new page
	 */
	function selectIndex($letter) {
		$this->ph = $this->genPost('DERIVED_SSS_BCC_SSR_ALPHANUM_', $letter);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->ph);
		$this->html = curl_exec($this->ch);
		$this->statenum++;
		$this->state = 'OPEN_LETTER';
		return $this->html;
	}

	/**
	 * @return string HTML of the new page
	 */
	function selectCourse($index) {
		$this->ph = $this->genPost('CRSE_TITLE$', $index);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->ph);
		$this->html = curl_exec($this->ch);
		$this->statenum++;
		$this->state = 'COURSE';
		return $this->html;
	}

	/**
	 * @return string HTML of the new page
	 */
	function selectSession($term = NULL, $viewall = false) {
		if($term == NULL) {
			$this->ph = $this->genPost('DERIVED_SAA_CRS_SSR_PB_GO');
		} elseif($viewall == true) {
			$this->ph = $this->genPost('CLASS_TBL_VW5$fviewall$0&DERIVED_SAA_CRS_TERM_ALT=', $term);
		} else {
			$this->ph = $this->genPost('DERIVED_SAA_CRS_SSR_PB_GO&DERIVED_SAA_CRS_TERM_ALT=', $term);
		}
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->ph);
		$this->html = curl_exec($this->ch);
		$this->statenum++;
		$this->state = 'SESSION';
		return $this->html;
	}

	/**
	 * @return string|int an array containing the raw data between tags found on SIS or error code on failure
	 */
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
			$raw = $this->innerHTML($this->dom->getElementById('CLASS_MTGPAT$scroll$'.$i));
			$tmp = new DOMDocument();
			$tmp->loadHTML($raw);
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

/*	function xparse() {
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
		$title = $this->dom->getElementById('DERIVED_CRSECAT_DESCR200')->nodeValue;
		$title = explode(" - ", $title);
		$session = new Course();
		$session->setDept(trim(substr($title[0], 0, 4)));
		$session->setLevel(trim(substr($title[0], 4)));
		$session->setName(trim($title[1]));
		$session->setCareer($this->dom->getElementById('SSR_CRSE_OFF_VW_ACAD_CAREER$0')->nodeValue);
		$session->setUnits($this->dom->getElementById('DERIVED_CRSECAT_UNITS_RANGE$0')->nodeValue);
		$session->setPrereq($this->dom->getElementById('DERIVED_CRSECAT_DESCR254A$0')->nodeValue);
		$session->setWQB($this->dom->getElementById('DERIVED_CRSECAT_DESCRFORMAL$0')->nodeValue);
		$session->setDesc($this->dom->getElementById('SSR_CRSE_OFF_VW_DESCRLONG$0')->nodeValue);
		for($i = 0; $i < $sessioncount; $i++) {
			$rawsession['title'] = $this->dom->getElementById('CLASS_SECTION$'.$i)->nodeValue; //rewrite to split session/course ID/type
			$raw = $this->innerHTML($this->dom->getElementById('CLASS_MTGPAT$scroll$'.$i));
			$tmp = new DOMDocument();
			$tmp->loadHTML($raw);
			$numrows = $tmp->getElementsByTagName('tr')->length - 1;
			for($j = 0; $j < $numrows; $j++){
				$rawsession['day'][$j] = $tmp->getElementById('MTGPAT_DAYS$'.$currentrow)->nodeValue; //rewrite to fix dumb inconsistencies in splitting of days
				$rawsession['start'][$j] = $tmp->getElementById('MTGPAT_START$'.$currentrow)->nodeValue;
				$rawsession['end'][$j] = $tmp->getElementById('MTGPAT_END$'.$currentrow)->nodeValue;
				$rawsession['room'][$j] = $tmp->getElementById('MTGPAT_ROOM$'.$currentrow)->nodeValue;
				$rawsession['prof'][$j] = $tmp->getElementById('MTGPAT_INSTR$'.$currentrow)->nodeValue;
				$rawsession['dates'][$j] = $tmp->getElementById('MTGPAT_DATES$'.$currentrow)->nodeValue;
				$currentrow++;				
			}
			$session->addLectures($rawsession, $i);
		}
		return $session;
	} */

	function __destruct() {
		curl_close($this->ch);
	}
}