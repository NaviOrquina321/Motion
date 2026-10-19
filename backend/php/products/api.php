<?php
/* Product CRUD. ?action=list (any user, supports q + category_id + barcode) | save, delete (admin, POST) */
require_once __DIR__ . '/../config/helpers.php';
require_login();
$pdo = db();
$action = $_GET['action'] ?? 'list';
const UPLOAD_DIR = __DIR__ . '/../uploads/';

function save_image($f) {
    if ($f['error'] !== UPLOAD_ERR_OK) fail('Image upload failed.');
    if ($f['size'] > 2 * 1024 * 1024) fail('Image must be 2 MB or smaller.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) fail('Only JPG, PNG, GIF or WEBP images are allowed.');
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $name);
    return $name;
}
function remove_image($name) {
    if ($name && is_file(UPLOAD_DIR . $name)) @unlink(UPLOAD_DIR . $name);
}

if ($action === 'list') {
    $q   = clean($_GET['q'] ?? '');
    $cat = (int)($_GET['category_id'] ?? 0);
    $sql = 'SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id WHERE 1=1';
    $args = [];
    if ($q !== '')  {
        $sql .= ' AND (p.name LIKE ? OR p.barcode = ? OR p.id = ?)';
        $args[] = "%$q%";
        $args[] = $q;
        $args[] = ctype_digit($q) ? (int)$q : 0;
    }
    if ($cat > 0)   { $sql .= ' AND p.category_id = ?'; $args[] = $cat; }
    $st = $pdo->prepare($sql . ' ORDER BY p.name');
    $st->execute($args);
    json_out(['success' => true, 'products' => $st->fetchAll()]);
}

if ($action === 'save') {
    only_post(false);
    verify_csrf();
    require_role('admin');
    $id      = (int)($_POST['id'] ?? 0);
    $name    = clean($_POST['name'] ?? '');
    $barcode = clean($_POST['barcode'] ?? '');
    $cat     = (int)($_POST['category_id'] ?? 0);
    $price   = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
    $cost    = filter_var($_POST['cost_price'] ?? '0', FILTER_VALIDATE_FLOAT);
    $stock   = filter_var($_POST['stock_quantity'] ?? '', FILTER_VALIDATE_INT);
    $reord   = filter_var($_POST['reorder_level'] ?? '', FILTER_VALIDATE_INT);

    if ($name === '' || strlen($name) > 120)  fail('Product name is required (max 120 characters).');
    if ($cat <= 0)                            fail('Please choose a category.');
    if ($price === false || $price < 0)       fail('Enter a valid selling price.');
    if ($cost === false || $cost < 0)         fail('Enter a valid cost price.');
    if ($stock === false || $stock < 0)       fail('Enter a valid stock quantity.');
    if ($reord === false || $reord < 0)       fail('Enter a valid reorder level.');

    $barcodeVal = $barcode !== '' ? $barcode : null;
    $newImage = !empty($_FILES['image']['name']) ? save_image($_FILES['image']) : null;
    try {
        if ($id > 0) {
            $st = $pdo->prepare('SELECT image FROM products WHERE id = ?');
            $st->execute([$id]);
            $old = $st->fetchColumn();
            if ($old === false) fail('Product not found.', 404);
            $pdo->prepare('UPDATE products SET name=?, barcode=?, category_id=?, price=?, cost_price=?, stock_quantity=?, reorder_level=?, image=COALESCE(?, image) WHERE id=?')
                ->execute([$name, $barcodeVal, $cat, $price, $cost, $stock, $reord, $newImage, $id]);
            if ($newImage) remove_image($old);
            log_activity('product_update', "Updated product ID #{$id}: {$name}");
        } else {
            $pdo->prepare('INSERT INTO products (name, barcode, category_id, price, cost_price, stock_quantity, reorder_level, image) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$name, $barcodeVal, $cat, $price, $cost, $stock, $reord, $newImage]);
            $id = (int)$pdo->lastInsertId();
            log_activity('product_create', "Created product ID #{$id}: {$name}");
        }
    } catch (PDOException $e) {
        if ($newImage) remove_image($newImage);
        if ($e->errorInfo[1] == 1062) fail('Barcode already exists for another product.');
        if ($e->errorInfo[1] == 1452) fail('Selected category does not exist.');
        throw $e;
    }
    json_out(['success' => true, 'message' => $id > 0 ? 'Product updated.' : 'Product added.']);
}

if ($action === 'delete') {
    only_post(); require_role('admin');
    $id = (int)(body()['id'] ?? 0);
    $st = $pdo->prepare('SELECT image FROM products WHERE id = ?');
    $st->execute([$id]);
    $img = $st->fetchColumn();
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    remove_image($img);
    log_activity('product_delete', "Deleted product ID #{$id}");
    json_out(['success' => true, 'message' => 'Product deleted.']);
}
fail('Unknown action.');
