<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Filter combinations a person saved on the recordings list, by name, to apply again with one click.
 */
return new class extends Migration
{
    public function __construct()
    {
        $this->connection = config('session-replay.storage.connection');
    }

    public function up(): void
    {
        Schema::connection($this->connection)->create('replay_saved_filters', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type');
            $table->string('owner_id');
            $table->string('name');
            $table->json('filters');
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('replay_saved_filters');
    }
};
