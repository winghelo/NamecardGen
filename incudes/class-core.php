<?php
/**
 * 核心控制器類別
 */

if (!defined('ABSPATH')) {
    exit;
}

class NamecardGen_Core {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // 初始化資料庫
        add_action('init', array($this, 'init_database'));
        
        // 註冊自訂文章類型
        add_action('init', array($this, 'register_post_types'));
        
        // 註冊短代碼
        add_action('init', array($this, 'register_shortcodes'));
    }
    
    public function init_database() {
        $database = new NamecardGen_Database();
        $database->init();
    }
    
    public function register_post_types() {
        // 註冊客戶自訂文章類型
        register_post_type('namecardgen_client',
            array(
                'labels' => array(
                    'name' => __('客戶', 'namecardgen'),
                    'singular_name' => __('客戶', 'namecardgen')
                ),
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => false,
                'capability_type' => 'post',
                'hierarchical' => false,
                'supports' => array('title')
            )
        );
        
        // 註冊方案自訂文章類型
        register_post_type('namecardgen_plan',
            array(
                'labels' => array(
                    'name' => __('方案', 'namecardgen'),
                    'singular_name' => __('方案', 'namecardgen')
                ),
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => false,
                'capability_type' => 'post',
                'hierarchical' => false,
                'supports' => array('title', 'editor')
            )
        );
    }
    
    public function register_shortcodes() {
        // 載入短代碼
        require_once NAMECARDGEN_PLUGIN_PATH . 'public/shortcodes.php';
    }
    
    // 工具方法
    public function get_clients() {
        $clients_class = new NamecardGen_Clients();
        return $clients_class->get_all_clients();
    }
    
    public function get_plans() {
        $plans_class = new NamecardGen_Plans();
        return $plans_class->get_all_plans();
    }
    
    public function create_namecard($client_id, $plan_id, $data) {
        // 創建名片邏輯
        $utilities = new NamecardGen_Utilities();
        return $utilities->generate_namecard($client_id, $plan_id, $data);
    }
}
?>
