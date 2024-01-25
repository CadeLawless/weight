<?php
session_start();
ini_set('display_errors', 'On');

// database connections
require "classes.php";
$db = new DB();
$weight = new Weight($db);

// check if logged in
if(!isset($_SESSION["logged_in"])){
    if(isset($_COOKIE["session_id"])){
        $session = $_COOKIE["session_id"];
        $findUser = $db->select("SELECT username, session_expiration FROM users WHERE session = ?", "s", [$session]);
        if($findUser->num_rows > 0){
            while($row = $findUser->fetch_assoc()){
                $session_expiration = $row["session_expiration"];
                if(date("Y-m-d H:i:s") < $session_expiration){
                    $username = $row["username"];
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

// initialize form field variables
$getWeight = "";
$dateEntered = date("Y-m-d");
$gender = "";
$age = "";
$bodyFatWeight = "";
$thigh = "";
$chest = "";
$abdomen = "";
$triceps = "";
$suprailiac = "";
$bodyFatDiv = "";

if (isset($_POST["weight-submit"])){
    $errors = false;
    $errorTitle = "<p>The form could not be submitted due to the following errors:</p>";
    $errorList = "";
    if(isset($_POST["weight"])){
        $getWeight = trim($_POST["weight"]);
        if(preg_match("/^\d*\.?\d*$/", $getWeight)){
            $getWeight = $weight->addDecimal($getWeight);
        }else{
            $errors = true;
            $errorList .= "<li>Weight must only contain numbers and up to 1 decimal</li>";
        }
    }else{
        $errors = true;
        $errorList .= "<li>Weight is a required field</li>";
    }
    date_default_timezone_set("America/Chicago");
    $dateEntered = $_POST["date_entered"] ?? "";
    if($dateEntered == ""){
        $errors = true;
        $errorList .= "<li>Date is a required field. Please fill it out.</li>";
    }
    if($dateEntered > date("Y-m-d")){
        $errors = true;
        $errorList .= "<li>Date cannot be in the future. Please enter a valid date.</li>";
    }
    if(!$errors){
        if(!$weight->insert_weight($getWeight, $dateEntered)){
            echo "<script>alert(`Weight could not be saved.`);</script>";
        }else{
            header("Location: index.php");
        } 
    }else{
        $errorMessage = "<div class='error' style='width: 100%;'>$errorTitle<ul>$errorList</ul></div>";
    }
}
if(isset($_POST["body-fat-submit"])){
    $gender = trim($_POST["gender"]);
    $age = trim($_POST["age"]);
    $bodyFatWeight = trim($_POST["body-fat-weight"]);
    $thigh = trim($_POST["thigh"]);
    $chest = trim($_POST["chest"]);
    $abdomen = trim($_POST["abdomen"]);
    $triceps = trim($_POST["triceps"]);
    $suprailiac = trim($_POST["suprailiac"]);

    /*
    For females:
    D = (1.0994921 - (0.0009929 x (Triceps + Thigh + Suprailiac)) + (0.0000023 x (Triceps + Thigh + Suprailiac)2) - (0.0001392 x Age))

    For males:
    D = (1.10938 - (0.0008267 x (Thigh + Chest + Abdomen)) + (0.0000016 x (Thigh + Chest + Abdomen)2) - (0.000257 x Age))

    Body density is transformed in fat percentage with the SIRI formula:
    BF% = 495/ D - 450
    Body fat is obtained from the BF% and subject weight based on:
    Body fat mass = BF% x Weight / 100
    Lean body mass = Weight – Body fat mass
    */

    if($gender == "male"){
        $density = (1.10938 - (0.0008267 * ($thigh + $chest + $abdomen)) + (0.0000016 * pow(($thigh + $chest + $abdomen), 2)) - (0.000257 * $age));
    }else{
        $density = (1.0994921 - (0.0009929 * ($triceps + $thigh + $suprailiac)) + (0.0000023 * pow(($triceps + $thigh + $suprailiac), 2)) - (0.0001392 * $age));
    }
    $bodyFatPercentage = round((495 / $density - 450), 1);
    $bodyFatMass = round(($bodyFatPercentage * $bodyFatWeight / 100), 1);
    $leanBodyMass = round(($bodyFatWeight - $bodyFatMass), 1);
    $bodyFatDiv = "
    <div class='body-fat-result-container'>
        <h3 onclick='slideIn(); this.style.display = \"none\";'>Click here to reveal your Body Fat Percentage</h3>
        <div style='display: none' class='body-fat-result'>
            <div>
                <label>Body Fat Percentage: </label>$bodyFatPercentage%<br>
                <label>Body Fat Mass: </label>$bodyFatMass lbs<br>
                <label>Lean Body Mass: </label>$leanBodyMass lbs
            </div>
        </div>
        <div class='running-img-container'>
            <img src='images/running-stickman.gif'>
        </div>
    </div>";
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
        @media (max-width: 325px) {
            #title-overlay {
                height: 190px;
            }
            #title-start {
                margin-bottom: 0;
                display: block !important;
            }
            #dropdown-title h1 {
                margin-top: 0;
                display: inline-block;
                margin-left: auto;
                margin-right: auto;
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
                    <h1>Weight <span id="down-caret">&#8964;</span></h1>
                    <ul id="dropdown-title-list" class="hide-dropdown">
                        <li><a href="measurements.php">Measurements</a></li>
                        <li><a href="body-fat.php">Body Fat %</a></li>
                    </ul>
                </div>
            </div>
            <br>
            <form id="daily-weight-form" method="post" action="" class="flex-form">
                <?php if(isset($errorMessage)) echo $errorMessage?>
                <div>
                    <div class="input-container">
                        <input type="text" inputmode="decimal" value="<?php echo $getWeight; ?>" maxlength="5" id="weight" name="weight">
                        <div id="label-after">lbs</div>
                    </div>
                </div>
                <div id="input-error" class="error" style="display: none">
                    <p style="display: block; margin: auto; text-align: center;">Oops! You forgot to fill out this field.<br>(There is only one, silly)</p>
                </div>
                <div>
                    <label for="date_entered">Date: </label>
                    <input required type="date" name="date_entered" id="date_entered" value="<?php echo $dateEntered; ?>" />
                </div>
                <input type="submit" name="weight-submit" id="weight-submit" value="Submit">
            </form>
        </div>
        <?php $weight->display_weight($bodyFatDiv);?>
    </div>
    <br><br><br>
</body>
</html>
<script src="submit-button.js"></script>
<script>
    let weightInput = document.querySelector("#weight");
    weightInput.addEventListener("keyup", function(e){
        if(/^\d*\.?\d*$/.test(weightInput.value) === false){
            document.querySelector('#input-error').style.display = "block";
            document.querySelector('#input-error p').innerHTML = "Weight must only contain numbers and up to 1 decimal";
        }else{
            document.querySelector('#input-error').style.display = "none";
        }
    });

    document.querySelector("#daily-weight-form").addEventListener("submit", function(e){
        let weight = weightInput.value;
        weight = weight.trim();
        if(weight.length > 0){
            if(/^\d*\.?\d*$/.test(weight) === false){
                e.preventDefault();
                document.querySelector('#weight-submit').classList.add("shake");
                setTimeout(function(){
                    document.querySelector('#weight-submit').classList.remove("shake");
                }, 1000);
            }
        }else{
            e.preventDefault();
            console.log(weight);
            document.querySelector('#weight-submit').classList.add("shake");
            document.querySelector('#input-error').style.display = "block";
            document.querySelector('#input-error p').innerHTML = "Oops! You forgot to fill out this field.<br>(There is only one, silly)";
            setTimeout(function(){
                document.querySelector('#weight-submit').classList.remove("shake");
            }, 1000);
        }
    });
    window.addEventListener("load", function(){
    <?php
    if(!isset($_GET["pageno"])){
        echo 'document.getElementById("weight").focus();';
    }
    ?>
    });
    // open edit popup for specified weight entry on click of edit button
    for(const btn of document.querySelectorAll(".popup-button")){
        btn.addEventListener("click", function(e){
            e.preventDefault();
            if(btn.classList.contains("body-fat-button")){
                btn.parentElement.nextElementSibling.classList.remove("hidden");
            }else{
                btn.nextElementSibling.classList.remove("hidden");
            }
        });
    }

    // close popup on click of x or no button
    for(const x of document.querySelectorAll(".close-button")){
        x.addEventListener("click", function(){
            x.parentElement.parentElement.parentElement.classList.add("hidden");
        })
    }
    function showGender(){
        document.querySelector("#restOfForm").style.display = "block";
        document.getElementById(this.value).style.display = "block";
        if(this.value == "male"){
            document.querySelector("#female").style.display = "none";
            for(const input of document.querySelectorAll("#male input")){
                input.setAttribute("required", "");
            }
            for(const input of document.querySelectorAll("#female input")){
                if(input.hasAttribute("required")){
                    input.removeAttribute("required");
                }
            }
        }else{
            document.querySelector("#male").style.display = "none";
            for(const input of document.querySelectorAll("#female input")){
                input.setAttribute("required", "");
            }
            for(const input of document.querySelectorAll("#male input")){
                if(input.hasAttribute("required")){
                    input.removeAttribute("required");
                }
            }
        }
    }
    function slideIn(){
        document.querySelector(".running-img-container").classList.add("slide-in");
        document.querySelector(".body-fat-result").style.display = "flex";
    }
    for(const x of document.querySelectorAll(".delete-icon")){
        x.addEventListener("click", function(){
            let tr = x.parentElement.parentElement.parentElement.parentElement;
            tr.classList.toggle("swipe");
        });
    }
    for(const button of document.querySelectorAll(".confetti-button")){
        button.addEventListener("click", () => {
            button.style.pointerEvents = "none";
            for(const hr of document.querySelectorAll(".hr")){
                hr.style.display = "block"; 
            }
            for(const average of document.querySelectorAll(".average")){
                average.style.display = "block";
            }
            var windowWidth = window.innerWidth;
            var windowHeight = window.innerHeight;
            let position = button.getBoundingClientRect();
            let left = position.left;
            let top = position.top;
            let centerX = left + button.offsetWidth / 2;
            centerX = centerX / windowWidth * 100;
            let centerY = top + button.offsetHeight / 2;
            centerY = centerY / windowHeight * 100;
            console.log(centerX, centerY);
            button.style.backgroundColor = "#ff7300";
            button.style.boxShadow = "0 0 0 4px dodgerblue";
            button.style.border = "2px solid white";
            confetti("tsparticles", {
                angle: 90,
                count: 75,
                position: {
                    x: centerX,
                    y: centerY,
                },
                spread: 60,
                startVelocity: 45,
                decay: 0.9,
                gravity: 1,
                drift: 0,
                ticks: 200,
                colors: ["#1e90ff", "#ff7300"],
                shapes: ["image"],
                shapeOptions: {
                    image: [{
                        src: "images/dumbbell-blue.png",
                        width: 100,
                        height: 100,
                    },
                    {
                        src: "images/dumbbell-orange.png",
                        width: 100,
                        height: 100,
                    },
                    ],
                },
                scalar: 3,
                zIndex: 100,
                disableForReducedMotion: true,
            });
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