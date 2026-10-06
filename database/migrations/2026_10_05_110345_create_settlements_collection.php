
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $collection) {
            $collection->index('group_id');
            $collection->index('paid_by');
            $collection->index('paid_to');
            $collection->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};