<?php
$conn;
function getDBConn(){
  global $user, $pass, $conn;
  if($conn==null){
    try{
      $conn = new PDO('pgsql:host=127.0.0.1;dbname=gazetteers', $user, $pass);
      $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) { echo "getDBConn().error: " . $e->getMessage() . PHP_EOL; die(); }
  }
  return $conn;
}
?>
