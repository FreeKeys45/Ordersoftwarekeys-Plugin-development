/**
 * Frontend JavaScript for Support Ticket System
 */

jQuery(document).ready(function($) {
    
    // Ticket form submission
    $('#sts-ticket-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = form.serialize();
        var submitBtn = form.find('.sts-submit-btn');
        
        // Basic validation
        var requiredFields = form.find('[required]');
        var isValid = true;
        
        requiredFields.each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('sts-error');
                isValid = false;
            } else {
                $(this).removeClass('sts-error');
            }
        });
        
        if (!isValid) {
            showFrontendNotice('Please fill in all required fields.', 'error');
            return;
        }
        
        $.ajax({
            url: sts_frontend_ajax.ajax_url,
            type: 'POST',
            data: formData + '&action=sts_submit_ticket',
            beforeSend: function() {
                submitBtn.prop('disabled', true).text('Submitting...');
            },
            success: function(response) {
                submitBtn.prop('disabled', false).text('Submit Ticket');
                
                if (response.success) {
                    // Clear form
                    form[0].reset();
                    
                    // Show success message
                    var successHtml = '<div class="sts-alert sts-alert-success">' +
                        '<p>Ticket submitted successfully! Your ticket ID is: <strong>' + response.data.ticket_id + '</strong></p>' +
                        '<p>You can view your ticket <a href="' + response.data.ticket_url + '">here</a>.</p>' +
                        '</div>';
                    
                    form.before(successHtml);
                    
                    // Scroll to success message
                    $('html, body').animate({
                        scrollTop: form.prev().offset().top - 100
                    }, 500);
                } else {
                    showFrontendNotice(response.data || 'Error submitting ticket', 'error');
                }
            },
            error: function() {
                submitBtn.prop('disabled', false).text('Submit Ticket');
                showFrontendNotice('Network error. Please try again.', 'error');
            }
        });
    });
    
    // Ticket reply form
    $('#sts-ticket-reply-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var message = form.find('textarea').val().trim();
        var ticketId = form.find('input[name="ticket_id"]').val();
        var nonce = form.find('input[name="nonce"]').val();
        var submitBtn = form.find('.sts-submit-btn');
        
        if (!message) {
            showFrontendNotice('Please enter a message', 'error');
            return;
        }
        
        $.ajax({
            url: sts_frontend_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sts_reply_ticket',
                ticket_id: ticketId,
                message: message,
                nonce: nonce
            },
            beforeSend: function() {
                submitBtn.prop('disabled', true).text('Sending...');
            },
            success: function(response) {
                submitBtn.prop('disabled', false).text('Send Reply');
                
                if (response.success) {
                    // Clear textarea
                    form.find('textarea').val('');
                    
                    // Add new message to conversation
                    var messageHtml = response.data.message_html;
                    $('.sts-conversation').append(messageHtml);
                    
                    // Scroll to new message
                    $('html, body').animate({
                        scrollTop: $(document).height()
                    }, 500);
                    
                    showFrontendNotice('Reply sent successfully!', 'success');
                } else {
                    showFrontendNotice(response.data || 'Error sending reply', 'error');
                }
            },
            error: function() {
                submitBtn.prop('disabled', false).text('Send Reply');
                showFrontendNotice('Network error. Please try again.', 'error');
            }
        });
    });
    
    // Close ticket
    $('.sts-close-ticket-btn').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to close this ticket? This action cannot be undone.')) {
            return;
        }
        
        var button = $(this);
        var ticketId = button.data('ticket-id');
        var nonce = button.data('nonce');
        
        $.ajax({
            url: sts_frontend_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sts_close_ticket',
                ticket_id: ticketId,
                nonce: nonce
            },
            beforeSend: function() {
                button.prop('disabled', true).text('Closing...');
            },
            success: function(response) {
                if (response.success) {
                    // Update status badge
                    $('.sts-status-badge').removeClass('sts-badge-open sts-badge-in-progress sts-badge-resolved')
                        .addClass('sts-badge-closed')
                        .text('Closed');
                    
                    // Remove close button
                    button.remove();
                    
                    // Show success message
                    var successHtml = '<div class="sts-alert sts-alert-success">' +
                        '<p>Ticket closed successfully.</p>' +
                        '</div>';
                    
                    $('.sts-ticket-header').after(successHtml);
                    
                    showFrontendNotice('Ticket closed successfully!', 'success');
                } else {
                    button.prop('disabled', false).text('Close Ticket');
                    showFrontendNotice(response.data || 'Error closing ticket', 'error');
                }
            },
            error: function() {
                button.prop('disabled', false).text('Close Ticket');
                showFrontendNotice('Network error. Please try again.', 'error');
            }
        });
    });
    
    // Filter tickets
    $('.sts-filter-tickets').on('change', function() {
        var status = $(this).val();
        var nonce = $(this).data('nonce');
        
        $.ajax({
            url: sts_frontend_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sts_filter_tickets',
                status: status,
                nonce: nonce
            },
            beforeSend: function() {
                $('.sts-tickets-table tbody').html('<tr><td colspan="6" class="text-center"><span class="spinner is-active"></span> Loading tickets...</td></tr>');
            },
            success: function(response) {
                if (response.success) {
                    $('.sts-tickets-table tbody').html(response.data.html);
                } else {
                    $('.sts-tickets-table tbody').html('<tr><td colspan="6" class="text-center">Error loading tickets</td></tr>');
                }
            },
            error: function() {
                $('.sts-tickets-table tbody').html('<tr><td colspan="6" class="text-center">Network error. Please try again.</td></tr>');
            }
        });
    });
    
    // Search tickets
    var searchTimeout;
    $('.sts-search-tickets').on('keyup', function() {
        clearTimeout(searchTimeout);
        
        var searchTerm = $(this).val();
        var nonce = $(this).data('nonce');
        
        searchTimeout = setTimeout(function() {
            if (searchTerm.length >= 2 || searchTerm.length === 0) {
                $.ajax({
                    url: sts_frontend_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sts_search_tickets',
                        search: searchTerm,
                        nonce: nonce
                    },
                    beforeSend: function() {
                        $('.sts-tickets-table tbody').html('<tr><td colspan="6" class="text-center"><span class="spinner is-active"></span> Searching tickets...</td></tr>');
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.sts-tickets-table tbody').html(response.data.html);
                        } else {
                            $('.sts-tickets-table tbody').html('<tr><td colspan="6" class="text-center">Error searching tickets</td></tr>');
                        }
                    },
                    error: function() {
                        $('.sts-tickets-table tbody').html('<tr><td colspan="6" class="text-center">Network error. Please try again.</td></tr>');
                    }
                });
            }
        }, 500);
    });
    
    // Mark ticket as resolved
    $('.sts-resolve-ticket-btn').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var ticketId = button.data('ticket-id');
        var nonce = button.data('nonce');
        
        $.ajax({
            url: sts_frontend_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sts_resolve_ticket',
                ticket_id: ticketId,
                nonce: nonce
            },
            beforeSend: function() {
                button.prop('disabled', true).text('Marking as resolved...');
            },
            success: function(response) {
                if (response.success) {
                    // Update status badge
                    $('.sts-status-badge').removeClass('sts-badge-open sts-badge-in-progress')
                        .addClass('sts-badge-resolved')
                        .text('Resolved');
                    
                    // Remove resolve button
                    button.remove();
                    
                    showFrontendNotice('Ticket marked as resolved!', 'success');
                } else {
                    button.prop('disabled', false).text('Mark as Resolved');
                    showFrontendNotice(response.data || 'Error updating ticket', 'error');
                }
            },
            error: function() {
                button.prop('disabled', false).text('Mark as Resolved');
                showFrontendNotice('Network error. Please try again.', 'error');
            }
        });
    });
    
    // Helper function to show frontend notices
    function showFrontendNotice(message, type) {
        // Remove existing notices
        $('.sts-notice').remove();
        
        // Create new notice
        var noticeClass = type === 'success' ? 'sts-alert-success' : 'sts-alert-error';
        var notice = $('<div class="sts-alert ' + noticeClass + ' sts-notice"><p>' + message + '</p></div>');
        
        // Insert at top of content
        $('.sts-content:first').prepend(notice);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Form field validation
    $('.sts-form-group input, .sts-form-group textarea').on('blur', function() {
        if ($(this).prop('required') && !$(this).val().trim()) {
            $(this).addClass('sts-error');
            $(this).after('<span class="sts-error-message">This field is required</span>');
        } else {
            $(this).removeClass('sts-error');
            $(this).next('.sts-error-message').remove();
        }
    });
    
    // Character counter for textareas
    $('textarea[maxlength]').on('keyup', function() {
        var maxLength = $(this).attr('maxlength');
        var currentLength = $(this).val().length;
        var counter = $(this).next('.sts-char-counter');
        
        if (!counter.length) {
            counter = $('<span class="sts-char-counter"></span>');
            $(this).after(counter);
        }
        
        counter.text(currentLength + '/' + maxLength);
        
        if (currentLength > maxLength) {
            counter.addClass('sts-char-counter-error');
        } else {
            counter.removeClass('sts-char-counter-error');
        }
    });
});
