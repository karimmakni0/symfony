/**
 * Admin Blog - User Details Popup
 * Displays comprehensive user information in a styled SweetAlert2 popup
 */

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
        const id = userData.id || 'N/A';
        const name = userData.name || 'Unknown';
        const email = userData.email || 'No email provided';
        const roles = userData.roles || 'No roles assigned';
        const phone = userData.phone || 'Not provided';
        
        // Create an avatar with user initials if no image is provided
        let avatarContent = '';
        if (userData.image) {
            avatarContent = `<img src="${userData.image}" alt="${name}" class="user-avatar-img">`;
        } else {
            // Get initials from name
            const initials = name.split(' ')
                .map(part => part.charAt(0).toUpperCase())
                .slice(0, 2)
                .join('');
                
            avatarContent = `<div class="user-avatar-initials">${initials}</div>`;
        }
        
        // Construct the content with modern styling
        Swal.fire({
            title: 'User Details',
            html: `
                <div class="user-details-container">
                    <div class="user-details-header">
                        <div class="user-avatar-large">
                            ${avatarContent}
                        </div>
                        <div class="user-primary-info">
                            <h3 class="user-name-large">${name}</h3>
                            <div class="user-id">ID: ${id}</div>
                        </div>
                    </div>
                    
                    <div class="user-details-content">
                        <div class="user-detail-row">
                            <div class="detail-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M22 6C22 4.9 21.1 4 20 4H4C2.9 4 2 4.9 2 6V18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V6ZM20 6L12 11L4 6H20ZM20 18H4V8L12 13L20 8V18Z" fill="currentColor"/>
                                </svg>
                                <span>Email:</span>
                            </div>
                            <div class="detail-value">${email}</div>
                        </div>
                        <div class="user-detail-row">
                            <div class="detail-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20 10.999H22C22 5.869 18.13 1.999 13 1.999V3.999C16.9 3.999 20 7.099 20 10.999ZM16 10.999H18C18 8.789 16.21 6.999 14 6.999V8.999C15.1 8.999 16 9.899 16 10.999ZM13 14.999V12.999H11C11 12.449 11.45 11.999 12 11.999H13C14.1 11.999 15 11.099 15 9.999C15 8.899 14.1 7.999 13 7.999H8V9.999H13C13.55 9.999 14 10.449 14 10.999C14 11.549 13.55 11.999 13 11.999H12C10.9 11.999 10 12.899 10 13.999V14.999H6V16.999H18V14.999H13Z" fill="currentColor"/>
                                </svg>
                                <span>Phone:</span>
                            </div>
                            <div class="detail-value">${phone}</div>
                        </div>
                        <div class="user-detail-row">
                            <div class="detail-label">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="currentColor"/>
                                </svg>
                                <span>Roles:</span>
                            </div>
                            <div class="detail-value">${roles}</div>
                        </div>
                    </div>
                </div>
            `,
            icon: null,
            confirmButtonColor: '#3554d1',
            confirmButtonText: 'Close',
            width: '500px',
            showClass: {
                popup: 'animate__animated animate__fadeIn animate__faster'
            },
            customClass: {
                popup: 'user-details-swal-popup',
                content: 'user-details-swal-content',
                confirmButton: 'user-details-confirm-btn'
            }
        });
    } catch (error) {
        console.error('Error showing user details:', error);
        alert(`Error displaying user details: ${error.message}`);
    }
}
