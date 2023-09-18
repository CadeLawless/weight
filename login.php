<?php
session_start();
ini_set('display_errors', 'On');
date_default_timezone_set("America/Chicago");

// database connections
require "classes.php";
$db = new DB();

// initialize form field variables
$username = "";
$password = "";

// function that error checks inputs
function inputCheck($input, $input_name, $input_type="", $required = "No", &$errors="", &$error_list=""){
    if(isset($_POST[$input]) && trim($_POST[$input]) != ""){
        $value = trim($_POST[$input]);
    }else{
        if($required == "Yes"){
            $errors = true;
            if($input_type == "text"){
                $error_list .= "<li>$input_name is a required field. Please fill it out.</li>";
            }elseif($input_type == "select" || $input_type == "radio"){
                $error_list .= "<li>$input_name is a required field. Please select an option.</li>";
            }elseif($input_type == "checkbox"){
                $error_list .= "<li>$input_name<?li>";
            }
        }
        $value = "";
    }
    return $value;
}

// function that pattern checks inputs
function patternCheck($input, $regex, &$errors, &$error_list, $msg){
    if($input != ""){
        if(!preg_match($regex, $input)){
            $errors = true;
            $error_list .= "<li>$msg</li>";
        }
    }
}

// submit php
if(isset($_POST["submit_button"])){
    $errors = false;
    $error_title = "<b>Login failed due to the following errors:<b>";
    $error_list = "";
    $username = inputCheck("username", "Username", "text", "Yes", $errors, $error_list);
    $password = inputCheck("password", "Password", "text", "Yes", $errors, $error_list);
    if(!$errors){
        $findUser = $db->select("SELECT password, ip_addresses FROM users WHERE username = ?", "s", [$username]);
        if($findUser->num_rows > 0){
            while($row = $findUser->fetch_assoc()){
                $hashed_password = $row["password"];
                if(password_verify($password, $hashed_password)){
                    $findIPAddresses = $db->select("SELECT username, ip_addresses FROM users WHERE ip_addresses LIKE ?", "s", ["%{$_SERVER["REMOTE_ADDR"]}%"]);
                    if($findIPAddresses->num_rows == 0){
                        $ip_addresses = unserialize($row["ip_addresses"]);
                        array_push($ip_addresses, ["ip_address" => $_SERVER["REMOTE_ADDR"], "last_login" => date("Y-m-d H:i:s")]);
                        $ip_addresses = serialize($ip_addresses);
                        if($db->write("UPDATE users SET ip_addresses = ? WHERE username = ?", "ss", [$ip_addresses, $username])){
                            $_SESSION["logged_in"] = true;
                            $_SESSION["username"] = $username;
                            header("Location: index.php");
                        }
                    }else{
                        $originalUser = true;
                        while($ip = $findIPAddresses->fetch_assoc()){
                            if($ip["username"] != $username){
                                $ip_addresses = unserialize($ip["ip_addresses"]);
                                foreach($ip_addresses as &$address){
                                    if($address["ip_address"] == $_SERVER["REMOTE_ADDR"]){
                                        unset($ip_addresses[array_search($address, $ip_addresses)]);
                                    }
                                }
                                unset($address);
                                $ip_addresses = serialize($ip_addresses);
                                $db->write("UPDATE users SET ip_addresses = ? WHERE username = ?", "ss", [$ip_addresses, $ip["username"]]);
                                $originalUser = false;
                            }
                        }
                        if(!$originalUser){
                            $ip_addresses = unserialize($row["ip_addresses"]);
                            array_push($ip_addresses, ["ip_address" => $_SERVER["REMOTE_ADDR"], "last_login" => date("Y-m-d H:i:s")]);
                            $ip_addresses = serialize($ip_addresses);
                            if($db->write("UPDATE users SET ip_addresses = ? WHERE username = ?", "ss", [$ip_addresses, $username])){
                                $_SESSION["logged_in"] = true;
                                $_SESSION["username"] = $username;
                                header("Location: index.php");
                            }
                        }else{
                            $_SESSION["logged_in"] = true;
                            $_SESSION["username"] = $username;
                            header("Location: index.php");
                        }
                    }
                }else{
                    $errors = true;
                    $error_list .= "<li>Username or password is incorrect</li>";
                }
            }
        }else{
            $errors = true;
            $error_list .= "<li>Username or password is incorrect</li>";
        }
    }else{
        $error_msg = "<div class='submit-error'>$error_title<ul>$errorList</ul></div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/daily_weight.css" />
    <title>Daily Weight | Login</title>
</head>
<body>
    <h1 class="center">Login</h1>
    <?php if(isset($error_msg)) echo $error_msg?>
    <form id="login-form flex" method="POST" action="">
        <div class="large-input center">
            <label for="username">Username: </label><br>
            <input type="text" name="username" id="username">
            <span class="error-msg hidden">Username must include</span>
        </div>
        <div class="large-input center">
            <label for="password">Password: </label><br>
            <input type="password" name="password" id="password">
            <span class="error-msg hidden">Password must include</span>
        </div>
        <p class="large-input center"><input type="submit" name="submit_button" value="Login"></p>
        <p style="font-size: 14px" class="large-input center">Dont have an account? <a href="create-an-account.php">Create one here</a></p>
    </form>
</body>
</html>