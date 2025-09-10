<div class="row g-3 mb-4">
    {{-- Payment Info --}}
    <div class="col-lg-4 col-md-6">
        <div class="p-3 border rounded bg-light h-100 shadow-sm">
            <h6 class="fs-13 font-bold mb-3 text-capitalize">
                {{ translate('payment_info') }}
            </h6>
            <div class="fs-12 mb-2">
                <span class="text-muted">{{ translate('payment_status') }}:</span>
                @if ($order['payment_status'] == 1)
                    <span class="badge bg-success text-white px-2">{{ translate('paid') }}</span>
                @else
                    <span class="badge bg-warning text-dark px-2">{{ translate('pending') }}</span>
                @endif
            </div>
            <div class="fs-12">
                <span class="text-muted">{{ translate('payment_method') }}:</span>
                @php
                    $paymentMethod = '';
                    if (!empty($order['wallet_transaction_id']) && !empty($order['payment_id'])) {
                        $paymentMethod = translate('wallet') . ' + ' . translate('online');
                    } elseif (!empty($order['wallet_transaction_id'])) {
                        $paymentMethod = translate('wallet');
                    } elseif (!empty($order['payment_id'])) {
                        $paymentMethod = translate('online');
                    } else {
                        $paymentMethod = translate('not_available');
                    }
                @endphp
                <span class="text-primary">{{ $paymentMethod }}</span>
            </div>
        </div>
    </div>

    {{-- Bill Address --}}
    <div class="col-lg-4 col-md-6">
        <div class="p-3 border rounded bg-light h-100 shadow-sm">
            <h6 class="fs-13 font-bold mb-3 text-capitalize">
                {{ translate('bill_address') }}
            </h6>
            <div class="fs-12">
                <p class="mb-2"><strong>{{ translate('name') }}:</strong> {{ $order['customer']['name'] ?? 'N/A' }}</p>
                <p class="mb-2"><strong>{{ translate('phone') }}:</strong> {{ $order['customer']['phone'] ?? 'N/A' }}</p>
                <p class="mb-0"><strong>{{ translate('email') }}:</strong> {{ $order['customer']['email'] ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    {{-- Prasad Details --}}
    @if ($order->is_prashad == '1')
        <div class="col-lg-4 col-md-6">
            <div class="p-3 border rounded bg-light h-100 shadow-sm">
                <h6 class="fs-13 font-bold mb-3 text-capitalize">
                    {{ translate('prasad_details') }}
                </h6>
                <div class="fs-12">
                    <p class="mb-2"><strong>{{ translate('name') }}:</strong> {{ $order['customer']['name'] ?? 'N/A' }}</p>
                    <p class="mb-2"><strong>{{ translate('phone') }}:</strong> {{ $order['customer']['phone'] ?? 'N/A' }}</p>
                    <p class="mb-2"><strong>{{ translate('city') }} / {{ translate('zip') }}:</strong>
                        {{ $order->city ?? 'N/A' }}, {{ $order->pincode ?? '' }}
                    </p>
                    <p class="mb-0"><strong>{{ translate('address') }}:</strong>
                        {{ $order->house_no ?? '' }}, {{ $order->area ?? '' }}
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
