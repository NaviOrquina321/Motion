<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/Project.php';
require_once __DIR__ . '/../Models/ProjectVersion.php';
require_once __DIR__ . '/SetupGuideGenerator.php';

class McpService {
    public static function handleRequest(array $request): array {
        $id = $request['id'] ?? null;
        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];

        switch ($method) {
            case 'initialize':
                return self::response($id, [
                    'protocolVersion' => '2024-11-05',
                    'capabilities' => [
                        'tools' => [
                            'listChanged' => true
                        ],
                        'resources' => [
                            'subscribe' => false,
                            'listChanged' => true
                        ]
                    ],
                    'serverInfo' => [
                        'name' => 'CloudVault AI Motion & Backup Agent',
                        'version' => '1.0.0'
                    ]
                ]);

            case 'tools/list':
                return self::response($id, [
                    'tools' => [
                        [
                            'name' => 'list_projects',
                            'description' => 'Lists all backed-up programming projects in CloudVault with stack, size, and health status.',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'user_id' => ['type' => 'integer', 'description' => 'Optional user ID filter']
                                ]
                            ]
                        ],
                        [
                            'name' => 'get_project_requirements',
                            'description' => 'Retrieves analyzed requirements, language stack, dependencies, and database backup details for a specific project.',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'project_id' => ['type' => 'integer', 'description' => 'ID of the project']
                                ],
                                'required' => ['project_id']
                            ]
                        ],
                        [
                            'name' => 'generate_setup_guide',
                            'description' => 'Generates tailored step-by-step restoration instructions for a project.',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'project_id' => ['type' => 'integer', 'description' => 'ID of the project']
                                ],
                                'required' => ['project_id']
                            ]
                        ],
                        [
                            'name' => 'generate_showcase_video',
                            'description' => 'Generates a Kario-style motion graphic showcase video script and scene animation for a project.',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'project_id' => ['type' => 'integer', 'description' => 'ID of the project'],
                                    'preset' => ['type' => 'string', 'description' => 'Motion preset e.g. liquid_glass, 3d_ring, product_launch, kinetic_type']
                                ],
                                'required' => ['project_id']
                            ]
                        ]
                    ]
                ]);

            case 'tools/call':
                $toolName = $params['name'] ?? '';
                $arguments = $params['arguments'] ?? [];
                return self::callTool($id, $toolName, $arguments);

            case 'resources/list':
                return self::response($id, [
                    'resources' => [
                        [
                            'uri' => 'cloudvault://projects',
                            'name' => 'Backed-up Projects List',
                            'mimeType' => 'application/json',
                            'description' => 'JSON manifest of all current CloudVault projects'
                        ]
                    ]
                ]);

            default:
                return self::error($id, -32601, "Method not found: {$method}");
        }
    }

    private static function callTool($id, string $name, array $args): array {
        $db = Database::getConnection();

        switch ($name) {
            case 'list_projects':
                $userId = $args['user_id'] ?? null;
                $sql = "SELECT p.*, pv.total_size, pv.file_count, pv.backup_mode FROM projects p LEFT JOIN project_versions pv ON p.current_version_id = pv.id WHERE p.deleted_at IS NULL";
                $params = [];
                if ($userId) {
                    $sql .= " AND p.user_id = ?";
                    $params[] = $userId;
                }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $projects = $stmt->fetchAll();

                return self::response($id, [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => json_encode(['status' => 'success', 'projects' => $projects], JSON_PRETTY_PRINT)
                        ]
                    ]
                ]);

            case 'get_project_requirements':
                $projectId = (int)($args['project_id'] ?? 0);
                $project = Project::findById($projectId);
                if (!$project) {
                    return self::error($id, -32602, "Project not found.");
                }

                $version = ProjectVersion::findById($project->current_version_id ?? 0);
                $stmt = $db->prepare("SELECT * FROM project_requirements WHERE project_version_id = ?");
                $stmt->execute([$version->id ?? 0]);
                $reqs = $stmt->fetchAll();

                return self::response($id, [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => json_encode([
                                'project_name' => $project->project_name,
                                'project_type' => $project->project_type,
                                'requirements' => $reqs
                            ], JSON_PRETTY_PRINT)
                        ]
                    ]
                ]);

            case 'generate_setup_guide':
                $projectId = (int)($args['project_id'] ?? 0);
                $project = Project::findById($projectId);
                if (!$project) return self::error($id, -32602, "Project not found.");

                $version = ProjectVersion::findById($project->current_version_id ?? 0);
                $stmt = $db->prepare("SELECT * FROM project_requirements WHERE project_version_id = ?");
                $stmt->execute([$version->id ?? 0]);
                $reqs = $stmt->fetchAll();

                $txtGuide = SetupGuideGenerator::generateTxtGuide($project, $version, $reqs, false);

                return self::response($id, [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $txtGuide
                        ]
                    ]
                ]);

            case 'generate_showcase_video':
                $projectId = (int)($args['project_id'] ?? 0);
                $preset = $args['preset'] ?? 'liquid_glass';
                $project = Project::findById($projectId);
                if (!$project) return self::error($id, -32602, "Project not found.");

                $script = [
                    'title' => $project->project_name . " — Motion Graphics Showcase",
                    'tagline' => $project->description ?: "Built with " . $project->project_type . ". Backed up and restored with CloudVault.",
                    'preset' => $preset,
                    'scenes' => [
                        ['time' => '00:00-00:03', 'text' => "Introducing " . $project->project_name, 'motion' => 'Kinetic Type Entrance'],
                        ['time' => '00:03-00:07', 'text' => "Built with " . $project->project_type . " & Verified Integrity", 'motion' => '3D Code Ring Layer'],
                        ['time' => '00:07-00:10', 'text' => "Restore and Transfer Anywhere in Seconds", 'motion' => 'Liquid Glass Focus']
                    ]
                ];

                return self::response($id, [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => json_encode($script, JSON_PRETTY_PRINT)
                        ]
                    ]
                ]);

            default:
                return self::error($id, -32601, "Unknown tool: {$name}");
        }
    }

    private static function response($id, array $result): array {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result
        ];
    }

    private static function error($id, int $code, string $message): array {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];
    }
}
