@extends('layouts.back-end.app-tour')
@section('title', translate('pickup'))
@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')

<div class="row">
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/withdraw-icon.png')}}" alt="">
                {{translate('pickup_order')}}
            </h2>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="px-3 py-4">
                        <div class="row align-items-center">
                            <div class="col-lg-4">
                            </div>
                            <div class="col-lg-8 mt-3 mt-lg-0 d-flex gap-3 justify-content-lg-end">
                            </div>
                        </div>
                    </div>
                    <div id="status-wise-view">
                        <div class="table-responsive">
                            <table id="datatable"
                                class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                                <thead class="thead-light thead-50 text-capitalize">
                                    <tr>
                                        <th>{{translate('SL')}}</th>
                                        <th>{{translate('Order_Id')}}</th>
                                        <th>{{translate('customer_info')}}</th>
                                        <th>{{translate('tour_info') }}</th>
                                        <th>{{translate('TXN_ID')}}</th>
                                        <th>{{translate('amount')}}</th>
                                        <th class="text-center">{{translate('final_amount')}}</th>
                                        <th class="text-center">{{translate('option')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(!empty($pickup_order) && count($pickup_order) > 0)
                                    @foreach($pickup_order as $key=>$orders)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><span class="font-weight-bold">{{ ($orders['order_id']??"") }}</span></td>
                                        <td>
                                            <div>
                                                <small>{{ ($orders['userData']['name']??"") }}</small><br>
                                                <small>{{ ($orders['userData']['phone']??"") }}</small><br>
                                                <small>{{ date('d M,Y h:i A',strtotime($orders['created_at']??"")) }}</small><br>
                                                <a class="btn btn-sm btn-outline-info" onclick="$('.modelopen_{{$key}}').modal()">view package</a><br>

                                                <div class="modal modelopen_{{$key}}" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">{{($orders['Tour']['tour_name']??'')}}</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-12">
                                                                        <table class="table">
                                                                            <thead>
                                                                                <tr>
                                                                                    <td>Name</td>
                                                                                    <td>qty</td>
                                                                                    <td>price</td>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @php($ex_charges = 0)
                                                                                @php($total_off_price = 0)
                                                                                @php($assign_cabs = 0)
                                                                                @if(!empty($orders['booking_package']) && json_decode($orders['booking_package'],true))
                                                                                @foreach(json_decode($orders['booking_package'],true) as $val)

                                                                                @if($val['type'] == 'ex_distance')
                                                                                @php($ex_charges = $val['price']??0)
                                                                                @elseif($val['type'] == 'cab')
                                                                                @php($assign_cabs = $val['id']??0)

                                                                                @endif

                                                                                @endforeach
                                                                                @endif

                                                                                @if(!empty($orders['booking_package']) && json_decode($orders['booking_package'],true))
                                                                                @foreach(json_decode($orders['booking_package'],true) as $val)
                                                                                @if($orders['use_date'] == 0 || (($val['type'] == 'cab' || $val['type'] == 'per_head' || $val['type'] == 'tax' || $val['type'] == 'cgst' || $val['type'] == 'sgst') && $orders['use_date'] == 1) || ($val['type'] != 'ex_distance' && $orders['use_date'] == 2) || ($val['type'] != 'ex_distance' && $orders['use_date'] == 3) || ($val['type'] != 'ex_distance' && $orders['use_date'] == 4))
                                                                                <tr>
                                                                                    <?php
                                                                                    if ($val['type'] == 'cab') {
                                                                                        $tourPackages = \App\Models\TourCab::where('id', ($val['id']??''))->first();
                                                                                        $images = getValidImage(path: 'storage/app/public/tour_and_travels/cab/' . ($tourPackages['image']??''), type: 'backend-product');
                                                                                    } elseif ($val['type'] == 'other' || $val['type'] == 'hotel' || $val['type'] == 'foods' || $val['type'] == 'food') {
                                                                                        $tourPackages = \App\Models\TourPackage::where('id', ($val['id']??''))->first();
                                                                                        $images = getValidImage(path: 'storage/app/public/tour_and_travels/package/' . ($tourPackages['image']??''), type: 'backend-product');
                                                                                    } else {
                                                                                        $tourPackages = [];
                                                                                    }
                                                                                    ?>
                                                                                    <td>
                                                                                        @if($val['type'] == 'ex_distance')
                                                                                        <span class="fs-15 font-semibold">Ex Distance</span>
                                                                                        @elseif($val['type'] == 'route')
                                                                                        <span class="fs-15 font-semibold">Route</span>
                                                                                        @elseif($val['type'] == 'per_head')
                                                                                        <span class="fs-15 font-semibold">Per Head</span>
                                                                                         @elseif($val['type'] == 'transport')
                                                                                        <span class="fs-15 font-semibold">Ex Transport</span>
                                                                                        @elseif($val['type'] == 'tax' || $val['type'] == 'cgst' || $val['type'] == 'sgst')
                                                                                        <span class="fs-15 font-semibold">{{ strtoupper($val['type']) }}</span>
                                                                                        @else
                                                                                        <div class="media align-items-center">
                                                                                            <img class="d-block rounded" src="{{ $images }}" alt="{{ translate('image_Description') }}" style="width: 80px;height: 72px;">
                                                                                            <div class="ml-1">
                                                                                                <small class="title-color" data-title="{{($tourPackages['name']??'')}}" role="tooltip" data-toggle="tooltip">
                                                                                                    {{ ($tourPackages['name']??"")}} <br>
                                                                                                    @if(!empty($val['seats']??""))
                                                                                                    {{ ($val['seats']??"")}} {{(($val['type'] == 'cab')?"seats":"people")}}
                                                                                                    @endif
                                                                                                </small>
                                                                                            </div>
                                                                                        </div>
                                                                                        @endif
                                                                                    </td>
                                                                                    <td>
                                                                                        @if($val['type'] == 'ex_distance')
                                                                                        <span class="fs-15 font-semibold">{{ ($val['qty']) }} Km</span>
                                                                                        @elseif($val['type'] == 'route')
                                                                                        <span class="fs-15 font-semibold"></span>
                                                                                        @elseif($val['type'] == 'tax' || $val['type'] == 'cgst' || $val['type'] == 'sgst')
                                                                                        <span class="fs-15 font-semibold"></span>
                                                                                        @else
                                                                                        <span class="fs-15 font-semibold">{{ ($val['qty']) }}</span>
                                                                                        @endif
                                                                                    </td>
                                                                                    <td>
                                                                                        @if($val['type'] == 'cab')
                                                                                        <span class="fs-15 font-semibold">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: (($val['price']??0) - ($ex_charges??0)) ), currencyCode: getCurrencyCode()) }}</span>
                                                                                        @php($total_off_price += (($val['price']??0) - ($ex_charges??0)))
                                                                                        @elseif($val['type'] == 'route')
                                                                                        <span class="fs-15 font-semibold">{{ ucwords(str_replace('_',' ',$val['price']))}}</span>
                                                                                        @else
                                                                                        @php($total_off_price += ($val['price']??0))
                                                                                        <span class="fs-15 font-semibold">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($val['price']) ), currencyCode: getCurrencyCode()) }}</span>
                                                                                        @endif
                                                                                    </td>
                                                                                </tr>
                                                                                @endif
                                                                                @endforeach
                                                                                @endif
                                                                            </tbody>
                                                                            <tfoot>
                                                                                <th></th>
                                                                                <th></th>
                                                                                <th>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($total_off_price??0) ), currencyCode: getCurrencyCode()) }}</th>
                                                                            </tfoot>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <small>{{ date('d M,Y',strtotime($orders['pickup_date']??"")) }} {{ ($orders['pickup_time']??"") }}</small>
                                                <p class="font-weight-bold mb-1" data-title="{{($orders['Tour']['tour_name']??'')}}" role='tooltip' data-toggle='tooltip'>{{ Str::limit(($orders['Tour']['tour_name']??""),20) }}</p>
                                                <span class="font-weight-bold mb-1 text-warning">
                                                    <?php $package_bookings = json_decode($orders['booking_package'], true);
                                                    if (is_array($package_bookings)) {
                                                        $types = array_column($package_bookings, 'type');
                                                        if (in_array('per_head', $types)) {
                                                            echo "Per Head";
                                                        } else {
                                                            echo "Cab";
                                                        }
                                                    }
                                                    ?>
                                                </span><br>
                                                <small class="btn btn-sm btn-warning text-white">{{ (($orders['part_payment'] == 'full')?$orders['part_payment']:'partially') }}</small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <p data-title="{{ ($orders['transaction_id']) }}" role='tooltip' data-toggle='tooltip'> {{ Str::limit(($orders['transaction_id']),20) }}</p>
                                        </td>
                                        <td>
                                            <div class='row' style="width: 248px;">
                                                <div class="col-6">{{ translate('amount') }}</div>
                                                <div class="col-6">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['amount'] + $orders['coupon_amount'])), currencyCode: getCurrencyCode()) }}</div>
                                                <div class="col-6">{{ translate('coupon_amount') }}</div>
                                                <div class="col-6"> {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['coupon_amount'])), currencyCode: getCurrencyCode()) }}</div>
                                                <div class="col-6">{{ translate('gst_amount') }}</div>
                                                <div class="col-6">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['gst_amount'])), currencyCode: getCurrencyCode()) }}</div>
                                                <div class="col-6">{{ translate('admin_commission') }}</div>
                                                <div class="col-6">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['admin_commission'])), currencyCode: getCurrencyCode()) }}</div>
                                            </div>
                                        </td>
                                        <td class="text-center"> {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ($orders['final_amount'])), currencyCode: getCurrencyCode()) }}</td>

                                        <td class="text-center">
                                            <a class="btn btn-sm btn-info" href="{{ route('tour-vendor.order.details',[$orders['id']])}}"><i class="tio-invisible"></i></a>
                                            @if($orders['traveller_cab_id'] != 0)
                                            <a class="btn btn-sm btn-info" data-toggle="tooltip" data-title="Drop OTP" onclick="otpSendAndValidate(`{{$orders['id']}}`)"><i class="tio-key"></i></a>
                                            @endif
                                            @if($orders['advance_withdrawal_amount'] == 0)
                                            <a class="btn btn-sm btn-info" title="Sum Amount Withdrawal" data-toggle="tooltip" onclick="withdrowal_models(`{{auth('tour')->user()->relation_id}}`,`{{$orders['id']}}`)"><i class="tio-saving_outlined">saving_outlined</i></a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endif
                                </tbody>
                                @if(!empty($pickup_order) && count($pickup_order) > 0)
                                <tfoot>
                                    <tr>
                                        <td colspan='6'></td>
                                        <td class="text-center">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: ( \App\Models\TourOrder::where('status',1)->where('pickup_status',1)->where('drop_status',0)->where('cab_assign',auth('tour')->user()->relation_id)->sum('final_amount')) ), currencyCode: getCurrencyCode()) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                            @if(count($pickup_order)==0)
                            <div class="text-center p-4">
                                <img class="mb-3 w-160"
                                    src="{{dynamicAsset(path: 'public/assets/back-end/svg/illustrations/sorry.svg')}}"
                                    alt="{{translate('image_description')}}">
                                <p class="mb-0">{{translate('no_data_to_show')}}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="table-responsive mt-4">
                        <div class="px-4 d-flex justify-content-center justify-content-md-end">
                            {{ $pickup_order->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade modal-center withdrowal-models" role="dialog" aria-label="modal order">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="close" data-dismiss="modal" aria-label="close"><i class="tio-clear" aria-hidden="true"></i></button>
                <h4 class="modal-title">Withdrawal Request Amount</h4>
                <form action="{{ route('tour-vendor.withdraw.add-request-admin-send')}}" method="post">
                    @csrf
                    <div class="row mt-2">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bolder">Holder Name</label>
                            <input type="text" name="holder_name" class="form-control holder_name_val">
                            <input type="hidden" name="ex_id" class="form-control order_id_val">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bolder">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control bank_name_val">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bolder">IFSC Code</label>
                            <input type="text" name="ifsc_code" class="form-control IFSC_code_val">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bolder">Account Number</label>
                            <input type="text" name="account_number" class="form-control account_number_val">
                            <input type="hidden" name="wallet_amount" class="form-control withdrawal_total_amounts">

                        </div>
                        <div class="col-12 text-center">
                            <hr>
                            <label class="font-weight-bolder">Or</label>
                            <hr>
                        </div>

                        <div class="col-md-12 form-group">
                            <label class="font-weight-bolder">URI</label>
                            <input type="text" name="upi_code" class="form-control" placeholder="abc@okhdfc">
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="font-weight-bolder w-100">Withdrawal Amount</label>
                            <span class="font-weight-bolder withdrawal_total_reqs h3" data-amount="0"></span>
                        </div>
                        <div class="col-md-6 form-group">
                            <input type="text" name="req_amount" min="" max="" class="form-control req_amount_place_show" placeholder="" onkeyup="validateMinMax(this)">
                            <span class="text-danger min-max-error-show"></span>
                        </div>
                        <div class="col-md-12 form-group text-end">
                            <input type="submit" class="btn btn-primary">
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/product-add-update.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/js/ckeditor.js') }}"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
    function otpSendAndValidate(order_id) {
        let otp = '';
        Swal.fire({
            title: 'Validate OTP',
            html: `
            <div style="text-align: center;">
                <button id="sendOtpBtn" class="swal2-confirm swal2-styled">Send OTP</button>
                <div style="margin-top: 21px;">
                    <label for="otpInput" style="display: block; margin-bottom: 5px;">Enter OTP</label>
                    <input type="text" id="otpInput" class="swal2-input" placeholder="Enter OTP" disabled>
                </div>
                
                <button id="validateOtpBtn" class="swal2-confirm swal2-styled" disabled style="margin-top: 10px;">Validate OTP</button>
            </div>
        `,
            showCancelButton: false,
            showConfirmButton: false,
            didOpen: () => {
                document.getElementById('sendOtpBtn').addEventListener('click', () => {
                    fetch('/api/v1/tour/cab-send-otp', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                'type': "two",
                                "order_id": order_id,
                                'cab_assign': "{{ auth('tour')->user()->relation_id}}"
                            }), // Replace with the actual phone number
                        })
                        .then((response) => response.json())
                        .then((data) => {
                            toastr.success('OTP sent successfully.', 'Success!');
                            // Swal.fire('Success!', 'OTP sent successfully.', 'success');
                            document.getElementById('otpInput').disabled = false; // Enable OTP input
                            document.getElementById('validateOtpBtn').disabled = false; // Enable Validate button
                        })
                        .catch((error) => {
                            // Swal.fire('Error!', 'Failed to send OTP.', 'error');
                            toastr.error('Failed to send OTP.', 'Error!');
                        });
                });

                // Add event listener for Validate OTP button
                document.getElementById('validateOtpBtn').addEventListener('click', () => {
                    otp = document.getElementById('otpInput').value; // Get the entered OTP
                    if (!otp) {
                        toastr.error('Please enter the OTP.', 'Error!');
                        // Swal.fire('Error!', 'Please enter the OTP.', 'error');
                        return;
                    }

                    fetch('/api/v1/tour/cab-otp-verify', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                'type': "two",
                                "order_id": order_id,
                                'cab_assign': "{{ auth('tour')->user()->relation_id}}",
                                otp: otp
                            }), // Send the OTP for validation
                        })
                        .then((response) => response.json())
                        .then((data) => {
                            console.log(data);
                            if (data.status == 1) {
                                toastr.success('OTP validated successfully.', 'Success!');
                                window.location.href = ``;
                                // Swal.fire('Success!', 'OTP validated successfully.', 'success');
                            } else {
                                toastr.error('Invalid OTP. Please try again.', 'Error!');
                                // Swal.fire('Error!', 'Invalid OTP. Please try again.', 'error');
                            }
                        })
                        .catch((error) => {
                            toastr.error('Failed to validate OTP.', 'Error!');
                            // Swal.fire('Error!', 'Failed to validate OTP.', 'error');
                        });
                });
            },
        });
    }
