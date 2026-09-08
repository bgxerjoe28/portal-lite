<?php

namespace Modules\Cbt\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CbtImageController
{
    /**
     * Upload gambar soal CBT dari RichTextEditor.
     * Dapat diakses oleh admin, guru, atau pengguna dengan hak akses CBT.
     *
     * Route: POST /cbt/questions/upload-image
     * Name:  cbt.questions.upload_image
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:10240', // Maksimal 10MB
            'bank_id' => 'nullable|integer',
        ]);

        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $bankId = $request->input('bank_id');

        // Pola nama: cbt_bank_{bankId}_{timestamp}_{random}.{ext}
        $prefix = $bankId ? "cbt_bank_{$bankId}_" : "cbt_img_";
        $filename = $prefix . time() . '_' . Str::random(8) . '.' . $ext;

        $disk = Storage::disk(config('filesystems.cbt_disk', 's3_cbt'));
        $contents = file_get_contents($file->getRealPath());

        // 1. Simpan ke disk aktif (MinIO S3 / CBT disk)
        try {
            $disk->put("cbt_questions/{$filename}", $contents, 'public');
        } catch (\Throwable $e) {
            // Jika storage utama timeout/down, fallback ke disk lokal
        }

        // 2. Simpan juga ke disk lokal sebagai fallback
        try {
            $mediaDest = storage_path('app/public/cbt_questions');
            if (!is_dir($mediaDest)) {
                @mkdir($mediaDest, 0755, true);
            }
            file_put_contents("{$mediaDest}/{$filename}", $contents);
        } catch (\Throwable $e) {
            // Silently continue
        }

        $url = route('cbt.questions.image', ['filename' => $filename]);

        return response()->json([
            'url'      => $url,
            'filename' => $filename,
        ]);
    }
    /**
     * Stream a CBT question image from the active storage disk (MinIO, S3, or local).
     *
     * Route: GET /cbt/questions/image/{filename}
     * Name:  cbt.questions.image
     *
     * Why a proxy instead of a direct MinIO URL?
     * - MinIO buckets default to private; direct URLs return 403.
     * - Setting bucket-level public policies requires server admin access.
     * - Pre-signed URLs expire and are stored permanently in question_text HTML.
     * - This proxy works with ANY storage backend without configuration changes.
     */
    public function show(Request $request, string $filename): StreamedResponse
    {
        // Sanitize: allow only safe filename characters, block path traversal
        $filename = basename($filename);
        if (! preg_match('/^[\w\-\.]+$/', $filename)) {
            abort(400, 'Invalid filename.');
        }

        $cdnUrl = config('filesystems.disks.s3_cbt.url');
        if ($cdnUrl && env('CBT_DIRECT_CDN_URL', false) && ! $request->boolean('stream')) {
            $directUrl = rtrim($cdnUrl, '/') . '/cbt_questions/' . $filename;
            return redirect()->away($directUrl, 301, [
                'Cache-Control' => 'public, max-age=86400, s-maxage=2592000, immutable',
            ]);
        }

        $path = 'cbt_questions/' . $filename;
        $disk = Storage::disk(config('filesystems.cbt_disk', 's3_cbt'));

        if (! $disk->exists($path)) {
            // Fallback: try the public local disk (legacy images stored before MinIO migration)
            $publicDisk = Storage::disk('public');
            if ($publicDisk->exists($path)) {
                $disk = $publicDisk;
            } else {
                abort(404, 'Image not found.');
            }
        }

        $mimeType = $disk->mimeType($path) ?: 'image/png';
        $size     = $disk->size($path);

        return response()->stream(function () use ($disk, $path) {
            $stream = $disk->readStream($path);
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type'        => $mimeType,
            'Content-Length'      => $size,
            'Cache-Control'       => 'public, max-age=86400, s-maxage=2592000, immutable', // Cache 1 hari browser, 30 hari di CDN Edge
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
