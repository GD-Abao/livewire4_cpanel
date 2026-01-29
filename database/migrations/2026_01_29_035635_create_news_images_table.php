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
        Schema::create('news_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('news_id'); // 關聯 news 表的外鍵
            $table->string('image_url'); // 圖片網址
            $table->unsignedInteger('sort_number')->default(0); // 排序編號，預設為 0
            $table->timestamps();
            $table->index(['news_id', 'sort_number']); // 建立索引以提升查詢效能
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_images');
    }
};
