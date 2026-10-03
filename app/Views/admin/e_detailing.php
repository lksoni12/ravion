<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $planDate       = (string) ($planDate ?? date('Y-m-d'));
    $scheduleDoctors = (array) ($scheduleDoctors ?? []);
    $scheduleChemists = (array) ($scheduleChemists ?? []);
    $doctorOptions  = (array) ($doctorOptions ?? []);
    $chemistOptions = (array) ($chemistOptions ?? []);
    $productOptions = (array) ($productOptions ?? []);
    $userName       = (string) (session()->get('user_name') ?? 'MR');
    $userInitials   = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $userName), 0, 2))));
?>

<!-- Page Header -->
<div class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6 mb-6 rounded-lg shadow-sm">
    <div class="flex items-center gap-4">
        <h1 class="text-xl font-bold text-gray-900">E-Detailing Schedule</h1>
        <div class="flex items-center gap-2 bg-amber-50 text-amber-700 border border-amber-200 px-3 py-1 rounded-full text-xs font-medium">
            <i class="fa-solid fa-rotate text-xs"></i>
            Tour Plan Synced (<?= esc($planDate) ?>)
        </div>
    </div>
    <div class="flex items-center gap-3">
        <span class="text-sm text-gray-600 font-medium"><?= esc($userName) ?></span>
        <div class="w-8 h-8 rounded-full bg-teal-600 text-white flex items-center justify-center font-bold text-sm"><?= esc($userInitials) ?></div>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
        <i class="fas fa-check-circle mr-2"></i><?= esc(session()->getFlashdata('success')) ?>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
        <i class="fas fa-exclamation-circle mr-2"></i><?= esc(session()->getFlashdata('error')) ?>
    </div>
<?php endif; ?>

<!-- Action Bar -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-4 rounded-xl border border-gray-200 shadow-sm mb-6">
    <div class="flex flex-wrap gap-2">
        <button type="button" onclick="openModal('doctorModal')"
                class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 cursor-pointer transition border-0">
            <i class="fa-solid fa-user-doctor"></i> Add Doctor
        </button>
        <button type="button" onclick="openModal('chemistModal')"
                class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 cursor-pointer transition border-0">
            <i class="fa-solid fa-mortar-pestle"></i> Add Chemist
        </button>
    </div>

    <!-- Bulk delete (hidden until rows checked) -->
    <div id="bulkActions" class="hidden items-center gap-2">
        <span class="text-sm text-gray-500 font-medium" id="selectedCount">0 selected</span>
        <form method="post" action="/phadmin/e-detailing/delete-doctors"
              class="inline m-0 border-0 bg-transparent p-0"
              onsubmit="return fillBulkIds()">
            <input type="hidden" name="plan_date" value="<?= esc($planDate) ?>">
            <input type="hidden" id="bulk-ids-input" name="selected_doctors[]" value="">
            <button type="submit"
                    class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-3 py-2 rounded-lg text-sm font-semibold flex items-center gap-1 cursor-pointer transition">
                <i class="fa-solid fa-trash-can"></i> Delete Selected
            </button>
        </form>
    </div>
</div>

