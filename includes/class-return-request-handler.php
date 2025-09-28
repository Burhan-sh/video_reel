<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Return_Request_Handler {
    
    public function __construct() {
        add_filter('woocommerce_my_account_my_orders_actions', array($this, 'add_return_request_button'), 10, 2);
        add_action('wp_ajax_submit_return_request', array($this, 'handle_return_request_submission'));
        add_action('wp_ajax_nopriv_submit_return_request', array($this, 'handle_return_request_submission'));
        add_action('wp_footer', array($this, 'add_return_request_modal'));
    }
    
    /**
     * Add return request button to order actions
     */
    public function add_return_request_button($actions, $order) {
        // Check if return request is allowed for this order
        if ($this->is_return_request_allowed($order)) {
            $order_number = $order->get_order_number();
            
            $actions['return_request'] = array(
                'url' => '#',
                'name' => __('Return Request', 'return-button'),
                'class' => 'button return-request-btn',
                'data-order-id' => $order->get_id(),
                'data-order-number' => $order_number
            );
        }
        
        return $actions;
    }
    
    /**
     * Check if return request is allowed for the order
     */
    private function is_return_request_allowed($order) {
        // Check if order is completed
        if ($order->get_status() !== 'completed') {
            return false;
        }
        
        // Check if there's already a pending return request
        if (Return_Request_Database::has_pending_return_request($order->get_id())) {
            return false;
        }
        
        // Get order completion date
        $completed_date = $order->get_date_completed();
        if (!$completed_date) {
            return false;
        }
        
        // Calculate time periods
        $return_period_days = get_option('return_button_return_period_days', 10);
        $grace_period_days = get_option('return_button_grace_period_days', 10);
        
        $current_time = current_time('timestamp');
        $completed_timestamp = $completed_date->getTimestamp();
        
        // Calculate the allowed return period (10 days after completion + 10 days grace period)
        $return_start_time = $completed_timestamp + ($return_period_days * DAY_IN_SECONDS);
        $return_end_time = $return_start_time + ($grace_period_days * DAY_IN_SECONDS);
        
        // Check if current time is within the return request window
        return ($current_time >= $return_start_time && $current_time <= $return_end_time);
    }
    
    /**
     * Handle return request form submission
     */
    public function handle_return_request_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'return_request_nonce')) {
            wp_die(__('Security check failed', 'return-button'));
        }
        
        // Sanitize and validate input data
        $order_id = intval($_POST['order_id']);
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $customer_email = sanitize_email($_POST['customer_email']);
        $customer_phone = sanitize_text_field($_POST['customer_phone']);
        $customer_whatsapp = sanitize_text_field($_POST['customer_whatsapp']);
        $return_reason = sanitize_textarea_field($_POST['return_reason']);
        
        // Validate required fields
        if (empty($order_id) || empty($customer_name) || empty($customer_email) || 
            empty($customer_phone) || empty($return_reason)) {
            wp_send_json_error(array('message' => __('Please fill all required fields.', 'return-button')));
        }
        
        // Validate email
        if (!is_email($customer_email)) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'return-button')));
        }
        
        // Get order and verify user permission
        $order = wc_get_order($order_id);
        if (!$order || $order->get_user_id() !== get_current_user_id()) {
            wp_send_json_error(array('message' => __('Invalid order.', 'return-button')));
        }
        
        // Check if return request is still allowed
        if (!$this->is_return_request_allowed($order)) {
            wp_send_json_error(array('message' => __('Return request is not allowed for this order.', 'return-button')));
        }
        
        // Handle file uploads
        $uploaded_files = $this->handle_file_uploads();
        
        // Prepare data for database
        $return_data = array(
            'order_id' => $order_id,
            'user_id' => get_current_user_id(),
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'customer_whatsapp' => $customer_whatsapp,
            'return_reason' => $return_reason,
            'uploaded_files' => $uploaded_files
        );
        
        // Insert into database
        $request_id = Return_Request_Database::insert_return_request($return_data);
        
        if ($request_id) {
            // Send email notification
            Return_Request_Email::send_admin_notification($request_id, $return_data);
            
            wp_send_json_success(array(
                'message' => __('Return request submitted successfully!', 'return-button'),
                'request_id' => $request_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to submit return request. Please try again.', 'return-button')));
        }
    }
    
    /**
     * Handle file uploads
     */
    private function handle_file_uploads() {
        $uploaded_files = array();
        
        if (!empty($_FILES['return_files'])) {
            $files = $_FILES['return_files'];
            
            // Setup upload directory
            $upload_dir = wp_upload_dir();
            $return_upload_dir = $upload_dir['basedir'] . '/return-requests/';
            
            // Create directory if it doesn't exist
            if (!file_exists($return_upload_dir)) {
                wp_mkdir_p($return_upload_dir);
            }
            
            // Handle multiple files
            if (is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $file_info = array(
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'size' => $files['size'][$i]
                        );
                        
                        $uploaded_file = $this->process_single_file($file_info, $return_upload_dir);
                        if ($uploaded_file) {
                            $uploaded_files[] = $uploaded_file;
                        }
                    }
                }
            } else {
                // Handle single file
                if ($files['error'] === UPLOAD_ERR_OK) {
                    $uploaded_file = $this->process_single_file($files, $return_upload_dir);
                    if ($uploaded_file) {
                        $uploaded_files[] = $uploaded_file;
                    }
                }
            }
        }
        
        return $uploaded_files;
    }
    
    /**
     * Process single file upload
     */
    private function process_single_file($file_info, $upload_dir) {
        // Validate file type
        $allowed_image_types = array('image/jpeg', 'image/jpg', 'image/png');
        $allowed_video_types = array('video/mp4');
        $allowed_types = array_merge($allowed_image_types, $allowed_video_types);
        
        if (!in_array($file_info['type'], $allowed_types)) {
            return false;
        }
        
        // Validate file extension
        $file_extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'mp4');
        
        if (!in_array($file_extension, $allowed_extensions)) {
            return false;
        }
        
        // Generate unique filename
        $filename = wp_unique_filename($upload_dir, sanitize_file_name($file_info['name']));
        $file_path = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file_info['tmp_name'], $file_path)) {
            return array(
                'filename' => $filename,
                'original_name' => $file_info['name'],
                'file_path' => $file_path,
                'file_url' => wp_upload_dir()['baseurl'] . '/return-requests/' . $filename,
                'file_type' => $file_info['type'],
                'file_size' => $file_info['size']
            );
        }
        
        return false;
    }
    
    /**
     * Add return request modal to footer
     */
    public function add_return_request_modal() {
        if (is_account_page()) {
            ?>
            <div id="return-request-modal" class="return-modal" style="display: none;">
                <div class="return-modal-content">
                    <div class="return-modal-header">
                        <h3><?php _e('Return Request', 'return-button'); ?></h3>
                        <span class="return-modal-close">&times;</span>
                    </div>
                    <form id="return-request-form" enctype="multipart/form-data">
                        <div class="return-form-row">
                            <label for="customer_name"><?php _e('Full Name', 'return-button'); ?> *</label>
                            <input type="text" id="customer_name" name="customer_name" required>
                        </div>
                        
                        <div class="return-form-row">
                            <label for="customer_email"><?php _e('Email Address', 'return-button'); ?> *</label>
                            <input type="email" id="customer_email" name="customer_email" required>
                        </div>
                        
                        <div class="return-form-row">
                            <label for="customer_phone"><?php _e('Phone Number', 'return-button'); ?> *</label>
                            <input type="tel" id="customer_phone" name="customer_phone" required>
                        </div>
                        
                        <div class="return-form-row">
                            <label for="customer_whatsapp"><?php _e('WhatsApp Number', 'return-button'); ?></label>
                            <input type="tel" id="customer_whatsapp" name="customer_whatsapp" placeholder="<?php _e('Leave empty if same as phone', 'return-button'); ?>">
                        </div>
                        
                        <div class="return-form-row">
                            <label for="return_reason"><?php _e('Return Reason', 'return-button'); ?> *</label>
                            <textarea id="return_reason" name="return_reason" rows="4" required placeholder="<?php _e('Please describe why you want to return this order...', 'return-button'); ?>"></textarea>
                        </div>
                        
                        <div class="return-form-row">
                            <label for="return_files"><?php _e('Upload Files', 'return-button'); ?></label>
                            <input type="file" id="return_files" name="return_files[]" multiple accept=".jpg,.jpeg,.png,.mp4">
                            <small><?php _e('Allowed formats: JPG, JPEG, PNG images and MP4 videos only', 'return-button'); ?></small>
                        </div>
                        
                        <div class="return-form-actions">
                            <button type="button" class="return-btn-cancel"><?php _e('Cancel', 'return-button'); ?></button>
                            <button type="submit" class="return-btn-submit"><?php _e('Submit Request', 'return-button'); ?></button>
                        </div>
                        
                        <input type="hidden" id="order_id" name="order_id">
                        <input type="hidden" name="action" value="submit_return_request">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('return_request_nonce'); ?>">
                    </form>
                </div>
            </div>
            <?php
        }
    }
}