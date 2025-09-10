@extends('layouts.front-end.app')
@section('title', translate("Event_Booking"))
@push('css_or_js')
<link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/payment.css') }}">
<script src="https://polyfill.io/v3/polyfill.min.js?version=3.52.1&features=fetch"></script>
<script src="https://js.stripe.com/v3/"></script>
<link rel="stylesheet"
    href="{{ theme_asset(path: 'public/assets/front-end/plugin/intl-tel-input/css/intlTelInput.css') }}">
<style type="text/css">
    #productList {
        background-color: white;
        border-radius: 6px;
        box-shadow: 2px 2px 2px 2px #f3f3f3;
    }

    /* Prograss */
    @media (min-width: 768px) {
        .md\:top-\[68px\] {
            top: 68px;
        }
    }

    .w-full {
        width: 100%;
    }

    .z-20 {
        z-index: 20;
    }

    .top-0 {
        top: 0;
    }

    .sticky {
        position: sticky;
    }

    .bg-bar {
        --tw-bg-opacity: 1;
        background-color: #f3f4f6;
    }

    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .overflow-x-scroll {
        overflow-x: scroll;
    }

    .max-w-screen-xl {
        max-width: 1280px;
    }

    .justify-center {
        justify-content: center;
    }

    .items-center {
        align-items: center;
    }

    .px-2 {
        padding-left: .5rem;
        padding-right: .5rem;
    }

    .shrink-0 {
        flex-shrink: 0;
    }

    .text-next {
        --tw-text-opacity: 1;
        color: #1573DF;
    }

    .text-disable {
        --tw-text-opacity: 1;
        color: #5f6672;
    }

    .border-bar {
        --tw-border-opacity: 1;
        border-color: #5f6672 !important;
    }

    .border {
        border-width: 1px;
    }

    .rounded-full {
        border-radius: 9999px;
    }

    .circle-img-container:hover .circle-img {
        top: -8px;
        left: 0px;
        width: 40px;
        height: 43px;
        z-index: 10;
        max-height: 146px;
    }

    .circle-img-container .circle-img {
        width: 40px;
        height: 43px;
        overflow: hidden;
        position: absolute;
        left: 0;
        top: 0;
        transition: all 0.12s;
        margin-left: -20px;
        background-color: white;
    }

    .rounded-full {
        border-radius: 9999px;
    }

    .bg-center {
        background-position: center;
    }

    .bg-cover {
        background-size: cover;
    }

    .w-full {
        width: 100%;
    }

    .circle-img-container {
        width: 33px;
        height: 40px;
        position: relative;
    }

    .tray {
        text-align: center;
        display: flex;
        flex-wrap: none;
        align-items: center;
        justify-content: center;
        margin-right: 20rem;
        justify-content: center;
        margin-top: 12px;
    }

    .responsive-bg {
        padding-top: 6rem !important;
        padding-bottom: 7rem !important;
        /* background:url("{{ asset('assets/front-end/img/slider/events.jpg') }}") no-repeat; */
        background:url("{{ asset('public/assets/front-end/img/slider/events.jpg') }}") no-repeat;
        background-size: cover;
        background-position: center center;
    }

    @media (max-width: 768px) {
        .responsive-bg {
            padding-top: 2.91rem !important;
            padding-bottom: 3rem !important;
            /* background:url("{{ asset('assets/front-end/img/slider/events1.jpg') }}") no-repeat; */
            background:url("{{ asset('public/assets/front-end/img/slider/events1.jpg') }}") no-repeat;
            background-size: cover;
            background-position: center center;
        }

        .font-size-ten {
            font-size: 9px;
        }

        .font-size-towal {
            font-size: 12px;
        }
    }
