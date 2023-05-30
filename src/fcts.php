<?php
ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
require_once("dbConn.php");
require_once("urls.php");
require_once("get_matchings.php");
require_once("query_gov_api.php");
require_once("query_geonames_api.php");
require_once("query_gnd_api.php");
require_once("query_db.php");
require_once("query_prng_db.php");
require_once("query_histonamen_db.php");
require_once("query_naszekaszuby_db.php");
require_once("query_carpathorusyn_db.php");
require_once("query_prusijalit_db.php");
require_once("query_wikidata_db.php");
require_once("query_histonamen_db.php");

$govCountryIds = [];
$govCountryIds["poland"] = "object_190086"; 
$dbcolumnnames = "gaz";
$dbMatchingTable = "refs.refs_all_ourgazzes";
if(empty($_GET["resultschema"])) $_GET["resultschema"] = "gaz"; 
if(empty($_GET["namesearchmode"])) $_GET["namesearchmode"] = "word"; 
if(isset($_GET["json"])) header('Content-type:application/json;charset=utf-8'); 
$_GET["gov_preferred_lang"] =  "deu"; 
if(!empty($_GET["fct"])){
  if($_GET["fct"] == "get_geonames_entities_by_name" && !empty($_GET["name"])) getGeamesEntitiesByName();
  else if($_GET["fct"] == "get_geonames_entity_by_id" && !empty($_GET["id"])) getGeonamesEntityById();
  else if($_GET["fct"] == "get_gov_entities_by_name" && !empty($_GET["name"])) getGovEntitiesByName();
  else if($_GET["fct"] == "get_gov_entity_by_id" && !empty($_GET["id"])) getGovEntityById();
  else if($_GET["fct"] == "get_gnd_entities_by_name" && !empty($_GET["name"])) getGndEntitiesByName();
  else if($_GET["fct"] == "get_gnd_entity_by_id" && !empty($_GET["id"])) getGndEntityById();
  else if($_GET["gaz"] == "prng" && !empty($_GET["name"])) getPrngEntitiesByName();
  else if($_GET["gaz"] == "prng" && !empty($_GET["id"])) getPrngEntityById();
}
else if(!empty($_GET["gaz"])){
  if($_GET["gaz"] == "gov" && !empty($_GET["name"])){
    if(preg_match("/^id\:.*$/", $_GET["name"])){
      $IDSEARCH_RESULT_IN_ARRAY = true;
      $_GET["id"] = trim(substr($_GET["name"], 3));
      getGovEntityById();
    }
    else{
      getGovEntitiesByName();
    }
  }
  else if($_GET["gaz"] == "gov" && !empty($_GET["id"])){
    if(isset($_GET["existspartofimg"])) checkGovPartOfGraphImg();
    else if(isset($_GET["partofimg"])) getGovPartOfGraphImg();
    else getGovEntityById();
  }
  else if($_GET["gaz"] == "geonames" && !empty($_GET["name"])) getGeonamesEntitiesByName();
  else if($_GET["gaz"] == "geonames" && !empty($_GET["id"])) getGeonamesEntityById();
  else if($_GET["gaz"] == "gnd" && !empty($_GET["name"])) getGndEntitiesByName();
  else if($_GET["gaz"] == "gnd" && !empty($_GET["id"])) getGndEntityById();
  else if($_GET["gaz"] == "prng" && !empty($_GET["name"])) getPrngEntitiesByName();
  else if($_GET["gaz"] == "prng" && !empty($_GET["id"])) getPrngEntityById();
    else if($_GET["gaz"] == "poland16thc" && !empty($_GET["name"])) getPoland16thCEntitiesByName();
  else if($_GET["gaz"] == "poland16thc" && !empty($_GET["id"])) getPoland16thCEntityById();
  else if($_GET["gaz"] == "naszekaszuby" && !empty($_GET["name"])) getNaszekaszubyEntitiesByName();
  else if($_GET["gaz"] == "naszekaszuby" && !empty($_GET["id"])) getNaszekaszubyEntityById();
  else if($_GET["gaz"] == "carpathorusyn" && !empty($_GET["name"])) getCarpathorusynEntitiesByName();
  else if($_GET["gaz"] == "carpathorusyn" && !empty($_GET["id"])) getCarpathorusynEntityById();
  else if($_GET["gaz"] == "prusijalit" && !empty($_GET["name"])) getPrusijalitEntitiesByName();
  else if($_GET["gaz"] == "prusijalit" && !empty($_GET["id"])) getPrusijalitEntityById();
  else if($_GET["gaz"] == "wikidata" && !empty($_GET["name"])) getWikidataEntitiesByName();
  else if($_GET["gaz"] == "wikidata" && !empty($_GET["id"])) getWikidataEntityById();
  else if($_GET["gaz"] == "histonamen" && !empty($_GET["name"])) getHistonamenEntitiesByName();
  else if($_GET["gaz"] == "histonamen" && !empty($_GET["id"])) getHistonamenEntityById();
}
?>
