<?php
require_once("query_db.php");
function getCarpathorusynEntitiesByName(){
  if($_GET["namesearchmode"]=="org" || $_GET["namesearchmode"]=="full") $nameTable = "carpathorusyn_names";
  else $nameTable = "carpathorusyn_nameparts";
  getDBGazEntitiesByName(
    "carpathorusyn",
     $nameTable,
    "processCarpathorusynObject",
    false,
    "carpathorusyn", "idx"
  );
}
function getCarpathorusynEntityById(){
  getDBGazEntityById("carpathorusyn", "idx", "processCarpathorusynObject", "carpathorusyn");
}
function processCarpathorusynObject($resultObj){
  $normedObj = new stdClass();
  $normedObj->id = $resultObj->refnum;
  $normedObj->name = $resultObj->name_russinisch;
  $normedObj->names = [];
  if(!empty($resultObj->name_ukrainisch)){
    $nameobj  = new stdClass();
    $nameobj->name = $resultObj->name_ukrainisch;
    $nameobj->lang = "uk"; 
    array_push($normedObj->names, $nameobj);
  }
  if(!empty($resultObj->name_polnisch)){
    $nameobj  = new stdClass();
    $nameobj->name = $resultObj->name_polnisch;
    $nameobj->lang = "pl"; 
    array_push($normedObj->names, $nameobj);
  }
  return $normedObj;
}
?>

