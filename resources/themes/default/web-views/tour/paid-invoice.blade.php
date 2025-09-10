<html>

<head>
    <meta charset="UTF-8">
    <title>{{ ucwords('invoice')}}</title>
    <meta http-equiv="Content-Type" content="text/html;" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/invoice.css') }}">
</head>

<body>

    <?php $companyName = getWebConfig(name: 'company_name'); ?>
    <div class="first">
        <table class="content-position mb-30">
            <tr>
                <th class="p-0 text-left font-size-26px">
                    {{ ucwords('order Invoice')}}
                </th>
                <th class="p-0 text-right">
                    <img height="40" src="{{dynamicStorage(path: 'storage/app/public/company/'.getWebConfig(name: 'company_web_logo'))}}"
                        alt="">
                </th>
            </tr>
        </table>

        <table class="bs-0 mb-30 px-10">
            <tr>
                <th class="content-position-y text-left">
                    <h4 class="text-uppercase mb-1 fz-14">
                        {{ ucwords('Order')}} #{{ $tourOrders['order_id'] }}
                    </h4>
                    <br>
                    <h4 class="text-uppercase mb-1 fz-14">
                        {{ ucwords('Tour Name')}}
                        : {{ ($tourOrders['Tour']['tour_name']??"") }}
                    </h4>
                </th>
                <th class="content-position-y text-right">
                    <h4 class="fz-14">
                        {{ ucwords('date')}} : {{date('d-m-Y h:i a',strtotime($tourOrders['created_at']))}}
                    </h4>
                    <h6>
                        <span class="text-muted text-capitalize">{{ translate('payment_status') }}</span>:
                        <?php if ($tourOrders['amount_status'] == 1) { ?>
                            <span
                                class="text-success text-capitalize">{{ translate('paid') }}{{ $tourOrders['part_payment'] == 'part' ? ' / partially' : '' }}</span>
                        <?php } else { ?>
                            <span
                                class="text-success text-capitalize">{{ translate('unpaid') }}
                                {{ $tourOrders['part_payment'] == 'part' ? ' / partially' : '' }}</span>
                        <?php } ?>
                    </h6>
                </th>
            </tr>
        </table>
    </div>
    <div class="">
        <section>
            <table class="content-position-y fz-12">
                <tr>
                    <td class="font-weight-bold p-1">
                        <table>
                            @if($tourOrders)
                            <tr>
                                <td>
                                    <span class="h2 m-0">{{ucwords('Tour Booking Info')}} </span>
                                    <div class="h4">
                                        <p class="mt-6px mb-0">{{ ucwords('Pickup location')}} : {{($tourOrders['pickup_address']??"")}}</p>
                                        <p class="mt-6px mb-0">{{ ucwords('date')}} : {{date('d-m-Y',strtotime($tourOrders['pickup_date']))}} {{ $tourOrders['pickup_time']}}</p>
                                    </div>
                                    <span class="h2 m-0"> </span>

                                </td>
                            </tr>
                            @endif
                            @if($tourOrders['company'])
                            <tr>
                                <td>
                                    <span class="h2 m-0">{{ucwords('Tour Company')}} </span>
                                    <div class="h4">
                                        <p class="mt-6px mb-0">{{($tourOrders['company']['company_name']??"")}}</p>
                                        <p class="mt-6px mb-0">{{($tourOrders['company']['email']??"")}}</p>
                                    </div>
                                    <span class="h2 m-0"> </span>

                                </td>
                            </tr>
                            @endif
                        </table>
                    </td>

                    <td>
                        <table>
                            <tr>
                                <td class="text-right">
                                    <span class="h2">{{ ucwords('customer info')}} </span>
                                    <div class="h4">
                                        @if($tourOrders['userdata'])
                                        <p class="mt-6px mb-0">{{ ($tourOrders['userdata']['name']??"")}} </p>
                                        <p class="mt-6px mb-0">{{ ($tourOrders['userdata']['phone']??"")}} </p>
                                        @if($tourOrders['userdata']['phone'] != $tourOrders['userdata']['email'])
                                        <p class="mt-6px mb-0">{{ ($tourOrders['userdata']['email']??"")}} </p>
                                        @endif
                                        @else
                                        <p class="mt-6px mb-0">Guest User</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </section>
    </div>
    <br>

    <div>
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
                                    } else {
                                        $tourPackages = [];
                                        $images = getValidImage(path: 'storage/app/public/tour_and_travels/package/', type: 'backend-product');
                                    }
                    ?>
                                    <tr>
                                        <td>
                                            <div class="media align-items-center gap-5">
                                                @if ($val['type'] == 'ex_distance')
                                                <small
                                                    class=" w-50 font-semibold text-center">Ex
                                                    Distance</small>
                                                @elseif($val['type'] == 'route')
                                                <small
                                                    class=" w-50 font-semibold text-center">Route</small>
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
                                                <small class="fs-15 font-semibold">Km:
                                                    {{ $val['qty'] }}</small>
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
                                                <?php if ($val['type'] == 'cab') { ?>
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
                                <td> <span class="fs-15 text-success">{{ translate('included in The Price') }}</span>
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

    <div class="content-position-y">
        <table class="fz-12">
            <tr>
                <th class="text-left width-60">
                    <h4 class="fz-12 mb-1">{{ ucwords('payment details')}}</h4>
                    <h5 class="fz-12 mb-1 font-weight-normal"></h5>
                    <p class="fz-12 font-weight-normal">
                        {{date('d M,Y',strtotime($tourOrders['created_at']))}}
                    </p>
                </th>

                <th class="calc-table">
                    <table>
                        <tbody>

                            <tr>
                                <td class="p-1 text-left"><b>{{ ucwords('sub total')}}</b></td>
                                @if($tourOrders['part_payment'] == 'part')
                                <td class="p-1 text-right">{{ webCurrencyConverter(amount: (($tourOrders['amount']??0) + ($tourOrders['amount']??0) + ($tourOrders['coupon_amount']??0))) }}</td>
                                @else
                                <td class="p-1 text-right">{{ webCurrencyConverter(amount: (($tourOrders['amount']??0) + ($tourOrders['coupon_amount']??0))) }}</td>
                                @endif

                            </tr>
                            <tr>
                                <td class="p-1 text-left"><b>{{ ucwords('coupon discount')}}</b></td>
                                <td class="p-1 text-right">
                                    - {{ webCurrencyConverter(amount: ($tourOrders['coupon_amount']??0)) }}</td>
                            </tr>
                            @if($tourOrders['part_payment'] == 'part')
                            <tr>
                                <td class="border-dashed-top font-weight-bold text-left"><b>{{ ucwords('remaining pay')}}</b></td>
                                <td class="border-dashed-top font-weight-bold text-right">
                                    {{ webCurrencyConverter(amount: ($tourOrders['amount']) ) }}
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td class="border-dashed-top font-weight-bold text-left"><b>{{ ucwords('paid amount')}}</b></td>
                                <td class="border-dashed-top font-weight-bold text-right">
                                    {{ webCurrencyConverter(amount: ($tourOrders['amount']) ) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </th>
            </tr>
        </table>
    </div>
    <br>
    <br>
    <div class="content-position-y">
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
            <table class="table">
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
    <div class="row">
        <section>
            <table>
                <tr>
                    <th class="content-position-y bg-light py-4">
                        <div class="d-flex justify-content-center gap-2">
                            <div class="mb-2">
                                <img height="10" src="{{ theme_asset(path: 'public/assets/front-end/img/icons/telephone.png') }}"
                                    alt="">
                                {{ ucwords('phone')}}
                                : {{ getWebConfig(name: 'company_phone') }}
                            </div>
                            <div class="mb-2">
                                <img height="10" src="{{ theme_asset(path: 'public/assets/front-end/img/icons/email.png') }}" alt="">
                                {{ ucwords('email')}}
                                : {{ getWebConfig(name: 'company_email') }}
                            </div>
                        </div>
                        <div class="mb-2">
                            <img height="10" src="{{ theme_asset(path: 'public/assets/front-end/img/icons/web.png') }}" alt="">
                            {{ ucwords('website')}}
                            : {{url('/')}}
                        </div>
                        <div>
                            {{ ucwords('all copy right reserved © '.date('Y').' ')}} {{($companyName??"") }}
                        </div>
                    </th>
                </tr>
            </table>
        </section>
    </div>

</body>

</html>