<!-- Doctor Schedule Table -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
        <h3 class="font-bold text-gray-800 flex items-center gap-2 m-0">
            <i class="fa-solid fa-list-check text-teal-600"></i>
            Today's Doctor List
            <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full font-normal" id="totalDocCount">
                <?= count($scheduleDoctors) ?> <?= count($scheduleDoctors) === 1 ? 'Doctor' : 'Doctors' ?>
            </span>
        </h3>
        <div class="flex items-center gap-3">
            <span class="text-xs text-gray-400 italic hidden sm:inline">No manual entry needed for Tour Plan doctors</span>
            <input type="date" id="ed-date-selector" value="<?= esc($planDate) ?>"
                   onchange="handleDateChange()"
                   class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 outline-none m-0">
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-gray-200">
                    <th class="py-3 px-6 w-12">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"
                               class="rounded border-gray-300 accent-teal-600">
                    </th>
                    <th class="py-3 px-6">Doctor Details</th>
                    <th class="py-3 px-6">Specialty</th>
                    <th class="py-3 px-6">Assigned Products</th>
                    <th class="py-3 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="doctorTableBody" class="divide-y divide-gray-100 text-sm">
                <?php foreach ($scheduleDoctors as $doc): ?>
                    <?php
                        $docId     = (int) ($doc['id'] ?? 0);
                        $docName   = (string) ($doc['doctor_name'] ?? 'Unknown');
                        $docSpec   = (string) ($doc['doctor_speciality'] ?? 'General');
                        $source    = ucfirst((string) ($doc['source'] ?? 'manual'));
                        $hasSession = ! empty($doc['active_session']);
                        $sessionId  = $hasSession ? (int) $doc['active_session']['id'] : 0;

                        $specColors = [
                            'Pediatrician' => 'bg-blue-50 text-blue-700',
                            'Gynecologist' => 'bg-purple-50 text-purple-700',
                            'Cardiologist' => 'bg-amber-50 text-amber-700',
                            'General Physician' => 'bg-indigo-50 text-indigo-700',
                            'Oncologist' => 'bg-red-50 text-red-700',
                        ];
                        $specClass = $specColors[$docSpec] ?? 'bg-gray-100 text-gray-700';
                    ?>
                    <tr class="hover:bg-gray-50 transition" data-id="<?= $docId ?>">
                        <td class="py-4 px-6">
                            <input type="checkbox" class="doc-checkbox rounded border-gray-300 accent-teal-600"
                                   onchange="updateBulkState()">
                        </td>
                        <td class="py-4 px-6">
                            <div class="font-semibold text-gray-900"><?= esc($docName) ?></div>
                            <?php
                                $sourceClass = ($source === 'Tour_plan' || $source === 'tour_plan') ? 'text-gray-400' : 'text-emerald-600';
                                if ($source === 'tour_plan') {
                                    $sourceLabel = 'Tour Plan Sync';
                                } elseif ($source === 'manual') {
                                    $sourceLabel = 'Manual Addition';
                                } else {
                                    $sourceLabel = $source;
                                }
                            ?>
                            <div class="text-xs <?= esc($sourceClass) ?>"><?= esc($sourceLabel) ?></div>
                        </td>
                        <td class="py-4 px-6">
                            <span class="<?= esc($specClass) ?> px-2.5 py-1 rounded-md text-xs font-medium">
                                <?= esc($docSpec) ?>
                            </span>
                        </td>
                        <td class="py-4 px-6" id="prod-list-<?= $docId ?>">
                            <span class="text-gray-400 text-xs">No products assigned</span>
                        </td>
                        <td class="py-4 px-6 text-right space-x-2">
                            <?php if ($hasSession): ?>
                                <a href="/phadmin/e-detailing/presentation/<?= $sessionId ?>"
                                   class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-md font-medium text-xs transition inline-flex items-center gap-1">
                                    <i class="fa-solid fa-play"></i> Resume Call
                                </a>
                            <?php else: ?>
                                <button type="button"
                                        onclick="openProductModal(<?= $docId ?>)"
                                        class="text-teal-600 hover:text-teal-800 bg-teal-50 hover:bg-teal-100 px-2.5 py-1.5 rounded-md font-medium text-xs cursor-pointer transition border-0">
                                    <i class="fa-solid fa-plus-square"></i> Add Product
                                </button>
                                <button type="button"
                                        id="btn-start-<?= $docId ?>"
                                        onclick="startCall(<?= $docId ?>, '<?= esc(addslashes($docName)) ?>')"
                                        disabled
                                        class="disabled:opacity-40 disabled:cursor-not-allowed bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-md font-medium text-xs cursor-pointer transition border-0">
                                    <i class="fa-solid fa-play"></i> Start Call
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($scheduleDoctors)): ?>
            <div class="text-center py-16 text-gray-400">
                <i class="fas fa-stethoscope text-5xl mb-4 block"></i>
                <p class="text-sm font-medium">No doctors scheduled for <?= esc($planDate) ?></p>
                <p class="text-xs mt-1">Add a doctor manually or sync from Tour Plan</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ─── MODALS ─────────────────────────────────── -->

<!-- Add Doctor Modal -->
<div id="doctorModal" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-gray-900">Select Doctor from Database</h3>
            <button type="button" onclick="closeModal('doctorModal')"
                    class="text-gray-400 hover:text-gray-600 text-xl cursor-pointer border-0 bg-transparent leading-none">&times;</button>
        </div>
        <div class="p-4 space-y-3">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                <input type="text" id="searchDocInput" onkeyup="filterDoctors()"
                       placeholder="Search doctor by name or specialty…"
                       class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 m-0">
            </div>
            <div class="max-h-64 overflow-y-auto divide-y divide-gray-100 border border-gray-200 rounded-lg" id="docList">
                <?php foreach ($doctorOptions as $doc): ?>
                    <button type="button"
                            class="w-full p-3 flex justify-between items-center hover:bg-gray-50 cursor-pointer doc-list-item text-left border-0 bg-transparent border-b border-gray-100 last:border-0"
                            onclick="submitAddDoctor(<?= (int) $doc['id'] ?>)">
                        <div>
                            <div class="font-medium text-sm text-gray-900"><?= esc($doc['name']) ?></div>
                            <div class="text-xs text-gray-500"><?= esc($doc['speciality'] ?? 'General') ?></div>
                        </div>
                        <i class="fa-solid fa-plus text-teal-600 text-sm" aria-hidden="true"></i>
                    </button>
                <?php endforeach; ?>
                <?php if (empty($doctorOptions)): ?>
                    <div class="p-4 text-center text-gray-400 text-sm">No doctors found in database</div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Hidden form to POST add-doctor -->
        <form id="add-doctor-form" method="post" action="/phadmin/e-detailing/add-doctor"
              class="hidden m-0 border-0 p-0 bg-transparent">
            <input type="hidden" name="plan_date"  value="<?= esc($planDate) ?>">
            <input type="hidden" name="doctor_id" id="add-doctor-id" value="">
        </form>
    </div>
