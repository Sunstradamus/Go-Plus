<?PHP
require 'cfg.php';
require 'course.php';
require 'scraper.php';
set_time_limit(60);
ob_start();
define('WORKING_DIRECTORY', getcwd());
define('SELF_FILENAME', basename(__FILE__));
function timeout(){
	chdir(WORKING_DIRECTORY);
	if($GLOBALS['status'] == 1){
		ob_end_flush();
		$end = microtime(true);
		echo "Ended on subject " . (count($GLOBALS['arr']) - 1) . " and course " . count($GLOBALS['arr'][count($GLOBALS['arr']) - 1]) . "<br/>";
		echo ($end - $GLOBALS['start']).' time elapsed before shutdown triggered.';
		$fh = fopen($GLOBALS['LOGFILE'], 'a');
		fwrite($fh, date('[D M d H:i:s Y]').' This should not show up, check status == 1' . PHP_EOL);
		fclose($fh);
	} elseif($GLOBALS['status'] == 2){
		$fh = fopen($GLOBALS['LOGFILE'], 'a');
		fwrite($fh, date('[D M d H:i:s Y]').' SQL ERROR ENCOUNTERED TERMINATING' . PHP_EOL);
		fclose($fh);
		die();
	} elseif($GLOBALS['status'] == 3){
		ob_end_flush();
		$end = microtime(true);
		echo "Ended on subject " . (count($GLOBALS['arr']) - 1) . " and course " . count($GLOBALS['arr'][count($GLOBALS['arr']) - 1]) . "<br/>";
		echo ($end - $GLOBALS['start']).' time elapsed before shutdown triggered.';
		if(file_exists($GLOBALS['DUMPFILE'])){
			unlink($GLOBALS['DUMPFILE']);
		}
		if(file_exists($GLOBALS['COOKIEJAR'])){
			unlink($GLOBALS['COOKIEJAR']);
		}
		$fh = fopen($GLOBALS['LOGFILE'], 'a');
		fwrite($fh, date('[D M d H:i:s Y]').' Finished scraping' . PHP_EOL);
		fclose($fh);
	} else {
		$fh = fopen($GLOBALS['DUMPFILE'], 'w');
		fwrite($fh, serialize($GLOBALS['arr']));
		fclose($fh);
		header('Location: http://testdump.x10host.com/'.SELF_FILENAME.'?index=' . $GLOBALS['index'] . '&term=' . $GLOBALS['session']);
		$fh = fopen($GLOBALS['LOGFILE'], 'a');
		fwrite($fh, date('[D M d H:i:s Y]').' TIMELIMIT EXCEEDED, REDIRECTING WITH index='.$GLOBALS['index'] . ' term=' . $GLOBALS['session'] . ' i=' . $GLOBALS['i'] . ' j=' . $GLOBALS['j'] . ' subjects=' . $GLOBALS['subjects'] . ' courses=' . $GLOBALS['courses'] . ' curi=' . $GLOBALS['curi'] . ' curj=' . $GLOBALS['curj'] . PHP_EOL);
		fclose($fh);
		die();
	}
}
register_shutdown_function('timeout');

$start = microtime(true);
if(isset($_GET['index'])){
	$index = $_GET['index'];
} else {
	$index = 'A';
}
if(isset($_GET['s'])){
	$fh = fopen($GLOBALS['LOGFILE'], 'a');
	fwrite($fh, date('[D M d H:i:s Y]').' Started scraping on index '. $index . PHP_EOL);
	fclose($fh);
}
if(isset($_GET['term'])){
	switch($_GET['term']){
		case 1:
			$session = 1;
			break;
		case 2:
			$session = 4;
			break;
		case 3:
			$session = 7;
			break;
		case 4:
			$session = 4;
			break;
		case 7:
			$session = 7;
			break;
		default:
			$session = 7;
			break;
	}
	//$session = $_GET['term'];
	$term = '1' . date('y') . $session;
} else {
	$term = '1' . date('y') . '7';
}
if(file_exists($GLOBALS['DUMPFILE'])){
	$fh = fopen($GLOBALS['DUMPFILE'], 'r');
	$cont = fread($fh, filesize($GLOBALS['DUMPFILE']));
	fclose($fh);
	$arr = unserialize($cont);
	$curi = (count($arr)-1);
	$curj = count($arr[$curi])-1;
} else {
	$curi = 0;
	$curj = 0;
}

$scraper = new Scraper;
$scraper->selectIndex($index);
$subjects = $scraper->getNumSubjects();
for($i = $curi; $i < $subjects; $i++){
	$scraper->toggleMenu((string) $i);
	$courses = $scraper->getNumCourses();
	for($j = $curj; $j < $courses; $j++){
		$scraper->selectCourse((string) $j);
		$scraper->selectSession();
		$scraper->selectSession($term);
		if($scraper->isMore()){
			$scraper->selectSession($term, true);
		}
		$arr[$i][$j] = $scraper->xparse();
		$scraper->returnToCatalog();
		if((microtime(true) - $start) > 50){
			exit();
		}
	}
	$scraper->toggleMenu((string) $i);
	$curj = 0;
}
$status = 1;

$mysqli = new mysqli($GLOBALS["SQLHOST"], $GLOBALS["SQLUSR"], $GLOBALS["SQLPWD"], $GLOBALS["SQLDB"]);
if($mysqli->connect_errno) {
	$status = 2;
	die ("Failed to connect: (" . $mysqli->connect_errno . ") ". $mysqli->connect_error);
}

for($i = 0; $i < count($arr); $i++){
	for($l = 0; $l < count($arr[$i]); $l++){
	$obj = $arr[$i][$l];
	
	//echo "\$arr[" . $i . "][" . $l . "]";
	while($obj->getDept() == "" || $obj->getLevel() == "" || $obj->getName() == ""){ // New code must test
		if($l == 0){
			echo "WARNING: PULLED BLANK ON FIRST INDEX - SKIPPING<br/>";
		} else {
			echo "WARNING: PULLED BLANK ON INDEX " . $l . " - SKIPPING - PREVIOUS COURSE: " . $arr[$i][$l-1]->getName() . "<br />";
		}
		if($l == (count($arr[$i])-1)){
			goto loopbreak;
		} else {
			$obj = $arr[$i][++$l];
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
	loopbreak:
}
}

$mysqli->close();
$status = 3;