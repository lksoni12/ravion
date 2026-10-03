<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $monthValue = (string) ($selectedMonth ?? date('Y-m'));
    $monthStart = DateTime::createFromFormat('Y-m-d', $monthValue . '-01') ?: new DateTime(date('Y-m-01'));
    $daysInMonth = (int) $monthStart->format('t');
    $existingExpense = $existingExpense ?? null;
    $existingRows = $existingRows ?? [];
    $employees = $employees ?? [];
    $canEdit = (bool) ($canEdit ?? false);
    $canApprove = (bool) ($canApprove ?? false);
    $targetUserId = (int) ($targetUserId ?? 0);
    $targetUserName = (string) ($targetUserName ?? '');
    $statusValue = strtolower((string) ($existingExpense['status'] ?? ''));
    $isOwner = in_array(strtolower((string) (session()->get('user_role') ?? 'mr')), ['admin', 'owner'], true);

    $inputEditableClass = 'bg-white outline-none focus:border-blue-500 text-gray-800';
    $inputDisabledClass = 'bg-gray-100 cursor-not-allowed text-gray-500';

    if ($statusValue === 'submitted') {
        $statusLabel = 'Pending Approval';
        $statusClasses = 'bg-blue-100 text-blue-700';
    } elseif ($statusValue === 'approved') {
        $statusLabel = 'Approved';
        $statusClasses = 'bg-green-100 text-green-700';
    } elseif ($statusValue === 'rejected') {
        $statusLabel = 'Rejected';
        $statusClasses = 'bg-red-100 text-red-700';
    } elseif ($statusValue === 'draft') {
        $statusLabel = 'Draft (auto-saved)';
        $statusClasses = 'bg-amber-100 text-amber-700';
    } else {
        $statusLabel = 'Draft';
        $statusClasses = 'bg-amber-100 text-amber-700';
    }
?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
        <?= esc(session()->getFlashdata('success')) ?>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
        <?= esc(session()->getFlashdata('error')) ?>
    </div>
<?php endif; ?>

<?php if (! empty($existingExpense['rejection_reason'])): ?>
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
        <strong>Rejection reason:</strong> <?= esc($existingExpense['rejection_reason']) ?>
    </div>
<?php endif; ?>

