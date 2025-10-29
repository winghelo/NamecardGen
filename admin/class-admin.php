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
                    
                    <!-- 其他表單欄位 -->
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=namecardgen-clients&action=edit&client_id=' . $client->id); ?>">
                                        <?php echo esc_html($client->company_name); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($client->contact_person); ?></td>
                                <td><?php echo esc_html($client->email); ?></td>
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
    // 這裡添加表單處理邏輯
    $clients_class = new NamecardGen_Clients();
    
    $client_data = array(
        'company_name' => sanitize_text_field($data['company_name']),
        'contact_person' => sanitize_text_field($data['contact_person']),
        'email' => sanitize_email($data['email']),
        'phone' => sanitize_text_field($data['phone']),
        'address' => sanitize_textarea_field($data['address'])
    );
    
    if ($client_id) {
            return $clients_class->update_client($client_id, $client_data);
        } else {
            return $clients_class->create_client($client_data);
        }
    }

    // 同樣添加方案相關的方法...
    public function display_plan_form($plan_id, $action) {
        // 方案表單顯示邏輯
    }

    public function display_plans_list() {
        // 方案列表顯示邏輯  
    }
    
    public function handle_plan_form_submit($data, $plan_id = 0) {
        // 方案表單提交邏輯
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
        
        <hr class="wp-header-end">
        
        <div class="namecardgen-card">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php _e('方案名稱', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('價格', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('有效期', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('最大名片數', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('狀態', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('操作', 'namecardgen'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($plans)) : ?>
                        <?php foreach ($plans as $plan) : ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo admin_url('admin.php?page=namecardgen-plans&action=edit&plan_id=' . $plan->id); ?>">
                                            <?php echo esc_html($plan->plan_name); ?>
                                        </a>
                                    </strong>
                                    <?php if ($plan->description) : ?>
                                        <p class="description"><?php echo esc_html($plan->description); ?></p>
                                    <?php endif; ?>
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
                    <?php else : ?>
                        <tr>
                            <td colspan="6" class="no-items">
                                <?php _e('暫無方案資料', 'namecardgen'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
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
        
        <hr class="wp-header-end">
        
        <div class="namecardgen-card">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php _e('方案名稱', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('價格', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('有效期', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('最大名片數', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('狀態', 'namecardgen'); ?></th>
                        <th scope="col"><?php _e('操作', 'namecardgen'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($plans)) : ?>
                        <?php foreach ($plans as $plan) : ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo admin_url('admin.php?page=namecardgen-plans&action=edit&plan_id=' . $plan->id); ?>">
                                            <?php echo esc_html($plan->plan_name); ?>
                                        </a>
                                    </strong>
                                    <?php if ($plan->description) : ?>
                                        <p class="description"><?php echo esc_html($plan->description); ?></p>
                                    <?php endif; ?>
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
                    <?php else : ?>
                        <tr>
                            <td colspan="6" class="no-items">
                                <?php _e('暫無方案資料', 'namecardgen'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
