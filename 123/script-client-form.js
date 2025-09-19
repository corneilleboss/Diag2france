document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('client-form');
    
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const nomClient = document.getElementById('nom-client').value;
        const emailClient = document.getElementById('email-client').value;
        const telClient = document.getElementById('tel-client').value;

        // Sauvegarde des informations dans le localStorage
        localStorage.setItem('nomClient', nomClient);
        localStorage.setItem('emailClient', emailClient);
        localStorage.setItem('telClient', telClient);

        // Redirection vers la prochaine étape (le récapitulatif des diagnostics)
        window.location.href = 'recap.html';
    });
});