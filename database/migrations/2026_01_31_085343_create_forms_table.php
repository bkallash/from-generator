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
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->json('schema'); // ← All fields stored here
            $table->mediumText('ai_insights')->nullable();
            $table->timestamp('ai_insights_updated_at')->nullable();
            $table->json('settings')->nullable(); // Notifications, redirects, etc.
            $table->timestamps();

            $table->index(['user_id', 'is_active'], 'forms_user_active_index');
            $table->index(['user_id', 'updated_at'], 'forms_user_updated_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
