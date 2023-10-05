<?php
session_start();
ini_set('display_errors', 'On');
date_default_timezone_set("America/Chicago");

// database connections
require "classes.php";
$db = new DB();
$weight = new Weight($db);

// check if logged in
if(!isset($_SESSION["logged_in"])){
    // check if IP address is associated with a user 
    $current_ip_address = $_SERVER["REMOTE_ADDR"];
    $findIPAddresses = $db->query("SELECT username, ip_addresses FROM users");
    if($findIPAddresses->num_rows > 0){
        while($row = $findIPAddresses->fetch_assoc()){
            $ip_addresses = unserialize($row["ip_addresses"]);
            if(is_array($ip_addresses)){
                foreach($ip_addresses as $ip){
                    if($ip["ip_address"] == $current_ip_address){
                        // check last login, if it has been over a year since last login, make them sign in again
                        $oneYearAgo = date("Y-m-d H:i:s", strtotime("-1 year"));
                        $last_login = date("Y-m-d H:i:s", strtotime($ip["last_login"]));
                        if($last_login < $oneYearAgo){
                            header("Location: login.php");
                        }else{
                            $_SESSION["logged_in"] = true;
                            $_SESSION["username"] = $row["username"];
                            $username = $_SESSION["username"];
                        }
                    }
                }
            }
        }
        if(!$_SESSION["logged_in"]){
            header("Location: login.php");
        }
    }else{
        header("Location: login.php");
    }
}else{
    $username = $_SESSION["username"];
}
/* $array = [
    ["ip_address" => $_SERVER["REMOTE_ADDR"], "last_login", date("Y-m-d H:i:s")]
];
echo serialize($array);
 */
// initialize form field variables
$waist = "";
$right_bicep = "";
$left_bicep = "";
$chest = "";

function errorCheck($input, $inputName, $required="No", &$errors="", &$error_list=""){
    if(isset($_POST[$input]) && trim($_POST[$input]) != ""){
        return trim($_POST[$input]);
    }else{
        if($required == "Yes"){
            $errors = true;
            $error_list .= "<li>$inputName is a required field. Please fill it out.</li>";
        }
        return "";
    }
}

function patternCheck($regex, $input, &$errors, &$error_list, $inputName) {
    if(!preg_match($regex, $input)){
        $errors = true;
        $error_list .= "<li>$inputName must only contain numbers and up to 1 decimal</li>";
    }
}

