<?PHP
class Scraper {

	private $ch; // cURL handle
	private $dom; // DOM reference
	private $html; // Retrieved HTML
	private $ph; // POST headers
	private $sid; // Session ID
	private $statenum; // IC State Number
	private $term; // Semester to parse
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
	function genPost($action, $value = NULL, $encode = true) {
		if($encode){
			$action = urlencode($action);
		}
		return $value==NULL ? 'ICAJAX=1&ICNAVTYPEDROPDOWN=0&ICType=Panel&ICElementNum=0&ICStateNum='.$this->statenum.'&ICAction='.$action.'&ICXPos=0&ICYPos=0&ResponsetoDiffFrame=-1&TargetFrameName=None&GSrchRaUrl=None&FacetPath=None&ICFocus=&ICSaveWarningFilter=0&ICChanged=-1&ICResubmit=0&ICSID='.$this->sid.'&ICActionPrompt=false&ICTypeAheadID=&ICFind=&ICAddCount=' : 'ICAJAX=1&ICNAVTYPEDROPDOWN=0&ICType=Panel&ICElementNum=0&ICStateNum='.$this->statenum.'&ICAction='.$action.$value.'&ICXPos=0&ICYPos=0&ResponsetoDiffFrame=-1&TargetFrameName=None&GSrchRaUrl=None&FacetPath=None&ICFocus=&ICSaveWarningFilter=0&ICChanged=-1&ICResubmit=0&ICSID='.$this->sid.'&ICActionPrompt=false&ICTypeAheadID=&ICFind=&ICAddCount=';
	}

	function getNumCourses() {
		@$this->dom->loadHTML($this->html);
		$finder = new DOMXPath($this->dom);
		$classname = 'PSEDITBOX_DISPONLY';
		$nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
		$count = ($nodes->length)/2;
		return $count;
	}
	
	function getNumSubjects() {
		@$this->dom->loadHTML($this->html);
		$finder = new DOMXPath($this->dom);
		$classname = 'SSSHYPERLINKBOLD';
		$nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
		$count = ($nodes->length)/2;
		return $count;
	}

