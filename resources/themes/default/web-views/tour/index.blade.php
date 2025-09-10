@extends('layouts.front-end.app')

@section('title', translate('Dharmik Yatra Packages – Ujjain Mahakal Darshan aur Teerth Yatra Book Karein'))
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
<meta property="twitter:description" content="{{ substr(strip_tags(str_replace('&nbsp;', ' ', $web_config['about']->value)), 0, 160) }}">
<meta name="description" content="Mahakal.com par Ujjain Mahakal Darshan, Omkareshwar, Maihar, Chitrakoot aur anya teerth sthal ke liye pavitra yatra packages book karein. Aaramdayak aur bhaktimay yatra anubhav paayein.">

<link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/style.css') }}">

<link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/owl.carousel.min.css') }}">
<link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/owl.theme.default.min.css') }}">
<link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/owl.theme.default.min.css') }}">

<link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/poojafilter/layout.css') }}">
<link href="{{ theme_asset(path: 'public/assets/front-end/vendor/fancybox/css/jquery.fancybox.min.css') }}"
    rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    .gold {
        color: #fe9802;
    }

    #no-tour-message {
        font-size: 18px;
        color: #ff0000;
        font-weight: bold;
    }

    /* Make scrollbar very thin */
    ::-webkit-scrollbar {
        width: 2px;
        /* Change to 2px if 1px is too small to be visible */
        height: 2px;
    }

    /* Change scrollbar track */
    ::-webkit-scrollbar-track {
        background: transparent;
    }

    /* Change scrollbar thumb */
    ::-webkit-scrollbar-thumb {
        background: #888;
        /* Change color */
        border-radius: 10px;
    }

    /* Hide scrollbar when not scrolling */
    ::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    .carousel-tour-visit .item {
        margin: 5px;
    }

    .vertical-activity-card__photo img {
        width: 100%;
        height: auto;
        border-radius: 10px;
    }


    .card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .nav-link.location.active {
        font-weight: bold;
    }

    .nav-link.location {
        color: black !important;
    }

    svg text {
        font-family: Lora;
        letter-spacing: 10px;
        stroke: #fe9802;
        font-size: 50px;
        font-weight: 700;
        stroke-width: 2;
        animation: textAnimate 3s infinite alternate;
    }

    @keyframes textAnimate {
        0% {
            stroke-dasharray: 0 50%;
            stroke-dashoffset: 10%;
            fill: hsl(35.99deg 100% 57.62%)
        }

        50% {
            stroke-dasharray: 20% 0;
            stroke-dashoffstet: -20%;
            fill: hsla(19, 6%, 7%, 0%)
        }

        100% {
            stroke-dasharray: 50% 0;
            stroke-dashoffstet: -20%;
            fill: hsla(189, 68%, 75%, 0%)
        }

    }

    .loader-icon-search {
        display: none;
        font-size: 1.5rem;
        /* Adjust size as needed */
    }

    .one-line-show {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .responsive-bg {
        padding-top: 6rem !important;
        padding-bottom: 7rem !important;
        background:url("{{ asset('public/assets/front-end/img/slider/yatra-booking.jpg') }}") no-repeat; 
        /*background:url("{{ asset('assets/front-end/img/slider/yatra-booking.jpg') }}") no-repeat;*/
        background-size: cover;
        background-position: center center;
    }

    @media (max-width: 768px) {
        .responsive-bg {
            padding-top: 2.91rem !important;
            padding-bottom: 3rem !important;
            background:url("{{ asset('assets/front-end/img/slider/yatra-booking1.jpg') }}") no-repeat;
            /* background:url("{{ asset('public/assets/front-end/img/slider/yatra-booking1.jpg') }}") no-repeat; */
            background-size: cover;
            background-position: center center;
        }
        .single-product-details{
            font-size: 11px;
        }
        .pooja-calendar {
            font-size: 9px;
        }
    }
</style>
@endpush

@section('content')
{{-- main page --}}
<div class="inner-page-bg center bg-bla-7 responsive-bg">
    <div class="container">
        <div class="row all-text-white">
            <div class="col-md-12 align-self-center">
            </div>
        </div>
    </div>
</div>
<div class="container-fluid">
    <div class="row"
        tyle="position: relative; background:url({{ getValidImage(path: 'storage/app/public/tour/video/75d4f624ef44d259c3f4431b6790d963.jpg', type: 'backend-product') }}) no-repeat;background-size:cover;background-position:center;background-attachment: fixed;">
        <div
            tyle="position: absolute;    top: 0;    left: 0;    right: 0;    bottom: 0;    background-color: rgb(52 51 51 / 38%);     z-index: 1;">
        </div>
        <div class="col-md-12 mt-4" style="position: relative; z-index: 2;">
            <div class="row justify-content-center align-items-center" style="position: relative; z-index: 2;">
                <div class="col-6 position-relative">
                    <input type="text" name='search' class="form-control border-0 fw-bold search_key"
                        style="margin-top: -44px;position: absolute;"
                        placeholder="{{ translate('search_by_Tour_Name') }}" autocomplete="off">
                    <div id="loader" class="loader-icon-search"
                        style="position: absolute; left: 10px; top: 10px; display: none;">
                        <i class="fa fa-spinner fa-spin"></i>
                    </div>
                    
                    <form action="{{ route('tour.tour-visit') }}" method="get">
                        <input type="hidden" name="id" class="search_ids" value="">
                        <ul id="search-results" class="list-group position-absolute w-100" style="top: 5px;"></ul>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-12 mt-4">
            <ul id="filters" class="clearfix">
                <li><span class="filter all_tours" data-filter=".all_tours"
                        onclick="toggleSubcategories('all_tours','all_tours')">{{ translate('All_Tour_Packages') }}</span>
                </li>
                @if ($headers)
                @foreach ($headers as $va)
                @php
                if ($loop->index == 0) {
                $get_first_name = $va['slug'];
                }
                @endphp
                <li><span
                        class="filter {{ $va['slug'] }} {{ $va['slug'] == 'citie_tour' || $va['slug'] == 'cities_tour' ? 'category_in_cities' : '' }}"
                        data-filter=".{{ $va['slug'] }}"
                        onclick="toggleSubcategories(`{{ $va['slug'] }}`,`{{ $va['name'] }}`)">{{ $va['name'] }}</span>
                </li>
                @endforeach
                @endif
                <li class="float-right">
                    <div class="input-group-overlay search-form-mobile text-align-direction">
                        <div class="d-flex align-items-center gap-2">
                            <input class="form-control" type="search" autocomplete="off"
                                placeholder="Search for items..." name="name" value="">
                        </div>
                    </div>
                </li>
            </ul>
            <ul class="list-group list-group-horizontal clearfix cities_tour-none citie_tour-none"
                style="text-wrap: nowrap;overflow: auto;padding:8px 0px 8px 0px;">
                @if ($state_name)
                @foreach ($state_name as $key => $pur)
                <?php
                $getStates = \App\Models\TourVisits::select('cities_name', 'state_name')->where('id', $pur['id'])->first();
                ?>
                <li class="list-group-item p-0 border-0 filter_state_name rounded text-center"
                    style="margin-left:1%;padding: 1px 6px 0px 6px !important;cursor: pointer;"
                    onclick="new_chanage()"
                    data-filter=".{{ Illuminate\Support\Str::Slug($getStates['state_name'], '_') }}">
                    <span class=" square-btn btn-sm subcategory"> {{ $pur['state_name'] }}</span>
                </li>
                @endforeach
                @endif
            </ul>

            <div id="portfoliolist" class="row">
                @if (!empty($getDataAll) && count($getDataAll) > 0)
                @foreach ($getDataAll as $use)
                <?php
                $getStates = \App\Models\TourVisits::select('cities_name', 'state_name')->where('id', $use['id'])->first();
                ?>
                <div
                    class="portfolio {{ $use['tour_type'] }} all_tours {{ $getStates['cities_name'] }} {{ Illuminate\Support\Str::Slug($getStates['cities_name'], '_') }} {{ Illuminate\Support\Str::Slug($getStates['state_name'], '_') }}    {{ \Illuminate\Support\Str::Slug($getStates['state_name'], '_') }}_{{ $use['tour_type'] }} {{ \Illuminate\Support\Str::Slug($getStates['state_name'], '_') }}_all_tours">
                    <div class="portfolio-wrapper">
                        <div class="card">
                            @if (!empty($use['number_of_day']))
                            <span class="for-discount-value pooja-badge p-1 pl-2 pr-2 font-bold fs-13">
                                <span class="direction-ltr blink d-block">
                                    {{ $use['number_of_day'] }}
                                </span>
                            </span>
                            @endif
                            <a href="{{ route('tour.tourvisit', [($use['slug']??'')]) }}">
                                <img src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . $use['tour_image'], type: 'product') }}"
                                    class="card-img-top puja-image" alt="...">
                            </a>
                            <div class="card-body">
                                <h5 class="font-weight-700 pooja-heading underborder card-title"
                                    style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; line-height: 1.5em; min-height: 3em;">
                                    {{ $use['tour_name'] }} &nbsp;
                                </h5>
                                <div class="mt-2 single-product-details min-height-unset">
                                    <div class="row px-3">
                                        <div class="col-12 text-left" style="display: ruby;">
                                            <?php
                                            $price_minst = 0;
                                            if (!empty($use['cab_list_price'])) {
                                                $decodedPrices = json_decode($use['cab_list_price'], true);
                                                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedPrices)) {
                                                    $prices = array_column($decodedPrices, 'price');
                                                    $price_minst = !empty($prices) ? min($prices) : 0;
                                                }
                                            }
                                            $package_tourone = [];
                                            $include_package_amount = 0;
                                            ?>
                                            <?php
                                            if (!empty($use['package_list_price']) && json_decode($use['package_list_price'], true)) {
                                                foreach (json_decode($use['package_list_price'], true) as $keyk => $plis) {
                                                    $tourPackages = \App\Models\TourPackage::where('id', $plis['package_id'])->first();
                                                    if ($tourPackages && !isset($package_tourone[$tourPackages['type']])) {
                                                        $package_tourone[$tourPackages['type']] = [
                                                            'package_id' => $plis['package_id'],
                                                            'image' => theme_asset(path: 'public/assets/front-end/img/' . ($tourPackages['type'] . '.png' ?? '')),
                                                        ];
                                                    }
                                                    $include_package_amount += $plis['pprice'] ?? 0;
                                                }
                                            }
                                            ?>
                                            <div class="px-2">
                                                <img src="{{ theme_asset(path: 'public/assets/front-end/img/sightseeing.png') }}"
                                                    style="width: 49px; height: 42px; margin-bottom: 4px;">
                                                <div class="ico-nem"><span
                                                        class="small font-weight-bold">{{ translate('sightseeing') }}</span>
                                                </div>
                                            </div>
                                            <div class="px-2">
                                                <img src="{{ theme_asset(path: 'public/assets/front-end/img/car.png') }}"
                                                    style="width: 49px; height: 42px; margin-bottom: 4px;">
                                                <div class="ico-nem"><span
                                                        class="small font-weight-bold">{{ translate('transport') }}</span>
                                                </div>
                                            </div>
                                            <?php if (!empty($package_tourone)) {
                                                ksort($package_tourone); ?>
                                                <?php foreach ($package_tourone as $key => $t_va) { ?>
                                                    <div class="px-2">
                                                        <img src="<?= htmlspecialchars($t_va['image']) ?>"
                                                            style="width: 49px; height: 42px; margin-bottom: 4px;">
                                                        <div class="ico-nem"><span
                                                                class="small font-weight-bold"><?= htmlspecialchars(translate($key)) ?></span>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div>
                                        <small class="ml-2 one-line-show mt-2">
                                            @if ($use['use_date'] == 1 || $use['use_date'] == 4 || $use['use_date'] == 2)
                                            {{ translate('pickup_From') }} :
                                            {{ $use['pickup_location'] ?? '' }}
                                            @endif
                                        </small>
                                    </div>
                                    <p class="ml-2 mb-1">
                                        @if ($use['use_date'] == 1)
                                        <?php
                                        $dates_formatt = explode(' - ', $use['startandend_date'] ?? '');
                                        $start_date1 = isset($dates_formatt[0]) ? \Carbon\Carbon::parse($dates_formatt[0])->format('d M,Y') : '';
                                        $end_date2 = isset($dates_formatt[1]) ? \Carbon\Carbon::parse($dates_formatt[1])->format('d M,Y') : '';
                                        ?>
                                        <span class="fw-semibold card-title"> Date : {{ $start_date1 }}
                                            To {{ $end_date2 }}</span>
                                        @else
                                        &nbsp;
                                        @endif
                                    </p>
                                    @php
                                    $price_minst = 0;
                                    if (
                                    !empty($use['cab_list_price']) &&
                                    json_decode($use['cab_list_price'], true)
                                    ) {
                                    $prices = array_column(
                                    json_decode($use['cab_list_price'], true),
                                    'price',
                                    );
                                    $price_minst = min($prices);
                                    }
                                    @endphp
                                    @if ($use['use_date'] == 1 || $use['use_date'] == 2 || $use['use_date'] == 3 || $use['use_date'] == 4)
                                    <a class="text-capitalize fw-semibold ml-2 card-title">{{ translate('min_price') }}
                                        :
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($price_minst ?? 0) + ($include_package_amount ?? 0)), currencyCode: getCurrencyCode()) }}
                                    </a>
                                    @else
                                    <a class="text-capitalize fw-semibold ml-2 card-title">{{ translate('minimum_price') }}
                                        :
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $price_minst ?? 0), currencyCode: getCurrencyCode()) }}
                                    </a>
                                    @endif
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <!-- Devotees Count -->
                                    <div class="d-flex align-items-center">
                                        <img src="{{ theme_asset(path: 'public/assets/front-end/img/track-order/users.gif') }}"
                                            alt="Users" class="colored-icon"
                                            style="width: 24px; height: 24px; margin-right: 5px;">
                                        <span class="pooja-calendar">{{ 10000 + $use->tour_order_review_count ?? 0 }} + People</span>
                                    </div>

                                    <!-- Star Rating -->
                                    <div class="d-flex align-items-center">
                                        {!! displayStarRating($use['review_avg_star'] ?? 0) !!}
                                        <span class="ml-2">({{ number_format($use->review_avg_star ?? 0, 1) }}/5)</span>
                                    </div>
                                </div>
                                <a href="{{ route('tour.tourvisit', [($use['slug']??'')]) }}"
                                    class="btn btn--primary btn-block btn-shadow mt-4 font-weight-bold">{{ translate('book_Now') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
@push('script')
<script src="{{ theme_asset(path: 'public/assets/front-end/js/home.js') }}"></script>
<script src="https://unpkg.com/gijgo@1.9.14/js/gijgo.min.js" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/gh/ethereumjs/browser-builds/dist/ethereumjs-tx/ethereumjs-tx-1.3.3.min.js">
</script>
<!-- <script src="{{ theme_asset(path: 'public/assets/front-end/js/home.js') }}"></script> -->
<script src="{{ theme_asset(path: 'public/assets/front-end/js/jquery.mixitup.min.js') }}"></script>
<script>
    $(".search_key").keyup(function() {
        var name = $(".search_key").val();

        if (name.length > 3) {
            // Show loader
            $("#loader").show();

            $.ajax({
                url: "{{ url('api/v1/tour/search') }}",
                data: {
                    name: name,
                    role: "web"
                },
                dataType: "json",
                type: "post",
                success: function(response) {
                    $("#loader").hide(); // Hide loader after getting response

                    // Check if results were found
                    if (response.status === 1) {
                        var resultHtml = response.data;
                        $("#search-results").html(resultHtml);
                    } else {
                        $("#search-results").html(
                            '<li class="list-group-item">No Results Found</li>');
                    }
                },
                error: function() {
                    $("#loader").hide(); // Hide loader on error
                    $("#search-results").html('<li class="list-group-item">Tour Not Available. Try another destination</li>');
                }
            });
        } else {
            $("#search-results").html(''); // Clear results if input length is less than 3
        }
    });
</script>

<script>
    $(document).ready(function() {
        var filterList = {
            init: function() {
                $('#portfoliolist').mixItUp({
                    selectors: {
                        target: '.portfolio',
                        filter: '.filter'
                    },
                    load: {
                        filter: '.all_tours'
                    }
                });
            }
        };

        filterList.init();

        // Search filter function
        $('input[type="search"]').on('keyup', function() {
            var searchText = $(this).val().toLowerCase();
            var activeCategory = $('.filter.active').data('filter');
            var found = false; // Track if any matching tour is found

            $('#portfoliolist .portfolio').each(function() {
                var matchesSearch = $(this).find('.card-title').text().toLowerCase().indexOf(
                    searchText) > -1;
                var matchesCategory = activeCategory === '.all_tours' || $(this).hasClass(
                    activeCategory.replace('.', ''));

                if (matchesSearch && matchesCategory) {
                    $(this).show();
                    found = true;
                } else {
                    $(this).hide();
                }
            });

            toggleNoTourMessage(found);
        });

        // Filter click event
        $('.filter').on('click', function() {
            $('.filter').removeClass('active');
            $('.filter_state_name').removeClass('active');
            $(this).addClass('active');

            var activeCategory = $(this).data('filter');
            var searchText = $('input[type="search"]').val().toLowerCase();
            var found = false;

            $('#portfoliolist .portfolio').each(function() {
                var matchesSearch = $(this).find('.card-title').text().toLowerCase().indexOf(
                    searchText) > -1;
                var matchesCategory = activeCategory === '.all_tours' || $(this).hasClass(
                    activeCategory.replace('.', ''));

                if (matchesSearch && matchesCategory) {
                    $(this).show();
                    found = true;
                } else {
                    $(this).hide();
                }
            });
            toggleNoTourMessage(found);
        });

        $('.filter_state_name').on('click', function() {
            $('.filter_state_name').removeClass('active');
            $(this).addClass('active');

            var activeCategory = $(this).data('filter');
            var activeCategory2 =
                `${activeCategory}_${($('.filter.active').data('filter')).replace('.', '')}`;
            var searchText = $('input[type="search"]').val().toLowerCase();
            var found = false;

            $('#portfoliolist .portfolio').each(function() {
                var matchesSearch = $(this).find('.card-title').text().toLowerCase().indexOf(
                    searchText) > -1;
                var matchesCategory = activeCategory2 === '.all_tours' || $(this).hasClass(
                    activeCategory2.replace('.', ''));

                if (matchesSearch && matchesCategory) {
                    $(this).show();
                    found = true;
                } else {
                    $(this).hide();
                }
            });

            toggleNoTourMessage(found);
        });

        // Function to toggle "No Tours Available" message
        function toggleNoTourMessage(found) {
            if (!found) {
                if ($('#no-tour-message').length === 0) {
                    $('#portfoliolist').append(
                        '<div id="no-tour-message" class="col-12 text-center my-3 text-danger">No Tours Available</div>'
                    );
                }
            } else {
                $('#no-tour-message').remove();
            }
        }
    });

    // $(document).ready(function() {
    //     var filterList = {
    //         init: function() {
    //             $('#portfoliolist').mixItUp({
    //                 selectors: {
    //                     target: '.portfolio',
    //                     filter: '.filter'
    //                 },
    //                 load: {
    //                     filter: '.all_tours'
    //                 }
    //             });
    //         }
    //     };

    //     filterList.init();

    //     // Search filter function
    //     $('input[type="search"]').on('keyup', function() {
    //         var searchText = $(this).val().toLowerCase();
    //         var activeCategory = $('.filter.active').data('filter');

    //         $('#portfoliolist .portfolio').each(function() {
    //             var matchesSearch = $(this).find('.card-title').text().toLowerCase().indexOf(searchText) > -1;

    //             // Adjusted logic to match dynamic class names
    //             var matchesCategory = activeCategory === '.all_tours' || $(this).hasClass(activeCategory.replace('.', ''));

    //             $(this).toggle(matchesSearch && matchesCategory);
    //         });
    //     });

    //     // Filter click event
    //     $('.filter').on('click', function() {
    //         $('.filter').removeClass('active');
    //         $('.filter_state_name').removeClass('active');
    //         $(this).addClass('active');

    //         var activeCategory = $(this).data('filter');
    //         var searchText = $('input[type="search"]').val().toLowerCase();

    //         $('#portfoliolist .portfolio').each(function() {
    //             var matchesSearch = $(this).find('.card-title').text().toLowerCase().indexOf(searchText) > -1;
    //             var matchesCategory = activeCategory === '.all_tours' || $(this).hasClass(activeCategory.replace('.', ''));
    //             console.log(activeCategory);
    //             $(this).toggle(matchesSearch && matchesCategory);
    //         });
    //     });


    //     // $('.filter_state_name').on('click', function() {
    //     //     $('.filter_state_name').removeClass('active');
    //     //     $(this).addClass('active');

    //     //     var activeCategory = $(this).data('filter');
    //     //     var activeCategory2 = `${activeCategory}_${($('.filter.active').data('filter')).replace('.', '')}`;
    //     //     var searchText = $('input[type="search"]').val().toLowerCase();

    //     //     $('#portfoliolist .portfolio').each(function() {
    //     //         var matchesSearch = $(this).find('.card-title').text().toLowerCase().indexOf(searchText) > -1;
    //     //         var matchesCategory = activeCategory2 === '.all_tours' || $(this).hasClass(activeCategory2.replace('.', ''));

    //     //         $(this).toggle(matchesSearch && matchesCategory);
    //     //     });
    //     // });
    //     $('.filter_state_name').on('click', function() {
    //         $('.filter_state_name').removeClass('active');
    //         $(this).addClass('active');

    //         var activeCategory = $(this).data('filter');
    //         var activeCategory2 = `${activeCategory}_${($('.filter.active').data('filter')).replace('.', '')}`;
    //         var searchText = $('input[type="search"]').val().toLowerCase();
    //         var found = false; // Variable to track if any matching tours are found

    //         $('#portfoliolist .portfolio').each(function() {
    //             var matchesSearch = $(this).find('.card-title').text().toLowerCase().indexOf(searchText) > -1;
    //             var matchesCategory = activeCategory2 === '.all_tours' || $(this).hasClass(activeCategory2.replace('.', ''));

    //             if (matchesSearch && matchesCategory) {
    //                 $(this).show();
    //                 found = true;
    //             } else {
    //                 $(this).hide();
    //             }
    //         });

    //         // Check if any tour is visible, if not, show a message
    //         if (!found) {
    //             if ($('#no-tour-message').length === 0) {
    //                 $('#portfoliolist').append('<div id="no-tour-message" class="col-12 text-center my-3">No Tours Available</div>');
    //             }
    //         } else {
    //             $('#no-tour-message').remove();
    //         }
    //     });

    // });

    // $(document).ready(function() {
    //     var filterList = {
    //         init: function() {
    //             $('#portfoliolist').mixItUp({
    //                 selectors: {
    //                     target: '.portfolio',
    //                     filter: '.filter'
    //                 },
    //                 load: {
    //                     filter: '.all_tours'
    //                 }
    //             });
    //         }
    //     };

    //     filterList.init();

    //     // Search filter function
    //     $('input[type="search"]').on('keyup', function() {
    //         var searchText = $(this).val().toLowerCase();
    //         var activeCategory = $('.filter.active').data('filter');

    //         $('#portfoliolist .portfolio').each(function() {
    //             var matchesSearch = $(this).find('.card-title').text().toLowerCase().indexOf(searchText) > -1;
    //             var matchesCategory = $(this).is(activeCategory);

    //             $(this).toggle(matchesSearch && matchesCategory);
    //         });
    //     });


    //     $('.filter').on('click', function() {
    //         $('.filter').removeClass('active');
    //         $(this).addClass('active');

    //         var activeCategory = $(this).data('filter');
    //         var searchText = $('input[type="search"]').val().toLowerCase();
    //         $('#portfoliolist .portfolio').each(function() {
    //             var matchesSearch = $(this).find('.card-title').text().toLowerCase().indexOf(searchText) > -1;
    //             var matchesCategory = $(this).is(activeCategory);

    //             $(this).toggle(matchesSearch && matchesCategory);
    //         });
    //     });
    // });

    function toggleSubcategories(type, name) {
        //     //$('.head-title-name-chnage').text(name.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase()));
        //    // if (type === 'cities_tour') {
        //         //$('.citie_tour-none').removeClass('d-none');
        //        // $('.cities_tour-none').removeClass('d-none');
        //    // } else if (type === 'citie_tour') {
        //        // $('.citie_tour-none').removeClass('d-none');
        //         //$('.cities_tour-none').removeClass('d-none');
        //     //} else {
        //     // $('.citie_tour-none').addClass('d-none');
        //     //$('.cities_tour-none').addClass('d-none');
        //     //}
    }

    // function new_chanage(){
    //     setTimeout(() => $('.category_in_cities').addClass('active'), 100);
    // }
</script>
@endpush