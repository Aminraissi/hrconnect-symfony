// Script de recherche en temps réel ultra-simple pour les offres d'emploi

// Attendre que le document soit prêt
$(function() {
    console.log('Script de recherche simplifié chargé');

    // Fonction de recherche simplifiée
    function search() {
        // Afficher le loader
        $('#search-loader').show();

        // Récupérer la valeur de recherche
        var term = $('#search-title').val();
        console.log('Recherche de:', term);

        // Construire l'URL
        var url = searchRouteUrl + '?title=' + encodeURIComponent(term);

        // Requête AJAX simplifiée
        $.get(url, function(data) {
            // Mettre à jour les résultats
            $('#results-body').html(data);

            // Mettre à jour le compteur
            var count = $('.table-img').length;
            $('.employee_count').text(count + ' offre(s) trouvée(s)' + (term ? ' pour "' + term + '"' : ''));

            // Masquer le loader
            $('#search-loader').hide();

            console.log('Recherche terminée, ' + count + ' résultats');
        }).fail(function() {
            console.error('Erreur de recherche');
            $('#search-loader').hide();
        });
    }

    // Détecter les changements dans le champ de recherche
    $('#search-title').on('keyup', function() {
        search();
    });

    // Empêcher la soumission normale du formulaire
    $('#advanced-search-form').on('submit', function(e) {
        e.preventDefault();
        search();
    });

    // Recherche initiale
    search();
});
