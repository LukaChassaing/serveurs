function load(url, callback) {
    var xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            console.log(xhr.response); 
            console.log(JSON.parse(xhr.response).coucou)
            
        }
    }

    xhr.open('GET', url, true);
    xhr.send('');
}

load('demande')