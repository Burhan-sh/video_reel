<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Return_Request_Email {
    
    /**
     * Send admin notification email
     */
    public static function send_admin_notification($request_id, $return_data) {
        // Get admin email
        $admin_email = get_option('admin_email');
        
        // Get order details
        $order = wc_get_order($return_data['order_id']);
        if (!$order) {
            return false;
        }
        
        // Prepare email content
        $subject = sprintf(__('New Return Request #%d for Order #%s', 'return-button'), $request_id, $order->get_order_number());
        
        $message = self::get_admin_email_template($request_id, $return_data, $order);
        
        // Set email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Reply-To: ' . $return_data['customer_email']
        );
        
        // Send email
        return wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Get admin email template
     */
    private static function get_admin_email_template($request_id, $return_data, $order) {
        $order_date = $order->get_date_created()->format('Y-m-d H:i:s');
        $order_total = $order->get_formatted_order_total();
        
        $message = '<html><body>';
        $message .= '<h2>' . sprintf(__('New Return Request #%d', 'return-button'), $request_id) . '</h2>';
        
        $message .= '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
        $message .= '<tr><td style="border: 1px solid #ddd; padding: 10px; font-weight: bold;">Order Number:</td><td style="border: 1px solid #ddd; padding: 10px;">' . $order->get_order_number() . '</td></tr>';
        $message .= '<tr><td style="border: 1px solid #ddd; padding: 10px; font-weight: bold;">Order Date:</td><td style="border: 1px solid #ddd; padding: 10px;">' . $order_date . '</td></tr>';
        $message .= '<tr><td style="border: 1px solid #ddd; padding: 10px; font-weight: bold;">Order Total:</td><td style="border: 1px solid #ddd; padding: 10px;">' . $order_total . '</td></tr>';
        $message .= '</table>';
        
        $message .= '<h3>' . __('Customer Information', 'return-button') . '</h3>';
        $message .= '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
        $message .= '<tr><td style="border: 1px solid #ddd; padding: 10px; font-weight: bold;">Name:</td><td style="border: 1px solid #ddd; padding: 10px;">' . esc_html($return_data['customer_name']) . '</td></tr>';
        $message .= '<tr><td style="border: 1px solid #ddd; padding: 10px; font-weight: bold;">Email:</td><td style="border: 1px solid #ddd; padding: 10px;">' . esc_html($return_data['customer_email']) . '</td></tr>';
        $message .= '<tr><td style="border: 1px solid #ddd; padding: 10px; font-weight: bold;">Phone:</td><td style="border: 1px solid #ddd; padding: 10px;">' . esc_html($return_data['customer_phone']) . '</td></tr>';
        
        if (!empty($return_data['customer_whatsapp'])) {
            $message .= '<tr><td style="border: 1px solid #ddd; padding: 10px; font-weight: bold;">WhatsApp:</td><td style="border: 1px solid #ddd; padding: 10px;">' . esc_html($return_data['customer_whatsapp']) . '</td></tr>';
        }
        $message .= '</table>';
        
        $message .= '<h3>' . __('Return Reason', 'return-button') . '</h3>';
        $message .= '<div style="border: 1px solid #ddd; padding: 15px; background-color: #f9f9f9;">';
        $message .= '<p>' . nl2br(esc_html($return_data['return_reason'])) . '</p>';
        $message .= '</div>';
        
        // Add uploaded files information
        if (!empty($return_data['uploaded_files'])) {
            $message .= '<h3>' . __('Uploaded Files', 'return-button') . '</h3>';
            $message .= '<ul>';
            foreach ($return_data['uploaded_files'] as $file) {
                $message .= '<li><a href="' . esc_url($file['file_url']) . '">' . esc_html($file['original_name']) . '</a> (' . size_format($file['file_size']) . ')</li>';
            }
            $message .= '</ul>';
        }
        
        // Add admin link
        $admin_url = admin_url('admin.php?page=return-requests&action=view&id=' . $request_id);
        $message .= '<p style="margin-top: 30px;"><a href="' . $admin_url . '" style="background-color: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;">View in Admin Dashboard</a></p>';
        
        $message .= '</body></html>';
        
        return $message;
    }
    
    /**
     * Send customer confirmation email
     */
    public static function send_customer_confirmation($request_id, $return_data) {
        $subject = sprintf(__('Return Request #%d Submitted Successfully', 'return-button'), $request_id);
        
        $message = self::get_customer_email_template($request_id, $return_data);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($return_data['customer_email'], $subject, $message, $headers);
    }
    
    /**
     * Get customer email template
     */
    private static function get_customer_email_template($request_id, $return_data) {
        $order = wc_get_order($return_data['order_id']);
        
        $message = '<html><body>';
        $message .= '<h2>' . __('Return Request Submitted Successfully', 'return-button') . '</h2>';
        
        $message .= '<p>' . sprintf(__('Dear %s,', 'return-button'), esc_html($return_data['customer_name'])) . '</p>';
        $message .= '<p>' . sprintf(__('Your return request #%d for order #%s has been submitted successfully.', 'return-button'), $request_id, $order->get_order_number()) . '</p>';
        
        $message .= '<h3>' . __('Request Details', 'return-button') . '</h3>';
        $message .= '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
        $message .= '<tr><td style="border: 1px solid #ddd; padding: 10px; font-weight: bold;">Request ID:</td><td style="border: 1px solid #ddd; padding: 10px;">#' . $request_id . '</td></tr>';
        $message .= '<tr><td style="border: 1px solid #ddd; padding: 10px; font-weight: bold;">Order Number:</td><td style="border: 1px solid #ddd; padding: 10px;">#' . $order->get_order_number() . '</td></tr>';
        $message .= '<tr><td style="border: 1px solid #ddd; padding: 10px; font-weight: bold;">Status:</td><td style="border: 1px solid #ddd; padding: 10px;">Pending Review</td></tr>';
        $message .= '</table>';
        
        $message .= '<p>' . __('We will review your request and get back to you within 2-3 business days.', 'return-button') . '</p>';
        $message .= '<p>' . __('Thank you for your patience.', 'return-button') . '</p>';
        
        $message .= '<p>' . __('Best regards,', 'return-button') . '<br>';
        $message .= get_bloginfo('name') . '</p>';
        
        $message .= '</body></html>';
        
        return $message;
    }
    
    /**
     * Send status update email to customer
     */
    public static function send_status_update($request_id, $new_status, $admin_notes = '') {
        $return_request = Return_Request_Database::get_return_request($request_id);
        if (!$return_request) {
            return false;
        }
        
        $order = wc_get_order($return_request->order_id);
        
        $subject = sprintf(__('Return Request #%d Status Update', 'return-button'), $request_id);
        
        $message = '<html><body>';
        $message .= '<h2>' . sprintf(__('Return Request #%d Status Update', 'return-button'), $request_id) . '</h2>';
        
        $message .= '<p>' . sprintf(__('Dear %s,', 'return-button'), esc_html($return_request->customer_name)) . '</p>';
        $message .= '<p>' . sprintf(__('The status of your return request #%d for order #%s has been updated.', 'return-button'), $request_id, $order->get_order_number()) . '</p>';
        
        $message .= '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
        $message .= '<tr><td style="border: 1px solid #ddd; padding: 10px; font-weight: bold;">New Status:</td><td style="border: 1px solid #ddd; padding: 10px;">' . ucfirst($new_status) . '</td></tr>';
        $message .= '</table>';
        
        if (!empty($admin_notes)) {
            $message .= '<h3>' . __('Admin Notes', 'return-button') . '</h3>';
            $message .= '<div style="border: 1px solid #ddd; padding: 15px; background-color: #f9f9f9;">';
            $message .= '<p>' . nl2br(esc_html($admin_notes)) . '</p>';
            $message .= '</div>';
        }
        
        $message .= '<p>' . __('Thank you for your business.', 'return-button') . '</p>';
        $message .= '<p>' . __('Best regards,', 'return-button') . '<br>';
        $message .= get_bloginfo('name') . '</p>';
        
        $message .= '</body></html>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($return_request->customer_email, $subject, $message, $headers);
    }
}