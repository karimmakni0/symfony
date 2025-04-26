// SweetAlert confirmation functions for upgrade requests
function confirmApprove(requestId, firstName, lastName, url) {
  Swal.fire({
    title: 'Approve Request?',
    html: `Are you sure you want to approve <strong>${firstName} ${lastName}</strong>'s request to become a Publicator?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, approve!',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = url;
    }
  });
}

function confirmReject(requestId, firstName, lastName, url) {
  Swal.fire({
    title: 'Reject Request?',
    html: `Are you sure you want to reject <strong>${firstName} ${lastName}</strong>'s request to become a Publicator?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, reject!',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = url;
    }
  });
}

function viewMessage(firstName, lastName, message) {
  Swal.fire({
    title: `Message from ${firstName} ${lastName}`,
    html: `<div class="p-3 text-left">${message || 'No message provided'}</div>`,
    width: 600,
    showConfirmButton: false,
    showCloseButton: true
  });
}

document.addEventListener('DOMContentLoaded', function() {
  // Auto-submit form when selects change
  document.querySelectorAll('.js-filter-status').forEach(select => {
    select.addEventListener('change', function() {
      document.querySelector('.js-filter-form').submit();
    });
  });
  
  // Add tooltips to action buttons
  const actionButtons = document.querySelectorAll('.action-btn');
  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    actionButtons.forEach(button => {
      new bootstrap.Tooltip(button);
    });
  }
});
