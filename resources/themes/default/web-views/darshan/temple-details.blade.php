@extends('layouts.front-end.app')
@section('title', translate('darshan'))
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

<style>
    a.section-link.active {
        color: #ffffff !important;
        background: var(--base) !important;
        font-weight: bold;

    }

    a.section-link {
        border-radius: 100px !important;
        padding: 9px 17px;
        /* font-size: 13px; */
        text-decoration: none;
    }

    .user-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }

    .review-comment {
        display: inline-block;
        word-wrap: break-word;
        width: 100%;
    }

    .read-more-btn {
        color: #007bff;
        cursor: pointer;
        font-size: 14px;
        display: block;
        margin-top: 10px;
    }




    section {
        width: 100%;
        height: 300px;
    }


    .gallery-img {
        transition: transform 0.5s ease, filter 0.5s ease;
        width: 100%;
        display: block;
    }

    .image-container:hover .gallery-img {
        transform: scale(1.2);
        /* filter: blur(2px); */
    }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.4.1/css/swiper.min.css">
<link href="https://fonts.googleapis.com/css?family=Oswald:500" rel="stylesheet">
@endpush

@section('content')
<div class="__inline-61">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6 mt-3">
                <div class="cz-product-gallery">

                    <div class="cz-preview">
                        <div id="sync1" class="owl-carousel owl-theme product-thumbnail-slider">
                            @if (!empty($templeList['galleries2']['images']) && json_decode($templeList['galleries2']['images'], true))
                            <?php $p = 0; ?>
                            @foreach (json_decode($templeList['galleries2']['images'], true) as $key => $img)
                            <div class="product-preview-item align-items-center justify-content-center {{ $p == 0 ? 'active' : '' }}"
                                id="image{{ $p }}">
                                <img class="cz-image-zoom img-responsive w-100"
                                    src="{{ getValidImage(path: 'storage/app/public/temple/gallery/' . $img, type: 'product') }}"
                                    data-zoom="{{ getValidImage(path: 'storage/app/public/temple/gallery/' . $img, type: 'product') }}"
                                    alt="{{ translate('product') }}" width="">
                            </div>
                            <?php $p++; ?>
                            @endforeach
                            @endif
                        </div>
                        @if (!empty($templeList['galleries2']['images']) && json_decode($templeList['galleries2']['images'], true))
                        <div style="position: absolute; z-index: 99;bottom: 9px;margin-left: 5px; background: #a5a5a524;box-shadow: 0px 3px 6px rgb(141 140 140 / 59%); border-radius: 12px;"
                            class="pl-2 pr-2">
                            <a class="h5 mb-0 p-2" onclick="openGallery()"><i
                                    class="tio-photo_gallery">photo_gallery</i> Gallery</a>
                        </div>
                        @endif
                    </div>
                    <div class="d-flex flex-column gap-3">
                        <button type="button" data-product-id="{{ $templeList['id'] }}"
                            class="btn __text-18px border wishList-pos-btn d-sm-none product-action-add-wishlist">
                            <i class="fa fa-heart wishlist_icon_{{ $templeList['id'] }} web-text-primary"
                                aria-hidden="true"></i>
                        </button>
                        <div class="sharethis-inline-share-buttons share--icons text-align-direction">
                        </div>
                    </div>
                    <div class="cz">
                        <div class="table-responsive __max-h-515px" data-simplebar>
                            <div class="d-flex">
                                <div id="sync2" class="owl-carousel owl-theme product-thumb-slider">
                                    @if (!empty($templeList['galleries2']['images']) && json_decode($templeList['galleries2']['images'], true))
                                    <?php $p = 0; ?>
                                    @foreach (array_reverse(json_decode($templeList['galleries2']['images'], true)) as $key => $img1)
                                    <div class="">
                                        <a class="product-preview-thumb align-items-center justify-content-center {{ $p == 0 ? 'active' : '' }}"
                                            id="preview-img{{ $p }}"
                                            href="#image{{ $p }}">
                                            <img alt="{{ translate('product') }}"
                                                src="{{ getValidImage(path: 'storage/app/public/temple/gallery/' . $img1, type: 'product') }}">
                                        </a>
                                    </div>
                                    <?php $p++; ?>
                                    @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
            <div class="col-md-6 mt-3">
                <div class="">
                    <span class="text-12 font-bold  line-clamp-2 text-ellipsis mb-0 h3"
                        style="color:#fe9802; ">{{ $templeList['name'] }}</span>
                </div>
                <div class="w-bar h-bar bg-gradient mt-2">
                    <h6><i class="tio-star text-warning"></i>
                        @php
                        $number = round($ratings['total'], 1);
                        @endphp

                        @if ($number >= 1000000)
                        {{ round($number / 1000000, 1) . 'M' . '+' }}
                        @elseif ($number >= 1000)
                        {{ round($number / 1000, 1) . 'K' . '+' }}
                        @else
                        {{ $number }}
                        @endif

                        @php
                        $total_user_rating = 0;

                        if (!empty($ratings['list']) && count($ratings['list']) > 0) {
                        if (count($ratings['list']) >= 1000000) {
                        $total_user_rating = round(count($ratings['list']) / 1000000, 1) . 'M' . '+';
                        } elseif (count($ratings['list']) >= 1000) {
                        $total_user_rating = round(count($ratings['list']) / 1000, 1) . 'K' . '+';
                        } else {
                        $total_user_rating = count($ratings['list']);
                        }
                        }
                        @endphp

                        ({{ $total_user_rating }} {{ translate('Reviews') }})
                    </h6>
                </div>
                <div>
                    <i class="tio-neighborhood text-warning"></i><span class="font-weight-bold">
                        {{ $templeList['cities']['city'] }},
                        {{ ucwords(strtolower($templeList['states']['name'] ?? '')) }},
                        {{ $templeList['country']['name'] ?? '' }} </span>
                </div>
                <div class="mt-2">
                    <div class="row">
                        <div class="col-12">
                            <i class="tio-calendar_month text-warning">calendar_month</i>
                            <span>{{ translate('opening_hours') }} :
                                {{ date('h:i A', strtotime($templeList['opening_time'])) }} -
                                {{ date('h:i A', strtotime($templeList['closeing_time'])) }}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    {!! $templeList['short_description'] ?? '' !!}
                </div>
                <div class="mt-2">
                    {!! $templeList['expect_details'] !!}
                </div>
                <div class="mt-2">
                    {!! $templeList['tips_details'] !!}
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row mt-2">
            <div class="col-12">
                <div class="card card-body px-4 pb-3 mb-3 __rounded-10 pt-3">
                    <div class="navbar_section1 section-links d-flex justify-content-between mt-3 border-top border-bottom py-2 mb-4"
                        style="    overflow: auto;">
                        <a class="section-link ml-2 {{ request()->comment == 'success' || request()->comment == 'error' ? '' : 'active' }}"
                            href="#religious_Places">{{ translate('religious_Places') }}</a>
                        @if ($nearbyHotels->count() > 0)
                        <a class="section-link" href="#near-hotel-places">{{ translate('near_hotel') }}</a>
                        @endif
                        @if ($nearbyRestaurant->count() > 0)
                        <a class="section-link"
                            href="#near-restaurant-places">{{ translate('near_Restaurant') }}</a>
                        @endif
                        @if ($nearbyCities->count() > 0)
                        <a class="section-link" href="#near-cities-places">{{ translate('near_Cities') }}</a>
                        @endif
                        <a class="section-link" href="#more_info">{{ translate('more_info') }}</a>
                        <a class="section-link mr-2 {{ request()->comment == 'success' || request()->comment == 'error' ? 'active' : '' }}"
                            href="#review_user">{{ translate('Reviews') }}</a>
                    </div>
                    <div class="content-sections px-lg-3">
                        <!-- Inclusion Section -->
                        <div class="section-content {{ request()->comment == 'success' || request()->comment == 'error' ? '' : 'active' }}"
                            id="religious_Places">
                            <!-- start near temple -->
                            @include('web-views.darshan.partial.near-temple')
                            <!-- end near temple -->
                        </div>
                        @if ($nearbyHotels->count() > 0)
                        <div class="section-content" id="near-hotel-places">
                            <!-- start near Hotel -->
                            @include('web-views.darshan.partial.near-hotel')
                            <!-- end near Hotel -->
                        </div>
                        @endif
                        @if ($nearbyRestaurant->count() > 0)
                        <div class="section-content" id="near-restaurant-places">
                            <!-- start near restaurant -->
                            @include('web-views.darshan.partial.near-restaurant')
                            <!-- end near ratorent -->
                        </div>
                        @endif
                        @if ($nearbyCities->count() > 0)
                        <div class="section-content" id="near-cities-places">
                            <!-- start near cities -->
                            @include('web-views.darshan.partial.near-cities')
                            <!-- end near cities -->
                        </div>
                        @endif
                        <div class="section-content" id="more_info">
                            <div class="row mt-2 p-2"
                                style="background: white; box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.0509803922); border-radius: 5px; border-bottom: 3px solid transparent;">
                                <div class="col-12"><span class="h6 font-weight-bold"></span></div>
                                <div class="col-12 feature-product-title mt-0">
                                    {{ translate('more_Info') }}
                                    <h4 class="mt-2 height-10">
                                        <span class="divider">&nbsp;</span>
                                    </h4>
                                </div>
                                <div class="col-12 mt-2">
                                    {!! $templeList['details'] !!}
                                </div>
                            </div>
                            <div class="row mt-2 p-2"
                                style="background: white; box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.0509803922); border-radius: 5px; border-bottom: 3px solid transparent;">
                                <div class="col-12 mt-2">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-4 mt-1 font-weight-bold"><img src="{{ theme_asset(path: 'public/assets/front-end/img/temple.png') }}" style="width: 34px; padding-bottom: 14px; position: relative;">{{ translate('temple_known') }}
                                            </div>
                                            <div class="col-8 mt-1">{{ $templeList['temple_known'] }}</div>
                                            <div class="col-12 mt-1">
                                                <hr style="border: 1px solid #d0c6c624;">
                                            </div>
                                            <div class="col-4 mt-1 font-weight-bold"><img src="{{ theme_asset(path: 'public/assets/front-end/img/time.png') }}" style="width: 34px;padding: 0px 2px 10px 0px; position: relative;">{{ translate('timings') }}</div>
                                            <div class="col-8 mt-1">{{ translate('open') }} :
                                                {{ date('h:i A', strtotime($templeList['opening_time'])) }}
                                                {{ translate('close') }} :
                                                {{ date('h:i A', strtotime($templeList['closeing_time'])) }}
                                            </div>
                                            <div class="col-12 mt-1">
                                                <hr style="border: 1px solid #d0c6c624;">
                                            </div>
                                            <div class="col-4 mt-1 font-weight-bold"><img src="{{ theme_asset(path: 'public/assets/front-end/img/entry_free.jpg') }}" style="width: 34px;padding: 0px 2px 10px 0px; position: relative;">{{ translate('Entry fee') }}
                                            </div>
                                            <div class="col-8 mt-1">{{ $templeList['entry_fee'] }}</div>
                                            <div class="col-12 mt-1">
                                                <hr style="border: 1px solid #d0c6c624;">
                                            </div>
                                            <div class="col-4 mt-1 font-weight-bold"><img src="{{ theme_asset(path: 'public/assets/front-end/img/Tips_and_restrictions.png') }}" style="width: 34px;padding: 0px 2px 10px 0px; position: relative;">
                                                {{ translate('tips_and_restrictions') }}
                                            </div>
                                            <div class="col-8 mt-1">{{ $templeList['tips_restrictions'] }}</div>
                                            <div class="col-12 mt-1">
                                                <hr style="border: 1px solid #d0c6c624;">
                                            </div>
                                            <div class="col-4 mt-1 font-weight-bold"><img src="{{ theme_asset(path: 'public/assets/front-end/img/facilities.png') }}" style="width: 34px; padding: 0px 2px 10px 0px; position: relative;">{{ translate('facilities') }}
                                            </div>
                                            <div class="col-8 mt-1">{{ $templeList['facilities'] }}</div>
                                            <div class="col-12 mt-1">
                                                <hr style="border: 1px solid #d0c6c624;">
                                            </div>

                                            <div class="col-4 mt-1 font-weight-bold"><img src="{{ theme_asset(path: 'public/assets/front-end/img/require_time.png') }}" style="width: 34px; padding-bottom: 14px; position: relative;">{{ translate('Require time') }}
                                            </div>
                                            <div class="col-8 mt-1">{{ $templeList['require_time'] }}</div>
                                            <div class="col-12 mt-1">
                                                <hr style="border: 1px solid #d0c6c624;">
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2 p-2"
                                style="background: white; box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.0509803922); border-radius: 5px; border-bottom: 3px solid transparent;">
                                <div class="col-12 mt-2">
                                    {!! $templeList['more_details'] !!}
                                </div>
                            </div>
                            <div class="row mt-2 p-2"
                                style="background: white; box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.0509803922); border-radius: 5px; border-bottom: 3px solid transparent;">
                                <div class="col-12 mt-2">
                                    {!! $templeList['temple_services'] !!}
                                </div>
                            </div>
                            <div class="row mt-2 p-2"
                                style="background: white; box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.0509803922); border-radius: 5px; border-bottom: 3px solid transparent;">
                                <div class="col-12 mt-2">
                                    {!! $templeList['temple_aarti'] !!}
                                </div>
                            </div>
                            <div class="row mt-2 p-2"
                                style="background: white; box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.0509803922); border-radius: 5px; border-bottom: 3px solid transparent;">
                                <div class="col-12 mt-2">
                                    {!! $templeList['tourist_place'] !!}
                                </div>
                            </div>
                            <div class="row mt-2 p-2"
                                style="background: white; box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.0509803922); border-radius: 5px; border-bottom: 3px solid transparent;">
                                <div class="col-12 mt-2">
                                    {!! $templeList['temple_local_food'] !!}
                                </div>
                            </div>
                        </div>
                        <div class="section-content {{ request()->comment == 'success' || request()->comment == 'error' ? 'active' : '' }}"
                            id="review_user">
                            <div class="row p-2 mt-2"
                                style="background: white; box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.0509803922); border-radius: 5px; border-bottom: 3px solid transparent;">
                                <div class="col-lg-4 px-max-md-0">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="suggestion-card">
                                                <div class="text-capitalize">
                                                    <p class="text-capitalize mb-0">
                                                        <a class='h3'>
                                                            {{ round($ratings['total'], 1) }}&nbsp;
                                                        </a>
                                                        <big>
                                                            @for ($inc = 1; $inc <= 5; $inc++)
                                                                @if ($inc <=(int) $ratings['total'])
                                                                <i class="tio-star text-warning"></i>
                                                                @elseif ($ratings['total'] != 0 && $inc <= (int) $ratings['total'] + 1.1 && $ratings['total']> ((int) $ratings['total']))
                                                                    <i class="tio-star-half text-warning"></i>
                                                                    @else
                                                                    <i class="tio-star-outlined text-warning"></i>
                                                                    @endif
                                                                    @endfor
                                                        </big>
                                                    </p>
                                                    <a class='small'>
                                                        &nbsp;{{ !empty($ratings['list']) && count($ratings['list']) > 0 ? count($ratings['list']) : 0 }}
                                                        {{ translate('Reviews') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="suggestion-card mt-4 mb-4">
                                                @if (request()->comment == 'success')
                                                <div class="text-capitalize w-100">
                                                    <span
                                                        class="text-success font-weight-bold">{{ translate('The_comment_has_been_added_successfully') }}</span>
                                                </div>
                                                @elseif((\App\Models\TempleReview::where('user_id',(auth('customer')->id()??""))->where('temple_id',($templeList['id']??''))->exists()))

                                                @else
                                                <div class="text-capitalize w-100">
                                                    <a class="btn btn-sm web-text-primary btn-outline-warning"
                                                        data-toggle="modal" data-target="#add_comments">add comment</a>
                                                </div>
                                                @if (request()->comment == 'error')
                                                <span
                                                    class="text-danger font-weight-bold">{{ translate('This_comment_was_not_added,_request_field_was_not_filled._Please_check.') }}</span>
                                                @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-8 d-md-block px-max-md-0">
                                    @if (!empty($ratings['list']) && count($ratings['list']) > 0)
                                    <div class="owl-theme owl-carousel review-slider">
                                        @foreach ($ratings['list'] as $counselling)
                                        <div class="card product-single-hover shadow-none rtl">
                                            <div class="card-body position-relative">
                                                <div class=" d-flex align-items-center">
                                                    <!-- User Icon -->
                                                    <img src="{{ getValidImage(path: 'storage/app/public/profile/' . ($counselling['userData']['image'] ?? ''), type: 'product') }}"
                                                        alt="User Icon" class="user-icon"
                                                        style="width: 50px; height: 50px; border-radius: 50%; margin-right: 10px;">

                                                    <!-- User Name -->
                                                    <div>
                                                        <p class="fw-bold m-0">
                                                            {{ $counselling['userData']['name'] ?? 'user name' }}
                                                        </p>
                                                        <p class="m-0">
                                                            <big class="small">
                                                                @for ($inc = 1; $inc <= 5; $inc++)
                                                                    @if ($inc <=(int) $counselling['star'])
                                                                    <i class="tio-star text-warning"></i>
                                                                    @elseif (
                                                                    $counselling['star'] != 0 &&
                                                                    $inc <= (int) $counselling['star'] + 1.1 &&
                                                                        $counselling['star']> ((int) $counselling['star']))
                                                                        <i
                                                                            class="tio-star-half text-warning"></i>
                                                                        @else
                                                                        <i
                                                                            class="tio-star-outlined text-warning"></i>
                                                                        @endif
                                                                        @endfor
                                                            </big>

                                                        </p>
                                                    </div>

                                                </div>

                                                <div class="single-product-details min-height-unset"
                                                    style="height: 100px; overflow: hidden;">
                                                    <div>
                                                        <a class="text-capitalize fw-semibold review-comment">
                                                            {{ $counselling['comment'] ?? '' }}
                                                            @php $filePath = 'storage/event/comment/' . ($counselling['image']??''); @endphp
                                                            @if (!empty($counselling['image']) && file_exists($filePath))
                                                            <img alt="{{ translate('product') }}"
                                                                src="{{ getValidImage(path: 'storage/app/public/event/comment/' . $counselling['image'], type: 'product') }}"
                                                                class='border border-light'
                                                                style="width:50px">
                                                            @endif
                                                        </a>
                                                    </div>
                                                    <a onclick="read(this)"
                                                        class="read-more-btn">{{ translate('Read more') }}</a>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @else
                                    <div class="text-center text-capitalize">
                                        <img class="mw-100"
                                            src="{{ asset('public/assets/front-end/img/icons/empty-review.svg') }}"
                                            alt="">
                                        <p class="text-capitalize">
                                            <small>No review given yet!</small>
                                        </p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- <div class="tab-pane fade show" id="gallerys" role="tabpanel">
                                                <div class="row">
                                                    @if (!empty($templeList['galleries2']))
                                                    @foreach ($templeList['galleries2'] as $image)
    @if (!empty($image['images']) && json_decode($image['images'], true))
    @foreach (json_decode($image['images'], true) as $img)
    <div class="col-4 mb-4">
                                                        <div class="image-container rounded border border-warning shadow">
                                                            <img src="{{ getValidImage(path: 'storage/app/public/temple/gallery/' . $img, type: 'product') }}" class="img-fluid gallery-img">

                                                        </div>
                                                    </div>
    @endforeach
    @endif
    @endforeach
@else
    <div class="col-md-12 text-center">
                                                        <h5 class="mt-1 fs-14">{{ translate('no_Image_found') }}!</h5>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div> -->
    <div class="row">
        @if ($templeList['video_url'] != null && str_contains($templeList['video_url'], 'youtube.com/embed/'))
        <!-- <div class="col-12 mb-4">
                                        <iframe width="420" height="315" src="{{-- $templeList['video_url'] --}}"> </iframe>
                                    </div> -->
        <div class="col-12 rtl text-align-direction">
            <style>
                .resp-iframe__container {
                    position: relative;
                    overflow: hidden;
                    border-radius: 1rem;
                }

                .resp-iframe__embed {
                    position: absolute;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    border: 0;
                }
            </style>
            <div class="resp-iframe">
                <div class="resp-iframe__container">
                    <iframe width="420" height="315" src="{{ $templeList['video_url'] }}" frameborder="0"
                        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen="">
                    </iframe>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="add_comments" tabindex="-1" aria-labelledby="addCommentsLabel" aria-hidden="true">
    <style>
        .star-rating {
            white-space: nowrap;
        }

        .star-rating [type="radio"] {
            appearance: none;
        }

        .star-rating i {
            font-size: 2.2em;
            transition: 0.3s;
        }

        .star-rating label:is(:hover, :has(~ :hover)) i {
            transform: scale(1.35);
            color: #fffdba;
            animation: jump 0.5s calc(0.3s + (var(--i) - 1) * 0.15s) alternate infinite;
        }

        .star-rating label:has(~ :checked) i {
            color: #faec1b;
            text-shadow: 0 0 2px #ffffff, 0 0 10px #ffee58;
        }

        @keyframes jump {

            0%,
            50% {
                transform: translatey(0) scale(1.35);
            }

            100% {
                transform: translatey(-15%) scale(1.35);
            }
        }
    </style>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCommentsLabel">{{ $templeList['name'] }}</h5>
                <button type="button" class="btn btn-close" data-dismiss="modal" aria-label="Close"><i
                        class="fa fa-times" aria-hidden="true"></i></button>
            </div>
            <form method="post" action="{{ route('temple-add-comment') }}">
                @csrf
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-12 text-center h3">
                            <span class="star-rating">
                                <label for="rate-1" style="--i:1"><i class="fa fa-solid fa-star"></i></label>
                                <input type="radio" name="rating" id="rate-1" value="1">
                                <label for="rate-2" style="--i:2"><i class="fa fa-solid fa-star"></i></label>
                                <input type="radio" name="rating" id="rate-2" value="2" checked>
                                <label for="rate-3" style="--i:3"><i class="fa fa-solid fa-star"></i></label>
                                <input type="radio" name="rating" id="rate-3" value="3">
                                <label for="rate-4" style="--i:4"><i class="fa fa-solid fa-star"></i></label>
                                <input type="radio" name="rating" id="rate-4" value="4">
                                <label for="rate-5" style="--i:5"><i class="fa fa-solid fa-star"></i></label>
                                <input type="radio" name="rating" id="rate-5" value="5">
                            </span>
                        </div>
                        <input type="hidden" name="temple_id" value="{{ $templeList['id'] }}">
                    </div>
                    <div class="mb-3">
                        <label for="comment" class="form-label">Add Comment</label>
                        <textarea class="form-control" name="comment" rows="4" placeholder="{{ translate('Share your thoughts') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Submit Comment</button>
                </div>
            </form>
        </div>
    </div>
</div>



<div class="modal fade" id="imageGalleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body">
                <div class="mb-4">
                    <button type="button" class="btn btn-danger btn-sm float-end borer mb-2 text-white"
                        data-dismiss="modal" aria-label="Close" style="    margin: -32px -22px 0px 0px;">x</button>
                </div>
                <div id="imageGalleryCarousel" class="carousel slide" data-ride="carousel">
                    <div class="carousel-inner">
                        @if (!empty($templeList['galleries2']['images']) && json_decode($templeList['galleries2']['images'], true))
                        @foreach (json_decode($templeList['galleries2']['images'] ?? '[]', true) as $index => $image)
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }} text-center">
                            <img src="{{ getValidImage(path: 'storage/app/public/temple/gallery/' . $image, type: 'product') }}"
                                alt="Image {{ $index + 1 }}" style="    height: 229px;">
                        </div>
                        @endforeach
                        @endif
                    </div>

                    <a class="carousel-control-prev" href="#imageGalleryCarousel" role="button" data-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="sr-only">Previous</span>
                    </a>
                    <a class="carousel-control-next" href="#imageGalleryCarousel" role="button" data-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="sr-only">Next</span>
                    </a>
                </div>
                <div class="d-flex mt-3 justify-content-center">
                    @foreach (json_decode($templeList['galleries2']['images'] ?? '[]', true) as $index => $image)
                    <div class="mx-1">
                        <img src="{{ getValidImage(path: 'storage/app/public/temple/gallery/' . $image, type: 'product') }}"
                            class="img-thumbnail"
                            style="width: 60px; height: 60px; object-fit: cover; cursor: pointer;"
                            onclick="setActiveSlide({{ $index }})" alt="Thumbnail {{ $index + 1 }}">
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>


