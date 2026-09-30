@if(!empty(@$previousTopic) || !empty(@$nextTopic))
    <div class="topic-navigation my-5">
        <div class="row g-3 align-items-stretch">

            {{-- Previous Topic --}}
            <div class="col-md-6">
                @if(!empty(@$previousTopic))
                    <a href="{{ Helper::topicURL($previousTopic->id, "", $previousTopic) }}"
                       class="topic-nav-card topic-nav-prev d-flex align-items-center h-100 text-decoration-none">

                        <div class="topic-nav-icon me-3">
                            <i class="fa fa-angle-left"></i>
                        </div>

                        <div class="topic-nav-content">
                            <div class="topic-nav-label">
                                {{ trim(str_replace('&laquo;', '', __("pagination.previous"))) }}
                            </div>
                            <div class="topic-nav-title">
                                    <?php
                                    if ($previousTopic->$title_var != "") {
                                        $title = $previousTopic->$title_var;
                                    } else {
                                        $title = $previousTopic->$title_var2;
                                    }
                                    ?>
                                {{ $title  }}
                            </div>
                        </div>
                    </a>
                @endif
            </div>
            <div class="col-md-6">
                @if(!empty(@$nextTopic))
                    <a href="{{ Helper::topicURL($nextTopic->id, "", $nextTopic) }}"
                       class="topic-nav-card topic-nav-next d-flex align-items-center justify-content-end h-100 text-decoration-none text-end">

                        <div class="topic-nav-content">
                            <div class="topic-nav-label">
                                {{ trim(str_replace('&raquo;', '', __("pagination.next"))) }}
                            </div>
                            <div class="topic-nav-title">
                                    <?php
                                    if ($nextTopic->$title_var != "") {
                                        $title = $nextTopic->$title_var;
                                    } else {
                                        $title = $nextTopic->$title_var2;
                                    }
                                    ?>
                                {{ $title }}
                            </div>
                        </div>

                        <div class="topic-nav-icon ms-3">
                            <i class="fa fa-angle-right"></i>
                        </div>
                    </a>
                @endif
            </div>

        </div>
    </div>
@endif
