@extends('layouts.back-end.app-trustees')

@section('title', translate('add_ads_Trust'))

@section('content')
@php 
use App\Utils\Helpers;
@endphp
<div class="content container-fluid">
    <div class="mb-3">
        <h2 class="h1 mb-0 d-flex gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/') }}" alt="">
            {{ translate('add_ads_Trust') }}
        </h2>
    </div>
    <div class="row">
        <!-- Form for adding new add_ads_Trust -->
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('trustees-vendor.ads-management.ad-store') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <ul class="nav nav-tabs w-fit-content mb-4">
                            @foreach($languages as $lang)
                            <li class="nav-item text-capitalize">
                                <a class="nav-link form-system-language-tab cursor-pointer {{$lang == $defaultLanguage? 'active':''}}" id="{{$lang}}-link">
                                    {{ getLanguageName($lang).'('.strtoupper($lang).')' }}
                                </a>
                            </li>
                            @endforeach
                        </ul>
                        <div class="row">
                            <div class="col-md-12">
                                @foreach($languages as $lang)
                                <div class="form-group {{$lang != $defaultLanguage ? 'd-none':''}} form-system-language-form" id="{{$lang}}-form">
                                    <div class="row">
                                        <div class="col-md-4 mt-2">
                                            <label class="title-color" for="name">{{ translate('Name') }}<span class="text-danger">*</span> ({{ strtoupper($lang) }})</label>
                                            <input type="text" name="name[]" class="form-control" value="{{ old('name.'.$loop->index)}}" id="{{$lang}}_name" {{$lang == $defaultLanguage? 'required':''}}>
                                        </div>
                                        <input type="hidden" name="lang[]" value="{{$lang}}" id="lang">
                                        <div class="col-md-4 mt-2">
                                            <label class="title-color" for="trust_name">{{ translate('Select_Purpose') }}<span class="text-danger">*</span></label>
                                            <select name="purpose_id" class="form-control fillupdata " data-point='3' onchange="$(`.fillupdata[data-point='3']`).val(this.value)" {{$lang == $defaultLanguage? 'required':''}}>
                                                <option value="">Select Purpose</option>
                                                @if($all_purpose)
                                                @foreach($all_purpose as $vals)
                                                <option value="{{ $vals['id']}}" {{ ((old('purpose_id') == $vals['id'])?"selected":"")}}>{{ ($vals['name']??"")}}</option>
                                                @endforeach
                                                @endif
                                            </select>
                                        </div>

                                        <!-- <div class="col-md-12 mt-4">
                                            <div class="row"> -->
                                        <div class="col-md-4 mt-2">
                                            <label class="title-color" for="types">{{ translate('Select_Types') }}<span class="text-danger">*</span></label>
                                            <select name="set_type" class="form-control fillupdata" data-point='5' onchange="$(`.fillupdata[data-point='5']`).val(this.value);handleTypeChange(this)" {{$lang == $defaultLanguage? 'required':''}}>
                                                <option value="">Select Types</option>
                                                <option value="1" {{ ((old('set_type') == 1)?"selected":"")}}>Add</option>
                                                <option value="0" {{ ((old('set_type') == 0)?"selected":"")}}>No Use</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mt-2 set_amount_display {{ ((old('set_type') == 1)?'':'d-none')}}">
                                            <label class="title-color" for="set_amount">{{ translate('amount') }}</label>
                                            <input type="text" name="set_amount" value="{{ old('set_amount')}}" class="form-control fillupdata" data-point='6' onblur="$(`.fillupdata[data-point='6']`).val(this.value)" id="{{$lang}}_admin_commission">
                                        </div>
                                        <div class="col-md-4 mt-2 set_amount_display {{ ((old('set_type') == 1)?'':'d-none')}}">
                                            <label class="title-color" for="set_amount">{{ translate('title') }}</label>
                                            <input type="text" name="set_title" value="{{ old('set_title')}}" class="form-control fillupdata" data-point='10' onblur="$(`.fillupdata[data-point='10']`).val(this.value)" id="{{$lang}}_title_ads">
                                        </div>
                                        <div class="col-md-4 mt-2 set_amount_display {{ ((old('set_type') == 1)?'':'d-none')}}">
                                            <label class="title-color" for="set_amount">{{ translate('Enter_number') }}</label>
                                            <input type="text" name="set_number" value="{{ old('set_number')}}" class="form-control fillupdata" data-point='8' onblur="$(`.fillupdata[data-point='8']`).val(this.value)" id="{{$lang}}_admin_commission">
                                        </div>
                                        <div class="col-md-4 mt-2 set_unit_display {{ ((old('set_type') == 1)?'':'d-none')}}">
                                            <label class="title-color" for="unit">{{ translate('Select_Unit') }}<span class="text-danger">*</span></label>
                                            <select name="set_unit" class="form-control fillupdata " data-point='7' onchange="$(`.fillupdata[data-point='7']`).val(this.value)">
                                                <option value="">Select Unit</option>
                                                @if($unit_list)
                                                @foreach($unit_list as $key=>$va)
                                                <option value="{{$key}}" {{ ((old('set_unit') == $key)?"selected":"")}}>{{$va}}</option>
                                                @endforeach
                                                @endif
                                            </select>
                                        </div>

                                        <!-- </div>
                                        </div> -->
                                        <div class="col-md-12 mt-4">
                                            <label class="title-color w-100 font-weight-bold h3" for="details">{{ translate('Description') }}<span class="text-danger">*</span> ({{ strtoupper($lang) }})</label>
                                            <textarea class='form-control ckeditor' name='description[]'>{{ old('description.'.$loop->index)}}</textarea>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="col-md-12 mt-3 rest-part">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="form-group">
                                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                                        <div>
                                                            <label for="name" class="title-color text-capitalize font-weight-bold mb-0">{{ translate('ads_image') }}</label>
                                                            <span class="badge badge-soft-info">{{ THEME_RATIO[theme_root_path()]['Product Image'] }}</span>
                                                            <span class="input-label-secondary cursor-pointer" data-toggle="tooltip" title="{{ translate('add_ads_image_in') }} JPG, PNG or JPEG {{ translate('format_within') }} 2MB">
                                                                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="custom_upload_input">
                                                            <input type="file" name="image" class="custom-upload-input-file action-upload-color-image image-input" data-imgpreview="pre_frc_certificate" accept=".jpg, .webp, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                            <span class="delete_file_input btn btn-outline-danger btn-sm square-btn d--none">
                                                                <i class="tio-delete"></i>
                                                            </span>
                                                            <div class="img_area_with_preview position-absolute z-index-2">
                                                                <img id="pre_frc_certificate" class="h-auto aspect-1 bg-white d-none" src="dummy" alt="">
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
                                </div>
                            </div>
                        </div>
                        @if (Helpers::Employee_modules_permission('Ads Management', 'Add Ads', 'Add'))
                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            <button type="reset" class="btn btn-secondary">{{ translate('reset') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('submit') }}</button>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>


    </div>
</div>
@endsection

@push('script')
<!-- Include SweetAlert2 for confirmation dialogs -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script src="{{ dynamicAsset(path: 'public/js/ckeditor.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<script>
    $('.image-input').on('change', function() {
        const input = this;
        const imgPreviewId = $(this).data('imgpreview');
        const img = document.getElementById(imgPreviewId);

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (img !== null) {
                    img.src = e.target.result;
                    img.classList.remove('d-none');
                }
                const imgName = input.files[0].name;
                const closestDataTitleElement = input.closest('[data-title]');
                if (closestDataTitleElement) {
                    closestDataTitleElement.setAttribute("data-title", imgName);
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    });

    function handleTypeChange(selectElement) {
        if (selectElement.value == "1") {
            $('.set_amount_display').removeClass('d-none');
            $('.set_unit_display').removeClass('d-none');
        } else {
            $('.set_amount_display').addClass('d-none');
            $('.set_unit_display').addClass('d-none');
        }
    }
</script>
@endpush