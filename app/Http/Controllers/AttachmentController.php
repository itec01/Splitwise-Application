<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Group;
use App\Models\Settlement;
use App\Services\GridFsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\ObjectId;
use Throwable;

class AttachmentController extends Controller
{
    public function __construct(
        protected GridFsService $gridFsService
    ) {}

    // Upload image to MongoDB GridFS.
    public function store(Request $request, string $settlementId)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:2048',
            ],
        ]);

        $settlement = Settlement::find($settlementId);

        if (! $settlement) {
            return response()->json([
                'success' => false,
                'message' => 'Settlement not found.',
            ], 404);
        }

        // Check settlement access and group membership
        $group = Group::find($settlement->group_id);
        $user = $request->user();

        if (! $group || ! $user || ! in_array((string) $user->getKey(), $group->member_ids ?? [], true)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to perform this action.',
            ], 403);
        }

        if ((string) $settlement->paid_by !== (string) $user->getKey()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to perform this action.',
            ], 403);
        }

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();

        $bucket = $this->gridFsService->bucket();
        $fileId = null;

        try {
            $stream = fopen($file->getRealPath(), 'rb');

            if ($stream === false) {
                throw new \RuntimeException(
                    'Unable to read uploaded file.'
                );
            }

            try {
                $fileId = $bucket->uploadFromStream(
                    $fileName,
                    $stream,
                    [
                        'metadata' => [
                            'settlement_id' => (string) $settlement->_id,
                        ],
                    ]
                );
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $attachment = Attachment::create([
                'settlement_id' => (string) $settlement->_id,
                'file_id' => (string) $fileId,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attachment uploaded successfully.',
                'data' => [
                    'id' => (string) $attachment->_id,
                    'settlement_id' => $attachment->settlement_id,
                    'file_id' => $attachment->file_id,
                    'file_name' => $attachment->file_name,
                    'file_size' => $attachment->file_size,
                    'mime_type' => $attachment->mime_type,
                ],
            ], 201);

        } catch (Throwable $e) {
            if ($fileId !== null) {
                try {
                    $bucket->delete($fileId);
                } catch (Throwable $cleanupError) {
                    Log::error('GridFS cleanup failed.');
                }
            }

            Log::error('Attachment upload failed.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to upload attachment.',
            ], 500);
        }
    }

    // Download image from MongoDB GridFS.
    public function download(Request $request, string $attachmentId)
    {
        if (! preg_match('/^[a-f0-9]{24}$/i', $attachmentId)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid attachment ID.',
            ], 422);
        }

        $attachment = Attachment::find($attachmentId);

        if (! $attachment) {
            return response()->json([
                'success' => false,
                'message' => 'Attachment not found.',
            ], 404);
        }

        $settlement = Settlement::find($attachment->settlement_id);

        if (! $settlement) {
            return response()->json([
                'success' => false,
                'message' => 'Settlement not found.',
            ], 404);
        }

        // Check settlement access and group membership
        $group = Group::find($settlement->group_id);
        $user = $request->user();

        if (! $group || ! $user || ! in_array((string) $user->getKey(), $group->member_ids ?? [], true)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to perform this action.',
            ], 403);
        }

        try {
            $bucket = $this->gridFsService->bucket();
            $fileId = new ObjectId($attachment->file_id);

            $directory = storage_path('app/public/attachment');
            if (! file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $fileName = $attachmentId.'_'.basename($attachment->file_name);
            $filePath = $directory.'/'.$fileName;

            $stream = $bucket->openDownloadStream($fileId);
            $fileStream = fopen($filePath, 'w');

            if ($fileStream === false) {
                throw new \Exception('Could not open local file for writing.');
            }

            try {
                stream_copy_to_stream($stream, $fileStream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
                if (is_resource($fileStream)) {
                    fclose($fileStream);
                }
            }

            return response()->json([
                'success' => true,
                'path' => 'app/public/attachment/'.$fileName,
            ], 200);
        } catch (Throwable $e) {
            Log::error('Attachment download failed.', [
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'file not downloaded',
            ], 500);
        }
    }
}
