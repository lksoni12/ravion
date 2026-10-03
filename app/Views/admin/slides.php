<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="page-head">
    <h2>Slide Manager</h2>
    <div class="actions">
        <span class="subtle">Super Admin and Manager</span>
        <button type="button" class="btn-add" data-popup-open="popup-add-slide"><i class="fa-solid fa-plus"></i>Upload Slide</button>
    </div>
</div>

<div class="panel">
<div class="table-tools">
    <input id="search-slides" class="table-search" placeholder="Search slides...">
</div>
<table id="table-slides">
    <thead><tr><th>ID</th><th>Title</th><th>Product</th><th>File</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
        <?php foreach ($slides as $slide): ?>
            <tr>
                <td><?= esc($slide['id']) ?></td>
                <td><?= esc($slide['title']) ?></td>
                <td><span class="tag"><?= esc($slide['product_name'] ?? '-') ?></span></td>
                <td><a href="/<?= esc($slide['file_path']) ?>" target="_blank" class="btn-link">Open</a></td>
                <td><span class="tag"><?= esc(strtoupper($slide['status'])) ?></span></td>
                <td>
                    <div class="actions">
                        <form method="post" action="/phadmin/slides/toggle/<?= esc($slide['id']) ?>" style="margin:0;border:none;background:transparent;padding:0;">
                            <button type="submit"><?= ($slide['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                        <form method="post" action="/phadmin/slides/delete/<?= esc($slide['id']) ?>" style="margin:0;border:none;background:transparent;padding:0;" onsubmit="return confirm('Delete slide?');">
                            <button type="submit">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<div id="popup-add-slide" class="popup-overlay" aria-hidden="true">
    <div class="popup-card">
        <div class="popup-head">
            <h3>Upload Slide</h3>
            <button type="button" class="popup-close" data-popup-close="popup-add-slide">Close</button>
        </div>
        <form method="post" action="/phadmin/slides" enctype="multipart/form-data" style="margin:0;border:none;padding:0;background:transparent;">
            <label>Slide Title</label>
            <input name="title" required>

            <label>Product (optional)</label>
            <select name="product_id">
                <option value="">Select Product</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= esc($product['id']) ?>"><?= esc($product['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Upload Slide File (PDF or Images)</label>
            <input type="file" name="slide_file" accept=".pdf,.jpg,.jpeg,.png,.webp" required>

            <button type="submit">Upload Slide</button>
        </form>
    </div>
</div>
<script>bindTableSearch('search-slides', 'table-slides');</script>
<?= $this->endSection() ?>
