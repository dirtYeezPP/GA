# CODE STUFF 

## AI BASED 

##### DELETE ROUTE STUFF 
``` php
    $filtedCats = array_filter($cats, function($c) use($catId){ 
        return $c['id'] != $catId; 
    });

    $filtedCats = array_values($filtedCats);  // text underneath explains THIS 
```
AI says (gemini) --> reset the array keys. Otherwise the JSON saves with "missing indices" and the array turns into an object. 

## YAN BASED 

## FREDRIC BASED 

## OTHER BASED 