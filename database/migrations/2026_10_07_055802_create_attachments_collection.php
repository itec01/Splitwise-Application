<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $database = DB::connection('mongodb')->getMongoDB();

        // Convert the collection names iterator into an array.
        $collections = iterator_to_array(
            $database->listCollectionNames()
        );

        // Create the attachments collection if it does not exist.
        if (! in_array('attachments', $collections, true)) {
            $database->createCollection('attachments');
        }
    }

    public function down(): void
    {
        $database = DB::connection('mongodb')->getMongoDB();

        $collections = iterator_to_array(
            $database->listCollectionNames()
        );

        if (in_array('attachments', $collections, true)) {
            $database->dropCollection('attachments');
        }
    }
};
