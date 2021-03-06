<?php
global $DB, $CFG, $PAGE, $OUTPUT, $USER;
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once(dirname(__FILE__) .'/../../mod/quiz/locallib.php');
//global $USER;

require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($SITE->fullname);
$main_url = new moodle_url('/local/essay_amag/reviewquestionB.php');
$PAGE->set_url($main_url);
$title = 'Corregir ensayos';
$PAGE->set_title($title);
$PAGE->set_heading($title);
print $OUTPUT->header();



//intentos de cada pregunta, tipo de pregunta y respuesta marcada, por alumno
$sql = "SELECT qna.id as qna, c.fullname as course, q.name as quiz, qa.id as qa, qa.userid, u.firstname, u.lastname, qna.questionid, qn.qtype as qntype, qna.questionsummary as question, qna.maxmark, qna.responsesummary, q.id as quizID
FROM {course_modules} cm 
INNER JOIN {course} c ON c.id = cm.course
INNER JOIN {quiz} q ON c.id = q.course 
INNER JOIN {quiz_attempts} qa ON q.id = qa.quiz 
INNER JOIN {user} u ON qa.userid = u.id
INNER JOIN {question_attempts} qna ON qa.uniqueid = qna.questionusageid
INNER JOIN {question} qn ON qna.questionid = qn.id 
WHERE cm.id IN (?)
AND cm.instance = q.id
AND qa.userid IN (?)
";
$users = $DB->get_records_sql($sql, array($_GET['id'],$_GET['userid']));

//combobox por usuario

$sqluser = "SELECT qna.id as qna, qa.userid, u.firstname, u.lastname 
                FROM {course_modules} cm 
                INNER JOIN {course} c ON c.id = cm.course                
                INNER JOIN {quiz} q ON c.id = q.course 
                INNER JOIN {quiz_attempts} qa ON q.id = qa.quiz 
                INNER JOIN {user} u ON qa.userid = u.id
                INNER JOIN {question_attempts} qna ON qa.uniqueid = qna.questionusageid
                INNER JOIN {question} qn ON qna.questionid = qn.id 
                WHERE cm.id IN (?)
                AND cm.instance = q.id 
                AND  qn.qtype = 'essay'
                ORDER BY u.lastname
            ";

        $userlist = $DB->get_records_sql($sqluser, array($_GET['id']));



//selector de alumno y paso de pagina
$uid ='';
$out1 = '';
$uidarr = array();

foreach ($userlist as $key => $value) {            
    if ($uid == $value->userid ) {
        continue;
    }
    array_push($uidarr, $value->userid);
    $uid = $value->userid;
}


$nextpst = $_GET['position']+1;
$prevpost = $_GET['position']-1;
$localpst =0;


$out1 .= '<div class="felement fselect">
            <select onchange="window.location=this.options[this.selectedIndex].value" onmousedown="if(this.options.length>8){this.size=10;}" onblur="this.size=0;">
                <option value="">Selecione alumno</option>';

                foreach ($userlist as $key => $value) {            
                    if ($uid == $value->userid ) {
                        continue;
                    }
                    $out1 .=  '<option value="http://moodle.dev/local/essay_amag/reviewquestionB.php?id='.$_GET['id'] .'&userid='.$value->userid.'&position='.$localpst++.'">'.$value->lastname .' '. $value->firstname .'</option>';
                    
                    $uid = $value->userid;
                }

$out1 .='  </select>
         </div>   
';

if ($prevpost >= 0) {
    $out1 .= '<div>
                <a href="http://moodle.dev/local/essay_amag/reviewquestionB.php?id='.$_GET['id'] .'&userid='.$uidarr[$prevpost].'&position='.$prevpost.'">Anterior  </a>';
}else{
    $out1 .= '<div>';
}

if (count($uidarr)-1 >= $nextpst) {
    $out1 .= '<a href="http://moodle.dev/local/essay_amag/reviewquestionB.php?id='.$_GET['id'] .'&userid='.$uidarr[$nextpst].'&position='.$nextpst.'">  Siguiente</a>
        </div>';
}else{
    $out1 .= '</div>';
}

echo $out1;

//filtro por tipo de  question
$questiontyp = array();

