document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-submit form when select changes
    const activitySelect = document.querySelector('.js-filter-activity');
    if (activitySelect) {
        activitySelect.addEventListener('change', function() {
            document.querySelector('.js-filter-form').submit();
        });
    }
    
    const statusSelect = document.querySelector('.js-filter-status');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            document.querySelector('.js-filter-form').submit();
        });
    }
    
    // Instant search - submit form on each keystroke
    const searchInput = document.querySelector('input[name="search"]');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // Submit form immediately on each keystroke
            document.querySelector('.js-filter-form').submit();
        });
    }
    
    // Date field auto-submit
    const dateFilter = document.querySelector('#date_filter');
    if (dateFilter) {
        dateFilter.addEventListener('change', function() {
            document.querySelector('.js-filter-form').submit();
        });
    }
});

/**
 * Function to pass form validation
 * (No date range validation needed with single date field)
 */
function validateDateRange() {
    return true;
}

/**
 * Confirm booking deletion with SweetAlert
 */
function confirmDeleteBooking(id, ticketNumber, deleteUrl) {
    Swal.fire({
        title: 'Delete Booking?',
        html: `Are you sure you want to delete booking <strong>#${id}</strong> (Ticket: ${ticketNumber})? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d93025',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = deleteUrl;
        }
    });
}
