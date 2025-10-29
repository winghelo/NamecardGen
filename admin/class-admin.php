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
            $result = $class->update_client($item_id, array('status' => $status));
        } elseif ($item_type === 'plan') {
            $class = new NamecardGen_Plans();
            $result = $class->update_plan($item_id, array('status' => $status));
        } else {
            return array(
                'success' => false,
                'message' => __('無效的項目類型', 'namecardgen')
            );
        }
        
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

    /**
     * 顯示客戶表單
     */
    public function display_client_form($client_id, $action) {
        $clients_class = new NamecardGen_Clients();
        $client = $client_id ? $clients_class->get_client_by_id($client_id) : null;
        
        $company_name = $client ? $client->company_name : '';
        $contact_person = $client ? $client->contact_person : '';
        $email = $client ? $client->email : '';
        $phone = $client ? $client->phone : '';
        $address = $client ? $client->address : '';
        $status = $client ? $client->status : 'active';
        ?>
        
        <div class="wrap namecardgen-admin">
            <h1><?php echo $action === 'add' ? __('新增客戶', 'namecardgen') : __('編輯客戶', 'namecardgen'); ?></h1>
            
            <div class="namecardgen-card">
                <form method="post">
                    <?php wp_nonce_field('namecardgen_client_form', 'namecardgen_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="company_name"><?php _e('公司名稱', 'namecardgen'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" name="company_name" id="company_name" 
                                       value="<?php echo esc_attr($company_name); ?>" 
                                       class="regular-text" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="contact_person"><?php _e('聯絡人', 'namecardgen'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="contact_person" id="contact_person" 
                                       value="<?php echo esc_attr($contact_person); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="email"><?php _e('電子郵件', 'namecardgen'); ?></label>
                            </th>
                            <td>
                                <input type="email" name="email" id="email" 
                                       value="<?php echo esc_attr($email); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="phone"><?php _e('電話', 'namecardgen'); ?></label>
                            </th>
                            <td>
                                <input type="tel" name="phone" id="phone" 
                                       value="<?php echo esc_attr($phone); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="address"><?php _e('地址', 'namecardgen'); ?></label>
                            </th>
                            <td>
                                <textarea name="address" id="address" rows="3" class="large-text"><?php echo esc_textarea($address); ?></textarea>
                            </td>
                        </tr>
                        
                        <?php if ($action === 'edit') : ?>
                        <tr>
                            <th scope="row">
                                <label for="status"><?php _e('狀態', 'namecardgen'); ?></label>
                            </th>
                            <td>
                                <select name="status" id="status">
                                    <option value="active" <?php selected($status, 'active'); ?>><?php _e('啟用', 'namecardgen'); ?></option>
                                    <option value="inactive" <?php selected($status, 'inactive'); ?>><?php _e('停用', 'namecardgen'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    
                    <div class="submit-buttons">
                        <?php submit_button($action === 'add' ? __('新增客戶', 'namecardgen') : __('更新客戶', 'namecardgen'), 'primary', 'submit_client'); ?>
                        <a href="<?php echo admin_url('admin.php?page=namecardgen-clients'); ?>" class="button button-secondary">
                            <?php _e('取消', 'namecardgen'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * 顯示客戶列表
     */
    public function display_clients_list() {
        $clients_class = new NamecardGen_Clients();
        $clients = $clients_class->get_all_clients();
        ?>
        
        <div class="wrap namecardgen-admin">
            <h1 class="wp-heading-inline"><?php _e('客戶管理', 'namecardgen'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=namecardgen-clients&action=add'); ?>" class="page-title-action">
                <?php _e('新增客戶', 'namecardgen'); ?>
            </a>
            
            <div class="namecardgen-card">
                <?php if (!empty($clients)) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('公司名稱', 'namecardgen'); ?></th>
                                <th><?php _e('聯絡人', 'namecardgen'); ?></th>
                                <th><?php _e('電子郵件', 'namecardgen'); ?></th>
                                <th><?php _e('電話', 'namecardgen'); ?></th>
                                <th><?php _e('狀態', 'namecardgen'); ?></th>
                                <th><?php _e('操作', 'namecardgen'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client) : ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <a href="<?php echo admin_url('admin.php?page=namecardgen-clients&action=edit&client_id=' . $client->id); ?>">
                                                <?php echo esc_html($client->company_name); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td><?php echo esc_html($client->contact_person); ?></td>
                                    <td><?php echo esc_html($client->email); ?></td>
                                    <td><?php echo esc_html($client->phone); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($client->status); ?>">
                                            <?php echo $client->status === 'active' ? __('啟用', 'namecardgen') : __('停用', 'namecardgen'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="<?php echo admin_url('admin.php?page=namecardgen-clients&action=edit&client_id=' . $client->id); ?>">
                                                    <?php _e('編輯', 'namecardgen'); ?>
                                                </a>
                                            </span>
                                            |
                                            <span class="delete">
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=namecardgen-clients&action=delete&client_id=' . $client->id), 'delete_client_' . $client->id); ?>" 
                                                   class="submitdelete" 
                                                   onclick="return confirm('<?php _e('確定要刪除這個客戶嗎？', 'namecardgen'); ?>')">
                                                    <?php _e('刪除', 'namecardgen'); ?>
                                                </a>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p><?php _e('暫無客戶資料', 'namecardgen'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * 處理客戶表單提交
     */
    public function handle_client_form_submit($data, $client_id = 0) {
        if (!wp_verify_nonce($data['namecardgen_nonce'], 'namecardgen_client_form')) {
            return new WP_Error('security_error', __('安全驗證失敗', 'namecardgen'));
        }
        
        $clients_class = new NamecardGen_Clients();
        
        $client_data = array(
            'company_name' => sanitize_text_field($data['company_name']),
            'contact_person' => sanitize_text_field($data['contact_person']),
            'email' => sanitize_email($data['email']),
            'phone' => sanitize_text_field($data['phone']),
            'address' => sanitize_textarea_field($data['address'])
        );
        
        if (isset($data['status'])) {
            $client_data['status'] = sanitize_text_field($data['status']);
        }
        
        if ($client_id) {
            // 更新現有客戶
            return $clients_class->update_client($client_id, $client_data);
        } else {
            // 新增客戶
            return $clients_class->create_client($client_data);
        }
    }

    /**
     * 顯示方案表單
     */
    public function display_plan_form($plan_id, $action) {
        $plans_class = new NamecardGen_Plans();
        $plan = $plan_id ? $plans_class->get_plan_by_id($plan_id) : null;
        
        $plan_name = $plan ? $plan->plan_name : '';
        $description = $plan ? $plan->description : '';
        $price = $plan ? $plan->price : 0.00;
        $duration_days = $plan ? $plan->duration_days : 30;
        $max_cards = $plan ? $plan->max_cards : 10;
        $features = $plan ? $plan->features : '';
        $status = $plan ? $plan->status : 'active';
        ?>
        
        <div class="wrap namecardgen-admin">
            <h1><?php echo $action === 'add' ? __('新增方案', 'namecardgen') : __('編輯方案', 'namecardgen'); ?></h1>
            
            <div class="namecardgen-card">
                <form method="post">
                    <?php wp_nonce_field('namecardgen_plan_form', 'namecardgen_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="plan_name"><?php _e('方案名稱', 'namecardgen'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" name="plan_name" id="plan_name" 
                                       value="<?php echo esc_attr($plan_name); ?>" 
                                       class="regular-text" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="description"><?php _e('方案描述', 'namecardgen'); ?></label>
                            </th>
                            <td>
                                <textarea name="description" id="description" rows="3" class="large-text"><?php echo esc_textarea($description); ?></textarea>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="price"><?php _e('價格 (TWD)', 'namecardgen'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="price" id="price" 
                                       value="<?php echo esc_attr($price); ?>" 
                                       step="0.01" min="0" class="small-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="duration_days"><?php _e('有效期 (天)', 'namecardgen'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="duration_days" id="duration_days" 
                                       value="<?php echo esc_attr($duration_days); ?>" 
                                       min="1" class="small-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="max_cards"><?php _e('最大名片數', 'namecardgen'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="max_cards" id="max_cards" 
                                       value="<?php echo esc_attr($max_cards); ?>" 
                                       min="1" class="small-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="features"><?php _e('功能列表', 'namecardgen'); ?></label>
                            </th>
                            <td>
                                <textarea name="features" id="features" rows="5" class="large-text"><?php echo esc_textarea($features); ?></textarea>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="status"><?php _e('狀態', 'namecardgen'); ?></label>
                            </th>
                            <td>
                                <select name="status" id="status">
                                    <option value="active" <?php selected($status, 'active'); ?>><?php _e('啟用', 'namecardgen'); ?></option>
                                    <option value="inactive" <?php selected($status, 'inactive'); ?>><?php _e('停用', 'namecardgen'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="submit-buttons">
                        <?php submit_button($action === 'add' ? __('新增方案', 'namecardgen') : __('更新方案', 'namecardgen'), 'primary', 'submit_plan'); ?>
                        <a href="<?php echo admin_url('admin.php?page=namecardgen-plans'); ?>" class="button button-secondary">
                            <?php _e('取消', 'namecardgen'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * 顯示方案列表
     */
    public function display_plans_list() {
        $plans_class = new NamecardGen_Plans();
        $plans = $plans_class->get_all_plans(array('include_inactive' => true));
        ?>
        
        <div class="wrap namecardgen-admin">
            <h1 class="wp-heading-inline"><?php _e('方案管理', 'namecardgen'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=namecardgen-plans&action=add'); ?>" class="page-title-action">
                <?php _e('新增方案', 'namecardgen'); ?>
            </a>
            
            <div class="namecardgen-card">
                <?php if (!empty($plans)) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('方案名稱', 'namecardgen'); ?></th>
                                <th><?php _e('價格', 'namecardgen'); ?></th>
                                <th><?php _e('有效期', 'namecardgen'); ?></th>
                                <th><?php _e('最大名片數', 'namecardgen'); ?></th>
                                <th><?php _e('狀態', 'namecardgen'); ?></th>
                                <th><?php _e('操作', 'namecardgen'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plans as $plan) : ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <a href="<?php echo admin_url('admin.php?page=namecardgen-plans&action=edit&plan_id=' . $plan->id); ?>">
                                                <?php echo esc_html($plan->plan_name); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($plan->price, 2); ?> TWD</strong>
                                    </td>
                                    <td>
                                        <?php echo sprintf(_n('%d 天', '%d 天', $plan->duration_days, 'namecardgen'), $plan->duration_days); ?>
                                    </td>
                                    <td>
                                        <?php echo number_format($plan->max_cards); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($plan->status); ?>">
                                            <?php echo $plan->status === 'active' ? __('啟用', 'namecardgen') : __('停用', 'namecardgen'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="<?php echo admin_url('admin.php?page=namecardgen-plans&action=edit&plan_id=' . $plan->id); ?>">
                                                    <?php _e('編輯', 'namecardgen'); ?>
                                                </a>
                                            </span>
                                            |
                                            <span class="delete">
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=namecardgen-plans&action=delete&plan_id=' . $plan->id), 'delete_plan_' . $plan->id); ?>" 
                                                   class="submitdelete" 
                                                   onclick="return confirm('<?php _e('確定要刪除這個方案嗎？', 'namecardgen'); ?>')">
                                                    <?php _e('刪除', 'namecardgen'); ?>
                                                </a>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p><?php _e('暫無方案資料', 'namecardgen'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * 處理方案表單提交
     */
    public function handle_plan_form_submit($data, $plan_id = 0) {
        if (!wp_verify_nonce($data['namecardgen_nonce'], 'namecardgen_plan_form')) {
            return new WP_Error('security_error', __('安全驗證失敗', 'namecardgen'));
        }
        
        $plans_class = new NamecardGen_Plans();
        
        $plan_data = array(
            'plan_name' => sanitize_text_field($data['plan_name']),
            'description' => sanitize_textarea_field($data['description']),
            'price' => floatval($data['price']),
            'duration_days' => intval($data['duration_days']),
            'max_cards' => intval($data['max_cards']),
            'features' => sanitize_textarea_field($data['features']),
            'status' => sanitize_text_field($data['status'])
        );
        
        if ($plan_id) {
            // 更新現有方案
            return $plans_class->update_plan($plan_id, $plan_data);
        } else {
            // 新增方案
            return $plans_class->create_plan($plan_data);
        }
    }
}
?>
