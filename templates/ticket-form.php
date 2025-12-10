<?php
/**
 * Ticket Form Template
 * 
 * @package SupportTicketSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Get current user info
$current_user = wp_get_current_user();
$user_name = $current_user->display_name;
$user_email = $current_user->user_email;
?>

<div class="sts-ticket-form-wrapper">
    <h2><?php esc_html_e( 'Create New Support Ticket', 'support-ticket-system' ); ?></h2>
    
    <form id="sts-ticket-form" method="post" action="">
        <?php wp_nonce_field( 'sts_submit_ticket', 'sts_ticket_nonce' ); ?>
        
        <div class="sts-form-group">
            <label for="sts_subject">
                <?php esc_html_e( 'Subject', 'support-ticket-system' ); ?> *
            </label>
            <input type="text" id="sts_subject" name="subject" required 
                   placeholder="<?php esc_attr_e( 'Brief description of your issue', 'support-ticket-system' ); ?>">
        </div>
        
        <div class="sts-form-group">
            <label for="sts_description">
                <?php esc_html_e( 'Description', 'support-ticket-system' ); ?> *
            </label>
            <textarea id="sts_description" name="description" required rows="10"
                      placeholder="<?php esc_attr_e( 'Please provide detailed information about your issue...', 'support-ticket-system' ); ?>"></textarea>
        </div>
        
        <div class="sts-form-row">
            <div class="sts-form-group sts-form-col">
                <label for="sts_priority">
                    <?php esc_html_e( 'Priority', 'support-ticket-system' ); ?>
                </label>
                <select id="sts_priority" name="priority">
                    <option value="low"><?php esc_html_e( 'Low', 'support-ticket-system' ); ?></option>
                    <option value="medium" selected><?php esc_html_e( 'Medium', 'support-ticket-system' ); ?></option>
                    <option value="high"><?php esc_html_e( 'High', 'support-ticket-system' ); ?></option>
                    <option value="critical"><?php esc_html_e( 'Critical', 'support-ticket-system' ); ?></option>
                </select>
            </div>
            
            <div class="sts-form-group sts-form-col">
                <label for="sts_department">
                    <?php esc_html_e( 'Department', 'support-ticket-system' ); ?>
                </label>
                <select id="sts_department" name="department">
                    <option value="general"><?php esc_html_e( 'General Support', 'support-ticket-system' ); ?></option>
                    <option value="technical"><?php esc_html_e( 'Technical Support', 'support-ticket-system' ); ?></option>
                    <option value="billing"><?php esc_html_e( 'Billing', 'support-ticket-system' ); ?></option>
                    <option value="sales"><?php esc_html_e( 'Sales', 'support-ticket-system' ); ?></option>
                </select>
            </div>
        </div>
        
        <?php if ( ! is_user_logged_in() ) : ?>
        <div class="sts-form-row">
            <div class="sts-form-group sts-form-col">
                <label for="sts_name">
                    <?php esc_html_e( 'Your Name', 'support-ticket-system' ); ?> *
                </label>
                <input type="text" id="sts_name" name="name" required 
                       placeholder="<?php esc_attr_e( 'Enter your full name', 'support-ticket-system' ); ?>">
            </div>
            
            <div class="sts-form-group sts-form-col">
                <label for="sts_email">
                    <?php esc_html_e( 'Email Address', 'support-ticket-system' ); ?> *
                </label>
                <input type="email" id="sts_email" name="email" required 
                       placeholder="<?php esc_attr_e( 'Enter your email address', 'support-ticket-system' ); ?>">
            </div>
        </div>
        <?php else : ?>
        <input type="hidden" name="name" value="<?php echo esc_attr( $user_name ); ?>">
        <input type="hidden" name="email" value="<?php echo esc_attr( $user_email ); ?>">
        <?php endif; ?>
        
        <div class="sts-form-group">
            <label for="sts_attachments">
                <?php esc_html_e( 'Attachments', 'support-ticket-system' ); ?>
                <span class="sts-help-text">
                    <?php esc_html_e( 'Maximum file size: 5MB. Allowed types: jpg, png, pdf, doc, docx', 'support-ticket-system' ); ?>
                </span>
            </label>
            <input type="file" id="sts_attachments" name="attachments[]" multiple 
                   accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
            <div id="sts-attachment-preview" class="sts-attachment-preview"></div>
        </div>
        
        <div class="sts-form-group sts-form-terms">
            <label>
                <input type="checkbox" name="terms" required>
                <?php esc_html_e( 'I agree to the terms of service and understand that support will respond within 24-48 hours.', 'support-ticket-system' ); ?>
            </label>
        </div>
        
        <div class="sts-form-actions">
            <button type="submit" class="sts-submit-btn">
                <?php esc_html_e( 'Submit Ticket', 'support-ticket-system' ); ?>
            </button>
            <button type="button" class="sts-cancel-btn" onclick="window.history.back();">
                <?php esc_html_e( 'Cancel', 'support-ticket-system' ); ?>
            </button>
        </div>
        
        <div class="sts-form-notice">
            <p><small><?php esc_html_e( 'Fields marked with * are required.', 'support-ticket-system' ); ?></small></p>
        </div>
    </form>
</div>

<div class="sts-help-section">
    <h3><?php esc_html_e( 'How to get the best support:', 'support-ticket-system' ); ?></h3>
    <ul>
        <li><?php esc_html_e( 'Be specific and detailed about your issue', 'support-ticket-system' ); ?></li>
        <li><?php esc_html_e( 'Include error messages or screenshots if applicable', 'support-ticket-system' ); ?></li>
        <li><?php esc_html_e( 'Provide steps to reproduce the issue', 'support-ticket-system' ); ?></li>
        <li><?php esc_html_e( 'Mention your software version and environment', 'support-ticket-system' ); ?></li>
    </ul>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle file upload preview
    $('#sts_attachments').on('change', function() {
        var files = $(this)[0].files;
        var preview = $('#sts-attachment-preview');
        preview.empty();
        
        if (files.length > 0) {
            preview.append('<p><?php esc_html_e( "Selected files:", "support-ticket-system" ); ?></p><ul></ul>');
            var list = preview.find('ul');
            
            for (var i = 0; i < files.length; i++) {
                var file = files[i];
                var listItem = $('<li></li>').text(file.name + ' (' + formatBytes(file.size) + ')');
                list.append(listItem);
            }
        }
    });
    
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
});
</script>
