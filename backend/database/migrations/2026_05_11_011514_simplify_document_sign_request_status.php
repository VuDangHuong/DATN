<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('document_sign_requests')
            ->whereIn('status', ['admin_reviewing', 'forwarded'])
            ->update(['status' => 'pending']);
 
        DB::table('document_sign_requests')
            ->where('status', 'completed')
            ->update(['status' => 'signed']);
 
        DB::table('document_sign_requests')
            ->whereIn('status', ['rejected_by_admin', 'rejected_by_lecturer'])
            ->update(['status' => 'rejected']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
