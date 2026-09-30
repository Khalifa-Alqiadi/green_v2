<?php

namespace App\Http\Controllers\Dashboard;

use App\Exceptions\AiOperationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\ResourceAiContentRequest;
use App\Http\Requests\Ai\ResourceAiTranslateRequest;
use App\Http\Requests\Ai\WebsiteAiSeoRequest;
use App\Services\Ai\ResourceAiService;
use Illuminate\Http\JsonResponse;

class ResourceAiController extends Controller
{
    public function __construct(private readonly ResourceAiService $ai) {}

    public function content(ResourceAiContentRequest $request): JsonResponse
    {
        return $this->respond(fn () => $this->ai->content(
            $request->string('resource_type')->toString(),
            $request->language(),
            $request->validated(),
            $request->resource(),
        ));
    }

    public function translate(ResourceAiTranslateRequest $request): JsonResponse
    {
        $allowed = match ($request->input('resource_type')) {
            'setting' => ['seo_title', 'seo_description', 'seo_keywords'],
            'menu' => ['title'],
            default => ['title', 'details'],
        };
        $fields = collect($request->validated('fields'))->only($allowed)->filter(fn ($value) => filled($value))->all();
        abort_if($fields === [], 422, __('backend.aiContentRequired'));

        return $this->respond(fn () => $this->ai->translate(
            $request->string('resource_type')->toString(),
            $request->language('source_language'),
            $request->language('target_language'),
            $fields,
            $request->resource(),
            $request->integer('context_id') ?: null,
        ));
    }

    public function websiteSeo(WebsiteAiSeoRequest $request): JsonResponse
    {
        return $this->respond(fn () => $this->ai->websiteSeo(
            $request->language(),
            $request->validated(),
            $request->resource(),
        ));
    }

    private function respond(callable $callback): JsonResponse
    {
        try {
            return response()->json($callback());
        } catch (AiOperationException $exception) {
            return response()->json([
                'message' => __('backend.aiErrorGeneric'),
                'code' => $exception->errorCode,
                'request_id' => $exception->requestId,
            ], $exception->httpStatus);
        }
    }
}
