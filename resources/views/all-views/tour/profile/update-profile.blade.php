@extends('layouts.back-end.app-tour')
@section('title', translate('profile'))
@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/plugins/intl-tel-input/css/intlTelInput.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.0.3/css/font-awesome.css">

@endpush

@section('content')

<div class="content container-fluid">
    @if(\App\Models\Seller::where('type','tour')->whereIn('update_seller_status',[1,2])->where('id',auth('tour')->id())->exists())
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 d-flex gap-2">
            {{ translate('Edit_Traveller') }}
        </h2>
    </div>

    <form class="product-form text-start" action="{{ route('tour-vendor.profile.profile-edit',[$getData['id']]) }}" method="POST" enctype="multipart/form-data" id="services_form">
        @csrf
        <div class="card">
            <div class="card-body">
                <div>
                    <div class="card-header pt-0">
                        <div class="d-flex gap-2">
                            <i class="tio-company"></i>
                            <h4 class="mb-0">{{ translate('General_Information') }}</h4>
                            <input type="hidden" name="id" value="{{ $getData['id']}}">
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="owner_name">{{ translate('Owner_name') }} </label>
                                <input type="text" required name="owner_name" class="form-control @error('owner_name') is-invalid @enderror" value="{{ old('owner_name',($getData['owner_name']??'')) }}" placeholder="{{ translate('Owner_name') }}" onkeyup="OnlyString(this)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="company_name">{{ translate('Company_Name') }} </label>
                                <input type="text" required name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name',($getData['company_name']??'')) }}" placeholder="{{ translate('company_name') }}" onkeyup="OnlyString(this)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="phone_no">{{ translate('phone_number') }} </label>
                                <input class="form-control form-control-user phone-input-with-country-picker  @error('phone_no') is-invalid @enderror onfillup" type="tel" id="exampleInputPhone" value="{{ old('phone_no',($getData['phone_no']??'')) }}" placeholder="{{ translate('enter_phone_number') }}" required>
                                <div class="">
                                    <input type="text" class="country-picker-phone-number w-50" value="{{ old('phone_no',($getData['phone_no']??'')) }}" name="phone_no" hidden readonly>
                                </div>
                    
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('Email_id') }} </label>
                                <input type="email" required name="email" class="form-control @error('email') is-invalid @enderror onfillup" value="{{ old('email',($getData['email']??'')) }}" placeholder="{{ translate('email_id') }}" data-point="2">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('Full_Address') }} </label>
                                <input type="text" required name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', ($getData['address']??'')) }}" placeholder="{{ translate('Full_address') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('WebSite_link') }} </label>
                                <input type="text" name="web_site_link" class="form-control @error('web_site_link') is-invalid @enderror onfillup" value="{{ old('web_site_link',($getData['web_site_link']??'')) }}" placeholder="{{ translate('Web_site_link') }}" data-point="3">
                            </div>
                        </div>
                    </div>
                    <div class="card-header pt-0">
                        <div class="d-flex gap-2">
                            <i class="tio-briefcase"></i>
                            <h4 class="mb-0">{{ translate('Business_Details') }}</h4>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="services">{{ translate('Services') }} </label>
                                <textarea required name="services" id="services" class="form-control ckeditor @error('services') is-invalid @enderror">{{ old('services', ($getData['services']??'')) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="expect_details">{{ translate('Area_of_Operation') }} </label>
                                <textarea required name="area_of_operation" id="area_of_operation " class="form-control ckeditor @error('area_of_operation') is-invalid @enderror">{{ old('area_of_operation',($getData['area_of_operation']??'')) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card-header pt-0">
                        <div class="d-flex gap-2">
                            <i class="tio-user"></i>
                            <h4 class="mb-0">{{ translate('Contact_Person_Details') }}</h4>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('person_name') }}</label>
                                <input type="text" name="person_name" class="form-control @error('person_name') is-invalid @enderror" value="{{ old('person_name',($getData['person_name']??'')) }}" placeholder="{{ translate('person_name') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('person_phone') }} </label>
                                <input type="text" name="person_phone" class="form-control  @error('person_phone') is-invalid @enderror onfillup" value="{{ old('person_phone',($getData['person_phone']??'')) }}" readOnly placeholder="{{ translate('person_phone') }}" data-point="4">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('person_email') }} </label>
                                <input type="text" name="person_email" class="form-control  @error('person_email') is-invalid @enderror onfillup" value="{{ old('person_email',($getData['person_email']??'')) }}" readOnly placeholder="{{ translate('person_email') }}" data-point="5">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('person_address') }}</label>
                                <input type="text" name="person_address" class="form-control  @error('person_address') is-invalid @enderror" value="{{ old('person_address',($getData['person_address']??'')) }}" placeholder="{{ translate('person_address') }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-header pt-0">
                        <div class="d-flex gap-2">
                            <i class="tio-saving_outlined">saving_outlined</i>
                            <h4 class="mb-0">{{ translate('Bank_Details') }}</h4>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('Holder’s Name') }} </label>
                                <input type="text" name="bank_holder_name" class="form-control  @error('bank_holder_name') is-invalid @enderror onfillup" value="{{ old('bank_holder_name',($getData['bank_holder_name']??'')) }}" placeholder="{{ translate('Holder’s Name') }}" data-point="6" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('Bank_Name') }} </label>
                                <input type="text" name="bank_name" class="form-control  @error('bank_name') is-invalid @enderror onfillup" value="{{ old('bank_name',($getData['bank_name']??'')) }}" placeholder="{{ translate('Bank_Name') }}" data-point="7">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('Branch_name') }} </label>
                                <input type="text" name="bank_branch" class="form-control  @error('bank_branch') is-invalid @enderror onfillup" value="{{ old('bank_branch',($getData['bank_branch']??'')) }}" placeholder="{{ translate('Branch_name') }}" data-point="8">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('IFSC_code') }} </label>
                                <input type="text" name="ifsc_code" class="form-control  @error('ifsc_code') is-invalid @enderror onfillup" value="{{ old('ifsc_code',($getData['ifsc_code']??'')) }}" placeholder="{{ translate('IFSC_code') }}" data-point="9" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('Account_number') }} </label>
                                <input type="text" id="account_number" name="account_number" class="form-control  @error('account_number') is-invalid @enderror onfillup" value="{{ old('account_number',($getData['account_number']??'')) }}" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );" placeholder="{{ translate('Account_number') }}" data-point="10" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('Confrim_Account_number') }} </label>
                                <input type="text" id="confirm_account_number" class="form-control" placeholder="{{ translate('Account_number') }}" value="{{ old('account_number',($getData['account_number']??'')) }}" onkeyup="this.value = this.value.replace(/[^0-9]/g, '' );" onblur="validateAccountNumber();" required>
                                <small id="account_match_error" style="color: red; display: none;">Account numbers do not match</small>
                            </div>
                        </div>
                    </div>
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
                                        <label for="name" class="title-color text-capitalize font-weight-bold mb-0">{{ translate('GST_certificate') }}</label>
                                        <span class="badge badge-soft-info">{{ THEME_RATIO[theme_root_path()]['Product Image'] }}</span>
                                        <span class="input-label-secondary cursor-pointer" data-toggle="tooltip" title="{{ translate('add_your_GST_certificate') }} JPG, PNG or JPEG {{ translate('format_within') }} 2MB">
                                            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <div class="custom_upload_input">
                                        <input type="file" name="gst_image" class="custom-upload-input-file action-upload-color-image" id="" data-imgpreview="pre_gst_img_viewer" accept=".jpg, .webp, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                        <span class="delete_file_input btn btn-outline-danger btn-sm square-btn d--none">
                                            <i class="tio-delete"></i>
                                        </span>
                                        <div class="img_area_with_preview position-absolute z-index-2">
                                            <img id="pre_gst_img_viewer" class="h-auto aspect-1 bg-white  {{ (($getData['gst_image'])?'':'d-none') }}" src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/doc/'.$getData['gst_image'], type: 'backend-product')  }}" alt="">
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
                <!-- 2 second -->
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="form-group">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <label for="name" class="title-color text-capitalize font-weight-bold mb-0">{{ translate('Pan_card') }}</label>
                                        <span class="badge badge-soft-info">{{ THEME_RATIO[theme_root_path()]['Product Image'] }}</span>
                                        <span class="input-label-secondary cursor-pointer" data-toggle="tooltip" title="{{ translate('add_your_service’s_thumbnail_in') }} JPG, PNG or JPEG {{ translate('format_within') }} 2MB">
                                            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <div class="custom_upload_input">
                                        <input type="file" name="pan_card_image" class="custom-upload-input-file action-upload-color-image" id="" data-imgpreview="pre_pan_card_img_viewer" accept=".jpg, .webp, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                        <span class="delete_file_input btn btn-outline-danger btn-sm square-btn d--none">
                                            <i class="tio-delete"></i>
                                        </span>

                                        <div class="img_area_with_preview position-absolute z-index-2">
                                            <img id="pre_pan_card_img_viewer" class="h-auto aspect-1 bg-white  {{ (($getData['pan_card_image'])?'':'d-none') }}" src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/doc/'.$getData['pan_card_image'], type: 'backend-product')  }}" alt="">
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
                <!--  3 three -->
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="form-group">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <label for="name" class="title-color text-capitalize font-weight-bold mb-0">{{ translate('aadhaar_card') }}</label>
                                        <span class="badge badge-soft-info">{{ THEME_RATIO[theme_root_path()]['Product Image'] }}</span>
                                        <span class="input-label-secondary cursor-pointer" data-toggle="tooltip" title="{{ translate('add_your_aadhaar_card') }} JPG, PNG or JPEG {{ translate('format_within') }} 2MB">
                                            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <div class="custom_upload_input">
                                        <input type="file" name="aadhaar_card_image" class="custom-upload-input-file action-upload-color-image" id="" data-imgpreview="pre_aadhaar_card_img_viewer" accept=".jpg, .webp, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                        <span class="delete_file_input btn btn-outline-danger btn-sm square-btn d--none">
                                            <i class="tio-delete"></i>
                                        </span>

                                        <div class="img_area_with_preview position-absolute z-index-2">
                                            <img id="pre_aadhaar_card_img_viewer" class="h-auto aspect-1 bg-white  {{ (($getData['aadhaar_card_image'])?'':'d-none') }}" src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/doc/'.$getData['aadhaar_card_image'], type: 'backend-product')  }}" alt="">
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
                <!-- 4 forth -->
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="form-group">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <label for="name" class="title-color text-capitalize font-weight-bold mb-0">{{ translate('Address_Proof') }}</label>
                                        <span class="badge badge-soft-info">{{ THEME_RATIO[theme_root_path()]['Product Image'] }}</span>
                                        <span class="input-label-secondary cursor-pointer" data-toggle="tooltip" title="{{ translate('add_your_service’s_thumbnail_in') }} JPG, PNG or JPEG {{ translate('format_within') }} 2MB">
                                            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <div class="custom_upload_input">
                                        <input type="file" name="address_proof_image" class="custom-upload-input-file action-upload-color-image" id="" data-imgpreview="pre_address_proof_img_viewer" accept=".jpg, .webp, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                        <span class="delete_file_input btn btn-outline-danger btn-sm square-btn d--none">
                                            <i class="tio-delete"></i>
                                        </span>
                                        <div class="img_area_with_preview position-absolute z-index-2">
                                            <img id="pre_address_proof_img_viewer" class="h-auto aspect-1 bg-white  {{ (($getData['address_proof_image'])?'':'d-none') }}" src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/doc/'.$getData['address_proof_image'], type: 'backend-product')  }}" alt="">
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
                <!-- 5 -->

                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="form-group">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <label for="name" class="title-color text-capitalize font-weight-bold mb-0">{{ translate('Company_image') }}</label>
                                        <span class="badge badge-soft-info">{{ THEME_RATIO[theme_root_path()]['Product Image'] }}</span>
                                        <span class="input-label-secondary cursor-pointer" data-toggle="tooltip" title="{{ translate('add_your_Company_thumbnail_in') }} JPG, PNG or JPEG {{ translate('format_within') }} 2MB">
                                            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/info-circle.svg') }}" alt="">
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <div class="custom_upload_input">
                                        <input type="file" name="image" class="custom-upload-input-file action-upload-color-image" id="" data-imgpreview="pre_Company_img_viewer" accept=".jpg, .webp, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                        <span class="delete_file_input btn btn-outline-danger btn-sm square-btn d--none">
                                            <i class="tio-delete"></i>
                                        </span>
                                        <div class="img_area_with_preview position-absolute z-index-2">
                                            <img id="pre_Company_img_viewer" class="h-auto aspect-1 bg-white  {{ (($getData['image'])?'':'d-none') }}" src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/doc/'.$getData['image'], type: 'backend-product')  }}" alt="">
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
        <div class="row justify-content-end gap-3 mt-3 mx-1">
            <button type="reset" id="reset" class="btn btn-secondary px-4">{{ translate('reset') }}</button>
            <button type="submit" class="btn btn--primary px-4">{{ translate('submit') }}</button>
        </div>
    </form>
    @else
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 d-flex gap-2">
            {{ translate('Traveller_profile') }}
        </h2>
    </div>
    <div class="card px-0 pb-0 mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="navbar-vertical navbar-expand-lg mb-3 mb-lg-5">
                        <button type="button" class="navbar-toggler btn btn-block btn-white mb-3"
                            aria-label="Toggle navigation" aria-expanded="false" aria-controls="navbarVerticalNavMenu"
                            data-bs-toggle="collapse" data-bs-target="#navbarVerticalNavMenu">
                            <span class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0">{{ translate('nav_menu') }}</span>
                                <span class="navbar-toggle-default">
                                    <i class="tio-menu-hamburger"></i>
                                </span>
                                <span class="navbar-toggle-toggled">
                                    <i class="tio-clear"></i>
                                </span>
                            </span>
                        </button>

                        <div id="navbarVerticalNavMenu" class="collapse navbar-collapse">
                            <ul id="navbarSettings" class="js-sticky-block js-scrollspy navbar-nav navbar-nav-lg nav-tabs card card-navbar-nav">
                                <li class="nav-item">
                                    <a class="nav-link active" href="javascript:void(0);" data-target="general1">
                                        <i class="tio-user-outlined nav-icon"></i>{{ translate('basic_Information') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-target="shops1">
                                        <i class="tio-documents nav-icon"></i>{{ translate('Service') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-target="document">
                                        <i class="tio-documents nav-icon"></i> {{ translate('Document') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-target="banks">
                                        <i class="tio-museum nav-icon"></i> {{ translate('Bank') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="javascript:void(0);" data-target="password_change">
                                        <i class="tio-password nav-icon"></i> {{ translate('Change_Password') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="card mb-3 mb-lg-5" id="general1-div">
                        <div class="row p-4 ">
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{ translate('owner_name') }}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{{ ($getData['owner_name']??'') }}</div>
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{ translate('company_name') }}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{{ ($getData['company_name']??'') }}</div>
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{ translate('phone') }}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{{ ($getData['phone_no']??'') }}</div>
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{ translate('email') }}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{{ ($getData['email']??'') }}</div>
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{ translate('Full_Address') }}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{{ ($getData['address']??'') }}</div>
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{ translate('WebSite_link') }}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{{ ($getData['web_site_link']??'') }}</div>
                            <div class="col-md-12 mt-2">
                                <hr>
                            </div>
                            <div class="col-md-12 mt-2">{{ translate('Contact_Person_Details') }}</div>
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{ translate('person_name') }}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{{ ($getData['person_name']??'') }}</div>
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{ translate('phone') }}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{{ ($getData['person_phone']??'') }}</div>
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{ translate('email') }}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{{ ($getData['person_email']??'') }}</div>
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{ translate('person_address') }}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{{ ($getData['person_address']??'') }}</div>
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{translate('image')}}</lable>
                            </div>
                            <div class="col-md-6 mt-2">
                                <div class="row">
                                    <div class="col-6">
                                        <img id="fassai-preview" src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/doc/'.$getData['image'], type: 'backend-product')  }}" alt="{{translate('fassai_image')}}" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-3 mb-lg-5" id="shops1-div" style="display: none;">
                        <div class="row p-4 ">
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{ translate('Services') }}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{!! ($getData['services']??'') !!}</div>
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{ translate('Area_of_Operation') }}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{!!($getData['area_of_operation']??'')!!}</div>
                        </div>
                    </div>
                    <div class="card mb-3 mb-lg-5" id="document-div" style="display: none;">
                        <div class="row p-4 ">
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{ translate('aadhar_image') }}</lable>
                            </div>
                            <div class="col-md-6 mt-2">
                                <div class="row">
                                    <div class="col-6">
                                        <img class="h-auto aspect-1 bg-white" src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/doc/'.($getData['aadhaar_card_image']??''),type: 'backend-basic') }}" alt="">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{ translate('pancard_image') }}</lable>
                            </div>
                            <div class="col-md-6 mt-2">
                                <div class="row">
                                    <div class="col-6">
                                        <img class="h-auto aspect-1 bg-white" src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/doc/'.($getData['pan_card_image']??''),type: 'backend-basic') }}" alt="">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{ translate('GST_certificate') }}</lable>
                            </div>
                            <div class="col-md-6 mt-2">
                                <div class="row">
                                    <div class="col-6">
                                        <img class="h-auto aspect-1 bg-white" src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/doc/'.($getData['gst_image']??''),type: 'backend-basic') }}" alt="">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{ translate('address_proof') }}</lable>
                            </div>
                            <div class="col-md-6 mt-2">
                                <div class="row">
                                    <div class="col-6">
                                        <img class="h-auto aspect-1 bg-white" src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/doc/'.($getData['address_proof_image']??''),type: 'backend-basic') }}" alt="">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-3 mb-lg-5" id="banks-div" style="display: none;">
                        <div class="row p-4 ">
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{translate('bank_Name')}}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{{$getData['bank_name']??""}}</div>
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{translate('branch_Name')}}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{{$getData['bank_branch']??""}}</div>
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{translate('holder_Name')}}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{{$getData['bank_holder_name']??""}}</div>
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{translate('account_No')}}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{{$getData['account_number']??""}}</div>
                            <div class="col-md-6 mt-2">
                                <lable class="col-form-label font-weight-bold">{{translate('IFSC_code')}}</lable>
                            </div>
                            <div class="col-md-6 mt-2">{{$getData['ifsc_code']??""}}</div>
                        </div>
                    </div>
                    <div class="card mb-3 mb-lg-5" id="password_change-div" style="display: none;">
                        <form action="{{ route('tour-vendor.profile.password-update',[auth('tour')->id()])}}" method="post" id="passwordForm">
                            @csrf
                            <div class="row p-4">

                                <!-- Old Password -->
                                <div class="col-md-6 mt-2">
                                    <label class="col-form-label font-weight-bold">{{ translate('Old_password') }}</label>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <div class="input-group input-group-merge">
                                        <input type="password" class="form-control password-check"
                                            name="old_password" required id="old_password"
                                            placeholder="Enter old password">
                                        <div class="input-group-append">
                                            <a class="input-group-text toggle-password" href="javascript:">
                                                <i class="tio-visible-outlined"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- New Password -->
                                <div class="col-md-6 mt-2">
                                    <label class="col-form-label font-weight-bold">{{ translate('New_password') }}</label>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <div class="input-group input-group-merge">
                                        <input type="password" class="form-control password-check"
                                            name="new_password" required id="new_password"
                                            placeholder="Password (min 8 characters)">
                                        <div class="input-group-append">
                                            <a class="input-group-text toggle-password" href="javascript:">
                                                <i class="tio-visible-outlined"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <small id="newPassError" class="text-danger"></small> <!-- Error Message -->
                                </div>

                                <!-- Confirm Password -->
                                <div class="col-md-6 mt-2">
                                    <label class="col-form-label font-weight-bold">{{ translate('Confirm_password') }}</label>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <div class="input-group input-group-merge">
                                        <input type="password" class="form-control password-check"
                                            name="confirm_password" required id="confirm_password"
                                            placeholder="Confirm new password">
                                        <div class="input-group-append">
                                            <a class="input-group-text toggle-password" href="javascript:">
                                                <i class="tio-visible-outlined"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <small id="confirmPassError" class="text-danger"></small> <!-- Error Message -->
                                </div>

                                <!-- Submit Button -->
                                <div class="col-md-12 mt-2">
                                    <button type="submit" class="btn btn-success">Update</button>
                                </div>

                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<span id="message-are-you-sure" data-text="{{ translate('are_you_sure') }}"></span>
