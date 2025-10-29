<?php
/**
 * 客戶管理頁面
 */

if (!defined('ABSPATH')) {
    exit;
}

// 獲取 admin 實例來調用方法
$admin_instance = NamecardGen_Admin::get_instance();

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
            // 使用 admin_instance 而不是 $this
            $result = $admin_instance->handle_client_form_submit($_POST, $client_id);
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
    $admin_instance->display_client_form($client_id, $action);
} else {
    $admin_instance->display_clients_list();
}
?>
