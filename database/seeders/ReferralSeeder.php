<?php

namespace Database\Seeders;

use App\Models\Referral;
use Illuminate\Database\Seeder;

class ReferralSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $referrals = [
            [
                'referrer_phone_number' => '+2348112010160',
                'referrer_user_id' => '6077078276350f339735990.55109245',
                'referree_phone_number' => '+4917629930165',
                'referree_user_id' => '14832474056439eea22542e4.18532803'
            ],
            [
                'referrer_phone_number' => '+2348112010160',
                'referrer_user_id' => '6077078276350f339735990.55109245',
                'referree_phone_number' => '+2347062010354',
                'referree_user_id' => '847238205643c91849b8ad8.81210956',
                'rewarded' => true,
                'reward_usd' => 5
            ],
            [
                'referrer_phone_number' => '+2348112010160',
                'referrer_user_id' => '6077078276350f339735990.55109245',
                'referree_phone_number' => '+2347062010352',
                'referree_user_id' => '688610769643c948b73a3a2.52877842'
            ],
            [
                'referrer_phone_number' => '+2348112010160',
                'referrer_user_id' => '6077078276350f339735990.55109245',
                'referree_phone_number' => '+2348032650221',
                'referree_user_id' => '123193697463df44f5b0b318.40523950',
                'rewarded' => true,
                'reward_usd' => 5
            ],
            [
                'referrer_phone_number' => '+2348112010160',
                'referrer_user_id' => '6077078276350f339735990.55109245',
                'referree_phone_number' => '+2348034859277',
                'referree_user_id' => '105681264063df48f6400048.94844068'
            ],
            [
                'referrer_phone_number' => '+2348112010160',
                'referrer_user_id' => '6077078276350f339735990.55109245',
                'referree_phone_number' => '+2348033039747',
                'referree_user_id' => '16821968906353c5939cfd13.92264459',
                'rewarded' => true,
                'reward_usd' => 5
            ],
            [
                'referrer_phone_number' => '+2348112010160',
                'referrer_user_id' => '6077078276350f339735990.55109245',
                'referree_phone_number' => '+15146996361',
                'referree_user_id' => '89064504363877deb8c8207.60268610',
                'rewarded' => true,
                'reward_usd' => 5
            ],
            [
                'referrer_phone_number' => '+2348112010160',
                'referrer_user_id' => '6077078276350f339735990.55109245',
                'referree_phone_number' => '+14384981566',
                'referree_user_id' => '754931593649f25a41a7e26.72819651',
                'rewarded' => true,
                'reward_usd' => 5
            ]
        ];

        foreach ($referrals as $referral) {
            Referral::create($referral);
        }
    }
}
