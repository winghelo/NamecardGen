<?php
/**
 * 名片生成表單模板
 */

if (!defined('ABSPATH')) {
    exit;
}

// 獲取表單欄位配置
$form_fields = namecardgen_get_form_fields();
$public_class = NamecardGen_Public::get_instance();
$templates = $public_class->get_available_templates();
$color_schemes = $public_class->get_color_schemes();

// 短代碼屬性
$show_preview = namecardgen_shortcode_bool($atts['show_preview']);
$button_text = $atts['button_text'];
$form_class = namecardgen_shortcode_class('form', $atts['class']);
?>

<div class="<?php echo esc_attr($form_class); ?>" id="namecardgen-form-container">
    <!-- 表單標題 -->
    <div class="namecardgen-form-header">
        <h3><?php _e('生成您的專業名片', 'namecardgen'); ?></h3>
        <p><?php _e('填寫以下資訊，立即生成專屬名片', 'namecardgen'); ?></p>
    </div>

    <!-- 進度指示器 -->
    <div class="namecardgen-progress-steps">
        <div class="step active" data-step="1">
            <span class="step-number">1</span>
            <span class="step-label"><?php _e('基本資訊', 'namecardgen'); ?></span>
        </div>
        <div class="step" data-step="2">
            <span class="step-number">2</span>
            <span class="step-label"><?php _e('設計選擇', 'namecardgen'); ?></span>
        </div>
        <div class="step" data-step="3">
            <span class="step-number">3</span>
            <span class="step-label"><?php _e('完成', 'namecardgen'); ?></span>
        </div>
    </div>

    <!-- 表單區域 -->
    <form id="namecardgen-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('namecardgen_public_nonce', 'namecardgen_nonce'); ?>
        
        <!-- 步驟1: 基本資訊 -->
        <div class="form-step active" data-step="1">
            <div class="step-header">
                <h4><?php _e('基本資訊', 'namecardgen'); ?></h4>
                <p><?php _e('請填寫您的公司與聯絡資訊', 'namecardgen'); ?></p>
            </div>
            
            <div class="form-fields">
                <?php foreach (array('company_name', 'contact_person', 'email', 'phone', 'address') as $field_name) : ?>
                    <?php if (isset($form_fields[$field_name])) : ?>
                        <div class="form-field">
                            <?php echo namecardgen_form_field($field_name, $form_fields[$field_name]); ?>
                            <span class="field-error"></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <div class="step-actions">
                <button type="button" class="button next-step" data-next="2">
                    <?php _e('下一步：選擇設計', 'namecardgen'); ?>
                </button>
            </div>
        </div>

        <!-- 步驟2: 設計選擇 -->
        <div class="form-step" data-step="2">
            <div class="step-header">
                <h4><?php _e('選擇設計', 'namecardgen'); ?></h4>
                <p><?php _e('選擇您喜歡的名片模板和顏色', 'namecardgen'); ?></p>
            </div>
            
            <!-- 模板選擇 -->
            <div class="design-section">
                <h5><?php _e('選擇模板', 'namecardgen'); ?></h5>
                <div class="template-selection">
                    <?php foreach ($templates as $template_key => $template) : ?>
                        <div class="template-option">
                            <input type="radio" name="template" id="template_<?php echo esc_attr($template_key); ?>" 
                                   value="<?php echo esc_attr($template_key); ?>" 
                                   <?php checked($template_key, 'default'); ?>>
                            <label for="template_<?php echo esc_attr($template_key); ?>">
                                <?php if (!empty($template['preview'])) : ?>
                                    <div class="template-preview">
                                        <img src="<?php echo esc_url($template['preview']); ?>" 
                                             alt="<?php echo esc_attr($template['name']); ?>">
                                    </div>
                                <?php endif; ?>
                                <div class="template-info">
                                    <strong><?php echo esc_html($template['name']); ?></strong>
                                    <p><?php echo esc_html($template['description']); ?></p>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- 顏色選擇 -->
            <div class="design-section">
                <h5><?php _e('顏色方案', 'namecardgen'); ?></h5>
                <div class="color-selection">
                    <?php foreach ($color_schemes as $color_key => $color_name) : ?>
                        <div class="color-option">
                            <input type="radio" name="color_scheme" id="color_<?php echo esc_attr($color_key); ?>" 
                                   value="<?php echo esc_attr($color_key); ?>" 
                                   <?php checked($color_key, 'blue'); ?>>
                            <label for="color_<?php echo esc_attr($color_key); ?>" 
                                   class="color-swatch color-<?php echo esc_attr($color_key); ?>" 
                                   title="<?php echo esc_attr($color_name); ?>">
                                <span class="color-name"><?php echo esc_html($color_name); ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 即時預覽 -->
            <?php if ($show_preview) : ?>
            <div class="design-section">
                <h5><?php _e('即時預覽', 'namecardgen'); ?></h5>
                <div class="live-preview">
                    <div class="namecard-preview" id="namecard-preview">
                        <div class="preview-content">
                            <div class="company-name"><?php _e('公司名稱', 'namecardgen'); ?></div>
                            <div class="contact-person"><?php _e('聯絡人姓名', 'namecardgen'); ?></div>
                            <div class="contact-info">
                                <span class="email">email@example.com</span>
                                <span class="phone">+886 2 1234 5678</span>
                            </div>
                            <div class="address"><?php _e('公司地址', 'namecardgen'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="step-actions">
                <button type="button" class="button prev-step" data-prev="1">
                    <?php _e('上一步', 'namecardgen'); ?>
                </button>
                <button type="submit" class="button button-primary submit-form">
                    <?php echo esc_html($button_text); ?>
                </button>
            </div>
        </div>

        <!-- 步驟3: 完成 -->
        <div class="form-step" data-step="3">
            <div class="step-header">
                <h4><?php _e('完成！', 'namecardgen'); ?></h4>
                <p><?php _e('您的名片已生成完成', 'namecardgen'); ?></p>
            </div>
            
            <div class="completion-message">
                <div class="success-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="success-content">
                    <h4><?php _e('名片生成成功！', 'namecardgen'); ?></h4>
                    <p><?php _e('您的專業名片已經準備就緒', 'namecardgen'); ?></p>
                    
                    <div class="download-actions">
                        <a href="#" class="button button-primary download-pdf" id="download-pdf-link" target="_blank">
                            <?php _e('下載PDF名片', 'namecardgen'); ?>
                        </a>
                        <button type="button" class="button create-another">
                            <?php _e('再生成一張', 'namecardgen'); ?>
                        </button>
                    </div>
                    
                    <div class="share-options">
                        <p><?php _e('分享您的名片：', 'namecardgen'); ?></p>
                        <div class="share-buttons">
                            <button type="button" class="button share-email">
                                <?php _e('透過郵件分享', 'namecardgen'); ?>
                            </button>
                            <button type="button" class="button share-link">
                                <?php _e('複製連結', 'namecardgen'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- 載入指示器 -->
    <div class="namecardgen-loading" style="display: none;">
        <div class="loading-spinner"></div>
        <p><?php _e('正在生成您的名片，請稍候...', 'namecardgen'); ?></p>
    </div>

    <!-- 錯誤訊息 -->
    <div class="namecardgen-error" style="display: none;">
        <div class="error-icon">
            <span class="dashicons dashicons-warning"></span>
        </div>
        <div class="error-content">
            <h4><?php _e('發生錯誤', 'namecardgen'); ?></h4>
            <p id="error-message"></p>
            <button type="button" class="button retry-form">
                <?php _e('重試', 'namecardgen'); ?>
            </button>
        </div>
    </div>
</div>

<style>
.namecardgen-form {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 30px;
}

.namecardgen-form-header {
    text-align: center;
    margin-bottom: 30px;
}

.namecardgen-form-header h3 {
    color: #333;
    margin-bottom: 10px;
}

.namecardgen-form-header p {
    color: #666;
    font-size: 16px;
}

/* 進度步驟 */
.namecardgen-progress-steps {
    display: flex;
    justify-content: center;
    margin-bottom: 40px;
    position: relative;
}

.namecardgen-progress-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 25%;
    right: 25%;
    height: 2px;
    background: #e0e0e0;
    z-index: 1;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
    flex: 1;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 8px;
    border: 2px solid #e0e0e0;
}

.step.active .step-number {
    background: #2271b1;
    color: white;
    border-color: #2271b1;
}

.step-label {
    font-size: 14px;
    color: #666;
    text-align: center;
}

.step.active .step-label {
    color: #2271b1;
    font-weight: 500;
}

/* 表單步驟 */
.form-step {
    display: none;
}

.form-step.active {
    display: block;
}

.step-header {
    text-align: center;
    margin-bottom: 30px;
}

.step-header h4 {
    color: #333;
    margin-bottom: 8px;
}

.step-header p {
    color: #666;
}

.form-fields {
    display: grid;
    gap: 20px;
    margin-bottom: 30px;
}

.form-field {
    display: flex;
    flex-direction: column;
}

.form-field label {
    font-weight: 500;
    margin-bottom: 5px;
    color: #333;
}

.form-field input,
.form-field textarea,
.form-field select {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-field input:focus,
.form-field textarea:focus,
.form-field select:focus {
    border-color: #2271b1;
    outline: none;
    box-shadow: 0 0 0 1px #2271b1;
}

.field-error {
    color: #d63638;
    font-size: 12px;
    margin-top: 5px;
    display: none;
}

/* 設計選擇 */
.design-section {
    margin-bottom: 30px;
}

.design-section h5 {
    margin-bottom: 15px;
    color: #333;
}

.template-selection {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.template-option input[type="radio"] {
    display: none;
}

.template-option label {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: block;
}

.template-option input[type="radio"]:checked + label {
    border-color: #2271b1;
    background: #f0f7ff;
}

.template-preview {
    text-align: center;
    margin-bottom: 10px;
}

.template-preview img {
    max-width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 4px;
}

.template-info strong {
    display: block;
    margin-bottom: 5px;
}

.template-info p {
    font-size: 12px;
    color: #666;
    margin: 0;
}

/* 顏色選擇 */
.color-selection {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.color-option input[type="radio"] {
    display: none;
}

.color-swatch {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #e0e0e0;
    transition: all 0.3s ease;
}

.color-option input[type="radio"]:checked + .color-swatch {
    border-color: #333;
    transform: scale(1.1);
}

.color-name {
    font-size: 10px;
    color: white;
    text-shadow: 1px 1px 1px rgba(0,0,0,0.5);
}

/* 顏色類別 */
.color-blue { background: #2271b1; }
.color-green { background: #00a32a; }
.color-red { background: #d63638; }
.color-purple { background: #8c35e0; }
.color-orange { background: #f36d00; }

/* 即時預覽 */
.live-preview {
    text-align: center;
}

.namecard-preview {
    width: 300px;
    height: 180px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin: 0 auto;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.preview-content {
    text-align: left;
}

.preview-content .company-name {
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 8px;
}

.preview-content .contact-person {
    font-size: 14px;
    margin-bottom: 8px;
}

.preview-content .contact-info {
    font-size: 12px;
    color: #666;
    margin-bottom: 8px;
}

.preview-content .address {
    font-size: 11px;
    color: #999;
}

/* 步驟操作 */
.step-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}

/* 完成頁面 */
.completion-message {
    text-align: center;
    padding: 40px 20px;
}

.success-icon {
    font-size: 64px;
    color: #00a32a;
    margin-bottom: 20px;
}

.download-actions {
    margin: 25px 0;
}

.share-options {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}

.share-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 10px;
}

/* 載入和錯誤狀態 */
.namecardgen-loading,
.namecardgen-error {
    text-align: center;
    padding: 40px 20px;
}

.loading-spinner {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #2271b1;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.error-icon {
    font-size: 48px;
    color: #d63638;
    margin-bottom: 20px;
}

/* 響應式設計 */
@media (max-width: 768px) {
    .namecardgen-form {
        padding: 20px;
        margin: 10px;
    }
    
    .template-selection {
        grid-template-columns: 1fr;
    }
    
    .step-actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .step-actions .button {
        width: 100%;
    }
    
    .namecard-preview {
        width: 250px;
        height: 150px;
    }
}
</style>
