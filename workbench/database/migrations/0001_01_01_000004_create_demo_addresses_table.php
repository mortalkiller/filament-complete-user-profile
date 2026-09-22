<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('label');
            $table->string('line_one');
            $table->string('city');
            $table->string('country_code', 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_addresses');
    }
};
