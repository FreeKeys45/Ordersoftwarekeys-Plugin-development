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
        // Enqueue on our admin pages
        if (strpos($hook, 'support_ticket') !== false) {
            wp_enqueue_style('support-ticket-admin-css', STS_PLUGIN_URL . 'assets/css/admin.css', array(), STS_PLUGIN_VERSION);
            wp_enqueue_script('support-ticket-admin-js', STS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), STS_PLUGIN_VERSION, true);
            
            wp_localize_script('support-ticket-admin-js', 'support_ticket_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('support_ticket_nonce')
            ));
        }
    }
}
