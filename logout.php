<?php
session_start();
// database connections
require "classes.php";
$db = new DB();

$username = $_SESSION["username"];
$last_login = strtotime("-1 year");
$last_login = date("Y-m-d H:i:s", $last_login);
$findUser = $db->select("SELECT ip_addresses FROM users WHERE username = ?", "s", [$username]);
if($findUser->num_rows > 0){
    while($row = $findUser->fetch_assoc()){
        $ip_addresses = unserialize($row["ip_addresses"]);
    }
    foreach($ip_addresses as &$ip){
        if($ip["ip_address"] == $_SERVER["REMOTE_ADDR"]){
            $ip["last_login"] = $last_login;
        }
    }
    unset($ip);
    $ip_addresses = serialize($ip_addresses);
    if($db->write("UPDATE users SET ip_addresses = ? WHERE username = ?", "ss", [$ip_addresses, $username])){
        session_unset();
        session_destroy();
        header("Location: login.php");
    }
}