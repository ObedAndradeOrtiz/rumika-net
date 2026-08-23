<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 60);
            $table->string('label', 40)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['client_id', 'is_primary']);
        });

        DB::table('clients')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($clients) {
                foreach ($clients as $client) {
                    DB::table('client_phones')->insert([
                        'client_id' => $client->id,
                        'phone' => $client->phone,
                        'label' => 'Principal',
                        'is_primary' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_phones');
    }
};
