<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/Project.php';

class VideoGeneratorService {
    public static function generateScript(Project $project, string $preset = 'liquid_glass'): array {
        $name = $project->project_name;
        $type = $project->project_type;
        $desc = $project->description ?: "A high-performance {$type} software project.";

        return [
            'project_id' => $project->id,
            'title' => $name,
            'preset' => $preset,
            'aspect_ratio' => '16:9',
            'duration_seconds' => 10,
            'scenes' => [
                [
                    'id' => 1,
                    'title' => 'Project Entrance',
                    'heading' => $name,
                    'subheading' => 'Software Project Repository',
                    'effect' => 'Kinetic Type & Edge Sheen',
                    'start' => 0,
                    'end' => 3
                ],
                [
                    'id' => 2,
                    'title' => 'Tech Stack & Integrity',
                    'heading' => "Powered by " . $type,
                    'subheading' => "SHA-256 Checksum Verified & Dependency Analyzed",
                    'effect' => '3D Code Ring & Liquid Glass Lens',
                    'start' => 3,
                    'end' => 7
                ],
                [
                    'id' => 3,
                    'title' => 'Restoration Call to Action',
                    'heading' => 'Transfer Anywhere. Restore Instantly.',
                    'subheading' => 'Setup-Assisted Package Ready in CloudVault',
                    'effect' => 'Light Sweep & Particle Morph',
                    'start' => 7,
                    'end' => 10
                ]
            ]
        ];
    }
}
