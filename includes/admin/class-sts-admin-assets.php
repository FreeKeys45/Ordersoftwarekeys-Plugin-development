<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class STS_Admin_Assets {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function enqueue_scripts($hook) {
        // Load only on our plugin pages
        if (strpos($hook, 'support_ticket') !== false || 
            strpos($hook, 'edit.php?post_type=support_ticket') !== false ||
            $hook === 'support_ticket_page_support-agents' ||
            $hook === 'support_ticket_page_add-support-agent' ||
            $hook === 'support_ticket_page_assign-support-agent' ||
            $hook === 'support_ticket_page_remove-support-agents') {
           
            wp_enqueue_style('support-ticket-admin-css', 
                STS_PLUGIN_URL . 'assets/css/admin.css', 
                array(), 
                STS_PLUGIN_VERSION
            );
            
            wp_enqueue_script('support-ticket-admin-js', 
                STS_PLUGIN_URL . 'assets/js/admin.js', 
                array('jquery'), 
                STS_PLUGIN_VERSION, 
                true
            );
            
            wp_localize_script('support-ticket-admin-js', 'support_ticket_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('support_ticket_nonce'),
                'strings'  => array(
                    'confirm_remove' => __('Are you sure you want to remove this agent?', 'support-ticket-system'),
                    'processing'     => __('Processing...', 'support-ticket-system'),
                    'error'          => __('An error occurred.', 'support-ticket-system'),
                    'success'        => __('Success!', 'support-ticket-system')
                )
            ));
        }
    }
}
