<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class STS_Ticket_View_Shortcode {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_shortcode('support_ticket_view', array($this, 'render'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    public function enqueue_assets() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'support_ticket_view')) {
            wp_enqueue_script('jquery');
            wp_enqueue_style('support-ticket-frontend-css', 
                STS_PLUGIN_URL . 'assets/css/frontend.css', 
                array(), 
                STS_PLUGIN_VERSION
            );
            
            wp_enqueue_script('support-ticket-frontend-js', 
                STS_PLUGIN_URL . 'assets/js/frontend.js', 
                array('jquery'), 
                STS_PLUGIN_VERSION, 
                true
            );
            
            wp_localize_script('support-ticket-frontend-js', 'support_ticket', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('support_ticket_nonce')
            ));
        }
    }
    
    public function render($atts = array()) {
        if (!is_user_logged_in()) {
            return $this->login_required_message();
        }
       
        $ticket_id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : (isset($atts['id']) ? sanitize_text_field($atts['id']) : 0);
        if (!$ticket_id) {
            return '<div class="support-ticket-container"><div class="support-ticket-card"><div class="support-ticket-alert support-ticket-alert-error">' . __('Invalid ticket ID.', 'support-ticket-system') . '</div></div></div>';
        }
       
        // Find the ticket by ticket_id or post ID
        $tickets = get_posts(array(
            'post_type' => 'support_ticket',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'ticket_id',
                    'value' => $ticket_id,
                    'compare' => '=',
                )
            ),
            'posts_per_page' => 1,
        ));
       
        // If not found by meta, try by post ID
        if (empty($tickets) && is_numeric($ticket_id)) {
            $ticket = get_post($ticket_id);
            if ($ticket && $ticket->post_type === 'support_ticket') {
                $tickets = array($ticket);
            }
        }
       
        if (empty($tickets)) {
            return '<div class="support-ticket-container"><div class="support-ticket-card"><div class="support-ticket-alert support-ticket-alert-error">' . __('Ticket not found.', 'support-ticket-system') . '</div></div></div>';
        }
       
        $ticket = $tickets[0];
        $current_user = wp_get_current_user();
       
        // Check if user can view this ticket
        $can_view = false;
        if (current_user_can('administrator') || current_user_can('support_agent')) {
            $can_view = true;
        } elseif ($ticket->post_author == $current_user->ID) {
            $can_view = true;
        }
       
        if (!$can_view) {
            return '<div class="support-ticket-container"><div class="support-ticket-card"><div class="support-ticket-alert support-ticket-alert-error">' . __('You do not have permission to view this ticket.', 'support-ticket-system') . '</div></div></div>';
        }
       
        // Get ticket metadata
        $ticket_meta = array(
            'ticket_id' => get_post_meta($ticket->ID, 'ticket_id', true) ?: $ticket->ID,
            'order_id' => get_post_meta($ticket->ID, 'order_id', true),
            'order_date' => get_post_meta($ticket->ID, 'order_date', true),
            'product_name' => get_post_meta($ticket->ID, 'product_name', true),
            'license_keys' => get_post_meta($ticket->ID, 'license_keys', true) ?: array(),
            'customer_name' => get_post_meta($ticket->ID, 'customer_name', true),
            'customer_email' => get_post_meta($ticket->ID, 'customer_email', true),
            'status' => get_post_meta($ticket->ID, 'status', true) ?: 'open',
            'priority' => get_post_meta($ticket->ID, 'priority', true) ?: 'medium',
            'last_update' => get_post_meta($ticket->ID, 'last_update', true),
            'attachments' => get_post_meta($ticket->ID, 'attachments', true) ?: array(),
            'assigned_agent' => get_post_meta($ticket->ID, 'assigned_agent', true),
            'issue_category' => get_post_meta($ticket->ID, 'issue_category', true),
            'license_status' => get_post_meta($ticket->ID, 'license_status', true),
            'internal_notes' => get_post_meta($ticket->ID, 'internal_notes', true),
        );
       
        $assigned_agent = $ticket_meta['assigned_agent'] ? get_userdata($ticket_meta['assigned_agent']) : null;
       
        // Get replies
        $replies = get_posts(array(
            'post_type' => 'support_ticket_reply',
            'meta_query' => array(
                array(
                    'key' => 'ticket_id',
                    'value' => $ticket->ID,
                    'compare' => '=',
                ),
            ),
            'orderby' => 'date',
            'order' => 'ASC',
            'posts_per_page' => -1,
        ));
       
        // Get ticket timeline
        $timeline = $this->get_ticket_timeline($ticket->ID);
       
        // Use template file if it exists
        if (file_exists(STS_PLUGIN_PATH . 'templates/ticket-view.php')) {
            ob_start();
            include(STS_PLUGIN_PATH . 'templates/ticket-view.php');
            return ob_get_clean();
        }
       
        // Fallback to inline template
        return $this->render_fallback($ticket, $ticket_meta, $assigned_agent, $replies, $timeline, $current_user);
    }
    
    private function login_required_message() {
        return '<div class="support-ticket-container">
            <div class="support-ticket-card">
                <div class="support-ticket-alert support-ticket-alert-error">
                    <p>' . __('Please log in to access the support system.', 'support-ticket-system') . '</p>
                </div>
                <a href="' . wp_login_url(get_permalink()) . '" class="support-ticket-btn support-ticket-btn-block">
                    ' . __('Login to Continue', 'support-ticket-system') . '
                </a>
            </div>
        </div>';
    }
    
    private function get_ticket_timeline($ticket_id) {
        $timeline = array();
       
        // Ticket creation
        $ticket = get_post($ticket_id);
        $timeline[] = array(
            'date' => $ticket->post_date,
            'action' => 'ticket_created',
            'user' => get_userdata($ticket->post_author),
            'description' => __('Ticket created', 'support-ticket-system')
        );
       
        // Status changes
        $status_changes = get_post_meta($ticket_id, 'status_history', true);
        if ($status_changes) {
            $timeline = array_merge($timeline, $status_changes);
        }
       
        // Agent assignments
        $agent_changes = get_post_meta($ticket_id, 'agent_history', true);
        if ($agent_changes) {
            $timeline = array_merge($timeline, $agent_changes);
        }
       
        usort($timeline, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
       
        return $timeline;
    }
    
    public function add_ticket_assignment_interface($ticket_id) {
        if (!current_user_can('administrator') && !current_user_can('support_agent')) {
            return;
        }
       
        // Only get users who have been explicitly added as support agents
        $agents = get_users(array(
            'meta_key' => 'is_explicit_support_agent',
            'meta_value' => true,
            'fields' => array('ID', 'display_name')
        ));
       
        $current_agent = get_post_meta($ticket_id, 'assigned_agent', true);
       
        echo '<div class="support-ticket-assignment">';
        echo '<label for="support-assign-agent">' . __('Assign to:', 'support-ticket-system') . '</label>';
        echo '<select id="support-assign-agent" data-ticket-id="' . $ticket_id . '">';
        echo '<option value="">' . __('Unassigned', 'support-ticket-system') . '</option>';
        foreach ($agents as $agent) {
            $selected = $current_agent == $agent->ID ? 'selected' : '';
            echo '<option value="' . $agent->ID . '" ' . $selected . '>' . esc_html($agent->display_name) . '</option>';
        }
        echo '</select>';
        echo '</div>';
    }
    
    private function render_fallback($ticket, $ticket_meta, $assigned_agent, $replies, $timeline, $current_user) {
        ob_start();
        ?>
        <div class="support-ticket-system-wrapper">
            <div class="support-ticket-container">
                <div class="support-ticket-card">
                    <div class="support-ticket-header">
                        <h1 class="support-ticket-title"><?php printf(__('Ticket #%s', 'support-ticket-system'), esc_html($ticket_meta['ticket_id'])); ?></h1>
                        <div class="support-ticket-status support-status-<?php echo esc_attr($ticket_meta['status']); ?>">
                            <?php echo esc_html(ucfirst($ticket_meta['status'])); ?>
                        </div>
                    </div>
                   
                    <?php if ($assigned_agent): ?>
                        <div class="support-ticket-assigned-agent">
                            <strong><?php _e('Assigned Support Agent:', 'support-ticket-system'); ?></strong> <?php echo esc_html($assigned_agent->display_name); ?>
                            <?php if (current_user_can('administrator') || current_user_can('support_agent')): ?>
                                <br><small><?php _e('Email:', 'support-ticket-system'); ?> <?php echo esc_html($assigned_agent->user_email); ?></small>
                                <br>
                                <button class="support-remove-agent-btn" data-ticket-id="<?php echo $ticket->ID; ?>">
                                    <?php _e('Remove Agent from Ticket', 'support-ticket-system'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Agent Assignment (for admins/agents) -->
                    <?php if (current_user_can('administrator') || current_user_can('support_agent')): ?>
                        <?php $this->add_ticket_assignment_interface($ticket->ID); ?>
                    <?php endif; ?>

                    <!-- Internal Notes (for admins/agents) -->
                    <?php if (current_user_can('administrator') || current_user_can('support_agent')): ?>
                        <div class="support-ticket-internal-notes">
                            <h4><?php _e('Internal Notes', 'support-ticket-system'); ?></h4>
                            <textarea id="support-internal-notes" class="support-ticket-form-control" placeholder="<?php _e('Add internal notes here...', 'support-ticket-system'); ?>" data-ticket-id="<?php echo $ticket->ID; ?>"><?php echo esc_textarea($ticket_meta['internal_notes']); ?></textarea>
                            <button type="button" class="support-ticket-btn" id="support-save-notes" data-ticket-id="<?php echo $ticket->ID; ?>"><?php _e('Save Notes', 'support-ticket-system'); ?></button>
                        </div>
                    <?php endif; ?>
                   
                    <div class="support-ticket-message">
                        <div class="support-ticket-message-header">
                            <div class="support-ticket-message-author">
                                <?php echo esc_html($ticket_meta['customer_name'] ?: $current_user->display_name); ?>
                                <?php if ($assigned_agent && $ticket->post_author != $assigned_agent->ID): ?>
                                    <span class="support-ticket-message-agent">(<?php _e('Customer', 'support-ticket-system'); ?>)</span>
                                <?php endif; ?>
                            </div>
                            <div class="support-ticket-message-date"><?php echo esc_html(date('M j, Y H:i', strtotime($ticket->post_date))); ?></div>
                        </div>
                        <div class="support-ticket-message-content">
                            <p><strong><?php _e('Product:', 'support-ticket-system'); ?></strong> <?php echo esc_html($ticket_meta['product_name'] ?: __('Not specified', 'support-ticket-system')); ?></p>
                            <?php if ($ticket_meta['order_id']): ?>
                                <p><strong><?php _e('Order ID:', 'support-ticket-system'); ?></strong> <?php echo esc_html($ticket_meta['order_id']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($ticket_meta['license_keys'])): ?>
                                <p><strong><?php _e('License Keys:', 'support-ticket-system'); ?></strong>
                                    <?php
                                    if (is_array($ticket_meta['license_keys'])) {
                                        echo esc_html(implode(', ', $ticket_meta['license_keys']));
                                    } else {
                                        echo esc_html($ticket_meta['license_keys']);
                                    }
                                    ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($ticket_meta['issue_category'] === 'license_issue' && $ticket_meta['license_status']): ?>
                                <p><strong><?php _e('License Status:', 'support-ticket-system'); ?></strong>
                                    <span class="support-ticket-license-status license-status-<?php echo esc_attr($ticket_meta['license_status']); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $ticket_meta['license_status']))); ?>
                                    </span>
                                </p>
                            <?php endif; ?>
                            <p><strong><?php _e('Priority:', 'support-ticket-system'); ?></strong> <span class="support-ticket-priority support-priority-<?php echo esc_attr($ticket_meta['priority']); ?>"><?php echo esc_html(ucfirst($ticket_meta['priority'])); ?></span></p>
                            <p><strong><?php _e('Category:', 'support-ticket-system'); ?></strong> <?php echo esc_html(ucfirst(str_replace('_', ' ', $ticket_meta['issue_category']))); ?></p>
                            <div class="support-ticket-message-content-text">
                                <?php echo wpautop(wp_kses_post($ticket->post_content)); ?>
                            </div>
                        </div>
                        <?php if (!empty($ticket_meta['attachments'])): ?>
                            <div class="support-ticket-attachments">
                                <h4><?php _e('Attachments:', 'support-ticket-system'); ?></h4>
                                <?php foreach ((array)$ticket_meta['attachments'] as $attachment):
                                    $is_video = in_array(pathinfo($attachment['name'], PATHINFO_EXTENSION), ['mp4', 'mov', 'avi', 'webm']);
                                ?>
                                    <?php if ($is_video): ?>
                                        <a href="<?php echo esc_url($attachment['url']); ?>" class="support-ticket-attachment" target="_blank">
                                            <?php echo esc_html($attachment['name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url($attachment['url']); ?>" class="support-ticket-attachment" target="_blank">
                                            <?php echo esc_html($attachment['name']); ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                   
                    <?php if (!empty($replies)): ?>
                        <h3><?php _e('Conversation', 'support-ticket-system'); ?></h3>
                        <?php foreach ($replies as $reply): ?>
                            <?php
                            $reply_author = get_userdata($reply->post_author);
                            $reply_attachments = get_post_meta($reply->ID, 'attachments', true) ?: array();
                            $is_agent = $reply_author && ($reply_author->has_cap('administrator') || $reply_author->has_cap('support_agent'));
                            ?>
                            <div class="support-ticket-message">
                                <div class="support-ticket-message-header">
                                    <div class="support-ticket-message-author">
                                        <?php echo esc_html($reply_author ? $reply_author->display_name : __('Unknown', 'support-ticket-system')); ?>
                                        <?php if ($is_agent): ?>
                                            <span class="support-ticket-message-agent">(<?php _e('Support Agent', 'support-ticket-system'); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="support-ticket-message-date"><?php echo esc_html(date('M j, Y H:i', strtotime($reply->post_date))); ?></div>
                                </div>
                                <div class="support-ticket-message-content">
                                    <?php echo wpautop(wp_kses_post($reply->post_content)); ?>
                                </div>
                                <?php if (!empty($reply_attachments)): ?>
                                    <div class="support-ticket-attachments">
                                        <h4><?php _e('Attachments:', 'support-ticket-system'); ?></h4>
                                        <?php foreach ((array)$reply_attachments as $attachment):
                                            $is_video = in_array(pathinfo($attachment['name'], PATHINFO_EXTENSION), ['mp4', 'mov', 'avi', 'webm']);
                                        ?>
                                            <?php if ($is_video): ?>
                                                <a href="<?php echo esc_url($attachment['url']); ?>" class="support-ticket-attachment" target="_blank">
                                                    <?php echo esc_html($attachment['name']); ?>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php echo esc_url($attachment['url']); ?>" class="support-ticket-attachment" target="_blank">
                                                    <?php echo esc_html($attachment['name']); ?>
                                                </a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Ticket Timeline -->
                    <?php if (!empty($timeline)): ?>
                        <div class="support-ticket-timeline">
                            <h3><?php _e('Ticket History', 'support-ticket-system'); ?></h3>
                            <?php foreach ($timeline as $event): ?>
                                <div class="support-ticket-timeline-item">
                                    <div class="support-ticket-timeline-date">
                                        <?php echo esc_html(date('M j, Y H:i', strtotime($event['date']))); ?>
                                    </div>
                                    <div class="support-ticket-timeline-content">
                                        <strong><?php echo esc_html($event['description']); ?></strong>
                                        <?php if (isset($event['user'])): ?>
                                            <br><small><?php _e('By:', 'support-ticket-system'); ?> <?php echo esc_html($event['user']->display_name); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                   
                    <?php if ($ticket_meta['status'] !== 'closed'): ?>
                        <div class="support-ticket-reply-form">
                            <h3><?php _e('Add Reply', 'support-ticket-system'); ?></h3>
                            <div id="support-ticket-reply-message"></div>
                            <form id="support-ticket-reply-form" enctype="multipart/form-data">
                                <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket->ID); ?>">
                               
                                <div class="support-ticket-form-group">
                                    <label for="support-reply-content"><?php _e('Your Reply', 'support-ticket-system'); ?> *</label>
                                    <textarea id="support-reply-content" name="content" class="support-ticket-form-control required" rows="6" required placeholder="<?php _e('Type your reply here', 'support-ticket-system'); ?>"></textarea>
                                </div>
                               
                                <div class="support-ticket-form-group">
                                    <label for="support-reply-attachments"><?php _e('Attachments (Screenshots/Videos)', 'support-ticket-system'); ?></label>
                                    <div class="support-ticket-file-upload">
                                        <input type="file" id="support-reply-attachments" name="attachments[]" class="support-ticket-file-input" multiple accept=".png,.jpg,.jpeg,.pdf,.txt,.mp4,.mov,.avi,.webm">
                                        <small><?php _e('You can upload up to 5 files. Maximum file size: 50MB. Allowed types: PNG, JPG, JPEG, PDF, TXT, MP4, MOV, AVI, WEBM', 'support-ticket-system'); ?></small>
                                        <div class="support-ticket-file-list"></div>
                                    </div>
                                </div>
                               
                                <button type="submit" class="support-ticket-btn support-ticket-btn-primary">
                                    <?php _e('Submit Reply', 'support-ticket-system'); ?>
                                </button>
                            </form>
                        </div>
                       
                        <div class="support-ticket-close-section">
                            <div id="support-ticket-close-message"></div>
                            <button class="support-ticket-btn support-ticket-btn-danger support-ticket-close-btn" data-ticket-id="<?php echo esc_attr($ticket->ID); ?>">
                                <?php _e('Close Ticket', 'support-ticket-system'); ?>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="support-ticket-alert support-ticket-alert-info">
                            <?php _e('This ticket is closed. You cannot add new replies.', 'support-ticket-system'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
