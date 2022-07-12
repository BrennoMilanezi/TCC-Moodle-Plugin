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
WHERE mul.courseid = ? AND usr.firstname NOT LIKE '%Admin%'
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
    $total_atividade = count($result_3);
    if($total_atividade > 0){
        foreach($result_3 as $item_3){

            $sql_4 = "SELECT usr.id as id_usuario
            FROM mdl_grade_grades gg
            JOIN mdl_user usr ON usr.id = gg.userid
            JOIN mdl_grade_items gi ON gg.itemid = gi.id
            JOIN mdl_".$item_3->itemmodule." m_modl
            JOIN mdl_modules mm  ON mm.name = '".$item_3->itemmodule."'
            JOIN mdl_course_modules cm ON cm.instance = m_modl.id AND cm.module = mm.id
            JOIN mdl_course_sections mcs ON mcs.id = cm.section
            WHERE m_modl.id = '".$item_3->iteminstance."' AND usr.firstname NOT LIKE '%Admin%'
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
    <script src="ajax/preenche.js"></script>
  </head>
  <body>
    <div style="margin-top: 1%;" class="container">
        <div id="selected" style="width: 20%;margin: auto;margin-bottom: 2%;text-align: center;">
            <b>Tempo:</b>
            <select class="form-select" aria-label="Default select example" onChange="preencheCampo('filtro_grafico_acesso_ativ', <?=$id_curso?>+'@'+this.value,'usuario')">
                <option value="0" selected>Selecione...</option>
                <option value="1" >Últimas duas semanas</option>
                <option value="2" >Último mês</option>
                <option value="3" >Últimos dois meses</option>
            </select>
        </div>
        <div id="filtro_grafico_acesso_ativ" class="row">
            <div class="col-3"></div>
            <div class="col-6">
                <b>Total de atividades: <?=$total_atividade?></b><br>
                <font><b style="color: #ff000094;">Vermelho</b> - alunos com atividades entregues abaixo de 40%</font><br>
                <font><b style="color: #ffff00c2;">Amarelo</b> - alunos com atividades entregues entre 40% a 70%</font><br>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                        <th scope="col">#</th>
                        <th scope="col">Aluno</th>
                        <th scope="col">Último acesso</th>
                        <th scope="col">Total dias do últ. acesso</th>
                        <th scope="col">Atv. entregues</th>
                        <th scope="col">Atv. não entregues</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        for($i = 0; $i < count($array_users); $i++){
                            $cont = $i + 1;
                            $user = $array_users[$i];
                            $usuario = $user["usuario"];
                            $diferenca = strtotime(date('Y-m-d')) - strtotime($user["ultimo_acesso"]);
                            $dias = floor($diferenca / (60 * 60 * 24));
                            $ultimo_acesso = converte_data($user["ultimo_acesso"]);
                            $total_atv_feitas = $user["total_atv_feitas"];
                            $total_atv_n_feitas = $user["total_atv_n_feitas"];
                            $total_atv = $total_atv_feitas + $total_atv_n_feitas;
                            $percentual_atv_feitas = 100*$total_atv_feitas/$total_atv;
                            $back_ground = "";
                            if($percentual_atv_feitas < 40){
                                $back_ground = "style=\"background: #ff00003d;\"";
                            }elseif($percentual_atv_feitas >= 40 && $percentual_atv_feitas < 70){
                                $back_ground = "style=\"background: #ffff006b;\"";
                            }
                
                            $teste = "<tr ".$back_ground."><td>".$cont."</td><td>".$usuario."</td><td>".$ultimo_acesso."</td><td>".$dias."</td><td>".$total_atv_feitas."</td><td>".$total_atv_n_feitas."</td></tr>";

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