<?php
require_once("process.php");
function getDBGazColumnNames($mainTable){
  global $dbcolumnnames;
  try{
    $conn = getDBConn();
    $tableColumns  = [];
    $sql = "select originalname, eigener_spaltenname, spaltenname_display from $mainTable order by idx";
    $stmt = $conn->prepare($sql);
    $ret = $stmt->execute();
    while($row = $stmt->fetch()){
      $tableColumn = new stdClass();
      $tableColumn->orgname = $row[0];
      $tableColumn->tblcolname = $row[1];
      $tableColumn->tblcoldisplay = $row[2];
      array_push($tableColumns, $tableColumn);
    }
    return $tableColumns;
  } catch (PDOException $e) { echo "getDBGazColumnNames().error: " . $e->getMessage() . PHP_EOL; die(); }
}
function getDBGazEntitiesByName($mainTable, $nameTable,  $processFunction, $coordsExist, $gazName="", $idColumn=""){
  global $dbcolumnnames; 
  if( $coordsExist || !( !empty($_GET["north"]) && !empty($_GET["south"]) && !empty($_GET["west"]) && !empty($_GET["east"]) ) ){
    $nameColumns = ["name_ascii_lowercase"];
    $sqlName = "";
    $name_org = $_GET["name"]; 
    $res = toLowerAndAscii($name_org);
    $name = $res['string'];
    if(!$res['isascii']) $nameColumns = ["name_lowercase"];
    $operator="=";
    if(strpos($name, "?")!==false || strpos($name, "*")!==false){
      $operator = " like ";
      $name = str_replace("?", "_", $name);
      $name = str_replace("*", "%", $name);
    }
    try{
      $conn = getDBConn();
      $tableColumns  = [];
      $params = [];
      $sql2 = "select distinct ";
      $mainTableAlias = "t1";
      $tableColumns = getDBGazColumnNames($mainTable . "_spaltennamen");
      foreach($tableColumns as $tableColumn){
        $sql2 .= $mainTableAlias . "." . $tableColumn->tblcolname . ",";
      }
      $sql2 = substr($sql2, 0, -1);
      $sql2 .= " from $mainTable $mainTableAlias";
      $sqlJoin = "";
      $nameTableAlias = $mainTableAlias;
      if(!empty($nameTable)){
        $nameTableAlias = "t2";
        $sql2 .= " left join $nameTable $nameTableAlias on $mainTableAlias.idx=$nameTableAlias.fk_ent";
      }
      $sql2 .= " where";
      foreach($nameColumns as $nameColumn){
        $sqlName .= " $nameTableAlias.$nameColumn $operator ? or ";
        array_push($params, $name);
      }
      $sqlName = mb_substr($sqlName, 0, -3);
      $sqlName = " (" . $sqlName . ") "; 
      $sql2 .= $sqlName; 
      if($coordsExist && !empty($_GET["north"]) && !empty($_GET["south"]) && !empty($_GET["west"]) && !empty($_GET["east"])){
       $sql2 .= " and coord && st_transform(ST_MakeEnvelope(?, ?, ?, ?, 4326), 4326) ";
       array_push($params, $_GET["west"], $_GET["south"], $_GET["east"], $_GET["north"]);
      }
      $sql2 .= " limit 1000";
      $stmt = $conn->prepare($sql2);
      $ret = $stmt->execute($params);
      $numcols = count($tableColumns);
      $orgObjs = [];
      while($row = $stmt->fetch()){
        $orgObj = new stdClass();
        for($i=0; $i<$numcols; $i++){
          if($dbcolumnnames=="org"){
            $col = $tableColumns[$i]->orgname;
          }
          else{
            $col = $tableColumns[$i]->tblcoldisplay; 
          }
          $orgObj->$col = $row[$i];
        }
        unset($orgObj->wkb_geometry); 
        if(isset($_GET["matchings"]) && $gazName!=="" && $idColumn!==""){
          $orgObj->matchings = getPossibleMatchings($gazName, $orgObj->$idColumn);
        }
        array_push($orgObjs, $orgObj);
      }
      if($_GET["resultschema"]=="gaz"){
        $normedObjs = [];
        foreach($orgObjs as $resultObj){
          $normedObj = $processFunction($resultObj);
          if(isset($_GET["matchings"]) && $gazName!=="" && $idColumn!==""){
            $normedObj->matchings = getPossibleMatchings($gazName, $normedObj->id);
          }
          array_push($normedObjs, $normedObj);
          $resultObjs = $normedObjs;
        }
      }
      else{
        $resultObjs = $orgObjs;
      }
      if($resultObjs==null ) $resultObjs = [];
    } catch (PDOException $e) { echo "getDBGazEntitiesByName().error: " . $e->getMessage() . PHP_EOL; die(); }
  }
  else $entObjs = [];
  echo json_encode($resultObjs, JSON_UNESCAPED_UNICODE);
}
function getDBGazEntityById($mainTable, $idColumn, $processFunction, $gazName=""){
  global $dbcolumnnames; 
  $id = $_GET["id"]; 
  try{
    $conn = getDBConn();
    $sql2 = "select ";
    $tableColumns = getDBGazColumnNames($mainTable . "_spaltennamen");
    foreach($tableColumns as $tableColumn){
      $sql2 .= $tableColumn->tblcolname . ",";
    }
    $sql2 = substr($sql2, 0, -1);
    $sql2 .= " from $mainTable where $idColumn=?";
    $stmt = $conn->prepare($sql2);
    $ret = $stmt->execute([$id]);
    $numcols = count($tableColumns);
    if($row = $stmt->fetch()){
      $orgObj = new stdClass();
      for($i=0; $i<$numcols; $i++){
        if($dbcolumnnames=="org"){
          $col = $tableColumns[$i]->orgname;
        }
        else{
          $col = $tableColumns[$i]->tblcoldisplay;
        }
        $orgObj->$col = $row[$i];
      }
    }
    if($_GET["resultschema"]=="gaz"){
      $resultObj = $processFunction($orgObj);
    }
    else{
      unset($orgObj->wkb_geometry); 
      $resultObj = $orgObj;
    }
    $resultObj->matchings = getPossibleMatchings($gazName, $id);
    echo json_encode($resultObj, JSON_UNESCAPED_UNICODE);
  } catch (PDOException $e) { echo "getDBGazEntityById().error: " . $e->getMessage() . PHP_EOL; die(); }
}
?>
