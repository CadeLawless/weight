<?php
ini_set('display_errors', 'On');
require "classes.php";
$db = new DB();
if(isset($_GET["id"])) $id = trim($_GET["id"]);
if($db->write("DELETE FROM daily_measurements WHERE id = ?", [$id])){
    header("Location: measurements.php");
}else{
    echo "<script>alert('Something went wrong while trying to delete this entry')</script>";
    // echo $db->getConnection()->error;
}
?>