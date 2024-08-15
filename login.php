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
        $findUser = $db->select("SELECT password FROM users WHERE username = ?", [$username]);
        if($findUser->num_rows > 0){
            while($row = $findUser->fetch_assoc()){
                $hashed_password = $row["password"];
                if(password_verify($password, $hashed_password)){
                    $expire_date = date("Y-m-d H:i:s", strtotime("+1 year"));
                    if($db->write("UPDATE users SET session = ?, session_expiration = ? WHERE username = ?", [session_id(), $expire_date, $username])){
                        $cookie_time = (3600 * 24 * 365); // 1 year
                        setcookie("session_id", session_id(), time() + $cookie_time);
                        $_SESSION["weight_logged_in"] = true;
                        $_SESSION["username"] = $username;
                        header("Location: index.php");
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
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <title>Daily Weight | Login</title>
</head>
<body>
    <div class="container">
        <form class="login-form flex login" method="POST" action="">
            <div class="large-input logo-header">
                <p class="center no-margin"><img class="logo" src="images/dumbbell-blue.png" /></p>
                <h1 class="center no-margin">Login</h1>
            </div>
            <?php if(isset($error_msg)) echo $error_msg?>
            <div class="large-input">
                <label for="username">Username: </label><br>
                <input type="text" name="username" id="username">
                <span class="error-msg hidden">Username must include</span>
            </div>
            <div class="large-input">
                <label for="password">Password: </label><br>
                <input type="password" name="password" id="password">
                <span class="error-msg hidden">Password must include</span>
            </div>
            <p class="large-input center"><input type="submit" name="submit_button" value="Login"></p>
            <p style="font-size: 14px" class="large-input center">Dont have an account? <a href="create-an-account.php">Create one here</a></p>
        </form>
    </div>
</body>
</html>