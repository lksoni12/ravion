<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-head flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Chemists Directory</h2>
        <p class="text-sm text-slate-500 mt-1">Manage chemist profiles, contact representatives, and local HQ territories.</p>
    </div>
    <button type="button" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold rounded-xl text-sm shadow-lg shadow-indigo-100 hover:shadow-xl transition-all duration-200 flex items-center gap-2 border-0 cursor-pointer" data-popup-open="popup-add-chemist">
        <i class="fa-solid fa-plus text-xs"></i> Add Chemist
    </button>
</div>

<!-- Edit Chemist Section (Appears only when editing) -->
<?php if (! empty($editingChemist)): ?>
<div class="mb-8 max-w-4xl">
    <div class="bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden">
        <div class="bg-amber-50/50 px-6 py-4 border-b border-amber-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-user-pen text-amber-600"></i>
                <h3 class="text-lg font-bold text-slate-800">Edit Chemist #<?= esc($editingChemist['id']) ?></h3>
            </div>
            <span class="text-xs font-extrabold px-3 py-1 bg-amber-100 text-amber-800 rounded-full uppercase tracking-wider">Editing Mode</span>
        </div>
        
        <form method="post" action="/phadmin/chemists/update/<?= esc($editingChemist['id']) ?>" class="p-6 space-y-4 m-0 border-0 bg-transparent">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="edit_chemist_name" class="block text-sm font-semibold text-slate-700 mb-1">Chemist Shop Name</label>
                    <input id="edit_chemist_name" name="name" value="<?= esc($editingChemist['name']) ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
                <div>
                    <label for="edit_contact_person" class="block text-sm font-semibold text-slate-700 mb-1">Contact Person Name</label>
                    <input id="edit_contact_person" name="contact_person" value="<?= esc($editingChemist['contact_person'] ?? '') ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
                <div>
                    <label for="edit_chemist_area" class="block text-sm font-semibold text-slate-700 mb-1">Area / Patch</label>
                    <select name="territory_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white">
                        <option value="">Select Area / Patch...</option>
                        <?php foreach ($allowedTerritories as $t): ?>
                            <?php if ($t['type'] !== 'state'): ?>
                                <option value="<?= esc($t['id']) ?>" <?= (int)($editingChemist['territory_id'] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>><?= esc($t['name']) ?> (<?= esc(strtoupper($t['type'])) ?>)</option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="edit_chemist_contact" class="block text-sm font-semibold text-slate-700 mb-1">Contact Number</label>
                    <input id="edit_chemist_contact" name="contact" value="<?= esc($editingChemist['contact']) ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="/phadmin/chemists" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm text-center transition-colors">Cancel</a>
                <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg text-sm shadow-md transition-all">Update Chemist</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Chemists Table Panel -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h3 class="text-base font-extrabold text-slate-800">Master Chemist Directory</h3>
            <p class="text-xs text-slate-500 mt-1">Directory of partner chemists and retail pharmacies.</p>
        </div>
        <div class="w-full md:w-auto">
            <div class="relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input id="search-chemists" class="w-full md:w-72 pl-9 pr-4 py-2 bg-white border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-slate-400" placeholder="Fast search shop name/area...">
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table id="table-chemists" class="w-full border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 border-b border-slate-100 text-xs font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-4 text-left">Shop Name</th>
                    <th class="py-3.5 px-4 text-left">Contact Person</th>
                    <th class="py-3.5 px-4 text-left">Area</th>
                    <th class="py-3.5 px-4 text-left">Contact No.</th>
                    <th class="py-3.5 px-4 text-center">Approval Status</th>
                    <th class="py-3.5 px-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (empty($chemists)): ?>
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-flask text-4xl mb-2 block"></i>
                            No chemists registered. Click "Add Chemist" to expand the directory.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($chemists as $c): ?>
                        <tr class="hover:bg-slate-50/30 transition-colors">
                            <td class="py-4 px-4 font-bold text-slate-800"><?= esc($c['name']) ?></td>
                            <td class="py-4 px-4 text-slate-600 font-medium"><?= esc($c['contact_person'] ?? '-') ?></td>
                            <td class="py-4 px-4 text-slate-600"><?= esc($c['area'] ?: '-') ?></td>
                            <td class="py-4 px-4 text-slate-600"><?= esc($c['contact'] ?: '-') ?></td>
                            <td class="py-4 px-4 text-center">
                                <?php
                                    $status = strtolower((string)($c['approval_status'] ?? 'approved'));
                                    if ($status === 'approved') {
                                        echo '<span class="inline-flex items-center px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-100 text-xs font-bold rounded-lg"><i class="fa-solid fa-circle-check mr-1 text-emerald-500"></i>Approved</span>';
                                    } elseif ($status === 'rejected') {
                                        echo '<span class="inline-flex items-center px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-100 text-xs font-bold rounded-lg"><i class="fa-solid fa-circle-xmark mr-1 text-rose-500"></i>Rejected</span>';
                                    } else {
                                        echo '<span class="inline-flex items-center px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-100 text-xs font-bold rounded-lg"><i class="fa-solid fa-clock mr-1 text-amber-500"></i>Pending</span>';
                                    }
                                ?>
                                
                                <?php if (! empty($canApprove) && ($c['approval_status'] ?? '') === 'pending'): ?>
                                    <form method="post" action="/phadmin/chemists/approve/<?= esc($c['id']) ?>" class="mt-2 flex items-center justify-center gap-1.5 border-0 bg-transparent p-0 m-0">
                                        <select name="approval_status" class="px-2 py-1 text-xs border border-slate-300 rounded-md focus:outline-none w-24 bg-white animate-pulse">
                                            <option value="approved">Approve</option>
                                            <option value="rejected">Reject</option>
                                        </select>
                                        <button type="submit" class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-md text-xs border-0 cursor-pointer transition-colors shadow-sm">OK</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="/phadmin/chemists?edit=<?= esc($c['id']) ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 transition-colors border border-amber-100" title="Edit chemist">
                                        <i class="fa-solid fa-user-pen text-sm"></i>
                                    </a>
                                    <form method="post" action="/phadmin/chemists/delete/<?= esc($c['id']) ?>" class="inline-block m-0 p-0 border-0 bg-transparent" onsubmit="return confirm('Are you sure you want to delete this chemist?');">
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition-colors border border-red-100 cursor-pointer p-0" title="Delete chemist">
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

<!-- Add Chemist Modal Overlay (Clean 2-Column Grid Layout) -->
<div id="popup-add-chemist" class="popup-overlay fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50 transition-opacity duration-300" aria-hidden="true">
    <div class="popup-card bg-white rounded-2xl border border-slate-100 shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto transform scale-95 transition-all duration-300">
        <div class="popup-head flex items-center justify-between border-b border-slate-100 p-4 pb-3">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-flask text-indigo-600"></i> Add Chemist
            </h3>
            <button type="button" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors border-0 cursor-pointer" data-popup-close="popup-add-chemist">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <form method="post" action="/phadmin/chemists" class="space-y-4 m-0 border-0 bg-transparent">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="chemist_name" class="block text-sm font-semibold text-slate-700 mb-1">Chemist Shop Name</label>
                        <input id="chemist_name" name="name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. Apollo Pharmacy">
                    </div>
                    <div>
                        <label for="contact_person" class="block text-sm font-semibold text-slate-700 mb-1">Contact Person Name</label>
                        <input id="contact_person" name="contact_person" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. Mr. Amit Patel">
                    </div>
                    <div>
                        <label for="chemist_area" class="block text-sm font-semibold text-slate-700 mb-1">Area / Patch</label>
                        <select name="territory_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white">
                            <option value="">Select Area / Patch...</option>
                            <?php foreach ($allowedTerritories as $t): ?>
                                <?php if ($t['type'] !== 'state'): ?>
                                    <option value="<?= esc($t['id']) ?>"><?= esc($t['name']) ?> (<?= esc(strtoupper($t['type'])) ?>)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="chemist_contact" class="block text-sm font-semibold text-slate-700 mb-1">Contact Number</label>
                        <input id="chemist_contact" name="contact" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. 020-24456789">
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm transition-colors border-0 cursor-pointer" data-popup-close="popup-add-chemist">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-sm shadow-md shadow-indigo-100 hover:shadow-lg transition-all duration-200 border-0 cursor-pointer">Add Chemist</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    bindTableSearch('search-chemists', 'table-chemists');

    // Smooth modal animation triggers
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
