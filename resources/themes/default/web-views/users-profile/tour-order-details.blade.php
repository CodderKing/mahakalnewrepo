@extends('layouts.front-end.app')

@section('title', translate('my_Order_List'))
@push('css_or_js')
<link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/social-icon.css') }}">
<style>
    @media (max-width: 767px) {
        .chat-container {
            height: 400px;
        }

        .chat-header,
        .chat-box,
        .chat-input {
            padding: 8px;
        }

        .user-message,
        .admin-message {
            font-size: 14px;
            padding: 8px;
        }

        .order_table_td {
            display: block;
            width: 100%;
        }

        .order_table_tr {
            display: block;
            margin-bottom: 20px;
        }

        .payment .table {
            min-width: 100%;
        }

        .mobile-full {
            width: 100% !important;
        }

        .customer-profile-orders .card-body {
            padding: 15px;
        }

        .payment .min-width-600px {
            min-width: auto !important;
        }
    }

    @media (max-width: 991px) {
        .customer-profile-wishlist {
            margin-top: 20px;
        }

        .d-lg-flex {
            display: block !important;
        }
    }

    .cancellation-policy-table td,
    .cancellation-policy-table th {
        font-size: 16px;
    }

    @media (max-width: 991px) {

        .cancellation-policy-table td,
        .cancellation-policy-table th {
            font-size: 14px;
        }
    }

    @media (max-width: 767px) {

        .cancellation-policy-table td,
        .cancellation-policy-table th {
            font-size: 13px;
        }
    }

    @media (max-width: 575px) {

        .cancellation-policy-table td,
        .cancellation-policy-table th {
            font-size: 12px;
        }
    }
</style>


<style>
    .chat-container {
        margin: 0 auto;
        border: 1px solid #ccc;
        border-radius: 10px;
        background-color: #fff;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 500px;
    }

    .chat-header {
        display: flex;
        align-items: center;
        padding: 10px;
        background-color: #fff;
        border-bottom: 1px solid #ccc;
    }


    .chat-box {
        padding: 10px;
        flex-grow: 1;
        overflow-y: auto;
        background-color: #f1f1f1;
    }

    .chat-input {
        display: flex;
        border-top: 1px solid #ccc;
        padding: 10px;
    }

    .chat-input input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 20px;
        outline: none;
    }

    .chat-input button {
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 50%;
        padding: 10px;
        margin-left: 10px;
        cursor: pointer;
    }

    .chat-input button i {
        font-size: 16px;
    }

    .chat-message {
        margin-bottom: 10px;
        padding: 10px;
        /* border-radius: 10px;
        max-width: 60%; */
        word-wrap: break-word;
    }

    .user-message {
        background-color: #ff9200;
        color: white;
        align-self: flex-end;
        text-align: right;
        border-radius: 8px;
    }

    .admin-message {
        background-color: #f1f1f1;
        color: black;
        align-self: flex-start;
        text-align: left;
    }
</style>
@endpush
@section('content')

