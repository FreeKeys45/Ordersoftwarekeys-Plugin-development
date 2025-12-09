<?php
// Prevent direct access
if (!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Cleanup options
delete_option('support_ticket_pages_created_v3');

// Remove custom roles and capabilities
$roles_to_modify = array('administrator', 'editor', 'support_agent');
foreach ($roles_to_modify as $role_name) {
    $role = get_role($role_name);
    if ($role) {
        $role->remove_cap('read_support_ticket');
        $role->remove_cap('read_support_tickets');
        $role->remove_cap('edit_support_ticket');
        $role->remove_cap('edit_support_tickets');
        $role->remove_cap('edit_others_support_tickets');
        $role->remove_cap('edit_published_support_tickets');
        
        if ($role_name === 'administrator') {
            $role->remove_cap('delete_support_tickets');
            $role->remove_cap('delete_support_ticket');
            $role->remove_cap('delete_others_support_tickets');
            $role->remove_cap('delete_published_support_tickets');
        }
    }
}

// Remove support_agent role
remove_role('support_agent');

// Delete uploaded files
$upload_dir = wp_upload_dir();
$support_ticket_dir = $upload_dir['basedir'] . '/support-tickets';
if (file_exists($support_ticket_dir)) {
    // We are being cautious: only delete files with specific extensions and then the directory
    $files = glob($support_ticket_dir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $allowed_extensions = array('jpg', 'jpeg', 'png', 'pdf', 'txt', 'mp4', 'mov', 'avi', 'webm');
            if (in_array($ext, $allowed_extensions)) {
                unlink($file);
            }
        }
    }
    // Try to remove the directory if it's empty
    @rmdir($support_ticket_dir);
}
