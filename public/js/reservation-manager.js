// Reservation Management JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Get the update URL template from the data attribute
    var updateUrlTemplate = document.getElementById('js-update-url-template').getAttribute('data-url');
    
    // Toggle reservation details - improved implementation
    function toggleDetails(event) {
        event.preventDefault();
        var id = this.getAttribute('data-id');
        if (!id) return;
        
        console.log('Toggling details for reservation ID:', id);
        
        var detailsRow = document.querySelector('.reservation-details-' + id);
        if (detailsRow) {
            var currentDisplay = window.getComputedStyle(detailsRow).display;
            detailsRow.style.display = (currentDisplay === 'none' || currentDisplay === '') ? 'table-row' : 'none';
            console.log('Found details row, toggled display to:', detailsRow.style.display);
        } else {
            console.log('Details row not found for ID:', id);
        }
    }
    
    // Apply click handler to all toggle buttons
    var toggleButtons = document.querySelectorAll('.view-details, .toggle-details');
    console.log('Found', toggleButtons.length, 'toggle buttons');
    
    for (var i = 0; i < toggleButtons.length; i++) {
        toggleButtons[i].addEventListener('click', toggleDetails);
    }

    // Helper function to submit a form
    function submitForm(url) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        document.body.appendChild(form);
        form.submit();
    }

    // Confirm reservation
    document.querySelectorAll('.confirm-reservation').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var routeUrl = this.getAttribute('data-route');
            
            if (!routeUrl && updateUrlTemplate) {
                routeUrl = updateUrlTemplate
                    .replace('ID_PLACEHOLDER', id)
                    .replace('STATUS_PLACEHOLDER', 'Confirmed');
            }

            Swal.fire({
                title: 'Confirm Reservation',
                text: 'Are you sure you want to confirm this reservation?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3554D1',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, confirm it!'
            }).then(function(result) {
                if (result.isConfirmed && routeUrl) {
                    submitForm(routeUrl);
                }
            });
        });
    });

    // Reject reservation
    document.querySelectorAll('.reject-reservation').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var routeUrl = this.getAttribute('data-route');
            
            if (!routeUrl && updateUrlTemplate) {
                routeUrl = updateUrlTemplate
                    .replace('ID_PLACEHOLDER', id)
                    .replace('STATUS_PLACEHOLDER', 'Rejected');
            }

            Swal.fire({
                title: 'Reject Reservation',
                text: 'Are you sure you want to reject this reservation?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3554D1',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, reject it!'
            }).then(function(result) {
                if (result.isConfirmed && routeUrl) {
                    submitForm(routeUrl);
                }
            });
        });
    });

    // Row hover effects
    document.querySelectorAll('.reservation-row').forEach(function(row) {
        row.addEventListener('mouseenter', function() { 
            this.classList.add('bg-blue-1-05'); 
        });
        row.addEventListener('mouseleave', function() { 
            this.classList.remove('bg-blue-1-05'); 
        });
    });
});
