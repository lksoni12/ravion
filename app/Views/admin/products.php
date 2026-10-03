<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-head flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Product Portfolio</h2>
        <p class="text-sm text-slate-500 mt-1">Manage the medical product catalog and detailing slide presentations.</p>
    </div>
    <button type="button" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold rounded-xl text-sm shadow-lg shadow-indigo-100 hover:shadow-xl transition-all duration-200 flex items-center gap-2 border-0 cursor-pointer" data-popup-open="popup-add-product">
        <i class="fa-solid fa-plus text-xs"></i> Add Product
    </button>
</div>

<!-- Edit Product Section (Appears only when editing) -->
<?php if (! empty($editingProduct)): ?>
<div class="mb-8 max-w-2xl">
    <div class="bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden">
        <div class="bg-amber-50/50 px-6 py-4 border-b border-amber-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-amber-600"></i>
                <h3 class="text-lg font-bold text-slate-800">Edit Product #<?= esc($editingProduct['id']) ?></h3>
            </div>
            <span class="text-xs font-extrabold px-3 py-1 bg-amber-100 text-amber-800 rounded-full uppercase tracking-wider">Editing Mode</span>
        </div>
        
        <form method="post" action="/phadmin/products/update/<?= esc($editingProduct['id']) ?>" enctype="multipart/form-data" class="p-6 space-y-4 m-0 border-0 bg-transparent">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Product Name</label>
                <input name="name" value="<?= esc($editingProduct['name']) ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">PTS Price (₹)</label>
                    <input type="number" step="0.01" name="pts_price" value="<?= esc($editingProduct['pts_price'] ?? '0') ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Packing</label>
                    <input name="packing" value="<?= esc($editingProduct['packing'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. 10x10 Tablets">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Upload Additional Slides (PDF or Images)</label>
                    <input type="file" name="detail_pdf_files[]" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Product Images</label>
                    <input type="file" name="product_image[]" accept="image/*" multiple class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                    <?php if (!empty($editingProduct['image_path'])): ?>
                        <?php 
                            $images = json_decode($editingProduct['image_path'], true);
                            if (!is_array($images)) $images = [$editingProduct['image_path']];
                        ?>
                        <div class="mt-2 text-xs flex flex-wrap gap-2">
                            <?php foreach ($images as $idx => $img): ?>
                                <a href="/<?= esc($img) ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1">
                                    <i class="fa-solid fa-image"></i> Image <?= $idx + 1 ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"><?= esc($editingProduct['description']) ?></textarea>
            </div>

            <?php if (! empty($editingProduct['slides'])): ?>
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <i class="fa-solid fa-layer-group text-slate-500"></i> Current Detailing Slides (Drag to Reorder)
                    </h4>
                    <div id="slides-sortable-list" class="grid grid-cols-1 gap-2 max-h-60 overflow-y-auto">
                        <?php foreach ($editingProduct['slides'] as $sidx => $slide): ?>
                            <div class="flex items-center justify-between bg-white px-3 py-2 rounded-lg border border-slate-100 text-xs shadow-sm hover:border-slate-200 transition-colors slide-sort-item" data-id="<?= esc($slide['id']) ?>">
                                <span class="font-semibold text-slate-700 flex items-center gap-2 truncate cursor-move slide-grip-handle">
                                    <i class="fa-solid fa-grip-vertical text-slate-400"></i>
                                    <?php
                                        $ext = strtolower(pathinfo($slide['file_path'], PATHINFO_EXTENSION));
                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                                            echo '<i class="fa-solid fa-image text-indigo-500 text-xs"></i>';
                                        } else {
                                            echo '<i class="fa-solid fa-file-pdf text-red-500 text-xs"></i>';
                                        }
                                    ?>
                                    <?= esc($slide['title']) ?>
                                    <span class="text-[10px] text-slate-400 font-mono ml-1 slide-order-val">(Order: <?= esc($slide['slide_order']) ?>)</span>
                                </span>
                                <div class="flex items-center gap-2">
                                    <a href="/<?= esc($slide['file_path']) ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-bold transition-colors">View</a>
                                    <span class="text-slate-300">|</span>
                                    <a href="/phadmin/slides" class="text-slate-500 hover:text-slate-700 font-semibold transition-colors">Manage</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                <a href="/phadmin/products" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm text-center transition-colors">Cancel</a>
                <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg text-sm shadow-md transition-all">Update Product</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Products Table Panel -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h3 class="text-base font-extrabold text-slate-800">Product Portfolio List</h3>
            <p class="text-xs text-slate-500 mt-1">Search through existing products and view their associated slide files.</p>
        </div>
        <div class="w-full md:w-auto">
            <div class="relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input id="search-products" class="w-full md:w-72 pl-9 pr-4 py-2 bg-white border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-slate-400" placeholder="Search products...">
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table id="table-products" class="w-full border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 border-b border-slate-100 text-xs font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-4 text-left w-16">Sort</th>
                    <th class="py-3.5 px-4 text-left">ID</th>
                    <th class="py-3.5 px-4 text-left">Product Name</th>
                    <th class="py-3.5 px-4 text-left">Packing</th>
                    <th class="py-3.5 px-4 text-left">PTS (₹)</th>
                    <th class="py-3.5 px-4 text-left">Description</th>
                    <th class="py-3.5 px-4 text-left">Product Image</th>
                    <th class="py-3.5 px-4 text-left">Detailing PDF / Slides</th>
                    <th class="py-3.5 px-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-box-open text-4xl mb-2 block"></i>
                            No products found. Click "Add Product" to get started.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr class="hover:bg-slate-50/30 transition-colors" data-id="<?= esc($p['id']) ?>">
                            <td class="py-4 px-4 text-slate-400 cursor-move grip-handle">
                                <i class="fa-solid fa-grip-vertical"></i>
                                <span class="ml-2 font-mono text-xs text-slate-400 display-order-val"><?= esc($p['display_order']) ?></span>
                            </td>
                            <td class="py-4 px-4 text-slate-500 font-mono text-xs"><?= esc($p['id']) ?></td>
                            <td class="py-4 px-4 font-bold text-slate-800"><?= esc($p['name']) ?></td>
                            <td class="py-4 px-4 text-slate-600 font-medium"><?= esc($p['packing'] ?: '-') ?></td>
                            <td class="py-4 px-4 text-slate-700 font-semibold">₹<?= number_format((float)($p['pts_price'] ?? 0), 2) ?></td>
                            <td class="py-4 px-4 text-slate-500 max-w-xs truncate" title="<?= esc($p['description']) ?>"><?= esc($p['description'] ?: '-') ?></td>
                            <td class="py-4 px-4">
                                <?php if (! empty($p['image_path'])): ?>
                                    <?php 
                                        $images = json_decode($p['image_path'], true);
                                        if (!is_array($images)) $images = [$p['image_path']];
                                    ?>
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach ($images as $img): ?>
                                            <a href="/<?= esc($img) ?>" target="_blank">
                                                <img src="/<?= esc($img) ?>" alt="<?= esc($p['name']) ?>" class="w-10 h-10 object-cover rounded shadow-sm border border-slate-200">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-300 text-xs">-</span>
                                  <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                                <?php if (! empty($p['slides'])): ?>
                                    <div class="flex flex-col gap-1.5">
                                        <?php foreach ($p['slides'] as $sidx => $slide): ?>
                                            <a href="/<?= esc($slide['file_path']) ?>" target="_blank" class="inline-flex items-center gap-1.5 text-indigo-600 hover:text-indigo-800 font-bold text-xs transition-colors bg-indigo-50/50 hover:bg-indigo-50 px-2.5 py-1.5 rounded-lg border border-indigo-100 w-fit">
                                                <?php
                                                    $ext = strtolower(pathinfo($slide['file_path'], PATHINFO_EXTENSION));
                                                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                                                        echo '<i class="fa-solid fa-image text-indigo-500 text-xs"></i>';
                                                    } else {
                                                        echo '<i class="fa-solid fa-file-pdf text-red-500 text-xs"></i>';
                                                    }
                                                ?>
                                                View (<?= esc($slide['title']) ?>)
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif (! empty($p['detail_pdf'])): ?>
                                    <a href="/<?= esc($p['detail_pdf']) ?>" target="_blank" class="inline-flex items-center gap-1.5 text-indigo-600 hover:text-indigo-800 font-bold text-xs transition-colors bg-indigo-50/50 hover:bg-indigo-50 px-2.5 py-1.5 rounded-lg border border-indigo-100 w-fit">
                                        <i class="fa-solid fa-file-pdf text-red-500 text-xs"></i>
                                        View PDF
                                    </a>
                                <?php else: ?>
                                    <span class="text-slate-400 font-medium italic">No slides uploaded</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="/phadmin/products?edit=<?= esc($p['id']) ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 transition-colors border border-amber-100" title="Edit product">
                                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                                    </a>
                                    <form method="post" action="/phadmin/products/delete/<?= esc($p['id']) ?>" class="inline-block m-0 p-0 border-0 bg-transparent" onsubmit="return confirm('Are you sure you want to delete this product? Associated slides will remain but lose product association.');">
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition-colors border border-red-100 cursor-pointer p-0" title="Delete product">
                                            <i class="fa-solid fa-trash-can text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Product Modal Overlay -->
