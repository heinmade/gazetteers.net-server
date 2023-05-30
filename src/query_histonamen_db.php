<?php
require_once("dbConn.php");
require_once("fcts.php"); 
require_once("urls.php");
require_once("get_matchings.php");
function getHistonamenEntitiesByName(){
  if( !(!empty($_GET["north"]) && !empty($_GET["south"]) && !empty($_GET["west"]) && !empty($_GET["east"])) ){
    if($_GET["namesearchmode"]=="org" || $_GET["namesearchmode"]=="full") $nameTable = "histonamen.ort_names";
    else $nameTable = "histonamen.ort_nameparts";
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
    $sqlSelect = " select a.ort";
    $sqlFrom = " from $nameTable a";
    $sqlWhere = " where a.$nameColumn $operator ?";
    $sqlOrder = " order by a.ort";
    $sqlLimit = " limit 1000";
    $sqlFilter = $sqlSelect . $sqlFrom . $sqlWhere . $sqlOrder . $sqlLimit;
    $entObjs = queryHistonamenEntities("in ($sqlFilter)", $params);
  }
  else $entObjs = [];
  echo json_encode($entObjs, JSON_UNESCAPED_UNICODE);
}
function getHistonamenEntityById(){
  $entObjs = queryHistonamenEntities("=?", [$_GET["id"]]);
  echo json_encode($entObjs[0], JSON_UNESCAPED_UNICODE);
}
function queryHistonamenEntities($subsql, $params){
  try{
    $conn = getDBConn();
    $entObjs = [];
    $sql =
      "select ort, name, blatt, netz " .
      "from histonamen.ort " .
      "where ort $subsql " .
      "order by ort";
    $stmt = $conn->prepare($sql);
    $ret = $stmt->execute($params);
    $oldortid = "";
    $oldEntObj = "";
    while($row = $stmt->fetch()){
      $entObj = new stdClass();
      $ortid = $row[0];
      $entObj->id = $ortid
      ;
      $entObj->name = $row[1];
      $entObj->blatt = $row[2];
      $entObj->netz = $row[3];
      $entObjs[$ortid] =  $entObj;
    }
    $sql =
      "select " .
      "a.ort, a.nr, a.ortsname, a.zeit, concat(d.name, ' ', d.notiz) as zeit_name, " .
      "a.staat, b.name as staat_name, " .
      "a.admin1, c.name as admin_name " .
      "from histonamen.chron a " .
      "join histonamen.zeit d on a.zeit=d.zeit " .
      "join histonamen.staat b on a.staat=b.staat " .
      "join histonamen.admin c on a.staat=c.staat and a.admin1=c.admin1 and a.admin2=c.admin2 and a.admin3=c.admin3 and a.admin4=c.admin4 " .
      "where ort $subsql " .
      "order by ort";
    $stmt = $conn->prepare($sql);
    $ret = $stmt->execute($params);
    $oldortid = "";
    $oldEntObj = "";
    while($row = $stmt->fetch()){
      $ortid = $row[0];
      $chronObj = new stdClass();
      $chronObj->name = $row[2];
      $chronObj->zeit = trim($row[4]);
      $chronObj->staat = $row[6];
      $chronObj->admin = $row[8];
      if($ortid!=$oldortid){
        $entObj = $entObjs[$ortid];
        $entObj->chron = [];
      }
      else $entObj = $oldEntObj;
      array_push($entObj->chron, $chronObj);
      $oldortid = $ortid;
      $oldEntObj = $entObj;
    }
    $pureEntObjs = [];
    foreach($entObjs as $entObj){
      array_push($pureEntObjs, $entObj);
      if(isset($_GET["matchings"])){
        $entObj->matchings = getPossibleMatchings("histonamen", $entObj->id, $entObj);
      }
    }
    $entObjs = $pureEntObjs;
    return $entObjs;
  } catch (PDOException $e) { echo "getDBGazEntityById().error: " . $e->getMessage() . PHP_EOL; die(); }
}
