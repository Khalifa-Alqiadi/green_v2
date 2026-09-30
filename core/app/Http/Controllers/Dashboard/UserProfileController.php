<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\FileHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserAccountRequest;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Models\Permissions;
use App\Models\User;
use App\Models\WebmasterSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserProfileController extends Controller
{
    private string $uploadPath = 'users';

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $actor = request()->user();
        $canChangePassword = $actor->can('changePassword', $user);
        $canManageMcp = $actor->can('manageMcp', $user);
        $canUpdateAccount = $actor->can('updateAccount', $user);
        $permissions = $canUpdateAccount ? Permissions::orderBy('id')->get() : collect();
        $GeneralWebmasterSections = WebmasterSection::where('status', 1)->orderBy('row_no')->get();
        $mcpTokens = $canManageMcp
            ? $user->mcpTokens()->latest()->get()
            : collect();

        return view('dashboard.users.show', compact(
            'user',
            'permissions',
            'mcpTokens',
            'canChangePassword',
            'canManageMcp',
            'canUpdateAccount',
            'GeneralWebmasterSections'
        ));
    }

    public function legacyEdit(User $user): RedirectResponse
    {
        $this->authorize('view', $user);

        return redirect()->route('userProfile.show', $user);
    }

    public function update(UpdateUserProfileRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $oldPhoto = $user->photo;
        $newPhoto = null;

        if ($request->hasFile('photo')) {
            $newPhoto = FileHelper::uploadFile($request->file('photo'), $this->uploadPath, 1)['name'] ?? null;
        }

        try {
            $user->name = strip_tags($validated['name']);
            $user->email = strip_tags($validated['email']);

            if ($newPhoto) {
                $user->photo = $newPhoto;
            } elseif ($request->boolean('photo_delete')) {
                $user->photo = null;
            }

            $user->updated_by = $request->user()->id;
            $user->save();
        } catch (\Throwable $exception) {
            if ($newPhoto) {
                FileHelper::deleteFile($this->uploadPath.'/'.$newPhoto);
            }

            throw $exception;
        }

        if ($oldPhoto && $oldPhoto !== 'profile.jpg' && $oldPhoto !== $user->photo) {
            FileHelper::deleteFile($this->uploadPath.'/'.$oldPhoto);
        }

        return redirect()->route('userProfile.show', $user)
            ->with('profile_tab', 'profile')
            ->with('doneMessage', __('backend.saveDone'));
    }

    public function updatePassword(UpdateUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->password = Hash::make($request->validated('password'));
        $user->updated_by = $request->user()->id;
        $user->save();

        return redirect()->route('userProfile.show', $user)
            ->with('profile_tab', 'password')
            ->with('doneMessage', __('backend.passwordUpdated'));
    }

    public function updateAccount(UpdateUserAccountRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $user->permissions_id = $validated['permissions_id'];
        $user->status = $validated['status'];
        $user->updated_by = $request->user()->id;
        $user->save();

        return redirect()->route('userProfile.show', $user)
            ->with('profile_tab', 'account')
            ->with('doneMessage', __('backend.saveDone'));
    }
}