</div>

<!-- Add Chemist Modal -->
<div id="chemistModal" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-gray-900">Select Chemist from Database</h3>
            <button type="button" onclick="closeModal('chemistModal')"
                    class="text-gray-400 hover:text-gray-600 text-xl cursor-pointer border-0 bg-transparent leading-none">&times;</button>
        </div>
        <div class="p-4 space-y-3">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                <input type="text" id="searchChemistInput" onkeyup="filterChemists()"
                       placeholder="Search chemist by name…"
                       class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 m-0">
            </div>
            <div class="max-h-64 overflow-y-auto divide-y divide-gray-100 border border-gray-200 rounded-lg" id="chemistList">
                <?php foreach ($chemistOptions as $chem): ?>
                    <button type="button"
                            class="w-full p-3 flex justify-between items-center hover:bg-gray-50 cursor-pointer chemist-list-item text-left border-0 bg-transparent border-b border-gray-100 last:border-0"
                            onclick="submitAddChemist(<?= (int) $chem['id'] ?>)">
                        <div>
                            <div class="font-medium text-sm text-gray-900"><?= esc($chem['name']) ?></div>
                            <div class="text-xs text-gray-500"><?= esc($chem['area'] ?? '') ?></div>
                        </div>
                        <i class="fa-solid fa-plus text-teal-600 text-sm" aria-hidden="true"></i>
                    </button>
                <?php endforeach; ?>
                <?php if (empty($chemistOptions)): ?>
                    <div class="p-4 text-center text-gray-400 text-sm">No chemists found in database</div>
                <?php endif; ?>
            </div>
        </div>
        <form id="add-chemist-form" method="post" action="/phadmin/e-detailing/add-chemist"
              class="hidden m-0 border-0 p-0 bg-transparent">
            <input type="hidden" name="plan_date"   value="<?= esc($planDate) ?>">
            <input type="hidden" name="chemist_id" id="add-chemist-id" value="">
        </form>
    </div>
</div>

<!-- Product Assignment Modal -->
<div id="productModal" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center p-4 z-50">
    <div class="bg-white rounded-xl shadow-xl max-w-sm w-full overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-gray-900">Assign Products for Detailing</h3>
            <button type="button" onclick="closeModal('productModal')"
                    class="text-gray-400 hover:text-gray-600 text-xl cursor-pointer border-0 bg-transparent leading-none">&times;</button>
        </div>
        <div class="p-4 space-y-3 max-h-64 overflow-y-auto" id="productOptionsContainer">
            <?php foreach ($productOptions as $product): ?>
                <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg border border-gray-100 cursor-pointer">
                    <input type="checkbox"
                           value="<?= esc($product['id']) ?>"
                           data-name="<?= esc($product['name']) ?>"
                           class="prod-select-box rounded text-teal-600 accent-teal-600">
                    <div>
                        <div class="font-medium text-sm"><?= esc($product['name']) ?></div>
                        <div class="text-xs text-gray-400"><?= esc($product['description'] ?? '') ?></div>
                    </div>
                </label>
            <?php endforeach; ?>
            <?php if (empty($productOptions)): ?>
                <p class="text-gray-400 text-sm text-center py-4">No products available</p>
            <?php endif; ?>
        </div>
        <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2">
            <button type="button" onclick="closeModal('productModal')"
                    class="px-3 py-1.5 text-xs font-semibold text-gray-500 hover:bg-gray-200 rounded-md cursor-pointer border-0 bg-transparent">Cancel</button>
            <button type="button" onclick="saveProductAssignment()"
                    class="px-4 py-1.5 text-xs font-semibold text-white bg-teal-600 hover:bg-teal-700 rounded-md cursor-pointer border-0">Save Changes</button>
        </div>
    </div>
</div>

<!-- Start Call hidden form -->
<form id="start-call-form" method="post" action="/phadmin/e-detailing/start-call"
      class="hidden m-0 border-0 p-0 bg-transparent">
    <input type="hidden" name="plan_date"             value="<?= esc($planDate) ?>">
    <input type="hidden" name="e_detailing_doctor_id" id="start-call-doc-id" value="">
    <div id="start-call-product-inputs"></div>
</form>

