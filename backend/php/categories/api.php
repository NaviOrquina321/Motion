<?php
/* Category CRUD.  ?action=list (any user)  |  save, delete (admin, POST) */
require_once __DIR__ . '/../config/helpers.php';
require_login();
$pdo = db();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $rows = $pdo->query(
        'SELECT c.*, COUNT(p.id) AS product_count
         FROM categories c LEFT JOIN products p ON p.category_id = c.id
         GROUP BY c.id ORDER BY c.name'
    )->fetchAll();
    json_out(['success' => true, 'categories' => $rows]);
}

if ($action === 'save') {
    only_post(); require_role('admin');
    $b = body();
    $id   = (int)($b['id'] ?? 0);
    $name = clean($b['name'] ?? '');
    $desc = clean($b['description'] ?? '');
    if ($name === '' || strlen($name) > 80) fail('Category name is required (max 80 characters).');
    if (strlen($desc) > 255) fail('Description is too long.');
    try {
        if ($id > 0) {
            $pdo->prepare('UPDATE categories SET name = ?, description = ? WHERE id = ?')->execute([$name, $desc, $id]);
        } else {
            $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)')->execute([$name, $desc]);
        }
    } catch (PDOException $e) {
        if (is_duplicate($e)) fail('That category already exists.');
        throw $e;
    }
    json_out(['success' => true, 'message' => $id > 0 ? 'Category updated.' : 'Category added.']);
}

if ($action === 'delete') {
    only_post(); require_role('admin');
    $id = (int)(body()['id'] ?? 0);
    $st = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
    $st->execute([$id]);
    if ($st->fetchColumn() > 0) fail('This category still has products. Move or delete them first.');
    $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    json_out(['success' => true, 'message' => 'Category deleted.']);
}
fail('Unknown action.');
