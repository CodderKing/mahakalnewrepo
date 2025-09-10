<?php

namespace App\Services;

use Illuminate\Support\Str;

use App\Traits\FileManagerTrait;
use Illuminate\Support\Facades\Storage;

class DonateTrustAdsService
{
    use FileManagerTrait;
    public function getAddData(object $request): array
    {
        $imageNames = '';
        if ($request->file('image')) {
            $imageNames = $this->upload(dir: 'donate/ads/', format: 'webp', image: $request->file('image'));
        }

        return [
            'name' => $request['name'][array_search('en', $request['lang'])],
            'slug' => Str::slug($request['name'][array_search('en', $request['lang'])], '-') . '-' . Str::random(6),
            'category_id' => (($request['type'] == 'outsite') ? $request['category_id'] : 0),
            'trust_id' => (($request['type'] == 'outsite') ? $request['trust_id'] : 0),
            'purpose_id' => $request['purpose_id'],
            'set_type' => $request['set_type'],
            'type' => $request['type'],
            'set_amount' => (($request['set_type'] == 1) ? $request['set_amount'] : 0),
            'set_number' => (($request['set_type'] == 1) ? $request['set_number'] : 0),
            'set_unit' => (($request['set_type'] == 1) ? $request['set_unit'] : ''),
            'set_title' => (($request['set_type'] == 1) ? $request['set_title'] : ''),
            'description' => $request['description'][array_search('en', $request['lang'])],
            'image' => $imageNames,
            'admin_commission' => $request['admin_commission'] ?? 0,
            'status' => (($request['type'] == 'outsite') ? 1 : 0),
            'is_approve' => (($request['type'] == 'outsite') ? 0 : 1),
        ];
    }

    public function getUpdateData(object $request, object $old_data): array
    {
        $dataArray = [
            'name' => $request['name'][array_search('en', $request['lang'])],
            'category_id' => (($request['type'] == 'outsite') ? $request['category_id'] : 0),
            'trust_id' => (($request['type'] == 'outsite') ? $request['trust_id'] : 0),
            'purpose_id' => $request['purpose_id'],
            'type' => $request['type'],
            'set_type' => $request['set_type'],
            'set_amount' => (($request['set_type'] == 1) ? $request['set_amount'] : 0),
            'set_number' => (($request['set_type'] == 1) ? $request['set_number'] : 0),
            'set_unit' => (($request['set_type'] == 1) ? $request['set_unit'] : ''),
            'set_title' => (($request['set_type'] == 1) ? $request['set_title'] : ''),
            'description' => $request['description'][array_search('en', $request['lang'])],
            'admin_commission' => $request['admin_commission'] ?? 0,
        ];
        if (empty($old_data['slug'])) {
            $dataArray['slug'] = Str::slug($request['name'][array_search('en', $request['lang'])], '-') . '-' . Str::random(6);
        }
        if ($request->file('image')) {
            $dataArray['image'] = $this->upload(dir: 'donate/ads/', format: 'webp', image: $request->file('image'));
            $this->delete(filePath: 'donate/ads/' . $old_data['image']);
        }
        return  $dataArray;
    }

    public function deleteAdsImage($old_data): bool
    {
        $this->delete(filePath: 'donate/ads/' . $old_data['image']);
        return true;
    }

