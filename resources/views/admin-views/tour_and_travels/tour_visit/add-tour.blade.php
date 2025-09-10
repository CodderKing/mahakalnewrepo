@extends('layouts.back-end.app')

@section('title', translate('Add_Tour'))

@push('css_or_js')
<link href="{{ dynamicAsset(path: 'public/assets/back-end/css/tags-input.min.css') }}" rel="stylesheet">
<link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
<script src="https://maps.googleapis.com/maps/api/js?key={{$googleMapsApiKey}}&libraries=places"></script>
<link href="https://unpkg.com/gijgo@1.9.14/css/gijgo.min.css" rel="stylesheet" type="text/css" />
<style>
    .toast-top-full-width {
        top: 20px;
        left: 0;
        right: 0;
        margin-left: auto;
        margin-right: auto;
        width: 100%;
        text-align: center;
        z-index: 9999;
        pointer-events: none;
        /* Allows clicking through empty space */
    }

    #toast-container>.toast {
        display: inline-block;
        min-width: 50%;
        max-width: 100%;
        text-align: center;
        pointer-events: all;
    }
</style>

@endpush

@section('content')
<div class="content container-fluid">
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 d-flex gap-2">
            {{ translate('Add_Tour') }}
        </h2>
    </div>

    <form class="product-form text-start" action="{{ route('admin.tour_visits.insert-tour') }}" method="POST" enctype="multipart/form-data" id="services_form">
        @csrf
        <div class="card">
            <div class="px-4 pt-3">
                <ul class="nav nav-tabs w-fit-content mb-4">
                    @foreach ($languages as $lang)
                    <li class="nav-item">
                        <span class="nav-link text-capitalize form-system-language-tab {{ $lang == $defaultLanguage ? 'active' : '' }} cursor-pointer" id="{{ $lang }}-link">{{ getLanguageName($lang) . '(' . strtoupper($lang) . ')' }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>

            <div class="card-body">
                @foreach ($languages as $key=>$lang)
                <div class="{{ $lang != $defaultLanguage ? 'd-none' : '' }} form-system-language-form" id="{{ $lang }}-form">
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}tour_name">{{ translate('tour_name') }} ({{ strtoupper($lang) }}) </label>
                                <input type="text" {{ $lang == $defaultLanguage ? 'required' : '' }} name="tour_name[]" id="{{ $lang }}tour_name" class="form-control @error('tour_name.'.$loop->index) is-invalid @enderror" value="{{ old('tour_name.'.$loop->index) }}" placeholder="{{ translate('tour_name') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}tour_name">{{ translate('tour_type') }} </label>
                                <select {{ $lang == $defaultLanguage ? 'required' : '' }} name="tour_type" id="{{ $lang }}tour_type" class="form-control @error('tour_type') is-invalid @enderror tour_types" onchange="$('.tour_types').val(this.value)">
                                    @if(!empty($typeList) && count($typeList))
                                    @foreach($typeList as $val)
                                    <option value="{{$val['slug']}}" {{ (( old('tour_type') == $val['slug'] )?'selected':'' )}}> {{ $val['name'] }}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_traveller_name">{{ translate('traveller') }} </label>
                                <select {{ $lang == $defaultLanguage ? 'required' : '' }} name="created_id" id="{{ $lang }}_traveller_name" class="form-control @error('created_id') is-invalid @enderror created_id" onchange="$('.created_id').val(this.value)">
                                    <option value="" selected disabled>Select Traveller</option>
                                    <option value="0" {{ ((old('created_id') == '0' )?'selected':'' ) }}>All Traveller</option>
                                    @if(!empty($travelar_list) && count($travelar_list) > 0)
                                    @foreach($travelar_list as $val)
                                    <option value="{{ $val['id']}}" {{ ((old('created_id') == $val['id'] )?'selected':'' ) }}>{{$val['company_name']}}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_cities_name">{{ translate('cities_name') }} </label>
                                <input type="text" {{ $lang == $defaultLanguage ? 'required' : '' }} name="cities_name[]" id="{{ $lang }}_cities_name" class="form-control @error('cities_name.'.$loop->index) is-invalid @enderror getAddress_google" value="{{ old('cities_name.'.$loop->index) }}" placeholder="{{ translate('cities_name') }}">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_country_name">{{ translate('country_name') }} </label>
                                <input type="text" {{ $lang == $defaultLanguage ? 'required' : '' }} name="country_name[]" aria-readonly="readonly" readonly id="{{ $lang }}_country_name" class="form-control @error('country_name.'.$loop->index) is-invalid @enderror " value="{{ old('country_name.'.$loop->index) }}" placeholder="{{ translate('country_name') }}" data-toggle="tooltip" role='tooltip' data-title='Please Select Cities'>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_state_name">{{ translate('state_name') }} </label>
                                <input type="text" {{ $lang == $defaultLanguage ? 'required' : '' }} name="state_name[]" aria-readonly="readonly" readonly id="{{ $lang }}_state_name" class="form-control @error('state_name.'.$loop->index) is-invalid @enderror" value="{{ old('state_name.'.$loop->index) }}" placeholder="{{ translate('state_name') }}" data-toggle="tooltip" role='tooltip' data-title='Please Select Cities'>
                                <input type="hidden" name='lat' class="lat_location" value="{{ old('lat') }}">
                                <input type="hidden" name='long' class="long_location" value="{{ old('long') }}">
                            </div>
                        </div>
                        <div class="col-md-4 d-none">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_part_located">{{ 'In which part is it located' }} </label>
                                <input type="text" name="part_located[]" id="{{ $lang }}_part_located" class="form-control @error('part_located.'.$loop->index) is-invalid @enderror " value="{{ old('part_located.'.$loop->index ) }}" placeholder="{{ translate('In which part is it located') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_ex_distance">{{ translate('1km_ex_distance_fee') }} </label>
                                <input type="text" name="ex_distance" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );$('.ex_distance_fee').val(this.value)" class="ex_distance_fee form-control @error('ex_distance') is-invalid @enderror " value="{{ old('ex_distance',10) }}" placeholder="{{ translate('1km_ex_distance_fee') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_number_of_day">{{ translate('number_of_days') }} (Helf day: 0.5)</label>
                                <input type="text" name="number_of_day" class="form-control @error('number_of_day') is-invalid @enderror number_of_day_number" value="{{ old('number_of_day',1) }}" placeholder="{{ translate('number_of_day') }}" required onkeyup="validateInputValue(this);$('.number_of_day_number').val(this.value)">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color">{{ translate('number_of_Night') }} </label>
                                <input type="text" name="number_of_night" class="form-control @error('number_of_night') is-invalid @enderror number_of_night_number" value="{{ old('number_of_night',1) }}" placeholder="{{ translate('number_of_Night') }}" required onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );$('.number_of_night_number').val(this.value)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_description">{{ translate('description') }} ({{ strtoupper($lang) }}) </label>
                                <textarea {{ $lang == $defaultLanguage ? 'required' : '' }} name="description[]" id="{{ $lang }}_description" class="form-control ckeditor @error('description.'.$loop->index) is-invalid @enderror">{{ old('description.'.$loop->index) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_highlights">{{ translate('highlights') }} ({{ strtoupper($lang) }}) </label>
                                <textarea {{ $lang == $defaultLanguage ? 'required' : '' }} name="highlights[]" id="{{ $lang }}_highlights" class="form-control ckeditor @error('highlights.'.$loop->index) is-invalid @enderror">{{ old('highlights.'.$loop->index) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_Inclusion">{{ translate('Inclusion') }} ({{ strtoupper($lang) }}) </label>
                                <textarea {{ $lang == $defaultLanguage ? 'required' : '' }} name="inclusion[]" id="{{ $lang }}_Inclusion" class="form-control ckeditor @error('inclusion.'.$loop->index) is-invalid @enderror">{{ old('inclusion.'.$loop->index) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_exclusion">{{ translate('exclusion') }} ({{ strtoupper($lang) }}) </label>
                                <textarea {{ $lang == $defaultLanguage ? 'required' : '' }} name="exclusion[]" id="{{ $lang }}_exclusion" class="form-control ckeditor @error('exclusion.'.$loop->index) is-invalid @enderror">{{ old('exclusion.'.$loop->index) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_terms_and_conditions">{{ translate('terms_and_conditions') }} ({{ strtoupper($lang) }}) </label>
                                <textarea {{ $lang == $defaultLanguage ? 'required' : '' }} name="terms_and_conditions[]" id="{{ $lang }}_terms_and_conditions" class="form-control ckeditor @error('terms_and_conditions.'.$loop->index) is-invalid @enderror">{{ old('terms_and_conditions.'.$loop->index) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_cancellation_policy">{{ translate('cancellation_policy ') }} ({{ strtoupper($lang) }}) </label>
                                <textarea {{ $lang == $defaultLanguage ? 'required' : '' }} name="cancellation_policy[]" id="{{ $lang }}_cancellation_policy" class="form-control ckeditor @error('cancellation_policy.'.$loop->index) is-invalid @enderror">{{ old('cancellation_policy.'.$loop->index) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_notes">{{ translate('notes ') }} ({{ strtoupper($lang) }}) </label>
                                <textarea {{ $lang == $defaultLanguage ? 'required' : '' }} name="notes[]" id="{{ $lang }}_notes" class="form-control ckeditor @error('notes.'.$loop->index) is-invalid @enderror">{{ old('notes.'.$loop->index) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="lang[]" value="{{ $lang }}">
                </div>
                @endforeach
                <div class="row">
                    <div class='col-md-3 form-group'>
                        <label class="title-color font-weight-bold h3" for="Language">{{ translate('choose_Tour_Plan') }}</label>
                        <a class="btn btn-sm btn-info" onclick="$('.infotourTypeShow').modal('show')">INFO</a>
                        <select class="form-control" name='use_date' onchange="use_date_functions(this)">
                            <option value="0" {{((old('use_date') == 0)?'selected':'' )}}>Cities Tour</option>
                            <option value="1" {{((old('use_date') == 1)?'selected':'' )}}>Special Tour(With Date)</option>
                            <option value="4" {{((old('use_date') == 4)?'selected':'' )}}>Special Tour(Without Date)</option>
                            <option value="2" {{((old('use_date') == 2)?'selected':'' )}}>Daily Tour(With Address)</option>
                            <option value="3" {{((old('use_date') == 3)?'selected':'' )}} {{ ((old('is_person_use') == 1) ? 'style=display:none;' :'' )}}>Daily Tour(WithOut Address)</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group  {{((old('use_date') == 0)?'d-none':'' )}} {{((old('use_date') == 2)?'d-none':'' )}} {{((old('use_date') == 4)?'d-none':'' )}} {{((old('use_date') == 3)?'d-none':'' )}} use_interested_and_not daily_tour_full_comman">
                        <label class="title-color font-weight-bold h3" for="Language">{{ translate('start_date_and_end_date') }}</label>
                        <input type="text" class="form-control all_select_data start_date_end_date" data-point='8' value="{{ old('startandend_date') }}" name='startandend_date' placeholder="{{ translate('enter_start_to_end_date') }}">
                    </div>
                    <div class="col-md-3 form-group  {{((old('use_date') == 0)?'d-none':'' )}} {{((old('use_date') == 2)?'d-none':'' )}} {{((old('use_date') == 4)?'d-none':'' )}} {{((old('use_date') == 3)?'d-none':'' )}} use_interested_and_not daily_tour_full_comman">
                        <label class="title-color font-weight-bold h3" for="Language">{{ translate('pickup_time') }}</label>
                        <input type="text" class="form-control pickup_times" value="{{ old('pickup_time') }}" name='pickup_time' placeholder="{{ translate('pickup_time') }}" readonly>
                    </div>
                    <div class="col-md-3 form-group  {{((old('use_date') == 0)?'d-none':'' )}} {{((old('use_date') == 3)?'d-none':'' )}} use_interested_and_not daily_tour_full_address">
                        <label class="title-color font-weight-bold h3" for="Language">{{ translate('pickup_location') }}</label>
                        <input type="text" class="form-control pickup_location_get" value="{{ old('pickup_location') }}" name='pickup_location' placeholder="{{ translate('pickup_location') }}">
                        <input type="hidden" class="pick_up_lat_location" name='pickup_lat' value="{{ old('pickup_lat') }}">
                        <input type="hidden" class="pick_up_long_location" name='pickup_long' value="{{ old('pickup_long') }}">
                    </div>


                    <div class='col-md-12 form-group'>
                        <label class="title-color font-weight-bold h3" for="Language">{{ translate('Package') }} &nbsp;&nbsp;&nbsp; <input type="checkbox" name="is_person_use" value="{{ old('is_person_use',1) }}" {{ ((old('is_person_use') == 1)?'checked':'' ) }} onclick="if(this.checked) {  
                            $('.cab_divShow').addClass('d-none');  
                            $('.persons_divShow').removeClass('d-none');
                            $('.persons_transport_divShow').removeClass('d-none');  
                            $('.package_divShow').addClass('d-none');  
                            $(`select[name='use_date'] option[value='3']`).hide();
                            $(`select[name='use_date']`).val('0');
                            $('.person_package_includes').removeClass('d-none'); 
                             $('.persons_transport_divShow').children().hide(); 
                        } else {  
                            $('.cab_divShow').removeClass('d-none');  
                            $('.persons_divShow').addClass('d-none'); 
                            $('.persons_transport_divShow').addClass('d-none');  
                            $('.package_divShow').removeClass('d-none'); 
                            $(`select[name='use_date'] option[value='3']`).show();
                            $(`select[name='use_date']`).val('0');
                            $('.person_package_includes').addClass('d-none'); 
                             $('.persons_transport_divShow').children().hide();
                        }">&nbsp;<small>Use person</small></label>
                    </div>
                    <div class="col-md-12 form-group person_package_includes {{ ((old('is_person_use') == 1)?'':'d-none' ) }}">
                        <div class="d-flex">
                            <input type="checkbox" name="include_package[sight_seen]" {{ old('include_package.sight_seen') ? 'checked' : '' }}>&nbsp;Sight-Seen &nbsp;&nbsp;&nbsp;
                            <input type="checkbox" name="include_package[cab]" {{ old('include_package.cab') ? 'checked' : '' }}>&nbsp;Transportion &nbsp;&nbsp;&nbsp;
                            <input type="checkbox" name="include_package[food]" {{ old('include_package.food') ? 'checked' : '' }}>&nbsp;Food &nbsp;&nbsp;&nbsp;
                            <input type="checkbox" name="include_package[hotel]" {{ old('include_package.hotel') ? 'checked' : '' }}>&nbsp;Accomadation &nbsp;&nbsp;&nbsp;
                        </div>
                    </div>
                    <div class="col-md-6 form-group add_persons_append_multi persons_divShow  {{ ((old('is_person_use') == 1)?'':'d-none' ) }}">
                        <div class="row">
                            <div class="col-12">
                                <span class="font-weight-bolder text-danger">Enter a Include {{(\App\Models\ServiceTax::first()['tour_tax']??0)}}% GST Amount</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class='col-4'>
                                <label class="title-color fw-bolder" for="Language">{{ translate('people_MIN') }}</label>
                            </div>
                            <div class='col-4'>
                                <label class="title-color fw-bolder" for="Language">{{ translate('people_MAX') }}</label>
                            </div>
                            <div class="col-4">
                                <label class="title-color fw-bolder" for="Language">{{ translate('per_head') }} <a class='btn btn--primary btn-sm p-1 mt-2' onclick="add_new_persons_html()"><i class='tio-add'></i></a></label>
                            </div>
                        </div>
                        @php
                        $Minpersons = old('min_person', []);
                        $MaxPersons = old('max_person', []);
                        $PersonPrice = old('person_price', []);
                        @endphp
                        @if(!empty($Minpersons))
                        @foreach($Minpersons as $inp => $oldper)
                        <div class="row">
                            <div class='col-3'>
                                <input type='text' class="form-control " name="min_person[]" value="{{ $Minpersons[$inp]??'' }}" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );validatePersonPrice(this)" placeholder="{{ translate('enter_Min') }}">
                            </div>
                            <div class="col-4">
                                <input type='text' class="form-control " name="max_person[]" value="{{ $MaxPersons[$inp]??'' }}" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );validatePersonPrice(this)" placeholder="{{ translate('enter_Max') }}">
                            </div>
                            <div class="col-4">
                                <input type='text' class="form-control" name="person_price[]" value="{{ $PersonPrice[$inp]??'' }}" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' )" placeholder="{{ translate('enter_Price') }}">
                            </div>
                            <div class="col-1 p-0">
                                @if($inp == 0)
                                <a class='btn btn--primary btn-sm p-1 mt-2' onclick="add_new_persons_html()"><i class='tio-add'></i></a>
                                @else
                                <button type="button" class="btn btn-danger btn-sm p-1 mt-2 remove-row"><i class='tio-clear'></i></button>
                                @endif
                            </div>
                        </div>
                        @endforeach
                        @else
                        <div class="row">
                            <div class='col-3'>
                                <input type='text' class="form-control " name="min_person[]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );validatePersonPrice(this)" placeholder="{{ translate('enter_Min') }}">
                            </div>
                            <div class="col-4">
                                <input type='text' class="form-control " name="max_person[]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );validatePersonPrice(this)" placeholder="{{ translate('enter_Max') }}">
                            </div>
                            <div class="col-4">
                                <input type='text' class="form-control" name="person_price[]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' )" placeholder="{{ translate('enter_Price') }}">
                            </div>
                            <div class="col-1 p-0">
                                <a class='btn btn--primary btn-sm p-1 mt-2' onclick="add_new_persons_html()"><i class='tio-add'></i></a>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="col-md-6 form-group add_cab_append_multi cab_divShow  {{ ((old('is_person_use') == 1)?'d-none':'' ) }}">
                        <div class="row">
                            <div class="col-12">
                                <span class="font-weight-bolder text-danger">Enter a Include {{(\App\Models\ServiceTax::first()['tour_tax']??0)}}% GST Amount</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class='col-6'>
                                <label class="title-color fw-bolder" for="Language">{{ translate('cab_name') }}</label>
                            </div>
                            <div class="col-6">
                                <label class="title-color fw-bolder" for="Language">{{ translate('Price') }}&nbsp;<a class='btn btn--primary btn-sm p-1 mt-2' onclick="add_new_cab_html()"><i class='tio-add'></i></a></label>
                            </div>
                        </div>
                        @php
                        $oldCabIds = old('cab_id', []);
                        $oldPrices = old('price', []);
                        $oldExcharge = old('excharge', []);
                        @endphp
                        @if(!empty($oldCabIds))
                        @foreach($oldCabIds as $index => $oldCabId)
                        <div class="row mt-2">
                            <div class='col-6 p-0 pr-1'>
                                <select class="form-control point_trigger1{{ $index }}" name="cab_id[{{ $index }}]" onchange="select_value(this)" data-point='point_trigger1{{ $index }}'>
                                    <option value="" selected disabled>{{ translate('Select_cab') }}</option>
                                    @foreach($cab_list as $cabs)
                                    <option value="{{ $cabs['id'] }}" {{ $cabs['id'] == $oldCabId ? 'selected' : '' }}>{{ $cabs['name'] }} - ({{ $cabs['seats'] }} seat)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-5 p-0 pr-1">
                                <input type='text' class="form-control point_trigger4{{ $index }}" name="price[{{ $index }}]" value="{{ $oldPrices[$index] ?? '' }}" onkeyup="select_value(this);this.value = this.value.replace(/[^0-9]/g, '' )" data-point='point_trigger4{{ $index }}' placeholder="{{ translate('enter_Price') }}">
                            </div>
                            @if($loop->index == 0)
                            <div class="col-1 p-0">
                                <a class='btn btn--primary btn-sm p-1 mt-2' onclick="add_new_cab_html()"><i class='tio-add'></i></a>
                                <a class='btn btn--primary btn-sm p-1 mt-2 cab-ex-distance-charge{{ $index }} {{((old("use_date",0) == 3)?"":"d-none" )}} specialTourwithoutdate' onclick="cab_ex_distance_model('{{ $index }}')"><i class="tio-bonnet_open"> bonnet_open </i></a>
                                <input type="hidden" class="from-control cab-json-show{{ $index }}" name="excharge[{{ $index }}]" value="{{ $oldExcharge[$index] ?? '' }}">
                            </div>
                            @else
                            <div class="col-1 p-0">
                                <a class='btn btn-danger btn-sm p-1 mt-2' onclick="remove_html(this)"><i class='tio-remove'></i></a>
                                <a class='btn btn--primary btn-sm p-1 mt-2 cab-ex-distance-charge{{ $index }} {{((old("use_date",0) == 3)?"":"d-none" )}} specialTourwithoutdate' onclick="cab_ex_distance_model('{{ $index }}')"><i class="tio-bonnet_open"> bonnet_open </i></a>
                                <input type="hidden" class="from-control cab-json-show{{ $index }}" name="excharge[{{ $index }}]" value="{{ $oldExcharge[$index] ?? '' }}">
                            </div>
                            @endif
                        </div>
                        @endforeach
                        @else
                        <div class="row mt-2">
                            <div class='col-6 p-0 pr-1'>
                                <select class="form-control point_trigger1" name="cab_id[0]" onchange="select_value(this)" data-point='point_trigger1'>
                                    <option value="" selected disabled>{{ translate('Select_cab') }}</option>
                                    @if($cab_list)
                                    @foreach($cab_list as $cabs)
                                    <option value="{{ $cabs['id']}}" {{ (collect(old('cab_id'))->contains($cabs['id'])) ? 'selected' : '' }}>{{ $cabs['name']}} - ({{ $cabs['seats'] }} seat)</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-5 p-0 pr-1">
                                <input type='text' class="form-control point_trigger4" name="price[0]" onkeyup="select_value(this);this.value = this.value.replace(/[^0-9]/g, '' )" data-point='point_trigger4' placeholder="{{ translate('enter_Price') }}">
                            </div>
                            <div class="col-1 p-0">
                                <a class='btn btn--primary btn-sm p-1 mt-2' onclick="add_new_cab_html()"><i class='tio-add'></i></a>
                                <a class='btn btn--primary btn-sm p-1 mt-2 cab-ex-distance-charge0 {{((old("use_date",0) == 3)?"":"d-none" )}} specialTourwithoutdate' onclick="cab_ex_distance_model('0')"><i class="tio-bonnet_open"> bonnet_open </i></a>
                                <input type="hidden" class="from-control cab-json-show0" name="excharge[0]">
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="col-md-6 form-group add_persons_transport_append_multi persons_transport_divShow  {{ ((old('is_person_use') == 1)?'':'d-none' ) }}">
                        <div class="row">
                            <div class="col-12">
                                <span class="font-weight-bolder text-danger">Enter a Include {{(\App\Models\ServiceTax::first()['tour_transport_tax']??0)}}% GST Amount</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class='col-2 px-1'>
                                <label class="title-color fw-bolder small" for="Language">{{ translate('start_Person') }}</label>
                            </div>
                            <div class='col-2 px-1'>
                                <label class="title-color fw-bolder small" for="Language">{{ translate('end_Person') }}</label>
                            </div>
                            <div class="col-2 px-1">
                                <label class="title-color fw-bolder small" for="Language">{{ translate('pick') }}</label>
                            </div>
                            <div class="col-2 px-1">
                                <label class="title-color fw-bolder small" for="Language">{{ translate('drop') }}</label>
                            </div>
                            <div class="col-2 px-1">
                                <label class="title-color fw-bolder small" for="Language">{{ translate('both') }}</label>
                            </div>
                            <div class="col-2 px-1">
                                <a class='btn btn--primary btn-sm p-1 mt-2' onclick="add_new_person_transport_html()"><i class='tio-add'></i></a>
                            </div>
                        </div>
                        @php
                        $Startpersons = old('start_person', []);
                        $EndPersons = old('end_person', []);
                        $PersonPick = old('person_pick', []);
                        $PersonDrop = old('person_drop', []);
                        $PersonBoth = old('person_both', []);
                        @endphp
                        @if(!empty($Startpersons))
                        @foreach($Startpersons as $inp => $oldtran)
                        <div class="row mt-2">
                            <div class='col-2 px-1'>
                                <input type='text' class="form-control px-2" name="start_person[]" value="{{ $Startpersons[$inp]??'' }}" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' )" placeholder="{{ translate('number') }}">
                            </div>
                            <div class="col-2 px-1">
                                <input type='text' class="form-control px-2" name="end_person[]" value="{{ $EndPersons[$inp]??'' }}" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );validateStartEndPersonsWithLastEnd(this)" placeholder="{{ translate('number') }}">
                            </div>
                            <div class='col-2 px-1'>
                                <input type='text' class="form-control px-2" name="person_pick[]" value="{{ $PersonPick[$inp]??'' }}" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' )" placeholder="{{ translate('pick') }}">
                            </div>
                            <div class="col-2 px-1">
                                <input type='text' class="form-control px-2" name="person_drop[]" value="{{ $PersonDrop[$inp]??'' }}" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' )" placeholder="{{ translate('drop') }}">
                            </div>
                            <div class="col-2 px-1">
                                <input type='text' class="form-control px-2" name="person_both[]" value="{{ $PersonBoth[$inp]??'' }}" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' )" placeholder="{{ translate('both') }}">
                            </div>
                            <div class="col-2 px-1">
                                @if($inp == 0)
                                <a class='btn btn--primary btn-sm p-1 mt-2' onclick="add_new_person_transport_html()"><i class='tio-add'></i></a>
                                @else
                                <button type="button" class="btn btn-danger btn-sm p-1 mt-2 remove-row"><i class='tio-clear'></i></button>
                                @endif
                            </div>
                        </div>
                        @endforeach
                        @else
                        <div class="row">
                            <div class='col-2 px-1'>
                                <input type='text' class="form-control px-2" name="start_person[]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' )" placeholder="{{ translate('number') }}">
                            </div>
                            <div class="col-2 px-1">
                                <input type='text' class="form-control px-2" name="end_person[]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );validateStartEndPersonsWithLastEnd(this)" placeholder="{{ translate('number') }}">
                            </div>
                            <div class='col-2 px-1'>
                                <input type='text' class="form-control px-2" name="person_pick[]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' )" placeholder="{{ translate('pick') }}">
                            </div>
                            <div class="col-2 px-1">
                                <input type='text' class="form-control px-2" name="person_drop[]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' )" placeholder="{{ translate('drop') }}">
                            </div>
                            <div class="col-2 px-1">
                                <input type='text' class="form-control px-2" name="person_both[]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' )" placeholder="{{ translate('both') }}">
                            </div>
                            <div class="col-2 px-1">
                                <a class='btn btn--primary btn-sm p-1 mt-2' onclick="add_new_person_transport_html()"><i class='tio-add'></i></a>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="col-md-6 form-group add_package_append_multi package_divShow  {{ ((old('is_person_use') == 1)?'d-none':'' ) }}">
                        <div class="row">
                            <div class='col-6'>
                                <label class="title-color fw-bolder" for="Language">{{ translate('package_name') }}</label>
                            </div>
                            <div class="col-6">
                                <label class="title-color fw-bolder" for="Language">{{ translate('Price') }}</label>
                                <a class='btn btn--primary btn-sm p-1 mt-2 float-end' onclick="add_new_package_html()"><i class='tio-add'></i></a>
                            </div>
                        </div>
                        @php
                        $oldPackages = old('package_id', []);
                        $oldPrices = old('pprice', []);
                        $oldPNumber = old('pnumber', []);
                        $oldPPersons = old('pperson', []);
                        @endphp
                        @if(!empty($oldPackages))
                        @foreach($oldPackages as $index => $oldCabId)
                        <div class="row mt-2">
                            <div class='col-4 p-0 pr-1'>
                                <select class="form-control point_pck_trigger2{{ $index }}" name="package_id[{{ $index }}]" onchange="select_value(this)" data-point='point_pck_trigger2{{ $index }}'>
                                    <option value="">Select Packages</option>
                                    @if($package_list)
                                    @foreach($package_list as $packval)
                                    <option value="{{ $packval['id'] }}" {{ $packval['id'] == $oldPackages[$index] ? 'selected' : '' }} data-type="{{ $packval['type']}}">{{ $packval['name'] }} -({{ $packval['seats']}} people)</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-1 p-0 pr-1">
                                <input type='text' class="p-0 pl-1 form-control point_packNumber_trigger4{{ $index }}" value="{{ $oldPNumber[$index] ?? '' }}" name="pnumber[{{ $index }}]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );$('.point_pack_trigger4{{ $index }}').val($('.point_packamount_trigger4{{ $index }}').val() * $('.point_packNumber_trigger4{{ $index }}').val());" data-point='point_packamount_trigger4{{ $index }}' placeholder="{{ translate('number_of_day_and_stay') }}">
                            </div>
                            <div class="col-2 p-0 pr-1">
                                <input type='text' class="form-control point_packamount_trigger4{{ $index }}" name="pperson[{{ $index }}]" value="{{ $oldPPersons[$index] ?? '' }}" onkeyup="select_value(this);this.value = this.value.replace(/[^0-9]/g, '' );$('.point_pack_trigger4{{ $index }}').val(this.value * $('.point_packNumber_trigger4{{ $index }}').val());" data-point='point_packamount_trigger4{{ $index }}' placeholder="{{ translate('enter_per_days') }}">
                            </div>
                            <div class="col-4 p-0 pr-1">
                                <input type='text' class="form-control point_pack_trigger4{{ $index }}" readonly name="pprice[{{ $index }}]" value="{{ $oldPrices[$index] ?? '' }}" onkeyup="select_value(this);this.value = this.value.replace(/[^0-9]/g, '' )" data-point='point_pack_trigger4{{ $index }}' placeholder="{{ translate('total_Price') }}">
                            </div>
                            @if($loop->index == 0)
                            <div class="col-1 p-0">
                                <a class='btn btn--primary btn-sm p-1 mt-2' onclick="add_new_package_html()"><i class='tio-add'></i></a>
                            </div>
                            @else
                            <div class="col-1 p-0">
                                <a class='btn btn-danger btn-sm p-1 mt-2' onclick="remove_html(this)"><i class='tio-remove'></i></a>
                            </div>
                            @endif
                        </div>
                        @endforeach
                        @else
                        <div class="row mt-2">
                            <div class='col-4 p-0 pr-1'>
                                <select class="form-control point_trigger2" name="package_id[0]" onchange="select_value(this)" data-point='point_trigger2'>
                                    <option value="">Select Packages</option>
                                    @if($package_list)
                                    @foreach($package_list as $packval)
                                    <option value="{{ $packval['id']}}" {{ (collect(old('package_id'))->contains($packval['id'])) ? 'selected' : '' }} data-type="{{ $packval['type']}}">{{ $packval['name']}} -({{ $packval['seats']}} people)</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-1 p-0 pr-1">
                                <input type='text' class="p-0 pl-1 form-control point_packNumber_triggers1" value="1" name="pnumber[0]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );$('.point_pack_triggers1').val($('.point_packamount_triggers1').val() * $('.point_packNumber_triggers1').val())" data-point='point_packNumber_triggers1' placeholder="{{ translate('number_of_day_and_stay') }}">
                            </div>
                            <div class="col-2 p-0 pr-1">
                                <input type='text' class="form-control point_packamount_triggers1" name="pperson[0]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );$('.point_pack_triggers1').val(this.value * $('.point_packNumber_triggers1').val())" data-point='point_packamount_triggers1' placeholder="{{ translate('enter_per_days') }}">
                            </div>
                            <div class="col-4 p-0 pr-1">
                                <input type='text' class="form-control point_pack_triggers1" readonly name="pprice[0]" disabled onkeyup="select_value(this);this.value = this.value.replace(/[^0-9]/g, '' )" data-point='point_pack_triggers1' placeholder="{{ translate('enter_Price') }}">
                            </div>
                            <div class="col-1 p-0">
                                <a class='btn btn--primary btn-sm p-1 mt-2' onclick="add_new_package_html()"><i class='tio-add'></i></a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 my-2">
                        <input type="checkbox" name="cities_tour" value="1">
                        <label class="title-color font-weight-bold h3" for="Language">{{ translate('Use_checkbox_only_for_city_tours') }}</label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <label class="title-color font-weight-bold h3" for="Language">{{ translate('time_slot(Optional)') }}</label>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="time_slot[]" class="times_slot_pick form-control" readonly value="{{ old('time_slot.0') }}">
                    </div>
                    <div class="col-md-3">
                        <a class="btn btn--primary btn-sm p-1 mt-2" onclick="time_slot_add()"><i class="tio-add"></i></a>
                    </div>
                </div>
                <div id="time_slot_container">
                    @foreach(old('time_slot',[]) as $index => $timeSlot)
                    <div class="row time_slot_add_html mt-2">
                        <div class="col-md-3">
                            <input type="text" name="time_slot[]" class="times_slot_pick form-control" value="{{ $timeSlot }}" data-index="{{ $index }}">
                        </div>
                        <div class="col-md-3">
                            <a class="btn btn-danger btn-sm p-1 mt-2" onclick="time_slot_remove(this)"><i class="tio-remove"></i></a>
                        </div>
                    </div>
                    @endforeach
                </div>

            </div>
        </div>
        <div class="mt-3 rest-part">
            <div class="row g-2">
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="form-group">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <label for="name" class="title-color text-capitalize font-weight-bold mb-0">{{ translate('tour_image') }}</label>
                                        <span class="badge badge-soft-info">{{ THEME_RATIO[theme_root_path()]['Product Image'] }}</span>
                                        <span class="input-label-secondary cursor-pointer" data-toggle="tooltip" title="{{ translate('add_your_Tour_image') }} JPG, PNG or JPEG {{ translate('format_within') }} 2MB">
                                            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <div class="custom_upload_input">
                                        <input type="file" name="tour_image" multiple class="custom-upload-input-file action-upload-color-image" id="" data-imgpreview="pre_tour_img_viewer" accept=".jpg, .webp, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                        <span class="delete_file_input btn btn-outline-danger btn-sm square-btn d--none">
                                            <i class="tio-delete"></i>
                                        </span>
                                        <div class="img_area_with_preview position-absolute z-index-2">
                                            <img id="pre_tour_img_viewer" class="h-auto aspect-1 bg-white d-none" src="dummy" alt="">
                                        </div>
                                        <div class="position-absolute h-100 top-0 w-100 d-flex align-content-center justify-content-center">
                                            <div class="d-flex flex-column justify-content-center align-items-center">
                                                <img alt="" class="w-75" src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-icon.svg') }}">
                                                <h3 class="text-muted">{{ translate('Upload_Image') }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-muted mt-2">
                                        {{ translate('image_format') }} : {{ 'Jpg, png, jpeg, webp,' }}
                                        <br>
                                        {{ translate('image_size') }} : {{ translate('max') }} {{ '2 MB' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="additional_image_column col-md-9">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <div>
                                    <label for="name"
                                        class="title-color text-capitalize font-weight-bold mb-0">{{ translate('upload_additional_image') }}</label>
                                    <span
                                        class="badge badge-soft-info">{{ THEME_RATIO[theme_root_path()]['Product Image'] }}</span>
                                    <span class="input-label-secondary cursor-pointer" data-toggle="tooltip"
                                        title="{{ translate('upload_any_additional_images_for_this_product_from_here') }}.">
                                        <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}"
                                            alt="">
                                    </span>
                                </div>
                            </div>
                            <p class="text-muted">{{ translate('upload_additional_service_images') }}</p>
                            <div class="row g-2" id="additional_Image_Section">
                                <div class="col-sm-12 col-md-4">
                                    <div class="custom_upload_input position-relative border-dashed-2">
                                        <input type="file" name="images[]" class="custom-upload-input-file action-add-more-image" data-index="1" data-imgpreview="additional_Image_1" accept=".jpg, .png, .webp, .jpeg, .gif, .bmp, .tif, .tiff|image/*" data-target-section="#additional_Image_Section">

                                        <span class="delete_file_input delete_file_input_section btn btn-outline-danger btn-sm square-btn d-none">
                                            <i class="tio-delete"></i>
                                        </span>

                                        <div class="img_area_with_preview position-absolute z-index-2 border-0">
                                            <img id="additional_Image_1" class="h-auto aspect-1 bg-white d-none" src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-icon.svg-dummy') }}" alt="">
                                        </div>
                                        <div class="position-absolute h-100 top-0 w-100 d-flex align-content-center justify-content-center">
                                            <div class="d-flex flex-column justify-content-center align-items-center">
                                                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-icon.svg') }}" class="w-75">
                                                <h3 class="text-muted">{{ translate('Upload_Image') }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>




        <div class="row justify-content-end gap-3 mt-3 mx-1">
            <button type="reset" id="reset" class="btn btn-secondary px-4">{{ translate('reset') }}</button>
            <button type="submit" class="btn btn--primary px-4">{{ translate('submit') }}</button>
        </div>
    </form>
</div>



<div class="modal fade exDistanceModal" tabindex="-1" aria-labelledby="exDistanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exDistanceModalLabel">Ex-Distance Charge</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Start</th>
                            <th>End</th>
                            <th>Charge</th>
                            <th>driver</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="distanceChargeTable">
                    </tbody>
                </table>
                <button type="button" class="btn btn-success btn-sm" onclick="addNewChargeRow()">+ Add Row</button>
                <button type="button" class="btn btn-primary btn-sm float-end" data-dismiss="modal" aria-label="Close">save</button>
            </div>
        </div>
    </div>
</div>

<span id="message-are-you-sure" data-text="{{ translate('are_you_sure') }}"></span>
<span id="message-yes-word" data-text="{{ translate('yes') }}"></span>
<span id="message-no-word" data-text="{{ translate('no') }}"></span>
<span id="image-path-of-product-upload-icon" data-path="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-icon.svg') }}"></span>
<span id="image-path-of-product-upload-icon-two" data-path="{{ dynamicAsset(path: 'public/assets/back-end/img/400x400/img2.jpg') }}"></span>
<span id="message-enter-choice-values" data-text="{{ translate('enter_choice_values') }}"></span>
<span id="message-upload-image" data-text="{{ translate('upload_Image') }}"></span>
<div class="modal fade infotourTypeShow" role="dialog" aria-label="modal order">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="close" data-dismiss="modal" aria-label="close">
                    <i class="tio-clear" aria-hidden="true"></i>
                </button>
                <h4 class="modal-title">Tour Info</h4>
                <div class="form-group">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="tour-options">
                                <div class="col-md-12 tour-option">
                                    <h5>1️⃣ Cities Tour</h5>
                                    <ol>
                                        <li>Users can book private trips by selecting the number of cabs required.</li>
                                        <li>Food and hotel options will be provided separately (not included in the package).</li>
                                        <li>The total amount will be calculated based on individual selections.</li>
                                    </ol>
                                </div>

                                <div class="col-md-12 tour-option">
                                    <h5>2️⃣ Special Tour (Fixed Date)</h5>
                                    <ol>
                                        <li>Users can book a complete package with a fixed date.</li>
                                        <li>The package includes food and hotel, which cannot be modified.</li>
                                        <li> Users can choose their preferred vehicle type.</li>
                                        <li> Users need to select the number of persons/tickets.</li>
                                    </ol>
                                </div>

                                <div class="col-md-12 tour-option">
                                    <h5>3️⃣ Special Tour (Flexible Date)</h5>
                                    <ol>
                                        <li>Users can book a complete package but with the flexibility to select their own date and time.</li>
                                        <li>The package includes food and hotel, which cannot be modified.</li>
                                        <li>Users can choose their preferred vehicle type.</li>
                                        <li> Users need to select the number of persons/tickets.</li>
                                    </ol>
                                </div>

                                <div class="col-md-12 tour-option">
                                    <h5>4️⃣ Daily Tour (Fixed Pickup Location)</h5>
                                    <ol>
                                        <li>Pickup location is predefined and cannot be changed.</li>
                                        <li> Users can select their own date and time for travel.</li>
                                        <li>A complete package is included.</li>
                                        <li>If the vehicle has 7 seats, users can select up to 7 persons only. </li>
                                        <li> If more than 7 persons need to travel (e.g., 8 persons), the user will need to book two separate times or choose a bigger vehicle (if available).</li>
                                    </ol>
                                </div>
                                <div class="col-md-12 tour-option">
                                    <h5>5️⃣ Daily Tour (Custom Pickup Location)</h5>
                                    <ol>
                                        <li>Users can select their own pickup location, date, and time.</li>
                                        <li> A complete package is included.</li>
                                        <li>If the vehicle has 7 seats, users can select up to 7 persons only.</li>
                                        <li> If more than 7 persons need to travel (e.g., 8 persons), the user will need to book two separate times or choose a bigger vehicle (if available).</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')

<script>
    function cab_ex_distance_model(id) {
        let inputValue = $(".cab-json-show" + id).val();
        let chargeData = [];

        // Parse JSON from input if available
        if (inputValue.trim() !== "") {
            try {
                chargeData = JSON.parse(inputValue);
            } catch (e) {
                console.error("Invalid JSON data", e);
            }
        } else {
            chargeData = [{
                start: 20,
                end: 30,
                charge: 0,
                driver: 0
            }];
        }

        populateDistanceChargeTable(id, chargeData, 'open');
        $(".exDistanceModal").attr("data-id", id);
        $(".exDistanceModal").modal("show");
    }

    function populateDistanceChargeTable(id, chargeData) {
        let tableBody = $("#distanceChargeTable");
        tableBody.empty();

        chargeData.forEach((item, index) => {
            let nextStart = chargeData[index + 1] ? chargeData[index + 1].start : '';

            tableBody.append(`
            <tr id="row-${id}-${index}">
                <td><input type="number" class="form-control" value="${item.start}" readonly></td>
                <td>
                    <input type="number" class="form-control end-value" value="${item.end}"  onkeyup="updateEndValue(${index}, ${id})" id="end-${id}-${index}">
                </td>
                <td>
                    <input type="number" class="form-control charge-value" value="${item.charge}" oninput="updateCharge(${index}, ${id})" id="charge-${id}-${index}">
                </td>
                <td>
                    <input type="number" class="form-control charge-value" value="${item.driver}" oninput="updateCharge(${index}, ${id})" id="driver-${id}-${index}">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" 
                        onclick="removeChargeRow(${index}, ${id})">X</button>
                </td>
            </tr>
        `);
        });

        $(".cab-json-show" + id).val(JSON.stringify(chargeData));
    }

    function addNewChargeRow() {
        let id = $(".exDistanceModal").attr("data-id");
        let inputValue = $(".cab-json-show" + id).val();
        let chargeData = inputValue ? JSON.parse(inputValue) : [];

        let lastEntry = chargeData.length > 0 ? chargeData[chargeData.length - 1] : {
            end: 30
        };
        let newStart = lastEntry.end + 1;
        let newEnd = newStart + 10;

        if (newEnd > 250) {
            alert("Maximum distance limit (250 km) reached.");
            return;
        }

        chargeData.push({
            start: newStart,
            end: newEnd,
            charge: 0,
            driver: 0
        });
        populateDistanceChargeTable(id, chargeData);
    }

    function removeChargeRow(index, id) {
        let inputValue = $(".cab-json-show" + id).val();
        let chargeData = inputValue ? JSON.parse(inputValue) : [];

        if (chargeData.length === 1) {
            alert("At least one row is required!");
            return;
        }

        chargeData.splice(index, 1);
        populateDistanceChargeTable(id, chargeData);
    }

    function updateCharge(index, id) {
        let inputValue = $(".cab-json-show" + id).val();
        let chargeData = inputValue ? JSON.parse(inputValue) : [];

        if (!chargeData[index]) {
            console.error("Error: Trying to update non-existent index:", index);
            return;
        }

        let chargeValue = document.getElementById(`charge-${id}-${index}`).value;
        let driverValue = document.getElementById(`driver-${id}-${index}`).value;

        chargeData[index].charge = parseFloat(chargeValue) || 0;
        chargeData[index].driver = parseFloat(driverValue) || 0;

        $(".cab-json-show" + id).val(JSON.stringify(chargeData));
    }

    function updateEndValue(index, id) {
        let inputValue = $(".cab-json-show" + id).val();
        let chargeData = inputValue ? JSON.parse(inputValue) : [];

        if (!chargeData[index]) {
            console.error("Error: Trying to update non-existent index:", index);
            return;
        }
        let newEndValue = document.getElementById(`end-${id}-${index}`).value.trim();
        if (newEndValue === "") {
            for (let i = index; i < chargeData.length; i++) {
                chargeData[i].start = i === index ? chargeData[i].start : null;
                chargeData[i].end = null;
            }
            populateDistanceChargeTable(id, chargeData);
            return;
        }

        newEndValue = parseInt(newEndValue);

        if (isNaN(newEndValue) || newEndValue > 250) {
            alert("Invalid value! End value must be a number and cannot exceed 250 km.");
            document.getElementById(`end-${id}-${index}`).value = chargeData[index].end;
            return;
        }

        chargeData[index].end = newEndValue;

        for (let i = index + 1; i < chargeData.length; i++) {
            if (chargeData[i - 1].end !== null) {
                chargeData[i].start = chargeData[i - 1].end + 1;
            } else {
                chargeData[i].start = null;
                chargeData[i].end = null;
            }
        }
        setTimeout(() => {
            populateDistanceChargeTable(id, chargeData);
        }, 1000);
    }
</script>
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/tags-input.min.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/spartan-multi-image-picker.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/back-end/plugins/summernote/summernote.min.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/product-add-update.js') }}"></script>
{{-- ck editor --}}
<script src="{{ dynamicAsset(path: 'public/js/ckeditor.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/js/sample.js') }}"></script>
<script src="https://unpkg.com/gijgo@1.9.14/js/gijgo.min.js" type="text/javascript"></script>

<script>
    // time picker
    $('#opentime').timepicker({
        uiLibrary: 'bootstrap4',
        modal: true,
        footer: true
    });
    $('#closetime').timepicker({
        uiLibrary: 'bootstrap4',
        modal: true,
        footer: true
    });

    initSample();
</script>
<script type="text/javascript">
    $(document).ready(function() {
        $('.ckeditor').ckeditor();
    });
</script>
<script type="text/javascript">
    $('.onfillup').on('input', function() {
        let val = $(this).val();
        let point = $(this).data('point');
        $(`.onfillup[data-point="${point}"]`).val(val);
    });


    $(document).ready(function() {
        $('.select2-multiple').select2({
            placeholder: "Select Package",
            allowClear: true
        });
    });

    let pointCounter_cab = "{{ count(old('cab_id', [1])) }}";

    function add_new_cab_html() {
        var newRow = `
        <div class="row mt-2">
            <div class='col-6 p-0 pr-1'>
                <select class="form-control point_trigger1${pointCounter_cab}" name="cab_id[${pointCounter_cab}]" onchange="select_value(this)" data-point='point_trigger1${pointCounter_cab}'>
                    <option value="" selected disabled>{{ translate('Select_cab') }}</option>
                    @foreach($cab_list as $cabs)
                    <option value="{{ $cabs['id'] }}">{{ $cabs['name'] }} - ({{ $cabs['seats'] }} seat)</option>
                    @endforeach
                </select>
            </div>
            <div class="col-5 p-0 pr-1">
                <input type='text' class="form-control   point_trigger4${pointCounter_cab}" name="price[${pointCounter_cab}]" value="" onkeyup="select_value(this);this.value = this.value.replace(/[^0-9]/g, '' )" data-point='point_trigger4${pointCounter_cab}' placeholder="{{ translate('enter_Price') }}">
            </div>
            <div class="col-1 p-0">
                <a class='btn btn-danger btn-sm p-1 mt-2' onclick="remove_html(this)"><i class='tio-remove'></i></a>
                <a class='btn btn--primary btn-sm p-1 mt-2 cab-ex-distance-charge${pointCounter_cab} {{((old('use_date',0) == 3)?'':'d-none' )}} specialTourwithoutdate' onclick="cab_ex_distance_model('${pointCounter_cab}')"><i class="tio-bonnet_open"> bonnet_open </i></a>
                <input type="hidden" class="from-control cab-json-show${pointCounter_cab}" name="excharge[${pointCounter_cab}]">
            </div>
        </div>
    `;
        $('.add_cab_append_multi').append(newRow);
        pointCounter_cab++;
    }


    let pointCounter_package = "{{ count(old('package_id', [1])) }}";

    function add_new_package_html() {
        var newRow = `
        <div class="row mt-2">
           
            <div class='col-4 p-0 pr-1'>
                <select class="form-control point_pack_trigger21${pointCounter_package}" name="package_id[${pointCounter_package}]" onchange="select_value(this)" data-point='point_pack_trigger21${pointCounter_package}'>
                <option value="">Select Packages</option>
                    @if($package_list)
                @foreach($package_list as $packval)
                    <option value="{{ $packval['id'] }}" data-type="{{ $packval['type']}}">{{ $packval['name'] }} - ({{ $packval['seats']}} people)</option>
                    @endforeach
                    @endif
                </select>
            </div>
            <div class="col-1 p-0 pr-1">
                                <input type='text' class="p-0 pl-1 form-control point_packNumber_trigger49${pointCounter_package}"  name="pnumber[${pointCounter_package}]" value="1" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );$('.point_pack_trigger49${pointCounter_package}').val($('.point_packamount_trigger49${pointCounter_package}').val() * $('.point_packNumber_trigger49${pointCounter_package}').val())" data-point='point_packNumber_trigger49${pointCounter_package}' placeholder="{{ translate('number_of_day_and_stay') }}">
                            </div>                            
                            <div class="col-2 p-0 pr-1">
                                <input type='text' class="form-control point_packamount_trigger49${pointCounter_package}" name="pperson[${pointCounter_package}]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );$('.point_pack_trigger49${pointCounter_package}').val(this.value * $('.point_packNumber_trigger49${pointCounter_package}').val())" data-point='point_packamount_trigger49${pointCounter_package}' placeholder="{{ translate('enter_per_days') }}">
                            </div>
            <div class="col-4 p-0 pr-1">
                <input type='text' class="form-control point_pack_trigger49${pointCounter_package}" readonly name="pprice[${pointCounter_package}]" value="" onkeyup="select_value(this);this.value = this.value.replace(/[^0-9]/g, '' )" data-point='point_pack_trigger49${pointCounter_package}' placeholder="{{ translate('enter_Price') }}">
            </div>
            <div class="col-1 p-0">
                <a class='btn btn-danger btn-sm p-1 mt-2' onclick="remove_html(this)"><i class='tio-remove'></i></a>
            </div>
        </div>
    `;
        $('.add_package_append_multi').append(newRow);
        pointCounter_package++;
        removeSelectedOptions();
    }


    let pointCounter = "{{ count(old('cab_id', [1])) }}";

    function remove_html(that) {
        $(that).closest('.row').remove();
    }

    function select_value(that) {
        var point = $(that).data('point');
        $(`.${point}`).val($(`.${point}`).val());
    }

    initializeDateRangePicker(false)

    function initializeDateRangePicker(isSingleDate) {
        var initialDateRange = "{{ old('startandend_date') }}";
        var today = moment().startOf('day');
        var startDate, endDate;
        if (initialDateRange) {
            var dates = initialDateRange.split(' - ');
            startDate = moment(dates[0], 'YYYY-MM-DD');
            endDate = moment(dates[1], 'YYYY-MM-DD');
        } else {
            startDate = moment().startOf('day');
            endDate = moment().endOf('day');
        }
        $('.start_date_end_date').daterangepicker({
            singleDatePicker: isSingleDate,
            startDate: startDate,
            endDate: endDate,
            minDate: today,
            locale: {
                format: 'YYYY-MM-DD'
            }
        }, function(start, end) {
            $('.datePickers').daterangepicker({
                singleDatePicker: true,
                locale: {
                    format: 'YYYY-MM-DD'
                },
                minDate: today,
                // minDate: start.format('YYYY-MM-DD'),
                maxDate: end.format('YYYY-MM-DD')
            });
        });
        if (initialDateRange && initialDateRange.includes(' - ')) {
            $('.datePickers').daterangepicker({
                singleDatePicker: true,
                locale: {
                    format: 'YYYY-MM-DD'
                },
                minDate: today,
                // minDate: startDate.format('YYYY-MM-DD'),
                maxDate: endDate.format('YYYY-MM-DD')
            });
        }
    }

    $('.pickup_times').timepicker({
        uiLibrary: 'bootstrap4',
        format: 'hh:MM TT', // Correct format for time display (12-hour with AM/PM)
        modal: true,
        footer: true
    });


    $(".getAddress_google").each(function() {
        let inputElement = this;
        let autocomplete = new google.maps.places.Autocomplete(inputElement, {
            types: ['establishment'],
        });
        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            if (!place.geometry) {
                $(inputElement).val('');
                return;
            }
            const lat = place.geometry.location.lat();
            const lng = place.geometry.location.lng();

            let addressComponents = place.address_components;
            let city = '';
            let state = '';
            let country = '';
            let partOfCity = '';
            let neighborhood = '';
            console.log(addressComponents);
            addressComponents.forEach(component => {
                const types = component.types;
                if (types.includes('locality')) {
                    city = component.long_name;
                }
                if (types.includes('administrative_area_level_1')) {
                    state = component.long_name;
                }
                if (types.includes('country')) {
                    country = component.long_name;
                }
                if (types.includes('sublocality_level_1')) {
                    partOfCity = component.long_name; // Sub-locality or area within the city
                }
                if (types.includes('neighborhood')) {
                    neighborhood = component.long_name; // Neighborhood name, if available
                }
            });
            $("#en_state_name").val(state);
            $("#en_country_name").val(country);
            $("#en_cities_name").val(city);
            $(".lat_location").val(lat);
            $(".long_location").val(lng);
            var points = $(inputElement).data('point');
            getHindiAddress(lat, lng, points, inputElement);
        });
    });

    function getHindiAddress(lat, lng, points, inputElement) {
        const apiKey = '{{$googleMapsApiKey}}';
        const geocodeUrl = `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&language=hi&key=${apiKey}`;

        $.getJSON(geocodeUrl, function(data) {
            if (data.status === 'OK' && data.results.length > 0) {
                let fullAddress = '';
                let city = '';
                let state = '';
                let country = '';
                let streetNumber = '';
                let streetName = '';
                console.log(data.results);

                data.results[0].address_components.forEach(function(component) {
                    const componentType = component.types[0];
                    switch (componentType) {
                        case 'street_number':
                            streetNumber = component.long_name; // Extract street number
                            break;
                        case 'route':
                            streetName = component.long_name; // Extract street name
                            break;
                        case 'locality':
                        case 'sublocality':
                            city = component.long_name; // Extract city name
                            break;
                        case 'administrative_area_level_1':
                            state = component.long_name; // Extract state name
                            break;
                        case 'country':
                            country = component.long_name; // Extract country name
                            break;
                    }
                });

                // Construct the full address in Hindi
                fullAddress = [streetNumber, streetName, city, state, country].filter(Boolean).join(', ');


                $("#in_state_name").val(state);
                $("#in_country_name").val(country);
                $("#in_cities_name").val(city);
            } else {
                console.error('Geocoding API error:', data.status);
            }
        });
    }



    $(".pickup_location_get").each(function() {
        let inputElement = this;
        let autocomplete = new google.maps.places.Autocomplete(inputElement, {
            types: ['establishment'],
        });
        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            if (!place.geometry) {
                $(inputElement).val('');
                return;
            }
            const lat = place.geometry.location.lat();
            const lng = place.geometry.location.lng();

            $(".pick_up_lat_location").val(lat);
            $(".pick_up_long_location").val(lng);
        });
    });
</script>
<script>
    $('.times_slot_pick').each(function() {
        $(this).timepicker({
            uiLibrary: 'bootstrap4',
            format: 'hh:MM TT',
            modal: true,
            footer: true
        });
    });

    function time_slot_add() {
        let html = `
        <div class="row time_slot_add_html mt-2">
            <div class="col-md-3">
                <input type="text" name="time_slot[]" readonly class="times_slot_pick form-control">
                </div>
                <div class="col-md-3">
                <a class="btn btn-danger btn-sm p-1 mt-2" onclick="time_slot_remove(this)"><i class="tio-remove"></i></a>
            </div>
        </div>
    `;
        document.getElementById('time_slot_container').insertAdjacentHTML('beforeend', html);

        $('.times_slot_pick').last().timepicker({
            uiLibrary: 'bootstrap4',
            format: 'hh:MM TT',
            modal: true,
            footer: true
        });
    }

    function time_slot_remove(element) {
        // Remove the parent row when the remove button is clicked
        element.closest('.time_slot_add_html').remove();
    }

    function use_date_functions(that) {
        if (that.value == 0) {
            $('.persons_transport_divShow').children().hide();
        } else {
            $('.persons_transport_divShow').children().show();
        }
        if (that.value == 0) {
            $('.use_interested_and_not').addClass('d-none');
        } else if (that.value == 2 || that.value == 4) {
            $('.daily_tour_full_comman').addClass('d-none');
            $('.daily_tour_full_address').removeClass('d-none');
        } else if (that.value == 3) {
            $('.use_interested_and_not').addClass('d-none');
        } else {
            $('.use_interested_and_not').removeClass('d-none');
        }
        if (that.value == 3) {
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-full-width",
                "timeOut": "10000",
            };
            toastr.warning("Please must Add The Extra Charges- Toll Tax, Driver charges etc", "WARNING");
            $('.specialTourwithoutdate').removeClass('d-none');
        } else {
            $('.specialTourwithoutdate').addClass('d-none');
        }
    }



    function removeSelectedOptions() {
        let selectedValues = [];

        // Collect all selected values
        document.querySelectorAll("select[name^='package_id']").forEach(select => {
            if (select.value) {
                selectedValues.push(select.value);
            }
        });

        document.querySelectorAll("select[name^='package_id']").forEach(select => {
            let currentValue = select.value;

            select.querySelectorAll("option").forEach(option => {
                if (option.value && selectedValues.includes(option.value) && option.value !== currentValue) {
                    option.disabled = true;
                } else {
                    option.disabled = false;
                }
            });
        });
    }
</script>
<script>
    function add_new_persons_html() {
        let min = parseInt($("input[name='min_person[]']").last().val()) || 0;
        let max = parseInt($("input[name='max_person[]']").last().val()) || 0;

        if (min > max) {
            alert("Minimum person cannot be greater than maximum person.");
            return;
        }

        let html = `
    <div class="row mt-2">
        <div class='col-3'>
            <input type='text' class="form-control" name="min_person[]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );validatePersonPrice(this)" placeholder="Enter Min">
        </div>
        <div class="col-4">
            <input type='text' class="form-control" name="max_person[]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );validatePersonPrice(this)" placeholder="Enter Max">
        </div>
        <div class="col-4">
            <input type='text' class="form-control" name="person_price[]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' )" placeholder="Enter Price">
        </div>
        <div class="col-1 p-0">
            <button type="button" class="btn btn-danger btn-sm p-1 mt-2 remove-row"><i class='tio-clear'></i></button>
        </div>
    </div>`;
        $('.add_persons_append_multi').append(html);
    }
    $(document).on('click', '.remove-row', function() {
        $(this).closest('.row').remove();
    });

    $(document).on('change', "input[name='min_person[]']", function() {
        let index = $("input[name='min_person[]']").index(this);

        if (index > 0) {
            let prevMax = parseInt($("input[name='max_person[]']").eq(index - 1).val()) || 0;
            let currentMin = parseInt($(this).val()) || 0;

            if (currentMin <= prevMax) {
                toastr.error("Min people must be greater than previous row’s Max.");
                $(this).val(prevMax + 1);
            }
        }
    });

    function validateInputValue(input) {
        let val = input.value;
        val = val.replace(/[^0-9.]/g, '');
        val = val.replace(/(\..*)\./g, '$1');
        if (val.includes('.')) {
            setTimeout(() => {
                if ($('.number_of_day_number').val().replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1').includes('.')) {
                    $('.number_of_day_number').val('0.5');
                }
            }, 300);
        } else {
            val = val.replace(/^0+(?!$)/, '');
        }
        input.value = val;
    }

    function add_new_person_transport_html() {
        var html = `
        <div class="row mt-2">
                            <div class='col-2 px-1'>
                                <input type='text' class="form-control px-2" name="start_person[]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' )" placeholder="{{ translate('number') }}">
                            </div>
                            <div class="col-2 px-1">
                                <input type='text' class="form-control px-2" name="end_person[]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );validateStartEndPersonsWithLastEnd(this)" placeholder="{{ translate('number') }}">
                            </div>
                            <div class='col-2 px-1'>
                                <input type='text' class="form-control px-2" name="person_pick[]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' )" placeholder="{{ translate('pick') }}">
                            </div>
                            <div class="col-2 px-1">
                                <input type='text' class="form-control px-2" name="person_drop[]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' )" placeholder="{{ translate('drop') }}">
                            </div>
                            <div class="col-2 px-1">
                                <input type='text' class="form-control px-2" name="person_both[]" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' )" placeholder="{{ translate('both') }}">
                            </div>
                            <div class="col-2 px-1">
                                <button type="button" class="btn btn-danger btn-sm p-1 mt-2 remove-row"><i class='tio-clear'></i></button>
                            </div>
                        </div>`;

        $('.add_persons_transport_append_multi').append(html);

    }

    function validateStartEndPersonsWithLastEnd(currentInput) {
        const row = currentInput.closest('.row');
        if (!row) {
            console.warn('No .row parent found.');
            return false;
        }
        const startInput = row.querySelector('input[name="start_person[]"]');
        const endInput = row.querySelector('input[name="end_person[]"]');
        if (!startInput || !endInput) {
            console.warn('Start or End input not found in this row.');
            return false;
        }
        const startVal = parseInt(startInput.value, 10);
        const endVal = parseInt(endInput.value, 10);
        startInput.classList.remove('is-invalid');
        endInput.classList.remove('is-invalid');
        let valid = true;
        if (isNaN(startVal) || isNaN(endVal)) {
            startInput.classList.add('is-invalid');
            endInput.classList.add('is-invalid');
            valid = false;
        } else if (startVal > endVal) {
            startInput.classList.add('is-invalid');
            endInput.classList.add('is-invalid');
            valid = false;
        }

        return valid;
    }

    function validatePersonPrice(currentInput) {
        const row = currentInput.closest('.row');
        if (!row) {
            console.warn('No .row parent found.');
            return false;
        }
        const startInput = row.querySelector('input[name="min_person[]"]');
        const endInput = row.querySelector('input[name="max_person[]"]');
        if (!startInput || !endInput) {
            console.warn('Start or End input not found in this row.');
            return false;
        }
        const startVal = parseInt(startInput.value, 10);
        const endVal = parseInt(endInput.value, 10);
        startInput.classList.remove('is-invalid');
        endInput.classList.remove('is-invalid');
        let valid = true;
        if (isNaN(startVal) || isNaN(endVal)) {
            startInput.classList.add('is-invalid');
            endInput.classList.add('is-invalid');
            valid = false;
        } else if (startVal > endVal) {
            startInput.classList.add('is-invalid');
            endInput.classList.add('is-invalid');
            valid = false;
        }

        return valid;
    }
</script>


@endpush