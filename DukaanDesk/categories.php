<?php
require_once __DIR__ . '/inc/auth.php';
guard('categories');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $db->prepare('DELETE FROM categories WHERE id = ?')->execute(array((int)$_POST['id']));
        flash('success', 'Category deleted. Its products are now uncategorised.');
        go('categories.php');
    }

    $id   = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        flash('danger', 'Category name is required.');
        go('categories.php');
    }

    try {
        if ($id) {
            $db->prepare('UPDATE categories SET name = ? WHERE id = ?')->execute(array($name, $id));
            flash('success', 'Category updated.');
        } else {
            $db->prepare('INSERT INTO categories (name) VALUES (?)')->execute(array($name));
            flash('success', 'Category added.');
        }
    } catch (PDOException $e) {
        // name column is unique
        flash('danger', 'A category with this name already exists.');
    }
    go('categories.php');
}

$categories = $db->query("SELECT c.id, c.name, COUNT(p.id) AS items
                          FROM categories c LEFT JOIN products p ON p.category_id = c.id
                          GROUP BY c.id, c.name ORDER BY c.name")->fetchAll();

$page_title = 'Categories';
$active = 'categories';
include 'inc/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Group your products, for example Grocery or Stationery.</p>
    <button class="btn btn-primary" data-form-modal data-title="Add Category"
            data-bs-toggle="modal" data-bs-target="#catModal">
        <i class="bi bi-plus-lg"></i> Add Category
    </button>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover align-middle js-table">
            <thead><tr><th>#</th><th>Category</th><th>Products</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td><?= $c['id'] ?></td>
                    <td><?= h($c['name']) ?></td>
                    <td><?= (int)$c['items'] ?></td>
                    <td class="text-nowrap">
                        <button class="btn btn-sm btn-outline-primary" data-form-modal data-title="Edit Category"
                                data-bs-toggle="modal" data-bs-target="#catModal"
                                data-row="<?= h(json_encode($c)) ?>"><i class="bi bi-pencil"></i></button>
                        <form method="post" class="d-inline js-confirm" data-msg="Delete this category?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="catModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="">
            <div class="modal-header">
                <h5 class="modal-title">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Category name *</label>
                <input type="text" name="name" class="form-control" maxlength="80" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<?php include 'inc/footer.php'; ?>
