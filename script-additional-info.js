document.addEventListener('DOMContentLoaded', () => {
    const dynamicFormContainer = document.getElementById('dynamic-form-container');
    const form = document.getElementById('additional-info-form');

    // Récupérer le type de bien depuis le localStorage
    const homeType = localStorage.getItem('homeType');

    // Définir les champs pour chaque type de bien
    const fields = {
        'maison': [
            { label: 'Le bien est-il mansardé ?', type: 'radio', name: 'mansarde', options: ['Oui', 'Non'] },
            { label: 'Nombre d’étages', type: 'number', name: 'etages' },
            { label: 'Présence d’une cave ou d’un garage ?', type: 'radio', name: 'caveGarage', options: ['Oui', 'Non'] },
            { label: 'Section cadastrale du bien', type: 'text', name: 'sectionCadastrale' },
            { label: 'Y a-t-il un grenier non aménagé ou des combles accessibles ?', type: 'radio', name: 'grenierCombles', options: ['Oui', 'Non'] },
            { label: 'Moyen d’accès aux combles disponible sur place', type: 'text', name: 'moyenAccesCombles' }
        ],
        'appartement': [
            { label: 'Code d’accès / Digicode', type: 'text', name: 'digicode' },
            { label: 'Étage du bien', type: 'number', name: 'etage' },
            { label: 'Le bien est-il situé au dernier étage ?', type: 'radio', name: 'dernierEtage', options: ['Oui', 'Non'] },
            { label: 'Présence d’un ascenseur ?', type: 'radio', name: 'ascenseur', options: ['Oui', 'Non'] },
            { label: 'Numéro de lot', type: 'text', name: 'numeroLot' },
            { label: 'Section cadastrale', type: 'text', name: 'sectionCadastrale' },
            { label: 'Présence d’une cave ?', type: 'radio', name: 'cave', options: ['Oui', 'Non'] },
            { label: 'Présence d’une place de parking intérieure ?', type: 'radio', name: 'parkingInterieur', options: ['Oui', 'Non'] }
        ],
        'copropriete': [ // J'ai renommé en 'copropriete' pour la cohérence
            { label: 'Type de chauffage', type: 'radio', name: 'chauffage', options: ['Collectif', 'Individuel'] },
            { label: 'Numéros de lots', type: 'text', name: 'numerosLots' },
            { label: 'Adresse e-mail du syndic', type: 'email', name: 'emailSyndic' },
            { label: 'Numéro de téléphone du syndic', type: 'tel', name: 'telSyndic' },
            { label: 'Tantièmes du bien (si chauffage collectif)', type: 'text', name: 'tantiemes' },
            { label: 'Coordonnées du gardien (si applicable)', type: 'text', name: 'coordonneesGardien' }
        ]
    };

    // Fonction pour générer les champs du formulaire
    function generateForm() {
        dynamicFormContainer.innerHTML = ''; // Nettoyer le conteneur

        const selectedFields = fields[homeType.toLowerCase()];
        if (!selectedFields) {
            dynamicFormContainer.innerHTML = `<p style="color: red;">Erreur: Type de bien non reconnu.</p>`;
            return;
        }

        selectedFields.forEach(field => {
            const fieldGroup = document.createElement('div');
            fieldGroup.className = 'form-group';
            
            const label = document.createElement('label');
            label.textContent = field.label;
            label.setAttribute('for', field.name);
            
            if (field.type === 'radio') {
                fieldGroup.innerHTML = `<label>${field.label}</label>`;
                field.options.forEach(option => {
                    const radioId = `${field.name}-${option.toLowerCase()}`;
                    fieldGroup.innerHTML += `
                        <input type="radio" id="${radioId}" name="${field.name}" value="${option}" required>
                        <label for="${radioId}" style="display:inline-block; margin-right: 15px;">${option}</label>
                    `;
                });
            } else {
                const input = document.createElement('input');
                input.type = field.type;
                input.name = field.name;
                input.id = field.name;
                input.required = true;
                fieldGroup.appendChild(label);
                fieldGroup.appendChild(input);
            }
            dynamicFormContainer.appendChild(fieldGroup);
        });
    }

    generateForm();

    // Gestion de la soumission du formulaire
    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const formData = new FormData(form);
        const data = {};
        for (const [key, value] of formData.entries()) {
            data[key] = value;
        }

        // Récupérer les données de la mission pour les combiner
        const rendezVousInfo = JSON.parse(localStorage.getItem('rendezVousInfo'));
        if (rendezVousInfo) {
            rendezVousInfo.additionalInfo = data;
            
            // Envoyer toutes les données au serveur
            // Vous devrez créer un nouveau script PHP, par exemple 'save-additional-info.php'
            fetch('save-additional-info.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(rendezVousInfo),
            })
            .then(response => response.json())
            .then(serverData => {
                if (serverData.success) {
                    alert('Informations complémentaires enregistrées avec succès !');
                    // Rediriger vers une page de confirmation finale si nécessaire
                    window.location.href = 'final-confirmation.html'; 
                } else {
                    alert('Erreur lors de l\'enregistrement des informations : ' + serverData.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur de connexion au serveur. Veuillez réessayer.');
            });
        }
    });
});