<form id="expense-grid-form" method="post" action="/phadmin/expenses" enctype="multipart/form-data" class="bg-transparent border-0 p-0 m-0 shadow-none" data-can-edit="<?= $canEdit ? '1' : '0' ?>">
    <input type="hidden" name="expense_month" id="expense_month_hidden" value="<?= esc($monthValue) ?>">

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm flex flex-col" style="height:80vh;">
        <div class="p-4 border-b border-gray-200 flex flex-wrap justify-between items-center gap-3 bg-gray-50 rounded-t-lg shrink-0">
            <div class="flex flex-wrap gap-3 items-center">
                <h3 class="text-lg font-medium text-gray-800 m-0">Expense Entry</h3>
                <input type="month" id="expense-month-selector" class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:ring-blue-500 focus:border-blue-500 outline-none transition m-0 w-auto" value="<?= esc($monthValue) ?>" onchange="handleExpenseMonthChange()">
                <span id="expense-status" class="px-2 py-1 rounded text-xs font-medium <?= esc($statusClasses) ?>">Status: <?= esc($statusLabel) ?></span>
                <span id="expense-autosave-indicator" class="text-xs text-gray-500 italic" aria-live="polite"></span>
                <?php if (in_array($role ?? '', ['owner', 'manager'], true) && $employees !== []): ?>
                    <select id="expense-employee-selector" class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:ring-blue-500 focus:border-blue-500 outline-none transition m-0 w-auto" onchange="handleExpenseEmployeeChange()">
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= esc($employee['id']) ?>" <?= ((int) $employee['id'] === $targetUserId) ? 'selected' : '' ?>>
                                <?= esc($employee['name']) ?> (<?= esc(strtoupper($employee['role'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <div class="flex flex-wrap gap-2 items-center">
                <?php if ($canEdit): ?>
                    <button type="button" id="expense-save-draft-btn"
                            onclick="triggerExpenseDraftSave()"
                            class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded text-sm font-medium hover:bg-gray-50 transition cursor-pointer">
                        Save Draft
                    </button>
                    <button id="expense-submit-btn" type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium hover:bg-blue-700 transition border-0 cursor-pointer">Submit for Approval</button>
                <?php endif; ?>
                <?php if ($canApprove && $existingExpense): ?>
                    <button type="button" data-popup-open="popup-approve-expense" class="bg-green-600 text-white px-4 py-2 rounded text-sm font-medium hover:bg-green-700 transition border-0 cursor-pointer">Approve</button>
                    <button type="button" data-popup-open="popup-reject-expense" class="bg-red-600 text-white px-4 py-2 rounded text-sm font-medium hover:bg-red-700 transition border-0 cursor-pointer">Reject</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="overflow-y-auto flex-1 relative">
            <table class="w-full text-left text-sm text-gray-600 border-0 rounded-none">
                <thead class="bg-gray-100 text-gray-700 uppercase text-xs sticky top-0 shadow-sm z-20">
                    <tr>
                        <th class="px-4 py-3 border-b">Date</th>
                        <th class="px-4 py-3 border-b w-32">Work Type</th>
                        <th class="px-4 py-3 border-b w-32">Area</th>
                        <th class="px-4 py-3 border-b text-center">Distance (KM)</th>
                        <th class="px-4 py-3 border-b text-center">Fare (&#8377;)</th>
                        <th class="px-4 py-3 border-b text-center">DA (&#8377;)</th>
                        <th class="px-4 py-3 border-b text-center">Misc (&#8377;)</th>
                        <th class="px-4 py-3 border-b text-center">Total (&#8377;)</th>
                        <th class="px-4 py-3 border-b text-center">Bill</th>
                    </tr>
                </thead>
                <tbody id="expense-tbody" class="divide-y divide-gray-200">
                    <?php for ($i = 1; $i <= $daysInMonth; $i++): ?>
                        <?php
                            $date = clone $monthStart;
                            $date->setDate((int) $monthStart->format('Y'), (int) $monthStart->format('m'), $i);
                            $dayName = $date->format('D');
                            $isSunday = $dayName === 'Sun';
                            $dateLabel = $date->format('d-M');

                            $row = $existingRows[$i] ?? null;
                            $rowWorkType = strtolower((string) ($row['work_type'] ?? ($isSunday ? 'holiday' : 'hq')));
                            $rowArea = (string) ($row['area_name'] ?? '');
                            $rowDistance = (float) ($row['distance_km'] ?? 0);
                            $rowFare = (float) ($row['fare_amount'] ?? 0);
                            $rowDa = (float) ($row['daily_allowance'] ?? ($isSunday ? 0 : 250));
                            $rowMisc = (float) ($row['misc_amount'] ?? 0);
                            $rowTotal = (float) ($row['total_amount'] ?? ($rowDa + $rowFare + $rowMisc));
                            $rowBillPath = (string) ($row['bill_path'] ?? '');

                            $disabledAttr = $canEdit ? '' : 'disabled';
                            $rowClass = $isSunday ? 'bg-red-50 hover:bg-red-100 transition-colors expense-row' : 'hover:bg-gray-50 transition-colors expense-row';
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td class="px-4 py-3 font-medium <?= $isSunday ? 'text-red-600' : 'text-gray-900' ?> whitespace-nowrap">
                                <?= esc($dateLabel) ?>
                                <input type="hidden" name="day[]" value="<?= $i ?>">
                                <input type="hidden" name="date_label[]" value="<?= esc($dateLabel) ?>">
                                <input type="hidden" name="day_name[]" value="<?= esc($dayName) ?>">
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($isSunday): ?>
                                    <select class="work-type-select border border-red-300 rounded px-2 py-1.5 bg-red-50 text-red-700 text-sm w-full m-0" disabled>
                                        <option>Holiday</option>
                                    </select>
                                    <input type="hidden" name="work_type[]" value="holiday">
                                <?php else: ?>
                                    <select name="work_type[]" class="work-type-select border border-gray-300 rounded px-2 py-1.5 text-sm w-full outline-none transition m-0 <?= $canEdit ? 'bg-white text-gray-800' : $inputDisabledClass ?>" onchange="updateExpenseLogic(this)" <?= $disabledAttr ?>>
                                        <option value="hq" <?= $rowWorkType === 'hq' ? 'selected' : '' ?>>HQ</option>
                                        <option value="ex-hq" <?= $rowWorkType === 'ex-hq' ? 'selected' : '' ?>>Ex-HQ</option>
                                        <option value="outstation" <?= $rowWorkType === 'outstation' ? 'selected' : '' ?>>Outstation</option>
                                        <option value="leave" <?= $rowWorkType === 'leave' ? 'selected' : '' ?>>Leave</option>
                                    </select>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($isSunday): ?>
                                    <select class="border border-gray-300 rounded px-2 py-1.5 text-sm w-full m-0 bg-gray-100 cursor-not-allowed text-gray-500" disabled>
                                        <option>N/A</option>
                                    </select>
                                    <input type="hidden" name="area_name[]" value="">
                                <?php else: ?>
                                    <div class="relative area-dropdown-container" data-day="<?= $i ?>">
                                        <input type="hidden" name="area_name[]" id="area-hidden-<?= $i ?>" value="<?= esc($rowArea) ?>" class="area-hidden-input">
                                        <button type="button" onclick="toggleAreaDropdown(<?= $i ?>)" id="area-btn-<?= $i ?>" class="px-2 py-1.5 text-xs font-medium w-full border border-gray-300 rounded text-left flex justify-between items-center outline-none transition m-0 <?= $canEdit ? 'bg-white text-gray-800' : $inputDisabledClass ?>" <?= ($isSunday || ! $canEdit || in_array($rowWorkType, ['leave', 'holiday'], true)) ? 'disabled' : '' ?>>
                                            <span class="truncate" id="area-text-<?= $i ?>"><?= $rowArea !== '' ? esc($rowArea) : 'Select Areas' ?></span>
                                            <i class="fa-solid fa-chevron-down text-[10px] text-gray-400"></i>
                                        </button>
                                        <div id="area-dropdown-<?= $i ?>" class="absolute z-50 mt-1 w-48 bg-white border border-gray-200 shadow-xl rounded-lg hidden flex-col max-h-48 overflow-y-auto text-xs font-medium text-gray-800">
                                            <!-- Checkboxes will be populated by JS -->
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" step="0.1" name="distance_km[]" class="distance-input w-20 border border-gray-300 rounded px-2 py-1.5 text-center text-sm mx-auto block m-0 disabled:bg-gray-100 disabled:cursor-not-allowed <?= $canEdit ? $inputEditableClass : $inputDisabledClass ?>" value="<?= $rowDistance ?: '' ?>" placeholder="0" oninput="calculateGrandTotal()" <?= ($isSunday || ! $canEdit || $rowWorkType === 'hq' || $rowWorkType === 'leave') ? 'disabled' : '' ?>>
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" step="0.01" name="fare_amount[]" class="fare-input w-20 border border-gray-300 rounded px-2 py-1.5 text-center text-sm mx-auto block m-0 disabled:bg-gray-100 disabled:cursor-not-allowed <?= $canEdit ? $inputEditableClass : $inputDisabledClass ?>" value="<?= $rowFare ?>" placeholder="0" oninput="calculateRowTotal(this)" <?= ($isSunday || ! $canEdit || $rowWorkType === 'hq' || $rowWorkType === 'leave') ? 'disabled' : '' ?>>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($isOwner && $canEdit): ?>
                                    <input type="number" class="da-input w-20 border border-gray-300 rounded px-2 py-1.5 text-center text-sm mx-auto block m-0 bg-white text-gray-800 focus:border-blue-500 font-semibold" value="<?= (int) $rowDa ?>" oninput="updateHiddenDa(this)" <?= ($isSunday || $rowWorkType === 'leave' || $rowWorkType === 'holiday') ? 'disabled' : '' ?>>
                                <?php else: ?>
                                    <span class="da-display font-medium <?= $isSunday ? 'text-gray-400' : 'text-gray-700' ?>"><?= (int) $rowDa ?></span>
                                <?php endif; ?>
                                <input type="hidden" name="daily_allowance_display[]" value="<?= (int) $rowDa ?>" class="da-hidden">
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" step="0.01" name="misc_amount[]" class="misc-input w-20 border border-gray-300 rounded px-2 py-1.5 text-center text-sm mx-auto block m-0 <?= ($canEdit && ! $isSunday) ? $inputEditableClass : $inputDisabledClass ?>" value="<?= $rowMisc ?>" placeholder="0" oninput="calculateRowTotal(this)" <?= ($isSunday || ! $canEdit) ? 'disabled' : '' ?>>
                            </td>
                            <td class="px-4 py-3 text-center font-bold <?= $isSunday ? 'text-gray-400' : 'text-gray-900' ?>">
                                <span class="total-display"><?= number_format($rowTotal, 2) ?></span>
                                <input type="hidden" name="total_amount_display[]" value="<?= number_format($rowTotal, 2, '.', '') ?>" class="total-hidden">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($isSunday): ?>
                                    <button type="button" class="text-gray-300 cursor-not-allowed border-0 bg-transparent" disabled><i class="fas fa-paperclip text-lg"></i></button>
                                <?php elseif ($canEdit): ?>
                                    <label for="bill-input-<?= $i ?>" class="bill-label cursor-pointer text-gray-400 hover:text-blue-600 transition-colors inline-block" title="Attach Bill">
                                        <span class="sr-only">Attach Bill</span>
                                        <input id="bill-input-<?= $i ?>" type="file" name="row_bill[]" accept=".png,.jpg,.jpeg,.pdf" class="hidden bill-input" onchange="markBillAttached(this)">
                                        <i class="fas fa-paperclip text-lg" aria-hidden="true"></i>
                                    </label>
                                <?php elseif ($rowBillPath !== ''): ?>
                                    <a href="/<?= esc($rowBillPath) ?>" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-800 inline-block" title="View attached bill"><i class="fas fa-paperclip text-lg"></i></a>
                                <?php else: ?>
                                    <button type="button" class="text-gray-300 cursor-not-allowed border-0 bg-transparent" disabled title="No Bill"><i class="fas fa-paperclip text-lg"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
                <tfoot class="bg-blue-50 border-t-2 border-blue-200 sticky bottom-0 z-20 text-gray-800" style="box-shadow:0 -2px 10px rgba(0,0,0,0.05);">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-right font-bold uppercase text-xs text-blue-800">Grand Total for Month:</td>
                        <td id="grand-total-distance" class="px-4 py-3 text-center font-bold">0</td>
                        <td id="grand-total-fare" class="px-4 py-3 text-center font-bold">0</td>
                        <td id="grand-total-da" class="px-4 py-3 text-center font-bold">0</td>
                        <td id="grand-total-misc" class="px-4 py-3 text-center font-bold">0</td>
                        <td id="grand-total-amount" class="px-4 py-3 text-center font-bold text-blue-700 text-base">0</td>
                        <td class="px-4 py-3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</form>

<?php if ($canApprove && $existingExpense): ?>
    <div id="popup-approve-expense" class="popup-overlay" aria-hidden="true">
        <div class="popup-card">
            <div class="popup-head">
                <h3>Approve Expense</h3>
                <button type="button" class="popup-close" data-popup-close="popup-approve-expense">Close</button>
            </div>
            <form method="post" action="/phadmin/expenses/status/<?= esc($existingExpense['id']) ?>" class="bg-transparent border-0 p-0 m-0 shadow-none">
                <input type="hidden" name="status" value="approved">
                <label for="approval-note-input" class="block text-sm font-medium text-gray-700 mb-1">Approval note (optional)</label>
                <textarea id="approval-note-input" name="approval_note" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 text-sm m-0"></textarea>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded text-sm font-medium hover:bg-green-700 transition border-0 cursor-pointer mt-2">Confirm Approve</button>
            </form>
        </div>
    </div>

    <div id="popup-reject-expense" class="popup-overlay" aria-hidden="true">
        <div class="popup-card">
            <div class="popup-head">
                <h3>Reject Expense</h3>
                <button type="button" class="popup-close" data-popup-close="popup-reject-expense">Close</button>
            </div>
            <form method="post" action="/phadmin/expenses/status/<?= esc($existingExpense['id']) ?>" class="bg-transparent border-0 p-0 m-0 shadow-none">
                <input type="hidden" name="status" value="rejected">
                <label for="rejection-reason-input" class="block text-sm font-medium text-gray-700 mb-1">Reason for rejection (required)</label>
                <textarea id="rejection-reason-input" name="rejection_reason" rows="3" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm m-0"></textarea>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded text-sm font-medium hover:bg-red-700 transition border-0 cursor-pointer mt-2">Confirm Reject</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
/* ── target user id (set from PHP) ──────────────────────────────── */
var _expTargetUserId = <?= (int) $targetUserId ?>;
var _expAssignedTerritories = <?= json_encode($assignedTerritories ?? []) ?>;
var _expDaHq = <?= (int) ($daHq ?? 250) ?>;
var _expDaEx = <?= (int) ($daEx ?? 450) ?>;
var _expDaOs = <?= (int) ($daOs ?? 700) ?>;

/* ── Top-level functions (hoisted, always accessible from onclick) ─ */

function handleExpenseMonthChange() {
    var month = document.getElementById('expense-month-selector').value;
    if (!month) return;
    var p = new URLSearchParams();
    p.set('month', month);
    if (_expTargetUserId > 0) p.set('employee_id', String(_expTargetUserId));
    window.location.href = '/phadmin/expenses?' + p.toString();
}

function handleExpenseEmployeeChange() {
    var sel   = document.getElementById('expense-employee-selector');
    var month = document.getElementById('expense-month-selector').value;
    if (!sel) return;
    var p = new URLSearchParams();
    if (month) p.set('month', month);
    p.set('employee_id', sel.value);
    window.location.href = '/phadmin/expenses?' + p.toString();
}

function updateExpenseLogic(selectElement) {
    var row = selectElement.closest('.expense-row');
    if (!row) return;
    var distanceInput = row.querySelector('.distance-input');
    var fareInput     = row.querySelector('.fare-input');
    var daDisplay     = row.querySelector('.da-display');
    var daHidden      = row.querySelector('.da-hidden');
    var daInput       = row.querySelector('.da-input');
    var areaSelect    = row.querySelector('select[name="area_name[]"]');
    var workType      = selectElement.value;
    var daAmount = 0, lock = false;

    if (workType === 'hq')                              { daAmount = _expDaHq; lock = true;  }
    else if (workType === 'ex-hq')                      { daAmount = _expDaEx; }
    else if (workType === 'outstation')                 { daAmount = _expDaOs; }
    else if (workType === 'leave' || workType === 'holiday') { daAmount = 0; lock = true; }

    if (distanceInput) { distanceInput.disabled = lock; if (lock) distanceInput.value = ''; }
    if (fareInput)     { fareInput.disabled = lock;     if (lock) fareInput.value = '0'; }
    if (daDisplay)     daDisplay.innerText = daAmount;
    if (daHidden)      daHidden.value      = daAmount;
    if (daInput)       daInput.value       = daAmount;

    // Enable/disable the Admin's custom input based on leave/holiday status
    if (daInput) {
        daInput.disabled = lock;
    }

    // Re-build dropdown if it was active
    if (btn && hiddenInput && workType !== 'leave' && workType !== 'holiday') {
        buildAreaDropdown(dayId, workType);
    }

    calculateRowTotal(selectElement);
}

function calculateRowTotal(element) {
    var row = element.closest('.expense-row');
    if (!row) return;
    var fare  = parseFloat((row.querySelector('.fare-input')  || {}).value) || 0;
    var da    = parseFloat((row.querySelector('.da-hidden')   || {}).value) || 0;
    var misc  = parseFloat((row.querySelector('.misc-input')  || {}).value) || 0;
    var total = fare + da + misc;
    var td = row.querySelector('.total-display'); if (td) td.innerText  = total.toFixed(2);
    var th = row.querySelector('.total-hidden');  if (th) th.value      = total.toFixed(2);
    calculateGrandTotal();
}

function updateHiddenDa(inputElement) {
    var row = inputElement.closest('.expense-row');
    if (!row) return;
    var daHidden = row.querySelector('.da-hidden');
    if (daHidden) {
        daHidden.value = inputElement.value;
    }
    calculateRowTotal(inputElement);
}

function calculateGrandTotal() {
    var dist = 0, fare = 0, da = 0, misc = 0, grand = 0;
    document.querySelectorAll('.expense-row').forEach(function (row) {
        dist  += parseFloat((row.querySelector('.distance-input') || {}).value) || 0;
        fare  += parseFloat((row.querySelector('.fare-input')     || {}).value) || 0;
        da    += parseFloat((row.querySelector('.da-hidden')      || {}).value) || 0;
        misc  += parseFloat((row.querySelector('.misc-input')     || {}).value) || 0;
        grand += parseFloat((row.querySelector('.total-hidden')   || {}).value) || 0;
    });
    var gd = document.getElementById('grand-total-distance'); if (gd) gd.innerText = dist;
    var gf = document.getElementById('grand-total-fare');     if (gf) gf.innerText = fare.toFixed(2);
    var ga = document.getElementById('grand-total-da');       if (ga) ga.innerText = da;
    var gm = document.getElementById('grand-total-misc');     if (gm) gm.innerText = misc.toFixed(2);
    var gg = document.getElementById('grand-total-amount');   if (gg) gg.innerText = grand.toFixed(2);
}

function markBillAttached(input) {
    var label = input.closest('.bill-label');
    if (!label) return;
    if (input.files && input.files.length > 0) {
        label.classList.remove('text-gray-400');
        label.classList.add('text-green-600');
        label.title = 'Bill attached: ' + input.files[0].name;
    } else {
        label.classList.add('text-gray-400');
        label.classList.remove('text-green-600');
        label.title = 'Attach Bill';
    }
}

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.area-dropdown-container')) {
            document.querySelectorAll('[id^="area-dropdown-"]').forEach(function(el) {
                el.classList.add('hidden');
                el.classList.remove('flex');
            });
        }
    });

    window.toggleAreaDropdown = function(dayId) {
        var dropdown = document.getElementById('area-dropdown-' + dayId);
        var isHidden = dropdown.classList.contains('hidden');
        
        document.querySelectorAll('[id^="area-dropdown-"]').forEach(function(el) {
            el.classList.add('hidden');
            el.classList.remove('flex');
        });

        if (isHidden) {
            var row = document.querySelector('input[name="day[]"][value="' + dayId + '"]').closest('tr');
            var workType = 'hq';
            if (row) {
                var selectEl = row.querySelector('.work-type-select');
                if (selectEl) workType = selectEl.value;
            }
            buildAreaDropdown(dayId, workType);
            dropdown.classList.remove('hidden');
            dropdown.classList.add('flex');
        }
    };

    function buildAreaDropdown(dayId, workType) {
        var dropdown = document.getElementById('area-dropdown-' + dayId);
        var hiddenInput = document.getElementById('area-hidden-' + dayId);
        if (!dropdown || !hiddenInput) return;

        var selectedAreas = hiddenInput.value ? hiddenInput.value.split(',').map(function(s) { return s.trim(); }) : [];
        var html = '';

        var validTerritories = [];
        _expAssignedTerritories.forEach(function(t) {
            var tType = t.type.toLowerCase();
            var wType = workType.toLowerCase();
            if (wType === 'ex-hq') wType = 'ex-station';
            if (wType === 'outstation') wType = 'out-station';
            if (tType === wType) {
                validTerritories.push(t.name);
                var isChecked = selectedAreas.includes(t.name);
                html += `
                    <label class="flex items-center px-3 py-2 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0">
                        <input type="checkbox" class="mr-2 rounded text-blue-600 area-checkbox-${dayId}" value="${t.name}" ${isChecked ? 'checked' : ''} onchange="updateSelectedAreas(${dayId})">
                        <span class="truncate">${t.name}</span>
                    </label>
                `;
            }
        });

        selectedAreas.forEach(function(sa) {
            if (sa && !validTerritories.includes(sa)) {
                html += `
                    <label class="flex items-center px-3 py-2 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0">
                        <input type="checkbox" class="mr-2 rounded text-blue-600 area-checkbox-${dayId}" value="${sa}" checked onchange="updateSelectedAreas(${dayId})">
                        <span class="truncate">${sa} (Unassigned)</span>
                    </label>
                `;
            }
        });

        if (html === '') {
            html = '<div class="p-3 text-gray-400 text-center text-xs">No areas found.</div>';
        }

        dropdown.innerHTML = html;
    }

    window.updateSelectedAreas = function(dayId) {
        var checkboxes = document.querySelectorAll('.area-checkbox-' + dayId + ':checked');
        var areas = Array.from(checkboxes).map(function(cb) { return cb.value; });
        
        var hiddenInput = document.getElementById('area-hidden-' + dayId);
        var btnText = document.getElementById('area-text-' + dayId);
        
        var newVal = areas.join(', ');
        hiddenInput.value = newVal;
        btnText.textContent = newVal || 'Select Areas';
        btnText.title = newVal;
        
        clearTimeout(fetchDraftTimer);
        fetchDraftTimer = setTimeout(function() {
            if (typeof triggerExpenseDraftSave === 'function') {
                triggerExpenseDraftSave(true);
            }
        }, 800);
    };

/* No-op until initAutoDraft overrides it */
function triggerExpenseDraftSave() {}

/* ── Auto-draft (wired up after DOM ready) ────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    calculateGrandTotal();

    // No need for old areaSelect change listener

    var form = document.getElementById('expense-grid-form');
    if (!form || form.dataset.canEdit !== '1') return;

    var indicator = document.getElementById('expense-autosave-indicator');
    var timer = null, inFlight = null;

    function getCsrfToken() {
        var m = document.cookie.match(/(?:^|;\s*)csrf_cookie_name=([^;]*)/);
        return m ? decodeURIComponent(m[1]) : '';
    }

    function setIndicator(text, color) {
        if (!indicator) return;
        indicator.textContent = text;
        indicator.className = 'text-xs italic ' + (color || 'text-gray-500');
    }

    function buildFormData() {
        var fd = new FormData();
        var mEl = document.getElementById('expense_month_hidden');
        fd.append('expense_month', mEl ? mEl.value : '');

        document.querySelectorAll('#expense-tbody .expense-row').forEach(function (row) {
            function rv(sel, def) {
                var el = row.querySelector(sel);
                return el ? el.value : (def !== undefined ? String(def) : '');
            }
            fd.append('day[]',           rv('input[name="day[]"]'));
            fd.append('date_label[]',    rv('input[name="date_label[]"]'));
            fd.append('day_name[]',      rv('input[name="day_name[]"]'));

            var hwt = row.querySelector('input[name="work_type[]"]');
            var swt = row.querySelector('select[name="work_type[]"]');
            fd.append('work_type[]', hwt ? hwt.value : (swt ? swt.value : 'hq'));

            fd.append('area_name[]',               rv('[name="area_name[]"]', ''));
            fd.append('distance_km[]',             rv('.distance-input',           '0'));
            fd.append('fare_amount[]',             rv('.fare-input',               '0'));
            fd.append('daily_allowance_display[]', rv('.da-hidden',                '0'));
            fd.append('misc_amount[]',             rv('.misc-input',               '0'));
            fd.append('total_amount_display[]',    rv('.total-hidden',             '0'));
        });
        return fd;
    }

    function sendDraft() {
        if (inFlight) inFlight.abort();
        var ctrl = new AbortController();
        inFlight = ctrl;
        setIndicator('Saving…', 'text-gray-500');

        fetch('/phadmin/expenses/draft', {
            method: 'POST',
            body: buildFormData(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCsrfToken() },
            signal: ctrl.signal,
            credentials: 'same-origin',
        })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
        .then(function (res) {
            inFlight = null;
            if (res.ok && res.data && res.data.ok) {
                setIndicator('Draft saved at ' + (res.data.saved_at || ''), 'text-green-600');
            } else {
                setIndicator('Save failed' + (res.data && res.data.message ? ': ' + res.data.message : ''), 'text-red-600');
            }
        })
        .catch(function (err) {
            inFlight = null;
            if (err && err.name === 'AbortError') return;
            setIndicator('Save failed (network)', 'text-red-600');
        });
    }

    function scheduleSave() {
        clearTimeout(timer);
        timer = setTimeout(sendDraft, 1500);
        setIndicator('Editing…', 'text-gray-400');
    }

    form.addEventListener('input',  function (e) { if (e.target.type !== 'file') scheduleSave(); });
    form.addEventListener('change', function (e) { if (e.target.type !== 'file') scheduleSave(); });
    form.addEventListener('submit', function ()  { clearTimeout(timer); if (inFlight) inFlight.abort(); });

    /* Override the top-level no-op */
    triggerExpenseDraftSave = function () { clearTimeout(timer); sendDraft(); };
});
</script>
<?= $this->endSection() ?>
