<?php

namespace App\Http\Controllers\Dashboard;

use App\Exceptions\AiOperationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\TopicAiAltRequest;
use App\Http\Requests\Ai\TopicAiContentRequest;
use App\Http\Requests\Ai\TopicAiImageRequest;
use App\Http\Requests\Ai\TopicAiSeoRequest;
use App\Http\Requests\Ai\TopicAiTranslateRequest;
use App\Services\Ai\GeneratedImageAssetService;
use App\Services\Ai\TopicAiService;
use App\Services\Ai\TopicImageService;
use Illuminate\Http\JsonResponse;

class TopicAiController extends Controller
{
    public function __construct(
        private readonly TopicAiService $ai,
        private readonly TopicImageService $images,
        private readonly GeneratedImageAssetService $assets,
    ) {}

    public function content(TopicAiContentRequest $request): JsonResponse
    {
        return $this->respond(fn () => $this->ai->content($request->section(), $request->language(), $request->validated(), $request->topic()));
    }

    public function seo(TopicAiSeoRequest $request): JsonResponse
    {
        return $this->respond(fn () => $this->ai->seo($request->section(), $request->language(), $request->validated(), $request->topic()));
    }

    public function translate(TopicAiTranslateRequest $request): JsonResponse
    {
        $allowed = $request->input('scope') === 'seo'
            ? ['seo_title', 'seo_description', 'seo_keywords', 'seo_url_slug', 'photo_alt']
            : ['title', 'details', 'photo_alt'];
        $fields = collect($request->validated('fields'))->only($allowed)->filter(fn ($value) => filled($value))->all();
        abort_if($fields === [], 422, __('backend.aiContentRequired'));

        return $this->respond(fn () => $this->ai->translate(
            $request->section(),
            $request->language('source_language'),
            $request->language('target_language'),
            $fields,
            $request->topic(),
        ));
    }

    public function image(TopicAiImageRequest $request): JsonResponse
    {
        return $this->respond(fn () => $this->images->generate($request->user(), $request->section(), $request->language(), $request->validated(), $request->topic()));
    }

    public function alt(TopicAiAltRequest $request): JsonResponse
    {
        $prompt = '';
        if ($request->filled('asset_id')) {
            $asset = $this->assets->findAvailable($request->input('asset_id'), $request->user());
            $prompt = $asset->optimized_prompt;
        }

        return $this->respond(fn () => $this->ai->alt($request->section(), $request->language(), $request->validated(), $request->topic(), $prompt));
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
