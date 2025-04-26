// Script de débogage pour vérifier que jQuery fonctionne correctement

console.log('Script de débogage chargé');

// Vérifier que jQuery est chargé
if (typeof jQuery !== 'undefined') {
    console.log('jQuery est chargé (version ' + jQuery.fn.jquery + ')');
} else {
    console.error('jQuery n\'est pas chargé!');
}

// Vérifier que les éléments existent
$(document).ready(function() {
    console.log('Document prêt');
    
    // Vérifier le formulaire
    if ($('#advanced-search-form').length) {
        console.log('Formulaire trouvé');
    } else {
        console.error('Formulaire non trouvé!');
    }
    
    // Vérifier le champ de recherche
    if ($('#search-title').length) {
        console.log('Champ de recherche trouvé');
        
        // Ajouter un gestionnaire d'événement de test
        $('#search-title').on('input', function() {
            console.log('Événement input détecté sur #search-title');
            console.log('Valeur actuelle:', $(this).val());
        });
        
        // Simuler une saisie
        $('#search-title').val('test').trigger('input');
    } else {
        console.error('Champ de recherche non trouvé!');
    }
    
    // Vérifier le conteneur de résultats
    if ($('#results-body').length) {
        console.log('Conteneur de résultats trouvé');
    } else {
        console.error('Conteneur de résultats non trouvé!');
    }
});
