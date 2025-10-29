<?php
/**
 * Plugin Name: NamecardGen
 * Plugin URI: https://github.com/winghelo/NamecardGen
 * Description: 專業名片生成與管理外掛
 * Version: 1.0.0
 * Author: Your Name
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
    // 只處理我們的類別
    if (strpos($class_name, 'NamecardGen') === 0) {
        $class_file = strtolower(str_replace('_', '-', $class_name));
        $class_file = str_replace('namecardgen-', '', $class_file);
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
        // 註冊激活和停用鉤子
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // 初始化外掛
        add_action('plugins_loaded', array($this, 'load_plugin'));
    }
    
    public function activate() {
        // 創建資料庫表格
        require_once NAMECARDGEN_PLUGIN_PATH . 'includes/class-database.php';
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
        // 載入核心檔案
        $this->load_core_files();
        
        // 初始化核心功能
        if (class_exists('NamecardGen_Core')) {
            NamecardGen_Core::get_instance();
        }
    }
    
    private function load_core_files() {
        // 載入核心類別
        $core_files = array(
            'class-core.php',
            'class-database.php', 
            'class-clients.php',
            'class-plans.php',
            'class-utilities.php'
        );
        
        foreach ($core_files as $file) {
            if (file_exists(NAMECARDGEN_PLUGIN_PATH . 'includes/' . $file)) {
                require_once NAMECARDGEN_PLUGIN_PATH . 'includes/' . $file;
            }
        }
        
        // 載入前台和後台
        if (is_admin()) {
            if (file_exists(NAMECARDGEN_PLUGIN_PATH . 'admin/class-admin.php')) {
                require_once NAMECARDGEN_PLUGIN_PATH . 'admin/class-admin.php';
                NamecardGen_Admin::get_instance();
            }
        } else {
            if (file_exists(NAMECARDGEN_PLUGIN_PATH . 'public/class-public.php')) {
                require_once NAMECARDGEN_PLUGIN_PATH . 'public/class-public.php';
                NamecardGen_Public::get_instance();
            }
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
