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
