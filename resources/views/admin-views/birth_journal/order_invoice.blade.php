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
                    {{ 'Kundali Invoice' }}
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
                                        <span class="h2 mt-0">{{ 'Customer Information' }} </span>
                                        <div class="h4 montserrat-normal-600">
                                            <p class="mt-6 mb-0">Name: {{ !empty($details['userData']['name'])?$details['userData']['name']:'' }}</p>
                                            <p class="mt-6 mb-0">phone: {{ !empty($details['userData']['phone'])?$details['userData']['phone']:'' }}</p>
                                            @if (str_contains($details['userData']['email'], '.com'))
                                            <p class="mt-6 mb-0">Email: {{ !empty($details['userData']['email'])?$details['userData']['email']:'' }}</p>
                                            @endif
                                        </div>
                                </td>
                            </tr>
                        </table>
                    </td>

                    <td>
                        
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
                        <th>{{ 'Name' }}</th>
                        <th>{{ 'Type' }}</th>
                        <th>{{ 'Price' }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td> {{ ucwords(str_replace('_',' ',($details['birthJournal']['name']??""))) }} </td>
                        <td> {{ ($details['birthJournal']['type']??"")}} </td>
                        <td>{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $details['amount']), currencyCode: getCurrencyCode(type: 'default')) }} </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <br>
    <div>
        <table>
            <tbody>
                <tr>
                    <td class="border-dashed-top font-weight-bold text-right"><b>{{ 'Total' }}</b></td>
                    <td class="border-dashed-top font-weight-bold" style="width:100px;">
                    {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $details['amount']), currencyCode: getCurrencyCode(type: 'default')) }}
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
