<?php
/**
 * Created by PhpStorm.
 * User: rlozano
 * Date: 30/10/2015
 * Time: 10:07
 */

class Orchestrateur{

private $dbh;

    function __construct(){
    $this->dbh = new PDO('mysql:host=mysql.montpellier.epsi.fr;port=5206;dbname=cars', "cars_user", "cars34");
    }


    function proceed(){
        // $this->razTasks();
        $isTasks = true;
        while($isTasks){
            $task = $this->getNewTask();
            if($task != null){
                $codeBrand = $task['brand'];
                $codeModel = $task['model'];
                $id = $task['id'];

                echo "Processing : ".$task['brand_label']." / ".$task['model_label']."\n";
                $this->launchWorker($id, $codeBrand, $codeModel);
            }
            else{
                $isTasks = false;
            }
        }
    }

    function getNewTask(){
            $query = "SELECT id, brand, brand_label, model, model_label FROM tasks WHERE status = \"PENDING\" LIMIT 1;";
            $row = (array)$this->dbh->query($query)->fetchObject();
            if(empty($row['id'])) {
                return null;
            } else {
                $this->updateStatus($row['id'], "IS_PROCESSING");

                while($this->getRunningJobCount() > 5){
                    sleep(2);
                }

                return $row;
            }
        }

    function getRunningJobCount() {
        $query = "SELECT COUNT(id) as running_jobs_number FROM tasks WHERE status = \"IS_PROCESSING\" GROUP BY status";
        $row = (array)$this->dbh->query($query)->fetchObject();
        return (int) $row['running_jobs_number'];
    }

    function updateStatus($id, $status){
        $query = "UPDATE tasks SET status = \"".$status."\" WHERE id = ".$id;
        $this->dbh->query($query);
    }

    function razTasks(){
        $query = "UPDATE tasks SET status = \"PENDING\"";
        $this->dbh->query($query);
    }

    function launchWorker($id, $codeMarque, $codeModele){
        echo "Codes : ".$codeMarque." / ".$codeModele."\n";
        exec("php getCar.php $codeMarque $codeModele &> logs/$codeMarque-$codeModele.log &");
        echo "PHP task launched\n";
    }

}

$orch = new Orchestrateur();
$orch->proceed();
