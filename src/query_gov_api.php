<?php
require_once("process.php");
function getGovEntitiesByName(){
  global $govCountryIds;
  if($_GET["ftype"]==="settlement"){
    $s = file_get_contents("govtypes.js");
    $b = strlen("var govtypes = ");
    $l = strlen($s) - 3 - $b;
    $s2 = substr($s, $b, $l);
    $govtypes = json_decode($s2, true);
  }
  if(!empty($_GET["north"]) && !empty($_GET["south"]) && !empty($_GET["west"]) && !empty($_GET["east"])){
    $bb = new stdClass();
    $bb->north = $_GET["north"];
    $bb->south = $_GET["south"];
    $bb->west = $_GET["west"];
    $bb->east = $_GET["east"];
  }
  $name_org = $_GET["name"]; 
  $pos = strpos($name_org, "*");
  if($pos!==false) $name = substr($name_org, 0, $pos);
  else $name = $name_org;
  $name = toLowerAndAscii($name)['string'];
  $soapclient = new SoapClient("http://gov.genealogy.net/services/ComplexService?wsdl");
  if(!empty($_GET["country"])){
    $country = strtolower($_GET["country"]);
    $countryid = $govCountryIds[$country];
    if(isset($countryid)){
      $result = $soapclient->searchDescendantsByName($countryid, $name); 
    }  
  }
  else{ 
    $result = $soapclient->searchByName($name);
  }
  if(!empty($result)){
    $orgObjs = [];
    $normedObjs = [];
    if(!is_array($result->object)){
      $resultArray[] = $result->object;  
    }
    else{
      $resultArray = $result->object;
    }
    foreach($resultArray as $resultObj){
      $normedObj = processGovObject($resultObj);
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
        if(!is_array($resultObj->type)){
          $typeArray = [];
          $typeArray[] = $resultObj->type;  
        }
        else{
          $typeArray = $resultObj->type;
        }
        $isSettlement = false;
        foreach($typeArray as $typeObject){
          $govtype = $govtypes[$typeObject->value];
          if(isset($govtype)){
            if(
              ( $govtype[1]==="Wohnplatz" ) ||
              ( $govtype[1]==="(politische) Verwaltung" && strpos(strtolower($govtype[0]), "stadt") ) 
              ){
              $isSettlement = true;
              break;
            }
          }
        }
        if(!$isSettlement) continue;
      }
      $link = getURL("gov", $normedObj->id);
      if($link!==""){
        $resultObj->link = $link;
        $normedObj->link = $link;
      }
      if(isset($_GET["matchings"])){
        $matchings = getPossibleMatchings("gov", $normedObj->id, $resultObj);
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
        else{
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
function getGovEntityById(){
  global $IDSEARCH_RESULT_IN_ARRAY;
  $id = $_GET["id"]; 
  $soapclient = new SoapClient("http://gov.genealogy.net/services/ComplexService?wsdl");
  $result = $soapclient->getObject($id); 
  if($_GET["resultschema"]=="gaz"){
    $resultObj = processGovObject($result);
  }
  else{
    $resultObj = $result;
  }
  $link = getURL("gov", $id);
  if($link!==""){
    $resultObj->link = $link;
  } 
  if(isset($_GET["matchings"])) $resultObj->matchings = getPossibleMatchings("gov", $id, $result);
  if($IDSEARCH_RESULT_IN_ARRAY){
    $array = [];
    array_push($array, $resultObj);
    $resultObj = $array;
  }
  echo json_encode($resultObj, JSON_UNESCAPED_UNICODE);
}
function processGovObject($resultObj, $nameToSearchFor=""){
  $containsNameToSearchFor = false;
  $normedObj = new stdClass();
  $normedObj->id = $resultObj->id;
  $normedObj->name = "";
  $normedObj->names = [];
  if(is_array($resultObj->name)){
    foreach($resultObj->name as $resnameobj){ 
      $name = $resnameobj->value;
      $lang = $resnameobj->lang; 
      $nameobj  = new stdClass();
      $nameobj->name = $name;
      $nameobj->lang = $lang;
      array_push($normedObj->names, $nameobj);
      if(empty($nameobj->lang) || $nameobj->lang==$_GET["gov_preferred_lang"]){ 
        $normedObj->name = $name;
      }
      if($name===$nameToSearchFor) $containsNameToSearchFor = true;
    }
  }
  else{
    $normedObj->name = $resultObj->name->value;
    if($normedObj->name===$nameToSearchFor) $containsNameToSearchFor = true;
  }
  $coords = new stdClass();
  $coords->lat = (string)$resultObj->position->lat;
  $coords->long = (string)$resultObj->position->lon;
  $normedObj->coordinates = $coords;
  return $normedObj;
}
function checkGovPartOfGraphImg(){
  $id = $_GET["id"]; 
  $resourceUrl = getGovPOGImgURL($id);
  $resourceExists = "false";
  $ch = curl_init($resourceUrl);
  curl_setopt($ch, CURLOPT_NOBODY, true);
  curl_exec($ch);
  $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if($statusCode == '200') $resourceExists = "true"; 
  $answer = "{\"exists\":\"" . $resourceExists . "\"}";
  echo $answer;
}
function getGovPartOfGraphImg(){
  $id = $_GET["id"]; 
  $remoteImage = "https://gov.genealogy.net/item/relationshipGraph/" . $id . "?full-size=1";
  $imageData = base64_encode(file_get_contents($remoteImage));
  echo $imageData;
}
function getGovPOGImgURL($id){
  return "https://gov.genealogy.net/item/relationshipGraph/" . $id;
}
?>
