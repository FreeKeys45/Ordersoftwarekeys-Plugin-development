<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class STS_AJAX_Handlers {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Ticket submission
        add_action('wp_ajax_support_submit_ticket', array($this, 'handle_submit_ticket'));
        add_action('wp_ajax_nopriv_support_submit_ticket', array($this, 'handle_no_privilege'));
        
        // Ticket reply
        add_action('wp_ajax_support_submit_reply', array($this, 'handle_submit_reply'));
        add_action('wp_ajax_nopriv_support_submit_reply', array($this, 'handle_no_privilege'));
        
        // Close ticket
        add_action('wp_ajax_support_close_ticket', array($this, 'handle_close_ticket'));
        add_action('wp_ajax_nopriv_support_close_ticket', array($this, 'handle_no_privilege'));
        
        // Agent login
        add_action('wp_ajax_support_agent_login', array($this, 'handle_agent_login'));
        add_action('wp_ajax_nopriv_support_agent_login', array($this, 'handle_no_privilege'));
        
        // Get order products
        add_action('wp_ajax_support_get_order_products', array($this, 'handle_get_order_products'));
        add_action('wp_ajax_nopriv_support_get_order_products', array($this, 'handle_no_privilege'));
        
        // Bulk actions
        add_action('wp_ajax_support_bulk_action', array($this, 'handle_bulk_action'));
        add_action('wp_ajax_nopriv_support_bulk_action', array($this, 'handle_no_privilege'));
        
        // Internal notes
        add_action('wp_ajax_support_save_internal_notes', array($this, 'handle_save_internal_notes'));
        add_action('wp_ajax_nopriv_support_save_internal_notes', array($this, 'handle_no_privilege'));
        
        // Agent assignment
        add_action('wp_ajax_support_assign_agent', array($this, 'handle_assign_agent'));
        add_action('wp_ajax_nopriv_support_assign_agent', array($this, 'handle_no_privilege'));
        
        // Remove agent
        add_action('wp_ajax_support_remove_agent', array($this, 'handle_remove_agent'));
        add_action('wp_ajax_nopriv_support_remove_agent', array($this, 'handle_no_privilege'));
        
        // Remove agents (bulk)
        add_action('wp_ajax_support_remove_agents', array($this, 'handle_remove_agents'));
        add_action('wp_ajax_nopriv_support_remove_agents', array($this, 'handle_no_privilege'));
    }
    
    private function verify_nonce() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'support_ticket_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'support-ticket-system')));
        }
    }
    
    public function handle_no_privilege() {
        wp_send_json_error(array('message' => __('You must be logged in to perform this action.', 'support-ticket-system')));
    }
    
    public function handle_submit_ticket() {
        $this->verify_nonce();
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to submit a ticket.', 'support-ticket-system')));
        }
        
        // Validate required fields
        $required_fields = array('order_id', 'product_name', 'issue_category', 'description', 'customer_name', 'customer_email');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => sprintf(__('Field %s is required.', 'support-ticket-system'), $field)));
            }
        }
        
        // Process file uploads
        $attachments = array();
        if (!empty($_FILES['attachments'])) {
            $upload_dir = wp_upload_dir();
            $support_ticket_dir = $upload_dir['basedir'] . '/support-tickets';
            
            // Create directory if it doesn't exist
            if (!file_exists($support_ticket_dir)) {
                wp_mkdir_p($support_ticket_dir);
            }
            
            foreach ($_FILES['attachments']['name'] as $key => $name) {
                if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['attachments']['tmp_name'][$key];
                    $file_extension = pathinfo($name, PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '.' . $file_extension;
                    $destination = $support_ticket_dir . '/' . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        $attachments[] = array(
                            'name' => $name,
                            'url' => $upload_dir['baseurl'] . '/support-tickets/' . $new_filename,
                            'path' => $destination
                        );
                    }
                }
            }
        }
        
        // Create ticket post
        $ticket_data = array(
            'post_title'   => sprintf(__('Ticket for Order #%s', 'support-ticket-system'), $_POST['order_id']),
            'post_content' => sanitize_textarea_field($_POST['description']),
            'post_status'  => 'publish',
            'post_type'    => 'support_ticket',
            'post_author'  => get_current_user_id(),
        );
        
        $ticket_id = wp_insert_post($ticket_data);
        
        if (is_wp_error($ticket_id)) {
            wp_send_json_error(array('message' => __('Failed to create ticket.', 'support-ticket-system')));
        }
        
        // Save ticket metadata
        update_post_meta($ticket_id, 'ticket_id', $ticket_id); // Using post ID as ticket ID
        update_post_meta($ticket_id, 'order_id', intval($_POST['order_id']));
        update_post_meta($ticket_id, 'product_name', sanitize_text_field($_POST['product_name']));
        update_post_meta($ticket_id, 'issue_category', sanitize_text_field($_POST['issue_category']));
        update_post_meta($ticket_id, 'priority', sanitize_text_field($_POST['priority']));
        update_post_meta($ticket_id, 'customer_name', sanitize_text_field($_POST['customer_name']));
        update_post_meta($ticket_id, 'customer_email', sanitize_email($_POST['customer_email']));
        update_post_meta($ticket_id, 'status', 'open');
        update_post_meta($ticket_id, 'last_update', current_time('mysql'));
        
        if (!empty($_POST['license_keys'])) {
            $license_keys = is_array($_POST['license_keys']) ? array_map('sanitize_text_field', $_POST['license_keys']) : array(sanitize_text_field($_POST['license_keys']));
            update_post_meta($ticket_id, 'license_keys', $license_keys);
        }
        
        if (!empty($_POST['license_status'])) {
            update_post_meta($ticket_id, 'license_status', sanitize_text_field($_POST['license_status']));
        }
        
        if (!empty($attachments)) {
            update_post_meta($ticket_id, 'attachments', $attachments);
        }
        
        // Set default terms
        wp_set_object_terms($ticket_id, 'open', 'ticket_status');
        wp_set_object_terms($ticket_id, $_POST['priority'], 'ticket_priority');
        
        // Send email notification (optional)
        // $this->send_ticket_notification($ticket_id);
        
        wp_send_json_success(array(
            'message' => __('Ticket created successfully!', 'support-ticket-system'),
            'redirect_url' => home_url('/my-tickets/')
        ));
    }
    
    public function handle_submit_reply() {
        $this->verify_nonce();
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to reply.', 'support-ticket-system')));
        }
        
        if (empty($_POST['ticket_id']) || empty($_POST['content'])) {
            wp_send_json_error(array('message' => __('Ticket ID and content are required.', 'support-ticket-system')));
        }
        
        $ticket_id = intval($_POST['ticket_id']);
        $ticket = get_post($ticket_id);
        
        if (!$ticket || $ticket->post_type !== 'support_ticket') {
            wp_send_json_error(array('message' => __('Invalid ticket.', 'support-ticket-system')));
        }
        
        // Check permissions
        $current_user = wp_get_current_user();
        if ($ticket->post_author != $current_user->ID && !current_user_can('administrator') && !current_user_can('support_agent')) {
            wp_send_json_error(array('message' => __('You do not have permission to reply to this ticket.', 'support-ticket-system')));
        }
        
        // Process file uploads for reply
        $attachments = array();
        if (!empty($_FILES['attachments'])) {
            $upload_dir = wp_upload_dir();
            $support_ticket_dir = $upload_dir['basedir'] . '/support-tickets';
            
            foreach ($_FILES['attachments']['name'] as $key => $name) {
                if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['attachments']['tmp_name'][$key];
                    $file_extension = pathinfo($name, PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '.' . $file_extension;
                    $destination = $support_ticket_dir . '/' . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        $attachments[] = array(
                            'name' => $name,
                            'url' => $upload_dir['baseurl'] . '/support-tickets/' . $new_filename,
                            'path' => $destination
                        );
                    }
                }
            }
        }
        
        // Create reply post
        $reply_data = array(
            'post_content' => sanitize_textarea_field($_POST['content']),
            'post_status'  => 'publish',
            'post_type'    => 'support_ticket_reply',
            'post_author'  => $current_user->ID,
        );
        
        $reply_id = wp_insert_post($reply_data);
        
        if (is_wp_error($reply_id)) {
            wp_send_json_error(array('message' => __('Failed to submit reply.', 'support-ticket-system')));
        }
        
        // Link reply to ticket
        update_post_meta($reply_id, 'ticket_id', $ticket_id);
        
        if (!empty($attachments)) {
            update_post_meta($reply_id, 'attachments', $attachments);
        }
        
        // Update ticket's last update time
        update_post_meta($ticket_id, 'last_update', current_time('mysql'));
        
        // If agent replied, change status to pending (waiting for customer)
        if (current_user_can('administrator') || current_user_can('support_agent')) {
            wp_set_object_terms($ticket_id, 'pending', 'ticket_status');
            update_post_meta($ticket_id, 'status', 'pending');
        } else {
            // If customer replied, change status to open
            wp_set_object_terms($ticket_id, 'open', 'ticket_status');
            update_post_meta($ticket_id, 'status', 'open');
        }
        
        wp_send_json_success(array(
            'message' => __('Reply submitted successfully!', 'support-ticket-system')
        ));
    }
    
    public function handle_close_ticket() {
        $this->verify_nonce();
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to close a ticket.', 'support-ticket-system')));
        }
        
        if (empty($_POST['ticket_id'])) {
            wp_send_json_error(array('message' => __('Ticket ID is required.', 'support-ticket-system')));
        }
        
        $ticket_id = intval($_POST['ticket_id']);
        $ticket = get_post($ticket_id);
        
        if (!$ticket || $ticket->post_type !== 'support_ticket') {
            wp_send_json_error(array('message' => __('Invalid ticket.', 'support-ticket-system')));
        }
        
        // Check permissions
        $current_user = wp_get_current_user();
        if ($ticket->post_author != $current_user->ID && !current_user_can('administrator') && !current_user_can('support_agent')) {
            wp_send_json_error(array('message' => __('You do not have permission to close this ticket.', 'support-ticket-system')));
        }
        
        // Close the ticket
        wp_set_object_terms($ticket_id, 'closed', 'ticket_status');
        update_post_meta($ticket_id, 'status', 'closed');
        update_post_meta($ticket_id, 'last_update', current_time('mysql'));
        
        wp_send_json_success(array(
            'message' => __('Ticket closed successfully.', 'support-ticket-system')
        ));
    }
    
    public function handle_agent_login() {
        $this->verify_nonce();
        
        // This is a custom login for agents. We'll use WordPress login system.
        $credentials = array(
            'user_login'    => isset($_POST['log']) ? $_POST['log'] : '',
            'user_password' => isset($_POST['pwd']) ? $_POST['pwd'] : '',
            'remember'      => isset($_POST['rememberme']) ? true : false
        );
        
        $user = wp_signon($credentials, false);
        
        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => $user->get_error_message()));
        }
        
        // Check if user has agent or admin role
        if (!in_array('support_agent', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_logout();
            wp_send_json_error(array('message' => __('You do not have permission to access the agent dashboard.', 'support-ticket-system')));
        }
        
        wp_send_json_success(array(
            'message' => __('Login successful!', 'support-ticket-system'),
            'redirect' => home_url('/agent-dashboard/')
        ));
    }
    
    public function handle_get_order_products() {
        $this->verify_nonce();
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'support-ticket-system')));
        }
        
        if (empty($_POST['order_id'])) {
            wp_send_json_error(array('message' => __('Order ID is required.', 'support-ticket-system')));
        }
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order || $order->get_user_id() != get_current_user_id()) {
            wp_send_json_error(array('message' => __('Order not found.', 'support-ticket-system')));
        }
        
        $products = array();
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $products[] = array(
                    'name' => $product->get_name()
                );
            }
        }
        
        wp_send_json_success(array('products' => $products));
    }
    
    public function handle_bulk_action() {
        $this->verify_nonce();
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'support-ticket-system')));
        }
        
        if (empty($_POST['tickets']) || empty($_POST['bulk_action'])) {
            wp_send_json_error(array('message' => __('No tickets selected.', 'support-ticket-system')));
        }
        
        $ticket_ids = array_map('intval', $_POST['tickets']);
        $action = sanitize_text_field($_POST['bulk_action']);
        
        foreach ($ticket_ids as $ticket_id) {
            $ticket = get_post($ticket_id);
            if (!$ticket || $ticket->post_type !== 'support_ticket') {
                continue;
            }
            
            // Check permissions
            $current_user = wp_get_current_user();
            if ($ticket->post_author != $current_user->ID && !current_user_can('administrator') && !current_user_can('support_agent')) {
                continue;
            }
            
            switch ($action) {
                case 'close':
                    wp_set_object_terms($ticket_id, 'closed', 'ticket_status');
                    update_post_meta($ticket_id, 'status', 'closed');
                    update_post_meta($ticket_id, 'last_update', current_time('mysql'));
                    break;
                    
                case 'assign':
                    if (current_user_can('administrator') || current_user_can('support_agent')) {
                        update_post_meta($ticket_id, 'assigned_agent', $current_user->ID);
                        update_post_meta($ticket_id, 'last_update', current_time('mysql'));
                    }
                    break;
            }
        }
        
        wp_send_json_success(array(
            'message' => __('Bulk action completed.', 'support-ticket-system')
        ));
    }
    
    public function handle_save_internal_notes() {
        $this->verify_nonce();
        
        if (!is_user_logged_in() || (!current_user_can('administrator') && !current_user_can('support_agent'))) {
            wp_send_json_error(array('message' => __('You do not have permission.', 'support-ticket-system')));
        }
        
        if (empty($_POST['ticket_id'])) {
            wp_send_json_error(array('message' => __('Ticket ID is required.', 'support-ticket-system')));
        }
        
        $ticket_id = intval($_POST['ticket_id']);
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        update_post_meta($ticket_id, 'internal_notes', $notes);
        
        wp_send_json_success(array(
            'message' => __('Notes saved.', 'support-ticket-system')
        ));
    }
    
    public function handle_assign_agent() {
        $this->verify_nonce();
        
        if (!is_user_logged_in() || (!current_user_can('administrator') && !current_user_can('support_agent'))) {
            wp_send_json_error(array('message' => __('You do not have permission.', 'support-ticket-system')));
        }
        
        if (empty($_POST['ticket_id']) || empty($_POST['agent_id'])) {
            wp_send_json_error(array('message' => __('Ticket ID and Agent ID are required.', 'support-ticket-system')));
        }
        
        $ticket_id = intval($_POST['ticket_id']);
        $agent_id = intval($_POST['agent_id']);
        
        // Check if agent exists and has the correct role
        $agent = get_userdata($agent_id);
        if (!$agent || (!in_array('support_agent', $agent->roles) && !in_array('administrator', $agent->roles))) {
            wp_send_json_error(array('message' => __('Invalid agent.', 'support-ticket-system')));
        }
        
        update_post_meta($ticket_id, 'assigned_agent', $agent_id);
        update_post_meta($ticket_id, 'last_update', current_time('mysql'));
        
        // Add to agent history
        $history = get_post_meta($ticket_id, 'agent_history', true) ?: array();
        $history[] = array(
            'date' => current_time('mysql'),
            'user' => get_userdata(get_current_user_id()),
            'description' => sprintf(__('Ticket assigned to %s', 'support-ticket-system'), $agent->display_name)
        );
        update_post_meta($ticket_id, 'agent_history', $history);
        
        wp_send_json_success(array(
            'message' => __('Agent assigned.', 'support-ticket-system')
        ));
    }
    
    public function handle_remove_agent() {
        $this->verify_nonce();
        
        if (!is_user_logged_in() || (!current_user_can('administrator') && !current_user_can('support_agent'))) {
            wp_send_json_error(array('message' => __('You do not have permission.', 'support-ticket-system')));
        }
        
        if (empty($_POST['ticket_id'])) {
            wp_send_json_error(array('message' => __('Ticket ID is required.', 'support-ticket-system')));
        }
        
        $ticket_id = intval($_POST['ticket_id']);
        delete_post_meta($ticket_id, 'assigned_agent');
        update_post_meta($ticket_id, 'last_update', current_time('mysql'));
        
        // Add to agent history
        $history = get_post_meta($ticket_id, 'agent_history', true) ?: array();
        $history[] = array(
            'date' => current_time('mysql'),
            'user' => get_userdata(get_current_user_id()),
            'description' => __('Agent removed from ticket', 'support-ticket-system')
        );
        update_post_meta($ticket_id, 'agent_history', $history);
        
        wp_send_json_success(array(
            'message' => __('Agent removed.', 'support-ticket-system')
        ));
    }
    
    public function handle_remove_agents() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission.', 'support-ticket-system')));
        }
        
        if (empty($_POST['agent_ids'])) {
            wp_send_json_error(array('message' => __('No agents selected.', 'support-ticket-system')));
        }
        
        $agent_ids = array_map('intval', $_POST['agent_ids']);
        $action_type = sanitize_text_field($_POST['action_type']);
        
        foreach ($agent_ids as $agent_id) {
            $user = get_userdata($agent_id);
            if (!$user) {
                continue;
            }
            
            if ($action_type === 'remove_role') {
                // Remove support_agent role if user has it
                if (in_array('support_agent', $user->roles)) {
                    $user->remove_role('support_agent');
                }
                // Remove the custom meta field
                delete_user_meta($agent_id, 'is_explicit_support_agent');
            }
        }
        
        wp_send_json_success(array(
            'message' => __('Agents removed successfully.', 'support-ticket-system')
        ));
    }
}
