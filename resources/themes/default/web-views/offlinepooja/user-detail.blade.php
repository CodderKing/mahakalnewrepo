@extends('layouts.front-end.app')
@section('title', $orderDetails['offlinePooja']['name'])
@push('css_or_js')
    <link rel="stylesheet"
        href="{{ theme_asset(path: 'public/assets/front-end/plugin/intl-tel-input/css/intlTelInput.css') }}">
    <style>
        .vertical {
            height: 10% position: absolute border-left: 2px solid black
        }
    </style>
    <script type="text/javascript">
        function preventBack() {
            window.history.forward();
        }
        setTimeout("preventBack()", 0);
        window.onunload = function() {
            null
        };
    </script>
@endpush
@section('content')
    <div class="w-full h-full sticky md:top-[68px] top-0 z-20">
        <div class="bg-bar w-full">
            <div class="d-flex overflow-x-scroll w-full scrollbar-hide max-w-screen-xl mx-auto"
                id="breadcrum-container-outer">
                <div class="d-flex flex-row items-center bg-bar h-14 px-4 md:px-0" id="breadcrum-container">
                    @include('web-views.offlinepooja.partials.statusbar')
                </div>
            </div>
        </div>
    </div>
    <div class="container mt-3  px-0 px-md-3 text-align-direction">
        <h3 class="mt-4 mb-3 text-center text-lg-left mobile-fs-20 fs-18 font-bold">
            <a href="{{ url()->previous() }}"><span aria-hidden="true"><i class="fa fa-arrow-left"></i></span>
            </a>
            <span class="text font-bold px-3">Enter details for your pooja</span>
        </h3>
        <div class="row">
            <div class="col-md-7">
                <div class="login-card">
                    <div class="mx-auto __max-w-760">
                        <h2 class="mt-4 mb-3 text-center text-lg-left mobile-fs-20 fs-18 font-bold">Your WhatsApp Number
                        </h2>
                        <span>{{ translate('Your_Pooja_booking_updates,_including_the_pandit\'s_live_location,_arrival_status,_and_other_details,_will_be_sent_on_WhatsApp_to_the_number_below.') }}</span>
                        <form class="needs-validation" action="{{ route('offline.pooja.user.store') }}" method="post">
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $orderDetails['order_id'] }}">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label class="form-label font-semibold">{{ translate('phone_number') }}
                                            <small class="text-primary">( *
                                                {{ translate('country_code_is_must_like_for_IND') }} 91 )</small></label>
                                        @php
                                            $customerPhone = isset($orderDetails['customers']['phone'])
                                                ? $orderDetails['customers']['phone']
                                                : '';
                                            $leadPhone = isset($orderDetails['leads']['person_phone'])
                                                ? $orderDetails['leads']['person_phone']
                                                : '';
                                            $phoneValue = $customerPhone . $leadPhone;
                                        @endphp
                                        <input class="form-control text-align-direction phone-input-with-country-picker"
                                            type="tel"
                                            value="{{ isset($orderDetails['customers']['phone']) ? $orderDetails['leads']['person_phone'] : '' }}"
                                            required readonly>
                                        <input type="hidden" class="country-picker-phone-number w-50" name="person_phone"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="rtl">
                                        <label class="custom-control custom-checkbox m-0 d-flex">
                                            <input type="checkbox" class="custom-control-input" name="newnumber"
                                                id="NewNumberAdd" value="0">
                                            <span class="custom-control-label">
                                                <span>I have a different number for calling</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-12" id="newPhoneAdd" style="display:none">
                                    <div class="form-group">
                                        <label class="form-label font-semibold">Enter new your Calling Number</label>
                                        <input class="form-control text-align-direction" type="tel" name="newPhone"
                                            placeholder="{{ translate('enter__new_phone_number') }}" autocomplete="off"
                                            inputmode="number">
                                    </div>
                                </div>
                            </div>
                            <hr class="my-2">
                            <h2 class="mt-4 mb-3 text-center text-lg-left mobile-fs-20 fs-18 font-bold">Enter Your Details
                            </h2>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label font-semibold">{{ translate('venue_address ') }}<span
                                                class="text-danger">*</span></label>
                                        <input class="form-control text-align-direction" type="text" name="venue_address"
                                            id="google-search" value="" placeholder="your venue address" required>
                                        <input class="form-control" type="hidden" name="state" id="state"
                                            placeholder="state">
                                        <input class="form-control" type="hidden" name="city" id="city"
                                            placeholder="city">
                                        <input class="form-control" type="hidden" name="pincode" id="pincode"
                                            placeholder="pincode">
                                        <input class="form-control" type="hidden" name="latitude" id="latitude"
                                            placeholder="latitude">
                                        <input class="form-control" type="hidden" name="longitude" id="longitude"
                                            placeholder="longitude">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label font-semibold">{{ translate('booking_date') }}<span
                                                class="text-danger">*</span></label>
                                        <input class="form-control text-align-direction" type="date" name="booking_date"
                                            min="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label class="form-label font-semibold">{{ translate('landmark ') }}<span
                                                class="text-danger">*</span></label>
                                        <input class="form-control text-align-direction" type="text" name="landmark"
                                            value="" placeholder="Landmark" required>
                                    </div>
                                </div>
                            </div>
                            <div class="web-direction">
                                <div class="mx-auto mt-4 __max-w-356">
                                    <button class="w-100 btn btn--primary" id=""
                                        type="submit">{{ translate('Proceed_to_book') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="flash_deal_product rtl cursor-pointer mb-2">
                    <div class="d-flex">
                        <div class="d-flex align-items-center justify-content-center p-3">
                            <div class="flash-deals-background-image image-default-bg-color">
                                <img src="{{ getValidImage(path: 'storage/app/public/offlinepooja/thumbnail/' . $orderDetails['offlinePooja']['thumbnail']) }}"
                                    class="__img-125px" alt="offlinepooja Name">
                            </div>
                        </div>
                        <div class="flash_deal_product_details pl-3 pr-3 pr-1 d-flex align-items-center">
                            <div>
                                <div>
                                    <h1 class="flash-product-title"
                                        style="font-size:15px;font-weight: 600;line-height:20px;margin-bottom:8px;">
                                        {{ $orderDetails['offlinePooja']['offlinepooja_name'] }}
                                    </h1>
                                </div>
                                <div class="widget-meta d-flex flex-wrap gap-8 align-items-center row-gap-0">
                                    <span class="flash-product-price fw-semibold text-dark">
                                        Your Package: <strong
                                            class="text-capitalize">{{ $orderDetails['package']['title'] }}</strong>
                                    </span>
                                </div>
                                <div class="d-flex flex-wrap gap-8 align-items-center row-gap-0">
                                    <span class="flash-product-price fw-semibold text-dark">
                                        {{-- @php
                                    $OrderPrice = \App\Models\OfflinepoojaOrder::where('order_id', $orderDetails['order_id'])->first();
                                @endphp
                                @if ($OrderPrice) --}}
                                        Paid Amount:<strong>{{ webCurrencyConverter(amount: $orderDetails['pay_amount']) }}
                                        </strong>
                                        {{-- @endif --}}
                                    </span>
                                </div>
                                <div class="d-flex flex-wrap gap-8 align-items-center row-gap-0">
                                    <span class="flash-product-price fw-semibold text-dark">
                                        {{-- @php
                                    $OrderPrice = \App\Models\OfflinepoojaOrder::where('order_id', $orderDetails['order_id'])->first();
                                @endphp
                                @if ($OrderPrice) --}}
                                        Remaining
                                        Amount:<strong>{{ webCurrencyConverter(amount: $orderDetails['remain_amount']) }}
                                        </strong>
                                        {{-- @endif --}}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    </script>
    <script src="{{ theme_asset(path: 'public/assets/front-end/plugin/intl-tel-input/js/intlTelInput.js') }}"></script>
    <script src="{{ theme_asset(path: 'public/assets/front-end/js/country-picker-init.js') }}"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places&callback=initAutocomplete"
        async></script>

    <script>
        $(document).ready(function() {
            $("#NewNumberAdd").change(function() {
                if ($(this).is(":checked")) {
                    $("#newPhoneAdd").show();
                    $("#newPhoneAdd input[name='newPhone']").prop("required", true);
                } else {
                    $("#newPhoneAdd").hide();
                    $("#newPhoneAdd input[name='newPhone']").prop("required", false);
                }
            });

        });
    </script>

    {{-- search place using google map --}}
    <script>
        let autocomplete;

        function initAutocomplete() {
            const input = document.getElementById("google-search");
            const options = {
                componentRestrictions: {
                    country: "IN"
                }
            }
            autocomplete = new google.maps.places.Autocomplete(input, options);
            autocomplete.addListener("place_changed", onPlaceChange)
        }

        function onPlaceChange() {
            const place = autocomplete.getPlace();
            const addressComponents = place.address_components;

            let latitude = place.geometry.location.lat();
            let longitude = place.geometry.location.lng();
            let address = place.formatted_address;
            let state = '';
            let city = '';
            let postalCode = '';

            addressComponents.forEach(component => {
                const componentType = component.types[0];

                switch (componentType) {
                    case 'administrative_area_level_1':
                        state = component.long_name;
                        break;
                    case 'locality':
                        city = component.long_name;
                        break;
                    case 'postal_code':
                        postalCode = component.long_name;
                        break;
                }
            });

            $('#state').val(state);
            $('#city').val(city);
            $('#pincode').val(postalCode);
            $('#latitude').val(latitude);
            $('#longitude').val(longitude);
        }
    </script>
@endpush
