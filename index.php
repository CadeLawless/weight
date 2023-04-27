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
if(isset($_GET["pageno"])){
    echo "
    <script>
        window.addEventListener('load', function(){
            document.querySelector('#weight-history-title').scrollIntoView();
        });
    </script>";
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
                <div class="input-container">
                    <input type="text" id="weight" name="weight">
                    <div id="label-after">lbs</div>
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
    weightInput.addEventListener("keydown", function(e){
        let k = e.key;
        if(k == "Enter"){
            document.querySelector("#submit-button").click();
        }
        if(weightInput.value.length >= 5 && (k != 'Backspace' && k != "Delete")){
            e.preventDefault();
        }
        let allowedChars = [
            "Backspace",
            "Delete",
            "Control",
            "Tab"
        ];
        if(k == ".") {
            //Check if the text already contains the . character
            if(weightInput.value.includes(".")) {
                e.preventDefault();
            }
        }else{
            if(!allowedChars.includes(k)){
                let regex = /\D/g;
                if(regex.test(k)){
                    e.preventDefault();
                }
            }
        }
    });

    <?php
    if(!isset($_GET["pageno"])){
        echo '
        window.addEventListener("load", function(){
            document.getElementById("weight").focus();
        });';
    }
    ?>

    function submitForm(){
        let weight = document.querySelector("#weight").value;
        weight = weight.trim();
        if(weight.length > 0){
            setTimeout(function(){document.querySelector('#weight-form').submit();}, 1000);
        }else{
            console.log(weight);
            document.querySelector('#error').style.display = "block";
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