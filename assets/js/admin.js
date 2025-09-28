jQuery(document).ready(function($) {
    'use strict';

    // Admin dashboard functionality
    
    // Quick status update
    $('.quick-status-update').on('change', function() {
        const requestId = $(this).data('request-id');
        const newStatus = $(this).val();
        const row = $(this).closest('tr');
        
        if (!requestId || !newStatus) {
            return;
        }
        
        // Show loading
        const loadingSpinner = '<span class="loading-spinner"></span>';
        row.find('.status-column').html(loadingSpinner + 'Updating...');
        
        // AJAX request to update status
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_return_request_status',
                request_id: requestId,
                new_status: newStatus,
                nonce: return_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update status display
                    const statusHtml = '<span class="status-' + newStatus + '">' + 
                                     newStatus.charAt(0).toUpperCase() + newStatus.slice(1) + 
                                     '</span>';
                    row.find('.status-column').html(statusHtml);
                    
                    // Show success message
                    showAdminNotice('success', 'Status updated successfully!');
                } else {
                    showAdminNotice('error', response.data.message || 'Failed to update status.');
                    // Reload to reset the form
                    location.reload();
                }
            },
            error: function() {
                showAdminNotice('error', 'An error occurred while updating status.');
                location.reload();
            }
        });
    });
    
    // Bulk actions
    $('#doaction, #doaction2').on('click', function(e) {
        const action = $(this).siblings('select').val();
        const checkedItems = $('input[name="return_request[]"]:checked');
        
        if (action === '-1' || checkedItems.length === 0) {
            return;
        }
        
        e.preventDefault();
        
        if (!confirm('Are you sure you want to perform this action on ' + checkedItems.length + ' item(s)?')) {
            return;
        }
        
        const requestIds = [];
        checkedItems.each(function() {
            requestIds.push($(this).val());
        });
        
        // Show loading
        $(this).prop('disabled', true).after('<span class="loading-spinner"></span>');
        
        // AJAX request for bulk action
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bulk_update_return_requests',
                bulk_action: action,
                request_ids: requestIds,
                nonce: return_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAdminNotice('success', response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAdminNotice('error', response.data.message || 'Bulk action failed.');
                    location.reload();
                }
            },
            error: function() {
                showAdminNotice('error', 'An error occurred during bulk action.');
                location.reload();
            }
        });
    });
    
    // Search functionality
    $('#return-requests-search').on('input', debounce(function() {
        const searchTerm = $(this).val();
        filterTable(searchTerm);
    }, 300));
    
    // Filter by status
    $('#status-filter').on('change', function() {
        const status = $(this).val();
        const currentUrl = new URL(window.location.href);
        
        if (status) {
            currentUrl.searchParams.set('status', status);
        } else {
            currentUrl.searchParams.delete('status');
        }
        
        window.location.href = currentUrl.toString();
    });
    
    // Table row highlighting
    $('.wp-list-table tbody tr').on('mouseenter', function() {
        $(this).addClass('hover-highlight');
    }).on('mouseleave', function() {
        $(this).removeClass('hover-highlight');
    });
    
    // File preview in modal
    $('.file-preview-link').on('click', function(e) {
        e.preventDefault();
        
        const fileUrl = $(this).attr('href');
        const fileName = $(this).text();
        const fileType = $(this).data('file-type');
        
        showFilePreview(fileUrl, fileName, fileType);
    });
    
    // Settings form validation
    $('#return-button-settings-form').on('submit', function(e) {
        const returnPeriod = parseInt($('#return_period_days').val());
        const gracePeriod = parseInt($('#grace_period_days').val());
        
        if (returnPeriod < 1 || returnPeriod > 365) {
            e.preventDefault();
            showAdminNotice('error', 'Return period must be between 1 and 365 days.');
            return;
        }
        
        if (gracePeriod < 1 || gracePeriod > 365) {
            e.preventDefault();
            showAdminNotice('error', 'Grace period must be between 1 and 365 days.');
            return;
        }
    });
    
    // Auto-refresh stats
    setInterval(function() {
        updateDashboardStats();
    }, 60000); // Refresh every minute
    
    // Functions
    
    function showAdminNotice(type, message) {
        // Remove existing notices
        $('.admin-notice-custom').remove();
        
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const noticeHtml = '<div class="notice ' + noticeClass + ' is-dismissible admin-notice-custom">' +
                          '<p>' + message + '</p>' +
                          '<button type="button" class="notice-dismiss">' +
                          '<span class="screen-reader-text">Dismiss this notice.</span>' +
                          '</button>' +
                          '</div>';
        
        $('.wrap h1').after(noticeHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.admin-notice-custom').fadeOut();
        }, 5000);
    }
    
    function filterTable(searchTerm) {
        const tableRows = $('.wp-list-table tbody tr');
        
        if (!searchTerm) {
            tableRows.show();
            return;
        }
        
        tableRows.each(function() {
            const rowText = $(this).text().toLowerCase();
            const shouldShow = rowText.includes(searchTerm.toLowerCase());
            
            if (shouldShow) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
    
    function showFilePreview(fileUrl, fileName, fileType) {
        let previewContent = '';
        
        if (fileType && fileType.startsWith('image/')) {
            previewContent = '<img src="' + fileUrl + '" alt="' + fileName + '" style="max-width: 100%; height: auto;">';
        } else if (fileType && fileType.startsWith('video/')) {
            previewContent = '<video controls style="max-width: 100%; height: auto;">' +
                           '<source src="' + fileUrl + '" type="' + fileType + '">' +
                           'Your browser does not support the video tag.' +
                           '</video>';
        } else {
            previewContent = '<p>Preview not available. <a href="' + fileUrl + '" target="_blank">Download file</a></p>';
        }
        
        const modalHtml = '<div id="file-preview-modal" class="file-preview-modal">' +
                         '<div class="file-preview-content">' +
                         '<div class="file-preview-header">' +
                         '<h3>' + fileName + '</h3>' +
                         '<span class="file-preview-close">&times;</span>' +
                         '</div>' +
                         '<div class="file-preview-body">' + previewContent + '</div>' +
                         '</div>' +
                         '</div>';
        
        $('body').append(modalHtml);
        
        // Show modal
        $('#file-preview-modal').fadeIn();
        
        // Close modal events
        $('.file-preview-close, #file-preview-modal').on('click', function(e) {
            if (e.target === this) {
                $('#file-preview-modal').fadeOut(function() {
                    $(this).remove();
                });
            }
        });
    }
    
    function updateDashboardStats() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_return_requests_stats',
                nonce: return_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const stats = response.data;
                    $('.stat-total .stat-number').text(stats.total);
                    $('.stat-pending .stat-number').text(stats.pending);
                    $('.stat-processing .stat-number').text(stats.processing);
                    $('.stat-completed .stat-number').text(stats.completed);
                }
            }
        });
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Initialize tooltips
    $('[data-tooltip]').each(function() {
        $(this).attr('title', $(this).data('tooltip'));
    });
    
    // Status change confirmation
    $('select[name="new_status"]').on('change', function() {
        const currentStatus = $(this).data('current-status');
        const newStatus = $(this).val();
        
        if (currentStatus === 'completed' && newStatus !== 'completed') {
            if (!confirm('Are you sure you want to change status from completed? This action should be carefully considered.')) {
                $(this).val(currentStatus);
            }
        }
    });
    
    // Add custom CSS for admin enhancements
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .hover-highlight {
                background-color: #f0f8ff !important;
            }
            .file-preview-modal {
                position: fixed;
                z-index: 10000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .file-preview-content {
                background-color: white;
                padding: 0;
                border-radius: 8px;
                max-width: 90%;
                max-height: 90%;
                overflow: auto;
            }
            .file-preview-header {
                padding: 20px;
                border-bottom: 1px solid #ddd;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .file-preview-header h3 {
                margin: 0;
            }
            .file-preview-close {
                font-size: 24px;
                cursor: pointer;
                color: #666;
            }
            .file-preview-close:hover {
                color: #000;
            }
            .file-preview-body {
                padding: 20px;
                text-align: center;
            }
            .admin-notice-custom {
                margin: 20px 0;
            }
        `)
        .appendTo('head');
});