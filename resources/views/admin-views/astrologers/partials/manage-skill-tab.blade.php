<div class="row">
    <div class="form-group col-md-6">
        <label for="primary_skills" class="form-label">Primary Skills</label>
        <select name="primary_skills" id="primary-skill" class="form-control">
            @foreach ($skills as $skill)
                <option value="{{ $skill['id'] }}">{{ $skill['name'] }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-6">
        <div id="other-skill-div" style="display: none;">
            <label for="other_skills" class="form-label">Other Skills (if you have any)</label>
            <select name="other_skills[]" id="other-skill" class="form-control multi-select"
                multiple>
                <option value="" disabled>Select Other Skills</option>
            </select>
        </div>
    </div>
    <div id="pandit-div" class="col-12" style="display: none">
        <div class="row">
        <div class="form-group col-6">
            <label for="pandit_category" class="form-label">Pooja Category</label>
            {{-- <select name="is_pandit_pooja_category[]" id="pandit-category" multiple class="form-control multi-select">
                @foreach ($panditCategories as $category)
                    <option value="{{$category['id']}}">{{$category['name']=='vip pooja'?'Customer Basis':$category['name']}}</option>
                @endforeach
            </select> --}}
            <select name="is_pandit_pooja_category[]" id="pandit-category" multiple class="form-control multi-select">
            </select>
        </div>
        <div class="form-group col-md-6">
            <label for="panda" class="form-label">Panda</label>
            <input type="text" name="is_pandit_panda" id="panda" class="form-control"
                placeholder="Your Panda">
        </div>
        <div class="form-group col-md-6">
            <label for="gotra" class="form-label">Gotra</label>
            <input type="text" name="is_pandit_gotra" id="gotra" class="form-control"
                placeholder="Your Gotra">
        </div>
        <div class="form-group col-md-6">
            <label for="primary_mandir" class="form-label">Primary Mandir/Ghat (where you perform pooja)</label>
            <input type="text" name="is_pandit_primary_mandir" id="primary-mandir" class="form-control"
                placeholder="Your Mandir/Ghat">
        </div>
        <div class="form-group col-md-6">
            <label for="primary_mandir_location" class="form-label">Primary Mandir/Ghat Address</label>
            <input type="text" name="is_pandit_primary_mandir_location" id="primary-mandir-location" class="form-control"
                placeholder="Your Mandir/Ghat Address">
        </div>
        <div class="form-group col-md-6">
            <label for="pooja_per_day" class="form-label">No. of Pooja Per Day</label>
            <input type="number" name="pooja_per_day" class="form-control" placeholder="Pooja per day">
        </div>
        <div class="form-group col-md-6">
            <label for="min_charge" class="form-label">Minimum Charge Per Pooja</label>
            <input type="number" name="min_charge" class="form-control" placeholder="Minimum Charge">
        </div>
        <div class="form-group col-md-6">
            <label for="max_charge" class="form-label">Maximum Charge Per Pooja</label>
            <input type="number" name="max_charge" class="form-control" placeholder="Maximum Charge">
        </div>
    </div>
</div>
    <div class="form-group col-md-6">
        <label for="category" class="form-label">Category</label>
        <select name="category[]"  class="form-control multi-select"
            multiple id="validationCustom20" required>
            @foreach ($categories as $category)
                <option value="{{ $category['id'] }}">{{ $category['name'] }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback">
            Please select category.
        </div>
    </div>
    <div class="form-group col-md-6">
        <label for="language" class="form-label">Language</label>
        <select name="language[]"  class="form-control multi-select" multiple id="validationCustom21" required>
            <option value="hi">Hindi</option>
            <option value="en">English</option>
        </select>
        <div class="invalid-feedback">
            Please select language.
        </div>
    </div>
    {{-- <div class="form-group col-md-6">
        <label for="charges" class="form-label">Add Your Charge (As per
            Minute)</label>
        <input type="number" name="charge" class="form-control"
            placeholder="Charge" >
    </div>
    <div class="form-group col-md-6">
        <label for="video_charges" class="form-label">Add Your video charge(As
            per Minute)</label>
        <input type="number" name="video_charge" class="form-control"
            placeholder="Video Call Charge" >
    </div>
    <div class="form-group col-md-6">
        <label for="report_charges" class="form-label">Add Your report charge
            (As per Minute)</label>
        <input type="number" name="report_charge" class="form-control"
            placeholder="Report Charge" >
    </div> --}}
    <div class="form-group col-md-6">
        <label for="experience" class="form-label">Experience In Years</label>
        <input type="number" name="experience" class="form-control"
            placeholder="Experience In Years" id="validationCustom22" required>
        <div class="invalid-feedback">
            Please enter your experience.
        </div>
    </div>
    <div class="form-group col-md-6">
        <label for="daily_hours_contribution" class="form-label">How many
            hours you can contribute daily?</label>
        <input type="number" name="daily_hours_contribution"
            class="form-control" placeholder="Daily Contribution">
    </div>
    <div class="form-group col-md-6">
        <label for="office_address" class="form-label">Your office address</label>
        <textarea name="office_address"  class="form-control" rows="2"></textarea>
    </div>
</div>