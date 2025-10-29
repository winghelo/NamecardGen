<?php
/**
 * 前台控制器類別
 */

if (!defined('ABSPATH')) {
    exit;
}

class NamecardGen_Public {
    
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
        // 載入短代碼
        add_action('init', array($this, 'load_shortcodes'));
        
        // 載入前台樣式和腳本
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        
        // 處理表單提交
        add_action('wp_ajax_namecardgen_submit_form', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_namecardgen_submit_form', array($this, 'handle_form_submission'));
        
        // 處理檔案下載
        add_action('wp_ajax_namecardgen_download', array($this, 'handle_file_download'));
        add_action('wp_ajax_nopriv_namecardgen_download', array($this, 'handle_file_download'));
    }
    
    /**
     * 載入短代碼
     */
    public function load_shortcodes() {
        require_once NAMECARDGEN_PLUGIN_PATH . 'public/shortcodes.php';
    }
    
    /**
     * 載入前台樣式和腳本
     */
    public function enqueue_public_scripts() {
        // 只在需要時載入
        if ($this->should_load_scripts()) {
            // 載入CSS
            wp_enqueue_style(
                'namecardgen-public-css',
                NAMECARDGEN_PLUGIN_URL . 'assets/css/public.css',
                array(),
                NAMECARDGEN_VERSION
            );
            
            // 載入JavaScript
            wp_enqueue_script(
                'namecardgen-public-js',
                NAMECARDGEN_PLUGIN_URL . 'assets/js/public.js',
                array('jquery'),
                NAMECARDGEN_VERSION,
                true
            );
            
            // 本地化腳本
            wp_localize_script('namecardgen-public-js', 'namecardgen_public', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('namecardgen_public_nonce'),
                'processing' => __('處理中...', 'namecardgen'),
                'success_message' => __('名片生成成功！', 'namecardgen'),
                'error_message' => __('發生錯誤，請重試', 'namecardgen'),
                'required_field' => __('此為必填欄位', 'namecardgen'),
                'invalid_email' => __('請輸入有效的電子郵件', 'namecardgen')
            ));
        }
    }
    
    /**
     * 判斷是否需要載入腳本
     */
    private function should_load_scripts() {
        global $post;
        
        if (is_admin()) {
            return false;
        }
        
        // 如果文章內容包含我們的短代碼，則載入
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'namecardgen_form')) {
            return true;
        }
        
        // 其他條件可以在此添加
        return apply_filters('namecardgen_should_load_scripts', false);
    }
    
    /**
     * 處理表單提交
     */
    public function handle_form_submission() {
        // 驗證nonce
        if (!wp_verify_nonce($_POST['nonce'], 'namecardgen_public_nonce')) {
            wp_send_json_error(array('message' => __('安全驗證失敗', 'namecardgen')));
        }
        
        // 驗證表單資料
        $validation = $this->validate_form_data($_POST);
        if (is_wp_error($validation)) {
            wp_send_json_error(array('message' => $validation->get_error_message()));
        }
        
        // 處理表單資料
        $result = $this->process_form_submission($_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => __('名片生成成功！', 'namecardgen'),
            'download_url' => $result['download_url'],
            'namecard_id' => $result['namecard_id']
        ));
    }
    
    /**
     * 驗證表單資料
     */
    private function validate_form_data($data) {
        $required_fields = array(
            'company_name' => __('公司名稱', 'namecardgen'),
            'contact_person' => __('聯絡人', 'namecardgen'),
            'email' => __('電子郵件', 'namecardgen')
        );
        
        // 檢查必填欄位
        foreach ($required_fields as $field => $label) {
            if (empty(trim($data[$field]))) {
                return new WP_Error('missing_field', sprintf(__('%s 是必填欄位', 'namecardgen'), $label));
            }
        }
        
        // 驗證電子郵件格式
        if (!is_email($data['email'])) {
            return new WP_Error('invalid_email', __('請輸入有效的電子郵件地址', 'namecardgen'));
        }
        
        // 驗證電話號碼（如果提供）
        if (!empty($data['phone']) && !$this->validate_phone_number($data['phone'])) {
            return new WP_Error('invalid_phone', __('請輸入有效的電話號碼', 'namecardgen'));
        }
        
        return true;
    }
    
    /**
     * 驗證電話號碼
     */
    private function validate_phone_number($phone) {
        // 基本的電話號碼驗證，可根據需求調整
        $pattern = '/^[0-9\-\+\(\)\s]{8,20}$/';
        return preg_match($pattern, $phone);
    }
    
    /**
     * 處理表單提交
     */
    private function process_form_submission($data) {
        $clients_class = new NamecardGen_Clients();
        $utilities = new NamecardGen_Utilities();
        $plans_class = new NamecardGen_Plans();
        
        // 獲取或創建客戶
        $client = $this->get_or_create_client($data);
        if (is_wp_error($client)) {
            return $client;
        }
        
        // 獲取免費方案
        $free_plan = $plans_class->get_free_plan();
        if (!$free_plan) {
            return new WP_Error('no_plan', __('沒有可用的方案', 'namecardgen'));
        }
        
        // 準備名片資料
        $namecard_data = array(
            'template' => isset($data['template']) ? sanitize_text_field($data['template']) : 'default',
            'design_options' => array(
                'color_scheme' => isset($data['color_scheme']) ? sanitize_text_field($data['color_scheme']) : 'blue',
                'layout' => isset($data['layout']) ? sanitize_text_field($data['layout']) : 'standard'
            )
        );
        
        // 生成名片
        $result = $utilities->generate_namecard($client->id, $free_plan->id, $namecard_data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // 發送確認郵件
        $this->send_confirmation_email($client, $result);
        
        return array(
            'download_url' => $utilities->generate_download_link($result['namecard_id']),
            'namecard_id' => $result['namecard_id']
        );
    }
    
    /**
     * 獲取或創建客戶
     */
    private function get_or_create_client($data) {
        $clients_class = new NamecardGen_Clients();
        
        // 先檢查是否已存在相同郵件的客戶
        $existing_client = $clients_class->get_client_by_user_id(get_current_user_id());
        if (!$existing_client && !empty($data['email'])) {
            // 也可以根據郵件查找
            // 這裡簡化處理，直接創建新客戶
        }
        
        if ($existing_client) {
            return $existing_client;
        }
        
        // 創建新客戶
        $client_data = array(
            'user_id' => get_current_user_id(),
            'company_name' => sanitize_text_field($data['company_name']),
            'contact_person' => sanitize_text_field($data['contact_person']),
            'email' => sanitize_email($data['email']),
            'phone' => isset($data['phone']) ? sanitize_text_field($data['phone']) : '',
            'address' => isset($data['address']) ? sanitize_textarea_field($data['address']) : ''
        );
        
        $client_id = $clients_class->create_client($client_data);
        
        if (is_wp_error($client_id)) {
            return $client_id;
        }
        
        return $clients_class->get_client_by_id($client_id);
    }
    
    /**
     * 發送確認郵件
     */
    private function send_confirmation_email($client, $namecard_result) {
        $utilities = new NamecardGen_Utilities();
        
        $to = $client->email;
        $subject = __('您的名片已生成完成', 'namecardgen');
        
        $message = '
        <html>
        <head>
            <title>' . __('名片生成確認', 'namecardgen') . '</title>
        </head>
        <body>
            <h2>' . __('感謝您使用我們的服務', 'namecardgen') . '</h2>
            <p>' . sprintf(__('親愛的 %s，', 'namecardgen'), $client->contact_person) . '</p>
            <p>' . __('您的名片已經生成完成，您可以透過以下連結下載：', 'namecardgen') . '</p>
            <p><a href="' . $namecard_result['pdf_path'] . '">' . __('下載名片', 'namecardgen') . '</a></p>
            <p>' . __('如果您有任何問題，請隨時與我們聯繫。', 'namecardgen') . '</p>
            <br>
            <p>' . __('謝謝！', 'namecardgen') . '</p>
        </body>
        </html>';
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $utilities->send_email_notification($to, $subject, $message, $headers);
    }
    
    /**
     * 處理檔案下載
     */
    public function handle_file_download() {
        $namecard_id = isset($_GET['namecard_id']) ? intval($_GET['namecard_id']) : 0;
        $file_type = isset($_GET['file_type']) ? sanitize_text_field($_GET['file_type']) : 'pdf';
        $nonce = isset($_GET['nonce']) ? $_GET['nonce'] : '';
        
        // 驗證nonce
        if (!wp_verify_nonce($nonce, 'namecardgen_download_' . $namecard_id)) {
            wp_die(__('下載連結已失效', 'namecardgen'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'namecardgen_namecards';
        
        $namecard = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d", $namecard_id
        ));
        
        if (!$namecard || !$namecard->pdf_path) {
            wp_die(__('檔案不存在', 'namecardgen'));
        }
        
        // 重定向到下載連結
        wp_redirect($namecard->pdf_path);
        exit;
    }
    
    /**
     * 獲取可用模板
     */
    public function get_available_templates() {
        return apply_filters('namecardgen_available_templates', array(
            'default' => array(
                'name' => __('預設模板', 'namecardgen'),
                'description' => __('簡潔專業的預設設計', 'namecardgen'),
                'preview' => NAMECARDGEN_PLUGIN_URL . 'assets/images/template-default.jpg'
            ),
            'modern' => array(
                'name' => __('現代風格', 'namecardgen'),
                'description' => __('時尚現代的設計風格', 'namecardgen'),
                'preview' => NAMECARDGEN_PLUGIN_URL . 'assets/images/template-modern.jpg'
            ),
            'classic' => array(
                'name' => __('經典風格', 'namecardgen'),
                'description' => __('傳統經典的商務設計', 'namecardgen'),
                'preview' => NAMECARDGEN_PLUGIN_URL . 'assets/images/template-classic.jpg'
            )
        ));
    }
    
    /**
     * 獲取顏色方案
     */
    public function get_color_schemes() {
        return apply_filters('namecardgen_color_schemes', array(
            'blue' => __('藍色', 'namecardgen'),
            'green' => __('綠色', 'namecardgen'),
            'red' => __('紅色', 'namecardgen'),
            'purple' => __('紫色', 'namecardgen'),
            'orange' => __('橙色', 'namecardgen')
        ));
    }
}
?>