<script>
/* ─── Assigned products per doctor (in-memory before Start Call) ─── */
const assignedProducts = {};  /* docId → [{id, name}] */
let currentDocId = null;

/* ─── Modal helpers ─── */
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

/* ─── Date change ─── */
function handleDateChange() {
    const d = document.getElementById('ed-date-selector').value;
    if (d) window.location.href = '/phadmin/e-detailing?date=' + encodeURIComponent(d);
}

/* ─── Doctor search filter ─── */
function filterDoctors() {
    const q = document.getElementById('searchDocInput').value.toLowerCase();
    document.querySelectorAll('#docList .doc-list-item').forEach(el => {
        el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

/* ─── Chemist search filter ─── */
function filterChemists() {
    const q = document.getElementById('searchChemistInput').value.toLowerCase();
    document.querySelectorAll('#chemistList .chemist-list-item').forEach(el => {
        el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

/* ─── Submit add-doctor form ─── */
function submitAddDoctor(doctorId) {
    document.getElementById('add-doctor-id').value = doctorId;
    document.getElementById('add-doctor-form').submit();
}

/* ─── Submit add-chemist form ─── */
function submitAddChemist(chemistId) {
    document.getElementById('add-chemist-id').value = chemistId;
    document.getElementById('add-chemist-form').submit();
}

/* ─── Product modal ─── */
function openProductModal(docId) {
    currentDocId = docId;
    document.querySelectorAll('.prod-select-box').forEach(cb => { cb.checked = false; });

    /* re-tick already-assigned products */
    const already = assignedProducts[docId] || [];
    already.forEach(p => {
        const cb = document.querySelector('.prod-select-box[value="' + p.id + '"]');
        if (cb) cb.checked = true;
    });
    openModal('productModal');
}

function saveProductAssignment() {
    const checked = document.querySelectorAll('.prod-select-box:checked');
    const cell    = document.getElementById('prod-list-' + currentDocId);
    const startBtn = document.getElementById('btn-start-' + currentDocId);

    if (checked.length > 0) {
        assignedProducts[currentDocId] = Array.from(checked).map(c => ({ id: c.value, name: c.dataset.name }));
        cell.innerHTML = '<div class="flex flex-wrap gap-1">' +
            assignedProducts[currentDocId].map(p =>
                '<span class="bg-teal-50 text-teal-800 border border-teal-100 text-xs px-2 py-0.5 rounded font-medium">' + p.name + '</span>'
            ).join('') + '</div>';
        if (startBtn) { startBtn.disabled = false; startBtn.classList.remove('opacity-40', 'cursor-not-allowed'); }
    } else {
        assignedProducts[currentDocId] = [];
        cell.innerHTML = '<span class="text-gray-400 text-xs">No products assigned</span>';
        if (startBtn) { startBtn.disabled = true; startBtn.classList.add('opacity-40', 'cursor-not-allowed'); }
    }
    closeModal('productModal');
}

/* ─── Start call → POST to backend ─── */
function startCall(docId, docName) {
    const prods = assignedProducts[docId] || [];
    if (prods.length === 0) { alert('Please assign at least one product before starting the call.'); return; }

    const form    = document.getElementById('start-call-form');
    const container = document.getElementById('start-call-product-inputs');
    container.innerHTML = '';
    document.getElementById('start-call-doc-id').value = docId;
    prods.forEach(p => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'product_ids[]'; inp.value = p.id;
        container.appendChild(inp);
    });
    form.submit();
}

/* ─── Bulk select ─── */
function toggleSelectAll() {
    const all = document.getElementById('selectAll').checked;
    document.querySelectorAll('.doc-checkbox').forEach(cb => { cb.checked = all; });
    updateBulkState();
}

function updateBulkState() {
    const checked = document.querySelectorAll('.doc-checkbox:checked');
    const total   = document.querySelectorAll('.doc-checkbox');
    const panel   = document.getElementById('bulkActions');
    const countEl = document.getElementById('selectedCount');
    const selAll  = document.getElementById('selectAll');

    if (checked.length > 0) {
        panel.classList.remove('hidden'); panel.classList.add('flex');
        countEl.textContent = checked.length + ' selected';
    } else {
        panel.classList.remove('flex'); panel.classList.add('hidden');
    }
    selAll.checked = (total.length > 0 && checked.length === total.length);
}

/* populate hidden bulk-delete field right before submit */
function fillBulkIds() {
    const ids = Array.from(document.querySelectorAll('.doc-checkbox:checked'))
        .map(cb => cb.closest('tr').dataset.id).filter(Boolean);
    if (ids.length === 0) return false;
    document.getElementById('bulk-ids-input').value = ids.join(',');
    return confirm('Remove ' + ids.length + ' selected doctor(s) from today\'s schedule?');
}

document.addEventListener('DOMContentLoaded', updateBulkState);
</script>
<?= $this->endSection() ?>
