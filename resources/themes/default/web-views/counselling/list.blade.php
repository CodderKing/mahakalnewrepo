@extends('layouts.front-end.app')

@section('title', 'Counselling')
@php
    use App\Utils\Helpers;
    use function App\Utils\displayStarRating;
@endphp
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
    <link href="https://unpkg.com/gijgo@1.9.14/css/gijgo.min.css" rel="stylesheet" type="text/css" />
    <!--poojafilter-css-->
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/poojafilter/layout.css') }}">
    <link href="{{ theme_asset(path: 'public/assets/front-end/vendor/fancybox/css/jquery.fancybox.min.css') }}"
        rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/animationbutton.css') }}">
    <style>
        .text-accent {
            font-size: 20px !important;
            margin-left: 5px;
        }

        .gold {
            color: #fe9802;
        }
    </style>
@endpush

@section('content')
    {{-- main page --}}
    <div class="inner-page-bg center bg-bla-7 py-4"
        style="background:url({{ asset('public/assets/front-end/img/bg.jpg') }}) no-repeat;background-size:cover;background-position:center center">
        <div class="container">
            <div class="row all-text-white">
                <div class="col-md-12 align-self-center">
                    <h1 class="innerpage-title">{{ @ucwords(translate('Counselling')) }}</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active"><a href="{{ url('/') }}" class="text-white"><i
                                        class="fa fa-home"></i> Home</a></li>
                            <li class="breadcrumb-item">{{ @ucwords(translate('Counselling')) }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <section class="cal_about_wrapper as_padderTop60 as_padderBottom60">
        <div class="container-fluid rtl px-0 px-md-3">
            <div class="__inline-62 pt-3">
                <div id="portfoliolist">
                    <div class="card card __shadow">
                        <div class="card-body p-xl-35">
                            <ul class="nav nav-pills nav-justified mb-5" id="linxea-avenir" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link font-weight-bold {{$type != 'muhurat'?'active':''}}" id="kundali-tab" data-toggle="tab"
                                        href="#kundali" role="tab" aria-controls="first" aria-selected="true"
                                        style="color: #222 !important">{{ translate('Counselling') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link font-weight-bold text-dark {{$type == 'muhurat'?'active':''}}" id="kundali-milan-tab" data-toggle="tab"
                                        href="#kundali-milan" role="tab" aria-controls="second" aria-selected="false"
                                        style="color: #222 !important">{{ translate('Muhurat') }}</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade {{$type != 'muhurat'?'show active':''}}" id="kundali" role="tabpanel"
                                    aria-labelledby="kundali-tab">
                                    @forelse ($consultation as $item)
                                        <div class="portfolio app" data-cat="app">
                                            <div class="portfolio-wrapper">
                                                <div class="card">
                                                    <img
                                                        src="{{ isset(json_decode($item['images'])[0]) ? asset('storage/app/public/pooja/' . json_decode($item['images'])[0]) : asset('def.png') }}"alt="">
                                                    <div class="card-body">
                                                        <h5 class="card-title font-weight-700">{{ $item['name'] }}</h5>
                                                        <div class="justify-content-between">
                                                            <div class="product-price">
                                                                <del class="category-single-product-price color-danger"
                                                                    style="color: red !important">
                                                                    ₹{{ $item['counselling_main_price'] }}
                                                                </del>
                                                                <span class="text-accent text-dark">
                                                                    ₹{{ $item['counselling_selling_price'] }}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <!-- Devotees Count -->
                                                            <div class="d-flex align-items-center">
                                                                <img src="{{ theme_asset(path: 'public/assets/front-end/img/track-order/users.gif') }}"
                                                                    alt="Users" class="colored-icon"
                                                                    style="width: 24px; height: 24px; margin-right: 5px;">
                                                                <span
                                                                    class="pooja-calendar">{{ 10000 + $item->pooja_order_review_count }}+
                                                                    Devotees</span>
                                                            </div>

                                                            <!-- Star Rating -->
                                                            <div class="d-flex align-items-center">
                                                                {!! displayStarRating($item->review_avg_rating ?? 0) !!}
                                                                <span
                                                                    class="ml-2">({{ number_format($item->review_avg_rating ?? 0, 1) }}/5)</span>
                                                            </div>
                                                        </div>
                                                        <a href="{{ route('counselling.details', $item['slug']) }}"
                                                            class="animated-button mt-2">

                                                            <span class="text-wrapper">
                                                                <span class="text-slide">{{ translate('Book_now') }}</span>
                                                                <span
                                                                    class="text-slide">{{ translate('Book_now!') }}</span>
                                                            </span>
                                                            <span class="icon">
                                                                <img src="{{ theme_asset(path: 'public/assets/front-end/img/track-order/arrow-white-icon.svg') }}"
                                                                    alt="arrow" />
                                                            </span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-center">No Data Available</p>
                                    @endforelse
                                </div>
                                <div class="tab-pane fade {{$type == 'muhurat'?'show active':''}}" id="kundali-milan" role="tabpanel"
                                    aria-labelledby="kundali-milan-tab">
                                    @forelse ($muhurat as $muhuratItem)
                                        <div class="portfolio app" data-cat="app">
                                            <div class="portfolio-wrapper">
                                                <div class="card">
                                                    <img
                                                        src="{{ isset(json_decode($muhuratItem['images'])[0]) ? asset('storage/app/public/pooja/' . json_decode($muhuratItem['images'])[0]) : asset('def.png') }}"alt="">
                                                    <div class="card-body">
                                                        <h5 class="card-title font-weight-700">{{ $muhuratItem['name'] }}
                                                        </h5>
                                                        <div class="justify-content-between">
                                                            <div class="product-price">
                                                                <del class="category-single-product-price color-danger"
                                                                    style="color: red !important">
                                                                    ₹{{ $muhuratItem['counselling_main_price'] }}
                                                                </del>
                                                                <span class="text-accent text-dark">
                                                                    ₹{{ $muhuratItem['counselling_selling_price'] }}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <!-- Devotees Count -->
                                                            <div class="d-flex align-items-center">
                                                                <img src="{{ theme_asset(path: 'public/assets/front-end/img/track-order/users.gif') }}"
                                                                    alt="Users" class="colored-icon"
                                                                    style="width: 24px; height: 24px; margin-right: 5px;">
                                                                <span
                                                                    class="pooja-calendar">{{ 10000 + $muhuratItem->pooja_order_review_count }}+
                                                                    Devotees</span>
                                                            </div>

                                                            <!-- Star Rating -->
                                                            <div class="d-flex align-items-center">
                                                                {!! displayStarRating($muhuratItem->review_avg_rating ?? 0) !!}
                                                                <span
                                                                    class="ml-2">({{ number_format($muhuratItem->review_avg_rating ?? 0, 1) }}/5)</span>
                                                            </div>
                                                        </div>
                                                        <a href="{{ route('counselling.details', $muhuratItem['slug']) }}"
                                                            class="animated-button mt-2">

                                                            <span class="text-wrapper">
                                                                <span
                                                                    class="text-slide">{{ translate('Book_now') }}</span>
                                                                <span
                                                                    class="text-slide">{{ translate('Book_now') }}</span>
                                                            </span>
                                                            <span class="icon">
                                                                <img src="{{ theme_asset(path: 'public/assets/front-end/img/track-order/arrow-white-icon.svg') }}"
                                                                    alt="arrow" />
                                                            </span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-center">No Data Available</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
@push('script')
    <script src="https://unpkg.com/gijgo@1.9.14/js/gijgo.min.js" type="text/javascript"></script>
    <script src="https://cdn.jsdelivr.net/gh/ethereumjs/browser-builds/dist/ethereumjs-tx/ethereumjs-tx-1.3.3.min.js">
    </script>
    <script src="{{ theme_asset(path: 'public/assets/front-end/js/owl.carousel.min.js') }}"></script>
    <!-- <script src="{{ theme_asset(path: 'public/assets/front-end/js/home.js') }}"></script> -->
    <script src="{{ theme_asset(path: 'public/assets/front-end/js/jquery.mixitup.min.js') }}"></script>
    <script src="{{ theme_asset(path: 'public/assets/front-end/js/api.js') }}"></script>

    <script type="text/javascript">
        $(function () {
            $('#portfoliolist').mixItUp({
                selectors: {
                    target: '.portfolio',
                    filter: '.filter'
                },
                load: {
                    filter: 'all'
                }
            });
    
            // Reset height after mixItUp operations
            $('#portfoliolist').on('mixEnd', function () {
                $('#kundali, #kundali-milan').css('height', 'auto');
            });
    
            // Reset height when Bootstrap tab changes
            $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
                $('#kundali, #kundali-milan').css('height', 'auto');
            });
        });
    </script>
@endpush
