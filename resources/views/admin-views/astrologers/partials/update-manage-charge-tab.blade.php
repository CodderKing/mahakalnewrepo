<div class="row p-2">
    <div id="astrologer-charge-div" class="col-12 py-4 mb-4" style="border: 1px solid grey; display:{{ $astrologer['type'] == 'freelancer' && $astrologer['primary_skills'] == 4 ? 'block' : 'none' }}">
        <div class="mb-3 d-flex">
            <h4>Astrologer Charge</h4>
        </div>
        <div class="row">
            <div class="form-group col-md-6">
                <label for="live_stream_charge" class="form-label">Live Stream Charge (As per
                    Minute)</label>
                <input type="number" name="live_stream_charge" class="form-control" placeholder="Enter live stream charge" value="{{$astrologer['is_astrologer_live_stream_charge']}}">
                <input type="hidden" name="live_stream_commission" value="{{$astrologer['is_astrologer_live_stream_commission']}}">
            </div>
            <div class="form-group col-md-6">
                <label for="call_charge" class="form-label">Calling Charge (As
                    per Minute)</label>
                <input type="number" name="call_charge" class="form-control" placeholder="Enter calling charge" value="{{$astrologer['is_astrologer_call_charge']}}">
                <input type="hidden" name="call_commission" value="{{$astrologer['is_astrologer_call_commission']}}">
            </div>
            <div class="form-group col-md-6">
                <label for="chat_charge" class="form-label">Chatting Charge (As
                    per Minute)</label>
                <input type="number" name="chat_charge" class="form-control" placeholder="Enter chating charge" value="{{$astrologer['is_astrologer_chat_charge']}}">
                <input type="hidden" name="chat_commission" value="{{$astrologer['is_astrologer_chat_commission']}}">
            </div>
            <div class="form-group col-md-6">
                <label for="report_charge" class="form-label">Report charge</label>
                <input type="number" name="report_charge" class="form-control" placeholder="Enter report Charge" value="{{$astrologer['is_astrologer_report_charge']}}">
                <input type="hidden" name="report_commission" value="{{$astrologer['is_astrologer_report_commission']}}">
            </div>
        </div>
    </div>

    <div id="pandit-charge-div" class="col-12 py-4 mb-4" style="border: 1px solid grey; display:{{ $astrologer['primary_skills'] == 3 ? 'block' : 'none' }}">
        <div class="mb-3 d-flex">
            <h4>Pandit Charge</h4>
        </div>
        {{-- <div class="form-group col-md-6">
            <label for="pandit_live_stream_charge" class="form-label">Live Stream Charge (As per
                Minute)</label>
            <input type="number" name="pandit_live_stream_charge" class="form-control" placeholder="Enter live stream charge" value="{{$astrologer['is_pandit_live_stream_charge']}}">
            <input type="hidden" name="pandit_live_stream_commission" value="{{$astrologer['is_pandit_live_stream_commission']}}">
        </div> --}}
        <div class="form-group col-md-6 d-flex align-items-center">
            <label for="is_offlinepooja" class="form-label m-0">Do You Perform Offline Pooja</label>
            <input type="checkbox" id="is-offlinepooja" class="ml-3" {{!empty($astrologer['is_pandit_offlinepooja'])?'checked':''}}>
        </div>
        <hr>

        <div class="my-2" id="pooja-list-heading" style="display: none !important;">
            <h4>Pooja Charge</h4>
        </div>
        <div class="row px-2 my-2" id="pooja-list">
        </div>

        <div class="my-2" id="vip-pooja-list-heading" style="display: none !important;">
            <h4>Customer Basis Charge</h4>
        </div>
        <div class="row px-2 my-2" id="vip-pooja-list">
        </div>

        <div class="my-2" id="anushthan-list-heading" style="display: none !important;">
            <h4>Anushthan Charge</h4>
        </div>
        <div class="row px-2 my-2" id="anushthan-list">
        </div>

        <div class="my-2" id="chadhava-list-heading" style="display: none !important;">
            <h4>Chadhava Charge</h4>
        </div>
        <div class="row px-2 my-2" id="chadhava-list">
        </div>

        <div class="my-2" id="offlinepooja-div" style="display: {{!empty($astrologer['is_pandit_offlinepooja'])?'block':'none'}}">
            <div>
                <h4>Offlinepooja Charge</h4>
            </div>
            @php
                $offlinepoojaChargeArr = json_decode($astrologer['is_pandit_offlinepooja'],true);
                $offlinepoojaTimeArr = json_decode($astrologer['is_pandit_offlinepooja_time'],true);
            @endphp
            @foreach ($offlinepoojaList as $key => $item)
                <div class="row px-2 my-2">
                    <input type="hidden" name="offlinepooja_charge_id[]" id="offlinepooja-charge-id-input{{ $item['id'] }}" class="form-control" value="{{ $item['id'] }}" {{isset($offlinepoojaChargeArr[$item['id']])?'':'disabled'}}>
                    <div class="col-4" style="align-self: center">{{ $item['name'] }}</div>
                    <div class="col-3">
                        <input type="number" name="offlinepooja_charge[]" id="offlinepooja-charge-input{{ $item['id'] }}" class="offlinepooja-charge-input form-control" placeholder="Enter Price" value="{{isset($offlinepoojaChargeArr[$item['id']])?$offlinepoojaChargeArr[$item['id']]:''}}" {{isset($offlinepoojaChargeArr[$item['id']])?($astrologer['type']=='in house'?'readonly':''):'disabled'}}>
                    </div>
                    <div class="col-3">
                        <input type="text" name="offlinepooja_time[]" id="offlinepooja-time-input{{ $item['id'] }}" class="offlinepooja-time-input form-control" placeholder="Enter Time" value="{{isset($offlinepoojaTimeArr[$item['id']])?$offlinepoojaTimeArr[$item['id']]:''}}" {{isset($offlinepoojaChargeArr[$item['id']])?'':'disabled'}}>
                    </div>
                    <div class="col-2" style="text-align: right; align-self: center;">
                        <div class="custom-control custom-switch mr-2">
                            <input type="checkbox"
                                class="custom-control-input offlinepooja-charge-checkbox"
                                id="offlinepoojaChargeCustomSwitch{{ $item['id'] }}" data-id="{{ $item['id'] }}" {{isset($offlinepoojaChargeArr[$item['id']])?'checked':''}}>
                            <label class="custom-control-label"
                                for="offlinepoojaChargeCustomSwitch{{ $item['id'] }}"></label>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
    </div>

    <div class="col-12 py-4 mb-4" style="border: 1px solid grey;">
        <div class="mb-3 d-flex">
            <h4>Kundali Making Charge</h4>
        </div>
        <div class="row">
            <div class="form-group col-md-4 d-flex align-items-center">
                <label for="is_kundali_make" class="form-label m-0">Do Your Make Kundali</label>
                <input type="checkbox" name="is_kundali_make" id="is-kundali-make" class="ml-3" {{$astrologer['is_kundali_make']==1?'checked':''}}>
            </div>
            <div class="form-group col-md-4 kundali-making-charge-div" style="display: {{$astrologer['is_kundali_make']==0?'none':''}}"> 
                <label for="kundali_making_charge" class="form-label">Kundali Making Charge (Basic)</label>
                <input type="number" name="kundali_making_charge" id="kundali-making-charge-input" class="form-control" placeholder="Enter kundali making charge" value="{{$astrologer['kundali_make_charge']}}" {{$astrologer['type']=='freelancer'?'':'readonly'}}>
                <input type="hidden" name="kundali_making_commission" id="kundali-making-commission-input" class="form-control" placeholder="Enter kundali making commission" value="{{$astrologer['kundali_make_commission']}}">
            </div>
            <div class="form-group col-md-4 kundali-making-charge-div" style="display: {{$astrologer['is_kundali_make']==0?'none':''}}"> 
                <label for="kundali_making_charge" class="form-label">Kundali Making Charge (Professional)</label>
                <input type="number" name="kundali_making_charge_pro" id="kundali-making-charge-input-pro" class="form-control" placeholder="Enter kundali making charge" value="{{$astrologer['kundali_make_charge_pro']??''}}" {{$astrologer['type']=='freelancer'?'':'readonly'}}>
                <input type="hidden" name="kundali_making_commission_pro" id="kundali-making-commission-input-pro" class="form-control" placeholder="Enter kundali making commission" value="{{$astrologer['kundali_make_commission_pro']}}">
            </div>
        </div>
    </div>

    {{-- @if ($astrologer['primary_skills']==4 || ($astrologer['primary_skills']==3 && !is_null($astrologer['other_skills']) && $astrologer['other_skills']->contains('id', 4))) --}}
        <div id="consultation-charge-div" class="col-12 py-4 mb-4" style="border: 1px solid grey; display: {{$astrologer['primary_skills']==4 || ($astrologer['primary_skills']==3 && !is_null($astrologer['other_skills']) && $astrologer['other_skills']->contains('id', 4))?'block':'none'}}">
            <div class="mb-3 d-flex">
                <h4>Consultation Charge</h4>
            </div>
            @php
                $consultationChargeArr = json_decode($astrologer['consultation_charge'],true);
            @endphp
            @foreach ($consultationList as $key => $item)
                <div class="row px-2 my-2">
                    <input type="hidden" name="consultation_charge_id[]" id="consultation-charge-id-input{{ $item['id'] }}" class="form-control" value="{{ $item['id'] }}" {{isset($consultationChargeArr[$item['id']])?'':'disabled'}}>
                    <div class="col-4" style="align-self: center">{{ $item['name'] }}</div>
                    <div class="col-4">
                        <input type="number" name="consultation_charge[]" id="consultation-charge-input{{ $item['id'] }}" class="form-control consultation-charge-input" placeholder="Enter Price" value="{{isset($consultationChargeArr[$item['id']])?$consultationChargeArr[$item['id']]:''}}" {{isset($consultationChargeArr[$item['id']])?($astrologer['type']=='in house'?'readonly':''):'disabled'}}>
                    </div>
                    <div class="col-4" style="text-align: right; align-self: center;">
                        <div class="custom-control custom-switch mr-2">
                            <input type="checkbox"
                                class="custom-control-input consultation-charge-checkbox"
                                id="consultationChargeCustomSwitch{{ $item['id'] }}" data-id="{{ $item['id'] }}" {{isset($consultationChargeArr[$item['id']])?'checked':''}}>
                            <label class="custom-control-label"
                                for="consultationChargeCustomSwitch{{ $item['id'] }}"></label>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    {{-- @endif --}}
</div>

<div class="d-flex gap-3 justify-content-end">
    <button type="reset" id="reset" class="btn btn-secondary px-4">{{ translate('reset') }}</button>
    <button type="submit" class="btn btn--primary px-4">{{ translate('update') }}</button>
</div>
