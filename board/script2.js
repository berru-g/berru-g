document.addEventListener('DOMContentLoaded', function () {
    // Gestion lMenu mobile 
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.querySelector('.sidebar');

    mobileMenuBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        sidebar.classList.toggle('active');
    });

    // Fermer le menu quand on clique à l'extérieur
    document.addEventListener('click', function (e) {
        if (!sidebar.contains(e.target) && e.target !== mobileMenuBtn) {
            sidebar.classList.remove('active');
        }
    });

    // Création de la modale
    const modal = document.getElementById('messageModal');

    // Gestion des clics sur les lignes
    document.querySelectorAll('.message-row').forEach(row => {
        row.addEventListener('click', function (e) {
            if (e.target.tagName === 'A' || e.target.closest('a') ||
                e.target.classList.contains('view-btn') || e.target.closest('.view-btn') ||
                e.target.classList.contains('status-btn') || e.target.closest('.status-btn')) {
                return;
            }

            const messageId = this.getAttribute('data-id');
            const isRead = this.getAttribute('data-read') === '1';
            const fullMessage = this.getAttribute('data-fullmessage');
            const senderName = this.cells[1].textContent;
            const senderEmail = this.cells[2].querySelector('a').textContent.trim();
            const senderPhone = this.cells[4].textContent;
            const senderBudget = this.cells[5].textContent;
            const messageDate = this.cells[0].textContent;

            // Afficher les détails dans la modale
            document.getElementById('modal-name').textContent = senderName;
            document.getElementById('modal-email').textContent = senderEmail;
            document.getElementById('modal-email').href = `mailto:${senderEmail}`;
            document.getElementById('modal-phone').textContent = senderPhone;
            document.getElementById('modal-budget').textContent = senderBudget;
            document.getElementById('modal-date').textContent = messageDate;
            document.getElementById('modal-message').innerHTML = nl2br(fullMessage);

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            if (!isRead) {
                markAsRead(messageId, this);
            }
        });
    });

    // Gestion des boutons d'action dans le tableau
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const row = this.closest('tr');
            row.click();
        });
    });

    // Gestion des boutons de la modale
    modal.querySelector('.reply-btn').addEventListener('click', function () {
        const email = document.getElementById('modal-email').textContent;
        window.location.href = `mailto:${email}?subject=Re: Votre message`;
    });

    modal.querySelector('.mark-read-btn').addEventListener('click', function () {
        const row = document.querySelector(`.message-row[data-id="${document.getElementById('message-id').textContent}"]`);
        if (row && row.classList.contains('unread')) {
            markAsRead(row.getAttribute('data-id'), row);
        }
    });

    modal.querySelector('.close-btn').addEventListener('click', closeModal);
    modal.querySelector('.close-modal').addEventListener('click', closeModal);

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Fonction pour convertir les retours à la ligne
    function nl2br(str) {
        return str.replace(/\n/g, '<br>');
    }

    // Fonction markAsRead existante
    function markAsRead(messageId, rowElement) {
        fetch('mark_as_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                id: messageId
            })
        })
            .then(response => {
                if (!response.ok) throw new Error('Erreur réseau');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    rowElement.classList.remove('unread');
                    document.getElementById('unreadCount').textContent = data.unread;
                    document.getElementById('unreadBadge').style.display =
                        data.unread > 0 ? 'flex' : 'none';
                } else {
                    console.error('Erreur:', data.error);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
    }

    // Fonctionnalités de tri et pagination existantes
    const table = document.getElementById('messagesTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const searchInput = document.getElementById('searchInput');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const pageNumbers = document.getElementById('pageNumbers');
    const firstBtn = document.getElementById('firstBtn');
    const lastBtn = document.getElementById('lastBtn');
    const statusFilter = document.getElementById('statusFilter');
    const dateFilter = document.getElementById('dateFilter');
    const clearFilters = document.getElementById('clearFilters');

    let currentPage = 1;
    const rowsPerPage = 10;
    let filteredRows = rows;
    let sortColumn = null;
    let sortDirection = 'asc';

    // Recherche instantanée
    searchInput.addEventListener('input', filterRows);
    statusFilter.addEventListener('change', filterRows);
    dateFilter.addEventListener('change', filterRows);
    clearFilters.addEventListener('click', function () {
        searchInput.value = '';
        statusFilter.value = 'all';
        dateFilter.value = 'all';
        filterRows();
    });

    function filterRows() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;
        const dateValue = dateFilter.value;
        const today = new Date();

        filteredRows = rows.filter(row => {
            // Filtre de recherche
            const matchesSearch = searchTerm === '' ||
                Array.from(row.cells).some(cell =>
                    cell.textContent.toLowerCase().includes(searchTerm)
                );

            // Filtre par statut
            const statusCell = row.cells[3].textContent.toLowerCase();
            const matchesStatus = statusValue === 'all' ||
                (statusValue === 'new' && row.classList.contains('unread')) ||
                (statusValue === 'in_progress' && statusCell.includes('en cours')) ||
                (statusValue === 'completed' && statusCell.includes('terminé'));

            // Filtre par date
            const dateCell = new Date(row.cells[0].textContent);
            let matchesDate = true;

            if (dateValue !== 'all') {
                const diffTime = today - dateCell;
                const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

                if (dateValue === 'today' && diffDays !== 0) matchesDate = false;
                if (dateValue === 'week' && diffDays > 7) matchesDate = false;
                if (dateValue === 'month' && diffDays > 30) matchesDate = false;
            }

            return matchesSearch && matchesStatus && matchesDate;
        });

        currentPage = 1;
        updateTable();
        updatePagination();
    }

    // Tri des colonnes
    table.querySelectorAll('th').forEach(header => {
        header.addEventListener('click', () => {
            const columnIndex = header.cellIndex;

            // Reset les autres en-têtes
            table.querySelectorAll('th').forEach(th => {
                if (th !== header) {
                    th.classList.remove('sorted-asc', 'sorted-desc');
                    th.querySelector('i').className = 'fas fa-sort';
                }
            });

            // Toggle la direction de tri
            if (sortColumn === columnIndex) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortColumn = columnIndex;
                sortDirection = 'asc';
            }

            header.classList.toggle('sorted-asc', sortDirection === 'asc');
            header.classList.toggle('sorted-desc', sortDirection === 'desc');

            const icon = header.querySelector('i');
            icon.className = sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';

            filteredRows.sort((a, b) => {
                let aValue = a.cells[columnIndex].textContent.toLowerCase();
                let bValue = b.cells[columnIndex].textContent.toLowerCase();

                // Gestion spéciale pour les dates
                if (columnIndex === 0) {
                    aValue = new Date(aValue);
                    bValue = new Date(bValue);
                    return sortDirection === 'asc' ? aValue - bValue : bValue - aValue;
                }

                // Gestion spéciale pour les budgets
                if (columnIndex === 5) {
                    aValue = parseFloat(aValue) || 0;
                    bValue = parseFloat(bValue) || 0;
                    return sortDirection === 'asc' ? aValue - bValue : bValue - aValue;
                }

                // Tri texte standard
                if (aValue < bValue) return sortDirection === 'asc' ? -1 : 1;
                if (aValue > bValue) return sortDirection === 'asc' ? 1 : -1;
                return 0;
            });

            updateTable();
        });
    });

    // Pagination
    function updatePagination() {
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        const totalItems = filteredRows.length;
        const startItem = (currentPage - 1) * rowsPerPage + 1;
        const endItem = Math.min(currentPage * rowsPerPage, totalItems);

        // Mise à jour des informations
        document.getElementById('startItem').textContent = startItem;
        document.getElementById('endItem').textContent = endItem;
        document.getElementById('totalItems').textContent = totalItems;

        // Boutons de navigation
        firstBtn.disabled = currentPage === 1;
        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage === totalPages || totalPages === 0;
        lastBtn.disabled = currentPage === totalPages || totalPages === 0;

        // Numéros de page
        pageNumbers.innerHTML = '';
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.textContent = i;
            pageBtn.className = i === currentPage ? 'page-number active' : 'page-number';
            pageBtn.addEventListener('click', () => {
                currentPage = i;
                updateTable();
                updatePagination();
            });
            pageNumbers.appendChild(pageBtn);
        }
    }

    // Mise à jour du tableau
    function updateTable() {
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const paginatedRows = filteredRows.slice(start, end);

        tbody.innerHTML = '';
        paginatedRows.forEach(row => tbody.appendChild(row));
    }

    // Événements pagination
    firstBtn.addEventListener('click', () => {
        currentPage = 1;
        updateTable();
        updatePagination();
    });

    prevBtn.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            updateTable();
            updatePagination();
        }
    });

    nextBtn.addEventListener('click', () => {
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            updateTable();
            updatePagination();
        }
    });

    lastBtn.addEventListener('click', () => {
        currentPage = Math.ceil(filteredRows.length / rowsPerPage);
        updateTable();
        updatePagination();
    });

    // Initialisation
    updatePagination();
    filterRows();

    // Export JSON
    document.getElementById('exportJsonBtn').addEventListener('click', async () => {
        try {
            const btn = document.getElementById('exportJsonBtn');
            const originalHTML = btn.innerHTML;

            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Export...';
            btn.disabled = true;

            const response = await fetch('export_to_json.php');
            const data = await response.json();

            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = `export_${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();

            setTimeout(() => {
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }, 100);

        } catch (error) {
            console.error('Erreur export:', error);
            Swal.fire('Erreur', 'Une erreur est survenue lors de l\'export', 'error');
        }
    });

    // Import JSON (exemple - à adapter)
    document.getElementById('importJsonBtn').addEventListener('click', function () {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';

        input.onchange = e => {
            const file = e.target.files[0];
            const reader = new FileReader();

            reader.onload = event => {
                try {
                    const jsonData = JSON.parse(event.target.result);
                    // Envoyer les données au serveur
                    Swal.fire({
                        title: 'Confirmer l\'import',
                        text: `Vous êtes sur le point d'importer ${jsonData.length} entrées. Continuer?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Importer',
                        cancelButtonText: 'Annuler'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch('import_from_json.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify(jsonData)
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire('Succès', `Import réussi: ${data.count} entrées`, 'success')
                                            .then(() => location.reload());
                                    } else {
                                        Swal.fire('Erreur', data.message || 'Erreur lors de l\'import', 'error');
                                    }
                                })
                                .catch(error => {
                                    Swal.fire('Erreur', 'Une erreur est survenue', 'error');
                                    console.error(error);
                                });
                        }
                    });
                } catch (error) {
                    Swal.fire('Erreur', 'Fichier JSON invalide', 'error');
                }
            };

            reader.readAsText(file);
        };

        input.click();
    });
});