/**
 * Admin JavaScript for Support Ticket System
 */

jQuery(document).ready(function($) {
    
    // Ticket status update
    $('.sts-update-status').on('change', function() {
        var ticketId = $(this).data('ticket-id');
        var newStatus = $(this).val();
        var nonce = $(this).data('nonce');
        
        $.ajax({
            url: sts_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sts_update_ticket_status',
                ticket_id: ticketId,
                status: newStatus,
                nonce: nonce
            },
            beforeSend: function() {
                $('.sts-status-' + ticketId).html('<span class="spinner is-active"></span>');
            },
            success: function(response) {
                if (response.success) {
                    // Update status badge
                    var badgeClass = 'sts-status-' + newStatus.replace('_', '-');
                    var badgeText = newStatus.replace('_', ' ').toUpperCase();
                    $('.sts-status-' + ticketId).html('<span class="sts-ticket-status ' + badgeClass + '">' + badgeText + '</span>');
                    
                    // Show success message
                    showAdminNotice('Ticket status updated successfully!', 'success');
                } else {
                    showAdminNotice(response.data || 'Error updating status', 'error');
                }
            },
            error: function() {
                showAdminNotice('Network error. Please try again.', 'error');
            }
        });
    });
    
    // Assign ticket to agent
    $('.sts-assign-agent').on('change', function() {
        var ticketId = $(this).data('ticket-id');
        var agentId = $(this).val();
        var nonce = $(this).data('nonce');
        
        $.ajax({
            url: sts_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sts_assign_ticket',
                ticket_id: ticketId,
                agent_id: agentId,
                nonce: nonce
            },
            beforeSend: function() {
                $('.sts-agent-' + ticketId).html('<span class="spinner is-active"></span>');
            },
            success: function(response) {
                if (response.success) {
                    // Update agent name
                    $('.sts-agent-' + ticketId).text(response.data.agent_name);
                    showAdminNotice('Ticket assigned successfully!', 'success');
                } else {
                    showAdminNotice(response.data || 'Error assigning ticket', 'error');
                }
            },
            error: function() {
                showAdminNotice('Network error. Please try again.', 'error');
            }
        });
    });
    
    // Priority update
    $('.sts-update-priority').on('change', function() {
        var ticketId = $(this).data('ticket-id');
        var newPriority = $(this).val();
        var nonce = $(this).data('nonce');
        
        $.ajax({
            url: sts_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sts_update_ticket_priority',
                ticket_id: ticketId,
                priority: newPriority,
                nonce: nonce
            },
            beforeSend: function() {
                $('.sts-priority-' + ticketId).html('<span class="spinner is-active"></span>');
            },
            success: function(response) {
                if (response.success) {
                    // Update priority badge
                    var badgeClass = 'sts-priority-' + newPriority;
                    $('.sts-priority-' + ticketId).html('<span class="sts-priority ' + badgeClass + '">' + newPriority.charAt(0).toUpperCase() + newPriority.slice(1) + '</span>');
                    showAdminNotice('Priority updated successfully!', 'success');
                } else {
                    showAdminNotice(response.data || 'Error updating priority', 'error');
                }
            },
            error: function() {
                showAdminNotice('Network error. Please try again.', 'error');
            }
        });
    });
    
    // Delete ticket
    $('.sts-delete-ticket').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this ticket? This action cannot be undone.')) {
            return;
        }
        
        var ticketId = $(this).data('ticket-id');
        var nonce = $(this).data('nonce');
        var row = $(this).closest('tr');
        
        $.ajax({
            url: sts_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sts_delete_ticket',
                ticket_id: ticketId,
                nonce: nonce
            },
            beforeSend: function() {
                row.css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(300, function() {
                        $(this).remove();
                    });
                    showAdminNotice('Ticket deleted successfully!', 'success');
                } else {
                    row.css('opacity', '1');
                    showAdminNotice(response.data || 'Error deleting ticket', 'error');
                }
            },
            error: function() {
                row.css('opacity', '1');
                showAdminNotice('Network error. Please try again.', 'error');
            }
        });
    });
    
    // Bulk actions
    $('#sts-bulk-action').on('change', function() {
        var action = $(this).val();
        if (action === 'delete') {
            if (!confirm('Are you sure you want to delete the selected tickets? This action cannot be undone.')) {
                $(this).val('');
                return;
            }
        }
    });
    
    // Settings tabs
    $('.sts-settings-nav a').on('click', function(e) {
        e.preventDefault();
        
        var tab = $(this).data('tab');
        
        // Update active tab
        $('.sts-settings-nav li').removeClass('active');
        $(this).parent().addClass('active');
        
        // Show selected tab content
        $('.sts-settings-tab').removeClass('active');
        $('#' + tab).addClass('active');
    });
    
    // Ticket reply
    $('#sts-send-reply').on('click', function() {
        var ticketId = $(this).data('ticket-id');
        var message = $('#sts-reply-message').val().trim();
        var nonce = $(this).data('nonce');
        
        if (!message) {
            alert('Please enter a message');
            return;
        }
        
        $.ajax({
            url: sts_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sts_admin_reply_ticket',
                ticket_id: ticketId,
                message: message,
                nonce: nonce
            },
            beforeSend: function() {
                $('#sts-send-reply').prop('disabled', true).text('Sending...');
            },
            success: function(response) {
                $('#sts-send-reply').prop('disabled', false).text('Send Reply');
                
                if (response.success) {
                    // Clear textarea
                    $('#sts-reply-message').val('');
                    
                    // Add new message to conversation
                    var messageHtml = response.data.message_html;
                    $('.sts-ticket-conversation').append(messageHtml);
                    
                    // Scroll to new message
                    $('html, body').animate({
                        scrollTop: $(document).height()
                    }, 500);
                    
                    showAdminNotice('Reply sent successfully!', 'success');
                } else {
                    showAdminNotice(response.data || 'Error sending reply', 'error');
                }
            },
            error: function() {
                $('#sts-send-reply').prop('disabled', false).text('Send Reply');
                showAdminNotice('Network error. Please try again.', 'error');
            }
        });
    });
    
    // Helper function to show admin notices
    function showAdminNotice(message, type) {
        // Remove existing notices
        $('.sts-admin-notice').remove();
        
        // Create new notice
        var noticeClass = type === 'success' ? 'notice notice-success' : 'notice notice-error';
        var notice = $('<div class="' + noticeClass + ' is-dismissible sts-admin-notice"><p>' + message + '</p></div>');
        
        // Insert after h1
        $('h1:first').after(notice);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Initialize dashboard stats chart
    if ($('#sts-stats-chart').length) {
        initializeStatsChart();
    }
    
    function initializeStatsChart() {
        var ctx = document.getElementById('sts-stats-chart').getContext('2d');
        
        $.ajax({
            url: sts_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sts_get_dashboard_stats'
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Open', 'In Progress', 'Resolved', 'Closed'],
                            datasets: [{
                                label: 'Tickets',
                                data: [
                                    data.open,
                                    data.in_progress,
                                    data.resolved,
                                    data.closed
                                ],
                                backgroundColor: [
                                    '#dc3545',
                                    '#ffc107',
                                    '#28a745',
                                    '#17a2b8'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                }
            }
        });
    }
});
