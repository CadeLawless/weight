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
                    $age = date_diff(date_create($date_of_birth), date_create('now'))->y;
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
    $findUser = $db->select("SELECT gender, date_of_birth FROM users WHERE username = ?", [$username]);
    if($findUser->num_rows > 0){
        while($row = $findUser->fetch_assoc()){
            $date_of_birth = $row["date_of_birth"];
            $age = date_diff(date_create($date_of_birth), date_create('now'))->y;
            $gender = $row["gender"];
            $male = $gender == "Male" ? true : false;
            $female = $gender == "Female" ? true : false;
        }
    }else{
        header("Location: login.php");
    }

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
$edit_body_fat_weight = "";
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

function patternCheck($regex, $input, &$errors, &$error_list, $inputName, $weight=false) {
    if($input != ""){
        if(!preg_match($regex, $input)){
            $errors = true;
            if($weight){
                $error_list .= "<li><em>Weight</em> must only contain numbers and up to 1 decimal</li>";
            }else{
                $error_list .= "<li><em>$inputName</em> may contain up to 2 whole numbers</li>";
            }
        }
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["submit_button"])){
        $errors = false;
        $errorTitle = "<p>The form could not be submitted due to the following errors:</p>";
        $errorList = "";

        $thigh = errorCheck("thigh", "Thigh", "Yes", $errors, $errorList);
        patternCheck("/^\d{1,2}$/", $thigh, $errors, $errorList, "Thigh");

        if($male){
            $chest = errorCheck("chest", "Chest", "Yes", $errors, $errorList);
            patternCheck("/^\d{1,2}$/", $chest, $errors, $errorList, "Chest");
            
            $abdomen = errorCheck("abdomen", "Abdomen", "Yes", $errors, $errorList);
            patternCheck("/^\d{1,2}$/", $abdomen, $errors, $errorList, "Abdomen");
        }

        if($female){
            $triceps = errorCheck("triceps", "Triceps", "Yes", $errors, $errorList);
            patternCheck("/^\d{1,2}$/", $triceps, $errors, $errorList, "Triceps");

            $suprailiac = errorCheck("suprailiac", "Suprailiac", "Yes", $errors, $errorList);
            patternCheck("/^\d{1,2}$/", $suprailiac, $errors, $errorList, "Suprailiac");
        }

        $body_fat_weight = errorCheck("body_fat_weight", "Weight", "Yes", $errors, $errorList);
        patternCheck("/^\d*\.?\d*$/", $body_fat_weight, $errors, $errorList, "Weight");
        if(!$errors){
            $body_fat_weight = $weight->addDecimal($body_fat_weight);
        }

        if(!$weightEnteredToday){
            $enter_weight = isset($_POST["enter_weight"]) ? "Yes" : "No";
        }

        $date_calculated = errorCheck("date_calculated", "Date", "Yes", $errors, $errorList);
        if($date_calculated > date("Y-m-d")){
            $errors = true;
            $errorList .= "<li>Date cannot be in the future. Please enter a valid date.</li>";
        }
        if(!$errors){
            if($male){
                $bodyFatPercentage = calculateBodyFatPercentage(male: true, thigh: $thigh, chest: $chest, abdomen: $abdomen, age: $age);
            }elseif($female){
                $bodyFatPercentage = calculateBodyFatPercentage(female: true, thigh: $thigh, triceps: $triceps, suprailiac: $suprailiac, age: $age);
            }
            $bodyFatMass = round(($bodyFatPercentage * $body_fat_weight / 100), 1);
            $leanBodyMass = round(($body_fat_weight - $bodyFatMass), 1);
            
            if(!$weightEnteredToday && $enter_weight == "Yes"){
                if(!$db->write("INSERT INTO daily_weight (username, pounds, date_weighed) VALUES (?, ?, ?)", [$username, $body_fat_weight, date("Y-m-d")])){
                    $errors = true;
                }
            }

            if(!$errors){
                if($male){
                    if($db->write("INSERT INTO daily_body_fat(username, thigh, chest, abdomen, weight, percentage, body_fat_mass, lean_body_mass, date_calculated) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)", [$username, $thigh, $chest, $abdomen, $body_fat_weight, $bodyFatPercentage, $bodyFatMass, $leanBodyMass, $date_calculated])){
                        header("Location: body-fat.php");
                    }else{
                        echo "<script>alert('Something went wrong while trying to record this body fat percentage')</script>";
                        // echo $db->error;
                    }
                }elseif($female){
                    if($db->write("INSERT INTO daily_body_fat(username, thigh, triceps, suprailiac, weight, percentage, body_fat_mass, lean_body_mass, date_calculated) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)", [$username, $thigh, $triceps, $suprailiac, $body_fat_weight, $bodyFatPercentage, $bodyFatMass, $leanBodyMass, $date_calculated])){
                        header("Location: body-fat.php");
                    }else{
                        echo "<script>alert('Something went wrong while trying to record this body fat percentage')</script>";
                        // echo $db->error;
                    }
                }
            }
        }else{
            $errorMessage = "<div class='error' style='width: 100%;'>$errorTitle<ul>$errorList</ul></div>";
        }
    }else{
        // edit submit php
        $findEntries = $db->select("SELECT id FROM daily_body_fat WHERE username = ?", [$username]);
        if($findEntries->num_rows > 0){
            while($row = $findEntries->fetch_assoc()){
                $id = $row["id"];
                if(isset($_POST["editButton$id"])){
                    $errors = false;
                    $errorTitle = "<p>The form could not be submitted due to the following errors:</p>";
                    $errorList = "";

                    $edit_thigh = errorCheck("{$id}_thigh", "Thigh", "Yes", $errors, $errorList);
                    patternCheck("/^\d{1,2}$/", $thigh, $errors, $errorList, "Thigh");
            
                    if($male){
                        $edit_chest = errorCheck("{$id}_chest", "Chest", "Yes", $errors, $errorList);
                        patternCheck("/^\d{1,2}$/", $chest, $errors, $errorList, "Chest");
                        
                        $edit_abdomen = errorCheck("{$id}_abdomen", "Abdomen", "Yes", $errors, $errorList);
                        patternCheck("/^\d{1,2}$/", $abdomen, $errors, $errorList, "Abdomen");
                    }
            
                    if($female){
                        $edit_triceps = errorCheck("{$id}_triceps", "Triceps", "Yes", $errors, $errorList);
                        patternCheck("/^\d{1,2}$/", $triceps, $errors, $errorList, "Triceps");
            
                        $edit_suprailiac = errorCheck("{$id}_suprailiac", "Suprailiac", "Yes", $errors, $errorList);
                        patternCheck("/^\d{1,2}$/", $suprailiac, $errors, $errorList, "Suprailiac");
                    }
            
                    $edit_body_fat_weight = errorCheck("{$id}_body_fat_weight", "Weight", "Yes", $errors, $errorList);
                    patternCheck("/^\d*\.?\d*$/", $body_fat_weight, $errors, $errorList, "Weight");
                        
                    $edit_date_calculated = errorCheck("{$id}_date_calculated", "Date", "Yes", $errors, $errorList);
                    if($edit_date_calculated > date("Y-m-d")){
                        $errors = true;
                        $errorList .= "<li>Date cannot be in the future. Please enter a valid date.</li>";
                    }
                    if(!$errors){
                        if($male){
                            $bodyFatPercentage = calculateBodyFatPercentage(male: true, thigh: $edit_thigh, chest: $edit_chest, abdomen: $edit_abdomen, age: $age);
                        }elseif($female){
                            $bodyFatPercentage = calculateBodyFatPercentage(female: true, thigh: $edit_thigh, triceps: $edit_triceps, suprailiac: $edit_suprailiac, age: $age);
                        }
                        $bodyFatMass = round(($bodyFatPercentage * $body_fat_weight / 100), 1);
                        $leanBodyMass = round(($body_fat_weight - $bodyFatMass), 1);
            
                        if($male){
                            if($db->write("UPDATE daily_body_fat SET thigh = ?, chest = ?, abdomen = ?, weight = ?, percentage = ?, body_fat_mass = ?, lean_body_mass = ?, date_calculated = ? WHERE id = ?", [$edit_thigh, $edit_chest, $edit_abdomen, $edit_body_fat_weight, $bodyFatPercentage, $bodyFatMass, $leanBodyMass, $edit_date_calculated, $id])){
                                $_SESSION["edit-success"] = "Successfully updated!";
                                header("Location: {$_SESSION["home"]}");
                            }else{
                                // $db->error
                                echo "<script>alert('Something went wrong while trying to update the entry');</script>";
                            }
                        }elseif($female){
                            if($db->write("UPDATE daily_body_fat SET thigh = ?, triceps = ?, suprailiac = ?, weight = ?, percentage = ?, body_fat_mass = ?, lean_body_mass = ?, date_calculated = ? WHERE id = ?", [$edit_thigh, $edit_triceps, $edit_suprailiac, $edit_body_fat_weight, $bodyFatPercentage, $bodyFatMass, $leanBodyMass, $edit_date_calculated, $id])){
                                $_SESSION["edit-success"] = "Successfully updated!";
                                header("Location: {$_SESSION["home"]}");
                            }else{
                                // $db->error
                                echo "<script>alert('Something went wrong while trying to update the entry');</script>";
                            }
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
                    <h1>Body Fat <span id="down-caret">&#8964;</span></h1>
                    <ul id="dropdown-title-list" class="hide-dropdown">
                        <li><a href="index.php">Weight</a></li>
                        <li><a href="measurements.php">Measurements</a></li>
                    </ul>
                </div>
            </div>
            <br>
            <form id="daily-weight-form" method="post" action="" class="flex-form">
                <?php if(isset($errorMessage)) echo $errorMessage?>
                <div>
                    <div class="measurement-input-container">
                        <label for="thigh">Thigh</label><br>
                        <input required type="text" inputmode="decimal" pattern="^\d{1,2}$" value="<?php echo $thigh?>" maxlength="2" id="thigh" name="thigh" class="weight-form-input">
                        <div id="label-after">mm</div>
                    </div>
                    <?php if($male){ ?>
                        <div class="measurement-input-container">
                            <label for="chest">Chest</label><br>
                            <input required type="text" inputmode="decimal" pattern="^\d{1,2}$" value="<?php echo $chest?>" maxlength="2" id="chest" name="chest" class="weight-form-input">
                            <div id="label-after">mm</div>
                        </div>
                        <div class="measurement-input-container">
                            <label for="abdomen">Abdomen</label><br>
                            <input required type="text" inputmode="decimal" pattern="^\d{1,2}$" value="<?php echo $abdomen?>" maxlength="2" id="abdomen" name="abdomen" class="weight-form-input">
                            <div id="label-after">mm</div>
                        </div>
                    <?php }elseif($female){ ?>
                        <div class="measurement-input-container">
                            <label for="triceps">Triceps</label><br>
                            <input required type="text" inputmode="decimal" pattern="^\d{1,2}$" value="<?php echo $triceps?>" maxlength="2" id="triceps" name="triceps" class="weight-form-input">
                            <div id="label-after">mm</div>
                        </div>
                        <div class="measurement-input-container">
                            <label for="suprailiac">Suprailiac</label><br>
                            <input required type="text" inputmode="decimal" pattern="^\d{1,2}$" value="<?php echo $suprailiac?>" maxlength="2" id="suprailiac" name="suprailiac" class="weight-form-input">
                            <div id="label-after">mm</div>
                        </div>
                    <?php } ?>
                    <div class="measurement-input-container" style="<?php if(!$weightEnteredToday) echo "margin-bottom: 85px;"; ?>">
                        <label for="body_fat_weight">Weight</label><br>
                        <input required type="text" inputmode="decimal" pattern="^\d*\.?\d*$" value="<?php echo $body_fat_weight?>" maxlength="5" id="body_fat_weight" name="body_fat_weight" class="weight-form-input">
                        <div id="label-after">lbs</div>
                        <?php if(!$weightEnteredToday){ ?>
                            <div class="center">
                                <input style="margin-top: 10px;" type="checkbox" name="enter_weight" id="enter_weight" <?php if($enter_weight == "Yes") echo "checked"; ?> />
                                <label style="font-size: 16px;" for="enter_weight">Add as weight entry for today</label>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="center">
                        <label for="date_calculated">Date: </label>
                        <input required type="date" name="date_calculated" id="date_calculated" value="<?php echo $date_calculated; ?>" />
                    </div>
                </div>
                <input type="submit" name="submit_button" id="weight-submit" value="Submit">
            </form>
        </div>
        <?php 
        if($male){
            $weight->display_body_fat(male: true, edit_error_id: $edit_error_id, edit_error_msg: $edit_error_msg, edit_input_array: ["thigh" => $edit_thigh, "chest" => $edit_chest, "abdomen" => $edit_abdomen, "weight" => $edit_body_fat_weight, "date_calculated" => $edit_date_calculated]);
        }elseif($female){
            $weight->display_body_fat(female: true, edit_error_id: $edit_error_id, edit_error_msg: $edit_error_msg, edit_input_array: ["thigh" => $edit_thigh, "triceps" => $edit_triceps, "suprailiac" => $edit_suprailiac, "weight" => $edit_body_fat_weight, "date_calculated" => $edit_date_calculated]);
        }
        ?>
    </div>
    <br><br><br>
</body>
</html>
<script src="includes/dropdown-title.js"></script>
<script>
    <?php
    if(!isset($_GET["pageno"])){
        echo 'document.getElementById("thigh").focus();';
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