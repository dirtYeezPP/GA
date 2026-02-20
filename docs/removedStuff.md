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


## PHP CATS ROUTE
might take back 
```php
get("/cats", function () use ($pdo) {
    $stmt = $pdo->query("SELECT id, name, breed, img FROM cattos");
    $cats = [];
    while ($cat = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cats[] = [
            'id'=>$cat['id'],
            'name'=>$cat['name'],
            'breed'=>$cat['breed'],
            'img'=>$cat['img']
        ];
    }
    Res::debug($cats);
});
```


```php
    $existentName = !empty($request['catName']);
    $existentBreed  = !empty($request['catBreed']);
    $existentPic    = !empty($request['catPic']);
    
        if (!empty($request['name'])) {
        $sqlPramValues["name"] = $request['name'];
    }
    if (!empty($request['breed'])) {
        $sqlPramValues["breed"] = $request['breed'];
    }
    if (!empty($request['img'])) {
        $sqlPramValues["img"] = $request['img'];
    }
    
    
    $sql = /** @lang text */
        "UPDATE cattos SET ";
    if($existentName) {
        $sql = $sql."name=:catName ";
        $sqlPramValues["catName"] = $request['catName'];
    }
    if($existentName && $existentBreed){
        $sql = $sql.", ";
    }
    if($existentBreed) {
        $sql = $sql."breed=:catBreed ";
        $sqlPramValues["catBreed"] = $request['catBreed'];
    }
    if($existentBreed && $existentPic){
        $sql = $sql.", ";
    }
    if($existentPic) {
        $sql = $sql."img=:catPic ";
        $sqlPramValues["catPic"] = $request['catPic'];
    }
    $sql = $sql."WHERE id=:id";
    echo $sql;
    if($existentName || $existentBreed || $existentPic){
        $pdo->prepare($sql)->execute($sqlPramValues);
    }
```
replaced with a loop.. 


``` js
async function submitRemoval(){
    console.log("HELLOOO");
    const id = document.querySelector("#id").value;
    if (!id) return console.error("No ID provided"); //check if ID is existent

    const data = new URLSearchParams();
    data.append('id', id);

    const response = await fetch(`/GA/cats`, {method:"DELETE", body:data, headers:{"Content-type":"application/x-www-form-urlencoded"}, redirect:"manual"});
    const loco = response.headers.get("Loco");
    console.log(loco);

    const json = await response.text();
    console.log(json);
    window.location.href = loco;

}
```

``` php
delete("/cats", function () use ($pdo) {
    parse_str(file_get_contents("php://input"), $_DELETE); //get ID from the request
    $catId = $_DELETE['id'];
    $pdo->prepare("DELETE FROM cattos WHERE id=?")->execute([$catId]);
    header("Loco: http://localhost/GA/cats");
});
```