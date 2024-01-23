<?php
class DB{
    private $connection;
    private $servername = "localhost";
    private $username = "root";
    private $password = "millieBean0514";
    private $database = "weight";

    public function __construct()
    {
        $this->connection = new mysqli($this->servername, $this->username, $this->password, $this->database);
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
    }
    public function query($query){
        return $this->connection->query($query);
    }

    public function error(){
        return $this->connection->error;
    }

    public function getConnection(){
        return $this->connection;
    }

    // parameterized mysqli select statement
    public function select($sql, $types, $values){
        if($selectStatement = $this->connection->prepare($sql)){
            $selectStatement->bind_param($types, ...$values);
            $selectStatement->execute();
            return $selectStatement->get_result();
        }else{
            //echo $db->error;
            return false;
        }
    }

    // parameterized mysqli write (insert, update, delete) statement
    public function write($sql, $types, $values){
        if($writeStatement = $this->connection->prepare($sql)){
            $writeStatement->bind_param($types, ...$values);
            if($writeStatement->execute()){
                return true;
            }else{
                //echo $writeStatement->error;
                return false;
            }
        }else{
            return false;
        }
    }
}
class Pagination {
    public static function paginate($type, $db, $query, $itemsPerPage, $pageNumber, $bodyFatDiv=""){
        $offset = ($pageNumber - 1) * $itemsPerPage;
        $selectQuery = $db->query("$query LIMIT $offset, $itemsPerPage");
        if($selectQuery->num_rows > 0){
            echo "<div class='horizontal-line'></div>";
            if($type == "Weight"){
                echo "<div class='flex flex-row'>
                <div class='button-wrapper mobile-button-wrapper'>
                    <div class='average-container circle-container confetti-button'>
                        <div>
                            <h3 id='average-title'>This Week's Average</h3>
                            <div class='hr' style='display: none'></div>
                        </div>
                        <div class='average'>";
                        $totalWeight = 0;
                        $totalRecords = 0;
                        $findAverage = $db->query("$query LIMIT 7");
                        if($findAverage->num_rows > 0){
                            while($row = $findAverage->fetch_assoc()){
                                $lbs = $row["pounds"];
                                $totalWeight += floatval($lbs);
                                $totalRecords += 1;
                            }
                            $average = $totalWeight / $totalRecords;
                            echo "<h3>" . round($average, 1) . " lbs</h3>";
                        }
                        echo "
                        </div>
                    </div>
                    <div class='average-container circle-container' style='background-color: #ff7300'>
                        <div>
                            <h3 id='average-title'>Last Week's Average</h3>
                            <div class='hr'></div>
                        </div>";
                        $lastTotalWeight = 0;
                        $lastTotalRecords = 0;
                        $findLastAverage = $db->query("$query LIMIT 7, 7");
                        if($findLastAverage->num_rows > 0){
                            while($row = $findLastAverage->fetch_assoc()){
                                $lastLbs = $row["pounds"];
                                $lastTotalWeight += floatval($lastLbs);
                                $lastTotalRecords += 1;
                            }
                            $lastAverage = $lastTotalWeight / $lastTotalRecords;
                            echo "<h3>" . round($lastAverage, 1) . " lbs</h3>";
                        }
                        echo "
                    </div>
                    </div>
                    <div>
                    <div class='body-fat-percentage'>
                        <a class='body-fat-button' onClick='showPopup()'>Calculate Body Fat Percentage</a>
                    </div>";
                    if($bodyFatDiv != "") echo $bodyFatDiv;
                    echo "
                    <div class = 'flex-popup'>
                        <div class = 'popup' id='body-fat-popup'>
                            <img onclick='closePopup()' src='images/close.png' class='close'>
                            <h3 style='text-align: center'>Body Fat Percentage Calculator</h3>
                            <form id='body-fat-form' method='POST' action=''>
                                <label>Gender: <br></label>
                                <select name='gender' onChange='showGender.call(this)'>
                                    <option selected disabled>Select an option</option>
                                    <option value='male'>Male</option>
                                    <option value='female'>Female</option>
                                </select><br><br>
                                <div id='restOfForm' style='display: none'>
                                    <label>Age: <br></label>
                                    <input type='text' inputmode='numeric' name='age' step='any'><br><br>
                                    <label>Weight: <br></label>
                                    <input type='text' inputmode='decimal' name='body-fat-weight'> <span>lbs</span><br><br>
                                    <label>Thigh: <br></label>
                                    <input type='text' inputmode='decimal' name='thigh'> <span>mm</span><br><br>
                                    <div id='male' style='display: none'>
                                        <label>Chest: <br></label>
                                        <input type='text' inputmode='decimal' name='chest'> <span>mm</span><br><br>
                                        <label>Abdomen: <br></label>
                                        <input type='text' inputmode='decimal' name='abdomen'> <span>mm</span><br><br>
                                    </div>
                                    <div id='female' style='display: none'>
                                        <label>Triceps: <br></label>
                                        <input type='text' inputmode='decimal' name='triceps'> <span>mm</span><br><br>
                                        <label>Suprailiac: <br></label>
                                        <input type='text' inputmode='decimal' name='suprailiac'> <span>mm</span><br><br>
                                    </div>
                                    <p align='center'><input type='submit' id='body-fat-submit' name='body-fat-submit' value='Calculate'></p>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class='desktop-flex-row'>
                    <div class='button-wrapper desktop-button-wrapper'>
                    <div class='average-container circle-container confetti-button'>
                        <div>
                            <h3 id='average-title'>This Week's Average</h3>
                            <div class='hr' style='display: none'></div>
                        </div>
                        <div class='average'>";
                        $totalWeight = 0;
                        $totalRecords = 0;
                        $findAverage = $db->query("$query LIMIT 7");
                        if($findAverage->num_rows > 0){
                            while($row = $findAverage->fetch_assoc()){
                                $lbs = $row["pounds"];
                                $totalWeight += floatval($lbs);
                                $totalRecords += 1;
                            }
                            $average = $totalWeight / $totalRecords;
                            echo "<h3>" . round($average, 1) . " lbs</h3>";
                        }
                        echo "
                        </div>
                    </div>
                    <div class='average-container circle-container' style='background-color: #ff7300'>
                        <div>
                            <h3 id='average-title'>Last Week's Average</h3>
                            <div class='hr'></div>
                        </div>";
                        $lastTotalWeight = 0;
                        $lastTotalRecords = 0;
                        $findLastAverage = $db->query("$query LIMIT 7, 7");
                        if($findLastAverage->num_rows > 0){
                            while($row = $findLastAverage->fetch_assoc()){
                                $lastLbs = $row["pounds"];
                                $lastTotalWeight += floatval($lastLbs)
                                ;
                                $lastTotalRecords += 1;
                            }
                            $lastAverage = $lastTotalWeight / $lastTotalRecords;
                            echo "<h3>" . round($lastAverage, 1) . " lbs</h3>";
                        }
                        echo "
                    </div>
                    </div>
                    <div class='history'>
                        <h2 id='weight-history-title' align='center'>Weight History</h2>
                        <div id='table'>
                            <table>
                                <thead>
                                    <tr>
                                        <th style='width: 40%;'>Date Weighed</th>
                                        <th>Weight</th>
                                        <td style='width: 10%;'></td>
                                        <td style='width: 10%;'></td>
                                    </tr>
                                </thead>
                            </table>";
                    while($row=$selectQuery->fetch_assoc()){
                        $dateWeighed = htmlspecialchars(date("m/d/y", strtotime($row["date_weighed"])));
                        $weight = htmlspecialchars($row["pounds"]);
                        $id = htmlspecialchars($row["id"]);
                        echo "
                            <table class='swipe-row'>
                                <tbody>
                                    <tr>
                                        <td style='width: 40%; padding: 0 15px;'>$dateWeighed</td>
                                        <td style='padding: 0 15px;'>$weight lbs</td>
                                        <td style='width: 10%; text-align: center;'><img class='edit-button' id='$id' src='images/edit.png' style='cursor: pointer; width: 15px; height: 15px;'></td>
                                        <div class='popup-container edit-popup-$id flex hidden'>
                                            <div class='popup flex'>
                                                <div class='close-container'>
                                                    <img src='images/site-images/close.png' class='close-button'>
                                                </div>
                                                <div>
                                                    <form method='POST' action=''>
                                                        <label for='date_weighed$id'>Date Weighed:<br></label>
                                                        <input type='date' value='{$row["date_weighed"]}' id='date_weighed$id' name='{$id}_date_weighed'><br>
                                                        <label for='weight$id'>Weight:<br></label>
                                                        <div class='input-container'>
                                                            <input type='text' inputmode='decimal' value='$weight' maxlength='5' id='weight$id' name='{$id}_weight'> <span>lbs</span>
                                                        </div>
                                                        <p class='center'><input type='submit' name='editButton$id' class='edit_submit'></p>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <td style='width: 10%; text-align: center;'><img class='delete-icon' src='images/delete.png' style='cursor: pointer; width: 15px; height: 15px;'></td>
                                        <td class='delete'><a href='delete.php?id=$id' class='delete-button'>Delete</a></td>
                                    </tr>
                                </tbody>
                            </table>";
                    }
                    echo "</div>";
                }else if($type == "Measurements"){
                    $allMeasurements = $selectQuery->fetch_all(MYSQLI_ASSOC);
                    echo "
                    <div class='history'>
                        <h2 id='weight-history-title' align='center'>Measurements History</h2>
                        <div id='table' class='measurements-table'>
                            <table>
                                <thead>
                                    <tr>
                                        <th class='blank-td'></th>";
                                        foreach($allMeasurements as $row){
                                            $dateMeasured = date("m/d/Y", strtotime($row["date_measured"]));
                                            echo "<th>$dateMeasured</th>";
                                        }
                                        echo "
                                    </tr>
                                </thead>
                            </table>
                            <table class='swipe-row'>
                                <tbody>
                                    <tr>
                                        <th>Waist</th>";
                                        foreach($allMeasurements as $row){
                                            $waist = $row["waist"];
                                            echo "<td>$waist</td>";
                                        }
                                        echo "
                                    </tr>
                                </tbody>
                            </table>
                            <table class='swipe-row'>
                                <tbody>
                                    <tr>
                                        <th>Right Bicep</th>";
                                        foreach($allMeasurements as $row){
                                            $rightBicep = $row["right_bicep"];
                                            echo "<td>$rightBicep</td>";
                                        }
                                        echo "
                                    </tr>
                                </tbody>
                            </table>
                            <table class='swipe-row'>
                                <tbody>
                                    <tr>
                                        <th>Left Bicep</th>";
                                        foreach($allMeasurements as $row){
                                            $leftBicep = $row["left_bicep"];
                                            echo "<td>$leftBicep</td>";
                                        }
                                        echo "
                                    </tr>
                                </tbody>
                            </table>
                            <table class='swipe-row'>
                                <tbody>
                                    <tr>
                                        <th>Chest</th>";
                                        foreach($allMeasurements as $row){
                                            $chest = $row["chest"];
                                            echo "<td>$chest</td>";
                                        }
                                        echo "
                                    </tr>
                                </tbody>
                            </table>";
                    echo "</div>";
                }
                    $numberOfItemsOnPage = $selectQuery->num_rows;
                    $numberOfItems = $db->query($query)->num_rows;
                    $totalPages = ceil($numberOfItems / $itemsPerPage);
                    echo "
                    <div class='paginate-footer'";
                    if($numberOfItems <= $numberOfItemsOnPage){
                        echo "style='height: 30px'";
                    }
                    echo ">
                        <div id='item-count'>
                            <p>Showing " . ($offset + 1) . "-" . ($numberOfItemsOnPage+$offset) . " of " . $numberOfItems . " entries</p>
                        </div>
                    ";
                if($numberOfItems > $numberOfItemsOnPage){
                    echo "<ul class=\"pagination\">
                        <li class='";
                        if($pageNumber <= 1) echo 'disabled';
                        echo "'><a href=\"?pageno=1#weight-history-title"."\"><img onClick='if(this.parentElement.parentElement.className == \"disabled\") return false;' class='first' src='images/first.png' style='width: 25px; height: 25px;'></a></li>
                        <li class=";
                        if($pageNumber <= 1) echo 'disabled';
                        echo ">
                            <a href='";
                            if($pageNumber <= 1){echo "#'";} else { echo "?pageno=".($pageNumber - 1)."#weight-history-title"; }
                            echo "'><img onClick='if(this.parentElement.parentElement.className == \"disabled\") return false;' class='prev' src='images/prev.png' style='width: 25px; height: 25px;'></a>
                        </li>
                        <li style='font-size: 14px; cursor: default; margin-bottom: 5px;'><strong style='font-size: 18px'>$pageNumber/$totalPages</strong></li>
                        <li class=";
                        if($pageNumber >= $totalPages) echo "disabled";
                        echo ">
                            <a href='";
                            if($pageNumber >= $totalPages){ echo '#\''; } else { echo "?pageno=".($pageNumber + 1)."#weight-history-title"; }
                            echo "'><img onClick='if(this.parentElement.parentElement.className == \"disabled\") return false;' class='next' src='images/prev.png' style='width: 25px; height: 25px;'></a>
                        </li>
                        <li class='";
                        if($pageNumber == $totalPages) echo 'disabled';
                        echo "'><a href=\"?pageno=$totalPages#weight-history-title"."\"><img onClick='if(this.parentElement.parentElement.className == \"disabled\") return false;' class='last' src='images/first.png' style='width: 25px; height: 25px;'></a></li>
                    </ul>";
                }
                echo "
                    </div>
                </div>
                </div>
                </div>    
            </div>
        </div>

            </div>
            </div>";
        }
    }
}
class Weight {
    private $db;
    public function __construct($db){
        $this->db = $db;
    }
    public function addDecimal($lbs){
        $weight = (string) round($lbs, 1);
        if(strpos($weight, ".") === false){
            return $weight.".0";
        }elseif($weight[0] == "."){
            return "0".$weight;
        }elseif($weight[sizeof(str_split($weight))-1] == "."){
            return $weight."0";
        }else{
            return $weight;
        }
    }
    public function insert_weight($weight, $date){
        $insertWeight = $this->db->getConnection()->prepare("INSERT INTO daily_weight (username, pounds, date_weighed) VALUES (?, ?, ?)");
        $insertWeight->bind_param("sss", $_SESSION["username"], $weight, $date);
        return($insertWeight->execute());
    }
    public function update_weight($weight, $date, $id){
        $updateWeight = $this->db->getConnection()->prepare("UPDATE daily_weight SET pounds = ?, date_weighed = ? WHERE id = ?");
        $updateWeight->bind_param("ssi", $weight, $date, $id);
        return($updateWeight->execute());
    }
    public function delete_weight($id){
        $updateWeight = $this->db->getConnection()->prepare("DELETE FROM daily_weight WHERE id = ?");
        $updateWeight->bind_param("i", $id);
        return($updateWeight->execute());
    }
    public function display_weight($bodyFatDiv){
        if(isset($_GET["pageno"])){
            $pageno = $_GET["pageno"];
        }else{
            $pageno = 1;
        }
        Pagination::paginate("Weight", $this->db, "SELECT * FROM daily_weight WHERE username = '{$_SESSION["username"]}' ORDER BY date_weighed DESC", 7, $pageno, $bodyFatDiv);
    }
    public function display_measurements(){
        if(isset($_GET["pageno"])){
            $pageno = $_GET["pageno"];
        }else{
            $pageno = 1;
        }
        Pagination::paginate("Measurements", $this->db, "SELECT * FROM daily_measurements WHERE username = '{$_SESSION["username"]}' ORDER BY date_measured DESC", 3, $pageno);
    }
}
?>