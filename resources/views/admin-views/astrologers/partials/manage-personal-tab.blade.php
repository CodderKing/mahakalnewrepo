<div class="row">
    <div class="col-md-8">
        <div class="form-group">
            <label for="name" class="form-label">Name</label>
            <input type="text" id="name" name="name" class="form-control" placeholder="Name" id="validationCustom01" required>
            <div class="invalid-feedback">
                Please enter name.
            </div>
        </div>
        <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="Email" id="validationCustom02" required>
            <p class="text-danger" id="email-validate" style="display: none;">Email already registered</p>
            <div class="invalid-feedback">
                Please enter email.
            </div>
        </div>
        <div class="form-group">
            <label for="mobile_no" class="form-label">Mobile Number</label>
            <input type="number" id="mobile-no" name="mobile_no" class="form-control" placeholder="Mobile Number" id="validationCustom03" required oninput="if(this.value.length > 10) this.value = this.value.slice(0, 10);">
            <p class="text-danger" id="mobile-no-validate" style="display: none;">Mobile no already register</p>
            <div class="invalid-feedback">
                Please enter mobile no.
            </div>
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
                <input type="file" name="image"
                    class="custom-file-input image-preview-before-upload" data-preview="#viewer" 
                    accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" id="validationCustom04" required>
                <label class="custom-file-label">
                    {{ translate('choose_file') }}
                </label>
                <div class="invalid-feedback">
                    Please select your profile picture.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="form-group col-md-6">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Password" minlength="6" id="validationCustom05" required>
        <div class="invalid-feedback">
            Please enter password.
        </div>
    </div>
    <div class="form-group col-md-6">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-control" placeholder="Enter your password again"
            minlength="6" id="validationCustom06" required>
        <div class="invalid-feedback">
            Please enter confirm password.
        </div>
    </div>
    <div class="form-group col-md-6">
        <label for="gender" class="form-label">Gender</label>
        <select name="gender" class="form-control">
            <option value="male">Male</option>
            <option value="female">Female</option>
        </select>
    </div>
    <div class="form-group col-md-6">
        <label for="dob" class="form-label">Birth Date</label>
        <input type="date" name="dob" class="form-control" id="validationCustom07" required>
        <div class="invalid-feedback">
            Please enter your DOB.
        </div>
    </div>
    <div class="form-group col-md-6">
        <label for="type" class="form-label">Type</label>
        <select name="type" id="type" class="form-control">
            <option value="in house">In house</option>
            <option value="freelancer">Freelancer</option>
        </select>
    </div>
    <div class="form-group col-md-6" id="salary-div">
        <label for="salary" class="form-label">Salary</label>
        <input type="number" name="salary" id="salary-input" class="form-control" placeholder="Salary">
    </div>
    {{-- <div class="form-group col-md-6">
        <label for="city" class="form-label">Which city do you currently
            live in?</label>
        <input type="text" name="city" class="form-control" placeholder="City" id="validationCustom08" required>
        <div class="invalid-feedback">
            Please enter your current city.
        </div>
    </div> --}}
    <div class="form-group col-12">
        <label for="address" class="form-label">Your current address</label>
        {{-- <textarea name="address" class="form-control" rows="2" id="validationCustom09" required></textarea> --}}
        <input type="text" name="address" class="form-control getAddress_google" placeholder="Address" id="validationCustom09" required></input>
        <div class="invalid-feedback">
            Please enter current address.
        </div>
    </div>
    <input type="hidden" name="state" id="state" class="form-control" placeholder="state">
    <input type="hidden" name="city" id="city" class="form-control" placeholder="city">
    <input type="hidden" name="pincode" id="pincode" class="form-control" placeholder="pincode">
    <input type="hidden" name="latitude" id="latitude" class="form-control" placeholder="latitude">
    <input type="hidden" name="longitude" id="longitude" class="form-control" placeholder="longitude">
</div>