<div id="popup-add-product" class="popup-overlay fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50 transition-opacity duration-300" aria-hidden="true">
    <div class="popup-card bg-white rounded-2xl border border-slate-100 shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto transform scale-95 transition-all duration-300">
        <div class="popup-head flex items-center justify-between border-b border-slate-100 p-4 pb-3">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-circle-plus text-indigo-600"></i> Add Product
            </h3>
            <button type="button" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors border-0 cursor-pointer" data-popup-close="popup-add-product">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <form method="post" action="/phadmin/products" enctype="multipart/form-data" class="space-y-4 m-0 border-0 bg-transparent">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Product Name</label>
                    <input name="name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. Amoxicillin 500mg">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">PTS Price (₹)</label>
                        <input type="number" step="0.01" name="pts_price" placeholder="0.00" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Packing</label>
                        <input name="packing" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. 10x10 Tablets">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Slides (PDF or Images)</label>
                        <input type="file" name="detail_pdf_files[]" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Product Images</label>
                        <input type="file" name="product_image[]" accept="image/*" multiple class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="Enter product description and therapeutic class..."></textarea>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm transition-colors border-0 cursor-pointer" data-popup-close="popup-add-product">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-sm shadow-md shadow-indigo-100 hover:shadow-lg transition-all duration-200 border-0 cursor-pointer">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    bindTableSearch('search-products', 'table-products');
    
    // Sortable JS setup for Products and Slides reordering
    document.addEventListener('DOMContentLoaded', function() {
        // Products sorting
        const productsTableBody = document.querySelector('#table-products tbody');
        if (productsTableBody) {
            Sortable.create(productsTableBody, {
                handle: '.grip-handle',
                animation: 150,
                onEnd: function() {
                    const rows = Array.from(productsTableBody.querySelectorAll('tr[data-id]'));
                    const order = rows.map(row => row.getAttribute('data-id')).filter(Boolean);
                    
                    // Update the display orders in the UI
                    rows.forEach((row, index) => {
                        const valSpan = row.querySelector('.display-order-val');
                        if (valSpan) valSpan.textContent = index + 1;
                    });

                    // Send to backend
                    fetch('/phadmin/products/reorder', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ order: order })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) {
                            alert('Failed to save product order: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        console.error('Error reordering products:', err);
                    });
                }
            });
        }

        // Slides sorting
        const slidesContainer = document.getElementById('slides-sortable-list');
        if (slidesContainer) {
            Sortable.create(slidesContainer, {
                handle: '.slide-grip-handle',
                animation: 150,
                onEnd: function() {
                    const items = Array.from(slidesContainer.querySelectorAll('.slide-sort-item'));
                    const order = items.map(item => item.getAttribute('data-id')).filter(Boolean);

                    // Update UI ordering labels
                    items.forEach((item, index) => {
                        const orderSpan = item.querySelector('.slide-order-val');
                        if (orderSpan) orderSpan.textContent = '(Order: ' + (index + 1) + ')';
                    });

                    // Send to backend
                    fetch('/phadmin/slides/reorder', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ order: order })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) {
                            alert('Failed to save slide order: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        console.error('Error reordering slides:', err);
                    });
                }
            });
        }
    });

    // Smooth popup entry transition enhancer
    (function() {
        const triggers = document.querySelectorAll('[data-popup-open]');
        triggers.forEach(button => {
            button.addEventListener('click', () => {
                const popup = document.getElementById(button.getAttribute('data-popup-open'));
                if (popup) {
                    const card = popup.querySelector('.popup-card');
                    popup.classList.add('flex');
                    popup.classList.remove('hidden');
                    setTimeout(() => {
                        popup.classList.add('opacity-100');
                        if (card) {
                            card.classList.remove('scale-95');
                            card.classList.add('scale-100');
                        }
                    }, 50);
                }
            });
        });

        const closers = document.querySelectorAll('[data-popup-close], .popup-overlay');
        closers.forEach(element => {
            element.addEventListener('click', (e) => {
                let targetId = element.getAttribute('data-popup-close');
                let popup = targetId ? document.getElementById(targetId) : null;
                
                // If clicked overlay directly
                if (!popup && element.classList.contains('popup-overlay') && e.target === element) {
                    popup = element;
                }

                if (popup) {
                    const card = popup.querySelector('.popup-card');
                    if (card) {
                        card.classList.remove('scale-100');
                        card.classList.add('scale-95');
                    }
                    popup.classList.remove('opacity-100');
                    setTimeout(() => {
                        popup.classList.add('hidden');
                        popup.classList.remove('flex');
                    }, 200);
                }
            });
        });
    })();
</script>
<?= $this->endSection() ?>
