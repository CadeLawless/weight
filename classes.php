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

    public function getConnection(){
        return $this->connection;
    }
}
class Pagination {
    public static function paginate($db, $query, $itemsPerPage, $pageNumber){
        $offset = ($pageNumber - 1) * $itemsPerPage;
        $selectQuery = $db->query("$query LIMIT $offset, $itemsPerPage");
        if($selectQuery->num_rows > 0){
            echo "
            <div class='vertical-line'></div>
            <div>
            <div class='body-fat-percentage'>
                <a class='body-fat-button' onClick='showPopup()'>Calculate Body Fat Percentage</a>
            </div>
            <button onclick='slideIn()'>Click Me</button>
            <div class='running'>
                <img src='images/running-stickman.gif'>
            </div>
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
                        </select><br>
                        <div id='restOfForm' style='display: none'>
                            <label>Age: <br></label>
                            <input type='number' name='age' step='any'><br>
                            <label>Weight: <br></label>
                            <input onkeypress='return isNumberKey(this, event)' onkeydown=\"if(this.value.length >= 5 && event.code != 'Backspace') return false;\" type='number' name='body-fat-weight' step='any'> <span>lbs</span><br>
                            <label>Thigh: <br></label>
                            <input type='number' step='any' name='thigh'> <span>mm</span><br>
                            <div id='male' style='display: none'>
                                <label>Chest: <br></label>
                                <input type='number' step='any' name='chest'> <span>mm</span><br>
                                <label>Abdomen: <br></label>
                                <input type='number' step='any' name='abdomen'> <span>mm</span><br>
                            </div>
                            <div id='female' style='display: none'>
                                <label>Triceps: <br></label>
                                <input type='number' step='any' name='triceps'> <span>mm</span><br>
                                <label>Suprailiac: <br></label>
                                <input type='number' step='any' name='suprailiac'> <span>mm</span><br>
                            </div>
                            <p align='center'><input type='submit' id='body-fat-submit' name='body-fat-submit' value='Calculate'></p>
                        </div>
                    </form>
                </div>
            </div>

            <h2 id='weight-history-title' align='center'>Weight History</h2>
            <div class='history'>
                <table>
                    <thead>
                        <tr>
                            <th class='th_border'>Date Weighed</th>
                            <th>Weight</th>
                        </tr>
                    </thead>
                    <tbody>
            ";
            while($row=$selectQuery->fetch_assoc()){
                $dateWeighed = $row["date_weighed"];
                $dateWeighed = date_create($dateWeighed);
                $dateWeighed = date_format($dateWeighed, "m/d/y");
                $weight = $row["pounds"];
                echo "
                    <tr>
                        <td>$dateWeighed</td>
                        <td>$weight lbs</td>
                    </tr>
                ";
            }
            echo "
                </tbody>
            </table>
            ";
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
                    echo "'><a href=\"?pageno=1"."\"><img onClick='if(this.parentElement.parentElement.className == \"disabled\") return false;' class='first' src='images/first.png' style='width: 25px; height: 25px;'></a></li>
                    <li class=";
                    if($pageNumber <= 1) echo 'disabled';
                    echo ">
                        <a href='";
                        if($pageNumber <= 1){echo "#'";} else { echo "?pageno=".($pageNumber - 1); }
                        echo "'><img onClick='if(this.parentElement.parentElement.className == \"disabled\") return false;' class='prev' src='images/prev.png' style='width: 25px; height: 25px;'></a>
                    </li>
                    <li style='font-size: 14px; cursor: default; margin-bottom: 5px;'><strong style='font-size: 18px'>$pageNumber/$totalPages</strong></li>
                    <li class=";
                    if($pageNumber >= $totalPages) echo "disabled";
                    echo ">
                        <a href='";
                        if($pageNumber >= $totalPages){ echo '#\''; } else { echo "?pageno=".($pageNumber + 1); }
                        echo "'><img onClick='if(this.parentElement.parentElement.className == \"disabled\") return false;' class='next' src='images/prev.png' style='width: 25px; height: 25px;'></a>
                    </li>
                    <li class='";
                    if($pageNumber == $totalPages) echo 'disabled';
                    echo "'><a href=\"?pageno=$totalPages"."\"><img onClick='if(this.parentElement.parentElement.className == \"disabled\") return false;' class='last' src='images/first.png' style='width: 25px; height: 25px;'></a></li>
                </ul>";
            }
            echo "
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
    public function addDecimal($weight){
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
        $insertWeight = $this->db->getConnection()->prepare("INSERT INTO daily_weight (pounds, date_weighed) VALUES (?, ?)");
        $insertWeight->bind_param("ss", $weight, $date);
        return($insertWeight->execute());
    }
    public function update_weight($weight, $date, $id){
        $updateWeight = $this->db->getConnection()->prepare("UPDATE daily_weight SET pounds = ?, date_weighed = ? WHERE id = ?");
        $updateWeight->bind_param("ssi", $weight, $date, $id);
        return($updateWeight->execute());
    }
    public function display_weight(){
        if(isset($_GET["pageno"])){
            $pageno = $_GET["pageno"];
        }else{
            $pageno = 1;
        }
        Pagination::paginate($this->db, "SELECT * FROM daily_weight ORDER BY date_weighed DESC", 7, $pageno);
    }
}
?>