    public function ReCorrectTrustData($request, $org_old, $vendor)
    {
        $dataArray = [];
        if ($vendor['all_doc_info'] && json_decode($vendor['all_doc_info'], true)) {
            $check_validate = json_decode($vendor['all_doc_info'], true);
            $getUniqueArray = [
                'name',
                'trust_name',
                "trust_category",
                'trust_email',
                'full_address',
                'description',
                "members",
                "website_link",
                'user_image',
                "gallery_image",
                'pan_card',
                'pan_card_image',
                'trust_pan_card',
                'trust_pan_card_image',
                'twelve_a_certificate',
                'eighty_g_certificate',
                'niti_aayog_certificate',
                'csr_certificate',
                'e_anudhan_certificate',
                'frc_certificate',
                'bank_name',
                'beneficiary_name',
                'ifsc_code',
                'account_type',
                'account_no',
                'cancelled_cheque_image',
                'twelve_a_number',
                'eighty_g_number',
                'niti_aayog_number',
                'csr_number',
                'e_anudhan_number',
                'frc_number'
            ];
            $dataArray['vendor']  = [];
            $dataArray['trust']  = [];
            foreach ($getUniqueArray as $value) {
                if ($check_validate[$value] == 2) {
                    $dataArray['vendor']['reupload_doc_status'] = 3;
                    break;
                }
            }
            if (!empty($org_old['slug'])) {
                $dataArray['trust']['slug'] = Str::slug($request['trust_name'], '-') . '-' . Str::random(6);
            }
            if ($check_validate['name'] == 2 || $check_validate['name'] == 0) {
                $check_validate['name'] = 0;
                $dataArray['trust']['name'] = $request['name'];
                $dataArray['vendor']['f_name'] = explode(" ", $request['name'])[0] ?? '';
                $dataArray['vendor']['l_name'] = explode(" ", $request['name'])[1] ?? '';
            }
            if ($check_validate['trust_name'] == 2 || $check_validate['trust_name'] == 0) {
                $check_validate['trust_name'] = 0;
                $dataArray['trust']['trust_name'] = $request['trust_name'];
            }
            if ($check_validate['trust_email'] == 2 || $check_validate['trust_email'] == 0) {
                $check_validate['trust_email'] = 0;
                $dataArray['trust']['trust_email'] = $request['trust_email'];
                // $dataArray['vendor']['email'] = $request['email_address'];
            }
            if ($check_validate['members'] == 2 || $check_validate['members'] == 0) {
                $check_validate['members'] = 0;
                $memberList = [];
                if (isset($request['member_name'][0]) && isset($request['member_phone_no'][0]) && isset($request['member_position'][0])) {
                    foreach ($request['member_name'] as $key => $val) {
                        $memberList[$key]['member_name'] = $val;
                        $memberList[$key]['member_phone_no'] = $request['member_phone_no'][$key];
                        $memberList[$key]['member_position'] = $request['member_position'][$key];
                    }
                }
                $dataArray['trust']['memberlist'] = json_encode($memberList);
            }
            if ($check_validate['trust_category'] == 2 || $check_validate['trust_category'] == 0) {
                $check_validate['trust_category'] = 0;
                $dataArray['trust']['category_id'] = $request['trust_category'];
            }
            if ($check_validate['full_address'] == 2 || $check_validate['full_address'] == 0) {
                $check_validate['full_address'] = 0;
                $dataArray['trust']['full_address'] = $request['full_address'];
            }
            if ($check_validate['description'] == 2 || $check_validate['description'] == 0) {
                $check_validate['description'] = 0;
                $dataArray['trust']['description'] = $request['description'];
            }

            if ($check_validate['website_link'] == 2 || $check_validate['website_link'] == 0) {
                $check_validate['website_link'] = 0;
                $dataArray['trust']['website'] = $request['website_link'];
            }
            if ($check_validate['user_image'] == 2 || $check_validate['user_image'] == 0) {
                $check_validate['user_image'] = 0;
                $dataArray['trust']['theme_image'] = $request['image'] ? $this->update(dir: 'donate/trust/', oldImage: $vendor['image'], format: 'webp', image: $request->file('image')) : $vendor['image'];
                $dataArray['vendor']['image'] = $dataArray['trust']['theme_image'];
            }

            if ($check_validate['gallery_image'] == 2 || $check_validate['gallery_image'] == 0) {
                $check_validate['gallery_image'] = 0;
                $imageNames = [];
                if ($request->file('images')) {
                    foreach ($request->file('images') as $image) {
                        $images = $this->upload(dir: 'donate/trust/', format: 'webp', image: $image);
                        $imageNames[] = $images;
                    }
                }
                if ($org_old['gallery_image'] && json_decode($org_old['gallery_image'], true)) {
                    $imageNames = array_merge(json_decode($org_old['gallery_image'], true), $imageNames);
                }
                $dataArray['trust']['gallery_image'] = json_encode($imageNames);
            }

            if ($check_validate['pan_card'] == 2 || $check_validate['pan_card'] == 0) {
                $check_validate['pan_card'] = 0;
                $dataArray['trust']['pan_card'] = $request['pan_number'];
                $dataArray['vendor']['pan_number'] = $request['pan_number'];
            }
            if ($check_validate['pan_card_image'] == 2 || $check_validate['pan_card_image'] == 0) {
                $check_validate['pan_card_image'] = 0;
                $dataArray['trust']['pan_card_image'] = $request['pan_card_image'] ? $this->update(dir: 'donate/document/', oldImage: $org_old['pan_card_image'], format: $request->file('pan_card_image')->getClientOriginalExtension(), image: $request->file('pan_card_image'), fileType: "file") : $org_old['pan_card_image'];
                $dataArray['vendor']['pancard_image'] = $dataArray['trust']['pan_card_image'];
            }

            if ($check_validate['trust_pan_card'] == 2 || $check_validate['trust_pan_card'] == 0) {
                $check_validate['trust_pan_card'] = 0;
                $dataArray['trust']['trust_pan_card'] = $request['trust_pan_card'];
            }
            if ($check_validate['trust_pan_card_image'] == 2 || $check_validate['trust_pan_card_image'] == 0) {
                $check_validate['trust_pan_card_image'] = 0;
                $dataArray['trust']['trust_pan_card_image'] = $request['trust_pan_card_image'] ? $this->update(dir: 'donate/document/', oldImage: $org_old['trust_pan_card_image'], format: $request->file('trust_pan_card_image')->getClientOriginalExtension(), image: $request->file('trust_pan_card_image'), fileType: "file") : $org_old['trust_pan_card_image'];
            }

            if ($check_validate['twelve_a_number'] == 2 || $check_validate['twelve_a_number'] == 0) {
                $check_validate['twelve_a_number'] = 0;
                $dataArray['trust']['twelve_a_number'] = $request['twelve_a_number'];
            }
            if ($check_validate['eighty_g_number'] == 2 || $check_validate['eighty_g_number'] == 0) {
                $check_validate['eighty_g_number'] = 0;
                $dataArray['trust']['eighty_g_number'] = $request['eighty_g_number'];
            }
            if ($check_validate['niti_aayog_number'] == 2 || $check_validate['niti_aayog_number'] == 0) {
                $check_validate['niti_aayog_number'] = 0;
                $dataArray['trust']['niti_aayog_number'] = $request['niti_aayog_number'];
            }
            if ($check_validate['csr_number'] == 2 || $check_validate['csr_number'] == 0) {
                $check_validate['csr_number'] = 0;
                $dataArray['trust']['csr_number'] = $request['csr_number'];
            }
            if ($check_validate['e_anudhan_number'] == 2 || $check_validate['e_anudhan_number'] == 0) {
                $check_validate['e_anudhan_number'] = 0;
                $dataArray['trust']['e_anudhan_number'] = $request['e_anudhan_number'];
            }
            if ($check_validate['frc_number'] == 2 || $check_validate['frc_number'] == 0) {
                $check_validate['frc_number'] = 0;
                $dataArray['trust']['frc_number'] = $request['frc_number'];
            }
            if ($check_validate['twelve_a_certificate'] == 2 || $check_validate['twelve_a_certificate'] == 0) {
                $check_validate['twelve_a_certificate'] = 0;
                $dataArray['trust']['twelve_a_certificate'] = $request['twelve_a_certificate'] ? $this->update(dir: 'donate/document/', oldImage: $org_old['twelve_a_certificate'], format: $request->file('twelve_a_certificate')->getClientOriginalExtension(), image: $request->file('twelve_a_certificate'), fileType: "file") : $org_old['twelve_a_certificate'];
            }
            if ($check_validate['eighty_g_certificate'] == 2 || $check_validate['eighty_g_certificate'] == 0) {
                $check_validate['eighty_g_certificate'] = 0;
                $dataArray['trust']['eighty_g_certificate'] = $request['eighty_g_certificate'] ? $this->update(dir: 'donate/document/', oldImage: $org_old['eighty_g_certificate'], format: $request->file('eighty_g_certificate')->getClientOriginalExtension(), image: $request->file('eighty_g_certificate'), fileType: "file") : $org_old['eighty_g_certificate'];
            }
            if ($check_validate['niti_aayog_certificate'] == 2 || $check_validate['niti_aayog_certificate'] == 0) {
                $check_validate['niti_aayog_certificate'] = 0;
                $dataArray['trust']['niti_aayog_certificate'] = $request['niti_aayog_certificate'] ? $this->update(dir: 'donate/document/', oldImage: $org_old['niti_aayog_certificate'], format: $request->file('niti_aayog_certificate')->getClientOriginalExtension(), image: $request->file('niti_aayog_certificate'), fileType: "file") : $org_old['niti_aayog_certificate'];
            }
            if ($check_validate['csr_certificate'] == 2 || $check_validate['csr_certificate'] == 0) {
                $check_validate['csr_certificate'] = 0;
                $dataArray['trust']['csr_certificate'] = $request['csr_certificate'] ? $this->update(dir: 'donate/document/', oldImage: $org_old['csr_certificate'], format: $request->file('csr_certificate')->getClientOriginalExtension(), image: $request->file('csr_certificate'), fileType: "file") : $org_old['csr_certificate'];
            }
            if ($check_validate['e_anudhan_certificate'] == 2 || $check_validate['e_anudhan_certificate'] == 0) {
                $check_validate['e_anudhan_certificate'] = 0;
                $dataArray['trust']['e_anudhan_certificate'] = $request['e_anudhan_certificate'] ? $this->update(dir: 'donate/document/', oldImage: $org_old['e_anudhan_certificate'], format: $request->file('e_anudhan_certificate')->getClientOriginalExtension(), image: $request->file('e_anudhan_certificate'), fileType: "file") : $org_old['e_anudhan_certificate'];
            }
            if ($check_validate['frc_certificate'] == 2 || $check_validate['frc_certificate'] == 0) {
                $check_validate['frc_certificate'] = 0;
                $dataArray['trust']['frc_certificate'] = $request['frc_certificate'] ? $this->update(dir: 'donate/document/', oldImage: $org_old['frc_certificate'], format: $request->file('frc_certificate')->getClientOriginalExtension(), image: $request->file('frc_certificate'), fileType: "file") : $org_old['frc_certificate'];
            }

            if ($check_validate['bank_name'] == 2 || $check_validate['bank_name'] == 0) {
                $check_validate['bank_name'] = 0;
                $dataArray['trust']['bank_name'] = $request['bank_name'];
                $dataArray['vendor']['bank_name'] = $request['bank_name'];
            }

            if ($check_validate['beneficiary_name'] == 2 || $check_validate['beneficiary_name'] == 0) {
                $check_validate['beneficiary_name'] = 0;
                $dataArray['trust']['beneficiary_name'] = $request['holder_name'];
                $dataArray['vendor']['holder_name'] = $request['holder_name'];
            }
            if ($check_validate['ifsc_code'] == 2 || $check_validate['ifsc_code'] == 0) {
                $check_validate['ifsc_code'] = 0;
                $dataArray['trust']['ifsc_code'] = $request['ifsc'];
                $dataArray['vendor']['ifsc'] = $request['ifsc'];
            }
            if ($check_validate['account_type'] == 2 || $check_validate['account_type'] == 0) {
                $check_validate['account_type'] = 0;
                $dataArray['trust']['account_type'] = $request['account_type'];
            }
            if ($check_validate['account_no'] == 2 || $check_validate['account_no'] == 0) {
                $check_validate['account_no'] = 0;
                $dataArray['trust']['account_no'] = $request['account_no'];
                $dataArray['vendor']['account_no'] = $request['account_no'];
            }
            $dataArray['vendor']['update_seller_status'] = 0;
            if ($check_validate['cancelled_cheque_image'] == 2 || $check_validate['cancelled_cheque_image'] == 0) {
                $check_validate['cancelled_cheque_image'] = 0;
                $dataArray['trust']['cancelled_cheque_image'] =  $request['cancelled_cheque_image'] ? $this->update(dir: 'donate/document/', oldImage: $vendor['cancel_check'], format: 'webp', image: $request->file('cancelled_cheque_image')) : $vendor['cancel_check'];
                $dataArray['vendor']['cancel_check'] =  $dataArray['trust']['cancelled_cheque_image'];
            }
            $dataArray['vendor']['all_doc_info'] = json_encode($check_validate);
        }
        return $dataArray;
    }
}
