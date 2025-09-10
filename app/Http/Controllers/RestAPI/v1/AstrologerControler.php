<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailPoojaJob;
use App\Jobs\SendWhatsappMessage;
use App\Models\Astrologer\Astrologer;
use App\Models\Astrologer\AstrologerWithdraw;
use App\Models\Astrologer\Availability;
use App\Models\Astrologer\Skills;
use App\Models\AstrologerCategory;
use App\Models\BusinessSetting;
use App\Models\Chadhava;
use App\Models\Chadhava_orders;
use App\Models\OfflinePoojaOrder;
use App\Models\Prashad_deliverys;
use App\Models\Service;
use App\Models\Service_order;
use App\Models\ServiceReview;
use App\Models\ServiceTax;
use App\Models\ServiceTransaction;
use App\Models\Vippooja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use DB;
use Illuminate\Support\Facades\Storage;

class AstrologerControler extends Controller
{

    // ======================= astrologer auth ========================

    public function login(Request $request)
    {
        $auth = Auth::guard('influencer')->attempt(['email' => $request->email, 'password' => $request->password]);
        if ($auth) {
            $user = Auth::guard('influencer')->user();
            if ($user->status == 0) {
                return response()->json(['status' => 400, 'message' => 'Your account is not active yet!', 'user' => null]);
            } elseif ($user->status == 2) {
                return response()->json(['status' => 400, 'message' => 'Your account has been blocked!', 'user' => null]);
            } elseif ($user->status == 1) {
                $data = Astrologer::where('id', $user->id)->with(['primarySkill'])->first();
                $token = $user->createToken('InfluenceAuthApp')->accessToken;
                return response()->json(['status' => 200, 'message' => 'Login successful', 'token' => $token, 'user' => $data]);
            }
        }
        return response()->json(['status' => 400, 'message' => 'Invalid credentials']);
    }

