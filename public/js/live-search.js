// Script de recherche en direct ultra-basique

// Variable globale pour stocker l'URL de recherche
var searchUrl = '';

// Fonction d'initialisation
function initLiveSearch(url) {
    console.log('Initialisation de la recherche en direct avec URL:', url);
    searchUrl = url;
    
    // Attacher l'événement directement
    var searchInput = document.getElementById('search-title');
    if (searchInput) {
        console.log('Champ de recherche trouvé, attachement des événements');
        
        // Utiliser plusieurs événements pour être sûr
        searchInput.addEventListener('keyup', doSearch);
        searchInput.addEventListener('input', doSearch);
        searchInput.addEventListener('change', doSearch);
        
        // Formulaire
        var form = document.getElementById('advanced-search-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                doSearch();
            });
        }
        
        // Recherche initiale
        doSearch();
    } else {
        console.error('Champ de recherche non trouvé!');
    }
}

// Fonction de recherche
function doSearch() {
    console.log('Exécution de la recherche');
    
    // Récupérer la valeur
    var input = document.getElementById('search-title');
    var term = input ? input.value : '';
    console.log('Terme de recherche:', term);
    
    // Afficher le loader
    var loader = document.getElementById('search-loader');
    if (loader) loader.style.display = 'block';
    
    // Construire l'URL
    var fullUrl = searchUrl + '?title=' + encodeURIComponent(term);
    console.log('URL complète:', fullUrl);
    
    // Créer une requête XHR
    var xhr = new XMLHttpRequest();
    xhr.open('GET', fullUrl, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            console.log('Réponse reçue');
            
            // Mettre à jour les résultats
            var resultsBody = document.getElementById('results-body');
            if (resultsBody) {
                resultsBody.innerHTML = xhr.responseText;
                console.log('Résultats mis à jour');
                
                // Mettre à jour le compteur
                var tableImgs = document.querySelectorAll('.table-img');
                var count = tableImgs ? tableImgs.length : 0;
                
                var countElement = document.querySelector('.employee_count');
                if (countElement) {
                    countElement.textContent = count + ' offre(s) trouvée(s)' + (term ? ' pour "' + term + '"' : '');
                }
                
                // Réinitialiser les icônes Feather si disponible
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            } else {
                console.error('Conteneur de résultats non trouvé!');
            }
        } else {
            console.error('Erreur lors de la requête:', xhr.status);
        }
        
        // Masquer le loader
        if (loader) loader.style.display = 'none';
    };
    
    xhr.onerror = function() {
        console.error('Erreur réseau lors de la requête');
        if (loader) loader.style.display = 'none';
    };
    
    // Envoyer la requête
    xhr.send();
}
