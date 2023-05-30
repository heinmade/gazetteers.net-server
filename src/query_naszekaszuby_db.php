<?php
require_once("query_db.php");
function getNaszekaszubyEntitiesByName(){
  if($_GET["namesearchmode"]=="org" || $_GET["namesearchmode"]=="full") $nameTable = "naszekaszuby_names";
  else $nameTable = "naszekaszuby_nameparts";
  getDBGazEntitiesByName(
    "naszekaszuby",
     $nameTable,
    "processNaszekaszubyObject",
    false,
    "naszekaszuby", "idx"
  );
}
function getNaszekaszubyEntityById(){
  getDBGazEntityById("naszekaszuby", "idx", "processNaszekaszubyObject", "naszekaszuby");
}
function processNaszekaszubyObject($resultObj){
  $normedObj = new stdClass();
  $normedObj->id = $resultObj->idx;
  $normedObj->name = $resultObj->name_kaschubisch;
  $normedObj->names = [];
  if(!empty($resultObj->name_polnisch)){
    $nameobj  = new stdClass();
    $nameobj->name = $resultObj->name_polnisch;
    $nameobj->lang = "pl"; 
    array_push($normedObj->names, $nameobj);
  }
  return $normedObj;
}
?>
