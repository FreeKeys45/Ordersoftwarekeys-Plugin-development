<?php
/**
 * Ticket View Template
 * 
 * @package SupportTicketSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Get ticket ID from URL
$ticket_id = isset( $_GET['ticket_id'] ) ? absint( $_GET['ticket_id'] ) : 0;

if ( ! $ticket_id ) {
    echo '<p class="sts-error">' . esc_html__( 'Invalid ticket ID.', 'support-ticket-system' ) . '</p>';
    return;
}

// Get ticket details
$ticket = STS_Ticket_View_Shortcode::get_ticket_by_id( $ticket_id );

if ( ! $ticket ) {
    echo '<p class="sts-error">' . esc_html__( 'Ticket not found.', 'support-ticket-system' ) . '</p>';
    return;
}

// Check if current user can view this ticket
$current_user_id = get_current_user_id();
if ( ! current_user_can( 'manage_options' ) && $ticket->user_id != $current_user_id ) {
    echo '<p class="sts-error">' . esc_html__( 'You do not have permission to view this ticket.', 'support-ticket-system' ) . '</p>';
    return;
}

// Get ticket messages
$messages = STS_Ticket_View_Shortcode::get_ticket_messages( $ticket_id );

// Format dates
$created_date = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->created_at ) );
$updated_date = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->updated_at ) );

// Get assigned agent name
$agent_name = 'Unassigned';
if ( $ticket->assigned_to ) {
    $agent = get_user_by( 'id', $ticket->assigned_to );
    if ( $agent ) {
        $agent_name = $agent->display_name;
    }
}

// Status badge class
$status_class = 'sts-badge-' . str_replace( '_', '-', $ticket->status );
?>

<div class="sts-ticket-view">
    <!-- Ticket Header -->
    <div class="sts-ticket-header">
        <div class="sts-ticket-meta">
            <div class="sts-ticket-id">
                <strong><?php esc_html_e( 'Ticket ID:', 'support-ticket-system' ); ?></strong>
                #<?php echo esc_html( $ticket->id ); ?>
            </div>
            
            <div class="sts-ticket-status">
                <span class="sts-status-badge <?php echo esc_attr( $status_class ); ?>">
                    <?php echo esc_html( ucfirst( str_replace( '_', ' ', $ticket->status ) ) ); ?>
                </span>
            </div>
        </div>
        
        <h1 class="sts-ticket-title"><?php echo esc_html( $ticket->subject ); ?></h1>
        
        <div class="sts-ticket-meta-details">
            <div class="sts-meta-item">
                <strong><?php esc_html_e( 'Created:', 'support-ticket-system' ); ?></strong>
                <?php echo esc_html( $created_date ); ?>
            </div>
            
            <div class="sts-meta-item">
                <strong><?php esc_html_e( 'Last Updated:', 'support-ticket-system' ); ?></strong>
                <?php echo esc_html( $updated_date ); ?>
            </div>
        </div>
    </div>
    
    <!-- Ticket Details -->
    <div class="sts-ticket-details">
        <h3><?php esc_html_e( 'Ticket Details', 'support-ticket-system' ); ?></h3>
        
        <div class="sts-details-grid">
            <div class="sts-detail-item">
                <span class="sts-detail-label"><?php esc_html_e( 'Priority:', 'support-ticket-system' ); ?></span>
                <span class="sts-detail-value sts-priority-<?php echo esc_attr( $ticket->priority ); ?>">
                    <?php echo esc_html( ucfirst( $ticket->priority ) ); ?>
                </span>
            </div>
            
            <div class="sts-detail-item">
                <span class="sts-detail-label"><?php esc_html_e( 'Department:', 'support-ticket-system' ); ?></span>
                <span class="sts-detail-value">
                    <?php echo esc_html( ucfirst( $ticket->department ) ); ?>
                </span>
            </div>
            
            <div class="sts-detail-item">
                <span class="sts-detail-label"><?php esc_html_e( 'Assigned To:', 'support-ticket-system' ); ?></span>
                <span class="sts-detail-value">
                    <?php echo esc_html( $agent_name ); ?>
                </span>
            </div>
            
            <div class="sts-detail-item">
                <span class="sts-detail-label"><?php esc_html_e( 'Requester:', 'support-ticket-system' ); ?></span>
                <span class="sts-detail-value">
                    <?php echo esc_html( $ticket->user_name ); ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Ticket Description -->
    <div class="sts-ticket-description">
        <h3><?php esc_html_e( 'Description', 'support-ticket-system' ); ?></h3>
        <div class="sts-description-content">
            <?php echo wpautop( esc_textarea( $ticket->description ) ); ?>
        </div>
    </div>
    
    <!-- Attachments -->
    <?php if ( ! empty( $ticket->attachments ) ) : 
        $attachments = maybe_unserialize( $ticket->attachments );
        if ( is_array( $attachments ) && ! empty( $attachments ) ) :
    ?>
    <div class="sts-ticket-attachments">
        <h3><?php esc_html_e( 'Attachments', 'support-ticket-system' ); ?></h3>
        <div class="sts-attachments-list">
            <?php foreach ( $attachments as $attachment ) : 
                $file_url = wp_get_attachment_url( $attachment );
                $file_name = get_the_title( $attachment );
                $file_type = wp_check_filetype( $file_url );
                $icon_class = 'sts-file-icon';
                
                // Add specific icon class based on file type
                if ( strpos( $file_type['type'], 'image' ) !== false ) {
                    $icon_class .= ' sts-file-image';
                } elseif ( strpos( $file_type['type'], 'pdf' ) !== false ) {
                    $icon_class .= ' sts-file-pdf';
                } elseif ( strpos( $file_type['type'], 'word' ) !== false ) {
                    $icon_class .= ' sts-file-word';
                }
            ?>
            <div class="sts-attachment-item">
                <div class="<?php echo esc_attr( $icon_class ); ?>"></div>
                <a href="<?php echo esc_url( $file_url ); ?>" target="_blank" class="sts-attachment-link">
                    <?php echo esc_html( $file_name ); ?>
                </a>
                <span class="sts-file-size"><?php echo esc_html( size_format( filesize( get_attached_file( $attachment ) ), 2 ) ); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; endif; ?>
    
    <!-- Conversation -->
    <div class="sts-ticket-conversation">
        <h3><?php esc_html_e( 'Conversation', 'support-ticket-system' ); ?></h3>
        
        <?php if ( ! empty( $messages ) ) : ?>
            <div class="sts-messages">
                <?php foreach ( $messages as $message ) : 
                    $message_date = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $message->created_at ) );
                    $author = $message->user_id ? get_user_by( 'id', $message->user_id ) : null;
                    $author_name = $author ? $author->display_name : $ticket->user_name;
                    $is_agent = $message->user_id && ( user_can( $message->user_id, 'manage_options' ) || user_can( $message->user_id, 'sts_agent' ) );
                ?>
                <div class="sts-message <?php echo $is_agent ? 'sts-message-agent' : 'sts-message-customer'; ?>">
                    <div class="sts-message-header">
                        <div class="sts-message-author">
                            <?php echo esc_html( $author_name ); ?>
                            <?php if ( $is_agent ) : ?>
                            <span class="sts-agent-badge"><?php esc_html_e( 'Agent', 'support-ticket-system' ); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="sts-message-date"><?php echo esc_html( $message_date ); ?></div>
                    </div>
                    <div class="sts-message-content">
                        <?php echo wpautop( esc_textarea( $message->message ) ); ?>
                    </div>
                    
                    <?php if ( ! empty( $message->attachments ) ) : 
                        $message_attachments = maybe_unserialize( $message->attachments );
                        if ( is_array( $message_attachments ) && ! empty( $message_attachments ) ) :
                    ?>
                    <div class="sts-message-attachments">
                        <?php foreach ( $message_attachments as $attachment ) : 
                            $file_url = wp_get_attachment_url( $attachment );
                            $file_name = get_the_title( $attachment );
                        ?>
                        <a href="<?php echo esc_url( $file_url ); ?>" target="_blank" class="sts-message-attachment">
                            📎 <?php echo esc_html( $file_name ); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p class="sts-no-messages"><?php esc_html_e( 'No messages yet.', 'support-ticket-system' ); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Reply Form -->
    <?php if ( $ticket->status !== 'closed' && $ticket->status !== 'resolved' ) : ?>
    <div class="sts-reply-form-wrapper">
        <h3><?php esc_html_e( 'Add a Reply', 'support-ticket-system' ); ?></h3>
        
        <form id="sts-ticket-reply-form" method="post">
            <?php wp_nonce_field( 'sts_reply_ticket', 'sts_reply_nonce' ); ?>
            <input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket_id ); ?>">
            
            <div class="sts-form-group">
                <textarea name="message" id="sts-reply-message" 
                          placeholder="<?php esc_attr_e( 'Type your reply here...', 'support-ticket-system' ); ?>"
                          rows="6" required></textarea>
            </div>
            
            <div class="sts-form-group">
                <label for="sts-reply-attachments">
                    <?php esc_html_e( 'Attach Files', 'support-ticket-system' ); ?>
                    <span class="sts-help-text">
                        <?php esc_html_e( 'Maximum file size: 5MB. Allowed types: jpg, png, pdf, doc, docx', 'support-ticket-system' ); ?>
                    </span>
                </label>
                <input type="file" id="sts-reply-attachments" name="attachments[]" multiple 
                       accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
            </div>
            
            <div class="sts-form-actions">
                <button type="submit" class="sts-submit-btn">
                    <?php esc_html_e( 'Send Reply', 'support-ticket-system' ); ?>
                </button>
                
                <?php if ( current_user_can( 'manage_options' ) || current_user_can( 'sts_agent' ) ) : ?>
                <button type="button" class="sts-resolve-btn sts-resolve-ticket-btn" 
                        data-ticket-id="<?php echo esc_attr( $ticket_id ); ?>"
                        data-nonce="<?php echo esc_attr( wp_create_nonce( 'sts_resolve_ticket' ) ); ?>">
                    <?php esc_html_e( 'Mark as Resolved', 'support-ticket-system' ); ?>
                </button>
                <?php endif; ?>
                
                <button type="button" class="sts-close-btn sts-close-ticket-btn" 
                        data-ticket-id="<?php echo esc_attr( $ticket_id ); ?>"
                        data-nonce="<?php echo esc_attr( wp_create_nonce( 'sts_close_ticket' ) ); ?>">
                    <?php esc_html_e( 'Close Ticket', 'support-ticket-system' ); ?>
                </button>
            </div>
        </form>
    </div>
    <?php else : ?>
    <div class="sts-ticket-closed-notice">
        <p>
            <?php if ( $ticket->status === 'closed' ) : ?>
                <?php esc_html_e( 'This ticket has been closed. You cannot reply to closed tickets.', 'support-ticket-system' ); ?>
            <?php else : ?>
                <?php esc_html_e( 'This ticket has been marked as resolved. If you need further assistance, please reply to reopen it.', 'support-ticket-system' ); ?>
            <?php endif; ?>
        </p>
        
        <?php if ( $ticket->status === 'resolved' ) : ?>
        <button type="button" class="sts-reopen-btn sts-reopen-ticket-btn" 
                data-ticket-id="<?php echo esc_attr( $ticket_id ); ?>"
                data-nonce="<?php echo esc_attr( wp_create_nonce( 'sts_reopen_ticket' ) ); ?>">
            <?php esc_html_e( 'Reopen Ticket', 'support-ticket-system' ); ?>
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Navigation -->
    <div class="sts-ticket-navigation">
        <a href="<?php echo esc_url( remove_query_arg( 'ticket_id' ) ); ?>" class="sts-back-btn">
            <?php esc_html_e( '← Back to Tickets', 'support-ticket-system' ); ?>
        </a>
    </div>
</div>