<span id="message-yes-word" data-text="{{ translate('yes') }}"></span>
<span id="message-no-word" data-text="{{ translate('no') }}"></span>


@endsection

@push('script')
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/admin/product-add-update.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/js/ckeditor.js') }}"></script>

<script src="{{ dynamicAsset(path: 'public/assets/back-end/plugins/intl-tel-input/js/intlTelInput.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/country-picker-init.js') }}"></script>
<script src="{{ dynamicAsset(path: 'public/assets/back-end/js/products-management.js') }}"></script>


<script>
    $('.toggle-password').on('click', function() {
        let input = $(this).closest('.input-group').find('input');
        input.attr('type', input.attr('type') === 'password' ? 'text' : 'password');
    });

    // Password match validation with error message
    $('#passwordForm').on('submit', function(e) {
        let newPassword = $('#new_password').val();
        let confirmPassword = $('#confirm_password').val();
        let isValid = true;

        // Clear previous errors
        $('#newPassError').text('');
        $('#confirmPassError').text('');

        // Check password length
        if (newPassword.length < 8) {
            $('#newPassError').text('Password must be at least 8 characters long.');
            isValid = false;
        }

        // Check if passwords match
        if (newPassword !== confirmPassword) {
            $('#confirmPassError').text('New password and Confirm password do not match.');
            isValid = false;
        }

        // Stop form submission if invalid
        if (!isValid) {
            e.preventDefault();
        }
    });

    function validateAccountNumber() {
        var accountNumber = document.getElementById("account_number").value;
        var confirmAccountNumber = document.getElementById("confirm_account_number").value;
        var errorMsg = document.getElementById("account_match_error");

        if (accountNumber !== confirmAccountNumber && confirmAccountNumber !== "") {
            errorMsg.style.display = "block";
            document.getElementById("confirm_account_number").value = "";
        } else {
            errorMsg.style.display = "none";
        }
    }
</script>

<script>
    function handleTabSwitch(target) {
        const sections = ['general1', 'shops1', 'document', 'banks', 'password_change'];

        // Hide all sections
        sections.forEach(section => {
            document.getElementById(section + '-div').style.display = 'none';
            document.querySelector('[data-target="' + section + '"]').classList.remove('active');
        });

        // Show the selected section
        document.getElementById(target + '-div').style.display = 'block';
        document.querySelector('[data-target="' + target + '"]').classList.add('active');
    }

    // Add event listeners to the tabs
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function() {
            const target = this.getAttribute('data-target');
            handleTabSwitch(target);
        });
    });
</script>
<script>
    function OnlyString(input) {
        input.value = input.value.replace(/[^a-zA-Z\s]/g, '');
    }
</script>
@endpush