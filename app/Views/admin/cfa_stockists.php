<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-head flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">CFA & Stockists Directory</h2>
        <p class="text-sm text-slate-500 mt-1">Manage Carry & Forwarding Agents (CFA) and local stockist distribution channels.</p>
    </div>
    <div class="flex items-center gap-3">
        <button type="button" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold rounded-xl text-sm shadow-lg shadow-indigo-100 hover:shadow-xl transition-all duration-200 flex items-center gap-2 border-0 cursor-pointer" data-popup-open="popup-add-cfa">
            <i class="fa-solid fa-plus text-xs"></i> Add CFA
        </button>
        <button type="button" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 active:bg-slate-950 text-white font-bold rounded-xl text-sm shadow-lg shadow-slate-100 hover:shadow-xl transition-all duration-200 flex items-center gap-2 border-0 cursor-pointer" data-popup-open="popup-add-stockist">
            <i class="fa-solid fa-plus text-xs"></i> Add Stockist
        </button>
    </div>
</div>

<!-- Editing Panels Section (Appears only when editing) -->
<div class="grid grid-cols-1 gap-6 mb-8">
    <?php if (! empty($editingCfa)): ?>
    <div class="max-w-4xl">
        <div class="bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden">
            <div class="bg-amber-50/50 px-6 py-4 border-b border-amber-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-user-pen text-amber-600"></i>
                    <h3 class="text-lg font-bold text-slate-800">Edit CFA #<?= esc($editingCfa['id']) ?></h3>
                </div>
                <span class="text-xs font-extrabold px-3 py-1 bg-amber-100 text-amber-800 rounded-full uppercase tracking-wider">Editing CFA</span>
            </div>
            
            <form method="post" action="/phadmin/cfas/update/<?= esc($editingCfa['id']) ?>" class="p-6 space-y-4 m-0 border-0 bg-transparent">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_cfa_name" class="block text-sm font-semibold text-slate-700 mb-1">CFA Name</label>
                        <input id="edit_cfa_name" name="name" value="<?= esc($editingCfa['name']) ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label for="edit_cfa_contact" class="block text-sm font-semibold text-slate-700 mb-1">Contact Person Name</label>
                        <input id="edit_cfa_contact" name="contact_person" value="<?= esc($editingCfa['contact_person'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label for="edit_cfa_mobile" class="block text-sm font-semibold text-slate-700 mb-1">Mobile Number</label>
                        <input id="edit_cfa_mobile" name="mobile" value="<?= esc($editingCfa['mobile'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label for="edit_cfa_email" class="block text-sm font-semibold text-slate-700 mb-1">Email Address</label>
                        <input id="edit_cfa_email" type="email" name="email" value="<?= esc($editingCfa['email'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label for="edit_cfa_gst" class="block text-sm font-semibold text-slate-700 mb-1">GST Number</label>
                        <input id="edit_cfa_gst" name="gst" value="<?= esc($editingCfa['gst'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label for="edit_cfa_state" class="block text-sm font-semibold text-slate-700 mb-1">State</label>
                        <input id="edit_cfa_state" name="state" value="<?= esc($editingCfa['state'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    <div class="md:col-span-2">
                        <label for="edit_cfa_address" class="block text-sm font-semibold text-slate-700 mb-1">Address</label>
                        <textarea id="edit_cfa_address" name="address" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"><?= esc($editingCfa['address'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="/phadmin/cfa-stockists" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm text-center transition-colors">Cancel</a>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg text-sm shadow-md transition-all">Update CFA</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (! empty($editingStockist)): ?>
    <div class="max-w-4xl">
        <div class="bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden">
            <div class="bg-amber-50/50 px-6 py-4 border-b border-amber-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-user-pen text-amber-600"></i>
                    <h3 class="text-lg font-bold text-slate-800">Edit Stockist #<?= esc($editingStockist['id']) ?></h3>
                </div>
                <span class="text-xs font-extrabold px-3 py-1 bg-amber-100 text-amber-800 rounded-full uppercase tracking-wider">Editing Stockist</span>
            </div>
            
            <form method="post" action="/phadmin/stockists/update/<?= esc($editingStockist['id']) ?>" class="p-6 space-y-4 m-0 border-0 bg-transparent">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_stockist_name" class="block text-sm font-semibold text-slate-700 mb-1">Stockist Name</label>
                        <input id="edit_stockist_name" name="name" value="<?= esc($editingStockist['name']) ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label for="edit_stockist_contact" class="block text-sm font-semibold text-slate-700 mb-1">Contact Person Name</label>
                        <input id="edit_stockist_contact" name="contact_person" value="<?= esc($editingStockist['contact_person'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label for="edit_stockist_mobile" class="block text-sm font-semibold text-slate-700 mb-1">Mobile Number</label>
                        <input id="edit_stockist_mobile" name="mobile" value="<?= esc($editingStockist['mobile'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label for="edit_stockist_email" class="block text-sm font-semibold text-slate-700 mb-1">Email Address</label>
                        <input id="edit_stockist_email" type="email" name="email" value="<?= esc($editingStockist['email'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label for="edit_stockist_gst" class="block text-sm font-semibold text-slate-700 mb-1">GST Number</label>
                        <input id="edit_stockist_gst" name="gst" value="<?= esc($editingStockist['gst'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label for="edit_stockist_city" class="block text-sm font-semibold text-slate-700 mb-1">City</label>
                        <input id="edit_stockist_city" name="city" value="<?= esc($editingStockist['city'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label for="edit_stockist_area" class="block text-sm font-semibold text-slate-700 mb-1">Area</label>
                        <input id="edit_stockist_area" name="area" value="<?= esc($editingStockist['area'] ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    <div class="md:col-span-2">
                        <label for="edit_stockist_address" class="block text-sm font-semibold text-slate-700 mb-1">Address</label>
                        <textarea id="edit_stockist_address" name="address" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"><?= esc($editingStockist['address'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="/phadmin/cfa-stockists" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm text-center transition-colors">Cancel</a>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg text-sm shadow-md transition-all">Update Stockist</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 gap-8">
    <!-- CFA Listing Panel -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-800">CFA Directory</h3>
                <p class="text-xs text-slate-500 mt-1 font-medium">Carry & Forwarding Agents coordinating regional distributions.</p>
            </div>
            <div class="w-full md:w-auto">
                <div class="relative">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input id="search-cfas" class="w-full md:w-72 pl-9 pr-4 py-2 bg-white border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-slate-400" placeholder="Fast search CFA...">
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table id="table-cfas" class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 border-b border-slate-100 text-xs font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4 text-left">ID</th>
                        <th class="py-3.5 px-4 text-left">CFA Name</th>
                        <th class="py-3.5 px-4 text-left">Contact Person</th>
                        <th class="py-3.5 px-4 text-left">Mobile</th>
                        <th class="py-3.5 px-4 text-left">Email</th>
                        <th class="py-3.5 px-4 text-left">GST Number</th>
                        <th class="py-3.5 px-4 text-left">State</th>
                        <th class="py-3.5 px-4 text-left">Address</th>
                        <th class="py-3.5 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($cfas)): ?>
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-house-chimney-medical text-4xl mb-2 block"></i>
                                No CFAs registered. Click "Add CFA" to register agents.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cfas as $cfa): ?>
                            <tr class="hover:bg-slate-50/30 transition-colors">
                                <td class="py-4 px-4 font-semibold text-slate-500">#<?= esc($cfa['id']) ?></td>
                                <td class="py-4 px-4 font-bold text-slate-800"><?= esc($cfa['name']) ?></td>
                                <td class="py-4 px-4 text-slate-600 font-medium"><?= esc($cfa['contact_person'] ?: '-') ?></td>
                                <td class="py-4 px-4 text-slate-600"><?= esc($cfa['mobile'] ?: '-') ?></td>
                                <td class="py-4 px-4 text-slate-600"><?= esc($cfa['email'] ?: '-') ?></td>
                                <td class="py-4 px-4 text-slate-600"><span class="font-mono text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded"><?= esc($cfa['gst'] ?: '-') ?></span></td>
                                <td class="py-4 px-4 text-slate-600"><span class="tag"><?= esc($cfa['state'] ?: '-') ?></span></td>
                                <td class="py-4 px-4 text-slate-500 max-w-xs truncate" title="<?= esc($cfa['address'] ?? '') ?>"><?= esc($cfa['address'] ?: '-') ?></td>
                                <td class="py-4 px-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="/phadmin/cfa-stockists?edit_cfa=<?= esc($cfa['id']) ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 transition-colors border border-amber-100" title="Edit CFA">
                                            <i class="fa-solid fa-user-pen text-sm"></i>
                                        </a>
                                        <form method="post" action="/phadmin/cfas/delete/<?= esc($cfa['id']) ?>" class="inline-block m-0 p-0 border-0 bg-transparent" onsubmit="return confirm('Are you sure you want to delete this CFA?');">
                                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition-colors border border-red-100 cursor-pointer p-0" title="Delete CFA">
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

    <!-- Stockist Listing Panel -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-800">Stockists Directory</h3>
                <p class="text-xs text-slate-500 mt-1 font-medium font-medium">Registered warehouse partners and local stockists.</p>
            </div>
            <div class="w-full md:w-auto">
                <div class="relative">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input id="search-stockists" class="w-full md:w-72 pl-9 pr-4 py-2 bg-white border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-slate-400" placeholder="Fast search stockists...">
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table id="table-stockists" class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 border-b border-slate-100 text-xs font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4 text-left">ID</th>
                        <th class="py-3.5 px-4 text-left">Stockist Name</th>
                        <th class="py-3.5 px-4 text-left">Contact Person</th>
                        <th class="py-3.5 px-4 text-left">Mobile</th>
                        <th class="py-3.5 px-4 text-left">Email</th>
                        <th class="py-3.5 px-4 text-left">GST Number</th>
                        <th class="py-3.5 px-4 text-left">City</th>
                        <th class="py-3.5 px-4 text-left">Area</th>
                        <th class="py-3.5 px-4 text-left">Address</th>
                        <th class="py-3.5 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($stockists)): ?>
                        <tr>
                            <td colspan="10" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-boxes-packing text-4xl mb-2 block"></i>
                                No stockists registered. Click "Add Stockist" to expand distribution network.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($stockists as $stockist): ?>
                            <tr class="hover:bg-slate-50/30 transition-colors">
                                <td class="py-4 px-4 font-semibold text-slate-500">#<?= esc($stockist['id']) ?></td>
                                <td class="py-4 px-4 font-bold text-slate-800"><?= esc($stockist['name']) ?></td>
                                <td class="py-4 px-4 text-slate-600 font-medium"><?= esc($stockist['contact_person'] ?: '-') ?></td>
                                <td class="py-4 px-4 text-slate-600"><?= esc($stockist['mobile'] ?: '-') ?></td>
                                <td class="py-4 px-4 text-slate-600"><?= esc($stockist['email'] ?: '-') ?></td>
                                <td class="py-4 px-4 text-slate-600"><span class="font-mono text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded"><?= esc($stockist['gst'] ?: '-') ?></span></td>
                                <td class="py-4 px-4 text-slate-600 font-medium"><?= esc($stockist['city'] ?: '-') ?></td>
                                <td class="py-4 px-4 text-slate-600"><span class="tag bg-indigo-50 border-indigo-100 text-indigo-700"><?= esc($stockist['area'] ?: '-') ?></span></td>
                                <td class="py-4 px-4 text-slate-500 max-w-xs truncate" title="<?= esc($stockist['address'] ?? '') ?>"><?= esc($stockist['address'] ?: '-') ?></td>
                                <td class="py-4 px-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="/phadmin/cfa-stockists?edit_stockist=<?= esc($stockist['id']) ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 transition-colors border border-amber-100" title="Edit stockist">
                                            <i class="fa-solid fa-user-pen text-sm"></i>
                                        </a>
                                        <form method="post" action="/phadmin/stockists/delete/<?= esc($stockist['id']) ?>" class="inline-block m-0 p-0 border-0 bg-transparent" onsubmit="return confirm('Are you sure you want to delete this stockist?');">
                                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition-colors border border-red-100 cursor-pointer p-0" title="Delete stockist">
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
</div>

