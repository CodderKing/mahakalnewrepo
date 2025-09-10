@php use App\Utils\Helpers; @endphp
@extends('layouts.back-end.app')
@section('title', translate('order_Details'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet"
        href="{{ dynamicAsset(path: 'public/assets/back-end/plugins/intl-tel-input/css/intlTelInput.css') }}">
@endpush

@section('content')
    {{-- reject modal --}}
    <div class="modal fade" id="reject-modal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Report Reject</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.counselling.order.report.reject') }}" method="post">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" value="{{ $details['id'] }}">
                        <div class="col-12 mt-2 text-center">
                            <textarea name="counselling_report_reject_reason" id="" rows="3" class="form-control"
                                placeholder="Enter reject reason" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- main page --}}
    <div class="content container-fluid">
        <div class="mb-4">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/all-orders.png') }}" alt="">
                {{ translate('order_Details') }}
            </h2>
        </div>

        <div class="row gy-3" id="printableArea">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap flex-md-nowrap gap-10 justify-content-between mb-4">
                            <div class="d-flex flex-column gap-10">
                                <h4 class="text-capitalize">{{ translate('Order_ID') }} #{{ $details['order_id'] }}</h4>
                                <div class="">
                                    {{ date('d M, Y , h:i A', strtotime($details['created_at'])) }}
                                </div>
                            </div>
                            <div class="text-sm-right flex-grow-1">
                                <div class="d-flex flex-wrap gap-10 justify-content-end">
                                    @if (Helpers::modules_permission_check('Consultation Order', 'Detail', 'print-invoice'))
                                        <a class="btn btn--primary px-4" target="_blank"
                                            href="{{ route('admin.counselling.order.generate.invoice', $details['id']) }}">
                                            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/uil_invoice.svg') }}"
                                                alt="" class="mr-1">
                                            {{ translate('print_Invoice') }}
                                        </a>
                                    @endif
                                </div>
                                <div class="d-flex flex-column gap-2 mt-3">
                                    <div class="order-status d-flex justify-content-sm-end gap-10 text-capitalize">
                                        <span class="title-color">{{ translate('status') }}: </span>
                                        <span
                                            class="badge badge-{{ $details['status'] == 0 ? 'primary' : ($details['status'] == 1 ? 'success' : 'danger') }} font-weight-bold radius-50 d-flex align-items-center py-1 px-2">{{ $details['status'] == 0 ? 'Pending' : ($details['status'] == 1 ? 'Completed' : 'Canceled') }}</span>
                                    </div>

                                    <div class="payment-method d-flex justify-content-sm-end gap-10 text-capitalize">
                                        <span class="title-color">{{ translate('payment_Method') }} :</span>
                                        <strong>{{ translate('online') }}</strong>
                                    </div>

                                    <div class="d-flex justify-content-sm-end gap-10">
                                        <span class="title-color">{{ translate('payment_Status') }}:</span>
                                        <span class="text-success payment-status-span font-weight-bold">
                                            {{ translate('paid') }}
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </div>

                        @if (empty($details['counselling_user']))
                            <div class="">
                                <h5 class="text-danger">Note:- Customer Detail Not Available</h5>
                            </div>
                        @endif

                        <div class="table-responsive datatable-custom">
                            <table
                                class="table fz-12 table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                                <thead class="thead-light thead-50 text-capitalize">
                                    <tr>
                                        <th>{{ translate('name') }}</th>
                                        <th>{{ translate('type') }}</th>
                                        <th>{{ translate('price') }}</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td>
                                            <div class="media align-items-center gap-10">
                                                <img class="avatar avatar-60 rounded"
                                                    src="{{ getValidImage(path: 'storage/app/public/pooja/thumbnail/' . $details['services']['thumbnail'], type: 'backend-product') }}"
                                                    alt="{{ translate('image_Description') }}">
                                                <div>
                                                    <h6 class="title-color">
                                                        {{ substr($details['services']['name'], 0, 20) }}{{ strlen($details['services']['name']) > 10 ? '...' : '' }}
                                                    </h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            {{ $details['services']['category']['name'] }}
                                        </td>
                                        <td>
                                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $details['pay_amount']), currencyCode: getCurrencyCode()) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="card-body ">
                            <table class="calculation-table table table-borderless mb-0">
                                <tbody class="totals">
                                    <tr class="border-top">
                                        <td>
                                            <div class="text-start">
                                                <span class="product-qty">{{ translate('Service_price') }}</span>
                                            </div>
                                        </td>
                                        <td>

                                            <div class="text-end">
                                                <span
                                                    class="fs-15 font-semibold">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $details['pay_amount']), currencyCode: getCurrencyCode()) }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="text-start">
                                                <span class="product-qty">{{ translate('coupon_discount') }}</span>
                                                <br>
                                                @if ($details->coupon_code)
                                                    <span class="text-danger">{{ $details->coupon_code }}</span>
                                                @else
                                                    <span class="text-muted">{{ translate('No Coupon Applied') }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-end">
                                                @if ($details->coupon_amount)
                                                    <span class="fs-15 font-semibold text-danger">
                                                        -
                                                        {{ webCurrencyConverter(amount: $details->coupon_amount) }}
                                                    </span>
                                                @else
                                                    <span
                                                        class="fs-15 text-muted">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: 0.0), currencyCode: getCurrencyCode()) }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="text-start">
                                                <span class="product-qty">
                                                    {{ translate('Amount_Paid_(via_Wallet)') }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-end">
                                                <span class="fs-15 font-semibold text-danger">-
                                                    {{ webCurrencyConverter(amount: $details->wallet_amount) }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="border-top">
                                        <td>
                                            <div class="text-start">
                                                <span class="font-weight-bold">
                                                    <strong>{{ translate('total_Price') }}</strong>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-end">
                                                <span class="font-weight-bold amount">
                                                    {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $details->transection_amount), currencyCode: getCurrencyCode()) }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>




                        {{-- @if (count($details['product_leads']) > 0)
                            <div class="table-responsive datatable-custom mt-4">
                                <table
                                    class="table fz-12 table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                                    <thead class="thead-light thead-50 text-capitalize">
                                        <tr>
                                            <th>{{ translate('dan_name') }}</th>
                                            <th>{{ translate('quantity') }}</th>
                                            <th>{{ translate('price') }}</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($details['product_leads'] as $productLeads)
                                            <tr>
                                                <td>
                                                    <h6 class="title-color">
                                                        {{ substr($productLeads['productsData']['name'], 0, 30) }}{{ strlen($productLeads['productsData']['name']) > 10 ? '...' : '' }}
                                                </td>
                                                <td>
                                                    {{ $productLeads['qty'] }}
                                                </td>
                                                <td>
                                                    {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $productLeads['final_price']), currencyCode: getCurrencyCode()) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif --}}


                    </div>
                </div>
            </div>

            <div class="col-lg-4">

                <div class="card mb-3">
                    <div class="card-body text-capitalize d-flex flex-column gap-4">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <label class="font-weight-bold title-color fz-14">{{ translate('type') }}</label>
                            <select name="type" id="type" class="astrologer-type form-control">
                                <option value="type">Select Type</option>
                                <option value="astrologer">Astrologer</option>
                                <option value="pandit">Pandit</option>
                            </select>
                        </div>
                    </div>
                </div>

                @if (Helpers::modules_permission_check('Consultation Order', 'Detail', 'assign-astrologer'))
                    <div class="card mb-3 astrologer-section d-none">
                        <div class="card-body text-capitalize d-flex flex-column gap-4">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <h4 class="mb-0">
                                    {{ translate(empty($details['pandit_assign']) ? 'assign_Astrologer' : 'Astrologer_information') }}
                                </h4>
                                @if ($details['status'] == 0)
                                    @if (!empty($details['pandit_assign']))
                                        <button class="btn btn-outline-primary btn-sm square-btn" data-toggle="modal"
                                            data-target="#change-astrologer-modal">
                                            <i class="tio-edit"></i>
                                        </button>
                                    @endif
                                @endif
                            </div>
                            @if (empty($details['pandit_assign']))
                                <div class="">
                                    <label class="font-weight-bold title-color fz-14">{{ translate('type') }}</label>
                                    <select name="astrologer_type" id="astrologer-type"
                                        class="astrologer-type form-control">
                                        <option value="in house">In house</option>
                                        <option value="freelancer">Freelancer</option>
                                    </select>

                                    <br>

                                    <div class="" id="in-house">
                                        <label
                                            class="font-weight-bold title-color fz-14">{{ translate('inhouse_Astrologer') }}</label>
                                        <select name="assign_astrologer" id="assign-astrologer"
                                            class="assign-astrologer form-control">
                                            <option value="">Select Astrologer</option>
                                            @if (count($inHouseAstrologers) > 0)
                                                @foreach ($inHouseAstrologers as $inhouse)
                                                    <option value="{{ $inhouse['id'] }}">{{ $inhouse['name'] }}</option>
                                                @endforeach
                                            @else
                                                <option disabled>No Astrologer Found</option>
                                            @endif
                                        </select>
                                    </div>

                                    <div class="" id="freelancer" style="display: none;">
                                        <label
                                            class="font-weight-bold title-color fz-14">{{ translate('freelancer_Astrologer') }}</label>
                                        <select name="assign_astrologer" id="assign-astrologer"
                                            class="assign-astrologer form-control">
                                            <option value="">Select Astrologer</option>
                                            @if (count($freelancerAstrologers) > 0)
                                                @foreach ($freelancerAstrologers as $freelancer)
                                                    <option value="{{ $freelancer['id'] }}">
                                                        {{ $freelancer['name'] . ' ( ₹' . json_decode($freelancer['consultation_charge'], true)[$details['service_id']] . ' )' }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option disabled>No Astrologer Found</option>
                                            @endif
                                        </select>
                                    </div>
                                    <form
                                        action="{{ route('admin.counselling.order.assign.astrologer', [$details['id']]) }}"
                                        method="post" id="assign-astrologer-form">
                                        @csrf
                                        <input type="hidden" name="astrologer_id" id="astrologer-id-val">
                                    </form>
                                </div>
                            @else
                                <div>
                                    @if (!empty($details['astrologer']))
                                        <div class="media flex-wrap gap-3">
                                            <div class="">
                                                <img class="avatar rounded-circle a vatar-70"
                                                    src="{{ getValidImage(path: 'storage/app/public/astrologers/' . $details['astrologer']['image'], type: 'backend-basic') }}"
                                                    alt="{{ translate('Image') }}">
                                            </div>
                                            <div class="media-body d-flex flex-column gap-1">
                                                <span class="title-color"><strong>{{ $details['astrologer']['name'] . ' (' . $details['astrologer']['type'] . ')' }}
                                                    </strong></span>
                                                <span
                                                    class="title-color break-all"><strong>{{ $details['astrologer']['mobile_no'] }}</strong></span>
                                                <span class="title-color break-all"
                                                    style="text-transform: lowercase !important;">{{ $details['astrologer']['email'] }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <p>Astrologer Detail Not Available</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if (Helpers::modules_permission_check('Consultation Order', 'Detail', 'assign-pandit'))
                    <div class="card mb-3 pandit-section d-none">
                        <div class="card-body text-capitalize d-flex flex-column gap-4">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <h4 class="mb-0">
                                    {{ translate(empty($details['pandit_assign']) ? 'assign_Pandit' : 'Pandit_information') }}
                                </h4>
                                @if ($details['status'] == 0)
                                    @if (!empty($details['pandit_assign']))
                                        <button class="btn btn-outline-primary btn-sm square-btn" data-toggle="modal"
                                            data-target="#change-pandit-modal">
                                            <i class="tio-edit"></i>
                                        </button>
                                    @endif
                                @endif
                            </div>
                            @if (empty($details['pandit_assign']))
                                <div class="">
                                    <label class="font-weight-bold title-color fz-14">{{ translate('type') }}</label>
                                    <select name="astrologer_type" id="pandit-type" class="astrologer-type form-control">
                                        <option value="in house">In house</option>
                                        <option value="freelancer">Freelancer</option>
                                    </select>

                                    <br>

                                    <div class="" id="in-house-pandit">
                                        <label
                                            class="font-weight-bold title-color fz-14">{{ translate('inhouse_Pandit') }}</label>
                                        <select name="assign_astrologer" id="assign-pandit"
                                            class="assign-pandit form-control">
                                            <option value="">Select Pandit</option>
                                            @if (count($panditinhouse) > 0)
                                                @foreach ($panditinhouse as $inhouse)
                                                    <option value="{{ $inhouse['id'] }}">{{ $inhouse['name'] }}</option>
                                                @endforeach
                                            @else
                                                <option disabled>No Pandit Found</option>
                                            @endif
                                        </select>
                                    </div>

                                    <div class="" id="freelancer-pandit" style="display: none;">
                                        <label
                                            class="font-weight-bold title-color fz-14">{{ translate('freelancer_Pandit') }}</label>
                                        <select name="assign_astrologer" id="assign-pandit"
                                            class="assign-astrologer form-control">
                                            <option value="">Select Pandit</option>
                                            @if (count($panditfreelancer) > 0)
                                                @foreach ($panditfreelancer as $freelancer)
                                                    <option value="{{ $freelancer['id'] }}">
                                                        {{ $freelancer['name'] . ' ( ₹' . json_decode($freelancer['consultation_charge'], true)[$details['service_id']] . ' )' }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option disabled>No Pandit Found</option>
                                            @endif
                                        </select>
                                    </div>
                                    <form
                                        action="{{ route('admin.counselling.order.assign.astrologer', [$details['id']]) }}"
                                        method="post" id="assign-astrologer-form">
                                        @csrf
                                        <input type="hidden" name="astrologer_id" id="astrologer-id-val">
                                    </form>
                                </div>
                            @else
                                <div>
                                    @if (
                                        !empty($details['astrologer']) &&
                                            !empty($details['astrologer']['primary_skills']) &&
                                            !empty($details['astrologer']['other_skills']))
                                        <div class="media flex-wrap gap-3">
                                            <div class="">
                                                <img class="avatar rounded-circle avatar-70"
                                                    src="{{ getValidImage(path: 'storage/app/public/astrologers/' . $details['astrologer']['image'], type: 'backend-basic') }}"
                                                    alt="{{ translate('Image') }}">
                                            </div>
                                            <div class="media-body d-flex flex-column gap-1">
                                                <span
                                                    class="title-color"><strong>{{ $details['astrologer']['name'] . ' (' . $details['astrologer']['type'] . ')' }}</strong></span>
                                                <span
                                                    class="title-color break-all"><strong>{{ $details['astrologer']['mobile_no'] }}</strong></span>
                                                <span class="title-color break-all"
                                                    style="text-transform: lowercase !important;">
                                                    {{ $details['astrologer']['email'] }}
                                                </span>
                                            </div>
                                        </div>
                                    @else
                                        <div class="">
                                            <label
                                                class="font-weight-bold title-color fz-14">{{ translate('type') }}</label>
                                            <select name="astrologer_type" id="pandit-type"
                                                class="astrologer-type form-control">
                                                <option value="in house">In house</option>
                                                <option value="freelancer">Freelancer</option>
                                            </select>

                                            <br>

                                            <div class="" id="in-house-pandit">
                                                <label
                                                    class="font-weight-bold title-color fz-14">{{ translate('inhouse_Pandit') }}</label>
                                                <select name="assign_astrologer" id="assign-pandit"
                                                    class="assign-pandit form-control">
                                                    <option value="">Select Pandit</option>
                                                    @if (count($panditinhouse) > 0)
                                                        @foreach ($panditinhouse as $inhouse)
                                                            <option value="{{ $inhouse['id'] }}">{{ $inhouse['name'] }}
                                                            </option>
                                                        @endforeach
                                                    @else
                                                        <option disabled>No Pandit Found</option>
                                                    @endif
                                                </select>
                                            </div>

                                            <div class="" id="freelancer-pandit" style="display: none;">
                                                <label
                                                    class="font-weight-bold title-color fz-14">{{ translate('freelancer_Pandit') }}</label>
                                                <select name="assign_astrologer" id="assign-pandit"
                                                    class="assign-astrologer form-control">
                                                    <option value="">Select Pandit</option>
                                                    @if (count($panditfreelancer) > 0)
                                                        @foreach ($panditfreelancer as $freelancer)
                                                            <option value="{{ $freelancer['id'] }}">
                                                                {{ $freelancer['name'] . ' ( ₹' . json_decode($freelancer['consultation_charge'], true)[$details['service_id']] . ' )' }}
                                                            </option>
                                                        @endforeach
                                                    @else
                                                        <option disabled>No Pandit Found</option>
                                                    @endif
                                                </select>
                                            </div>
                                            <form
                                                action="{{ route('admin.counselling.order.assign.astrologer', [$details['id']]) }}"
                                                method="post" id="assign-astrologer-form">
                                                @csrf
                                                <input type="hidden" name="astrologer_id" id="astrologer-id-val">
                                            </form>
                                        </div>
                                    @endif

                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if (Helpers::modules_permission_check('Consultation Order', 'Detail', 'upload-report'))
                    @if (!empty($details['pandit_assign']))
                        <div class="card mb-3">
                            <div class="card-body text-capitalize d-flex flex-column gap-4">
                                <div class="d-flex " style="justify-content: space-between;">
                                    <h4 class="mb-0 text-center">{{ translate('report') }}</h4>
                                    <span
                                        class="badge badge-{{ $details['counselling_report_verified'] == 0 ? 'warning' : ($details['counselling_report_verified'] == 1 ? 'success' : 'danger') }}">
                                        {{ $details['counselling_report_verified'] == 0 ? 'Pending' : ($details['counselling_report_verified'] == 1 ? 'Verified' : 'Rejected') }}
                                    </span>
                                        @if ($details['counselling_report_verified'] == 2)
                                        <span class="input-label-secondary cursor-pointer" data-toggle="tooltip" title="{{$details['counselling_report_reject_reason']}}">
                                            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                        </span>
                                        @endif
                                </div>

                                @if (!empty($details['counselling_report']))
                                    <div class="text-center">
                                        @php
                                            $type = explode('.', $details['counselling_report']);
                                        @endphp
                                        @if ($type[1] == 'jpg' || $type[1] == 'jpeg' || $type[1] == 'png')
                                            <a href="{{ asset('storage/app/public/consultation-order-report/' . $details['counselling_report']) }}"
                                                target="_blank"><img
                                                    src="{{ asset('storage/app/public/consultation-order-report/' . $details['counselling_report']) }}"
                                                    alt="" width="100"></a>
                                        @elseif ($type[1] == 'doc' || $type[1] == 'docx')
                                            <a href="{{ asset('storage/app/public/consultation-order-report/' . $details['counselling_report']) }}"
                                                target="_blank"><img
                                                    src="{{ asset('public/assets/back-end/img/doc-icon/word.png') }}"
                                                    alt="" width="100"></a>
                                        @elseif ($type[1] == 'Pdf' || $type[1] == 'pdf')
                                            <a href="{{ asset('storage/app/public/consultation-order-report/' . $details['counselling_report']) }}"
                                                target="_blank"><img
                                                    src="{{ asset('public/assets/back-end/img/doc-icon/pdf.png') }}"
                                                    alt="" width="100"></a>
                                        @endif

                                        <div class="d-flex justify-content-center mt-3">
                                            @if ($details['counselling_report_verified'] == 0)
                                                <form
                                                    action="{{ route('admin.counselling.order.report.verify', [$details['id']]) }}"
                                                    method="post">
                                                    @csrf
                                                    <div class="col-12 mt-2 text-center">
                                                        <button type="submit" class="btn btn-success">Verify</button>
                                                    </div>
                                                </form>

                                                <div class="mt-2 text-center">
                                                    <button type="button" class="btn btn-danger" data-toggle="modal"
                                                        data-target="#reject-modal">
                                                        Reject
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if (empty($details['counselling_report']) || $details['counselling_report_verified'] == 2)
                                    <div class="">
                                        <form action="{{ route('admin.counselling.order.report', [$details['id']]) }}"
                                            method="post" enctype="multipart/form-data">
                                            @csrf
                                            <div class="form-group">
                                                <label
                                                    class="font-weight-bold title-color fz-14">{{ translate('upload_file') }}</label>
                                                <input type="file" class="form-control" name="report"
                                                    accept=".jpg, .jpeg, .png, .pdf, .doc, .docx" required>

                                            </div>
                                            <div class="col-12 text-center">
                                                <button type="submit" class="btn btn-primary">Upload</button>
                                            </div>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif

                @if (Helpers::modules_permission_check('Consultation Order', 'Detail', 'order-status'))
                    <div class="card mb-3">
                        <div class="card-body text-capitalize d-flex flex-column gap-4">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <h4 class="mb-0 text-center">{{ translate('order_status') }}</h4>
                            </div>
                            @if ($details['status'] == 0)
                                <div class="">
                                    <label
                                        class="font-weight-bold title-color fz-14">{{ translate('change_order_status') }}</label>
                                    <select name="order_status" id="order_status" class="order-status form-control"
                                        data-id="{{ $details['id'] }}"
                                        {{ empty($details['pandit_assign']) ? 'disabled' : '' }}>
                                        <option value="0" {{ $details['status'] == 0 ? 'selected' : '' }}>
                                            {{ translate('pending') }}</option>
                                        @if ($details['counselling_report_verified'] == 1)
                                            <option value="1" {{ $details['status'] == 1 ? 'selected' : '' }}>
                                                {{ translate('complete') }}</option>
                                        @endif
                                        <option value="2" {{ $details['status'] == 2 ? 'selected' : '' }}>
                                            {{ translate('canceled') }} </option>
                                    </select>
                                    <form action="{{ route('admin.counselling.order.status', [$details['id']]) }}"
                                        method="post" id="order-status-form">
                                        @csrf
                                        <input type="hidden" name="order_status" id="order-status-val">
                                    </form>
                                </div>
                            @else
                                <div class="text-center">
                                    <span class="badge badge-{{ $details['status'] == 1 ? 'success' : 'danger' }}"
                                        style="font-size: 18px;">{{ $details['status'] == 1 ? 'Completed' : 'Canceled' }}</span>
                                </div>
                                @if ($details['status'] == 2)
                                    <p>Reason : {{ $details['order_canceled_reason'] }}</p>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif

                @if (!empty($details['customers']))
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex gap-2 align-items-center justify-content-between mb-4">
                                <h4 class="d-flex gap-2">
                                    <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/vendor-information.png') }}"
                                        alt="">
                                    {{ translate('customer_information') }}
                                </h4>
                            </div>
                            <div class="media flex-wrap gap-3">
                                <div class="">
                                    <img class="avatar rounded-circle avatar-70"
                                        src="{{ getValidImage(path: 'storage/app/public/profile/' . $details['customers']['image'], type: 'backend-basic') }}"
                                        alt="{{ translate('Image') }}">
                                </div>
                                <div class="media-body d-flex flex-column align-self-center gap-1">
                                    <span class="title-color"><strong>{{ $details['customers']['f_name'] . ' ' . $details['customers']['l_name'] }}
                                        </strong></span>
                                    <span
                                        class="title-color break-all"><strong>{{ $details['customers']['phone'] }}</strong></span>
                                    @if (str_contains($details['customers']['email'], '.com'))
                                        <span
                                            class="title-color break-all"><strong>{{ $details['customers']['email'] }}</strong></span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex gap-2 align-items-center justify-content-between mb-4">
                                <h4 class="d-flex gap-2">
                                    <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/vendor-information.png') }}"
                                        alt="">
                                    {{ translate('counselling_user') }}
                                </h4>
                            </div>
                            <div class="mt-3">
                                <p class="title-color break-all"><strong>Name:</strong>
                                    {{ !empty($details['counselling_user']['name']) ? $details['counselling_user']['name'] : 'NA' }}
                                </p>
                                <p class="title-color break-all"><strong>Gender:</strong>
                                    {{ !empty($details['counselling_user']['gender']) ? ucfirst($details['counselling_user']['gender']) : 'NA' }}
                                </p>
                                <p class="title-color break-all"><strong>DOB:</strong>
                                    {{ !empty($details['counselling_user']['dob']) ? $details['counselling_user']['dob'] : 'NA' }}
                                </p>
                                <p class="title-color break-all"><strong>Birth Time:</strong>
                                    {{ !empty($details['counselling_user']['time']) ? $details['counselling_user']['time'] : 'NA' }}
                                </p>
                                <p class="title-color break-all"><strong>Country:</strong>
                                    {{ !empty($details['counselling_user']['country']) ? $details['counselling_user']['country'] : 'NA' }}
                                </p>
                                <p class="title-color break-all"><strong>City:</strong>
                                    {{ !empty($details['counselling_user']['city']) ? $details['counselling_user']['city'] : 'NA' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    </div>

    {{-- change-astrologer-modal --}}
    <div class="modal fade" id="change-astrologer-modal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId"
        aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Astrologer</h5>
                </div>
                <div class="modal-body">
                    <div class="">
                        <label class="font-weight-bold title-color fz-14">{{ translate('type') }}</label>
                        <select id="astrologer-type-change" class="form-control">
                            <option value="in house">In house</option>
                            <option value="freelancer">Freelancer</option>
                        </select>
                        <br>
                        <div class="" id="in-house-change">
                            <label
                                class="font-weight-bold title-color fz-14">{{ translate('inhouse_Astrologer') }}</label>
                            <select id="assign-astrologer-change" class="assign-astrologer-change form-control">
                                <option value="">Select Astrologer</option>
                                @if (count($inHouseAstrologers) > 0)
                                    @foreach ($inHouseAstrologers as $inhouse)
                                        <option value="{{ $inhouse['id'] }}">{{ $inhouse['name'] }}</option>
                                    @endforeach
                                @else
                                    <option disabled>No Astrologer Found</option>
                                @endif
                            </select>
                        </div>

                        <div class="" id="freelancer-change" style="display: none;">
                            <label
                                class="font-weight-bold title-color fz-14">{{ translate('freelancer_Astrologer') }}</label>
                            <select id="assign-astrologer-change" class="assign-astrologer-change form-control">
                                <option value="">Select Astrologer</option>
                                @if (count($freelancerAstrologers) > 0)
                                    @foreach ($freelancerAstrologers as $freelancer)
                                        <option value="{{ $freelancer['id'] }}">
                                            {{ $freelancer['name'] . ' ( ₹' . json_decode($freelancer['consultation_charge'], true)[$details['service_id']] . ' )' }}
                                        </option>
                                    @endforeach
                                @else
                                    <option disabled>No Astrologer Found</option>
                                @endif
                            </select>
                        </div>
                        <form action="{{ route('admin.counselling.order.assign.astrologer', [$details['id']]) }}"
                            method="post" id="change-astrologer-form">
                            @csrf
                            <input type="hidden" name="astrologer_id" id="change-astrologer-id-val">
                        </form>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    {{-- <button type="submit" class="btn btn-primary">Change</button> --}}
                </div>
            </div>
        </div>
    </div>

    {{-- change-pandit-modal --}}
    <div class="modal fade" id="change-pandit-modal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId"
        aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Pandit</h5>
                </div>
                <div class="modal-body">
                    <div class="">
                        <label class="font-weight-bold title-color fz-14">{{ translate('type') }}</label>
                        <select id="pandit-type-change" class="form-control">
                            <option value="in house">In house</option>
                            <option value="freelancer">Freelancer</option>
                        </select>
                        <br>
                        <div class="" id="in-house-change-pandit">
                            <label class="font-weight-bold title-color fz-14">{{ translate('inhouse_Pandit') }}</label>
                            <select id="assign-pandit-change" class="assign-pandit-change form-control">
                                <option value="">Select Pandit</option>
                                @if (count($panditinhouse) > 0)
                                    @foreach ($panditinhouse as $inhouse)
                                        <option value="{{ $inhouse['id'] }}">{{ $inhouse['name'] }}</option>
                                    @endforeach
                                @else
                                    <option disabled>No Pandit Found</option>
                                @endif
                            </select>
                        </div>

                        <div class="" id="freelancer-change-pandit" style="display: none;">
                            <label
                                class="font-weight-bold title-color fz-14">{{ translate('freelancer_Pandit') }}</label>
                            <select id="assign-pandit-change" class="assign-pandit-change form-control">
                                <option value="">Select Pandit</option>
                                @if (count($panditfreelancer) > 0)
                                    @foreach ($panditfreelancer as $freelancer)
                                        <option value="{{ $freelancer['id'] }}">
                                            {{ $freelancer['name'] . ' ( ₹' . json_decode($freelancer['consultation_charge'], true)[$details['service_id']] . ' )' }}
                                        </option>
                                    @endforeach
                                @else
                                    <option disabled>No Pandit Found</option>
                                @endif
                            </select>
                        </div>
                        <form action="{{ route('admin.counselling.order.assign.astrologer', [$details['id']]) }}"
                            method="post" id="change-pandit-form">
                            @csrf
                            <input type="hidden" name="astrologer_id" id="change-astrologer-id-val">
                        </form>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    {{-- <button type="submit" class="btn btn-primary">Change</button> --}}
                </div>
            </div>
        </div>
    </div>

    {{-- order-cancel-modal --}}
    <div class="modal fade" id="order-cancel-modal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId"
        aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Order</h5>
                </div>
                <form action="{{ route('admin.counselling.order.status', [$details['id']]) }}" method="post">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="order_status" id="order-cancel-status">
                        <textarea name="cancel_reason" cols="5" class="form-control" placeholder="Enter cancel reason" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    {{-- <script
        src="https://maps.googleapis.com/maps/api/js?key={{ getWebConfig('map_api_key') }}&callback=map_callback_fucntion&libraries=places&v=3.49"
        defer></script> --}}
    {{-- <script src="{{ dynamicAsset(path: 'public/assets/back-end/plugins/intl-tel-input/js/intlTelInput.js') }}"></script> --}}
    {{-- <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/country-picker-init.js') }}"></script> --}}
    {{-- <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/order.js') }}"></script> --}}

    {{-- status change --}}
    <script>
        $('.order-status').on('change', function() {
            var orderStatus = $(this).val();
            $('#order-status-val').val(orderStatus);
            if (orderStatus == 1) {
                Swal.fire({
                    title: 'Are You Sure To change status',
                    type: 'success',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.value) {
                        $('#order-status-form').submit();
                    }
                });
            } else if (orderStatus == 2) {
                $('#order-cancel-status').val(orderStatus);
                $('#order-cancel-modal').modal('show');
            }
        });
    </script>

    {{-- astrologer assign --}}
    <script>
        $('.assign-astrologer').on('change', function() {
            var astrologerId = $(this).val();
            $('#astrologer-id-val').val(astrologerId);
            if (astrologerId != "") {
                Swal.fire({
                    title: 'Are You Sure To Assign Astrologer',
                    type: 'success',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.value) {
                        $('#assign-astrologer-form').submit();
                    }
                });
            }
        });
    </script>

    {{-- pandit assign --}}
    <script>
        $('.assign-pandit').on('change', function() {
            var astrologerId = $(this).val();
            $('#astrologer-id-val').val(astrologerId);
            if (astrologerId != "") {
                Swal.fire({
                    title: 'Are You Sure To Assign Pandit',
                    type: 'success',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.value) {
                        $('#assign-astrologer-form').submit();
                    }
                });
            }
        });
    </script>

    {{-- change astrologer modal --}}
    <script>
        $('.assign-astrologer-change').on('change', function() {
            var astrologerId = $(this).val();
            $('#change-astrologer-id-val').val(astrologerId);
            Swal.fire({
                title: 'Are You Sure To Change Astrologer',
                type: 'success',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $('#change-astrologer-form').submit();
                }
            });
        });
    </script>



    {{-- change pandit modal --}}
    <script>
        $('.assign-pandit-change').on('change', function() {
            var astrologerId = $(this).val();
            $('#change-astrologer-id-val').val(astrologerId);
            Swal.fire({
                title: 'Are You Sure To Change Pandit',
                type: 'success',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $('#change-pandit-form').submit();
                }
            });
        });
    </script>



    {{-- change astrologer type --}}
    <script>
        $('#astrologer-type').change(function(e) {
            e.preventDefault();

            var type = $(this).val();
            if (type == 'in house') {
                $('#in-house').show();
                $('#freelancer').hide();
            } else if (type == 'freelancer') {
                $('#in-house').hide();
                $('#freelancer').show();
            }
        });

        $('#astrologer-type-change').change(function(e) {
            e.preventDefault();

            var type = $(this).val();
            if (type == 'in house') {
                $('#in-house-change').show();
                $('#freelancer-change').hide();
            } else if (type == 'freelancer') {
                $('#in-house-change').hide();
                $('#freelancer-change').show();
            }
        });
    </script>

    {{-- change pandit type --}}
    <script>
        $('#pandit-type').change(function(e) {
            e.preventDefault();

            var type = $(this).val();
            if (type == 'in house') {
                $('#in-house-pandit').show();
                $('#freelancer-pandit').hide();
            } else if (type == 'freelancer') {
                $('#in-house-pandit').hide();
                $('#freelancer-pandit').show();
            }
        });

        $('#pandit-type-change').change(function(e) {
            e.preventDefault();

            var type = $(this).val();
            if (type == 'in house') {
                $('#in-house-change-pandit').show();
                $('#freelancer-change-pandit').hide();
            } else if (type == 'freelancer') {
                $('#in-house-change-pandit').hide();
                $('#freelancer-change-pandit').show();
            }
        });
    </script>


    <script>
        document.getElementById('type').addEventListener('change', function() {
            var selectedValue = this.value;

            document.querySelector('.astrologer-section').classList.add('d-none');
            document.querySelector('.pandit-section').classList.add('d-none');

            if (selectedValue === 'astrologer') {
                document.querySelector('.astrologer-section').classList.remove('d-none');
            } else if (selectedValue === 'pandit') {
                document.querySelector('.pandit-section').classList.remove('d-none');
            }
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let typeSelect = document.getElementById("type");

            let savedType = localStorage.getItem("selectedType");
            if (savedType) {
                typeSelect.value = savedType;
                toggleSections(savedType);
            }

            typeSelect.addEventListener("change", function() {
                let selectedType = this.value;
                localStorage.setItem("selectedType", selectedType);
                toggleSections(selectedType);
            });

            function toggleSections(type) {
                document.querySelector(".astrologer-section").classList.toggle("d-none", type !== "astrologer");
                document.querySelector(".pandit-section").classList.toggle("d-none", type !== "pandit");
            }
        });
    </script>
@endpush
