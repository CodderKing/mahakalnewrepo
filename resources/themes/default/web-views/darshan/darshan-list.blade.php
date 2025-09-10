@extends('layouts.front-end.app')

@section('title',
    translate('Darshan aur Yatra Booking | Online Mandir Yatra Ki Suvidha
    '))

    @push('css_or_js')
        <meta property="og:image"
            content="{{ dynamicStorage(path: 'storage/app/public/company') }}/{{ $web_config['web_logo']->value }}" />
        <meta property="og:title" content="Terms & conditions of {{ $web_config['name']->value }} " />
        <meta property="og:url" content="{{ env('APP_URL') }}">
        <meta property="og:description"
            content="{{ substr(strip_tags(str_replace('&nbsp;', ' ', $web_config['about']->value)), 0, 160) }}">
        <meta property="twitter:card"
            content="{{ dynamicStorage(path: 'storage/app/public/company') }}/{{ $web_config['web_logo']->value }}" />
        <meta property="twitter:title" content="Terms & conditions of {{ $web_config['name']->value }}" />
        <meta property="twitter:url" content="{{ env('APP_URL') }}">
        <meta property="twitter:description"
            content="{{ substr(strip_tags(str_replace('&nbsp;', ' ', $web_config['about']->value)), 0, 160) }}">
        <meta name="description"
            content="Prasiddh mandiron ke darshan aur yatra ki online booking karein. Char Dham, Jyotirling aur teerth yatra ki aasaan suvidha paayen.">
        <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/animationbutton.css') }}">
        <style>
            .two-lines-only {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                text-overflow: ellipsis;
                line-height: 1.5em;
                min-height: 3em;
            }

            .newpadding {
                padding: 5px 1.25rem 1.25rem;
            }

            .one-lines-only {
                display: -webkit-box;
                -webkit-line-clamp: 1;
                -webkit-box-orient: vertical;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .responsive-bg {
                padding-top: 3rem !important;
                padding-bottom: 4rem !important;
                /* background:url("{{ asset('assets/front-end/img/slider/darshan.jpg') }}") no-repeat; */
                background: url("{{ asset('public/assets/front-end/img/slider/darshan.jpg') }}") no-repeat;
                background-size: cover;
                background-position: center center;
            }

            @media (max-width: 768px) {
                .responsive-bg {
                    padding-top: 1.91rem !important;
                    padding-bottom: 2rem !important;
                    /* background:url("{{ asset('assets/front-end/img/slider/darshan1.jpg') }}") no-repeat; */
                    background: url("{{ asset('public/assets/front-end/img/slider/darshan1.jpg') }}") no-repeat;
                    background-size: cover;
                    background-position: center center;
                }

                .font-size-set {
                    font-size: 12px;
                }

                .sm-show-input .form-control {
                    height: calc(1em + 1rem + 1px);
                }

                .sm-show-search-button {
                    height: calc(1em + 1rem + 1px);
                    padding: 2px 13px;
                }

                .inner-page-bg h1.innerpage-title {
                    font-size: 1.1875rem;
                }
            }

            .btn.active-category {
                background-color: #fe9802;
                color: #fff;
            }
        </style>
    @endpush


@section('content')
    <div class="inner-page-bg center bg-bla-7 responsive-bg">
        <div class="container">
            <div class="row all-text-white">
                <div class="col-md-12 align-self-center text-center">
                    <h1 class="innerpage-title mb-1">{{ translate('darshan_places') }}</h1>
                    <h5>
                        <form action="{{ url()->current() }}" method="GET">
                            <div class="input-group w-50 d-none d-sm-flex" style="margin-left: 28%; opacity: 0.6;">
                                <input type="text" name="search" class="form-control border-0 fw-bold"
                                    placeholder="{{ translate('Search_Temple_Name_&_Address') }}">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>

                            <div class="input-group w-100 d-sm-none sm-show-input" style="opacity: 0.6;">
                                <input type="text" name="search" class="form-control border-0 fw-bold"
                                    placeholder="{{ translate('Search_Temple_Name_&_Address') }}" style="color: #4b566b;">
                                <button class="btn btn-primary sm-show-search-button" type="submit">Search</button>
                            </div>
                        </form>
                    </h5>
                </div>
            </div>
        </div>
    </div>
    <!-- start to temple section -->
    <!-- <section class="temple-section"> -->
    <div class="__inline-62 py-3">
        <div class="container-fluid p-0 rtl">
            <!-- <div class="__inline-62 pt-3"> -->
            <div class='ml-2 list-group list-group-horizontal flex-nowrap overflow-auto'>
                <button class="temple-category-filter btn active-category" data-category="all">All</button>
                @if (isset($categoryList) && !empty($categoryList) && count($categoryList) > 0)
                    @foreach ($categoryList as $cat_name)
                        <button class="temple-category-filter btn"
                            data-category="{{ Str::slug($cat_name['name']) }}">{{ ucwords($cat_name['name']) }}</button>
                    @endforeach
                @endif
            </div>
            <div class="row m-0 p-0">
                <div class="col-12">
                    <a href="{{ route('darshan') }}" class="float-end btn btn--primary btn-sm me-4"><i
                            class="fa fa-refresh"></i> {{ translate('Clear') }}</a>
                </div>
            </div>
            <div class="container feature-product p-2">
                <div class="portfoliolist_event p-1">
                    <div class="EventFilter row ">
                        @if (isset($templeList) && !empty($templeList) && count($templeList) > 0)
                            @foreach ($templeList as $product)
                                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 my-2 portfolioEvents {{ Str::slug($product['category']['name']) }}"
                                    data-cat="{{ Str::slug($product['category']['name']) }}"
                                    styles="display: inline-block;" data-bound="">
                                    <div class="portfolio-wrapper">
                                        <div class="card">
                                            {{-- <span class="for-discount-value pooja-badge p-1 pl-2 pr-2 font-bold fs-13">
										<span class="direction-ltr blink d-block">{{ Str::slug($product['category']['name'])}}</span>
								</span> --}}
                                            <a href="{{ route('temple-details', [$product['slug']]) }}"><img
                                                    src="{{ getValidImage(path: 'storage/app/public/temple/thumbnail/' . $product['thumbnail'], type: 'product') }}"
                                                    class="card-img-top puja-image" alt="..."></a>
                                            <div class="card-body newpadding">
                                                <h5 class="font-weight-bolder two-lines-only mt-2 mb-0">
                                                    {{ $product['name'] }}</h5>
                                                <span
                                                    class="card-text one-lines-only">{{ translate('time') }}:&nbsp;{{ date('h:i A', strtotime($product['opening_time'])) }}
                                                    - {{ date('h:i A', strtotime($product['closeing_time'])) }}</span>
                                                <span class="card-text mb-2 one-lines-only">{{ translate('Entry fee') }}
                                                    :&nbsp;{{ $product['entry_fee'] }}</span>
                                                <a href="{{ route('temple-details', [$product['slug']]) }}"
                                                    class="text-white animated-button mt-2">
                                                    <span class="text-wrapper">
                                                        <span class="text-slide">{{ translate('EXPLORE') }}</span>
                                                        <span class="text-slide">{{ translate('EXPLORE') }}</span>
                                                    </span>
                                                    <span class="icon">
                                                        <img src="{{ theme_asset(path: 'public/assets/front-end/img/track-order/arrow-white-icon.svg') }}"
                                                            alt="arrow">
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
                <div class="text-center pt-2 d-md-none">
                    <a class="text-capitalize view-all-text web-text-primary" href="{{ route('event') }}">
                        {{ translate('view_all') }}
                        <i
                            class="czi-arrow-{{ Session::get('direction') === 'rtl' ? 'left mr-1 ml-n1 mt-1' : 'right ml-1' }}"></i>
                    </a>
                </div>
            </div>
            <!-- </div> -->
        </div>

    </div>
@endsection

@push('script')
    <script>
        $('.temple-category-filter').on('click', function() {
            var category = $(this).data('category');
            $('.temple-category-filter').removeClass('active-category');
            $(this).addClass('active-category');
            filterEventItems(category);
        });

        function filterEventItems(category) {
            if (category === 'all') {
                $('.portfolioEvents').stop(true, true).fadeIn(340);
            } else {
                $('.portfolioEvents').each(function() {
                    if ($(this).data('cat') === category) {
                        $(this).stop(true, true).fadeIn(340);
                    } else {
                        $(this).stop(true, true).fadeOut(340);
                    }
                });
            }
        }
    </script>
@endpush
