<?php
require_once("process.php");
function getGeonamesEntitiesByName(){
  $name_org = $_GET["name"]; 
  $pos = strpos($name_org, "*");
  if($pos!==false){
    $name = substr($name_org, 0, $pos);
    $searchparam = "name_startsWith";
  }
  else{
    $name = $name_org;
    $searchparam = "name";
  }
  $name = toLowerAndAscii($name)['string'];
  $bbfilter = "";
  if(!empty($_GET["north"]) && !empty($_GET["south"]) && !empty($_GET["west"]) && !empty($_GET["east"])){
    $bbfilter .= "&north=" . $_GET["north"] . "&south=" . $_GET["south"] . "&west="  . $_GET["west"] . "&east=" . $_GET["east"];
  }
  $name = urlencode($name);
  $result = file_get_contents("http://api.geonames.org/searchJSON?username=" . $geonamesuser . "&style=full&maxRows=1000&" . $searchparam . "=" . $name . $bbfilter);
  $result = json_decode($result, false, 512, JSON_UNESCAPED_UNICODE);
  if(!empty($result)){
    $orgObjs = [];
    $normedObjs = [];
    foreach($result->geonames as $resultObj){
      $normedObj = processGeonamesObject($resultObj);
      $names = [];
      array_push($names, $normedObj->name);
      foreach($normedObj->names as $nameobj){ 
        array_push($names, $nameobj->name);
      }
      if($_GET["namesearchmode"]!=="org"){
        $exactMatch = $_GET["namesearchmode"]=="full";
        $result = searchMatchesName($names, $name_org, $exactMatch);
        if(!$result){
          continue;
        }
      }
      if($_GET["ftype"]==="settlement"){
        $isSettlement = false;
        $typeClass = $resultObj->fcl;
        if(isset($typeClass) && $typeClass==="P"){
          $isSettlement = true;
        }
        if(!$isSettlement) continue;
      }
      $link = getURL("geonames", $normedObj->id);
      if($link!==""){
        $resultObj->link = $link;
        $normedObj->link = $link;
      }
      if(isset($_GET["matchings"])){
        $matchings = getPossibleMatchings("geonames", $normedObj->id);
        $resultObj->matchings = $matchings;
        $normedObj->matchings = $matchings;
      }
      array_push($orgObjs, $resultObj);
      array_push($normedObjs, $normedObj);
    }
    if($_GET["resultschema"]=="gaz"){
      $resultObjs = $normedObjs;
    }
    else{
      $resultObjs = $orgObjs;
    }
    echo json_encode($resultObjs, JSON_UNESCAPED_UNICODE);
  }
}
function getGeonamesEntityById(){
  $id = $_GET["id"]; 
  $result = file_get_contents("http://api.geonames.org/getJSON?username=heinmagazapp&style=full&geonameId=" . $id);
  $result = json_decode($result, false, 512, JSON_UNESCAPED_UNICODE);
  if($_GET["resultschema"]=="gaz"){
    $resultObj = processGeonamesObject($result);
  }
  else{
    $resultObj = $result;
  }
  $link = getURL("geonames", $id);
  if($link!==""){
    $resultObj->link = $link;
  }
  if(isset($_GET["matchings"])) $resultObj->matchings = getPossibleMatchings("geonames", $id);
  echo json_encode($resultObj, JSON_UNESCAPED_UNICODE);
}
function processGeonamesObject($resultObj){
  $normedObj = new stdClass();
  $normedObj->id = $resultObj->geonameId;
  $normedObj->name = $resultObj->name; 
  if(!empty($resultObj->alternateNames) && is_array($resultObj->alternateNames)){
    $normedObj->names = [];
    foreach($resultObj->alternateNames as $resnameobj){ 
      $nameobj  = new stdClass();
      $nameobj->name = $resnameobj->name;
      if(!empty($resnameobj->lang)) $nameobj->lang = $resnameobj->lang; 
      array_push($normedObj->names, $nameobj);
    }
  }
  $coords = new stdClass();
  $coords->lat = $resultObj->lat;
  $coords->long = $resultObj->lng;
  $normedObj->coordinates = $coords;
  return $normedObj;
}
?>
