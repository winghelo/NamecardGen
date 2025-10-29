<?php
/**
 * 客戶管理類別
 */

if (!defined('ABSPATH')) {
    exit;
}

class NamecardGen_Clients {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'namecardgen_clients';
    }
    
    /**
     * 獲取所有客戶
     */
    public function get_all_clients($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'page' => 1,
            'per_page' => 20,
            'status' => 'active',
            'search' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "WHERE status = '" . esc_sql($args['status']) . "'";
        
        if (!empty($args['search'])) {
            $where .= $wpdb->prepare(
                " AND (company_name LIKE %s OR contact_person LIKE %s OR email LIKE %s)",
                '%' . $wpdb->esc_like($args['search']) . '%',
                '%' . $wpdb->esc_like($args['search']) . '%',
                '%' . $wpdb->esc_like($args['search']) . '%'
            );
        }
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $clients = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                 {$where} 
                 ORDER BY created_at DESC 
                 LIMIT %d OFFSET %d",
                $args['per_page'],
                $offset
            )
        );
        
        return $clients;
    }
    
    /**
     * 根據ID獲取客戶
     */
    public function get_client_by_id($client_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $client_id)
        );
    }
    
    /**
     * 根據用戶ID獲取客戶
     */
    public function get_client_by_user_id($user_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE user_id = %d", $user_id)
        );
    }
    
    /**
     * 創建新客戶
     */
    public function create_client($data) {
        global $wpdb;
        
        $defaults = array(
            'user_id' => get_current_user_id(),
            'company_name' => '',
            'contact_person' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
            'status' => 'active'
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // 驗證必要欄位
        if (empty($data['company_name'])) {
            return new WP_Error('missing_company', '公司名稱是必填欄位');
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $data['user_id'],
                'company_name' => sanitize_text_field($data['company_name']),
                'contact_person' => sanitize_text_field($data['contact_person']),
                'email' => sanitize_email($data['email']),
                'phone' => sanitize_text_field($data['phone']),
                'address' => sanitize_textarea_field($data['address']),
                'status' => $data['status']
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', '創建客戶時發生資料庫錯誤');
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * 更新客戶資料
     */
    public function update_client($client_id, $data) {
        global $wpdb;
        
        $update_data = array();
        $format = array();
        
        if (isset($data['company_name'])) {
            $update_data['company_name'] = sanitize_text_field($data['company_name']);
            $format[] = '%s';
        }
        
        if (isset($data['contact_person'])) {
            $update_data['contact_person'] = sanitize_text_field($data['contact_person']);
            $format[] = '%s';
        }
        
        if (isset($data['email'])) {
            $update_data['email'] = sanitize_email($data['email']);
            $format[] = '%s';
        }
        
        if (isset($data['phone'])) {
            $update_data['phone'] = sanitize_text_field($data['phone']);
            $format[] = '%s';
        }
        
        if (isset($data['address'])) {
            $update_data['address'] = sanitize_textarea_field($data['address']);
            $format[] = '%s';
        }
        
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
            $format[] = '%s';
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', '沒有提供要更新的資料');
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $client_id),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', '更新客戶時發生資料庫錯誤');
        }
        
        return $result;
    }
    
    /**
     * 刪除客戶（軟刪除）
     */
    public function delete_client($client_id) {
        return $this->update_client($client_id, array('status' => 'deleted'));
    }
    
    /**
     * 獲取客戶數量
     */
    public function get_clients_count($status = 'active') {
        global $wpdb;
        
        return $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s", $status)
        );
    }
    
    /**
     * 檢查客戶是否存在
     */
    public function client_exists($client_id) {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE id = %d AND status != 'deleted'", $client_id)
        );
        
        return $count > 0;
    }
}
?>
