<?php
require_once("../../../config.php");
require("../lib.php");
require('../javascriptfunctions.php');
global $DB;
require_once($CFG->dirroot.'/lib/moodlelib.php');

$campo = $_GET['campo'];
$valor = $_GET['valor'];
$tipo = $_GET['tipo'];

if($campo == "filtro_grafico_notas" && $tipo == "usuario"){
    $array_valor = explode("@", $valor);
    $id_curso = $array_valor[0];
    $arrayitem = explode("-", $array_valor[1]);
    $itemmodule = $arrayitem[0];
    $iteminstance = $arrayitem[1];

    $host = $_SERVER['HTTP_HOST'];

    echo "<center><iframe style=\"width: 75%;height: 75%;\" src=\"http://".$host."/blocks/analytics_graphs/grafico_notas_filtro_usuario.php?c=".$id_curso."&m=".$itemmodule."&i=".$iteminstance."\"></iframe></center>";
}
?>