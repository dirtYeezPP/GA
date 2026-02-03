

<?php 

class res{

    public static function debug($data){
        echo "<pre>"; 
        var_dump($data); 
        echo "</pre>"; 
    } 
    public static function json($data){
        header("content-Type:application/json");
        echo json_encode($data); 
    }

}; 
