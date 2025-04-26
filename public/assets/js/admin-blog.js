// Handle form functionality without bootstrap dependency
document.addEventListener('DOMContentLoaded', function() {
  // Auto-submit form when select changes (this doesn't depend on bootstrap)
  var activitySelect = document.querySelector('.js-filter-activity');
  if (activitySelect) {
    activitySelect.addEventListener('change', function() {
      var filterForm = document.querySelector('.js-filter-form');
      if (filterForm) {
        filterForm.submit();
      }
    });
  }
  
  // View switching functionality (list/grid toggle)
  const listViewBtn = document.querySelector('.list-view');
  const gridViewBtn = document.querySelector('.grid-view');
  const blogPostsContainer = document.querySelector('#blog-posts');
  
  if (listViewBtn && gridViewBtn && blogPostsContainer) {
    console.log('View buttons and container found!');
    
    // Check for saved preference
    const savedView = localStorage.getItem('blog-view-mode') || 'list';
    if (savedView === 'grid') {
      applyGridView();
    }
    
    // List view click handler
    listViewBtn.addEventListener('click', function(e) {
      e.preventDefault();
      applyListView();
    });
    
    // Grid view click handler
    gridViewBtn.addEventListener('click', function(e) {
      e.preventDefault();
      applyGridView();
    });
    
    function applyGridView() {
      console.log('Applying grid view');
      document.body.classList.add('grid-view-active');
      blogPostsContainer.classList.add('grid-view-active');
      gridViewBtn.classList.add('active');
      listViewBtn.classList.remove('active');
      localStorage.setItem('blog-view-mode', 'grid');
    }
    
    function applyListView() {
      console.log('Applying list view');
      document.body.classList.remove('grid-view-active');
      blogPostsContainer.classList.remove('grid-view-active');
      listViewBtn.classList.add('active');
      gridViewBtn.classList.remove('active');
      localStorage.setItem('blog-view-mode', 'list');
    }
  } else {
    console.log('View toggle elements not found:', { listViewBtn, gridViewBtn, blogPostsContainer });
  }
  
  // Initialize tooltips only if they're needed and Bootstrap is available
  // We'll check every 100ms for 2 seconds to see if Bootstrap loads
  var tooltipAttempts = 0;
  var tooltipCheckInterval = setInterval(function() {
    if (typeof window.bootstrap !== 'undefined') {
      clearInterval(tooltipCheckInterval);
      try {
        var tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        if (tooltipTriggerList.length > 0) {
          tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new window.bootstrap.Tooltip(tooltipTriggerEl);
          });
        }
      } catch (e) {
        console.log('Tooltip initialization skipped');
      }
    } else if (tooltipAttempts >= 20) { // Try for 2 seconds max
      clearInterval(tooltipCheckInterval);
    }
    tooltipAttempts++;
  }, 100);
});

// Function to validate date range
function validateDateRange() {
  const dateFrom = document.getElementById('date_from').value;
  const dateTo = document.getElementById('date_to').value;
  
  if (dateFrom && dateTo && new Date(dateFrom) > new Date(dateTo)) {
    Swal.fire({
      title: 'Invalid Date Range',
      text: 'The "From" date cannot be later than the "To" date.',
      icon: 'error',
      confirmButtonColor: '#3554d1'
    });
    return false;
  }
  return true;
}

// Confirm blog post deletion
function confirmDeleteBlog(id, title, deleteUrl) {
  Swal.fire({
    title: 'Delete Blog Post?',
    html: `Are you sure you want to delete the blog post <strong>${title}</strong>?<br>This action cannot be undone.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d93025',
    cancelButtonColor: '#697488',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = deleteUrl;
    }
  });
}

// Function to display image preview before upload
function previewImage(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    
    reader.onload = function(e) {
      document.getElementById('imagePreview').innerHTML = `
        <div class="image-preview-container">
          <img src="${e.target.result}" class="image-preview" alt="Preview">
        </div>`;
    };
    
    reader.readAsDataURL(input.files[0]);
  }
}
