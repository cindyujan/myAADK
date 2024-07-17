<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('klien_update_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klien_id')->constrained('klien')->onDelete('cascade');
            $table->json('requested_data'); // Store the requested updates in JSON format
            $table->enum('status', ['Baharu','Kemaskini', 'Lulus', 'Ditolak'])->default('Kemaskini');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('klien_update_requests');
    }
};
