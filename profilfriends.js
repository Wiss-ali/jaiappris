//js

// Attendez que le document soit chargé
document.addEventListener('DOMContentLoaded', function() {
    // Ajoutez un écouteur d'événements au clic sur le document
    document.addEventListener('click', function(event) {
        // Appelez la fonction toggleDropdown pour gérer l'affichage du menu déroulant
        toggleDropdown(event);
        // Appelez la fonction handleFriendActions pour gérer les actions d'amis
        handleFriendActions(event);
    });
});

// Fonction pour basculer l'affichage du menu déroulant
function toggleDropdown(event) {
    const friendLogo = document.querySelector('.friend-logo');
    const friendActions = document.querySelector('.friend-actions');
    if (friendLogo && friendActions) {
        // Vérifiez si la cible de l'événement est à l'intérieur du logo d'ami
        if (friendLogo.contains(event.target)) {
            // Affichez ou masquez le menu déroulant en fonction de son état actuel
            friendActions.style.display = friendActions.style.display === 'none' ? 'block' : 'none';
        } else if (!friendActions.contains(event.target)) {
            // Masquez le menu déroulant si la cible n'est pas à l'intérieur
            friendActions.style.display = 'none';
        }
    }
}

// Fonction pour gérer les actions d'amis
function handleFriendActions(event) {
    if (event.target.classList.contains('action-friend')) {
        event.preventDefault();
        const userId = event.target.getAttribute('data-user-id');
        const action = event.target.getAttribute('data-action');
        // Obtenez l'URL de l'action à partir de l'action spécifiée
        let actionUrl = getActionUrl(action);
        if (actionUrl) {
            // Appelez la fonction handleFriendAction pour effectuer l'action sur l'ami
            handleFriendAction(userId, actionUrl);
        }
    }
}

// Fonction pour obtenir l'URL de l'action en fonction de l'action spécifiée
function getActionUrl(action) {
    const actionUrls = {
        'add': 'add-friend.php',
        'remove': 'remove-friend.php',
        'block': 'block-user.php',
        'accept': 'accept-friend-request.php',
        'decline': 'decline-friend-request.php'
    };
    return actionUrls[action] || '';
}

// Fonction pour gérer l'action d'ami en effectuant une requête HTTP
function handleFriendAction(userId, actionUrl) {
    fetch(actionUrl, {
        method: 'POST',
        body: JSON.stringify({ user2_id: userId }),
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message); // Ou mettez à jour l'interface utilisateur selon votre préférence
            location.reload(); // Rechargez la page pour voir les changements
        } else {
            console.error('Erreur :', data.message);
        }
    })
    .catch((error) => {
        console.error('Erreur :', error);
    });
}

//jQuery

$(document).ready(function() {
    // Basculer le menu déroulant des paramètres
    $('.settings-icon').click(function() {
        $(this).next('.settings-dropdown').toggle();
    });

    // Supprimer une publication
    $('.delete-post').click(function(e) {
        e.preventDefault();
        const publicationId = $(this).data('publication-id');
        if (confirm('Êtes-vous sûr de vouloir supprimer cette publication ?')) {
            $.ajax({
                url: 'sup-publication.php',
                type: 'POST',
                data: { id: publicationId },
                success: function(response) {
                    $('.publication[data-publication-id="' + publicationId + '"]').remove();
                }
            });
        }
    });

    // Masquer le menu déroulant des paramètres en cliquant à l'extérieur
    $(window).click(function(e) {
        if (!$(e.target).hasClass('settings-icon') && !$(e.target).parents('.settings-menu').length) {
            $('.settings-dropdown').hide();
        }
    });

    // Basculer la popup d'ajout de publication
    $('#btnAjouterPost').click(function() {
        togglePopup();
    });

    // Gérer la soumission du formulaire de "J'aime"
    $(document).on('submit', '.like-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const publicationId = form.data('publication-id');
        const url = form.attr('action');

        $.ajax({
            type: "POST",
            url: url,
            data: form.serialize(),
            success: function(response) {
                console.log("Réponse reçue : ", response);
                if (response.newLikeCount !== undefined) {
                    $('.like-count[data-publication-id="' + publicationId + '"]').text(response.newLikeCount);
                    form.find('[name="like"]').toggleClass('liked');
                } else if (response.error) {
                    console.error("Erreur : ", response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error("Erreur AJAX : ", error);
            }
        });
    });

    // Ajoutez d'autres scripts si nécessaire...
});

function togglePopup() {
    var popup = document.getElementById('popupForm');
    popup.style.display = popup.style.display === 'none' ? 'block' : 'none';
}
