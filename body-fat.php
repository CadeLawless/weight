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
    if(isset($_COOKIE["session_id"])){
        $session = $_COOKIE["session_id"];
        $findUser = $db->select("SELECT username, gender, date_of_birth, session_expiration FROM users WHERE session = ?", [$session]);
        if($findUser->num_rows > 0){
            while($row = $findUser->fetch_assoc()){
                $session_expiration = $row["session_expiration"];
                if(date("Y-m-d H:i:s") < $session_expiration){
                    $username = $row["username"];
                    $date_of_birth = $row["date_of_birth"];
                    $gender = $row["gender"];
                    $male = $gender == "Male" ? true : false;
                    $female = $gender == "Female" ? true : false;
                    $_SESSION["logged_in"] = true;
                    $_SESSION["username"] = $username;
                }else{
                    header("Location: login.php");
                }
            }
        }else{
            header("Location: login.php");
        }
    }else{
        header("Location: login.php");
    }
}else{
    $username = $_SESSION["username"];
}

$pageno = $_GET["pageno"] ?? 1;
$_SESSION["home"] = "body-fat.php?pageno=$pageno#weight-history-title";

// initialize form field variables
$thigh = "";
if($male){
    $chest = "";
    $abdomen = "";
}
if($female){
    $triceps = "";
    $suprailiac = "";
}
$body_fat_weight = "";
// check to see if user has weighed today
$findWeightToday = $db->select("SELECT pounds FROM daily_weight WHERE username = ? AND date_weighed = ?", [$username, date("Y-m-d")]);
$weightEnteredToday = $findWeightToday->num_rows > 0 ? true : false;
if($weightEnteredToday){
    while($row = $findWeightToday->fetch_assoc()){
        $body_fat_weight = $row["pounds"];
    }
}else{
    $enter_weight = "No";
}
$date_calculated = date("Y-m-d");

// function that calculates body fat
function calculateBodyFatPercentage($male=false, $female=false, $thigh="", $chest="", $abdomen="", $triceps="", $suprailiac="", $age=""){
    if($male){
        $density = (1.10938 - (0.0008267 * ($thigh + $chest + $abdomen)) + (0.0000016 * pow(($thigh + $chest + $abdomen), 2)) - (0.000257 * $age));
    }
    if($female){
        $density = (1.0994921 - (0.0009929 * ($triceps + $thigh + $suprailiac)) + (0.0000023 * pow(($triceps + $thigh + $suprailiac), 2)) - (0.0001392 * $age));
    }
    $bodyFatPercentage = round((495 / $density - 450), 1);
    return $bodyFatPercentage;
}

