<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-head flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Doctors Directory</h2>
        <p class="text-sm text-slate-500 mt-1">Manage doctor listings, category classifications, and patch area allocations.</p>
    </div>
    <button type="button" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold rounded-xl text-sm shadow-lg shadow-indigo-100 hover:shadow-xl transition-all duration-200 flex items-center gap-2 border-0 cursor-pointer" data-popup-open="popup-add-doctor">
        <i class="fa-solid fa-plus text-xs"></i> Add Doctor
    </button>
</div>

<!-- Inline Filters Panel -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/30 flex items-center gap-2">
        <i class="fa-solid fa-sliders text-indigo-600"></i>
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Search & Filters</h3>
    </div>
    <form method="get" action="/phadmin/doctors" class="p-6 m-0 border-0 bg-transparent">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Doctor Name</label>
                <div class="relative">
                    <i class="fa-solid fa-user-doctor absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input name="name" value="<?= esc($selectedName ?? '') ?>" placeholder="Search by name..." class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Category</label>
                <select name="category" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white">
                    <option value="" <?= empty($selectedCategory) ? 'selected' : '' ?>>All Categories</option>
                    <option value="A" <?= ($selectedCategory ?? '') === 'A' ? 'selected' : '' ?>>Category A</option>
                    <option value="B" <?= ($selectedCategory ?? '') === 'B' ? 'selected' : '' ?>>Category B</option>
                    <option value="C" <?= ($selectedCategory ?? '') === 'C' ? 'selected' : '' ?>>Category C</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Area / HQ</label>
                <div class="relative">
                    <i class="fa-solid fa-map-pin absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input name="area" value="<?= esc($selectedArea ?? '') ?>" placeholder="Search by area..." class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                </div>
            </div>
        </div>
        <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-slate-100">
            <a href="/phadmin/doctors" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm text-center transition-colors">Reset</a>
            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-sm shadow-md transition-all duration-200 border-0 cursor-pointer">Apply Filters</button>
        </div>
    </form>
</div>

