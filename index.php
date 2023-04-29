<?php
//ini_set('display_errors', 'On');
require "classes.php";
$db = new DB();
$weight = new Weight($db);

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST["weight"])){
        $getWeight = trim($_POST["weight"]);
        $getWeight = $weight->addDecimal($getWeight);
    }
    $dateEntered = date("Y-m-d");
    if(!$weight->insert_weight($getWeight, $dateEntered)){
        echo "<script>alert(`Weight could not be saved.`);</script>";
    }else{
        header("Location: index.php");
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
    <title>Daily Weight</title>
</head>
<body>
    <div class="flex">
        <div>
            <h1 align="center">Daily Weight</h1>
            <form id="weight-form" method="post" action="" class="flex-form">
                <div>
                    <div class="input-container">
                        <input type="text" required maxlength="5" pattern="\d*\.?\d*" id="weight" name="weight">
                        <div id="label-after">lbs</div>
                    </div>
                    <span class="error-msg">Weight must only contain numbers and up to 1 decimal</span>
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
        <?php $weight->display_weight();?>
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
    window.addEventListener("load", function(){
    <?php
    if(!isset($_GET["pageno"])){
        echo 'document.getElementById("weight").focus();';
    }else{
        echo "document.querySelector('#weight-history-title').scrollIntoView();";
    }
    ?>
    });

    function submitForm(){
        let weight = weightInput.value;
        weight = weight.trim();
        if(weight.length > 0){
            if(!weightInput.validity.patternMismatch){
                setTimeout(function(){document.querySelector('#weight-form').submit();}, 1000);
            }
        }else{
            console.log(weight);
            document.querySelector('#error').style.display = "block";
            document.querySelector('#error p').innerHTML = "Oops! You forgot to fill out this field.<br>(There is only one, silly)";
            document.querySelector('#submit-button').style.backgroundColor = 'red';
            setTimeout(function(){
                document.querySelector('.button').style.backgroundColor = '';
                document.querySelector('.button').classList.toggle('button__circle');
                document.querySelector('.tick').innerHTML = "Submit";}, 1000);
        }
    }
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
        }else{
            document.querySelector("#male").style.display = "none";
        }
    }
    function slideIn(){
        document.querySelector(".running img").classList.add("slide-in");
    }
</script>