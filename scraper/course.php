<?PHP

class Course {

	private $dept;
	private $level;
	private $name;
	private $desc;
	private $prereq;
	private $wqb;
	private $units;
	private $career;
	private $lectures;
	private $tutorials;
	private $labs;
	private $sections;

	function setDept($str) {
		$this->dept = $str;
	}

	function setLevel($str) {
		$this->level = $str;
	}

	function setName($str) {
		$this->name = $str;
	}

	function setDesc($str) {
		$this->desc = $str;
	}

	function setWQB($str) {
		$this->wqb = $str;
	}

	function setUnits($str) {
		$this->units = $str;
	}

	function setCareer($str) {
		$this->career = $str;
	}

	function setPrereq($str) {
		$this->prereq = $str;
	}

	function getDept() {
		return $this->dept;
	}

	function getLevel() {
		return $this->level;
	}

	function getName() {
		return $this->name;
	}

	function getDesc() {
		return $this->desc;
	}

	function getPrereq() {
		return $this->prereq;
	}

	function getWQB() {
		return $this->wqb;
	}

	function getUnits() {
		return $this->units;
	}

	function getCareer() {
		return $this->career;
	}

	function addLectures($str, $idx) {
		$this->lectures[$idx] = $str;
	}

	function addLabs($str, $idx) {
		$this->labs[$idx] = $str;
	}

	function addTutorials($str, $idx) {
		$this->tutorials[$idx] = $str;
	}
	
	function addSections($str, $idx) {
		$this->sections[$idx] = $str;
	}

	function getLectures() {
		return $this->lectures;
	}

	function getLabs() {
		return $this->labs;
	}

	function getTutorials() {
		return $this->tutorials;
	}
	
	function getSections() {
		return $this->sections;
	}

}