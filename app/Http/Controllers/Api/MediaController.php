<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    /**
     * Upload an image, video, thumbnail, or avatar and persist its record in MySQL.
     * Supports both standard multipart/form-data ('file') and JSON/base64 ('file_base64'/'file_data').
     */
    public function upload(Request $request): JsonResponse
    {
        $type = $request->input('type', 'image');
        $userId = $request->input('user_id', 'anonymous');
        $postId = $request->input('post_id');

        $fileName = null;
        $fileContents = null;
        $mimeType = null;
        $fileSize = 0;

        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');
            if (!$uploadedFile->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Uploaded file is invalid: ' . $uploadedFile->getErrorMessage(),
                ], 400);
            }

            $extension = $uploadedFile->getClientOriginalExtension();
            if (empty($extension)) {
                $extension = match ($type) {
                    'video' => 'mp4',
                    default => 'jpg',
                };
            }

            $fileName = Str::uuid()->toString() . '.' . $extension;
            $mimeType = $uploadedFile->getMimeType() ?: 'application/octet-stream';
            $fileSize = $uploadedFile->getSize() ?: 0;
            $fileContents = file_get_contents($uploadedFile->getRealPath());
        } elseif ($request->filled('file_base64') || $request->filled('file_data')) {
            $rawBase64 = $request->input('file_base64') ?: $request->input('file_data');
            
            // Strip data uri prefix if present (e.g. data:image/jpeg;base64,...)
            if (preg_match('/^data:([^;]+);base64,(.+)$/', $rawBase64, $matches)) {
                $mimeType = $matches[1];
                $rawBase64 = $matches[2];
            }

            $fileContents = base64_decode($rawBase64);
            if ($fileContents === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid base64 payload.',
                ], 400);
            }

            $fileSize = strlen($fileContents);
            $extension = $request->input('extension');
            if (empty($extension)) {
                $extension = match ($type) {
                    'video' => 'mp4',
                    default => 'jpg',
                };
            }
            $fileName = Str::uuid()->toString() . '.' . $extension;
            if (empty($mimeType)) {
                $mimeType = match ($extension) {
                    'mp4' => 'video/mp4',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    default => 'image/jpeg',
                };
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No file or file_base64 provided in request.',
            ], 422);
        }

        $folder = match ($type) {
            'avatar' => "avatars/{$userId}",
            'video' => "moments/{$userId}/videos",
            'thumbnail' => "moments/{$userId}/thumbnails",
            default => "moments/{$userId}/images",
        };

        $relativePath = "{$folder}/{$fileName}";

        // Save file to disk
        Storage::disk('public')->put($relativePath, $fileContents);

        // Generate accessible URL with CORS support
        $url = $request->root() . '/media/file/' . $relativePath;


        // Save media record in MySQL database
        $media = Media::create([
            'user_id' => $userId,
            'post_id' => $postId,
            'file_name' => $fileName,
            'file_path' => $relativePath,
            'file_type' => $type,
            'mime_type' => $mimeType ?: 'application/octet-stream',
            'file_size' => $fileSize,
            'url' => $url,
        ]);

        return response()->json([
            'success' => true,
            'url' => $url,
            'media' => $media,
        ], 201);
    }

    /**
     * Serve or stream media file (supports byte-range headers for video playback).
     */
    public function show(int|string $id): BinaryFileResponse|JsonResponse
    {
        $media = is_numeric($id) ? Media::find($id) : Media::where('file_name', $id)->first();

        if (!$media) {
            return response()->json(['message' => 'Media not found.'], 404);
        }

        $fullPath = storage_path('app/public/' . $media->file_path);
        if (!file_exists($fullPath)) {
            return response()->json(['message' => 'File not found on disk.'], 404);
        }

        return response()->file($fullPath, [
            'Content-Type' => $media->mime_type ?: 'application/octet-stream',
            'Accept-Ranges' => 'bytes',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Allow-Headers' => '*',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Serve or stream media file by relative storage path with CORS headers.
     */
    public function serveFile(Request $request, string $path): BinaryFileResponse|JsonResponse
    {
        $fullPath = storage_path('app/public/' . $path);
        if (!file_exists($fullPath)) {
            return response()->json(['message' => 'File not found on disk.'], 404);
        }

        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        $mime = match (strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            default => mime_content_type($fullPath) ?: 'application/octet-stream',
        };

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Accept-Ranges' => 'bytes',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Allow-Headers' => '*',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}


