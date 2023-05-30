<?php
const urlprefixes = array(
  "gnd" => "https://d-nb.info/gnd/",
  "geonames" => "https://www.geonames.org/",
  "gov" => "http://gov.genealogy.net/item/show/",
  "wikidata" => "https://www.wikidata.org/wiki/"
);
function getURL($db, $dbid){
  $prefix = urlprefixes[$db];
  if($db=="wikidata" && $dbid[0] != "Q") $dbid = "Q" . $dbid; 
  if(isset($prefix)) return $prefix . $dbid;
  return "";
};
?>
