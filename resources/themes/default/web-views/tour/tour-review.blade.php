<div class="row">
    <div class="col-lg-12 col-md-12">
        {{-- @if (!empty($reviewCounts['list']) && count($reviewCounts['list']) > 0) --}}
        <div class="owl-theme owl-carousel review-slider">
            {{-- Video --}}
            <div class="card product-single-hover shadow-none rtl">
                <div class="card-body position-relative">
                    <div class="ratio ratio-16x9">
                        <iframe width="100%" height="100%" src="https://www.youtube.com/embed/{{ $videoId }}"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen style="border-radius: 10px;"></iframe>
                    </div>
                    <div class="d-flex align-items-center mt-2">
                        <img src="{{ asset('public/images/default.png') }}" alt="User Icon" class="user-icon"
                            style="width: 50px; height: 50px; border-radius: 50%; margin-right: 10px;">
                        <div>
                            <p class="fw-bold m-0" style="font-size:14px;">Purohit Ji</p>
                        </div>
                    </div>
                </div>
            </div>
            {{--  --}}
            <div class="card product-single-hover shadow-none rtl">
                <div class="card-body position-relative">
                    <div class="single-review-details">
                        <div class="review-content" id="content-{{ $poojaReview['id'] ?? '0' }}">
                            {{ $poojaReview['comment'] }}
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-2">
                        <img src="{{ asset(empty($poojaReview['userData']['image']) ? 'public/images/default.png' : '/storage/app/public/profile/' . $poojaReview['userData']['image']) }}"
                            alt="User Icon" class="user-icon"
                            style="width: 50px; height: 50px; border-radius: 50%; margin-right: 10px;">
                        <div>
                            <p class="fw-bold m-0" style="font-size:14px;">
                                {{ $poojaReview['userData']['name'] ?? 'User Name' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            {{--  --}}
        </div>
        {{-- @else --}}
        <div class="text-center text-capitalize">
            <p class="text-capitalize"><small>{{ translate('No_comment_given_yet') }}!</small></p>
        </div>
        {{-- @endif --}}
    </div>
</div>
