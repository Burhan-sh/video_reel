<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include WP List Table class
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Return_Request_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Return Requests', 'return-button'),
            __('Return Requests', 'return-button'),
            'manage_woocommerce',
            'return-requests',
            array($this, 'admin_page'),
            'dashicons-undo',
            30
        );
        
        add_submenu_page(
            'return-requests',
            __('All Return Requests', 'return-button'),
            __('All Requests', 'return-button'),
            'manage_woocommerce',
            'return-requests',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'return-requests',
            __('Settings', 'return-button'),
            __('Settings', 'return-button'),
            'manage_woocommerce',
            'return-requests-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Admin page display
     */
    public function admin_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($action === 'view' && $id) {
            $this->view_request_page($id);
        } else {
            $this->list_requests_page();
        }
    }
    
    /**
     * List requests page
     */
    private function list_requests_page() {
        $list_table = new Return_Request_List_Table();
        $list_table->prepare_items();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Return Requests', 'return-button'); ?></h1>
            
            <form method="get">
                <input type="hidden" name="page" value="return-requests">
                <?php $list_table->search_box(__('Search Requests', 'return-button'), 'search_id'); ?>
            </form>
            
            <form method="post">
                <?php $list_table->display(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * View single request page
     */
    private function view_request_page($id) {
        $request = Return_Request_Database::get_return_request($id);
        if (!$request) {
            echo '<div class="notice notice-error"><p>' . __('Return request not found.', 'return-button') . '</p></div>';
            return;
        }
        
        $order = wc_get_order($request->order_id);
        
        ?>
        <div class="wrap">
            <h1><?php printf(__('Return Request #%d', 'return-button'), $id); ?></h1>
            
            <div class="return-request-details">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Order Number', 'return-button'); ?></th>
                        <td>
                            <a href="<?php echo admin_url('post.php?post=' . $request->order_id . '&action=edit'); ?>">
                                #<?php echo $order ? $order->get_order_number() : $request->order_id; ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Customer Name', 'return-button'); ?></th>
                        <td><?php echo esc_html($request->customer_name); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Customer Email', 'return-button'); ?></th>
                        <td><a href="mailto:<?php echo esc_attr($request->customer_email); ?>"><?php echo esc_html($request->customer_email); ?></a></td>
                    </tr>
                    <tr>
                        <th><?php _e('Phone Number', 'return-button'); ?></th>
                        <td><?php echo esc_html($request->customer_phone); ?></td>
                    </tr>
                    <?php if (!empty($request->customer_whatsapp)): ?>
                    <tr>
                        <th><?php _e('WhatsApp Number', 'return-button'); ?></th>
                        <td><?php echo esc_html($request->customer_whatsapp); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php _e('Return Reason', 'return-button'); ?></th>
                        <td><?php echo nl2br(esc_html($request->return_reason)); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Request Date', 'return-button'); ?></th>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($request->request_date)); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Status', 'return-button'); ?></th>
                        <td>
                            <span class="status-<?php echo esc_attr($request->status); ?>">
                                <?php echo ucfirst($request->status); ?>
                            </span>
                        </td>
                    </tr>
                </table>
                
                <?php if (!empty($request->uploaded_files)): ?>
                <h3><?php _e('Uploaded Files', 'return-button'); ?></h3>
                <div class="uploaded-files">
                    <?php foreach ($request->uploaded_files as $file): ?>
                        <div class="file-item">
                            <a href="<?php echo esc_url($file['file_url']); ?>" target="_blank">
                                <?php echo esc_html($file['original_name']); ?>
                            </a>
                            <span class="file-size">(<?php echo size_format($file['file_size']); ?>)</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <h3><?php _e('Update Status', 'return-button'); ?></h3>
                <form method="post" action="">
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Status', 'return-button'); ?></th>
                            <td>
                                <select name="new_status">
                                    <option value="pending" <?php selected($request->status, 'pending'); ?>><?php _e('Pending', 'return-button'); ?></option>
                                    <option value="processing" <?php selected($request->status, 'processing'); ?>><?php _e('Processing', 'return-button'); ?></option>
                                    <option value="completed" <?php selected($request->status, 'completed'); ?>><?php _e('Completed', 'return-button'); ?></option>
                                    <option value="cancelled_by_admin" <?php selected($request->status, 'cancelled_by_admin'); ?>><?php _e('Cancelled by Admin', 'return-button'); ?></option>
                                    <option value="cancelled_by_user" <?php selected($request->status, 'cancelled_by_user'); ?>><?php _e('Cancelled by User', 'return-button'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Admin Notes', 'return-button'); ?></th>
                            <td>
                                <textarea name="admin_notes" rows="4" cols="50"><?php echo esc_textarea($request->admin_notes); ?></textarea>
                            </td>
                        </tr>
                    </table>
                    
                    <?php wp_nonce_field('update_return_request', 'update_nonce'); ?>
                    <input type="hidden" name="request_id" value="<?php echo $id; ?>">
                    <input type="hidden" name="action" value="update_status">
                    
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Update Status', 'return-button'); ?>">
                        <a href="<?php echo admin_url('admin.php?page=return-requests'); ?>" class="button"><?php _e('Back to List', 'return-button'); ?></a>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('return_button_settings', 'settings_nonce');
            
            update_option('return_button_return_period_days', intval($_POST['return_period_days']));
            update_option('return_button_grace_period_days', intval($_POST['grace_period_days']));
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'return-button') . '</p></div>';
        }
        
        $return_period_days = get_option('return_button_return_period_days', 10);
        $grace_period_days = get_option('return_button_grace_period_days', 10);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Return Request Settings', 'return-button'); ?></h1>
            
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Return Period (Days)', 'return-button'); ?></th>
                        <td>
                            <input type="number" name="return_period_days" value="<?php echo esc_attr($return_period_days); ?>" min="1" max="365">
                            <p class="description"><?php _e('Number of days after order completion when return request becomes available.', 'return-button'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Grace Period (Days)', 'return-button'); ?></th>
                        <td>
                            <input type="number" name="grace_period_days" value="<?php echo esc_attr($grace_period_days); ?>" min="1" max="365">
                            <p class="description"><?php _e('Number of days the return request button remains available after the return period starts.', 'return-button'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php wp_nonce_field('return_button_settings', 'settings_nonce'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
            if (!wp_verify_nonce($_POST['update_nonce'], 'update_return_request')) {
                wp_die(__('Security check failed', 'return-button'));
            }
            
            $request_id = intval($_POST['request_id']);
            $new_status = sanitize_text_field($_POST['new_status']);
            $admin_notes = sanitize_textarea_field($_POST['admin_notes']);
            
            if (Return_Request_Database::update_status($request_id, $new_status, $admin_notes)) {
                // Send email notification to customer
                Return_Request_Email::send_status_update($request_id, $new_status, $admin_notes);
                
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success"><p>' . __('Status updated successfully!', 'return-button') . '</p></div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p>' . __('Failed to update status.', 'return-button') . '</p></div>';
                });
            }
        }
    }
}

