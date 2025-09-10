@extends('layouts.back-end.app')

@section('title', translate('leads_manualy_generate'))

@push('css_or_js')
    <link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
    <link href="https://unpkg.com/gijgo@1.9.14/css/gijgo.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <link rel="stylesheet"
        href="{{ dynamicAsset(path: 'public/assets/back-end/plugins/intl-tel-input/css/intlTelInput.css') }}">
    <style>
        .gj-datepicker-bootstrap [role=right-icon] button .gj-icon {
            top: 14px;
            right: 5px;
        }

        .gj-timepicker-bootstrap [role=right-icon] button .gj-icon {
            top: 14px;
            right: 5px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="card">


            <div class="card-body">
                <form class="Leads" action="{{ route('admin.leads.add-new-leads') }}" method="POST"
                    enctype="multipart/form-data" id="lead-form-generate">
                    @csrf
                    <div class="">

                        <div class="d-flex gap-2">
                            <i class="tio-company"></i>
                            <h4 class="mb-0">{{ translate('Add New Lead') }}</h4>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color" for="owner_name">{{ translate('platform_type') }}</label>
                                    @php
                                        if (auth('admin')->check()) {
                                            $adminId = App\Models\Admin::where('id', auth('admin')->id())->first();
                                        }
                                    @endphp
                                    <select name="platform" id="platform " class="form-control" required>
                                        <option value="">All Types</option>
                                        <option value="app">App</option>
                                        <option value="web">Website</option>
                                        <option value="admin">Admin Side</option>
                                        <option value="instagram">Instagram</option>
                                        <option value="facebook">Facebook</option>
                                        <option value="ads">Ads</option>
                                    </select>
                                    <small class="form-text text-muted" style="color: red; font-weight: bold;">
                                        Note: platform_type select करना ज़रूरी है। बिना चयन किए रिकॉर्ड सेव नहीं होगा।
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="title-color" for="owner_name">{{ translate('select_service_type') }}
                                    </label>
                                    <select name="type" id="type" class="form-control" required>
                                        <option value="">All Types</option>
                                        <option value="pooja">Pooja</option>
                                        <option value="vip">VIP</option>
                                        <option value="anushthan">Anushthan</option>
                                        <option value="chadhava" disabled>Chadhava</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">

                                    <div id="service-section"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div id="package-section"></div>
                                </div>
                            </div>
                            <div class="col-md-4" id="bookingDate" style="display: none;">
                                <div class="form-group">
                                    <label class="title-color" for="owner_name">{{ translate('booking_date') }}
                                    </label>
                                    <input class="form-control" type="date" name="booking_date" id="selectDate"
                                        placeholder="Booking Slot Date" autocomplete="off" required>

                                </div>
                            </div>

                            <div class="col-md-4" id="userData" style="display: none;">
                                <div class="form-group">
                                    <label class="form-label font-semibold">{{ translate('phone_number') }}
                                        <small class="text-primary">(
                                            *{{ translate('country_code_is_must_like_for_IND') }} 91
                                            )</small>
                                    </label>
                                    <input class="form-control form-control-user phone-input-with-country-picker"
                                        type="tel" id="person-number" value="{{ old('phone') }}"
                                        placeholder="{{ translate('enter_phone_number') }}" required>
                                    <div class="">
                                        <input type="text" class="country-picker-phone-number w-50"
                                            value="{{ old('phone') }}" name="phone" hidden readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4" id="userNameData" style="display: none;">
                                <div class="form-group">
                                    <label class="form-label font-semibold">{{ translate('your_name') }}</label>
                                    <input class="form-control text-align-direction"
                                        value="{{ !empty($customer['f_name']) ? $customer['f_name'] : '' }}{{ !empty($customer['l_name']) ? $customer['l_name'] : '' }}"
                                        type="text" name="person_name" id="person-name"
                                        placeholder="{{ translate('Ex') }}: {{ translate('your_name') }}!"
                                        inputmode="name" required {{ isset($customer['f_name']) ? 'readonly' : '' }}
                                        input-mode="text">
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="row justify-content-end gap-3 mt-3 mx-1">
                        <button type="reset" id="reset"
                            class="btn btn-secondary px-4">{{ translate('reset') }}</button>
                        <button type="submit" class="btn btn--primary px-4">{{ translate('submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



@endsection
<!-- Load jQuery first -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Then other scripts -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ dynamicAsset(path: 'public/assets/back-end/plugins/intl-tel-input/js/intlTelInput.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/country-picker-init.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/products-management.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<!-- jQuery UI CSS -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<!-- jQuery UI JS -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
@push('script')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let today = new Date();
            let yyyy = today.getFullYear();
            let mm = String(today.getMonth() + 1).padStart(2, '0');
            let dd = String(today.getDate() + 1).padStart(2, '0'); // अगले दिन से

            let minDate = `${yyyy}-${mm}-${dd}`;
            let dateInput = document.getElementById("selectDate");
            dateInput.min = minDate;

            // क्लिक होते ही calendar open
            dateInput.addEventListener("click", function() {
                this.showPicker(); // Modern browsers में
            });
        });
    </script>
    <script>
        // Change the sevice
        $(document).ready(function() {
            $('#type').on('change', function() {
                const type = $(this).val();

                $.ajax({
                    url: '{{ route('admin.leads.getServicesByType') }}',
                    method: 'GET',
                    data: {
                        type
                    },
                    beforeSend: function() {
                        $('#service-section').html(
                            '<div class="text-center"><div class="spinner-border" role="status"></div></div>'
                        );
                        $('#package-section').html('');
                    },
                    success: function(response) {
                        $('#service-section').html(response.services_html);
                        $('#package-section').html(response.packages_html ?? '');
                        $('.js-select2-custom').select2({
                            placeholder: "-- Select Service --",
                            allowClear: true,
                            width: '100%'
                        });
                    },
                    error: function() {
                        alert('Something went wrong!');
                    }
                });
            });

            // Optional: load packages only when a service is selected
            $(document).on('change', '.service-select', function() {
                const serviceId = $(this).val();
                const type = $('#type').val();

                // Get schedule from selected option
                const selected = $(this).find(':selected');
                const schedule = selected.data('schedule');

                if (type === 'chadhava') {
                    $('#package-section').html('');
                    $('#bookingDate').show();
                    $('#userData').show();
                    $('#userNameData').show();
                    return; 
                }

                //  हमेशा packages load करो
                $.ajax({
                    url: '{{ route('admin.leads.getPackagesByService') }}',
                    method: 'GET',
                    data: {
                        service_id: serviceId,
                        type: type
                    },
                    beforeSend: function() {
                        $('#package-section').html(
                            '<div class="text-center"><div class="spinner-border" role="status"></div></div>'
                        );
                        $('#bookingDate').hide();
                        $('#userData').hide();
                        $('#userNameData').hide();
                    },
                    success: function(response) {
                        let html = '';

                        //  अगर schedule है तो पहले schedule दिखा दो
                        if (schedule && schedule.length > 0) {
                            html += `<div class="alert alert-info mb-2">
                            <strong>Available Schedule:</strong> ${JSON.stringify(schedule)}
                         </div>`;
                        }

                        //  अब package dropdown जोड़ दो
                        html += response.packages_html ?? '';

                        $('#package-section').html(html);

                        if ($(response.packages_html).find('.package-select option').length >
                            0) {
                            $('#bookingDate').show();
                            $('#userData').show();
                            $('#userNameData').show();
                        }
                    },
                    error: function() {
                        toaster.error('Failed to load packages.');
                        $('#bookingDate').hide();
                        $('#userData').hide();
                        $('#userNameData').hide();
                    }
                });
            });


            $(document).on('change', '.package-select', function() {
                const selected = $(this).find(':selected');
                const hasValue = selected.val() !== '';

                if (hasValue) {
                    $('#bookingDate').show();
                    $('#userData').show();
                    $('#userNameData').show();

                    // Fill hidden inputs
                    $('#package_price').val(selected.data('price') ?? '');
                    $('#package_title').val(selected.data('title') ?? '');
                    $('#package_person').val(selected.data('person') ?? '');
                    $('#package_name').val(selected.data('title') ?? '');

                    // Show selected package visibly
                    $('#selected-package-text').text(
                        `${selected.data('title')} - ₹${selected.data('price')} (${selected.data('person')} Person)`
                    );
                    $('#selected-package-display').show();

                } else {
                    $('#bookingDate').hide();
                    $('#userData').hide();
                    $('#userNameData').hide();
                    $('#selected-package-display').hide();
                }
            });


        });
    </script>
    <script>
        $(document).ready(function() {
            $('#service-select').select2({
                placeholder: "-- Select Service --",
                allowClear: true,
                width: '100%'
            });
        });
    </script>
    <script>
        $('#person-number').blur(function(e) {
            e.preventDefault();
            var code = $('.iti__selected-dial-code').text();
            var mobile = $(this).val();
            var no = code + '' + mobile;
            $.ajax({
                type: "get",
                url: "{{ url('admin/leads/check-customer-exits') }}/" + no,
                success: function(response) {
                    if (response.status == 200) {
                        var name = response.user.f_name + ' ' + response.user.l_name;
                        $('#person-name').val(name);
                        $('#verifyOTP').val(1);
                    } else {
                        $(this).text('Please Wait ...');
                        $(this).prop('disabled', true);
                        $('#send-otp-btn').addClass('d-none');
                        $('#withoutOTP').removeClass('d-none');
                    }
                }
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectEl = document.getElementById('service-select');
            const infoDiv = document.getElementById('service-info');
            const selectedText = document.getElementById('selected-package-text');

            if (selectEl) {
                selectEl.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    if (this.value) {
                        selectedText.textContent = selectedOption.text;
                        infoDiv.style.display = 'block';
                    } else {
                        infoDiv.style.display = 'none';
                        selectedText.textContent = '';
                    }
                });
            }
        });
    </script>
@endpush
