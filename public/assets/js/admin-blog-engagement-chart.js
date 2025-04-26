/**
 * Admin Blog Engagement Charts
 * Enhances the post engagement popup with interactive charts
 */

// Define viewCommentsUrl globally so it works across scripts
var viewCommentsUrl = '/admin/blog/comments/0';

// Helper function to generate random ID for chart canvases
function generateChartId() {
    return 'chart-' + Math.random().toString(36).substring(2, 9);
}

// Enhanced Post Engagement function with charts
window.showPostEngagement = function(post) {
    if (typeof Swal === 'undefined') {
        alert("Post engagement: " + JSON.stringify(post));
        return;
    }
    
    // Calculate totals and percentages
    var totalEngagement = post.comments + post.likes + post.dislikes;
    var likePercentage = totalEngagement > 0 ? ((post.likes / totalEngagement) * 100).toFixed(1) : 0;
    var dislikePercentage = totalEngagement > 0 ? ((post.dislikes / totalEngagement) * 100).toFixed(1) : 0;
    var commentPercentage = totalEngagement > 0 ? ((post.comments / totalEngagement) * 100).toFixed(1) : 0;
    
    // Generate unique chart IDs
    var pieChartId = generateChartId();
    var barChartId = generateChartId();
    
    Swal.fire({
        title: '<span class="engagement-title-icon"><i class="icon-activity"></i></span> Post Engagement',
        html: 
            '<div class="engagement-details-popup">' +
                '<div class="engagement-post-title">' + post.title + '</div>' +
                
                '<div class="engagement-summary">' +
                    '<div class="total-engagement">' +
                        '<div class="total-number">' + totalEngagement + '</div>' +
                        '<div class="total-label">Total Interactions</div>' +
                    '</div>' +
                '</div>' +
                
                // Chart display section
                '<div class="engagement-charts">' +
                    '<div class="chart-row">' +
                        '<div class="pie-chart-container">' +
                            '<canvas id="' + pieChartId + '" width="200" height="200"></canvas>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                
                '<div class="engagement-metrics">' +
                    '<div class="metric-row">' +
                        '<div class="metric-label">' +
                            '<i class="icon-message-circle comment-icon"></i> Comments' +
                        '</div>' +
                        '<div class="metric-bar-container">' +
                            '<div class="metric-bar comment-bar" style="width: ' + commentPercentage + '%"></div>' +
                        '</div>' +
                        '<div class="metric-value">' +
                            post.comments + ' <span class="metric-percent">(' + commentPercentage + '%)</span>' +
                        '</div>' +
                    '</div>' +
                    
                    '<div class="metric-row">' +
                        '<div class="metric-label">' +
                            '<i class="icon-thumbs-up like-icon"></i> Likes' +
                        '</div>' +
                        '<div class="metric-bar-container">' +
                            '<div class="metric-bar like-bar" style="width: ' + likePercentage + '%"></div>' +
                        '</div>' +
                        '<div class="metric-value">' +
                            post.likes + ' <span class="metric-percent">(' + likePercentage + '%)</span>' +
                        '</div>' +
                    '</div>' +
                    
                    '<div class="metric-row">' +
                        '<div class="metric-label">' +
                            '<i class="icon-thumbs-down dislike-icon"></i> Dislikes' +
                        '</div>' +
                        '<div class="metric-bar-container">' +
                            '<div class="metric-bar dislike-bar" style="width: ' + dislikePercentage + '%"></div>' +
                        '</div>' +
                        '<div class="metric-value">' +
                            post.dislikes + ' <span class="metric-percent">(' + dislikePercentage + '%)</span>' +
                        '</div>' +
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
        },
        didOpen: () => {
            // Wait for the DOM to render the canvas elements
            setTimeout(() => {
                // Create the pie chart if Chart.js is available
                if (typeof Chart !== 'undefined') {
                    // Create pie chart
                    var pieCtx = document.getElementById(pieChartId).getContext('2d');
                    new Chart(pieCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Comments', 'Likes', 'Dislikes'],
                            datasets: [{
                                data: [post.comments, post.likes, post.dislikes],
                                backgroundColor: [
                                    '#3554d1', // Comments - Blue
                                    '#27ae60', // Likes - Green
                                    '#d93025'  // Dislikes - Red
                                ],
                                borderWidth: 0,
                                borderRadius: 5,
                                hoverOffset: 10
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '65%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        font: {
                                            family: "'Jost', sans-serif",
                                            size: 14
                                        },
                                        color: '#051036',
                                        padding: 20
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            var label = context.label || '';
                                            var value = context.raw || 0;
                                            var percentage = totalEngagement > 0 ? ((value / totalEngagement) * 100).toFixed(1) + '%' : '0%';
                                            return label + ': ' + value + ' (' + percentage + ')';
                                        }
                                    }
                                }
                            },
                            animation: {
                                animateScale: true,
                                animateRotate: true,
                                duration: 1000,
                                easing: 'easeOutQuart'
                            }
                        }
                    });
                } else {
                    console.error('Chart.js is not loaded. Please include it in your page.');
                    document.getElementById(pieChartId).innerHTML = 'Chart.js is required to display charts.';
                }
            }, 100);
        }
    });
};
