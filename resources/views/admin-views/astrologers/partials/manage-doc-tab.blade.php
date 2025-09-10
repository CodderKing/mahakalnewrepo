<div class="row">
    <div class="col-md-8">
        <div class="form-group">
            <label for="adharcard" class="form-label">Aadharcard</label>
            <input type="text" name="adharcard" class="form-control" placeholder="Aadharcard number" maxlength="12"  oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 12);" id="validationCustom10" required>
            <div class="invalid-feedback">
                Please enter aadhar number.
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="text-center mt-3">
                    <img class="upload-img-view" id="aadhar-front-viewer"
                        src="{{ dynamicAsset(path: 'public\assets\back-end\img\400x400\img2.jpg') }}" alt="">
                </div>
                <div class="form-group mt-3 text-center">
                    <label for="adhar_front_image" class="title-color">
                        {{ translate('aadhar_Front_Image') }}<span class="text-danger">*</span>
                    </label>
                    <div class="custom-file text-left">
                        <input type="file" name="adhar_front_image"
                            class="custom-file-input image-preview-before-upload" data-preview="#aadhar-front-viewer" 
                            accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" id="validationCustom11" required>
                        <label class="custom-file-label">
                            {{ translate('choose_file') }}
                        </label>
                        <div class="invalid-feedback">
                            Please select aadhar image.
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="text-center mt-3">
                    <img class="upload-img-view" id="aadhar-back-viewer"
                        src="{{ dynamicAsset(path: 'public\assets\back-end\img\400x400\img2.jpg') }}" alt="">
                </div>
                <div class="form-group mt-3 text-center">
                    <label for="image" class="title-color">
                        {{ translate('aadhar_Back_Image') }}
                    </label>
                    <div class="custom-file text-left">
                        <input type="file" name="adhar_back_image"
                            class="custom-file-input image-preview-before-upload" data-preview="#aadhar-back-viewer"
                            accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                        <label class="custom-file-label">
                            {{ translate('choose_file') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="pancard" class="form-label">Pancard</label>
            <input type="text" name="pancard" class="form-control" placeholder="Pancard number" id="pancard" id="validationCustom12" required>
            <p class="text-danger" id="pancard-validate" style="display: none;">Pancard is invalid</p>
            <div class="invalid-feedback">
                Please enter pancard number.
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="text-center mt-3">
                    <img class="upload-img-view" id="pancard-viewer"
                        src="{{ dynamicAsset(path: 'public\assets\back-end\img\400x400\img2.jpg') }}" alt="">
                </div>
                <div class="form-group mt-3 text-center">
                    <label for="image" class="title-color">
                        {{ translate('pancard_Image') }}<span class="text-danger">*</span>
                    </label>
                    <div class="custom-file text-left">
                        <input type="file" name="pancard_image"
                            class="custom-file-input image-preview-before-upload" data-preview="#pancard-viewer" 
                            accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" id="validationCustom13" required>
                        <label class="custom-file-label" for="astrologer-image">
                            {{ translate('choose_file') }}
                        </label>
                        <div class="invalid-feedback">
                            Please select pancard image.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-8">
        <div class="row">
            <div class="form-group col-md-6">
                <label for="bank_name" class="form-label">Bank Name</label>
                <input type="text" name="bank_name" class="form-control" placeholder="Enter bank name" id="validationCustom14" required>
                <div class="invalid-feedback">
                    Please enter bank name.
                </div>
            </div>
            <div class="form-group col-md-6">
                <label for="holder_name" class="form-label">Account Holder Name</label>
                <input type="text" name="holder_name" class="form-control" placeholder="Enter account holder name" id="validationCustom15" required>
                <div class="invalid-feedback">
                    Please enter account holder name.
                </div>
            </div>
            <div class="form-group col-md-6">
                <label for="branch_name" class="form-label">Branch Name</label>
                <input type="text" name="branch_name" class="form-control" placeholder="Enter branch name" id="validationCustom16" required>
                <div class="invalid-feedback">
                    Please enter branch name.
                </div>
            </div>
            <div class="form-group col-md-6">
                <label for="bank_ifsc" class="form-label">Bank IFSC</label>
                <input type="text" name="bank_ifsc" class="form-control" placeholder="Enter IFSC code" id="validationCustom17" required>
                <div class="invalid-feedback">
                    Please enter bank ifsc.
                </div>
            </div>
            <div class="form-group col-md-6">
                <label for="account_no" class="form-label">Bank Account No.</label>
                <input type="number" id="account-no" name="account_no" class="form-control" placeholder="Enter account no" id="validationCustom18" required>
                <div class="invalid-feedback">
                    Please enter bank account number.
                </div>
                <p class="text-danger" id="account-validate" style="display: none;">Account No. does not match</p>
            </div>
            <div class="form-group col-md-6">
                <label class="form-label">Confirm Account No.</label>
                <input type="number" id="confirm-account-no" class="form-control" placeholder="confirm account no" id="validationCustom19" required>
                <div class="invalid-feedback">
                    Please confirm bank account number .
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="text-center mt-2">
            <img class="upload-img-view" id="bank-passbook-viewer"
                src="{{ dynamicAsset(path: 'public\assets\back-end\img\400x400\img2.jpg') }}" alt="">
        </div>
        <div class="form-group mt-3 text-center">
            <label for="image" class="title-color">
                {{ translate('bank_Passbook_Image') }}
            </label>
            <div class="custom-file text-left">
                <input type="file" name="bank_passbook_image"
                    class="custom-file-input image-preview-before-upload" data-preview="#bank-passbook-viewer"
                    accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                <label class="custom-file-label" for="astrologer-image">
                    {{ translate('choose_file') }}
                </label>
            </div>
        </div>
    </div>
</div>

{{-- <div class="row">
    <div class="col-md-8">
        <div class="form-group">
            <label for="name" class="form-label">Name</label>
            <input type="text" name="name" class="form-control" placeholder="Name" >
        </div>
        <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="Email" >
            <p class="text-danger" id="email-validate" style="display: none;">Email already registered</p>
        </div>
        <div class="form-group">
            <label for="mobile_no" class="form-label">Mobile Number</label>
            <input type="number" id="mobile-no" name="mobile_no" class="form-control" placeholder="Mobile Number"  oninput="if(this.value.length > 10) this.value = this.value.slice(0, 10);">
            <p class="text-danger" id="mobile-no-validate" style="display: none;">Mobile no already register</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="text-center mt-3">
            <img class="upload-img-view" id="viewer"
                src="{{ dynamicAsset(path: 'public\assets\back-end\img\400x400\img2.jpg') }}" alt="">
        </div>
        <div class="form-group mt-2">
            <label for="image" class="title-color">
                {{ translate('astrologer_Image') }}<span class="text-danger">*</span>
            </label>
            <span class="ml-1 text-info">
                {{ THEME_RATIO[theme_root_path()]['Brand Image'] }}
            </span>
            <div class="custom-file text-left">
                <input type="file" name="image" id="astrologer-image"
                    class="custom-file-input image-preview-before-upload" data-preview="#viewer" 
                    accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                <label class="custom-file-label" for="astrologer-image">
                    {{ translate('choose_file') }}
                </label>
            </div>
        </div>
    </div>
</div> --}}

{{-- <div class="row">
    <div class="form-group col-md-6">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Password" minlength="6" >
    </div>
    <div class="form-group col-md-6">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-control" placeholder="Enter your password again"
            minlength="6" >
    </div>
    <div class="form-group col-md-6">
        <label for="gender" class="form-label">Gender</label>
        <select name="gender" id="" class="form-control">
            <option value="male">Male</option>
            <option value="female">Female</option>
        </select>
    </div>
    <div class="form-group col-md-6">
        <label for="dob" class="form-label">Birth Date</label>
        <input type="date" name="dob" class="form-control" >
    </div>
    <div class="form-group col-md-6">
        <label for="pancard" class="form-label">Pancard</label>
        <input type="text" name="pancard" class="form-control" placeholder="Pancard number" id="pancard" >
        <p class="text-danger" id="pancard-validate" style="display: none;">Pancard is invalid</p>
    </div>
    <div class="form-group col-md-6">
        <label for="adharcard" class="form-label">Adharcard</label>
        <input type="text" name="adharcard" class="form-control" placeholder="Adharcard number" maxlength="12"  oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 12);">
    </div>
    <div class="form-group col-md-6">
        <label for="type" class="form-label">Type</label>
        <select name="type" id="type" class="form-control">
            <option value="in house">In house</option>
            <option value="freelancer">Freelancer</option>
        </select>
    </div>
    <div class="form-group col-md-6">
        <label for="city" class="form-label">Which city do you currently
            live in?</label>
        <input type="text" name="city" class="form-control" placeholder="City" >
    </div>
    <div class="form-group col-md-6">
        <label for="address" class="form-label">Your current address</label>
        <textarea name="address" id="" class="form-control" rows="2" ></textarea>
    </div>
</div> --}}
