<?php
ini_set('display_errors', 'On');
require "classes.php";
$db = new DB();
$weight = new Weight($db);

// initialize form field variables
$getWeight = "";
$gender = "";
$age = "";
$bodyFatWeight = "";
$thigh = "";
$chest = "";
$abdomen = "";
$triceps = "";
$suprailiac = "";
$bodyFatDiv = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
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
    }else{
        if(isset($_POST["weight"])){
            $getWeight = trim($_POST["weight"]);
            $getWeight = $weight->addDecimal($getWeight);
        }
        $dateEntered = date("Y-m-d");
        if(!$weight->insert_weight($getWeight, $dateEntered)){
            echo "<script>alert(`Weight could not be saved.`);</script>";
        }    
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/daily_weight.css" />
    <link rel="stylesheet" type="text/css" href="css/info-banner.css" />
    <link rel="stylesheet" type="text/css" href="css/submit-button.css" />
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <title>Daily Weight</title>
</head>
<body>
    <div class="flex">
        <div>
            <h1 align="center">Daily Weight</h1>
            <form id="weight-form" method="post" action="" class="flex-form">
                <div>
                    <div class="input-container">
                        <input type="text" inputmode="decimal" required maxlength="5" pattern="\d*\.?\d*" id="weight" name="weight">
                        <div id="label-after">lbs</div>
                    </div>
                </div>
                <div id="error" style="display: none">
                    <p style="display: block; margin: auto; text-align: center;">Oops! You forgot to fill out this field.<br>(There is only one, silly)</p>
                </div>
                <div id="submit-button" class="button" onclick="submitForm()">
                    <div class="container">
                        <div class="tick"></div>
                    </div>
                </div>
            </form>
        </div>
        <?php 
        $weight->display_weight($bodyFatDiv);?>
    </div>
</body>
</html>
<script src="submit-button.js"></script>
<script>
    let weightInput = document.querySelector("#weight");
    weightInput.addEventListener("keyup", function(e){
        if(weightInput.validity.patternMismatch){
            document.querySelector('#error').style.display = "block";
            document.querySelector('#error p').innerHTML = "Weight must only contain numbers and up to 1 decimal";
        }else{
            document.querySelector('#error').style.display = "none";
        }
    });

    weightInput.addEventListener("keydown", function(e){
        if(e.key == "Enter"){
            e.preventDefault();
            document.querySelector("#submit-button").click();
        }
    });

    function submitForm(){
        let weight = weightInput.value;
        weight = weight.trim();
        if(weight.length > 0){
            if(!weightInput.validity.patternMismatch){
                setTimeout(function(){document.querySelector('#weight-form').submit();}, 1000);
            }else{
                document.querySelector('#submit-button').style.backgroundColor = 'red';
                setTimeout(function(){
                    document.querySelector('.button').style.backgroundColor = '';
                    document.querySelector('.button').classList.toggle('button__circle');
                    document.querySelector('.tick').innerHTML = "Submit";
                }, 1000);
            }
        }else{
            console.log(weight);
            document.querySelector('#error').style.display = "block";
            document.querySelector('#error p').innerHTML = "Oops! You forgot to fill out this field.<br>(There is only one, silly)";
            document.querySelector('#submit-button').style.backgroundColor = 'red';
            setTimeout(function(){
                document.querySelector('.button').style.backgroundColor = '';
                document.querySelector('.button').classList.toggle('button__circle');
                document.querySelector('.tick').innerHTML = "Submit";
            }, 1000);
        }
    }
    window.addEventListener("load", function(){
    <?php
    if(!isset($_GET["pageno"])){
        echo 'document.getElementById("weight").focus();';
    }
    ?>
    });
    function showPopup(){
        let popup = document.getElementById("body-fat-popup");
        popup.style.display = "block";
        popup.style.zIndex = "10";
        popup.parentElement.style.zIndex = "5";
    }
    function closePopup(){
        let popup= document.getElementById("body-fat-popup");
        popup.style.display = "none";
        popup.style.zIndex = "-1";
        popup.parentElement.style.zIndex = "-1";
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
    function swipe(){
            this.parentElement.parentElement.classList.toggle("swipe");
    }
    document.querySelector(".average-container").addEventListener("click", function(){ 
        
    });
    for(const button of document.querySelectorAll(".confetti-button")){
        button.addEventListener("click", () => {
            button.style.pointerEvents = "none";
            for(const hr of document.querySelectorAll(".hr")){
                hr.style.display = "block"; 
            }
            for(const average of document.querySelectorAll(".average")){
                average.style.display = "block";
            }
            button.style.backgroundColor = "#ff7300";
            button.style.outline = "4px solid dodgerblue";
            button.style.border = "2px solid white";
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 },
                colors: ["#1e90ff", "#ff7300"]
            });
        });
    }
</script>