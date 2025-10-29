/**
 * NamecardGen 前台 JavaScript
 */

(function($) {
    'use strict';

    class NamecardGenPublic {
        constructor() {
            this.currentStep = 1;
            this.totalSteps = 3;
            this.formData = {};
            this.init();
        }

        init() {
            this.bindEvents();
            this.initFormValidation();
            this.initLivePreview();
            this.autoSaveFormData();
        }

        bindEvents() {
            // 步驟導航
            $(document).on('click', '.next-step', this.nextStep.bind(this));
            $(document).on('click', '.prev-step', this.prevStep.bind(this));
            
            // 表單提交
            $(document).on('submit', '#namecardgen-form', this.handleFormSubmit.bind(this));
            
            // 重新生成
            $(document).on('click', '.create-another', this.resetForm.bind(this));
            
            // 重試表單
            $(document).on('click', '.retry-form', this.retryForm.bind(this));
            
            // 分享功能
            $(document).on('click', '.share-email', this.shareViaEmail.bind(this));
            $(document).on('click', '.share-link', this.copyShareLink.bind(this));
            
            // 即時表單更新
            $(document).on('input', '.namecardgen-field', this.handleFieldInput.bind(this));
            
            // 設計選擇變更
            $(document).on('change', 'input[name="template"], input[name="color_scheme"]', this.updateLivePreview.bind(this));
            
            // 鍵盤導航
            $(document).on('keydown', this.handleKeyboardNavigation.bind(this));
        }

        initFormValidation() {
            // 實時表單驗證
            $('.namecardgen-field').each(function() {
                const field = $(this);
                field.on('blur', function() {
                    validateField(field);
                });
            });
        }

        initLivePreview() {
            // 初始化即時預覽
            this.updateLivePreview();
        }

        autoSaveFormData() {
            // 自動保存表單數據到本地存儲
            $('.namecardgen-field').on('input', this.debounce(() => {
                this.saveFormData();
            }, 500));
        }

        saveFormData() {
            const formData = {};
            $('.namecardgen-field').each(function() {
                const field = $(this);
                const fieldName = field.attr('name');
                const fieldValue = field.val();
                if (fieldName) {
                    formData[fieldName] = fieldValue;
                }
            });
            
            // 保存到本地存儲
            localStorage.setItem('namecardgen_form_data', JSON.stringify(formData));
        }

        loadFormData() {
            const savedData = localStorage.getItem('namecardgen_form_data');
            if (savedData) {
                const formData = JSON.parse(savedData);
                for (const fieldName in formData) {
                    $(`[name="${fieldName}"]`).val(formData[fieldName]);
                }
                this.updateLivePreview();
            }
        }

        nextStep(e) {
            e.preventDefault();
            const nextStep = $(e.target).data('next');
            
            if (this.validateCurrentStep()) {
                this.goToStep(nextStep);
            }
        }

        prevStep(e) {
            e.preventDefault();
            const prevStep = $(e.target).data('prev');
            this.goToStep(prevStep);
        }

        goToStep(stepNumber) {
            // 隱藏所有步驟
            $('.form-step').removeClass('active').fadeOut(300);
            
            // 更新進度指示器
            this.updateProgressSteps(stepNumber);
            
            // 顯示目標步驟
            setTimeout(() => {
                $(`[data-step="${stepNumber}"]`).addClass('active').fadeIn(300);
                this.currentStep = stepNumber;
                
                // 滾動到頂部
                $('html, body').animate({
                    scrollTop: $('.namecardgen-form').offset().top - 20
                }, 300);
                
                // 觸發步驟更改事件
                this.onStepChange(stepNumber);
            }, 300);
        }

        updateProgressSteps(currentStep) {
            $('.step').removeClass('active completed');
            
            $('.step').each(function() {
                const step = $(this);
                const stepNumber = parseInt(step.data('step'));
                
                if (stepNumber < currentStep) {
                    step.addClass('completed');
                } else if (stepNumber === currentStep) {
                    step.addClass('active');
                }
            });
        }

        onStepChange(stepNumber) {
            switch (stepNumber) {
                case 2:
                    this.updateLivePreview();
                    break;
                case 3:
                    // 最終步驟，可以添加完成動畫等
                    break;
            }
        }

        validateCurrentStep() {
            let isValid = true;
            const currentStep = $(`.form-step[data-step="${this.currentStep}"]`);
            
            // 重置錯誤狀態
            currentStep.find('.form-field').removeClass('error');
            currentStep.find('.field-error').hide();
            
            // 驗證必填欄位
            currentStep.find('.namecardgen-field[required]').each(function() {
                const field = $(this);
                if (!validateField(field)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                this.showError('請填寫所有必填欄位');
                // 滾動到第一個錯誤欄位
                const firstError = currentStep.find('.form-field.error').first();
                if (firstError.length) {
                    $('html, body').animate({
                        scrollTop: firstError.offset().top - 100
                    }, 300);
                }
            }
            
            return isValid;
        }

        handleFormSubmit(e) {
            e.preventDefault();
            
            if (this.validateCurrentStep()) {
                this.submitForm();
            }
        }

        submitForm() {
            const form = $('#namecardgen-form');
            const submitButton = form.find('.submit-form');
            const loadingElement = $('.namecardgen-loading');
            
            // 顯示載入狀態
            submitButton.prop('disabled', true).text(namecardgen_public.processing);
            loadingElement.fadeIn(300);
            
            // 收集表單數據
            const formData = new FormData(form[0]);
            
            // 添加額外數據
            formData.append('action', 'namecardgen_submit_form');
            
            $.ajax({
                url: namecardgen_public.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.handleSubmitResponse(response);
                },
                error: (xhr, status, error) => {
                    this.handleSubmitError(error);
                },
                complete: () => {
                    loadingElement.fadeOut(300);
                    submitButton.prop('disabled', false).text(form.find('.submit-form').data('original-text') || '生成名片');
                }
            });
        }

        handleSubmitResponse(response) {
            if (response.success) {
                // 顯示成功步驟
                this.goToStep(3);
                
                // 設置下載連結
                if (response.data.download_url) {
                    $('#download-pdf-link').attr('href', response.data.download_url);
                }
                
                // 顯示成功訊息
                this.showSuccess(response.data.message);
                
                // 清除保存的表單數據
                localStorage.removeItem('namecardgen_form_data');
                
                // 發送轉換事件給分析工具
                this.trackConversion(response.data.namecard_id);
            } else {
                this.showError(response.data.message);
            }
        }

        handleSubmitError(error) {
            console.error('Form submission error:', error);
            this.showError(namecardgen_public.error_message);
        }

        trackConversion(namecardId) {
            // 發送轉換事件給 Google Analytics 等
            if (typeof gtag !== 'undefined') {
                gtag('event', 'namecard_generated', {
                    'event_category': 'conversion',
                    'event_label': namecardId
                });
            }
            
            // Facebook Pixel
            if (typeof fbq !== 'undefined') {
                fbq('track', 'CompleteRegistration');
            }
        }

        resetForm() {
            // 重置表單
            $('#namecardgen-form')[0].reset();
            
            // 清除錯誤狀態
            $('.form-field').removeClass('error');
            $('.field-error').hide();
            
            // 回到第一步
            this.goToStep(1);
            
            // 重置預覽
            this.updateLivePreview();
            
            // 顯示成功訊息
            this.showSuccess('表單已重置，可以開始創建新的名片');
        }

        retryForm() {
            $('.namecardgen-error').fadeOut(300);
            this.submitForm();
        }

        handleFieldInput(e) {
            const field = $(e.target);
            const fieldName = field.attr('name');
            
            if (fieldName) {
                this.formData[fieldName] = field.val();
                this.updateLivePreview();
            }
        }

        updateLivePreview() {
            const preview = $('#namecard-preview');
            if (!preview.length) return;
            
            // 更新公司名稱
            const companyName = this.formData.company_name || '<?php _e("公司名稱", "namecardgen"); ?>';
            preview.find('.company-name').text(companyName);
            
            // 更新聯絡人
            const contactPerson = this.formData.contact_person || '<?php _e("聯絡人姓名", "namecardgen"); ?>';
            preview.find('.contact-person').text(contactPerson);
            
            // 更新聯絡資訊
            const email = this.formData.email || 'email@example.com';
            const phone = this.formData.phone || '+886 2 1234 5678';
            preview.find('.email').text(email);
            preview.find('.phone').text(phone);
            
            // 更新地址
            const address = this.formData.address || '<?php _e("公司地址", "namecardgen"); ?>';
            preview.find('.address').text(address);
            
            // 更新顏色方案
            const colorScheme = $('input[name="color_scheme"]:checked').val() || 'blue';
            preview.removeClass (function (index, className) {
                return (className.match (/(^|\s)color-\S+/g) || []).join(' ');
            }).addClass('color-' + colorScheme);
            
            // 應用顏色方案樣式
            this.applyColorScheme(colorScheme, preview);
        }

        applyColorScheme(colorScheme, preview) {
            const colors = {
                blue: { primary: '#2196F3', secondary: '#1976D2' },
                green: { primary: '#4CAF50', secondary: '#388E3C' },
                red: { primary: '#f44336', secondary: '#d32f2f' },
                purple: { primary: '#9C27B0', secondary: '#7B1FA2' },
                orange: { primary: '#FF9800', secondary: '#F57C00' }
            };
            
            const scheme = colors[colorScheme] || colors.blue;
            
            preview.css({
                'background': `linear-gradient(135deg, ${scheme.primary}, ${scheme.secondary})`,
                'color': 'white'
            });
            
            preview.find('.company-name').css({
                'color': 'white',
                'font-weight': 'bold'
            });
        }

        shareViaEmail() {
            const namecardId = $('#download-pdf-link').data('namecard-id');
            const subject = encodeURIComponent('我的專業名片');
            const body = encodeURIComponent(`您好，

我與您分享我的專業名片，請查收附件。

祝好！`);
            
            window.location.href = `mailto:?subject=${subject}&body=${body}`;
        }

        copyShareLink() {
            const downloadUrl = $('#download-pdf-link').attr('href');
            
            navigator.clipboard.writeText(downloadUrl).then(() => {
                this.showSuccess('連結已複製到剪貼簿');
            }).catch(() => {
                this.showError('無法複製連結，請手動複製');
            });
        }

        handleKeyboardNavigation(e) {
            // Enter 鍵導航到下一步
            if (e.key === 'Enter' && e.ctrlKey) {
                e.preventDefault();
                if (this.currentStep < this.totalSteps) {
                    this.nextStep({ preventDefault: () => {} });
                }
            }
            
            // ESC 鍵重置表單
            if (e.key === 'Escape') {
                this.resetForm();
            }
        }

        showError(message) {
            const errorElement = $('.namecardgen-error');
            errorElement.find('#error-message').text(message);
            errorElement.fadeIn(300);
            
            // 自動隱藏錯誤訊息
            setTimeout(() => {
                errorElement.fadeOut(300);
            }, 5000);
        }

        showSuccess(message) {
            // 可以添加成功通知的實現
            console.log('Success:', message);
        }

        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }

    // 欄位驗證函數
    function validateField(field) {
        const value = field.val().trim();
        const fieldName = field.attr('name');
        const fieldContainer = field.closest('.form-field');
        const errorElement = fieldContainer.find('.field-error');
        
        let isValid = true;
        let errorMessage = '';
        
        // 必填欄位驗證
        if (field.prop('required') && !value) {
            isValid = false;
            errorMessage = namecardgen_public.required_field;
        }
        
        // 電子郵件驗證
        if (field.attr('type') === 'email' && value && !isValidEmail(value)) {
            isValid = false;
            errorMessage = namecardgen_public.invalid_email;
        }
        
        // 電話驗證
        if (fieldName === 'phone' && value && !isValidPhone(value)) {
            isValid = false;
            errorMessage = '請輸入有效的電話號碼';
        }
        
        // 更新UI
        if (!isValid) {
            fieldContainer.addClass('error');
            errorElement.text(errorMessage).show();
        } else {
            fieldContainer.removeClass('error');
            errorElement.hide();
        }
        
        return isValid;
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function isValidPhone(phone) {
        const phoneRegex = /^[0-9\-\+\(\)\s]{8,20}$/;
        return phoneRegex.test(phone);
    }

    // 頁面載入完成後初始化
    $(document).ready(function() {
        window.namecardgenPublic = new NamecardGenPublic();
        
        // 載入保存的表單數據
        window.namecardgenPublic.loadFormData();
        
        // 初始化工具提示
        initTooltips();
        
        // 初始化動畫
        initAnimations();
    });

    function initTooltips() {
        // 初始化工具提示
        $('[data-tooltip]').each(function() {
            const tooltip = $(this);
            const tooltipText = tooltip.data('tooltip');
            
            tooltip.on('mouseenter', function() {
                $('<div class="namecardgen-tooltip">' + tooltipText + '</div>')
                    .appendTo('body')
                    .css({
                        position: 'absolute',
                        top: tooltip.offset().top - 40,
                        left: tooltip.offset().left + (tooltip.width() / 2),
                        transform: 'translateX(-50%)'
                    })
                    .fadeIn(200);
            });
            
            tooltip.on('mouseleave', function() {
                $('.namecardgen-tooltip').fadeOut(200, function() {
                    $(this).remove();
                });
            });
        });
    }

    function initAnimations() {
        // 初始化滾動動畫
        if (typeof ScrollReveal !== 'undefined') {
            const sr = ScrollReveal();
            sr.reveal('.namecardgen-form', {
                duration: 600,
                distance: '20px',
                easing: 'cubic-bezier(0.5, 0, 0, 1)',
                origin: 'bottom',
                reset: false
            });
        }
        
        // 添加載入類別
        $('body').addClass('namecardgen-loaded');
    }

    // 全局錯誤處理
    window.addEventListener('error', function(e) {
        console.error('NamecardGen Error:', e.error);
    });

})(jQuery);
