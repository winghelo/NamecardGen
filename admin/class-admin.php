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