    public function logout(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $revoke = Auth::guard('influencer_api')->user()->token()->revoke();
            if ($revoke) {
                return response()->json(['message' => translate('logged_out_successfully')], 200);
            }
            return response()->json(['message' => translate('unable_to_logout')], 403);
        }
        return response()->json(['message' => translate('unauthorized_user')], 403);
    }

    public function change_password(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                Astrologer::where('id', $userId)->update(['password' => bcrypt($request->password)]);
                return response()->json(['status' => 200, 'message' => 'Password changed succesfully']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    // public function update_notification(Request $request)
    // {
    //     if (Auth::guard('influencer_api')->check()) {
    //         $user = Auth::guard('influencer_api')->user();
    //         $notification = Astrologer::where('id', $user->id)->update(['receive_notification' => $request->notification]);
    //         if ($notification) {
    //             return response()->json(['status' => 200, 'message' => 'Notification updated successfully']);
    //         }
    //         return response()->json(['status' => 400, 'message' => 'Unable to update notification']);
    //     }
    //     return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    // }

    // ======================= astrologer master data ========================

    // public function category(Request $request)
    // {
    //     if (Auth::guard('influencer_api')->check()) {
    //         $category = AstrologerCategory::where('status', 1)->get();
    //         if ($category) {
    //             return response()->json(['status' => 200, 'categories' => $category]);
    //         }
    //         return response()->json(['status' => 400, 'message' => 'Category data not found']);
    //     }
    //     return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    // }

    // public function skill(Request $request)
    // {
    //     if (Auth::guard('influencer_api')->check()) {
    //         $skill = Skills::where('status', 1)->get();
    //         if ($skill) {
    //             return response()->json(['status' => 200, 'skills' => $skill]);
    //         }
    //         return response()->json(['status' => 400, 'message' => 'Skill data not found']);
    //     }
    //     return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    // }

    // ======================= astrologer detail ========================

    public function profile_detail(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $user = Auth::guard('influencer_api')->user();
            $profile = Astrologer::where('id', $user->id)->with(['primarySkill', 'availability'])->first();
            if ($profile) {
                return response()->json(['status' => 200, 'profile' => $profile]);
            }
            return response()->json(['status' => 400, 'message' => 'Profile data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    // ======================= astrologer update ========================

    public function update_profile(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $updateProfile = Astrologer::find($userId);
                if ($request->hasFile('image')) {
                    $oldImagePath = storage_path('app/public/astrologers/' . $updateProfile->image);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                    }

                    $file = $request->file('image');
                    $imageName = time() . '-astrologer' . $file->getClientOriginalName();
                    $file->storeAs('public/astrologers', $imageName);
                    $updateProfile->image = $imageName;
                }
                $updateProfile->name = $request->name;
                $updateProfile->dob = $request->dob;
                if ($updateProfile->save()) {
                    return response()->json(['status' => 200, 'message' => 'profile updated successfuly']);
                }
                return response()->json(['status' => 400, 'message' => 'unable to updated profile']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    public function update_address(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $updateAddress = Astrologer::find($userId);
                $updateAddress->address = $request->address;
                $updateAddress->state = $request->state;
                $updateAddress->city = $request->city;
                $updateAddress->pincode = $request->pincode;
                $updateAddress->latitude = $request->latitude;
                $updateAddress->longitude = $request->longitude;
                if ($updateAddress->save()) {
                    return response()->json(['status' => 200, 'message' => 'address updated successfuly']);
                }
                return response()->json(['status' => 400, 'message' => 'unable to updated address']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    // public function update_document(Request $request)
    // {
    //     if (Auth::guard('influencer_api')->check()) {
    //         $userId = Auth::guard('influencer_api')->user()->id;
    //         if ($userId) {
    //             $updateDocument = Astrologer::find($userId);

    //             //adhar front image
    //             if ($request->hasFile('adhar_front_image')) {
    //                 $adharFrontOldImagePath = storage_path('app/public/astrologers/aadhar' . $updateDocument->adharcard_front_image);
    //                 if (File::exists($adharFrontOldImagePath)) {
    //                     File::delete($adharFrontOldImagePath);
    //                 }

    //                 $adharFrontFile = $request->file('adhar_front_image');
    //                 $adharFrontImageName = time() . '-aadharfront' . $adharFrontFile->getClientOriginalName();
    //                 $adharFrontFile->storeAs('public/astrologers/aadhar', $adharFrontImageName);
    //                 $updateDocument->adharcard_front_image = $adharFrontImageName;
    //             }

    //             //adhar back image
    //             if ($request->hasFile('adhar_back_image')) {
    //                 $adharBackOldImagePath = storage_path('app/public/astrologers/aadhar' . $updateDocument->adharcard_back_image);
    //                 if (File::exists($adharBackOldImagePath)) {
    //                     File::delete($adharBackOldImagePath);
    //                 }

    //                 $adharBackFile = $request->file('adhar_back_image');
    //                 $adharBackImageName = time() . '-aadharback' . $adharBackFile->getClientOriginalName();
    //                 $adharBackFile->storeAs('public/astrologers/aadhar', $adharBackImageName);
    //                 $updateDocument->adharcard_back_image = $adharBackImageName;
    //             }

    //             //pancard image
    //             if ($request->hasFile('pancard_image')) {
    //                 $pancardOldImagePath = storage_path('app/public/astrologers/pancard' . $updateDocument->pancard_image);
    //                 if (File::exists($pancardOldImagePath)) {
    //                     File::delete($pancardOldImagePath);
    //                 }

    //                 $pancardFile = $request->file('pancard_image');
    //                 $pancardImageName = time() . '-pancard' . $pancardFile->getClientOriginalName();
    //                 $pancardFile->storeAs('public/astrologers/pancard', $pancardImageName);
    //                 $updateDocument->pancard_image = $pancardImageName;
    //             }

    //             //bank passbook image
    //             if ($request->hasFile('bank_passbook_image')) {
    //                 $bankPassbookOldImagePath = storage_path('app/public/astrologers/bankpassbook' . $updateDocument->bank_passbook_image);
    //                 if (File::exists($bankPassbookOldImagePath)) {
    //                     File::delete($bankPassbookOldImagePath);
    //                 }

    //                 $bankPassbookFile = $request->file('bank_passbook_image');
    //                 $bankPassbookImageName = time() . '-bankpassbook' . $bankPassbookFile->getClientOriginalName();
    //                 $bankPassbookFile->storeAs('public/astrologers/bankpassbook', $bankPassbookImageName);
    //                 $updateDocument->bank_passbook_image = $bankPassbookImageName;
    //             }

    //             $updateDocument->adharcard = $request->adharcard;
    //             $updateDocument->pancard = $request->pancard;
    //             $updateDocument->bank_name = $request->bank_name;
    //             $updateDocument->holder_name = $request->holder_name;
    //             $updateDocument->branch_name = $request->branch_name;
    //             $updateDocument->bank_ifsc = $request->bank_ifsc;
    //             $updateDocument->account_no = $request->account_no;
    //             if ($updateDocument->save()) {
    //                 return response()->json(['status' => 200, 'message' => 'document updated successfuly']);
    //             }
    //             return response()->json(['status' => 400, 'message' => 'unable to updated document']);
    //         }
    //         return response()->json(['status' => 400, 'message' => 'User Data not found']);
    //     }
    //     return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    // }

    // public function update_skill(Request $request)
    // {
    //     if (Auth::guard('influencer_api')->check()) {
    //         $userId = Auth::guard('influencer_api')->user()->id;
    //         if ($userId) {
    //             $updateSkill = Astrologer::find($userId);
    //             $updateSkill->other_skills = $request->other_skills;
    //             $updateSkill->category = $request->category;
    //             $updateSkill->language = $request->language;
    //             $updateSkill->experience = $request->experience;
    //             $updateSkill->daily_hours_contribution = $request->daily_hours_contribution;
    //             $updateSkill->office_address = $request->office_address;
    //             if ($updateSkill->save()) {
    //                 return response()->json(['status' => 200, 'message' => 'skill updated successfuly']);
    //             }
    //             return response()->json(['status' => 400, 'message' => 'unable to updated skill']);
    //         }
    //         return response()->json(['status' => 400, 'message' => 'User Data not found']);
    //     }
    //     return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    // }

    // public function update_detail(Request $request)
    // {
    //     if (Auth::guard('influencer_api')->check()) {
    //         $userId = Auth::guard('influencer_api')->user()->id;
    //         if ($userId) {
    //             $updateDetail = Astrologer::find($userId);
    //             $updateDetail->highest_qualification = $request->highest_qualification;
    //             $updateDetail->other_qualification = $request->other_qualification;
    //             $updateDetail->college = $request->college;
    //             $updateDetail->business_source = $request->business_source;
    //             $updateDetail->instagram = $request->instagram;
    //             $updateDetail->facebook = $request->facebook;
    //             $updateDetail->linkedin = $request->linkedin;
    //             $updateDetail->youtube = $request->youtube;
    //             $updateDetail->website = $request->website;
    //             $updateDetail->foreign_country = $request->foreign_country;
    //             $updateDetail->working = $request->working;
    //             $updateDetail->bio = $request->bio;
    //             if ($updateDetail->save()) {
    //                 return response()->json(['status' => 200, 'message' => 'detail updated successfuly']);
    //             }
    //             return response()->json(['status' => 400, 'message' => 'unable to updated detail']);
    //         }
    //         return response()->json(['status' => 400, 'message' => 'User Data not found']);
    //     }
    //     return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    // }

    public function update_availability(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $updateAvailability = Availability::where('astrologer_id', $userId)->first();
                $updateAvailability->sunday = $request->sunday;
                $updateAvailability->monday = $request->monday;
                $updateAvailability->tuesday = $request->tuesday;
                $updateAvailability->wednesday = $request->wednesday;
                $updateAvailability->thursday = $request->thursday;
                $updateAvailability->friday = $request->friday;
                $updateAvailability->saturday = $request->saturday;
                $updateAvailability->save();
                if ($updateAvailability->save()) {
                    return response()->json(['status' => 200, 'message' => 'availability updated successfuly']);
                }
                return response()->json(['status' => 400, 'message' => 'unable to updated detail']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    // public function counselling_update_service_charges(Request $request)
    // {
    //     if (Auth::guard('influencer_api')->check()) {
    //         $userId = Auth::guard('influencer_api')->user()->id;
    //         if ($userId) {
    //             $consultationCommissionData = Astrologer::select('consultation_commission')->where('id', $userId)->first();
    //             $consultationCommissionDataArr = json_decode($consultationCommissionData['consultation_commission'], true);
    //             $consultationChargeJson = null;
    //             $consultationCommissionJson = null;
    //             if ($request->consultation_charge) {
    //                 $consultationChargeArr = array_combine($request->consultation_charge_id, $request->consultation_charge);
    //                 $consultationChargeFilterArr = array_filter($consultationChargeArr, function ($value) {
    //                     return !is_null($value);
    //                 });
    //                 if (count($consultationChargeFilterArr) > 0) {
    //                     $consultationChargeJson = json_encode($consultationChargeFilterArr);
    //                     $consultationCommission = array_map(function ($value) {
    //                         return '5';
    //                     }, $consultationChargeFilterArr);
    //                     if (!empty($consultationCommissionDataArr)) {
    //                         foreach ($consultationCommission as $key => $value) {
    //                             if (!array_key_exists($key, $consultationCommissionDataArr)) {
    //                                 $consultationCommissionDataArr[$key] = $value;
    //                             }
    //                         }
    //                         foreach ($consultationCommissionDataArr as $key => $value) {
    //                             if (!array_key_exists($key, $consultationCommission)) {
    //                                 unset($consultationCommissionDataArr[$key]);
    //                             }
    //                         }
    //                         ksort($consultationCommissionDataArr);
    //                         $consultationCommissionJson = json_encode($consultationCommissionDataArr);
    //                     } else {
    //                         $consultationCommissionJson = json_encode($consultationCommission);
    //                     }
    //                 }
    //             }

    //             $updateCounsellingCharges = Astrologer::find($userId);
    //             $updateCounsellingCharges->is_astrologer_live_stream_charge = $request->live_stream_charge;
    //             $updateCounsellingCharges->is_astrologer_live_stream_commission = empty($request->live_stream_charge) ? null : (!empty($request->live_stream_charge) && !empty($request->live_stream_commission) ? $request->live_stream_commission : 5);
    //             $updateCounsellingCharges->is_astrologer_call_charge = $request->call_charge;
    //             $updateCounsellingCharges->is_astrologer_call_commission = empty($request->call_charge) ? null : (!empty($request->call_charge) && !empty($request->call_commission) ? $request->call_commission : 5);
    //             $updateCounsellingCharges->is_astrologer_chat_charge = $request->chat_charge;
    //             $updateCounsellingCharges->is_astrologer_chat_commission = empty($request->chat_charge) ? null : (!empty($request->chat_charge) && !empty($request->chat_commission) ? $request->chat_commission : 5);
    //             $updateCounsellingCharges->is_astrologer_report_charge = $request->report_charge;
    //             $updateCounsellingCharges->is_astrologer_report_commission = empty($request->report_charge) ? null : (!empty($request->report_charge) && !empty($request->report_commission) ? $request->report_commission : 5);
    //             $updateCounsellingCharges->consultation_charge = $consultationChargeJson;
    //             $updateCounsellingCharges->consultation_commission = $consultationCommissionJson;
    //             if ($updateCounsellingCharges->save()) {
    //                 return response()->json(['status' => 200, 'message' => 'service charges updated successfuly']);
    //             }
    //             return response()->json(['status' => 400, 'message' => 'unable to updated detail']);
    //         }
    //         return response()->json(['status' => 400, 'message' => 'User Data not found']);
    //     }
    //     return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    // }

    // public function pooja_update_service_charges(Request $request)
    // {
    //     if (Auth::guard('influencer_api')->check()) {
    //         $userId = Auth::guard('influencer_api')->user()->id;
    //         if ($userId) {
    //             // pooja charge array
    //             $poojaCommissionData = Astrologer::select('is_pandit_pooja_commission')->where('id', $id)->first();
    //             $poojaCommissionDataArr = json_decode($poojaCommissionData['is_pandit_pooja_commission'], true);
    //             $poojaChargeJson = null;
    //             $poojaCommissionJson = null;
    //             if ($request->pooja_charge_id) {
    //                 $poojaChargeArr = array_combine($request->pooja_charge_id, $request->pooja_charge);
    //                 $poojaChargeFilterArr = array_filter($poojaChargeArr, function ($value) {
    //                     return !is_null($value);
    //                 });
    //                 if (count($poojaChargeFilterArr) > 0) {
    //                     $poojaChargeJson = json_encode($poojaChargeFilterArr);
    //                     $poojaCommission = array_map(function ($value) {
    //                         return '5';
    //                     }, $poojaChargeFilterArr);
    //                     if (!empty($poojaCommissionDataArr)) {
    //                         foreach ($poojaCommission as $key => $value) {
    //                             if (!array_key_exists($key, $poojaCommissionDataArr)) {
    //                                 $poojaCommissionDataArr[$key] = $value;
    //                             }
    //                         }
    //                         foreach ($poojaCommissionDataArr as $key => $value) {
    //                             if (!array_key_exists($key, $poojaCommission)) {
    //                                 unset($poojaCommissionDataArr[$key]);
    //                             }
    //                         }
    //                         ksort($poojaCommissionDataArr);
    //                         $poojaCommissionJson = json_encode($poojaCommissionDataArr);
    //                     } else {
    //                         $poojaCommissionJson = json_encode($poojaCommission);
    //                     }
    //                 }
    //             }

    //             // vip pooja charge array
    //             $vipPoojaCommissionData = Astrologer::select('is_pandit_vippooja_commission')->where('id', $id)->first();
    //             $vipPoojaCommissionDataArr = json_decode($vipPoojaCommissionData['is_pandit_vippooja_commission'], true);
    //             $vipPoojaChargeJson = null;
    //             $vipPoojaCommissionJson = null;
    //             if ($request->vip_pooja_charge_id) {
    //                 $vipPoojaChargeArr = array_combine($request->vip_pooja_charge_id, $request->vip_pooja_charge);
    //                 $vipPoojaChargeFilterArr = array_filter($vipPoojaChargeArr, function ($value) {
    //                     return !is_null($value);
    //                 });
    //                 if (count($vipPoojaChargeFilterArr) > 0) {
    //                     $vipPoojaChargeJson = json_encode($vipPoojaChargeFilterArr);
    //                     $vipPoojaCommission = array_map(function ($value) {
    //                         return '5';
    //                     }, $vipPoojaChargeFilterArr);
    //                     // dd($vipPoojaCommission);
    //                     if (!empty($vipPoojaCommissionDataArr)) {
    //                         foreach ($vipPoojaCommission as $key => $value) {
    //                             if (!array_key_exists($key, $vipPoojaCommissionDataArr)) {
    //                                 $vipPoojaCommissionDataArr[$key] = $value;
    //                             }
    //                         }
    //                         foreach ($vipPoojaCommissionDataArr as $key => $value) {
    //                             if (!array_key_exists($key, $vipPoojaCommission)) {
    //                                 unset($vipPoojaCommissionDataArr[$key]);
    //                             }
    //                         }
    //                         ksort($vipPoojaCommissionDataArr);
    //                         $vipPoojaCommissionJson = json_encode($vipPoojaCommissionDataArr);
    //                     } else {
    //                         $vipPoojaCommissionJson = json_encode($vipPoojaCommission);
    //                     }
    //                 }
    //             }
    //             // dd($vipPoojaCommissionJson);

    //             // anushthan charge array
    //             $anushthanCommissionData = Astrologer::select('is_pandit_anushthan_commission')->where('id', $id)->first();
    //             $anushthanCommissionDataArr = json_decode($anushthanCommissionData['is_pandit_anushthan_commission'], true);
    //             $anushthanChargeJson = null;
    //             $anushthanCommissionJson = null;
    //             if ($request->anushthan_charge_id) {
    //                 $anushthanChargeArr = array_combine($request->anushthan_charge_id, $request->anushthan_charge);
    //                 $anushthanChargeFilterArr = array_filter($anushthanChargeArr, function ($value) {
    //                     return !is_null($value);
    //                 });
    //                 if (count($anushthanChargeFilterArr) > 0) {
    //                     $anushthanChargeJson = json_encode($anushthanChargeFilterArr);
    //                     $anushthanCommission = array_map(function ($value) {
    //                         return '5';
    //                     }, $anushthanChargeFilterArr);
    //                     if (!empty($anushthanCommissionDataArr)) {
    //                         foreach ($anushthanCommission as $key => $value) {
    //                             if (!array_key_exists($key, $anushthanCommissionDataArr)) {
    //                                 $anushthanCommissionDataArr[$key] = $value;
    //                             }
    //                         }
    //                         foreach ($anushthanCommissionDataArr as $key => $value) {
    //                             if (!array_key_exists($key, $anushthanCommission)) {
    //                                 unset($anushthanCommissionDataArr[$key]);
    //                             }
    //                         }
    //                         ksort($anushthanCommissionDataArr);
    //                         $anushthanCommissionJson = json_encode($anushthanCommissionDataArr);
    //                     } else {
    //                         $anushthanCommissionJson = json_encode($anushthanCommission);
    //                     }
    //                 }
    //             }


    //             // chadhava charge array
    //             $chadhavaCommissionData = Astrologer::select('is_pandit_chadhava_commission')->where('id', $id)->first();
    //             $chadhavaCommissionDataArr = json_decode($chadhavaCommissionData['is_pandit_chadhava_commission'], true);
    //             // dd($chadhavaCommissionDataArr);
    //             $chadhavaChargeJson = null;
    //             $chadhavaCommissionJson = null;
    //             if ($request->chadhava_charge_id) {
    //                 $chadhavaChargeArr = array_combine($request->chadhava_charge_id, $request->chadhava_charge);
    //                 $chadhavaChargeFilterArr = array_filter($chadhavaChargeArr, function ($value) {
    //                     return !is_null($value);
    //                 });
    //                 if (count($chadhavaChargeFilterArr) > 0) {
    //                     $chadhavaChargeJson = json_encode($chadhavaChargeFilterArr);
    //                     $chadhavaCommission = array_map(function ($value) {
    //                         return '5';
    //                     }, $chadhavaChargeFilterArr);
    //                     if (!empty($chadhavaCommissionDataArr)) {
    //                         foreach ($chadhavaCommission as $key => $value) {
    //                             if (!array_key_exists($key, $chadhavaCommissionDataArr)) {
    //                                 $chadhavaCommissionDataArr[$key] = $value;
    //                             }
    //                         }
    //                         foreach ($chadhavaCommissionDataArr as $key => $value) {
    //                             if (!array_key_exists($key, $chadhavaCommission)) {
    //                                 unset($chadhavaCommissionDataArr[$key]);
    //                             }
    //                         }
    //                         ksort($chadhavaCommissionDataArr);
    //                         $chadhavaCommissionJson = json_encode($chadhavaCommissionDataArr);
    //                     } else {
    //                         $chadhavaCommissionJson = json_encode($chadhavaCommission);
    //                     }
    //                 }
    //             }

    //             // consultation charge array
    //             $consultationCommissionData = Astrologer::select('consultation_commission')->where('id', $id)->first();
    //             $consultationCommissionDataArr = json_decode($consultationCommissionData['consultation_commission'], true);
    //             $consultationChargeJson = null;
    //             $consultationCommissionJson = null;
    //             if ($request->consultation_charge) {
    //                 $consultationChargeArr = array_combine($request->consultation_charge_id, $request->consultation_charge);
    //                 $consultationChargeFilterArr = array_filter($consultationChargeArr, function ($value) {
    //                     return !is_null($value);
    //                 });
    //                 if (count($consultationChargeFilterArr) > 0) {
    //                     $consultationChargeJson = json_encode($consultationChargeFilterArr);
    //                     $consultationCommission = array_map(function ($value) {
    //                         return '5';
    //                     }, $consultationChargeFilterArr);
    //                     if (!empty($consultationCommissionDataArr)) {
    //                         foreach ($consultationCommission as $key => $value) {
    //                             if (!array_key_exists($key, $consultationCommissionDataArr)) {
    //                                 $consultationCommissionDataArr[$key] = $value;
    //                             }
    //                         }
    //                         foreach ($consultationCommissionDataArr as $key => $value) {
    //                             if (!array_key_exists($key, $consultationCommission)) {
    //                                 unset($consultationCommissionDataArr[$key]);
    //                             }
    //                         }
    //                         ksort($consultationCommissionDataArr);
    //                         $consultationCommissionJson = json_encode($consultationCommissionDataArr);
    //                     } else {
    //                         $consultationCommissionJson = json_encode($consultationCommission);
    //                     }
    //                 }
    //             }

    //             $updatePoojaCharges = Astrologer::find($userId);

    //             $updatePoojaCharges->is_pandit_pooja_category = $request->is_pandit_pooja_category ? json_encode($request->is_pandit_pooja_category) : null;
    //             $updatePoojaCharges->is_pandit_live_stream_charge = $request->pandit_live_stream_charge;
    //             $updatePoojaCharges->is_pandit_live_stream_commission = empty($request->pandit_live_stream_charge) ? null : (!empty($request->pandit_live_stream_charge) && !empty($request->pandit_live_stream_commission) ? $request->pandit_live_stream_commission : 5);
    //             $updatePoojaCharges->is_pandit_pooja = $poojaChargeJson;
    //             $updatePoojaCharges->is_pandit_vippooja = $vipPoojaChargeJson;
    //             $updatePoojaCharges->is_pandit_anushthan = $anushthanChargeJson;
    //             $updatePoojaCharges->is_pandit_chadhava = $chadhavaChargeJson;
    //             $updatePoojaCharges->consultation_charge = $consultationChargeJson;
    //             $updatePoojaCharges->is_pandit_pooja_commission = $poojaCommissionJson;
    //             $updatePoojaCharges->is_pandit_vippooja_commission = $vipPoojaCommissionJson;
    //             $updatePoojaCharges->is_pandit_anushthan_commission = $anushthanCommissionJson;
    //             $updatePoojaCharges->is_pandit_chadhava_commission = $chadhavaCommissionJson;
    //             $updatePoojaCharges->consultation_commission = $consultationCommissionJson;

    //             if ($updatePoojaCharges->save()) {
    //                 return response()->json(['status' => 200, 'message' => 'service charges updated successfuly']);
    //             }
    //             return response()->json(['status' => 400, 'message' => 'unable to updated detail']);
    //         }
    //         return response()->json(['status' => 400, 'message' => 'User Data not found']);
    //     }
    //     return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    // }

    // ======================= order list ========================

    public function assigned_order(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $status = $request->status;
                $order['counselling'] = Service_order::where('pandit_assign', $userId)->where('type', 'counselling')->with('services')->where('status', $request->status)->orderBy('created_at', 'DESC')->get();

                $order['pooja'] = Service_order::where('pandit_assign', $userId)->where('type', 'pooja')->with('services')->selectRaw('service_id, COUNT(*) as total_orders,booking_date,GROUP_CONCAT(members SEPARATOR "|") as members,created_at')->groupBy('service_id', 'booking_date')->where(function ($query) use ($status) {
                    if ($status == 0) {
                        $query->whereIn('order_status', [0, 3, 4, 5]);
                    } else {
                        $query->where('order_status', $status);
                    }
                })->orderBy('created_at', 'DESC')->get();
                $order['vip'] = Service_order::where('pandit_assign', $userId)->where('type', 'vip')->with('vippoojas')->where(function ($query) use ($status) {
                    if ($status == 0) {
                        $query->whereIn('order_status', [0, 3, 4, 5]);
                    } else {
                        $query->where('order_status', $status);
                    }
                })->orderBy('created_at', 'DESC')->get();
                $order['anushthan'] = Service_order::where('pandit_assign', $userId)->where('type', 'anushthan')->with('vippoojas')->where(function ($query) use ($status) {
                    if ($status == 0) {
                        $query->whereIn('order_status', [0, 3, 4, 5]);
                    } else {
                        $query->where('order_status', $status);
                    }
                })->orderBy('created_at', 'DESC')->get();
                $order['chadhava'] = Chadhava_orders::where('pandit_assign', $userId)->where('type', 'chadhava')->where(function ($query) use ($status) {
                    if ($status == 0) {
                        $query->whereIn('order_status', [0, 3, 4, 5]);
                    } else {
                        $query->where('order_status', $status);
                    }
                })->with('chadhava')->selectRaw('service_id, COUNT(*) as total_orders,pandit_assign,booking_date, COUNT(created_at) as booking_count, SUM(pay_amount) as total_amount, GROUP_CONCAT(members SEPARATOR "|") as members,order_status,created_at,customer_id,id')->groupBy('service_id', 'booking_date')->orderBy('created_at', 'DESC')->get();
                $order['offlinePooja'] = OfflinePoojaOrder::where('pandit_assign', $userId)->with('offlinePooja')
                    ->where('offline_pooja_orders.pandit_assign', $userId)
                    ->where('offline_pooja_orders.status', $request->status)
                    ->join('offlinepooja_categories', 'offline_pooja_orders.type', '=', 'offlinepooja_categories.id')
                    ->select(
                        'offline_pooja_orders.*',
                        'offlinepooja_categories.name as type'
                    )
                    ->orderBy('offline_pooja_orders.created_at', 'DESC')
                    ->get();
                if ($order) {
                    return response()->json(['status' => 200, 'orders' => $order]);
                }
                return response()->json(['status' => 400, 'message' => 'Unable to fetch orders']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    // public function category_change_list(Request $request)
    // {
    //     if (Auth::guard('influencer_api')->check()) {
    //         $userId = Auth::guard('influencer_api')->user()->id;
    //         if ($userId) {
    //             $ids = $request->input('id');
    //             $categoryIds = [];
    //             $pooja = "";
    //             $vipPooja = "";
    //             $anushthan = "";
    //             $chadhava = "";

    //             if (!empty($ids)) {
    //                 //for pooja
    //                 if (in_array("34", $ids) || in_array("35", $ids) || in_array("36", $ids) || in_array("38", $ids)) {
    //                     foreach ($ids as $id) {
    //                         if ($id == 34 || $id == 35 || $id == 36 || $id == 38) {
    //                             $categoryIds[] = $id;
    //                         }
    //                     }
    //                     $pooja = Service::whereIn('sub_category_id', $categoryIds)->where('status', 1)->get();
    //                 }

    //                 // for vip pooja
    //                 if (in_array("50", $ids)) {
    //                     $vipPooja = Vippooja::where('is_anushthan', 0)->where('status', 1)->get();
    //                 }

    //                 //for anushthan
    //                 if (in_array("51", $ids)) {
    //                     $anushthan = Vippooja::where('is_anushthan', 1)->where('status', 1)->get();
    //                 }

    //                 //for chadhava
    //                 if (in_array("52", $ids)) {
    //                     $chadhava = Chadhava::where('status', 1)->get();
    //                 }
    //             }

    //             return response()->json(['status' => 200, 'pooja' => $pooja, 'vipPooja' => $vipPooja, 'anushthan' => $anushthan, 'chadhava' => $chadhava]);
    //         }
    //         return response()->json(['status' => 400, 'message' => 'User Data not found']);
    //     }
    //     return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    // }

    // ======================= counselling order details ========================

    public function counselling_order_detail($orderId)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $orderDetail = Service_order::where('order_id', $orderId)->with('customers')->with('services.category')->with('payments')->with('counselling_user')->first();
                if ($orderDetail) {
                    return response()->json(['status' => 200, 'orderDetail' => $orderDetail]);
                }
                return response()->json(['status' => 400, 'message' => 'order detail not found']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    public function counselling_order_report_upload(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                if ($request->hasFile('report')) {
                    // $file = $request->file('report');
                    // $report = time() . '-report.' . $file->getClientOriginalExtension();
                    // $file->storeAs('public/consultation-order-report', $report);

                    $file = $request->file('report');

                    $allowedExtensions = ['jpg', 'jpeg', 'png'];
                    $extension = strtolower($file->getClientOriginalExtension());

                    $report = time() . '-report.' . $extension;

                    if (in_array($extension, $allowedExtensions)) {
                        $mainImage = Image::make($file);

                        $headerPath = public_path('assets/back-end/img/counselling-report/top.png');
                        $footerPath = public_path('assets/back-end/img/counselling-report/bottom.png');

                        $headerImage = Image::make($headerPath)->resize($mainImage->width(), null, function ($constraint) {
                            $constraint->aspectRatio();
                        });

                        $footerImage = Image::make($footerPath)->resize($mainImage->width(), null, function ($constraint) {
                            $constraint->aspectRatio();
                        });

                        $finalHeight = $headerImage->height() + $mainImage->height() + $footerImage->height();
                        $canvas = Image::canvas($mainImage->width(), $finalHeight);

                        $canvas->insert($headerImage, 'top');
                        $canvas->insert($mainImage, 'top-left', 0, $headerImage->height());
                        $canvas->insert($footerImage, 'bottom');

                        Storage::put("public/consultation-order-report/{$report}", (string) $canvas->encode());
                    } else {
                        $file->storeAs('public/consultation-order-report', $report);
                    }

                    $uploadReport = Service_order::where('id', $request->id)->update(['counselling_report' => $report, 'counselling_report_verified' => 0, 'counselling_report_reject_reason' => null]);
                    if ($uploadReport) {
                        return response()->json(['status' => 200, 'message' => 'report uploaded successfully']);
                    }
                    return response()->json(['status' => 400, 'message' => 'unable to upload report']);
                }
                return response()->json(['status' => 400, 'message' => 'file is missing']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    // public function order_status_changed(Request $request)
    // {
    //     if (Auth::guard('influencer_api')->check()) {
    //         $userId = Auth::guard('influencer_api')->user()->id;
    //         if ($userId) {
    //             $orderStatus = "";
    //             if ($request->order_status == 1) {
    //                 $orderStatus = Service_order::where('id', $request->order_id)->update(['status' => $request->order_status, 'order_completed' => now()]);
    //             } else if ($request->order_status == 2) {
    //                 $orderStatus = Service_order::where('id', $request->order_id)->update(['status' => $request->order_status, 'order_canceled' => now(), 'order_canceled_reason' => $request->reason]);
    //             }
    //             if ($orderStatus) {
    //                 return response()->json(['status' => 200, 'message' => 'Order status changed successfully']);
    //             }
    //             return response()->json(['status' => 400, 'message' => 'Unable to changed status']);
    //         }
    //         return response()->json(['status' => 400, 'message' => 'User Data not found']);
    //     }
    //     return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    // }

    // ======================= pooja / vip / anushthan / chadhava order details ========================

    public function pooja_order_detail(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $orderDetail = Service_order::where('service_id', $request->service_id)->where('booking_date', $request->booking_date)
                    ->with(['services', 'pandit', 'vippoojas'])
                    ->selectRaw('service_id,status, COUNT(*) as total_orders, DATE(created_at) as order_date,booking_date, COUNT(*) as booking_count, GROUP_CONCAT(members SEPARATOR "|") as members,GROUP_CONCAT(gotra SEPARATOR "|") as gotra,order_status,schedule_time,created_at,updated_at')->orderBy('created_at', 'DESC')->first();

                if ($orderDetail) {
                    return response()->json(['status' => 200, 'orderDetail' => $orderDetail]);
                }
                return response()->json(['status' => 400, 'message' => 'order detail not found']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    public function pooja_order_schedule_time(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $schedule = Service_order::where('service_id', $request->service_id)->where('booking_date', $request->booking_date)->with(['services', 'vippoojas'])->get();
                foreach ($schedule as $pooja) {
                    $pooja->schedule_time = $request->schedule_time;
                    $pooja->schedule_created = now();
                    $pooja->order_status = 3;
                    $pooja->save();

                    $messageData = [
                        'scheduled_time' => $request->schedule_time ?? 'N/A',
                        'booking_date' => date('d-m-Y', strtotime($pooja->booking_date)),
                        'orderId' => $pooja->order_id,
                        'customer_id' => $pooja->customer_id,
                    ];
                    if ($pooja->type == 'pooja') {
                        $messageData['service_name'] = $pooja->services->name;
                        $messageData['puja_venue'] = $pooja->services->pooja_venue ?? 'N/A';
                        SendWhatsappMessage::dispatch('whatsapp', 'Schedule', $messageData);
                    } elseif ($pooja->type == 'vip') {
                        $messageData['service_name'] = $pooja->vippoojas->name;
                        $messageData['puja'] = 'VIP Puja';
                        SendWhatsappMessage::dispatch('vipanushthan', 'Schedule', $messageData);
                    } elseif ($pooja->type == 'anushthan') {
                        $messageData['service_name'] = $pooja->vippoojas->name;
                        $messageData['puja'] = 'Anushthan';
                        SendWhatsappMessage::dispatch('vipanushthan', 'Schedule', $messageData);
                    }

                    $userInfo = \App\Models\User::where('id', $pooja->customer_id)->first();
                    if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                        if ($pooja->type == 'pooja') {
                            $service_name = \App\Models\Service::where('id', $pooja->service_id)->where('product_type', 'pooja')->first();
                        } elseif ($pooja->type == 'vip') {
                            $service_name = \App\Models\Vippooja::where('id', $pooja->service_id)->where('is_anushthan', 0)->first();
                        } elseif ($pooja->type == 'anushthan') {
                            $service_name = \App\Models\Vippooja::where('id', $pooja->service_id)->where('is_anushthan', 1)->first();
                        }

                        $bookingDetails = \App\Models\Service_order::where('service_id', ($pooja->service_id ?? ""))
                            ->where('type', $pooja->type)->where('booking_date', ($pooja->booking_date ?? ""))
                            ->where('customer_id', ($pooja->customer_id ?? ""))->where('order_id', ($pooja->order_id ?? ""))
                            ->first();
                        $data = [
                            'type' => 'pooja',
                            'email' => $userInfo->email,
                            'subject' => $pooja->type . ' Time Scheduled',
                            'htmlContent' =>
                            \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-schedule-template', compact('userInfo', 'service_name', 'bookingDetails'))->render(),
                        ];
                        SendEmailPoojaJob::dispatch($data);
                    }
                }

                if ($schedule) {
                    return response()->json(['status' => 200, 'message' => 'Order schedule time updated']);
                }
                return response()->json(['status' => 400, 'message' => 'Unable to changed status']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    public function pooja_order_golive(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $golive = Service_order::where('service_id', $request->service_id)->where('booking_date', $request->booking_date)->with(['services', 'vippoojas'])->get();
                foreach ($golive as $pooja) {
                    $pooja->live_stream = $request->live_url;
                    $pooja->live_created_stream = now();
                    $pooja->order_status = 4;
                    $pooja->save();

                    $messageData = [
                        'live_stream' => $request->live_url,
                        'scheduled_time' => $request->schedule_time ?? 'N/A',
                        'booking_date' => date('d-m-Y', strtotime($pooja->booking_date)),
                        'orderId' => $pooja->order_id,
                        'customer_id' => $pooja->customer_id,
                    ];
                    if ($pooja->type == 'pooja') {
                        $messageData['service_name'] = $pooja->services->name;
                        $messageData['puja_venue'] = $pooja->services->pooja_venue ?? 'N/A';
                        SendWhatsappMessage::dispatch('whatsapp', 'Live Stream', $messageData);
                    } elseif ($pooja->type == 'vip') {
                        $messageData['service_name'] = $pooja->vippoojas->name;
                        $messageData['puja'] = 'VIP Puja';
                        SendWhatsappMessage::dispatch('vipanushthan', 'Live Stream', $messageData);
                    } elseif ($pooja->type == 'anushthan') {
                        $messageData['service_name'] = $pooja->vippoojas->name;
                        $messageData['puja'] = 'Anushthan';
                        SendWhatsappMessage::dispatch('vipanushthan', 'Live Stream', $messageData);
                    }

                    // send email
                    $userInfo = \App\Models\User::where('id', $pooja->customer_id)->first();
                    if ($pooja->type == 'pooja') {
                        $service_name = \App\Models\Service::where('id', $pooja->service_id)->where('product_type', 'pooja')->first();
                    } elseif ($pooja->type == 'vip') {
                        $service_name = \App\Models\Vippooja::where('id', $pooja->service_id)->where('is_anushthan', 0)->first();
                    } elseif ($pooja->type == 'anushthan') {
                        $service_name = \App\Models\Vippooja::where('id', $pooja->service_id)->where('is_anushthan', 1)->first();
                    }
                    $bookingDetails = \App\Models\Service_order::where('service_id', ($pooja->service_id ?? ""))
                        ->where('type', $pooja->type)
                        ->where('booking_date', ($pooja->booking_date ?? ""))
                        ->where('customer_id', ($pooja->customer_id ?? ""))
                        ->where('order_id', ($pooja->order_id ?? ""))
                        ->first();
                    if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                        $data = [
                            'type' => 'pooja',
                            'email' => $userInfo->email,
                            'subject' => $pooja->type . ' Live Now',
                            'htmlContent' =>
                            \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-live-template', compact('userInfo', 'service_name', 'bookingDetails'))->render(),
                        ];

                        SendEmailPoojaJob::dispatch($data);
                    }
                }

                if ($golive) {
                    return response()->json(['status' => 200, 'message' => 'Order live stream url updated']);
                }
                return response()->json(['status' => 400, 'message' => 'Unable to changed status']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    public function pooja_order_complete(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $complete = Service_order::where('service_id', $request->service_id)->where('booking_date', $request->booking_date)->with(['services', 'customers', 'payments', 'vippoojas'])->get();
                foreach ($complete as $pooja) {
                    $certificate = Image::make(public_path('assets/back-end/img/certificate/format/certificate-format.png'));
                    $certificate->text(ucwords($pooja->customers->f_name . ' ' . $pooja->customers->l_name), 950, 630, function ($font) {
                        $font->file(public_path('fonts/Roboto-Bold.ttf'));
                        $font->size(100);
                        $font->color('#ffffff');
                        $font->align('center');
                        $font->valign('top');
                    });
                    $serviceName = "";
                    if ($pooja->type == 'vip' || $pooja->type == 'anushthan') {
                        $serviceName = wordwrap($pooja->vippoojas->name, 65, "\n", false);
                    } else {
                        $serviceName = wordwrap($pooja->services->name, 65, "\n", false);
                    }
                    $certificate->text($serviceName, 500, 815, function ($font) {
                        $font->file(public_path('fonts/Roboto-Black.ttf'));
                        $font->size(40);
                        $font->color('#ffffff');
                        $font->align('left');
                        $font->valign('top');
                    });

                    $certificate->text(date('d/m/Y', strtotime($pooja->created_at)), 830, 994, function ($font) {
                        $font->file(public_path('fonts/Roboto-Black.ttf'));
                        $font->size(40);
                        $font->color('#ffffff');
                        $font->align('center');
                        $font->valign('top');
                    });
                    $certificatePath = 'assets/back-end/img/certificate/pooja/' . $pooja['order_id'] . '.jpg';
                    $certificate->save(public_path($certificatePath));

                    $pooja->pooja_video = $request->live_url;
                    $pooja->video_created_sharing = now();
                    $pooja->order_status = 1;
                    $pooja->status = 1;
                    $pooja->is_completed = 1;
                    $pooja->is_edited = 1;
                    $pooja->pooja_certificate = $certificatePath;
                    $pooja->order_completed = now();
                    $pooja->save();

                    if ($pooja->is_prashad == 1) {
                        Prashad_deliverys::where('order_id', $pooja['order_id'])->where('service_id', $request->service_id)->update([
                            'pooja_status' => 1,
                            'status' => 1,
                            'order_completed' => now(),
                        ]);
                    }

                    // whatsapp video share
                    $messageData = [
                        'share_video' => $request->live_url,
                        'scheduled_time' => $request->schedule_time ?? 'N/A',
                        'booking_date' => date('d-m-Y', strtotime($pooja->booking_date)),
                        'orderId' => $pooja->order_id,
                        'customer_id' => $pooja->customer_id,
                    ];
                    if ($pooja->type == 'pooja') {
                        $messageData['service_name'] = $pooja->services->name;
                        $messageData['puja_venue'] = $pooja->services->pooja_venue ?? 'N/A';
                        SendWhatsappMessage::dispatch('whatsapp', 'Shared Video', $messageData);
                    } elseif ($pooja->type == 'vip') {
                        $messageData['service_name'] = $pooja->vippoojas->name;
                        $messageData['puja'] = 'VIP Puja';
                        SendWhatsappMessage::dispatch('vipanushthan', 'Shared Video', $messageData);
                    } elseif ($pooja->type == 'anushthan') {
                        $messageData['service_name'] = $pooja->vippoojas->name;
                        $messageData['puja'] = 'Anushthan';
                        SendWhatsappMessage::dispatch('vipanushthan', 'Shared Video', $messageData);
                    }

                    // whatsapp order complete
                    $messageCompleteData = [
                        'share_video' => $request->live_url,
                        'attachment' => asset('public/' . $certificatePath),
                        'type' => 'text-with-media',
                        'amount' => webCurrencyConverter((float) ($pooja->pay_amount ?? 0)),
                        'booking_date' => date('d-m-Y', strtotime($pooja->booking_date)),
                        'orderId' => $pooja->order_id,
                        'customer_id' => $pooja->customer_id,
                    ];
                    if ($pooja->type == 'pooja') {
                        $messageCompleteData['service_name'] = $pooja->services->name;
                        $messageCompleteData['puja_venue'] = $pooja->services->pooja_venue ?? 'N/A';
                        SendWhatsappMessage::dispatch('whatsapp', 'Completed', $messageCompleteData);
                    } elseif ($pooja->type == 'vip') {
                        $messageCompleteData['service_name'] = $pooja->vippoojas->name;
                        $messageCompleteData['puja'] = 'VIP Puja';
                        SendWhatsappMessage::dispatch('vipanushthan', 'Completed', $messageCompleteData);
                    } elseif ($pooja->type == 'anushthan') {
                        $messageCompleteData['service_name'] = $pooja->vippoojas->name;
                        $messageCompleteData['puja'] = 'Anushthan';
                        SendWhatsappMessage::dispatch('vipanushthan', 'Completed', $messageCompleteData);
                    }

                    // send email
                    $userInfo = \App\Models\User::where('id', $pooja->customer_id)->first();
                    if ($pooja->type == 'pooja') {
                        $service_name = \App\Models\Service::where('id', $pooja->service_id)->where('product_type', 'pooja')->first();
                    } elseif ($pooja->type == 'vip') {
                        $service_name = \App\Models\Vippooja::where('id', $pooja->service_id)->where('is_anushthan', 0)->first();
                    } elseif ($pooja->type == 'anushthan') {
                        $service_name = \App\Models\Vippooja::where('id', $pooja->service_id)->where('is_anushthan', 1)->first();
                    }
                    $bookingDetails = \App\Models\Service_order::where('service_id', ($pooja->service_id ?? ""))
                        ->where('type', $pooja->type)
                        ->where('booking_date', ($pooja->booking_date ?? ""))
                        ->where('customer_id', ($pooja->customer_id ?? ""))
                        ->where('order_id', ($pooja->order_id ?? ""))
                        ->first();
                    if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                        $data = [
                            'type' => 'pooja',
                            'email' => $userInfo->email,
                            'subject' => $pooja->type . ' Completed',
                            'htmlContent' =>
                            \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-complete', compact('userInfo', 'service_name', 'bookingDetails'))->render(),
                        ];

                        SendEmailPoojaJob::dispatch($data);
                    }
                }

                $commission = 0;
                $tax = ServiceTax::first();
                $astrologer = Astrologer::where('id', $pooja['pandit_assign'])->first();
                if ($astrologer) {
                    if ($request->type == 'pooja') {
                        foreach (json_decode($astrologer['is_pandit_pooja_commission']) as $key => $value) {
                            if ($key == $pooja['services']['id']) {
                                $commission = $value;
                            }
                        }
                    } elseif ($request->type == 'vip') {
                        foreach (json_decode($astrologer['is_pandit_vippooja_commission']) as $key => $value) {
                            if ($key == $pooja['services']['id']) {
                                $commission = $value;
                            }
                        }
                    } elseif ($request->type == 'anushthan') {
                        foreach (json_decode($astrologer['is_pandit_anushthan_commission']) as $key => $value) {
                            if ($key == $pooja['services']['id']) {
                                $commission = $value;
                            }
                        }
                    }
                }
                $transaction = new ServiceTransaction();
                $transaction->astro_id = $pooja['pandit_assign'];
                $transaction->type = $request->type;
                $transaction->order_id = $pooja['order_id'];
                if (empty($pooja['payments']['transaction_id'])) {
                    $transaction->txn_id = $pooja['wallet_translation_id'];
                } else {
                    $transaction->txn_id = $pooja['payments']['transaction_id'];
                }
                $transaction->amount = $pooja['pay_amount'];
                $transaction->commission = $commission;
                $transaction->tax = $tax['online_pooja'] ?? 0;
                $transaction->save();

                if ($complete) {
                    return response()->json(['status' => 200, 'message' => 'video share and order completed']);
                }
                return response()->json(['status' => 400, 'message' => 'Unable to changed status']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    // ======================= vip order details ========================

    public function vip_order_detail(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $orderDetail = Service_order::where('order_id', $request->order_id)->with(['services', 'pandit', 'vippoojas'])->first();

                if ($orderDetail) {
                    return response()->json(['status' => 200, 'orderDetail' => $orderDetail]);
                }
                return response()->json(['status' => 400, 'message' => 'order detail not found']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    public function vip_order_schedule_time(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $schedule = Service_order::where('order_id', $request->order_id)->with(['services', 'vippoojas'])->first();
                $schedule->schedule_time = $request->schedule_time;
                $schedule->schedule_created = now();
                $schedule->order_status = 3;
                $schedule->save();

                $messageData = [
                    'scheduled_time' => $request->schedule_time ?? 'N/A',
                    'booking_date' => date('d-m-Y', strtotime($schedule->booking_date)),
                    'orderId' => $schedule->order_id,
                    'customer_id' => $schedule->customer_id,
                ];

                $messageData['service_name'] = $schedule->vippoojas->name;
                $messageData['puja'] = 'VIP Puja';
                SendWhatsappMessage::dispatch('vipanushthan', 'Schedule', $messageData);


                $userInfo = \App\Models\User::where('id', $schedule->customer_id)->first();
                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {

                    $service_name = \App\Models\Vippooja::where('id', $schedule->service_id)->where('is_anushthan', 0)->first();
                    $bookingDetails = \App\Models\Service_order::where('service_id', ($schedule->service_id ?? ""))
                        ->where('type', $schedule->type)->where('booking_date', ($schedule->booking_date ?? ""))
                        ->where('customer_id', ($schedule->customer_id ?? ""))->where('order_id', ($schedule->order_id ?? ""))
                        ->first();
                    $data = [
                        'type' => 'pooja',
                        'email' => $userInfo->email,
                        'subject' => $schedule->type . ' Time Scheduled',
                        'htmlContent' =>
                        \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-schedule-template', compact('userInfo', 'service_name', 'bookingDetails'))->render(),
                    ];
                    SendEmailPoojaJob::dispatch($data);
                }

                if ($schedule) {
                    return response()->json(['status' => 200, 'message' => 'Order schedule time updated']);
                }
                return response()->json(['status' => 400, 'message' => 'Unable to changed status']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    public function vip_order_golive(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $golive = Service_order::where('order_id', $request->order_id)->with(['services', 'vippoojas'])->first();
                $golive->live_stream = $request->live_url;
                $golive->live_created_stream = now();
                $golive->order_status = 4;
                $golive->save();

                $messageData = [
                    'live_stream' => $request->live_url,
                    'scheduled_time' => $request->schedule_time ?? 'N/A',
                    'booking_date' => date('d-m-Y', strtotime($golive->booking_date)),
                    'orderId' => $golive->order_id,
                    'customer_id' => $golive->customer_id,
                ];

                $messageData['service_name'] = $golive->vippoojas->name;
                $messageData['puja'] = 'VIP Puja';
                SendWhatsappMessage::dispatch('vipanushthan', 'Live Stream', $messageData);

                // send email
                $userInfo = \App\Models\User::where('id', $golive->customer_id)->first();

                $service_name = \App\Models\Vippooja::where('id', $golive->service_id)->where('is_anushthan', 0)->first();

                $bookingDetails = \App\Models\Service_order::where('service_id', ($golive->service_id ?? ""))
                    ->where('type', $golive->type)
                    ->where('booking_date', ($golive->booking_date ?? ""))
                    ->where('customer_id', ($golive->customer_id ?? ""))
                    ->where('order_id', ($golive->order_id ?? ""))
                    ->first();
                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $data = [
                        'type' => 'pooja',
                        'email' => $userInfo->email,
                        'subject' => $golive->type . ' Live Now',
                        'htmlContent' =>
                        \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-live-template', compact('userInfo', 'service_name', 'bookingDetails'))->render(),
                    ];

                    SendEmailPoojaJob::dispatch($data);
                }

                if ($golive) {
                    return response()->json(['status' => 200, 'message' => 'Order live stream url updated']);
                }
                return response()->json(['status' => 400, 'message' => 'Unable to changed status']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    public function vip_order_complete(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $complete = Service_order::where('order_id', $request->order_id)->with(['services', 'customers', 'payments', 'vippoojas'])->first();
                $certificate = Image::make(public_path('assets/back-end/img/certificate/format/certificate-format.png'));
                $certificate->text(ucwords($complete->customers->f_name . ' ' . $complete->customers->l_name), 950, 630, function ($font) {
                    $font->file(public_path('fonts/Roboto-Bold.ttf'));
                    $font->size(100);
                    $font->color('#ffffff');
                    $font->align('center');
                    $font->valign('top');
                });
                $serviceName = "";
                if ($complete->type == 'vip') {
                    $serviceName = wordwrap($complete->vippoojas->name, 65, "\n", false);
                } else {
                    $serviceName = wordwrap($complete->services->name, 65, "\n", false);
                }
                $certificate->text($serviceName, 500, 815, function ($font) {
                    $font->file(public_path('fonts/Roboto-Black.ttf'));
                    $font->size(40);
                    $font->color('#ffffff');
                    $font->align('left');
                    $font->valign('top');
                });

                $certificate->text(date('d/m/Y', strtotime($complete->created_at)), 830, 994, function ($font) {
                    $font->file(public_path('fonts/Roboto-Black.ttf'));
                    $font->size(40);
                    $font->color('#ffffff');
                    $font->align('center');
                    $font->valign('top');
                });
                $certificatePath = 'assets/back-end/img/certificate/pooja/' . $complete['order_id'] . '.jpg';
                $certificate->save(public_path($certificatePath));

                $complete->pooja_video = $request->live_url;
                $complete->video_created_sharing = now();
                $complete->order_status = 1;
                $complete->status = 1;
                $complete->is_completed = 1;
                $complete->is_edited = 1;
                $complete->pooja_certificate = $certificatePath;
                $complete->order_completed = now();
                $complete->save();

                if ($complete->is_prashad == 1) {
                    Prashad_deliverys::where('order_id', $request->order_id)->update([
                        'pooja_status' => 1,
                        'status' => 1,
                        'order_completed' => now(),
                    ]);
                }

                // whatsapp video share
                $messageData = [
                    'share_video' => $request->live_url,
                    'scheduled_time' => $request->schedule_time ?? 'N/A',
                    'booking_date' => date('d-m-Y', strtotime($complete->booking_date)),
                    'orderId' => $complete->order_id,
                    'customer_id' => $complete->customer_id,
                ];

                $messageData['service_name'] = $complete->vippoojas->name;
                $messageData['puja'] = 'VIP Puja';
                SendWhatsappMessage::dispatch('vipanushthan', 'Shared Video', $messageData);

                // whatsapp order complete
                $messageCompleteData = [
                    'share_video' => $request->live_url,
                    'attachment' => asset('public/' . $certificatePath),
                    'type' => 'text-with-media',
                    'amount' => webCurrencyConverter((float) ($complete->pay_amount ?? 0)),
                    'booking_date' => date('d-m-Y', strtotime($complete->booking_date)),
                    'orderId' => $complete->order_id,
                    'customer_id' => $complete->customer_id,
                ];

                $messageCompleteData['service_name'] = $complete->vippoojas->name;
                $messageCompleteData['puja'] = 'VIP Puja';
                SendWhatsappMessage::dispatch('vipanushthan', 'Completed', $messageCompleteData);

                // send email
                $userInfo = \App\Models\User::where('id', $complete->customer_id)->first();

                $service_name = \App\Models\Vippooja::where('id', $complete->service_id)->where('is_anushthan', 0)->first();

                $bookingDetails = \App\Models\Service_order::where('service_id', ($complete->service_id ?? ""))
                    ->where('type', $complete->type)
                    ->where('booking_date', ($complete->booking_date ?? ""))
                    ->where('customer_id', ($complete->customer_id ?? ""))
                    ->where('order_id', ($complete->order_id ?? ""))
                    ->first();
                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $data = [
                        'type' => 'pooja',
                        'email' => $userInfo->email,
                        'subject' => $complete->type . ' Completed',
                        'htmlContent' =>
                        \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-complete', compact('userInfo', 'service_name', 'bookingDetails'))->render(),
                    ];

                    SendEmailPoojaJob::dispatch($data);
                }

                $commission = 0;
                $tax = ServiceTax::first();
                $astrologer = Astrologer::where('id', $complete['pandit_assign'])->first();
                if ($astrologer) {
                    foreach (json_decode($astrologer['is_pandit_vippooja_commission']) as $key => $value) {
                        if ($key == $complete['services']['id']) {
                            $commission = $value;
                        }
                    }
                }
                $transaction = new ServiceTransaction();
                $transaction->astro_id = $complete['pandit_assign'];
                $transaction->type = $request->type;
                $transaction->order_id = $complete['order_id'];
                if (empty($complete['payments']['transaction_id'])) {
                    $transaction->txn_id = $complete['wallet_translation_id'];
                } else {
                    $transaction->txn_id = $complete['payments']['transaction_id'];
                }

                $transaction->amount = $complete['pay_amount'];
                $transaction->commission = $commission;
                $transaction->tax = $tax['online_pooja'] ?? 0;
                $transaction->save();

                if ($complete) {
                    return response()->json(['status' => 200, 'message' => 'video share and order completed']);
                }
                return response()->json(['status' => 400, 'message' => 'Unable to changed status']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    // ======================= anushthan order details ========================

    public function anushthan_order_detail(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $orderDetail = Service_order::where('order_id', $request->order_id)->with(['services', 'pandit', 'vippoojas'])->first();

                if ($orderDetail) {
                    return response()->json(['status' => 200, 'orderDetail' => $orderDetail]);
                }
                return response()->json(['status' => 400, 'message' => 'order detail not found']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }
    public function anushthan_order_schedule_time(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $schedule = Service_order::where('order_id', $request->order_id)->with(['services', 'vippoojas'])->first();
                $schedule->schedule_time = $request->schedule_time;
                $schedule->schedule_created = now();
                $schedule->order_status = 3;
                $schedule->save();

                $messageData = [
                    'scheduled_time' => $request->schedule_time ?? 'N/A',
                    'booking_date' => date('d-m-Y', strtotime($schedule->booking_date)),
                    'orderId' => $schedule->order_id,
                    'customer_id' => $schedule->customer_id,
                ];

                $messageData['service_name'] = $schedule->vippoojas->name;
                $messageData['puja'] = 'Anushthan';
                SendWhatsappMessage::dispatch('vipanushthan', 'Schedule', $messageData);


                $userInfo = \App\Models\User::where('id', $schedule->customer_id)->first();
                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {

                    $service_name = \App\Models\Vippooja::where('id', $schedule->service_id)->where('is_anushthan', 1)->first();
                    $bookingDetails = \App\Models\Service_order::where('service_id', ($schedule->service_id ?? ""))
                        ->where('type', $schedule->type)->where('booking_date', ($schedule->booking_date ?? ""))
                        ->where('customer_id', ($schedule->customer_id ?? ""))->where('order_id', ($schedule->order_id ?? ""))
                        ->first();
                    $data = [
                        'type' => 'pooja',
                        'email' => $userInfo->email,
                        'subject' => $schedule->type . ' Time Scheduled',
                        'htmlContent' =>
                        \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-schedule-template', compact('userInfo', 'service_name', 'bookingDetails'))->render(),
                    ];
                    SendEmailPoojaJob::dispatch($data);
                }

                if ($schedule) {
                    return response()->json(['status' => 200, 'message' => 'Order schedule time updated']);
                }
                return response()->json(['status' => 400, 'message' => 'Unable to changed status']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }
    public function anushthan_order_golive(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $golive = Service_order::where('order_id', $request->order_id)->with(['services', 'vippoojas'])->first();
                $golive->live_stream = $request->live_url;
                $golive->live_created_stream = now();
                $golive->order_status = 4;
                $golive->save();

                $messageData = [
                    'live_stream' => $request->live_url,
                    'scheduled_time' => $request->schedule_time ?? 'N/A',
                    'booking_date' => date('d-m-Y', strtotime($golive->booking_date)),
                    'orderId' => $golive->order_id,
                    'customer_id' => $golive->customer_id,
                ];

                $messageData['service_name'] = $golive->vippoojas->name;
                $messageData['puja'] = 'Anushthan';
                SendWhatsappMessage::dispatch('vipanushthan', 'Live Stream', $messageData);

                // send email
                $userInfo = \App\Models\User::where('id', $golive->customer_id)->first();

                $service_name = \App\Models\Vippooja::where('id', $golive->service_id)->where('is_anushthan', 1)->first();

                $bookingDetails = \App\Models\Service_order::where('service_id', ($golive->service_id ?? ""))
                    ->where('type', $golive->type)
                    ->where('booking_date', ($golive->booking_date ?? ""))
                    ->where('customer_id', ($golive->customer_id ?? ""))
                    ->where('order_id', ($golive->order_id ?? ""))
                    ->first();
                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $data = [
                        'type' => 'pooja',
                        'email' => $userInfo->email,
                        'subject' => $golive->type . ' Live Now',
                        'htmlContent' =>
                        \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-live-template', compact('userInfo', 'service_name', 'bookingDetails'))->render(),
                    ];

                    SendEmailPoojaJob::dispatch($data);
                }

                if ($golive) {
                    return response()->json(['status' => 200, 'message' => 'Order live stream url updated']);
                }
                return response()->json(['status' => 400, 'message' => 'Unable to changed status']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }
    public function anushthan_order_complete(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $complete = Service_order::where('order_id', $request->order_id)->with(['services', 'customers', 'payments', 'vippoojas'])->first();
                $certificate = Image::make(public_path('assets/back-end/img/certificate/format/certificate-format.png'));
                $certificate->text(ucwords($complete->customers->f_name . ' ' . $complete->customers->l_name), 950, 630, function ($font) {
                    $font->file(public_path('fonts/Roboto-Bold.ttf'));
                    $font->size(100);
                    $font->color('#ffffff');
                    $font->align('center');
                    $font->valign('top');
                });
                $serviceName = "";
                if ($complete->type == 'vip') {
                    $serviceName = wordwrap($complete->vippoojas->name, 65, "\n", false);
                } else {
                    $serviceName = wordwrap($complete->services->name, 65, "\n", false);
                }
                $certificate->text($serviceName, 500, 815, function ($font) {
                    $font->file(public_path('fonts/Roboto-Black.ttf'));
                    $font->size(40);
                    $font->color('#ffffff');
                    $font->align('left');
                    $font->valign('top');
                });

                $certificate->text(date('d/m/Y', strtotime($complete->created_at)), 830, 994, function ($font) {
                    $font->file(public_path('fonts/Roboto-Black.ttf'));
                    $font->size(40);
                    $font->color('#ffffff');
                    $font->align('center');
                    $font->valign('top');
                });
                $certificatePath = 'assets/back-end/img/certificate/pooja/' . $complete['order_id'] . '.jpg';
                $certificate->save(public_path($certificatePath));

                $complete->pooja_video = $request->live_url;
                $complete->video_created_sharing = now();
                $complete->order_status = 1;
                $complete->status = 1;
                $complete->is_completed = 1;
                $complete->is_edited = 1;
                $complete->pooja_certificate = $certificatePath;
                $complete->order_completed = now();
                $complete->save();

                if ($complete->is_prashad == 1) {
                    Prashad_deliverys::where('order_id', $request->order_id)->update([
                        'pooja_status' => 1,
                        'status' => 1,
                        'order_completed' => now(),
                    ]);
                }

                // whatsapp video share
                $messageData = [
                    'share_video' => $request->live_url,
                    'scheduled_time' => $request->schedule_time ?? 'N/A',
                    'booking_date' => date('d-m-Y', strtotime($complete->booking_date)),
                    'orderId' => $complete->order_id,
                    'customer_id' => $complete->customer_id,
                ];

                $messageData['service_name'] = $complete->vippoojas->name;
                $messageData['puja'] = 'Anushthan';
                SendWhatsappMessage::dispatch('vipanushthan', 'Shared Video', $messageData);

                // whatsapp order complete
                $messageCompleteData = [
                    'share_video' => $request->live_url,
                    'attachment' => asset('public/' . $certificatePath),
                    'type' => 'text-with-media',
                    'amount' => webCurrencyConverter((float) ($complete->pay_amount ?? 0)),
                    'booking_date' => date('d-m-Y', strtotime($complete->booking_date)),
                    'orderId' => $complete->order_id,
                    'customer_id' => $complete->customer_id,
                ];

                $messageCompleteData['service_name'] = $complete->vippoojas->name;
                $messageCompleteData['puja'] = 'Anushthan';
                SendWhatsappMessage::dispatch('vipanushthan', 'Completed', $messageCompleteData);

                // send email
                $userInfo = \App\Models\User::where('id', $complete->customer_id)->first();

                $service_name = \App\Models\Vippooja::where('id', $complete->service_id)->where('is_anushthan', 1)->first();

                $bookingDetails = \App\Models\Service_order::where('service_id', ($complete->service_id ?? ""))
                    ->where('type', $complete->type)
                    ->where('booking_date', ($complete->booking_date ?? ""))
                    ->where('customer_id', ($complete->customer_id ?? ""))
                    ->where('order_id', ($complete->order_id ?? ""))
                    ->first();
                if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                    $data = [
                        'type' => 'pooja',
                        'email' => $userInfo->email,
                        'subject' => $complete->type . ' Completed',
                        'htmlContent' =>
                        \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-complete', compact('userInfo', 'service_name', 'bookingDetails'))->render(),
                    ];

                    SendEmailPoojaJob::dispatch($data);
                }

                $commission = 0;
                $tax = ServiceTax::first();
                $astrologer = Astrologer::where('id', $complete['pandit_assign'])->first();
                if ($astrologer) {
                    foreach (json_decode($astrologer['is_pandit_vippooja_commission']) as $key => $value) {
                        if ($key == $complete['services']['id']) {
                            $commission = $value;
                        }
                    }
                }
                $transaction = new ServiceTransaction();
                $transaction->astro_id = $complete['pandit_assign'];
                $transaction->type = $request->type;
                $transaction->order_id = $complete['order_id'];
                if (empty($complete['payments']['transaction_id'])) {
                    $transaction->txn_id = $complete['wallet_translation_id'];
                } else {
                    $transaction->txn_id = $complete['payments']['transaction_id'];
                }

                $transaction->amount = $complete['pay_amount'];
                $transaction->commission = $commission;
                $transaction->tax = $tax['online_pooja'] ?? 0;
                $transaction->save();

                if ($complete) {
                    return response()->json(['status' => 200, 'message' => 'video share and order completed']);
                }
                return response()->json(['status' => 400, 'message' => 'Unable to changed status']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }


    // chadhava orders

    public function chadhava_order_detail(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $orderDetail = Chadhava_orders::where('service_id', $request->service_id)
                    ->where('booking_date', $request->booking_date)
                    ->with(['chadhava', 'pandit'])
                    ->selectRaw('service_id,status,COUNT(*) as total_orders,DATE(created_at) as order_date,booking_date,COUNT(*) as booking_count,GROUP_CONCAT(members SEPARATOR "|") as members,GROUP_CONCAT(gotra SEPARATOR "|") as gotra,order_status,schedule_time,created_at,updated_at')
                    ->groupBy('service_id', 'status', 'booking_date', 'order_status', 'schedule_time', 'created_at', 'updated_at')
                    ->latest('created_at')
                    ->first();

                if ($orderDetail) {
                    return response()->json(['status' => 200, 'orderDetail' => $orderDetail]);
                }
                return response()->json(['status' => 400, 'message' => 'order detail not found']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    public function chadhava_order_schedule_time(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $schedule = Chadhava_orders::where('service_id', $request->service_id)->where('booking_date', $request->booking_date)->with('chadhava')->get();
                foreach ($schedule as $pooja) {
                    $pooja->schedule_time = $request->schedule_time;
                    $pooja->schedule_created = now();
                    $pooja->order_status = 3;
                    $pooja->save();

                    $messageData = [
                        'service_name' => $pooja->chadhava->name,
                        'scheduled_time' => $request->schedule_time ?? 'N/A',
                        'booking_date' => date('d-m-Y', strtotime($pooja->booking_date)),
                        'customer_id' => $pooja->customer_id,
                        'orderId' => $pooja->order_id,
                        'chadhava_venue' => $pooja->chadhava->chadhava_venue,
                    ];

                    SendWhatsappMessage::dispatch('chadhava', 'Schedule', $messageData);

                    //email
                    $userInfo = \App\Models\User::where('id', $pooja->customer_id)->first();
                    if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                        $service_name = \App\Models\Chadhava::where('id', $pooja->service_id)
                            ->first();
                        $bookingDetails = \App\Models\Chadhava_orders::where('service_id', ($pooja->service_id ?? ""))
                            ->where('type', 'chadhava')->where('booking_date', ($pooja->booking_date ?? ""))
                            ->where('customer_id', ($pooja->customer_id ?? ""))->where('order_id', ($pooja->order_id ?? ""))
                            ->first();
                        $data = [
                            'type' => 'chadhava',
                            'email' => $userInfo->email,
                            'subject' => 'Chadhava Time Scheduled',
                            'htmlContent' =>
                            \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-schedule-template', compact('userInfo', 'service_name', 'bookingDetails'))->render(),
                        ];

                        SendEmailPoojaJob::dispatch($data);
                    }
                }

                if ($schedule) {
                    return response()->json(['status' => 200, 'message' => 'Order schedule time updated']);
                }
                return response()->json(['status' => 400, 'message' => 'Unable to changed status']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    public function chadhava_order_golive(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $golive = Chadhava_orders::where('service_id', $request->service_id)->where('booking_date', $request->booking_date)->with('chadhava')->get();
                foreach ($golive as $pooja) {
                    $pooja->live_stream = $request->live_url;
                    $pooja->live_created_stream = now();
                    $pooja->order_status = 4;
                    $pooja->save();

                    $messageData = [
                        'service_name' => $pooja->chadhava->name,
                        'live_stream' => $pooja->live_url ?? 'mahakal.com',
                        'booking_date' => date('d-m-Y', strtotime($pooja->booking_date)),
                        'customer_id' => $pooja->customer_id,
                        'orderId' => $pooja->order_id,
                        'chadhava_venue' => $pooja->chadhava->chadhava_venue ?? 'N/A',
                    ];

                    SendWhatsappMessage::dispatch('chadhava', 'Live Stream', $messageData);
                    // send email

                    $userInfo = \App\Models\User::where('id', $pooja->customer_id)->first();
                    $service_name = \App\Models\Chadhava::where('id', $pooja->service_id)
                        ->first();
                    $bookingDetails = \App\Models\Chadhava_orders::where('service_id', ($pooja->service_id ?? ""))
                        ->where('type', 'chadhava')
                        ->where('booking_date', ($pooja->booking_date ?? ""))
                        ->where('customer_id', ($pooja->customer_id ?? ""))
                        ->where('order_id', ($pooja->order_id ?? ""))
                        ->first();
                    if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                        $data = [
                            'type' => 'chadhava',
                            'email' => $userInfo->email,
                            'subject' => 'Chadhava Live Now',
                            'htmlContent' =>
                            \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-live-template', compact('userInfo', 'service_name', 'bookingDetails'))->render(),
                        ];

                        SendEmailPoojaJob::dispatch($data);
                    }
                }

                if ($golive) {
                    return response()->json(['status' => 200, 'message' => 'Order live stream url updated']);
                }
                return response()->json(['status' => 400, 'message' => 'Unable to changed status']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    public function chadhava_order_complete(Request $request)
    {
       
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $complete = Chadhava_orders::where('service_id', $request->service_id)->where('booking_date', $request->booking_date)->with(['chadhava', 'customers', 'payments'])->get();
              
                foreach ($complete as $pooja) {
                    $certificate = Image::make(public_path('assets/back-end/img/certificate/format/certificate-format.png'));
                    $certificate->text(ucwords($pooja->customers->f_name . ' ' . $pooja->customers->l_name), 950, 630, function ($font) {
                        $font->file(public_path('fonts/Roboto-Bold.ttf'));
                        $font->size(100);
                        $font->color('#ffffff');
                        $font->align('center');
                        $font->valign('top');
                    });

                    $serviceName = wordwrap($pooja->chadhava->name, 65, "\n", false);
                    $certificate->text($serviceName, 500, 815, function ($font) {
                        $font->file(public_path('fonts/Roboto-Black.ttf'));
                        $font->size(40);
                        $font->color('#ffffff');
                        $font->align('left');
                        $font->valign('top');
                    });

                    $certificate->text(date('d/m/Y', strtotime($pooja->created_at)), 830, 994, function ($font) {
                        $font->file(public_path('fonts/Roboto-Black.ttf'));
                        $font->size(40);
                        $font->color('#ffffff');
                        $font->align('center');
                        $font->valign('top');
                    });
                    $certificatePath = 'assets/back-end/img/certificate/pooja/' . $pooja['order_id'] . '.jpg';
                    $certificate->save(public_path($certificatePath));

                    $pooja->pooja_video = $request->live_url;
                    $pooja->video_created_sharing = now();
                    $pooja->order_status = 1;
                    $pooja->status = 1;
                    $pooja->is_completed = 1;
                    $pooja->is_edited = 1;
                    $pooja->pooja_certificate = $certificatePath;
                    $pooja->order_completed = now();
                    $pooja->save();

                    // share video message
                    $messageData = [
                        'service_name' => $pooja->chadhava->name,
                        'share_video' =>  $request->live_url ?? 'mahakal.com',
                        'chadhava_venue' => $pooja->chadhava->chadhava_venue,
                        'booking_date' => date('d-m-Y', strtotime($pooja->booking_date)),
                        'customer_id' => $pooja->customer_id,
                        'orderId' => $pooja->order_id,
                    ];

                    SendWhatsappMessage::dispatch('chadhava', 'Shared Video', $messageData);
                    //email
                    $userInfo = \App\Models\User::where('id', $pooja->customer_id)->first();
                    $service_name = \App\Models\Chadhava::where('id', $pooja->service_id)->first();

                    if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                        $bookingDetails = Chadhava_orders::where('service_id', $pooja->service_id)
                            ->where('type', 'chadhava')
                            ->where('booking_date', $pooja->booking_date)
                            ->where('customer_id', $pooja->customer_id)
                            ->where('order_id', $pooja->order_id)
                            ->first();
                       
                        $data = [
                            'type' => 'chadhava',
                            'email' => $userInfo->email,
                            'subject' => 'Chadhava Video Link Shared',
                            'htmlContent' =>
                            \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-share-template', compact('userInfo', 'service_name', 'bookingDetails'))->render(),
                        ];
                        SendEmailPoojaJob::dispatch($data);
                    }

                    // complete message
                    $messageData = [
                        'service_name' => $pooja->chadhava->name,
                        'share_video' => $request->live_url ?? 'mahakal.com',
                        'customer_id' => $pooja->customer_id,
                        'attachment' => asset('public/' . $certificatePath),
                        'type' => 'text-with-media',
                        'orderId' => $pooja->order_id,
                        'chadhava_venue' => $pooja->chadhava->chadhava_venue,
                        'amount' => webCurrencyConverter((float) ($pooja->pay_amount ?? 0)),
                        'booking_date' => date('d-m-Y', strtotime($pooja->booking_date)),
                    ];
                   
                    SendWhatsappMessage::dispatch('chadhava', 'Completed', $messageData);

                    // send email
                    $userInfo = \App\Models\User::where('id', $pooja->customer_id)->first();
                    if ($userInfo && !empty($userInfo['email']) && filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
                        $service_name = \App\Models\Chadhava::where('id', $pooja->service_id)->first();
                        $bookingDetails = \App\Models\Chadhava_orders::where('service_id', ($pooja->service_id ?? ""))
                            ->where('type', 'chadhava')->where('booking_date', ($pooja->booking_date ?? ""))->where('customer_id', ($pooja->customer_id ?? ""))->where('order_id', ($pooja->order_id ?? ""))->first();

                        $data = [
                            'type' => 'chadhava',
                            'email' => $userInfo->email,
                            'subject' => 'Chadhava Completed.',
                            'htmlContent' => \Illuminate\Support\Facades\View::make('admin-views.email.email-template.pooja-complete', compact('userInfo', 'service_name', 'bookingDetails'))->render(),
                        ];
                        SendEmailPoojaJob::dispatch($data);
                    }
                }

                $commission = 0;
                $tax = ServiceTax::first();
                $astrologer = Astrologer::where('id', $pooja['pandit_assign'])->first();
                if ($astrologer) {
                    if ($request->type == 'chadhava') {
                        foreach (json_decode($astrologer['is_pandit_chadhava_commission']) as $key => $value) {
                            if ($key == $pooja['chadhava']['id']) {
                                $commission = $value;
                            }
                        }
                    }
                }
                $transaction = new ServiceTransaction();
                $transaction->astro_id = $pooja['pandit_assign'];
                $transaction->type = $request->type;
                $transaction->order_id = $pooja['order_id'];
                $transaction->service_id = $pooja->service_id;
                $transaction->booking_date = $pooja->booking_date;
                if (empty($pooja['payments']['transaction_id'])) {
                    $transaction->txn_id = $pooja['wallet_translation_id'];
                } else {
                    $transaction->txn_id = $pooja['payments']['transaction_id'];
                }

                $transaction->amount = $pooja['pay_amount'];
                $transaction->commission = $commission;
                $transaction->tax = $tax['online_pooja'] ?? 0;
                $transaction->save();

                if ($complete) {
                    return response()->json(['status' => 200, 'message' => 'video share and order completed']);
                }
                return response()->json(['status' => 400, 'message' => 'Unable to changed status']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    // public function pooja_order_complete(Request $request)
    // {
    //     if (Auth::guard('influencer_api')->check()) {
    //         $userId = Auth::guard('influencer_api')->user()->id;
    //         if ($userId) {
    //             $orders = Service_order::where('service_id', $request->service_id)->where('booking_date', $request->booking_date)->with(['services', 'customers', 'payments'])->get();

    //             $commission = 0;
    //             $tax = ServiceTax::first();

    //             // dd($orders);
    //             foreach ($orders as $pooja) {
    //                 if ($request->order_status == 1) {
    //                     $certificate = Image::make(public_path('assets/back-end/img/certificate/format/certificate-format.png'));
    //                     $certificate->text(ucwords($pooja->customers->f_name . ' ' . $pooja->customers->l_name), 950, 630, function ($font) {
    //                         $font->file(public_path('fonts/Roboto-Bold.ttf'));
    //                         $font->size(100);
    //                         $font->color('#ffffff');
    //                         $font->align('center');
    //                         $font->valign('top');
    //                     });

    //                     $serviceName = wordwrap($pooja->services->name, 65, "\n", false);
    //                     $certificate->text($serviceName, 500, 815, function ($font) {
    //                         $font->file(public_path('fonts/Roboto-Black.ttf'));
    //                         $font->size(40);
    //                         $font->color('#ffffff');
    //                         $font->align('left');
    //                         $font->valign('top');
    //                     });

    //                     $certificate->text(date('d/m/Y', strtotime($pooja->created_at)), 830, 994, function ($font) {
    //                         $font->file(public_path('fonts/Roboto-Black.ttf'));
    //                         $font->size(40);
    //                         $font->color('#ffffff');
    //                         $font->align('center');
    //                         $font->valign('top');
    //                     });
    //                     $certificatePath = 'assets/back-end/img/certificate/pooja/' . $pooja['order_id'] . '.jpg';
    //                     $certificate->save(public_path($certificatePath));
    //                     // Service_order::where('order_id', $pooja['order_id'])->update(['pooja_certificate' => $pooja['order_id'] . '.jpg']);
    //                     $astrologer = Astrologer::where('id', $pooja['pandit_assign'])->first();
    //                     // dd($astrologer);
    //                     if ($astrologer) {
    //                         foreach (json_decode($astrologer['is_pandit_pooja_commission']) as $key => $value) {
    //                             if ($key == $pooja['services']['id']) {
    //                                 $commission = $value;
    //                             }
    //                         }
    //                     }
    //                     $transaction = new ServiceTransaction();
    //                     $transaction->astro_id = $pooja['pandit_assign'];
    //                     $transaction->type = $pooja['services']['product_type'];
    //                     $transaction->order_id = $pooja['order_id'];
    //                     if (empty($pooja['payments']['transaction_id'])) {
    //                         $transaction->txn_id = $pooja['wallet_translation_id'];
    //                     } else {
    //                         $transaction->txn_id = $pooja['payments']['transaction_id'];
    //                     }

    //                     $transaction->amount = $pooja['pay_amount'];
    //                     $transaction->commission = $commission;
    //                     $transaction->tax = $tax['online_pooja'] ?? 0;
    //                     $transaction->save();

    //                     Service_order::where('order_id', $pooja['order_id'])->update([
    //                         'status' => $request->order_status,
    //                         'is_completed' => $request->order_status,
    //                         'order_status' => $request->order_status,
    //                         'is_edited' => 1,
    //                         'pooja_certificate' => $certificatePath,
    //                         'order_completed' => now(),
    //                     ]);
    //                 } elseif ($request->order_status == 2) {
    //                     $status = Service_order::where('order_id', $pooja['order_id'])->update([
    //                         'order_status' => $request->order_status,
    //                     ]);
    //                 } else {
    //                     $status = Service_order::where('order_id', $pooja['order_id'])->update([
    //                         'order_status' => $request->order_status,
    //                     ]);
    //                 }
    //             }
    //             return response()->json(['status' => 400, 'message' => 'Unable to changed status']);
    //         }
    //         return response()->json(['status' => 400, 'message' => 'User Data not found']);
    //     }
    //     return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    // }

    // public function pooja_order_status_changed(Request $request)
    // {
    //     if (Auth::guard('influencer_api')->check()) {
    //         $userId = Auth::guard('influencer_api')->user()->id;
    //         if ($userId) {
    //             $video = "";
    //             if ($request->type == 'chadhava') {
    //                 $video = Chadhava_orders::where('service_id', $request->service_id)->where('booking_date', $request->booking_date)->get();
    //                 foreach ($video as $pooja) {
    //                     $pooja->status = $request->status;
    //                     $pooja->save();
    //                 }
    //             } elseif ($request->type == 'service') {
    //                 $video = Service_order::where('service_id', $request->service_id)->where('booking_date', $request->booking_date)->get();
    //                 foreach ($video as $pooja) {
    //                     $pooja->status = $request->status;
    //                     $pooja->save();
    //                 }
    //             }
    //             if ($video) {
    //                 return response()->json(['status' => 200, 'message' => 'Status updated']);
    //             }
    //             return response()->json(['status' => 400, 'message' => 'Unable to changed status']);
    //         }
    //         return response()->json(['status' => 400, 'message' => 'User Data not found']);
    //     }
    //     return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    // }

    // ======================== Offline pooja order detail ============================

    public function offlinepooja_order_detail($orderId)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $orderDetail = OfflinePoojaOrder::where('order_id', $orderId)->with('customers')->with('offlinePooja.category')->with('payments')->first();
                if ($orderDetail) {
                    return response()->json(['status' => 200, 'orderDetail' => $orderDetail]);
                }
                return response()->json(['status' => 400, 'message' => 'order detail not found']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    public function offlinepooja_order_status_changed(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $statusChanged = OfflinePoojaOrder::where('order_id', $request->orderId)->update(['status' => $request->status]);
                if ($statusChanged) {
                    return response()->json(['status' => 200, 'message' => 'Order status changed successfully']);
                }
                return response()->json(['status' => 400, 'message' => 'Unable to update order']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }


    // ===================== Wallet =======================

    public function wallet_balance()
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $withdrawal = AstrologerWithdraw::where('astro_id', $userId)
                    ->where('status', 2)
                    ->sum('amount');

                $wallet = ServiceTransaction::where('astro_id', $userId)
                    ->with([
                        'serviceOrder.services',
                        'serviceOrder.vippoojas',
                        'chadhavaOrder.chadhava',
                        'offlinepoojaOrder.offlinePooja'
                    ])
                    ->get();

                $astrologer = Astrologer::where('id', $userId)->first();
                $isPanditAnushthan     = json_decode($astrologer->is_pandit_anushthan, true);
                $isPanditChadhava      = json_decode($astrologer->is_pandit_chadhava, true);
                $isPanditPooja         = json_decode($astrologer->is_pandit_pooja, true);
                $isPanditVipPooja      = json_decode($astrologer->is_pandit_vippooja, true);
                $isPanditOfflinePooja  = json_decode($astrologer->is_pandit_offlinepooja, true);
                $consultationCharges   = json_decode($astrologer->consultation_charge, true);

                $wallet = $wallet->map(function ($trans) use (
                    $isPanditAnushthan,
                    $isPanditChadhava,
                    $isPanditPooja,
                    $isPanditVipPooja,
                    $isPanditOfflinePooja,
                    $consultationCharges
                ) {
                    $trans->astro_amount = 0;

                    switch ($trans->type) {
                        case 'anushthan':
                            $serviceId = Service_order::where('order_id', $trans->order_id)->value('service_id');
                            $trans->astro_amount = $isPanditAnushthan[$serviceId] ?? 0;
                            break;

                        case 'chadhava':
                            $serviceId = Chadhava_orders::where('order_id', $trans->order_id)->value('service_id');
                            $trans->astro_amount = $isPanditChadhava[$serviceId] ?? 0;
                            break;

                        case 'pooja':
                            $serviceId = Service_order::where('order_id', $trans->order_id)->value('service_id');
                            $trans->astro_amount = $isPanditPooja[$serviceId] ?? 0;
                            break;

                        case 'vip':
                            $serviceId = Service_order::where('order_id', $trans->order_id)->value('service_id');
                            $trans->astro_amount = $isPanditVipPooja[$serviceId] ?? 0;
                            break;

                        case 'offlinepooja':
                            $serviceId = OfflinePoojaOrder::where('order_id', $trans->order_id)->value('service_id');
                            $trans->astro_amount = $isPanditOfflinePooja[$serviceId] ?? 0;
                            break;

                        case 'counselling':
                            $serviceId = Service_order::where('order_id', $trans->order_id)->value('service_id');
                            $trans->astro_amount = $consultationCharges[$serviceId] ?? 0;
                            break;
                    }

                    $trans->commission = floor(($trans->amount * $trans->commission) / 100);
                    $trans->tax = floor(($trans->amount * $trans->tax) / 100);

                    return $trans;
                });
                $totalAmount = $wallet->sum('astro_amount') - $withdrawal;
                return response()->json([
                    'status' => 200,
                    'wallet' => $wallet,
                    'total_amount' => $totalAmount
                ]);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    public function wallet_withdraw_request(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $exists = AstrologerWithdraw::where('astro_id', $userId)->whereIn('status', [0, 1])->exists();
                if (!$exists) {
                    $withdrawStore = new AstrologerWithdraw;
                    $withdrawStore->astro_id = $userId;
                    $withdrawStore->amount = $request->amount;
                    if ($withdrawStore->save()) {
                        return response()->json(['status' => 200, 'message' => 'Amount requested successfully']);
                    }
                    return response()->json(['status' => 400, 'message' => 'Unable to store withdraw request']);
                }
                return response()->json(['status' => 201, 'message' => 'Previous request is pending']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }
    public function wallet_withdraw_list()
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                $withdrawList = AstrologerWithdraw::where('astro_id', $userId)->orderBy('created_at', 'desc')->get();
                if ($withdrawList) {
                    return response()->json(['status' => 200, 'list' => $withdrawList]);
                }
                return response()->json(['status' => 400, 'message' => 'Unable to get withdraw request']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }


    // public function transaction()
    // {
    //     if (Auth::guard('influencer_api')->check()) {
    //         $userId = Auth::guard('influencer_api')->user()->id;
    //         if ($userId) {
    //             $transactions = ServiceTransaction::where('astro_id', $userId)->get();
    //             $transactions = $transactions->map(function ($trans) {
    //                 $commission = ($trans->amount * $trans->commission) / 100;
    //                 $tax = ($trans->amount * $trans->tax) / 100;
    //                 $trans->commission = $commission;
    //                 $trans->tax = $tax;
    //                 return $trans;
    //             });

    //             return response()->json(['status' => 200, 'transactions' => $transactions]);
    //         }
    //         return response()->json(['status' => 400, 'message' => 'User Data not found']);
    //     }
    //     return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    // }

    // public function service_review()
    // {
    //     if (Auth::guard('influencer_api')->check()) {
    //         $userId = Auth::guard('influencer_api')->user()->id;
    //         if ($userId) {
    //             $reviews = ServiceReview::select('*', DB::raw('SUM(rating) as total_rate'), DB::raw('COUNT(*) as total_count'))
    //                 ->groupBy('service_id')
    //                 ->where('astro_id', $userId)
    //                 ->with('services')
    //                 ->get();
    //             return response()->json(['status' => 200, 'review' => $reviews]);
    //         }
    //         return response()->json(['status' => 400, 'message' => 'User Data not found']);
    //     }
    //     return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    // }

    // public function user_review($serviceId)
    // {
    //     if (Auth::guard('influencer_api')->check()) {
    //         $userId = Auth::guard('influencer_api')->user()->id;
    //         if ($userId) {
    //             $userReview = ServiceReview::where(['service_id' => $serviceId, 'astro_id' => $userId])->with('users')->get();
    //             return response()->json(['status' => 200, 'user_review' => $userReview]);
    //         }
    //         return response()->json(['status' => 400, 'message' => 'User Data not found']);
    //     }
    //     return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    // }

    public function order_count(Request $request)
    {
        if (Auth::guard('influencer_api')->check()) {
            $userId = Auth::guard('influencer_api')->user()->id;
            if ($userId) {
                // pending
                $counsellingPending = Service_order::where('pandit_assign', $userId)->where('type', 'counselling')->where('status', 0)->whereNull('counselling_report')->get()->count();
                $poojaPending = Service_order::where('pandit_assign', $userId)->where('type', 'pooja')->where('order_status', 0)->where('status', 0)->groupBy('service_id', 'booking_date')->get()->count();
                $vipPending = Service_order::where('pandit_assign', $userId)->where('type', 'vip')->where('order_status', 0)->where('status', 0)->groupBy('service_id', 'booking_date')->get()->count();
                $anushthanPending = Service_order::where('pandit_assign', $userId)->where('type', 'anushthan')->where('order_status', 0)->where('status', 0)->groupBy('service_id', 'booking_date')->get()->count();
                $chadhavaPending = Chadhava_orders::where('pandit_assign', $userId)->where('order_status', 0)->where('status', 0)->groupBy('service_id', 'booking_date')->get()->count();
                $offlinepoojaPending = OfflinePoojaOrder::where('pandit_assign', $userId)->where('status', 0)->get()->count();
                $pendingCount = $counsellingPending + $poojaPending + $vipPending + $anushthanPending + $chadhavaPending + $offlinepoojaPending;

                // inprogress
                $counsellingInprogress = Service_order::where('pandit_assign', $userId)->where('type', 'counselling')->where('status', 0)->whereNotNull('counselling_report')->get()->count();
                $poojaInprogress = Service_order::where('pandit_assign', $userId)->where('type', 'pooja')->whereIn('order_status', [3, 4, 5])->groupBy('service_id', 'booking_date')->get()->count();
                $vipInprogress = Service_order::where('pandit_assign', $userId)->where('type', 'vip')->whereIn('order_status', [3, 4, 5])->groupBy('service_id', 'booking_date')->get()->count();
                $anushthanInprogress = Service_order::where('pandit_assign', $userId)->where('type', 'anushthan')->whereIn('order_status', [3, 4, 5])->groupBy('service_id', 'booking_date')->get()->count();
                $chadhavaInprogress = Chadhava_orders::where('pandit_assign', $userId)->whereIn('order_status', [3, 4, 5])->groupBy('service_id', 'booking_date')->get()->count();
                $inprogressCount = $counsellingInprogress + $poojaInprogress + $vipInprogress + $anushthanInprogress + $chadhavaInprogress;

                // completed
                $counsellingComplete = Service_order::where('pandit_assign', $userId)->where('type', 'counselling')->where('status', 1)->get()->count();
                $poojaComplete = Service_order::where('pandit_assign', $userId)->where('type', 'pooja')->where('status', 1)->where('order_status', 1)->groupBy('service_id', 'booking_date')->get()->count();
                $vipComplete = Service_order::where('pandit_assign', $userId)->where('type', 'vip')->where('status', 1)->where('order_status', 1)->groupBy('service_id', 'booking_date')->get()->count();
                $anushthanComplete = Service_order::where('pandit_assign', $userId)->where('type', 'anushthan')->where('status', 1)->where('order_status', 1)->groupBy('service_id', 'booking_date')->get()->count();
                $chadhavaComplete = Chadhava_orders::where('pandit_assign', $userId)->where('status', 1)->where('order_status', 1)->groupBy('service_id', 'booking_date')->get()->count();
                $offlinepoojaComplete = OfflinePoojaOrder::where('pandit_assign', $userId)->where('status', 1)->get()->count();
                $completeCount = $counsellingComplete + $poojaComplete + $vipComplete + $anushthanComplete + $chadhavaComplete + $offlinepoojaComplete;

                // cancle
                $counsellingCancle = Service_order::where('pandit_assign', $userId)->where('type', 'counselling')->where('status', 2)->get()->count();
                $poojaCancle = Service_order::where('pandit_assign', $userId)->where('type', 'pooja')->where('status', 2)->where('order_status', 2)->groupBy('service_id', 'booking_date')->get()->count();
                $vipCancle = Service_order::where('pandit_assign', $userId)->where('type', 'vip')->where('status', 2)->where('order_status', 2)->groupBy('service_id', 'booking_date')->get()->count();
                $anushthanCancle = Service_order::where('pandit_assign', $userId)->where('type', 'anushthan')->where('status', 2)->where('order_status', 2)->groupBy('service_id', 'booking_date')->get()->count();
                $chadhavaCancle = Chadhava_orders::where('pandit_assign', $userId)->where('status', 2)->where('order_status', 2)->groupBy('service_id', 'booking_date')->get()->count();
                $offlinepoojaCancle = OfflinePoojaOrder::where('pandit_assign', $userId)->where('status', 2)->get()->count();
                $cancleCount = $counsellingCancle + $poojaCancle + $vipCancle + $anushthanCancle + $chadhavaCancle + $offlinepoojaCancle;

                if ($pendingCount + $inprogressCount + $completeCount + $cancleCount) {
                    return response()->json(['status' => 200, 'pending' => $pendingCount, 'inprogress' => $inprogressCount, 'complete' => $completeCount, 'cancle' => $cancleCount]);
                }
                return response()->json(['status' => 400, 'message' => 'Unable to fetch orders count']);
            }
            return response()->json(['status' => 400, 'message' => 'User Data not found']);
        }
        return response()->json(['status' => 400, 'message' => 'Unauthorize user']);
    }

    // ======================= inaugration ========================
    public function inaugration(Request $request)
    {
        if ($request->has('otp')) {
            if ($request->otp == 26022025) {
                $inaugration = BusinessSetting::where('type', 'inaugration')->update(['value' => 1]);
                if ($inaugration) {
                    return response()->json(['status' => true, 'message' => 'Inaugration Completed'], 200);
                }
                return response()->json(['status' => false, 'message' => 'Unable to Inaugrate'], 400);
            }
            return response()->json(['status' => false, 'message' => 'Incorrect OTP'], 400);
        }
        return response()->json(['status' => false, 'message' => 'Enter OTP!'], 400);
    }

    public function KundaliNewOrder(Request $request)
    {
        $getOrders = \App\Models\BirthJournalKundali::with(['userData', 'birthJournal_kundalimilan', 'country', 'country_female'])->whereHas('birthJournal_kundalimilan', function ($query) {
            $query->where('name', 'kundali_milan');
        })->where('assign_pandit', 0)->where('milan_verify', 0)->where('payment_status', 1)->get();
        if ($getOrders) {
            $getData = [];
            $p = 0;
            foreach ($getOrders as $key => $value) {
                $getData[$p]['id'] = $value['id'];
                $getData[$p]['order_id'] = $value['order_id'];
                $getData[$p]['user_name'] = ($value['userData']['f_name'] ?? "") . " " . ($value['userData']['l_name'] ?? "");
                $getData[$p]['user_phone'] = $value['userData']['phone'] ?? "";
                $getData[$p]['user_email'] = $value['userData']['email'] ?? "";
                $getData[$p]['user_image'] = getValidImage(path: 'storage/app/public/profile/' . ($value['userData']['image'] ?? ''), type: 'backend-profile');

                $getData[$p]['kundali_id'] = $value['birthJournal_kundalimilan']['id'] ?? "";
                $getData[$p]['kundali_price'] = $value['birthJournal_kundalimilan']['selling_price'] ?? "";
                $getData[$p]['kundali_pages'] = $value['birthJournal_kundalimilan']['pages'] ?? "";
                $getData[$p]['kundali_type'] = $value['birthJournal_kundalimilan']['type'] ?? "";
                $getData[$p]['kundali_image'] = getValidImage(path: 'storage/app/public/birthjournal/image/' . $value['birthJournal_kundalimilan']['image'] ?? "", type: 'backend-profile');

                $getData[$p]['male_name'] = $value['name'] ?? "";
                $getData[$p]['male_gender'] = $value['gender'] ?? "";
                $getData[$p]['male_dob'] = $value['bod'] ?? "";
                $getData[$p]['male_time'] = $value['time'] ?? "";
                $getData[$p]['male_country'] = $value['country']['name'] ?? "";
                $getData[$p]['male_address'] = $value['state'] ?? "";

                $getData[$p]['female_name'] = $value['female_name'] ?? "";
                $getData[$p]['female_gender'] = $value['female_gender'] ?? "";
                $getData[$p]['female_dob'] = $value['female_dob'] ?? "";
                $getData[$p]['female_time'] = $value['female_time'] ?? "";
                $getData[$p]['female_country'] = $value['country_female']['name'] ?? "";
                $getData[$p]['female_address'] = $value['female_place'] ?? "";

                $getData[$p]['language'] = $value['language'] ?? "";
                $getData[$p]['chart'] = $value['chart_style'] ?? "";
                $getData[$p]['transaction_id'] = $value['transaction_id'] ?? "";
                $p++;
            }
            return response()->json(['status' => 1, 'message' => 'Get Data Successfully', "data" => $getData], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not Found Data', "data" => []], 200);
        }
    }

    public function KundaliOrderAssign(Request $request)
    {
        $rules = [
            "order_id" => [
                "required",
                function ($attribute, $value, $fail) use ($request) {
                    if (!\App\Models\BirthJournalKundali::where('id', $value)->exists()) {
                        $fail('The selected Order Id is invalid.');
                    }
                },
            ],
        ];
        $messages = ["order_id.required" => "Order Id is Empty !"];
        $request->validate($rules, $messages);
        $userId = Auth::guard('influencer_api')->user()->id;
        $getOrders = \App\Models\BirthJournalKundali::where('assign_pandit', 0)->where('milan_verify', 0)->where('payment_status', 1)->where('id', $request->order_id)->first();
        if ($userId && $getOrders) {
            $getOrders->assign_pandit = $userId;
            $getOrders->save();
            return response()->json(['status' => 1, 'message' => 'Assign Order Successfully', "data" => []], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not Found Data', "data" => []], 200);
        }
    }

    public function KundaliOrderDetails(Request $request, $id)
    {
        $userId = Auth::guard('influencer_api')->user()->id;
        $getOrders =  \App\Models\BirthJournalKundali::with(['userData', 'birthJournal_kundalimilan', 'country', 'country_female', 'astrologer'])->whereHas('birthJournal_kundalimilan', function ($query) {
            $query->where('name', 'kundali_milan');
        })->where('assign_pandit', $userId)->where('id', $id)->where('milan_verify', 0)->where('payment_status', 1)->first();

        if ($userId && $getOrders) {
            $getData = [];
            $getData['id'] = $getOrders['id'];
            $getData['order_id'] = $getOrders['order_id'];
            $getData['user_name'] = ($getOrders['userData']['f_name'] ?? "") . " " . ($getOrders['userData']['l_name'] ?? "");
            $getData['user_phone'] = $getOrders['userData']['phone'] ?? "";
            $getData['user_email'] = $getOrders['userData']['email'] ?? "";
            $getData['user_image'] = getValidImage(path: 'storage/app/public/profile/' . ($getOrders['userData']['image'] ?? ''), type: 'backend-profile');

            $getData['kundali_id'] = $getOrders['birthJournal_kundalimilan']['id'] ?? "";
            $getData['kundali_price'] = $getOrders['birthJournal_kundalimilan']['selling_price'] ?? "";
            $getData['kundali_pages'] = $getOrders['birthJournal_kundalimilan']['pages'] ?? "";
            $getData['kundali_type'] = $getOrders['birthJournal_kundalimilan']['type'] ?? "";
            $getData['kundali_image'] = getValidImage(path: 'storage/app/public/birthjournal/image/' . $getOrders['birthJournal_kundalimilan']['image'] ?? "", type: 'backend-profile');

            $getData['male_name'] = $getOrders['name'] ?? "";
            $getData['male_gender'] = $getOrders['gender'] ?? "";
            $getData['male_dob'] = $getOrders['bod'] ?? "";
            $getData['male_time'] = $getOrders['time'] ?? "";
            $getData['male_country'] = $getOrders['country']['name'] ?? "";
            $getData['male_address'] = $getOrders['state'] ?? "";

            $getData['female_name'] = $getOrders['female_name'] ?? "";
            $getData['female_gender'] = $getOrders['female_gender'] ?? "";
            $getData['female_dob'] = $getOrders['female_dob'] ?? "";
            $getData['female_time'] = $getOrders['female_time'] ?? "";
            $getData['female_country'] = $getOrders['country_female']['name'] ?? "";
            $getData['female_address'] = $getOrders['female_place'] ?? "";

            $getData['language'] = $getOrders['language'] ?? "";
            $getData['chart'] = $getOrders['chart_style'] ?? "";
            $getData['transaction_id'] = $getOrders['transaction_id'] ?? "";
            $getData['astro_name'] = ($details['astrologer']['name'] ?? '') . ' (' . ($getOrders['astrologer']['type'] ?? "") . ')';
            $getData['astro_phone'] = ($getOrders['astrologer']['mobile_no'] ?? '');
            $getData['astro_email'] = ($getOrders['astrologer']['email'] ?? "");
            $getData['astro_image'] = getValidImage(path: 'storage/app/public/astrologers/' . ($getOrders['astrologer']['image'] ?? ''), type: 'backend-basic');
            return response()->json(['status' => 1, 'message' => 'Get Data Successfully', "data" => $getData], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not Found Data', "data" => []], 200);
        }
    }
    public function KundaliPendingOrder(Request $request)
    {
        return  $this->KundaliOrderGet($request, 'pending');
    }
    public function KundaliRejectOrder(Request $request)
    {
        return  $this->KundaliOrderGet($request, 'reject');
    }
    public function KundaliApproalOrder(Request $request)
    {
        return  $this->KundaliOrderGet($request, 'approval');
    }

    public function KundaliOrderGet($request, $type)
    {
        $userId = Auth::guard('influencer_api')->user()->id;
        $orderquery = \App\Models\BirthJournalKundali::with(['userData', 'birthJournal_kundalimilan', 'country', 'country_female'])->whereHas('birthJournal_kundalimilan', function ($query) {
            $query->where('name', 'kundali_milan');
        })->where('assign_pandit', $userId);
        if ($type == 'pending') {
            $orderquery->where('milan_verify', 0)->whereIn('reject_status', [0, 1]);
        } else if ($type == 'reject') {
            $orderquery->where('milan_verify', 0)->where('reject_status', 2);
        } else {
            $orderquery->where('milan_verify', 1);
        }

        $getOrders = $orderquery->where('payment_status', 1)->get();
        if ($getOrders && $userId) {
            $getData = [];
            $p = 0;
            foreach ($getOrders as $key => $value) {
                $getData[$p]['id'] = $value['id'];
                $getData[$p]['order_id'] = $value['order_id'];
                $getData[$p]['user_name'] = ($value['userData']['f_name'] ?? "") . " " . ($value['userData']['l_name'] ?? "");
                $getData[$p]['user_phone'] = $value['userData']['phone'] ?? "";
                $getData[$p]['user_email'] = $value['userData']['email'] ?? "";
                $getData[$p]['user_image'] = getValidImage(path: 'storage/app/public/profile/' . ($value['userData']['image'] ?? ''), type: 'backend-profile');

                $getData[$p]['kundali_id'] = $value['birthJournal_kundalimilan']['id'] ?? "";
                $getData[$p]['kundali_price'] = $value['birthJournal_kundalimilan']['selling_price'] ?? "";
                $getData[$p]['kundali_pages'] = $value['birthJournal_kundalimilan']['pages'] ?? "";
                $getData[$p]['kundali_type'] = $value['birthJournal_kundalimilan']['type'] ?? "";
                $getData[$p]['kundali_image'] = getValidImage(path: 'storage/app/public/birthjournal/image/' . $value['birthJournal_kundalimilan']['image'] ?? "", type: 'backend-profile');

                $getData[$p]['male_name'] = $value['name'] ?? "";
                $getData[$p]['male_gender'] = $value['gender'] ?? "";
                $getData[$p]['male_dob'] = $value['bod'] ?? "";
                $getData[$p]['male_time'] = $value['time'] ?? "";
                $getData[$p]['male_country'] = $value['country']['name'] ?? "";
                $getData[$p]['male_address'] = $value['state'] ?? "";

                $getData[$p]['female_name'] = $value['female_name'] ?? "";
                $getData[$p]['female_gender'] = $value['female_gender'] ?? "";
                $getData[$p]['female_dob'] = $value['female_dob'] ?? "";
                $getData[$p]['female_time'] = $value['female_time'] ?? "";
                $getData[$p]['female_country'] = $value['country_female']['name'] ?? "";
                $getData[$p]['female_address'] = $value['female_place'] ?? "";

                $getData[$p]['language'] = $value['language'] ?? "";
                $getData[$p]['chart'] = $value['chart_style'] ?? "";
                $getData[$p]['transaction_id'] = $value['transaction_id'] ?? "";
                $getData[$p]['reject_status'] = $value['reject_status'] ?? "";
                if ($value['kundali_pdf']) {
                    $getData[$p]['kundali_pdf'] = getValidImage(path: 'storage/app/public/birthjournal/kundali_milan/' . ($value['kundali_pdf'] ?? ""), type: 'backend-product');
                } else {
                    $getData[$p]['kundali_pdf'] = '';
                }
                if ($type == 'reject') {
                    $getData[$p]['reject_message'] = $value['reject_message'] ?? "";
                }
                $p++;
            }
            return response()->json(['status' => 1, 'message' => 'Get Data Successfully', "data" => $getData], 200);
        } else {
            return response()->json(['status' => 0, 'message' => 'Not Found Data', "data" => []], 200);
        }
    }

    public function KundalipdfUpload(Request $request)
    {
        $rules = [
            "order_id" => [
                "required",
                function ($attribute, $value, $fail) use ($request) {
                    if (!\App\Models\BirthJournalKundali::where('id', $value)->exists()) {
                        $fail('The selected Order Id is invalid.');
                    }
                },
            ],
            "kundali_pdf" => "required|mimes:pdf,doc,docx|max:5120",
        ];
        $messages = ["order_id.required" => "Order Id is Empty !", 'kundali_pdf' => "upload only pdf and doc format"];
        $request->validate($rules, $messages);
        $userId = Auth::guard('influencer_api')->user()->id;
        $getOrders = \App\Models\BirthJournalKundali::where('assign_pandit', $userId)->where('milan_verify', 0)->where('payment_status', 1)->where('id', $request->order_id)->with(['birthJournal_kundalimilan'])->first();
        if ($userId && $getOrders) {
            if ($request->file('kundali_pdf')) {
                $relativePath = 'birthjournal/kundali_milan/' . $getOrders['kundali_pdf'];
                if (Storage::disk('public')->exists($relativePath)) {
                    Storage::disk('public')->delete($relativePath);
                }
                $destinationPath = storage_path('app/public/birthjournal/kundali_milan');
                $fileName = $getOrders['birthJournal_kundalimilan']['pages'] . '_page_' . $getOrders['language'] . "_milan" . time() . '.' . $request->file('kundali_pdf')->getClientOriginalExtension();
                $request->file('kundali_pdf')->move($destinationPath, $fileName);
                $getOrders->kundali_pdf = $fileName;
                $getOrders->reject_status = 1;
                $getOrders->save();
                return response()->json(['status' => 1, 'message' => 'Pdf Upload Successfully', "data" => []], 200);
            } else {
                return response()->json(['status' => 0, 'message' => 'sum Issue', "data" => []], 200);
            }
        } else {
            return response()->json(['status' => 0, 'message' => 'Not Found Data', "data" => []], 200);
        }
    }
}