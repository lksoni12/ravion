<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-head flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Territory Management</h2>
        <p class="text-sm text-slate-500 mt-1">Super Admin-only access. Organize states, HQs, Ex-Stations, and Out-Stations (OS).</p>
    </div>
    <button type="button" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold rounded-xl text-sm shadow-lg shadow-indigo-100 hover:shadow-xl transition-all duration-200 flex items-center gap-2 border-0 cursor-pointer" data-popup-open="popup-add-hq">
        <i class="fa-solid fa-plus text-xs"></i> Add HQ
    </button>
</div>

<?php
// Group territories
$states = [];
$hqs = [];
$stations = []; // child stations grouped by hq_id

foreach ($territories as $t) {
    if ($t['type'] === 'state') {
        $states[$t['id']] = $t;
    } elseif ($t['type'] === 'hq') {
        $hqs[$t['id']] = $t;
    } else {
        $stations[$t['parent_id']][] = $t;
    }
}
?>

<!-- Territories List Panel -->
<?php if (empty($states)): ?>
    <div class="py-12 text-center text-slate-400 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <i class="fa-solid fa-map-location text-5xl mb-3 block text-slate-300"></i>
        <h3 class="text-base font-bold text-slate-700">No Territories Found</h3>
        <p class="text-xs text-slate-400 mt-1">Click "+ Add HQ" to register a State and Headquarter.</p>
    </div>
<?php else: ?>
    <?php foreach ($states as $stateId => $state): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-map text-slate-500"></i>
                    <h3 class="text-base font-extrabold text-slate-800"><?= esc($state['name']) ?> (State)</h3>
                </div>
                <!-- State Actions -->
                <div class="flex items-center gap-2">
                    <button type="button" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-255 text-slate-700 border border-slate-250 rounded-lg text-xs font-bold transition-all cursor-pointer" 
                            onclick="openEditStateModal(<?= esc(json_encode($state)) ?>)">
                        Rename
                    </button>
                    <form method="post" action="/phadmin/territories/delete/<?= esc($state['id']) ?>" class="inline-block m-0 p-0 border-0 bg-transparent" onsubmit="return confirm('Are you sure you want to delete this State? All child HQs and stations will be deleted.');">
                        <button type="submit" class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-650 border border-red-100 rounded-lg text-xs font-bold transition-all cursor-pointer">
                            Delete State
                        </button>
                    </form>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-50/30 text-slate-655 border-b border-slate-100 text-xs font-bold uppercase tracking-wider">
                            <th class="py-3 px-4 text-left w-1/3">HQ Name</th>
                            <th class="py-3 px-4 text-left">Ex-Stations</th>
                            <th class="py-3 px-4 text-left">Out-Stations (OS)</th>
                            <th class="py-3 px-4 text-center w-40">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php
                        $stateHqs = array_filter($hqs, function($h) use ($stateId) {
                            return (int)$h['parent_id'] === (int)$stateId;
                        });
                        if (empty($stateHqs)):
                        ?>
                            <tr>
                                <td colspan="4" class="py-6 px-4 text-center text-slate-400 text-xs">No HQs added in this State yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stateHqs as $hqId => $hq):
                                $hqStations = $stations[$hqId] ?? [];
                                $exCount = count(array_filter($hqStations, function($st) { return $st['type'] === 'ex-station'; }));
                                $osCount = count(array_filter($hqStations, function($st) { return $st['type'] === 'out-station'; }));
                            ?>
                                <tr class="hover:bg-slate-50/20 transition-colors">
                                    <td class="py-4 px-4 font-bold text-slate-800"><?= esc($hq['name']) ?></td>
                                    <td class="py-4 px-4">
                                        <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 text-xs font-bold rounded border border-indigo-100"><?= $exCount ?> Ex-Stations</span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="px-2 py-0.5 bg-amber-50 text-amber-700 text-xs font-bold rounded border border-amber-100"><?= $osCount ?> Out-Stations</span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Manage Button -->
                                            <button type="button" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1 border-0 cursor-pointer" 
                                                    onclick="openManageHq(<?= esc(json_encode($hq)) ?>, <?= esc(json_encode($hqStations)) ?>)">
                                                <i class="fa-solid fa-sliders text-[10px]"></i> Manage
                                            </button>
                                            <form method="post" action="/phadmin/territories/delete/<?= esc($hq['id']) ?>" class="inline-block m-0 p-0 border-0 bg-transparent" onsubmit="return confirm('Delete this HQ? All its Ex-stations and Out-stations will also be deleted.');">
                                                <button type="submit" class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-red-50 hover:bg-red-100 text-red-655 transition-colors border border-red-100 cursor-pointer p-0" title="Delete HQ">
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
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
    <?php endforeach; ?>