foreach ($users as $key => $value) {
    $temp=0;
    $sql2 = "SELECT qnas.id, qnas.state, qnas.fraction, qnas.userid 
    FROM {question_attempt_steps} qnas
    INNER JOIN {question_attempts} qna ON qnas.questionattemptid = qna.id
    WHERE qnas.questionattemptid IN (?)
    and (qnas.state = 'mangrpartial' or qnas.state = 'mangrright') ";//cambiar segun etiqueta en DB de cada tipo de pregunta

    $notas = $DB->get_records_sql($sql2, array($value->qna));

    foreach ($notas as $keys => $values) {
        if (empty($values)) {
            $temp='';
        }else{
        $temp = $values->fraction;
        }
    }

    $value->nota = number_format($temp * $value->maxmark, 2) ;
    if ($value->nota <= 0) {
        $value->nota = '';
    }
    if ($value->qntype == 'essay') {
        array_push($questiontyp, $value);
    }else{
        continue;
    } 
                   
                  
}

//echo "<script src='//cdn.tinymce.com/4/tinymce.min.js'></script>";
//echo "<script>tinymce.init({ selector:'textarea' });</script>";
echo "<script src='https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js'></script>";




foreach ($questiontyp as $key => $value) {

    $out = ''; 

    $out .= '
            <table class="generaltable generalbox quizreviewsummary">
               <tbody> 
                <tr>
                   <th class="cell" scope="row">
                   ALUMNO
                   </th>
                   <td class="cell">';
                    $out .=$value->firstname . ' ' . $value->lastname;
    $out .= '      </td>
                </tr>
                <tr>
                   <th class="cell" scope="row">
                   CUESTIONARIO
                   </th>
                   <td class="cell">';
                   $out .= $value->quiz;
    $out .= '      </td>
                </tr>
                <tr>
                   <th class="cell" scope="row">
                   PREGUNTA
                   </th>
                   <td class="cell">';
                   $out .= $value->question;
     $out .= '     </td>
                </tr>
              </tbody>  
            </table>';

    $out .= '<div class="que essay manualgraded complete">
                <div class="formulation clearfix">
                   <h5>
                        Respuesta';
               
        $out .= '  </h5>
                    <div class="qtype_essay_editor qtype_essay_response readonly">
                       <p>';
                       $out .= $value->responsesummary;
        $out .= '      </p>
                    </div>                              
                </div> ';


    $out .= '   <div role="main">
                    <form id="formulario-' .$key .'" class="mform"  method ="post" >
                        <div>
                            <div class="comment clearfix">
                            <div>
                               <h4>     
                                  Comentario 
                               </h4>     
                            </div>
                            
                                <textarea name="comment" row="20" >  
                                </textarea >
                                <h4>Puntuación </h4>
                            
                            <div class="fitem required fitem_ftext  ">
                                <input type="hidden" name="qna" value="'. $value->qna .'"> ' ;
                                //$out .= $value->qna;    
                                $out .= '
                                <input id="grade" type="number" min="0" max="'.$value->maxmark.'" name="grade"  value="'.$value->nota.'">';
                                //$out .= $value->nota;    
                                $out .='
                                 <input type ="hidden" name="valores" id="valores" value="'. $value->userid.'&'.$value->quizid.'&'.$_GET['id'] .'&'.$key.'">
                                 

                                <a>
                                sobre  '. number_format($value->maxmark, 2) .' ';
        $out .= '               </a>                              
                                <lable  id="grade_error'.$key.'">
                                </lable>
                            </div> 
                            </div>   
                        </div>
                        <div role="main" class="">
                           <button id="btn_enviar-' .$key .'" type="button" class="btn"> GUARDAR  </button>
                        </div>
                        
                    </form>
                </div>
            </div>
           ';


echo $out;

}


$PAGE->requires->js_call_amd('local_essay_amag/module', 'init');
print $OUTPUT->footer();



//La calificación se encuentra fuera del rango válido.
/*echo "
    <script>
      $(function update() {

        $('.mform .btn').click( function (e) {

          e.preventDefault();
          var form = $(this).parent().parent();
          $.ajax({
            type: 'post',
            url: 'updateGradeQuiz.php',
            data: $(form).serialize(),
            success: function(data) {
             
              if (data = 'grade_error') {
                $('#grade_error').html(".'<span class="error" '.'id="grade_error">'.'La calificación se encuentra fuera del rango válido.</span>'. ");
                $('#grade').val('');
              }else{

              }
            }
          });

        });

      });
    </script>
        ";*/

