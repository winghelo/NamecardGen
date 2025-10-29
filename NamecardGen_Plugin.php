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

// 防止直接訪問
if (!defined('ABSPATH')) {
    exit;
}

// 定義外掛常數
define('NAMECARDGEN_VERSION', '1.0.0');
define('NAMECARDGEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NAMECARDGEN_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('NAMECARDGEN_PLUGIN_FILE', __FILE__);

// 自動加載類別
spl_autoload_register(function ($class_name) {
    if (false !== strpos($class_name, 'NamecardGen')) {
        $class_file = str_replace(['NamecardGen', '_'], ['', '-'], $class_name);
        $class_file = strtolower($class_file);
        $file_path = NAMECARDGEN_PLUGIN_PATH . 'includes/class-' . $class_file . '.php';
        
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
});

// 初始化外掛
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
        // 載入核心類別
        $this->load_core_files();
        
        // 註冊激活和停用鉤子
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // 初始化外掛
        add_action('plugins_loaded', array($this, 'load_plugin'));
    }
    
    private function load_core_files() {
        // 載入核心檔案
        require_once NAMECARDGEN_PLUGIN_PATH . 'includes/class-core.php';
        require_once NAMECARDGEN_PLUGIN_PATH . 'includes/class-database.php';
        require_once NAMECARDGEN_PLUGIN_PATH . 'includes/class-utilities.php';
        
        // 載入前台和後台
        if (is_admin()) {
            require_once NAMECARDGEN_PLUGIN_PATH . 'admin/class-admin.php';
        } else {
            require_once NAMECARDGEN_PLUGIN_PATH . 'public/class-public.php';
        }
    }
    
    public function activate() {
        // 創建資料庫表格
        $database = new NamecardGen_Database();
        $database->create_tables();
        
        // 設定預設選項
        update_option('namecardgen_version', NAMECARDGEN_VERSION);
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function load_plugin() {
        // 初始化核心功能
        NamecardGen_Core::get_instance();
        
        // 初始化管理區域
        if (is_admin()) {
            NamecardGen_Admin::get_instance();
        } else {
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
