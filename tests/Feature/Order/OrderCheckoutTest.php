<?php

namespace Tests\Feature\Order;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderCheckoutTest extends TestCase
{
    protected ?User $customer = null;
    protected ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::where('email', 'customer@example.com')->first();
        if (!$this->customer) {
            $customerRoleId = DB::table('roles')->where('code', 'customer')->value('id');
            $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
            $userId = DB::table('users')->insertGetId([
                'fullname' => 'Nguyễn Văn A',
                'email' => 'customer@example.com',
                'password_hash' => '$2y$12$e/a/e.8qJ3d1hN1yv0.0qO7b6/2a6.9yQk/qY6.rXv8eO6Wl1kK/q', // customer123
                'role_id' => $customerRoleId,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->customer = User::find($userId);
        }

        $this->admin = User::where('email', 'admin@techshop.vn')->first();
    }

    /**
     * Helper chuẩn bị variant và tồn kho
     */
    protected function prepareProductVariant(int $initialStock = 10): object
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        $categoryId = DB::table('categories')->where('slug', 'dien-thoai')->value('id');
        if (!$categoryId) {
            $categoryId = DB::table('categories')->insertGetId([
                'name' => 'Điện thoại',
                'slug' => 'dien-thoai',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Test Phone ' . uniqid(),
            'slug' => 'test-phone-' . uniqid(),
            'status' => 'Active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $sku = 'TEST-SKU-' . strtoupper(uniqid());
        $variantId = DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'sku' => $sku,
            'variant_name' => '128GB Black',
            'price' => 10000000,
            'sale_price' => 9000000,
            'status' => 'Active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('inventory')->insert([
            'variant_id' => $variantId,
            'quantity' => $initialStock,
            'updated_at' => $now,
        ]);

        return (object) [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'sku' => $sku,
            'price' => 10000000,
            'sale_price' => 9000000,
        ];
    }

    /**
     * Helper tạo active cart và thêm item
     */
    protected function setupCartWithItem(int $variantId, int $quantity): int
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        // Tìm hoặc tạo active cart
        $cart = DB::table('carts')
            ->where('user_id', $this->customer->id)
            ->where('status', 'Active')
            ->first();

        if (!$cart) {
            $cartId = DB::table('carts')->insertGetId([
                'user_id' => $this->customer->id,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $cartId = $cart->id;
            DB::table('cart_items')->where('cart_id', $cartId)->delete();
        }

        DB::table('cart_items')->insert([
            'cart_id' => $cartId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $cartId;
    }

    /**
     * Tạo một đơn VietQR Pending để kiểm thử thao tác đối soát của admin.
     */
    protected function checkoutVietQrOrder(int $variantId, int $quantity): string
    {
        $this->setupCartWithItem($variantId, $quantity);

        $response = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'VietQR',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.payment_method', 'VietQR')
            ->assertJsonPath('data.status', 'Pending');

        return $response->json('data.order_number');
    }

    /**
     * Test checkout thất bại khi giỏ hàng trống (422 EMPTY_CART)
     */
    public function test_checkout_empty_cart_returns_422_error(): void
    {
        // Xóa sạch items trong cart active của user
        $cart = DB::table('carts')
            ->where('user_id', $this->customer->id)
            ->where('status', 'Active')
            ->first();

        if ($cart) {
            DB::table('cart_items')->where('cart_id', $cart->id)->delete();
        }

        $response = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'COD',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'EMPTY_CART',
                ],
            ]);
    }

    /**
     * Test checkout COD thành công, trừ kho đúng và xóa giỏ hàng
     */
    public function test_checkout_cod_success_and_deducts_inventory(): void
    {
        $variant = $this->prepareProductVariant(10);
        $this->setupCartWithItem($variant->variant_id, 2);

        $response = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'COD',
            'note' => 'Giao trong giờ hành chính',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'Pending',
                    'payment_method' => 'COD',
                    'payment_url' => null,
                    'total_amount' => 18000000, // 9,000,000 * 2
                ],
            ]);

        $orderData = $response->json('data');
        $orderNumber = $orderData['order_number'];

        // Kiểm tra tồn kho đã bị trừ từ 10 xuống 8
        $currentStock = DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity');
        $this->assertEquals(8, $currentStock);

        // Kiểm tra đơn hàng trong DB
        $orderInDb = DB::table('orders')->where('order_number', $orderNumber)->first();
        $this->assertNotNull($orderInDb);
        $this->assertEquals('Pending', $orderInDb->status);

        // Kiểm tra payments trong DB
        $paymentInDb = DB::table('payments')->where('order_id', $orderInDb->id)->first();
        $this->assertNotNull($paymentInDb);
        $this->assertEquals('COD', $paymentInDb->method);
        $this->assertEquals('Pending', $paymentInDb->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $orderInDb->id,
            'actor_user_id' => $this->customer->id,
            'from_status' => null,
            'to_status' => 'Pending',
            'action' => 'checkout_created',
        ]);

        // Kiểm tra giỏ hàng đã được xóa sạch
        $cart = DB::table('carts')->where('user_id', $this->customer->id)->where('status', 'Active')->first();
        $itemsInCart = DB::table('cart_items')->where('cart_id', $cart->id)->count();
        $this->assertEquals(0, $itemsInCart);
    }

    /**
     * Voucher phải được báo giá theo server, lưu vào đơn và hạ số tiền payment.
     */
    public function test_checkout_with_voucher_persists_discount_and_payment_amount(): void
    {
        $variant = $this->prepareProductVariant(5);
        $this->setupCartWithItem($variant->variant_id, 1);
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        $code = 'TEST10-' . strtoupper(uniqid());

        $voucherId = DB::table('vouchers')->insertGetId([
            'code' => $code,
            'name' => 'Giảm 10% cho kiểm thử',
            'discount_type' => 'Percentage',
            'discount_value' => 10,
            'max_discount_amount' => 500000,
            'min_order_amount' => 0,
            'usage_limit' => 1,
            'used_count' => 0,
            'per_user_limit' => 1,
            'status' => 'Active',
            'starts_at' => null,
            'ends_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->actingAs($this->customer)
            ->postJson('/api/vouchers/preview', ['code' => strtolower($code)])
            ->assertOk()
            ->assertJsonPath('data.code', $code)
            ->assertJsonPath('data.discount_amount', 500000)
            ->assertJsonPath('data.total_amount', 8500000);

        $response = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'VietQR',
            'voucher_code' => $code,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.voucher_code', $code)
            ->assertJsonPath('data.discount_amount', 500000)
            ->assertJsonPath('data.total_amount', 8500000)
            ->assertJsonPath('data.payment.amount', 8500000);

        $orderId = (int) $response->json('data.order_id');
        $this->assertDatabaseHas('voucher_redemptions', [
            'voucher_id' => $voucherId,
            'user_id' => $this->customer->id,
            'order_id' => $orderId,
            'discount_amount' => 500000,
        ]);
        $this->assertSame(1, (int) DB::table('vouchers')->where('id', $voucherId)->value('used_count'));
    }

    /**
     * Test checkout VietQR trả đủ dữ liệu chuyển khoản, vẫn giữ payment Pending
     * và không tạo payment attempt/webhook.
     */
    public function test_checkout_vietqr_returns_transfer_instructions(): void
    {
        $variant = $this->prepareProductVariant(5);
        $this->setupCartWithItem($variant->variant_id, 1);

        $response = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'VietQR',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.payment_method', 'VietQR')
            ->assertJsonPath('data.payment.status', 'Pending')
            ->assertJsonPath('data.payment_instructions.bank_code', 'MB')
            ->assertJsonPath('data.payment_instructions.account_number', '0123456789')
            ->assertJsonPath('data.payment_instructions.amount', 9000000);

        $orderNumber = $response->json('data.order_number');
        $instructions = $response->json('data.payment_instructions');
        $this->assertStringContainsString(rawurlencode("Thanh toan {$orderNumber}"), $instructions['qr_image_url']);

        $order = DB::table('orders')->where('order_number', $orderNumber)->first();
        $this->assertNotNull($order);
        $this->assertNull($order->payment_expires_at);

        $payment = DB::table('payments')->where('order_id', $order->id)->first();
        $this->assertEquals('VietQR', $payment->method);
        $this->assertEquals('Pending', $payment->status);
        $this->assertSame(0, DB::table('payment_attempts')->where('payment_id', $payment->id)->count());

        $this->actingAs($this->customer)
            ->getJson("/api/orders/{$orderNumber}")
            ->assertOk()
            ->assertJsonPath('data.payment_instructions.bank_code', 'MB')
            ->assertJsonPath('data.payment_instructions.amount', 9000000)
            ->assertJsonPath('data.payment_instructions.transfer_content', "Thanh toan {$orderNumber}");
    }

    /**
     * Admin xác nhận VietQR: payment Paid và order Confirmed, không hoàn kho.
     */
    public function test_admin_can_confirm_pending_vietqr_payment_once(): void
    {
        $variant = $this->prepareProductVariant(10);
        $orderNumber = $this->checkoutVietQrOrder($variant->variant_id, 2);

        $this->assertEquals(8, DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity'));

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/payments/{$orderNumber}/confirm-vietqr");

        $response->assertOk()
            ->assertJsonPath('data.order_number', $orderNumber)
            ->assertJsonPath('data.order_status', 'Confirmed')
            ->assertJsonPath('data.payment.method', 'VietQR')
            ->assertJsonPath('data.payment.status', 'Paid');

        $order = DB::table('orders')->where('order_number', $orderNumber)->first();
        $payment = DB::table('payments')->where('order_id', $order->id)->first();
        $this->assertEquals('Confirmed', $order->status);
        $this->assertEquals('Paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertEquals(8, DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity'));
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'actor_user_id' => $this->admin->id,
            'from_status' => 'Pending',
            'to_status' => 'Confirmed',
            'action' => 'vietqr_confirmed',
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/payments/{$orderNumber}/confirm-vietqr")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'INVALID_VIETQR_PAYMENT_STATE');
    }

    /**
     * Nghiệm thu end-to-end: VietQR được đối soát xong vẫn đi hết luồng giao hàng
     * và audit trail ghi đủ các mốc nghiệp vụ.
     */
    public function test_paid_vietqr_order_can_complete_delivery_workflow_with_full_history(): void
    {
        $variant = $this->prepareProductVariant(10);
        $orderNumber = $this->checkoutVietQrOrder($variant->variant_id, 1);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/payments/{$orderNumber}/confirm-vietqr")
            ->assertOk()
            ->assertJsonPath('data.order_status', 'Confirmed')
            ->assertJsonPath('data.payment.status', 'Paid');

        foreach (['Processing', 'Shipped', 'Delivered'] as $status) {
            $this->actingAs($this->admin)
                ->patchJson("/api/admin/orders/{$orderNumber}/status", ['status' => $status])
                ->assertOk()
                ->assertJsonPath('data.status', $status);
        }

        $this->actingAs($this->admin)
            ->getJson("/api/admin/orders/{$orderNumber}")
            ->assertOk()
            ->assertJsonPath('data.status', 'Delivered')
            ->assertJsonPath('data.payment.status', 'Paid')
            ->assertJsonPath('data.status_history.0.action', 'checkout_created')
            ->assertJsonPath('data.status_history.1.action', 'vietqr_confirmed')
            ->assertJsonPath('data.status_history.2.to_status', 'Processing')
            ->assertJsonPath('data.status_history.3.to_status', 'Shipped')
            ->assertJsonPath('data.status_history.4.to_status', 'Delivered');

        $order = DB::table('orders')->where('order_number', $orderNumber)->first();
        $this->assertEquals('Delivered', $order->status);
        $this->assertEquals(9, DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity'));
    }

    /**
     * Admin hủy VietQR Pending: hoàn kho đúng một lần dù gọi endpoint lặp lại.
     */
    public function test_admin_can_cancel_pending_vietqr_payment_and_restore_inventory_once(): void
    {
        $variant = $this->prepareProductVariant(10);
        $orderNumber = $this->checkoutVietQrOrder($variant->variant_id, 3);

        $this->assertEquals(7, DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity'));

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/payments/{$orderNumber}/cancel-vietqr", [
                'reason' => 'Chưa nhận được tiền chuyển khoản',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.order_number', $orderNumber)
            ->assertJsonPath('data.order_status', 'Cancelled')
            ->assertJsonPath('data.payment.status', 'Cancelled');

        $order = DB::table('orders')->where('order_number', $orderNumber)->first();
        $payment = DB::table('payments')->where('order_id', $order->id)->first();
        $this->assertEquals('Cancelled', $order->status);
        $this->assertStringContainsString('Admin hủy VietQR: Chưa nhận được tiền chuyển khoản', $order->note);
        $this->assertEquals('Cancelled', $payment->status);
        $this->assertEquals(10, DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity'));
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'actor_user_id' => $this->admin->id,
            'from_status' => 'Pending',
            'to_status' => 'Cancelled',
            'action' => 'vietqr_cancelled',
            'reason' => 'Chưa nhận được tiền chuyển khoản',
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/payments/{$orderNumber}/cancel-vietqr")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'INVALID_VIETQR_PAYMENT_STATE');

        $this->assertEquals(10, DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity'));
    }

    /**
     * API chi tiết đơn admin trả audit trail đầy đủ, theo thứ tự tạo đơn rồi đối soát VietQR.
     */
    public function test_admin_order_detail_returns_status_history(): void
    {
        $variant = $this->prepareProductVariant(10);
        $orderNumber = $this->checkoutVietQrOrder($variant->variant_id, 1);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/payments/{$orderNumber}/confirm-vietqr")
            ->assertOk();

        $this->actingAs($this->admin)
            ->getJson("/api/admin/orders/{$orderNumber}")
            ->assertOk()
            ->assertJsonPath('data.status_history.0.action', 'checkout_created')
            ->assertJsonPath('data.status_history.0.to_status', 'Pending')
            ->assertJsonPath('data.status_history.1.action', 'vietqr_confirmed')
            ->assertJsonPath('data.status_history.1.from_status', 'Pending')
            ->assertJsonPath('data.status_history.1.to_status', 'Confirmed')
            ->assertJsonPath('data.status_history.1.actor.email', 'admin@techshop.vn');
    }

    /**
     * Khách hàng không có quyền admin.payments không thể xử lý VietQR.
     */
    public function test_customer_cannot_process_vietqr_payment(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/admin/payments/ORD-00000001/confirm-vietqr')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'PERMISSION_DENIED');
    }

    /**
     * Admin không được xác nhận VietQR Pending qua API trạng thái đơn chung.
     */
    public function test_admin_cannot_confirm_pending_vietqr_through_generic_order_status_api(): void
    {
        $variant = $this->prepareProductVariant(10);
        $orderNumber = $this->checkoutVietQrOrder($variant->variant_id, 1);

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/orders/{$orderNumber}/status", ['status' => 'Confirmed'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'VIETQR_REQUIRES_PAYMENT_CONFIRMATION');

        $order = DB::table('orders')->where('order_number', $orderNumber)->first();
        $payment = DB::table('payments')->where('order_id', $order->id)->first();
        $this->assertEquals('Pending', $order->status);
        $this->assertEquals('Pending', $payment->status);
    }

    /**
     * Admin không được hủy VietQR Pending qua API trạng thái chung, tránh bỏ qua payment flow.
     */
    public function test_admin_cannot_cancel_pending_vietqr_through_generic_order_status_api(): void
    {
        $variant = $this->prepareProductVariant(10);
        $orderNumber = $this->checkoutVietQrOrder($variant->variant_id, 2);

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/orders/{$orderNumber}/status", ['status' => 'Cancelled'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'VIETQR_REQUIRES_PAYMENT_ACTION');

        $this->assertEquals(8, DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity'));
        $order = DB::table('orders')->where('order_number', $orderNumber)->first();
        $payment = DB::table('payments')->where('order_id', $order->id)->first();
        $this->assertEquals('Pending', $order->status);
        $this->assertEquals('Pending', $payment->status);
    }

    /**
     * Đơn đã nhận tiền không thể bị hủy qua API trạng thái vì chưa có nghiệp vụ hoàn tiền.
     */
    public function test_admin_cannot_cancel_paid_vietqr_order_through_generic_order_status_api(): void
    {
        $variant = $this->prepareProductVariant(10);
        $orderNumber = $this->checkoutVietQrOrder($variant->variant_id, 1);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/payments/{$orderNumber}/confirm-vietqr")
            ->assertOk();

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/orders/{$orderNumber}/status", ['status' => 'Cancelled'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'ORDER_ALREADY_PAID');

        $order = DB::table('orders')->where('order_number', $orderNumber)->first();
        $payment = DB::table('payments')->where('order_id', $order->id)->first();
        $this->assertEquals('Confirmed', $order->status);
        $this->assertEquals('Paid', $payment->status);
        $this->assertEquals(9, DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity'));
    }

    /**
     * Luồng trạng thái chung vẫn được dùng để xác nhận đơn COD chưa thanh toán.
     */
    public function test_admin_can_confirm_pending_cod_through_generic_order_status_api(): void
    {
        $variant = $this->prepareProductVariant(10);
        $this->setupCartWithItem($variant->variant_id, 1);

        $checkout = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'COD',
        ]);
        $orderNumber = $checkout->json('data.order_number');

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/orders/{$orderNumber}/status", ['status' => 'Confirmed'])
            ->assertOk()
            ->assertJsonPath('data.previous_status', 'Pending')
            ->assertJsonPath('data.status', 'Confirmed');

        $order = DB::table('orders')->where('order_number', $orderNumber)->first();
        $payment = DB::table('payments')->where('order_id', $order->id)->first();
        $this->assertEquals('Confirmed', $order->status);
        $this->assertEquals('Pending', $payment->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'actor_user_id' => $this->admin->id,
            'from_status' => 'Pending',
            'to_status' => 'Confirmed',
            'action' => 'admin_status_updated',
        ]);
    }

    /**
     * Admin hủy COD có thể ghi lý do, đồng thời đồng bộ note và audit event.
     */
    public function test_admin_can_cancel_pending_cod_with_reason(): void
    {
        $variant = $this->prepareProductVariant(10);
        $this->setupCartWithItem($variant->variant_id, 2);

        $checkout = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'COD',
        ]);
        $orderNumber = $checkout->json('data.order_number');

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/orders/{$orderNumber}/status", [
                'status' => 'Cancelled',
                'reason' => 'Khách yêu cầu hủy trước khi giao',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Cancelled')
            ->assertJsonPath('data.reason', 'Khách yêu cầu hủy trước khi giao');

        $order = DB::table('orders')->where('order_number', $orderNumber)->first();
        $this->assertStringContainsString('Admin hủy đơn: Khách yêu cầu hủy trước khi giao', $order->note);
        $this->assertEquals(10, DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity'));
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'actor_user_id' => $this->admin->id,
            'from_status' => 'Pending',
            'to_status' => 'Cancelled',
            'action' => 'admin_status_updated',
            'reason' => 'Khách yêu cầu hủy trước khi giao',
        ]);
    }

    /**
     * Lý do chỉ hợp lệ cho thao tác hủy đơn, không được gắn vào trạng thái khác.
     */
    public function test_admin_order_status_reason_is_rejected_when_not_cancelling(): void
    {
        $variant = $this->prepareProductVariant(10);
        $this->setupCartWithItem($variant->variant_id, 1);

        $checkout = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'COD',
        ]);
        $orderNumber = $checkout->json('data.order_number');

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/orders/{$orderNumber}/status", [
                'status' => 'Confirmed',
                'reason' => 'Không hợp lệ ở trạng thái này',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    /**
     * Thu tiền COD là payment action nên phải ghi audit, dù trạng thái đơn giữ nguyên.
     */
    public function test_admin_confirming_cod_payment_writes_history(): void
    {
        $variant = $this->prepareProductVariant(10);
        $this->setupCartWithItem($variant->variant_id, 1);

        $checkout = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'COD',
        ]);
        $orderNumber = $checkout->json('data.order_number');

        foreach (['Confirmed', 'Processing', 'Shipped'] as $status) {
            $this->actingAs($this->admin)
                ->patchJson("/api/admin/orders/{$orderNumber}/status", ['status' => $status])
                ->assertOk();
        }

        $this->actingAs($this->admin)
            ->postJson("/api/admin/payments/{$orderNumber}/confirm-cod")
            ->assertOk()
            ->assertJsonPath('data.payment.status', 'Paid');

        $order = DB::table('orders')->where('order_number', $orderNumber)->first();
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'actor_user_id' => $this->admin->id,
            'from_status' => 'Shipped',
            'to_status' => 'Shipped',
            'action' => 'cod_payment_confirmed',
        ]);
    }

    /**
     * Test checkout thất bại khi kho không đủ số lượng (409 INSUFFICIENT_STOCK)
     */
    public function test_checkout_insufficient_stock_returns_409(): void
    {
        $variant = $this->prepareProductVariant(1);
        $this->setupCartWithItem($variant->variant_id, 5); // Đặt 5 trong khi tồn chỉ có 1

        $response = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'COD',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INSUFFICIENT_STOCK',
                ],
            ]);

        // Đảm bảo tồn kho không bị trừ khi lỗi
        $currentStock = DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity');
        $this->assertEquals(1, $currentStock);
    }

    /**
     * Test khách hàng hủy đơn Pending thì hoàn lại tồn kho nguyên tử
     */
    public function test_cancel_pending_order_restores_inventory(): void
    {
        $variant = $this->prepareProductVariant(10);
        $this->setupCartWithItem($variant->variant_id, 3);

        $checkoutRes = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'COD',
        ]);

        $orderNumber = $checkoutRes->json('data.order_number');

        // Tồn kho hiện tại là 7
        $this->assertEquals(7, DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity'));

        // Hủy đơn
        $cancelRes = $this->actingAs($this->customer)->postJson("/api/orders/{$orderNumber}/cancel", [
            'reason' => 'Đổi ý không mua nữa',
        ]);

        $cancelRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'order_number' => $orderNumber,
                    'status' => 'Cancelled',
                ],
            ]);

        // Tồn kho phải được hoàn lại đúng 10
        $this->assertEquals(10, DB::table('inventory')->where('variant_id', $variant->variant_id)->value('quantity'));

        // Trạng thái order và payment trong DB là Cancelled
        $order = DB::table('orders')->where('order_number', $orderNumber)->first();
        $this->assertEquals('Cancelled', $order->status);

        $payment = DB::table('payments')->where('order_id', $order->id)->first();
        $this->assertEquals('Cancelled', $payment->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'actor_user_id' => $this->customer->id,
            'from_status' => 'Pending',
            'to_status' => 'Cancelled',
            'action' => 'customer_cancelled',
            'reason' => 'Đổi ý không mua nữa',
        ]);
    }

    /**
     * VNPay đã bị loại khỏi payment contract; request cũ phải bị validation từ chối.
     */
    public function test_checkout_rejects_removed_vnpay_method(): void
    {
        $response = $this->actingAs($this->customer)->postJson('/api/orders/checkout', [
            'fullname' => 'Nguyễn Văn A',
            'phone' => '0987654321',
            'address' => '123 Đường ABC, Hà Nội',
            'payment_method' => 'VNPay',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    /**
     * Các endpoint VNPay cũ không còn được đăng ký.
     */
    public function test_vnpay_routes_are_not_registered(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/payments/vnpay/create', ['order_number' => 'ORD-00000001'])
            ->assertNotFound();
    }
}
