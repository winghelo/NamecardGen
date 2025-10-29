<?php
if (!defined('ABSPATH')) {
    exit;
}

class NamecardGen_Shortcodes {
    
    private $clients;
    private $database;
    
    public function __construct($clients, $database) {
        $this->clients = $clients;
        $this->database = $database;
    }
    
    public function init() {
        // 註冊短代碼
        add_shortcode('NameCardGen', array($this, 'display_frontend_form'));
        add_shortcode('NamecardGen_Stats', array($this, 'display_stats'));
        
        // 註冊 AJAX 處理
        add_action('wp_ajax_namecardgen_upload', array($this, 'handle_file_upload'));
        add_action('wp_ajax_nopriv_namecardgen_upload', array($this, 'handle_file_upload'));
        
        // 註冊 URL 重寫規則
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'serve_custom_image'));
    }
    
    public function display_frontend_form($atts = array()) {
        // 只在非管理員頁面顯示
        if (is_admin()) {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'max_width' => '600px',
            'show_qr' => 'yes'
        ), $atts);
        
        ob_start();
        ?>
        <div id="namecardgen-public-form" style="max-width: <?php echo esc_attr($atts['max_width']); ?>; margin: 0 auto; padding: 30px; border: 1px solid #e0e0e0; border-radius: 12px; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="color: #333; margin-bottom: 10px;">🎴 生成您的專屬名片連結</h2>
                <p style="color: #666; font-size: 16px;">上傳圖片，立即獲得專屬網址和QR碼</p>
            </div>
            
            <form id="namecardgen-upload-form" enctype="multipart/form-data">
                <?php wp_nonce_field('namecardgen_public_upload', '_namecardgen_nonce'); ?>
                
                <!-- 基本資訊 -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">您的姓名 *</label>
                        <input type="text" name="client_name" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;" placeholder="請輸入姓名">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">電子郵件 *</label>
                        <input type="email" name="client_email" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;" placeholder="請輸入電子郵件">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">電話號碼</label>
                        <input type="tel" name="client_phone" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;" placeholder="請輸入電話號碼">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">公司名稱</label>
                        <input type="text" name="client_company" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;" placeholder="請輸入公司名稱">
                    </div>
                </div>
                
                <!-- 檔案上傳區域 -->
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">上傳名片圖片</label>
                    <div style="border: 2px dashed #ccc; border-radius: 8px; padding: 30px; text-align: center; background: #fafafa; transition: all 0.3s ease;">
                        <input type="file" name="namecardgen_image" accept=".jpg,.jpeg,.png" required
                               style="width: 100%; padding: 10px; border: none; background: transparent;"
                               onchange="document.getElementById('file-name').textContent = this.files[0]?.name || '未選擇檔案'">
                        <div style="margin-top: 10px;">
                            <span id="file-name" style="color: #666; font-size: 14px;">請選擇 JPG 或 PNG 圖片 (最大 10MB)</span>
                        </div>
                    </div>
                </div>
                
                <!-- 自訂連結名稱 -->
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">自訂連結名稱</label>
                    <input type="text" name="namecardgen_custom_name"
                           pattern="[a-zA-Z0-9_-]+"
                           title="只能包含英文、數字、底線和連字符"
                           placeholder="例如: my-company-card"
                           required
                           style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;"
                           oninput="document.getElementById('link-preview').textContent = this.value || 'your-name'">
                    <div style="margin-top: 8px;">
                        <small style="color: #666;">您的專屬連結: <?php echo home_url('/'); ?><span id="link-preview" style="color: #007cba; font-weight: bold;">your-name</span>.jpg</small>
                    </div>
                </div>
                
                <!-- 重要提醒 -->
                <div style="background: #fff3cd; padding: 15px; border-radius: 6px; border-left: 4px solid #ffc107; margin-bottom: 25px;">
                    <p style="margin: 0; color: #856404; font-size: 14px;">
                        <strong>💡 重要提醒:</strong><br>
                        • 修改連結名稱會改變網址，之前的連結將失效<br>
                        • 重新上傳圖片不會改變連結，但會替換現有圖片<br>
                        • 請確保連結名稱容易記憶且唯一
                    </p>
                </div>
                
                <!-- 提交按鈕 -->
                <button type="submit" style="width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: bold; transition: all 0.3s ease;">
                    <span id="submit-text">🚀 生成專屬連結與 QR 碼</span>
                    <div id="loading-spinner" style="display: none;">上傳中，請稍候...</div>
                </button>
            </form>
            
            <!-- 結果顯示區域 -->
            <div id="namecardgen-result" style="display: none; margin-top: 30px; padding: 25px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="font-size: 48px; margin-bottom: 10px;">✅</div>
                    <h3 style="color: #28a745; margin-bottom: 10px;">生成成功！</h3>
                    <p style="color: #666;">您的專屬名片連結已準備好</p>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">專屬連結</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="result-url" readonly
                               style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: white; font-family: 'Courier New', monospace;">
                        <button onclick="copyToClipboard('result-url')"
                                style="background: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">
                            複製連結
                        </button>
                    </div>
                </div>
                
                <?php if ($atts['show_qr'] === 'yes') : ?>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">QR 碼</label>
                    <div style="text-align: center;">
                        <img id="result-qrcode" src="" alt="QR Code"
                             style="max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 8px; padding: 10px; background: white;">
                        <p style="margin-top: 10px; color: #666; font-size: 14px;">掃描 QR 碼即可訪問您的名片</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 6px; border-left: 4px solid #007cba;">
                    <h4 style="margin: 0 0 10px 0; color: #007cba;">下一步操作</h4>
                    <ul style="margin: 0; padding-left: 20px; color: #666;">
                        <li>將連結分享給您的客戶或朋友</li>
                        <li>將 QR 碼列印在實體名片上</li>
                        <li>在社交媒體分享您的專屬連結</li>
                    </ul>
                </div>
            </div>
        </div>

        <script>
        function copyToClipboard(elementId) {
            var copyText = document.getElementById(elementId);
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand('copy');
            
            // 顯示複製成功提示
            var originalText = event.target.textContent;
            event.target.textContent = '已複製！';
            event.target.style.background = '#6c757d';
            
            setTimeout(function() {
                event.target.textContent = originalText;
                event.target.style.background = '#28a745';
            }, 2000);
        }
        
        jQuery(document).ready(function($) {
            $('#namecardgen-upload-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                var submitBtn = $(this).find('button[type="submit"]');
                var submitText = $('#submit-text');
                var loadingSpinner = $('#loading-spinner');
                
                // 顯示載入狀態
                submitText.hide();
                loadingSpinner.show();
                submitBtn.prop('disabled', true).css('opacity', '0.7');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // 顯示成功結果
                            $('#result-url').val(response.data.url);
                            $('#result-qrcode').attr('src', response.data.qr_code);
                            $('#namecardgen-result').show();
                            
                            // 重置表單
                            $('#namecardgen-upload-form')[0].reset();
                            $('#link-preview').text('your-name');
                            $('#file-name').text('請選擇 JPG 或 PNG 圖片 (最大 10MB)');
                            
                            // 滾動到結果區域
                            $('html, body').animate({
                                scrollTop: $('#namecardgen-result').offset().top - 100
                            }, 500);
                            
                        } else {
                            alert('錯誤: ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('上傳失敗: ' + error);
                    },
                    complete: function() {
                        // 恢復按鈕狀態
                        submitText.show();
                        loadingSpinner.hide();
                        submitBtn.prop('disabled', false).css('opacity', '1');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function display_stats($atts = array()) {
        $atts = shortcode_atts(array(
            'show_total' => 'yes',
            'show_today' => 'yes',
            'show_plans' => 'yes'
        ), $atts);
        
        ob_start();
        ?>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6; margin: 20px 0;">
            <h3 style="margin-top: 0;">📊 NamecardGen 統計</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                <?php if ($atts['show_total'] === 'yes') : ?>
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #007cba;">0</div>
                    <div style="color: #666; font-size: 14px;">總名片數</div>
                </div>
                <?php endif; ?>
                
                <?php if ($atts['show_today'] === 'yes') : ?>
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #28a745;">0</div>
                    <div style="color: #666; font-size: 14px;">今日生成</div>
                </div>
                <?php endif; ?>
                
                <?php if ($atts['show_plans'] === 'yes') : ?>
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #ffc107;">3</div>
                    <div style="color: #666; font-size: 14px;">可用計劃</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function handle_file_upload() {
        // 安全檢查
        if (!wp_verify_nonce($_POST['_namecardgen_nonce'], 'namecardgen_public_upload')) {
            wp_send_json_error('安全驗證失敗，請刷新頁面後重試。');
        }
        
        // 檢查文件
        if (empty($_FILES['namecardgen_image'])) {
            wp_send_json_error('請選擇要上傳的圖片。');
        }
        
        $file = $_FILES['namecardgen_image'];
        $custom_name = sanitize_text_field($_POST['namecardgen_custom_name']);
        $client_name = sanitize_text_field($_POST['client_name']);
        $client_email = sanitize_email($_POST['client_email']);
        $client_phone = sanitize_text_field($_POST['client_phone']);
        $client_company = sanitize_text_field($_POST['client_company']);
        
        // 驗證文件類型
        $allowed_types = array('image/jpeg', 'image/png', 'image/jpg');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error('只允許 JPG 和 PNG 格式的圖片。');
        }
        
        // 驗證文件大小 (10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            wp_send_json_error('文件大小不能超過 10MB。');
        }
        
        // 驗證自訂名稱
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $custom_name)) {
            wp_send_json_error('連結名稱只能包含英文、數字、底線和連字符。');
        }
        
        // 建立上傳目錄
        $upload_dir = wp_upload_dir();
        $customer_images_dir = $upload_dir['basedir'] . '/namecardgen-images/';
        if (!file_exists($customer_images_dir)) {
            wp_mkdir_p($customer_images_dir);
        }
        
        // 處理文件名
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $custom_name . '.' . $file_extension;
        $filepath = $customer_images_dir . $filename;
        
        // 如果文件已存在，先刪除舊文件
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // 移動文件
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // 生成訪問URL
            $image_url = home_url('/' . $custom_name . '.' . $file_extension);
            
            // 生成QR碼 (使用 HTTPS)
            $qr_code_url = 'https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=' . urlencode($image_url);
            
            // 儲存到資料庫
            $client_data = array(
                'name' => $client_name,
                'email' => $client_email,
                'phone' => $client_phone,
                'company' => $client_company,
                'custom_link' => $custom_name,
                'image_url' => $image_url,
                'created_at' => current_time('mysql')
            );
            
            $result = $this->clients->add_client($client_data);
            
            if ($result) {
                wp_send_json_success(array(
                    'url' => $image_url,
                    'qr_code' => $qr_code_url,
                    'message' => '名片生成成功！您的專屬連結已建立。'
                ));
            } else {
                wp_send_json_error('資料庫儲存失敗，請重試。');
            }
        } else {
            wp_send_json_error('文件上傳失敗，請重試。');
        }
    }
    
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^([a-zA-Z0-9_-]+)\.(jpg|png|jpeg)$',
            'index.php?namecardgen_image=$matches[1]&namecardgen_ext=$matches[2]',
            'top'
        );
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'namecardgen_image';
        $vars[] = 'namecardgen_ext';
        return $vars;
    }
    
    public function serve_custom_image() {
        $image_name = get_query_var('namecardgen_image');
        $image_ext = get_query_var('namecardgen_ext');
        
        if ($image_name && $image_ext) {
            $upload_dir = wp_upload_dir();
            $filepath = $upload_dir['basedir'] . '/namecardgen-images/' . $image_name . '.' . $image_ext;
            
            if (file_exists($filepath)) {
                $mime_type = mime_content_type($filepath);
                header('Content-Type: ' . $mime_type);
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                exit;
            } else {
                status_header(404);
                echo '圖片未找到';
                exit;
            }
        }
    }
}