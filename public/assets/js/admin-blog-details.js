document.addEventListener('DOMContentLoaded', function() {
  // Initialize tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
});

/**
 * Confirm post deletion with SweetAlert
 */
function confirmDeletePost(id, title, deleteUrl) {
  Swal.fire({
    title: 'Delete Blog Post?',
    html: `Are you sure you want to delete the blog post <strong>"${title}"</strong>?<br>This action cannot be undone.`,
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

/**
 * Confirm comment deletion with SweetAlert
 */
function confirmDeleteComment(id, deleteUrl) {
  Swal.fire({
    title: 'Delete Comment?',
    text: 'Are you sure you want to delete this comment? This action cannot be undone.',
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

/**
 * Show the full image in a modal
 */
function viewImage(imageSrc, title) {
  Swal.fire({
    title: title,
    imageUrl: imageSrc,
    imageAlt: title,
    width: 'auto',
    confirmButtonColor: '#3554d1'
  });
}
