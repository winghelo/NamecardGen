<?php
/**
 * 後台主頁面
 */

if (!defined('ABSPATH')) {
    exit;
}

// 獲取統計數據
$stats = NamecardGen_Admin::get_instance()->get_stats();
?>

<div class="wrap namecardgen-admin">
    <h1><?php _e('名片生成器 - 總覽', 'namecardgen'); ?></h1>
    
    <div class="namecardgen-stats-container">
        <div class="namecardgen-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html($stats['total_clients']); ?></h3>
                <p><?php _e('總客戶數', 'namecardgen'); ?></p>
            </div>
        </div>
        
        <div class="namecardgen-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-yes"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html($stats['active_clients']); ?></h3>
                <p><?php _e('活躍客戶', 'namecardgen'); ?></p>
            </div>
        </div>
        
        <div class="namecardgen-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-admin-generic"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html($stats['total_plans']); ?></h3>
                <p><?php _e('方案數量', 'namecardgen'); ?></p>
            </div>
        </div>
        
        <div class="namecardgen-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-id"></span>
            </div>
            <div class="stat-content">
                <h3>0</h3>
                <p><?php _e('生成名片', 'namecardgen'); ?></p>
            </div>
        </div>
    </div>

    <div class="namecardgen-dashboard-content">
        <div class="namecardgen-dashboard-row">
            <div class="namecardgen-dashboard-col">
                <div class="namecardgen-card">
                    <h2><?php _e('快速操作', 'namecardgen'); ?></h2>
                    <div class="quick-actions">
                        <a href="<?php echo admin_url('admin.php?page=namecardgen-clients&action=add'); ?>" class="button button-primary">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e('新增客戶', 'namecardgen'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=namecardgen-plans&action=add'); ?>" class="button">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php _e('新增方案', 'namecardgen'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=namecardgen-cards'); ?>" class="button">
                            <span class="dashicons dashicons-id"></span>
                            <?php _e('管理名片', 'namecardgen'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="namecardgen-card">
                    <h2><?php _e('最近客戶', 'namecardgen'); ?></h2>
                    <?php
                    $clients_class = new NamecardGen_Clients();
                    $recent_clients = $clients_class->get_all_clients(array('per_page' => 5));
                    
                    if (!empty($recent_clients)) : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('公司名稱', 'namecardgen'); ?></th>
                                    <th><?php _e('聯絡人', 'namecardgen'); ?></th>
                                    <th><?php _e('電子郵件', 'namecardgen'); ?></th>
                                    <th><?php _e('建立時間', 'namecardgen'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_clients as $client) : ?>
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
                                        <td><?php echo date('Y-m-d H:i', strtotime($client->created_at)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p><?php _e('暫無客戶資料', 'namecardgen'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="namecardgen-dashboard-col">
                <div class="namecardgen-card">
                    <h2><?php _e('方案列表', 'namecardgen'); ?></h2>
                    <?php
                    $plans_class = new NamecardGen_Plans();
                    $plans = $plans_class->get_all_plans();
                    
                    if (!empty($plans)) : ?>
                        <div class="plans-list">
                            <?php foreach ($plans as $plan) : ?>
                                <div class="plan-item">
                                    <div class="plan-header">
                                        <h4><?php echo esc_html($plan->plan_name); ?></h4>
                                        <span class="plan-price"><?php echo number_format($plan->price, 2); ?> TWD</span>
                                    </div>
                                    <div class="plan-description">
                                        <?php echo esc_html($plan->description); ?>
                                    </div>
                                    <div class="plan-features">
                                        <span class="feature"><?php echo sprintf(__('有效期: %d 天', 'namecardgen'), $plan->duration_days); ?></span>
                                        <span class="feature"><?php echo sprintf(__('最大名片數: %d', 'namecardgen'), $plan->max_cards); ?></span>
                                    </div>
                                    <div class="plan-actions">
                                        <a href="<?php echo admin_url('admin.php?page=namecardgen-plans&action=edit&plan_id=' . $plan->id); ?>" class="button button-small">
                                            <?php _e('編輯', 'namecardgen'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p><?php _e('暫無方案資料', 'namecardgen'); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="namecardgen-card">
                    <h2><?php _e('系統資訊', 'namecardgen'); ?></h2>
                    <table class="system-info-table">
                        <tr>
                            <th><?php _e('外掛版本', 'namecardgen'); ?></th>
                            <td><?php echo NAMECARDGEN_VERSION; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('WordPress 版本', 'namecardgen'); ?></th>
                            <td><?php echo get_bloginfo('version'); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('PHP 版本', 'namecardgen'); ?></th>
                            <td><?php echo phpversion(); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('資料庫版本', 'namecardgen'); ?></th>
                            <td><?php echo get_option('namecardgen_version', '1.0.0'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.namecardgen-stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.namecardgen-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.namecardgen-stat-card .stat-icon {
    font-size: 2em;
    color: #2271b1;
}

.namecardgen-stat-card .stat-content h3 {
    margin: 0 0 5px 0;
    font-size: 1.8em;
    font-weight: bold;
}

.namecardgen-stat-card .stat-content p {
    margin: 0;
    color: #646970;
}

.namecardgen-dashboard-content {
    margin-top: 30px;
}

.namecardgen-dashboard-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.namecardgen-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.namecardgen-card h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.quick-actions .button {
    display: flex;
    align-items: center;
    gap: 5px;
}

.plans-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.plan-item {
    border: 1px solid #e1e1e1;
    border-radius: 4px;
    padding: 15px;
}

.plan-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.plan-header h4 {
    margin: 0;
}

.plan-price {
    font-weight: bold;
    color: #2271b1;
}

.plan-description {
    color: #666;
    margin-bottom: 10px;
    font-size: 0.9em;
}

.plan-features {
    display: flex;
    gap: 15px;
    margin-bottom: 10px;
    font-size: 0.85em;
}

.plan-features .feature {
    background: #f6f7f7;
    padding: 2px 8px;
    border-radius: 3px;
}

.system-info-table {
    width: 100%;
}

.system-info-table th,
.system-info-table td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.system-info-table th {
    font-weight: 600;
    width: 40%;
}

@media (max-width: 1200px) {
    .namecardgen-dashboard-row {
        grid-template-columns: 1fr;
    }
}
</style>
