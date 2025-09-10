@extends('layouts.back-end.app-tour')

@section('title', translate('view_Tour'))

@push('css_or_js')
<link href="{{ dynamicAsset(path: 'public/assets/back-end/css/tags-input.min.css') }}" rel="stylesheet">
<link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">
<script src="https://maps.googleapis.com/maps/api/js?key={{$googleMapsApiKey}}&libraries=places"></script>
<link href="https://unpkg.com/gijgo@1.9.14/css/gijgo.min.css" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="content container-fluid">
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 d-flex gap-2">
            {{ translate('view_Tour') }}
        </h2>
    </div>
        <div class="card">
            <input type="hidden" name="id" value="{{ $getData['id'] }}">
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
                <?php
                $translate = [];
                if (count($getData['translations'])) {
                    foreach ($getData['translations'] as $translations) {
                        if ($translations->locale == $lang && $translations->key == 'tour_name') {
                            $translate[$lang]['tour_name'] = $translations->value;
                        }
                        if ($translations->locale == $lang && $translations->key == 'description') {
                            $translate[$lang]['description'] = $translations->value;
                        }
                        if ($translations->locale == $lang && $translations->key == 'cities_name') {
                            $translate[$lang]['cities_name'] = $translations->value;
                        }
                        if ($translations->locale == $lang && $translations->key == 'country_name') {
                            $translate[$lang]['country_name'] = $translations->value;
                        }
                        if ($translations->locale == $lang && $translations->key == 'state_name') {
                            $translate[$lang]['state_name'] = $translations->value;
                        }
                        if ($translations->locale == $lang && $translations->key == 'part_located') {
                            $translate[$lang]['part_located'] = $translations->value;
                        }
                        if ($translations->locale == $lang && $translations->key == 'highlights') {
                            $translate[$lang]['highlights'] = $translations->value;
                        }
                        if ($translations->locale == $lang && $translations->key == 'inclusion') {
                            $translate[$lang]['inclusion'] = $translations->value;
                        }
                        if ($translations->locale == $lang && $translations->key == 'exclusion') {
                            $translate[$lang]['exclusion'] = $translations->value;
                        }
                        if ($translations->locale == $lang && $translations->key == 'terms_and_conditions') {
                            $translate[$lang]['terms_and_conditions'] = $translations->value;
                        }
                        if ($translations->locale == $lang && $translations->key == 'cancellation_policy') {
                            $translate[$lang]['cancellation_policy'] = $translations->value;
                        }
                        if ($translations->locale == $lang && $translations->key == 'notes') {
                            $translate[$lang]['notes'] = $translations->value;
                        }
                    }
                }
                ?>
                <div class="{{ $lang != $defaultLanguage ? 'd-none' : '' }} form-system-language-form" id="{{ $lang }}-form">
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}tour_name">{{ translate('tour_name') }} ({{ strtoupper($lang) }}) </label>
                                <input type="text" {{ $lang == $defaultLanguage ? 'required' : '' }} name="tour_name[]" id="{{ $lang }}tour_name" class="form-control @error('tour_name.'.$loop->index) is-invalid @enderror" value="{{ old('tour_name.'.$loop->index,($lang == $defaultLanguage ? $getData['tour_name'] : $translate[$lang]['tour_name'] ?? '') ) }}" placeholder="{{ translate('tour_name') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}tour_name">{{ translate('tour_type') }} </label>
                                <select {{ $lang == $defaultLanguage ? 'required' : '' }} name="tour_type" id="{{ $lang }}tour_type" class="form-control @error('tour_type') is-invalid @enderror tour_types" onchange="$('.tour_types').val(this.value)">
                                    @if(!empty($typeList) && count($typeList))
                                    @foreach($typeList as $val)
                                    <option value="{{$val['slug']}}" {{ (( old('tour_type',$getData['tour_type']) == $val['slug'] )?'selected':'' )}}> {{ $val['name'] }}</option>
                                    @endforeach
                                    @endif
                                </select>

                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_traveller_name">{{ translate('traveller') }} </label>
                                <select {{ $lang == $defaultLanguage ? 'required' : '' }} name="created_id" id="{{ $lang }}_traveller_name" class="form-control @error('created_id') is-invalid @enderror created_id" onchange="$('.created_id').val(this.value)">
                                    <option value="0" selected disabled>Select Traveller</option>
                                    <option value="0" {{ ((old('created_id',$getData['created_id']) == '0' )?'selected':'' ) }}>All Traveller</option>
                                    @if(!empty($travelar_list) && count($travelar_list) > 0)
                                    @foreach($travelar_list as $val)
                                    <option value="{{ $val['id']}}" {{ ((old('created_id',$getData['created_id']) == $val['id'] )?'selected':'' ) }}>{{$val['company_name']}}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_cities_name">{{ translate('cities_name') }} </label>
                                <input type="text" {{ $lang == $defaultLanguage ? 'required' : '' }} name="cities_name[]" id="{{ $lang }}_cities_name" class="form-control @error('cities_name.'.$loop->index) is-invalid @enderror getAddress_google" value="{{ old('cities_name.'.$loop->index,($lang == $defaultLanguage ? $getData['cities_name'] : $translate[$lang]['cities_name'] ?? '') ) }}" placeholder="{{ translate('cities_name') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_country_name">{{ translate('country_name') }} </label>
                                <input type="text" {{ $lang == $defaultLanguage ? 'required' : '' }} name="country_name[]" aria-readonly="readonly" readonly id="{{ $lang }}_country_name" class="form-control @error('country_name.'.$loop->index) is-invalid @enderror " value="{{ old('country_name.'.$loop->index,($lang == $defaultLanguage ? $getData['country_name'] : $translate[$lang]['country_name'] ?? '') ) }}" placeholder="{{ translate('country_name') }}" data-toggle="tooltip" role='tooltip' data-title='Please Select Cities'>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_state_name">{{ translate('state_name') }} </label>
                                <input type="text" {{ $lang == $defaultLanguage ? 'required' : '' }} name="state_name[]" aria-readonly="readonly" readonly id="{{ $lang }}_state_name" class="form-control @error('state_name.'.$loop->index) is-invalid @enderror" value="{{ old('state_name.'.$loop->index,($lang == $defaultLanguage ? $getData['state_name'] : $translate[$lang]['state_name'] ?? '') ) }}" placeholder="{{ translate('state_name') }}" data-toggle="tooltip" role='tooltip' data-title='Please Select Cities'>
                                <input type="hidden" name='lat' class="lat_location" value="{{ old('lat', $getData['lat']) }}">
                                <input type="hidden" name='long' class="long_location" value="{{ old('long', $getData['long']) }}">
                            </div>
                        </div>
                        <div class="col-md-4 d-none">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_part_located">{{ 'In which part is it located' }} </label>
                                <input type="text" name="part_located[]" id="{{ $lang }}_part_located" class="form-control @error('part_located.'.$loop->index) is-invalid @enderror " value="{{ old('part_located.'.$loop->index,($lang == $defaultLanguage ? $getData['part_located'] : $translate[$lang]['part_located'] ?? '') ) }}" placeholder="{{ translate('In which part is it located') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_ex_distance">{{ translate('1km_ex_distance_fee') }} </label>
                                <input type="text" name="ex_distance" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );$('.ex_distance_fee').val(this.value)" class="ex_distance_fee form-control @error('ex_distance') is-invalid @enderror " value="{{ old('ex_distance',$getData['ex_distance']) }}" placeholder="{{ translate('1km_ex_distance_fee') }}" required>
                            </div>
                        </div>
                        <div class="col-md-8"></div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_description">{{ translate('description') }} ({{ strtoupper($lang) }}) </label>
                                <textarea {{ $lang == $defaultLanguage ? 'required' : '' }} name="description[]" id="{{ $lang }}_description" class="form-control ckeditor @error('description.'.$loop->index) is-invalid @enderror">{{ old('description.'.$loop->index,($lang == $defaultLanguage ? $getData['description'] : $translate[$lang]['description'] ?? '')) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_highlights">{{ translate('highlights') }} ({{ strtoupper($lang) }}) </label>
                                <textarea {{ $lang == $defaultLanguage ? 'required' : '' }} name="highlights[]" id="{{ $lang }}_highlights" class="form-control ckeditor @error('highlights.'.$loop->index) is-invalid @enderror">{{ old('highlights.'.$loop->index,($lang == $defaultLanguage ? $getData['highlights'] : $translate[$lang]['highlights'] ?? '')) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_Inclusion">{{ translate('Inclusion') }} ({{ strtoupper($lang) }}) </label>
                                <textarea {{ $lang == $defaultLanguage ? 'required' : '' }} name="inclusion[]" id="{{ $lang }}_Inclusion" class="form-control ckeditor @error('inclusion.'.$loop->index) is-invalid @enderror">{{ old('inclusion.'.$loop->index,($lang == $defaultLanguage ? $getData['inclusion'] : $translate[$lang]['inclusion'] ?? '')) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_exclusion">{{ translate('exclusion') }} ({{ strtoupper($lang) }}) </label>
                                <textarea {{ $lang == $defaultLanguage ? 'required' : '' }} name="exclusion[]" id="{{ $lang }}_exclusion" class="form-control ckeditor @error('exclusion.'.$loop->index) is-invalid @enderror">{{ old('exclusion.'.$loop->index,($lang == $defaultLanguage ? $getData['exclusion'] : $translate[$lang]['exclusion'] ?? '')) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_terms_and_conditions">{{ translate('terms_and_conditions') }} ({{ strtoupper($lang) }}) </label>
                                <textarea {{ $lang == $defaultLanguage ? 'required' : '' }} name="terms_and_conditions[]" id="{{ $lang }}_terms_and_conditions" class="form-control ckeditor @error('terms_and_conditions.'.$loop->index) is-invalid @enderror">{{ old('terms_and_conditions.'.$loop->index,($lang == $defaultLanguage ? $getData['terms_and_conditions'] : $translate[$lang]['terms_and_conditions'] ?? '')) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_cancellation_policy">{{ translate('cancellation_policy ') }} ({{ strtoupper($lang) }}) </label>
                                <textarea {{ $lang == $defaultLanguage ? 'required' : '' }} name="cancellation_policy[]" id="{{ $lang }}_cancellation_policy" class="form-control ckeditor @error('cancellation_policy.'.$loop->index) is-invalid @enderror">{{ old('cancellation_policy.'.$loop->index,($lang == $defaultLanguage ? $getData['cancellation_policy'] : $translate[$lang]['cancellation_policy'] ?? '')) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="title-color" for="{{ $lang }}_notes">{{ translate('notes ') }} ({{ strtoupper($lang) }}) </label>
                                <textarea {{ $lang == $defaultLanguage ? 'required' : '' }} name="notes[]" id="{{ $lang }}_notes" class="form-control ckeditor @error('notes.'.$loop->index) is-invalid @enderror">{{ old('notes.'.$loop->index,($lang == $defaultLanguage ? $getData['notes'] : $translate[$lang]['notes'] ?? '')) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="lang[]" value="{{ $lang }}">
                </div>
                @endforeach
                <div class="row">
                    <div class='col-md-12 form-group'>
                        <label class="title-color font-weight-bold h3" for="Language">{{ translate('Package') }}</label>
                    </div>
                    <div class="col-md-6 form-group add_cab_append_multi">
                        <div class="row">
                            <div class='col-6'>
                                <label class="title-color fw-bolder" for="Language">{{ translate('cab_name') }}</label>
                            </div>
                            <div class="col-6">
                                <label class="title-color fw-bolder" for="Language">{{ translate('Price') }}</label>
                            </div>
                        </div>
                        <input type="hidden" id="total_rows_cab" name="total_rows_cab" value="{{ old('total_rows_cab', count(json_decode($getData['cab_list_price'] ?? `['']`, true) ?? [''])) }}">
                        @php
                        $totalRows_cab = old('total_rows_cab', count(json_decode($getData['cab_list_price'] ?? '[""]', true) ?? [""]));
                        $oldData = json_decode($getData['cab_list_price'], true);
                        @endphp

                        @for ($i = 0; $i < $totalRows_cab; $i++)
                            <div class="row mt-2">
                            <div class='col-6 p-0 pr-1'>
                                <select class="form-control point_trigger16{{$i}}" name="cab_id[{{ $i }}]" onchange="select_value(this)" data-point='point_trigger16{{$i}}'>
                                    <option value="" selected disabled>{{ translate('Select_cab') }}</option>
                                    @if($cab_list)
                                    @foreach($cab_list as $cabs)
                                    <option value="{{ $cabs['id'] }}" {{ (collect(old('cab_id.' . $i, $oldData[$i]['cab_id'] ?? ''))->contains($cabs['id'])) ? 'selected' : '' }}>{{ $cabs['name'] }} -({{ $cabs['seats'] }} seat)</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-5 p-0 pr-1">
                                <input type='text' class="form-control   point_trigger46{{$i}}" name="price[{{ $i }}]" value="{{ old('price.' . $i, $oldData[$i]['price'] ?? '') }}" onkeyup="select_value(this)" data-point='point_trigger46{{$i}}' placeholder="{{ translate('enter_Price') }}">
                            </div>

                            @if($i == 0)
                            <div class="col-1 p-0">
                                <a class='btn btn--primary btn-sm p-1 mt-2' onclick="add_new_cab_html()"><i class='tio-add'></i></a>
                            </div>
                            @else
                            <div class="col-1 p-0">
                                <a class='btn btn-danger btn-sm p-1 mt-2' onclick="remove_html(this)"><i class='tio-remove'></i></a>
                            </div>
                            @endif
                    </div>
                    @endfor
                </div>
                <div class="col-md-6 add_package_append_multi">
                    <div class="row">
                        <div class='col-6'>
                            <label class="title-color fw-bolder" for="Language">{{ translate('package_name') }}</label>
                        </div>
                        <div class="col-6">
                            <label class="title-color fw-bolder" for="Language">{{ translate('Price') }}</label>
                        </div>
                    </div>
                    <input type="hidden" id="total_rows_package" name="total_rows_package" value="{{ old('total_rows_package', count(json_decode($getData['package_list_price'] ?? `['']`, true) ?? [''])) }}">
                    @php
                    $totalRows_package = old('total_rows_package', count(json_decode($getData['package_list_price'] ?? '[""]', true) ?? [""]));
                    $oldData = json_decode($getData['package_list_price'], true);
                    @endphp
                    @for ($i = 0; $i < $totalRows_package; $i++)
                        <div class="row mt-2">
                        <div class='col-6 p-0 pr-1'>
                            <select class="form-control point_trigger26{{$i}}" name="package_id[{{ $i }}]" onchange="select_value(this)" data-point='point_trigger26{{$i}}'>
                                @if($package_list)
                                @foreach($package_list as $packval)
                                <option value="{{ $packval['id'] }}" {{ (collect(old('package_id.' . $i, $oldData[$i]['package_id'] ?? []))->contains($packval['id'])) ? 'selected' : '' }}>{{ $packval['name'] }} -({{ $packval['seats'] }} people)</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-5 p-0 pr-1">
                            <input type='text' class="form-control   point_trigger48{{$i}}" name="pprice[{{ $i }}]" value="{{ old('pprice.' . $i, $oldData[$i]['pprice'] ?? '') }}" onkeyup="select_value(this)" data-point='point_trigger48{{$i}}' placeholder="{{ translate('enter_Price') }}">
                        </div>

                        @if($i == 0)
                        <div class="col-1 p-0">
                            <a class='btn btn--primary btn-sm p-1 mt-2' onclick="add_new_package_html()"><i class='tio-add'></i></a>
                        </div>
                        @else
                        <div class="col-1 p-0">
                            <a class='btn btn-danger btn-sm p-1 mt-2' onclick="remove_html(this)"><i class='tio-remove'></i></a>
                        </div>
                        @endif
                </div>
                @endfor

            </div>


            <div class='col-md-3 form-group'>
                <label class="title-color font-weight-bold h3" for="Language">{{ translate('date_choose') }}</label>
                <a class="btn btn-sm btn-info" onclick="$('.infotourTypeShow').modal('show')">INFO</a>
                <select class="form-control" name='use_date' onchange="use_date_functions(this)">
                    <option value="0" {{((old('use_date',$getData['use_date']) == 0)?'selected':'' )}}>Cities Tour</option>
                    <option value="1" {{((old('use_date',$getData['use_date']) == 1)?'selected':'' )}}>Special Tour(With Date)</option>
                    <option value="4" {{((old('use_date',$getData['use_date']) == 4)?'selected':'' )}}>Special Tour(Without Date)</option>
                    <option value="2" {{((old('use_date',$getData['use_date']) == 2)?'selected':'' )}}>Daily Tour(With Address)</option>
                    <option value="3" {{((old('use_date',$getData['use_date']) == 3)?'selected':'' )}}>Daily Tour(WithOut Address)</option>
                </select>
            </div>
           
            <div class="col-md-3 form-group  {{((old('use_date',$getData['use_date']) == 0)?'d-none':'' )}} {{((old('use_date',$getData['use_date']) == 2)?'d-none':'' )}} {{((old('use_date',$getData['use_date']) == 4)?'d-none':'' )}} {{((old('use_date',$getData['use_date']) == 3)?'d-none':'' )}} use_interested_and_not daily_tour_full_comman">
                <label class="title-color font-weight-bold h3" for="Language">{{ translate('start_date_and_end_date') }}</label>
                <input type="text" class="form-control all_select_data start_date_end_date" data-point='8' value="{{ old('startandend_date',($getData['startandend_date']??''))}}" name='startandend_date' placeholder="{{ translate('enter_start_to_end_date') }}">
            </div>
            <div class="col-md-3 form-group  {{((old('use_date',$getData['use_date']) == 0)?'d-none':'' )}} {{((old('use_date',$getData['use_date']) == 2)?'d-none':'' )}} {{((old('use_date',$getData['use_date']) == 4)?'d-none':'' )}} {{((old('use_date',$getData['use_date']) == 3)?'d-none':'' )}} use_interested_and_not daily_tour_full_comman">
                <label class="title-color font-weight-bold h3" for="Language">{{ translate('pickup_time') }}</label>
                <input type="text" class="form-control pickup_times" value="{{ old('pickup_time',($getData['pickup_time']??'')) }}" name='pickup_time' placeholder="{{ translate('pickup_time') }}" readonly>
            </div>
            <div class="col-md-3 form-group  {{((old('use_date',$getData['use_date']) == 0)?'d-none':'' )}} {{((old('use_date',$getData['use_date']) == 3)?'d-none':'' )}} use_interested_and_not daily_tour_full_address">
                <label class="title-color font-weight-bold h3" for="Language">{{ translate('pickup_location') }}</label>
                <input type="text" class="form-control pickup_location_get" value="{{ old('pickup_location',($getData['pickup_location']??'')) }}" name='pickup_location' placeholder="{{ translate('pickup_location') }}">
                <input type="hidden" class="pick_up_lat_location" name='pickup_lat' value="{{ old('pickup_lat',($getData['pickup_lat']??'')) }}">
                <input type="hidden" class="pick_up_long_location" name='pickup_long' value="{{ old('pickup_long',($getData['pickup_long']??'')) }}">
            </div>
        </div>
            <div class="row">
            <div class="col-12 my-2">
                <input type="checkbox" name="cities_tour" value="1" {{ (( $getData['cities_tour'] == '1' )? 'checked' : '' ) }}>
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
            @foreach(old('time_slot', json_decode($getData['time_slot'] ?? json_encode(['']))) as $index => $timeSlot)
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
`       </div>
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
                                            <img id="pre_tour_img_viewer" class="h-auto aspect-1 bg-white" src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . $getData['tour_image'], type: 'backend-product') }}" alt="">
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
                            <p class="text-muted">{{ translate('upload_additional_images') }}</p>
                            <div class="coba-area">

                                <div class="row g-2" id="additional_Image_Section">

                                    @if (!empty($getData['image']) && json_decode($getData['image'],true))
                                    @foreach (json_decode($getData['image'],true) as $key => $photo)
                                    @php($unique_id = rand(1111, 9999))

                                    <div class="col-sm-12 col-md-4">
                                        <div
                                            class="custom_upload_input custom-upload-input-file-area position-relative border-dashed-2">
                                            <a class="delete_file_input_css btn btn-outline-danger btn-sm square-btn"
                                                href="{{ route('tour-vendor.tour_visits.tour-delete-image', ['id' => $getData['id'], 'name' => $photo]) }}">
                                                <i class="tio-delete"></i>
                                            </a>
                                            <div
                                                class="img_area_with_preview position-absolute z-index-2 border-0">
                                                <img id="additional_Image_{{ $unique_id }}"
                                                    alt=""
                                                    class="h-auto aspect-1 bg-white onerror-add-class-d-none"
                                                    src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/tour_visit/' . $photo, type: 'backend-product') }}">
                                            </div>
                                            <div
                                                class="position-absolute h-100 top-0 w-100 d-flex align-content-center justify-content-center">
                                                <div
                                                    class="d-flex flex-column justify-content-center align-items-center">
                                                    <img alt=""
                                                        src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-icon.svg') }}"
                                                        class="w-75">
                                                    <h3 class="text-muted">{{ translate('Upload_Image') }}
                                                    </h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                    @endif

                                    <div class="col-sm-12 col-md-4">
                                        <div class="custom_upload_input position-relative border-dashed-2">
                                            <input type="file" name="images[]"
                                                class="custom-upload-input-file action-add-more-image" data-index="1"
                                                data-imgpreview="additional_Image_1"
                                                accept=".jpg, .webp, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*"
                                                data-target-section="#additional_Image_Section">

                                            <span
                                                class="delete_file_input delete_file_input_section btn btn-outline-danger btn-sm square-btn d-none">
                                                <i class="tio-delete"></i>
                                            </span>

                                            <div class="img_area_with_preview position-absolute z-index-2 border-0">
                                                <img id="additional_Image_1" class="h-auto aspect-1 bg-white d-none"
                                                    alt="" src="">
                                            </div>
                                            <div
                                                class="position-absolute h-100 top-0 w-100 d-flex align-content-center justify-content-center">
                                                <div
                                                    class="d-flex flex-column justify-content-center align-items-center">
                                                    <img alt=""
                                                        src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-icon.svg') }}"
                                                        class="w-75">
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
        </div>
</div>


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

<span id="message-are-you-sure" data-text="{{ translate('are_you_sure') }}"></span>
<span id="message-yes-word" data-text="{{ translate('yes') }}"></span>
<span id="message-no-word" data-text="{{ translate('no') }}"></span>
<span id="image-path-of-product-upload-icon" data-path="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/product-upload-icon.svg') }}"></span>
<span id="image-path-of-product-upload-icon-two" data-path="{{ dynamicAsset(path: 'public/assets/back-end/img/400x400/img2.jpg') }}"></span>
<span id="message-enter-choice-values" data-text="{{ translate('enter_choice_values') }}"></span>
<span id="message-upload-image" data-text="{{ translate('upload_Image') }}"></span>
@endsection

@push('script')
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/tags-input.min.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/spartan-multi-image-picker.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/back-end/plugins/summernote/summernote.min.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/product-add-update.js') }}"></script>
{{-- ck editor --}}
<script src="{{ dynamicAsset(path: 'public/js/ckeditor.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/js/sample.js') }}"></script>
<script src="https://unpkg.com/gijgo@1.9.14/js/gijgo.min.js" type="text/javascript"></script>

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
        element.closest('.time_slot_add_html').remove();
    }

     function use_date_functions(that) {
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
    }
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
    let pointCounter = 1;

    function add_new_cab_html() {
        var totalRows = parseInt(document.getElementById('total_rows_cab').value) + 1;
        document.getElementById('total_rows_cab').value = totalRows;

        var newRow = `
        <div class="row mt-2">
            <div class='col-6 p-0 pr-1'>
                <select class="form-control point_trigger1${totalRows}" name="cab_id[${totalRows}]" onchange="select_value(this)" data-point='point_trigger1${totalRows}'>
                    <option value="" selected disabled>{{ translate('Select_cab') }}</option>
                    @foreach($cab_list as $cabs)
                    <option value="{{ $cabs['id'] }}">{{ $cabs['name'] }} ({{ $cabs['seats'] }} seat)</option>
                    @endforeach
                </select>
            </div>
            <div class="col-5 p-0 pr-1">
                <input type='text' class="form-control   point_trigger4${totalRows}" name="price[${totalRows}]" value="" onkeyup="select_value(this)" data-point='point_trigger4${totalRows}' placeholder="{{ translate('enter_Price') }}">
            </div>
            <div class="col-1 p-0">
                <a class='btn btn-danger btn-sm p-1 mt-2' onclick="remove_html(this)"><i class='tio-remove'></i></a>
            </div>
        </div>
    `;
        $('.add_cab_append_multi').append(newRow);
    }

    function add_new_package_html() {
        var totalRows = parseInt(document.getElementById('total_rows_package').value) + 1;
        document.getElementById('total_rows_package').value = totalRows;

        var newRow = `
        <div class="row mt-2">
           
            <div class='col-6 p-0 pr-1'>
                <select class="form-control point_trigger21${totalRows}" name="package_id[${totalRows}]" onchange="select_value(this)" data-point='point_trigger21${totalRows}'>
                    @if($package_list)
                    @foreach($package_list as $packval)
                    <option value="{{ $packval['id'] }}">{{ $packval['name'] }} -({{ $packval['seats'] }} people)</option>
                    @endforeach
                    @endif
                </select>
            </div>
            <div class="col-5 p-0 pr-1">
                <input type='text' class="form-control   point_trigger49${totalRows}" name="pprice[${totalRows}]" value="" onkeyup="select_value(this)" data-point='point_trigger49${totalRows}' placeholder="{{ translate('enter_Price') }}">
            </div>
            <div class="col-1 p-0">
                <a class='btn btn-danger btn-sm p-1 mt-2' onclick="remove_html(this)"><i class='tio-remove'></i></a>
            </div>
        </div>
    `;
        $('.add_package_append_multi').append(newRow);
    }



    function remove_html(that) {
        $(that).closest('.row').remove();
    }

    function select_value(that) {
        var point = $(that).data('point');
        $(`.${point}`).val($(`.${point}`).val());
    }
    initializeDateRangePicker(false)

    function initializeDateRangePicker(isSingleDate) {
        var initialDateRange = "{{ old('startandend_date',($getData['startandend_date']??''))}}";
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
            locale: {
                format: 'YYYY-MM-DD'
            }
        }, function(start, end) {
            $('.datePickers').daterangepicker({
                singleDatePicker: true,
                locale: {
                    format: 'YYYY-MM-DD'
                },
                minDate: start.format('YYYY-MM-DD'),
                maxDate: end.format('YYYY-MM-DD')
            });
        });
        if (initialDateRange && initialDateRange.includes(' - ')) {
            $('.datePickers').daterangepicker({
                singleDatePicker: true,
                locale: {
                    format: 'YYYY-MM-DD'
                },
                minDate: startDate.format('YYYY-MM-DD'),
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


@endpush