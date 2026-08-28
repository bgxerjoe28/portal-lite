<?php

namespace Modules\Akademik\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Akademik\Services\GoogleSheetPermitSyncService;

class GoogleSheetPermitWebhookController extends Controller
{
    public function syncSheet(Request $request, GoogleSheetPermitSyncService $syncService)
    {
        $sheetUrl = $request->input('sheet_url');
        $onlyUnchecked = $request->boolean('only_unchecked', true);

        try {
            $result = $syncService->syncFromSheetUrl($sheetUrl, $onlyUnchecked);
            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
