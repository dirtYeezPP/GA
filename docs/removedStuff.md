## DELETED STUFF FROM INDEX 

##### FROM DELETE ROUTE 

``` php
    $filteredCats = array_filter($cats, function ($c) use ($catId) { //use gör att vi kan använda cat som om den är global fr
        return $c['id'] != $catId;
    });
    $filteredCats = array_values($filteredCats);

    data::saveData("cats", $filteredCats);
```

##### FROM UPDATE ROUTE 

``` php
    $cats = data::getData("cats");

    foreach ($cats as &$c) {
        if ($request['id'] == $c['id']) {
            $c['catName'] = $request['catName'] ?: $c['catName']; //ternary operator
            $c['catBreed'] = $request['catBreed'] ?: $c['catBreed'];
        }
    }

    data::saveData("cats", $cats);
```
took care of looping through each cat in array to find which one to modify. 
requires the '&' before $c to not make a copy of it and use actual cats cat. 