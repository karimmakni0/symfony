// Blog page alert functions
document.addEventListener('DOMContentLoaded', function() {
    // Make sure these functions are available globally
    window.confirmDeleteBlog = function(id, title, deleteUrl) {
        if (event) event.preventDefault();
        
        if (typeof Swal === 'undefined') {
            if (confirm("Are you sure you want to delete this post: " + title + "? This action cannot be undone.")) {
                window.location.href = deleteUrl;
            }
            return false;
        }
        
        Swal.fire({
            title: 'Are you sure?',
            html: "You are about to delete the blog post: <strong>" + title + "</strong>.<br>This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d93025',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = deleteUrl;
            }
        });
        
        return false;
    };
    
    window.showUserDetails = function(user) {
        if (typeof Swal === 'undefined') {
            alert("User details: " + JSON.stringify(user));
            return;
        }
        
        Swal.fire({
            title: 'User Details',
            html: 
                '<div class="user-details-popup">' +
                    '<div class="user-info-row">' +
                        '<div class="user-info-label">Name:</div>' +
                        '<div class="user-info-value">' + user.name + '</div>' +
                    '</div>' +
                    '<div class="user-info-row">' +
                        '<div class="user-info-label">Email:</div>' +
                        '<div class="user-info-value">' + user.email + '</div>' +
                    '</div>' +
                    '<div class="user-info-row">' +
                        '<div class="user-info-label">ID:</div>' +
                        '<div class="user-info-value">#' + user.id + '</div>' +
                    '</div>' +
                    '<div class="user-info-row">' +
                        '<div class="user-info-label">Roles:</div>' +
                        '<div class="user-info-value">' + user.roles + '</div>' +
                    '</div>' +
                '</div>',
            width: '500px',
            padding: '20px',
            customClass: {
                popup: 'user-details-swal-popup',
                title: 'user-details-swal-title',
                htmlContainer: 'user-details-swal-html'
            }
        });
    };
    
    window.showPostDetails = function(post) {
        if (typeof Swal === 'undefined') {
            alert("Post details: " + JSON.stringify(post));
            return;
        }
        
        var imageHtml = '';
        if (post.image) {
            imageHtml = '<img src="' + post.image + '" class="post-details-image" alt="' + post.title + '">';
        }
        
        Swal.fire({
            title: '<span class="details-title-icon"><i class="icon-file-text"></i></span>' + post.title,
            html: 
                '<div class="post-details-popup">' +
                    imageHtml +
                    '<div class="post-info-row">' +
                        '<div class="post-info-label">ID:</div>' +
                        '<div class="post-info-value">#' + post.id + '</div>' +
                    '</div>' +
                    '<div class="post-info-row">' +
                        '<div class="post-info-label">Activity:</div>' +
                        '<div class="post-info-value">' + post.activity + '</div>' +
                    '</div>' +
                    '<div class="post-info-row">' +
                        '<div class="post-info-label">Date:</div>' +
                        '<div class="post-info-value">' + post.date + '</div>' +
                    '</div>' +
                    '<div class="post-info-row">' +
                        '<div class="post-info-label">Author:</div>' +
                        '<div class="post-info-value">' + post.author + '</div>' +
                    '</div>' +
                    '<div class="post-info-row">' +
                        '<div class="post-info-label">Engagement:</div>' +
                        '<div class="post-info-value">' +
                            '<div class="engagement-stats">' +
                                '<div class="stat-badge"><i class="icon-message-circle"></i> ' + post.comments + ' Comments</div>' +
                                '<div class="stat-badge like-badge"><i class="icon-thumbs-up"></i> ' + post.likes + ' Likes</div>' +
                                '<div class="stat-badge dislike-badge"><i class="icon-thumbs-down"></i> ' + post.dislikes + ' Dislikes</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="post-info-row">' +
                        '<div class="post-info-label">Description:</div>' +
                        '<div class="post-info-value">' +
                            '<div class="post-description-content">' + post.description + '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>',
            width: '700px',
            padding: '20px',
            customClass: {
                popup: 'post-details-swal-popup',
                title: 'post-details-swal-title',
                htmlContainer: 'user-details-swal-html'
            }
        });
    };
    
    window.showPostEngagement = function(post) {
        if (typeof Swal === 'undefined') {
            alert("Post engagement: " + JSON.stringify(post));
            return;
        }
        
        var totalEngagement = post.comments + post.likes + post.dislikes;
        var likePercentage = totalEngagement > 0 ? ((post.likes / totalEngagement) * 100).toFixed(1) : 0;
        var dislikePercentage = totalEngagement > 0 ? ((post.dislikes / totalEngagement) * 100).toFixed(1) : 0;
        var commentPercentage = totalEngagement > 0 ? ((post.comments / totalEngagement) * 100).toFixed(1) : 0;
        
        Swal.fire({
            title: '<span class="details-title-icon"><i class="icon-bar-chart-2"></i></span>Post Engagement',
            html: 
                '<div class="engagement-details-popup">' +
                    '<div class="engagement-post-title">' + post.title + '</div>' +
                    
                    '<div class="engagement-summary">' +
                        '<div class="total-engagement">' +
                            '<div class="total-number">' + totalEngagement + '</div>' +
                            '<div class="total-label">Total Engagements</div>' +
                        '</div>' +
                    '</div>' +
                    
                    '<div class="engagement-metrics">' +
                        '<div class="metric-row">' +
                            '<div class="metric-label"><i class="icon-message-circle"></i> Comments</div>' +
                            '<div class="metric-bar-container">' +
                                '<div class="metric-bar comment-bar" style="width: ' + commentPercentage + '%"></div>' +
                            '</div>' +
                            '<div class="metric-value">' + post.comments + ' <span class="metric-percent">(' + commentPercentage + '%)</span></div>' +
                        '</div>' +
                        
                        '<div class="metric-row">' +
                            '<div class="metric-label"><i class="icon-thumbs-up"></i> Likes</div>' +
                            '<div class="metric-bar-container">' +
                                '<div class="metric-bar like-bar" style="width: ' + likePercentage + '%"></div>' +
                            '</div>' +
                            '<div class="metric-value">' + post.likes + ' <span class="metric-percent">(' + likePercentage + '%)</span></div>' +
                        '</div>' +
                        
                        '<div class="metric-row">' +
                            '<div class="metric-label"><i class="icon-thumbs-down"></i> Dislikes</div>' +
                            '<div class="metric-bar-container">' +
                                '<div class="metric-bar dislike-bar" style="width: ' + dislikePercentage + '%"></div>' +
                            '</div>' +
                            '<div class="metric-value">' + post.dislikes + ' <span class="metric-percent">(' + dislikePercentage + '%)</span></div>' +
                        '</div>' +
                    '</div>' +
                    
                    '<div class="engagement-actions mt-20">' +
                        '<a href="' + viewCommentsUrl.replace('0', post.id) + '" class="btn-view-comments">View All Comments</a>' +
                    '</div>' +
                '</div>',
            width: '700px',
            padding: '20px',
            customClass: {
                popup: 'engagement-swal-popup',
                title: 'engagement-swal-title',
                htmlContainer: 'engagement-swal-html'
            }
        });
    };
    
    // Initialize Bootstrap tooltips if available
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});

// Store the view comments URL globally
var viewCommentsUrl = '';
