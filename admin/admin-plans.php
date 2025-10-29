<?php
/**
 * 方案管理頁面
 */

if (!defined('ABSPATH')) {
    exit;
}

// 處理表單提交
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$plan_id = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : 0;

$plans_class = new NamecardGen_Plans();
$message = '';
$message_type = '';

// 處理不同操作
switch ($action) {
    case 'add':
    case 'edit':
        // 處理表單提交
        if (isset($_POST['submit_plan'])) {
            $result = $this->handle_plan_form_submit($_POST, $plan_id);
            if (!is_wp_error($result)) {
                $message = $action === 'add' ? __('方案已成功新增', 'namecardgen') : __('方案已成功更新', 'namecardgen');
                $message_type = 'success';
                $action = 'list';
            } else {
                $message = $result->get_error_message();
                $message_type = 'error';
            }
        }
        break;
        
    case 'delete':
        if ($plan_id && wp_verify_nonce($_GET['_wpnonce'], 'delete_plan_' . $plan_id)) {
            $result = $plans_class->delete_plan($plan_id);
            if (!is_wp_error($result)) {
                $message = __('方案已成功刪除', 'namecardgen');
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
    $this->display_plan_form($plan_id, $action);
} else {
    $this->display_plans_list();
}

/**
 * 顯示方案列表
 */
private function display_plans_list() {
    $plans_class = new NamecardGen_Plans();
    $plans = $plans_class->get_all_plans(array('include_inactive' => true));
    ?>
    
    <div class="wrap namecardgen-admin">
        <h1 class="wp-heading-inline"><?php _e('方案管理', 'namecardgen'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=namecardgen-plans&action=add'); ?>" class="page-title-action">
            <?php _e('新增方案', 'namecardgen'); ?>
        </a>
        
        <hr class="wp-header-end">
        
        <!-- 方案表格 -->
        <div class="namecardgen-card">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-primary"><?php _e('方案名稱', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('價格', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('有效期', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('最大名片數', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('狀態', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('建立時間', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('操作', 'namecardgen'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($plans)) : ?>
                        <?php foreach ($plans as $plan) : ?>
                            <tr>
                                <td class="column-primary" data-colname="<?php _e('方案名稱', 'namecardgen'); ?>">
                                    <strong>
                                        <a href="<?php echo admin_url('admin.php?page=namecardgen-plans&action=edit&plan_id=' . $plan->id); ?>">
                                            <?php echo esc_html($plan->plan_name); ?>
                                        </a>
                                    </strong>
                                    <?php if ($plan->description) : ?>
                                        <p class="description"><?php echo esc_html($plan->description); ?></p>
                                    <?php endif; ?>
                                    <button type="button" class="toggle-row">
                                        <span class="screen-reader-text"><?php _e('顯示更多細節', 'namecardgen'); ?></span>
                                    </button>
                                </td>
                                <td data-colname="<?php _e('價格', 'namecardgen'); ?>">
                                    <strong><?php echo number_format($plan->price, 2); ?> TWD</strong>
                                </td>
                                <td data-colname="<?php _e('有效期', 'namecardgen'); ?>">
                                    <?php echo sprintf(_n('%d 天', '%d 天', $plan->duration_days, 'namecardgen'), $plan->duration_days); ?>
                                </td>
                                <td data-colname="<?php _e('最大名片數', 'namecardgen'); ?>">
                                    <?php echo number_format($plan->max_cards); ?>
                                </td>
                                <td data-colname="<?php _e('狀態', 'namecardgen'); ?>">
                                    <span class="status-badge status-<?php echo esc_attr($plan->status); ?>">
                                        <?php echo $this->get_plan_status_label($plan->status); ?>
                                    </span>
                                </td>
                                <td data-colname="<?php _e('建立時間', 'namecardgen'); ?>">
                                    <?php echo date('Y-m-d', strtotime($plan->created_at)); ?>
                                </td>
                                <td data-colname="<?php _e('操作', 'namecardgen'); ?>">
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
                    <?php else : ?>
                        <tr>
                            <td colspan="7" class="no-items">
                                <?php _e('暫無方案資料', 'namecardgen'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="namecardgen-card">
            <h3><?php _e('方案功能說明', 'namecardgen'); ?></h3>
            <ul>
                <li><strong><?php _e('基礎方案', 'namecardgen'); ?>:</strong> <?php _e('適合個人使用者，提供基本的名片生成功能', 'namecardgen'); ?></li>
                <li><strong><?php _e('專業方案', 'namecardgen'); ?>:</strong> <?php _e('適合中小企業，提供更多模板和高級功能', 'namecardgen'); ?></li>
                <li><strong><?php _e('企業方案', 'namecardgen'); ?>:</strong> <?php _e('適合大型企業，提供完整的名片解決方案和API整合', 'namecardgen'); ?></li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * 顯示方案表單
 */
private function display_plan_form($plan_id, $action) {
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
                            <p class="description"><?php _e('例如：基礎方案、專業方案、企業方案', 'namecardgen'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="description"><?php _e('方案描述', 'namecardgen'); ?></label>
                        </th>
                        <td>
                            <textarea name="description" id="description" rows="3" class="large-text"><?php echo esc_textarea($description); ?></textarea>
                            <p class="description"><?php _e('簡要描述此方案的特點和優勢', 'namecardgen'); ?></p>
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
                            <p class="description"><?php _e('設定為 0 表示免費方案', 'namecardgen'); ?></p>
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
                            <p class="description"><?php _e('方案的有效天數', 'namecardgen'); ?></p>
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
                            <p class="description"><?php _e('此方案允許生成的最大名片數量', 'namecardgen'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="features"><?php _e('功能列表', 'namecardgen'); ?></label>
                        </th>
                        <td>
                            <textarea name="features" id="features" rows="5" class="large-text"><?php echo esc_textarea($features); ?></textarea>
                            <p class="description">
                                <?php _e('每行一個功能，例如：', 'namecardgen'); ?><br>
                                <?php _e('基礎模板, PNG格式, 基本支援, 線上預覽', 'namecardgen'); ?>
                            </p>
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
                            <p class="description"><?php _e('停用的方案將不會顯示在前台', 'namecardgen'); ?></p>
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
        
        <?php if ($action === 'edit' && $plan) : ?>
        <div class="namecardgen-card">
            <h3><?php _e('功能預覽', 'namecardgen'); ?></h3>
            <?php
            $features_list = $plans_class->parse_features($features);
            if (!empty($features_list)) : ?>
                <ul class="features-preview">
                    <?php foreach ($features_list as $feature) : ?>
                        <li><?php echo esc_html($feature); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php _e('尚未設定功能列表', 'namecardgen'); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <style>
    .features-preview {
        list-style-type: disc;
        margin-left: 20px;
    }
    
    .features-preview li {
        margin-bottom: 5px;
    }
    
    .description {
        font-style: italic;
        color: #666;
    }
    </style>
    <?php
}

/**
 * 處理方案表單提交
 */
private function handle_plan_form_submit($data, $plan_id = 0) {
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

/**
 * 獲取方案狀態標籤
 */
private function get_plan_status_label($status) {
    $labels = array(
        'active' => __('啟用', 'namecardgen'),
        'inactive' => __('停用', 'namecardgen')
    );
    
    return isset($labels[$status]) ? $labels[$status] : $status;
}
?>
