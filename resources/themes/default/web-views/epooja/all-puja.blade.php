@extends('layouts.front-end.app')
@section('title', translate('Sabhi Pooja Sevaayein – Ghar Baithe Online Pooja Book Karein | Mahakal.com'))
@php
    use App\Utils\Helpers;
    use function App\Utils\getNextPoojaDay;
    use function App\Utils\getNextChadhavaDay;
    use function App\Utils\displayStarRating;
@endphp
@push('css_or_js')
    <meta property="og:image"
        content="{{ dynamicStorage(path: 'storage/app/public/company') }}/{{ $web_config['web_logo']->value }}" />
        <meta name="description" content="Mahakal.com par sabhi pooja sevaayein jaise Griha Pravesh, Mangal Dosh Nivaran, Rudrabhishek, Vivaah Pooja aur anya dharmik anushthaan online book karein. Anubhavi panditon ke saath ghar par pooja karaayein.">
    <meta property="og:title" content="Terms & conditions of {{ $web_config['name']->value }} " />
    <meta property="og:url" content="{{ env('APP_URL') }}">
    <meta property="og:description"
        content="{{ substr(strip_tags(str_replace('&nbsp;', ' ', $web_config['about']->value)), 0, 160) }}">
    <meta property="twitter:card"
        content="{{ dynamicStorage(path: 'storage/app/public/company') }}/{{ $web_config['web_logo']->value }}" />
    <meta property="twitter:title" content="Terms & conditions of {{ $web_config['name']->value }}" />
    <meta property="twitter:url" content="{{ env('APP_URL') }}">
    <meta
        property="twitter:description"content="{{ substr(strip_tags(str_replace('&nbsp;', ' ', $web_config['about']->value)), 0, 160) }}">
    <link href="https://unpkg.com/gijgo@1.9.14/css/gijgo.min.css" rel="stylesheet" type="text/css" />
    <!--poojafilter-css-->
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/poojafilter/layout.css') }}">
    <link href="{{ theme_asset(path: 'public/assets/front-end/vendor/fancybox/css/jquery.fancybox.min.css') }}"
        rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet"  href="{{ theme_asset(path: 'public/assets/front-end/css/animationbutton.css') }}">
    <style>
        .pooja-search {
            position: sticky;
            top: 124px;
            /* width: 50%; */
            left: 41rem;
            /* background-color: white; */
            padding: 10px 20px;
            /* box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); */
            z-index: 9;
        }

        .pooja-menu {
            position: sticky;
            top: 83px;
            left: 0;
            right: 0;
            background-color: white;
            /* box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);  */
            z-index: 9;
        }

        .form-control:focus {
            border-color: #fe9802 !important;
        }

        .search-icon {
            margin-left: 21rem;
        }

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

        @keyframes slideBackground {
            0% {
                background-position: 100% 0;
            }

            100% {
                background-position: -100% 0;
            }
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
                    <h1 class="innerpage-title">{{ ucwords(translate('Explore_Upcoming_puja_services_on_Mahakal.com')) }}
                    </h1>
                    <span class="font-normal font-normal">
                        {{ translate('book_puja_online_in_your_name_and_gotra_receive_the_puja_video_along_with_the_tirth_prasad_and_gain_blessings_from_the_divine') }}</span>
                </div>
            </div>
        </div>
    </div>
    {{-- <!-- Fixed search form -->
    <div class="fixed-search">
        <div class="container">
            <div class="row pt-3 pb-2">
                <div class="col-md-6">
                    <div class="pooja-search">
                        <div class="d-flex align-items-center gap-2">
                            <input class="form-control form-control-md" type="search" autocomplete="off"
                                placeholder="Search for puja name mahakal com" name="name">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}
    <section class="cal_about_wrapper">
        <div class="container-fluid rtl px-0 px-md-3">

            <div class="__inline-62 pt-2">
                <div class="container">
                <?php
                $dataFilterString = '';
                foreach ($subcategory as $subcat) {
                    $dataFilterString .= '.' . $subcat->slug . ',';
                }
                $dataFilterString = rtrim($dataFilterString, ', ');
                ?>
                <ul id="filters" class="clearfix pooja-menu" style="padding: 10px;">
                    <li><span class="filter active" data-filter="{{ $dataFilterString }}">All</span></li>
                    @foreach ($subcategory as $item)
                        <li><span class="filter" data-filter=".{{ $item->slug }}">{{ @Ucwords($item->name) }}</span>
                        </li>
                    @endforeach
                    <li>
                        <div id="search-icon" class="search-icon"><i class="fa fa-search mt-2"></i> </div>
                    </li>
                </ul>
                <div id="search-bar-container" class="pooja-search" style="display: none;">
                    <div class="d-flex align-items-center gap-2">
                        <input class="form-control" type="search" autocomplete="off" placeholder="Search for items..."
                            name="name" value="">
                    </div>
                </div>
                <div id="portfoliolist">
                @foreach ($PoojaShow as $poojaD)
                        @if ($poojaD->type == 'weekly')
                            @include('web-views.partials._pooja_weekly', ['poojaD' => $poojaD])
                        @else
                            @if (!empty($poojaD->type == 'special'))
                                @include('web-views.partials._pooja_special', ['poojaD' => $poojaD])
                            @endif
                        @endif
                    @endforeach

                    {{-- VIP POOJA --}}
                    @foreach ($vippooja as $vip)
                        <div class="portfolio vip-puja" data-cat="vip-puja">
                            <div class="portfolio-wrapper">
                                <div class="card">
                                    <span class="for-discount-value pooja-badge p-1 pl-2 pr-2 font-bold fs-13">
                                        <span class="direction-ltr blink d-block">{{ translate('VIP_pooja') }}</span>
                                    </span>
                                    @if (!empty($vip->thumbnail))
                                        <a href="{{ route('vip.details', $vip->slug) }}"><img
                                                src="{{ getValidImage(path: 'storage/app/public/pooja/vip/thumbnail/' . $vip->thumbnail) }}"
                                                class="card-img-top puja-image" alt="{{ $vip->thumbnail }}"></a>
                                    @else
                                        <a href="{{ route('vip.details', $vip->slug) }}"><img
                                                src="{{ getValidImage(path: 'storage/app/public/company/' . getWebConfig(name: 'loader_gif'), type: 'source', source: theme_asset(path: 'public/assets/front-end/img/kashi-vishwanath-temple.jpg')) }}"
                                                class="card-img-top puja-image" alt="..."></a>
                                    @endif
                                    <div class="card-body newpadding">
                                        <p class="pooja-heading underborder two-lines-only">
                                            {{ strtoupper($vip->pooja_heading) }}
                                        </p>
                                        <div class="w-bar h-bar bg-gradient mt-2"></div>
                                        <p class="pooja-name two-lines-only">{{ Str::words($vip->name, 20, '...') }}</p>
                                        <p class="card-text mt-2 mb-2 two-lines-only">{{ $vip->short_benifits }}</p>
                                        <div class="d-flex">
                                            <img src="{{ theme_asset(path: 'public\assets\front-end\img\track-order\temple.png') }}"
                                                alt="" style="width:24px;height:24px;">
                                            <p class="pooja-venue one-lines-only">{{ translate('Updated_soon') }}</p>
                                        </div>

                                        <div class="d-flex">
                                            <img src="{{ theme_asset(path: 'public\assets\front-end\img\track-order\date.png') }}"
                                                alt="" style="width:24px;height:24px;">
                                            <p class="pooja-calendar">
                                                {{ translate('You_can_choose_puja_date') }}</p>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <!-- Devotees Count -->
                                            <div class="d-flex align-items-center">
                                                <img src="{{ theme_asset(path: 'public/assets/front-end/img/track-order/users.gif') }}"
                                                    alt="Users" class="colored-icon"
                                                    style="width: 24px; height: 24px; margin-right: 5px;">
                                                <span class="pooja-calendar">{{ 10000 + $vip->pooja_order_review_count }}+
                                                    Devotees</span>
                                            </div>

                                            <!-- Star Rating -->
                                            <div class="d-flex align-items-center">
                                                {!! displayStarRating($vip->review_avg_rating ?? 0) !!}
                                                <span class="ml-2">({{ number_format($vip->review_avg_rating ?? 5, 1) }}/5)</span>
                                            </div>
                                        </div>
                                        <a href="{{ route('vip.details', $vip['slug']) }}" class="animated-button mt-2">
                                        
                                            <span class="text-wrapper">
                                                <span class="text-slide">{{ translate('GO_PARTICIPATE') }}</span>
                                                <span class="text-slide">{{ translate('Limited_slots!') }}</span>
                                            </span>
                                            <span class="icon">
                                                  <img src="{{ theme_asset(path: 'public/assets/front-end/img/track-order/arrow-white-icon.svg') }}" alt="arrow" />
                                            </span>
                                        </a>



                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    {{-- Anushthan --}}
                    @foreach ($anushthan as $anusvip)
                        {{-- Anushthan POOJA --}}
                        <div class="portfolio anushthan" data-cat="anushthan">
                            <div class="portfolio-wrapper">
                                <div class="card">
                                    <span class="for-discount-value  pooja-badge p-1 pl-2 pr-2 font-bold fs-13">
                                        <span class="direction-ltr blink d-block">{{ translate('anushthan') }}</span>
                                    </span>
                                    @if (!empty($anusvip->thumbnail))
                                        <a href="{{ route('anushthan.details', $anusvip->slug) }}"><img
                                                src="{{ getValidImage(path: 'storage/app/public/pooja/vip/thumbnail/' . $anusvip->thumbnail) }}"
                                                class="card-img-top puja-image" alt="{{ $anusvip->thumbnail }}"></a>
                                    @else
                                        <a href="{{ route('anushthan.details', $anusvip->slug) }}"><img
                                                src="{{ getValidImage(path: 'storage/app/public/company/' . getWebConfig(name: 'loader_gif'), type: 'source', source: theme_asset(path: 'public/assets/front-end/img/kashi-vishwanath-temple.jpg')) }}"
                                                class="card-img-top puja-image" alt="..."></a>
                                    @endif
                                    <div class="card-body newpadding">
                                        <p class="pooja-heading underborder two-lines-only">
                                            {{ strtoupper($anusvip->pooja_heading) }}
                                        </p>
                                        <div class="w-bar h-bar bg-gradient mt-2"></div>
                                        <p class="pooja-name two-lines-only">{{ Str::words($anusvip->name, 20, '...') }}
                                        </p>
                                        <p class="card-text mt-2 mb-2 two-lines-only">{{ $anusvip->short_benifits }}</p>
                                        <div class="d-flex">
                                            <img src="{{ theme_asset(path: 'public\assets\front-end\img\track-order\temple.png') }}"
                                                alt="" style="width:24px;height:24px;">
                                            <p class="pooja-venue one-lines-only">{{ translate('Updated_soon') }}</p>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <!-- Devotees Count -->
                                            <div class="d-flex align-items-center">
                                                <img src="{{ theme_asset(path: 'public/assets/front-end/img/track-order/users.gif') }}"
                                                    alt="Users" class="colored-icon"
                                                    style="width: 24px; height: 24px; margin-right: 5px;">
                                                <span
                                                    class="pooja-calendar">{{ 10000 + $anusvip->pooja_order_review_count }}+
                                                    Devotees</span>
                                            </div>

                                            <!-- Star Rating -->
                                            <div class="d-flex align-items-center">
                                                {!! displayStarRating($anusvip->review_avg_rating ?? 0) !!}
                                                <span
                                                    class="ml-2">({{ number_format($anusvip->review_avg_rating ?? 5, 1) }}/5)</span>
                                            </div>
                                        </div>
                                        <a href="{{ route('anushthan.details', $anusvip['slug']) }}" class="animated-button mt-2">
                                        
                                            <span class="text-wrapper">
                                                <span class="text-slide">{{ translate('GO_PARTICIPATE') }}</span>
                                                <span class="text-slide">{{ translate('Limited_slots!') }}</span>
                                            </span>
                                            <span class="icon">
                                                <img src="{{ theme_asset(path: 'public/assets/front-end/img/track-order/arrow-white-icon.svg') }}" alt="arrow" />
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                   
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
    {{-- <script src="{{ theme_asset(path: 'public/assets/front-end/js/api.js') }}"></script>
    <script src="{{ theme_asset(path: 'public/assets/front-end/js/panchang.js') }}"></script> --}}

    <script type="text/javascript">
        $(function() {
            var filterList = {
                init: function() {

                    $('#portfoliolist').mixItUp({
                        selectors: {
                            target: '.portfolio',
                            filter: '.filter'
                        },
                        load: {
                            filter: '{{ $dataFilterString }}'
                        }
                    });

                }

            };
            filterList.init();

            $('input[type="search"]').on('keyup', function() {
                var searchText = $(this).val().toLowerCase();
                var activeCategory = $('.filter.active').data('filter');

                $('#portfoliolist .portfolio').each(function() {

                    var poojaName = $(this).find('.pooja-name').text().toLowerCase();
                    var poojaHeading = $(this).find('.pooja-heading').text().toLowerCase();
                    var poojaVenue = $(this).find('.pooja-venue').text().toLowerCase();
                    var cardText = $(this).find('.card-text').text().toLowerCase();
                    var poojaCalendar = $(this).find('.pooja-calendar').text().toLowerCase();


                    var matchesSearch = poojaName.indexOf(searchText) > -1 ||
                        poojaHeading.indexOf(searchText) > -1 ||
                        poojaVenue.indexOf(searchText) > -1 ||
                        cardText.indexOf(searchText) > -1 ||
                        poojaCalendar.indexOf(searchText) > -1;

                    var matchesCategory = $(this).is(activeCategory);


                    $(this).toggle(matchesSearch && matchesCategory);
                });
            });

            $('.filter').on('click', function() {
                $('.filter').removeClass('active');
                $(this).addClass('active');

                var activeCategory = $(this).data('filter');
                var searchText = $('input[type="search"]').val().toLowerCase();

                $('#portfoliolist .portfolio').each(function() {
                    var poojaName = $(this).find('.pooja-name').text().toLowerCase();
                    var poojaHeading = $(this).find('.pooja-heading').text().toLowerCase();
                    var poojaVenue = $(this).find('.pooja-venue').text().toLowerCase();
                    var cardText = $(this).find('.card-text').text().toLowerCase();
                    var poojaCalendar = $(this).find('.pooja-calendar').text().toLowerCase();
                    var matchesSearch = poojaName.indexOf(searchText) > -1 ||
                        poojaHeading.indexOf(searchText) > -1 ||
                        poojaVenue.indexOf(searchText) > -1 ||
                        cardText.indexOf(searchText) > -1 ||
                        poojaCalendar.indexOf(searchText) > -1;
                    var matchesCategory = $(this).is(activeCategory);
                    $(this).toggle(matchesSearch && matchesCategory);
                });
            });


        });
    </script>
    <script>
        function showRemainingAddresses(that) {
            var id = $(that).data('id');
            var remainingDiv = document.getElementById('remainingAddresses' + id);
            if (remainingDiv.style.display === 'none') {
                remainingDiv.style.display = 'block';
            } else {
                remainingDiv.style.display = 'none';
            }
        }
        $(document).ready(function() {
            $('#search-icon').click(function() {
                $('#search-bar-container').toggle();
                $('#search-bar').focus();
            });
        });
    </script>
@endpush