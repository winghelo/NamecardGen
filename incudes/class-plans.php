<?php
/**
 * 計劃管理類別
 */

if (!defined('ABSPATH')) {
    exit;
}

class NamecardGen_Plans {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'namecardgen_plans';
    }
    
    /**
     * 獲取所有方案
     */
    public function get_all_plans($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'active',
            'include_inactive' => false
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "WHERE 1=1";
        
        if (!$args['include_inactive']) {
            $where .= $wpdb->prepare(" AND status = %s", $args['status']);
        }
        
        $plans = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} {$where} ORDER BY price ASC, created_at DESC"
        );
        
        return $plans;
    }
    
    /**
     * 根據ID獲取方案
     */
    public function get_plan_by_id($plan_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $plan_id)
        );
    }
    
    /**
     * 創建新方案
     */
    public function create_plan($data) {
        global $wpdb;
        
        $defaults = array(
            'plan_name' => '',
            'description' => '',
            'price' => 0.00,
            'duration_days' => 30,
            'max_cards' => 10,
            'features' => '',
            'status' => 'active'
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // 驗證必要欄位
        if (empty($data['plan_name'])) {
            return new WP_Error('missing_plan_name', '方案名稱是必填欄位');
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'plan_name' => sanitize_text_field($data['plan_name']),
                'description' => sanitize_textarea_field($data['description']),
                'price' => floatval($data['price']),
                'duration_days' => intval($data['duration_days']),
                'max_cards' => intval($data['max_cards']),
                'features' => sanitize_textarea_field($data['features']),
                'status' => $data['status']
            ),
            array('%s', '%s', '%f', '%d', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', '創建方案時發生資料庫錯誤');
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * 更新方案
     */
    public function update_plan($plan_id, $data) {
        global $wpdb;
        
        $update_data = array();
        $format = array();
        
        if (isset($data['plan_name'])) {
            $update_data['plan_name'] = sanitize_text_field($data['plan_name']);
            $format[] = '%s';
        }
        
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
            $format[] = '%s';
        }
        
        if (isset($data['price'])) {
            $update_data['price'] = floatval($data['price']);
            $format[] = '%f';
        }
        
        if (isset($data['duration_days'])) {
            $update_data['duration_days'] = intval($data['duration_days']);
            $format[] = '%d';
        }
        
        if (isset($data['max_cards'])) {
            $update_data['max_cards'] = intval($data['max_cards']);
            $format[] = '%d';
        }
        
        if (isset($data['features'])) {
            $update_data['features'] = sanitize_textarea_field($data['features']);
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
            array('id' => $plan_id),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', '更新方案時發生資料庫錯誤');
        }
        
        return $result;
    }
    
    /**
     * 刪除方案（軟刪除）
     */
    public function delete_plan($plan_id) {
        return $this->update_plan($plan_id, array('status' => 'inactive'));
    }
    
    /**
     * 獲取方案數量
     */
    public function get_plans_count($status = 'active') {
        global $wpdb;
        
        return $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s", $status)
        );
    }
    
    /**
     * 檢查方案是否存在
     */
    public function plan_exists($plan_id) {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE id = %d AND status = 'active'", $plan_id)
        );
        
        return $count > 0;
    }
    
    /**
     * 獲取免費方案
     */
    public function get_free_plan() {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE price = 0.00 AND status = 'active' ORDER BY id ASC LIMIT 1")
        );
    }
    
    /**
     * 解析功能列表
     */
    public function parse_features($features_string) {
        if (empty($features_string)) {
            return array();
        }
        
        $features = explode(',', $features_string);
        $features = array_map('trim', $features);
        $features = array_filter($features);
        
        return $features;
    }
    
    /**
     * 驗證方案是否適合客戶
     */
    public function validate_plan_for_client($plan_id, $client_id) {
        $plan = $this->get_plan_by_id($plan_id);
        
        if (!$plan) {
            return new WP_Error('invalid_plan', '無效的方案');
        }
        
        if ($plan->status !== 'active') {
            return new WP_Error('inactive_plan', '此方案已停用');
        }
        
        // 檢查客戶是否已達到方案的最大名片數量
        $clients_class = new NamecardGen_Clients();
        $client = $clients_class->get_client_by_id($client_id);
        
        if (!$client) {
            return new WP_Error('invalid_client', '無效的客戶');
        }
        
        // 這裡可以添加更多驗證邏輯
        
        return true;
    }
}
?>
