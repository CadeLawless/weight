<?php
ini_set('display_errors', 'On');
require "classes.php";
$db = new DB();
$weight = new Weight($db);
if(isset($_GET["id"])) $id = trim($_GET["id"]);
if($weight->delete_weight($id)){
    header("Location: index.php");
}else{
    echo "<script>alert('Something went wrong while trying to delete this entry')</script>";
    // echo $db->getConnection()->error;
}
?>