<?php
require_once("dbConn.php");
#require_once("fcts.php"); 
require_once("urls.php");
require_once("get_matchings.php");
function getWikidataEntitiesByName(){
  if($_GET["namesearchmode"]=="org" || $_GET["namesearchmode"]=="full") $nameTable = "wikidata.items_names";
  else $nameTable = "wikidata.items_nameparts";
  $name_org = $_GET["name"]; 
  $nameColumn = "name_ascii_lowercase";
  $res = toLowerAndAscii($name_org);
  $name = $res['string'];
  if(!$res['isascii']) $nameColumn = "name_lowercase";
  $operator = "=";
  if(strpos($name, "?")!==false || strpos($name, "*")!==false){
    $operator = " like ";
    $name = str_replace("?", "_", $name);
    $name = str_replace("*", "%", $name);
  }
  $params = [];
  array_push($params, $name);
  $sqlSelect = " select a.wdid";
  $sqlFrom = " from $nameTable a";
  $sqlWhere = " where a.$nameColumn $operator ?";
  $sqlOrder = " order by a.wdid";
  $sqlLimit = " limit 1000";
  if(!empty($_GET["north"]) && !empty($_GET["south"]) && !empty($_GET["west"]) && !empty($_GET["east"])){
    $sqlFrom .= " join wikidata.items_coords b on a.wdid=b.wdid";
    $sqlWhere .= " and b.geom && st_transform(ST_MakeEnvelope(?, ?, ?, ?, 4326), 4326) ";
    array_push($params, $_GET["west"], $_GET["south"], $_GET["east"], $_GET["north"]);
  }
  if($_GET["ftype"]==="settlement"){
    $sqlFrom .= " join wikidata.items_types c on a.wdid=c.itemid join wikidata.settlement_types d on c.typeid=d.item";
  }
  $sqlFilter = $sqlSelect . $sqlFrom . $sqlWhere . $sqlOrder . $sqlLimit;
  $entObjs = queryWikidataEntities("in ($sqlFilter)", $params);
  echo json_encode($entObjs, JSON_UNESCAPED_UNICODE);
}
function getWikidataEntityById(){
  $entObjs = queryWikidataEntities("=?", [$_GET["id"]]);
  echo json_encode($entObjs[0], JSON_UNESCAPED_UNICODE);
}
function queryWikidataEntities($subsql, $params){
  try{
    $conn = getDBConn();
    $entObjs = [];
    $sql = "select distinct wdid, name, lang, region from wikidata.items_names " .
    "where wdid $subsql order by wdid;";
    $stmt = $conn->prepare($sql);
    $ret = $stmt->execute($params);
    $oldwdid = "";
    $oldEntObj = "";
    while($row = $stmt->fetch()){
      $wdid = $row[0];
      $nameObj = new stdClass();
      $nameObj->name = $row[1];
      $nameObj->lang = $row[2];
      if($wdid!=$oldwdid){
        $entObj = new stdClass();
        $entObj->id = $wdid;
        $entObjs[$wdid] =  $entObj;
        $entObj->names = [];
      }
      else $entObj = $oldEntObj;
      array_push($entObj->names, $nameObj);
      $oldwdid = $wdid;
      $oldEntObj = $entObj;
    }
    $sql = "select distinct wdid, lat, lon from wikidata.items_coords " .
    "where wdid $subsql order by wdid;";
    $stmt = $conn->prepare($sql);
    $ret = $stmt->execute($params);
    $oldwdid = "";
    $oldEntObj = "";
    while($row = $stmt->fetch()){
      $wdid = $row[0];
      $coordObj = new stdClass();
      $coordObj->lat = $row[1];
      $coordObj->lon = $row[2];
      if($wdid!=$oldwdid){
        $entObj = $entObjs[$wdid];
        $entObj->coordinates = [];
      }
      else $entObj = $oldEntObj;
      array_push($entObj->coordinates, $coordObj);
      $oldwdid = $wdid;
      $oldEntObj = $entObj;
    }
    $sql = "select distinct itemid, typelabel_en from wikidata.items_types " .
    "where itemid $subsql order by itemid;";
    $stmt = $conn->prepare($sql);
    $ret = $stmt->execute($params);
    $oldwdid = "";
    $oldEntObj = "";
    while($row = $stmt->fetch()){
      $wdid = $row[0];
      $attrObj = new stdClass();
      $attrObj->name = $row[1];
      if($wdid!=$oldwdid){
        $entObj = $entObjs[$wdid];
        $entObj->types = [];
      }
      else $entObj = $oldEntObj;
      array_push($entObj->types, $attrObj);
      $oldwdid = $wdid;
      $oldEntObj = $entObj;
    }
    $sql = "select distinct wdid, reffed_db, reffed_id from wikidata.items_refs " .
    "where wdid $subsql order by wdid, reffed_db;";
    $stmt = $conn->prepare($sql);
    $ret = $stmt->execute($params);
    $oldwdid = "";
    $oldEntObj = "";
    while($row = $stmt->fetch()){
      $wdid = $row[0];
      $attrObj = new stdClass();
      $attrObj->database = $row[1];
      $attrObj->entity = $row[2];
      if($wdid!=$oldwdid){
        $entObj = $entObjs[$wdid];
        $entObj->sameas = [];
      }
      else $entObj = $oldEntObj;
      array_push($entObj->sameas, $attrObj);
      $oldwdid = $wdid;
      $oldEntObj = $entObj;
    }
    $pureEntObjs = [];
    foreach($entObjs as $entObj){
      array_push($pureEntObjs, $entObj);
      if(isset($_GET["matchings"])){
        $entObj->matchings = getPossibleMatchings("wikidata", substr($entObj->id,1), $entObj);
      }
      $link = getURL("wikidata", $entObj->id);
      if($link!==""){
        $entObj->link = $link;
      }
    }
    $entObjs = $pureEntObjs;
    return $entObjs;
  } catch (PDOException $e) { echo "getDBGazEntityById().error: " . $e->getMessage() . PHP_EOL; die(); }
}
