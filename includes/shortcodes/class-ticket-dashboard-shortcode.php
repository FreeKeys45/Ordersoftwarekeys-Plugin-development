<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class STS_Ticket_Dashboard_Shortcode {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_shortcode('support_ticket_dashboard', array($this, 'render'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    public function enqueue_assets() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'support_ticket_dashboard')) {
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
       
        $current_user = wp_get_current_user();
        $tickets = $this->get_user_tickets($current_user->ID);
        $metrics = $this->get_ticket_metrics($current_user->ID);
       
        // Use template file if it exists
        if (file_exists(STS_PLUGIN_PATH . 'templates/ticket-dashboard.php')) {
            ob_start();
            include(STS_PLUGIN_PATH . 'templates/ticket-dashboard.php');
            return ob_get_clean();
        }
       
        // Fallback to inline template
        return $this->render_fallback($current_user, $tickets, $metrics);
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
    
    private function get_user_tickets($user_id) {
        $args = array(
            'post_type' => 'support_ticket',
            'author' => $user_id,
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        );
       
        return get_posts($args);
    }
    
    private function get_ticket_metrics($user_id) {
        $all_tickets = $this->get_user_tickets($user_id);
        $open_tickets = array_filter($all_tickets, function($ticket) {
            return get_post_meta($ticket->ID, 'status', true) === 'open';
        });
       
        $closed_tickets = array_filter($all_tickets, function($ticket) {
            return get_post_meta($ticket->ID, 'status', true) === 'closed';
        });
       
        $pending_tickets = array_filter($all_tickets, function($ticket) {
            return get_post_meta($ticket->ID, 'status', true) === 'pending';
        });
       
        return array(
            'total' => count($all_tickets),
            'open' => count($open_tickets),
            'closed' => count($closed_tickets),
            'pending' => count($pending_tickets)
        );
    }
    
    private function render_fallback($current_user, $tickets, $metrics) {
        ob_start();
        ?>
        <div class="support-ticket-system-wrapper">
            <div class="support-ticket-container">
                <div class="support-ticket-card">
                    <div class="support-ticket-header">
                        <h1 class="support-ticket-title"><?php _e('My Support Tickets', 'support-ticket-system'); ?></h1>
                        <a href="<?php echo esc_url(home_url('/submit-ticket/')); ?>" class="support-ticket-btn support-ticket-btn-primary">
                            <?php _e('Create New Ticket', 'support-ticket-system'); ?>
                        </a>
                    </div>

                    <!-- Metrics Dashboard -->
                    <div class="support-ticket-metrics">
                        <div class="support-ticket-metric-card">
                            <div class="support-ticket-metric-value"><?php echo $metrics['total']; ?></div>
                            <div class="support-ticket-metric-label"><?php _e('Total Tickets', 'support-ticket-system'); ?></div>
                        </div>
                        <div class="support-ticket-metric-card">
                            <div class="support-ticket-metric-value"><?php echo $metrics['open']; ?></div>
                            <div class="support-ticket-metric-label"><?php _e('Open Tickets', 'support-ticket-system'); ?></div>
                        </div>
                        <div class="support-ticket-metric-card">
                            <div class="support-ticket-metric-value"><?php echo $metrics['closed']; ?></div>
                            <div class="support-ticket-metric-label"><?php _e('Closed Tickets', 'support-ticket-system'); ?></div>
                        </div>
                        <div class="support-ticket-metric-card">
                            <div class="support-ticket-metric-value"><?php echo $metrics['pending']; ?></div>
                            <div class="support-ticket-metric-label"><?php _e('Pending Tickets', 'support-ticket-system'); ?></div>
                        </div>
                    </div>

                    <!-- Search and Filters -->
                    <div class="support-ticket-search">
                        <input type="text" id="support-ticket-search" placeholder="<?php _e('Search tickets...', 'support-ticket-system'); ?>">
                    </div>

                    <div class="support-ticket-filters">
                        <select id="support-status-filter">
                            <option value=""><?php _e('All Statuses', 'support-ticket-system'); ?></option>
                            <option value="open"><?php _e('Open', 'support-ticket-system'); ?></option>
                            <option value="pending"><?php _e('Pending', 'support-ticket-system'); ?></option>
                            <option value="closed"><?php _e('Closed', 'support-ticket-system'); ?></option>
                        </select>
                       
                        <select id="support-priority-filter">
                            <option value=""><?php _e('All Priorities', 'support-ticket-system'); ?></option>
                            <option value="urgent"><?php _e('Urgent', 'support-ticket-system'); ?></option>
                            <option value="high"><?php _e('High', 'support-ticket-system'); ?></option>
                            <option value="medium"><?php _e('Medium', 'support-ticket-system'); ?></option>
                            <option value="low"><?php _e('Low', 'support-ticket-system'); ?></option>
                        </select>
                    </div>

                    <!-- Bulk Actions -->
                    <div class="support-ticket-bulk-actions">
                        <select id="support-bulk-action">
                            <option value=""><?php _e('Bulk Actions', 'support-ticket-system'); ?></option>
                            <option value="close"><?php _e('Close Tickets', 'support-ticket-system'); ?></option>
                        </select>
                        <button type="button" class="support-ticket-btn" id="support-apply-bulk"><?php _e('Apply', 'support-ticket-system'); ?></button>
                    </div>

                    <!-- Need Help Section -->
                    <div class="support-ticket-help-section-email">
                        <h3><?php _e('Need Help?', 'support-ticket-system'); ?></h3>
                        <p><?php _e('If you have any issues with your purchase, click the button below to report a problem:', 'support-ticket-system'); ?></p>
                        <a href="<?php echo esc_url(home_url('/submit-ticket/')); ?>" class="support-ticket-btn-report-email">
                            <span class="support-ticket-btn-icon"> </span>
                            <?php _e('Report a Problem', 'support-ticket-system'); ?>
                        </a>
                    </div>
                   
                    <?php if (empty($tickets)): ?>
                        <div class="support-ticket-no-tickets">
                            <p><?php _e('You haven\'t submitted any support tickets yet.', 'support-ticket-system'); ?></p>
                            <a href="<?php echo esc_url(home_url('/submit-ticket/')); ?>" class="support-ticket-btn support-ticket-btn-primary">
                                <?php _e('Create Your First Ticket', 'support-ticket-system'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="support-ticket-list">
                            <?php foreach ($tickets as $ticket):
                                $ticket_id = get_post_meta($ticket->ID, 'ticket_id', true);
                                $status = get_post_meta($ticket->ID, 'status', true);
                                $priority = get_post_meta($ticket->ID, 'priority', true);
                                $last_update = get_post_meta($ticket->ID, 'last_update', true);
                                $product_name = get_post_meta($ticket->ID, 'product_name', true);
                                $assigned_agent_id = get_post_meta($ticket->ID, 'assigned_agent', true);
                                $assigned_agent = $assigned_agent_id ? get_userdata($assigned_agent_id) : null;
                            ?>
                                <div class="support-ticket-item" data-ticket="<?php echo $ticket->ID; ?>">
                                    <input type="checkbox" class="support-ticket-checkbox" value="<?php echo $ticket->ID; ?>">
                                    <div class="support-ticket-id">#<?php echo esc_html($ticket_id ?: $ticket->ID); ?></div>
                                    <div class="support-ticket-product"><?php echo esc_html($product_name ?: __('No product', 'support-ticket-system')); ?></div>
                                    <?php if ($assigned_agent): ?>
                                        <div class="support-ticket-agent">
                                            <strong><?php _e('Agent:', 'support-ticket-system'); ?></strong> <?php echo esc_html($assigned_agent->display_name); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="support-ticket-status support-status-<?php echo esc_attr($status ?: 'open'); ?>">
                                        <?php echo esc_html(ucfirst($status ?: 'open')); ?>
                                    </div>
                                    <div class="support-ticket-priority support-priority-<?php echo esc_attr($priority ?: 'medium'); ?>">
                                        <?php echo esc_html(ucfirst($priority ?: 'medium')); ?></div>
                                    <div class="support-ticket-date"><?php echo esc_html(date('M j, Y', strtotime($last_update ?: $ticket->post_date))); ?></div>
                                    <div class="support-ticket-quick-actions">
                                        <button class="support-ticket-quick-btn" data-action="view" data-ticket="<?php echo $ticket->ID; ?>"> </button>
                                        <?php if ($status !== 'closed'): ?>
                                            <button class="support-ticket-quick-btn" data-action="close" data-ticket="<?php echo $ticket->ID; ?>"> </button>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?php echo esc_url(support_get_ticket_view_url($ticket_id ?: $ticket->ID)); ?>" class="support-ticket-btn support-ticket-btn-secondary">
                                        <?php _e('View', 'support-ticket-system'); ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
