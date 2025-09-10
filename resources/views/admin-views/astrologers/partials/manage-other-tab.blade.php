<div class="row">
    <div class="form-group col-md-6">
        <label for="highest_qualification" class="form-label">Select your highest qualification</label>
        <select name="highest_qualification" class="form-control" onchange="qualification(this)">
            <option value="">Select qualification</option>
            <option value="10th">10th</option>
            <option value="12th">12th</option>
            <option value="diploma">Diploma</option>
            <option value="graduate">Graduate</option>
            <option value="post graduate">Post Graduate</option>
            <option value="phd">PHD</option>
            <option value="others">Others</option>
        </select>
    </div>
    <div class="form-group col-md-6" id="other-qualification" style="display: none;">
        <label for="other_qualification" class="form-label">Other Qualification</label>
        <input type="text" name="other_qualification" id="other-qualification-text" class="form-control" placeholder="other qualification">
    </div>
    <div class="form-group col-md-6">
        <label for="college" class="form-label">College/School/University</label>
        <input type="text" name="college" class="form-control" placeholder="Enter your College/School/University">
    </div>
    <div class="form-group col-md-6">
        <label for="onboard_you" class="form-label">Why do you think we should
            onboard you?</label>
        <input type="text" name="onboard_you" class="form-control" placeholder="Why we should on board you" >
    </div>
    <div class="form-group col-md-6">
        <label for="interview_time" class="form-label">What is suitable time
            for interview?</label>
        <input type="text" name="interview_time" class="form-control" placeholder="Enter suitable time for interview">
    </div>
    <div class="form-group col-md-6">
        <label for="business_source" class="form-label">Main Source of
            business</label>
        <select name="business_source"  class="form-control">
            <option value="">Select your business source</option>
            <option value="own business">Own Business</option>
            <option value="private job">Private Job</option>
            <option value="goverment job">Goverment Job</option>
            <option value="studying in college">Studying in College</option>
            <option value="none of the above">None of the above</option>
        </select>
    </div>
    <div class="form-group col-md-6">
        <label for="learn_primary_skill" class="form-label">From where did you
            learn your primary skill?</label>
        <input type="text" name="learn_primary_skill" class="form-control"
            placeholder="From where did you learn">
    </div>
    <div class="form-group col-md-6">
        <label for="instagram" class="form-label">Instagram profile
            link</label>
        <input type="url" name="instagram" class="form-control"
            placeholder="Please let us know your Instagram profile">
    </div>
    <div class="form-group col-md-6">
        <label for="facebook" class="form-label">Facebook profile link</label>
        <input type="url" name="facebook" class="form-control"
            placeholder="Please let us know your Facebook profile">
    </div>
    <div class="form-group col-md-6">
        <label for="linkedin" class="form-label">Linkedin profile link</label>
        <input type="url" name="linkedin" class="form-control"
            placeholder="Please let us know your Linkedin profile">
    </div>
    <div class="form-group col-md-6">
        <label for="youtube" class="form-label">Youtube profile link</label>
        <input type="url" name="youtube" class="form-control"
            placeholder="Please let us know your Youtube profile">
    </div>
    <div class="form-group col-md-6">
        <label for="website" class="form-label">Website profile link</label>
        <input type="url" name="website" class="form-control"
            placeholder="Please let us know your Website profile">
    </div>
    <div class="form-group col-md-6">
        <label for="min_earning" class="form-label">Minimum Earning Expection
            from Mahakal</label>
        <input type="text" name="min_earning" class="form-control" placeholder="Minimum Earning">
    </div>
    <div class="form-group col-md-6">
        <label for="max_earning" class="form-label">Maximum Earning Expection
            from Mahakal</label>
        <input type="text" name="max_earning" class="form-control" placeholder="Maximum Earning">
    </div>
    <div class="form-group col-md-6">
        <label for="foreign_country" class="form-label">Number of the foreign
            countries you lived/travelled to?</label>
        <select name="foreign_country"  class="form-control">
            <option value="0">0</option>
            <option value="1-2">1-2</option>
            <option value="3-5">3-5</option>
            <option value="6+">6+</option>
        </select>
    </div>
    <div class="form-group col-md-6">
        <label for="working" class="form-label">Are you currently working a
            fulltime job?</label>
        <select name="working"  class="form-control">
            <option value="">Select your working</option>
            <option value="no i am working as part timer or freelancer">No, I
                am working as part-timer or freelancer</option>
            <option value="yes i am working somewhere as a full timer">Yes, I
                am working somewhere as a full-timer</option>
            <option value="no i am not working anywhere else">No, I am not
                working anywhere else</option>
            <option value="i own a business">I own a business</option>
        </select>
    </div>
    <div class="form-group col-md-6">
        <label for="bio" class="form-label">Your Bio</label>
        <textarea name="bio"  rows="2" maxlength="200" class="form-control" placeholder="Describe Bio"></textarea>
    </div>
    <div class="form-group col-md-6">
        <label for="qualities" class="form-label">What are some good qualities
            of perfect influencer?</label>
        <textarea name="qualities"  rows="2" maxlength="200" class="form-control" placeholder="Describe Here"></textarea>
    </div>
    <div class="form-group col-md-6">
        <label for="challenge" class="form-label">What was the biggest
            challenge faced and how did you overcome it?</label>
        <textarea name="challenge"  rows="2" maxlength="200" class="form-control" placeholder="Describe Here"></textarea>
    </div>
    <div class="form-group col-md-6">
        <label for="repeat_question" class="form-label">A customer is asking
            the same question repeatedly: what will you do?</label>
        <textarea name="repeat_question"  rows="2" maxlength="200" class="form-control" placeholder="Describe Here" ></textarea>
    </div>
</div>
