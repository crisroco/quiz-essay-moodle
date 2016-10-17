<?php
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $USER, $CFG;

$nota = $_POST['grade'];
$qna = $_POST['qna'];
$comment = $_POST['comment'];
$valores = $_POST['valores'];
$valores = explode('&',$valores);
$userid = $valores[0];
$quiz = $valores[1];
$module = $valores[2];

$sql = "
    SELECT * 
    FROM {question_attempts} qna
    INNER JOIN {question_attempt_steps} qnas ON qna.id = qnas.questionattemptid
    WHERE qna.id IN (?)
";


$datos = $DB->get_records_sql($sql, array($qna));
$maxmark = '';
$sequencenumber = '';
$questionattemptid = '';

foreach ($datos as $key => $value) {
	$sequencenumber = $value->sequencenumber+1;
}

foreach ($datos as $key => $value) {
    if ($maxmark != '') {
        continue;
    }
   $maxmark = $value->maxmark;   
}
$fraction = $nota/$maxmark;

//TABLE: question_attempt_steps

	$create = new stdClass();
	$create->questionattemptid = $qna;
	$create->sequencenumber = $sequencenumber;
	$create->state =($fraction == 1) ? 'mangrright' : 'mangrpartial';
	$create->fraction = $fraction;
	$create->timecreated = time();
	$create->userid = $USER->id;

	$attemptstepid = $DB->insert_record('question_attempt_steps',  $create);
	echo "<br>  create";



//TABLE: question_attempt_step_data

	$create2 = new stdClass();
	$create2->attemptstepid = $attemptstepid;
	$create2->name = '-comment';
	$create2->value = $comment;
	$DB->insert_record('question_attempt_step_data',  $create2);

	
	$create3 = new stdClass();	
	$create3->attemptstepid = $attemptstepid;
	$create3->name = '-commentformat';
	$create3->value = 1;
	$DB->insert_record('question_attempt_step_data',  $create3);
	
	$create4 = new stdClass();
	$create4->attemptstepid = $attemptstepid;
	$create4->name = '-mark';
	$create4->value = $nota;
	$DB->insert_record('question_attempt_step_data',  $create4);
	
	$create5 = new stdClass();
	$create5->attemptstepid = $attemptstepid;
	$create5->name = '-maxmark';
	$create5->value = $maxmark;
	$DB->insert_record('question_attempt_step_data',  $create5);

	echo "create2";



//get sumgrades


$sql_steps = "SELECT qnas.id as stepid, qna.questionid ,qna.id as qnaid, qa.quiz, qnas.userid as userid, qa.state, qa.sumgrades, qna.maxmark, qna.responsesummary as respuesta, qnas.fraction, qnas.timecreated 
	FROM {quiz_attempts} qa
	INNER JOIN {question_attempts} qna ON qa.id = qna.questionusageid
	INNER JOIN {question_attempt_steps} qnas ON qna.id = qnas.questionattemptid
	WHERE qa.quiz IN (?) 
	AND qa.userid IN (?)
	ORDER BY  qna.questionid ASC, qnas.timecreated DESC";

$steps = $DB->get_records_sql($sql_steps, array($quiz,$userid));

$questionid = '';
$sumgrades = 0;
foreach ($steps as $key => $value) {
	if ($questionid == $value->questionid) {
		continue;
	}elseif ($value->fraction == '') {
		$sumgrades = null;
		break;
	}else 
	{	
		$sumgrades += ($value->maxmark * $value->fraction);
	}

	$questionid = $value->questionid;
}

//TABLES: 

if ($sumgrades != null) {
	
	//TABLE: quiz_attempts
	$qzattmp = $DB->get_record('quiz_attempts',  array('quiz'=>$quiz, 'userid'=>$userid));
			
		$create3 = new stdClass();
		$create3->id = $qzattmp->id;
		$create3->timemodified = time();
		$create3->sumgrades = $sumgrades;

		$DB->update_record('quiz_attempts',  $create3);

	//TABLE: quiz_grades		
	$qzgrd = $DB->get_record('quiz_grades',  array('quiz'=>$quiz, 'userid'=>$userid));
	
		$create4 = new stdClass();
		$create4->id = $qzgrd->id;
		$create4->timemodified = time();
		$create4->grade = $sumgrades;

		$DB->update_record('quiz_grades',  $create4);

	//TABLE: grade_grades
	$modules = $DB->get_record('course_modules',  array('id'=>$module));
	$gradeitem = $DB->get_record('grade_items',  array('courseid'=> $modules->course, 'iteminstance' =>$modules->instance ));
	
	$grade_grades = $DB->get_record('grade_grades',  array('itemid'=>$gradeitem->id, 'userid'=>$userid));

	$create5 = new stdClass();
		$create5->id = $grade_grades->id;
		$create5->rawgrade = $sumgrades;
		$create5->usermodified = $USER->id;
		$create5->finalgrade = $sumgrades;
		$create5->timemodified = time(); 
	 	
	 	$DB->update_record('grade_grades',  $create5);

	 	echo 'ok';
		
}
	



