<?php endif; ?>

<!-- + Add HQ Modal -->
<div id="popup-add-hq" class="popup-overlay fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50 transition-opacity duration-300" aria-hidden="true">
    <div class="popup-card bg-white rounded-2xl border border-slate-100 shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto transform scale-95 transition-all duration-300">
        <div class="popup-head flex items-center justify-between border-b border-slate-100 p-4 pb-3">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-map-location-dot text-indigo-600"></i> Add Headquarter (HQ)
            </h3>
            <button type="button" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors border-0 cursor-pointer" data-popup-close="popup-add-hq">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <form method="post" action="/phadmin/territories" class="space-y-4 m-0 border-0 bg-transparent" id="add-hq-form">
                <input type="hidden" name="type" value="hq">
                <input type="hidden" name="state_mode" id="add-hq-state-mode" value="existing">
                
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-semibold text-slate-700">State Location</label>
                        <!-- Mode Toggle Button -->
                        <button type="button" id="btn-toggle-state-mode" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold border-0 bg-transparent cursor-pointer flex items-center gap-1" onclick="toggleStateMode()">
                            <i class="fa-solid fa-plus text-[9px]"></i> Add New State
                        </button>
                    </div>
                    
                    <!-- Existing States Selector -->
                    <div id="container-existing-state" class="relative">
                        <select name="state_id" id="add-hq-state-select" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors bg-white text-slate-800">
                            <option value="">Select State...</option>
                            <?php foreach ($states as $s): ?>
                                <option value="<?= esc($s['id']) ?>"><?= esc($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- New State input -->
                    <div id="container-new-state" class="hidden">
                        <input name="new_state_name" id="add-hq-new-state-input" class="w-full px-3 py-2 border border-slate-350 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors text-slate-800 font-semibold" placeholder="e.g. Maharashtra">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">HQ Name</label>
                    <input name="name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors text-slate-800 font-semibold" placeholder="e.g. Pune HQ">
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm transition-colors border-0 cursor-pointer" data-popup-close="popup-add-hq">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-sm shadow-md transition-all border-0 cursor-pointer">Save HQ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rename State Modal -->
<div id="popup-edit-state" class="popup-overlay fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50 transition-opacity duration-300" aria-hidden="true">
    <div class="popup-card bg-white rounded-2xl border border-slate-100 shadow-2xl max-w-sm w-full transform scale-95 transition-all duration-300">
        <div class="popup-head flex items-center justify-between border-b border-slate-100 p-4 pb-3">
            <h3 class="text-sm font-bold text-slate-800">Rename State</h3>
            <button type="button" class="w-6 h-6 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 transition-colors border-0 cursor-pointer" data-popup-close="popup-edit-state">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>
        <div class="p-5">
            <form method="post" action="" id="edit-state-form" class="space-y-4 m-0 border-0 p-0">
                <input type="hidden" name="type" value="state">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">State Name</label>
                    <input name="name" id="edit-state-input-name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 font-semibold">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs border-0 cursor-pointer" data-popup-close="popup-edit-state">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs border-0 cursor-pointer shadow-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Manage HQ Overlay Modal -->
<div id="popup-manage-hq" class="popup-overlay fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 z-40 transition-opacity duration-300" aria-hidden="true">
    <div class="popup-card bg-white rounded-2xl border border-slate-100 shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto transform scale-95 transition-all duration-300">
        <div class="popup-head flex items-center justify-between border-b border-slate-100 p-4 pb-3">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-sliders text-indigo-600 text-base"></i> Manage HQ: <span id="manage-hq-name" class="text-indigo-600">Pune HQ</span>
            </h3>
            <button type="button" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors border-0 cursor-pointer" onclick="closeManageHq()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-6">
            
            <!-- Quick Edit HQ Name / State -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/60">
                <h4 class="text-xs font-extrabold text-slate-705 uppercase tracking-wider mb-3">HQ Settings</h4>
                <form method="post" action="" id="manage-hq-settings-form" class="grid grid-cols-1 sm:grid-cols-3 gap-3 m-0 border-0 p-0 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Rename HQ</label>
                        <input name="name" id="manage-hq-input-name" required class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-semibold text-slate-850">
                        <input type="hidden" name="type" value="hq">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">State Parent</label>
                        <select name="parent_id" id="manage-hq-input-parent" required class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-semibold text-slate-855 text-slate-800">
                            <!-- Populated in JS -->
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="w-full px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-bold transition-all border-0 cursor-pointer shadow-sm">
                            Update HQ Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- List of Mapped Areas (Ex-Station / Outstation) -->
            <div>
                <h4 class="text-xs font-extrabold text-slate-705 uppercase tracking-wider mb-3">Mapped Stations (Ex-Stations & Outstations)</h4>
                <div class="overflow-x-auto border border-slate-200 rounded-xl max-h-64 overflow-y-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 border-b border-slate-200 text-[10px] font-bold uppercase tracking-wider">
                                <th class="py-2 px-3 text-left w-1/2">Area Name</th>
                                <th class="py-2 px-3 text-left">Type</th>
                                <th class="py-2 px-3 text-center w-40">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="manage-hq-stations-body" class="divide-y divide-slate-100 text-xs">
                            <!-- Dynamic Rows in JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Area Form under this HQ -->
            <div class="border-t border-slate-150 pt-5">
                <h4 class="text-xs font-extrabold text-indigo-600 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-plus"></i> Add New Area under this HQ
                </h4>
                <form method="post" action="/phadmin/territories" class="grid grid-cols-1 sm:grid-cols-3 gap-3 m-0 border-0 p-0 items-end">
                    <input type="hidden" name="parent_id" id="add-area-parent-id">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Area Name</label>
                        <input name="name" required placeholder="e.g. Deesa" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-250 rounded-lg text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-850 font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Area Type</label>
                        <select name="type" required class="w-full px-3 py-1.5 bg-slate-50 border border-slate-250 rounded-lg text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-800 font-semibold">
                            <option value="ex-station">Ex-Station</option>
                            <option value="out-station">Out-Station (OS)</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-md shadow-indigo-100 transition-all border-0 cursor-pointer">
                            Add Area
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Area Modal (Double Modal friendly z-index 60) -->
<div id="popup-edit-area" class="popup-overlay fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 z-[60] transition-opacity duration-300" aria-hidden="true">
    <div class="popup-card bg-white rounded-2xl border border-slate-100 shadow-2xl max-w-sm w-full transform scale-95 transition-all duration-300">
        <div class="popup-head flex items-center justify-between border-b border-slate-100 p-4 pb-3">
            <h3 class="text-sm font-bold text-slate-800">Edit Area</h3>
            <button type="button" class="w-6 h-6 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 transition-colors border-0 cursor-pointer" onclick="closeEditArea()">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>
        <div class="p-5">
            <form method="post" action="" id="edit-area-form" class="space-y-4 m-0 border-0 p-0">
                <input type="hidden" name="parent_id" id="edit-area-parent-id">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Area Name</label>
                    <input name="name" id="edit-area-input-name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-850 font-semibold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Area Type</label>
                    <select name="type" id="edit-area-input-type" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-slate-800 font-semibold">
                        <option value="ex-station">Ex-Station</option>
                        <option value="out-station">Out-Station (OS)</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs border-0 cursor-pointer" onclick="closeEditArea()">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs border-0 cursor-pointer shadow-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Move Area Modal (Double Modal friendly z-index 60) -->
<div id="popup-move-area" class="popup-overlay fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 z-[60] transition-opacity duration-300" aria-hidden="true">
    <div class="popup-card bg-white rounded-2xl border border-slate-100 shadow-2xl max-w-sm w-full transform scale-95 transition-all duration-300">
        <div class="popup-head flex items-center justify-between border-b border-slate-100 p-4 pb-3">
            <h3 class="text-sm font-bold text-slate-800">Move Area to Another HQ</h3>
            <button type="button" class="w-6 h-6 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 transition-colors border-0 cursor-pointer" onclick="closeMoveArea()">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>
        <div class="p-5">
            <form method="post" action="" id="move-area-form" class="space-y-4 m-0 border-0 p-0">
                <input type="hidden" name="name" id="move-area-input-name">
                <input type="hidden" name="type" id="move-area-input-type">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Select Target HQ</label>
                    <select name="parent_id" id="move-area-input-parent" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-slate-855 text-slate-800 font-semibold">
                        <!-- Populated with other HQs in JS -->
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs border-0 cursor-pointer" onclick="closeMoveArea()">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs border-0 cursor-pointer shadow-sm">Move Area</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const allTerritories = <?= json_encode($territories) ?>;

    // Toggle mode between Selecting Existing State vs. Creating New State
    function toggleStateMode() {
        const modeInput = document.getElementById('add-hq-state-mode');
        const existingContainer = document.getElementById('container-existing-state');
        const newContainer = document.getElementById('container-new-state');
        const selectEl = document.getElementById('add-hq-state-select');
        const inputEl = document.getElementById('add-hq-new-state-input');
        const toggleBtn = document.getElementById('btn-toggle-state-mode');

        if (modeInput.value === 'existing') {
            modeInput.value = 'new';
            existingContainer.classList.add('hidden');
            newContainer.classList.remove('hidden');
            selectEl.removeAttribute('required');
            inputEl.setAttribute('required', 'required');
            inputEl.focus();
            toggleBtn.innerHTML = `<i class="fa-solid fa-chevron-left text-[9px]"></i> Select Existing State`;
        } else {
            modeInput.value = 'existing';
            existingContainer.classList.remove('hidden');
            newContainer.classList.add('hidden');
            inputEl.removeAttribute('required');
            selectEl.setAttribute('required', 'required');
            toggleBtn.innerHTML = `<i class="fa-solid fa-plus text-[9px]"></i> Add New State`;
        }
    }

    // Rename State Modal handler
    function openEditStateModal(state) {
        const modal = document.getElementById('popup-edit-state');
        const form = document.getElementById('edit-state-form');
        form.action = '/phadmin/territories/update/' + state.id;
        document.getElementById('edit-state-input-name').value = state.name;

        modal.classList.add('flex');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.add('opacity-100');
            modal.querySelector('.popup-card').classList.remove('scale-95');
            modal.querySelector('.popup-card').classList.add('scale-100');
        }, 50);
    }

    // Manage HQ Panel handler
    function openManageHq(hq, hqStations) {
        document.getElementById('manage-hq-name').textContent = hq.name;
        document.getElementById('manage-hq-input-name').value = hq.name;
        document.getElementById('add-area-parent-id').value = hq.id;
        
        const settingsForm = document.getElementById('manage-hq-settings-form');
        settingsForm.action = '/phadmin/territories/update/' + hq.id;

        // Populate parent states select in HQ settings
        const parentSelect = document.getElementById('manage-hq-input-parent');
        parentSelect.innerHTML = '';
        const states = allTerritories.filter(t => t.type === 'state');
        states.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name;
            if (parseInt(s.id) === parseInt(hq.parent_id)) {
                opt.selected = true;
            }
            parentSelect.appendChild(opt);
        });

        // Render child stations list
        const tbody = document.getElementById('manage-hq-stations-body');
        tbody.innerHTML = '';
        if (!hqStations || hqStations.length === 0) {
            tbody.innerHTML = `<tr><td colspan="3" class="py-4 text-center text-slate-400">No areas added under this HQ yet.</td></tr>`;
        } else {
            hqStations.forEach(st => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50/50 transition-all';
                
                const typeLabel = st.type === 'ex-station' ? 'Ex-Station' : 'Out-Station (OS)';
                const typeClass = st.type === 'ex-station' ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700';
                
                // Escape quotes for json safe onclick string
                const stEscaped = JSON.stringify(st).replace(/"/g, '&quot;');
                
                tr.innerHTML = `
                    <td class="py-2.5 px-3 font-semibold text-slate-800">${st.name}</td>
                    <td class="py-2.5 px-3">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold ${typeClass}">${typeLabel}</span>
                    </td>
                    <td class="py-2.5 px-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button type="button" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded text-[10px] border-0 cursor-pointer transition-all" onclick="openEditAreaModal(${stEscaped})">Edit</button>
                            <button type="button" class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded text-[10px] border-0 cursor-pointer transition-all" onclick="openMoveAreaModal(${stEscaped})">Move</button>
                            <form method="post" action="/phadmin/territories/delete/${st.id}" class="inline-block m-0 p-0 border-0 bg-transparent" onsubmit="return confirm('Are you sure you want to delete this area?');">
                                <button type="submit" class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-650 font-bold rounded text-[10px] border-0 cursor-pointer transition-all">Delete</button>
                            </form>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        const modal = document.getElementById('popup-manage-hq');
        modal.classList.add('flex');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.add('opacity-100');
            modal.querySelector('.popup-card').classList.remove('scale-95');
            modal.querySelector('.popup-card').classList.add('scale-100');
        }, 50);
    }

    function closeManageHq() {
        const modal = document.getElementById('popup-manage-hq');
        modal.classList.remove('opacity-100');
        modal.querySelector('.popup-card').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 200);
    }

    // Edit Child Area
    function openEditAreaModal(st) {
        const modal = document.getElementById('popup-edit-area');
        const form = document.getElementById('edit-area-form');
        form.action = '/phadmin/territories/update/' + st.id;
        document.getElementById('edit-area-input-name').value = st.name;
        document.getElementById('edit-area-input-type').value = st.type;
        document.getElementById('edit-area-parent-id').value = st.parent_id;

        modal.classList.add('flex');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.add('opacity-100');
            modal.querySelector('.popup-card').classList.remove('scale-95');
            modal.querySelector('.popup-card').classList.add('scale-100');
        }, 50);
    }

    function closeEditArea() {
        const modal = document.getElementById('popup-edit-area');
        modal.classList.remove('opacity-100');
        modal.querySelector('.popup-card').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 200);
    }

    // Move Child Area to Another HQ
    function openMoveAreaModal(st) {
        const modal = document.getElementById('popup-move-area');
        const form = document.getElementById('move-area-form');
        form.action = '/phadmin/territories/update/' + st.id;
        document.getElementById('move-area-input-name').value = st.name;
        document.getElementById('move-area-input-type').value = st.type;

        const parentSelect = document.getElementById('move-area-input-parent');
        parentSelect.innerHTML = '';

        // Filter out other HQs
        const otherHqs = allTerritories.filter(t => t.type === 'hq' && parseInt(t.id) !== parseInt(st.parent_id));
        otherHqs.forEach(hq => {
            const opt = document.createElement('option');
            opt.value = hq.id;
            opt.textContent = hq.name;
            parentSelect.appendChild(opt);
        });

        if (otherHqs.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'No other HQs available';
            parentSelect.appendChild(opt);
        }

        modal.classList.add('flex');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.add('opacity-100');
            modal.querySelector('.popup-card').classList.remove('scale-95');
            modal.querySelector('.popup-card').classList.add('scale-100');
        }, 50);
    }

    function closeMoveArea() {
        const modal = document.getElementById('popup-move-area');
        modal.classList.remove('opacity-100');
        modal.querySelector('.popup-card').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 200);
    }

    // Smooth modal animation triggers for standard data-popup elements
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
