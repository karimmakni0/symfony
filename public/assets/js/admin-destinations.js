document.addEventListener('DOMContentLoaded', function() {
    // Initialize all tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Fix table layout issues
    fixTableLayout();
    
    // Re-check on window resize
    window.addEventListener('resize', function() {
        fixTableLayout();
    });
    
    // Real-time filtering
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            document.querySelector('.js-filter-form').submit();
        });
    }
});

/**
 * Fix table layout issues
 */
function fixTableLayout() {
    const tableContainer = document.querySelector('.tabs-table-container');
    if (tableContainer) {
        // Ensure the container has proper width
        const parentWidth = tableContainer.parentElement.offsetWidth;
        tableContainer.style.maxWidth = parentWidth + 'px';
        
        // Ensure proper scroll indication if needed
        const table = tableContainer.querySelector('table');
        if (table && table.offsetWidth > tableContainer.offsetWidth) {
            tableContainer.classList.add('has-scroll');
        } else if (table) {
            tableContainer.classList.remove('has-scroll');
        }
    }
}

/**
 * Confirm destination deletion with SweetAlert
 */
function confirmDeleteDestination(id, name) {
    Swal.fire({
        title: 'Delete Destination?',
        html: `Are you sure you want to delete destination <strong>${name}</strong>? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d93025',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `/admin/delete_destination/${id}`;
        }
    });
    
    return false; // Prevent default link behavior
}

/**
 * Show the full description details in a SweetAlert popup
 */
function showDescriptionDetails(destinationName, description) {
    // Check if SweetAlert is available
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded');
        alert('Could not display description. SweetAlert2 library is missing.');
        return;
    }
    
    try {
        Swal.fire({
            title: destinationName,
            html: `
                <div class="description-details-popup">
                    <h3>Description</h3>
                    <p>${description}</p>
                </div>
            `,
            icon: 'info',
            confirmButtonColor: '#3554d1',
            confirmButtonText: 'Close',
            width: '600px',
            customClass: {
                popup: 'description-details-swal-popup',
                content: 'description-details-swal-content'
            }
        });
    } catch (error) {
        console.error('Error showing description details:', error);
        alert(`Error displaying description details: ${error.message}`);
    }
}

/**
 * Show comprehensive user details in a SweetAlert popup
 */
function showUserDetails(userData) {
    // Check if SweetAlert is available
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded');
        alert('Could not display user details. SweetAlert2 library is missing.');
        return;
    }
    
    try {
        // Safely extract user data with fallbacks
        const fullName = userData.lastname ? `${userData.name} ${userData.lastname}` : userData.name;
        
        Swal.fire({
            title: 'User Details',
            html: `
                <div class="user-details-popup">
                    <div class="detail-item">
                        <span class="detail-label">ID:</span>
                        <span class="detail-value">#${userData.id}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value">${fullName}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value">${userData.email}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value">${userData.phone}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Role:</span>
                        <span class="detail-value">${userData.roles}</span>
                    </div>
                </div>
            `,
            icon: 'info',
            confirmButtonColor: '#3554d1',
            confirmButtonText: 'Close',
            width: '500px',
            customClass: {
                popup: 'user-details-swal-popup',
                content: 'user-details-swal-content'
            }
        });
    } catch (error) {
        console.error('Error showing user details:', error);
        alert(`Error displaying user details: ${error.message}`);
    }
}
