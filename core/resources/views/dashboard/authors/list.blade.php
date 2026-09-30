@extends('dashboard.layouts.master')
@section('title', __('backend.authors'))
@section('content')
    <div class="padding">
        <div class="box">
            <div class="box-header dker">
                <h3>{{ __('backend.authors') }}</h3>
                <small><a href="{{ route('adminHome') }}">{{ __('backend.home') }}</a> / {{ __('backend.authors') }}</small>
            </div>
            <div class="box-tool box-tool-lg">
                <ul class="nav" style="display:flex;justify-content:space-around">
                    <li class="nav-item inline">
                        <form method="GET" action="{{ route('authors') }}" class="w-md">
                            <div class="input-group">
                                <input type="text" name="q" value="{{ request('q') }}" class="form-control p-x"
                                       autocomplete="off" placeholder="{{ __('backend.searchAuthors') }}">
                                <span class="input-group-btn"><button class="btn white b-a no-shadow" type="submit"><i class="fa fa-search"></i></button></span>
                            </div>
                        </form>
                    </li>
                    @if(@Auth::user()->permissionsGroup->add_status)
                        <li class="nav-item inline">
                            <a class="btn btn-fw primary m-l" href="{{ route('authorsCreate') }}">
                                <i class="material-icons">&#xe7fe;</i> {{ __('backend.addAuthor') }}
                            </a>
                        </li>
                    @endif
                </ul>
            </div>

            @if($Authors->isEmpty())
                <div class="p-a text-center">
                    <div class="text-muted m-b"><i class="fa fa-user-circle fa-4x"></i></div>
                    <h6>{{ __('backend.noAuthors') }}</h6>
                </div>
            @else
                <form method="POST" action="{{ route('authorsUpdateAll') }}" class="dashboard-form" onsubmit="return confirmAuthorsBulkAction(this)">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered m-a-0">
                            <thead class="dker"><tr>
                                @if(@Auth::user()->permissionsGroup->edit_status)
                                    <th class="width20"><label class="ui-check m-a-0"><input id="checkAll" type="checkbox"><i></i></label></th>
                                @endif
                                <th class="text-center" style="width:85px">{{ __('backend.authorPhoto') }}</th>
                                <th>{{ __('backend.authorArabicName') }}</th>
                                <th>{{ __('backend.authorEnglishName') }}</th>
                                <th>{{ __('backend.authorPosition') }}</th>
                                <th class="text-center" style="width:90px">{{ __('backend.authorTopicsCount') }}</th>
                                <th class="text-center" style="width:70px">{{ __('backend.status') }}</th>
                                <th class="text-center" style="width:130px">{{ __('backend.options') }}</th>
                            </tr></thead>
                            <tbody>
                            @foreach($Authors as $Author)
                                <tr>
                                    @if(@Auth::user()->permissionsGroup->edit_status)
                                        <td><label class="ui-check m-a-0"><input type="checkbox" name="ids[]" value="{{ $Author->id }}"><i class="dark-white"></i></label></td>
                                    @endif
                                    <td class="text-center">
                                        @if($Author->photo)
                                            <img src="{{ route('fileView', ['path' => 'authors/'.$Author->photo]) }}" alt="{{ $Author->name_ar }}" style="width:52px;height:52px;object-fit:cover">
                                        @else
                                            <i class="fa fa-user-circle fa-3x text-muted"></i>
                                        @endif
                                    </td>
                                    <td class="h6">
                                        @if(@Auth::user()->permissionsGroup->edit_status)<a href="{{ route('authorsEdit', $Author->id) }}">{{ $Author->name_ar }}</a>@else{{ $Author->name_ar }}@endif
                                    </td>
                                    <td>{{ $Author->name_en ?: '—' }}</td>
                                    <td>{{ $Author->position_ar ?: $Author->position_en ?: '—' }}</td>
                                    <td class="text-center"><strong>{{ $Author->topics_count }}</strong></td>
                                    <td class="text-center"><i class="fa {{ $Author->status ? 'fa-check text-success' : 'fa-times text-danger' }}"></i></td>
                                    <td class="text-center">
                                        @if(@Auth::user()->permissionsGroup->edit_status)
                                            <a class="btn btn-sm success" href="{{ route('authorsEdit', $Author->id) }}" title="{{ __('backend.edit') }}"><i class="material-icons">&#xe3c9;</i></a>
                                        @endif
                                        @if(@Auth::user()->permissionsGroup->delete_status)
                                            <button type="button" class="btn btn-sm warning" onclick="deleteAuthor('{{ route('authorsDestroy', $Author->id) }}')" title="{{ __('backend.delete') }}"><i class="material-icons">&#xe872;</i></button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <footer class="dker p-a">
                        <div class="row">
                            <div class="col-sm-4">
                                @if(@Auth::user()->permissionsGroup->edit_status)
                                    <select name="action" class="form-control c-select w-sm inline v-middle" required>
                                        <option value="">{{ __('backend.bulkAction') }}</option>
                                        <option value="activate">{{ __('backend.activeSelected') }}</option>
                                        <option value="block">{{ __('backend.blockSelected') }}</option>
                                        @if(@Auth::user()->permissionsGroup->delete_status)<option value="delete">{{ __('backend.deleteSelected') }}</option>@endif
                                    </select>
                                    <button type="submit" class="btn white">{{ __('backend.apply') }}</button>
                                @endif
                            </div>
                            <div class="col-sm-3 text-center"><small class="text-muted">{{ __('backend.showing') }} {{ $Authors->firstItem() }}-{{ $Authors->lastItem() }} {{ __('backend.of') }} <strong>{{ $Authors->total() }}</strong> {{ __('backend.records') }}</small></div>
                            <div class="col-sm-5 text-right text-center-xs">{!! $Authors->links() !!}</div>
                        </div>
                    </footer>
                </form>
            @endif
        </div>
    </div>

    <div id="delete-author" class="modal fade" data-backdrop="true">
        <div class="modal-dialog" id="animate"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">{{ __('backend.confirmation') }}</h5></div>
            <div class="modal-body text-center p-lg"><h5>{{ __('backend.confirmationDeleteMsg') }}</h5></div>
            <div class="modal-footer">
                <button type="button" class="btn dark-white p-x-md" data-dismiss="modal">{{ __('backend.no') }}</button>
                <form id="delete-author-form" method="POST" action="" class="inline">@csrf @method('DELETE')<button type="submit" class="btn danger p-x-md">{{ __('backend.yes') }}</button></form>
            </div>
        </div></div>
    </div>
@endsection
@push('after-scripts')
    <script>
        $('#checkAll').on('click', function () { $('input[name="ids[]"]').prop('checked', this.checked); });
        function deleteAuthor(url) { $('#delete-author-form').attr('action', url); $('#delete-author').modal('show'); }
        function confirmAuthorsBulkAction(form) {
            if (!form.querySelector('input[name="ids[]"]:checked')) {
                alert(@json(__('backend.selectAtLeastOneAuthor')));
                return false;
            }
            return form.querySelector('[name="action"]').value !== 'delete' || confirm(@json(__('backend.confirmationDeleteMsg')));
        }
    </script>
@endpush