<div class="container py-2 py-md-4 p-0 p-md-2 user-profile-container px-5px">
    <div class="row">
        @include('web-views.partials._profile-aside')
        <section class="col-lg-9 __customer-profile customer-profile-wishlist px-0">
            <!-- <div class="card __card d-lg-flex web-direction customer-profile-orders"> -->
            <div class="card __card customer-profile-orders shadow-sm rounded">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <div class="d-flex align-items-center gap-2 text-capitalize">
                                <h4 class="text-capitalize mb-0 mobile-fs-14 fs-18 font-bold">{{ translate('order') }} #{{ $tourOrders['order_id'] ?? '' }} </h4>
                                <?php
                                if (($tourOrders['status'] == 0 || $tourOrders['status'] == 1) && $tourOrders['cab_assign'] == 0 && $tourOrders['pickup_status'] == 0) {
                                    $showClass = 'primary';
                                    $showName = 'Pending';
                                } elseif (($tourOrders['status'] == 0 || $tourOrders['status'] == 1) && $tourOrders['cab_assign'] != 0 && $tourOrders['pickup_status'] == 0) {
                                    $showClass = 'primary';
                                    $showName = 'Processing';
                                } elseif (($tourOrders['status'] == 0 || $tourOrders['status'] == 1) && $tourOrders['cab_assign'] != 0 && $tourOrders['pickup_status'] == 1 && $tourOrders['drop_status'] == 0) {
                                    $showClass = 'success';
                                    $showName = 'Pickup';
                                } elseif (($tourOrders['status'] == 0 || $tourOrders['status'] == 1) && $tourOrders['cab_assign'] != 0 && $tourOrders['drop_status'] == 1) {
                                    $showClass = 'success';
                                    $showName = 'Completed';
                                } else {
                                    $showClass = 'danger';
                                    $showName = 'Refund';
                                }
                                ?>
                                <span
                                    class="status-badge rounded-pill __badge badge-soft-badge-soft-{{ $showClass }} fs-12 font-semibold text-capitalize">
                                    {{ $showName }}
                                </span>
                            </div>
                            <div class="date fs-12 font-semibold text-secondary-50 text-body mb-3 mt-2">
                                {{ date('d M, Y h:i A', strtotime($tourOrders['created_at'])) }}
                            </div>
                        </div>
                        <button class="profile-aside-btn btn btn--primary px-2 rounded px-2 py-1 d-lg-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15"
                                fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M7 9.81219C7 9.41419 6.842 9.03269 6.5605 8.75169C6.2795 8.47019 5.898 8.31219 5.5 8.31219C4.507 8.31219 2.993 8.31219 2 8.31219C1.602 8.31219 1.2205 8.47019 0.939499 8.75169C0.657999 9.03269 0.5 9.41419 0.5 9.81219V13.3122C0.5 13.7102 0.657999 14.0917 0.939499 14.3727C1.2205 14.6542 1.602 14.8122 2 14.8122H5.5C5.898 14.8122 6.2795 14.6542 6.5605 14.3727C6.842 14.0917 7 13.7102 7 13.3122V9.81219ZM14.5 9.81219C14.5 9.41419 14.342 9.03269 14.0605 8.75169C13.7795 8.47019 13.398 8.31219 13 8.31219C12.007 8.31219 10.493 8.31219 9.5 8.31219C9.102 8.31219 8.7205 8.47019 8.4395 8.75169C8.158 9.03269 8 9.41419 8 9.81219V13.3122C8 13.7102 8.158 14.0917 8.4395 14.3727C8.7205 14.6542 9.102 14.8122 9.5 14.8122H13C13.398 14.8122 13.7795 14.6542 14.0605 14.3727C14.342 14.0917 14.5 13.7102 14.5 13.3122V9.81219ZM12.3105 7.20869L14.3965 5.12269C14.982 4.53719 14.982 3.58719 14.3965 3.00169L12.3105 0.915687C11.725 0.330188 10.775 0.330188 10.1895 0.915687L8.1035 3.00169C7.518 3.58719 7.518 4.53719 8.1035 5.12269L10.1895 7.20869C10.775 7.79419 11.725 7.79419 12.3105 7.20869ZM7 2.31219C7 1.91419 6.842 1.53269 6.5605 1.25169C6.2795 0.970186 5.898 0.812187 5.5 0.812187C4.507 0.812187 2.993 0.812187 2 0.812187C1.602 0.812187 1.2205 0.970186 0.939499 1.25169C0.657999 1.53269 0.5 1.91419 0.5 2.31219V5.81219C0.5 6.21019 0.657999 6.59169 0.939499 6.87269C1.2205 7.15419 1.602 7.31219 2 7.31219H5.5C5.898 7.31219 6.2795 7.15419 6.5605 6.87269C6.842 6.59169 7 6.21019 7 5.81219V2.31219Z" fill="white" />
                            </svg>
                        </button>
                    </div>
                    <ul class="nav nav-tabs nav--tabs d-flex justify-content-start mt-3 border-top border-bottom py-2"
                        role="tablist">
                        <li class="nav-item">
                            <a class="nav-link __inline-27 active" href="#all_order" data-toggle="tab" role="tab">
                                {{ translate('order_summary') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link __inline-27" href="#Itinerary" data-toggle="tab" role="tab">
                                {{ translate('Itinerary') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link __inline-27" href="#reviews" data-toggle="tab" role="tab">
                                {{ translate('reviews') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link __inline-27  {{ !empty($tourOrders['refund_query_id']) ? 'd-none' : 'd-none' }}"
                                href="#chat-roles" data-toggle="tab" role="tab">
                                {{ translate('Refund_inquiry') }}
                            </a>
                        </li>

                    </ul>
                    <div class="tab-content px-lg-3">
                        <div class="tab-pane fade show active text-justify" id="all_order" role="tabpanel">
                            <div class="bg-white border-lg rounded mobile-full">
                                <div class="p-lg-3 p-0">
                                    <div class="card border-sm">
                                        <div class="p-lg-3">
                                            <div class="border-lg rounded payment mb-lg-3 table-responsive">
                                                <table class="table table-borderless mb-0">
                                                    <thead>
                                                        <tr class="order_table_tr">
                                                            <td class="order_table_td">
                                                                <div class="">
                                                                    <div class="_1 py-2 d-flex justify-content-between align-items-center">
                                                                        <h6 class="fs-13 font-bold text-capitalize">
                                                                            {{ translate('payment_info') }}
                                                                        </h6>
                                                                    </div>
                                                                    <div class="fs-12">
                                                                        <span
                                                                            class="text-muted text-capitalize">{{ translate('payment_status') }}</span>:
                                                                        <?php if ($tourOrders['amount_status'] == 1) { ?>
                                                                            <span
                                                                                class="text-success text-capitalize">{{ translate('paid') }}{{ $tourOrders['part_payment'] == 'part' ? ' / partially' : '' }}</span>
                                                                        <?php } else { ?>
                                                                            <span
                                                                                class="text-success text-capitalize">{{ translate('unpaid') }}
                                                                                {{ $tourOrders['part_payment'] == 'part' ? ' / partially' : '' }}</span>
                                                                        <?php }
                                                                        if ($tourOrders['part_payment'] == 'part') { ?>
                                                                            <a class="btn btn-sm btn--primary"
                                                                                onclick="pay_now_order()">pay now</a>
                                                                        <?php } ?>
                                                                    </div>
                                                                    <div class="mt-2 fs-12">
                                                                        <span
                                                                            class="text-muted text-capitalize">{{ translate('payment_method') }}</span>
                                                                        :<span class="text-primary text-capitalize">
                                                                            @if ($tourOrders['transaction_id'] == 'wallet')
                                                                            {{ translate('Wallet') }}
                                                                            @else
                                                                            {{ translate('online') }}
                                                                            @endif
                                                                        </span>
                                                                    </div>
                                                                    @if ($tourOrders['refund_status'] != 0)
                                                                    <div class="mt-2 fs-12">
                                                                        <span
                                                                            class="text-muted text-capitalize">{{ translate('Refound_status') }}</span>
                                                                        :<span
                                                                            class="text-primary text-capitalize">{{ $tourOrders['refund_status'] == 1 ? 'Refunded' : ($tourOrders['refund_status'] == 3 ? 'refund Cancel' : 'refund proccess') }}</span>
                                                                    </div>
                                                                    @endif
                                                                    <div class="mt-2 fs-12">

                                                                        <small class="fs-13 font-bold text-capitalize">{{ translate('Tour_name') }}</small>
                                                                        :
                                                                        <span>{{ $tourOrders['Tour']['tour_name'] ?? '' }}</span>
                                                                        <br>
                                                                        <small
                                                                            class="fs-13 font-bold text-capitalize">{{ translate('Tour_date') }}</small>
                                                                        : <span>{{ date('d M, Y', strtotime($tourOrders['pickup_date'])) }}
                                                                            {{ $tourOrders['pickup_time'] }}</span><br>
                                                                        <small
                                                                            class="fs-13 font-bold text-capitalize">{{ translate('pickup_location') }}</small>
                                                                        :
                                                                        <span>{{ $tourOrders['pickup_address'] ?? '' }}</span><br>
                                                                        <small
                                                                            class="fs-13 font-bold text-capitalize">{{ translate('booking_time') }}</small>
                                                                        :
                                                                        <span>{{ date('d M, Y h:i A', strtotime($tourOrders['created_at'])) }}</span><br>
                                                                    </div>
                                                                </div>
                                                                <!--  -->
                                                                @if ($tourOrders['cab_assign'] != 0)
                                                                <div
                                                                    class="mt-2 py-2 d-flex justify-content-between align-items-center">
                                                                    <small
                                                                        class="fs-13 font-bold text-capitalize">{{ translate('company_info') }}</small>
                                                                </div>
                                                                <div class="fs-12">
                                                                    <span
                                                                        class="text-muted text-capitalize">{{ translate('traveller_name') }}</span>:
                                                                    <span
                                                                        class="font-weight-bold text-capitalize">{{ $tourOrders['company']['company_name'] ?? '' }}</span>
                                                                </div>
                                                                @endif
                                                            </td>
                                                            <td class="order_table_td">
                                                                <div class="">
                                                                    <div class="py-2">
                                                                        <h6 class="fs-13 font-bold text-capitalize">
                                                                            {{ translate('User_info') }}:
                                                                        </h6>
                                                                    </div>
                                                                    <div class="">
                                                                        <span class="text-capitalize fs-12">
                                                                            <span class="text-capitalize">
                                                                                <span
                                                                                    class="min-w-60px">{{ translate('name') }}</span>
                                                                                :
                                                                                &nbsp;{{ $tourOrders['userData']['name'] ?? '' }}
                                                                            </span>
                                                                            <br>
                                                                            <span class="text-capitalize">
                                                                                <span
                                                                                    class="min-w-60px">{{ translate('phone') }}</span>
                                                                                :
                                                                                &nbsp;{{ $tourOrders['userData']['phone'] ?? '' }},
                                                                            </span>
                                                                            <br>
                                                                            <span style="text-transform: lowercase;">
                                                                                <span class="min-w-60px">{{ translate('Email') }}</span>:
                                                                                &nbsp;<span>{{ $tourOrders['userData']['email'] ?? '' }}</span>,
                                                                            </span>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </div>
                                            <div class="payment mb-3 table-responsive d-none d-lg-block">
                                                <?php $ex_distance = 0;
                                                if (!empty($tourOrders['booking_package'])) {
                                                    $decodedPackages = json_decode($tourOrders['booking_package'], true);
                                                    if (is_array($decodedPackages)) {
                                                        foreach ($decodedPackages as $val) {
                                                            if (isset($val['id'], $val['type'], $val['price']) && $val['id'] == 0 && $val['type'] == 'ex_distance' && $val['price'] > 0) {
                                                                $ex_distance = $val['price'];
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }
                                                ?>
                                                <table class="table table-borderless min-width-600px">
                                                    <thead class="thead-light text-capitalize">
                                                        <tr class="fs-13 font-semibold">
                                                            <th class="px-5">{{ translate('packages') }}</th>
                                                            <th>{{ translate('qty') }}</th>
                                                            <th>{{ translate('price') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $p_checkIndex = 0;
                                                        $tax_summary = [];
                                                        $tax_summary_amounts = 0;
                                                        if (!empty($tourOrders['booking_package'])) {
                                                            $decodedPackages = json_decode($tourOrders['booking_package'], true);
                                                            if (is_array($decodedPackages)) {
                                                                foreach ($decodedPackages as $val) {
                                                                    if ((((float)$val['price'] ?? 0) > 0) || $val['type'] == "route") {
                                                                        if ($val['type'] == 'cab') {
                                                                            if ($tourOrders['Tour']['use_date'] != 0) {
                                                                                $p_checkIndex = ($val['qty']);
                                                                            }
                                                                            $tourPackages = \App\Models\TourCab::where('id', ($val['id'] ?? ''))->first();
                                                                            $images = getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($tourPackages['image'] ?? ''), type: 'backend-product');
                                                                        } elseif ($val['type'] == 'other' || $val['type'] == 'food' || $val['type'] == 'foods' || $val['type'] == 'hotel') {
                                                                            if ($tourOrders['Tour']['use_date'] != 0) {
                                                                                continue;
                                                                            }
                                                                            $tourPackages = \App\Models\TourPackage::where('id', ($val['id'] ?? ''))->first();
                                                                            $images = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($tourPackages['image'] ?? ''), type: 'backend-product');
                                                                        } elseif ($val['type'] == 'per_head' || $val['type'] == 'transport') {
                                                                            $tourPackages = [];
                                                                            $images = getValidImage(path: 'storage/app/public/tour_and_travels/package/', type: 'backend-product');
                                                                        } elseif ($val['type'] == 'tax' || $val['type'] == 'cgst' || $val['type'] == 'sgst') {
                                                                            $tax_summary[] =  ['title' => $val['title'], 'price' => $val['price']];
                                                                            $tax_summary_amounts += $val['price'];
                                                                            continue;
                                                                        } else {
                                                                            $tourPackages = [];
                                                                            $images = getValidImage(path: 'storage/app/public/tour_and_travels/package/', type: 'backend-product');
                                                                        }
                                                        ?>
                                                                        <tr>
                                                                            <td>
                                                                                <div class="media align-items-center gap-5">
                                                                                    @if ($val['type'] == 'ex_distance')
                                                                                    <small class=" w-50 font-semibold text-center">Ex Distance</small>
                                                                                    @elseif($val['type'] == 'route')
                                                                                    <small class=" w-50 font-semibold text-center">Route</small>
                                                                                    @elseif($val['type'] == 'per_head')
                                                                                    <small class=" w-50 font-semibold text-center">Per Head</small>
                                                                                     @elseif($val['type'] == 'transport')
                                                                                    <small class=" w-50 font-semibold text-center">Ex Transport</small>
                                                                                    @else
                                                                                    <img class="d-block get-view-by-onclick rounded"
                                                                                        src="{{ $images }}"
                                                                                        alt="{{ translate('image_Description') }}"
                                                                                        style="width: 80px;height: 72px;">
                                                                                    <div class="ml-1">
                                                                                        <small class="title-color"
                                                                                            data-title="{{ $tourPackages['name'] ?? '' }}"
                                                                                            role="tooltip" data-toggle="tooltip">
                                                                                            {{ $tourPackages['name'] ?? '' }} <br>
                                                                                            @if (!empty($val['seats'] ?? '') && $val['type'] == 'cab')
                                                                                            {{ $val['seats'] ?? '' }}
                                                                                            {{ $val['type'] == 'cab' ? 'seats' : 'people' }}
                                                                                            @endif
                                                                                        </small>
                                                                                    </div>
                                                                                    @endif
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <div class="media align-items-center gap-5">
                                                                                    @if ($val['type'] == 'ex_distance')
                                                                                    <small class="fs-15 font-semibold">Km: {{ $val['qty'] }}</small>
                                                                                    @elseif($val['type'] == 'route')
                                                                                    <small class="fs-15 font-semibold"></small>
                                                                                    @else
                                                                                    <small class="fs-15 font-semibold">
                                                                                        @if ($val['type'] == 'cab')
                                                                                        @if (($tourOrders['Tour']['tour_type'] ?? '') == 'cities_tour')
                                                                                        cabs :
                                                                                        @else
                                                                                        people :
                                                                                        @endif
                                                                                        @else
                                                                                        people :
                                                                                        @endif
                                                                                        {{ $val['qty'] }}
                                                                                    </small>
                                                                                    @endif
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <div class="">
                                                                                    <?php if ($val['type'] == 'cab' || $val['type'] == 'per_head' || ($val['type'] == 'transport')) { ?>
                                                                                        <span class="fs-15 font-semibold">
                                                                                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ((float) $val['price'] ?? 0) - $ex_distance), currencyCode: getCurrencyCode()) }}
                                                                                        </span>
                                                                                        <?php } else {
                                                                                        if ($tourOrders['use_date'] == 0 && $val['type'] != 'route') { ?>
                                                                                            <span class="fs-15 font-semibold">
                                                                                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: (float) $val['price'] ?? 0), currencyCode: getCurrencyCode()) }}
                                                                                            </span>
                                                                                        <?php } elseif ($val['type'] == 'ex_distance') { ?>
                                                                                            <span class="fs-15 font-semibold">
                                                                                                {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: (float) $val['price'] ?? 0), currencyCode: getCurrencyCode()) }}
                                                                                            </span>
                                                                                        <?php  } elseif ($val['type'] == 'route') { ?>
                                                                                            <span class="fs-15 font-semibold">
                                                                                                {{ ucwords(str_replace('_', ' ', $val['price'] ?? '')) }}
                                                                                            </span>
                                                                                        <?php   } else { ?>
                                                                                            <span
                                                                                                class="fs-15 text-success">{{ translate('included in The Price') }}</span>
                                                                                    <?php }
                                                                                    } ?>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                <?php
                                                                    }
                                                                } ?>
                                                                @if ($p_checkIndex > 0)
                                                                @if (!empty($tourOrders['Tour']['package_list_price']) && json_decode($tourOrders['Tour']['package_list_price'], true))
                                                                @foreach (json_decode($tourOrders['Tour']['package_list_price'], true) as $p_info)
                                                                <tr>
                                                                    <td>
                                                                        <?php $tourPackages = \App\Models\TourPackage::where('id', $p_info['package_id'] ?? '')->first(); ?>
                                                                        <div
                                                                            class="media align-items-center gap-5">
                                                                            <img class="d-block rounded"
                                                                                src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($tourPackages['image'] ?? ''), type: 'backend-product') }}"
                                                                                alt="{{ translate('image_Description') }}"
                                                                                style="width: 80px;height: 72px;">
                                                                            <div class="ml-1">
                                                                                <small class="title-color"
                                                                                    data-title="{{ $tourPackages['name'] ?? '' }}"
                                                                                    role="tooltip"
                                                                                    data-toggle="tooltip">
                                                                                    {{ $tourPackages['name'] ?? '' }}
                                                                                </small>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                    <td>people : {{ $p_checkIndex }}</td>
                                                                    <td> <span
                                                                            class="fs-15 text-success">{{ translate('included in The Price') }}</span>
                                                                    </td>
                                                                </tr>
                                                                @endforeach
                                                                @endif
                                                                @endif
                                                        <?php  }
                                                        } ?>

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row d-flex justify-content-end mt-2">
                                        <div class="col-md-8 col-lg-5">
                                            <div class="bg-white border-sm rounded">
                                                <div class="card-body ">
                                                    <table class="calculation-table table table-borderless mb-0">
                                                        <tbody class="totals">
                                                            <tr>
                                                                <td>
                                                                    <div class="text-start">
                                                                        <span
                                                                            class="font-semibold">{{ translate('item') }}</span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="text-end">
                                                                        <span
                                                                            class="font-semibold">{{ translate('Price') }}</span>
                                                                    </div>
                                                                </td>
                                                            </tr>

                                                            <tr class="border-top">
                                                                <td>
                                                                    <div class="text-start">
                                                                        <span
                                                                            class="product-qty">{{ translate('subtotal') }}</span>
                                                                    </div>
                                                                </td>
                                                                <td>

                                                                    <div class="text-end">
                                                                        <span class="fs-15 font-semibold">
                                                                            @if($tourOrders['part_payment'] == 'part')
                                                                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ((float) $tourOrders['amount'] ?? 0) + ((float) $tourOrders['amount'] ?? 0) + ((float) $tourOrders['coupon_amount'] ?? 0) - $tax_summary_amounts ), currencyCode: getCurrencyCode()) }}
                                                                            @else
                                                                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ((float) $tourOrders['amount'] ?? 0) + ((float) $tourOrders['coupon_amount'] ?? 0) - $tax_summary_amounts), currencyCode: getCurrencyCode()) }}
                                                                            @endif
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            @if($tax_summary)
                                                            @foreach($tax_summary as $taxs)
                                                            <tr class="border-top">
                                                                <td>
                                                                    <div class="text-start">
                                                                        <span class="product-qty" style="font-size: 13px;">{{ $taxs['title']}}</span>
                                                                    </div>
                                                                </td>
                                                                <td>

                                                                    <div class="text-end">
                                                                        <span
                                                                            class="fs-15 font-semibold">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: (float) $taxs['price']??0), currencyCode: getCurrencyCode()) }}</span>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                            @endif
                                                            <tr class="border-top">
                                                                <td>
                                                                    <div class="text-start">
                                                                        <span
                                                                            class="product-qty">{{ translate('coupon_discount') }}</span>
                                                                    </div>
                                                                </td>
                                                                <td>

                                                                    <div class="text-end">
                                                                        <span
                                                                            class="fs-15 font-semibold">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: (float) $tourOrders['coupon_amount'] ?? 0), currencyCode: getCurrencyCode()) }}</span>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            @if($tourOrders['part_payment'] == 'part')
                                                            <tr class="border-top">
                                                                <td>
                                                                    <div class="text-start">
                                                                        <span
                                                                            class="product-qty">{{ translate('remaining_pay') }}</span>
                                                                    </div>
                                                                </td>
                                                                <td>

                                                                    <div class="text-end">
                                                                        <span
                                                                            class="fs-15 font-semibold">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: (float) $tourOrders['amount'] ?? 0), currencyCode: getCurrencyCode()) }}</span>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            @endif
                                                            <tr class="border-top">
                                                                <td>
                                                                    <div class="text-start">
                                                                        <span class="font-weight-bold">
                                                                            <strong>{{ translate('Paid_Amount') }}</strong>
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="text-end">
                                                                        <span class="font-weight-bold amount">
                                                                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: (float) $tourOrders['amount'] ?? 0), currencyCode: getCurrencyCode()) }}
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            @if ($tourOrders['refund_status'] == 1)
                                                            <tr class="border-top">
                                                                <td>
                                                                    <div class="text-start">
                                                                        <span class="font-weight-bold">
                                                                            <strong>{{ translate('refund_Price') }}</strong>
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="text-end">
                                                                        <span class="font-weight-bold amount">
                                                                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: (float) $tourOrders['refund_amount'] ?? 0), currencyCode: getCurrencyCode()) }}
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            @endif
                                                        </tbody>
                                                    </table>
                                                    @if ($tourOrders['amount_status'] == '1' && $tourOrders['pickup_status'] == 0)
                                                    @if (empty($tourOrders['refund_query_id']) && $tourOrders['status'] != 2)
                                                    <button type="button" onclick="click_inquery()"
                                                        class="btn btn-soft-danger btn-soft-border w-100 btn-sm text-danger font-semibold text-capitalize mt-3">
                                                        {{ translate('cancel_Tour') }}
                                                    </button>
                                                    @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="card-body">
                                                <div class="row">
                                                    @if (!empty($tourOrders['Tour']['tour_type'] ?? ''))
                                                    @php
                                                    $getSpecial_tour = \App\Models\TourRefundPolicy::where('status',1)->where('type', $tourOrders['Tour']['tour_type'])->orderBy('day', 'desc')->get();
                                                    @endphp
                                                    @if (!empty($getSpecial_tour))
                                                    @php $data_check = ''; @endphp
                                                    @foreach ($getSpecial_tour as $val)
                                                    @php
                                                    $pickupDate = strtotime($tourOrders['pickup_date'].' '.$tourOrders['pickup_time'] .' -' .$val['day'] .' hours');
                                                    $createdAt = strtotime($tourOrders['created_at']);
                                                    @endphp

                                                    @if ($pickupDate > $createdAt)
                                                    @php
                                                    $data_check = 'access';
                                                    break;
                                                    @endphp
                                                    @endif
                                                    @endforeach
                                                    @if ($data_check == 'access')
                                                    <table class="table cancellation-policy-table">
                                                        <thead>
                                                            <tr>
                                                                <td colspan="3" class="text-center"
                                                                    style="padding: 5px; background-color: gainsboro;">
                                                                    {{ ucwords('cancellation policy') }}
                                                                </td>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($getSpecial_tour as $val)
                                                            @php
                                                            $pickupDate = strtotime($tourOrders['pickup_date'].' '.$tourOrders['pickup_time'].' -' .$val['day'] .' hours');
                                                            $createdAt = strtotime($tourOrders['created_at']);
                                                            @endphp
                                                            @if ($pickupDate > $createdAt)
                                                            <tr>
                                                                <td>
                                                                    {!! preg_replace('/\{\{\s*\$date\s*\}\}/','<strong>' . date('d-m-Y h:i A', strtotime($tourOrders['pickup_date'].' '.$tourOrders['pickup_time']. ' -' . $val['day'] . ' hours')) . '</strong>',$val['message']) !!}
                                                                </td>
                                                                <td>{{ $val['percentage'] }}%</td>
                                                                <td>
                                                                    {{-- (($tourOrders['amount']*$val['percentage'])/100) --}}
                                                                    <?php
                                                                    $total_amounts = 0;
                                                                    $total_amounts = (float) $tourOrders['amount'] ?? 0;
                                                                    $total_amounts = ($total_amounts * ((float) ($val['percentage'] ?? 0))) / 100;
                                                                    ?>
                                                                    {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $total_amounts), currencyCode: getCurrencyCode()) }}
                                                                </td>
                                                            </tr>
                                                            @endif
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                    @endif
                                                    @endif

                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade text-justify" id="Itinerary" role="tabpanel">
                            <div class="col-12">
                                <div class="card-body bg-white border-lg rounded mobile-full">
                                    <div class="row">
                                        <?php
                                        if (isset(($tourOrders['Tour']['TourPlane'])) && !empty(($tourOrders['Tour']['TourPlane'] ?? ''))) {
                                            $loop_index = 1;
                                            foreach ($tourOrders['Tour']['TourPlane'] as $vals) { ?>
                                                <div class="col-md-2">Days {{ $loop_index }}</div>
                                                <div class="col-md-10">{{ $vals['name'] }}</div>
                                                <div class="col-md-2"></div>
                                                <div class="col-md-10">{{ $vals['time'] }}</div>
                                                <div class="col-md-2"></div>
                                                <div class="col-md-10">{!! $vals['description'] !!}</div>
                                                <div class="col-md-12 py-2">
                                                    <hr>
                                                </div>
                                        <?php $loop_index++;
                                            }
                                        } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade  text-justify" id="reviews" role="tabpanel">
                            <div class="col-12">
                                <div class="card-body bg-white border-lg rounded mobile-full">
                                    @php
                                    $getTourReview = \App\Models\TourReviews::where([
                                    'order_id' => $tourOrders['id'],
                                    'user_id' => $tourOrders['user_id'],
                                    'tour_id' => $tourOrders['tour_id'],
                                    ])->first();
                                    @endphp
                                    @if (!$getTourReview || $getTourReview['is_edited'] == 0)
                                    <form action="{{ route('tour.add-review') }}" method="post"
                                        enctype="multipart/form-data">
                                        @csrf
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label for="exampleInputEmail1">{{ translate('rating') }}</label>
                                                <select class="form-control" name="rating">
                                                    <option value="1">{{ translate('1') }}</option>
                                                    <option value="2">{{ translate('2') }}</option>
                                                    <option value="3">{{ translate('3') }}</option>
                                                    <option value="4">{{ translate('4') }}</option>
                                                    <option value="5" selected>{{ translate('5') }}</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="exampleInputEmail1">{{ translate('comment') }}</label>
                                                <input type="hidden" name="tour_id"
                                                    value="{{ $tourOrders['tour_id'] }}">
                                                <input type="hidden" name="order_id"
                                                    value="{{ $tourOrders['id'] }}" hidden>
                                                <textarea class="form-control" name="comment">{{ $getTourReview['comment'] ?? '' }}</textarea>
                                            </div>


                                        </div>
                                        <div class="modal-footer">
                                            <a href="{{ URL::previous() }}"
                                                class="btn btn-secondary">{{ translate('back') }}</a>
                                            <button type="submit"
                                                class="btn btn--primary">{{ translate('submit') }}</button>
                                        </div>
                                    </form>
                                    @else
                                    <section class="rating__card text-center">
                                        <blockquote class="rating__card__quote">“{{ $getTourReview['comment'] }}”
                                        </blockquote>
                                        <div class="rating__card__stars">
                                            @for ($i = 1; $i <= 5; $i++)
                                                @if ($i <=$getTourReview['star'])
                                                <i class="fa fa-star star-rating text-warning"></i>
                                                @else
                                                <i class="fa fa-star-o star-rating"></i>
                                                @endif
                                                @endfor
                                                <br>
                                                <span
                                                    class="rating__card__stars__name">{{ $tourOrders['userData']['name'] }}</span>
                                        </div>
                                        <p class="rating__card__bottomText">
                                            {{ date('h:i A, d M Y', strtotime($getTourReview['created_at'])) }}
                                        </p>
                                    </section>

                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade  text-justify chat_inquirys" id="chat-roles" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <div class="chat-container">
                                        <div class="chat-header">
                                            <i class="tio-money_vs">money_vs</i>&nbsp;
                                            <span>{{ translate('Refund_inquiry') }}</span>
                                        </div>
                                        <div class="chat-box" id="form-reload-order-cancel-chat">
                                            @php
                                            $order_id = \App\Models\TourOrder::where(
                                            'id',
                                            $tourOrders['id'],
                                            )->first();
                                            $get_Chat = \App\Models\TourCancelResonance::where(
                                            'ticket_id',
                                            $order_id['refund_query_id'],
                                            )->get();
                                            @endphp
                                            @if ($get_Chat)
                                            @foreach ($get_Chat as $val)
                                            <div class="row">
                                                <div class="col-md-5">
                                                    @if ($val['type'] == 'admin')
                                                    <div class="admin-message">
                                                        <div class="chat-message">
                                                            {{ $val['msg'] }}
                                                        </div>
                                                    </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-2"></div>
                                                <div class="col-md-5">
                                                    @if ($val['type'] == 'user')
                                                    <div class="user-message">
                                                        <div class="chat-message">
                                                            {{ $val['msg'] }}
                                                        </div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                            @endforeach
                                            @endif
                                        </div>

                                        <div class="chat-input">
                                            <input type="text" id="chatMessage" class="cancel-order-msg"
                                                placeholder="Write your message here...">
                                            <button id="submitBtn"><i class="fa fa-paper-plane"></i></button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </section>
    </div>

