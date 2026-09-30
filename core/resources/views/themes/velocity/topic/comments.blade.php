@if($WebmasterSection->comments_status)
    @php
        $velocityCommentFormId = 'velocity-comment-form-'.$Topic->id;
        $velocityCommentFeedbackId = $velocityCommentFormId.'-feedback';
        $velocityComments = collect($Topic->approvedComments);
        $velocityCommentFormConfig = [
            'target' => $velocityCommentFormId,
            'feedbackTarget' => $velocityCommentFeedbackId,
            'submittingLabel' => __('backend.loading'),
            'errorLabel' => __('backend.error'),
        ];
    @endphp
    <section class="velocity-topic-section velocity-comments" id="comments" aria-labelledby="velocity-comments-title">
        <div class="velocity-comments__heading">
            <div>
                <span class="velocity-comments__eyebrow" aria-hidden="true">
                    @include(theme_view('components.icon'), ['name' => 'comment'])
                </span>
                <h2 id="velocity-comments-title">{{ __('frontend.comments') }}</h2>
            </div>
            <span class="velocity-comments__count" title="{{ __('frontend.comments') }}">
                {{ $velocityComments->count() }}
            </span>
        </div>

        @if($velocityComments->isNotEmpty())
            <ol class="velocity-comment-list">
                @foreach($velocityComments as $velocityComment)
                    @php
                        $velocityCommentName = trim(strip_tags((string) $velocityComment->name));
                        $velocityCommentInitial = Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($velocityCommentName, 0, 1));
                    @endphp
                    <li class="velocity-comment-list__item">
                        <article class="velocity-comment">
                            <span class="velocity-comment__avatar" aria-hidden="true">
                                {{ $velocityCommentInitial !== '' ? $velocityCommentInitial : '•' }}
                            </span>
                            <div class="velocity-comment__content">
                                <header class="velocity-comment__header">
                                    <strong>{{ $velocityCommentName }}</strong>
                                    <time datetime="{{ date(DATE_ATOM, strtotime($velocityComment->date)) }}">
                                        {{ date('d M Y, h:i A', strtotime($velocityComment->date)) }}
                                    </time>
                                </header>
                                <p class="velocity-comment__message">{!! nl2br(e(strip_tags($velocityComment->comment))) !!}</p>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ol>
        @else
            <div class="velocity-comments__empty">
                @include(theme_view('components.icon'), ['name' => 'comment'])
                <p>{{ __('frontend.noData') }}</p>
            </div>
        @endif

        <div class="velocity-panel velocity-submission-panel velocity-comment-composer">
            <div class="velocity-comment-composer__heading">
                <span class="velocity-comment-composer__icon" aria-hidden="true">
                    @include(theme_view('components.icon'), ['name' => 'comment'])
                </span>
                <h3>{{ __('frontend.newComment') }}</h3>
            </div>
            @if(($errors ?? null)?->any())
                @component(theme_view('components.status'), ['tone' => 'error'])
                    <ul>
                        @foreach($errors->all() as $velocityError)<li>{{ $velocityError }}</li>@endforeach
                    </ul>
                @endcomponent
            @endif
            <form class="velocity-comment-form" id="{{ $velocityCommentFormId }}" method="POST" action="{{ route('commentSubmit') }}" data-velocity-native-form>
                @csrf
                @honeypot
                <div class="velocity-form-grid">
                    <label for="{{ $velocityCommentFormId }}-name">
                        <span>{{ __('frontend.yourName') }}</span>
                        <input
                            class="velocity-field"
                            id="{{ $velocityCommentFormId }}-name"
                            type="text"
                            name="comment_name"
                            value="{{ old('comment_name', Auth::user()?->name) }}"
                            autocomplete="name"
                            required
                            maxlength="100"
                            @error('comment_name') aria-invalid="true" aria-describedby="{{ $velocityCommentFormId }}-name-error" @enderror
                        >
                        @error('comment_name')
                            <small class="velocity-field-error" id="{{ $velocityCommentFormId }}-name-error" role="alert">{{ $message }}</small>
                        @enderror
                    </label>
                    <label for="{{ $velocityCommentFormId }}-email">
                        <span>{{ __('frontend.yourEmail') }}</span>
                        <input
                            class="velocity-field"
                            id="{{ $velocityCommentFormId }}-email"
                            type="email"
                            name="comment_email"
                            value="{{ old('comment_email', Auth::user()?->email) }}"
                            autocomplete="email"
                            inputmode="email"
                            required
                            maxlength="100"
                            @error('comment_email') aria-invalid="true" aria-describedby="{{ $velocityCommentFormId }}-email-error" @enderror
                        >
                        @error('comment_email')
                            <small class="velocity-field-error" id="{{ $velocityCommentFormId }}-email-error" role="alert">{{ $message }}</small>
                        @enderror
                    </label>
                    <label class="velocity-form-grid__wide" for="{{ $velocityCommentFormId }}-message">
                        <span>{{ __('frontend.comment') }}</span>
                        <textarea
                            class="velocity-textarea"
                            id="{{ $velocityCommentFormId }}-message"
                            name="comment_message"
                            rows="6"
                            required
                            @error('comment_message') aria-invalid="true" aria-describedby="{{ $velocityCommentFormId }}-message-error" @enderror
                        >{{ old('comment_message') }}</textarea>
                        @error('comment_message')
                            <small class="velocity-field-error" id="{{ $velocityCommentFormId }}-message-error" role="alert">{{ $message }}</small>
                        @enderror
                    </label>
                </div>
                @include('common.recaptcha', ['action' => 'comment'])
                <input type="hidden" name="topic_id" value="{{ $Topic->id }}">
                <div id="{{ $velocityCommentFeedbackId }}" class="velocity-form-feedback" aria-live="polite"></div>
                <div class="velocity-comment-form__footer">
                    <button class="velocity-button velocity-button--primary" type="submit">
                        {{ __('frontend.sendComment') }}
                        <span class="velocity-arrow" aria-hidden="true">→</span>
                    </button>
                </div>
            </form>
            <div data-velocity-component="cms-form-enhancer" data-velocity-config="{{ $velocityCommentFormId }}-config"></div>
            <script id="{{ $velocityCommentFormId }}-config" type="application/json" data-velocity-config>
                @json($velocityCommentFormConfig)
            </script>
        </div>
    </section>
@endif
