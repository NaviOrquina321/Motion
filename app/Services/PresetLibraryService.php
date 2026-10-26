<?php

class PresetLibraryService {
    public static function getPresets(): array {
        return [
            'liquid_glass' => [
                'name' => 'Liquid Glass',
                'description' => 'A refractive lens effect sliding over code & screens.',
                'badge' => 'Most Popular',
                'preview_bg' => 'linear-gradient(135deg, #0F172A 0%, #1E293B 100%)'
            ],
            '3d_ring' => [
                'name' => '3D Code Ring',
                'description' => 'Project cards and dependencies travel around a 3D ring with depth.',
                'badge' => '3D Motion',
                'preview_bg' => 'linear-gradient(135deg, #06B6D4 0%, #3B82F6 100%)'
            ],
            'kinetic_type' => [
                'name' => 'Kinetic Type',
                'description' => 'Dynamic word-drum animations synced to code execution.',
                'badge' => 'Typography',
                'preview_bg' => 'linear-gradient(135deg, #8B5CF6 0%, #EC4899 100%)'
            ],
            'product_launch' => [
                'name' => 'Product Launch',
                'description' => 'High-impact launch showcase inspired by Framer & Lovable 2.0.',
                'badge' => 'Featured',
                'preview_bg' => 'linear-gradient(135deg, #10B981 0%, #3B82F6 100%)'
            ],
            'glitch_cyber' => [
                'name' => 'Cyber Glitch',
                'description' => 'Futuristic code distortion and particle light sweep.',
                'badge' => 'Cyberpunk',
                'preview_bg' => 'linear-gradient(135deg, #EF4444 0%, #F59E0B 100%)'
            ]
        ];
    }
}
