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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');

            /**
             * The 'content' column stores the user's input:
             * {"email": "user@example.com", "message": "Hello!"}
             */
            $table->json('content');
            $table->json('ai_metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'created_at'], 'submissions_form_created_index');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
