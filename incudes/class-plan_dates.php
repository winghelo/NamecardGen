<div class="wrap">
    <h1>計劃日期管理</h1>
    
    <div style="background: #f8d7da; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
        <h3>分配計劃日期</h3>
        <form method="post">
            <input type="hidden" name="action" value="assign_plan_date">
            <?php wp_nonce_field('assign_plan_date_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="select_client">選擇客戶</label></th>
                    <td>
                        <select id="select_client" name="client_id" required style="width: 300px;">
                            <option value="">-- 選擇客戶 --</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="select_plan">選擇計劃</label></th>
                    <td>
                        <select id="select_plan" name="plan_id" required style="width: 300px;">
                            <option value="">-- 選擇計劃 --</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="start_date">開始日期</label></th>
                    <td><input type="date" id="start_date" name="start_date" required style="width: 200px;"></td>
                </tr>
                <tr>
                    <th><label for="end_date">結束日期</label></th>
                    <td><input type="date" id="end_date" name="end_date" required style="width: 200px;"></td>
                </tr>
            </table>
            <button type="submit" class="button button-primary">分配計劃</button>
        </form>
    </div>

    <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
        <h3>計劃日期列表</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>客戶</th>
                    <th>計劃</th>
                    <th>開始日期</th>
                    <th>結束日期</th>
                    <th>狀態</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px;">
                        <p>暫無計劃日期資料</p>
                        <p>請先添加客戶和計劃，然後分配日期</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
