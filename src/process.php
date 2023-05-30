<?php
function toAscii($str){
  setlocale(LC_ALL, "en_US.UTF-8"); 
  $strmod = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
  $conv = true;
  if (strpos($strmod, '?') !== FALSE){
    $strmod = $str;
    $conv = false;
  }
  return array('string' => $strmod, 'isascii' => $conv);
}
function toLower($str){
  $str = mb_strtolower($str, "UTF-8");
  return $str;
}
function toLowerAndAscii($str){
  $res = toAscii($str);
  $res['string'] = toLower($res['string']);
  return $res;
}
function searchMatchesName($names, $orgSearchphrase, $exactMatch){
  $searchphrase = toLowerAndAscii($orgSearchphrase)['string'];
  $searchphrase = reHandleWildcard($searchphrase);
  if($exactMatch) $re = reIsSearchphraseTheString($searchphrase);
  else $re = reIsSearchphraseAWordInString($searchphrase);
  foreach($names as $orgName){
    $name = toLowerAndAscii($orgName)['string'];
    if(preg_match($re, $name)) return true;
  }
  return false;
}
function reHandleWildcard($searchphrase){
  return str_replace("*", ".*", $searchphrase);
}
function reIsSearchphraseAWordInString($searchphrase){
  $separators = "\s,;.:\"'\-\/_()\[\]";
  $begin = "(^|[$separators])";
  $end = "($|[$separators])";
  return "/$begin$searchphrase$end/";
}
function reIsSearchphraseTheString($searchphrase){
  $begin = "^";
  $end = "$";
  return "/$begin$searchphrase$end/";
}
function isInBoundingBox($bb, $point){
  if( $point->long < $bb->west || $point->long > $bb->east || $point->lat < $bb->south || $point->lat > $bb->north )
    return false;
  return true;
}
?>
