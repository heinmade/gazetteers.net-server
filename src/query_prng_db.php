<?php
require_once("query_db.php");
function getPrngEntitiesByName(){
  if($_GET["namesearchmode"]=="org" || $_GET["namesearchmode"]=="full") $nameTable = "prng_names";
  else $nameTable = "prng_nameparts";
   getDBGazEntitiesByName(
    "prng_miejscowosci",
    $nameTable,
    "processPrngObject",
    true,
    "prng", "id_prng"
  );
}
function getPrngEntityById(){
  getDBGazEntityById("prng_miejscowosci", "id_prng", "processPrngObject", "prng");
}
function processPrngObject($resultObj){
  $normedObj = new stdClass();
  $normedObj->id = $resultObj->id_prng;
  $normedObj->name = $resultObj->hauptname;
  $normedObj->names = [];
  if($resultObj->weitere_namen!=null){
    $nameobj  = new stdClass();
    $nameobj->name = $resultObj->weitere_namen;
    if($resultObj->sprachcode_weitere_namen) $nameobj->lang = $resultObj->sprachcode_weitere_namen; 
    array_push($normedObj->names, $nameobj);
  }
  if($resultObj->historischer_name!=null){
    $nameobj  = new stdClass();
    $nameobj->name = $resultObj->historischer_name;
    array_push($normedObj->names, $nameobj);
  }
  if($resultObj->sicherer_name!=null){
    $nameobj  = new stdClass();
    $nameobj->name = $resultObj->sicherer_name;
    array_push($normedObj->names, $nameobj);
  }
  if($resultObj->exonym_fremd!=null){
    $nameobj  = new stdClass();
    $nameobj->name = $resultObj->exonym_fremd;
    array_push($normedObj->names, $nameobj);
  }
  if($resultObj->endonym_fremd!=null){
    $nameobj  = new stdClass();
    $nameobj->name = $resultObj->endonym_fremd;
    array_push($normedObj->names, $nameobj);
  }
  $coords = new stdClass();
  $coords->lat = $resultObj->lat_normalisiert;
  $coords->long = $resultObj->lon_normalisiert;
  $normedObj->coordinates = $coords;
  return $normedObj;
}
?>
