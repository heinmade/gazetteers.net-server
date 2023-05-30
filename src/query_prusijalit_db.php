<?php
require_once("query_db.php");
function getPrusijalitEntitiesByName(){
  if($_GET["namesearchmode"]=="org" || $_GET["namesearchmode"]=="full") $nameTable = "prusija_lt_names";
  else $nameTable = "prusija_lt_nameparts";
  getDBGazEntitiesByName(
    "prusija_lt",
     $nameTable,
    "processPrusijalitObject",
    true,
    "prusijalit", "idx"
  );
}
function getPrusijalitEntityById(){
  getDBGazEntityById("prusija_lt", "idx", "processPrusijalitObject", "prusijalit");
}
function processPrusijalitObject($resultObj){
  $normedObj = new stdClass();
  $normedObj->id = $resultObj->idx;
  $normedObj->name = $resultObj->name_litauisch;
 $normedObj->names = [];
  if(!empty($resultObj->name_deutsch)){
    $nameobj  = new stdClass();
    $nameobj->name = $resultObj->name_deutsch;
    $nameobj->lang = "de"; 
    array_push($normedObj->names, $nameobj);
  }
  if(!empty($resultObj->name_russisch)){
    $nameobj  = new stdClass();
    $nameobj->name = $resultObj->name_russisch;
    $nameobj->lang = "ru"; 
    array_push($normedObj->names, $nameobj);
  }
  if(!empty($resultObj->name_polnisch)){
    $nameobj  = new stdClass();
    $nameobj->name = $resultObj->name_polnisch;
    $nameobj->lang = "pl"; 
    array_push($normedObj->names, $nameobj);
  }
  $coords = new stdClass();
  $point = $resultObj->koordinaten;
  if( !empty(trim($point)) && strpos($point, ",")!==false ){
    $p = strpos($point, ",");
    $coords->lat = trim(substr($resultObj->koordinaten, 0, $p));
    $coords->long = trim(substr($resultObj->koordinaten, $p+1));
    $normedObj->coordinates = $coords;
  }
  return $normedObj;
}
?>
