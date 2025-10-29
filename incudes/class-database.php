<?php
/**
 * 資料庫處理類別
 */

if (!defined('ABSPATH')) {
    exit;
}

class NamecardGen_Database {
    
    private $charset_collate;
    
    public function __construct() {
        global $wpdb;
        $this->charset_collate = $wpdb->get_charset_collate();
    }
    
    public function init() {
        $this->create_tables();
    }
    
    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $this->create_clients_table();
        $this->create_plans_table();
        $this->create_namecards_table();
        $this->create_orders_table();

        // 記錄資料庫版本
    update_option('namecardgen_db_version', '1.0.0');
    }

    }
    
    private function create_clients_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'namecardgen_clients';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9),
            company_name varchar(255) NOT NULL,
            contact_person varchar(255),
            email varchar(255),
            phone varchar(50),
            address text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) {$this->charset_collate};";
        
        dbDelta($sql);
    }
    
    private function create_plans_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'namecardgen_plans';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            plan_name varchar(255) NOT NULL,
            description text,
            price decimal(10,2) DEFAULT 0.00,
            duration_days int DEFAULT 30,
            max_cards int DEFAULT 100,
            features text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY status (status)
        ) {$this->charset_collate};";
        
        dbDelta($sql);
        
        // 插入預設方案
    }
    
    private function create_namecards_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'namecardgen_namecards';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id mediumint(9) NOT NULL,
            plan_id mediumint(9) NOT NULL,
            card_data text NOT NULL,
            design_template varchar(100),
            pdf_path varchar(500),
            status varchar(20) DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY client_id (client_id),
            KEY plan_id (plan_id),
            KEY status (status)
        ) {$this->charset_collate};";
        
        dbDelta($sql);
    }
    
    private function create_orders_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'namecardgen_orders';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id mediumint(9) NOT NULL,
            plan_id mediumint(9) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_status varchar(20) DEFAULT 'pending',
            transaction_id varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime,
            PRIMARY KEY (id),
            KEY client_id (client_id),
            KEY payment_status (payment_status)
        ) {$this->charset_collate};";
        
        dbDelta($sql);
    }
    
    private function insert_default_plans() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'namecardgen_plans';
        
        $default_plans = array(
            array(
                'plan_name' => '基礎方案',
                'description' => '適合個人使用者的基礎名片方案',
                'price' => 0.00,
                'duration_days' => 30,
                'max_cards' => 10,
                'features' => '基礎模板,PNG格式,基本支援'
            ),
            array(
                'plan_name' => '專業方案',
                'description' => '適合中小企業的專業名片方案',
                'price' => 299.00,
                'duration_days' => 365,
                'max_cards' => 100,
                'features' => '專業模板,PDF+PNG格式,優先支援,自訂設計'
            ),
            array(
                'plan_name' => '企業方案',
                'description' => '適合大型企業的完整解決方案',
                'price' => 999.00,
                'duration_days' => 365,
                'max_cards' => 1000,
                'features' => '所有模板,多種格式,專屬支援,API整合'
            )
        );
        
        foreach ($default_plans as $plan) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE plan_name = %s",
                $plan['plan_name']
            ));
            
            if (!$existing) {
                $wpdb->insert($table_name, $plan);
            }
        }
    }
    
    public function get_table_prefix() {
        global $wpdb;
        return $wpdb->prefix . 'namecardgen_';
    }
}
?>
