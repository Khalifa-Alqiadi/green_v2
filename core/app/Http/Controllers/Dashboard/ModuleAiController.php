<?php

namespace App\Http\Controllers\Dashboard;

use App\Exceptions\AiOperationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\ModuleAiCreateRequest;
use App\Models\WebmasterSection;
use App\Services\Ai\AiSettingsService;
use App\Services\Ai\ModuleAiService;
use App\Services\Dashboard\ModuleCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ModuleAiController extends Controller
{
    public function create(ModuleAiCreateRequest $request, ModuleAiService $ai, ModuleCreationService $creator): JsonResponse
    {
        $user = $request->user();
        $description = $request->validated('description');
        $key = 'module-ai:'.$user->id.':'.hash('sha256', app()->getLocale().'|'.$description);
        $timeout = (int) app(AiSettingsService::class)->settings()->ai_timeout_seconds;
        // Serialize creation per user, including other tabs and sessions.
        $lock = Cache::lock('module-ai:user:'.$user->id, max(900, $timeout + 300));
        if (! $lock->get()) {
            return response()->json(['message' => __('backend.aiModuleBusy'), 'code' => 'module_creation_busy'], 409);
        }

        try {
            $result = Cache::get($key);
            if (! is_array($result) || ! WebmasterSection::query()->whereKey($result['id'])->where('created_by', $user->id)->exists()) {
                $definition = $ai->generateDefinition($user, $description);
                $section = $creator->createFromDefinition($definition, $user);
                $result = [
                    'id' => $section->id,
                    'warnings' => array_values(array_unique([...$definition['warnings'], ...$definition['unsupported_requirements']])),
                ];
                // Store only the committed result, never the requirements or definition.
                Cache::put($key, $result, now()->addDay());
            }
            $request->session()->flash('doneMessage', __('backend.addDone'));
            if ($result['warnings'] !== []) {
                $request->session()->flash('warningMessage', implode('<br>', array_map(fn ($warning) => e($warning), $result['warnings'])));
            }

            return response()->json([
                'message' => __('backend.addDone'),
                'warnings' => $result['warnings'],
                'redirect' => route('WebmasterSectionsEdit', $result['id']),
            ], 201);
        } catch (AiOperationException $exception) {
            return response()->json([
                'message' => __($exception->errorCode === 'module_requirements_unsupported' ? 'backend.aiModuleClarify' : 'backend.aiErrorGeneric'),
                'code' => $exception->errorCode,
                'request_id' => $exception->requestId,
            ], $exception->httpStatus);
        } catch (ValidationException $exception) {
            return response()->json(['message' => __('backend.aiModuleClarify'), 'code' => 'module_definition_invalid'], 422);
        } catch (Throwable $exception) {
            Log::error('AI module creation failed.', ['user_id' => $user->id, 'exception' => $exception::class]);

            return response()->json(['message' => __('backend.aiModuleCreationFailed'), 'code' => 'module_creation_failed'], 500);
        } finally {
            $lock->release();
        }
    }
}
