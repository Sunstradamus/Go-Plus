<?PHP
class DBParser {

	private $ts;

	function numToDay($num) {
		switch($num){
			case 1:
				return 'Monday';
			case 2:
				return 'Tuesday';
			case 3:
				return 'Wednesday';
			case 4:
				return 'Thursday';
			case 5:
				return 'Friday';
			case 6:
				return 'Saturday';
			case 7:
				return 'Sunday';
			default:
				return 'Undated';
		}
	}
	
	function numToTerm($num) {
		$year = "20";
		$year .= substr($num, 1, 2);
		$sem = substr($num, 3);
		switch($sem){
			case 1:
				$sem = "Spring";
				break;
			case 4:
				$sem = "Summer";
				break;
			case 7:
				$sem = "Fall";
				break;
			default:
				$sem = "UNKNOWN";
				break;
		}
		return $sem . " " . $year;
	}
	
	function numToWQB($num) {
		$wqb = "";
		if($num & 1){
			$wqb .= "Writing|";
		}
		if($num & 2){
			$wqb .= "Quantitative|";
		}
		if($num & 4){
			$wqb .= "Science|";
		}
		if($num & 8){
			$wqb .= "Social Sciences|";
		}
		if($num & 16){
			$wqb .= "Humanities|";
		}
		$wqb = substr($wqb, 0, -1);
		$wqb = str_replace("|", "/", $wqb);
		return $wqb;
	}

	function genTime($ts) {
		$start = $this->timeslotToStartTime($ts);
		$end = $this->timeslotToEndTime($ts)+100;
		if(strlen($end) == 3){
			$end = '0'.$end;
		}
		if($start == 0 || $end == 0){
			$time = "Exam time is currently unsupported";
		} else {
			$time = date('g:i A', strtotime($start)). " - " . date('g:i A', strtotime($end));
		}
		return $time;
	}
	
	function timeslotToStartTime($ts) {
		$time = 0;
		if($ts & 1) {
			$time = '0830';
			$this->ts = $ts - 1;
			return $time;
		}
		if($ts & 2) {
			$time = '0930';
			$this->ts = $ts - 2;
			return $time;
		}
		if($ts & 4) {
			$time = 1030;
			$this->ts = $ts - 4;
			return $time;
		}
		if($ts & 8) {
			$time = 1130;
			$this->ts = $ts - 8;
			return $time;
		}
		if($ts & 16) {
			$time = 1230;
			$this->ts = $ts - 16;
			return $time;
		}
		if($ts & 32) {
			$time = 1330;
			$this->ts = $ts - 32;
			return $time;
		}
		if($ts & 64) {
			$time = 1430;
			$this->ts = $ts - 64;
			return $time;
		}
		if($ts & 128) {
			$time = 1530;
			$this->ts = $ts - 128;
			return $time;
		}
		if($ts & 256) {
			$time = 1630;
			$this->ts = $ts - 256;
			return $time;
		}
		if($ts & 512) {
			$time = 1730;
			$this->ts = $ts - 512;
			return $time;
		}
		if($ts & 1024) {
			$time = 1830;
			$this->ts = $ts - 1024;
			return $time;
		}
		if($ts & 2048) {
			$time = 1930;
			$this->ts = $ts - 2048;
			return $time;
		}
		if($ts & 4096) {
			$time = 2030;
			$this->ts = $ts - 4096;
			return $time;
		}
	}
	
	function timeslotToEndTime($ts) {
		$time = 0;
		if($ts & 1) {
			$time = '0830';
		}
		if($ts & 2) {
			$time = '0930';
		}
		if($ts & 4) {
			$time = 1030;
		}
		if($ts & 8) {
			$time = 1130;
		}
		if($ts & 16) {
			$time = 1230;
		}
		if($ts & 32) {
			$time = 1330;
		}
		if($ts & 64) {
			$time = 1430;
		}
		if($ts & 128) {
			$time = 1530;
		}
		if($ts & 256) {
			$time = 1630;
		}
		if($ts & 512) {
			$time = 1730;
		}
		if($ts & 1024) {
			$time = 1830;
		}
		if($ts & 2048) {
			$time = 1930;
		}
		if($ts & 4096) {
			$time = 2030;
		}
		return $time;
	}

}