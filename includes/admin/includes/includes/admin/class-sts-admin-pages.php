<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class STS_Admin_Pages {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_support_agents_menu'));
        add_action('admin_init', array($this, 'handle_agent_management'));
        add_action('admin_post_remove_support_agent', array($this, 'handle_remove_support_agent'));
    }
    
    public function add_support_agents_menu() {
        // Main Support Agents menu
        add_submenu_page(
            'edit.php?post_type=support_ticket',
            __('Support Agents', 'support-ticket-system'),
            __('Support Agents', 'support-ticket-system'),
            'manage_options',
            'support-agents',
            array($this, 'support_agents_page')
        );
       
        // Add Support Agent submenu
        add_submenu_page(
            'edit.php?post_type=support_ticket',
            __('Add Support Agent', 'support-ticket-system'),
            __('Add Support Agent', 'support-ticket-system'),
            'manage_options',
            'add-support-agent',
            array($this, 'add_support_agent_page')
        );
       
        // Assign Support Agent submenu
        add_submenu_page(
            'edit.php?post_type=support_ticket',
            __('Assign Support Agent', 'support-ticket-system'),
            __('Assign Support Agent', 'support-ticket-system'),
            'manage_options',
            'assign-support-agent',
            array($this, 'assign_support_agent_page')
        );
       
        // Remove Support Agents submenu
        add_submenu_page(
            'edit.php?post_type=support_ticket',
            __('Remove Support Agents', 'support-ticket-system'),
            __('Remove Support Agents', 'support-ticket-system'),
            'manage_options',
            'remove-support-agents',
            array($this, 'remove_support_agents_page')
        );
    }

    public function support_agents_page() {
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'support-ticket-system'));
        }

        // Only get users who have been explicitly added as support agents
        $users = get_users(array(
            'meta_key' => 'is_explicit_support_agent',
            'meta_value' => true,
            'fields' => array('ID', 'display_name', 'user_email')
        ));

        $agents = array();
        foreach ($users as $user) {
            // Get the user object with roles
            $user_with_roles = get_userdata($user->ID);
           
            // Safely check if roles exist and are arrays
            if ($user_with_roles && isset($user_with_roles->roles) && is_array($user_with_roles->roles)) {
                $user_roles = $user_with_roles->roles;
                // Add roles to the user object for display
                $user->roles = $user_roles;
                $agents[] = $user;
            }
        }
        ?>
        <div class="wrap support-agents-wrap">
            <h1><?php _e('Support Agents Management', 'support-ticket-system'); ?></h1>
           
            <?php
            // Display admin notices
            if (isset($_GET['message'])) {
                $message_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'success';
                $message = sanitize_text_field($_GET['message']);
                echo '<div class="notice notice-' . esc_attr($message_type) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
            }
            ?>
           
            <div class="card">
                <h2><?php _e('Available Support Agents', 'support-ticket-system'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'support-ticket-system'); ?></th>
                            <th><?php _e('Email', 'support-ticket-system'); ?></th>
                            <th><?php _e('Role', 'support-ticket-system'); ?></th>
                            <th><?php _e('Assigned Tickets', 'support-ticket-system'); ?></th>
                            <th><?php _e('Actions', 'support-ticket-system'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($agents)): ?>
                            <tr>
                                <td colspan="5"><?php _e('No support agents found.', 'support-ticket-system'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($agents as $agent):
                                $assigned_tickets = $this->get_agent_ticket_count($agent->ID);
                                $role_names = array();
                                if (isset($agent->roles) && is_array($agent->roles)) {
                                    $role_names = array_map(function($role) {
                                        return ucfirst($role);
                                    }, $agent->roles);
                                } else {
                                    $role_names = array(__('No role', 'support-ticket-system'));
                                }
                            ?>
                                <tr>
                                    <td><?php echo esc_html($agent->display_name); ?></td>
                                    <td><?php echo esc_html($agent->user_email); ?></td>
                                    <td><?php echo implode(', ', $role_names); ?></td>
                                    <td><?php echo $assigned_tickets; ?></td>
                                    <td class="support-agent-actions">
                                        <a href="<?php echo admin_url('edit.php?post_type=support_ticket&assigned_agent=' . $agent->ID); ?>" class="button">
                                            <?php _e('View Tickets', 'support-ticket-system'); ?>
                                        </a>
                                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $agent->ID); ?>" class="button">
                                            <?php _e('Edit User', 'support-ticket-system'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function add_support_agent_page() {
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'support-ticket-system'));
        }

        // Handle agent creation
        if (isset($_POST['create_agent']) && check_admin_referer('create_support_agent')) {
            $this->create_support_agent();
        }
        ?>
        <div class="wrap support-agents-wrap">
            <h1><?php _e('Add Support Agent', 'support-ticket-system'); ?></h1>
           
            <?php
            // Display admin notices
            if (isset($_GET['message'])) {
                $message_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'success';
                $message = sanitize_text_field($_GET['message']);
                echo '<div class="notice notice-' . esc_attr($message_type) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
            }
            ?>
           
            <div class="card">
                <h2><?php _e('Create New Support Agent', 'support-ticket-system'); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field('create_support_agent'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="agent_username"><?php _e('Username', 'support-ticket-system'); ?> *</label></th>
                            <td><input type="text" name="agent_username" id="agent_username" required class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="agent_email"><?php _e('Email', 'support-ticket-system'); ?> *</label></th>
                            <td><input type="email" name="agent_email" id="agent_email" required class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="agent_first_name"><?php _e('First Name', 'support-ticket-system'); ?></label></th>
                            <td><input type="text" name="agent_first_name" id="agent_first_name" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="agent_last_name"><?php _e('Last Name', 'support-ticket-system'); ?></label></th>
                            <td><input type="text" name="agent_last_name" id="agent_last_name" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="agent_password"><?php _e('Password', 'support-ticket-system'); ?> *</label></th>
                            <td>
                                <input type="password" name="agent_password" id="agent_password" required class="regular-text">
                                <p class="description"><?php _e('Set a strong password for the new agent.', 'support-ticket-system'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Create Support Agent', 'support-ticket-system'), 'primary', 'create_agent'); ?>
                </form>
            </div>
        </div>
        <?php
    }

    public function assign_support_agent_page() {
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'support-ticket-system'));
        }

        // Handle agent assignment
        if (isset($_POST['assign_agent']) && check_admin_referer('assign_support_agent')) {
            $this->assign_support_agent();
        }
        ?>
        <div class="wrap support-agents-wrap">
            <h1><?php _e('Assign Support Agent', 'support-ticket-system'); ?></h1>
           
            <?php
            // Display admin notices
            if (isset($_GET['message'])) {
                $message_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'success';
                $message = sanitize_text_field($_GET['message']);
                echo '<div class="notice notice-' . esc_attr($message_type) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
            }
            ?>
           
            <div class="card">
                <h2><?php _e('Assign Existing User as Support Agent', 'support-ticket-system'); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field('assign_support_agent'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="agent_user"><?php _e('User', 'support-ticket-system'); ?> *</label></th>
                            <td>
                                <select name="agent_user" id="agent_user" required class="regular-text">
                                    <option value=""><?php _e('Select a user', 'support-ticket-system'); ?></option>
                                    <?php
                                    $users = get_users(array(
                                        'role__in' => array('administrator', 'editor'),
                                        'fields' => array('ID', 'display_name', 'user_email')
                                    ));
                                   
                                    foreach ($users as $user) {
                                        // Skip if already a support agent
                                        if (get_user_meta($user->ID, 'is_explicit_support_agent', true)) {
                                            continue;
                                        }
                                        echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Assign as Support Agent', 'support-ticket-system'), 'primary', 'assign_agent'); ?>
                </form>
            </div>
        </div>
        <?php
    }

    public function remove_support_agents_page() {
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'support-ticket-system'));
        }

        // Only get users who have been explicitly added as support agents
        $users = get_users(array(
            'meta_key' => 'is_explicit_support_agent',
            'meta_value' => true,
            'fields' => array('ID', 'display_name', 'user_email')
        ));

        $agents = array();
        foreach ($users as $user) {
            // Get the user object with roles
            $user_with_roles = get_userdata($user->ID);
           
            // Safely check if roles exist and are arrays
            if ($user_with_roles && isset($user_with_roles->roles) && is_array($user_with_roles->roles)) {
                $user_roles = $user_with_roles->roles;
                if (in_array('support_agent', $user_roles)) {
                    // Add roles to the user object for display
                    $user->roles = $user_roles;
                    $agents[] = $user;
                }
            }
        }
        ?>
        <div class="wrap support-agents-wrap">
            <h1><?php _e('Remove Support Agents', 'support-ticket-system'); ?></h1>
           
            <div class="support-agent-notice support-agent-notice-success">
                <p><?php _e('Note: Removing agents here will only remove them from the support ticket system. Their WordPress user accounts will remain active.', 'support-ticket-system'); ?></p>
            </div>
           
            <?php
            // Display admin notices
            if (isset($_GET['message'])) {
                $message_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'success';
                $message = sanitize_text_field($_GET['message']);
                echo '<div class="notice notice-' . esc_attr($message_type) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
            }
            ?>
           
            <div class="card">
                <h2><?php _e('Support Agents to Remove', 'support-ticket-system'); ?></h2>
               
                <?php if (empty($agents)): ?>
                    <p><?php _e('No support agents found.', 'support-ticket-system'); ?></p>
                <?php else: ?>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <input type="hidden" name="action" value="remove_support_agent">
                        <?php wp_nonce_field('remove_support_agent_action'); ?>
                        
                        <div class="support-agent-bulk-actions">
                            <input type="checkbox" id="support-select-all-agents">
                            <label for="support-select-all-agents"><?php _e('Select All', 'support-ticket-system'); ?></label>
                           
                            <select id="support-bulk-action-remove" name="bulk_action">
                                <option value="remove_role"><?php _e('Remove support agent role', 'support-ticket-system'); ?></option>
                            </select>
                           
                            <button type="submit" id="support-apply-bulk-remove" class="button">
                                <?php _e('Apply', 'support-ticket-system'); ?>
                            </button>
                        </div>
                       
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th class="check-column"><input type="checkbox" id="cb-select-all-1"></th>
                                    <th><?php _e('Name', 'support-ticket-system'); ?></th>
                                    <th><?php _e('Email', 'support-ticket-system'); ?></th>
                                    <th><?php _e('Assigned Tickets', 'support-ticket-system'); ?></th>
                                    <th><?php _e('Actions', 'support-ticket-system'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agents as $agent):
                                    $assigned_tickets = $this->get_agent_ticket_count($agent->ID);
                                ?>
                                    <tr>
                                        <th class="check-column">
                                            <input type="checkbox" name="agent_ids[]" value="<?php echo $agent->ID; ?>" class="support-agent-checkbox">
                                        </th>
                                        <td><?php echo esc_html($agent->display_name); ?></td>
                                        <td><?php echo esc_html($agent->user_email); ?></td>
                                        <td><?php echo $assigned_tickets; ?></td>
                                        <td class="support-agent-actions">
                                            <a href="<?php echo admin_url('edit.php?post_type=support_ticket&assigned_agent=' . $agent->ID); ?>" class="button">
                                                <?php _e('View Tickets', 'support-ticket-system'); ?>
                                            </a>
                                            <a href="<?php echo admin_url('user-edit.php?user_id=' . $agent->ID); ?>" class="button">
                                                <?php _e('Edit User', 'support-ticket-system'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    // Handler for removing support agents
    public function handle_remove_support_agent() {
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'support-ticket-system'));
        }

        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'remove_support_agent_action')) {
            wp_die(__('Security check failed.', 'support-ticket-system'));
        }

        // Get selected agent IDs
        $agent_ids = isset($_POST['agent_ids']) ? array_map('intval', $_POST['agent_ids']) : array();
        $bulk_action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : 'remove_role';

        if (empty($agent_ids)) {
            $redirect_url = add_query_arg(
                array(
                    'page' => 'remove-support-agents',
                    'message' => urlencode(__('No agents selected.', 'support-ticket-system')),
                    'type' => 'error'
                ),
                admin_url('edit.php?post_type=support_ticket')
            );
            wp_redirect($redirect_url);
            exit;
        }

        $removed_count = 0;

        foreach ($agent_ids as $agent_id) {
            $user = get_userdata($agent_id);
            if (!$user) {
                continue;
            }

            if ($bulk_action === 'remove_role') {
                // Remove support_agent role if user has it
                if (in_array('support_agent', $user->roles)) {
                    $user->remove_role('support_agent');
                }
                // Remove the custom meta field
                delete_user_meta($agent_id, 'is_explicit_support_agent');
                $removed_count++;
            }
        }

        // Redirect with success message
        $redirect_url = add_query_arg(
            array(
                'page' => 'remove-support-agents',
                'message' => urlencode(sprintf(__('%d agents have been processed successfully.', 'support-ticket-system'), $removed_count)),
                'type' => 'success'
            ),
            admin_url('edit.php?post_type=support_ticket')
        );
        wp_redirect($redirect_url);
        exit;
    }

    private function create_support_agent() {
        if (!current_user_can('create_users')) {
            wp_die(__('You do not have permission to create users.', 'support-ticket-system'));
        }

        // Sanitize and validate input
        $username = isset($_POST['agent_username']) ? sanitize_user($_POST['agent_username']) : '';
        $email = isset($_POST['agent_email']) ? sanitize_email($_POST['agent_email']) : '';
        $password = isset($_POST['agent_password']) ? $_POST['agent_password'] : '';
        $first_name = isset($_POST['agent_first_name']) ? sanitize_text_field($_POST['agent_first_name']) : '';
        $last_name = isset($_POST['agent_last_name']) ? sanitize_text_field($_POST['agent_last_name']) : '';

        // Validate required fields
        if (empty($username) || empty($email) || empty($password)) {
            wp_redirect(admin_url('edit.php?post_type=support_ticket&page=add-support-agent&message=' . urlencode(__('All required fields must be filled.', 'support-ticket-system')) . '&type=error'));
            exit;
        }

        // Check if username already exists
        if (username_exists($username)) {
            wp_redirect(admin_url('edit.php?post_type=support_ticket&page=add-support-agent&message=' . urlencode(__('Username already exists.', 'support-ticket-system')) . '&type=error'));
            exit;
        }

        // Check if email already exists
        if (email_exists($email)) {
            wp_redirect(admin_url('edit.php?post_type=support_ticket&page=add-support-agent&message=' . urlencode(__('Email already exists.', 'support-ticket-system')) . '&type=error'));
            exit;
        }

        // Create the user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_redirect(admin_url('edit.php?post_type=support_ticket&page=add-support-agent&message=' . urlencode($user_id->get_error_message()) . '&type=error'));
            exit;
        }

        // Update user details
        $update_data = array(
            'ID' => $user_id,
            'role' => 'support_agent'
        );

        if (!empty($first_name)) {
            $update_data['first_name'] = $first_name;
        }

        if (!empty($last_name)) {
            $update_data['last_name'] = $last_name;
        }

        if (!empty($first_name) && !empty($last_name)) {
            $update_data['display_name'] = $first_name . ' ' . $last_name;
        }

        wp_update_user($update_data);
       
        // Add custom meta to track that this user was explicitly added as a support agent
        update_user_meta($user_id, 'is_explicit_support_agent', true);

        wp_redirect(admin_url('edit.php?post_type=support_ticket&page=add-support-agent&message=' . urlencode(__('Support agent created successfully!', 'support-ticket-system')) . '&type=success'));
        exit;
    }

    private function assign_support_agent() {
        if (!current_user_can('promote_users')) {
            wp_die(__('You do not have permission to assign users.', 'support-ticket-system'));
        }

        // Sanitize and validate input
        $user_id = isset($_POST['agent_user']) ? intval($_POST['agent_user']) : 0;

        // Validate required fields
        if (empty($user_id)) {
            wp_redirect(admin_url('edit.php?post_type=support_ticket&page=assign-support-agent&message=' . urlencode(__('Please select a user.', 'support-ticket-system')) . '&type=error'));
            exit;
        }

        // Get the user
        $user = get_userdata($user_id);
        if (!$user) {
            wp_redirect(admin_url('edit.php?post_type=support_ticket&page=assign-support-agent&message=' . urlencode(__('User not found.', 'support-ticket-system')) . '&type=error'));
            exit;
        }

        // Check if already a support agent
        if (get_user_meta($user_id, 'is_explicit_support_agent', true)) {
            wp_redirect(admin_url('edit.php?post_type=support_ticket&page=assign-support-agent&message=' . urlencode(__('User is already a support agent.', 'support-ticket-system')) . '&type=error'));
            exit;
        }

        // Add support_agent role
        $user->add_role('support_agent');
       
        // Add custom meta to track that this user was explicitly added as a support agent
        update_user_meta($user_id, 'is_explicit_support_agent', true);

        wp_redirect(admin_url('edit.php?post_type=support_ticket&page=assign-support-agent&message=' . urlencode(__('User assigned as support agent successfully!', 'support-ticket-system')) . '&type=success'));
        exit;
    }

    private function get_agent_ticket_count($agent_id) {
        $tickets = get_posts(array(
            'post_type' => 'support_ticket',
            'meta_query' => array(
                array(
                    'key' => 'assigned_agent',
                    'value' => $agent_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
       
        return count($tickets);
    }

    public function handle_agent_management() {
        if (!current_user_can('manage_options')) {
            return;
        }
       
        // Handle agent assignment from admin
        if (isset($_POST['assign_agent_nonce']) && wp_verify_nonce($_POST['assign_agent_nonce'], 'assign_agent')) {
            $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
            $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
           
            if ($ticket_id && $agent_id) {
                update_post_meta($ticket_id, 'assigned_agent', $agent_id);
                update_post_meta($ticket_id, 'last_update', current_time('mysql'));
               
                wp_redirect(admin_url('edit.php?post_type=support_ticket&page=support-agents&message=' . urlencode(__('Agent assigned successfully!', 'support-ticket-system')) . '&type=success'));
                exit;
            }
        }
    }
}