<!-- Add CFA Modal Overlay -->
<div id="popup-add-cfa" class="popup-overlay fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50 transition-opacity duration-300" aria-hidden="true">
    <div class="popup-card bg-white rounded-2xl border border-slate-100 shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto transform scale-95 transition-all duration-300">
        <div class="popup-head flex items-center justify-between border-b border-slate-100 p-4 pb-3">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-house-chimney-medical text-indigo-600"></i> Add New CFA
            </h3>
            <button type="button" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors border-0 cursor-pointer" data-popup-close="popup-add-cfa">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <form method="post" action="/phadmin/cfas" class="space-y-4 m-0 border-0 bg-transparent">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="cfa_name" class="block text-sm font-semibold text-slate-700 mb-1">CFA Name</label>
                        <input id="cfa_name" name="name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. Ravion Logistics">
                    </div>
                    <div>
                        <label for="cfa_contact" class="block text-sm font-semibold text-slate-700 mb-1">Contact Person Name</label>
                        <input id="cfa_contact" name="contact_person" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. Mr. Rajesh Sharma">
                    </div>
                    <div>
                        <label for="cfa_mobile" class="block text-sm font-semibold text-slate-700 mb-1">Mobile Number</label>
                        <input id="cfa_mobile" name="mobile" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. 9876543210">
                    </div>
                    <div>
                        <label for="cfa_email" class="block text-sm font-semibold text-slate-700 mb-1">Email Address</label>
                        <input id="cfa_email" type="email" name="email" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. contact@ravionlogistics.com">
                    </div>
                    <div>
                        <label for="cfa_gst" class="block text-sm font-semibold text-slate-700 mb-1">GST Number</label>
                        <input id="cfa_gst" name="gst" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. 27AAAAA0000A1Z5">
                    </div>
                    <div>
                        <label for="cfa_state" class="block text-sm font-semibold text-slate-700 mb-1">State</label>
                        <input id="cfa_state" name="state" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. Maharashtra">
                    </div>
                    <div class="md:col-span-2">
                        <label for="cfa_address" class="block text-sm font-semibold text-slate-700 mb-1">Address</label>
                        <textarea id="cfa_address" name="address" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="Enter complete office/warehouse address..."></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm transition-colors border-0 cursor-pointer" data-popup-close="popup-add-cfa">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-sm shadow-md shadow-indigo-100 hover:shadow-lg transition-all duration-200 border-0 cursor-pointer">Add CFA</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Stockist Modal Overlay -->
