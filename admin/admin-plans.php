<?php
/**
 * 方案管理頁面
 */

if (!defined('ABSPATH')) {
    exit;
}

// 獲取 admin 實例來調用方法
$admin_instance = NamecardGen_Admin::get_instance();

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
            // 使用 admin_instance 而不是 $this
            $result = $admin_instance->handle_plan_form_submit($_POST, $plan_id);
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
    $admin_instance->display_plan_form($plan_id, $action);
} else {
    $admin_instance->display_plans_list();
}
?>
