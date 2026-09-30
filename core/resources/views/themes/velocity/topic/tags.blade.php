@if($WebmasterSection->tags_status)
    @php
        $velocityTags = collect($Topic->tags)->map(fn ($item) => $item->tag)->filter();
    @endphp
    @if($velocityTags->isNotEmpty())
        <nav class="velocity-topic-tags" aria-label="{{ __('backend.tags') }}">
            @foreach($velocityTags as $velocityTag)
                <a class="velocity-chip" href="{{ route('tag', $velocityTag->seo_url) }}">#{{ $velocityTag->title }}</a>
            @endforeach
        </nav>
    @endif
@endif
