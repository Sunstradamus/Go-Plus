<?PHP
include 'cfg.php';
require 'course.php';

$fh = fopen('dmp.dat', 'r');
$cont = fread($fh, filesize('dmp.dat'));
fclose($fh);
$arr = unserialize($cont);

$obj = $arr[0];

$mysqli = new mysqli($GLOBALS["SQLHOST"], $GLOBALS["SQLUSR"], $GLOBALS["SQLPWD"], $GLOBALS["SQLDB"]);
if($mysqli->connect_errno) {
	echo "Failed to connect: (" . $mysqli->connect_errno . ") ". $mysqli->connect_error;
}

$sql = "INSERT INTO `course` (`subject`, `level`, `title`, `career`, `wqb`, `units`, `prereq`, `desc`) SELECT '" . $obj->getDept() . "', '" . $obj->getLevel() . "', '" . $obj->getName() . "', '" . $obj->getCareer() . "', '" . $obj->getWQB() . "', '" . $obj->getUnits() . "', '" . $obj->getPrereq() . "', '" . $obj->getDesc() . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `course` WHERE subject='" . $obj->getDept() . "' AND level='" . $obj->getLevel() . "')";

if(!$mysqli->query($sql)){
	echo $mysqli->errno . $mysqli->error;
}

$course_id = $mysqli->insert_id;
if($mysqli->insert_id != 0){
	echo "Success: Course ID - " . $mysqli->insert_id;
} else {
	echo "Duplicate entry";
}

if($obj->getLectures() != NULL){
	$lec = $obj->getLectures();

	$sql = "INSERT INTO `section`(`course_id`, `id`, `type`, `section`, `term`) VALUES ('" . $mysqli->insert_id . "', '" . $lec[0]['id'] . "', 'LEC', '" . $lec[0]['section'] . "', '" . $lec[0]['term'] . "')";

	if(!$mysqli->query($sql)){
		echo $mysqli->errno . $mysqli->error;
	}
	echo "Successfully inserted LEC";

	$instructor_id = 1;
	if(substr_count($lec[0]['prof'][0], "Tbd") == 1 || substr_count($lec[0]['prof'][0], "TBA") == 1){
		$sql = "INSERT INTO `instructor`(`name`) VALUES ('" . $lec[0]['prof'][0] . "')";
		if(!$mysqli->query($sql)){
			echo $mysqli->errno . $mysqli->error;
		}
		$instructor_id = $mysqli->insert_id;
		echo "Success: Instructor ID - " . $instructor_id;
	}

	$sql = "INSERT INTO `sectionComponent`(`section_id`, `day`, `instructor`, `timeslot`, `campus`, `room`, `dates`) VALUES ('" . $lec[0]['id'] . "', '" . $lec[0]['day'][0] . "', '" . $instructor_id . "', '0', '" . $lec[0]['campus'][0] . "', '" . $lec[0]['room'][0] . "', '" . $lec[0]['dates'][0] . "')";

	if(!$mysqli->query($sql)){
		echo $mysqli->errno . $mysqli->error;
	}
	echo "Successfully inserted LEC components";
} elseif ($obj->getTutorials() != NULL){
	$tut = $obj->getTutorials();
} elseif ($obj->getLabs() != NULL){
	$lab = $obj->getLabs();
} else {
	$sec = $obj->getSections();
}

$mysqli->close();