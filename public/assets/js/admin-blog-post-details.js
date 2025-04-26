/**
 * Admin Blog - Post Details with Comments
 * Displays comprehensive post information in a styled SweetAlert2 popup
 */

/**
 * Show comprehensive post details in a SweetAlert popup, including comments
 */
function showPostDetails(post) {
    // Check if SweetAlert is available
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded');
        alert('Could not display post details. SweetAlert2 library is missing.');
        return;
    }
    
    try {
        // Safely extract post data with fallbacks
        const id = post.id || 'N/A';
        const title = post.title || 'Untitled Post';
        const description = post.description || 'No description provided';
        const activity = post.activity || 'No activity assigned';
        const date = post.date || 'Unknown date';
        const author = post.author || 'Unknown author';
        const likes = post.likes || 0;
        const dislikes = post.dislikes || 0;
        const comments = post.comments || 0;
        
        // Create an image section if post has an image
        let imageHtml = '';
        if (post.image) {
            imageHtml = `<div class="post-image-container">
                <img src="${post.image}" class="post-details-image" alt="${title}">
            </div>`;
        }
        
        // Fetch comments if available
        let commentsHtml = '';
        if (post.commentsList && post.commentsList.length > 0) {
            let commentItems = '';
            post.commentsList.forEach(comment => {
                commentItems += `
                <div class="comment-item">
                    <div class="comment-header">
                        <div class="comment-author">${comment.author || 'Anonymous'}</div>
                        <div class="comment-date">${comment.date || ''}</div>
                    </div>
                    <div class="comment-content">${comment.content || ''}</div>
                </div>`;
            });
            
            commentsHtml = `
            <div class="post-comments-section">
                <h3 class="section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 11.5C21.0034 12.8199 20.6951 14.1219 20.1 15.3C19.3944 16.7118 18.3098 17.8992 16.9674 18.7293C15.6251 19.5594 14.0782 19.9994 12.5 20C11.1801 20.0035 9.87812 19.6951 8.7 19.1L3 21L4.9 15.3C4.30493 14.1219 3.99656 12.8199 4 11.5C4.00061 9.92179 4.44061 8.37488 5.27072 7.03258C6.10083 5.69028 7.28825 4.6056 8.7 3.90003C9.87812 3.30496 11.1801 2.99659 12.5 3.00003H13C15.0843 3.11502 17.053 3.99479 18.5291 5.47089C20.0052 6.94699 20.885 8.91568 21 11V11.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Comments (${post.commentsList.length})
                </h3>
                <div class="comments-list">
                    ${commentItems}
                </div>
            </div>`;
        } else {
            commentsHtml = `
            <div class="post-comments-section">
                <h3 class="section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 11.5C21.0034 12.8199 20.6951 14.1219 20.1 15.3C19.3944 16.7118 18.3098 17.8992 16.9674 18.7293C15.6251 19.5594 14.0782 19.9994 12.5 20C11.1801 20.0035 9.87812 19.6951 8.7 19.1L3 21L4.9 15.3C4.30493 14.1219 3.99656 12.8199 4 11.5C4.00061 9.92179 4.44061 8.37488 5.27072 7.03258C6.10083 5.69028 7.28825 4.6056 8.7 3.90003C9.87812 3.30496 11.1801 2.99659 12.5 3.00003H13C15.0843 3.11502 17.053 3.99479 18.5291 5.47089C20.0052 6.94699 20.885 8.91568 21 11V11.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Comments (${comments})
                </h3>
                <div class="no-comments-message">No comments for this post yet.</div>
            </div>`;
        }
        
        // Build engagement stats section
        const engagementHtml = `
        <div class="engagement-stats">
            <div class="stat-item likes">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7 22H4C3.46957 22 2.96086 21.7893 2.58579 21.4142C2.21071 21.0391 2 20.5304 2 20V13C2 12.4696 2.21071 11.9609 2.58579 11.5858C2.96086 11.2107 3.46957 11 4 11H7M14 9V5C14 4.20435 13.6839 3.44129 13.1213 2.87868C12.5587 2.31607 11.7956 2 11 2L7 11V22H18.28C18.7623 22.0055 19.2304 21.8364 19.5979 21.524C19.9654 21.2116 20.2077 20.7769 20.28 20.3L21.66 11.3C21.7035 11.0134 21.6842 10.7207 21.6033 10.4423C21.5225 10.1638 21.3821 9.90629 21.1919 9.68751C21.0016 9.46873 20.7661 9.29393 20.5016 9.17522C20.2371 9.0565 19.9499 8.99672 19.66 9H14Z" stroke="#27ae60" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="stat-value">${likes}</span>
            </div>
            <div class="stat-item dislikes">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17 2H20C20.5304 2 21.0391 2.21071 21.4142 2.58579C21.7893 2.96086 22 3.46957 22 4V11C22 11.5304 21.7893 12.0391 21.4142 12.4142C21.0391 12.7893 20.5304 13 20 13H17M10 15V19C10 19.7956 10.3161 20.5587 10.8787 21.1213C11.4413 21.6839 12.2044 22 13 22L17 13V2H5.72C5.23765 1.99446 4.76958 2.16359 4.40212 2.47599C4.03467 2.78839 3.79235 3.2231 3.72 3.7L2.34 12.7C2.29647 12.9866 2.31583 13.2793 2.39666 13.5577C2.4775 13.8362 2.61791 14.0937 2.80815 14.3125C2.9984 14.5313 3.23393 14.7061 3.49843 14.8248C3.76294 14.9435 4.05009 15.0033 4.34 15H10Z" stroke="#d93025" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="stat-value">${dislikes}</span>
            </div>
        </div>`;

        // Construct the content with modern styling
        Swal.fire({
            title: title,
            html: `
                <div class="post-details-container">
                    <div class="post-details-header">
                        ${imageHtml}
                        <div class="post-meta">
                            <div class="post-meta-row">
                                <div class="meta-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="currentColor"/>
                                    </svg>
                                    <span>Author:</span>
                                </div>
                                <div class="meta-value">${author}</div>
                            </div>
                            <div class="post-meta-row">
                                <div class="meta-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M19 4H18V2H16V4H8V2H6V4H5C3.89 4 3.01 4.9 3.01 6L3 20C3 21.1 3.89 22 5 22H19C20.1 22 21 21.1 21 20V6C21 4.9 20.1 4 19 4ZM19 20H5V10H19V20ZM19 8H5V6H19V8Z" fill="currentColor"/>
                                    </svg>
                                    <span>Date:</span>
                                </div>
                                <div class="meta-value">${date}</div>
                            </div>
                            <div class="post-meta-row">
                                <div class="meta-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M13.5 1.5V9H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M21 14V19.5C21 20.0304 20.7893 20.5391 20.4142 20.9142C20.0391 21.2893 19.5304 21.5 19 21.5H5C4.46957 21.5 3.96086 21.2893 3.58579 20.9142C3.21071 20.5391 3 20.0304 3 19.5V4.5C3 3.96957 3.21071 3.46086 3.58579 3.08579C3.96086 2.71071 4.46957 2.5 5 2.5H12L21 11.5V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span>ID:</span>
                                </div>
                                <div class="meta-value">${id}</div>
                            </div>
                            <div class="post-meta-row">
                                <div class="meta-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M17.5 10H19C20.1 10 21 10.9 21 12V17C21 18.1 20.1 19 19 19H5C3.9 19 3 18.1 3 17V12C3 10.9 3.9 10 5 10H6.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M12 3V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M8.5 7L12 3L15.5 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span>Activity:</span>
                                </div>
                                <div class="meta-value">
                                    <span class="activity-badge">${activity}</span>
                                </div>
                            </div>
                            ${engagementHtml}
                        </div>
                    </div>
                    
                    <div class="post-details-content">
                        <h3 class="section-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 9H9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Description
                        </h3>
                        <div class="post-description">
                            ${description}
                        </div>
                    </div>
                    
                    ${commentsHtml}
                </div>
            `,
            icon: null,
            confirmButtonColor: '#3554d1',
            confirmButtonText: 'Close',
            width: '700px',
            showClass: {
                popup: 'animate__animated animate__fadeIn animate__faster'
            },
            customClass: {
                popup: 'post-details-swal-popup',
                content: 'post-details-swal-content',
                confirmButton: 'post-details-confirm-btn'
            }
        });
    } catch (error) {
        console.error('Error showing post details:', error);
        alert(`Error displaying post details: ${error.message}`);
    }
}

