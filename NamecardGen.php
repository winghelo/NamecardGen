<?php
/**
 * Plugin Name: NamecardGen
 * Plugin URI: https://github.com/winghelo/NamecardGen
 * Description: 專業名片生成與管理外掛
 * Version: 1.0.0
 * Author: 諗下先
 * License: GPL v2 or later
 * Text Domain: namecardgen
 */

if (!defined('ABSPATH')) {
    exit;
}

// 定義外掛常數
define('NAMECARDGEN_VERSION', '1.0.0');
define('NAMECARDGEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NAMECARDGEN_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('NAMECARDGEN_PLUGIN_FILE', __FILE__);

class NamecardGen_Plugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // 先載入核心檔案
        $this->load_core_files();
        
        // 註冊激活鉤子
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // 初始化外掛
        add_action('plugins_loaded', array($this, 'load_plugin'));
    }
    
    private function load_core_files() {
        // 按順序載入核心類別
        $core_files = array(
            'includes/class-database.php',
            'includes/class-utilities.php', 
            'includes/class-clients.php',
            'includes/class-plans.php',
            'includes/class-core.php',
            'admin/class-admin.php',
            'public/class-public.php'
        );
        
        foreach ($core_files as $file) {
            $file_path = NAMECARDGEN_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    public function activate() {
        // 確保資料庫類別已載入
        if (class_exists('NamecardGen_Database')) {
            $database = new NamecardGen_Database();
            $database->create_tables();
        }
        
        update_option('namecardgen_version', NAMECARDGEN_VERSION);
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function load_plugin() {
        // 初始化核心
        if (class_exists('NamecardGen_Core')) {
            NamecardGen_Core::get_instance();
        }
        
        // 初始化管理區域
        if (is_admin() && class_exists('NamecardGen_Admin')) {
            NamecardGen_Admin::get_instance();
        }
        
        // 初始化前台
        if (!is_admin() && class_exists('NamecardGen_Public')) {
            NamecardGen_Public::get_instance();
        }
        
        // 載入文字域
        load_plugin_textdomain('namecardgen', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
}

// 啟動外掛
function namecardgen_init() {
    return NamecardGen_Plugin::get_instance();
}
add_action('plugins_loaded', 'namecardgen_init');
?>
