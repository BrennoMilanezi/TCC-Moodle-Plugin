<?php
require_once("../../config.php");
require("lib.php");
require('javascriptfunctions.php');
global $DB;
require_once($CFG->dirroot.'/lib/moodlelib.php');

$id_curso = $_GET['id'];

$sql = "SELECT ROW_NUMBER() OVER () as id, usr.id as id_usuario, CONCAT(usr.firstname, ' ', usr.lastname) as usuario
from mdl_user_lastaccess mul 
JOIN mdl_user usr ON usr.id = mul.userid 
WHERE mul.courseid = ?
ORDER BY usuario";

$array_users = [];
$result = $DB->get_records_sql($sql, array($id_curso));

foreach($result as $item){

    $sql_2 = "SELECT 1 as id, FROM_UNIXTIME(timecreated) as ultimo_acesso from mdl_logstore_standard_log WHERE userid = ".$item->id_usuario." AND courseid = ".$id_curso." ORDER BY timecreated DESC LIMIT 1";
    $result_2 = $DB->get_records_sql($sql_2);

    $sql_3 = "SELECT ROW_NUMBER() OVER () as id, gi.itemmodule, gi.iteminstance
    FROM mdl_grade_grades gg
    JOIN mdl_grade_items gi ON gg.itemid = gi.id
    WHERE gg.rawgrade IS NOT NULL AND gi.courseid = ".$id_curso."
    GROUP BY gi.itemname
    ORDER BY gi.id ASC";

    $result_3 = $DB->get_records_sql($sql_3);
    $total_atv_feitas = 0;
    $total_atv_n_feitas = 0;

    foreach($result_3 as $item_3){

        $sql_4 = "SELECT usr.id as id_usuario
        FROM mdl_grade_grades gg
        JOIN mdl_user usr ON usr.id = gg.userid
        JOIN mdl_grade_items gi ON gg.itemid = gi.id
        JOIN mdl_".$item_3->itemmodule." m_modl
        JOIN mdl_modules mm  ON mm.name = '".$item_3->itemmodule."'
        JOIN mdl_course_modules cm ON cm.instance = m_modl.id AND cm.module = mm.id
        JOIN mdl_course_sections mcs ON mcs.id = cm.section
        WHERE m_modl.id = '".$item_3->iteminstance."'
        AND gi.itemmodule = '".$item_3->itemmodule."' AND gi.iteminstance = '".$item_3->iteminstance."'
        AND gi.courseid = '".$id_curso."' AND gg.userid = '".$item->id_usuario."'";

        $result_4 = $DB->get_records_sql($sql_4);

        if(count($result_4) > 0){
            $total_atv_feitas++;
        }else{
            $total_atv_n_feitas++;
        }

    }

    $array_users[] = array("id_usuario" => $item->id_usuario, "usuario" => $item->usuario, "ultimo_acesso" => $result_2[1]->ultimo_acesso, 
    "total_atv_feitas" => $total_atv_feitas, "total_atv_n_feitas" => $total_atv_n_feitas);
}

function converte_data($data) {

    if (preg_match('/([0-9]+)-([0-9]+)-([0-9]+)/', $data))
        return preg_replace('/([0-9]+)-([0-9]+)-([0-9]+)/', '$3/$2/$1', $data);
    else
        return preg_replace('/([0-9]{2})(\/|\s|\.)?([0-9]{2})(\/|\s|\.)?([0-9]{4})/', '$5-$3-$1', $data);
}
?>
<html>
  <head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  </head>
  <body>
    <div style="margin-top: 3%;" class="container">
        <div class="row">
            <div class="col-3"></div>
            <div class="col-6">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                        <th scope="col">#</th>
                        <th scope="col">Aluno</th>
                        <th scope="col">Último acesso</th>
                        <th scope="col">Atv. feitas</th>
                        <th scope="col">Atv. não feitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        for($i = 0; $i < count($array_users); $i++){
                            $cont = $i + 1;
                            $user = $array_users[$i];
                            $usuario = $user["usuario"];
                            $ultimo_acesso = converte_data($user["ultimo_acesso"]);
                            $total_atv_feitas = $user["total_atv_feitas"];
                            $total_atv_n_feitas = $user["total_atv_n_feitas"];
                
                            $teste = "<tr><td>".$cont."</td><td>".$usuario."</td><td>".$ultimo_acesso."</td><td>".$total_atv_feitas."</td><td>".$total_atv_n_feitas."</td></tr>";

                            echo $teste;
                        }
                        ?>
                    </tbody>
                </table>    
            </div>
            <div class="col-3"></div>
        </div>
    </div>
  </body>
</html>