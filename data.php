<?php 

class data{
    //spec för JSON, hämta data 
    public static function getData($file){
        $fileName = $file . ".json"; 

        $data = file_get_contents($fileName); 
        return json_decode($data, true); //true -> object become array --> easier to work with when data  
    }

    //skapa eller spara yes 
    public static function saveData($file, $data){
        $fileName = $file . ".json"; 
        $data = (array) $data;
        $data = json_encode($data, JSON_PRETTY_PRINT); //flagga (prettyy pring), få det se finare ut i fil
        file_put_contents($fileName, $data); 
    }

    public static function changeData($file, $data){
        $fileName = $file . "json"; 
        $data = file_get_contents($fileName);
        
        
    }


}