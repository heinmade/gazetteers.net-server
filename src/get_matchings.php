<?php
require_once("dbConn.php");
require_once("urls.php");
const inRefTableAndMatchingTable = [ "geonames", "gnd", "gov", "prng", "teryt_simc", "wikidata", "poland16thc" ];
const onlyInMatchingTable = [ "naszekaszuby", "carpathorusyn", "prusijalit" , "histonamen" ];
$relevantGazetteers = null;
function getPossibleMatchings($db1, $db1id, $resultObj=null){
  global $dbMatchingTable;
  global $relevantGazetteers;
  if($relevantGazetteers==null) $relevantGazetteers = array_merge(inRefTableAndMatchingTable, onlyInMatchingTable);
  try{
    $conn = getDBConn();
    $sameas = [];
    if(!in_array($db1, $relevantGazetteers)) return $sameas;
    $refs1 = [];
    if($resultObj!=null){
      if($db1=="gnd"){
        if(!empty($resultObj->sameAs)){
          foreach($resultObj->sameAs as $sameAs){
            $id = $sameAs->id; 
            $db = "";
            if(strpos($id, "geonames") !== false) $db = "geonames";
            else if(strpos($id, 'wikidata') !== false) $db = "wikidata";
            if($db=="") continue;
            $id = substr($id, strrpos($id, '/')+1);
            $ref = new stdClass();
            $ref->db = $db;
            $ref->id = $id;
            $ref->type="ref (from live data)";
            $refs1[$ref->db][$ref->id] = $ref;
          }
        }
      }
      else if($db1=="gov"){
        $refBlock = $resultObj->{'external-reference'};
        if($refBlock){
          $refArray = [];
          if(!is_array($refBlock)){
            $refArray[] = $refBlock;  
          }
          else{
            $refArray = $refBlock;
          }
          foreach($refArray as $refObj){
            $refString = $refObj->value;
            $refParts = explode(":", $refString);
            $db = tolower(trim($refParts[0]));
            if($db == "geonames" || $db == "gnd" || $db == "simc" || $db == "wikidata"){
              if($db == "simc") $db = "teryt_simc";
              $id = trim($refParts[1]);
              $ref = new stdClass();
              $ref->db = $db;
              $ref->id = $id;
              $ref->type="ref (from live data)";
              $refs1[$ref->db][$ref->id] = $ref;
            }
          }
        }
      }
      else if($db1=="wikidata"){
        if(!empty($resultObj->sameas)){
          foreach($resultObj->sameas as $sameAs){
            $db = $sameAs->database;
            if($db == "simc" || $db == "teryt") $db = "teryt_simc";
            $id = $sameAs->entity;
            $ref = new stdClass();
            $ref->db = $db;
            $ref->id = $id;
            $ref->type="ref (from live data)"; 
            $refs1[$ref->db][$ref->id] = $ref;
          }
        }
      }
    }
    $sameas = array_merge($sameas, $refs1);
    $refs2 = [];
    if(!in_array($db1, onlyInMatchingTable)){
      $sql = "select distinct db2, db2id, dbpath, distance, idx from refs where db1=? and db1id=? order by db2, distance, idx";
      $stmt = $conn->prepare($sql);
      $ret = $stmt->execute([$db1, $db1id]);
      while($row = $stmt->fetch()){
        $db2 = $row[0];
        if(!in_array($db2, $relevantGazetteers)) continue;
        if(array_key_exists($db2, $sameas)) continue; 
        $ref = new stdClass();
        $ref->db = $row[0];
        $ref->id = $row[1];
        $dbpath = $row[2];
        $ref->type="ref";
        $ref->description = "ref dbpath: " . $dbpath; 
        $link = getURL($ref->db, $ref->id);
        if($link!==""){
          $ref->link = $link;
        } 
        if(!isset($refs2[$ref->db][$ref->id])){
          $refs2[$ref->db][$ref->id] = $ref;
        }
      }
    }
    $sameas = array_merge($sameas, $refs2);
    $matchings = [];
    $sql = "select distinct db2, db2id, type, match_subtype, match_levdist, match_geodist_meter, ref_dbpath, ref_distance from matches_linked_with_refs where db1=? and db1id=? order by db2, type, ref_distance";
    $stmt = $conn->prepare($sql);
    $ret = $stmt->execute([$db1, $db1id]);
    while($row = $stmt->fetch()){
      $db2 = $row[0];
      if(!in_array($db2, $relevantGazetteers)) continue;
      if(array_key_exists($db2, $sameas)) continue; 
      $matching = new stdClass();
      $matching->db = $row[0];
      $matching->id = $row[1];
      $matching->type = $row[2];
      $match_subtype = $row[3];
      $match_levdist = $row[4];
      $match_geodist_meter = $row[5];
      $ref_dbpath = trim($row[6]);
      $description = "";
      if(isset($match_levdist)) $description .= "lev dist: " . $match_levdist . ", ";
      if(isset($match_geodist_meter)){
        $match_geodist_meter = round($match_geodist_meter);
        $description .= "geo dist: " . $match_geodist_meter . " m, ";
      }
      if(isset($match_subtype)) $description .= $match_subtype . ", ";
      if($description != "") $description = substr($description, 0, strlen($description)-2);
      if($ref_dbpath==""){
        $matching->description = $description;
      }
      else{
        $matching->description = "match: { " . $description . " } , ref: { " . $ref_dbpath ." }";
      }
      $link = getURL($matching->db, $matching->id);
      if($link!==""){
        $matching->link = $link;
      } 
      if(!isset($matchings[$matching->db][$matching->id])){
          $matchings[$matching->db][$matching->id] = $matching;
      }
    }
    $sameas = array_merge($sameas, $matchings);
    $sameas_without_keys = [];
    foreach($sameas as $stmnt){
      foreach($stmnt as $purestmnt){
        if($purestmnt->db=="wikidata" && $purestmnt->id[0] != "Q")
          $purestmnt->id = "Q" . $purestmnt->id;
        array_push($sameas_without_keys, $purestmnt);
      }
    }
    $sameas = $sameas_without_keys;  
    return $sameas;
  } catch (PDOException $e) { echo "getPossibleMatchings().error: " . $e->getMessage() . PHP_EOL; die(); }
}
?>
