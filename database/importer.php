<?PHP
include 'cfg.php';
require 'course.php';

$fh = fopen('dmp.dat', 'r');
$cont = fread($fh, filesize('dmp.dat'));
fclose($fh);
$arr = unserialize($cont);

$mysqli = new mysqli($GLOBALS["SQLHOST"], $GLOBALS["SQLUSR"], $GLOBALS["SQLPWD"], $GLOBALS["SQLDB"]);
if($mysqli->connect_errno) {
	echo "Failed to connect: (" . $mysqli->connect_errno . ") ". $mysqli->connect_error;
}

for($i = 0; $i < count($arr); $i++){
	$obj = $arr[$i];
	$sql = "INSERT INTO `course` (`subject`, `level`, `title`, `career`, `wqb`, `units`, `prereq`, `desc`) SELECT '" . $obj->getDept() . "', '" . $obj->getLevel() . "', '" . $obj->getName() . "', '" . $obj->getCareer() . "', '" . $obj->getWQB() . "', '" . $obj->getUnits() . "', '" . $obj->getPrereq() . "', '" . $obj->getDesc() . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `course` WHERE subject='" . $obj->getDept() . "' AND level='" . $obj->getLevel() . "')";

	if(!$mysqli->query($sql)){
		echo $mysqli->errno . $mysqli->error;
	}

	if($mysqli->insert_id != 0){
		echo "Success: Course ID - " . $mysqli->insert_id ."<br/>";
		$course_id = $mysqli->insert_id;
	} else {
		echo "Duplicate entry, retrieving Course ID - ";
		$sql = "SELECT `id` FROM `course` WHERE subject ='" . $obj->getDept() . "' AND level = '" . $obj->getLevel() . "'";
		if($res = $mysqli->query($sql)){
			$row = $res->fetch_assoc();
			$course_id = $row['id'];
			echo $course_id . "<br/>";
		} else {
			die($mysqli->errno . $mysqli->error);
		}
	}

	if($obj->getLectures() != NULL){
		$lec = $obj->getLectures();
	
		for($j = 0; $j < count($lec); $j++){
			$sql = "INSERT IGNORE INTO `section`(`course_id`, `id`, `type`, `section`, `term`) VALUES ('" . $course_id . "', '" . $lec[$j]['id'] . "', 'LEC', '" . $lec[$j]['section'] . "', '" . $lec[$j]['term'][0] . "')";

			if(!$mysqli->query($sql)){
				die ($mysqli->errno . $mysqli->error);
			}
			if($mysqli->affected_rows != 0){
				echo "Successfully inserted LEC<br/>";
			} else {
				echo "Duplicate entry, skipping section<br/>";
			}
			for($k = 0; $k < count($lec[$j]['term']); $k++){
				$instructor_id = 1;
				if(substr_count($lec[$j]['prof'][$k], ".") == 0 || substr_count($lec[$j]['prof'][$k], "TBA") == 0 || substr_count($lec[$j]['prof'][$k], "Staff") == 0){
					$sql = "INSERT INTO `instructor`(`name`) SELECT '" . $lec[$j]['prof'][$k] . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `instructor` WHERE name='" . $lec[$j]['prof'][$k] . "')";
					if(!$mysqli->query($sql)){
						die ($mysqli->errno . $mysqli->error);
					}
					if($mysqli->insert_id != 0){
						$instructor_id = $mysqli->insert_id;
						echo "Success: Instructor ID - " . $instructor_id . "<br/>";
					} else {
						echo "Duplicate instructor, retrieving ID - ";
						$sql = "SELECT `id` FROM `instructor` WHERE name='" . $lec[$j]['prof'][$k] . "'";
						if($res = $mysqli->query($sql)){
							$row = $res->fetch_assoc();
							$instructor_id = $row['id'];
							echo $instructor_id . "<br/>";
						} else {
							die($mysqli->errno . $mysqli->error);
						}
					}
				} elseif (substr_count($lec[$j]['prof'][$k], "Exam") == 1) {
					$instructor_id = 2;
				}

				$sql = "INSERT INTO `sectionComponent`(`section_id`, `day`, `instructor`, `timeslot`, `campus`, `room`, `dates`) SELECT '" . $lec[$j]['id'] . "', '" . $lec[$j]['day'][$k] . "', '" . $instructor_id . "', '0', '" . $lec[$j]['campus'][$k] . "', '" . $lec[$j]['room'][$k] . "', '" . $lec[$j]['dates'][$k] . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `sectionComponent` WHERE section_id='".$lec[$j]['id']."' AND day='".$lec[$j]['day'][$k]."' AND instructor='".$instructor_id."' AND timeslot='0' AND room='".$lec[$j]['room'][$k]."' AND dates='".$lec[$j]['dates'][$k]."')";

				if(!$mysqli->query($sql)){
					echo $mysqli->errno . $mysqli->error;
				}
				if($mysqli->affected_rows != 0){
					echo "Successfully updated LEC components<br/>";
				} else {
					echo "Duplicate LEC component, skipping<br/>";
				}
			}
		}
	} elseif ($obj->getTutorials() != NULL){
		$tut = $obj->getTutorials();
	} elseif ($obj->getLabs() != NULL){
		$lab = $obj->getLabs();
	} else {
		$sec = $obj->getSections();
	}
}

$mysqli->close();