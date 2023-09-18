<?php
session_start();
ini_set('display_errors', 'On');

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
    $dateEntered = date("Y-m-d");
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

// logout
if(isset($_POST["logout"])){
    $last_login = strtotime("-1 year");
    $last_login = date("Y-m-d H:i:s", $last_login);
    $findUser = $db->select("SELECT ip_addresses FROM users WHERE username = ?", "s", [$username]);
    if($findUser->num_rows > 0){
        while($row = $findUser->fetch_assoc()){
            $ip_addresses = unserialize($row["ip_addresses"]);
        }
        foreach($ip_addresses as &$ip){
            if($ip["ip_address"] == $_SERVER["REMOTE_ADDR"]){
                $ip["last_login"] = $last_login;
            }
        }
        unset($ip);
        $ip_addresses = serialize($ip_addresses);
        if($db->write("UPDATE users SET ip_addresses = ? WHERE username = ?", "ss", [$ip_addresses, $username])){
            session_unset();
            session_destroy();
            header("Location: login.php");
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
</head>
<body>
    <div class="large-input right" id="user-bar">
        <p class="inline" style="font-size: 15px; margin-right: 10px;">Howdy, <?php echo $username?></p>
        <form class="inline" method="POST" action="">
            <input type="submit" name="logout" value="Logout" id="logout">
        </form>
    </div>
    <div class="flex">
        <div>
            <h1 style="margin-top: 60px;" align="center">Daily Weight</h1>
            <form id="daily-weight-form" method="post" action="" class="flex-form">
                <?php if(isset($errorMessage)) echo $errorMessage?>
                <div>
                    <div class="input-container">
                        <input type="text" inputmode="decimal" value="<?php echo $getWeight?>" maxlength="5" id="weight" name="weight">
                        <div id="label-after">lbs</div>
                    </div>
                </div>
                <div id="input-error" class="error" style="display: none">
                    <p style="display: block; margin: auto; text-align: center;">Oops! You forgot to fill out this field.<br>(There is only one, silly)</p>
                </div>
                <input type="submit" name="weight-submit" id="weight-submit" value="Submit">
            </form>
        </div>
        <?php 
        $weight->display_weight($bodyFatDiv);?>
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
</script>