</script>

<script>
    function withdrowal_models(id, order_id) {
        $.ajax({
            url: "{{ route('tour-vendor.withdraw.get-vendor-data')}}",
            data: {
                id,
                tour_order_id: order_id,
                _token: '{{ csrf_token() }}'
            },
            dataType: "json",
            type: "post",
            success: function(data) {
                var status = data.success;
                if (status == 1) {
                    toastr.success(data.message);
                    var bank = data.bank_info;
                    $('.withdrowal-models').modal('show');
                    $(".order_id_val").val(order_id);
                    $(".holder_name_val").val(bank.bank_holder_name);
                    $(".bank_name_val").val(bank.bank_name);
                    $(".IFSC_code_val").val(bank.ifsc_code);
                    $(".account_number_val").val(bank.account_number);
                    $(".withdrawal_total_reqs").text((data.amount).toLocaleString("en-US", {
                        style: "currency",
                        currency: "{{ getCurrencyCode() }}"
                    }));
                    $(".withdrawal_total_reqs").data('amount', data.amount);
                    $(".withdrawal_total_amounts").val(data.amount);
                    $(".req_amount_place_show").attr("placeholder", `Total Request Amount : ${(data.amount).toLocaleString("en-US", { style: "currency", currency: "{{ getCurrencyCode() }}"})}`);
                    $(".req_amount_place_show").attr("min", 10);
                    $(".req_amount_place_show").attr("max", data.amount);
                } else {
                    toastr.error(data.message);
                }
            }
        })
    }
</script>
@endpush