/**
 * NamecardGen 後台 JavaScript
 */

(function($) {
    'use strict';

    class NamecardGenAdmin {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initTabs();
            this.initModals();
            this.initBulkActions();
        }

        bindEvents() {
            // 表單提交處理
            $(document).on('submit', '.namecardgen-admin form', this.handleFormSubmit.bind(this));
            
            // 刪除操作確認
            $(document).on('click', '.submitdelete', this.confirmDelete.bind(this));
            
            // 批量操作
            $(document).on('change', '.namecardgen-bulk-action', this.handleBulkAction.bind(this));
            
            // 狀態切換
            $(document).on('change', '.namecardgen-status-toggle', this.handleStatusToggle.bind(this));
            
            // 搜尋功能
            $(document).on('keyup', '.namecardgen-search-box input', this.handleSearch.bind(this));
            
            // 工具提示
            $(document).on('mouseenter', '.namecardgen-tooltip', this.showTooltip.bind(this));
            $(document).on('mouseleave', '.namecardgen-tooltip', this.hideTooltip.bind(this));
        }

        initTabs() {
            // 初始化標籤頁
            $('.namecardgen-tabs').each(function() {
                $(this).tabs({
                    activate: function(event, ui) {
                        // 更新 URL hash
                        window.location.hash = ui.newPanel.attr('id');
                    }
                });
                
                // 從 URL hash 激活對應標籤頁
                const hash = window.location.hash;
                if (hash) {
                    const tab = $('.namecardgen-tabs a[href="' + hash + '"]');
                    if (tab.length) {
                        tab.click();
                    }
                }
            });
        }

        initModals() {
            // 初始化模態框
            $('.namecardgen-modal').dialog({
                autoOpen: false,
                modal: true,
                width: '80%',
                maxWidth: 800,
                height: 'auto',
                classes: {
                    "ui-dialog": "namecardgen-dialog",
                    "ui-dialog-titlebar": "namecardgen-dialog-titlebar"
                },
                open: function() {
                    $('body').addClass('namecardgen-modal-open');
                },
                close: function() {
                    $('body').removeClass('namecardgen-modal-open');
                }
            });
        }

        initBulkActions() {
            // 初始化批量操作下拉選單
            $('.namecardgen-bulk-action').select2({
                minimumResultsForSearch: -1,
                width: '200px'
            });
        }

        handleFormSubmit(e) {
            const form = $(e.target);
            const submitButton = form.find('input[type="submit"], button[type="submit"]');
            
            // 顯示載入狀態
            submitButton.prop('disabled', true).addClass('is-busy');
            
            // 添加載入指示器
            if (!form.find('.namecardgen-loading').length) {
                form.append('<div class="namecardgen-loading">處理中...</div>');
            }
            
            // 如果是 AJAX 表單，阻止預設提交
            if (form.hasClass('namecardgen-ajax-form')) {
                e.preventDefault();
                this.submitAjaxForm(form);
            }
        }

        submitAjaxForm(form) {
            const formData = new FormData(form[0]);
            
            $.ajax({
                url: namecardgen_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.handleAjaxResponse(response, form);
                },
                error: (xhr, status, error) => {
                    this.showNotice('發生錯誤：' + error, 'error');
                    this.resetForm(form);
                }
            });
        }

        handleAjaxResponse(response, form) {
            if (response.success) {
                this.showNotice(response.data.message, 'success');
                
                // 如果有重定向URL，進行重定向
                if (response.data.redirect_url) {
                    setTimeout(() => {
                        window.location.href = response.data.redirect_url;
                    }, 1000);
                } else {
                    // 重新載入頁面或更新內容
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            } else {
                this.showNotice(response.data.message, 'error');
                this.resetForm(form);
            }
        }

        resetForm(form) {
            const submitButton = form.find('input[type="submit"], button[type="submit"]');
            submitButton.prop('disabled', false).removeClass('is-busy');
            form.find('.namecardgen-loading').remove();
        }

        confirmDelete(e) {
            const message = $(e.target).data('confirm') || namecardgen_admin.confirm_delete;
            if (!confirm(message)) {
                e.preventDefault();
            }
        }

        handleBulkAction(e) {
            const action = $(e.target).val();
            const selectedItems = $('.namecardgen-checkbox:checked');
            
            if (selectedItems.length === 0) {
                this.showNotice('請先選擇項目', 'warning');
                return;
            }
            
            if (action) {
                this.executeBulkAction(action, selectedItems);
            }
        }

        executeBulkAction(action, selectedItems) {
            const itemIds = selectedItems.map(function() {
                return $(this).val();
            }).get();
            
            $.ajax({
                url: namecardgen_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'namecardgen_admin_action',
                    sub_action: 'bulk_' + action,
                    item_ids: itemIds,
                    nonce: namecardgen_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice(response.data.message, 'success');
                        // 重新載入頁面
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        this.showNotice(response.data.message, 'error');
                    }
                },
                error: () => {
                    this.showNotice('操作失敗，請重試', 'error');
                }
            });
        }

        handleStatusToggle(e) {
            const toggle = $(e.target);
            const itemId = toggle.data('item-id');
            const itemType = toggle.data('item-type');
            const newStatus = toggle.is(':checked') ? 'active' : 'inactive';
            
            $.ajax({
                url: namecardgen_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'namecardgen_admin_action',
                    sub_action: 'update_status',
                    item_id: itemId,
                    item_type: itemType,
                    status: newStatus,
                    nonce: namecardgen_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('狀態已更新', 'success');
                    } else {
                        this.showNotice(response.data.message, 'error');
                        // 恢復之前的狀態
                        toggle.prop('checked', !toggle.is(':checked'));
                    }
                },
                error: () => {
                    this.showNotice('更新失敗，請重試', 'error');
                    toggle.prop('checked', !toggle.is(':checked'));
                }
            });
        }

        handleSearch(e) {
            const searchInput = $(e.target);
            const searchTerm = searchInput.val();
            const searchTimeout = searchInput.data('timeout');
            
            // 清除之前的計時器
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // 設置新的計時器
            searchInput.data('timeout', setTimeout(() => {
                this.performSearch(searchTerm);
            }, 500));
        }

        performSearch(searchTerm) {
            const currentUrl = new URL(window.location.href);
            
            if (searchTerm) {
                currentUrl.searchParams.set('s', searchTerm);
            } else {
                currentUrl.searchParams.delete('s');
            }
            
            // 重定向到搜尋結果
            window.location.href = currentUrl.toString();
        }

        showTooltip(e) {
            const element = $(e.target);
            const tooltipText = element.data('tooltip');
            
            if (tooltipText) {
                $('<div class="namecardgen-tooltip-content">' + tooltipText + '</div>').appendTo('body');
                
                const tooltip = $('.namecardgen-tooltip-content');
                const position = element.offset();
                
                tooltip.css({
                    top: position.top - tooltip.outerHeight() - 10,
                    left: position.left + (element.outerWidth() / 2) - (tooltip.outerWidth() / 2)
                }).fadeIn(200);
            }
        }

        hideTooltip() {
            $('.namecardgen-tooltip-content').fadeOut(200, function() {
                $(this).remove();
            });
        }

        showNotice(message, type = 'info') {
            // 移除現有的通知
            $('.namecardgen-notice').remove();
            
            const noticeClass = 'notice notice-' + type;
            const notice = $('<div class="' + noticeClass + ' namecardgen-notice is-dismissible"><p>' + message + '</p></div>');
            
            // 添加到頁面頂部
            $('.wrap').prepend(notice);
            
            // 添加關閉功能
            notice.on('click', '.notice-dismiss', function() {
                $(this).closest('.namecardgen-notice').remove();
            });
            
            // 5秒後自動消失
            setTimeout(() => {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }

        // 工具方法
        formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('zh-TW') + ' ' + date.toLocaleTimeString('zh-TW');
        }

        debounce(func, wait, immediate) {
            let timeout;
            return function() {
                const context = this, args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    }

    // 頁面載入完成後初始化
    $(document).ready(function() {
        window.namecardgenAdmin = new NamecardGenAdmin();
        
        // 初始化資料表格功能
        initDataTables();
        initQuickEdit();
        initInlineEdit();
    });

    // 資料表格功能
    function initDataTables() {
        $('.wp-list-table').each(function() {
            const table = $(this);
            
            // 添加排序功能
            table.find('th.sortable a').on('click', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                window.location.href = url;
            });
            
            // 添加行選擇功能
            table.find('tbody .check-column input[type="checkbox"]').on('change', function() {
                const allChecked = table.find('tbody .check-column input[type="checkbox"]:checked').length === 
                                 table.find('tbody .check-column input[type="checkbox"]').length;
                table.find('thead .check-column input[type="checkbox"]').prop('checked', allChecked);
            });
            
            table.find('thead .check-column input[type="checkbox"]').on('change', function() {
                table.find('tbody .check-column input[type="checkbox"]').prop('checked', $(this).is(':checked'));
            });
        });
    }

    // 快速編輯功能
    function initQuickEdit() {
        $(document).on('click', '.editinline', function() {
            const postId = $(this).closest('tr').attr('id').replace('post-', '');
            
            // 顯示載入中
            const row = $(this).closest('tr');
            row.after('<tr class="inline-edit-row"><td colspan="6">載入中...</td></tr>');
            
            // 載入編輯表單
            $.ajax({
                url: namecardgen_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'namecardgen_admin_action',
                    sub_action: 'get_quick_edit_form',
                    post_id: postId,
                    nonce: namecardgen_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        row.next('.inline-edit-row').html(response.data.html);
                    } else {
                        row.next('.inline-edit-row').html('載入失敗');
                    }
                }
            });
        });
    }

    // 行內編輯功能
    function initInlineEdit() {
        $(document).on('click', '.namecardgen-inline-edit', function() {
            const field = $(this).closest('.namecardgen-editable-field');
            const originalValue = field.data('original-value');
            const fieldName = field.data('field-name');
            const itemId = field.data('item-id');
            
            // 創建編輯表單
            const editForm = `
                <div class="namecardgen-inline-edit-form">
                    <input type="text" value="${originalValue}" class="namecardgen-edit-input">
                    <button type="button" class="button button-small save-edit">保存</button>
                    <button type="button" class="button button-small cancel-edit">取消</button>
                </div>
            `;
            
            field.hide().after(editForm);
            
            // 保存編輯
            $(document).on('click', '.save-edit', function() {
                const newValue = $('.namecardgen-edit-input').val();
                saveInlineEdit(itemId, fieldName, newValue, field);
            });
            
            // 取消編輯
            $(document).on('click', '.cancel-edit', function() {
                $('.namecardgen-inline-edit-form').remove();
                field.show();
            });
        });
    }

    function saveInlineEdit(itemId, fieldName, newValue, field) {
        $.ajax({
            url: namecardgen_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'namecardgen_admin_action',
                sub_action: 'inline_edit',
                item_id: itemId,
                field_name: fieldName,
                field_value: newValue,
                nonce: namecardgen_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    field.text(newValue).show();
                    $('.namecardgen-inline-edit-form').remove();
                    window.namecardgenAdmin.showNotice('更新成功', 'success');
                } else {
                    window.namecardgenAdmin.showNotice(response.data.message, 'error');
                }
            },
            error: function() {
                window.namecardgenAdmin.showNotice('更新失敗', 'error');
            }
        });
    }

})(jQuery);