$edit_error_id = "";
$edit_error_msg = "";
$edit_thigh = "";
if($male){
    $edit_chest = "";
    $edit_abdomen = "";
}
if($female){
    $edit_triceps = "";
    $edit_suprailiac = "";
}
$edit_date_calculated = "";

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

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["submit_button"])){
        $errors = false;
        $errorTitle = "<p>The form could not be submitted due to the following errors:</p>";
        $errorList = "";
        $thigh = errorCheck("thigh", "Thigh", "Yes", $errors, $errorList);
        if($male){
            $chest = errorCheck("chesat", "Chest", "Yes", $errors, $errorList);
            $abdomen = errorCheck("abdomen", "Abdomen", "Yes", $errors, $errorList);
        }
        if($female){
            $triceps = errorCheck("triceps", "Triceps", "Yes", $errors, $errorList);
            $suprailiac = errorCheck("suprailiac", "Suprailiac", "Yes", $errors, $errorList);
        }
        $body_fat_weight = errorCheck("body_fat_weight", "Weight", "Yes", $errors, $errorList);
        patternCheck("/^\d*\.?\d*$/", $waist, $errors, $errorList, "Waist");
        patternCheck("/^\d*\.?\d*$/", $right_bicep, $errors, $errorList, "Right Bicep");
        patternCheck("/^\d*\.?\d*$/", $left_bicep, $errors, $errorList, "Left Bicep");
        patternCheck("/^\d*\.?\d*$/", $chest, $errors, $errorList, "Chest");
        $date_measured = errorCheck("date_measured", "Date Measured", "Yes", $errors, $errorList);
        if($date_measured > date("Y-m-d")){
            $errors = true;
            $errorList .= "<li>Date cannot be in the future. Please enter a valid date.</li>";
        }
        if(!$errors){
            if($db->write("INSERT INTO daily_measurements(username, waist, right_bicep, left_bicep, chest, date_measured) VALUES(?, ?, ?, ?, ?, ?)", [$username, $waist, $right_bicep, $left_bicep, $chest, $date_measured])){
                header("Location: measurements.php");
            }else{
                echo "<script>alert('Something went wrong while trying to record these measurements')</script>";
                // echo $db->error;
            }
        }else{
            $errorMessage = "<div class='error' style='width: 100%;'>$errorTitle<ul>$errorList</ul></div>";
        }
    }else{
        // edit submit php
        $findEntries = $db->select("SELECT id FROM daily_measurements WHERE username = ?", [$username]);
        if($findEntries->num_rows > 0){
            while($row = $findEntries->fetch_assoc()){
                $id = $row["id"];
                if(isset($_POST["editButton$id"])){
                    $errors = false;
                    $errorTitle = "<p>The form could not be submitted due to the following errors:</p>";
                    $errorList = "";
                    $edit_waist = errorCheck("{$id}_waist", "Waist", "Yes", $errors, $errorList);
                    $edit_right_bicep = errorCheck("{$id}_right_bicep", "Right Bicep", "Yes", $errors, $errorList);
                    $edit_left_bicep = errorCheck("{$id}_left_bicep", "Left Bicep", "Yes", $errors, $errorList);
                    $edit_chest = errorCheck("{$id}_chest", "Chest", "Yes", $errors, $errorList);
                    patternCheck("/^\d*\.?\d*$/", $edit_waist, $errors, $errorList, "Waist");
                    patternCheck("/^\d*\.?\d*$/", $edit_right_bicep, $errors, $errorList, "Right Bicep");
                    patternCheck("/^\d*\.?\d*$/", $edit_left_bicep, $errors, $errorList, "Left Bicep");
                    patternCheck("/^\d*\.?\d*$/", $edit_chest, $errors, $errorList, "Chest");
                    $edit_date_measured = errorCheck("{$id}_date_measured", "Date Measured", "Yes", $errors, $errorList);
                    if($edit_date_measured > date("Y-m-d")){
                        $errors = true;
                        $errorList .= "<li>Date cannot be in the future. Please enter a valid date.</li>";
                    }            
                    if(!$errors){
                        if($db->write("UPDATE daily_measurements SET waist = ?, right_bicep = ?, left_bicep = ?, chest = ?, date_measured = ? WHERE id = ?", [$edit_waist, $edit_right_bicep, $edit_left_bicep, $edit_chest, $edit_date_measured, $id])){
                            $_SESSION["edit-success"] = "Successfully updated!";
                            header("Location: {$_SESSION["home"]}");
                        }else{
                            // $db->error
                            echo "<script>alert('Something went wrong while trying to update the entry');</script>";
                        }
                    }else{
                        $edit_error_id = $id;
                        $edit_error_msg = "<div class='error'>$errorTitle<ul>$errorList</ul></div>";
                    }
                }
            }
        }
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
                        <input required type="text" inputmode="decimal" pattern="^\d*\.?\d*$" value="<?php echo $waist?>" maxlength="5" id="waist" name="waist" class="weight-form-input">
                        <div id="label-after">in</div>
                    </div>
                    <div class="measurement-input-container">
                        <label for="waist">Right Bicep</label><br>
                        <input required type="text" inputmode="decimal" pattern="^\d*\.?\d*$" value="<?php echo $right_bicep?>" maxlength="5" id="right_bicep" name="right_bicep" class="weight-form-input">
                        <div id="label-after">in</div>
                    </div>
                    <div class="measurement-input-container">
                        <label for="waist">Left Bicep</label><br>
                        <input required type="text" inputmode="decimal" pattern="^\d*\.?\d*$" value="<?php echo $left_bicep?>" maxlength="5" id="left_bicep" name="left_bicep" class="weight-form-input">
                        <div id="label-after">in</div>
                    </div>
                    <div class="measurement-input-container">
                        <label for="waist">Chest</label><br>
                        <input required type="text" inputmode="decimal" pattern="^\d*\.?\d*$" value="<?php echo $chest?>" maxlength="5" id="chest" name="chest" class="weight-form-input">
                        <div id="label-after">in</div>
                    </div>
                    <div class="center">
                        <label for="date_measured">Date: </label>
                        <input required required type="date" name="date_measured" id="date_measured" value="<?php echo $date_measured; ?>" />
                    </div>
                </div>
                <input type="submit" name="submit_button" id="weight-submit" value="Submit">
            </form>
        </div>
        <?php $weight->display_measurements($edit_error_id, $edit_error_msg, ["waist" => $edit_waist, "right_bicep" => $edit_right_bicep, "left_bicep" => $edit_left_bicep, "chest" => $edit_chest, "date_measured" => $edit_date_measured]); ?>
    </div>
    <br><br><br>
</body>
</html>
<script src="includes/dropdown-title.js"></script>
<script>
    <?php
    if(!isset($_GET["pageno"])){
        echo 'document.getElementById("waist").focus();';
    }
    ?>
    // open edit popup for specified weight entry on click of edit button
    for(const btn of document.querySelectorAll(".popup-button")){
        btn.addEventListener("click", function(e){
            e.preventDefault();
            if(btn.classList.contains("body-fat-button")){
                btn.parentElement.nextElementSibling.classList.remove("hidden");
                btn.parentElement.nextElementSibling.firstElementChild.classList.add("active");
            }else{
                btn.nextElementSibling.classList.remove("hidden");
                btn.nextElementSibling.firstElementChild.classList.add("active");
            }
        });
    }

    // close popup on click of x or no button
    for(const x of document.querySelectorAll(".close-button, .no-button")){
        x.addEventListener("click", function(){
            x.closest(".popup-container").classList.add("hidden");
        })
    }

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
</script>