<?php
/**
 * 工具函數類別
 */

if (!defined('ABSPATH')) {
    exit;
}

class NamecardGen_Utilities {
    
    /**
     * 生成隨機字串
     */
    public function generate_random_string($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $random_string = '';
        
        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[wp_rand(0, strlen($characters) - 1)];
        }
        
        return $random_string;
    }
    
    /**
     * 驗證電子郵件格式
     */
    public function validate_email($email) {
        return is_email($email);
    }
    
    /**
     * 驗證電話號碼格式
     */
    public function validate_phone($phone) {
        // 基本的電話號碼驗證，可根據需求調整
        $pattern = '/^[0-9\-\+\(\)\s]{8,20}$/';
        return preg_match($pattern, $phone);
    }
    
    /**
     * 格式化日期
     */
    public function format_date($date_string, $format = 'Y-m-d H:i:s') {
        $timestamp = strtotime($date_string);
        return date($format, $timestamp);
    }
    
    /**
     * 生成名片
     */
    public function generate_namecard($client_id, $plan_id, $data) {
        global $wpdb;
        
        $clients_class = new NamecardGen_Clients();
        $plans_class = new NamecardGen_Plans();
        
        // 驗證客戶和方案
        $client = $clients_class->get_client_by_id($client_id);
        $plan = $plans_class->get_plan_by_id($plan_id);
        
        if (!$client || !$plan) {
            return new WP_Error('invalid_data', '無效的客戶或方案');
        }
        
        // 準備名片資料
        $card_data = array(
            'client_info' => array(
                'company_name' => $client->company_name,
                'contact_person' => $client->contact_person,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address
            ),
            'design_data' => $data,
            'generated_at' => current_time('mysql'),
            'template' => isset($data['template']) ? $data['template'] : 'default'
        );
        
        // 儲存到資料庫
        $table_name = $wpdb->prefix . 'namecardgen_namecards';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'client_id' => $client_id,
                'plan_id' => $plan_id,
                'card_data' => wp_json_encode($card_data),
                'design_template' => $card_data['template'],
                'status' => 'completed'
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', '儲存名片時發生資料庫錯誤');
        }
        
        $namecard_id = $wpdb->insert_id;
        
        // 生成PDF檔案（這裡是示例，實際需要實現PDF生成邏輯）
        $pdf_path = $this->generate_pdf($namecard_id, $card_data);
        
        if ($pdf_path && !is_wp_error($pdf_path)) {
            $wpdb->update(
                $table_name,
                array('pdf_path' => $pdf_path),
                array('id' => $namecard_id),
                array('%s'),
                array('%d')
            );
        }
        
        return array(
            'namecard_id' => $namecard_id,
            'pdf_path' => $pdf_path,
            'card_data' => $card_data
        );
    }
    
    /**
     * 生成PDF檔案
     */
    private function generate_pdf($namecard_id, $card_data) {
        // 這裡應該實現實際的PDF生成邏輯
        // 目前返回虛擬路徑
        
        $upload_dir = wp_upload_dir();
        $pdf_filename = 'namecard-' . $namecard_id . '-' . $this->generate_random_string(8) . '.pdf';
        $pdf_path = $upload_dir['path'] . '/' . $pdf_filename;
        
        // 實際應用中，這裡會使用 TCPDF、mPDF 或其他 PDF 庫來生成PDF
        // file_put_contents($pdf_path, $pdf_content);
        
        return $upload_dir['url'] . '/' . $pdf_filename;
    }
    
    /**
     * 獲取名片的圖片URL
     */
    public function get_namecard_image_url($namecard_id, $size = 'thumbnail') {
        // 這裡可以實現生成名片圖片的功能
        // 目前返回預設圖片
        
        return NAMECARDGEN_PLUGIN_URL . 'assets/images/default-namecard.jpg';
    }
    
    /**
     * 記錄日誌
     */
    public function log_message($message, $type = 'info') {
        if (!WP_DEBUG_LOG) {
            return;
        }
        
        $timestamp = current_time('mysql');
        $log_entry = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
        
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/namecardgen-debug.log';
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * 發送電子郵件通知
     */
    public function send_email_notification($to, $subject, $message, $headers = array()) {
        $default_headers = array('Content-Type: text/html; charset=UTF-8');
        $headers = array_merge($default_headers, $headers);
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * 檢查檔案類型
     */
    public function validate_file_type($file_path, $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf')) {
        $file_info = wp_check_filetype($file_path);
        return in_array($file_info['ext'], $allowed_types);
    }
    
    /**
     * 安全獲取POST/GET資料
     */
    public function get_sanitized_input($key, $default = '', $type = 'text') {
        $input = isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
        
        switch ($type) {
            case 'email':
                return sanitize_email($input);
            case 'url':
                return esc_url_raw($input);
            case 'textarea':
                return sanitize_textarea_field($input);
            case 'integer':
                return intval($input);
            case 'float':
                return floatval($input);
            case 'html':
                return wp_kses_post($input);
            case 'text':
            default:
                return sanitize_text_field($input);
        }
    }
    
    /**
     * 生成下載連結
     */
    public function generate_download_link($namecard_id, $file_type = 'pdf') {
        $nonce = wp_create_nonce('namecardgen_download_' . $namecard_id);
        return add_query_arg(array(
            'action' => 'download_namecard',
            'namecard_id' => $namecard_id,
            'file_type' => $file_type,
            'nonce' => $nonce
        ), admin_url('admin-ajax.php'));
    }
    
    /**
     * 格式化價格
     */
    public function format_price($price, $currency = 'TWD') {
        $currencies = array(
            'TWD' => 'NT$',
            'USD' => '$',
            'JPY' => '¥'
        );
        
        $symbol = isset($currencies[$currency]) ? $currencies[$currency] : '';
        
        return $symbol . number_format($price, 2);
    }
}
?>
