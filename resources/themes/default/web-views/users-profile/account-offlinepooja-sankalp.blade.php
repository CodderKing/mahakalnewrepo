@extends('layouts.front-end.app')
@section('title', translate('order_Sankalp'))
@push('css_or_js')
    <link rel="stylesheet"
        href="{{ theme_asset(path: 'public/assets/front-end/plugin/intl-tel-input/css/intlTelInput.css') }}">
@endpush
@section('content')
    <style>
        .pac-container {
            z-index: 10000 !important;
        }
    </style>
    <div class="container pb-5 mb-2 mb-md-4 mt-3 rtl __inline-47 text-align-direction">
        <div class="row g-3">
            @include('web-views.partials._profile-aside')
            <section class="col-lg-9">
                @include('web-views.users-profile.offlinepooja-details.offlinepooja-order-partial')
                <div class="card border-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <h5 class="font-bold m-0 fs-16">Sankalp Details Updates</h5>
                            <div class="text-end d-none d-lg-block">
                                @if ($order['is_edited'] == '0')
                                    <button class="btn btn-danger px-2 py-1" type="button" id="editButton">Edit
                                        Details</button>
                                @else
                                    <button class="btn btn-primary px-2 py-1" type="button" id="editButton">Show
                                        Details</button>
                                @endif
                            </div>

                        </div>
                        @if ($order)
                            <form class="needs-validation" id="sankalp_check"
                                action="{{ route('offlinepoojasanklpUpdate', $order['order_id']) }}" method="post">
                                @csrf
                                <input type="hidden" name="orer_id" value="{{ $order['order_id'] }}">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label class="form-label font-semibold">{{ translate('phone_number') }}
                                                <small class="text-primary">(
                                                    *{{ translate('country_code_is_must_like_for_IND') }}
                                                    91)</small></label>
                                            <input class="form-control text-align-direction phone-input-with-country-picker"
                                                type="tel"
                                                value="{{ isset($order['customers']['phone']) ? $order['leads']['person_phone'] : '' }}"
                                                required readonly inputmode="numeric" maxlength="10" minlength="10">
                                            <input type="hidden" class="country-picker-phone-number w-50" name="newphone"
                                                readonly>
                                        </div>
                                    </div>

                                </div>
                                <hr class="my-2">

                                <div class="row hideable-div mt-3">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label class="form-label font-semibold">{{ translate('venue_address ') }}<span
                                                    class="text-danger">*</span></label>
                                            <input class="form-control text-align-direction" type="text"
                                                name="venue_address" id="google-search2" placeholder="venue address"
                                                value="{{ $order['venue_address'] }}"
                                                {{ $order['is_edited'] == '1' ? 'disabled' : '' }} required>
                                            <input class="form-control" type="hidden" name="state" id="state2"
                                                placeholder="State" value="{{ $order['state'] }}">
                                            <input class="form-control" type="hidden" name="city" id="city2"
                                                placeholder="City Name" value="{{ $order['city'] }}">
                                            <input class="form-control" type="hidden" name="pincode" id="pincode2"
                                                placeholder="Pincode" value="{{ $order['pincode'] }}">
                                            <input class="form-control" type="hidden" name="latitude" id="latitude2"
                                                placeholder="latitude" value="{{ $order['latitude'] }}">
                                            <input class="form-control" type="hidden" name="longitude" id="longitude2"
                                                placeholder="longitude" value="{{ $order['longitude'] }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label font-semibold">{{ translate('booking_Date') }}<span
                                                    class="text-danger">*</span></label>
                                            <input class="form-control text-align-direction" type="date"
                                                name="booking_date" value="{{ $order['booking_date'] }}"
                                                {{ $order['is_edited'] == '1' ? 'disabled' : '' }} min="{{ date('Y-m-d') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label class="form-label font-semibold">{{ translate('landmark') }}<span
                                                    class="text-danger">*</span></label>
                                            <input class="form-control text-align-direction" type="text" name="landmark"
                                                placeholder="Landmark" value="{{ $order['landmark'] }}"
                                                {{ $order['is_edited'] == '1' ? 'disabled' : '' }} required>
                                        </div>
                                    </div>
                                </div>
                                @if ($order['is_edited'] == '0')
                                    <div class="web-direction">
                                        <div class="mx-auto mt-4 __max-w-356">
                                            <button class="w-100 btn btn--primary" id=""
                                                type="submit">{{ translate('Update_Details') }}
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </form>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
@push('script')
    <script src="{{ theme_asset(path: 'public/assets/front-end/plugin/intl-tel-input/js/intlTelInput.js') }}"></script>
    <script src="{{ theme_asset(path: 'public/assets/front-end/js/country-picker-init.js') }}"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places&callback=initAutocomplete"
        async></script>
    <script src="{{ theme_asset(path: 'public/assets/front-end/js/spartan-multi-image-picker.js') }}"></script>
    <script src="{{ theme_asset(path: 'public/assets/front-end/js/account-order-details.js') }}"></script>

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
    <script>
        $(document).ready(function() {
            $('#editButton').click(function() {
                $('#sankalp_check').toggle();
            });
        });
    </script>
@endpush
