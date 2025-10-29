<?php
/**
 * Plugin Name: NamecardGen
 * Description: 名片生成管理系統 - 多檔案專業版
 * Version: 1.0.0
 * Author: 諗下先
 */

// 安全檢查
if (!defined('ABSPATH')) {
    exit;
}

// 載入必要檔案
require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-clients.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-plans.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-pages.php';
require_once plugin_dir_path(__FILE__) . 'public/shortcodes.php';

class NamecardGen_Complete {
    
    private $database;
    private $clients;
    private $plans;
    private $admin_pages;
    private $shortcodes;
    
    public function __construct() {
        // 初始化各個模組
        $this->database = new NamecardGen_Database();
        $this->clients = new NamecardGen_Clients();
        $this->plans = new NamecardGen_Plans();
        $this->admin_pages = new NamecardGen_Admin_Pages($this->clients, $this->plans, $this->database);
        $this->shortcodes = new NamecardGen_Shortcodes($this->clients, $this->database);
        
        // 註冊啟用掛鉤
        register_activation_hook(__FILE__, array($this->database, 'create_tables'));
        
        // 初始化
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        // 確保所有模組正確初始化
        $this->admin_pages->init();
        $this->shortcodes->init();
    }
}

// 初始化外掛
new NamecardGen_Complete();

// 停用外掛時清理
register_deactivation_hook(__FILE__, 'namecardgen_deactivate');
function namecardgen_deactivate() {
    flush_rewrite_rules();
}