/**
 * WP List Table for Return Requests
 */
class Return_Request_List_Table extends WP_List_Table {
    
    public function __construct() {
        parent::__construct(array(
            'singular' => 'return_request',
            'plural' => 'return_requests',
            'ajax' => false
        ));
    }
    
    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'id' => __('ID', 'return-button'),
            'order_id' => __('Order', 'return-button'),
            'customer_name' => __('Customer', 'return-button'),
            'customer_email' => __('Email', 'return-button'),
            'status' => __('Status', 'return-button'),
            'request_date' => __('Date', 'return-button'),
            'actions' => __('Actions', 'return-button')
        );
    }
    
    public function get_sortable_columns() {
        return array(
            'id' => array('id', false),
            'order_id' => array('order_id', false),
            'customer_name' => array('customer_name', false),
            'status' => array('status', false),
            'request_date' => array('request_date', true)
        );
    }
    
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        
        $this->items = Return_Request_Database::get_all_return_requests($status_filter, $per_page, $offset);
        $total_items = Return_Request_Database::get_total_count($status_filter);
        
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
    
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
                return $item->id;
            case 'order_id':
                $order = wc_get_order($item->order_id);
                return '<a href="' . admin_url('post.php?post=' . $item->order_id . '&action=edit') . '">#' . 
                       ($order ? $order->get_order_number() : $item->order_id) . '</a>';
            case 'customer_name':
                return esc_html($item->customer_name);
            case 'customer_email':
                return '<a href="mailto:' . esc_attr($item->customer_email) . '">' . esc_html($item->customer_email) . '</a>';
            case 'status':
                return '<span class="status-' . esc_attr($item->status) . '">' . ucfirst($item->status) . '</span>';
            case 'request_date':
                return date('Y-m-d H:i', strtotime($item->request_date));
            case 'actions':
                return '<a href="' . admin_url('admin.php?page=return-requests&action=view&id=' . $item->id) . '" class="button button-small">' . __('View', 'return-button') . '</a>';
            default:
                return '';
        }
    }
    
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="return_request[]" value="%s" />', $item->id);
    }
}