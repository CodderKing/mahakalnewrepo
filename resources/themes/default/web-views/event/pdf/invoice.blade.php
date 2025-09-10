<html>

<head>
    <meta charset="UTF-8">
    <title>{{ ucwords('invoice')}}</title>
    <meta http-equiv="Content-Type" content="text/html;" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/invoice.css') }}">
</head>

<body>

    @php($companyName = getWebConfig(name: 'company_name'))
    <div class="first">
        <table class="content-position mb-30">
            <tr>
                <th class="p-0 text-left font-size-26px">
                    {{ ucwords('order Invoice')}}
                </th>
                <th class="p-0 text-right">
                    <img height="40" src="{{dynamicStorage(path: "storage/app/public/company/".getWebConfig(name: 'company_web_logo'))}}"
                        alt="">
                </th>
            </tr>
        </table>

        <table class="bs-0 mb-30 px-10">
            <tr>
                <th class="content-position-y text-left">
                    <h4 class="text-uppercase mb-1 fz-14">
                        {{ ucwords('Order')}} #{{ $orderData['order_no'] }}
                    </h4>
                    <br>
                    <h4 class="text-uppercase mb-1 fz-14">
                        {{ ucwords('Event Name')}}
                        : {{ ($orderData['eventid']['event_name']??"") }}
                    </h4>
                </th>
                <th class="content-position-y text-right">
                    <h4 class="fz-14">
                        {{ ucwords('date')}} : {{date('d-m-Y h:i:s a',strtotime($orderData['created_at']))}}
                    </h4>
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
                            <tr>
                                <td>
                                    <?php
                                    $shipping_address = 'please show';
                                    ?>
                                    <span class="h2 m-0">{{ucwords('organizer')}} </span>
                                    <div class="h4">
                                        <p class="mt-6px mb-0">{{($orderData['eventid']->organizers['organizer_name']??"")}}</p>
                                        <p class="mt-6px mb-0">{{($orderData['eventid']->organizers['email_address']??"")}}</p>
                                    </div>
                                    <span class="h2 m-0"> </span>

                                </td>
                            </tr>
                        </table>
                    </td>

                    <td>
                        <table>
                            <tr>
                                <td class="text-right">
                                    <span class="h2">{{ ucwords('customer info')}} </span>
                                    <div class="h4">
                                        @if($orderData['userdata'])
                                        <p class="mt-6px mb-0">{{ ($orderData['userdata']['name']??"")}} </p>
                                        <p class="mt-6px mb-0">{{ ($orderData['userdata']['phone']??"")}} </p>
                                        @if($orderData['userdata']['phone'] != $orderData['userdata']['email'])
                                        <p class="mt-6px mb-0">{{ ($orderData['userdata']['email']??"")}} </p>
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
        <div class="content-position-y">
            <table class="customers bs-0">
                <thead>
                    <tr>
                        <th>{{ ucwords('no.')}}</th>
                        <th>{{ ucwords('venue name')}}</th>
                        <th>
                            {{ ucwords('unit price')}}
                        </th>
                        <th>
                            {{ ucwords('qty')}}
                        </th>
                        <th class="text-right">
                            {{ ucwords('total')}}
                        </th>
                    </tr>
                </thead>
                <?php
                $total = 0;
                $subTotal = 0;
                $totalTax = 0;
                $totalShippingCost = 0;
                $totalDiscountOnProduct = 0;
                $extraDiscount = 0;
                ?>
                <tbody>
                    @foreach($orderData['orderitem'] as $key=>$details)
                    <?php $subTotal = ($details['price']) * $details->qty ?>
                    <tr>
                        <td>{{$key+1}}</td>
                        <td style="width: 31% !important;">
                            @if($orderData['eventid']->all_venue_data && json_decode($orderData['eventid']->all_venue_data,true)) 
                                @foreach(json_decode($orderData['eventid']->all_venue_data,true) as $val) 
                                    @if($val['id'] == $orderData['venue_id'])
                                        {{ $val['en_event_venue']  }}
                                    @break
                                    @endif                                
                                @endforeach
                            @endif

                            <br>

                        </td>
                        <td>{{ webCurrencyConverter(amount: (($orderData['amount'] + $orderData['coupon_amount'])/($details['no_of_seats']??0)) ) }}</td>
                        <td>{{($details['no_of_seats']??0)}}</td>
                        <td class="text-right">{{ webCurrencyConverter(amount: ($orderData['amount'] + $orderData['coupon_amount'])) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @php($shipping=400)
    <div class="content-position-y">
        <table class="fz-12">
            <tr>
                <th class="text-left width-60">
                    <h4 class="fz-12 mb-1">{{ ucwords('payment details')}}</h4>
                    <h5 class="fz-12 mb-1 font-weight-normal"></h5>
                    <p class="fz-12 font-weight-normal">
                        {{date('d M,Y',strtotime($orderData['created_at']))}}</p>
                </th>

                <th class="calc-table">
                    <table>
                        <tbody>

                            <tr>
                                <td class="p-1 text-left"><b>{{ ucwords('sub total')}}</b></td>
                                <td class="p-1 text-right">{{ webCurrencyConverter(amount: (($orderData['amount']??0) + ($orderData['coupon_amount']??0))) }}</td>

                            </tr>
                            <tr>
                                <td class="p-1 text-left"><b>{{ ucwords('coupon discount')}}</b></td>
                                <td class="p-1 text-right">
                                    - {{ webCurrencyConverter(amount: ($orderData['coupon_amount']??0)) }}</td>
                            </tr>
                            <tr>
                                <td class="border-dashed-top font-weight-bold text-left"><b>{{ ucwords('total')}}</b></td>
                                <td class="border-dashed-top font-weight-bold text-right">
                                    {{ webCurrencyConverter(amount: ($orderData['amount']) ) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </th>
            </tr>
        </table>
    </div>
    <br>
    <br><br><br>

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
                            {{ ucwords('all copy right reserved © '.date('Y').' ').$companyName}}
                        </div>
                    </th>
                </tr>
            </table>
        </section>
    </div>

</body>

</html>