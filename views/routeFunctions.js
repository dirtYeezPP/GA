
async function handleResError(response){
    const json = await response.json();
    window.location = json.errorPath;
}

// UPDATE ROUTE SCRIPT
async function updateCarInfo(){
    console.log("UPDATE ATTEMPT.....");
    const id = document.querySelector("#id").value;
    const name = document.querySelector("#name").value;
    const breed = document.querySelector("#breed").value;

    const data = new URLSearchParams();
    data.append('id', id);
    data.append('name', name);
    data.append('breed', breed);

    const response = await fetch(`/GA/cats`, {method:"PATCH", body:data, headers:{"Content-type":"application/x-www-form-urlencoded"}, redirect:"manual"});

    if(!response.ok){
        await handleResError(response)
        return;
    }
    //alert("car is updated")
    window.location.reload();
}


async function updateCarImage(){
    const id = document.querySelector("#id").value;
    const img = document.querySelector("#img");

    if(img.files.length === 0){
        alert("dude select an image first :(")
        return;
    }
    const data = new FormData();
    data.append('id', id);
    data.append('img', img.files[0]);

    const response = await fetch(`/GA/cats/image`, {method:"POST", body:data});
    if(!response.ok){
        await handleResError(response)
        return;
    }

    window.location.reload();
}



// php vill inte ha in grejer i json utan i det som står i headers
// ?id=${id}&catName=${name}&catBreed=${breed} efter cats


// DELETE ROUTE SCRIPT
async function deleteCar(id) {
    if (!id) return;
    const data = new URLSearchParams();
    data.append('id', id);

    try {
        const response = await fetch(`/GA/cats`, {
            method: "DELETE",
            body: data,
            headers: {"Content-type": "application/x-www-form-urlencoded"}
        });

        if (!response.ok) {
            await handleResError(response)
            return;
        }
        // Find card in the HTML
        const cardToRemove = document.getElementById(`catCard-${id}`);
        // Make it disappear
        if (cardToRemove) {
            cardToRemove.remove();
            console.log(`Cat ${id} has left the building.`);
        }
    } catch (error) {
        console.error("Network error:", error);
    }
}


async function submitProfileChanges(){
    const password = document.querySelector("#password").value;
    const email = document.querySelector("#uEmail").value;
    const name = document.querySelector("#uName").value;

    if(!password){
        alert("we need the password you bum");
        return;
    }

    const data = new URLSearchParams();
    data.append('password', password);
    data.append('email', email);
    data.append('name', name);

    try {
        const response = await fetch(`/GA/profile`, {
            method: "PATCH",
            body: data,
            headers: {"Content-type": "application/x-www-form-urlencoded"},
            redirect: "manual"
        });

        if (!response.ok) {
            await handleResError(response)
            return;
        }
        const jRes = await response.json();
        window.location.href = jRes.Loco;

    } catch (error) {
        console.error("Network error:", error);
    }
}

//IN PROGRESS
async function deleteProfile(){

    const approved = confirm("are u sure u wanna delete ts? leave the cult?");
    if(!approved) return;

    try {
        const response = await fetch(`/GA/deleteProfile`, {
            method: "DELETE",
            headers: {"Content-type": "application/x-www-form-urlencoded"},
            redirect: "manual"
        });

        if (!response.ok) {
            await handleResError(response);
            return;
        }
        const jRes = await response.json();
        window.location.href = jRes.Loco;
    } catch (error) {
        console.error("Network error:", error);
    }
    window.location.reload();
}

