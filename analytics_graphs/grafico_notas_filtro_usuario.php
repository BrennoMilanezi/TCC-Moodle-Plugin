<?php
require_once("../../config.php");
require("lib.php");
require('javascriptfunctions.php');
global $DB;
require_once($CFG->dirroot.'/lib/moodlelib.php');


$id_curso = $_GET['c'];
$itemmodule = $_GET['m'];
$iteminstance = $_GET['i'];

$nome_mapa = "'Notas dos alunos com quantidade de acessos da seção/atividade'";

if($iteminstance == 919){

  $sql3 = "SELECT ROW_NUMBER() OVER () as id, gi.itemmodule, gi.iteminstance, gi.itemname as exercicio,
  ROUND(AVG(gg.rawgrade/(gg.rawgrademax-gg.rawgrademin)*100),2) AS media
  FROM mdl_grade_grades gg
  JOIN mdl_grade_items gi ON gg.itemid = gi.id
  WHERE gg.rawgrade IS NOT NULL AND gi.courseid = ?
  GROUP BY exercicio
  ORDER BY gi.id ASC";

  $result3 = $DB->get_records_sql($sql3, array($id_curso));

  foreach($result3 as $item){

    $sql3 = "SELECT ROW_NUMBER() OVER () as id, mcs.name
    FROM mdl_".$item->itemmodule." m_modl
    JOIN mdl_modules mm  ON mm.name = '".$item->itemmodule."'
    JOIN mdl_course_modules cm ON cm.instance = m_modl.id AND cm.module = mm.id
    JOIN mdl_course_sections mcs ON mcs.id = cm.section
    WHERE m_modl.id = ?";

    $result4 = $DB->get_records_sql($sql3, array($item->iteminstance));

    $item->topico = $result4[1]->name;
  }

  foreach($result3 as $item){

    $sql3 = "SELECT ROW_NUMBER() OVER () as id, mcs.name from mdl_logstore_standard_log l
    JOIN mdl_course_modules m ON m.id = l.contextinstanceid 
    JOIN mdl_course_sections mcs ON mcs.id = m.section
    WHERE mcs.name = '".$item->topico."' AND l.courseid = ? AND m.course = ? AND (l.action = 'viewed' OR l.action = 'submission')
    GROUP BY eventname";

    $result4 = $DB->get_records_sql($sql3, array($id_curso, $id_curso));

    $item->acessos = count($result4);
  }
?>
<html>
  <head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="ajax/preenche.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
       google.charts.load('current', {'packages':['corechart']});
       google.charts.setOnLoadCallback(drawVisualization);

      function drawVisualization() {
        var data = new google.visualization.arrayToDataTable([
          ['Seção - Exercicio', 'Nota média da turma', 'Quantidade de acessos da turma'],
          <?php foreach($result3 as $item){
          $topico = str_replace("'", "", $item->topico);
          $exercicio = str_replace("'", "", $item->exercicio);
          $exercicio = "'".$topico." - ".$exercicio."'";
          ?>
            [<?=$exercicio?>, <?=$item->media?>, <?=$item->acessos?>],
          <?php } ?>
        ]);

        var options = {
          title : 'Média de notas com quantidade de acessos da turma por seção/atividade',
          vAxes: {0: {title:"Nota média", minValue: 0},
            1: {title:"Quantidade de acessos da turma", minValue: 0}},
          hAxis: {title: 'Atividade'},
          seriesType: 'bars',
          series:{
            0:{targetAxisIndex:0},
            1:{type: "line", targetAxisIndex:1}
          },
        };

        var chart = new google.visualization.ComboChart(document.getElementById('chart_div_aluno'));
        chart.draw(data, options);
    };
    </script>
  </head>
  <body>
    <div id="chart_div_aluno" style="width: 1050px; height: 450px; margin: auto;"></div>
  </body>
</html>
<?php
}else{
  $sql3 = "SELECT ROW_NUMBER() OVER () as id, usr.id as id_usuario, CONCAT(usr.firstname, ' ', usr.lastname) as usuario,
  IF(gg.rawgrade IS NOT NULL, ROUND((gg.rawgrade/(gg.rawgrademax-gg.rawgrademin))*100,2), 0) AS nota, mcs.name as topico
  FROM mdl_grade_grades gg
  JOIN mdl_user usr ON usr.id = gg.userid
  JOIN mdl_grade_items gi ON gg.itemid = gi.id
  JOIN mdl_".$itemmodule." m_modl
  JOIN mdl_modules mm  ON mm.name = '".$itemmodule."'
  JOIN mdl_course_modules cm ON cm.instance = m_modl.id AND cm.module = mm.id
  JOIN mdl_course_sections mcs ON mcs.id = cm.section
  WHERE m_modl.id = '".$iteminstance."' AND usr.firstname NOT LIKE '%Admin%'
  AND gi.itemmodule = '".$itemmodule."' AND gi.iteminstance = '".$iteminstance."'
  AND gi.courseid = '".$id_curso."'";

  $result3 = $DB->get_records_sql($sql3);

  foreach($result3 as $item){

      $sql3 = "select ROW_NUMBER() OVER () as id, COUNT(l.id) as acessos from mdl_logstore_standard_log l
      JOIN mdl_modules mm  ON mm.name = '".$itemmodule."'
      JOIN mdl_course_modules m ON m.id = l.contextinstanceid 
      JOIN mdl_course_sections mcs ON mcs.id = m.section
      WHERE mcs.name = '".$item->topico."' AND l.userid = ? AND l.courseid = ? AND m.course = ? AND (l.action = 'viewed' OR l.action = 'submission')
      GROUP BY eventname";

      $result4 = $DB->get_records_sql($sql3, array($item->id_usuario, $id_curso, $id_curso));
      if($result4[1]->acessos){      
        $item->acessos = $result4[1]->acessos;
      }else{
        $item->acessos = 0.00;
      }
  }
  sort($result3);
?>
<html>
  <head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="ajax/preenche.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
       google.charts.load('current', {'packages':['corechart']});
       google.charts.setOnLoadCallback(drawVisualizationAluno);
      function drawVisualizationAluno() {
        var data_aluno = new google.visualization.arrayToDataTable([
          ['Aluno', 'Nota', 'Quantidade de acessos do aluno'],
          <?php foreach($result3 as $item){
            $nome_usuario = "'".$item->usuario."'"; 
          ?>
            [<?=$nome_usuario?>, <?=$item->nota?>, <?=$item->acessos?>],
          <?php } ?>
        ]);

        var options_aluno = {
          title : <?=$nome_mapa?>,
          vAxes: {0: {title:"Nota", minValue: 0},
            1: {title:"Quantidade de acessos do aluno", minValue: 0}},
          hAxis: {title: 'Aluno'},
          seriesType: 'bars',
          series:{
            0:{targetAxisIndex:0},
            1:{type: "line", targetAxisIndex:1}
          },
        };

        var chart_aluno = new google.visualization.ComboChart(document.getElementById('chart_div_aluno'));
        chart_aluno.draw(data_aluno, options_aluno);
    };
    </script>
  </head>
  <body>
      <div id="chart_div_aluno" style="width: 1050px; height: 450px; margin: auto;"></div>
  </body>
</html>
<?php
}
?>