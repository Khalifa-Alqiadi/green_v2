@if($name === 'whatsapp')
    @include(theme_view('components.icon'), ['name' => 'whatsapp', 'class' => 'velocity-social__icon'])
@else
    <svg class="velocity-social__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        @switch($name)
            @case('facebook')
                <path d="M14 22v-9h3l.5-4H14V7c0-1.2.4-2 2-2h2V1.4A26 26 0 0 0 15 1c-3 0-5 1.9-5 5.3V9H7v4h3v9Z"/>
                @break
            @case('instagram')
                <rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/>
                <circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/>
                <circle cx="17.5" cy="6.5" r="1.2"/>
                @break
            @case('youtube')
                <path fill-rule="evenodd" d="M21.6 5.5C21.2 4 19.9 4 12 4S2.8 4 2.4 5.5C2 7 2 9.5 2 12s0 5 .4 6.5C2.8 20 4.1 20 12 20s9.2 0 9.6-1.5c.4-1.5.4-4 .4-6.5s0-5-.4-6.5ZM10 8l6 4-6 4Z" clip-rule="evenodd"/>
                @break
            @case('linkedin')
                <circle cx="4.5" cy="4.5" r="2"/>
                <path d="M3 8h3v13H3Zm6 0h3v1.8A4.5 4.5 0 0 1 16 7.6c4 0 5 2.5 5 6.1V21h-3v-6.5c0-2.2-.5-3.8-2.5-3.8S12 12.3 12 14.5V21H9Z"/>
                @break
            @case('x')
                <path d="M18.9 2H22l-6.8 7.8L23 22h-6.2L12 14.6 5.5 22H2.3l8.2-9.4L1 2h6.4l5.9 8.5L18.9 2ZM17.8 20h1.7L6.5 3.9H4.7Z"/>
                @break
            @case('twitter')
                <path d="M22 5.9a8 8 0 0 1-2.4.7 4.2 4.2 0 0 0 1.8-2.3 8.3 8.3 0 0 1-2.6 1 4.2 4.2 0 0 0-7.2 3.8A11.9 11.9 0 0 1 3 4.7a4.2 4.2 0 0 0 1.3 5.6 4.2 4.2 0 0 1-1.9-.5v.1a4.2 4.2 0 0 0 3.4 4.1 4.2 4.2 0 0 1-1.9.1 4.2 4.2 0 0 0 3.9 2.9A8.4 8.4 0 0 1 2 18.7a11.9 11.9 0 0 0 18.3-10 8.5 8.5 0 0 0 2.1-2.2Z"/>
                @break
            @case('telegram')
                <path d="m21.5 3.5-3.2 17c-.2 1-1 1.2-1.8.6l-4.9-3.6-2.4 2.3c-.3.3-.5.5-1 .5l.4-5 9.1-8.2c.4-.4-.1-.6-.6-.2L5.8 14l-4.9-1.5c-1.1-.4-1.1-1.1.2-1.6L20 3.6c.9-.3 1.7-.1 1.5-.1Z"/>
                @break
            @case('tumblr')
                <path d="M14.2 22c-4.2 0-6.2-2.2-6.2-6.3V11H5V7.3c3.1-1.1 4.4-3.8 4.6-6.3h3.8v5.7H18V11h-4.6v4.1c0 1.9 1 2.6 2.5 2.6.8 0 1.5-.2 2.1-.5v4.1c-.9.4-2.2.7-3.8.7Z"/>
                @break
        @endswitch
    </svg>
@endif
