<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\RevokeUserMcpTokenRequest;
use App\Http\Requests\StoreUserMcpTokenRequest;
use App\Http\Requests\UpdateUserMcpStatusRequest;
use App\Http\Requests\UpdateUserMcpTokenRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class UserMcpController extends Controller
{
    public function updateStatus(UpdateUserMcpStatusRequest $request, User $user): RedirectResponse
    {
        $user->mcp_enabled = $request->boolean('mcp_enabled');
        $user->updated_by = $request->user()->id;
        $user->save();

        return redirect()->route('userProfile.show', $user)
            ->with('profile_tab', 'mcp')
            ->with('doneMessage', __('backend.saveDone'));
    }

    public function store(StoreUserMcpTokenRequest $request, User $user): JsonResponse
    {
        $newAccessToken = $user->createToken($request->validated('name'), ['mcp:use']);

        return response()->json([
            'message' => __('backend.mcpTokenCreated'),
            'token' => $newAccessToken->plainTextToken,
            'token_record' => [
                'id' => $newAccessToken->accessToken->id,
                'name' => $newAccessToken->accessToken->name,
                'created_at' => $newAccessToken->accessToken->created_at?->toIso8601String(),
            ],
        ], 201)->withHeaders([
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
        ]);
    }

    public function destroy(
        RevokeUserMcpTokenRequest $request,
        User $user,
        int $token
    ): RedirectResponse {
        $mcpToken = $user->mcpTokens()->whereKey($token)->firstOrFail();
        $mcpToken->delete();

        return redirect()->route('userProfile.show', $user)
            ->with('profile_tab', 'mcp')
            ->with('doneMessage', __('backend.mcpTokenRevoked'));
    }

    public function update(
        UpdateUserMcpTokenRequest $request,
        User $user,
        int $token
    ): RedirectResponse {
        $mcpToken = $user->mcpTokens()->whereKey($token)->firstOrFail();
        $mcpToken->name = $request->validated('name');
        $mcpToken->save();

        return redirect()->route('userProfile.show', $user)
            ->with('profile_tab', 'mcp')
            ->with('doneMessage', __('backend.mcpTokenUpdated'));
    }
}
