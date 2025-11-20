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
        Schema::create('semaphore_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique()->nullable();
            $table->string('number'); // Recipient phone number
            $table->text('message'); // Message content
            $table->string('status')->nullable(); // Message status
            $table->string('sender_name')->nullable();
            $table->timestamp('sent_at')->nullable(); // When message was sent
            $table->timestamp('retrieved_at')->nullable(); // When we retrieved from API
            $table->json('raw_data')->nullable(); // Store full API response
            $table->timestamps();
            
            $table->index('number');
            $table->index('sent_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semaphore_messages');
    }
};
