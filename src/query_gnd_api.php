<?php
require_once("process.php");
function getGndEntitiesByName(){
  $name_org = $_GET["name"]; 
  $name = toLowerAndAscii($name_org)['string'];
  if(!empty($_GET["north"]) && !empty($_GET["south"]) && !empty($_GET["west"]) && !empty($_GET["east"])){
    $bb = new stdClass();
    $bb->north = $_GET["north"];
    $bb->south = $_GET["south"];
    $bb->west = $_GET["west"];
    $bb->east = $_GET["east"];
  }
  $name_org = $name;
  $name = urlencode($name_org);
  $queryPrefix = "http://lobid.org/gnd/search?format=json&size=2000";
  $queryFilter = "&filter=";
  $queryFilterType = "type:PlaceOrGeographicName";
  $queryQuery = "&q=";
  $queryQueryPreferredName = "preferredName.ascii:" . $name;
  $queryQueryAllName = $queryQueryPreferredName . "+OR+variantName.ascii:"  . $name;
  $queryMainName = $queryPrefix . $queryFilter . $queryFilterType . $queryQuery . $queryQueryPreferredName;
  $queryAllName = $queryPrefix . $queryFilter . $queryFilterType . $queryQuery . $queryQueryAllName;
  if($_GET["namescope"] === "main")
    $result = file_get_contents($queryMainName);
  else
    $result = file_get_contents($queryAllName);
  $result = json_decode($result, false, 512, JSON_UNESCAPED_UNICODE);
  if(!empty($result)){
    $orgObjs = [];
    $normedObjs = [];
    foreach($result->member as $resultObj){
      $normedObj = processGndObject($resultObj);
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
        $types = $resultObj->type;
        foreach($types as $type){
          if($type==="PlaceOrGeographicName"){
            $isSettlement = true;
          }
          else if($type==="BuildingOrMemorial"){
            $isSettlement = false;
            break;
          }
        }
        if(!$isSettlement) continue;
      }
      $link = getURL("gnd", $normedObj->id);
      if($link!==""){
        $resultObj->link = $link;
        $normedObj->link = $link;
      }
      if(isset($_GET["matchings"])){
        $matchings = getPossibleMatchings("gnd", $normedObj->id, $resultObj);
        $resultObj->matchings = $matchings;
        $normedObj->matchings = $matchings;
      }
      if(empty($bb)){
        array_push($orgObjs, $resultObj);
        array_push($normedObjs, $normedObj);
      }
      else{
        if(isInBoundingBox($bb, $normedObj->coordinates)){
          array_push($orgObjs, $resultObj);
          array_push($normedObjs, $normedObj);
        }
      }
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
function getGndEntityById(){
  $id = $_GET["id"]; 
  $result = file_get_contents("http://lobid.org/gnd/" . $id . ".json");
  $result = json_decode($result, false, 512, JSON_UNESCAPED_UNICODE);
  if($_GET["resultschema"]=="gaz"){
    $resultObj = processGndObject($result);
  }
  else{
    $resultObj = $result;
  }
  $link = getURL("gnd", $id);
  if($link!==""){
    $resultObj->link = $link;
  }
  if(isset($_GET["matchings"])) $resultObj->matchings = getPossibleMatchings("gnd", $id, $result);
  echo json_encode($resultObj, JSON_UNESCAPED_UNICODE);
}
function processGndObject($resultObj){
  $normedObj = new stdClass();
  $normedObj->id = $resultObj->gndIdentifier;
  $normedObj->name = $resultObj->preferredName;
  if(!empty($resultObj->variantName) && is_array($resultObj->variantName)){
    $normedObj->names = [];
    foreach($resultObj->variantName as $resnameobj){ 
      $nameobj  = new stdClass();
      $nameobj->name = $resnameobj;
      array_push($normedObj->names, $nameobj);
    }
  }
  $coordsdef = "";
  if(!empty($resultObj->hasGeometry)){
    if(is_array($resultObj->hasGeometry)){
      if(!empty($resultObj->hasGeometry[0]->asWKT)){
        if(is_array($resultObj->hasGeometry)){
          $coordsdef = trim($resultObj->hasGeometry[0]->asWKT[0]);
          if(strpos($coordsdef, "Point") === 0){
            $p1 = strpos($coordsdef, "(");
            if($p1) $p2 = strpos($coordsdef, ")");
            if($p1 && $p2){
              $coordsdef = trim(substr($coordsdef, $p1+1, ($p2-$p1-1)));
              $point = explode(" ", $coordsdef);
              if(count($point)==2){
                $coords = new stdClass();
                $lat = trim($point[1]);
                if(substr($lat, 0, 1) == "+") $lat = substr($lat, 1);
                if(substr($lat, 0, 1) == "0") $lat = substr($lat, 1);
                if(substr($lat, 0, 2) == "-0") $lat = "-" . substr($lat, 2);
                $coords->lat = $lat;     
                $long = trim($point[0]);
                if(substr($long, 0, 1) == "+") $long = substr($long, 1);
                if(substr($long, 0, 1) == "0") $long = substr($long, 1);
                if(substr($long, 0, 2) == "-0") $long = "-" . substr($long, 2);
                $coords->long = $long;
                $normedObj->coordinates = $coords; 
              }
            }   
          }
        }
      }
    }
  }
  return $normedObj;
}
?>
