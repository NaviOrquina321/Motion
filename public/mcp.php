<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../app/Services/McpService.php';

$rawBody = file_get_contents('php://input');

if (empty($rawBody)) {
    // Return discovery information if accessed via GET
    echo json_encode([
        'mcp_server' => 'CloudVault AI Motion & Backup MCP Agent',
        'status' => 'online',
        'protocol_version' => '2024-11-05',
        'endpoint' => 'POST /mcp.php',
        'documentation' => 'Connect Claude Desktop, Claude Code, or ChatGPT to manage project backups and generate Kario-style showcase videos over MCP.'
    ], JSON_PRETTY_PRINT);
    exit;
}

$request = json_decode($rawBody, true);
if (!is_array($request)) {
    echo json_encode([
        'jsonrpc' => '2.0',
        'id' => null,
        'error' => [
            'code' => -32700,
            'message' => 'Parse error: Invalid JSON payload'
        ]
    ]);
    exit;
}

$response = McpService::handleRequest($request);
echo json_encode($response);