</style>
@endpush
@section('content')
@php
$final_price_val = 0;
@endphp
<div class="w-full h-full sticky md:top-[68px] top-0 z-20">
    <div class="bg-bar w-full">
        <div class="d-flex overflow-x-scroll w-full scrollbar-hide max-w-screen-xl mx-auto" id="breadcrum-container-outer">
            <div class="d-flex flex-row items-center bg-bar h-14 px-4 md:px-0" id="breadcrum-container">
                <div class="bg-bar w-full">
                    <div class="d-flex overflow-x-scroll w-full scrollbar-hide max-w-screen-xl mx-auto" id="breadcrum-container-outer">
                        <div class="d-flex flex-row items-center bg-bar h-14 px-4 md:px-0" id="breadcrum-container">
                            <div class="d-flex justify-center items-center pt-3 pb-3 font-size-ten">
                                <div class="d-flex justify-center items-center">
                                    <svg class="shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="8" cy="8" r="8" fill="#00BD68"></circle>
                                        <path d="M6.98587 10.3993L4.80078 8.1901L5.65181 7.33194L6.98587 8.68297L10.3497 5.2793L11.2008 6.13746L6.98587 10.3993Z" fill="white"></path>
                                    </svg>
                                    <div class="pl-1 !w-full flex break-words md:whitespace-nowrap text-xs text-[#6B7280] font-medium  ">{{translate('Add Details')}}</div>
                                </div>
                                <div class="px-2 shrink-0 flex text-next"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M7.2051 10.9945C7.07387 10.8632 7.00015 10.6852 7.00015 10.4996C7.00015 10.314 7.07387 10.136 7.2051 10.0047L10.2102 6.99962L7.2051 3.99452C7.13824 3.92994 7.08491 3.8527 7.04822 3.7673C7.01154 3.6819 6.99223 3.59004 6.99142 3.4971C6.99061 3.40415 7.00832 3.31198 7.04352 3.22595C7.07872 3.13992 7.13069 3.06177 7.19642 2.99604C7.26214 2.93032 7.3403 2.87834 7.42633 2.84314C7.51236 2.80795 7.60453 2.79023 7.69748 2.79104C7.79042 2.79185 7.88228 2.81116 7.96768 2.84785C8.05308 2.88453 8.13032 2.93786 8.1949 3.00472L11.6949 6.50472C11.8261 6.63599 11.8998 6.814 11.8998 6.99962C11.8998 7.18523 11.8261 7.36325 11.6949 7.49452L8.1949 10.9945C8.06363 11.1257 7.88561 11.1995 7.7 11.1995C7.51438 11.1995 7.33637 11.1257 7.2051 10.9945Z" fill="#9CA3AF"></path>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.0051 10.9949C2.87387 10.8636 2.80015 10.6856 2.80015 10.5C2.80015 10.3144 2.87387 10.1364 3.0051 10.0051L6.0102 6.99999L3.0051 3.99489C2.87759 3.86287 2.80703 3.68605 2.80863 3.50251C2.81022 3.31897 2.88384 3.1434 3.01363 3.01362C3.14341 2.88383 3.31898 2.81022 3.50252 2.80862C3.68606 2.80703 3.86288 2.87758 3.9949 3.00509L7.4949 6.50509C7.62613 6.63636 7.69985 6.81438 7.69985 6.99999C7.69985 7.18561 7.62613 7.36362 7.4949 7.49489L3.9949 10.9949C3.86363 11.1261 3.68561 11.1998 3.5 11.1998C3.31438 11.1998 3.13637 11.1261 3.0051 10.9949Z" fill="#9CA3AF"></path>
                                    </svg>
                                </div>
                                <div class="d-flex justify-center items-center">
                                    <svg class="shrink-0" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="8" cy="8" r="8" fill="#00BD68"></circle>
                                        <path d="M6.98587 10.3993L4.80078 8.1901L5.65181 7.33194L6.98587 8.68297L10.3497 5.2793L11.2008 6.13746L6.98587 10.3993Z" fill="white"></path>
                                    </svg>
                                    <div class="pl-1 !w-full flex break-words md:whitespace-nowrap text-xs text-disable font-medium">{{ translate('Event')}}</div>
                                </div>
                                <div class="px-2 shrink-0 flex text-next"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M7.2051 10.9945C7.07387 10.8632 7.00015 10.6852 7.00015 10.4996C7.00015 10.314 7.07387 10.136 7.2051 10.0047L10.2102 6.99962L7.2051 3.99452C7.13824 3.92994 7.08491 3.8527 7.04822 3.7673C7.01154 3.6819 6.99223 3.59004 6.99142 3.4971C6.99061 3.40415 7.00832 3.31198 7.04352 3.22595C7.07872 3.13992 7.13069 3.06177 7.19642 2.99604C7.26214 2.93032 7.3403 2.87834 7.42633 2.84314C7.51236 2.80795 7.60453 2.79023 7.69748 2.79104C7.79042 2.79185 7.88228 2.81116 7.96768 2.84785C8.05308 2.88453 8.13032 2.93786 8.1949 3.00472L11.6949 6.50472C11.8261 6.63599 11.8998 6.814 11.8998 6.99962C11.8998 7.18523 11.8261 7.36325 11.6949 7.49452L8.1949 10.9945C8.06363 11.1257 7.88561 11.1995 7.7 11.1995C7.51438 11.1995 7.33637 11.1257 7.2051 10.9945Z" fill="#9CA3AF"></path>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.0051 10.9949C2.87387 10.8636 2.80015 10.6856 2.80015 10.5C2.80015 10.3144 2.87387 10.1364 3.0051 10.0051L6.0102 6.99999L3.0051 3.99489C2.87759 3.86287 2.80703 3.68605 2.80863 3.50251C2.81022 3.31897 2.88384 3.1434 3.01363 3.01362C3.14341 2.88383 3.31898 2.81022 3.50252 2.80862C3.68606 2.80703 3.86288 2.87758 3.9949 3.00509L7.4949 6.50509C7.62613 6.63636 7.69985 6.81438 7.69985 6.99999C7.69985 7.18561 7.62613 7.36362 7.4949 7.49489L3.9949 10.9949C3.86363 11.1261 3.68561 11.1998 3.5 11.1998C3.31438 11.1998 3.13637 11.1261 3.0051 10.9949Z" fill="#9CA3AF"></path>
                                    </svg>
                                </div>
                                <div class="d-flex justify-center items-center">
                                    <div class="d-flex justify-center items-center w-4 h-4 rounded-full  text-next  text-[10px]  font-medium shrink-0 ">3</div>
                                    <div class="pl-1 !w-full flex break-words md:whitespace-nowrap text-xs text-disable font-medium">{{ translate('Make Payment')}}</div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="inner-page-bg center bg-bla-7 py-4 responsive-bg">
    <div class="container">
        <div class="row all-text-white">
            <div class="col-md-12 align-self-center">
                <h1 class="innerpage-title">{{ ucwords(translate('Event_Booking')) }}</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active"><a href="{{ url('/') }}" class="text-white"><i class="fa fa-home"></i> Home</a></li>
                        <li class="breadcrumb-item">{{ ucwords(translate('Event_Booking')) }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>
@php
$langs = (str_replace('_', '-', app()->getLocale()) == 'in')?'hi': str_replace('_', '-', app()->getLocale())
@endphp
<div class="container mt-3 rtl text-align-direction" id="cart-summary">
    <!--  <h3 class="mt-4 mb-3 text-center text-lg-left mobile-fs-20 fs-18 font-bold">
            <a href="#"><span aria-hidden="true"><i class="fa fa-arrow-left"></i></span></a> 
        </h3> -->

    <div class="row g-3 mx-max-md-0">
        <section class="col-md-6 px-max-md-0">
            <div class="cards">
                <div class="card-header" id="">
                    <div class="details __h-100">
                        <span class="mb-2 __inline-24"></span>
                        <div class="d-flex justify-content-between">

                            <span class=""><b> </b></span>

                        </div>
                        <?php
                        $PoojaTime = 5;
                        foreach ([2, 4] as $key => $week) {
                            $currentTimestamp = time();
                            $nextTimestamp = strtotime($week, $currentTimestamp);
                            $nextDate = date('d M', $nextTimestamp);
                            $fullDate = date('m-d-Y', $nextTimestamp);
                            $fullDates = date('y-m-d', $nextTimestamp);
                            $nextDates[] = $fullDate;
                            $fullDates = $fullDate;
                        }
                        ?>
                        <div class="flex flex-col">
                            <span class='font-weight-bold'>{{ ucwords($eventData['event_name']??"")}}</span>
                        </div>
                        <hr class="my-2">
                        <div class="flex flex-col">
                            <div class="flex items-center space-x-1 pt-[16px] md:pt-2">
                                @php
                                $date_upcommining = '';
                                $time_upcommining = '';
                                $Venue = '';
                                @endphp

                                @if(!empty($eventData['all_venue_data']) && json_decode($eventData['all_venue_data'],true))
                                @foreach(json_decode($eventData['all_venue_data'],true) as $check)
                                @php
                                $currentDateTime = new DateTime();
                                $eventDateTime = DateTime::createFromFormat('d-m-Y h:i A', date('d-m-Y',strtotime($check['date'])) . ' ' . date('h:i A',strtotime($check['start_time'])) );

                                @endphp
                                @if($eventDateTime && $eventDateTime > $currentDateTime)
                                @php
                                $Venue = ((!empty($check[$langs . '_event_venue_full_address']??'')) ? ucwords($check[$langs . '_event_venue_full_address']??'') : ucwords($check[$langs . '_event_venue']??''));
                                $date_upcommining = date('d M,Y ,l',strtotime($check['date']));
                                $time_upcommining = date('H:i:s',strtotime($check['start_time']));
                                @endphp

                                @break
                                @endif
                                @endforeach
                                @endif
                                <span class='text-warning'>
                                    {{ translate('Next Upcoming Event Venue')}}
                                </span><br>
                                <span class="">
                                    <i class="fa fa-map-marker" aria-hidden="true" style="color: var(--primary-clr);"></i>
                                    {{ $Venue }}
                                </span><br>
                                <span class="mb-2">
                                    <i class="fa fa-calendar" aria-hidden="true" style="color: var(--primary-clr);"></i>
                                </span>{{ ($date_upcommining) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- </div> -->

                <div class="my-3 d-block d-sm-none">
                    @if(!empty($eventData) && json_decode($eventData['all_venue_data'],true))
                    @foreach (json_decode($eventData['all_venue_data'],true) as $product)
                    @if(!empty($product['package_list']) && $product['id'] == $getLeads['venue_id'])
                    @foreach($product['package_list'] as $pacl)
                    <div class="flash_deal_product rtl cursor-pointer mb-2" id="get-view-by-onclicksm{{ $product['id'] }}" data-Pid="{{ $product['id'] }}" data-qtyMin="1" data-Pprice="">
                        <div class="row">
                            <div class="col-3 align-items-center justify-content-center p-3">
                                <div class="p-2">
                                    {{ \App\Models\EventPackage::where('id',$pacl['package_name'])->first()['package_name']??''}}
                                </div>
                            </div>
                            <div class="col-9 flash_deal_product_details pl-3 pr-3 pr-1 d-flex align-items-center">
                                <div class='mt-2'>
                                    <div class="d-flex flex-wrap gap-8 align-items-center row-gap-0 mb-2">
                                        {!! (\App\Models\EventPackage::where('id',$pacl['package_name'])->first()['description']??'') !!}
                                    </div>
                                    <div class="d-flex flex-wrap gap-8 align-items-center row-gap-0 mb-2">
                                        <span class="flash-product-price fw-semibold text-dark">
                                            &#8377; {{ $pacl['price_no'] }}
                                        </span>
                                        @if($pacl['available'] <= 0)
                                            <img src="{{ asset('assets/front-end/img/icons/sold-out.png') }}" alt="" style="position: absolute; margin-top: -8%;z-index: 1; width: 22%; margin-left: -20px;">
                                            @else
                                            <button class="btn btn--primary rounded-pill text-uppercase py-1 fs-12"
                                                type="button" onclick="addEventProduct(this)"
                                                data-lead_id="{{ $lead }}"
                                                data-package_id="{{ $pacl['package_name'] }}"
                                                data-venue_id="{{  $product['id'] }}"> {{ translate('Select Package')}} </button>
                                            @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @endif
                    @endforeach
                    @else
                    <div class="text-center p-4">
                        <img class="mb-3 w-160" src="{{ dynamicAsset(path: 'public/assets/back-end/svg/illustrations/sorry.svg') }}" alt="">
                        <p class="mb-0">{{ translate('no_data_to_show') }}</p>
                    </div>
                    @endif
                </div>
                <div class="card-body" id="productList">
                    <div class="m-2 mb-4 text-warning">
                        {{ translate('Your event booking')}}
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class='table table-borderless table-thead-bordered table-nowrap table-align-middle'>
                            @php
                            $selected_product_array = [];
                            @endphp
                            <tbody>
                                @if (!empty($getLeads))
                                @if($getLeads['venue_id'] != 0)
                                @php
                                array_push($selected_product_array, $getLeads['venue_id']);
                                @endphp
                                <tr>
                                    <td>
                                        <div class='d-flex flex-sm-row gap-3'>
                                            <div class=''>
                                                <a class='position-relative overflow-hidden'>
                                                    <img class='rounded' src="{{ getValidImage(path: 'storage/app/public/event/events/' . $eventData['event_image'], type: 'product') }}" id="Productimage" alt='Product' style="width: 62px; height: auto;">
                                                </a>
                                            </div>
                                            <div class='flex-grow-1'>
                                                <div class="font-size-towal">
                                                    @if(!empty($eventData['all_venue_data']) && json_decode($eventData['all_venue_data'],true))
                                                    @foreach(json_decode($eventData['all_venue_data'],true) as $check)
                                                    @if($check['id'] == $getLeads['venue_id'])
                                                    <a class='font-weight-bold d-block' id='productName'>
                                                        {{((!empty($check[$langs . '_event_venue_full_address']??'')) ? ucwords($check[$langs . '_event_venue_full_address']??'') : ucwords($check[$langs . '_event_venue']??''));}}
                                                    </a>
                                                    <small class="d-block">{{ date('d M,Y h:i A',(strtotime($check['date'].' '.$check['start_time']))) }}</small>
                                                    @break
                                                    @endif
                                                    @endforeach
                                                    @endif
                                                </div>
                                                <div class='fw-semibold mt-2'>
                                                    <div class='text-center'>
                                                        <div id='productPrice' class="font-size-ten float-left" data-amount="{{$getLeads['amount']}}">
                                                            {{ $getLeads['qty']  }} X {{ webCurrencyConverter(amount: $getLeads['amount'] *  $getLeads['qty']) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class='d-sm-block d-none'>
                                        <div class='qty d-flex justify-content-center align-items-center gap-3'>
                                            <span class="qty_minus btn-sm {{ $getLeads['qty'] > 1 ? 'btn--primary' : '' }} py-2 DeleteIcon-show{{ $getLeads['id'] }}"
                                                onclick="QuantityUpdate(`{{ $getLeads['id'] }}`, `{{ $getLeads['amount'] }}`, -1, 'de')"
                                                data-increment='{{ -1 }}'
                                                data-event="{{ $getLeads['qty'] == 1 ? 'delete' : 'minus' }}">
                                                <i class="{{ $getLeads['qty'] > 1 ? 'tio-remove' : 'tio-delete text-danger d-none' }}" id="DeleteIcon{{ $getLeads['id'] }}"></i>
                                            </span>

                                            <input type='text' class="qty_input cartQuantity{{ $getLeads['product_id'] }}"
                                                value="{{ $getLeads['qty'] }}" name="quantity{{ $getLeads['id'] }}"
                                                id="cart_quantity_web{{ $getLeads['id'] }}" data-minimum-order='1'
                                                data-cart-id="{{ $getLeads['id'] }}"
                                                data-increment="{{ '0' }}"
                                                oninput="QuantityUpdate(`{{ $getLeads['id'] }}`,`{{ $getLeads['amount'] }}`,0,'')" readonly>

                                            <span class="qty_plus btn-sm btn--primary py-2"
                                                onclick="QuantityUpdate(`{{ $getLeads['id'] }}`, `{{ $getLeads['amount'] }}`, 1, 'in')"><i class='tio-add'></i> </span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="d-sm-none d-block">
                                        <div class='qty d-flex justify-content-center align-items-center gap-3'>
                                            <span class="qty_minus btn-sm {{ $getLeads['qty'] > 1 ? 'btn--primary' : '' }}  py-2 DeleteIcon-show{{ $getLeads['id'] }}"
                                                onclick="QuantityUpdate(`{{ $getLeads['id'] }}`, `{{ $getLeads['amount'] }}`, -1, 'de')"
                                                data-increment='{{ -1 }}'
                                                data-event="{{ $getLeads['qty'] == 1 ? 'delete' : 'minus' }}">
                                                <i class="{{ $getLeads['qty'] > 1 ? 'tio-remove' : 'tio-delete text-danger d-none' }}" id="DeleteIconsm{{ $getLeads['id'] }}"></i>
                                            </span>

                                            <input type='text' class="qty_input cartQuantity{{ $getLeads['product_id'] }}"
                                                value="{{ $getLeads['qty'] }}" name="quantity{{ $getLeads['id'] }}"
                                                id="cart_quantity_websm{{ $getLeads['id'] }}" data-minimum-order='1'
                                                data-cart-id="{{ $getLeads['id'] }}"
                                                data-increment="{{ '0' }}"
                                                oninput="QuantityUpdate(`{{ $getLeads['id'] }}`,`{{ $getLeads['amount'] }}`,0,'')" readonly>

                                            <span class="qty_plus btn-sm btn--primary py-2" onclick="QuantityUpdate(`{{ $getLeads['id'] }}`, `{{ $getLeads['amount'] }}`, 1, 'in')">
                                                <i class='tio-add'></i>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                                @endif
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>
            @if($getLeads['amount'] > 0)
            <aside class="col-lg-12 pt-2 pt-lg-2 px-max-md-0 order-summery-aside">
                <div class="__cart-total __cart-total_sticky">
                    @if((\App\Models\User::where('id',auth('customer')->id())->first()['wallet_balance']??0) > 0)
                    <div class="row">
                        <div class="col-12 text-end">
                            <input type="checkbox" onclick="wallet_calculation()" class="wallet_checked" value="1" data-amount="{{ (\App\Models\User::where('id',auth('customer')->id())->first()['wallet_balance']??0)  }}" checked>&nbsp;{{ translate('apply_Wallet')}}
                        </div>
                    </div>
                    @endif
                    <div class="cart_total p-0 div-tab-reloads">
                        <div class="pt-2 d-flex justify-content-between">
                            <span class="cart_value">{{ translate('Item')}}</span>
                            <!-- <span class="cart_value">Qty</span> -->
                            <span class="cart_value">{{ translate('Price') }}</span>

                        </div>
                        <hr class="my-2">
                        <div id="productCount">
                            <div class="finalProduct">
                                @php $final_price_val = 0;@endphp
                                @if (!empty($getLeads))
                                @php
                                $final_price_val +=$getLeads['amount'] * $getLeads['qty'];
                                @endphp
                                <input type="hidden" name="final_price" id="productCountFinal{{ $getLeads['id'] }}"
                                    value="{{ $getLeads['total_amount'] }}.00">
                                <div class="d-flex justify-content-between">
                                    <span class="cart_title">Event Amount</span>
                                    <!-- <span class="cart_value" style="margin-right: 11rem;">X {{ $getLeads['qty']  }}</span> -->
                                    <span class="cart_value totalProduct" data-amount="{{($getLeads['amount'] * $getLeads['qty'])}}"> {{ webCurrencyConverter(amount:  $getLeads['amount'] * $getLeads['qty'])  }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="pt-2">
                            <form class="needs-validation" action="javascript:" method="post" novalidate id="coupon-code-events-ajax">
                                <div class="d-flex form-control rounded-pill ps-3 p-1">
                                    <img width="24" src="{{theme_asset(path: 'public/assets/front-end/img/icons/coupon.svg')}}" alt="" onclick="couponList()">
                                    <input type="hidden" name="user_id" value="{{ auth('customer')->check() ? auth('customer')->user()->id : $userId->id }}">
                                    <input type="hidden" name="amount" value="{{$final_price_val}}">
                                    <input class="input_code border-0 px-2 text-dark bg-transparent outline-0 w-100 input_coupon_code" type="text" name="coupon_code" placeholder="{{translate('coupon_code')}}">
                                    <button class="btn btn--primary rounded-pill text-uppercase py-1 fs-12 coupan_apply_text" type="button" id="events-coupon-code" onclick="apply_coupan()">
                                        {{translate('apply')}}
                                    </button>
                                </div>
                                <div class="invalid-feedback">{{translate('please_provide_coupon_code')}}</div>
                            </form>
                            <span id="route-coupon-events" data-url="{{ url('/api/v1/event/eventcoupon') }}"></span>
                            <!-- <div class="d-flex justify-content-between mt-2 d-none"> -->
                            <div class="justify-content-between mt-2 {{ (($getLeads['coupon_id'])?'d-none':'d-none')}} Coupon_apply_discount_css">
                                <span class="cart_title">{{translate('coupon_discount')}}</span>
                                <span class="cart_value Coupon_apply_discount"> - {{ webCurrencyConverter(amount: ($getLeads['coupon_amount']??0)) }} </span>
                            </div>
                        </div>

                        <div class="d-none show_user_wallet_amount">
                            <hr class="my-2">
                            <div class="d-flex justify-content-between">
                                <span class="cart_title text-success font-weight-bold">
                                    <img width="20" src="{{ theme_asset(path: 'public/assets/back-end/img/admin-wallet.png')}}" style="margin-top: -9px;"> User Wallet <small>({{ webCurrencyConverter(amount:(\App\Models\User::where('id',auth('customer')->id())->first()['wallet_balance']??0))  }})</small>
                                </span>
                                @if((\App\Models\User::where('id',auth('customer')->id())->first()['wallet_balance']??0) >= $final_price_val)
                                <span class="cart_value text-success user_wallet_amount">{{ webCurrencyConverter(amount: $final_price_val)  }} </span>
                                @else
                                <span class="cart_value text-success user_wallet_amount">{{ webCurrencyConverter(amount: (\App\Models\User::where('id',auth('customer')->id())->first()['wallet_balance']??0))  }} </span>
                                @endif
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between mt-2">
                                <span class="cart_title text-success font-weight-bold user_wallet_am_remaining_text font-weight-bold" style="color: darkred !important;">Remaining Amount</span>
                                <span class="cart_value text-success user_wallet_amount_remaining" style="color: darkred !important;"> -{{ webCurrencyConverter(amount: ($final_price_val - (\App\Models\User::where('id',auth('customer')->id())->first()['wallet_balance']??0)))  }}</span>
                            </div>

                        </div>
                        <div class="justify-content-between d-flex">
                            <span class="cart_title text-primary font-weight-bold">Final Amount</span>
                            <span class="cart_value" id="mainProductPrice">{{ webCurrencyConverter(amount: ($final_price_val))  }} </span>

                        </div>
                        <!-- </div> -->
                    </div>
                    <hr class="my-2">

                    @if (1 == 1)
                    @foreach ($payment_gateways_list as $payment_gateway)
                    <form method="post" class="digital_payment {{ $payment_gateway->key_name }}_form-submit" id="{{ $payment_gateway->key_name }}_form" action="{{ route('event-payment-request',[$ids,'lead'=>$lead]) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="Details">
                            <input type="hidden" name="user_id" value="{{ auth('customer')->check() ? auth('customer')->user()->id : $userId->id }}">
                            <input type="hidden" name="customer_id" value="{{ auth('customer')->check() ? auth('customer')->user()->id : $userId->id }}">
                            <input type="hidden" name="payment_method" value="{{ $payment_gateway->key_name }}">
                            <input type="hidden" name="payment_platform" value="web">
                            @if ($payment_gateway->mode == 'live' && isset($payment_gateway->live_values['callback_url']))
                            <input type="hidden" name="callback" value="{{ $payment_gateway->live_values['callback_url'] }}">
                            @elseif ($payment_gateway->mode == 'test' && isset($payment_gateway->test_values['callback_url']))
                            <input type="hidden" name="callback" value="{{ $payment_gateway->test_values['callback_url'] }}">
                            @else
                            <input type="hidden" name="callback" value="">
                            @endif
                            <input type="hidden" name="external_redirect_link" value="{{ route('event_pay_success',[$ids,'lead'=>$lead]) }}">
                            <label class="d-flex align-items-center gap-2 mb-0 form-check py-2 cursor-pointer">
                                <input type="radio" id="{{ $payment_gateway->key_name }}" name="online_payment" class="form-check-input custom-radio"
                                    value="{{ $payment_gateway->key_name }}" hidden>
                                <img width="30" src="{{ dynamicStorage(path: 'storage/app/public/payment_modules/gateway_image') }}/{{ $payment_gateway->additional_data && json_decode($payment_gateway->additional_data)->gateway_image != null ? json_decode($payment_gateway->additional_data)->gateway_image : '' }}"
                                    alt="" hidden>
                                <span class="text-capitalize form-check-label" hidden>
                                    @if ($payment_gateway->additional_data && json_decode($payment_gateway->additional_data)->gateway_title != null)
                                    {{ json_decode($payment_gateway->additional_data)->gateway_title }}
                                    @else
                                    {{ str_replace('_', ' ', $payment_gateway->key_name) }}
                                    @endif
                                </span>
                            </label>

                            <input type="hidden" name="booking_date" value="{{ date('Y-m-d H:i:s')}}">
                            <input type="hidden" name="event_id" value="{{ $eventData['id'] }}">
                            <input type="hidden" name="package_id" value="{{ ($getLeads['package_id']??'') }}">
                            <input type="hidden" name="leads_id" value="{{ $lead }}">
                            <input type="hidden" name="coupon_amount" value="" class='Coupon_apply_discount'>
                            <input type="hidden" name="coupon_id" value="" class='Coupon_apply_id'>
                            <input type="hidden" name="venue_id" value="{{ ($getLeads['venue_id']??'') }}">
                            <input type="hidden" name="payment_amount" id="mainProductPriceInput" value="{{$final_price_val}}">
                            <input type="hidden" name="wallet_type" class="user-wallet-adds" value="0">
                            <div class="add-member-list d-none">
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="button" class="btn btn--primary btn-block name_change_continues" onclick="AddMemberList('{{ $payment_gateway->key_name }}_form-submit')">{{ translate('Proceed_To_Checkout')}}</button>
                        </div>
                    </form>
                    @endforeach
                    @endif
                </div>
            </aside>
            @else
            <aside class="col-lg-12 pt-2 pt-lg-2 px-max-md-0 order-summery-aside">
                <div class="__cart-total __cart-total_sticky">
                    <div class="cart_total p-0">
                        <form method="post" class="booking_free" id="bookingForm" action="{{ route('event-booking-free',[$ids]) }}">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ auth('customer')->check() ? auth('customer')->user()->id : $userId->id }}">
                            <input type="hidden" name="customer_id" value="{{ auth('customer')->check() ? auth('customer')->user()->id : $userId->id }}">
                            <input type="hidden" name="booking_date" value="{{ date('Y-m-d H:i:s')}}">
                            <input type="hidden" name="event_id" value="{{ $eventData['id'] }}">
                            <input type="hidden" name="package_id" value="{{ ($getLeads['package_id']??'') }}">
                            <input type="hidden" name="leads_id" value="{{ $lead }}">
                            <input type="hidden" name="venue_id" value="{{ ($getLeads['venue_id']??'') }}">
                            <input type="hidden" name="qty" id='free_booking_qty' value="{{ ($getLeads['qty']??'') }}">
                            <input type="hidden" name="coupon_amount" value="">
                            <input type="hidden" name="coupon_id" value="">
                            <div class="add-member-list d-none">
                            </div>
                            <div class="mt-4">
                                <button type="button" class="btn btn--primary btn-block" onclick="AddMemberList('booking_free')">{{ translate('booking')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </aside>
            @endif
        </section>

        <section class="col-md-6 px-max-md-0 d-none d-sm-block">
            @if(!empty($eventData) && json_decode($eventData['all_venue_data'],true))
            @foreach (json_decode($eventData['all_venue_data'],true) as $product)
            @if(!empty($product['package_list']) && $product['id'] == $getLeads['venue_id'])
            @foreach($product['package_list'] as $pacl)
            <div class="flash_deal_product rtl cursor-pointer mb-2" id="get-view-by-onclick{{ $product['id'] }}" data-Pid="{{ $product['id'] }}" data-qtyMin="1" data-Pprice="">
                <div class="row">
                    <div class="col-3 align-items-center justify-content-center p-3">
                        <div class="p-2">
                            {{ \App\Models\EventPackage::where('id',$pacl['package_name'])->first()['package_name']??''}}
                        </div>
                    </div>
                    <div class="col-9 flash_deal_product_details pl-3 pr-3 pr-1 d-flex align-items-center">
                        <div class='mt-2'>
                            <div class="d-flex flex-wrap gap-8 align-items-center row-gap-0 mb-2">
                                {!! (\App\Models\EventPackage::where('id',$pacl['package_name'])->first()['description']??'') !!}
                            </div>
                            <div class="d-flex flex-wrap gap-8 align-items-center row-gap-0 mb-2">
                                <span class="flash-product-price fw-semibold text-dark">
                                    &#8377; {{ $pacl['price_no'] }}
                                </span>
                                @if($pacl['available'] <= 0)
                                    <img src="{{ asset('assets/front-end/img/icons/sold-out.png') }}" alt="" style="position: absolute; margin-top: -8%;z-index: 1; width: 22%; margin-left: -20px;">
                                    @else
                                    <button class="btn btn--primary rounded-pill text-uppercase py-1 fs-12"
                                        type="button" onclick="addEventProduct(this)"
                                        data-lead_id="{{ $lead }}"
                                        data-package_id="{{ $pacl['package_name'] }}"
                                        data-venue_id="{{  $product['id'] }}"> {{ translate('Select Package')}} </button>
                                    @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            @endif
            @endforeach
            @else
            <div class="text-center p-4">
                <img class="mb-3 w-160" src="{{ dynamicAsset(path: 'public/assets/back-end/svg/illustrations/sorry.svg') }}" alt="">
                <p class="mb-0">{{ translate('no_data_to_show') }}</p>
            </div>
            @endif
        </section>
    </div>
</div>
<div class="modal fade" id="coupon-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Coupons</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body row g-3" id="modal-body">
            </div>
        </div>
    </div>
</div>
@php
if($langs == "hi"){
$textmessages = htmlspecialchars("कृपया बुकिंग से पहले व्यक्ति का विवरण पूरा करें।");
}else{
$textmessages = htmlspecialchars("Please complete the persons details before proceeding with booking");
}
$textmessages = rawurlencode($textmessages);
$html12 = file_get_contents('https://translate.google.com/translate_tts?ie=UTF-8&client=gtx&q=' . $textmessages . '&tl='.$langs.'-IN');
@endphp
<audio class="audio-play-messages d-none" src="data:audio/mpeg;base64,{{ base64_encode($html12)}}" controls></audio>

<!-- Modal -->
<div class="modal fade person-information-details" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Person Information</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="personTabsmodals" role="tablist"></ul>
                <div class="tab-content p-3 border border-top-0" id="personTabContent-modals"></div>
            </div>

        </div>
    </div>
</div>

@endsection
@push('script')
<script src="{{ theme_asset(path: 'public/assets/front-end/js/panchang.js') }}"></script>
<script>
    var t = new Date("{{ date('Y-m-d', strtotime($fullDates)) }}");
</script>
<script type="text/javascript">
    // Total Payment
    function addEventProduct(that) {
        var lead_id = $(that).data('lead_id');
        var venue_id = $(that).data('venue_id');
        var package_id = $(that).data('package_id');
        $.ajax({
            url: "{{ route('event-booking-leads-update') }}",
            method: 'POST',
            data: {
                lead_id: lead_id,
                venue_id: venue_id,
                package_id: package_id,
                _token: '{{ csrf_token() }}'
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                location.reload();
            },
            error: function(xhr, status, error) {}
        });

    }
</script>
<script src="{{ theme_asset(path: 'public/assets/front-end/js/owl.carousel.min.js') }}"></script>
<script src="{{ theme_asset(path: 'public/assets/front-end/js/payment.js') }}"></script>
<script>
    function couponList() {
        let expireDate = "";
        let formattedDate = "";
        let body = "";
        $.ajax({
            type: "post",
            data: {
                _token: $('meta[name="_token"]').attr('content'),
                type: "event",
            },
            url: "{{ route('coupon.coupon-list-type') }}",
            success: function(response) {
                $('#modal-body').html('');
                let body = '';
                if (response.status == 200) {
                    if (response.coupons.length > 0) {
                        $.each(response.coupons, function(key, value) {
                            expireDate = new Date(value.expire_date);
                            formattedDate = expireDate.toLocaleString('en-GB', {
                                day: 'numeric',
                                month: 'short',
                                year: 'numeric'
                            }).replace(" ", ", ");

                            body += `<div class="col-lg-6">
                                        <div class="ticket-box">
                                        <div class="ticket-start">
                                            <img width="30" src="{{ asset('public/assets/front-end/img/icons/dollar.png') }}" alt="">
                                            <h2 class="ticket-amount">${((value.discount_type == 'percentage')?'':'₹')}${value.discount} ${((value.discount_type == 'percentage')?'%':'')}</h2>
                                            <p>On All Events</p>
                                        </div>
                                        <div class="ticket-border"></div>
                                        <div class="ticket-end">
                                            <button class="ticket-welcome-btn couponid click-to-copy-coupon couponid-${value.code}" data-value="${value.code}" onclick="copyToClipboard(this)">${value.code}</button>
                                            <button
                                                class="ticket-welcome-btn couponid-hide d-none couponhideid-${value.code}">Copied</button>
                                            <h6>Valid till ${formattedDate}</h6>
                                            <p class="m-0">Available from minimum purchase ₹${value.min_purchase}</p>
                                        </div>
                                        </div>
                                    </div>`;
                        });
                        $('#modal-body').append(body);
                        $('#coupon-modal').on('hidden.bs.modal', function() {
                            if ($('.modal.show').length) {
                                $('body').addClass('modal-open');
                            }
                        });
                        $('#coupon-modal').modal('show');
                    } else {
                        body = 'Coupons not available';
                        $('#modal-body').append(body);
                        $('#modal-body').css({
                            'display': 'flex',
                            'justify-content': 'center',
                            'padding': '50px 0px',
                            'color': 'red'
                        });
                        $('#coupon-modal').modal('show');
                    }
                } else {
                    toaster.error('Coupon not available');
                }
            }
        });
    }

    function copyToClipboard(button) {
        const value = button.getAttribute("data-value");
        if ($('.input_code').val() == '') {
            $('.input_code').val(value);
            $('#coupon-modal').modal('hide');
        } else {
            navigator.clipboard.writeText(value)
                .then(() => {
                    toastr.success("Copied to clipboard");
                })
                .catch(err => {
                    toast.error("Failed to copy");
                });
        }
    }
</script>
<script>
    toastr.options = {
        "closeButton": true,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "3000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    function QuantityUpdate(lead_id, amount, quantity, type) {
        var inputBox = $('#cart_quantity_web' + lead_id);
        var inputBoxsm = $('#cart_quantity_websm' + lead_id);
        if (quantity == -1) {
            if (inputBox.val() == 1) {
                //  deleteQuantity(lead_id, amount, quantity, 'remove');
            } else {
                if (inputBox.val() == 2) {
                    $('#DeleteIcon' + lead_id).addClass('tio-delete text-danger d-none');
                    $('#DeleteIcon' + lead_id).removeClass('tio-remove');
                    $('#DeleteIconsm' + lead_id).addClass('tio-delete text-danger d-none');
                    $('#DeleteIconsm' + lead_id).removeClass('tio-remove');
                    $('.DeleteIcon-show' + lead_id).removeClass('btn--primary');
                    $('#get-view-by-onclick' + lead_id).append();
                    $('#get-view-by-onclicksm' + lead_id).append();
                    // toastr.warning('Quantity not Applicable.');
                    var newQuantity = parseInt(inputBox.val()) + quantity;
                    inputBox.val(newQuantity);
                    inputBoxsm.val(newQuantity);
                    ProductQuantity(lead_id, amount, newQuantity, type);
                } else {
                    var newQuantity = parseInt(inputBox.val()) + quantity;
                    inputBox.val(newQuantity);
                    inputBoxsm.val(newQuantity);
                    ProductQuantity(lead_id, amount, newQuantity, type);
                }
            }
        } else {
            $('#DeleteIcon' + lead_id).removeClass('tio-delete text-danger d-none');
            $('#DeleteIcon' + lead_id).addClass('tio-remove');
            $('#DeleteIconsm' + lead_id).removeClass('tio-delete text-danger d-none');
            $('#DeleteIconsm' + lead_id).addClass('tio-remove');
            $('.DeleteIcon-show' + lead_id).addClass('btn--primary');
            var newQuantity = parseInt(inputBox.val()) + quantity;
            inputBox.val(newQuantity);
            inputBoxsm.val(newQuantity);
            ProductQuantity(lead_id, amount, newQuantity, type);
        }
        if ($(".Coupon_apply_id").val()) {
            $(".coupan_apply_text").text("{{translate('apply')}}");
            $(".Coupon_apply_discount").val(0);
            $(".Coupon_apply_id").val('');
            // $("#mainProductPriceInput").val("{{$final_price_val}}");
            // $('#mainProductPrice').text(`{{webCurrencyConverter(amount:  $final_price_val) }}`);
            $(".Coupon_apply_discount").text('');
            $(".Coupon_apply_discount_css").addClass('d-none');
            $(".Coupon_apply_discount_css").removeClass('d-flex');
            toastr.error("Remove Coupon", {
                CloseButton: true,
                ProgressBar: true
            });
        }

        wallet_calculation();
    }

    function ProductQuantity(lead_id, amount, quantity, type) {
        // $(`.div-tab-reloads`).load(location.href + ` .div-tab-reloads > *`);
        $.ajax({
            url: "{{ route('event-booking-leads-qty-update') }}",
            method: 'POST',
            data: {
                lead_id,
                amount,
                quantity,
                type,
                _token: '{{ csrf_token() }}',
                coupon_amount: $('.Coupon_apply_discount').val(),
                coupon_id: $('.Coupon_apply_id').val(),
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                // toastr.success(response.message);
                // location.reload();
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    }
    // Delete Quantity
    function deleteQuantity(lead_id, amount, quantity, type) {
        $.ajax({
            url: "{{ route('event-booking-leads-qty-update') }}",
            method: 'POST',
            data: {
                lead_id,
                amount,
                quantity,
                type,
                _token: '{{ csrf_token() }}'
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                toastr.success('Remove Venue Successfully');
                location.reload();

            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    }


    // $('#events-coupon-code').on('click', function() {
    function apply_coupan() {

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            }
        });
        $.ajax({
            type: "POST",
            url: $('#route-coupon-events').data('url'),
            data: {
                'amount': $('.totalProduct').data('amount'),
                'user_id': "{{ auth('customer')->user()->id }}",
                'coupon_code': $('.input_coupon_code').val()
            },
            success: function(data) {
                let messages = data.message;
                if (data.status == 1) {
                    $(".coupan_apply_text").text("{{translate('applyed')}}");
                    $(".Coupon_apply_discount").val(data.data['coupon_amount']);
                    $(".Coupon_apply_id").val(data.data['coupon_id']);
                    $("#mainProductPriceInput").val(data.data['final_amount']);
                    $('#mainProductPrice').text(`₹${(data.data['final_amount']).toFixed(2)}`);
                    $(".Coupon_apply_discount").text(`- ₹${data.data['coupon_amount']}`);
                    $(".Coupon_apply_discount_css").addClass('d-flex');
                    $(".Coupon_apply_discount_css").removeClass('d-none');
                    toastr.success(messages, {
                        CloseButton: true,
                        ProgressBar: true
                    });
                } else {
                    $(".coupan_apply_text").text("{{translate('apply')}}");
                    $(".Coupon_apply_discount").val(0);
                    $(".Coupon_apply_id").val('');
                    $("#mainProductPriceInput").val("{{$final_price_val}}");
                    $('#mainProductPrice').text(`{{webCurrencyConverter(amount:  $final_price_val) }}`);
                    $(".Coupon_apply_discount").text('');
                    $(".Coupon_apply_discount_css").addClass('d-none');
                    $(".Coupon_apply_discount_css").removeClass('d-flex');
                    toastr.error(messages, {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
                wallet_calculation();
                var lead_id = "{{ $getLeads['id'] }}";
                $.ajax({
                    url: "{{ route('event-booking-leads-qty-update') }}",
                    method: 'POST',
                    data: {
                        lead_id: "{{ $getLeads['id'] }}",
                        amount: "{{ $getLeads['amount'] }}",
                        quantity: parseInt($('#cart_quantity_web' + lead_id).val()),
                        type: '',
                        _token: '{{ csrf_token() }}',
                        coupon_amount: $('.Coupon_apply_discount').val(),
                        coupon_id: $('.Coupon_apply_id').val(),
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {},
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            }
        });
    }
    // })
</script>

<script>
    var forms = document.querySelectorAll('.digital_payment');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            var amountInput = form.querySelector('input[name="payment_amount"]');
            var amount = parseFloat(amountInput.value);
            if (amount <= 0 || isNaN(amount) || amount === "") {
                event.preventDefault();
                toastr.error('{{ translate("The payment amount must be greater than 0")}}.');
            } else {
                return false;
            }
        });
    });

    document.getElementById('bookingForm').addEventListener('submit', function(event) {
        var qty = document.getElementById('free_booking_qty').value;
        if (qty <= 0 || isNaN(qty) || qty === "") {
            event.preventDefault();
            toastr.error('{{ translate("Select Package")}}');
        } else {
            event.preventDefault();
            Swal.fire({
                title: '{{ translate("Are you sure?") }}',
                text: '{{ translate("Do you want to proceed with the booking?") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ translate("Yes, book now!") }}',
                cancelButtonText: '{{ translate("Cancel") }}'
            }).then((result) => {
                if (result.value == true) {
                    document.getElementById('bookingForm').submit();
                }
            });
        }
    });

    function wallet_calculation() {
        var amount = $("#productPrice").data('amount');
        var qty = $(".qty_input").val();
        $('#productPrice').text(`${qty} X ₹${(parseFloat(amount) * parseInt(qty)).toFixed(2)}`);
        $(".totalProduct").text(`₹${(parseFloat(amount) * parseInt(qty)).toFixed(2)}`);
        $('.totalProduct').data('amount', (parseFloat(amount) * parseInt(qty)))
        var coupon = $(".Coupon_apply_discount").val();
        if (coupon > 0) {
            // $('#mainProductPrice').text(`₹${((parseFloat(amount) * parseInt(qty)) - parseFloat(coupon)).toFixed(2)}`);
            $('#mainProductPriceInput').val(`${((parseFloat(amount) * parseInt(qty)) - parseFloat(coupon))}`);
        } else {
            // $('#mainProductPrice').text(`₹${(parseFloat(amount) * parseInt(qty)).toFixed(2)}`);
            $('#mainProductPriceInput').val(`${(parseFloat(amount) * parseInt(qty))}`);
        }

        var isChecked = $('.wallet_checked').prop('checked');
        var totalPrice = $("#mainProductPriceInput").val();
        let walletAmount = $('.wallet_checked').data('amount');
        if (isChecked) {
            $(".show_user_wallet_amount").removeClass('d-none');
            $(".user-wallet-adds").val(1);
            if (walletAmount >= totalPrice) {
                $(".name_change_continues").text(`{{ translate('book_now')}}`);
                $(".user_wallet_amount_remaining").text('');
                $(".user_wallet_amount").text(`${(totalPrice - 0).toLocaleString("en-US", { style: "currency", currency: "{{getCurrencyCode()}}"})}`);
                $(".user_wallet_am_remaining_text").text('');
                $('#mainProductPrice').text(`${(0.00).toLocaleString("en-US", { style: "currency", currency: "{{getCurrencyCode()}}"})}`);
            } else {
                $(".user_wallet_amount").text(`${(walletAmount - 0).toLocaleString("en-US", { style: "currency", currency: "{{getCurrencyCode()}}"})}`);
                $(".name_change_continues").text(`{{ translate('Proceed_To_Checkout')}}`);
                let remainingAmount = totalPrice - walletAmount;
                let formattedAmount = remainingAmount.toLocaleString("en-US", {
                    style: "currency",
                    currency: "{{getCurrencyCode()}}"
                });
                $(".user_wallet_amount_remaining").text(`-${formattedAmount}`);
                $(".user_wallet_am_remaining_text").text("{{ translate('remaining_amount')}}");
                $('#mainProductPrice').text(`${formattedAmount.toLocaleString("en-US", { style: "currency", currency: "{{getCurrencyCode()}}"})}`);
            }
        } else {
            $(".user-wallet-adds").val(0);
            $(".show_user_wallet_amount").addClass('d-none');
            $(".name_change_continues").text(`{{ translate('Proceed_To_Checkout')}}`);
            $(".user_wallet_amount_remaining").text('');
            $(".user_wallet_am_remaining_text").text('');
            let formattedAmount1 = (totalPrice - 0).toLocaleString("en-US", {
                style: "currency",
                currency: "{{getCurrencyCode()}}"
            });
            console.log(totalPrice);
            console.log(formattedAmount1);
            $('#mainProductPrice').text(`${(formattedAmount1).toLocaleString("en-US", { style: "currency", currency: "{{getCurrencyCode()}}"})}`);
        }
    }
</script>
<script>
    wallet_calculation();

    function AddMemberList(clas) {
        $('.audio-play-messages')[0].play();
        let count = parseInt($('.qty_input').val());
        let tabs = '';
        let contents = '';

        for (let i = 1; i <= count; i++) {
            let activeClass = i === 1 ? 'active' : '';
            tabs += `
            <li class="nav-item">
                <a class="nav-link ${activeClass}" data-toggle="tab" href="#person${i}" style="color:black !important">Person ${i}</a>
            </li>
           `;

            contents += `
            <div class="tab-pane fade ${activeClass ? 'show active' : ''}" id="person${i}">
            <div class="form-group">
            <label>Full Name (Person ${i})</label>
            <input type="hidden" name="member[${i - 1}]['id']" class="form-control" value="${i}">
            <input type="text" name="member[${i - 1}]['name']" class="form-control" required minlength="3" onkeyup="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
            </div>
            <div class="form-group">
            <label>phone</label>
            <input type="text" name="member[${i - 1}]['phone']" class="form-control" required minlength="10" maxlength="11" onkeyup="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>
            <div class="form-group">
            <label>Aadhar</label>
            <input type="text" name="member[${i - 1}]['aadhar']" class="form-control" {{ (($eventData['required_aadhar_status'] == 1)?'required minlength="12" maxlength="12" ':'') }}  onkeyup="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>
            <?php if ($eventData['required_aadhar_status'] == 1) { ?>
            <div class="form-group">
            <label>Aadhar Image</label>
            <input type="file" name="member[${i - 1}]['aadhar_image']" class="form-control" required>
            </div>
            <?php } ?>
            <div class="form-group">
            ${i > 1 ? '<button class="btn btn--primary prev-tab">Back</button>' : ''}
            ${i < count ? `<button class="btn btn--primary next-tab ${i > 1?'float-end':''}">Next</button>` : `<button class="btn btn-success float-end last-submit" data-id="${clas}">Submit</button>`}
            </div>
        </div>
            `;
        }

        $('#personTabsmodals').html(tabs);
        $('#personTabContent-modals').html(contents);
        $('.person-information-details').modal('show');
    }
</script>
<script>
    $(document).on('click', '.last-submit', function() {
        let $currentTab = $(this).closest('.tab-pane');
        let isValid = true;
        $currentTab.find('input, select, textarea').each(function() {
            const $input = $(this);
            const val = $input.val();
            const minLength = $input.attr('minlength');
            const isTooShort = minLength && val.length < parseInt(minLength);

            if (!this.checkValidity() || isTooShort) {
                isValid = false;
                $input.addClass('is-invalid');
            } else {
                $input.removeClass('is-invalid');
            }
        });

        if (isValid) {
            $('.add-member-list').empty();
            let formData = new FormData();
            $('.tab-pane').each(function(index) {
                let i = (index);
                let $tab = $(this);
                let name = $tab.find(`input[name="member[${i}]['name']"]`).val();
                let phone = $tab.find(`input[name="member[${i}]['phone']"]`).val();
                let aadhar = $tab.find(`input[name="member[${i}]['aadhar']"]`).val();
                let $aadharImageInput = $tab.find(`input[name="member[${i}]['aadhar_image']"]`);

                $('.add-member-list').append(`
                    <input type="hidden" name="member[${i}][name]" value="${name}">
                    <input type="hidden" name="member[${i}][phone]" value="${phone}">
                    <input type="hidden" name="member[${i}][aadhar]" value="${aadhar}">
                `);

                if ($aadharImageInput.length) {
                    $aadharImageInput.attr('name', `member[${i}][aadhar_image]`);
                    $('.add-member-list').append($aadharImageInput);
                }
            });
            let className = $(this).data('id');
            $(`.${className}`).submit();
        }
    });

    $(document).on('click', '.next-tab', function() {
        let $currentTab = $(this).closest('.tab-pane');
        let isValid = true;
        $currentTab.find('input, select, textarea').each(function() {
            const $input = $(this);
            const val = $input.val();
            const minLength = $input.attr('minlength');
            const isTooShort = minLength && val.length < parseInt(minLength);

            if (!this.checkValidity() || isTooShort) {
                isValid = false;
                $input.addClass('is-invalid');
            } else {
                $input.removeClass('is-invalid');
            }
        });

        if (isValid) {
            let $active = $('.nav-tabs .nav-link.active');
            let $next = $active.parent().next('li').find('.nav-link');
            $next.tab('show');
        }
    });

    $(document).on('click', '.prev-tab', function() {
        let $active = $('.nav-tabs .nav-link.active');
        let $prev = $active.parent().prev('li').find('.nav-link');
        $prev.tab('show');
    });
</script>
@endpush