@endsection

@push('script')
<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.4.1/js/swiper.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/2.0.2/TweenMax.min.js"></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/js/home.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
<script>
    ! function(e) {
        "undefined" == typeof module ? this.charming = e : module.exports = e
    }(function(e, n) {
        "use strict";
        n = n || {};
        var t = n.tagName || "span",
            o = null != n.classPrefix ? n.classPrefix : "char",
            r = 1,
            a = function(e) {
                for (var n = e.parentNode, a = e.nodeValue, c = a.length, l = -1; ++l < c;) {
                    var d = document.createElement(t);
                    o && (d.className = o + r, r++), d.appendChild(document.createTextNode(a[l])), n.insertBefore(d,
                        e)
                }
                n.removeChild(e)
            };
        return function c(e) {
            for (var n = [].slice.call(e.childNodes), t = n.length, o = -1; ++o < t;) c(n[o]);
            e.nodeType === Node.TEXT_NODE && a(e)
        }(e), e
    });
</script>
<script></script>
<script>
    function read(el) {
        var parentDiv = $(el).closest('.single-product-details');
        var commentDiv = parentDiv.find('.review-comment');
        if (parentDiv.css('height') === '100px') {
            parentDiv.css('height', 'auto'); // Expand
            commentDiv.css('-webkit-line-clamp', '10');
            $(el).text("{{ translate('Read less') }}");
        } else {
            parentDiv.css('height', '100px'); // Collapse
            commentDiv.css('-webkit-line-clamp', '1');
            $(el).text("{{ translate('Read more') }}");
        }
    }