<!-- Edit Doctor Section (Appears only when editing) -->
<?php if (! empty($editingDoctor)): ?>
<div class="mb-8 max-w-4xl">
    <div class="bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden">
        <div class="bg-amber-50/50 px-6 py-4 border-b border-amber-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-user-pen text-amber-600"></i>
                <h3 class="text-lg font-bold text-slate-800">Edit Doctor #<?= esc($editingDoctor['id']) ?></h3>
            </div>
            <span class="text-xs font-extrabold px-3 py-1 bg-amber-100 text-amber-800 rounded-full uppercase tracking-wider">Editing Mode</span>
        </div>
        
        <form method="post" action="/phadmin/doctors/update/<?= esc($editingDoctor['id']) ?>" class="p-6 space-y-4 m-0 border-0 bg-transparent">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Doctor Name</label>
                    <input name="name" value="<?= esc($editingDoctor['name']) ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Speciality</label>
                    <input name="speciality" value="<?= esc($editingDoctor['speciality']) ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Contact Number</label>
                    <input name="contact" value="<?= esc($editingDoctor['contact']) ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Date of Birth</label>
                    <input type="date" name="birthday" value="<?= esc($editingDoctor['birthday'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Category</label>
                    <select name="category" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white">
                        <option value="A" <?= ($editingDoctor['category'] ?? '') === 'A' ? 'selected' : '' ?>>Category A</option>
                        <option value="B" <?= ($editingDoctor['category'] ?? '') === 'B' ? 'selected' : '' ?>>Category B</option>
                        <option value="C" <?= ($editingDoctor['category'] ?? '') === 'C' ? 'selected' : '' ?>>Category C</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Monthly Potential</label>
                    <input name="monthly_potential" value="<?= esc($editingDoctor['monthly_potential'] ?? '') ?>" placeholder="High / Medium / Low or ₹" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Area / HQ</label>
                    <select name="territory_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white">
                        <option value="">Select Area / Patch...</option>
                        <?php foreach ($allowedTerritories as $t): ?>
                            <?php if ($t['type'] !== 'state'): ?>
                                <option value="<?= esc($t['id']) ?>" <?= (int)($editingDoctor['territory_id'] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>><?= esc($t['name']) ?> (<?= esc(strtoupper($t['type'])) ?>)</option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Clinic Address</label>
                    <textarea name="clinic_address" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"><?= esc($editingDoctor['clinic_address'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Assigned MR / Representative</label>
                    <select name="assigned_to_user_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white">
                        <option value="">Vacant (Select MR)</option>
                        <?php foreach (($assignableUsers ?? []) as $user): ?>
                            <option value="<?= esc($user['id']) ?>" <?= ((int) ($editingDoctor['assigned_to_user_id'] ?? 0) === (int) $user['id']) ? 'selected' : '' ?>><?= esc($user['name']) ?> (<?= esc(strtoupper($user['role'])) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="/phadmin/doctors" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm text-center transition-colors">Cancel</a>
                <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg text-sm shadow-md transition-all">Update Doctor</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Doctors Table Panel -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h3 class="text-base font-extrabold text-slate-800">Master Doctor Directory</h3>
            <p class="text-xs text-slate-500 mt-1">Directory of certified doctors and clinics with corresponding categories.</p>
        </div>
        <div class="w-full md:w-auto flex items-center gap-3">
            <?php if (! empty($canApprove)): ?>
                <button type="button" onclick="bulkApproveDoctors()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-sm shadow-md transition-all duration-200 flex items-center gap-2 border-0 cursor-pointer whitespace-nowrap">
                    <i class="fa-solid fa-check-double text-xs"></i> Approve Selected
                </button>
            <?php endif; ?>
            <div class="relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input id="search-doctors" class="w-full md:w-72 pl-9 pr-4 py-2 bg-white border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-slate-400" placeholder="Fast search name/speciality...">
            </div>
        </div>
    </div>
    
    <form id="bulk-approve-form" method="post" action="/phadmin/doctors/bulk-approve" class="hidden">
        <input type="hidden" name="doctor_ids" id="bulk-doctor-ids">
    </form>
    
    <div class="overflow-x-auto">
        <table id="table-doctors" class="w-full border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 border-b border-slate-100 text-xs font-bold uppercase tracking-wider">
                    <?php if (! empty($canApprove)): ?>
                        <th class="py-3.5 px-4 text-center w-12">
                            <input type="checkbox" id="select-all-doctors" onclick="toggleAllDoctorCheckboxes(this)" class="rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 cursor-pointer w-4 h-4">
                        </th>
                    <?php endif; ?>
                    <th class="py-3.5 px-4 text-left">Doctor Name</th>
                    <th class="py-3.5 px-4 text-left">Speciality</th>
                    <th class="py-3.5 px-4 text-left">Category</th>
                    <th class="py-3.5 px-4 text-left">Monthly Potential</th>
                    <th class="py-3.5 px-4 text-left">Area</th>
                    <th class="py-3.5 px-4 text-left">Assigned MR</th>
                    <th class="py-3.5 px-4 text-left">Contact</th>
                    <th class="py-3.5 px-4 text-center">Approval</th>
                    <th class="py-3.5 px-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (empty($doctors)): ?>
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-user-md text-4xl mb-2 block"></i>
                            No doctors registered. Click "Add Doctor" to expand the directory.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($doctors as $d): ?>
                        <tr class="hover:bg-slate-50/30 transition-colors">
                            <?php if (! empty($canApprove)): ?>
                                <td class="py-4 px-4 text-center">
                                    <?php if (($d['approval_status'] ?? '') === 'pending'): ?>
                                        <input type="checkbox" value="<?= esc($d['id']) ?>" class="doctor-checkbox rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 cursor-pointer w-4 h-4">
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td class="py-4 px-4 font-bold text-slate-800"><?= esc($d['name']) ?></td>
                            <td class="py-4 px-4 text-slate-600 font-medium"><?= esc($d['speciality'] ?: '-') ?></td>
                            <td class="py-4 px-4">
                                <?php
                                    $cat = strtoupper((string)($d['category'] ?? 'C'));
                                    if ($cat === 'A') {
                                        echo '<span class="inline-flex items-center px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-extrabold rounded-md">Cat A</span>';
                                    } elseif ($cat === 'B') {
                                        echo '<span class="inline-flex items-center px-2 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 text-xs font-extrabold rounded-md">Cat B</span>';
                                    } else {
                                        echo '<span class="inline-flex items-center px-2 py-0.5 bg-slate-50 text-slate-600 border border-slate-200 text-xs font-extrabold rounded-md">Cat C</span>';
                                    }
                                ?>
                            </td>
                            <td class="py-4 px-4 text-slate-700 font-semibold"><?= esc($d['monthly_potential'] ?: '-') ?></td>
                            <td class="py-4 px-4 text-slate-600"><?= esc($d['area_patch'] ?: '-') ?></td>
                            <td class="py-4 px-4 text-slate-600">
                                <?php
                                    $assignedName = '-';
                                    foreach (($assignableUsers ?? []) as $assignee) {
                                        if ((int) $assignee['id'] === (int) ($d['assigned_to_user_id'] ?? 0)) {
                                            $assignedName = (string) ($assignee['name'] ?? '-');
                                            break;
                                        }
                                    }
                                ?>
                                <span class="inline-flex items-center gap-1.5 font-medium text-slate-700">
                                    <i class="fa-solid fa-user-nurse text-slate-400 text-xs"></i>
                                    <?= esc($assignedName) ?>
                                </span>
                            </td>
                            <td class="py-4 px-4 text-slate-600"><?= esc($d['contact'] ?: '-') ?></td>
                            <td class="py-4 px-4 text-center">
                                <?php
                                    $status = strtolower((string)($d['approval_status'] ?? 'approved'));
                                    if ($status === 'approved') {
                                        echo '<span class="inline-flex items-center px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-100 text-xs font-bold rounded-lg"><i class="fa-solid fa-circle-check mr-1 text-emerald-500"></i>Approved</span>';
                                    } elseif ($status === 'rejected') {
                                        echo '<span class="inline-flex items-center px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-100 text-xs font-bold rounded-lg"><i class="fa-solid fa-circle-xmark mr-1 text-rose-500"></i>Rejected</span>';
                                    } else {
                                        echo '<span class="inline-flex items-center px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-100 text-xs font-bold rounded-lg"><i class="fa-solid fa-clock mr-1 text-amber-500"></i>Pending</span>';
                                    }
                                ?>
                                
                                <?php if (! empty($canApprove) && ($d['approval_status'] ?? '') === 'pending'): ?>
                                    <form method="post" action="/phadmin/doctors/approve/<?= esc($d['id']) ?>" class="mt-2 flex items-center justify-center gap-1.5 border-0 bg-transparent p-0 m-0">
                                        <select name="approval_status" class="px-2 py-1 text-xs border border-slate-300 rounded-md focus:outline-none w-24 bg-white">
                                            <option value="approved">Approve</option>
                                            <option value="rejected">Reject</option>
                                        </select>
                                        <button type="submit" class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-md text-xs border-0 cursor-pointer transition-colors shadow-sm">OK</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="/phadmin/doctors?edit=<?= esc($d['id']) ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 transition-colors border border-amber-100" title="Edit doctor">
                                        <i class="fa-solid fa-user-pen text-sm"></i>
                                    </a>
                                    <form method="post" action="/phadmin/doctors/delete/<?= esc($d['id']) ?>" class="inline-block m-0 p-0 border-0 bg-transparent" onsubmit="return confirm('Are you sure you want to delete this doctor?');">
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition-colors border border-red-100 cursor-pointer p-0" title="Delete doctor">
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

<!-- Add Doctor Modal Overlay (Spacious 2-Column Grid Layout) -->
<div id="popup-add-doctor" class="popup-overlay fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50 transition-opacity duration-300" aria-hidden="true">
    <div class="popup-card bg-white rounded-2xl border border-slate-100 shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto transform scale-95 transition-all duration-300">
        <div class="popup-head flex items-center justify-between border-b border-slate-100 p-4 pb-3">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-user-md text-indigo-600"></i> Add Doctor
            </h3>
            <button type="button" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors border-0 cursor-pointer" data-popup-close="popup-add-doctor">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <form method="post" action="/phadmin/doctors" class="space-y-4 m-0 border-0 bg-transparent">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Doctor Name</label>
                        <input name="name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. Dr. Rajesh Sharma">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Speciality</label>
                        <input name="speciality" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. Pediatrician">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Contact Number</label>
                        <input name="contact" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. +91 98765 43210">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Date of Birth</label>
                        <input type="date" name="birthday" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Category</label>
                        <select name="category" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white">
                            <option value="A">Category A</option>
                            <option value="B">Category B</option>
                            <option value="C" selected>Category C</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Monthly Potential</label>
                        <input name="monthly_potential" placeholder="High / Medium / Low or ₹" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Area / Patch</label>
                        <select name="territory_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white">
                            <option value="">Select Area / Patch...</option>
                            <?php foreach ($allowedTerritories as $t): ?>
                                <?php if ($t['type'] !== 'state'): ?>
                                    <option value="<?= esc($t['id']) ?>"><?= esc($t['name']) ?> (<?= esc(strtoupper($t['type'])) ?>)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Clinic Address</label>
                        <textarea name="clinic_address" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="Enter full clinic/hospital address..."></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm transition-colors border-0 cursor-pointer" data-popup-close="popup-add-doctor">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-sm shadow-md shadow-indigo-100 hover:shadow-lg transition-all duration-200 border-0 cursor-pointer">Add Doctor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    bindTableSearch('search-doctors', 'table-doctors');

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
    
    function toggleAllDoctorCheckboxes(source) {
        document.querySelectorAll('.doctor-checkbox').forEach(cb => {
            cb.checked = source.checked;
        });
    }

    function bulkApproveDoctors() {
        const selected = Array.from(document.querySelectorAll('.doctor-checkbox:checked')).map(cb => cb.value);
        if (selected.length === 0) {
            alert('Please select at least one doctor to approve.');
            return;
        }
        if (confirm('Are you sure you want to approve the ' + selected.length + ' selected doctor(s)?')) {
            document.getElementById('bulk-doctor-ids').value = selected.join(',');
            document.getElementById('bulk-approve-form').submit();
        }
    }
</script>
<?= $this->endSection() ?>
