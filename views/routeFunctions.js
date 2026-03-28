
// UPDATE ROUTE SCRIPT
async function submitChanges(){
    console.log("UPDATE ATTEMPT.....");
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

    const msg = await response.text();
    console.log("serv says:", msg);

    if(!response.ok){
        alert("uh oh.. you bum" + msg);
        return;
    }

    const loco = response.headers.get("Loco");
    if(loco){
        window.location.href = loco;
    } else{
        alert("success but uhm where do we go now")
    }

    //window.location.href = loco;
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

        if (response.ok) {
            // 1. Find the card in the HTML
            const cardToRemove = document.getElementById(`catCard-${id}`);

            // 2. Make it disappear!
            if (cardToRemove) {
                cardToRemove.remove();
                console.log(`Cat ${id} has left the building.`);
            }
        } else {
            alert("Delete failed on the server.");
        }
    } catch (error) {
        console.error("Network error:", error);
    }
}


async function submitProfileChanges(){
    const password = document.querySelector("#paswr").value;
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

        if(response.ok){
            const jRes = await response.json();
            window.location.href = jRes.Loco;
        } else {
            console.error("server refused this action");
        }
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

        if(response.ok){
            const jRes = await response.json();
            window.location.href = jRes.Loco;
        } else {
            console.error("server refused this action");
        }
    } catch (error) {
        console.error("Network error:", error);
    }
}

