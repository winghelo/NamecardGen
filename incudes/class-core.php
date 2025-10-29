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
        // 確保資料庫類別已載入
        if (class_exists('NamecardGen_Database')) {
            $database = new NamecardGen_Database();
            $database->init();
        }
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
                'show_ui' => false, // 改為 false，我們使用自訂界面
                'show_in_menu' => false,
                'capability_type' => 'post',
                'hierarchical' => false,
                'supports' => array('title')
            )
        );
    }
    
    public function register_shortcodes() {
        // 確保短代碼檔案存在
        if (file_exists(NAMECARDGEN_PLUGIN_PATH . 'public/shortcodes.php')) {
            require_once NAMECARDGEN_PLUGIN_PATH . 'public/shortcodes.php';
        }
    }
}
?>
