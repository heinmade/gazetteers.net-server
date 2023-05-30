<?php
require_once("query_db.php");
function getPoland16thCEntitiesByName(){
  if($_GET["namesearchmode"]=="org" || $_GET["namesearchmode"]=="full") $nameTable = "poland_16th_c_names";
  else $nameTable = "poland_16th_c_nameparts";
   getDBGazEntitiesByName(
    "poland_16th_c",
    $nameTable,
    "processPoland16thCObject",
    true,
    "historicalatlasofpoland_16th_c", "objectid"
  );
}
function getPoland16thCEntityById(){
  getDBGazEntityById("poland_16th_c", "objectid", "processPoland16thCObject", "poland16thc");
}
function processPoland16thCObject($resultObj){
  $normedObj = new stdClass();
  $normedObj->id = $resultObj->objectid; 
  $normedObj->name = $resultObj->nazwa_16w; 
  $normedObj->names = [];
  if($resultObj->nazwa_wspolczesna!=null && trim($resultObj->nazwa_wspolczesna)!==""){ 
    $nameobj  = new stdClass();
    $nameobj->name = $resultObj->nazwa_wspolczesna; 
    $nameobj->type = "Jetziger Name"; 
    array_push($normedObj->names, $nameobj);
  }
  $coords = new stdClass();
  $coords->lat = $resultObj->lat;
  $coords->long = $resultObj->lon;
  $normedObj->coordinates = $coords;
  return $normedObj;
}
?>
