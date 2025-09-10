@extends('layouts.back-end.app')

@section('title', translate('Edit_Traveller'))

@push('css_or_js')
<link href="{{ dynamicAsset(path: 'public/assets/back-end/css/tags-input.min.css') }}" rel="stylesheet">
<link href="{{ dynamicAsset(path: 'public/assets/select2/css/select2.min.css') }}" rel="stylesheet">

@endpush

@section('content')
<div class="content container-fluid">
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 d-flex gap-2">
            {{ translate('Edit_Traveller') }}
        </h2>
    </div>

    <form class="product-form text-start" action="{{ route('admin.tour_and_travels.edit',[$getData['id']]) }}" method="POST" enctype="multipart/form-data" id="services_form">
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
                                <input type="text" required name="owner_name" class="form-control @error('owner_name') is-invalid @enderror" value="{{ old('owner_name',($getData['owner_name']??'') ) }}" placeholder="{{ translate('Owner_name') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="company_name">{{ translate('Company_Name') }} </label>
                                <input type="text" required name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name',($getData['company_name']??'')) }}" placeholder="{{ translate('company_name') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="phone_no">{{ translate('phone_number') }} </label>
                                <input type="text" required name="phone_no" class="form-control @error('phone_no') is-invalid @enderror onfillup" value="{{ old('phone_no',($getData['phone_no']??'')) }}" placeholder="{{ translate('phone_number') }}" data-point='1'>
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
                                <input type="text" required name="address[]" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', ($getData['address']??'')) }}" placeholder="{{ translate('Full_address') }}">
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
                                <label class="title-color" for="_services">{{ translate('Services') }} </label>
                                <textarea required name="services[]" id="_services" class="form-control ckeditor @error('services') is-invalid @enderror">{{ old('services', ($getData['services']??'')) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="_expect_details">{{ translate('Area_of_Operation') }} </label>
                                <textarea required name="area_of_operation[]" id="_area_of_operation " class="form-control ckeditor @error('area_of_operation') is-invalid @enderror">{{ old('area_of_operation',($getData['area_of_operation']??'')) }}</textarea>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="lang[]" value="">

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
                                <input type="text" name="person_name" class="form-control @error('person_name') is-invalid @enderror" value="{{ old('person_name', ($getData['person_name']??'')) }}" placeholder="{{ translate('person_name') }}" >
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('person_phone') }} </label>
                                <input type="text" name="person_phone" class="form-control  @error('person_phone') is-invalid @enderror onfillup" value="{{ old('person_phone',($getData['person_phone']??'')) }}" placeholder="{{ translate('person_phone') }}" data-point="4">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('person_email') }} </label>
                                <input type="text" name="person_email" class="form-control  @error('person_email') is-invalid @enderror onfillup" value="{{ old('person_email',($getData['person_email']??'')) }}" placeholder="{{ translate('person_email') }}" data-point="5">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('person_address') }}</label>
                                <input type="text" name="person_address" class="form-control  @error('person_address') is-invalid @enderror" value="{{ old('person_address', ($getData['person_address']??'')) }}" placeholder="{{ translate('person_address') }}">
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
                                <input type="text" name="bank_holder_name" class="form-control  @error('bank_holder_name') is-invalid @enderror onfillup" value="{{ old('bank_holder_name',($getData['bank_holder_name']??'')) }}" placeholder="{{ translate('Holder’s Name') }}" data-point="6">
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
                                <input type="text" name="ifsc_code" class="form-control  @error('ifsc_code') is-invalid @enderror onfillup" value="{{ old('ifsc_code',($getData['ifsc_code']??'')) }}" placeholder="{{ translate('IFSC_code') }}" data-point="9">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="title-color" for="">{{ translate('Account_number') }} </label>
                                <input type="text" id="account_number" name="account_number" class="form-control  @error('account_number') is-invalid @enderror onfillup" value="{{ old('account_number',($getData['account_number']??'')) }}" placeholder="{{ translate('Account_number') }}" data-point="10">
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
</div>

<span id="message-are-you-sure" data-text="{{ translate('are_you_sure') }}"></span>
<span id="message-yes-word" data-text="{{ translate('yes') }}"></span>
<span id="message-no-word" data-text="{{ translate('no') }}"></span>

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
</script>
<script>
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

@endpush