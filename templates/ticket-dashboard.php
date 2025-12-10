<?php
/**
 * Ticket Dashboard Template
 * 
 * @package SupportTicketSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Get user tickets
$user_id = get_current_user_id();
$tickets = STS_Ticket_Dashboard_Shortcode::get_user_tickets( $user_id );
$stats = STS_Ticket_Dashboard_Shortcode::get_ticket_stats( $user_id );

// Get filter status
$current_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'all';
?>

<div class="sts-dashboard">
    <div class="sts-dashboard-header">
        <h1 class="sts-dashboard-title">
            <?php esc_html_e( 'My Support Tickets', 'support-ticket-system' ); ?>
        </h1>
        
        <a href="?page=submit-ticket" class="sts-new-ticket-btn">
            <?php esc_html_e( '+ New Ticket', 'support-ticket-system' ); ?>
        </a>
    </div>
    
    <!-- Stats Cards -->
    <div class="sts-stats-cards">
        <div class="sts-stat-card open">
            <span class="sts-stat-number"><?php echo esc_html( $stats['open'] ); ?></span>
            <span class="sts-stat-label"><?php esc_html_e( 'Open', 'support-ticket-system' ); ?></span>
        </div>
        
        <div class="sts-stat-card in-progress">
            <span class="sts-stat-number"><?php echo esc_html( $stats['in_progress'] ); ?></span>
            <span class="sts-stat-label"><?php esc_html_e( 'In Progress', 'support-ticket-system' ); ?></span>
        </div>
        
        <div class="sts-stat-card resolved">
            <span class="sts-stat-number"><?php echo esc_html( $stats['resolved'] ); ?></span>
            <span class="sts-stat-label"><?php esc_html_e( 'Resolved', 'support-ticket-system' ); ?></span>
        </div>
        
        <div class="sts-stat-card closed">
            <span class="sts-stat-number"><?php echo esc_html( $stats['closed'] ); ?></span>
            <span class="sts-stat-label"><?php esc_html_e( 'Closed', 'support-ticket-system' ); ?></span>
        </div>
    </div>
    
    <!-- Filter and Search -->
    <div class="sts-dashboard-toolbar">
        <div class="sts-filter-section">
            <label for="sts-status-filter"><?php esc_html_e( 'Filter by status:', 'support-ticket-system' ); ?></label>
            <select id="sts-status-filter" class="sts-filter-tickets" data-nonce="<?php echo esc_attr( wp_create_nonce( 'sts_filter_tickets' ) ); ?>">
                <option value="all" <?php selected( $current_filter, 'all' ); ?>>
                    <?php esc_html_e( 'All Tickets', 'support-ticket-system' ); ?>
                </option>
                <option value="open" <?php selected( $current_filter, 'open' ); ?>>
                    <?php esc_html_e( 'Open', 'support-ticket-system' ); ?>
                </option>
                <option value="in_progress" <?php selected( $current_filter, 'in_progress' ); ?>>
                    <?php esc_html_e( 'In Progress', 'support-ticket-system' ); ?>
                </option>
                <option value="resolved" <?php selected( $current_filter, 'resolved' ); ?>>
                    <?php esc_html_e( 'Resolved', 'support-ticket-system' ); ?>
                </option>
                <option value="closed" <?php selected( $current_filter, 'closed' ); ?>>
                    <?php esc_html_e( 'Closed', 'support-ticket-system' ); ?>
                </option>
            </select>
        </div>
        
        <div class="sts-search-section">
            <input type="text" id="sts-ticket-search" class="sts-search-tickets" 
                   placeholder="<?php esc_attr_e( 'Search tickets...', 'support-ticket-system' ); ?>"
                   data-nonce="<?php echo esc_attr( wp_create_nonce( 'sts_search_tickets' ) ); ?>">
        </div>
    </div>
    
    <!-- Tickets Table -->
    <div class="sts-tickets-table">
        <?php if ( ! empty( $tickets ) ) : ?>
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Ticket ID', 'support-ticket-system' ); ?></th>
                        <th><?php esc_html_e( 'Subject', 'support-ticket-system' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'support-ticket-system' ); ?></th>
                        <th><?php esc_html_e( 'Priority', 'support-ticket-system' ); ?></th>
                        <th><?php esc_html_e( 'Created', 'support-ticket-system' ); ?></th>
                        <th><?php esc_html_e( 'Last Update', 'support-ticket-system' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'support-ticket-system' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $tickets as $ticket ) : 
                        $ticket_url = add_query_arg( array( 'ticket_id' => $ticket->id ), get_permalink() );
                        $created_date = date_i18n( get_option( 'date_format' ), strtotime( $ticket->created_at ) );
                        $updated_date = date_i18n( get_option( 'date_format' ), strtotime( $ticket->updated_at ) );
                    ?>
                    <tr>
                        <td class="sts-ticket-id">#<?php echo esc_html( $ticket->id ); ?></td>
                        <td class="sts-ticket-subject">
                            <a href="<?php echo esc_url( $ticket_url ); ?>">
                                <?php echo esc_html( $ticket->subject ); ?>
                            </a>
                        </td>
                        <td>
                            <?php
                            $status_class = 'sts-badge-' . str_replace( '_', '-', $ticket->status );
                            ?>
                            <span class="sts-status-badge <?php echo esc_attr( $status_class ); ?>">
                                <?php echo esc_html( ucfirst( str_replace( '_', ' ', $ticket->status ) ) ); ?>
                            </span>
                        </td>
                        <td>
                            <span class="sts-priority-indicator sts-priority-<?php echo esc_attr( $ticket->priority ); ?>"></span>
                            <?php echo esc_html( ucfirst( $ticket->priority ) ); ?>
                        </td>
                        <td><?php echo esc_html( $created_date ); ?></td>
                        <td><?php echo esc_html( $updated_date ); ?></td>
                        <td class="sts-ticket-actions">
                            <a href="<?php echo esc_url( $ticket_url ); ?>" class="sts-view-btn">
                                <?php esc_html_e( 'View', 'support-ticket-system' ); ?>
                            </a>
                            <?php if ( $ticket->status === 'open' || $ticket->status === 'in_progress' ) : ?>
                            <a href="<?php echo esc_url( add_query_arg( array( 'action' => 'close', 'ticket_id' => $ticket->id ), get_permalink() ) ); ?>" 
                               class="sts-close-btn" 
                               onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to close this ticket?', 'support-ticket-system' ); ?>')">
                                <?php esc_html_e( 'Close', 'support-ticket-system' ); ?>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="sts-no-tickets">
                <p><?php esc_html_e( 'You have no support tickets yet.', 'support-ticket-system' ); ?></p>
                <a href="?page=submit-ticket" class="sts-create-first-ticket">
                    <?php esc_html_e( 'Create your first ticket', 'support-ticket-system' ); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ( $stats['total'] > 10 ) : ?>
    <div class="sts-pagination">
        <?php
        $paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $total_pages = ceil( $stats['total'] / 10 );
        
        if ( $total_pages > 1 ) :
        ?>
        <nav class="sts-pagination-nav">
            <?php if ( $paged > 1 ) : ?>
            <a href="<?php echo esc_url( add_query_arg( 'paged', $paged - 1 ) ); ?>" class="sts-pagination-prev">
                <?php esc_html_e( 'Previous', 'support-ticket-system' ); ?>
            </a>
            <?php endif; ?>
            
            <span class="sts-pagination-info">
                <?php printf( esc_html__( 'Page %1$d of %2$d', 'support-ticket-system' ), $paged, $total_pages ); ?>
            </span>
            
            <?php if ( $paged < $total_pages ) : ?>
            <a href="<?php echo esc_url( add_query_arg( 'paged', $paged + 1 ) ); ?>" class="sts-pagination-next">
                <?php esc_html_e( 'Next', 'support-ticket-system' ); ?>
            </a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Quick Help -->
    <div class="sts-dashboard-help">
        <h3><?php esc_html_e( 'Need Help?', 'support-ticket-system' ); ?></h3>
        <ul>
            <li>
                <strong><?php esc_html_e( 'Response Time:', 'support-ticket-system' ); ?></strong>
                <?php esc_html_e( 'We typically respond within 24-48 hours.', 'support-ticket-system' ); ?>
            </li>
            <li>
                <strong><?php esc_html_e( 'Working Hours:', 'support-ticket-system' ); ?></strong>
                <?php esc_html_e( 'Monday to Friday, 9 AM - 6 PM EST', 'support-ticket-system' ); ?>
            </li>
            <li>
                <strong><?php esc_html_e( 'Emergency Support:', 'support-ticket-system' ); ?></strong>
                <?php esc_html_e( 'For critical issues, please call our emergency line.', 'support-ticket-system' ); ?>
            </li>
        </ul>
    </div>
</div>
