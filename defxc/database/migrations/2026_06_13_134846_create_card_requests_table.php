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
        Schema::create('card_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('credit_score', 10);
            $table->string('img_one', 191)->nullable();
            $table->string('img_two', 191)->nullable();
            $table->string('status');  // pending, approved, rejected
            $table->string('type', 100)->nullable();  // card type label
            $table->timestamps();
 
            $table->index(['wallet_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_requests');
    }
};
