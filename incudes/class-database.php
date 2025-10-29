<?php
if (!defined('ABSPATH')) {
    exit;
}

class NamecardGen_Database {
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // 客戶資料表 - 升級版本
        $table_clients = $wpdb->prefix . 'namecardgen_clients';
        $sql_clients = "CREATE TABLE $table_clients (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20),
            company varchar(100),
            plan_id mediumint(9) DEFAULT NULL,
            image_url varchar(255),
            custom_link varchar(100),
            status varchar(20) DEFAULT 'active',
            expired_at datetime NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY custom_link (custom_link),
            UNIQUE KEY email (email),
            KEY plan_id (plan_id)
        ) $charset_collate;";
        
        // 計劃資料表 - 升級版本
        $table_plans = $wpdb->prefix . 'namecardgen_plans';
        $sql_plans = "CREATE TABLE $table_plans (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            plan_name varchar(100) NOT NULL,
            price decimal(10,2) DEFAULT 0.00,
            description text,
            valid_days int DEFAULT 30,
            features text,
            is_active tinyint(1) DEFAULT 1,
            max_cards int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_clients);
        dbDelta($sql_plans);
        
        // 插入預設計劃
        $this->insert_default_plans();
        
        flush_rewrite_rules();
    }
    
    private function insert_default_plans() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'namecardgen_plans';
        
        $default_plans = array(
            array(
                'plan_name' => '個人版',
                'price' => 150.00,
                'description' => '個人名片連結功能',
                'valid_days' => 365,
                'features' => '1張名片,1個連結,365天有效期',
                'is_active' => 1,
                'max_cards' => 1
            ),
            array(
                'plan_name' => '雙人版',
                'price' => 250.00,
                'description' => '雙人名片連結功能',
                'valid_days' => 365,
                'features' => '2張名片,2個連結,365天有效期',
                'is_active' => 1,
                'max_cards' => 2
            ),
            array(
                'plan_name' => '企業版',
                'price' => 500.00,
                'description' => '五人名片連結功能',
                'valid_days' => 365,
                'features' => '5張名片,5個連結,365天有效期',
                'is_active' => 1,
                'max_cards' => 5
            )
        );
        
        foreach ($default_plans as $plan) {
            $wpdb->insert($table_name, $plan);
        }
    }
    
    public function get_clients_with_plans() {
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
    
    public function get_active_plans() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'namecardgen_plans';
        return $wpdb->get_results("SELECT * FROM $table_name WHERE is_active = 1 ORDER BY price ASC");
    }
    
    public function get_plan_stats() {
        global $wpdb;
        $clients_table = $wpdb->prefix . 'namecardgen_clients';
        $plans_table = $wpdb->prefix . 'namecardgen_plans';
        
        return $wpdb->get_results("
            SELECT p.plan_name, COUNT(c.id) as client_count 
            FROM $plans_table p 
            LEFT JOIN $clients_table c ON p.id = c.plan_id 
            WHERE p.is_active = 1 
            GROUP BY p.id 
            ORDER BY client_count DESC
        ");
    }
}