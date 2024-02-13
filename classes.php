<?php
class DB{
    private $connection;
    private $servername = "localhost";
    private $username = "root";
    private $password = "millieBean0514";
    private $database = "weight";
    /* private $servername = "db5014008294.hosting-data.io";
    private $username = "dbu17367";
    private $password = "7pmSdg^r8AR7bDe%H8H3";
    private $database = "dbs11710177"; */


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
    public function select($sql, $values){
        if($selectStatement = $this->connection->prepare($sql)){
            $selectStatement->execute($values);
            return $selectStatement->get_result();
        }else{
            //echo $db->error;
            return false;
        }
    }

    // parameterized mysqli write (insert, update, delete) statement
    public function write($sql, $values){
        if($writeStatement = $this->connection->prepare($sql)){
            if($writeStatement->execute($values)){
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
    public static function paginate($type, $db, $query, $itemsPerPage, $pageNumber, $bodyFatDiv="", $editErrorID="", $editErrorMsg="", $editInputArray=[]){
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
                        <a class='body-fat-button popup-button'>Calculate Body Fat Percentage</a>
                    </div>";
                    if($bodyFatDiv != "") echo $bodyFatDiv;
                    echo "
                    <div class = 'flex hidden popup-container'>
                        <div class = 'popup flex' id='body-fat-popup'>
                            <div class='close-container'>
                                <img src='images/close.png' class='close-button'>
                            </div>
                            <div style='overflow: auto;'>
                                <h3 style='text-align: center'>Body Fat Percentage Calculator</h3><br />
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
                                        <td style='width: 10%; text-align: center;'>
                                            <img class='edit-button popup-button' id='$id' src='images/edit.png' style='cursor: pointer; width: 15px; height: 15px;'>
                                            <div class='popup-container edit-popup-$id flex";
                                            if($id != $editErrorID) echo " hidden";
                                            echo "'>
                                                <div class='popup flex'>
                                                    <div class='close-container'>
                                                        <img src='images/close.png' class='close-button'>
                                                    </div>
                                                    <div class='popup-content'>";
                                                        if($id == $editErrorID) echo $editErrorMsg;
                                                        echo "
                                                        <form class='edit-form' method='POST' action=''>
                                                            <div style='margin: auto;'>
                                                                <label for='date_weighed$id'>Date Weighed:<br></label>
                                                                <input type='date' value='";
                                                                echo $id == $editErrorID ? $editInputArray["date_weighed"] : $row["date_weighed"];
                                                                echo "' id='date_weighed$id' name='{$id}_date_weighed'><br>
                                                                <label for='weight$id'>Weight:<br></label>
                                                                <div class='input-container'>
                                                                    <input class='edit-weight' type='text' inputmode='decimal' value='";
                                                                    echo $id == $editErrorID ? $editInputArray["weight"] : $weight;
                                                                    echo "' maxlength='5' id='weight$id' name='{$id}_weight'> <span>lbs</span>
                                                                </div>
                                                            </div>
                                                            <p class='center'><input type='submit' name='editButton$id' class='edit_submit'></p>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style='width: 10%; text-align: center;'><img class='delete-icon' src='images/delete.png' style='cursor: pointer; width: 15px; height: 15px;'></td>
                                        <td class='delete'><a href='delete.php?id=$id' class='delete-button'>Delete</a></td>
                                    </tr>
                                </tbody>
                            </table>";
                    }
                    echo "</div>";
                }else if($type == "Measurements"){
                    if($selectQuery->num_rows > 0){
                        echo "
                        <div class='history'>
                            <h2 id='weight-history-title' align='center'>Measurements History</h2>
                            <div class='measurements-table'>";
                            while($row = $selectQuery->fetch_assoc()){
                                $id = $row["id"];
                                $waist = htmlspecialchars($row["waist"]);
                                $right_bicep = htmlspecialchars($row["right_bicep"]);
                                $left_bicep = htmlspecialchars($row["left_bicep"]);
                                $chest = htmlspecialchars($row["chest"]);
                                $date_measured = date("n/j/Y", strtotime($row["date_measured"]));
                                echo "
                                <div class='measurement-entry'>
                                    <div class='flex-row top-row'>
                                        <div class='flex-td label-td'>$date_measured</div>
                                        <div class='flex-td measurement-td'>
                                            <img class='edit-button popup-button' src='images/edit-white.png' style='cursor: pointer; width: 15px; height: 15px;'>
                                            <div class='popup-container edit-popup-$id flex";
                                            if($id != $editErrorID) echo " hidden";
                                            echo "'>
                                                <div class='popup flex'>
                                                    <div class='close-container'>
                                                        <img src='images/close.png' class='close-button'>
                                                    </div>
                                                    <div class='popup-content'>";
                                                        if($id == $editErrorID) echo $editErrorMsg;
                                                        echo "
                                                        <form class='edit-form' method='POST' action=''>
                                                            <div style='margin: auto;'>
                                                                <label for='date_measured$id'>Date Measured:<br></label>
                                                                <input type='date' value='";
                                                                echo $id == $editErrorID ? $editInputArray["date_measured"] : date("Y-m-d", strtotime($row["date_measured"]));
                                                                echo "' id='date_measured$id' name='{$id}_date_measured'><br>
                                                                <label for='waist$id'>Waist:<br></label>
                                                                <div class='input-container'>
                                                                    <input class='edit-waist' type='text' inputmode='decimal' value='";
                                                                    echo $id == $editErrorID ? $editInputArray["waist"] : $waist;
                                                                    echo "' maxlength='5' id='waist$id' name='{$id}_waist'> <span>in</span>
                                                                </div>
                                                                <div class='input-container'>
                                                                    <label for='right_bicep$id'>Right Bicep:<br></label>
                                                                    <input class='edit-right_bicep' type='text' inputmode='decimal' value='";
                                                                    echo $id == $editErrorID ? $editInputArray["right_bicep"] : $right_bicep;
                                                                    echo "' maxlength='5' id='right_bicep$id' name='{$id}_right_bicep'> <span>in</span>
                                                                </div>
                                                                <div class='input-container'>
                                                                    <label for='left_bicep$id'>Left Bicep:<br></label>
                                                                    <input class='edit-left_bicep' type='text' inputmode='decimal' value='";
                                                                    echo $id == $editErrorID ? $editInputArray["left_bicep"] : $left_bicep;
                                                                    echo "' maxlength='5' id='left_bicep$id' name='{$id}_left_bicep'> <span>in</span>
                                                                </div>
                                                                <div class='input-container'>
                                                                    <label for='chest$id'>Chest:<br></label>
                                                                    <input class='edit-chest' type='text' inputmode='decimal' value='";
                                                                    echo $id == $editErrorID ? $editInputArray["chest"] : $chest;
                                                                    echo "' maxlength='5' id='chest$id' name='{$id}_chest'> <span>in</span>
                                                                </div>
                                                            </div>
                                                            <p class='center'><input type='submit' name='editButton$id' class='edit_submit'></p>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <img class='delete-icon popup-button' src='images/delete-white.png' style='cursor: pointer; width: 15px; height: 15px;'>
                                            <div class='popup-container delete-popup-$id flex hidden'>
                                                <div class='popup flex'>
                                                    <div class='close-container'>
                                                        <img src='images/close.png' class='close-button'>
                                                    </div>
                                                    <div class='popup-content'>
                                                        <p><strong>Are you sure you want to delete this entry?</strong></p>
                                                        <p>
                                                            <span>Date Measured: $date_measured</span><br />
                                                            <span>Waist: $waist in</span><br />
                                                            <span>Right Bicep: $right_bicep in</span><br />
                                                            <span>Left Bicep: $left_bicep in</span><br />
                                                            <span>Chest: $chest in</span><br />
                                                        </p>
                                                        <p class='center'><a class='no-button'>No</a><a class='yes-button' href='delete-measurement.php?id=$id'>Yes</a></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class='flex-row'>
                                        <div class='flex-td label-td'>Waist</div>
                                        <div class='flex-td measurement-td'>$waist in</div>
                                    </div>
                                    <div class='flex-row'>
                                        <div class='flex-td label-td'>Right Bicep</div>
                                        <div class='flex-td measurement-td'>$right_bicep in</div>
                                    </div>
                                    <div class='flex-row'>
                                        <div class='flex-td label-td'>Left Bicep</div>
                                        <div class='flex-td measurement-td'>$left_bicep in</div>
                                    </div>
                                    <div class='flex-row'>
                                        <div class='flex-td label-td'>Chest</div>
                                        <div class='flex-td measurement-td'>$chest in</div>
                                    </div>
                                </div>";
                            }
                            echo "</div>";
                        }
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
    public function display_weight($bodyFatDiv, $edit_error_id, $edit_error_msg, $edit_input_array=[]){
        $pageno = $_GET["pageno"] ?? 1;
        Pagination::paginate(type: "Weight", db: $this->db, query: "SELECT * FROM daily_weight WHERE username = '{$_SESSION["username"]}' ORDER BY date_weighed DESC", itemsPerPage: 7, pageNumber: $pageno, bodyFatDiv: $bodyFatDiv, editErrorID: $edit_error_id, editErrorMsg: $edit_error_msg, editInputArray: $edit_input_array);
    }
    public function display_measurements($edit_error_id, $edit_error_msg, $edit_input_array=[]){
        $pageno = $_GET["pageno"] ?? 1;
        Pagination::paginate(type: "Measurements", db: $this->db, query: "SELECT * FROM daily_measurements WHERE username = '{$_SESSION["username"]}' ORDER BY date_measured DESC", itemsPerPage: 3, pageNumber: $pageno, editErrorID: $edit_error_id, editErrorMsg: $edit_error_msg, editInputArray: $edit_input_array);
    }
}
?>