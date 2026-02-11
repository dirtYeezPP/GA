<?php


class Db{


    private static function connect(){
    
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "cat_db";
        // Create connection
        $con = new mysqli($servername, $username, $password, $dbname);

        // Check connection
        if ($con->connect_error)  return ["error" => $con->connect_error];
        
        return $con;


        }


        public static function getCats(){

            // Koppla upp oss mot databas
            $con = self::connect();

            $q = $con->query("SELECT * FROM cats");

            $cats = [];
            while($row = $q->fetch_assoc()){
                array_push($cats, $row);
            }
            $con->close();
            return $cats;


        }



    }/* End class */