/**
 * Show edit post popup with form for making changes
 */
function showEditPost(post, saveUrl) {
    // Check if SweetAlert is available
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded');
        alert('Could not display edit form. SweetAlert2 library is missing.');
        return;
    }
    
    try {
        // Safely extract post data with fallbacks
        const id = post.id || '';
        const title = post.title || '';
        const description = post.description || '';
        const activity = post.activity || '';
        const activityId = post.activityId || '';
        
        // Get activities for dropdown
        const activities = post.allActivities || [];
        let activityOptions = '';
        
        // Debug to browser console to see actual structure
        console.log('Activities data:', activities);
        
        if (Array.isArray(activities) && activities.length > 0) {
            activities.forEach(act => {
                // Get activity ID safely
                const actId = act.id || act.Id || '';
                const selected = actId == activityId ? 'selected' : '';
                
                // Try different property names that might contain activity name
                const actName = act.activityName || act.ActivityName || act.name || act.Name || act.activity_name || 'Unknown';
                
                // Create option with appropriate values
                activityOptions += `<option value="${actId}" ${selected}>${actName}</option>`;
            });
        } else {
            // Fallback option if no activities
            activityOptions = '<option value="">No activities available</option>';
        }
        
        // Construct the form with existing data
        Swal.fire({
            title: 'Edit Post',
            html: `
                <form id="edit-post-form" class="edit-post-form">
                    <input type="hidden" name="id" value="${id}">
                    
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" value="${title}" class="swal2-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="activity">Activity</label>
                        <select id="activity" name="activityId" class="swal2-select">
                            ${activityOptions}
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="swal2-textarea" rows="5" required>${description}</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Post Image</label>
                        <div class="image-upload-container">
                            <div class="image-preview-container ${post.image ? '' : 'no-image'}" id="imagePreviewContainer">
                                ${post.image ? `<img src="${post.image}" alt="Post image" id="imagePreview">` : '<div class="no-image-placeholder">No image uploaded</div>'}
                            </div>
                            <div class="image-upload-controls">
                                <input type="file" id="imageUpload" name="imageUpload" accept="image/*" class="swal2-file" style="display: none;">
                                <button type="button" id="selectImageBtn" class="select-image-btn">Select Image</button>
                                ${post.image ? '<button type="button" id="removeImageBtn" class="remove-image-btn">Remove Image</button>' : ''}
                            </div>
                        </div>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Save Changes',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3554d1',
            width: '600px',
            showClass: {
                popup: 'animate__animated animate__fadeIn animate__faster'
            },
            customClass: {
                popup: 'edit-post-swal-popup',
                confirmButton: 'edit-post-confirm-btn',
                cancelButton: 'edit-post-cancel-btn'
            },
            didOpen: () => {
                // Set up image upload preview functionality
                const imageUpload = document.getElementById('imageUpload');
                const imagePreview = document.getElementById('imagePreview');
                const imagePreviewContainer = document.getElementById('imagePreviewContainer');
                const selectImageBtn = document.getElementById('selectImageBtn');
                const removeImageBtn = document.getElementById('removeImageBtn');
                
                // Handle select image button click
                selectImageBtn.addEventListener('click', () => {
                    imageUpload.click();
                });
                
                // Handle image preview when file is selected
                imageUpload.addEventListener('change', (e) => {
                    if (e.target.files.length > 0) {
                        const file = e.target.files[0];
                        const reader = new FileReader();
                        
                        reader.onload = (e) => {
                            if (!imagePreview) {
                                const newImg = document.createElement('img');
                                newImg.id = 'imagePreview';
                                newImg.alt = 'Post image';
                                imagePreviewContainer.innerHTML = '';
                                imagePreviewContainer.appendChild(newImg);
                                imagePreview = newImg;
                            }
                            
                            imagePreview.src = e.target.result;
                            imagePreviewContainer.classList.remove('no-image');
                            
                            // Add remove button if it doesn't exist
                            if (!removeImageBtn) {
                                const newRemoveBtn = document.createElement('button');
                                newRemoveBtn.id = 'removeImageBtn';
                                newRemoveBtn.className = 'remove-image-btn';
                                newRemoveBtn.textContent = 'Remove Image';
                                document.querySelector('.image-upload-controls').appendChild(newRemoveBtn);
                                
                                // Add event listener to the new button
                                newRemoveBtn.addEventListener('click', removeImage);
                            }
                        };
                        
                        reader.readAsDataURL(file);
                    }
                });
                
                // Handle remove image button click
                if (removeImageBtn) {
                    removeImageBtn.addEventListener('click', removeImage);
                }
                
                function removeImage() {
                    if (imagePreview) {
                        imagePreview.remove();
                    }
                    imagePreviewContainer.classList.add('no-image');
                    imagePreviewContainer.innerHTML = '<div class="no-image-placeholder">No image uploaded</div>';
                    imageUpload.value = '';
                    
                    // Create a hidden input to signal image removal
                    const removeImageInput = document.createElement('input');
                    removeImageInput.type = 'hidden';
                    removeImageInput.name = 'removeImage';
                    removeImageInput.value = '1';
                    document.getElementById('edit-post-form').appendChild(removeImageInput);
                    
                    // Remove the remove button
                    if (removeImageBtn) {
                        removeImageBtn.remove();
                    }
                }
            },
            preConfirm: () => {
                // Validate form
                const form = document.getElementById('edit-post-form');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return false;
                }
                
                // Get form values
                const formData = new FormData(form);
                
                // Add the file upload if one was selected
                const imageUpload = document.getElementById('imageUpload');
                if (imageUpload.files.length > 0) {
                    formData.append('picture', imageUpload.files[0]);
                }
                
                // Submit form via AJAX (without setting Content-Type header to allow file upload)
                return fetch(saveUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error}`);
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show success message
                Swal.fire({
                    title: 'Success!',
                    text: 'Post updated successfully',
                    icon: 'success',
                    confirmButtonColor: '#3554d1'
                }).then(() => {
                    // Reload the page to show updated data
                    window.location.reload();
                });
            }
        });
    } catch (error) {
        console.error('Error showing edit post form:', error);
        alert(`Error displaying edit form: ${error.message}`);
    }
}