	function isMore() {
		@$this->dom->loadHTML($this->html);
		if($this->dom->getElementById('CLASS_TBL_VW5$fviewall$0')->nodeValue != "View All"){
			return false;
		} else {
			return true;
		}
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
			if($this->state != 'SESSION'){
				return '1';
			}
			@$this->dom->loadHTML($this->html);
			if($this->dom->getElementById('CLASS_TBL_VW5$fviewall$0')->nodeValue != "View All"){
				$this->ph = $this->genPost('DERIVED_SAA_CRS_SSR_PB_GO$98$&DERIVED_SAA_CRS_TERM_ALT=', $term, false);
			} else {
				$this->ph = $this->genPost('CLASS_TBL_VW5$fviewall$0');
				$this->ph .= "&DERIVED_SAA_CRS_TERM_ALT=" . $term;
			}
			$this->term = $term;
		} else {
			if($this->state != 'SESSION'){
				return '1';
			}
			$this->term = $term;
			$this->ph = $this->genPost('DERIVED_SAA_CRS_SSR_PB_GO$98$&DERIVED_SAA_CRS_TERM_ALT=', $term, false);
		}
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->ph);
		$this->html = curl_exec($this->ch);
		$this->statenum++;
		$this->state = 'SESSION';
		return $this->html;
	}
	
	function dayToNum($day) {
		switch($day) {
			case 'Mo':
				return 1;
			case 'Tu':
				return 2;
			case 'We':
				return 3;
			case 'Th':
				return 4;
			case 'Fr':
				return 5;
			case 'Sa':
				return 6;
			case 'Su':
				return 7;
			default:
				return 0;
		}
	}
	
	/*
	
	function genTimeslot($start, $end) {
		$start24 = date('Hi', strtotime($start));
		$end24 = date('Hi', strtotime($end));
		$diff = ($end24 - $start24);
		$s = 0;
		if($diff == 90){
			$s += $this->timeToTimeslot($start24);
		}
		if($diff == 190){
			$s += $this->timeToTimeslot($start24);
			$start24 += 100;
			$s += $this->timeToTimeslot($start24);
		}
		if($diff == 290){
			$s += $this->timeToTimeslot($start24);
			$start24 += 100;
			$s += $this->timeToTimeslot($start24);
			$start24 += 100;
			$s += $this->timeToTimeslot($start24);
		}
		return $s;
	}
	
	function timeToTimeslot($str){
		$c = 0;
		switch($str){
			case 830:
				$c += 1;
				return $c;
			case 930:
				$c += 2;
				return $c;
			case 1030:
				$c += 4;
				return $c;
			case 1130:
				$c += 8;
				return $c;
			case 1230:
				$c += 16;
				return $c;
			case 1330:
				$c += 32;
				return $c;
			case 1430:
				$c += 64;
				return $c;
			case 1530:
				$c += 128;
				return $c;
			case 1630:
				$c += 256;
				return $c;
			case 1730:
				$c += 512;
				return $c;
			case 1830:
				$c += 1024;
				return $c;
			case 1930:
				$c += 2048;
				return $c;
			case 2030:
				$c += 4096;
				return $c;
			default:
				return $c;
		}
	} */
	
	function roundHalfHourUp($str){
		$hour = substr($str, 0, 2);
		$min = substr($str, 2);
		if($min == '00' || $min == 30){
			return $str;
		} elseif ($min < 30){
			return $hour.'30';
		} elseif ($min > 30){
			$hour = intval($hour);
			$hour++;
			if(strlen($hour) == 1){
				$hour = '0'.$hour;
			}
			return $hour.'00';
		}
	}
	
	function genTimeslot($start, $end){
		$start24 = date('Hi', strtotime($start));
		$end24 = $this->roundHalfHourUp(date('Hi', strtotime($end)));
		$diff = ($end24 - $start24);
		$ts = 0;
		if($diff == 30 || $diff == 70){
			$ts += $this->timeToTimeslot($start24);
		} elseif ($diff == 100){
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +30 minutes'));
			$ts += $this->timeToTimeslot($start24);
		} elseif ($diff == 130 || $diff == 170){
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +30 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +60 minutes'));
			$ts += $this->timeToTimeslot($start24);
		} elseif ($diff == 200){
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +30 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +60 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +90 minutes'));
			$ts += $this->timeToTimeslot($start24);
		} elseif ($diff == 230 || $diff == 270){
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +30 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +60 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +90 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +120 minutes'));
			$ts += $this->timeToTimeslot($start24);
		} elseif ($diff == 300){
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +30 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +60 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +90 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +120 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +150 minutes'));
			$ts += $this->timeToTimeslot($start24);
		} elseif ($diff == 330 || $diff == 370){
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +30 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +60 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +90 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +120 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +150 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +180 minutes'));
			$ts += $this->timeToTimeslot($start24);
		} elseif ($diff == 400){
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +30 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +60 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +90 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +120 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +150 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +180 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +210 minutes'));
			$ts += $this->timeToTimeslot($start24);
		} elseif ($diff == 430 || $diff == 470){
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +30 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +60 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +90 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +120 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +150 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +180 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +210 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +240 minutes'));
			$ts += $this->timeToTimeslot($start24);
		} elseif ($diff == 500){
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +30 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +60 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +90 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +120 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +150 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +180 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +210 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +240 minutes'));
			$ts += $this->timeToTimeslot($start24);
			$start24 = date('Hi', strtotime($start.' +270 minutes'));
			$ts += $this->timeToTimeslot($start24);
		}
		return $ts;
	}
	
	function timeToTimeslot($str){
		$c = 0;
		switch($str){
			case 800:
				$c += 1;
				return $c;
			case 830:
				$c += 2;
				return $c;
			case 900:
				$c += 4;
				return $c;
			case 930:
				$c += 8;
				return $c;
			case 1000:
				$c += 16;
				return $c;
			case 1030:
				$c += 32;
				return $c;
			case 1100:
				$c += 64;
				return $c;
			case 1130:
				$c += 128;
				return $c;
			case 1200:
				$c += 256;
				return $c;
			case 1230:
				$c += 512;
				return $c;
			case 1300:
				$c += 1024;
				return $c;
			case 1330:
				$c += 2048;
				return $c;
			case 1400:
				$c += 4096;
				return $c;
			case 1430:
				$c += 8192;
				return $c;
			case 1500:
				$c += 16384;
				return $c;
			case 1530:
				$c += 32768;
				return $c;
			case 1600:
				$c += 65536;
				return $c;
			case 1630:
				$c += 131072;
				return $c;
			case 1700:
				$c += 262144;
				return $c;
			case 1730:
				$c += 524288;
				return $c;
			case 1800:
				$c += 1048576;
				return $c;
			case 1830:
				$c += 2097152;
				return $c;
			case 1900:
				$c += 4194304;
				return $c;
			case 1930:
				$c += 8388608;
				return $c;
			case 2000:
				$c += 16777216;
				return $c;
			case 2030:
				$c += 33554432;
				return $c;
			case 2100:
				$c += 67108864;
				return $c;
			case 2130:
				$c += 134217728;
				return $c;
			case 2200:
				$c += 268435456;
				return $c;
			default:
				return $c;
		}
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

	function xparse() {
		if($this->state != 'SESSION'){
			return 1;
		}
		@$this->dom->loadHTML($this->html);
		$classname = 'PSLEVEL1GRIDNBO';
		$numrows = 0;
		$currentrow = 0;
		$lecc = 0;
		$labc = 0;
		$tutc = 0;
		$secc = 0;
		$title = str_replace(chr(0xC2).chr(0xA0), '', $this->dom->getElementById('DERIVED_CRSECAT_DESCR200')->nodeValue);
		$title = explode(" - ", $title);
		$course = explode(" ", $title[0]);
		$session = new Course();
		$session->setDept(trim($course[0]));
		$session->setLevel(trim($course[1]));
		$session->setName(trim($title[1]));
		$session->setCareer($this->dom->getElementById('SSR_CRSE_OFF_VW_ACAD_CAREER$0')->nodeValue);
		$session->setUnits($this->dom->getElementById('DERIVED_CRSECAT_UNITS_RANGE$0')->nodeValue);
		$session->setPrereq(str_replace(chr(0xC2).chr(0xA0), '', $this->dom->getElementById('DERIVED_CRSECAT_DESCR254A$0')->nodeValue));
		$session->setWQB($this->dom->getElementById('DERIVED_CRSECAT_DESCRFORMAL$0')->nodeValue);
		$session->setDesc(str_replace(chr(0xC2).chr(0xA0), '', $this->dom->getElementById('SSR_CRSE_OFF_VW_DESCRLONG$0')->nodeValue));
		$finder = new DOMXPath($this->dom);
		$sessionnodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
		$sessioncount = $sessionnodes->length;
		if($sessioncount % 2 == 1){
			return 2;
		}
		$sessioncount /= 2;
		for($i = 0; $i < $sessioncount; $i++) {
			$rawsession = array();
			$title = $this->dom->getElementById('CLASS_SECTION$'.$i)->nodeValue;
			$type = substr($title, 5, 3);
			$rawsession['id'] = substr(substr($title, 9), 1, -1);
			$rawsession['section'] = substr($title, 0, 4);
			$rawsession['type'] = $type;
			$raw = $this->innerHTML($this->dom->getElementById('CLASS_MTGPAT$scroll$'.$i));
			$tmp = new DOMDocument();
			$tmp->loadHTML($raw);
			$numrows = $tmp->getElementsByTagName('tr')->length - 1;
			for($j = 0, $k = 0; $j < $numrows; $j++){
				$day = $tmp->getElementById('MTGPAT_DAYS$'.$currentrow)->nodeValue;
				$rm = $tmp->getElementById('MTGPAT_ROOM$'.$currentrow)->nodeValue;
				if($rm == 'Distance Education'){
					$loc[1] = 'DIST ED';
					$loc[2] = $loc[1];
				} else {
					$loc = explode(": ", $tmp->getElementById('MTGPAT_ROOM$'.$currentrow)->nodeValue);
					$loc[1] = substr(str_replace("Room", "", $loc[1]), 0, -1); //MKR
				}
				if(strlen($day) > 2) {
					for($k = 0; $k < (strlen($day)/2); $k++) {
						$temp = substr($day, 0, 2);
						$day = substr($day, 2);
						$rawsession['term'][$j+$k] = $this->term;
						$rawsession['day'][$j+$k] = $this->dayToNum($temp);
						$rawsession['timeslot'][$j+$k] = $this->genTimeslot($tmp->getElementById('MTGPAT_START$'.$currentrow)->nodeValue, $tmp->getElementById('MTGPAT_END$'.$currentrow)->nodeValue);
						//$rawsession['campus'][$j+$k] = substr(str_replace("Room", "", $loc[1]), 0, -1);
						$rawsession['campus'][$j+$k] = $loc[1]; //MKR
						$rawsession['room'][$j+$k] = $loc[2];
						$rawsession['prof'][$j+$k] = $tmp->getElementById('MTGPAT_INSTR$'.$currentrow)->nodeValue;
						$rawsession['dates'][$j+$k] = $tmp->getElementById('MTGPAT_DATES$'.$currentrow)->nodeValue;
					}
				}
				$rawsession['term'][$j+$k] = $this->term;
				$rawsession['day'][$j+$k] = $this->dayToNum($day);
				$rawsession['timeslot'][$j+$k] = $this->genTimeslot($tmp->getElementById('MTGPAT_START$'.$currentrow)->nodeValue, $tmp->getElementById('MTGPAT_END$'.$currentrow)->nodeValue);
				//$rawsession['campus'][$j+$k] = substr(str_replace("Room", "", $loc[1]), 0, -1);
				$rawsession['campus'][$j+$k] = $loc[1];
				$rawsession['room'][$j+$k] = $loc[2];
				$rawsession['prof'][$j+$k] = $tmp->getElementById('MTGPAT_INSTR$'.$currentrow)->nodeValue;
				$rawsession['dates'][$j+$k] = $tmp->getElementById('MTGPAT_DATES$'.$currentrow)->nodeValue;
				$currentrow++;				
			}
			switch($type) {
				case 'LEC':
					$session->addLectures($rawsession, $lecc);
					$lecc++;
					break;
				case 'LAB':
					$session->addLabs($rawsession, $labc);
					$labc++;
					break;
				case 'TUT':
					$session->addTutorials($rawsession, $tutc);
					$tutc++;
					break;
				//case 'SEC':
				//	$session->addSections($rawsession, $secc);
				//	$secc++;
				//	break;
				default:
					$session->addSections($rawsession, $secc);
					$secc++;
					//return 3;
					break;
			}
		}
		return $session;
	}

	function __destruct() {
		curl_close($this->ch);
	}
}