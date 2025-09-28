<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Return_Request_Database {
    
    const TABLE_NAME = 'return_requests';
    
    /**
     * Create the return requests table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            order_id int(11) NOT NULL,
            user_id int(11) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(20) NOT NULL,
            customer_whatsapp varchar(20) DEFAULT NULL,
            return_reason text NOT NULL,
            uploaded_files text DEFAULT NULL,
            status varchar(50) DEFAULT 'pending',
            request_date datetime DEFAULT CURRENT_TIMESTAMP,
            updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            admin_notes text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Insert a new return request
     */
    public static function insert_return_request($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'order_id' => $data['order_id'],
                'user_id' => $data['user_id'],
                'customer_name' => sanitize_text_field($data['customer_name']),
                'customer_email' => sanitize_email($data['customer_email']),
                'customer_phone' => sanitize_text_field($data['customer_phone']),
                'customer_whatsapp' => sanitize_text_field($data['customer_whatsapp']),
                'return_reason' => sanitize_textarea_field($data['return_reason']),
                'uploaded_files' => maybe_serialize($data['uploaded_files']),
                'status' => 'pending'
            ),
            array(
                '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            )
        );
        
        return $result !== false ? $wpdb->insert_id : false;
    }
    
    /**
     * Get return request by ID
     */
    public static function get_return_request($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id)
        );
        
        if ($result && $result->uploaded_files) {
            $result->uploaded_files = maybe_unserialize($result->uploaded_files);
        }
        
        return $result;
    }
    
    /**
     * Get return requests by order ID
     */
    public static function get_return_requests_by_order($order_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name WHERE order_id = %d ORDER BY request_date DESC", $order_id)
        );
    }
    
    /**
     * Update return request status
     */
    public static function update_status($id, $status, $admin_notes = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        return $wpdb->update(
            $table_name,
            array(
                'status' => $status,
                'admin_notes' => sanitize_textarea_field($admin_notes)
            ),
            array('id' => $id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Get all return requests for admin
     */
    public static function get_all_return_requests($status = '', $limit = 20, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $where = '';
        if (!empty($status)) {
            $where = $wpdb->prepare(" WHERE status = %s", $status);
        }
        
        $sql = "SELECT * FROM $table_name $where ORDER BY request_date DESC LIMIT %d OFFSET %d";
        
        return $wpdb->get_results(
            $wpdb->prepare($sql, $limit, $offset)
        );
    }
    
    /**
     * Get total return requests count
     */
    public static function get_total_count($status = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $where = '';
        if (!empty($status)) {
            $where = $wpdb->prepare(" WHERE status = %s", $status);
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where");
    }
    
    /**
     * Check if order has pending return request
     */
    public static function has_pending_return_request($order_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE order_id = %d AND status IN ('pending', 'processing')",
                $order_id
            )
        );
        
        return $count > 0;
    }
}