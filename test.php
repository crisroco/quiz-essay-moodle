<?php
global $DB, $CFG, $PAGE, $OUTPUT, $USER;
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once('locallib.php');
require_once('lib.php');

require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($SITE->fullname);
$main_url = new moodle_url('/mod/quiz/reviewquestionB.php');
$PAGE->set_url($main_url);
$title = 'Corregir ensayos';
$PAGE->set_title($title);
$PAGE->set_heading($title);
print $OUTPUT->header();

$str = '3&4&9';//user quiz module
$str = explode('&', $str);

$userid = $str[0];
$quiz = $str[1];
$module = $str[2];

$modules = $DB->get_record('course_modules',  array('id'=>$module));
$gradeitem = $DB->get_record('grade_items',  array('courseid'=> $modules->course, 'iteminstance' =>$modules->instance ));
$grade_grades = $DB->get_record('grade_grades',  array('itemid'=>$gradeitem->id, 'userid'=>$userid));
echo "<pre>";
 		print_r($grade_grades);
 	echo "</pre>";


$sql_steps = "SELECT qnas.id as stepid, qna.questionid ,qna.id as qnaid, qa.quiz, qnas.userid as userid, qa.state, qa.sumgrades, qna.maxmark, qna.responsesummary as respuesta, qnas.fraction, qnas.timecreated 
	FROM {quiz_attempts} qa
	INNER JOIN {question_attempts} qna ON qa.id = qna.questionusageid
	INNER JOIN {question_attempt_steps} qnas ON qna.id = qnas.questionattemptid
	WHERE qa.quiz IN (?) 
	AND qa.userid IN (?)
	ORDER BY  qna.questionid ASC, qnas.timecreated DESC";

$steps = $DB->get_records_sql($sql_steps, array(4,3));

$questionid = '';
$sumgrades = 0;
foreach ($steps as $key => $value) {
	if ($questionid == $value->questionid) {
		continue;
	}elseif ($value->fraction == '') {
		$sumgrades = 'null';
		break;
	}else 
	{	
		$sumgrades += ($value->maxmark * $value->fraction);
	}

	$questionid = $value->questionid;
}

echo "<pre>";
 		print_r($sumgrades);
 	echo "</pre>";
 	


// $attempts = quiz_get_user_attempts($quiz->id, $userid, 'finished');
 $attempts = quiz_get_user_attempts(4, 3, 'finished');
 foreach ($attempts as $key => $value) {
 	echo "<pre>";
 		print_r($value);
 	echo "</pre>";
 }
 die();

 /*$create2 = new stdClass();
	$create2->id = 3;

 	$grades = quiz_get_user_grades($create2, 0);
 	echo "<pre>";
 		print_r($grades);
 	echo "</pre>";*/

   
 
 






print $OUTPUT->footer();