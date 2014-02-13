<?PHP
class DBParser {

	private $ts;

	function genTime($ts) {
		$start = $this->timeslotToStartTime($ts);
		$end = $this->timeslotToEndTime($ts)+100;
		$time = date('g:i A', strtotime($start)). " - " . date('g:i A', strtotime($end));
		return $time;
	}
	
	function timeslotToStartTime($ts) {
		$time = 0;
		if($ts & 1) {
			$time = 830;
			$this->ts = $ts - 1;
			return $time;
		}
		if($ts & 2) {
			$time = 930;
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
			$time = 830;
		}
		if($ts & 2) {
			$time = 930;
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