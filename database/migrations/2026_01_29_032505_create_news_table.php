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
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // 標題
            $table->text('body'); // 內容
            $table->text('locale'); // 語系
            $table->string('image_url')->nullable(); // 圖片 -> 建議用這個命名
            $table->integer('sort_number')->default(1); // 排序 -> 建議用這個命名
            $table->boolean('is_active')->default(true); // 是否啟用 -> 建議用這個命名
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
