document.querySelectorAll('.delete-site-btn').forEach(button => {
    button.addEventListener('click', function (e) {
        e.stopPropagation(); // ⭐ Empêche la propagation du clic au lien parent
        const siteId = this.getAttribute('data-site-id');
        const siteName = this.getAttribute('data-site-name');
        // ... (le reste du code reste identique)


        document.querySelectorAll('.delete-site-btn').forEach(button => {
            button.addEventListener('click', function (e) {
                e.stopPropagation(); // Empêche le clic sur le lien parent
                const siteId = this.getAttribute('data-site-id');
                const siteName = this.getAttribute('data-site-name');

                Swal.fire({
                    title: 'Supprimer ce site ?',
                    text: `Êtes-vous sûr de vouloir supprimer "${siteName}" ? Cette action est irréversible.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Oui, supprimer',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Appel AJAX
                        fetch('delete_site.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `site_id=${encodeURIComponent(siteId)}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire(
                                        'Supprimé !',
                                        'Le site a été supprimé avec succès.',
                                        'success'
                                    ).then(() => {
                                        window.location.reload(); // Recharge la page
                                    });
                                } else {
                                    Swal.fire(
                                        'Erreur',
                                        data.message || 'Impossible de supprimer le site.',
                                        'error'
                                    );
                                }
                            })
                            .catch(error => {
                                Swal.fire(
                                    'Erreur',
                                    'Erreur réseau. Veuillez réessayer.',
                                    'error'
                                );
                            });
                    }
                });
            });
        });
    });
});