<?php
    $dbh = new PDO('mysql:host=mysql.montpellier.epsi.fr;port=5206;dbname=cars', "cars_user", "cars34");

    $stop = false;

    while(!$stop) {
        $query = "SELECT COUNT(id) as tasks_number, status FROM tasks GROUP BY status";
        $results = (array)$dbh->query($query)->fetchAll();
        $cars_query = "SELECT COUNT(id) as cars_number FROM cars";
        $cars_results = (array)$dbh->query($cars_query)->fetchObject();

        system('clear');
        foreach ($results as $key => $value) {
            echo $value['status'].": ".$value['tasks_number']."\n";
        }
        echo "Cars: ".$cars_results['cars_number'];

        //print_r($results);

        usleep(100000);
    }
