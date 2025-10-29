<?php
/**
 * 後台控制器類別
 */

if (!defined('ABSPATH')) {
    exit;
}

class NamecardGen_Admin {
    
    private static $instance = null;
    private $pages = array();
    
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
        // 添加管理員選單
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // 載入管理員樣式和腳本
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // 註冊AJAX處理
        add_action('wp_ajax_namecardgen_admin_action', array($this, 'handle_ajax_request'));
        
        // 添加管理員通知
        add_action('admin_notices', array($this, 'show_admin_notices'));
    }
    
    /**
     * 添加管理員選單
     */
    public function add_admin_menu() {
        $capability = 'manage_options';
        
        // 主選單
        add_menu_page(
            __('名片生成器', 'namecardgen'),
            __('名片生成器', 'namecardgen'),
            $capability,
            'namecardgen',
            array($this, 'display_main_page'),
            'dashicons-id',
            30
        );
        
        // 子選單項目
        $this->pages[] = add_submenu_page(
            'namecardgen',
            __('總覽', 'namecardgen'),
            __('總覽', 'namecardgen'),
            $capability,
            'namecardgen',
            array($this, 'display_main_page')
        );
        
        $this->pages[] = add_submenu_page(
            'namecardgen',
            __('客戶管理', 'namecardgen'),
            __('客戶管理', 'namecardgen'),
            $capability,
            'namecardgen-clients',
            array($this, 'display_clients_page')
        );
        
        $this->pages[] = add_submenu_page(
            'namecardgen',
            __('方案管理', 'namecardgen'),
            __('方案管理', 'namecardgen'),
            $capability,
            'namecardgen-plans',
            array($this, 'display_plans_page')
        );
        
        $this->pages[] = add_submenu_page(
            'namecardgen',
            __('名片管理', 'namecardgen'),
            __('名片管理', 'namecardgen'),
            $capability,
            'namecardgen-cards',
            array($this, 'display_cards_page')
        );
        
        $this->pages[] = add_submenu_page(
            'namecardgen',
            __('設定', 'namecardgen'),
            __('設定', 'namecardgen'),
            $capability,
            'namecardgen-settings',
            array($this, 'display_settings_page')
        );
    }
    
    /**
     * 載入管理員樣式和腳本
     */
    public function enqueue_admin_scripts($hook) {
        // 只在我們的頁面載入
        if (strpos($hook, 'namecardgen') === false) {
            return;
        }
        
        // 載入CSS
        wp_enqueue_style(
            'namecardgen-admin-css',
            NAMECARDGEN_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            NAMECARDGEN_VERSION
        );
        
        // 載入JavaScript
        wp_enqueue_script(
            'namecardgen-admin-js',
            NAMECARDGEN_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            NAMECARDGEN_VERSION,
            true
        );
        
        // 本地化腳本
        wp_localize_script('namecardgen-admin-js', 'namecardgen_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('namecardgen_admin_nonce'),
            'confirm_delete' => __('確定要刪除這個項目嗎？', 'namecardgen'),
            'processing' => __('處理中...', 'namecardgen'),
            'error' => __('發生錯誤，請重試', 'namecardgen')
        ));
        
        // 載入jQuery UI（用於標籤頁等）
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('jquery-ui-dialog');
    }
    
    /**
     * 顯示主頁面
     */
    public function display_main_page() {
        require_once NAMECARDGEN_PLUGIN_PATH . 'admin/admin-main.php';
    }
    
    /**
     * 顯示客戶管理頁面
     */
    public function display_clients_page() {
        require_once NAMECARDGEN_PLUGIN_PATH . 'admin/admin-clients.php';
    }
    
    /**
     * 顯示方案管理頁面
     */
    public function display_plans_page() {
        require_once NAMECARDGEN_PLUGIN_PATH . 'admin/admin-plans.php';
    }
    
    /**
     * 顯示名片管理頁面
     */
    public function display_cards_page() {
        require_once NAMECARDGEN_PLUGIN_PATH . 'admin/admin-cards.php';
    }
    
    /**
     * 顯示設定頁面
     */
    public function display_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('名片生成器設定', 'namecardgen') . '</h1>';
        echo '<div class="card">';
        echo '<h2>' . __('設定選項', 'namecardgen') . '</h2>';
        echo '<p>' . __('這裡是外掛的設定頁面。', 'namecardgen') . '</p>';
        echo '<form method="post" action="options.php">';
        
        // 這裡可以添加實際的設定選項
        settings_fields('namecardgen_settings');
        do_settings_sections('namecardgen_settings');
        
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th scope="row">' . __('預設方案', 'namecardgen') . '</th>';
        echo '<td>';
        echo '<select name="namecardgen_default_plan">';
        echo '<option value="0">' . __('選擇預設方案', 'namecardgen') . '</option>';
        // 這裡可以從資料庫載入方案選項
        echo '</select>';
        echo '</td>';
        echo '</tr>';
        echo '</table>';
        
        submit_button(__('儲存設定', 'namecardgen'));
        echo '</form>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * 處理AJAX請求
     */
    public function handle_ajax_request() {
        // 驗證nonce
        if (!wp_verify_nonce($_POST['nonce'], 'namecardgen_admin_nonce')) {
            wp_die(__('安全驗證失敗', 'namecardgen'));
        }
        
        $action = isset($_POST['sub_action']) ? $_POST['sub_action'] : '';
        $response = array('success' => false, 'message' => '');
        
        switch ($action) {
            case 'delete_client':
                $response = $this->delete_client($_POST['client_id']);
                break;
                
            case 'delete_plan':
                $response = $this->delete_plan($_POST['plan_id']);
                break;
                
            case 'update_status':
                $response = $this->update_item_status($_POST['item_id'], $_POST['item_type'], $_POST['status']);
                break;
                
            default:
                $response['message'] = __('無效的操作', 'namecardgen');
        }
        
        wp_send_json($response);
    }
    
    /**
     * 刪除客戶
     */
    private function delete_client($client_id) {
        $clients_class = new NamecardGen_Clients();
        $result = $clients_class->delete_client($client_id);
        
        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'message' => $result->get_error_message()
            );
        }
        
        return array(
            'success' => true,
            'message' => __('客戶已成功刪除', 'namecardgen')
        );
    }
    
    /**
     * 刪除方案
     */
    private function delete_plan($plan_id) {
        $plans_class = new NamecardGen_Plans();
        $result = $plans_class->delete_plan($plan_id);
        
        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'message' => $result->get_error_message()
            );
        }
        
        return array(
            'success' => true,
            'message' => __('方案已成功刪除', 'namecardgen')
        );
    }
    
    /**
     * 更新項目狀態
     */
    private function update_item_status($item_id, $item_type, $status) {
        if ($item_type === 'client') {
            $class = new NamecardGen_Clients();
        } elseif ($item_type === 'plan') {
            $class = new NamecardGen_Plans();
        } else {
            return array(
                'success' => false,
                'message' => __('無效的項目類型', 'namecardgen')
            );
        }
        
        $result = $class->update_item_status($item_id, $status);
        
        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'message' => $result->get_error_message()
            );
        }
        
        return array(
            'success' => true,
            'message' => __('狀態已更新', 'namecardgen')
        );
    }
    
    /**
     * 顯示管理員通知
     */
    public function show_admin_notices() {
        if (isset($_GET['namecardgen_message'])) {
            $message_type = isset($_GET['message_type']) ? $_GET['message_type'] : 'success';
            $message = sanitize_text_field($_GET['namecardgen_message']);
            
            echo '<div class="notice notice-' . esc_attr($message_type) . ' is-dismissible">';
            echo '<p>' . esc_html($message) . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * 獲取統計數據
     */
    public function get_stats() {
        $clients_class = new NamecardGen_Clients();
        $plans_class = new NamecardGen_Plans();
        
        return array(
            'total_clients' => $clients_class->get_clients_count(),
            'total_plans' => $plans_class->get_plans_count(),
            'active_clients' => $clients_class->get_clients_count('active'),
            'active_plans' => $plans_class->get_plans_count('active')
        );
    }
}
?>