</div>


<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-faded-info">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ translate('Send_Message_to_traveller') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="tour-view-chat-form">
                    @csrf
                    <input type="hidden" value="{{ $tourOrders['id'] }}" class="tour-view-chat-order_id">
                    <input type="hidden" value="{{ $tourOrders['user_id'] }}" class="tour-view-chat-user_id">
                    <textarea name="message" class="form-control min-height-100px max-height-200px tour-view-chat-msg" required
                        placeholder="{{ translate('Write_here') }}..."></textarea>
                    <br>
                    @php
                    $getSpecial_tour = \App\Models\TourRefundPolicy::where('status', 1)
                    ->where('type', $tourOrders['Tour']['tour_type'] ?? '')
                    ->orderBy('day', 'desc')
                    ->get();

                    $refund_amount = 0;
                    $pickupTimestamp = strtotime($tourOrders['pickup_date'] . ' ' . $tourOrders['pickup_time']);
                    @endphp

                    @if (!empty($getSpecial_tour) && count($getSpecial_tour) > 0)
                    @foreach ($getSpecial_tour as $val)
                    @php
                    $calculatedTimestamp = strtotime("-" . $val['day'] . " hours", $pickupTimestamp);
                    $currentTimestamp = strtotime(now());
                    @endphp
                    @if ($currentTimestamp <= $calculatedTimestamp)
                        @php
                        $refund_amount=($tourOrders['amount'] * $val['percentage']) / 100;
                        break;
                        @endphp
                        @endif
                        @endforeach
                        @endif


                        <input type="hidden" value="{{ $refund_amount ?? 0 }}" class="form-control tour-view-chat-amount_id">
                        <br>
                        <span class="font-weight-bold">Refund Amount :
                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: (float) $refund_amount ?? 0), currencyCode: getCurrencyCode()) }}
                        </span>
                        <br>
                        <br>
                        <div class="justify-content-end gap-2 d-flex flex-wrap">
                            <button type='button' class="btn btn--primary text-white tour-view-chat-submit">
                                {{ translate('send') }}
                            </button>
                        </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade addFundToWallet" tabindex="-1" aria-labelledby="addFundToWalletModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">

            <div class="modal-header border-0">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body px-5">
                <form action="{{ route('tour.add_remaining_amount', [$tourOrders['id']]) }}" method="post">
                    @csrf
                    <div class="">
                        <h4 class="text-center">{{ translate('remaining amount due') }}</h4>
                        <input type="hidden" name="user_id"
                            value="{{ auth('customer')->check() ? auth('customer')->user()->id : $userId->id ?? '' }}">
                        <input type="hidden" name="customer_id"
                            value="{{ auth('customer')->check() ? auth('customer')->user()->id : $userId->id ?? '' }}">
                        <input type="hidden" value="{{ $tourOrders['amount'] }}" name="payment_amount" required
                            placeholder="{{ translate('ex') }}: {{ webCurrencyConverter(amount: $tourOrders['amount'] ?? 0) }}">
                        <input type="hidden" value="web" name="payment_platform" required>
                        @if ((\App\Models\User::where('id', auth('customer')->id())->first()['wallet_balance'] ?? 0) > 0)
                        <input type="checkbox" value="1" name="wallet_type"> User Wallet <small
                            class="text-success font-weight-bold">({{ webCurrencyConverter(amount: \App\Models\User::where('id', auth('customer')->id())->first()['wallet_balance'] ?? 0) }})</small>
                        @endif
                        <input type="hidden" value="{{ request()->url() }}" name="external_redirect_link"
                            required>
                    </div>
                    <?php
                    $payment_gateways = \App\Utils\payment_gateways();
                    ?>
                    <div id="add-fund-list-area">
                        @if (count($payment_gateways) > 0)
                        <h6 class="mb-2">{{ translate('payment_Methods') }}
                            <small>({{ translate('faster_&_secure_way_to_pay_bill') }})</small>
                        </h6>
                        <div class="gateways_list">

                            @forelse ($payment_gateways as $gateway)
                            <label class="form-check form--check rounded">
                                <input type="radio" class="form-check-input d-none" name="payment_method"
                                    value="{{ $gateway->key_name }}" required
                                    {{ $loop->index == 0 ? 'checked' : '' }}>
                                <div class="check-icon">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="8" cy="8" r="8" fill="#1455AC" />
                                        <path
                                            d="M9.18475 6.49574C10.0715 5.45157 11.4612 4.98049 12.8001 5.27019L7.05943 11.1996L3.7334 7.91114C4.68634 7.27184 5.98266 7.59088 6.53004 8.59942L6.86856 9.22314L9.18475 6.49574Z"
                                            fill="white" />
                                    </svg>
                                </div>
                                @php($payment_method_title = !empty($gateway->additional_data) ? json_decode($gateway->additional_data)->gateway_title ?? ucwords(str_replace('_', ' ', $gateway->key_name)) : ucwords(str_replace('_', ' ', $gateway->key_name)))
                                @php($payment_method_img = !empty($gateway->additional_data) ? json_decode($gateway->additional_data)->gateway_image : '')
                                <div class="form-check-label d-flex align-items-center">
                                    <img width="60" alt="{{ translate('payment') }}"
                                        src="{{ getValidImage(path: 'storage/app/public/payment_modules/gateway_image/' . $payment_method_img, type: 'banner') }}">
                                    <span class="ml-3">{{ $payment_method_title }}</span>
                                </div>
                            </label>
                            @empty
                            @endforelse
                        </div>
                        <div class="d-flex justify-content-center pt-2 pb-3">
                            <button type="submit" class="btn btn--primary w-75 mx-3"
                                id="add_fund_to_wallet_form_btn">{{ translate('pay') }}</button>
                        </div>
                        @else
                        <h6 class="small text-center">{{ translate('no_Payment_Methods_Gateway_found') }}</h6>
                        @endif
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>
    function pay_now_order() {
        $('.addFundToWallet').modal();
    }

    const chatBox = document.getElementById('chat-box');
    const submitBtn = document.getElementById('submitBtn');
    const chatMessageInput = document.getElementById('chatMessage');
    let isAdmin = false;

    submitBtn.addEventListener('click', function() {
        // const message = chatMessageInput.value;
        // if (message.trim() !== '') {
        //   const messageDiv = document.createElement('div');
        //   messageDiv.textContent = message;
        //   if (isAdmin) {
        //     messageDiv.classList.add('chat-message', 'admin-message');
        //   } else {
        //     messageDiv.classList.add('chat-message', 'user-message');
        //   }
        //   chatBox.appendChild(messageDiv);
        //   chatMessageInput.value = '';
        //   chatBox.scrollTop = chatBox.scrollHeight;
        //   isAdmin = !isAdmin;
        // }

        var order_id = "{{ $tourOrders['id'] }}";
        var msg = $('.cancel-order-msg').val();
        $.ajax({
            url: "{{ route('tour.cancel-order-resonance') }}",
            data: {
                type: "user",
                msg,
                order_id,
                _token: '{{ csrf_token() }}'
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            dataType: "json",
            type: "post",
            success: function(data) {
                chatMessageInput.value = '';
                $("#form-reload-order-cancel-chat").load(location.href +
                    " #form-reload-order-cancel-chat > *");
            }
        });

    });


    function click_inquery() {
        $('#exampleModal').modal('show');
    }
    $('.tour-view-chat-submit').click(function() {
        var msg = $('.tour-view-chat-msg').val();
        var order_id = $('.tour-view-chat-order_id').val();
        var user_id = $('.tour-view-chat-user_id').val();
        var amount = $('.tour-view-chat-amount_id').val();
        if (!msg || msg.trim().length === 0) {
            toastr.error('{{ translate('
                Enter a Tour Cancel Resonance ') }}');
            return false;
        } else {
            $.ajax({
                url: "{{ route('tour.create-ticket') }}",
                data: {
                    msg,
                    order_id,
                    user_id,
                    amount,
                    _token: '{{ csrf_token() }}'
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                dataType: "json",
                type: "post",
                success: function(data) {
                    window.location.href = ``;
                    // toastr.success('{{ translate('ticket successfully created') }}');
                    // $('.tab-pane').removeClass('active show');
                    // $('.nav-link').removeClass('active');
                    // $('.chat_inquirys').addClass('active show');
                    // $('a[href="#chat-roles"]').addClass('active');
                    // $('a[href="#chat-roles"]').removeClass('d-none');
                    $('#exampleModal').modal('hide');
                }
            })
        }
    })


    setInterval(function() {
        if ($('.nav-link[href="#chat-roles"]').hasClass('active')) {
            $("#form-reload-order-cancel-chat").load(location.href + " #form-reload-order-cancel-chat > *");
        }
    }, 1000);
</script>
@endpush