</script>
<script>
    $('.nearby-slider-1').owlCarousel({
        loop: false,
        autoplay: true,
        margin: 20,
        nav: true,
        navText: directionFromSession === 'rtl' ? ["<i class='czi-arrow-right'></i>",
            "<i class='czi-arrow-left'></i>"
        ] : ["<i class='czi-arrow-left'></i>", "<i class='czi-arrow-right'></i>"],
        dots: false,
        autoplayHoverPause: true,
        rtl: directionFromSession === 'rtl',
        ltr: directionFromSession === 'ltr',
        responsive: {
            0: {
                items: 1
            },
            360: {
                items: 1
            },
            375: {
                items: 1
            },
            540: {
                items: 2
            },
            576: {
                items: 2
            },
            768: {
                items: 3
            },
            992: {
                items: 4
            },
            1200: {
                items: 6
            },
        },
    });

    $(document).ready(function() {
        $('.section-link').on('click', function(e) {
            e.preventDefault();

            const targetId = $(this).attr('href');
            $('html, body').animate({
                scrollTop: $(targetId).offset().top - $('.navbar_section1').outerHeight() - 200
            }, 200);

        });

        $(window).on('scroll', function() {
            const scrollTop = $(window).scrollTop() + $('.navbar_section1').outerHeight() + 200;
            if (scrollTop > 900) {
                $('.navbar-stuck-toggler').removeClass('show');
                $('.navbar-stuck-menu').removeClass('show');
                $(".navbar_section1").css({
                    'position': 'sticky',
                    'top': '84px',
                    'right': '3px',
                    'left': '3px',
                    'background-color': '#fff',
                    'z-index': '1000',
                    'box-shadow': '0 2px 10px rgba(0, 0, 0, 0.1)',
                    'overflow': 'auto',
                });
            } else {
                $(".navbar_section1").css({
                    'position': 'static',
                    'box-shadow': 'none'
                });
            }
            $('.section-content').each(function() {
                const sectionTop = $(this).offset().top - 100;
                const sectionBottom = sectionTop + $(this).outerHeight();
                const sectionId = $(this).attr('id');
                const navLink = $(`.section-link[href="#${sectionId}"]`);

                if (scrollTop >= sectionTop && scrollTop < sectionBottom) {
                    $('.section-link').removeClass('active'); // Remove active from all links
                    navLink.addClass('active'); // Add active to the current section link
                }
            });
        });
    });

    function openGallery() {
        var myModal = new bootstrap.Modal(document.getElementById('imageGalleryModal'), {
            keyboard: true
        });
        myModal.show();
    }

    function setActiveSlide(index) {
        $('#imageGalleryCarousel').carousel('pause');
        $(".carousel-item").removeClass('active');
        $(".carousel-item").eq(index).addClass('active');
        $('#imageGalleryCarousel').carousel(index);
        $('#imageGalleryCarousel').carousel('cycle');
    }

    $('#imageGalleryCarousel').carousel({
        interval: 3000
    });
</script>
@endpush