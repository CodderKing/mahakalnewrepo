@extends('layouts.back-end.app-tour')

@section('title', translate('driver_Edit'))

@section('content')
<div class="content container-fluid">
    <div class="mb-3">
        <h2 class="h1 mb-0 d-flex gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/') }}" alt="">
            {{ translate('driver_Edit') }}
        </h2>
    </div>
    <div class="row">
        <!-- Form for adding new tour_cab -->
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('tour-vendor.tour_cab_management.driver-edit') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label class="title-color" for="name">{{ translate('cab_driver_name') }}<span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{old('name',$getData['name'])}}" class="form-control" placeholder="{{ translate('enter_cab_driver_name') }}" required>
                                <input type="hidden" name="id" value="{{ $getData['id']}}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="title-color" for="reg_number">{{ translate('phone') }}<span class="text-danger">*</span></label>
                                <input type="text" name="phone" value="{{old('phone',$getData['phone'])}}" maxlength="12" class="form-control" placeholder="{{ translate('enter_phone_number') }}" required oninput="validatePhone(this)">
                                <span id="phone_error" style="color: red; font-size: 14px;"></span>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="title-color" for="email">{{ translate('enter_email_Id') }}</label>
                                <input type="text" name="email" value="{{old('email',$getData['email']) }}" class="form-control" placeholder="{{ translate('enter_email_Id') }}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="title-color" for="gender">{{ translate('gender') }}<span class="text-danger">*</span></label>
                                <select name="gender" class="form-control" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ ((old('gender',$getData['gender']) == 'male' )?"selected":"" )}}>Male</option>
                                    <option value="female" {{ ((old('gender',$getData['gender']) == 'female' )?"selected":"" )}}>FeMale</option>
                                    <option value="other" {{ ((old('gender',$getData['gender']) == 'other' )?"selected":"" )}}>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="title-color" for="reg_number">{{ translate('date_of_birth') }}<span class="text-danger">*</span></label>
                                <input type="date" name="dob" value="{{old('dob',$getData['dob'])}}" class="form-control" placeholder="{{ translate('enter_date_of_birth') }}" required onchange="validateDob(this)">
                                <span id="date_of_brith_error" style="color: red; font-size: 14px;"></span>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="title-color" for="year_ex">{{ translate('years_of_driving_experience') }}<span class="text-danger">*</span></label>
                                <input type="number" name="year_ex" value="{{old('year_ex',$getData['year_ex']) }}" class="form-control" placeholder="{{ translate('enter_years_of_driving_experience') }}" required>
                            </div>

                            <div class="col-md-4 form-group">
                                <label class="title-color" for="license_number">{{ translate('driving_license_number') }}<span class="text-danger">*</span></label>
                                <input type="text" name="license_number" value="{{old('license_number',$getData['license_number'])}}" class="form-control" placeholder="{{ translate('enter_driving_license_number') }}" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="title-color" for="pan_number">{{ translate('pan_number') }}<span class="text-danger">*</span></label>
                                <input type="text" name="pan_number" value="{{old('pan_number',$getData['pan_number']) }}" class="form-control" placeholder="{{ translate('enter_pan_number') }}" required oninput="validatePAN(this)">
                                <span id="pan_error" style="color: red; font-size: 14px;"></span>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="title-color" for="aadhar_number">{{ translate('aadhar_number') }}<span class="text-danger">*</span></label>
                                <input type="number" name="aadhar_number" value="{{old('aadhar_number',$getData['aadhar_number'])}}" class="form-control" placeholder="{{ translate('enter_aadhar_number') }}" required oninput="validateAadhar(this)">
                                <span id="aadhar_error" style="color: red; font-size: 14px;"></span>
                            </div>
                            <!--  -->
                            <div class="col-md-3 mb-4">
                                <div class="text-center">
                                    <img class="upload-img-view" id="driver_user_image"  src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_driver/'.$getData['image'], type: 'backend-product')  }}" alt="">
                                </div>
                                <div class="form-group">
                                    <label for="detail_image" class="title-color"> {{ translate('driver_image') }}<span class="text-danger">*</span></label>
                                    <span class="ml-1 text-info">  {{ THEME_RATIO[theme_root_path()]['Brand Image'] }} </span>
                                    <div class="custom-file text-left">
                                        <input type="file" name="image" id="image" class="custom-file-input image-preview-before-upload" data-preview="#driver_user_image" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                        <label class="custom-file-label" for="detail-image">  {{ translate('choose_file') }}  </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-4">
                                <div class="text-center">
                                    <img class="upload-img-view" id="driving_license_number1"  src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_driver/'.$getData['license_image'], type: 'backend-product')  }}" alt="">
                                </div>
                                <div class="form-group">
                                    <label for="detail_image" class="title-color"> {{ translate('license') }}<span class="text-danger">*</span></label>
                                    <span class="ml-1 text-info">  {{ THEME_RATIO[theme_root_path()]['Brand Image'] }} </span>
                                    <div class="custom-file text-left">
                                        <input type="file" name="license_image" id="image" class="custom-file-input image-preview-before-upload" data-preview="#driving_license_number1" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                        <label class="custom-file-label" for="detail-image">  {{ translate('choose_file') }}  </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-4">
                                <div class="text-center">
                                    <img class="upload-img-view" id="pan_number1"  src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_driver/'.$getData['pan_image'], type: 'backend-product')  }}" alt="">
                                </div>
                                <div class="form-group">
                                    <label for="detail_image" class="title-color"> {{ translate('pan_card') }}<span class="text-danger">*</span></label>
                                    <span class="ml-1 text-info">  {{ THEME_RATIO[theme_root_path()]['Brand Image'] }} </span>
                                    <div class="custom-file text-left">
                                        <input type="file" name="pan_image" id="image" class="custom-file-input image-preview-before-upload" data-preview="#pan_number1" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                        <label class="custom-file-label" for="detail-image">  {{ translate('choose_file') }}  </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-4">
                                <div class="text-center">
                                    <img class="upload-img-view" id="aadhar_number1"  src="{{ getValidImage(path: 'storage/app/public/tour_and_travels/tour_traveller_driver/'.$getData['aadhar_image'], type: 'backend-product')  }}" alt="">
                                </div>
                                <div class="form-group">
                                    <label for="detail_image" class="title-color"> {{ translate('aadhar_card') }}<span class="text-danger">*</span></label>
                                    <span class="ml-1 text-info">  {{ THEME_RATIO[theme_root_path()]['Brand Image'] }} </span>
                                    <div class="custom-file text-left">
                                        <input type="file" name="aadhar_image" id="image" class="custom-file-input image-preview-before-upload" data-preview="#aadhar_number1" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                        <label class="custom-file-label" for="detail-image">  {{ translate('choose_file') }}  </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Buttons for form actions -->
                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            <button type="reset" class="btn btn-secondary">{{ translate('reset') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('submit') }}</button>
                        </div>
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
    function validatePAN(input) {
        const panPattern = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
        const panValue = input.value.toUpperCase();

        input.value = panValue; // Ensure uppercase
        const errorSpan = document.getElementById('pan_error');

        if (!panPattern.test(panValue)) {
            errorSpan.textContent = "Please enter a valid PAN card number (e.g., ABCDE1234F).";
        } else {
            errorSpan.textContent = "";
        }
    }

    function validateAadhar(input) {
        const aadharPattern = /^\d{12}$/;
        const errorSpan = document.getElementById('aadhar_error');

        if (!aadharPattern.test(input.value)) {
            errorSpan.textContent = "Aadhar number must be exactly 12 digits.";
        } else {
            errorSpan.textContent = "";
        }
    }
    function validatePhone(input) {
    const phoneError = document.getElementById('phone_error');
    // Allow only digits
    input.value = input.value.replace(/\D/g, '');
    
    if (input.value.length > 10) {
        input.value = input.value.slice(0, 10);
    }

    if (input.value.length < 10) {
        phoneError.textContent = 'Phone number must be exactly 10 digits.';
    } else {
        phoneError.textContent = '';
    }
}

function validateDob(input) {
    const dobError = document.getElementById('date_of_brith_error');
    const dob = new Date(input.value);
    const today = new Date();
    if (isNaN(dob.getTime())) {
        dobError.textContent = 'Invalid date. Please enter a valid date.';
        return;
    }
    if (dob > today) {
        dobError.textContent = 'Date of birth cannot be in the future.';
        return;
    }
    const age = today.getFullYear() - dob.getFullYear();
    if (age < 18) {
        dobError.textContent = 'You must be at least 18 years old.';
        return;
    }
    dobError.textContent = '';
}
</script>
@endpush