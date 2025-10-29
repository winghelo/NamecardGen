<?php
/**
 * 名片管理頁面
 */

if (!defined('ABSPATH')) {
    exit;
}

// 獲取名片資料
global $wpdb;
$table_name = $wpdb->prefix . 'namecardgen_namecards';

// 分頁參數
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 搜尋條件
$where = "WHERE 1=1";
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

if ($search) {
    $where .= $wpdb->prepare(
        " AND (c.company_name LIKE %s OR c.contact_person LIKE %s OR nc.design_template LIKE %s)",
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%'
    );
}

// 獲取名片列表
$namecards = $wpdb->get_results(
    $wpdb->prepare("
        SELECT nc.*, c.company_name, c.contact_person, c.email, p.plan_name 
        FROM $table_name nc
        LEFT JOIN {$wpdb->prefix}namecardgen_clients c ON nc.client_id = c.id
        LEFT JOIN {$wpdb->prefix}namecardgen_plans p ON nc.plan_id = p.id
        $where 
        ORDER BY nc.created_at DESC 
        LIMIT %d OFFSET %d
    ", $per_page, $offset)
);

// 獲取總數
$total_namecards = $wpdb->get_var("SELECT COUNT(*) FROM $table_name nc $where");
$total_pages = ceil($total_namecards / $per_page);
?>

<div class="wrap namecardgen-admin">
    <h1 class="wp-heading-inline"><?php _e('名片管理', 'namecardgen'); ?></h1>
    
    <?php if ($search) : ?>
        <span class="subtitle"><?php printf(__('搜尋結果: %s', 'namecardgen'), esc_html($search)); ?></span>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <!-- 搜尋表單 -->
    <div class="namecardgen-search-box">
        <form method="get">
            <input type="hidden" name="page" value="namecardgen-cards">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('搜尋名片...', 'namecardgen'); ?>">
            <?php submit_button(__('搜尋', 'namecardgen'), 'button', '', false); ?>
        </form>
    </div>
    
    <!-- 名片統計 -->
    <div class="namecardgen-stats-container" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
        <div class="namecardgen-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-id"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_namecards); ?></h3>
                <p><?php _e('總名片數', 'namecardgen'); ?></p>
            </div>
        </div>
        
        <div class="namecardgen-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-yes"></span>
            </div>
            <div class="stat-content">
                <h3><?php 
                    $completed = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'completed'");
                    echo number_format($completed);
                ?></h3>
                <p><?php _e('已完成', 'namecardgen'); ?></p>
            </div>
        </div>
        
        <div class="namecardgen-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="stat-content">
                <h3><?php 
                    $draft = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'draft'");
                    echo number_format($draft);
                ?></h3>
                <p><?php _e('草稿', 'namecardgen'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- 名片表格 -->
    <div class="namecardgen-card">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="column-primary"><?php _e('客戶資訊', 'namecardgen'); ?></th>
                    <th scope="col"><?php _e('方案', 'namecardgen'); ?></th>
                    <th scope="col"><?php _e('模板', 'namecardgen'); ?></th>
                    <th scope="col"><?php _e('狀態', 'namecardgen'); ?></th>
                    <th scope="col"><?php _e('建立時間', 'namecardgen'); ?></th>
                    <th scope="col"><?php _e('操作', 'namecardgen'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($namecards)) : ?>
                    <?php foreach ($namecards as $namecard) : 
                        $card_data = json_decode($namecard->card_data, true);
                    ?>
                        <tr>
                            <td class="column-primary" data-colname="<?php _e('客戶資訊', 'namecardgen'); ?>">
                                <strong><?php echo esc_html($namecard->company_name); ?></strong>
                                <?php if ($namecard->contact_person) : ?>
                                    <br><small><?php echo sprintf(__('聯絡人: %s', 'namecardgen'), esc_html($namecard->contact_person)); ?></small>
                                <?php endif; ?>
                                <?php if ($namecard->email) : ?>
                                    <br><small><?php echo esc_html($namecard->email); ?></small>
                                <?php endif; ?>
                                <button type="button" class="toggle-row">
                                    <span class="screen-reader-text"><?php _e('顯示更多細節', 'namecardgen'); ?></span>
                                </button>
                            </td>
                            <td data-colname="<?php _e('方案', 'namecardgen'); ?>">
                                <?php echo esc_html($namecard->plan_name); ?>
                            </td>
                            <td data-colname="<?php _e('模板', 'namecardgen'); ?>">
                                <?php echo esc_html($namecard->design_template ?: 'default'); ?>
                            </td>
                            <td data-colname="<?php _e('狀態', 'namecardgen'); ?>">
                                <span class="status-badge status-<?php echo esc_attr($namecard->status); ?>">
                                    <?php echo $admin_instance->get_namecard_status_label($namecard->status); ?>
                                </span>
                            </td>
                            <td data-colname="<?php _e('建立時間', 'namecardgen'); ?>">
                                <?php echo date('Y-m-d H:i', strtotime($namecard->created_at)); ?>
                            </td>
                            <td data-colname="<?php _e('操作', 'namecardgen'); ?>">
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="#" class="view-namecard" data-namecard-id="<?php echo $namecard->id; ?>">
                                            <?php _e('預覽', 'namecardgen'); ?>
                                        </a>
                                    </span>
                                    |
                                    <span class="download">
                                        <?php if ($namecard->pdf_path) : ?>
                                            <a href="<?php echo esc_url($namecard->pdf_path); ?>" target="_blank" download>
                                                <?php _e('下載', 'namecardgen'); ?>
                                            </a>
                                        <?php else : ?>
                                            <span class="disabled"><?php _e('下載', 'namecardgen'); ?></span>
                                        <?php endif; ?>
                                    </span>
                                    |
                                    <span class="delete">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=namecardgen-cards&action=delete&namecard_id=' . $namecard->id), 'delete_namecard_' . $namecard->id); ?>" 
                                           class="submitdelete" 
                                           onclick="return confirm('<?php _e('確定要刪除這張名片嗎？', 'namecardgen'); ?>')">
                                            <?php _e('刪除', 'namecardgen'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" class="no-items">
                            <?php _e('暫無名片資料', 'namecardgen'); ?>
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
    
    <!-- 名片預覽模態框 -->
    <div id="namecard-preview-modal" style="display: none;">
        <div class="namecard-preview-content">
            <div class="namecard-preview-header">
                <h3><?php _e('名片預覽', 'namecardgen'); ?></h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="namecard-preview-body">
                <div id="namecard-preview-container">
                    <!-- 預覽內容將在這裡動態載入 -->
                </div>
            </div>
            <div class="namecard-preview-footer">
                <button type="button" class="button button-primary download-pdf"><?php _e('下載PDF', 'namecardgen'); ?></button>
                <button type="button" class="button close-modal"><?php _e('關閉', 'namecardgen'); ?></button>
            </div>
        </div>
    </div>
</div>

<style>
.namecard-preview-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    margin: 50px auto;
    position: relative;
}

.namecard-preview-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.namecard-preview-header h3 {
    margin: 0;
}

.close-modal {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.namecard-preview-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

.namecard-preview-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

#namecard-preview-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    display: flex;
    align-items: flex-start;
    justify-content: center;
}

.namecard-preview {
    border: 1px solid #ddd;
    padding: 20px;
    margin-bottom: 20px;
    background: white;
}

.namecard-field {
    margin-bottom: 10px;
}

.namecard-field label {
    font-weight: bold;
    display: inline-block;
    width: 100px;
}

.row-actions .disabled {
    color: #ccc;
    cursor: not-allowed;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-completed {
    background: #d4edda;
    color: #155724;
}

.status-draft {
    background: #fff3cd;
    color: #856404;
}

.status-processing {
    background: #cce7ff;
    color: #004085;
}
</style>

<script>
jQuery(document).ready(function($) {
    // 名片預覽功能
    $('.view-namecard').on('click', function(e) {
        e.preventDefault();
        var namecardId = $(this).data('namecard-id');
        
        // 顯示載入中
        $('#namecard-preview-container').html('<p>載入中...</p>');
        $('#namecard-preview-modal').show();
        
        // AJAX 載入名片資料
        $.ajax({
            url: namecardgen_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'namecardgen_admin_action',
                sub_action: 'get_namecard_details',
                namecard_id: namecardId,
                nonce: namecardgen_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#namecard-preview-container').html(response.data.html);
                } else {
                    $('#namecard-preview-container').html('<p>載入失敗: ' + response.data.message + '</p>');
                }
            },
            error: function() {
                $('#namecard-preview-container').html('<p>載入失敗，請重試</p>');
            }
        });
    });
    
    // 關閉模態框
    $('.close-modal').on('click', function() {
        $('#namecard-preview-modal').hide();
    });
    
    // 點擊背景關閉
    $('#namecard-preview-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
});
</script>
<?php

/**
 * 獲取名片狀態標籤
 */
function get_namecard_status_label($status) {
    $labels = array(
        'draft' => __('草稿', 'namecardgen'),
        'processing' => __('處理中', 'namecardgen'),
        'completed' => __('已完成', 'namecardgen'),
        'failed' => __('失敗', 'namecardgen')
    );
    
    return isset($labels[$status]) ? $labels[$status] : $status;
}
?>
