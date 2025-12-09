<?php
/**
 * Agent Dashboard Shortcode
 * 
 * @package SupportTicketSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class STS_Agent_Dashboard_Shortcode {
    
    public function __construct() {
        add_shortcode( 'agent_dashboard', array( $this, 'render_agent_dashboard' ) );
    }
    
    public function render_agent_dashboard( $atts, $content = null ) {
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Please log in to view the agent dashboard.', 'support-ticket-system' ) . '</p>';
        }
        
        // Check if user has agent capabilities
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'sts_agent' ) ) {
            return '<p>' . esc_html__( 'You do not have permission to access the agent dashboard.', 'support-ticket-system' ) . '</p>';
        }
        
        // Start output buffering
        ob_start();
        
        // Include the template
        include STS_TEMPLATE_PATH . 'agent-dashboard.php';
        
        return ob_get_clean();
    }
    
    public static function get_agent_tickets( $status = 'all', $limit = -1 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sts_tickets';
        
        $current_user_id = get_current_user_id();
        
        $where_clause = "WHERE assigned_to = %d";
        $params = array( $current_user_id );
        
        if ( $status !== 'all' ) {
            $where_clause .= " AND status = %s";
            $params[] = $status;
        }
        
        $query = "SELECT * FROM {$table_name} {$where_clause} ORDER BY created_at DESC";
        
        if ( $limit > 0 ) {
            $query .= " LIMIT %d";
            $params[] = $limit;
        }
        
        if ( ! empty( $params ) ) {
            $query = $wpdb->prepare( $query, $params );
        }
        
        return $wpdb->get_results( $query );
    }
    
    public static function get_ticket_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sts_tickets';
        $current_user_id = get_current_user_id();
        
        $stats = array(
            'open' => 0,
            'in_progress' => 0,
            'resolved' => 0,
            'closed' => 0,
            'total' => 0
        );
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT status, COUNT(*) as count 
                 FROM {$table_name} 
                 WHERE assigned_to = %d 
                 GROUP BY status",
                $current_user_id
            )
        );
        
        foreach ( $results as $row ) {
            $stats[$row->status] = (int) $row->count;
            $stats['total'] += (int) $row->count;
        }
        
        return $stats;
    }
}

// Initialize the shortcode
new STS_Agent_Dashboard_Shortcode();
