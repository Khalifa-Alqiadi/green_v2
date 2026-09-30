@if(($count ?? 0) > 1)
    @php
        $velocityCarouselConfigId = ($target ?? 'velocity-carousel').'-config';
        $velocityCarouselConfig = [
            'target' => $target,
            'count' => $count,
            'autoplay' => $autoplay ?? false,
            'interval' => $interval ?? 6000,
            'pagination' => $pagination ?? true,
            'previousLabel' => html_entity_decode(__('pagination.previous')),
            'nextLabel' => html_entity_decode(__('pagination.next')),
        ];
    @endphp
    <div
        class="velocity-carousel__enhancement"
        data-velocity-component="velocity-carousel"
        data-velocity-config="{{ $velocityCarouselConfigId }}"
    ></div>
    <script id="{{ $velocityCarouselConfigId }}" type="application/json" data-velocity-config>{!! json_encode($velocityCarouselConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endif
