@php use App\Utils\Helpers; @endphp
@extends('layouts.back-end.app')

@section('title', translate('service_Tax'))

@section('content')
    {{-- main page --}}
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 d-flex gap-2">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/festival.png') }}" alt="">
                {{ translate('service_Tax') }}
                {{-- <span class="badge badge-soft-dark radius-50 fz-14">{{ $festivals->total() }}</span> --}}
            </h2>
        </div>
        <div class="row mt-20">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body p-0 m-3">
                        <form action="{{ route('admin.service.tax.update') }}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="offline_pooja">Offline Pooja</label>
                                        <div class="input-group">
                                            <input type="number" name="offline_pooja" class="form-control"
                                                placeholder="Enter offline pooja tax" required
                                                value="{{ $tax['offline_pooja'] }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="online_pooja">Online Pooja</label>
                                        <div class="input-group">
                                            <input type="number" name="online_pooja" class="form-control"
                                                placeholder="Enter online pooja tax" required
                                                value="{{ $tax['online_pooja'] }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="consultation">Consultation</label>
                                        <div class="input-group">
                                            <input type="number" name="consultation" class="form-control"
                                                placeholder="Enter consultation tax" required
                                                value="{{ $tax['consultation'] }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="live_stream">Live Stream</label>
                                        <div class="input-group">
                                            <input type="number" name="live_stream" class="form-control"
                                                placeholder="Enter live stream tax" required
                                                value="{{ $tax['live_stream'] }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="call">Call</label>
                                        <div class="input-group">
                                            <input type="number" name="call" class="form-control"
                                                placeholder="Enter call tax" required value="{{ $tax['call'] }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="chat">Chat</label>
                                        <div class="input-group">
                                            <input type="number" name="chat" class="form-control"
                                                placeholder="Enter chat tax" required value="{{ $tax['chat'] }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="tour_tax">Tour Tax</label>
                                        <div class="input-group">
                                            <input type="number" name="tour_tax" class="form-control"
                                                placeholder="Enter tour_tax tax" required value="{{ $tax['tour_tax'] ?? 0 }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="event_tax">Event Tax</label>
                                        <div class="input-group">
                                            <input type="number" name="event_tax" class="form-control"
                                                placeholder="Enter event_tax tax" required
                                                value="{{ $tax['event_tax'] ?? 0 }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if (Helpers::modules_permission_check('Service Tax', 'Service Tax', 'edit'))
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-primary">Update</button>
                                    </div>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/products-management.js') }}"></script>
@endpush
