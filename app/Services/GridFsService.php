<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use MongoDB\GridFS\Bucket;

class GridFsService
{
    public function bucket(): Bucket
    {
        $bucket = DB::connection('mongodb')->getMongoDB()->selectGridFSBucket();

        // $client = $connection->getMongoClient();

        // $databaseName = config(
        //     'database.connections.mongodb.database'
        // );

        // $database = $client->selectDatabase($databaseName);

        // return $database->selectGridFSBucket([
        //     'bucketName' => 'fs',
        // ]);
        return $bucket;
    }
}
