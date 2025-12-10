<?php
/**
 * Agent Dashboard Template
 * 
 * @package SupportTicketSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Get agent stats and tickets
$stats = STS_Agent_Dashboard_Shortcode::get_ticket_stats();
$tickets = STS_Agent_Dashboard_Shortcode::get_agent_tickets( 'all', 20 );

// Get filter status
$current_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'all';
?>

<div class="sts-agent-dashboard">
    <div class="sts-dashboard-header">
        <h1 class="sts-dashboard-title">
            <?php esc_html_e( 'Agent Dashboard', 'support-ticket-system' ); ?>
        </h1>
        
        <div class="sts-agent-actions">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=sts-tickets' ) ); ?>" class="sts-admin-link" target="_blank">
                <?php esc_html_e( 'Admin Panel', 'support-ticket-system' ); ?>
            </a>
        </div>
    </div>
    
    <!-- Agent Stats -->
    <div class="sts-agent-stats">
        <div class="sts-agent-stat">
            <span class="sts-agent-stat-number"><?php echo esc_html( $stats['total'] ); ?></span>
            <span class="sts-agent-stat-label"><?php esc_html_e( 'Total Tickets', 'support-ticket-system' ); ?></span>
        </div>
        
        <div class="sts-agent-stat">
            <span class="sts-agent-stat-number"><?php echo esc_html( $stats['open'] ); ?></span>
            <span class="sts-agent-stat-label"><?php esc_html_e( 'Open', 'support-ticket-system' ); ?></span>
        </div>
        
        <div class="sts-agent-stat">
            <span class="sts-agent-stat-number"><?php echo esc_html( $stats['in_progress'] ); ?></span>
            <span class="sts-agent-stat-label"><?php esc_html_e( 'In Progress', 'support-ticket-system' ); ?></span>
        </div>
        
        <div class="sts-agent-stat">
            <span class="sts-agent-stat-number"><?php echo esc_html( $stats['resolved'] ); ?></span>
            <span class="sts-agent-stat-label"><?php esc_html_e( 'Resolved', 'support-ticket-system' ); ?></span>
        </div>
        
        <div class="sts-agent-stat">
            <span class="sts-agent-stat-number"><?php echo esc_html( $stats['closed'] ); ?></span>
            <span class="sts-agent-stat-label"><?php esc_html_e( 'Closed', 'support-ticket-system' ); ?></span>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="sts-quick-actions">
        <h3><?php esc_html_e( 'Quick Actions', 'support-ticket-system' ); ?></h3>
        <div class="sts-actions-grid">
            <a href="#unassigned-tickets" class="sts-action-card">
                <span class="sts-action-icon">📋</span>
                <span class="sts-action-label"><?php esc_html_e( 'View Unassigned', 'support-ticket-system' ); ?></span>
            </a>
            
            <a href="#high-priority" class="sts-action-card">
                <span class="sts-action-icon">⚠️</span>
                <span class="sts-action-label"><?php esc_html_e( 'High Priority', 'support-ticket-system' ); ?></span>
            </a>
            
            <a href="#overdue-tickets" class="sts-action-card">
                <span class="sts-action-icon">⏰</span>
                <span class="sts-action-label"><?php esc_html_e( 'Overdue', 'support-ticket-system' ); ?></span>
            </a>
            
            <a href="#performance" class="sts-action-card">
                <span class="sts-action-icon">📊</span>
                <span class="sts-action-label"><?php esc_html_e( 'Performance', 'support-ticket-system' ); ?></span>
            </a>
        </div>
    </div>
    
    <!-- Tickets Filter -->
    <div class="sts-agent-filters">
        <div class="sts-filter-tabs">
            <a href="#" class="sts-filter-tab <?php echo $current_filter === 'all' ? 'active' : ''; ?>" 
               data-status="all">
                <?php esc_html_e( 'All Tickets', 'support-ticket-system' ); ?>
                <span class="sts-tab-count">(<?php echo esc_html( $stats['total'] ); ?>)</span>
            </a>
            
            <a href="#" class="sts-filter-tab <?php echo $current_filter === 'open' ? 'active' : ''; ?>" 
               data-status="open">
                <?php esc_html_e( 'Open', 'support-ticket-system' ); ?>
                <span class="sts-tab-count">(<?php echo esc_html( $stats['open'] ); ?>)</span>
            </a>
            
            <a href="#" class="sts-filter-tab <?php echo $current_filter === 'in_progress' ? 'active' : ''; ?>" 
               data-status="in_progress">
                <?php esc_html_e( 'In Progress', 'support-ticket-system' ); ?>
                <span class="sts-tab-count">(<?php echo esc_html( $stats['in_progress'] ); ?>)</span>
            </a>
            
            <a href="#" class="sts-filter-tab <?php echo $current_filter === 'resolved' ? 'active' : ''; ?>" 
               data-status="resolved">
                <?php esc_html_e( 'Resolved', 'support-ticket-system' ); ?>
                <span class="sts-tab-count">(<?php echo esc_html( $stats['resolved'] ); ?>)</span>
            </a>
            
            <a href="#" class="sts-filter-tab <?php echo $current_filter === 'closed' ? 'active' : ''; ?>" 
               data-status="closed">
                <?php esc_html_e( 'Closed', 'support-ticket-system' ); ?>
                <span class="sts-tab-count">(<?php echo esc_html( $stats['closed'] ); ?>)</span>
            </a>
        </div>
        
        <div class="sts-search-box">
            <input type="text" id="sts-agent-search" class="sts-search-input" 
                   placeholder="<?php esc_attr_e( 'Search tickets...', 'support-ticket-system' ); ?>">
            <button type="button" class="sts-search-btn">🔍</button>
        </div>
    </div>
    
    <!-- Tickets Table -->
    <div class="sts-agent-tickets-table">
        <?php if ( ! empty( $tickets ) ) : ?>
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'support-ticket-system' ); ?></th>
                        <th><?php esc_html_e( 'Subject', 'support-ticket-system' ); ?></th>
                        <th><?php esc_html_e( 'Requester', 'support-ticket-system' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'support-ticket-system' ); ?></th>
                        <th><?php esc_html_e( 'Priority', 'support-ticket-system' ); ?></th>
                        <th><?php esc_html_e( 'Created', 'support-ticket-system' ); ?></th>
                        <th><?php esc_html_e( 'Last Reply', 'support-ticket-system' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'support-ticket-system' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $tickets as $ticket ) : 
                        $ticket_url = add_query_arg( array( 'ticket_id' => $ticket->id ), get_permalink() );
                        $created_date = date_i18n( 'M j', strtotime( $ticket->created_at ) );
                        $updated_date = date_i18n( 'M j', strtotime( $ticket->updated_at ) );
                        $requester = get_user_by( 'id', $ticket->user_id );
                        $requester_name = $requester ? $requester->display_name : $ticket->user_name;
                    ?>
                    <tr>
                        <td class="sts-ticket-id">#<?php echo esc_html( $ticket->id ); ?></td>
                        <td class="sts-ticket-subject">
                            <a href="<?php echo esc_url( $ticket_url ); ?>">
                                <?php echo esc_html( $ticket->subject ); ?>
                            </a>
                        </td>
                        <td class="sts-ticket-requester">
                            <?php echo esc_html( $requester_name ); ?>
                        </td>
                        <td>
                            <select class="sts-update-status-select" data-ticket-id="<?php echo esc_attr( $ticket->id ); ?>"
                                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'sts_update_ticket_status' ) ); ?>">
                                <option value="open" <?php selected( $ticket->status, 'open' ); ?>>
                                    <?php esc_html_e( 'Open', 'support-ticket-system' ); ?>
                                </option>
                                <option value="in_progress" <?php selected( $ticket->status, 'in_progress' ); ?>>
                                    <?php esc_html_e( 'In Progress', 'support-ticket-system' ); ?>
                                </option>
                                <option value="resolved" <?php selected( $ticket->status, 'resolved' ); ?>>
                                    <?php esc_html_e( 'Resolved', 'support-ticket-system' ); ?>
                                </option>
                                <option value="closed" <?php selected( $ticket->status, 'closed' ); ?>>
                                    <?php esc_html_e( 'Closed', 'support-ticket-system' ); ?>
                                </option>
                            </select>
                        </td>
                        <td>
                            <select class="sts-update-priority-select" data-ticket-id="<?php echo esc_attr( $ticket->id ); ?>"
                                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'sts_update_ticket_priority' ) ); ?>">
                                <option value="low" <?php selected( $ticket->priority, 'low' ); ?>>
                                    <?php esc_html_e( 'Low', 'support-ticket-system' ); ?>
                                </option>
                                <option value="medium" <?php selected( $ticket->priority, 'medium' ); ?>>
                                    <?php esc_html_e( 'Medium', 'support-ticket-system' ); ?>
                                </option>
                                <option value="high" <?php selected( $ticket->priority, 'high' ); ?>>
                                    <?php esc_html_e( 'High', 'support-ticket-system' ); ?>
                                </option>
                                <option value="critical" <?php selected( $ticket->priority, 'critical' ); ?>>
                                    <?php esc_html_e( 'Critical', 'support-ticket-system' ); ?>
                                </option>
                            </select>
                        </td>
                        <td><?php echo esc_html( $created_date ); ?></td>
                        <td><?php echo esc_html( $updated_date ); ?></td>
                        <td class="sts-ticket-actions">
                            <a href="<?php echo esc_url( $ticket_url ); ?>" class="sts-view-btn" title="<?php esc_attr_e( 'View Ticket', 'support-ticket-system' ); ?>">
                                👁️
                            </a>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=sts-edit-ticket&ticket_id=' . $ticket->id ) ); ?>" 
                               class="sts-edit-btn" title="<?php esc_attr_e( 'Edit Ticket', 'support-ticket-system' ); ?>" target="_blank">
                                ✏️
                            </a>
                            <a href="#" class="sts-assign-btn sts-assign-to-me" 
                               data-ticket-id="<?php echo esc_attr( $ticket->id ); ?>"
                               data-nonce="<?php echo esc_attr( wp_create_nonce( 'sts_assign_ticket_to_me' ) ); ?>"
                               title="<?php esc_attr_e( 'Assign to Me', 'support-ticket-system' ); ?>">
                                👤
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="sts-no-tickets">
                <p><?php esc_html_e( 'No tickets assigned to you.', 'support-ticket-system' ); ?></p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=sts-tickets' ) ); ?>" class="sts-browse-tickets" target="_blank">
                    <?php esc_html_e( 'Browse all tickets', 'support-ticket-system' ); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Performance Metrics -->
    <div class="sts-performance-metrics">
        <h3><?php esc_html_e( 'Performance Metrics', 'support-ticket-system' ); ?></h3>
        
        <div class="sts-metrics-grid">
            <div class="sts-metric-card">
                <div class="sts-metric-value">24h</div>
                <div class="sts-metric-label"><?php esc_html_e( 'Avg. Response Time', 'support-ticket-system' ); ?></div>
            </div>
            
            <div class="sts-metric-card">
                <div class="sts-metric-value">95%</div>
                <div class="sts-metric-label"><?php esc_html_e( 'Satisfaction Rate', 'support-ticket-system' ); ?></div>
            </div>
            
            <div class="sts-metric-card">
                <div class="sts-metric-value">12</div>
                <div class="sts-metric-label"><?php esc_html_e( 'Tickets Resolved (7 days)', 'support-ticket-system' ); ?></div>
            </div>
            
            <div class="sts-metric-card">
                <div class="sts-metric-value">4.8</div>
                <div class="sts-metric-label"><?php esc_html_e( 'Avg. Rating', 'support-ticket-system' ); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="sts-recent-activity">
        <h3><?php esc_html_e( 'Recent Activity', 'support-ticket-system' ); ?></h3>
        
        <div class="sts-activity-list">
            <div class="sts-activity-item">
                <div class="sts-activity-icon">📝</div>
                <div class="sts-activity-content">
                    <div class="sts-activity-text">
                        <?php esc_html_e( 'You replied to ticket #1234', 'support-ticket-system' ); ?>
                    </div>
                    <div class="sts-activity-time">2 hours ago</div>
                </div>
            </div>
            
            <div class="sts-activity-item">
                <div class="sts-activity-icon">✅</div>
                <div class="sts-activity-content">
                    <div class="sts-activity-text">
                        <?php esc_html_e( 'You resolved ticket #1233', 'support-ticket-system' ); ?>
                    </div>
                    <div class="sts-activity-time">Yesterday</div>
                </div>
            </div>
            
            <div class="sts-activity-item">
                <div class="sts-activity-icon">👤</div>
                <div class="sts-activity-content">
                    <div class="sts-activity-text">
                        <?php esc_html_e( 'You assigned ticket #1232 to yourself', 'support-ticket-system' ); ?>
                    </div>
                    <div class="sts-activity-time">2 days ago</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Filter tickets by status
    $('.sts-filter-tab').on('click', function(e) {
        e.preventDefault();
        
        var status = $(this).data('status');
        
        // Update active tab
        $('.sts-filter-tab').removeClass('active');
        $(this).addClass('active');
        
        // Filter tickets
        $.ajax({
            url: sts_frontend_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sts_filter_agent_tickets',
                status: status,
                nonce: '<?php echo esc_js( wp_create_nonce( 'sts_filter_agent_tickets' ) ); ?>'
            },
            beforeSend: function() {
                $('.sts-agent-tickets-table tbody').html('<tr><td colspan="8" class="text-center"><span class="spinner is-active"></span> Loading tickets...</td></tr>');
            },
            success: function(response) {
                if (response.success) {
                    $('.sts-agent-tickets-table tbody').html(response.data.html);
                } else {
                    $('.sts-agent-tickets-table tbody').html('<tr><td colspan="8" class="text-center">Error loading tickets</td></tr>');
                }
            },
            error: function() {
                $('.sts-agent-tickets-table tbody').html('<tr><td colspan="8" class="text-center">Network error. Please try again.</td></tr>');
            }
        });
    });
    
    // Assign ticket to myself
    $('.sts-assign-to-me').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var ticketId = button.data('ticket-id');
        var nonce = button.data('nonce');
        
        $.ajax({
            url: sts_frontend_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sts_assign_ticket_to_me',
                ticket_id: ticketId,
                nonce: nonce
            },
            beforeSend: function() {
                button.prop('disabled', true).text('Assigning...');
            },
            success: function(response) {
                button.prop('disabled', false).html('👤');
                
                if (response.success) {
                    showAgentNotice('Ticket assigned to you!', 'success');
                } else {
                    showAgentNotice(response.data || 'Error assigning ticket', 'error');
                }
            },
            error: function() {
                button.prop('disabled', false).html('👤');
                showAgentNotice('Network error. Please try again.', 'error');
            }
        });
    });
    
    // Update ticket status
    $('.sts-update-status-select').on('change', function() {
        var ticketId = $(this).data('ticket-id');
        var newStatus = $(this).val();
        var nonce = $(this).data('nonce');
        
        $.ajax({
            url: sts_frontend_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sts_update_ticket_status',
                ticket_id: ticketId,
                status: newStatus,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    showAgentNotice('Status updated successfully!', 'success');
                } else {
                    showAgentNotice(response.data || 'Error updating status', 'error');
                }
            },
            error: function() {
                showAgentNotice('Network error. Please try again.', 'error');
            }
        });
    });
    
    // Update ticket priority
    $('.sts-update-priority-select').on('change', function() {
        var ticketId = $(this).data('ticket-id');
        var newPriority = $(this).val();
        var nonce = $(this).data('nonce');
        
        $.ajax({
            url: sts_frontend_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sts_update_ticket_priority',
                ticket_id: ticketId,
                priority: newPriority,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    showAgentNotice('Priority updated successfully!', 'success');
                } else {
                    showAgentNotice(response.data || 'Error updating priority', 'error');
                }
            },
            error: function() {
                showAgentNotice('Network error. Please try again.', 'error');
            }
        });
    });
    
    // Search tickets
    $('#sts-agent-search').on('keyup', function() {
        var searchTerm = $(this).val();
        
        if (searchTerm.length >= 2 || searchTerm.length === 0) {
            $.ajax({
                url: sts_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sts_search_agent_tickets',
                    search: searchTerm,
                    nonce: '<?php echo esc_js( wp_create_nonce( 'sts_search_agent_tickets' ) ); ?>'
                },
                beforeSend: function() {
                    $('.sts-agent-tickets-table tbody').html('<tr><td colspan="8" class="text-center"><span class="spinner is-active"></span> Searching tickets...</td></tr>');
                },
                success: function(response) {
                    if (response.success) {
                        $('.sts-agent-tickets-table tbody').html(response.data.html);
                    } else {
                        $('.sts-agent-tickets-table tbody').html('<tr><td colspan="8" class="text-center">Error searching tickets</td></tr>');
                    }
                },
                error: function() {
                    $('.sts-agent-tickets-table tbody').html('<tr><td colspan="8" class="text-center">Network error. Please try again.</td></tr>');
                }
            });
        }
    });
    
    function showAgentNotice(message, type) {
        // Remove existing notices
        $('.sts-agent-notice').remove();
        
        // Create new notice
        var noticeClass = type === 'success' ? 'sts-alert-success' : 'sts-alert-error';
        var notice = $('<div class="sts-alert ' + noticeClass + ' sts-agent-notice"><p>' + message + '</p></div>');
        
        // Insert at top of dashboard
        $('.sts-agent-dashboard').prepend(notice);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
});
</script>
