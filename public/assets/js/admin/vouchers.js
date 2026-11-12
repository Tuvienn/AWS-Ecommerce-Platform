document.addEventListener('DOMContentLoaded', function () {
    const $ = (id) => document.getElementById(id);
    const form = $('voucher-form');
    const tableBody = $('voucher-table-body');
    let vouchers = [];

    const formatMoney = (value) => window.Utils?.formatPrice
        ? window.Utils.formatPrice(value)
        : Number(value || 0).toLocaleString('vi-VN') + ' ₫';
    const escape = (value) => window.Utils?.escapeHtml
        ? window.Utils.escapeHtml(value)
        : String(value || '');
    const toast = (message, type) => window.Utils?.showToast ? window.Utils.showToast(message, type) : alert(message);

    const formatDateInput = (value) => value ? String(value).slice(0, 16).replace(' ', 'T') : '';
    const nullableNumber = (id) => {
        const value = $(id).value.trim();
        return value === '' ? null : Number(value);
    };

    function resetForm() {
        form.reset();
        $('voucher-id').value = '';
        $('voucher-code').disabled = false;
        $('voucher-min-order').value = '0';
        $('voucher-user-limit').value = '1';
        $('voucher-status').value = 'Active';
        $('btn-save-voucher').innerHTML = '<i class="fa-solid fa-plus me-1"></i> Tạo voucher';
        $('btn-reset-voucher').classList.add('d-none');
    }

    function benefit(voucher) {
        if (voucher.discount_type === 'Percentage') {
            const cap = voucher.max_discount_amount ? ', tối đa ' + formatMoney(voucher.max_discount_amount) : '';
            return 'Giảm ' + voucher.discount_value + '%' + cap;
        }
        return 'Giảm ' + formatMoney(voucher.discount_value);
    }

    function render() {
        if (!vouchers.length) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Chưa có voucher phù hợp.</td></tr>';
            return;
        }

        tableBody.innerHTML = vouchers.map((voucher) => {
            const totalLimit = voucher.usage_limit === null ? 'Không giới hạn' : voucher.used_count + '/' + voucher.usage_limit;
            const minOrder = Number(voucher.min_order_amount) > 0 ? 'Đơn từ ' + formatMoney(voucher.min_order_amount) : 'Không yêu cầu đơn tối thiểu';
            const status = voucher.status === 'Active'
                ? '<span class="badge bg-success">Đang bật</span>'
                : '<span class="badge bg-secondary">Tạm tắt</span>';
            return '<tr>' +
                '<td><strong class="text-primary">' + escape(voucher.code) + '</strong><div class="small text-muted">' + escape(voucher.name) + '</div></td>' +
                '<td>' + escape(benefit(voucher)) + '</td>' +
                '<td class="small">' + escape(minOrder) + '<div class="text-muted">Tối đa ' + voucher.per_user_limit + ' lượt/khách</div></td>' +
                '<td>' + totalLimit + '</td>' +
                '<td>' + status + '</td>' +
                '<td class="text-end"><button class="btn btn-sm btn-outline-primary btn-edit-voucher" data-id="' + voucher.id + '"><i class="fa-solid fa-pen"></i> Sửa</button></td>' +
                '</tr>';
        }).join('');

        document.querySelectorAll('.btn-edit-voucher').forEach((button) => {
            button.addEventListener('click', () => editVoucher(button.dataset.id));
        });
    }

    async function load() {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Đang tải voucher...</td></tr>';
        try {
            const params = new URLSearchParams();
            const q = $('voucher-search').value.trim();
            const status = $('voucher-status-filter').value;
            if (q) params.set('q', q);
            if (status) params.set('status', status);
            const suffix = params.toString() ? '?' + params.toString() : '';
            const response = await window.ApiClient.get('/api/admin/vouchers' + suffix);
            vouchers = response.data || [];
            render();
        } catch (error) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Không thể tải danh sách voucher.</td></tr>';
            toast(error?.message || 'Không thể tải voucher.', 'error');
        }
    }

    function editVoucher(id) {
        const voucher = vouchers.find((item) => String(item.id) === String(id));
        if (!voucher) return;
        $('voucher-id').value = voucher.id;
        $('voucher-code').value = voucher.code;
        $('voucher-code').disabled = true;
        $('voucher-name').value = voucher.name;
        $('voucher-type').value = voucher.discount_type;
        $('voucher-value').value = voucher.discount_value;
        $('voucher-max-discount').value = voucher.max_discount_amount || '';
        $('voucher-min-order').value = voucher.min_order_amount;
        $('voucher-usage-limit').value = voucher.usage_limit || '';
        $('voucher-user-limit').value = voucher.per_user_limit;
        $('voucher-status').value = voucher.status;
        $('voucher-starts-at').value = formatDateInput(voucher.starts_at);
        $('voucher-ends-at').value = formatDateInput(voucher.ends_at);
        $('btn-save-voucher').innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu thay đổi';
        $('btn-reset-voucher').classList.remove('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const id = $('voucher-id').value;
        const data = {
            name: $('voucher-name').value.trim(),
            discount_type: $('voucher-type').value,
            discount_value: Number($('voucher-value').value),
            max_discount_amount: nullableNumber('voucher-max-discount'),
            min_order_amount: Number($('voucher-min-order').value || 0),
            usage_limit: nullableNumber('voucher-usage-limit'),
            per_user_limit: Number($('voucher-user-limit').value || 1),
            status: $('voucher-status').value,
            starts_at: $('voucher-starts-at').value || null,
            ends_at: $('voucher-ends-at').value || null
        };
        if (!id) data.code = $('voucher-code').value.trim();

        try {
            $('btn-save-voucher').disabled = true;
            if (id) {
                await window.ApiClient.patch('/api/admin/vouchers/' + encodeURIComponent(id), data);
            } else {
                await window.ApiClient.post('/api/admin/vouchers', data);
            }
            toast(id ? 'Đã cập nhật voucher.' : 'Đã tạo voucher.', 'success');
            resetForm();
            load();
        } catch (error) {
            toast(error?.message || 'Không thể lưu voucher.', 'error');
        } finally {
            $('btn-save-voucher').disabled = false;
        }
    });

    $('btn-reset-voucher').addEventListener('click', resetForm);
    $('btn-filter-voucher').addEventListener('click', load);
    $('voucher-search').addEventListener('keydown', (event) => {
        if (event.key === 'Enter') load();
    });

    load();
});
