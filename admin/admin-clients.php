<?php
/**
 * 客戶管理頁面
 */

if (!defined('ABSPATH')) {
    exit;
}

// 處理表單提交
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

$clients_class = new NamecardGen_Clients();
$message = '';
$message_type = '';

// 處理不同操作
switch ($action) {
    case 'add':
    case 'edit':
        // 處理表單提交
        if (isset($_POST['submit_client'])) {
            $result = $this->handle_client_form_submit($_POST, $client_id);
            if (!is_wp_error($result)) {
                $message = $action === 'add' ? __('客戶已成功新增', 'namecardgen') : __('客戶已成功更新', 'namecardgen');
                $message_type = 'success';
                $action = 'list';
            } else {
                $message = $result->get_error_message();
                $message_type = 'error';
            }
        }
        break;
        
    case 'delete':
        if ($client_id && wp_verify_nonce($_GET['_wpnonce'], 'delete_client_' . $client_id)) {
            $result = $clients_class->delete_client($client_id);
            if (!is_wp_error($result)) {
                $message = __('客戶已成功刪除', 'namecardgen');
                $message_type = 'success';
            } else {
                $message = $result->get_error_message();
                $message_type = 'error';
            }
        }
        $action = 'list';
        break;
}

// 顯示訊息
if ($message) {
    echo '<div class="notice notice-' . esc_attr($message_type) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
}

// 顯示對應的頁面
if ($action === 'add' || $action === 'edit') {
    $this->display_client_form($client_id, $action);
} else {
    $this->display_clients_list();
}

/**
 * 顯示客戶列表
 */
private function display_clients_list() {
    $clients_class = new NamecardGen_Clients();
    
    // 分頁參數
    $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    
    $args = array(
        'page' => $page,
        'per_page' => $per_page,
        'search' => $search
    );
    
    $clients = $clients_class->get_all_clients($args);
    $total_clients = $clients_class->get_clients_count();
    $total_pages = ceil($total_clients / $per_page);
    ?>
    
    <div class="wrap namecardgen-admin">
        <h1 class="wp-heading-inline"><?php _e('客戶管理', 'namecardgen'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=namecardgen-clients&action=add'); ?>" class="page-title-action">
            <?php _e('新增客戶', 'namecardgen'); ?>
        </a>
        
        <?php if ($search) : ?>
            <span class="subtitle"><?php printf(__('搜尋結果: %s', 'namecardgen'), esc_html($search)); ?></span>
        <?php endif; ?>
        
        <hr class="wp-header-end">
        
        <!-- 搜尋表單 -->
        <div class="namecardgen-search-box">
            <form method="get">
                <input type="hidden" name="page" value="namecardgen-clients">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('搜尋客戶...', 'namecardgen'); ?>">
                <?php submit_button(__('搜尋', 'namecardgen'), 'button', '', false); ?>
            </form>
        </div>
        
        <!-- 客戶表格 -->
        <div class="namecardgen-card">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-primary"><?php _e('公司名稱', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('聯絡人', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('電子郵件', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('電話', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('建立時間', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('狀態', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('操作', 'namecardgen'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($clients)) : ?>
                        <?php foreach ($clients as $client) : ?>
                            <tr>
                                <td class="column-primary" data-colname="<?php _e('公司名稱', 'namecardgen'); ?>">
                                    <strong>
                                        <a href="<?php echo admin_url('admin.php?page=namecardgen-clients&action=edit&client_id=' . $client->id); ?>">
                                            <?php echo esc_html($client->company_name); ?>
                                        </a>
                                    </strong>
                                    <button type="button" class="toggle-row">
                                        <span class="screen-reader-text"><?php _e('顯示更多細節', 'namecardgen'); ?></span>
                                    </button>
                                </td>
                                <td data-colname="<?php _e('聯絡人', 'namecardgen'); ?>"><?php echo esc_html($client->contact_person); ?></td>
                                <td data-colname="<?php _e('電子郵件', 'namecardgen'); ?>"><?php echo esc_html($client->email); ?></td>
                                <td data-colname="<?php _e('電話', 'namecardgen'); ?>"><?php echo esc_html($client->phone); ?></td>
                                <td data-colname="<?php _e('建立時間', 'namecardgen'); ?>"><?php echo date('Y-m-d H:i', strtotime($client->created_at)); ?></td>
                                <td data-colname="<?php _e('狀態', 'namecardgen'); ?>">
                                    <span class="status-badge status-<?php echo esc_attr($client->status); ?>">
                                        <?php echo $this->get_status_label($client->status); ?>
                                    </span>
                                </td>
                                <td data-colname="<?php _e('操作', 'namecardgen'); ?>">
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
                    <?php else : ?>
                        <tr>
                            <td colspan="7" class="no-items">
                                <?php _e('暫無客戶資料', 'namecardgen'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 分頁 -->
        <?php if ($total_pages > 1) : ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $page
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * 顯示客戶表單
 */
private function display_client_form($client_id, $action) {
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
 * 處理客戶表單提交
 */
private function handle_client_form_submit($data, $client_id = 0) {
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
 * 獲取狀態標籤
 */
private function get_status_label($status) {
    $labels = array(
        'active' => __('啟用', 'namecardgen'),
        'inactive' => __('停用', 'namecardgen'),
        'deleted' => __('已刪除', 'namecardgen')
    );
    
    return isset($labels[$status]) ? $labels[$status] : $status;
}
?>
