<?php
if (!defined('ABSPATH')) {
    exit;
}

class NamecardGen_Clients {
    
    private $database;
    
    public function __construct($database = null) {
        if ($database) {
            $this->database = $database;
        }
    }
    
    public function set_database($database) {
        $this->database = $database;
    }
    
    public function add_client($client_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'namecardgen_clients';
        
        // 設定預設值
        $default_data = array(
            'status' => 'active',
            'created_at' => current_time('mysql')
        );
        
        $client_data = array_merge($default_data, $client_data);
        
        return $wpdb->insert($table_name, $client_data);
    }
    
    public function update_client_plan($client_id, $plan_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'namecardgen_clients';
        $expired_at = null;
        
        // 如果有選擇計劃，計算到期時間
        if ($plan_id) {
            $plan = $this->get_plan($plan_id);
            if ($plan && $plan->valid_days > 0) {
                $expired_at = date('Y-m-d H:i:s', strtotime("+{$plan->valid_days} days"));
            }
        }
        
        return $wpdb->update(
            $table_name,
            array(
                'plan_id' => $plan_id,
                'expired_at' => $expired_at,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $client_id)
        );
    }
    
    public function delete_client($client_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'namecardgen_clients';
        return $wpdb->delete($table_name, array('id' => $client_id));
    }
    
    public function get_client($client_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'namecardgen_clients';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $client_id));
    }
    
    public function get_all_clients() {
        global $wpdb;
        $clients_table = $wpdb->prefix . 'namecardgen_clients';
        $plans_table = $wpdb->prefix . 'namecardgen_plans';
        
        return $wpdb->get_results("
            SELECT c.*, p.plan_name, p.price 
            FROM $clients_table c 
            LEFT JOIN $plans_table p ON c.plan_id = p.id 
            ORDER BY c.created_at DESC
        ");
    }
    
    public function get_plan($plan_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'namecardgen_plans';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $plan_id));
    }
    
    public function get_client_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'namecardgen_clients';
        
        $stats = array(
            'total_clients' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'today_clients' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s",
                current_time('Y-m-d')
            )),
            'month_clients' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE YEAR(created_at) = %d AND MONTH(created_at) = %d",
                current_time('Y'),
                current_time('m')
            ))
        );
        
        return $stats;
    }
    
    public function get_client_status($client) {
        if ('active' !== $client->status) {
            return array('class' => 'status-inactive', 'text' => '❌ 無效');
        }
        
        if ($client->expired_at && strtotime($client->expired_at) < time()) {
            return array('class' => 'status-expired', 'text' => '⏰ 已過期');
        }
        
        return array('class' => 'status-active', 'text' => '✅ 有效');
    }
}