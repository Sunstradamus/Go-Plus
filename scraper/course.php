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
	private $lecture = ['id' => "", 'section' => "", 'day' => array(), 'start' => array(), 'end' => array(), 'room' => array(), 'prof' => array(), 'dates' => array(), ];
	private $tutorial = ['id' => "", 'section' => "", 'day' => array(), 'start' => array(), 'end' => array(), 'room' => array(), 'prof' => array(), 'dates' => array(), ];
	private $lab = ['id' => "", 'section' => "", 'day' => array(), 'start' => array(), 'end' => array(), 'room' => array(), 'prof' => array(), 'dates' => array(), ];
	private $lectures;
	private $tutorials;
	private $labs;

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

	function setLectureSection($str) {
		$this->lecture['section'] = $str;
	}

	function setLectureID($str) {
		$this->lecture['id'] = $str;
	}

	function addLectureDay($str) {
		$this->lecture['day'][] = $str;
	}

	function addLectureStart($str) {
		$this->lecture['start'][] = $str;
	}

	function addLectureEnd($str) {
		$this->lecture['end'][] = $str;
	}

	function addLectureRoom($str) {
		$this->lecture['room'][] = $str;
	}

	function addLectureProf($str) {
		$this->lecture['prof'][] = $str;
	}

	function addLectureDates($str) {
		$this->lecture['dates'][] = $str;
	}

	function setTutorialSection($str) {
		$this->tutorial['section'] = $str;
	}

	function setTutorialID($str) {
		$this->tutorial['id'] = $str;
	}

	function addTutorialDay($str) {
		$this->tutorial['day'][] = $str;
	}

	function addTutorialStart($str) {
		$this->tutorial['start'][] = $str;
	}

	function addTutorialEnd($str) {
		$this->tutorial['end'][] = $str;
	}

	function addTutorialRoom($str) {
		$this->tutorial['room'][] = $str;
	}

	function addTutorialProf($str) {
		$this->tutorial['prof'][] = $str;
	}

	function addTutorialDates($str) {
		$this->tutorial['dates'][] = $str;
	}

	function setLabSection($str) {
		$this->lab['section'] = $str;
	}

	function setLabID($str) {
		$this->lab['id'] = $str;
	}

	function addLabDay($str) {
		$this->lab['day'][] = $str;
	}

	function addLabStart($str) {
		$this->lab['start'][] = $str;
	}

	function addLabEnd($str) {
		$this->lab['end'][] = $str;
	}

	function addLabRoom($str) {
		$this->lab['room'][] = $str;
	}

	function addLabProf($str) {
		$this->lab['prof'][] = $str;
	}

	function addLabDates($str) {
		$this->lab['dates'][] = $str;
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

	function getLecture() {
		return $this->lecture;
	}

	function getLab() {
		return $this->lab;
	}

	function getTutorial() {
		return $this->tutorial;
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

	function getLectures() {
		return $this->lectures;
	}

	function getLabs() {
		return $this->labs;
	}

	function getTutorials() {
		return $this->tutorials;
	}

}