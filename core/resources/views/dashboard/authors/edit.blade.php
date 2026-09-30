@extends('dashboard.layouts.master')
@section('title', __('backend.editAuthor'))
@section('content')
    <div class="padding"><div class="box">
        <div class="box-header dker"><h3>{{ __('backend.editAuthor') }}</h3><small><a href="{{ route('authors') }}">{{ __('backend.authors') }}</a></small></div>
        <div class="box-body p-a-2">
            <form method="POST" action="{{ route('authorsUpdate', $Author->id) }}" enctype="multipart/form-data" class="dashboard-form">
                @csrf
                @include('dashboard.authors.form')
                <button type="submit" class="btn primary"><i class="material-icons">&#xe161;</i> {{ __('backend.save') }}</button>
                <a href="{{ route('authors') }}" class="btn white">{{ __('backend.cancel') }}</a>
            </form>
        </div>
    </div></div>
@endsection
