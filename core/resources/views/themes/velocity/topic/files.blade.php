@php
    $velocityLanguageCode = Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
@endphp

@if(collect($Topic->attachFiles)->isNotEmpty())
    <section class="velocity-topic-section" aria-labelledby="velocity-files-title">
        <h2 id="velocity-files-title">{{ __('frontend.downloadAttach') }}</h2>
        <ul class="velocity-file-list">
            @foreach($Topic->attachFiles as $velocityFile)
                @php
                    $velocityFileTitle = $velocityFile->{'title_'.$velocityLanguageCode}
                        ?: $velocityFile->{'title_'.$velocityDefaultCode}
                        ?: basename($velocityFile->file);
                @endphp
                <li>
                    <a href="{{ route('fileView', ['path' => 'topics/'.$velocityFile->file]) }}" target="_blank" rel="noopener">
                        <span aria-hidden="true">↓</span>
                        <span>{{ $velocityFileTitle }}</span>
                        <small>{{ strtoupper(pathinfo($velocityFile->file, PATHINFO_EXTENSION)) }}</small>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
