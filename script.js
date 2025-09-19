document.addEventListener('DOMContentLoaded', () => {
    const louerBtn = document.getElementById('louer-btn');
    const vendreBtn = document.getElementById('vendre-btn');
    const addressInput = document.getElementById('address-input');
    const suggestionsList = document.getElementById('address-suggestions');
    const startBtn = document.querySelector('.start-btn');

    localStorage.setItem('transactionType', 'location');

    louerBtn.addEventListener('click', () => {
        louerBtn.classList.add('active');
        vendreBtn.classList.remove('active');
        localStorage.setItem('transactionType', 'location');
    });

    vendreBtn.addEventListener('click', () => {
        vendreBtn.classList.add('active');
        louerBtn.classList.remove('active');
        localStorage.setItem('transactionType', 'vente');
    });
    
    addressInput.addEventListener('input', () => {
        const query = addressInput.value;

        if (query.length < 3) {
            suggestionsList.style.display = 'none';
            startBtn.disabled = true;
            return;
        }

        const url = `https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(query)}&limit=5`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                suggestionsList.innerHTML = '';
                if (data.features.length > 0) {
                    suggestionsList.style.display = 'block';
                    data.features.forEach(feature => {
                        const li = document.createElement('li');
                        li.textContent = feature.properties.label;
                        li.addEventListener('click', () => {
                            addressInput.value = feature.properties.label;
                            suggestionsList.style.display = 'none';
                            startBtn.disabled = false;
                            
                            localStorage.setItem('selectedAddress', feature.properties.label);
                            localStorage.setItem('latitude', feature.geometry.coordinates[1]);
                            localStorage.setItem('longitude', feature.geometry.coordinates[0]);
                        });
                        suggestionsList.appendChild(li);
                    });
                } else {
                    suggestionsList.style.display = 'none';
                }
            })
            .catch(error => console.error("Erreur de l'API : ", error));
    });

    startBtn.addEventListener('click', () => {
        window.location.href = 'form.html';
    });
});