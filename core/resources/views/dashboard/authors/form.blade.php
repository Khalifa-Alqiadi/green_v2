@foreach(Helper::languagesList() as $ActiveLanguage)
    @if($ActiveLanguage->box_status && in_array($ActiveLanguage->code, ['ar', 'en']))
        @php($nameField = 'name_'.$ActiveLanguage->code)
        <div class="form-group row">
            <label for="{{ $nameField }}" class="col-sm-2 form-control-label">
                {{ __('backend.authorName') }} {!! Helper::languageName($ActiveLanguage) !!}
            </label>
            <div class="col-sm-10">
                <input type="text" name="{{ $nameField }}" id="{{ $nameField }}"
                       value="{{ old($nameField, optional($Author)->{$nameField}) }}" maxlength="191"
                       dir="{{ $ActiveLanguage->direction }}" class="form-control"
                       autocomplete="off" {{ $ActiveLanguage->code === 'ar' ? 'required' : '' }}>
            </div>
        </div>
    @endif
@endforeach

@foreach(Helper::languagesList() as $ActiveLanguage)
    @if($ActiveLanguage->box_status && in_array($ActiveLanguage->code, ['ar', 'en']))
        @php($positionField = 'position_'.$ActiveLanguage->code)
        <div class="form-group row">
            <label for="{{ $positionField }}" class="col-sm-2 form-control-label">
                {{ __('backend.authorPosition') }} {!! Helper::languageName($ActiveLanguage) !!}
            </label>
            <div class="col-sm-10">
                <input type="text" name="{{ $positionField }}" id="{{ $positionField }}"
                       value="{{ old($positionField, optional($Author)->{$positionField}) }}" maxlength="191"
                       dir="{{ $ActiveLanguage->direction }}" class="form-control" autocomplete="off">
            </div>
        </div>
    @endif
@endforeach

<div class="form-group row">
    <label for="photo" class="col-sm-2 form-control-label">{{ __('backend.authorPhoto') }}</label>
    <div class="col-sm-10">
        @if(optional($Author)->photo)
            <div class="m-b-sm">
                <img src="{{ route('fileView', ['path' => 'authors/'.$Author->photo]) }}"
                     alt="{{ $Author->name_ar }}" style="width:90px;height:90px;object-fit:cover">
            </div>
            <label class="md-check m-b-sm">
                <input type="checkbox" name="photo_delete" value="1" {{ old('photo_delete') ? 'checked' : '' }}>
                <i class="danger"></i> {{ __('backend.removeAuthorPhoto') }}
            </label>
        @endif
        <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
        <small class="text-muted">{{ __('backend.authorPhotoHint') }}</small>
    </div>
</div>

<div class="form-group row">
    <label class="col-sm-2 form-control-label">{{ __('backend.status') }}</label>
    <div class="col-sm-10">
        <label class="md-check m-r">
            <input type="radio" name="status" value="1" {{ (int) old('status', optional($Author)->status ?? 1) === 1 ? 'checked' : '' }}>
            <i class="primary"></i> {{ __('backend.active') }}
        </label>
        <label class="md-check">
            <input type="radio" name="status" value="0" {{ (int) old('status', optional($Author)->status ?? 1) === 0 ? 'checked' : '' }}>
            <i class="danger"></i> {{ __('backend.notActive') }}
        </label>
    </div>
</div>
