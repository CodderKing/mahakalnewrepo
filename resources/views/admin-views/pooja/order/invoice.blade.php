<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ 'Invoice' }}</title>
    <meta http-equiv="Content-Type" content="text/html;" />
    <meta charset="UTF-8">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/invoice.css') }}">
</head>

<body>
    <div class="first">
        <table class="content-position mb-30">
            <tr>
                <th class="p-0 text-left fz-26">
                    {{ 'Order Invoice' }}
                </th>
                <th>
                    <img height="40" src="{{ dynamicStorage(path: "storage/app/public/company/$companyWebLogo") }}"
                        alt="">
                </th>
            </tr>
        </table>
        <table class="bs-0 mb-30 px-10">
            <tr>
                <th class="content-position-y text-left">
                    <h4 class="text-uppercase mb-1 fz-14">
                        {{ 'Invoice' }} #{{ $details['order_id'] }}
                    </h4><br>
                </th>
                <th class="content-position-y text-right">
                    <h4 class="fz-14">{{ 'Date' }} :
                        {{ date('d-m-Y h:i:s a', strtotime($details['created_at'])) }}</h4>
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
                                    @if ($details['is_prashad'] == 1)
                                        <span class="h2 mt-0">{{ 'Prashad Shipping Address' }} </span>
                                        <div class="h4 montserrat-normal-600">
                                            <p class="mt-6 mb-0">{{ $details['house_no'] }} {{ $details['area'] }} </p>
                                            <p class="mt-6 mb-0">{{ $details['landmark'] }} </p>
                                            <p class="mt-6 mb-0">
                                                {{ $details['city'] . ' (' . $details['state'] . ')' }}
                                                {{ $details['pincode'] }}</p>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>

                    <td>
                        <table>
                            <tr>
                                <td class="text-right">
                                    <span class="h2">{{ 'Billing Address' }} </span>
                                    <div class="h4 montserrat-normal-600">
                                        <p class="mt-6 mb-0">
                                            {{ $details['customers']['f_name'] . ' ' . $details['customers']['l_name'] }}
                                        </p>
                                        <p class="mt-6 mb-0">{{ $details['customers']['email'] }}</p>
                                        <p class="mt-6 mb-0">{{ $details['customers']['phone'] }}</p>
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

    <div class="">
        <div class="content-position-y">
            <table class="customers bs-0">
                <thead>
                    <tr>
                        <th>{{ 'Pooja Name' }}</th>
                        <th>{{ 'Package Price' }}</th>
                        @if (!empty($details['members']))
                        <th>{{ 'Pooja Members' }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            {{ $details['services']['name'] }}
                        </td>
                        <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $details['package_price']), currencyCode: getCurrencyCode(type: 'default')) }}
                        </td>
                        <td>
                            @if (!empty($details['members']))
                                @foreach (json_decode($details['members']) as $key => $member)
                                    <p>{{ $key + 1 }}{{ '. ' . $member }}</p>
                                @endforeach
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @php
        $totalDaanPrice = 0;
    @endphp
    @if (count($details['product_leads']) > 0)
        <br><br><br>
        <div class="">
            <div class="content-position-y">
                <table class="customers bs-0">
                    <thead>
                        <tr>
                            <th>{{ 'SL' }}</th>
                            <th>{{ 'Daan' }}</th>
                            <th>{{ 'Unit Price' }}</th>
                            <th>{{ 'Qty' }}</th>
                            <th>{{ 'Total Price' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($details['product_leads'] as $key => $productLeads)
                            @php
                                $totalDaanPrice += $productLeads['final_price'];
                            @endphp
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>
                                    {{ $productLeads['productsData']['name'] }}
                                </td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $productLeads['product_price']), currencyCode: getCurrencyCode(type: 'default')) }}
                                </td>
                                <td>{{ $productLeads['qty'] }}</td>
                                <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $productLeads['final_price']), currencyCode: getCurrencyCode(type: 'default')) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <br>
    <div>
        <table>
            <tbody>
                <tr>
                    <td class="border-dashed-top font-weight-bold text-right"><b>{{ 'Total' }}</b></td>
                    <td class="border-dashed-top font-weight-bold" style="width:100px;">
                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $details['package_price'] + $totalDaanPrice), currencyCode: getCurrencyCode(type: 'default')) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <br>
    <br><br><br>

    <div class="row">
        <section>
            <table class="">
                <tr>
                    <th class="fz-12 font-normal pb-3">
                        {{ 'If you require any assistance or have feedback or suggestions about our site you can email us at' }}
                        <a href="mailto:{{ $companyEmail }}">({{ $companyEmail }})</a>
                    </th>
                </tr>
                <tr>
                    <th class="content-position-y bg-light py-4">
                        <div class="d-flex justify-content-center gap-2">
                            <div class="mb-2">
                                <i class="fa fa-phone"></i>
                                {{ 'Phone' }} : {{ $companyPhone }}
                            </div>
                            <div class="mb-2">
                                <i class="fa fa-envelope" aria-hidden="true"></i>
                                {{ 'Email' }} : {{ $companyEmail }}
                            </div>
                        </div>
                        <div class="mb-2">
                            {{ url('/') }}
                        </div>
                        <div>
                            {{ 'All Copyright Reserved ©' }} {{ date('Y') }} {{ $companyName }}
                        </div>
                    </th>
                </tr>
            </table>
        </section>
    </div>

</body>

</html>
