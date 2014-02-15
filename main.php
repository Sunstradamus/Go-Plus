<?PHP
require 'cfg.php';
require 'dbparse.php';

$mysqli = new mysqli($GLOBALS["SQLHOST"], $GLOBALS["SQLUSR"], $GLOBALS["SQLPWD"], $GLOBALS["SQLDB"]);
if($mysqli->connect_errno) {
	die ("Failed to connect: (" . $mysqli->connect_errno . ") ". $mysqli->connect_error);
}
$parse = new DBParser();

if(isset($_GET['search'])){
	$start = microtime(true);

	$sql = "SELECT * FROM `course` JOIN `section` ON course.id = section.course_id JOIN `sectionComponent` ON sectionComponent.section_id = section.id JOIN `instructor` on sectionComponent.instructor = instructor.id WHERE ";

	if(isset($_GET['room']) && $_GET['room'] != ""){
		$sql .= "sectionComponent.room='" . $mysqli->real_escape_string($_GET['room']) . "' AND ";
		// Can simpy postpend sectionComponent.room='' and break query down by empty string
	} else {
		//$sql = "SELECT * FROM `course` JOIN `section` ON course.id = section.course_id JOIN `sectionComponent` ON sectionComponent.section_id = section.id JOIN `instructor` on sectionComponent.instructor = instructor.id WHERE section.term=1141 ";
		$sql .= "1=1 AND ";
	}
	if(isset($_GET['lvl']) && $_GET['lvl'] != ""){
		switch($_GET['lvl_mod']){
			case 1:
				$sql .= "course.level='" . $mysqli->real_escape_string($_GET['lvl']) . "' AND ";
				break;
			case 2:
				$sql .= "course.level>'" . $mysqli->real_escape_string($_GET['lvl']) . "' AND ";
				break;
			case 3:
				$sql .= "course.level<'" . $mysqli->real_escape_string($_GET['lvl']) . "' AND ";
				break;
			case 4:
				$sql .= "course.level>='" . $mysqli->real_escape_string($_GET['lvl']) . "' AND course.level<'" . $mysqli->real_escape_string($_GET['lvl']+100) . "' AND ";
				break;
			default:
				$sql .= "course.level='" . $mysqli->real_escape_string($_GET['lvl']) . "' AND ";
				break;
		}
	} else {
		$sql .= "1=1 AND ";
	}
	if(isset($_GET['prof']) && $_GET['prof'] != ""){
		$sql .= "instructor.name LIKE '%" . $mysqli->real_escape_string($_GET['prof']) . "%' AND ";
	} else {
		$sql .= "1=1 AND ";
	}
	if(isset($_GET['term']) && $_GET['term'] != ""){
		$sql .= "section.term='" . $mysqli->real_escape_string($_GET['term']) . "' AND ";
	} else {
		$sql .= "1=1 AND ";
	}
	if(isset($_GET['campus']) && $_GET['campus'] != ""){
		$sql .= "sectionComponent.campus='" . $mysqli->real_escape_string($_GET['campus']) . "' AND ";
	} else {
		$sql .= "1=1 AND ";
	}
	if(isset($_GET['type']) && $_GET['type'] != ""){
		$sql .= "section.type='" . $mysqli->real_escape_string($_GET['type']) . "' AND ";
	} else {
		$sql .= "1=1 AND ";
	}
	if(isset($_GET['wqb'])){
		$c = 0;
		foreach($_GET['wqb'] as $value){
			switch($value){
				case 'w':
					$c += 1;
					break;
				case 'q':
					$c += 2;
					break;
				case 's':
					$c += 4;
					break;
				case 'ss':
					$c += 8;
					break;
				case 'h':
					$c += 16;
					break;
				default:
					$c = 0;
					break;
			}
		}
		$sql .= "course.wqb='" . $c . "' AND ";
	}
	if(isset($_GET['day'])){
		$sql .= "(";
		foreach($_GET['day'] as $value){
			$sql .= "sectionComponent.day='" . $mysqli->real_escape_string($value) . "' OR ";
		}
		$sql = substr($sql, 0, -4);
		$sql .= ") AND ";
	}
	if(isset($_GET['ts'])){
		$ts = 0;
		foreach($_GET['ts'] as $value){
			$ts += $value;
		}
		$sql .= "sectionComponent.timeslot&" . $mysqli->real_escape_string($ts) . ">0 AND ";
	}
	
	if(isset($_GET['dept']) && $_GET['dept'] != ""){
		$sql .= "course.subject='" . $mysqli->real_escape_string($_GET['dept']) . "' ";
	} else {
		$sql .= "1=1 ";
	}
	
	$sql .= "ORDER BY section.course_id ASC";

	if($res = $mysqli->query($sql)){
		if($mysqli->affected_rows == 0){
			echo "No search results found.";
		} else {
			echo "<table>";
			while($row = $res->fetch_assoc()){
				echo "<tr>";
				//echo "<td>" . $row['course_id'] . "</td>";
				echo "<td>" . $row['subject'] . "</td>";
				echo "<td>" . $row['level'] . "</td>";
				echo "<td>" . $row['title'] . "</td>";
				echo "<td>" . $row['career'] . "</td>";
				echo "<td>" . $parse->numToWQB($row['wqb']) . "</td>";
				echo "<td>" . $row['units'] . "</td>";
				echo "<td>" . $row['prereq'] . "</td>";
				echo "<td>" . $row['desc'] . "</td>";
				echo "<td>" . $row['section_id'] . "</td>";
				echo "<td>" . $row['type'] . "</td>";
				echo "<td>" . $row['section'] . "</td>";
				echo "<td>" . $parse->numToTerm($row['term']) . "</td>";
				echo "<td>" . $parse->numToDay($row['day']) . "</td>";
				echo "<td>" . $row['name'] . "</td>";
				echo "<td>" . $parse->genTime($row['timeslot']) . "</td>";
				echo "<td>" . $row['campus'] . "</td>";
				echo "<td>" . $row['room'] . "</td>";
				echo "</tr>";
			}
			echo "</table>";
		}
	} else {
		echo $mysqli->errno . $mysqli->error;
	}

	$mysqli->close();

	$end = microtime(true);
	echo "<br/>";
	echo "Page took " . ($end - $start) . " seconds to generate";
} else { ?>

You can fill any of these boxes in to search by them, if you leave them blank, it assumes you do NOT care about it and leaves it as a wildcard and not a search condition. For example,
 if you wanted a course run by the amazing Jamie Mulholland that also meets the B-Hum requirement, you would enter Jamie Mulholland in instructor and tick only Humanities.<br/><br/>

<form method="get">
<input type="hidden" name="search" >
Search by course subject: <select name="dept">
<option value="">Any</option>
<?PHP
	$sql = "SELECT DISTINCT subject FROM `course` ORDER BY subject ASC";
	if($res = $mysqli->query($sql)){
		while($row = $res->fetch_assoc()){
			echo "<option value=\"" . $row['subject'] . "\">" . $row['subject'] . "</option>";
		}
	}
?>
</select><br />
Search by course number: <select name="lvl_mod"><option value="1">Exactly equal to</option><option value="2">Greater than</option><option value="3">Less than</option><option value="4">In +100 range of</option> <input type="text" name="lvl" maxlength="3"><br />
Search by room: <input type="text" name="room" ><br />
Search by instructor: <input type="text" name="prof" ><br />
Search by WQB: <input type="checkbox" name="wqb[]" value="w">Writing <input type="checkbox" name="wqb[]" value="q">Quantitative <input type="checkbox" name="wqb[]" value="s">Science <input type="checkbox" name="wqb[]" value="ss">Social Science <input type="checkbox" name="wqb[]" value="h">Humanities<br />
Search by timeslot: <input type="checkbox" name="ts[]" value="1">8:30 - 9:30 AM <input type="checkbox" name="ts[]" value="2">9:30 - 10:30 AM <input type="checkbox" name="ts[]" value="4">10:30 - 11:30 AM <input type="checkbox" name="ts[]" value="8">11:30 AM - 12:30 PM<br/><input type="checkbox" name="ts[]" value="16">12:30 - 1:30 PM <input type="checkbox" name="ts[]" value="32">1:30 - 2:30 PM <input type="checkbox" name="ts[]" value="64">2:30 - 3:30 PM <input type="checkbox" name="ts[]" value="128">3:30 - 4:30 PM <input type="checkbox" name="ts[]" value="256">4:30 - 5:30 PM<br /><input type="checkbox" name="ts[]" value="512">5:30 - 6:30 PM <input type="checkbox" name="ts[]" value="1024">6:30 - 7:30 PM <input type="checkbox" name="ts[]" value="2048">7:30 - 8:30 PM <input type="checkbox" name="ts[]" value="4096">8:30 - 9:30 PM <br />
Search by term: 
<select name="term">
<?PHP
	$sql = "SELECT DISTINCT term FROM `section` ORDER BY term ASC";
	if($res = $mysqli->query($sql)){
		while($row = $res->fetch_assoc()){
			echo "<option value=\"" . $row['term'] . "\">" . $parse->numToTerm($row['term']) . "</option>";
		}
	}
?>
</select><br />
Search by type: 
<select name="type">
<option value="">Any</option>
<?PHP
	$sql = "SELECT DISTINCT type FROM `section` ORDER BY type ASC";
	if($res = $mysqli->query($sql)){
		$res->data_seek(1);
		while($row = $res->fetch_assoc()){
			if($row['type'] == "LEC"){
				echo "<option value=\"" . $row['type'] . "\" selected>" . $row['type'] . "</option>";
			} else {
				echo "<option value=\"" . $row['type'] . "\">" . $row['type'] . "</option>";
			}
		}
	}
?>
</select><br />
Search by campus: 
<select name="campus">
<option value="">Any</option>
<?PHP
	$sql = "SELECT DISTINCT campus FROM `sectionComponent` ORDER BY campus ASC";
	if($res = $mysqli->query($sql)){
		$res->data_seek(1);
		while($row = $res->fetch_assoc()){
			echo "<option value=\"" . $row['campus'] . "\">" . $row['campus'] . "</option>";
		}
	}
?>
</select><br />
Search by day: <input type="checkbox" name="day[]" value="1">Mon <input type="checkbox" name="day[]" value="2">Tues <input type="checkbox" name="day[]" value="3">Wed <input type="checkbox" name="day[]" value="4">Thurs <input type="checkbox" name="day[]" value="5">Fri <input type="checkbox" name="day[]" value="6">Sat <input type="checkbox" name="day[]" value="7">Sun<br />
<input type="submit" name="Submit" >
</form>

<?PHP
	$mysqli->close();
}