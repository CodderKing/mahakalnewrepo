@push('css_or_js')
    <style>
        .text-GRAY-90 {
            --tw-text-opacity: 1;
            color: rgb(246 141 28/var(--tw-text-opacity)) !important;
        }

        .text-PURPLE-60 {
            --tw-text-opacity: 1;
            color: rgb(67 10 189/var(--tw-text-opacity)) !important;
        }

        .text-BLUE-60 {
            --tw-text-opacity: 1;
            color: rgb(53 100 226/var(--tw-text-opacity));
        }

        .text-RED-61 {
            --tw-text-opacity: 1;
            color: rgb(255 50 1/var(--tw-text-opacity));
        }

        .package-Information div span {
            overflow: auto;
            height: 293px;
            /* display: -webkit-box;
            -webkit-line-clamp: 11;
            -webkit-box-orient: vertical; */
        }
    </style>
@endpush
@php
    $package = \App\Models\Package::where('id', $pac->package_id)->first();
@endphp
<div class="col-lg-3 packageCard">
    <div class="card mb-lg-0 rounded-lg shadow">
        <div
            class="card-header "style=" background: linear-gradient(to bottom, {{ $package ? $package->color : 'primary' }}, #ffffff); height:180px;">
            <h5 class="card-title text-uppercase text-center " style="line-height: 1.5em;min-height: 3em;">{{ $package->title }}</h5>
            <h6 class="text-center">Pooja Amount &#8360;.{{ $pac->price }}</h6>
            <h5 class="text-center">Booking Amount &#8360;.{{ $pac->price * ($pac->percent / 100) }}</h5>
            {{-- <span class="h5"><br>Pooja for {{ $package->person }} Person</span> --}}
        </div>
        <div class="card-body rounded-bottom">
            <div style="height: 200px;height: 272px; overflow: auto;">
                <div class="flex flex-col package-Information" style="font-size: 14px;">
                    <div style="display: flex; flex-direction: column">
                        <span style="flex-direction: row; align-items: start; width: 100%;" class="item">
                            {!! $package->description !!}
                        </span>
                    </div>
                </div>
            </div>
            @php
                if (auth('customer')->check()) {
                    $customer = App\Models\User::where('id', auth('customer')->id())->first();
                }
            @endphp
            @if (auth('customer')->check())
                <a href="javascript:void(0);" class="btn btn--primary btn-block btn-shadow mt-4 font-weight-bold"
                    data-id="{{ $package->id }}" data-name="{{ $package->title }}" data-price="{{ $pac->price }}"
                    data-percent={{ $pac->percent }} data-person="{{ $package->person }}"
                    onclick="alreadyLoginModel(this)">{{ translate('book_now') }}</a>
            @else
                <a href="javascript:void(0);" class="btn btn--primary btn-block btn-shadow mt-4 font-weight-bold"
                    data-id="{{ $package->id }}" data-name="{{ $package->title }}" data-price="{{ $pac->price }}"
                    data-percent={{ $pac->percent }} data-person="{{ $package->person }}"
                    onclick="participateModel(this)">{{ translate('book_now') }}</a>
            @endif
        </div>
    </div>
</div>