<div id="popup-add-stockist" class="popup-overlay fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50 transition-opacity duration-300" aria-hidden="true">
    <div class="popup-card bg-white rounded-2xl border border-slate-100 shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto transform scale-95 transition-all duration-300">
        <div class="popup-head flex items-center justify-between border-b border-slate-100 p-4 pb-3">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-boxes-packing text-slate-800"></i> Add New Stockist
            </h3>
            <button type="button" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors border-0 cursor-pointer" data-popup-close="popup-add-stockist">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <form method="post" action="/phadmin/stockists" class="space-y-4 m-0 border-0 bg-transparent">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="stockist_name" class="block text-sm font-semibold text-slate-700 mb-1">Stockist Name</label>
                        <input id="stockist_name" name="name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. Apex Pharma Agencies">
                    </div>
                    <div>
                        <label for="stockist_contact" class="block text-sm font-semibold text-slate-700 mb-1">Contact Person Name</label>
                        <input id="stockist_contact" name="contact_person" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. Mr. Vipul Mehta">
                    </div>
                    <div>
                        <label for="stockist_mobile" class="block text-sm font-semibold text-slate-700 mb-1">Mobile Number</label>
                        <input id="stockist_mobile" name="mobile" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. 9123456789">
                    </div>
                    <div>
                        <label for="stockist_email" class="block text-sm font-semibold text-slate-700 mb-1">Email Address</label>
                        <input id="stockist_email" type="email" name="email" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. sales@apexpharma.com">
                    </div>
                    <div>
                        <label for="stockist_gst" class="block text-sm font-semibold text-slate-700 mb-1">GST Number</label>
                        <input id="stockist_gst" name="gst" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. 27BBBBB0000B1Z5">
                    </div>
                    <div>
                        <label for="stockist_city" class="block text-sm font-semibold text-slate-700 mb-1">City</label>
                        <input id="stockist_city" name="city" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. Pune">
                    </div>
                    <div>
                        <label for="stockist_area" class="block text-sm font-semibold text-slate-700 mb-1">Area</label>
                        <input id="stockist_area" name="area" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. Sadashiv Peth, Pune">
                    </div>
                    <div class="md:col-span-2">
                        <label for="stockist_address" class="block text-sm font-semibold text-slate-700 mb-1">Address</label>
                        <textarea id="stockist_address" name="address" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="Enter complete office/godown address..."></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm transition-colors border-0 cursor-pointer" data-popup-close="popup-add-stockist">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold rounded-lg text-sm shadow-md transition-all duration-200 border-0 cursor-pointer">Add Stockist</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    bindTableSearch('search-cfas', 'table-cfas');
    bindTableSearch('search-stockists', 'table-stockists');

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
