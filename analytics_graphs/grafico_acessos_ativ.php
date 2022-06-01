<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
require_once("../../config.php");
require("lib.php");
require('javascriptfunctions.php');
global $DB;
require_once($CFG->dirroot.'/lib/moodlelib.php');

$courseid = required_param('id', PARAM_INT);
require_login($courseid);
$context = context_course::instance($courseid);
require_capability('block/analytics_graphs:viewpages', $context);

/* Log */
$event = \block_analytics_graphs\event\block_analytics_graphs_event_view_graph::create(array(
    'objectid' => $courseid,
    'context' => $context,
    'other' => "grades_chart.php",
));
$event->trigger();

/*$sql = "SELECT gi.id, categoryid, fullname, itemname, gradetype, grademax, grademin
            FROM {grade_categories} gc
            LEFT JOIN {grade_items} gi ON gc.courseid = gi.courseid AND gc.id = gi.categoryid
            WHERE gc.courseid = ? AND categoryid IS NOT NULL AND EXISTS (
                SELECT *
                    FROM {grade_grades} gg
                    WHERE gg.itemid = gi.id AND gg.rawgrade IS NOT NULL )
        ORDER BY fullname, itemname";

$result = $DB->get_records_sql($sql, array($courseid));*/

$sql2 = "SELECT ROW_NUMBER() OVER () as id, gi.itemmodule, gi.iteminstance, gi.itemname as exercicio,
ROUND(AVG(gg.rawgrade/(gg.rawgrademax-gg.rawgrademin)*100),2) AS media
FROM mdl_grade_grades gg
JOIN mdl_grade_items gi ON gg.itemid = gi.id
WHERE gg.rawgrade IS NOT NULL AND gi.courseid = ?
GROUP BY exercicio
ORDER BY gi.id ASC";

$result2 = $DB->get_records_sql($sql2, array($courseid));

if(count($result2) > 0){

foreach($result2 as $item){

    $sql2 = "SELECT ROW_NUMBER() OVER () as id, mcs.name
    FROM mdl_".$item->itemmodule." m_modl
    JOIN mdl_modules mm  ON mm.name = '".$item->itemmodule."'
    JOIN mdl_course_modules cm ON cm.instance = m_modl.id AND cm.module = mm.id
    JOIN mdl_course_sections mcs ON mcs.id = cm.section
    WHERE m_modl.id = ?";

    $result3 = $DB->get_records_sql($sql2, array($item->iteminstance));

    $item->topico = $result3[1]->name;
}

foreach($result2 as $item){

    $sql2 = "SELECT ROW_NUMBER() OVER () as id, mcs.name from mdl_logstore_standard_log l
    JOIN mdl_course_modules m ON m.id = l.contextinstanceid 
    JOIN mdl_course_sections mcs ON mcs.id = m.section
    WHERE mcs.name = '".$item->topico."' AND l.courseid = ? AND m.course = ? AND (l.action = 'viewed' OR l.action = 'submission')
    GROUP BY eventname";

    $result3 = $DB->get_records_sql($sql2, array($courseid, $courseid));

    $item->acessos = count($result3);
}
/*
$array = array();
foreach($result2 as $item){
    if(!(count($array[$item->topico]) > 0 )){
        $array[$item->topico] = array();
    }
    array_push($array[$item->topico], $item);
}

print_r($array);

$groupmembers = block_analytics_graphs_get_course_group_members($courseid);
$groupmembersjson = json_encode($groupmembers);

print_r($groupmembers);
*/
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
          ['Seção - Exercicio', 'Nota Média', 'Acessos'],
          <?php foreach($result2 as $item){
          $topico = str_replace("'", "", $item->topico);
          $exercicio = str_replace("'", "", $item->exercicio);
          $exercicio = "'".$topico." - ".$exercicio."'";
          ?>
            [<?=$exercicio?>, <?=$item->media?>, <?=$item->acessos?>],
          <?php } ?>
        ]);

        var options = {
          title : 'Média de Notas com média de acessos por seção/atividade',
          vAxes: {0: {title:"Nota Média", minValue: 0},
            1: {title:"Acessos", minValue: 0}},
          hAxis: {title: 'Exercício'},
          seriesType: 'bars',
          series:{
            0:{targetAxisIndex:0},
            1:{type: "line", targetAxisIndex:1}
          },
        };

        var chart = new google.visualization.ComboChart(document.getElementById('chart_div'));
        chart.draw(data, options);
    };
    </script>
  </head>
  <body>
    <div id="selected" style="width: 20%;margin: auto;margin-bottom: 1%;text-align: center;margin-top: 2%;">
        <b>Atividade:</b>
        <select class="form-select" aria-label="Default select example" onChange="preencheCampo('filtro_grafico_notas', <?=$courseid?>+'@'+this.value,'usuario')">
            <option value="0-919" selected>Todas</option>
            <?php foreach($result2 as $item){ 
                $topico = str_replace("'", "", $item->topico);
                $exercicio = str_replace("'", "", $item->exercicio);
                $exercicio = $topico." - ".$exercicio; 
                echo "<option value=".$item->itemmodule.'-'.$item->iteminstance." >".$exercicio."</option>"; }?>
        </select>
    </div>
    <div id="filtro_grafico_notas">
        <div id="chart_div" style="width: 1050px; height: 450px; margin: auto;"></div>
    </div>
  </body>
</html>
<?php
}else{
    echo "Atividades sem nenhuma nota.";
}
?>
