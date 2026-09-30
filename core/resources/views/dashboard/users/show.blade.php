@extends('dashboard.layouts.master')
@section('title', __('backend.userProfile'))

@push('after-styles')
    <style>
        .user-profile-page {
            --profile-primary: #11bba4;
            --profile-dark: #2d3e4e;
            --profile-muted: #64748b;
        }

        .user-profile-page .profile-hero {
            position: relative;
            overflow: hidden;
            padding: 2rem;
            border: 0;
            border-radius: 12px;
        }

        [dir="rtl"] .user-profile-page .profile-hero:after {
            right: auto;
            left: -55px;
        }

        .user-profile-page .profile-hero__content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-profile-page .profile-avatar {
            width: 104px;
            height: 104px;
            flex: 0 0 104px;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, .88);
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .2);
        }

        .user-profile-page .profile-identity {
            min-width: 0;
            flex: 1;
        }

        .user-profile-page .profile-identity h2 {
            margin: 0 0 .3rem;
            font-size: 1.65rem;
            font-weight: 600;
        }

        .user-profile-page .profile-email {
            margin: 0 0 .9rem;
        }

        .user-profile-page .profile-badges {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .user-profile-page .profile-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .38rem .7rem;
            border-radius: 99px;
            font-size: .78rem;
        }

        .user-profile-page .profile-badge__dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #cbd5e1;
        }

        .user-profile-page .profile-badge__dot.is-active {
            background: #5ee095;
            box-shadow: 0 0 0 3px rgba(94, 224, 149, .17);
        }

        .user-profile-page .profile-back {
            position: relative;
            z-index: 2;
            align-self: flex-start;
            padding: .55rem .8rem;
            border: 1px solid rgba(0, 0, 0, .1);
            border-radius: 8px;
            background: rgba(255, 255, 255, .1);
        }

        .user-profile-page .profile-shell {
            margin-top: 1.25rem;
            overflow: hidden;
            border-radius: 6px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
        }

        .user-profile-page .profile-nav {
            display: flex;
            overflow-x: auto;
            margin: 0;
            padding: 0;
            white-space: nowrap;
        }

        .user-profile-page .profile-nav .nav-link {
            display: flex;
            align-items: center;
            gap: .45rem;
            padding: 1rem 1.1rem .85rem;
            border: 0;
            border-bottom: 3px solid transparent;
            font-weight: 500;
        }

        .user-profile-page .profile-nav .nav-link.active {
            color: var(--profile-primary);
            border-bottom-color: var(--profile-primary);
            background: transparent;
        }

        .user-profile-page .profile-nav .material-icons {
            font-size: 20px;
        }

        .user-profile-page .profile-panel {
            padding: 1.75rem;
        }

        .user-profile-page .section-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .user-profile-page .section-heading h4 {
            margin: 0 0 .35rem;
            font-weight: 600;
        }

        .user-profile-page .section-heading p {
            max-width: 700px;
            margin: 0;
        }

        .user-profile-page .mcp-summary {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
            padding: 1.25rem;
            border-radius: 10px;
        }

        .user-profile-page .mcp-status {
            display: flex;
            align-items: center;
            gap: .9rem;
        }

        .user-profile-page .mcp-status__icon {
            display: flex;
            width: 46px;
            height: 46px;
            align-items: center;
            justify-content: center;
            flex: 0 0 46px;
            color: var(--profile-primary);
            border-radius: 10px;
            background: #dff1fb;
        }

        .user-profile-page .mcp-status__icon .material-icons {
            font-size: 25px;
        }

        .user-profile-page .mcp-status h5 {
            margin: 0 0 .2rem;
            font-weight: 600;
        }

        .user-profile-page .mcp-status p {
            margin: 0;
        }

        .user-profile-page .status-pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .38rem .7rem;
            border-radius: 99px;
            font-size: .78rem;
            font-weight: 600;
        }

        .user-profile-page .status-pill.is-enabled {
            color: #08783e;
            background: #ddf7e8;
        }

        .user-profile-page .status-pill.is-disabled {
            color: #64748b;
            background: #e9eef3;
        }

        .user-profile-page .status-pill:before {
            width: 7px;
            height: 7px;
            content: '';
            border-radius: 50%;
            background: currentColor;
        }

        .user-profile-page .token-card {
            overflow: hidden;
            border-radius: 10px;
        }

        .user-profile-page .token-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.1rem 1.25rem;
        }

        .user-profile-page .token-card__header h5 {
            margin: 0 0 .2rem;
            font-weight: 600;
        }

        .user-profile-page .token-card__header p {
            margin: 0;
            font-size: .85rem;
        }

        .user-profile-page .token-table {
            margin: 0;
        }

        .user-profile-page .token-table th {
            padding: .85rem 1rem;
            border-top: 0;
            font-size: .76rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .user-profile-page .token-table td {
            padding: .95rem 1rem;
            vertical-align: middle;
            border-color: #edf1f5;
        }

        .user-profile-page .token-name {
            display: flex;
            align-items: center;
            gap: .7rem;
            font-weight: 600;
        }

        .user-profile-page .token-name__icon {
            display: flex;
            width: 34px;
            height: 34px;
            align-items: center;
            justify-content: center;
            flex: 0 0 34px;
            color: var(--profile-primary);
            border-radius: 8px;
            background: #e8f4fb;
        }

        .user-profile-page .token-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .4rem;
        }

        .user-profile-page .token-actions .btn {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
        }

        .user-profile-page .token-empty {
            padding: 3rem 1.25rem;
            color: var(--profile-muted);
            text-align: center;
        }

        .user-profile-page .token-empty .material-icons {
            display: block;
            margin-bottom: .6rem;
            color: #a9bac8;
            font-size: 42px;
        }

        .user-profile-page .modal-content {
            overflow: hidden;
            border: 0;
            border-radius: 12px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .22);
        }

        .user-profile-page .modal-header {
            padding: 1.15rem 1.35rem;
            border-bottom-color: #e7edf3;
            background: #f8fafc;
        }

        .user-profile-page .modal-title {
            color: #24364b;
            font-weight: 600;
        }

        .user-profile-page .modal-body {
            padding: 1.35rem;
        }

        .user-profile-page .modal-footer {
            padding: 1rem 1.35rem;
            border-top-color: #e7edf3;
        }

        .user-profile-page .modal-help {
            margin: -.25rem 0 1.2rem;
            color: var(--profile-muted);
        }

        .user-profile-page .btn .material-icons {
            vertical-align: middle;
            font-size: 18px;
        }

        @media (max-width: 767px) {
            .user-profile-page .profile-hero {
                padding: 1.4rem;
            }

            .user-profile-page .profile-hero__content {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .user-profile-page .profile-avatar {
                width: 82px;
                height: 82px;
                flex-basis: 82px;
            }

            .user-profile-page .profile-back {
                position: absolute;
                top: 1rem;
                right: 1rem;
            }

            [dir="rtl"] .user-profile-page .profile-back {
                right: auto;
                left: 1rem;
            }

            .user-profile-page .profile-panel {
                padding: 1.1rem;
            }

            .user-profile-page .section-heading, .user-profile-page .token-card__header {
                align-items: stretch;
                flex-direction: column;
            }

            .user-profile-page .mcp-summary {
                grid-template-columns: 1fr;
            }

            .user-profile-page .mcp-summary > .btn {
                width: 100%;
            }

            .user-profile-page .token-actions {
                justify-content: flex-start;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $activeTab = session('profile_tab', 'profile');
        if ($errors->getBag('password')->any()) $activeTab = 'password';
        if ($errors->getBag('mcp')->any() || $errors->getBag('mcpTokenEdit')->any()) $activeTab = 'mcp';
        if ($errors->getBag('account')->any()) $activeTab = 'account';
        $profilePhoto = $user->photo
            ? route('fileView', ['path' => 'users/'.$user->photo])
            : route('fileView', ['path' => 'contacts/profile.jpg']);
    @endphp

    <div class="padding user-profile-page">
        <section class="profile-hero">
            <div class="profile-hero__content">
                <img src="{{ $profilePhoto }}" alt="{{ $user->name }}" class="profile-avatar">
                <div class="profile-identity">
                    <h2>{{ $user->name }}</h2>
                    <p class="profile-email">{{ $user->email }}</p>
                    <div class="profile-badges">
                        <span class="profile-badge white"><i class="material-icons md-18">&#xe8d3;</i> {{ $user->permissionsGroup?->name }}</span>
                        <span class="profile-badge white"><span class="profile-badge__dot {{ $user->status ? 'is-active' : '' }}"></span>{{ $user->status ? __('backend.active') : __('backend.notActive') }}</span>
                        <span class="profile-badge white"><span class="profile-badge__dot {{ $user->mcp_enabled ? 'is-active' : '' }}"></span>{{ $user->mcp_enabled ? __('backend.mcpEnabled') : __('backend.mcpDisabled') }}</span>
                    </div>
                </div>
                <a class="profile-back" href="{{ Auth::user()->permissionsGroup?->roles_status ? route('users') : route('adminHome') }}" aria-label="{{ __('backend.close') }}">
                    <i class="material-icons">&#xe31b;</i> {{ __('backend.back') }}
                </a>
            </div>
        </section>

        <div class="profile-shell white">
            <ul class="nav profile-nav light dk" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'profile' ? 'active' : '' }}" data-toggle="tab" href="#tab_profile"><i class="material-icons">&#xe7fd;</i>{{ __('backend.profileInformation') }}
                    </a></li>
                @if($canChangePassword)
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'password' ? 'active' : '' }}" data-toggle="tab" href="#tab_password"><i class="material-icons">&#xe897;</i>{{ __('backend.changePassword') }}
                        </a></li>
                @endif
                @if($canManageMcp)
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'mcp' ? 'active' : '' }}" data-toggle="tab" href="#tab_mcp"><i class="material-icons">&#xe8b8;</i>{{ __('backend.mcpServer') }}
                        </a></li>
                @endif
                @if($canUpdateAccount)
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'account' ? 'active' : '' }}" data-toggle="tab" href="#tab_account"><i class="material-icons">&#xe8d3;</i>{{ __('backend.accountAdministration') }}
                        </a></li>
                @endif
            </ul>

            <div class="tab-content">
                <div class="tab-pane {{ $activeTab === 'profile' ? 'active' : '' }}" id="tab_profile">
                    <div class="profile-panel">
                        <div class="section-heading">
                            <div><h4>{{ __('backend.profileInformation') }}</h4>
                                <p class="text-muted">{{ __('backend.userProfile') }}</p></div>
                        </div>
                        <form method="POST" action="{{ route('userProfile.update', $user) }}" enctype="multipart/form-data" class="dashboard-form profile-form-card">
                            @csrf
                            @method('PATCH')
                            <div class="form-group row">
                                <label for="account_name" class="col-sm-3 form-control-label">{{ __('backend.fullName') }}</label>
                                <div class="col-sm-9">
                                    <input type="text" name="name" id="account_name" maxlength="100" required value="{{ old('name', $user->name) }}" class="form-control">@error('name', 'profile')
                                    <small class="text-danger">{{ $message }}</small>@enderror</div>
                            </div>
                            <div class="form-group row">
                                <label for="account_email" class="col-sm-3 form-control-label">{{ __('backend.loginEmail') }}</label>
                                <div class="col-sm-9">
                                    <input type="email" name="email" id="account_email" maxlength="100" required value="{{ old('email', $user->email) }}" class="form-control">@error('email', 'profile')
                                    <small class="text-danger">{{ $message }}</small>@enderror</div>
                            </div>
                            <div class="form-group row">
                                <label for="account_photo" class="col-sm-3 form-control-label">{{ __('backend.personalPhoto') }}</label>
                                <div class="col-sm-9">
                                    <input type="file" name="photo" id="account_photo" accept="image/*" class="form-control">
                                    <small class="text-muted">{{ __('backend.imagesTypes') }}
                                        · {{ __('backend.maximumFileSize', ['size' => '5 MB']) }}</small>
                                    @error('photo', 'profile')
                                    <br><small class="text-danger">{{ $message }}</small>@enderror
                                    @if($user->photo)
                                        <div class="checkbox m-t-sm">
                                            <label class="md-check"><input type="checkbox" name="photo_delete" value="1"><i class="danger"></i>{{ __('backend.deleteCurrentPhoto') }}
                                            </label></div>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group row m-b-0">
                                <div class="offset-sm-3 col-sm-9">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="material-icons">&#xe161;</i> {{ __('backend.update') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if($canChangePassword)
                    <div class="tab-pane {{ $activeTab === 'password' ? 'active' : '' }}" id="tab_password">
                        <div class="profile-panel">
                            <div class="section-heading">
                                <div><h4>{{ __('backend.changePassword') }}</h4>
                                    <p class="text-muted">{{ Auth::id() === $user->id ? __('backend.changeOwnPasswordHelp') : __('backend.changeUserPasswordHelp') }}</p>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('userProfile.password.update', $user) }}" class="dashboard-form profile-form-card">
                                @csrf
                                @method('PATCH')
                                <div class="form-group row">
                                    <label for="current_password" class="col-sm-3 form-control-label">{{ __('backend.currentOperatorPassword') }}</label>
                                    <div class="col-sm-9">
                                        <input type="password" name="current_password" id="current_password" required autocomplete="current-password" class="form-control">@error('current_password', 'password')
                                        <small class="text-danger">{{ $message }}</small>@enderror</div>
                                </div>
                                <div class="form-group row">
                                    <label for="new_password" class="col-sm-3 form-control-label">{{ __('backend.newPassword') }}</label>
                                    <div class="col-sm-9">
                                        <input type="password" name="password" id="new_password" minlength="8" required autocomplete="new-password" class="form-control">@error('password', 'password')
                                        <small class="text-danger">{{ $message }}</small>@enderror</div>
                                </div>
                                <div class="form-group row">
                                    <label for="new_password_confirmation" class="col-sm-3 form-control-label">{{ __('backend.confirmPassword') }}</label>
                                    <div class="col-sm-9">
                                        <input type="password" name="password_confirmation" id="new_password_confirmation" minlength="8" required autocomplete="new-password" class="form-control">
                                    </div>
                                </div>
                                <div class="form-group row m-b-0">
                                    <div class="offset-sm-3 col-sm-9">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="material-icons">&#xe897;</i> {{ __('backend.changePassword') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                @if($canManageMcp)
                    <div class="tab-pane {{ $activeTab === 'mcp' ? 'active' : '' }}" id="tab_mcp">
                        <div class="profile-panel">
                            <div class="section-heading">
                                <div><h4>{{ __('backend.mcpServer') }}</h4>
                                    <p class="text-muted">{{ __('backend.mcpDescription') }}</p></div>
                                <span class="status-pill {{ $user->mcp_enabled ? 'is-enabled' : 'is-disabled' }}">{{ $user->mcp_enabled ? __('backend.mcpEnabled') : __('backend.mcpDisabled') }}</span>
                            </div>

                            @if($errors->getBag('mcp')->any())
                                <div class="alert alert-danger">@foreach($errors->getBag('mcp')->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach</div>
                            @endif
                            @if($errors->getBag('mcpTokenEdit')->any())
                                <div class="alert alert-danger">@foreach($errors->getBag('mcpTokenEdit')->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach</div>
                            @endif

                            <div class="mcp-summary b-a light dk">
                                <div class="mcp-status">
                                    <span class="mcp-status__icon"><i class="material-icons">&#xe8b8;</i></span>
                                    <div><h5>{{ __('backend.mcpStatus') }}</h5>
                                        <p class="text-muted">{{ __('backend.mcpDisableNote') }}</p></div>
                                </div>
                                <button type="button" class="btn {{ $user->mcp_enabled ? 'btn-warning' : 'btn-success' }}" data-toggle="modal" data-target="#mcp-status-modal">
                                    <i class="material-icons">&#xe8ac;</i> {{ $user->mcp_enabled ? __('backend.disableMcp') : __('backend.enableMcp') }}
                                </button>
                            </div>

                            @unless($user->mcp_enabled)
                                <div class="alert alert-warning">
                                    <i class="material-icons md-18">&#xe002;</i> {{ __('backend.mcpDisabledTokenNote') }}
                                </div>
                            @endunless

                            <div class="token-card b-a">
                                <div class="token-card__header light dk">
                                    <div><h5>{{ __('backend.mcpTokens') }}</h5>
                                        <p class="text-muted">{{ __('backend.mcpTokenManagementHelp') }}</p></div>
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#mcp-create-modal">
                                        <i class="material-icons">&#xe145;</i> {{ __('backend.createMcpToken') }}
                                    </button>
                                </div>
                                @if($mcpTokens->isEmpty())
                                    <div class="token-empty"><i class="material-icons">&#xe899;</i>
                                        <div>{{ __('backend.noMcpTokens') }}</div>
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table token-table">
                                            <thead>
                                            <tr>
                                                <th>{{ __('backend.mcpTokenName') }}</th>
                                                <th>{{ __('backend.createdAt') }}</th>
                                                <th>{{ __('backend.lastUsedAt') }}</th>
                                                <th>{{ __('backend.expiresAt') }}</th>
                                                <th class="text-right">{{ __('backend.options') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($mcpTokens as $token)
                                                <tr>
                                                    <td>
                                                        <div class="token-name">
                                                            <span class="token-name__icon"><i class="material-icons md-18">&#xe899;</i></span><span>{{ $token->name }}</span>
                                                        </div>
                                                    </td>
                                                    <td>{{ $token->created_at?->format('Y-m-d H:i') }}</td>
                                                    <td>{{ $token->last_used_at?->format('Y-m-d H:i') ?? __('backend.never') }}</td>
                                                    <td>{{ $token->expires_at?->format('Y-m-d H:i') ?? __('backend.never') }}</td>
                                                    <td>
                                                        <div class="token-actions">
                                                            <button type="button" class="btn btn-sm primary js-edit-mcp-token" data-token-id="{{ $token->id }}" data-token-name="{{ $token->name }}" data-action="{{ route('userProfile.mcp.tokens.update', [$user, $token->id]) }}">
                                                                <i class="material-icons">&#xe3c9;</i> {{ __('backend.edit') }}
                                                            </button>
                                                            <button type="button" class="btn btn-sm danger js-revoke-mcp-token" data-token-name="{{ $token->name }}" data-action="{{ route('userProfile.mcp.tokens.destroy', [$user, $token->id]) }}">
                                                                <i class="material-icons">&#xe872;</i> {{ __('backend.revoke') }}
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                @if($canUpdateAccount)
                    <div class="tab-pane {{ $activeTab === 'account' ? 'active' : '' }}" id="tab_account">
                        <div class="profile-panel">
                            <div class="section-heading">
                                <div><h4>{{ __('backend.accountAdministration') }}</h4>
                                    <p class="text-muted">{{ __('backend.accountStatus') }}</p></div>
                            </div>
                            <form method="POST" action="{{ route('userProfile.account.update', $user) }}" class="dashboard-form profile-form-card">
                                @csrf
                                @method('PATCH')
                                <div class="form-group row">
                                    <label for="permissions_id" class="col-sm-3 form-control-label">{{ __('backend.Permission') }}</label>
                                    <div class="col-sm-9">
                                        <select name="permissions_id" id="permissions_id" required class="form-control c-select">@foreach($permissions as $permission)
                                                <option value="{{ $permission->id }}" @selected((int) old('permissions_id', $user->permissions_id) === (int) $permission->id)>{{ $permission->name }}</option>
                                            @endforeach</select>@error('permissions_id', 'account')
                                        <small class="text-danger">{{ $message }}</small>@enderror</div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-3 form-control-label">{{ __('backend.accountStatus') }}</label>
                                    <div class="col-sm-9">
                                        <label class="md-check m-r"><input type="radio" name="status" value="1" @checked((int) old('status', $user->status) === 1)><i class="primary"></i>{{ __('backend.active') }}
                                        </label><label class="md-check"><input type="radio" name="status" value="0" @checked((int) old('status', $user->status) === 0)><i class="danger"></i>{{ __('backend.notActive') }}
                                        </label></div>
                                </div>
                                <div class="form-group row m-b-0">
                                    <div class="offset-sm-3 col-sm-9">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="material-icons">&#xe161;</i> {{ __('backend.update') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if($canManageMcp)
            <div id="mcp-status-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('userProfile.mcp.status.update', $user) }}" class="dashboard-form">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="mcp_enabled" value="{{ $user->mcp_enabled ? 0 : 1 }}">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ $user->mcp_enabled ? __('backend.disableMcp') : __('backend.enableMcp') }}</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('backend.close') }}">
                                    <span aria-hidden="true">&times;</span></button>
                            </div>
                            <div class="modal-body"><p class="modal-help">{{ __('backend.mcpStatusPasswordHelp') }}</p>
                                <div class="form-group m-b-0">
                                    <label for="mcp_status_password">{{ __('backend.currentOperatorPassword') }}</label><input type="password" name="current_password" id="mcp_status_password" required autocomplete="current-password" class="form-control">@error('current_password', 'mcp')
                                    <small class="text-danger">{{ $message }}</small>@enderror</div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('backend.cancel') }}</button>
                                <button type="submit" class="btn {{ $user->mcp_enabled ? 'btn-warning' : 'btn-success' }}">{{ $user->mcp_enabled ? __('backend.disableMcp') : __('backend.enableMcp') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div id="mcp-create-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form id="mcp-token-form" method="POST" action="{{ route('userProfile.mcp.tokens.store', $user) }}" class="dashboard-form">
                            @csrf
                            <div class="modal-header"><h5 class="modal-title">{{ __('backend.createMcpToken') }}</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('backend.close') }}">
                                    <span aria-hidden="true">&times;</span></button>
                            </div>
                            <div class="modal-body"><p class="modal-help">{{ __('backend.mcpTokenNameHelp') }}</p>
                                <div id="mcp-token-errors" class="alert alert-danger" style="display:none"></div>
                                <div class="form-group m-b-0">
                                    <label for="mcp_token_name">{{ __('backend.mcpTokenName') }}</label><input type="text" name="name" id="mcp_token_name" maxlength="100" required class="form-control" autofocus>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('backend.cancel') }}</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="material-icons">&#xe145;</i> {{ __('backend.createMcpToken') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div id="mcp-edit-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form id="mcp-edit-form" method="POST" class="dashboard-form">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="editing_token" id="mcp_edit_token_id">
                            <div class="modal-header"><h5 class="modal-title">{{ __('backend.editMcpToken') }}</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('backend.close') }}">
                                    <span aria-hidden="true">&times;</span></button>
                            </div>
                            <div class="modal-body"><p class="modal-help">{{ __('backend.mcpTokenNameHelp') }}</p>
                                <div class="form-group m-b-0">
                                    <label for="mcp_edit_token_name">{{ __('backend.mcpTokenName') }}</label><input type="text" name="name" id="mcp_edit_token_name" maxlength="100" required class="form-control">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('backend.cancel') }}</button>
                                <button type="submit" class="btn btn-primary">{{ __('backend.update') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div id="mcp-revoke-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form id="mcp-revoke-form" method="POST" class="dashboard-form">
                            @csrf
                            @method('DELETE')
                            <div class="modal-header"><h5 class="modal-title">{{ __('backend.revoke') }}</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('backend.close') }}">
                                    <span aria-hidden="true">&times;</span></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-warning m-b-0">
                                    <i class="material-icons md-18">&#xe002;</i> {{ __('backend.mcpRevokeConfirm') }}
                                    <strong id="mcp-revoke-token-name"></strong></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('backend.cancel') }}</button>
                                <button type="submit" class="btn btn-danger">
                                    <i class="material-icons">&#xe872;</i> {{ __('backend.revoke') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div id="mcp-token-created-modal" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">{{ __('backend.mcpTokenCreated') }}</h5></div>
                        <div class="modal-body">
                            <div class="alert alert-warning">{{ __('backend.mcpOneTimeTokenHelp') }}</div>
                            <textarea id="mcp-plain-token" class="form-control" rows="4" readonly dir="ltr"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" id="copy-mcp-token" class="btn btn-primary"><i class="material-icons">&#xe14d;</i> {{ __('backend.copyToken') }}
                            </button>
                            <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('backend.close') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('after-scripts')
    <script>
        (function () {
            var createForm = document.getElementById('mcp-token-form');
            if (!createForm) return;

            var createErrors = document.getElementById('mcp-token-errors');
            var tokenField = document.getElementById('mcp-plain-token');
            var createdModal = $('#mcp-token-created-modal');

            createForm.addEventListener('submit', function (event) {
                event.preventDefault();
                createErrors.style.display = 'none';
                createErrors.textContent = '';

                fetch(createForm.action, {
                    method: 'POST',
                    body: new FormData(createForm),
                    credentials: 'same-origin',
                    headers: {'Accept': 'application/json'}
                })
                    .then(function (response) {
                        return response.json().then(function (body) {
                            if (!response.ok) throw body;
                            return body;
                        });
                    })
                    .then(function (body) {
                        createForm.reset();
                        tokenField.value = body.token;
                        $('#mcp-create-modal').one('hidden.bs.modal', function () {
                            createdModal.modal('show');
                        }).modal('hide');
                    })
                    .catch(function (body) {
                        var messages = body.errors ? Object.values(body.errors).reduce(function (all, group) {
                            return all.concat(group);
                        }, []) : [body.message || @json(__('backend.error'))];
                        createErrors.textContent = messages.join(' ');
                        createErrors.style.display = 'block';
                    });
            });

            document.querySelectorAll('.js-edit-mcp-token').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.getElementById('mcp-edit-form').action = button.dataset.action;
                    document.getElementById('mcp_edit_token_id').value = button.dataset.tokenId;
                    document.getElementById('mcp_edit_token_name').value = button.dataset.tokenName;
                    $('#mcp-edit-modal').modal('show');
                });
            });

            document.querySelectorAll('.js-revoke-mcp-token').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.getElementById('mcp-revoke-form').action = button.dataset.action;
                    document.getElementById('mcp-revoke-token-name').textContent = button.dataset.tokenName;
                    $('#mcp-revoke-modal').modal('show');
                });
            });

            document.getElementById('copy-mcp-token').addEventListener('click', function () {
                tokenField.select();
                if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(tokenField.value);
                else document.execCommand('copy');
            });

            createdModal.on('hidden.bs.modal', function () {
                tokenField.value = '';
                window.location.reload();
            });

            function revealActiveProfileTab(tabLink) {
                if (window.innerWidth < 768 && tabLink) {
                    tabLink.scrollIntoView({block: 'nearest', inline: 'center'});
                }
            }

            revealActiveProfileTab(document.querySelector('.profile-nav .nav-link.active'));
            $('.profile-nav .nav-link').on('shown.bs.tab', function (event) {
                revealActiveProfileTab(event.target);
            });

            @if($errors->getBag('mcp')->any())
            $('#mcp-status-modal').modal('show');
            @endif
            @if($errors->getBag('mcpTokenEdit')->any() && old('editing_token'))
            var editButton = document.querySelector('.js-edit-mcp-token[data-token-id="{{ (int) old('editing_token') }}"]');
            if (editButton) editButton.click();
            @endif
        })();
    </script>
@endpush
