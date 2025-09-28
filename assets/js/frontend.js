jQuery(document).ready(function($) {
    'use strict';

    // Return Request Modal functionality
    const modal = $('#return-request-modal');
    const form = $('#return-request-form');
    const closeModal = $('.return-modal-close, .return-btn-cancel');

    // Open modal when return request button is clicked
    $(document).on('click', '.return-request-btn', function(e) {
        e.preventDefault();
        
        const orderId = $(this).data('order-id');
        const orderNumber = $(this).data('order-number');
        
        // Set order ID in the form
        $('#order_id').val(orderId);
        
        // Reset form
        form[0].reset();
        $('.return-message').remove();
        
        // Pre-fill user data if available
        prefillUserData();
        
        // Show modal
        modal.fadeIn(300);
        
        // Focus on first field
        setTimeout(function() {
            $('#customer_name').focus();
        }, 350);
    });

    // Close modal
    closeModal.on('click', function(e) {
        e.preventDefault();
        modal.fadeOut(300);
    });

    // Close modal when clicking outside
    modal.on('click', function(e) {
        if (e.target === this) {
            modal.fadeOut(300);
        }
    });

    // Close modal on escape key
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27 && modal.is(':visible')) {
            modal.fadeOut(300);
        }
    });

    // Auto-fill WhatsApp if same as phone
    $('#customer_phone').on('blur', function() {
        const phoneNumber = $(this).val();
        const whatsappField = $('#customer_whatsapp');
        
        if (phoneNumber && !whatsappField.val()) {
            whatsappField.attr('placeholder', 'Same as phone: ' + phoneNumber);
        }
    });

    // Form submission
    form.on('submit', function(e) {
        e.preventDefault();
        
        // Remove previous messages
        $('.return-message').remove();
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Prepare form data
        const formData = new FormData(this);
        
        // Add loading state
        form.addClass('return-form-loading');
        $('.return-btn-submit').prop('disabled', true).text('Submitting...');
        
        // AJAX request
        $.ajax({
            url: return_button_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                    
                    // Close modal after delay
                    setTimeout(function() {
                        modal.fadeOut(300);
                        // Reload page to update button state
                        location.reload();
                    }, 2000);
                } else {
                    showMessage('error', response.data.message || return_button_ajax.messages.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showMessage('error', return_button_ajax.messages.error);
            },
            complete: function() {
                // Remove loading state
                form.removeClass('return-form-loading');
                $('.return-btn-submit').prop('disabled', false).text('Submit Request');
            }
        });
    });

    // Form validation
    function validateForm() {
        let isValid = true;
        const requiredFields = ['customer_name', 'customer_email', 'customer_phone', 'return_reason'];
        
        // Remove previous error styling
        $('.form-field-error').removeClass('form-field-error');
        
        requiredFields.forEach(function(fieldName) {
            const field = $('#' + fieldName);
            const value = field.val().trim();
            
            if (!value) {
                field.addClass('form-field-error');
                isValid = false;
            }
        });
        
        // Email validation
        const email = $('#customer_email').val().trim();
        if (email && !isValidEmail(email)) {
            $('#customer_email').addClass('form-field-error');
            showMessage('error', 'Please enter a valid email address.');
            isValid = false;
        }
        
        // File validation
        const fileInput = $('#return_files')[0];
        if (fileInput.files.length > 0) {
            if (!validateFiles(fileInput.files)) {
                isValid = false;
            }
        }
        
        if (!isValid && !$('.return-message').length) {
            showMessage('error', 'Please fill all required fields correctly.');
        }
        
        return isValid;
    }

    // Email validation
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // File validation
    function validateFiles(files) {
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'video/mp4'];
        const maxSize = 10 * 1024 * 1024; // 10MB
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            // Check file type
            if (!allowedTypes.includes(file.type)) {
                showMessage('error', 'Invalid file type. Only JPG, JPEG, PNG images and MP4 videos are allowed.');
                return false;
            }
            
            // Check file size
            if (file.size > maxSize) {
                showMessage('error', 'File size too large. Maximum allowed size is 10MB per file.');
                return false;
            }
        }
        
        return true;
    }

    // Show message
    function showMessage(type, message) {
        $('.return-message').remove();
        
        const messageHtml = '<div class="return-message ' + type + '">' + message + '</div>';
        form.prepend(messageHtml);
        
        // Scroll to top of form
        form.scrollTop(0);
    }

    // Pre-fill user data
    function prefillUserData() {
        // Get user data from WooCommerce if available
        const userEmail = $('.woocommerce-MyAccount-content .woocommerce-Address-title').siblings('address').find('a[href^="mailto:"]').text();
        
        if (userEmail) {
            $('#customer_email').val(userEmail);
        }
        
        // You can add more prefill logic here based on available user data
    }

    // File input styling and preview
    $('#return_files').on('change', function() {
        const files = this.files;
        const fileList = $('.file-preview-list');
        
        // Remove existing preview
        fileList.remove();
        
        if (files.length > 0) {
            let previewHtml = '<div class="file-preview-list"><strong>Selected files:</strong><ul>';
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileSize = formatFileSize(file.size);
                previewHtml += '<li>' + file.name + ' (' + fileSize + ')</li>';
            }
            
            previewHtml += '</ul></div>';
            $(this).parent().append(previewHtml);
        }
    });

    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Add CSS for form field errors
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .form-field-error {
                border-color: #e74c3c !important;
                box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1) !important;
            }
            .file-preview-list {
                margin-top: 10px;
                padding: 10px;
                background-color: #f8f9fa;
                border-radius: 4px;
                font-size: 12px;
            }
            .file-preview-list ul {
                margin: 5px 0 0 0;
                padding-left: 20px;
            }
            .file-preview-list li {
                margin: 2px 0;
                color: #6c757d;
            }
        `)
        .appendTo('head');

    // Security enhancements
    // Prevent XSS in form inputs
    $('input[type="text"], input[type="email"], input[type="tel"], textarea').on('input', function() {
        const value = $(this).val();
        // Remove potentially dangerous characters
        const sanitized = value.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
        $(this).val(sanitized);
    });

    // Phone number formatting
    $('#customer_phone, #customer_whatsapp').on('input', function() {
        let value = $(this).val().replace(/\D/g, ''); // Remove non-digits
        
        // Limit to reasonable phone number length
        if (value.length > 15) {
            value = value.substring(0, 15);
        }
        
        $(this).val(value);
    });

    // Character limit for return reason
    $('#return_reason').on('input', function() {
        const maxLength = 1000;
        const currentLength = $(this).val().length;
        
        if (currentLength > maxLength) {
            $(this).val($(this).val().substring(0, maxLength));
        }
        
        // Show character count
        let charCounter = $(this).siblings('.char-counter');
        if (charCounter.length === 0) {
            charCounter = $('<small class="char-counter"></small>');
            $(this).after(charCounter);
        }
        
        const remaining = maxLength - $(this).val().length;
        charCounter.text(remaining + ' characters remaining');
        
        if (remaining < 50) {
            charCounter.css('color', '#e74c3c');
        } else {
            charCounter.css('color', '#6c757d');
        }
    });
});