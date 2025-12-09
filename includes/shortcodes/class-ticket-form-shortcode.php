<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class STS_Ticket_Form_Shortcode {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_shortcode('support_ticket_form', array($this, 'render'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    public function enqueue_assets() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'support_ticket_form')) {
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
                'nonce'    => wp_create_nonce('support_ticket_nonce'),
                'strings'  => array(
                    'loading' => __('Loading...', 'support-ticket-system'),
                    'error' => __('An error occurred. Please try again.', 'support-ticket-system'),
                    'success' => __('Success!', 'support-ticket-system'),
                    'required_field' => __('This field is required.', 'support-ticket-system'),
                    'file_too_big' => __('File is too large. Maximum size is 50MB.', 'support-ticket-system'),
                    'invalid_file_type' => __('Invalid file type. Allowed types: png, jpg, jpeg, pdf, txt, mp4, mov, avi, webm', 'support-ticket-system'),
                )
            ));
        }
    }
    
    public function render($atts = array()) {
        if (!is_user_logged_in()) {
            return $this->login_required_message();
        }
       
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return '<div class="support-ticket-container"><div class="support-ticket-card"><div class="support-ticket-alert support-ticket-alert-error">' . __('WooCommerce is required for this feature.', 'support-ticket-system') . '</div></div></div>';
        }
       
        $current_user = wp_get_current_user();
        $orders = $this->get_user_orders();
       
        // Get order_id from URL if present
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $product_name = isset($_GET['product']) ? sanitize_text_field($_GET['product']) : '';
       
        // Use template file if it exists
        if (file_exists(STS_PLUGIN_PATH . 'templates/ticket-form.php')) {
            ob_start();
            include(STS_PLUGIN_PATH . 'templates/ticket-form.php');
            return ob_get_clean();
        }
       
        // Fallback to inline template
        return $this->render_fallback($current_user, $orders, $order_id, $product_name);
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
    
    private function get_user_orders() {
        if (!is_user_logged_in() || !class_exists('WooCommerce')) {
            return array();
        }
       
        $customer_orders = wc_get_orders(array(
            'customer_id' => get_current_user_id(),
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'status' => array('completed', 'processing'),
        ));
       
        return $customer_orders;
    }
    
    private function render_fallback($current_user, $orders, $order_id, $product_name) {
        ob_start();
        ?>
        <div class="support-ticket-system-wrapper">
            <div class="support-ticket-container">
                <div class="support-ticket-card">
                    <div class="support-ticket-submit-title">
                        <h1><?php _e('Create a Support Ticket', 'support-ticket-system'); ?></h1>
                        <p style="color: #718096; font-size: 16px; margin-top: 10px;"><?php _e('Fill out the form below to get help with your purchase', 'support-ticket-system'); ?></p>
                    </div>
                   
                    <div id="support-ticket-form-message"></div>
                   
                    <?php if (empty($orders)): ?>
                        <div class="support-ticket-alert support-ticket-alert-info">
                            <?php _e('You need to have at least one completed order to submit a support ticket.', 'support-ticket-system'); ?>
                        </div>
                    <?php else: ?>
                        <div class="support-ticket-form-container">
                            <form id="support-ticket-form" method="post" enctype="multipart/form-data">
                                <div class="support-ticket-form-section">
                                    <h3><?php _e('Order Information', 'support-ticket-system'); ?></h3>
                                    <?php if ($order_id): ?>
                                        <div class="support-ticket-order-info">
                                            <?php
                                            $order = wc_get_order($order_id);
                                            if ($order && $order->get_user_id() === $current_user->ID) {
                                                echo '<p><strong>' . __('Order ID:', 'support-ticket-system') . '</strong> #' . $order_id . '</p>';
                                                echo '<p><strong>' . __('Order Date:', 'support-ticket-system') . '</strong> ' . $order->get_date_created()->format('F j, Y') . '</p>';
                                                echo '<p><strong>' . __('Total:', 'support-ticket-system') . '</strong> ' . $order->get_formatted_order_total() . '</p>';
                                            }
                                            ?>
                                        </div>
                                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                    <?php else: ?>
                                        <div class="support-ticket-form-group">
                                            <label for="support-order-id"><?php _e('Order ID', 'support-ticket-system'); ?> *</label>
                                            <select id="support-order-id" name="order_id" class="support-ticket-form-control required" required>
                                                <option value=""><?php _e('Select your order', 'support-ticket-system'); ?></option>
                                                <?php foreach ($orders as $order): ?>
                                                    <option value="<?php echo $order->get_id(); ?>" <?php selected($order_id, $order->get_id()); ?>>
                                                        <?php printf(__('Order #%s - %s - %s', 'support-ticket-system'), $order->get_id(), $order->get_date_created()->format('M j, Y'), wc_price($order->get_total())); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>
                                   
                                    <div class="support-ticket-form-group">
                                        <label for="support-product-name"><?php _e('Product', 'support-ticket-system'); ?> *</label>
                                        <select id="support-product-name" name="product_name" class="support-ticket-form-control required" required>
                                            <option value=""><?php _e('Select a product', 'support-ticket-system'); ?></option>
                                            <?php
                                            if ($order_id) {
                                                $selected_order = wc_get_order($order_id);
                                                if ($selected_order && $selected_order->get_user_id() === $current_user->ID) {
                                                    foreach ($selected_order->get_items() as $item) {
                                                        $product = $item->get_product();
                                                        if ($product) {
                                                            $selected = ($product_name && $product->get_name() === $product_name) ? 'selected' : '';
                                                            echo '<option value="' . esc_attr($product->get_name()) . '" ' . $selected . '>' . esc_html($product->get_name()) . '</option>';
                                                        }
                                                    }
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="support-ticket-form-section">
                                    <h3><?php _e('Issue Details', 'support-ticket-system'); ?></h3>
                                   
                                    <div class="support-ticket-form-group">
                                        <label for="support-issue-category"><?php _e('Issue Category', 'support-ticket-system'); ?> *</label>
                                        <select id="support-issue-category" name="issue_category" class="support-ticket-form-control required" required>
                                            <option value=""><?php _e('Select a category', 'support-ticket-system'); ?></option>
                                            <option value="license_issue"><?php _e('Issue with License Key', 'support-ticket-system'); ?></option>
                                            <option value="technical"><?php _e('Technical Issue', 'support-ticket-system'); ?></option>
                                            <option value="billing"><?php _e('Billing Question', 'support-ticket-system'); ?></option>
                                            <option value="feature"><?php _e('Feature Request', 'support-ticket-system'); ?></option>
                                            <option value="other"><?php _e('Other', 'support-ticket-system'); ?></option>
                                        </select>
                                    </div>

                                    <div class="support-ticket-form-group support-ticket-license-section" style="display: none;">
                                        <div class="support-license-key-group">
                                            <label><?php _e('License Keys', 'support-ticket-system'); ?></label>
                                            <p class="support-license-key-description"><?php _e('Enter your license keys manually. Click the + button to add multiple keys.', 'support-ticket-system'); ?></p>
                                           
                                            <div class="support-license-keys-container">
                                                <div class="support-license-key-row">
                                                    <div class="support-license-key-input">
                                                        <input type="text" name="license_keys[]" class="support-ticket-form-control" placeholder="<?php _e('Enter license key manually', 'support-ticket-system'); ?>">
                                                    </div>
                                                    <button type="button" class="support-license-key-add">+ <?php _e('Add Key', 'support-ticket-system'); ?></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="support-ticket-form-group support-ticket-license-status-section" style="display: none;">
                                        <label for="support-license-status"><?php _e('License Key Status', 'support-ticket-system'); ?> *</label>
                                        <select id="support-license-status" name="license_status" class="support-ticket-form-control">
                                            <option value=""><?php _e('Select license status', 'support-ticket-system'); ?></option>
                                            <option value="invalid"><?php _e('Invalid License', 'support-ticket-system'); ?></option>
                                            <option value="revoked"><?php _e('License Revoked', 'support-ticket-system'); ?></option>
                                            <option value="duplicate"><?php _e('Duplicate License', 'support-ticket-system'); ?></option>
                                            <option value="expired"><?php _e('License Expired', 'support-ticket-system'); ?></option>
                                            <option value="activation_limit"><?php _e('Activation Limit Reached', 'support-ticket-system'); ?></option>
                                        </select>
                                    </div>
                                   
                                    <div class="support-ticket-form-group">
                                        <label for="support-priority"><?php _e('Priority', 'support-ticket-system'); ?></label>
                                        <select id="support-priority" name="priority" class="support-ticket-form-control">
                                            <option value="low"><?php _e('Low', 'support-ticket-system'); ?></option>
                                            <option value="medium" selected><?php _e('Medium', 'support-ticket-system'); ?></option>
                                            <option value="high"><?php _e('High', 'support-ticket-system'); ?></option>
                                            <option value="urgent"><?php _e('Urgent', 'support-ticket-system'); ?></option>
                                        </select>
                                    </div>
                                   
                                    <div class="support-ticket-form-group">
                                        <label for="support-description"><?php _e('Description', 'support-ticket-system'); ?> *</label>
                                        <textarea id="support-description" name="description" class="support-ticket-form-control required" rows="8" required placeholder="<?php _e('Please describe your issue in detail. Include any error messages, steps to reproduce, and what you were trying to accomplish.', 'support-ticket-system'); ?>"></textarea>
                                    </div>
                                </div>

                                <div class="support-ticket-form-section">
                                    <h3><?php _e('Attachments', 'support-ticket-system'); ?></h3>
                                    <div class="support-ticket-form-note">
                                        <strong><?php _e('Optional:', 'support-ticket-system'); ?></strong> <?php _e('You can upload screenshots or screen recordings to help us understand your issue better.', 'support-ticket-system'); ?>
                                    </div>
                                   
                                    <div class="support-ticket-form-group">
                                        <div class="support-ticket-file-upload">
                                            <input type="file" id="support-attachments" name="attachments[]" class="support-ticket-file-input" multiple accept=".png,.jpg,.jpeg,.pdf,.txt,.mp4,.mov,.avi,.webm">
                                            <small><?php _e('You can upload up to 5 files. Maximum file size: 50MB. Allowed types: PNG, JPG, JPEG, PDF, TXT, MP4, MOV, AVI, WEBM', 'support-ticket-system'); ?></small>
                                            <div class="support-ticket-file-list"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="support-ticket-form-section">
                                    <h3><?php _e('Contact Information', 'support-ticket-system'); ?></h3>
                                   
                                    <div class="support-ticket-form-group">
                                        <label for="support-name"><?php _e('Your Name', 'support-ticket-system'); ?> *</label>
                                        <input type="text" id="support-name" name="customer_name" class="support-ticket-form-control required" required value="<?php echo esc_attr($current_user->display_name); ?>">
                                    </div>
                                   
                                    <div class="support-ticket-form-group">
                                        <label for="support-email"><?php _e('Your Email', 'support-ticket-system'); ?> *</label>
                                        <input type="email" id="support-email" name="customer_email" class="support-ticket-form-control required" required value="<?php echo esc_attr($current_user->user_email); ?>">
                                    </div>
                                </div>
                               
                                <button type="submit" class="support-ticket-btn support-ticket-btn-primary support-ticket-btn-block" style="padding: 15px; font-size: 16px;">
                                    <?php _e('Create Support Ticket', 'support-ticket-system'); ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
