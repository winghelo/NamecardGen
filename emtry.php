<?php
if (!defined('ABSPATH')) {
    exit;
}

class NamecardGen_Admin_Pages {
    
    private $clients;
    private $plans;
    private $database;
    
    public function __construct($clients, $plans, $database) {
        $this->clients = $clients;
        $this->plans = $plans;
        $this->database = $database;
    }
    
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_form_submissions'));
    }
    
    public function add_admin_menu() {
        // 主選單
        add_menu_page(
            'NamecardGen 管理',
            'NamecardGen',
            'manage_options',
            'namecardgen-main',
            array($this, 'display_main_page'),
            'dashicons-id',
            30
        );
        
        // 子選單 - 客戶管理
        add_submenu_page(
            'namecardgen-main',
            '客戶管理',
            '客戶管理',
            'manage_options',
            'namecardgen-clients',
            array($this, 'display_clients_page')
        );
        
        // 子選單 - 計劃管理
        add_submenu_page(
            'namecardgen-main',
            '計劃管理',
            '計劃管理',
            'manage_options',
            'namecardgen-plans',
            array($this, 'display_plans_page')
        );
        
        // 子選單 - 所有名片
        add_submenu_page(
            'namecardgen-main',
            '所有名片',
            '所有名片',
            'manage_options',
            'namecardgen-all-cards',
            array($this, 'display_all_cards_page')
        );
    }
    
    public function handle_form_submissions() {
        // 處理添加客戶
        if (isset($_POST['add_client']) && wp_verify_nonce($_POST['client_nonce'], 'add_client_action')) {
            $plan_id = !empty($_POST['client_plan']) ? intval($_POST['client_plan']) : NULL;
            $expired_at = NULL;
            
            // 如果有選擇計劃，計算到期時間
            if ($plan_id) {
                $plan = $this->plans->get_plan($plan_id);
                if ($plan && $plan->valid_days > 0) {
                    $expired_at = date('Y-m-d H:i:s', strtotime("+{$plan->valid_days} days"));
                }
            }
            
            $client_data = array(
                'name' => sanitize_text_field($_POST['client_name']),
                'email' => sanitize_email($_POST['client_email']),
                'phone' => sanitize_text_field($_POST['client_phone']),
                'company' => sanitize_text_field($_POST['client_company']),
                'plan_id' => $plan_id,
                'expired_at' => $expired_at
            );
            
            $result = $this->clients->add_client($client_data);
            
            if ($result) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>✅ 客戶添加成功！</p></div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>❌ 客戶添加失敗，請檢查資料是否正確。</p></div>';
                });
            }
        }
        
        // 處理添加計劃
        if (isset($_POST['add_plan']) && wp_verify_nonce($_POST['plan_nonce'], 'add_plan_action')) {
            $plan_data = array(
                'plan_name' => sanitize_text_field($_POST['plan_name']),
                'price' => floatval($_POST['plan_price']),
                'description' => sanitize_textarea_field($_POST['plan_description']),
                'valid_days' => intval($_POST['plan_days']),
                'features' => sanitize_textarea_field($_POST['plan_features']),
                'max_cards' => intval($_POST['plan_max_cards']),
                'is_active' => isset($_POST['plan_active']) ? 1 : 0
            );
            
            $result = $this->plans->add_plan($plan_data);
            
            if ($result) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>✅ 計劃添加成功！</p></div>';
                });
            }
        }
        
        // 處理更新客戶計劃
        if (isset($_POST['update_client_plan']) && wp_verify_nonce($_POST['update_plan_nonce'], 'update_client_plan_action')) {
            $client_id = intval($_POST['client_id']);
            $plan_id = !empty($_POST['client_plan']) ? intval($_POST['client_plan']) : NULL;
            
            $result = $this->clients->update_client_plan($client_id, $plan_id);
            
            if ($result !== false) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>✅ 客戶計劃更新成功！</p></div>';
                });
            }
        }
        
        // 處理刪除客戶
        if (isset($_GET['delete_client']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_client')) {
            $client_id = intval($_GET['delete_client']);
            $result = $this->clients->delete_client($client_id);
            
            if ($result) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>✅ 客戶刪除成功！</p></div>';
                });
            }
        }
        
        // 處理刪除計劃
        if (isset($_GET['delete_plan']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_plan')) {
            $plan_id = intval($_GET['delete_plan']);
            $result = $this->plans->delete_plan($plan_id);
            
            if ($result) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>✅ 計劃刪除成功！</p></div>';
                });
            } else {
                $client_count = $this->plans->get_plan_client_count($plan_id);
                add_action('admin_notices', function() use ($client_count) {
                    echo '<div class="notice notice-error is-dismissible"><p>❌ 無法刪除計劃，還有 ' . $client_count . ' 個客戶使用此計劃。</p></div>';
                });
            }
        }
    }
    
    public function display_main_page() {
        $client_stats = $this->clients->get_client_stats();
        $plan_stats = $this->database->get_plan_stats();
        ?>
        <div class="wrap">
            <h1>🎴 NamecardGen 名片生成系統 v2.0</h1>
            
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px; margin: 20px 0;">
                <h2 style="color: white; margin-top: 0;">歡迎使用 NamecardGen 2.0</h2>
                <p style="font-size: 16px;">多檔案專業版 - 強化計劃管理系統</p>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0;">
                <div style="background: #e7f3ff; padding: 25px; border-radius: 8px; border-left: 4px solid #007cba; text-align: center;">
                    <div style="font-size: 36px; margin-bottom: 10px;">👥</div>
                    <h3 style="margin: 0 0 10px 0;">客戶總數</h3>
                    <div style="font-size: 32px; font-weight: bold; color: #007cba;"><?php echo $client_stats['total_clients']; ?></div>
                </div>
                
                <div style="background: #fff3cd; padding: 25px; border-radius: 8px; border-left: 4px solid #ffc107; text-align: center;">
                    <div style="font-size: 36px; margin-bottom: 10px;">📊</div>
                    <h3 style="margin: 0 0 10px 0;">今日新增</h3>
                    <div style="font-size: 32px; font-weight: bold; color: #ffc107;"><?php echo $client_stats['today_clients']; ?></div>
                </div>
                
                <div style="background: #d4edda; padding: 25px; border-radius: 8px; border-left: 4px solid #28a745; text-align: center;">
                    <div style="font-size: 36px; margin-bottom: 10px;">🎴</div>
                    <h3 style="margin: 0 0 10px 0;">本月新增</h3>
                    <div style="font-size: 32px; font-weight: bold; color: #28a745;"><?php echo $client_stats['month_clients']; ?></div>
                </div>
            </div>

            <!-- 計劃使用統計 -->
            <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e0e0e0; margin: 20px 0;">
                <h3>📈 計劃使用統計</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                    <?php foreach ($plan_stats as $stat) : ?>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center; border-left: 4px solid #667eea;">
                        <div style="font-size: 20px; font-weight: bold; color: #333;"><?php echo $stat->client_count; ?></div>
                        <div style="color: #666; font-size: 14px;"><?php echo esc_html($stat->plan_name); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 30px 0;">
                <div style="background: #e7f3ff; padding: 25px; border-radius: 8px; border-left: 4px solid #007cba;">
                    <h3>👥 客戶管理</h3>
                    <p>管理所有客戶資料，查看客戶計劃與狀態。</p>
                    <a href="<?php echo admin_url('admin.php?page=namecardgen-clients'); ?>" class="button button-primary">管理客戶</a>
                </div>
                
                <div style="background: #fff3cd; padding: 25px; border-radius: 8px; border-left: 4px solid #ffc107;">
                    <h3>📊 計劃管理</h3>
                    <p>設定不同方案計劃與價格策略。</p>
                    <a href="<?php echo admin_url('admin.php?page=namecardgen-plans'); ?>" class="button">管理計劃</a>
                </div>
                
                <div style="background: #f8d7da; padding: 25px; border-radius: 8px; border-left: 4px solid #dc3545;">
                    <h3>🎴 所有名片</h3>
                    <p>查看所有已生成的名片與統計資料。</p>
                    <a href="<?php echo admin_url('admin.php?page=namecardgen-all-cards'); ?>" class="button">查看名片</a>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function display_clients_page() {
        $clients = $this->clients->get_all_clients();
        $plans = $this->plans->get_active_plans();
        ?>
        <div class="wrap">
            <h1>👥 客戶管理</h1>
            
            <!-- 添加客戶表單 -->
            <div style="background: #e7f3ff; padding: 25px; border-radius: 8px; margin-bottom: 30px;">
                <h3>➕ 添加新客戶</h3>
                <form method="post" action="">
                    <?php wp_nonce_field('add_client_action', 'client_nonce'); ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">客戶姓名 *</label>
                            <input type="text" name="client_name" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">電子郵件 *</label>
                            <input type="email" name="client_email" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">電話號碼</label>
                            <input type="tel" name="client_phone" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">公司名稱</label>
                            <input type="text" name="client_company" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">選擇計劃</label>
                            <select name="client_plan" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="">-- 請選擇計劃 --</option>
                                <?php foreach ($plans as $plan) : ?>
                                <option value="<?php echo $plan->id; ?>">
                                    <?php echo esc_html($plan->plan_name); ?> - $<?php echo number_format($plan->price, 2); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color: #666;">選擇客戶適用的收費計劃</small>
                        </div>
                    </div>
                    <button type="submit" name="add_client" style="background: #007cba; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 15px;">
                        添加客戶
                    </button>
                </form>
            </div>

            <!-- 客戶列表 -->
            <div style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px;">
                <h3>📋 客戶列表 (<?php echo count($clients); ?> 位客戶)</h3>
                <?php if ($clients) : ?>
                <table class="wp-list-table widefat fixed striped" style="width: 100%;">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">客戶姓名</th>
                            <th width="20%">電子郵件</th>
                            <th width="15%">使用計劃</th>
                            <th width="10%">狀態</th>
                            <th width="15%">到期時間</th>
                            <th width="20%">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client) : 
                            $status = $this->clients->get_client_status($client);
                            $plan_name = $client->plan_name ?: '<span style="color: #6c757d;">未選擇</span>';
                            $expired_text = $client->expired_at ? date('Y-m-d', strtotime($client->expired_at)) : '--';
                        ?>
                        <tr>
                            <td><?php echo $client->id; ?></td>
                            <td>
                                <strong><?php echo esc_html($client->name); ?></strong>
                                <?php if ($client->company) : ?>
                                <br><small style="color: #666;"><?php echo esc_html($client->company); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($client->email); ?></td>
                            <td><?php echo $plan_name; ?></td>
                            <td>
                                <span class="<?php echo $status['class']; ?>" style="font-weight: bold;">
                                    <?php echo $status['text']; ?>
                                </span>
                            </td>
                            <td><?php echo $expired_text; ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('update_client_plan_action', 'update_plan_nonce'); ?>
                                    <input type="hidden" name="client_id" value="<?php echo $client->id; ?>">
                                    <select name="client_plan" style="padding: 4px; font-size: 12px; margin-right: 5px;" onchange="this.form.submit()">
                                        <option value="">變更計劃</option>
                                        <?php foreach ($plans as $plan) : ?>
                                        <option value="<?php echo $plan->id; ?>" <?php selected($client->plan_id, $plan->id); ?>>
                                            <?php echo esc_html($plan->plan_name); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="update_client_plan" value="1">
                                </form>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=namecardgen-clients&delete_client=' . $client->id), 'delete_client'); ?>" 
                                   class="button button-small" 
                                   onclick="return confirm('確定要刪除這個客戶嗎？')"
                                   style="background: #dc3545; color: white; border: none; font-size: 12px; padding: 4px 8px;">
                                   刪除
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                <div style="text-align: center; padding: 40px;">
                    <div style="color: #666; font-size: 16px;">
                        <p>📝 暫無客戶資料</p>
                        <p>請使用上方表單添加第一個客戶</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <style>
        .status-active { color: #28a745; }
        .status-inactive { color: #dc3545; }
        .status-expired { color: #ffc107; }
        </style>
        <?php
    }
    
    public function display_plans_page() {
        $plans = $this->plans->get_all_plans();
        ?>
        <div class="wrap">
            <h1>📊 計劃管理</h1>
            
            <div style="background: #fff3cd; padding: 25px; border-radius: 8px; margin-bottom: 30px;">
                <h3>➕ 添加新計劃</h3>
                <form method="post" action="">
                    <?php wp_nonce_field('add_plan_action', 'plan_nonce'); ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">計劃名稱 *</label>
                            <input type="text" name="plan_name" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="例如: 基礎版">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">價格 (HKD)</label>
                            <input type="number" name="plan_price" step="0.01" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="0.00" value="0.00">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">有效天數</label>
                            <input type="number" name="plan_days" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="30" value="30">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">最大名片數</label>
                            <input type="number" name="plan_max_cards" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="0表示無限" value="0">
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">計劃描述</label>
                            <textarea name="plan_description" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="描述此計劃的特點和服務..."></textarea>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">功能特色</label>
                            <textarea name="plan_features" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="列出此計劃包含的功能，用逗號分隔"></textarea>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" name="plan_active" value="1" checked> 啟用此計劃
                            </label>
                        </div>
                    </div>
                    <button type="submit" name="add_plan" style="background: #ffc107; color: black; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 15px;">
                        添加計劃
                    </button>
                </form>
            </div>

            <div style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px;">
                <h3>📈 計劃列表 (<?php echo count($plans); ?> 個計劃)</h3>
                <?php if ($plans) : ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; margin-top: 20px;">
                    <?php foreach ($plans as $plan) : 
                        $client_count = $this->plans->get_plan_client_count($plan->id);
                        $status_class = $plan->is_active ? 'status-active' : 'status-inactive';
                        $status_text = $plan->is_active ? '✅ 啟用中' : '❌ 已停用';
                    ?>
                    <div style="border: 2px solid <?php echo $plan->is_active ? '#28a745' : '#6c757d'; ?>; border-radius: 8px; padding: 20px; background: #f8f9fa;">
                        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 15px;">
                            <h3 style="margin: 0; color: #333;"><?php echo esc_html($plan->plan_name); ?></h3>
                            <div style="font-size: 24px; font-weight: bold; color: #007cba;">
                                $<?php echo number_format($plan->price, 2); ?>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <p style="margin: 0 0 10px 0; color: #666;"><?php echo esc_html($plan->description); ?></p>
                            <?php if ($plan->features) : ?>
                            <div style="background: white; padding: 10px; border-radius: 4px; border-left: 3px solid #007cba;">
                                <strong>包含功能:</strong>
                                <div style="font-size: 14px; color: #555; margin-top: 5px;"><?php echo nl2br(esc_html($plan->features)); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 14px; color: #666;">
                            <div>有效天數: <strong><?php echo $plan->valid_days; ?> 天</strong></div>
                            <div>名片數量: <strong><?php echo $plan->max_cards ? $plan->max_cards . ' 張' : '無限'; ?></strong></div>
                            <div>使用客戶: <strong><?php echo $client_count; ?> 位</strong></div>
                            <div>計劃狀態: <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span></div>
                        </div>
                        
                        <div style="margin-top: 15px; display: flex; gap: 10px;">
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=namecardgen-plans&delete_plan=' . $plan->id), 'delete_plan'); ?>" 
                               class="button button-small" 
                               onclick="return confirm('確定要刪除這個計劃嗎？')"
                               style="background: #dc3545; color: white; border: none;">
                               刪除計劃
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                <div style="text-align: center; padding: 40px;">
                    <div style="color: #666; font-size: 16px;">
                        <p>💡 暫無計劃資料</p>
                        <p>請使用上方表單添加第一個計劃</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    public function display_all_cards_page() {
        $cards = $this->clients->get_all_clients();
        $client_stats = $this->clients->get_client_stats();
        $plan_stats = $this->plans->get_plan_stats();
        ?>
        <div class="wrap">
            <h1>🎴 所有名片</h1>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <h3>📊 統計資訊</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                    <div style="background: white; padding: 15px; border-radius: 6px; text-align: center; border: 1px solid #dee2e6;">
                        <div style="font-size: 24px; font-weight: bold; color: #007cba;"><?php echo $client_stats['total_clients']; ?></div>
                        <div>總名片數</div>
                    </div>
                    <div style="background: white; padding: 15px; border-radius: 6px; text-align: center; border: 1px solid #dee2e6;">
                        <div style="font-size: 24px; font-weight: bold; color: #28a745;"><?php echo $client_stats['today_clients']; ?></div>
                        <div>今日生成</div>
                    </div>
                    <div style="background: white; padding: 15px; border-radius: 6px; text-align: center; border: 1px solid #dee2e6;">
                        <div style="font-size: 24px; font-weight: bold; color: #ffc107;"><?php echo $client_stats['month_clients']; ?></div>
                        <div>本月生成</div>
                    </div>
                </div>

                <!-- 計劃分佈統計 -->
                <div style="margin-top: 20px;">
                    <h4>計劃分佈</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-top: 10px;">
                        <?php foreach ($plan_stats as $stat) : ?>
                        <div style="background: white; padding: 10px; border-radius: 4px; text-align: center; border-left: 4px solid #667eea;">
                            <div style="font-size: 18px; font-weight: bold;"><?php echo $stat->client_count; ?></div>
                            <div style="font-size: 12px; color: #666;"><?php echo esc_html($stat->plan_name); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-radius: 8px;">
                <h3>📋 名片列表 (<?php echo count($cards); ?> 張名片)</h3>
                <?php if ($cards) : ?>
                <table class="wp-list-table widefat fixed striped" style="width: 100%;">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">客戶姓名</th>
                            <th width="20%">專屬連結</th>
                            <th width="15%">使用計劃</th>
                            <th width="15%">建立時間</th>
                            <th width="10%">狀態</th>
                            <th width="20%">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cards as $card) : 
                            $status = $this->clients->get_client_status($card);
                        ?>
                        <tr>
                            <td><?php echo $card->id; ?></td>
                            <td>
                                <strong><?php echo esc_html($card->name); ?></strong>
                                <?php if ($card->company) : ?>
                                <br><small style="color: #666;"><?php echo esc_html($card->company); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($card->custom_link) : ?>
                                <code style="background: #f8f9fa; padding: 2px 5px; border-radius: 3px;">
                                    <?php echo home_url('/' . $card->custom_link); ?>
                                </code>
                                <?php else : ?>
                                <span style="color: #6c757d;">未生成</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($card->plan_name) : ?>
                                <span style="color: #007cba; font-weight: bold;"><?php echo esc_html($card->plan_name); ?></span>
                                <?php else : ?>
                                <span style="color: #6c757d;">未選擇</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($card->created_at)); ?></td>
                            <td>
                                <span class="<?php echo $status['class']; ?>" style="font-weight: bold;">
                                    <?php echo $status['text']; ?>
                                </span>
                            </td>
                            <td>
                                <button class="button button-small" style="background: #007cba; color: white; border: none;">
                                    查看詳情
                                </button>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=namecardgen-all-cards&delete_client=' . $card->id), 'delete_client'); ?>" 
                                   class="button button-small" 
                                   onclick="return confirm('確定要刪除這張名片嗎？')"
                                   style="background: #dc3545; color: white; border: none;">
                                   刪除
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                <div style="text-align: center; padding: 40px;">
                    <div style="color: #666; font-size: 16px;">
                        <p>🎴 暫無名片資料</p>
                        <p>客戶上傳圖片後，名片將顯示在這裡</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <style>
        .status-active { color: #28a745; }
        .status-inactive { color: #dc3545; }
        .status-expired { color: #ffc107; }
        code { font-family: 'Courier New', monospace; }
        </style>
        <?php
    }
}