<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AiGeneratedAsset;
use App\Services\Ai\GeneratedImageAssetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

class AiGeneratedAssetController extends Controller
{
    public function __construct(private readonly GeneratedImageAssetService $assets) {}

    public function preview(Request $request, AiGeneratedAsset $asset): StreamedResponse|Response
    {
        if (!$asset->isAvailableFor($request->user()->id)) {
            return response('', 404, ['Cache-Control' => 'private, no-store, max-age=0']);
        }
        $stream = Storage::disk($asset->disk)->readStream($asset->path);
        if ($stream === false) {
            return response('', 404, ['Cache-Control' => 'private, no-store, max-age=0']);
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $asset->mime_type,
            'Content-Length' => (string) $asset->byte_size,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline; filename="ai-preview"',
        ]);
    }

    public function destroy(Request $request, AiGeneratedAsset $asset): JsonResponse
    {
        if (!$asset->isAvailableFor($request->user()->id)) {
            return response()->json(['message' => __('backend.aiUnavailable'), 'code' => 'asset_not_found'], 404);
        }
        $this->assets->delete($asset);

        return response()->json(['data' => ['deleted' => true]]);
    }
}
