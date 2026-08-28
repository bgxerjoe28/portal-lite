<?php

namespace Modules\Cbt\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CbtImageController
{
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
