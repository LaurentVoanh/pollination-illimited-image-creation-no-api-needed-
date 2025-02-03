<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Diaporama Image Generator</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #ffffff;
        }
        .popup {
            text-align: center;
        }
        .slideshow {
            display: none;
            position: relative;
            width: 100%;
            height: 100%;
        }
        .slide {
            position: absolute;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 0.5s;
        }
        .slide.active {
            opacity: 1;
        }
        .text {
            position: absolute;
            bottom: 20px;
            left: 20px;
            color: white;
            font-size: 24px;
            opacity: 0;
            transition: opacity 0.5s;
        }
        .text.active {
            opacity: 0;
        }
        .debug {
              display: none; /* Masque complètement l'élément */
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0);
            color: white;
            padding: 10px;
            overflow-y: auto;
            max-height: 0px;
        }
        .loading-gif {
    display: block;
    margin: auto;
}
    </style>
</head>
<body>
    
 <script>
    if ('wakeLock' in navigator) {
      let wakeLock = null;

      const requestWakeLock = async () => {
        try {
          wakeLock = await navigator.wakeLock.request('screen');
          wakeLock.addEventListener('release', () => {
            console.log('Wake Lock was released');
          });
          console.log('Wake Lock is active');
        } catch (err) {
          console.error(`${err.name}, ${err.message}`);
        }
      };

      requestWakeLock();
    }
  </script>



    <div class="popup" id="popup">
         <img src="1.gif" alt="Loading GIF" width="200" height="200"><br>
        <h3>Deepseek.my.id V0.01</h3>
        <h4>Créez toutes vos images en une fois</h4>
        <h5>Créateur d'image, utilisation illimité, 100% gratuit, sans inscription, rapide et immédiat</h5>
       
        <input type="text" id="prompt" placeholder="Entrez ici l'image que vous voullez creer, soyez le plus precis possible">
        <input type="number" id="imageCount" placeholder="Nombre d'images à creer">
        <button onclick="startProcess()">Valider</button>
    </div>
   <div class="slideshow" id="slideshow">
    <img src="1.gif" alt="Loading GIF" class="loading-gif" width="200" height="200">
    <div class="slide" style="background-image: url('loading.gif');"></div>
    <div class="text">Chargement...</div>
</div>

    <div class="debug" id="debug"></div>
    <script>
       document.addEventListener('DOMContentLoaded', function() {
    const slideshow = document.getElementById('slideshow');
    const debug = document.getElementById('debug');
    const loadingGif = document.querySelector('.loading-gif');
    let currentIndex = 0;
    let images = [];
    let texts = [];
    let prompt = '';
    let imageCount = 0;
    let currentSeed = 0;
    let savedImages = [];

    function log(message) {
        console.log(message);
        debug.innerHTML += `<p>${message}</p>`;
        debug.scrollTop = debug.scrollHeight;
    }

    function showSlide(index) {
        const slides = document.querySelectorAll('.slide');
        const textElements = document.querySelectorAll('.text');
        slides.forEach((slide, i) => {
            if (i === index) {
                slide.classList.add('active');
                textElements[i].classList.add('active');
            } else {
                slide.classList.remove('active');
                textElements[i].classList.remove('active');
            }
        });
    }

    function nextSlide() {
        currentIndex = (currentIndex + 1) % images.length;
        showSlide(currentIndex);
    }

    function updateSlideshow() {
        slideshow.innerHTML = '';
        images.forEach((image, index) => {
            const slide = document.createElement('div');
            slide.className = 'slide';
            slide.style.backgroundImage = `url('${image}')`;
            slideshow.appendChild(slide);

            const text = document.createElement('div');
            text.className = 'text';
            text.textContent = texts[index] || `Image ${index + 1}`;
            slideshow.appendChild(text);
        });
        showSlide(currentIndex);
        loadingGif.style.display = 'none'; // Masquer l'image 1.gif
    }

    async function fetchImage(seed) {
        log(`Tentative de récupération de l'image avec le seed ${seed}`);
        const url = `https://image.pollinations.ai/prompt/${encodeURIComponent(prompt)}?width=1080&height=1920&nologo=poll&nofeed=yes&seed=${seed}`;

        try {
            const response = await fetch(url);
            const blob = await response.blob();
            const imageName = `image/${await getIP()}_${seed}_${prompt.split(' ').slice(0, 2).join('_')}.png`;
            const jsonName = `image/${await getIP()}_${seed}_${prompt.split(' ').slice(0, 2).join('_')}.json`;

            // Vérifier la taille de l'image
            if (blob.size < 70 * 1024) {
                log(`Image trop petite (${blob.size} octets), suppression...`);
                // Supprimer l'image et le JSON
                await deleteFile(imageName);
                await deleteFile(jsonName);
                fetchNextImage();
                return;
            }

            // Enregistrer l'image sur le serveur
            await saveImageToServer(blob, imageName);

            // Créer et sauvegarder le JSON sur le serveur
            const jsonData = {
                prompt: prompt,
                image: imageName,
                date: new Date().toISOString()
            };
            await saveJSONToServer(jsonData, jsonName);

            // Mettre à jour le diaporama
            const imageURL = `https://deepseek.my.id/${imageName}`;
            log(`Image enregistrée : ${imageURL}`);

            images.push(imageURL);
            texts.push(`Image ${images.length} - Seed: ${seed}`);
            updateSlideshow();
            savedImages.push({ url: imageURL, name: imageName, json: jsonName });

            fetchNextImage();
        } catch (error) {
            log(`Erreur lors de la récupération de l'image : ${error.message}`);
            fetchNextImage();
        }
    }

    function fetchNextImage() {
        currentSeed = Math.floor(Math.random() * (55555 - 11111 + 1)) + 11111;
        log(`Nouveau seed généré : ${currentSeed}`);
        fetchImage(currentSeed);
    }

    async function saveImageToServer(blob, imageName) {
        const formData = new FormData();
        formData.append('image', blob, imageName);

        try {
            const response = await fetch('upload-image.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.text();
            log(`Réponse du serveur pour l'image : ${result}`);
        } catch (error) {
            log(`Erreur lors de l'envoi de l'image : ${error.message}`);
        }
    }

    async function saveJSONToServer(jsonData, jsonName) {
        try {
            const response = await fetch('upload-json.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    json: jsonData,
                    filename: jsonName
                })
            });
            const result = await response.text();
            log(`Réponse du serveur pour le JSON : ${result}`);
        } catch (error) {
            log(`Erreur lors de l'envoi du JSON : ${error.message}`);
        }
    }

    async function deleteFile(fileName) {
        try {
            const response = await fetch('delete-file.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ filename: fileName })
            });
            const result = await response.text();
            log(`Réponse du serveur pour la suppression : ${result}`);
        } catch (error) {
            log(`Erreur lors de la suppression du fichier : ${error.message}`);
        }
    }

    async function getIP() {
        try {
            const response = await fetch('https://api.ipify.org?format=json');
            const data = await response.json();
            return data.ip.replace(/\./g, '_');
        } catch (error) {
            log(`Erreur lors de la récupération de l'IP : ${error.message}`);
            return 'unknown';
        }
    }

    window.startProcess = function() {
        prompt = document.getElementById('prompt').value;
        imageCount = parseInt(document.getElementById('imageCount').value);
        log(`Prompt saisi : ${prompt}`);
        log(`Nombre d'images souhaitées : ${imageCount}`);
        document.getElementById('popup').style.display = 'none';
        slideshow.style.display = 'block';
        fetchNextImage();
        setInterval(nextSlide, 30000); // Change slide every 30 seconds
    };
});
    </script>
</body>
</html>
