<?php
/**
 * 短代碼處理
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 名片生成表單短代碼
 */
function namecardgen_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'template' => 'default',
        'show_preview' => 'yes',
        'button_text' => '生成名片',
        'class' => ''
    ), $atts, 'namecardgen_form');
    
    ob_start();
    
    // 載入表單模板
    include NAMECARDGEN_PLUGIN_PATH . 'public/templates/form-template.php';
    
    return ob_get_clean();
}
add_shortcode('namecardgen_form', 'namecardgen_form_shortcode');

/**
 * 客戶名片列表短代碼
 */
function namecardgen_my_cards_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>' . __('請先登入以查看您的名片', 'namecardgen') . '</p>';
    }
    
    $atts = shortcode_atts(array(
        'per_page' => '10',
        'show_download' => 'yes',
        'class' => ''
    ), $atts, 'namecardgen_my_cards');
    
    ob_start();
    
    // 載入客戶名片列表模板
    include NAMECARDGEN_PLUGIN_PATH . 'public/templates/my-cards-template.php';
    
    return ob_get_clean();
}
add_shortcode('namecardgen_my_cards', 'namecardgen_my_cards_shortcode');

/**
 * 方案列表短代碼
 */
function namecardgen_plans_shortcode($atts) {
    $atts = shortcode_atts(array(
        'show_free' => 'yes',
        'layout' => 'grid',
        'class' => ''
    ), $atts, 'namecardgen_plans');
    
    ob_start();
    
    // 載入方案列表模板
    include NAMECARDGEN_PLUGIN_PATH . 'public/templates/plans-template.php';
    
    return ob_get_clean();
}
add_shortcode('namecardgen_plans', 'namecardgen_plans_shortcode');

/**
 * 名片預覽短代碼
 */
function namecardgen_preview_shortcode($atts) {
    $atts = shortcode_atts(array(
        'namecard_id' => '0',
        'width' => '300px',
        'height' => '180px',
        'class' => ''
    ), $atts, 'namecardgen_preview');
    
    ob_start();
    
    // 載入名片預覽模板
    include NAMECARDGEN_PLUGIN_PATH . 'public/templates/preview-template.php';
    
    return ob_get_clean();
}
add_shortcode('namecardgen_preview', 'namecardgen_preview_shortcode');

/**
 * 註冊所有短代碼
 */
function namecardgen_register_shortcodes() {
    // 上面的短代碼已經通過 add_shortcode 註冊
    // 這裡可以添加其他全域短代碼處理
}
add_action('init', 'namecardgen_register_shortcodes');

/**
 * 短代碼幫助函數
 */

/**
 * 生成表單欄位
 */
function namecardgen_form_field($field_name, $field_config) {
    $defaults = array(
        'type' => 'text',
        'label' => '',
        'placeholder' => '',
        'required' => false,
        'value' => '',
        'options' => array(),
        'class' => ''
    );
    
    $field_config = wp_parse_args($field_config, $defaults);
    
    $field_html = '';
    $required_attr = $field_config['required'] ? ' required' : '';
    $class_attr = $field_config['class'] ? ' class="' . esc_attr($field_config['class']) . '"' : '';
    
    switch ($field_config['type']) {
        case 'text':
        case 'email':
        case 'tel':
            $field_html = sprintf(
                '<input type="%s" name="%s" placeholder="%s" value="%s"%s%s>',
                esc_attr($field_config['type']),
                esc_attr($field_name),
                esc_attr($field_config['placeholder']),
                esc_attr($field_config['value']),
                $required_attr,
                $class_attr
            );
            break;
            
        case 'textarea':
            $field_html = sprintf(
                '<textarea name="%s" placeholder="%s"%s%s>%s</textarea>',
                esc_attr($field_name),
                esc_attr($field_config['placeholder']),
                $required_attr,
                $class_attr,
                esc_textarea($field_config['value'])
            );
            break;
            
        case 'select':
            $options_html = '';
            foreach ($field_config['options'] as $value => $label) {
                $selected = $value == $field_config['value'] ? ' selected' : '';
                $options_html .= sprintf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr($value),
                    $selected,
                    esc_html($label)
                );
            }
            $field_html = sprintf(
                '<select name="%s"%s%s>%s</select>',
                esc_attr($field_name),
                $required_attr,
                $class_attr,
                $options_html
            );
            break;
            
        case 'radio':
        case 'checkbox':
            $field_html = '';
            foreach ($field_config['options'] as $value => $label) {
                $checked = $value == $field_config['value'] ? ' checked' : '';
                $field_html .= sprintf(
                    '<label><input type="%s" name="%s" value="%s"%s%s> %s</label>',
                    esc_attr($field_config['type']),
                    esc_attr($field_name),
                    esc_attr($value),
                    $checked,
                    $class_attr,
                    esc_html($label)
                );
            }
            break;
    }
    
    if ($field_config['label']) {
        $field_html = sprintf(
            '<label>%s %s</label>',
            esc_html($field_config['label']),
            $field_html
        );
    }
    
    return $field_html;
}

/**
 * 獲取表單欄位配置
 */
function namecardgen_get_form_fields() {
    $public_class = NamecardGen_Public::get_instance();
    
    return apply_filters('namecardgen_form_fields', array(
        'company_name' => array(
            'type' => 'text',
            'label' => __('公司名稱', 'namecardgen'),
            'placeholder' => __('請輸入公司名稱', 'namecardgen'),
            'required' => true,
            'class' => 'namecardgen-field'
        ),
        'contact_person' => array(
            'type' => 'text',
            'label' => __('聯絡人', 'namecardgen'),
            'placeholder' => __('請輸入聯絡人姓名', 'namecardgen'),
            'required' => true,
            'class' => 'namecardgen-field'
        ),
        'email' => array(
            'type' => 'email',
            'label' => __('電子郵件', 'namecardgen'),
            'placeholder' => __('請輸入電子郵件', 'namecardgen'),
            'required' => true,
            'class' => 'namecardgen-field'
        ),
        'phone' => array(
            'type' => 'tel',
            'label' => __('電話', 'namecardgen'),
            'placeholder' => __('請輸入電話號碼', 'namecardgen'),
            'required' => false,
            'class' => 'namecardgen-field'
        ),
        'address' => array(
            'type' => 'textarea',
            'label' => __('地址', 'namecardgen'),
            'placeholder' => __('請輸入公司地址', 'namecardgen'),
            'required' => false,
            'class' => 'namecardgen-field'
        ),
        'template' => array(
            'type' => 'select',
            'label' => __('選擇模板', 'namecardgen'),
            'required' => true,
            'class' => 'namecardgen-field',
            'options' => array_map(function($template) {
                return $template['name'];
            }, $public_class->get_available_templates())
        ),
        'color_scheme' => array(
            'type' => 'select',
            'label' => __('顏色方案', 'namecardgen'),
            'required' => false,
            'class' => 'namecardgen-field',
            'options' => $public_class->get_color_schemes()
        )
    ));
}

/**
 * 處理短代碼屬性布林值
 */
function namecardgen_shortcode_bool($value) {
    $true_values = array('yes', 'true', '1', 'on');
    return in_array(strtolower($value), $true_values);
}

/**
 * 生成短代碼CSS類名
 */
function namecardgen_shortcode_class($base_class, $additional_classes = '') {
    $classes = array('namecardgen-' . $base_class);
    
    if ($additional_classes) {
        $additional_classes_array = explode(' ', $additional_classes);
        $classes = array_merge($classes, $additional_classes_array);
    }
    
    return implode(' ', array_map('sanitize_html_class', $classes));
}
?>
