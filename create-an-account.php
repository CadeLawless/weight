<?php
session_start();
ini_set('display_errors', 'On');
date_default_timezone_set("America/Chicago");

// database connections
require "classes.php";
$db = new DB();

// initialize form field variables
$fname = "";
$lname = "";
$email = "";
$username = "";
$password = "";
$confirm_password = "";

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
    $error_title = "<b>An account could not be created due to the following errors:<b>";
    $error_list = "";
    $fname = inputCheck("fname", "First Name", "text", "Yes", $errors, $error_list);
    $lname = inputCheck("lname", "Last Name", "text", "Yes", $errors, $error_list);
    $email = inputCheck("email", "Email Address", "text", "Yes", $errors, $error_list);
    patternCheck($email, "/\S+@\S+\.\S{2,}/", $errors, $error_list, "Email Address format must match: email@address.com");
    $username = inputCheck("username", "Username", "text", "Yes", $errors, $error_list);
    patternCheck($username, "/[a-zA-Z]+\d+/", $errors, $error_list, "Username must include at least 1 letter and 1 number.");
    if(!$errors){
        $findUsers = $db->select("SELECT username FROM users WHERE username = ?", "s", [$username]);
        if($findUsers->num_rows > 0){
            $errors = true;
            $error_list .= "<li>That username is already taken. Try a different one.</li>";
        }
        $findUsers = $db->select("SELECT username FROM users WHERE email = ?", "s", [$email]);
        if($findUsers->num_rows > 0){
            $errors = true;
            $error_list .= "<li>That email already already has an account set up. Sign in <a href='login.php'>here</a></li>";
        }
    }
    $password = inputCheck("password", "Password", "text", "Yes", $errors, $error_list);
    patternCheck($password, "/[a-zA-Z]+\d+/", $errors, $error_list, "Password must include at least 1 letter and 1 number.");
    $confirm_password = inputCheck("confirm_password", "Confirm Password", "text", "Yes", $errors, $error_list);
    if($password != "" && $confirm_password != "" && $password != $confirm_password){
        $errors = true;
        $error_list .= "<li>Password and Confirm Password must match.</li>";
    }else{
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    }
    if(!$errors){
        $expire_date = date("Y-m-d H:i:s", strtotime("+1 year"));
        if($db->write("INSERT INTO users (fname, lname, email, username, password, session, session_expiration) VALUES(?,?,?,?,?,?,?)", "ssssssss", [$fname, $lname, $email, $username, $hashed_password, session_id(), $expire_date])){
            $cookie_time = (3600 * 24 * 365); // 1 year
            setcookie("session_id", session_id(), time() + $cookie_time);
            $_SESSION["logged_in"] = true;
            $_SESSION["username"] = $username;
            header("Location: index.php");
        }else{
            echo "<script>alert('Something went wrong while trying to create this account')</script>";
            // echo $db->error;
        }
    }else{
        $error_msg = "<div class='error'>$error_title<ul>$error_list</ul></div><br>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/daily_weight.css" />
    <title>Daily Weight | Create an Account</title>
</head>
<body>
    <h1 class="center">Create an Account</h1>
    <?php if(isset($error_msg)) echo $error_msg?>
    <form id="login-form flex" method="POST" action="">
        <div class="large-input center">
            <label for="fname">First Name: </label><br>
            <input type="text" name="fname" value="<?php echo $fname?>" id="fname">
        </div>
        <div class="large-input center">
            <label for="lname">Last Name: </label><br>
            <input type="text" name="lname" value="<?php echo $lname?>" id="lname">
        </div>
        <div class="large-input center">
            <label for="email">Email Address: </label><br>
            <input type="text" name="email" id="email" value="<?php echo $email?>" pattern="^\S+@\S+\.\S{2,}$">
            <span class="error-msg hidden">Email Address format must match: email@address.com</span>
        </div>
        <div class="large-input center">
            <label for="date-of-birth">Date of Birth: </label><br>
            <input type="date" name="date_of_birth" id="date-of-birth" value="<?php echo $date_of_birth?>">
        </div>
        <div class="large-input center">
            <label>Gender: </label><br>
            <input type="radio" name="gender" id="male" value="Male"><label for="male">Male</label>
            <input type="radio" name="gender" id="female" value="Female"><label for="female">Female</label>
        </div>

        <div class="large-input center">
            <label for="username">Username: </label><br>
            <input type="text" name="username" value="<?php echo $username?>" id="username" pattern=".*[a-zA-Z]+\d+.*">
            <span class="error-msg hidden">Username must include at least 1 letter and 1 number</span>
        </div>
        <div class="large-input center">
            <label for="password">Password: </label><br>
            <input type="password" name="password" id="password" value="<?php echo $password?>" pattern=".*[a-zA-Z]+\d+.*">
            <span class="error-msg hidden">Password must include at least 1 letter and 1 number</span>
        </div>
        <div class="large-input center">
            <label for="confirm_password">Confirm Password: </label><br>
            <input type="password" name="confirm_password" value="<?php echo $confirm_password?>" id="confirm_password">
            <span class="error-msg hidden">Passwords must match</span>
        </div>
        <p class="large-input center"><input type="submit" name="submit_button" value="Start Tracking"></p>
    </form>
</body>
</html>