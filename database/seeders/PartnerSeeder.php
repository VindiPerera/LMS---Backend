<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo language-exchange partners for the Connect tab, so
 * GET /api/partners has real data to show instead of an empty list.
 * Mirrors the shape (and a few of the profiles) previously hardcoded in
 * hello-frontend/lib/data/mock_data.dart's `mockUsers`.
 */
class PartnerSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $partners = [
            [
                'name' => 'Kevin',
                'handle' => 'kevin_jp',
                'country_flag' => '🇯🇵',
                'native_lang' => 'Japanese',
                'learning_lang' => 'English',
                'is_online' => true,
                'age' => 27,
                'gender' => 'male',
                'bio' => "Hello! I'm Kevin from Japan. I love hiking and photography 📷",
                'active_label' => 'Active now',
                'tags' => ['Free to Chat', 'Very active', 'Photography'],
            ],
            [
                'name' => 'Andrew Ferdinandus',
                'handle' => 'andrew_f',
                'country_flag' => '🇦🇺',
                'native_lang' => 'English',
                'learning_lang' => 'Sinhala',
                'is_online' => false,
                'age' => 31,
                'gender' => 'male',
                'bio' => "G'day! Learning Sinhala for a trip to Sri Lanka next year.",
                'active_label' => 'Active 2 hours ago',
                'tags' => ['Similar age range', 'Travel'],
            ],
            [
                'name' => 'Zet',
                'handle' => 'zetify',
                'country_flag' => '🇮🇩',
                'native_lang' => 'Indonesian',
                'learning_lang' => 'English',
                'is_online' => true,
                'is_vip' => true,
                'age' => 25,
                'gender' => 'male',
                'bio' => 'Music producer 🎧 always down to talk about R&B and beats.',
                'active_label' => 'Active now',
                'tags' => ['You both like Music', 'Very Responsive'],
            ],
            [
                'name' => 'Zuhri',
                'handle' => 'zuhri_m',
                'country_flag' => '🇮🇩',
                'native_lang' => 'Indonesian',
                'learning_lang' => 'English',
                'is_online' => true,
                'age' => 22,
                'gender' => 'female',
                'bio' => "Wants to visit: New York 🗽 Let's practice speaking!",
                'active_label' => 'Active now',
                'tags' => ['Free to Chat', 'New'],
            ],
            [
                'name' => 'Maria Santos',
                'handle' => 'maria_es',
                'country_flag' => '🇪🇸',
                'native_lang' => 'Spanish',
                'learning_lang' => 'English',
                'is_online' => false,
                'age' => 29,
                'gender' => 'female',
                'bio' => "¡Hola! I'm Maria from Spain, learning English every day 🇪🇸",
                'active_label' => 'Active 1 hour ago',
                'tags' => ['Passionate about speaking practice', 'Similar age range'],
            ],
            [
                'name' => 'Wei Chen',
                'handle' => 'wei_cn',
                'country_flag' => '🇨🇳',
                'native_lang' => 'Chinese',
                'learning_lang' => 'Sinhala',
                'is_online' => true,
                'age' => 26,
                'gender' => 'male',
                'bio' => 'Software engineer, love badminton 🏸 and language exchange.',
                'active_label' => 'Active now',
                'tags' => ['You both like Badminton', 'Very active'],
            ],
            [
                'name' => 'Sajida',
                'handle' => 'sajida_teach',
                'country_flag' => '🇬🇧',
                'native_lang' => 'English',
                'learning_lang' => 'French',
                'is_online' => true,
                'age' => 34,
                'gender' => 'female',
                'bio' => "I'm Sajida from England. I'm a graduate in Biomedical Science who loves teaching English conversation.",
                'active_label' => 'Active now',
                'tags' => ['Teacher', 'Very Responsive'],
                'role' => 'teacher',
                'detail' => 'English Conversation',
            ],
        ];

        foreach ($partners as $partner) {
            User::updateOrCreate(
                ['handle' => $partner['handle']],
                array_merge([
                    'email' => $partner['handle'].'@facetalk.demo',
                    'password' => Hash::make(Str::random(40)),
                    'role' => 'student',
                    'profile_completed' => true,
                    'is_vip' => false,
                ], $partner)
            );
        }
    }
}
