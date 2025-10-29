<?php
if (!defined('ABSPATH')) {
    exit;
}

class NamecardGen_Plans {
    
    public function add_plan($plan_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'namecardgen_plans';
        
        // 設定預設值
        $default_data = array(
            'is_active' => 1,
            'created_at' => current_time('mysql')
        );
        
        $plan_data = array_merge($default_data, $plan_data);
        
        return $wpdb->insert($table_name, $plan_data);
    }
    
    public function delete_plan($plan_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'namecardgen_plans';
        
        // 檢查是否有客戶使用此計劃
        $clients_table = $wpdb->prefix . 'namecardgen_clients';
        $client_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $clients_table WHERE plan_id = %d", 
            $plan_id
        ));
        
        if ($client_count > 0) {
            return false; // 有客戶使用，無法刪除
        }
        
        return $wpdb->delete($table_name, array('id' => $plan_id));
    }
    
    public function get_plan($plan_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'namecardgen_plans';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $plan_id));
    }
    
    public function get_all_plans() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'namecardgen_plans';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY price ASC");
    }
    
    public function get_active_plans() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'namecardgen_plans';
        return $wpdb->get_results("SELECT * FROM $table_name WHERE is_active = 1 ORDER BY price ASC");
    }
    
    public function get_plan_client_count($plan_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'namecardgen_clients';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE plan_id = %d", 
            $plan_id
        ));
    }
    
    public function get_plan_stats() {
        global $wpdb;
        $clients_table = $wpdb->prefix . 'namecardgen_clients';
        $plans_table = $wpdb->prefix . 'namecardgen_plans';
        
        return $wpdb->get_results("
            SELECT p.*, COUNT(c.id) as client_count 
            FROM $plans_table p 
            LEFT JOIN $clients_table c ON p.id = c.plan_id 
            WHERE p.is_active = 1 
            GROUP BY p.id 
            ORDER BY client_count DESC
        ");
    }
    
    public function update_plan($plan_id, $plan_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'namecardgen_plans';
        
        return $wpdb->update(
            $table_name,
            $plan_data,
            array('id' => $plan_id)
        );
    }
}