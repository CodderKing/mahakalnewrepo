<html>

<head>
    <meta charset="UTF-8">
    <title>{{ ucwords('invoice')}}</title>
    <meta http-equiv="Content-Type" content="text/html;" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="{{ theme_asset(path: 'public/assets/front-end/css/invoice.css') }}">
    <style> 
.badge-soft-success {
    color: #00c9a7;
    background-color: rgba(0, 201, 167, .1);
}

.badge {
    /* display: inline-block; */
    padding: .3125em .5em;
    /* font-size: 75%; */
    font-weight: 600;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: .3125rem;
    transition: all .2s ease-in-out;
}
.badge-soft-danger {
    color: #ed4c78;
    background-color: rgba(237, 76, 120, .1);
}
    </style>
</head>

<body>

    @php($companyName = getWebConfig(name: 'company_name'))
    <div class="first">
        <table class="content-position mb-30">
            <tr>
                <th class="p-0 text-left font-size-26px">
                    {{ ucwords('Donate Invoice')}}
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
                   #{{ ucwords('donate')}}: {{ $orderData['trans_id']}}
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
                                    <span class="h2 m-0">{{ucwords('trust')}} </span>
                                    <div class="h4">
                                        @if(!empty($orderData['getTrust']))
                                        <p class="mt-6px mb-0">{{($orderData['getTrust']['trust_name']??"")}}</p>
                                        <p class="mt-6px mb-0">{{($orderData['getTrust']['name']??"")}}</p>
                                        @else
                                        <p class="mt-6px mb-0"> {{ ucwords($companyName)}}</p>
                                        @endif
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
                                        @if($orderData['users'])
                                        <p class="mt-6px mb-0">{{ ($orderData['users']['name']??"")}} </p>
                                        <p class="mt-6px mb-0">{{ ($orderData['users']['phone']??"")}} </p>
                                        @if($orderData['users']['phone'] != $orderData['users']['email'])
                                        <p class="mt-6px mb-0">{{ ($orderData['users']['email']??"")}} </p>
                                        @endif
                                        @else
                                        <p class="mt-6px mb-0">Guest User</p>
                                        @endif
                                        <p class="mt-6px mb-0">{{ ucwords('Payment Mode')}} : <span> {{ (($orderData['amount_status'] == 1)?'Paid':'Unpaid')}} </span></p>

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
                        <th>{{ ucwords('name')}}</th>
                        <th>
                            {{ ucwords('amount')}}
                        </th>
                        <th class="text-right">
                            {{ ucwords('total')}}
                        </th>
                    </tr>
                </thead>
                
                <tbody>
                    <tr>
                        <td>1</td>
                        <td style="width: 31% !important;">   
                              @if($orderData['type'] == 'donate_trust')
                              {{($orderData['getTrust']['trust_name']??"")}}
                              @else 
                              {{($orderData['adsTrust']['name']??"")}}
                              @endif
                                             </td>
                        <td>{{ webCurrencyConverter(amount: ($orderData['amount']??"")) }}</td>
                        <td class="text-right">{{ webCurrencyConverter(amount:  ($orderData['amount']??"")) }}</td>
                    </tr>
                    
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