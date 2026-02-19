
// UPDATE ROUTE SCRIPT
async function submitChanges(){
    console.log("HELLOOO");
    const id = document.querySelector("#id").value;
    const name = document.querySelector("#name").value;
    const breed = document.querySelector("#breed").value;
    const catPic = document.querySelector('#img').value;

    const data = new URLSearchParams();
    data.append('id', id);
    data.append('name', name);
    data.append('breed', breed);
    data.append('img', catPic);

    const response = await fetch(`/GA/cats`, {method:"PATCH", body:data, headers:{"Content-type":"application/x-www-form-urlencoded"}, redirect:"manual"});
    const loco = response.headers.get("Loco");
    console.log(loco);

    const json = await response.text();
    console.log(json);
    //window.location.href = loco;
}

// php vill inte ha in grejer i json utan i det som står i headers
// ?id=${id}&catName=${name}&catBreed=${breed} efter cats


// DELETE ROUTE SCRIPT
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

