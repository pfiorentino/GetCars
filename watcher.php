<?php
    function term_head($value = "") {
        if (!empty($value)){
            $value = " $value ";
        }

        $remaining_chars = (80-strlen($value))/2;

        echo str_repeat("=", floor($remaining_chars));
        echo $value;
        echo str_repeat("=", ceil($remaining_chars))."\n";
    }

    function term_print($value = "", $center = false) {
        $remaining_chars = 78-strlen($value);
        echo strlen($value);
        echo $remaining_chars;

        if ($center){
            echo "|".str_repeat(" ", floor($remaining_chars/2));
            echo $value;
            echo str_repeat(" ", ceil($remaining_chars/2))."|\n";
        } else {
            echo "| ".$value.str_repeat(" ", $remaining_chars-1)."|\n";
        }
    }

    function term_array_print($values = array()) {
        $cell_size = floor(78 / count($values));

        echo "|";
        foreach ($values as $key => $value) {
            $remaining_chars = $cell_size-strlen($value);

            echo " ".$value;

            if ($key < count($values)-1){
                echo str_repeat(" ", $remaining_chars-2);
                echo "|";
            } else {
                echo str_repeat(" ", $remaining_chars-1+(78-$cell_size*count($values)));
            }
        }
        echo "|\n";
    }

    $dbh = new PDO('mysql:host=mysql.montpellier.epsi.fr;port=5206;dbname=cars', "cars_user", "cars34");

    $stop = false;

    while(!$stop) {
        $query = "SELECT COUNT(id) as tasks_number, status FROM tasks GROUP BY status";
        $results = (array)$dbh->query($query)->fetchAll();
        $tasks_running_query = "SELECT * FROM tasks WHERE status = 'IS_PROCESSING'";
        $tasks_running_results = (array)$dbh->query($tasks_running_query)->fetchAll();
        $cars_query = "SELECT COUNT(id) as cars_number FROM cars";
        $cars_results = (array)$dbh->query($cars_query)->fetchObject();

        system('clear');

        term_head("TASKS");
        term_print();

        $temp_array = array();
        foreach ($results as $key => $value) {
            $temp_array[] = $value['status'].": ".$value['tasks_number'];
        }
        term_array_print($temp_array);

        if (count($tasks_running_results) > 0){
            term_print();

            foreach ($tasks_running_results as $key => $value) {
                term_array_print(array($value["brand_label"]." - ".$value["model_label"], $value["brand"]." - ".$value["model"]));
                $cmd_res = exec("tail logs/".$value["brand"]."-".$value["model"].".log 2>&1");

                if (!empty($cmd_res) && $cmd_res != ")"){
                    term_print(" --> ".substr($cmd_res, 0, 70));
                }
            }
        }

        term_print();
        term_head();

        echo "\n\n";

        term_head("CARS");
        term_print();

        term_print("Cars: ".$cars_results['cars_number']);

        term_print();
        term_head();
        echo "\n";

        //print_r($results);

        usleep(100000);
    }
