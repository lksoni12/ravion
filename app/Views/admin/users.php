<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-head flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">User Management</h2>
        <p class="text-sm text-slate-500 mt-1">Super Admin-only access. Manage employee profiles, roles, HQs, and reporting hierarchies.</p>
    </div>
    <button type="button" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold rounded-xl text-sm shadow-lg shadow-indigo-100 hover:shadow-xl transition-all duration-200 flex items-center gap-2 border-0 cursor-pointer" data-popup-open="popup-add-user">
        <i class="fa-solid fa-plus text-xs"></i> Add Employee
    </button>
</div>

<!-- Master DA Settings Panel (Admin Only) -->
<div class="mb-8 max-w-4xl bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden transition-all duration-300">
    <div class="bg-indigo-50/50 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-gears text-indigo-600"></i>
            <h3 class="text-base font-extrabold text-slate-800">Master Daily Allowance (DA) Settings</h3>
        </div>
        <span class="text-xs font-bold text-slate-500">Configure Default Role-Wise DA Rates</span>
    </div>
    <form method="post" action="/phadmin/settings/da" class="p-6 m-0 border-0 bg-transparent space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- MR Rates -->
            <div class="space-y-3">
                <h4 class="text-xs font-extrabold text-indigo-700 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-indigo-500"></span> MR (Representative) Defaults
                </h4>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">HQ DA (&#8377;)</label>
                        <input type="number" name="da_mr_hq" value="<?= esc($settings['da_mr_hq'] ?? 250) ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-semibold">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Ex-HQ DA (&#8377;)</label>
                        <input type="number" name="da_mr_ex" value="<?= esc($settings['da_mr_ex'] ?? 450) ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-semibold">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Outstation (&#8377;)</label>
                        <input type="number" name="da_mr_os" value="<?= esc($settings['da_mr_os'] ?? 700) ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-semibold">
                    </div>
                </div>
            </div>
            
            <!-- Manager Rates -->
            <div class="space-y-3">
                <h4 class="text-xs font-extrabold text-emerald-700 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Manager Defaults
                </h4>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">HQ DA (&#8377;)</label>
                        <input type="number" name="da_manager_hq" value="<?= esc($settings['da_manager_hq'] ?? 250) ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-semibold">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Ex-HQ DA (&#8377;)</label>
                        <input type="number" name="da_manager_ex" value="<?= esc($settings['da_manager_ex'] ?? 450) ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-semibold">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Outstation (&#8377;)</label>
                        <input type="number" name="da_manager_os" value="<?= esc($settings['da_manager_os'] ?? 700) ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-semibold">
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Secondary Target (Global Defaults) -->
        <div class="border-t border-slate-100 pt-4 mt-2">
            <h4 class="text-xs font-extrabold text-blue-700 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                <i class="fa-solid fa-bullseye text-blue-600"></i> Global Monthly Target Settings
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Monthly Secondary Target (&#8377;)</label>
                    <input type="number" name="monthly_secondary_target" value="<?= esc($settings['monthly_secondary_target'] ?? '1000000') ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-semibold">
                </div>
            </div>
        </div>
        <div class="flex justify-end pt-3">
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs shadow-md transition-all cursor-pointer border-0">
                <i class="fa-solid fa-save mr-1"></i> Save Master DA Settings
            </button>
        </div>
    </form>
</div>

<!-- Edit User Section (Appears only when editing) -->
<?php if (! empty($editingUser)): ?>
<div class="mb-8 max-w-4xl">
    <div class="bg-white rounded-2xl border border-amber-200 shadow-sm">
        <div class="bg-amber-50/50 px-6 py-4 border-b border-amber-100 flex items-center justify-between rounded-t-2xl">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-user-pen text-amber-600"></i>
                <h3 class="text-lg font-bold text-slate-800">Edit Employee #<?= esc($editingUser['id']) ?></h3>
            </div>
            <span class="text-xs font-extrabold px-3 py-1 bg-amber-100 text-amber-800 rounded-full uppercase tracking-wider">Editing Mode</span>
        </div>
        
        <form method="post" action="/phadmin/users/update/<?= esc($editingUser['id']) ?>" class="p-6 space-y-4 m-0 border-0 bg-transparent">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Full Name</label>
                    <input name="name" value="<?= esc($editingUser['name']) ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Username (User ID)</label>
                    <input value="<?= esc($editingUser['username'] ?? '') ?>" readonly class="w-full px-3 py-2 border border-slate-200 bg-slate-50 text-slate-500 rounded-lg cursor-not-allowed focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Mobile Number</label>
                    <input name="mobile" value="<?= esc($editingUser['mobile'] ?? '') ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Email Address</label>
                    <input type="email" name="email" value="<?= esc($editingUser['email'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Role</label>
                    <select name="role" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                        <option value="mr" <?= ($editingUser['role'] ?? '') === 'mr' ? 'selected' : '' ?>>MR</option>
                        <option value="manager" <?= ($editingUser['role'] ?? '') === 'manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="owner" <?= ($editingUser['role'] ?? '') === 'owner' ? 'selected' : '' ?>>Super Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Area / HQ</label>
                    <input name="area" value="<?= esc($editingUser['area'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Reporting To</label>
                    <select name="manager_user_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                        <option value="">Vacant (Direct to Super Admin)</option>
                        <?php foreach ($managers as $manager): ?>
                            <option value="<?= esc($manager['id']) ?>" <?= ((int) ($editingUser['manager_user_id'] ?? 0) === (int) $manager['id']) ? 'selected' : '' ?>><?= esc($manager['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Reset Password (Leave blank to keep current)</label>
                    <input type="password" name="password" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="••••••••">
                </div>
            </div>

            <!-- Employee-specific DA & Target Overrides -->
            <div class="mt-4 border-t border-slate-100 pt-4">
                <h4 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-wallet text-indigo-600"></i> Employee-Specific DA & Target Rates (Overwrites defaults)
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">HQ DA Rate (&#8377;)</label>
                        <input type="number" name="da_hq" value="<?= esc($editingUser['da_hq'] ?? '') ?>" placeholder="Inherited Default" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-sm font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Ex-HQ DA Rate (&#8377;)</label>
                        <input type="number" name="da_ex" value="<?= esc($editingUser['da_ex'] ?? '') ?>" placeholder="Inherited Default" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-sm font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Outstation DA Rate (&#8377;)</label>
                        <input type="number" name="da_os" value="<?= esc($editingUser['da_os'] ?? '') ?>" placeholder="Inherited Default" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-sm font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Monthly Target (&#8377;)</label>
                        <input type="number" name="monthly_target" value="<?= esc($editingUser['monthly_target'] ?? '') ?>" placeholder="Split Global Default" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-sm font-semibold">
                    </div>
                </div>
                <div class="territory-assignment-section mt-6 border-t border-slate-100 pt-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-map-location-dot text-indigo-600"></i> Assign Field Territories
                    </h4>
                    <span class="text-xs text-slate-500 font-medium">Select headquarter assignment</span>
                </div>
                
                <div class="mr-territory-container hidden max-w-md">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Primary HQ Area (Exactly One)</label>
                    <div class="relative">
                        <select name="territory_ids[]" class="w-full px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none appearance-none">
                            <option value="">Select HQ...</option>
                            <?php foreach ($territories as $t): ?>
                                <?php if ($t['type'] === 'hq'): ?>
                                    <option value="<?= esc($t['id']) ?>" <?= in_array((int)$t['id'], $assignedTerritoryIds ?? [], true) ? 'selected' : '' ?>><?= esc($t['name']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <div class="manager-territory-container hidden">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">HQ Areas (Select one or more)</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-48 overflow-y-auto p-3 bg-slate-50/60 border border-slate-200 rounded-xl">
                        <?php foreach ($territories as $t): ?>
                            <?php if ($t['type'] === 'hq'): ?>
                                <label class="flex items-center gap-2.5 px-3 py-2 bg-white hover:bg-indigo-50/40 border border-slate-200 hover:border-indigo-200 rounded-lg cursor-pointer text-xs font-semibold text-slate-700 transition-all">
                                    <input type="checkbox" name="territory_ids[]" value="<?= esc($t['id']) ?>" <?= in_array((int)$t['id'], $assignedTerritoryIds ?? [], true) ? 'checked' : '' ?> class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 border-slate-300">
                                    <span><?= esc($t['name']) ?></span>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="owner-territory-container hidden p-4 bg-indigo-50/50 border border-indigo-100 rounded-xl text-xs text-indigo-700 font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-sm"></i>
                    <span>Super Admin has global access to all territories. No territory mapping is required.</span>
                </div>
            </div>          </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="/phadmin/users" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm text-center transition-colors">Cancel</a>
                <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg text-sm shadow-md transition-all">Update Employee</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Users List Panel -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h3 class="text-base font-extrabold text-slate-800">Employee List</h3>
            <p class="text-xs text-slate-500 mt-1">Total active workforce directory. Search by name, role, HQ, or user ID.</p>
        </div>
        <div class="w-full md:w-auto">
            <div class="relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input id="search-users" class="w-full md:w-72 pl-9 pr-4 py-2 bg-white border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-slate-400" placeholder="Search employees...">
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table id="table-users" class="w-full border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 border-b border-slate-100 text-xs font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-4 text-left">ID</th>
                    <th class="py-3.5 px-4 text-left">Full Name</th>
                    <th class="py-3.5 px-4 text-left">User ID</th>
                    <th class="py-3.5 px-4 text-left">Mobile</th>
                    <th class="py-3.5 px-4 text-left">Role</th>
                    <th class="py-3.5 px-4 text-left">Area / HQ</th>
                    <th class="py-3.5 px-4 text-left">Reporting To</th>
                    <th class="py-3.5 px-4 text-center">Status</th>
                    <th class="py-3.5 px-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-user-slash text-4xl mb-2 block"></i>
                            No users registered. Click "Add Employee" to create profiles.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-slate-50/30 transition-colors">
                            <td class="py-4 px-4 text-slate-500 font-mono text-xs"><?= esc($user['id']) ?></td>
                            <td class="py-4 px-4 font-bold text-slate-800"><?= esc($user['name']) ?></td>
                            <td class="py-4 px-4 text-slate-600 font-medium"><?= esc($user['username'] ?? '-') ?></td>
                            <td class="py-4 px-4 text-slate-600"><?= esc($user['mobile'] ?? '-') ?></td>
                            <td class="py-4 px-4">
                                <?php
                                    $role = strtolower((string)($user['role'] ?? ''));
                                    if ($role === 'owner') {
                                        echo '<span class="inline-flex items-center px-2.5 py-1 bg-purple-50 text-purple-700 border border-purple-200 text-xs font-bold rounded-lg">Super Admin</span>';
                                    } elseif ($role === 'manager') {
                                        echo '<span class="inline-flex items-center px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 text-xs font-bold rounded-lg">Manager</span>';
                                    } else {
                                        echo '<span class="inline-flex items-center px-2.5 py-1 bg-teal-50 text-teal-700 border border-teal-200 text-xs font-bold rounded-lg">MR</span>';
                                    }
                                ?>
                            </td>
                            <td class="py-4 px-4 text-slate-600"><?= esc($user['area'] ?? '-') ?></td>
                            <td class="py-4 px-4 text-slate-600">
                                <?php if ((int) ($user['manager_user_id'] ?? 0) > 0): ?>
                                    <?php
                                        $managerName = '-';
                                        foreach ($users as $lookup) {
                                            if ((int) $lookup['id'] === (int) $user['manager_user_id']) {
                                                $managerName = (string) ($lookup['name'] ?? '-');
                                                break;
                                            }
                                        }
                                    ?>
                                    <span class="inline-flex items-center gap-1.5 font-medium text-slate-700">
                                        <i class="fa-solid fa-user text-slate-400 text-xs"></i>
                                        <?= esc($managerName) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400 italic">Direct to Super Admin</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <?php if ((int) $user['is_active'] === 1): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-100 text-xs font-bold rounded-lg">
                                        <i class="fa-solid fa-circle text-[7px] mr-1.5 text-emerald-500"></i>Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-1 bg-slate-50 text-slate-600 border border-slate-200 text-xs font-bold rounded-lg">
                                        <i class="fa-solid fa-circle text-[7px] mr-1.5 text-slate-400"></i>Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="/phadmin/users?edit=<?= esc($user['id']) ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 transition-colors border border-amber-100" title="Edit employee">
                                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                                    </a>
                                    
                                    <form method="post" action="/phadmin/users/toggle/<?= esc($user['id']) ?>" class="inline-block m-0 p-0 border-0 bg-transparent">
                                        <button type="submit" class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg text-xs font-bold border cursor-pointer transition-colors <?= (int) $user['is_active'] === 1 ? 'bg-amber-50 hover:bg-amber-100 border-amber-200 text-amber-700' : 'bg-emerald-50 hover:bg-emerald-100 border-emerald-200 text-emerald-700' ?>">
                                            <?= (int) $user['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>

                                    <form method="post" action="/phadmin/users/delete/<?= esc($user['id']) ?>" class="inline-block m-0 p-0 border-0 bg-transparent" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition-colors border border-red-100 cursor-pointer p-0" title="Delete employee">
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

<!-- Add User Modal Overlay -->
<div id="popup-add-user" class="popup-overlay fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50 transition-opacity duration-300" aria-hidden="true">
    <div class="popup-card bg-white rounded-2xl border border-slate-100 shadow-2xl max-w-2xl w-full max-h-[92vh] flex flex-col transform scale-95 transition-all duration-300 overflow-hidden">
        <div class="popup-head flex items-center justify-between border-b border-slate-100 p-5 bg-gradient-to-r from-slate-50 via-white to-slate-50 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600/10 text-indigo-600 flex items-center justify-center font-bold text-lg shadow-inner">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <div>
                    <h3 class="text-lg font-extrabold text-slate-800 tracking-tight">Add New Employee</h3>
                    <p class="text-xs text-slate-500">Create employee account credentials, assign role, and map territory areas.</p>
                </div>
            </div>
            <button type="button" class="w-8 h-8 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-700 transition-all border-0 cursor-pointer" data-popup-close="popup-add-user">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
        
        <div class="p-6 overflow-y-auto custom-scrollbar flex-grow">
            <form method="post" action="/phadmin/users" class="space-y-6 m-0 border-0 bg-transparent">
                <!-- Section 1: Basic Profile -->
                <div>
                    <h4 class="text-xs font-extrabold text-indigo-600 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                        <i class="fa-solid fa-id-card"></i> Profile & Credentials
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Name <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="fa-solid fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input name="name" required class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none" placeholder="e.g. Rahul Kumar">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Mobile Number <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="fa-solid fa-phone absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input name="mobile" required class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none" placeholder="e.g. 9876543210">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Username (User ID) <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="fa-solid fa-at absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input name="username" required class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none" placeholder="e.g. mr_rahul">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="password" name="password" required class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none" placeholder="••••••••">
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Address (Optional)</label>
                            <div class="relative">
                                <i class="fa-solid fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="email" name="email" class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none" placeholder="e.g. rahul@domain.com">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Role & Hierarchy -->
                <div class="border-t border-slate-100 pt-5">
                    <h4 class="text-xs font-extrabold text-indigo-600 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                        <i class="fa-solid fa-user-gear"></i> Role & Hierarchy Configuration
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Role <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <select name="role" required class="w-full px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none appearance-none">
                                    <option value="mr">MR</option>
                                    <option value="manager">Manager</option>
                                    <option value="owner">Super Admin</option>
                                </select>
                                <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Base Area / HQ Name</label>
                            <div class="relative">
                                <i class="fa-solid fa-building absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input name="area" class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none" placeholder="e.g. Pune">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Reporting Manager</label>
                            <div class="relative">
                                <select name="manager_user_id" class="w-full px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none appearance-none">
                                    <option value="">Vacant (Direct to Super Admin)</option>
                                    <?php foreach ($managers as $manager): ?>
                                        <option value="<?= esc($manager['id']) ?>"><?= esc($manager['name']) ?> (<?= esc($manager['area'] ?? '-') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employee-specific DA & Target Overrides -->
                <div class="border-t border-slate-100 pt-5">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-xs font-extrabold text-indigo-600 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-wallet"></i> Employee-Specific DA & Target Rates (Optional)
                        </h4>
                        <span class="text-[11px] text-slate-400">Overwrites defaults if set</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">HQ DA Rate (&#8377;)</label>
                            <input type="number" name="da_hq" placeholder="Inherited Default" class="w-full px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Ex-HQ DA Rate (&#8377;)</label>
                            <input type="number" name="da_ex" placeholder="Inherited Default" class="w-full px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Outstation DA Rate (&#8377;)</label>
                            <input type="number" name="da_os" placeholder="Inherited Default" class="w-full px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Monthly Target (&#8377;)</label>
                            <input type="number" name="monthly_target" placeholder="Split Global Default" class="w-full px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Territory Assignment -->
                <div class="territory-assignment-section border-t border-slate-100 pt-5">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-xs font-extrabold text-indigo-600 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-map-location-dot"></i> Assign Field Territories
                        </h4>
                        <span class="text-[11px] text-slate-400">Select active working areas</span>
                    </div>
                    
                    <div class="mr-territory-container hidden max-w-md">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Primary HQ Area (Exactly One)</label>
                        <div class="relative">
                            <select name="territory_ids[]" class="w-full px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm font-medium focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none appearance-none">
                                <option value="">Select HQ...</option>
                                <?php foreach ($territories as $t): ?>
                                    <?php if ($t['type'] === 'hq'): ?>
                                        <option value="<?= esc($t['id']) ?>"><?= esc($t['name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <i class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="manager-territory-container hidden">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">HQ Areas (Select one or more)</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-48 overflow-y-auto p-3 bg-slate-50/60 border border-slate-200 rounded-xl">
                            <?php foreach ($territories as $t): ?>
                                <?php if ($t['type'] === 'hq'): ?>
                                    <label class="flex items-center gap-2.5 px-3 py-2 bg-white hover:bg-indigo-50/40 border border-slate-200 hover:border-indigo-200 rounded-lg cursor-pointer text-xs font-semibold text-slate-700 transition-all">
                                        <input type="checkbox" name="territory_ids[]" value="<?= esc($t['id']) ?>" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 border-slate-300">
                                        <span><?= esc($t['name']) ?></span>
                                    </label>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="owner-territory-container hidden p-4 bg-indigo-50/50 border border-indigo-100 rounded-xl text-xs text-indigo-700 font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-sm"></i>
                        <span>Super Admin has global access to all territories. No territory mapping is required.</span>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex justify-end gap-3 pt-5 border-t border-slate-100">
                    <button type="button" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-all border-0 cursor-pointer" data-popup-close="popup-add-user">Cancel</button>
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-sm shadow-lg shadow-indigo-200/50 hover:shadow-indigo-300/60 active:scale-95 transition-all duration-200 border-0 cursor-pointer flex items-center gap-2">
                        <i class="fa-solid fa-plus text-xs"></i> Create Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    bindTableSearch('search-users', 'table-users');
    
    document.addEventListener('DOMContentLoaded', function () {
        // Territory multi-select dropdown handler
        function setupTerritoryDropdowns() {
            document.querySelectorAll('.territory-dropdown-container').forEach(container => {
                const trigger = container.querySelector('.dropdown-trigger');
                const menu = container.querySelector('.dropdown-menu');
                const arrow = container.querySelector('.dropdown-arrow');
                const searchInput = container.querySelector('.dropdown-search');
                const badge = container.querySelector('.selected-count-badge');
                const label = container.querySelector('.dropdown-label');
                const checkboxes = container.querySelectorAll('.territory-checkbox');

                if (!trigger || !menu) return;

                function updateState() {
                    const checked = Array.from(checkboxes).filter(cb => cb.checked);
                    const count = checked.length;
                    
                    if (badge) {
                        badge.textContent = `${count} selected`;
                    }
                    
                    if (label) {
                        const placeholder = container.getAttribute('data-placeholder') || 'Select areas...';
                        if (count === 0) {
                            label.textContent = placeholder;
                            label.classList.add('text-slate-400');
                            label.classList.remove('text-slate-800');
                        } else if (count === 1) {
                            const optionText = checked[0].closest('label').querySelector('.option-text').textContent.trim();
                            label.textContent = optionText;
                            label.classList.remove('text-slate-400');
                            label.classList.add('text-slate-800');
                        } else {
                            label.textContent = `${count} areas selected`;
                            label.classList.remove('text-slate-400');
                            label.classList.add('text-slate-800');
                        }
                    }
                }

                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    // Close other menus
                    document.querySelectorAll('.dropdown-menu').forEach(otherMenu => {
                        if (otherMenu !== menu) {
                            otherMenu.classList.add('hidden');
                            const otherArrow = otherMenu.closest('.territory-dropdown-container')?.querySelector('.dropdown-arrow');
                            if (otherArrow) otherArrow.classList.remove('rotate-180');
                        }
                    });

                    const isOpen = !menu.classList.contains('hidden');
                    if (isOpen) {
                        menu.classList.add('hidden');
                        if (arrow) arrow.classList.remove('rotate-180');
                    } else {
                        menu.classList.remove('hidden');
                        if (arrow) arrow.classList.add('rotate-180');
                        if (searchInput) searchInput.focus();
                    }
                });

                if (searchInput) {
                    searchInput.addEventListener('input', (e) => {
                        const term = e.target.value.toLowerCase().trim();
                        container.querySelectorAll('.option-item').forEach(item => {
                            const text = item.querySelector('.option-text').textContent.toLowerCase();
                            if (text.includes(term)) {
                                item.classList.remove('hidden');
                            } else {
                                item.classList.add('hidden');
                            }
                        });
                    });
                }

                // Prevent scroll/wheel event bubbling to parent overflow container
                const optionsList = container.querySelector('.dropdown-options-list');
                if (optionsList) {
                    optionsList.addEventListener('wheel', (e) => {
                        const scrollTop = optionsList.scrollTop;
                        const scrollHeight = optionsList.scrollHeight;
                        const height = optionsList.clientHeight;
                        const delta = e.deltaY;
                        
                        // Prevent parent scrolling when reaching top/bottom boundaries
                        if ((delta > 0 && scrollTop + height >= scrollHeight) || (delta < 0 && scrollTop <= 0)) {
                            e.preventDefault();
                        }
                    }, { passive: false });

                    optionsList.addEventListener('touchmove', (e) => {
                        // Prevent touch scroll from propagating and scrolling the parent modal
                        e.stopPropagation();
                    }, { passive: true });
                }

                checkboxes.forEach(cb => {
                    cb.addEventListener('change', updateState);
                });

                updateState();
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('.territory-dropdown-container')) {
                    document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.add('hidden'));
                    document.querySelectorAll('.dropdown-arrow').forEach(arrow => arrow.classList.remove('rotate-180'));
                }
            });
        }

        setupTerritoryDropdowns();

        // Sync reporting managers selection depending on role
        function syncReporting(form) {
            const roleSelect = form.querySelector('select[name="role"]');
            const reportingSelect = form.querySelector('select[name="manager_user_id"]');
            const territorySection = form.querySelector('.territory-assignment-section');
            if (!roleSelect || !reportingSelect) {
                return;
            }

            const apply = function () {
                if (roleSelect.value === 'manager' || roleSelect.value === 'owner') {
                    reportingSelect.value = '';
                    reportingSelect.setAttribute('disabled', 'disabled');
                    reportingSelect.classList.add('bg-slate-50', 'text-slate-400', 'cursor-not-allowed');
                } else {
                    reportingSelect.removeAttribute('disabled');
                    reportingSelect.classList.remove('bg-slate-50', 'text-slate-400', 'cursor-not-allowed');
                }

                if (territorySection) {
                    const mrContainer = territorySection.querySelector('.mr-territory-container');
                    const managerContainer = territorySection.querySelector('.manager-territory-container');
                    const ownerContainer = territorySection.querySelector('.owner-territory-container');
                    
                    const mrSelect = mrContainer ? mrContainer.querySelector('select') : null;
                    const managerCheckboxes = managerContainer ? managerContainer.querySelectorAll('input[type="checkbox"]') : [];

                    if (roleSelect.value === 'mr') {
                        mrContainer?.classList.remove('hidden');
                        managerContainer?.classList.add('hidden');
                        ownerContainer?.classList.add('hidden');
                        
                        mrSelect?.removeAttribute('disabled');
                        managerCheckboxes.forEach(cb => cb.setAttribute('disabled', 'disabled'));
                    } else if (roleSelect.value === 'manager') {
                        mrContainer?.classList.add('hidden');
                        managerContainer?.classList.remove('hidden');
                        ownerContainer?.classList.add('hidden');
                        
                        mrSelect?.setAttribute('disabled', 'disabled');
                        managerCheckboxes.forEach(cb => cb.removeAttribute('disabled'));
                    } else {
                        mrContainer?.classList.add('hidden');
                        managerContainer?.classList.add('hidden');
                        ownerContainer?.classList.remove('hidden');
                        
                        mrSelect?.setAttribute('disabled', 'disabled');
                        managerCheckboxes.forEach(cb => cb.setAttribute('disabled', 'disabled'));
                    }
                }
            };

            roleSelect.addEventListener('change', apply);
            apply();
        }

        const addForm = document.querySelector('#popup-add-user form');
        if (addForm) {
            syncReporting(addForm);
        }

        const editForm = document.querySelector('form[action*="/phadmin/users/update/"]');
        if (editForm) {
            syncReporting(editForm);
        }
    });

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
