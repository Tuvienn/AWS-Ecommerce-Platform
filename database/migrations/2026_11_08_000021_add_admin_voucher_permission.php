<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');

        DB::table('permissions')->updateOrInsert(
            ['code' => 'admin.vouchers'],
            ['name' => 'Quản lý mã giảm giá', 'created_at' => $now]
        );

        $permissionId = DB::table('permissions')->where('code', 'admin.vouchers')->value('id');
        $adminRoleId = DB::table('roles')->where('code', 'admin')->value('id');

        if ($permissionId && $adminRoleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $adminRoleId, 'permission_id' => $permissionId],
                []
            );
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('code', 'admin.vouchers')->value('id');
        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
