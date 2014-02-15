<?PHP
include 'cfg.php';
require 'course.php';

$fh = fopen($GLOBALS['DUMPFILE'], 'r');
$cont = fread($fh, filesize($GLOBALS['DUMPFILE']));
fclose($fh);
$arr = unserialize($cont);

$start = microtime(true);

$mysqli = new mysqli($GLOBALS["SQLHOST"], $GLOBALS["SQLUSR"], $GLOBALS["SQLPWD"], $GLOBALS["SQLDB"]);
if($mysqli->connect_errno) {
	die ("Failed to connect: (" . $mysqli->connect_errno . ") ". $mysqli->connect_error);
}

for($i = 0; $i < count($arr); $i++){
	$obj = $arr[$i];
	
	while($obj->getDept() == "" || $obj->getLevel() == "" || $obj->getName() == ""){ // New code must test
		if($i == 0){
			echo "WARNING: PULLED BLANK ON FIRST INDEX - SKIPPING<br/>";
		} else {
			echo "WARNING: PULLED BLANK ON INDEX " . $i . " - SKIPPING - PREVIOUS COURSE: " . $arr[$i-1]->getName() . "<br />";
		}
		if($i == count($arr)-1){
			goto loopbreak;
		} else {
			$obj = $arr[++$i];
		}
	}
	
	$sql = "INSERT INTO `course` (`subject`, `level`, `title`, `career`, `wqb`, `units`, `prereq`, `desc`) SELECT '" . $obj->getDept() . "', '" . $obj->getLevel() . "', '" . $mysqli->real_escape_string($obj->getName()) . "', '" . $obj->getCareer() . "', '" . $obj->getWQB() . "', '" . $obj->getUnits() . "', '" . $mysqli->real_escape_string($obj->getPrereq()) . "', '" . $mysqli->real_escape_string($obj->getDesc()) . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `course` WHERE subject='" . $obj->getDept() . "' AND level='" . $obj->getLevel() . "')";

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
		$sec = $obj->getLectures();
	
		for($j = 0; $j < count($sec); $j++){
			$sql = "INSERT IGNORE INTO `section`(`course_id`, `id`, `type`, `section`, `term`) VALUES ('" . $course_id . "', '" . $sec[$j]['id'] . "', 'LEC', '" . $sec[$j]['section'] . "', '" . $sec[$j]['term'][0] . "')";

			if(!$mysqli->query($sql)){
				die ($mysqli->errno . $mysqli->error);
			}
			if($mysqli->affected_rows != 0){
				echo "Successfully inserted LEC<br/>";
			} else {
				echo "Duplicate entry, skipping section<br/>";
			}
			for($k = 0; $k < count($sec[$j]['term']); $k++){
				$instructor_id = 1;
				if(substr_count($sec[$j]['prof'][$k], ".") == 0 || substr_count($sec[$j]['prof'][$k], "TBA") == 0 || substr_count($sec[$j]['prof'][$k], "Staff") == 0){
					$sql = "INSERT INTO `instructor`(`name`) SELECT '" . $mysqli->real_escape_string($sec[$j]['prof'][$k]) . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `instructor` WHERE name='" . $mysqli->real_escape_string($sec[$j]['prof'][$k]) . "')";
					if(!$mysqli->query($sql)){
						die ($mysqli->errno . $mysqli->error);
					}
					if($mysqli->insert_id != 0){
						$instructor_id = $mysqli->insert_id;
						echo "Success: Instructor ID - " . $instructor_id . "<br/>";
					} else {
						echo "Duplicate instructor, retrieving ID - ";
						$sql = "SELECT `id` FROM `instructor` WHERE name='" . $mysqli->real_escape_string($sec[$j]['prof'][$k]) . "'";
						if($res = $mysqli->query($sql)){
							$row = $res->fetch_assoc();
							$instructor_id = $row['id'];
							echo $instructor_id . "<br/>";
						} else {
							die($mysqli->errno . $mysqli->error);
						}
					}
				} elseif (substr_count($sec[$j]['prof'][$k], "Exam") == 1) {
					$instructor_id = 2;
				}

				$sql = "INSERT INTO `sectionComponent`(`section_id`, `day`, `instructor`, `timeslot`, `campus`, `room`, `dates`) SELECT '" . $sec[$j]['id'] . "', '" . $sec[$j]['day'][$k] . "', '" . $instructor_id . "', '" . $sec[$j]['timeslot'][$k] . "', '" . $sec[$j]['campus'][$k] . "', '" . $sec[$j]['room'][$k] . "', '" . $sec[$j]['dates'][$k] . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `sectionComponent` WHERE section_id='".$sec[$j]['id']."' AND day='".$sec[$j]['day'][$k]."' AND instructor='".$instructor_id."' AND timeslot='" . $sec[$j]['timeslot'][$k] . "' AND room='".$sec[$j]['room'][$k]."' AND dates='".$sec[$j]['dates'][$k]."')";

				if(!$mysqli->query($sql)){
					echo $mysqli->errno . $mysqli->error;
				}
				if($mysqli->affected_rows != 0){
					echo "Successfully updated LEC components<br/>";
				} else {
					echo "Duplicate LEC component, skipping - " . $sec[$j]['id'] . "<br/>";
				}
			}
		}
	}
	if($obj->getTutorials() != NULL){
		$sec = $obj->getTutorials();
		
		for($j = 0; $j < count($sec); $j++){
			$sql = "INSERT IGNORE INTO `section`(`course_id`, `id`, `type`, `section`, `term`) VALUES ('" . $course_id . "', '" . $sec[$j]['id'] . "', 'TUT', '" . $sec[$j]['section'] . "', '" . $sec[$j]['term'][0] . "')";

			if(!$mysqli->query($sql)){
				die ($mysqli->errno . $mysqli->error);
			}
			if($mysqli->affected_rows != 0){
				echo "Successfully inserted TUT<br/>";
			} else {
				echo "Duplicate entry, skipping section<br/>";
			}
			for($k = 0; $k < count($sec[$j]['term']); $k++){
				$instructor_id = 1;
				if(substr_count($sec[$j]['prof'][$k], ".") == 0 || substr_count($sec[$j]['prof'][$k], "TBA") == 0 || substr_count($sec[$j]['prof'][$k], "Staff") == 0){
					$sql = "INSERT INTO `instructor`(`name`) SELECT '" . $mysqli->real_escape_string($sec[$j]['prof'][$k]) . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `instructor` WHERE name='" . $mysqli->real_escape_string($sec[$j]['prof'][$k]) . "')";
					if(!$mysqli->query($sql)){
						die ($mysqli->errno . $mysqli->error);
					}
					if($mysqli->insert_id != 0){
						$instructor_id = $mysqli->insert_id;
						echo "Success: Instructor ID - " . $instructor_id . "<br/>";
					} else {
						echo "Duplicate instructor, retrieving ID - ";
						$sql = "SELECT `id` FROM `instructor` WHERE name='" . $mysqli->real_escape_string($sec[$j]['prof'][$k]) . "'";
						if($res = $mysqli->query($sql)){
							$row = $res->fetch_assoc();
							$instructor_id = $row['id'];
							echo $instructor_id . "<br/>";
						} else {
							die($mysqli->errno . $mysqli->error);
						}
					}
				} elseif (substr_count($sec[$j]['prof'][$k], "Exam") == 1) {
					$instructor_id = 2;
				}

				$sql = "INSERT INTO `sectionComponent`(`section_id`, `day`, `instructor`, `timeslot`, `campus`, `room`, `dates`) SELECT '" . $sec[$j]['id'] . "', '" . $sec[$j]['day'][$k] . "', '" . $instructor_id . "', '" . $sec[$j]['timeslot'][$k] . "', '" . $sec[$j]['campus'][$k] . "', '" . $sec[$j]['room'][$k] . "', '" . $sec[$j]['dates'][$k] . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `sectionComponent` WHERE section_id='".$sec[$j]['id']."' AND day='".$sec[$j]['day'][$k]."' AND instructor='".$instructor_id."' AND timeslot='" . $sec[$j]['timeslot'][$k] . "' AND room='".$sec[$j]['room'][$k]."' AND dates='".$sec[$j]['dates'][$k]."')";

				if(!$mysqli->query($sql)){
					echo $mysqli->errno . $mysqli->error;
				}
				if($mysqli->affected_rows != 0){
					echo "Successfully updated TUT components<br/>";
				} else {
					echo "Duplicate TUT component, skipping - " . $sec[$j]['id'] . "<br/>";
				}
			}
		}
	}
	if($obj->getLabs() != NULL){
		$sec = $obj->getLabs();
		
		for($j = 0; $j < count($sec); $j++){
			$sql = "INSERT IGNORE INTO `section`(`course_id`, `id`, `type`, `section`, `term`) VALUES ('" . $course_id . "', '" . $sec[$j]['id'] . "', 'LAB', '" . $sec[$j]['section'] . "', '" . $sec[$j]['term'][0] . "')";

			if(!$mysqli->query($sql)){
				die ($mysqli->errno . $mysqli->error);
			}
			if($mysqli->affected_rows != 0){
				echo "Successfully inserted LAB<br/>";
			} else {
				echo "Duplicate entry, skipping section<br/>";
			}
			for($k = 0; $k < count($sec[$j]['term']); $k++){
				$instructor_id = 1;
				if(substr_count($sec[$j]['prof'][$k], ".") == 0 || substr_count($sec[$j]['prof'][$k], "TBA") == 0 || substr_count($sec[$j]['prof'][$k], "Staff") == 0){
					$sql = "INSERT INTO `instructor`(`name`) SELECT '" . $mysqli->real_escape_string($sec[$j]['prof'][$k]) . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `instructor` WHERE name='" . $mysqli->real_escape_string($sec[$j]['prof'][$k]) . "')";
					if(!$mysqli->query($sql)){
						die ($mysqli->errno . $mysqli->error);
					}
					if($mysqli->insert_id != 0){
						$instructor_id = $mysqli->insert_id;
						echo "Success: Instructor ID - " . $instructor_id . "<br/>";
					} else {
						echo "Duplicate instructor, retrieving ID - ";
						$sql = "SELECT `id` FROM `instructor` WHERE name='" . $mysqli->real_escape_string($sec[$j]['prof'][$k]) . "'";
						if($res = $mysqli->query($sql)){
							$row = $res->fetch_assoc();
							$instructor_id = $row['id'];
							echo $instructor_id . "<br/>";
						} else {
							die($mysqli->errno . $mysqli->error);
						}
					}
				} elseif (substr_count($sec[$j]['prof'][$k], "Exam") == 1) {
					$instructor_id = 2;
				}

				$sql = "INSERT INTO `sectionComponent`(`section_id`, `day`, `instructor`, `timeslot`, `campus`, `room`, `dates`) SELECT '" . $sec[$j]['id'] . "', '" . $sec[$j]['day'][$k] . "', '" . $instructor_id . "', '" . $sec[$j]['timeslot'][$k] . "', '" . $sec[$j]['campus'][$k] . "', '" . $sec[$j]['room'][$k] . "', '" . $sec[$j]['dates'][$k] . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `sectionComponent` WHERE section_id='".$sec[$j]['id']."' AND day='".$sec[$j]['day'][$k]."' AND instructor='".$instructor_id."' AND timeslot='" . $sec[$j]['timeslot'][$k] . "' AND room='".$sec[$j]['room'][$k]."' AND dates='".$sec[$j]['dates'][$k]."')";

				if(!$mysqli->query($sql)){
					echo $mysqli->errno . $mysqli->error;
				}
				if($mysqli->affected_rows != 0){
					echo "Successfully updated LAB components<br/>";
				} else {
					echo "Duplicate LAB component, skipping - " . $sec[$j]['id'] . "<br/>";
				}
			}
		}
	}
	if($obj->getSections() != NULL){
		$sec = $obj->getSections();
		
		for($j = 0; $j < count($sec); $j++){
			$sql = "INSERT IGNORE INTO `section`(`course_id`, `id`, `type`, `section`, `term`) VALUES ('" . $course_id . "', '" . $sec[$j]['id'] . "', '" . $sec[$j]['type'] . "', '" . $sec[$j]['section'] . "', '" . $sec[$j]['term'][0] . "')";

			if(!$mysqli->query($sql)){
				die ($mysqli->errno . $mysqli->error);
			}
			if($mysqli->affected_rows != 0){
				echo "Successfully inserted SEC<br/>";
			} else {
				echo "Duplicate entry, skipping section<br/>";
			}
			for($k = 0; $k < count($sec[$j]['term']); $k++){
				$instructor_id = 1;
				if(substr_count($sec[$j]['prof'][$k], ".") == 0 || substr_count($sec[$j]['prof'][$k], "TBA") == 0 || substr_count($sec[$j]['prof'][$k], "Staff") == 0){
					$sql = "INSERT INTO `instructor`(`name`) SELECT '" . $mysqli->real_escape_string($sec[$j]['prof'][$k]) . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `instructor` WHERE name='" . $mysqli->real_escape_string($sec[$j]['prof'][$k]) . "')";
					if(!$mysqli->query($sql)){
						die ($mysqli->errno . $mysqli->error);
					}
					if($mysqli->insert_id != 0){
						$instructor_id = $mysqli->insert_id;
						echo "Success: Instructor ID - " . $instructor_id . "<br/>";
					} else {
						echo "Duplicate instructor, retrieving ID - ";
						$sql = "SELECT `id` FROM `instructor` WHERE name='" . $mysqli->real_escape_string($sec[$j]['prof'][$k]) . "'";
						if($res = $mysqli->query($sql)){
							$row = $res->fetch_assoc();
							$instructor_id = $row['id'];
							echo $instructor_id . "<br/>";
						} else {
							die($mysqli->errno . $mysqli->error);
						}
					}
				} elseif (substr_count($sec[$j]['prof'][$k], "Exam") == 1) {
					$instructor_id = 2;
				}

				$sql = "INSERT INTO `sectionComponent`(`section_id`, `day`, `instructor`, `timeslot`, `campus`, `room`, `dates`) SELECT '" . $sec[$j]['id'] . "', '" . $sec[$j]['day'][$k] . "', '" . $instructor_id . "', '" . $sec[$j]['timeslot'][$k] . "', '" . $sec[$j]['campus'][$k] . "', '" . $sec[$j]['room'][$k] . "', '" . $sec[$j]['dates'][$k] . "' FROM DUAL WHERE NOT EXISTS (SELECT * FROM `sectionComponent` WHERE section_id='".$sec[$j]['id']."' AND day='".$sec[$j]['day'][$k]."' AND instructor='".$instructor_id."' AND timeslot='" . $sec[$j]['timeslot'][$k] . "' AND campus='".$sec[$j]['campus'][$k]."' AND room='".$sec[$j]['room'][$k]."' AND dates='".$sec[$j]['dates'][$k]."')";

				if(!$mysqli->query($sql)){
					echo $mysqli->errno . $mysqli->error;
				}
				if($mysqli->affected_rows != 0){
					echo "Successfully updated SEC components<br/>";
				} else {
					echo "Duplicate SEC component, skipping - " . $sec[$j]['id'] . "<br/>";
				}
			}
		}
	}
}

loopbreak:
$mysqli->close();
$end = microtime(true);
echo $end-$start;