if (isset($_POST["submit_button"])){
    $errors = false;
    $errorTitle = "<p>The form could not be submitted due to the following errors:</p>";
    $errorList = "";
    $waist = errorCheck("waist", "Waist", "Yes", $errors, $errorList);
    $right_bicep = errorCheck("right_bicep", "Right Bicep", "Yes", $errors, $errorList);
    $left_bicep = errorCheck("left_bicep", "Left Bicep", "Yes", $errors, $errorList);
    $chest = errorCheck("chest", "Chest", "Yes", $errors, $errorList);
    patternCheck("/^\d*\.?\d*$/", $waist, $errors, $errorList, "Waist");
    patternCheck("/^\d*\.?\d*$/", $right_bicep, $errors, $errorList, "Right Bicep");
    patternCheck("/^\d*\.?\d*$/", $left_bicep, $errors, $errorList, "Left Bicep");
    patternCheck("/^\d*\.?\d*$/", $chest, $errors, $errorList, "Chest");
    $dateEntered = date("Y-m-d");
    if(!$errors){
        if($db->write("INSERT INTO daily_measurements(username, waist, right_bicep, left_bicep, chest, date_measured) VALUES(?, ?, ?, ?, ?, '$dateEntered')", "sssss", [$username, $waist, $right_bicep, $left_bicep, $chest])){
            header("Location: measurements.php");
        }else{
            echo "<script>alert('Something went wrong while trying to record these measurements')</script>";
            // echo $db->error;
        }
    }else{
        $errorMessage = "<div class='error' style='width: 100%;'>$errorTitle<ul>$errorList</ul></div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1">
    <link rel="stylesheet" type="text/css" href="css/daily_weight.css" />
    <link rel="stylesheet" type="text/css" href="css/info-banner.css" />
    <link rel="stylesheet" type="text/css" href="css/submit-button.css" />
    <!-- <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/tsparticles-confetti@2.10.0/tsparticles.confetti.bundle.min.js"></script>
    <title>Daily Weight</title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">

    <style>
        @media (max-width: 503px) {
            #title-overlay {
                height: 190px;
            }
            h1 {
                display: block !important;
                font-size: 12vw;
            }
            #title-start {
                margin-bottom: 0;
            }
            #dropdown-title h1 {
                margin-top: 0;
            }
            #dropdown-title {
                display: block;
            }
            .show-dropdown {
                top: 102%;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/nav.php"?>
    <div class="flex">
        <div>
            <div id="title-overlay"></div>
            <div id="title-container">
                <div id="title-top"></div>
                <h1 id="title-start">Daily </h1>
                <div id="dropdown-title">
                    <h1>Measurements <span id="down-caret">&#8964;</span></h1>
                    <ul id="dropdown-title-list" class="hide-dropdown">
                        <li><a href="index.php">Weight</a></li>
                        <li><a href="body-fat.php">Body Fat %</a></li>
                    </ul>
                </div>
            </div>
            <br>
            <form id="daily-weight-form" method="post" action="" class="flex-form">
                <?php if(isset($errorMessage)) echo $errorMessage?>
                <div>
                    <div class="measurement-input-container">
                        <label for="waist">Waist</label><br>
                        <input type="text" inputmode="decimal" pattern="^\d*\.?\d*$" value="<?php echo $waist?>" maxlength="5" id="waist" name="waist" class="weight-form-input">
                        <div id="label-after">in</div>
                    </div>
                    <div class="measurement-input-container">
                        <label for="waist">Right Bicep</label><br>
                        <input type="text" inputmode="decimal" pattern="^\d*\.?\d*$" value="<?php echo $right_bicep?>" maxlength="5" id="right_bicep" name="right_bicep" class="weight-form-input">
                        <div id="label-after">in</div>
                    </div>
                    <div class="measurement-input-container">
                        <label for="waist">Left Bicep</label><br>
                        <input type="text" inputmode="decimal" pattern="^\d*\.?\d*$" value="<?php echo $left_bicep?>" maxlength="5" id="left_bicep" name="left_bicep" class="weight-form-input">
                        <div id="label-after">in</div>
                    </div>
                    <div class="measurement-input-container">
                        <label for="waist">Chest</label><br>
                        <input type="text" inputmode="decimal" pattern="^\d*\.?\d*$" value="<?php echo $chest?>" maxlength="5" id="chest" name="chest" class="weight-form-input">
                        <div id="label-after">in</div>
                    </div>
                </div>
                <div id="input-error" class="error" style="display: none">
                    <p style="display: block; margin: auto; text-align: center;">Oops! You forgot to fill out this field.<br>(There is only one, silly)</p>
                </div>
                <input type="submit" name="submit_button" id="weight-submit" value="Submit">
            </form>
        </div>
        <?php $weight->display_measurements()?>
    </div>
    <br><br><br>
</body>
</html>
<script>
    <?php
    if(!isset($_GET["pageno"])){
        echo 'document.getElementById("waist").focus();';
    }
    ?>
    for(const x of document.querySelectorAll(".delete-icon")){
        x.addEventListener("click", function(){
            let tr = x.parentElement.parentElement.parentElement.parentElement;
            tr.classList.toggle("swipe");
        });
    }
    function getScroll(id){
        let scroll = window.pageYOffset;
        window.location = "../delete.php?id = ";
    }
    document.querySelector("#dropdown-title h1").addEventListener("click", function(){
        document.querySelector("#dropdown-title-list").classList.toggle("hide-dropdown");
        document.querySelector("#dropdown-title-list").classList.toggle("show-dropdown");
    });
    window.addEventListener("click", function(e){
        if(document.querySelector("#dropdown-title-list").classList.contains("show-dropdown")){
            if(e.target != document.querySelector("#dropdown-title-list") && e.target != document.querySelector("#dropdown-title h1")){
                document.querySelector("#dropdown-title-list").classList.toggle("hide-dropdown");
                document.querySelector("#dropdown-title-list").classList.toggle("show-dropdown");
            }
        }
    });
</script>