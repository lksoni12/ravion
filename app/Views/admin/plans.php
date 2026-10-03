<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $monthValue  = (string) ($selectedMonth ?? date('Y-m'));
    $monthStart  = \DateTime::createFromFormat('Y-m-d', $monthValue . '-01') ?: new \DateTime(date('Y-m-01'));
    $daysInMonth = (int) $monthStart->format('t');

    $existingPlan = $existingPlan ?? null;
    $existingRows = $existingRows ?? [];
    $canEdit      = (bool) ($canEdit ?? true);
    $doctors      = $doctors ?? [];
    $statusValue  = strtolower((string) ($existingPlan['status'] ?? ''));

    if ($statusValue === 'submitted') {
        $statusLabel   = 'Pending Approval';
        $statusClasses = 'bg-blue-50 text-blue-700 border border-blue-100';
    } elseif ($statusValue === 'approved') {
        $statusLabel   = 'Approved & Locked';
        $statusClasses = 'bg-emerald-50 text-emerald-700 border border-emerald-100';
    } elseif ($statusValue === 'completed') {
        $statusLabel   = 'Completed';
        $statusClasses = 'bg-purple-50 text-purple-700 border border-purple-100';
    } elseif ($statusValue === 'rejected') {
        $statusLabel   = 'Rejected';
        $statusClasses = 'bg-rose-50 text-rose-700 border border-rose-100';
    } elseif ($statusValue === 'draft') {
        $statusLabel   = 'Draft (auto-saved)';
        $statusClasses = 'bg-amber-50 text-amber-705 text-amber-700 border border-amber-100';
    } else {
        $statusLabel   = 'Draft';
        $statusClasses = 'bg-amber-50 text-amber-700 border border-amber-100';
    }

    $inputEdit = 'bg-white border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-slate-800 transition-colors';
    $inputLock = 'bg-slate-50 border-slate-200 cursor-not-allowed text-slate-400';
?>

<!-- Alert System -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center gap-2">
        <i class="fa-solid fa-circle-check text-emerald-500"></i>
        <span><?= esc(session()->getFlashdata('success')) ?></span>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="mb-5 rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 flex items-center gap-2">
        <i class="fa-solid fa-circle-xmark text-rose-500"></i>
        <span><?= esc(session()->getFlashdata('error')) ?></span>
    </div>
<?php endif; ?>

<!-- Rejection Warning Alert with Reason Details -->
<?php if ($statusValue === 'rejected'): ?>
    <div class="mb-6 rounded-2xl bg-rose-50 border border-rose-200 p-5 text-sm text-rose-800">
        <div class="flex items-center gap-2 mb-2 font-bold">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-lg"></i>
            <span class="text-base">Tour Plan Rejected by Manager</span>
        </div>
        <p class="mb-3 text-rose-700 font-medium">Your tour plan for <?= esc($monthValue) ?> was rejected. Make the necessary corrections below and re-submit. Your previously entered data has been preserved.</p>
        <?php if (! empty($existingPlan['approval_note'])): ?>
            <div class="bg-white/80 border border-rose-100 rounded-xl p-3.5 mt-2">
                <span class="block text-xs font-bold uppercase tracking-wider text-rose-900 mb-1">Reason for Rejection:</span>
                <span class="text-slate-800 font-semibold italic text-xs">"<?= esc($existingPlan['approval_note']) ?>"</span>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Pending Team Submissions Box for Managers / Admins -->
<?php if (! empty($pendingTeamPlans)): ?>
    <div class="mb-6 bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-amber-50/50 border-b border-amber-100 flex items-center gap-2">
            <i class="fa-solid fa-bell-concierge text-amber-600 animate-bounce"></i>
            <h3 class="text-sm font-extrabold text-amber-800 uppercase tracking-wider m-0">Pending Team Tour Plan Approvals (<?= esc($monthValue) ?>)</h3>
        </div>
        <div class="divide-y divide-slate-100">
            <?php foreach ($pendingTeamPlans as $pending): ?>
                <div class="px-5 py-3.5 flex flex-wrap items-center justify-between gap-3 bg-white">
                    <div>
                        <span class="font-extrabold text-slate-800 text-sm"><?= esc($pending['user_name'] ?? '—') ?></span>
                        <span class="ml-1.5 text-xs font-bold text-slate-400 px-2 py-0.5 bg-slate-100 rounded-full uppercase tracking-wider"><?= esc($pending['user_role'] ?? '') ?></span>
                        <span class="ml-2 bg-indigo-50 text-indigo-700 border border-indigo-100 text-xs px-2.5 py-0.5 rounded-full font-bold">
                            <?= esc($pending['row_count']) ?> day<?= (int) $pending['row_count'] === 1 ? '' : 's' ?> planned
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <form method="post" action="/phadmin/plans/approve-month" class="inline m-0 p-0 border-0 bg-transparent">
                            <input type="hidden" name="target_user_id" value="<?= esc($pending['created_by_user_id']) ?>">
                            <input type="hidden" name="plan_month"     value="<?= esc($monthValue) ?>">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-bold border-0 cursor-pointer shadow-sm shadow-emerald-50 transition-all" onclick="return confirm('Approve tour plan for <?= esc(addslashes($pending['user_name'] ?? '')) ?>?')">
                                <i class="fa-solid fa-check mr-1 text-[10px]"></i> Approve
                            </button>
                        </form>
                        <button type="button" onclick="openRejectModal('<?= esc($pending['created_by_user_id']) ?>', '<?= esc(addslashes($pending['user_name'] ?? '')) ?>')" class="bg-rose-600 hover:bg-rose-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-bold border-0 cursor-pointer shadow-sm shadow-rose-50 transition-all">
                            <i class="fa-solid fa-xmark mr-1 text-[10px]"></i> Reject
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Reject Month Plan Modal -->
<div id="reject-plan-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100 transform scale-95 transition-all duration-300">
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-extrabold text-slate-800 flex items-center gap-2 m-0 text-base">
                <i class="fa-solid fa-circle-xmark text-rose-500"></i> Reject Tour Plan
            </h3>
            <button type="button" onclick="closeRejectModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-150 bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-850 border-0 cursor-pointer transition-colors">&times;</button>
        </div>
        <form method="post" action="/phadmin/plans/reject-month" class="m-0 p-0 border-0 bg-transparent">
            <input type="hidden" name="target_user_id" id="reject-target-user-id" value="">
            <input type="hidden" name="plan_month"     value="<?= esc($monthValue) ?>">
            <div class="p-5 space-y-4">
                <p class="text-sm text-slate-650 m-0 leading-relaxed">
                    Rejecting plan for <strong id="reject-user-name" class="text-slate-800"></strong>. The MR will be notified to correct and resubmit, while their existing data remains preserved.
                </p>
                <div>
                    <label for="rejection-reason-plan" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Reason for rejection <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="rejection-reason-plan" name="rejection_reason" rows="3" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm m-0 outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all placeholder-slate-400" placeholder="Please specify the changes needed..."></textarea>
                </div>
            </div>
            <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex justify-end gap-2.5">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-lg border-0 bg-transparent cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg border-0 cursor-pointer shadow-md shadow-rose-50">Confirm Reject</button>
            </div>
        </form>
    </div>
</div>

<!-- Main Form -->
<form id="tour-plan-form" method="post" action="/phadmin/plans/submit" class="bg-transparent border-0 p-0 m-0 shadow-none" data-can-edit="<?= $canEdit ? '1' : '0' ?>">
    <input type="hidden" name="plan_month" id="plan_month_hidden" value="<?= esc($monthValue) ?>">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        
        <!-- Header Bar -->
        <div class="p-5 border-b border-slate-200 flex flex-wrap justify-between items-center gap-3 bg-slate-50/50">
            <div class="flex flex-wrap gap-4 items-center">
                <h3 class="text-lg font-extrabold text-slate-800 m-0">Monthly Tour Planner</h3>
                <input type="month" id="plan-month-selector" value="<?= esc($monthValue) ?>" onchange="handlePlanMonthChange()" class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm text-slate-700 outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all m-0 w-auto bg-white cursor-pointer font-semibold">
                <?php if (in_array($role ?? '', ['owner', 'manager'], true) && ! empty($employees)): ?>
                    <select id="plan-employee-selector" class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm text-slate-700 outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all m-0 w-auto bg-white cursor-pointer font-semibold" onchange="handlePlanEmployeeChange()">
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= esc($employee['id']) ?>" <?= ((int) $employee['id'] === (int) $targetUserId) ? 'selected' : '' ?>>
                                <?= esc($employee['name']) ?> (<?= esc(strtoupper($employee['role'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <span id="plan-status" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider <?= esc($statusClasses) ?>">
                    <?= esc($statusLabel) ?>
                </span>
                <span id="plan-autosave-indicator" class="text-xs font-semibold text-slate-550 flex items-center gap-1.5" aria-live="polite"></span>
            </div>
            
            <?php if ($canEdit): ?>
                <div class="flex flex-wrap gap-2.5 items-center">
                    <button type="button" id="save-draft-btn" onclick="triggerDraftSave()" class="px-5 py-2.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-xl text-sm border border-slate-300 transition shadow-sm cursor-pointer">
                        Save Draft
                    </button>
                    <button type="submit" id="plan-submit-btn" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-bold rounded-xl text-sm shadow-md transition border-0 cursor-pointer">
                        Submit Month Plan
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Scrollable Table -->
        <div class="overflow-x-auto" style="max-height:75vh;">
            <table class="w-full text-left text-sm text-slate-600 border-0 border-collapse">
                <thead class="bg-slate-50 text-slate-650 uppercase text-xs font-bold tracking-wider sticky top-0 z-20 shadow-sm border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3.5 w-24 whitespace-nowrap">Date</th>
                        <th class="px-4 py-3.5 w-32">Work Type</th>
                        <th class="px-4 py-3.5 w-40">Area / Patch</th>
                        <th class="px-4 py-3.5 w-40">Joint Working</th>
                        <th class="px-4 py-3.5 w-60">Planned Doctors</th>
                        <th class="px-4 py-3.5">Remarks / Objectives</th>
                        <th class="px-4 py-3.5 text-center w-32">Plan Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php for ($i = 1; $i <= $daysInMonth; $i++): ?>
                        <?php
                            $d        = clone $monthStart;
                            $d->setDate((int) $monthStart->format('Y'), (int) $monthStart->format('m'), $i);
                            $dayName  = $d->format('D');
                            $isSun    = ($dayName === 'Sun');
                            $dateLabel = $d->format('d-M');

                            if (isset($existingRows[$i][0])) {
                                $rowData = $existingRows[$i][0];
                            } elseif (isset($existingRows[$i])) {
                                $rowData = $existingRows[$i];
                            } else {
                                $rowData = null;
                            }
                            $defaultActivity = $isSun ? 'holiday' : 'hq';
                            $actType = strtolower((string) ($rowData['activity_type'] ?? $defaultActivity));
                            $rowArea  = (string) ($rowData['area_name'] ?? '');
                            $rowJoint = (string) ($rowData['joint_call_with'] ?? '');
                            $rowNote  = (string) ($rowData['objective'] ?? '');
                            $dis      = ($isSun || ! $canEdit) ? 'disabled' : '';
                            
                            $trClass  = $isSun
                                ? 'bg-rose-50/50 hover:bg-rose-50 transition-colors'
                                : 'hover:bg-slate-50/40 transition-colors';
                        ?>
                        <tr class="<?= $trClass ?>" data-day="<?= $i ?>">

                            <!-- Date Column -->
                            <td class="px-4 py-3.5 font-bold <?= $isSun ? 'text-rose-600' : 'text-slate-800' ?> whitespace-nowrap">
                                <?= esc($dateLabel) ?>
                                <span class="text-[10px] font-extrabold block text-slate-400 mt-0.5 uppercase tracking-wide"><?= esc($dayName) ?></span>
                                <input type="hidden" name="day[]"        value="<?= $i ?>">
                                <input type="hidden" name="date_label[]" value="<?= esc($dateLabel) ?>">
                                <input type="hidden" name="day_name[]"   value="<?= esc($dayName) ?>">
                            </td>

                            <!-- Work Type -->
                            <td class="px-4 py-3.5">
                                <?php if ($isSun): ?>
                                    <select class="px-2 py-2 bg-rose-55 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold rounded-lg w-full m-0 cursor-not-allowed" disabled>
                                        <option>Holiday</option>
                                    </select>
                                    <input type="hidden" name="activity_type[]" value="holiday">
                                <?php else: ?>
                                    <select name="activity_type[]" class="activity-type-select px-2 py-2 text-xs font-medium w-full border rounded-lg outline-none <?= $canEdit ? 'bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 ' . $inputEdit : $inputLock ?>" <?= $dis ?> onchange="handleWorkTypeChange(this)">
                                        <option value="hq"         <?= $actType === 'hq'         ? 'selected' : '' ?>>HQ</option>
                                        <option value="ex-hq"      <?= $actType === 'ex-hq'      ? 'selected' : '' ?>>Ex-HQ</option>
                                        <option value="outstation" <?= $actType === 'outstation' ? 'selected' : '' ?>>Outstation</option>
                                        <option value="leave"      <?= $actType === 'leave'      ? 'selected' : '' ?>>Leave</option>
                                    </select>
                                <?php endif; ?>
                            </td>

                            <!-- Area / Patch (Multi-select) -->
                            <td class="px-4 py-3.5">
                                <div class="relative area-dropdown-container" data-day="<?= $i ?>">
                                    <input type="hidden" name="area_name[]" id="area-hidden-<?= $i ?>" value="<?= esc($rowArea) ?>" class="area-hidden-input">
                                    <button type="button" onclick="toggleAreaDropdown(<?= $i ?>)" id="area-btn-<?= $i ?>" class="px-2 py-2 text-xs font-medium w-full border rounded-lg text-left flex justify-between items-center <?= ($canEdit && ! $isSun) ? $inputEdit : $inputLock ?>" <?= $dis ?> <?= ($canEdit && ! $isSun && in_array($actType, ['leave', 'holiday'], true)) ? 'disabled' : '' ?>>
                                        <span class="truncate" id="area-text-<?= $i ?>"><?= $rowArea !== '' ? esc($rowArea) : 'Select Areas' ?></span>
                                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                                    </button>
                                    <div id="area-dropdown-<?= $i ?>" class="absolute z-50 mt-1 w-56 bg-white border border-slate-200 shadow-xl rounded-lg hidden flex-col max-h-48 overflow-y-auto text-xs font-medium">
                                        <!-- Checkboxes will be populated by JS -->
                                    </div>
                                </div>
                            </td>

                            <!-- Joint Working -->
                            <td class="px-4 py-3.5">
                                <select name="joint_call_with[]" class="px-2 py-2 text-xs font-medium w-full border rounded-lg outline-none <?= ($canEdit && ! $isSun) ? $inputEdit : $inputLock ?>" <?= $dis ?>>
                                    <option value="None" <?= ($rowJoint === '' || $rowJoint === 'None') ? 'selected' : '' ?>>None</option>
                                    <option value="Area Manager" <?= $rowJoint === 'Area Manager' ? 'selected' : '' ?>>Area Manager</option>
                                    <option value="Senior MR"    <?= $rowJoint === 'Senior MR'    ? 'selected' : '' ?>>Senior MR</option>
                                    <option value="RSM"          <?= $rowJoint === 'RSM'          ? 'selected' : '' ?>>RSM</option>
                                </select>
                            </td>

                            <!-- Planned Doctors Count Badge and Add Doctor Modal Trigger Button -->
                            <td class="px-4 py-3.5">
                                <?php if ($isSun): ?>
                                    <span class="text-slate-400 font-medium text-xs">—</span>
                                    <input type="hidden" name="doctor_ids[]" value="[]">
                                <?php else: ?>
                                    <?php
                                        $dayDrIds = $rowData['doctor_ids'] ?? [];
                                        $selectedCount = count($dayDrIds);
                                    ?>
                                    <div class="flex flex-col gap-1.5 items-start">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span id="dr-count-text-<?= $i ?>" class="text-[11px] font-bold px-2 py-0.5 <?= $selectedCount > 0 ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : 'bg-slate-100 text-slate-500' ?> rounded-full">
                                                <?= $selectedCount > 0 ? "$selectedCount Doctor" . ($selectedCount === 1 ? '' : 's') : 'No doctors selected' ?>
                                            </span>
                                            
                                            <button type="button" id="dr-view-details-<?= $i ?>" onclick="openViewPlanModal(<?= $i ?>, '<?= esc($dateLabel) ?>')" class="text-indigo-600 hover:text-indigo-800 font-bold text-xs bg-transparent border-0 cursor-pointer p-0 transition-colors <?= $selectedCount > 0 ? '' : 'hidden' ?>">
                                                View details
                                            </button>
                                        </div>
                                        
                                        <?php if ($canEdit): ?>
                                            <button type="button" onclick="openAddDoctorModal(<?= $i ?>, '<?= esc($dateLabel) ?>')" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-300 hover:border-slate-400 text-slate-700 hover:text-indigo-750 font-bold rounded-lg text-xs transition shadow-xs cursor-pointer">
                                                <i class="fa-solid fa-plus text-[10px] text-slate-500"></i> Add Doctor
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" name="doctor_ids[]" id="doc-input-<?= $i ?>" value="<?= esc(json_encode(array_map('strval', $dayDrIds))) ?>">
                                <?php endif; ?>
                            </td>

                            <!-- Remarks / Objectives -->
                            <td class="px-4 py-3.5">
                                <input type="text" name="objective[]" value="<?= esc($rowNote) ?>" placeholder="<?= $isSun ? 'Weekly Off' : 'Enter daily objectives...' ?>" class="w-full px-2.5 py-2 border rounded-lg outline-none text-xs transition m-0 <?= ($canEdit && ! $isSun) ? $inputEdit : $inputLock ?>" <?= $dis ?>>
                            </td>

                            <!-- Action Column: View Details -->
                            <td class="px-4 py-3.5 text-center">
                                <?php if ($isSun): ?>
                                    <span class="text-slate-300 text-xs">—</span>
                                <?php else: ?>
                                    <button type="button" id="dr-row-details-btn-<?= $i ?>" onclick="openViewPlanModal(<?= $i ?>, '<?= esc($dateLabel) ?>')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-200 text-slate-700 hover:text-indigo-750 rounded-lg text-xs font-bold transition shadow-xs cursor-pointer <?= $selectedCount > 0 ? '' : 'opacity-40 pointer-events-none' ?>">
                                        <i class="fa-solid fa-eye text-slate-400"></i> Details
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<!-- Add Doctors Selection Multi-Select Dynamic Modal -->
<div id="add-doctor-modal" class="popup-overlay fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50 transition-opacity duration-300" aria-hidden="true">
    <div class="popup-card bg-white rounded-2xl border border-slate-100 shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto transform scale-95 transition-all duration-300">
        <div class="popup-head flex items-center justify-between border-b border-slate-100 p-4 pb-3">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-user-doctor text-indigo-600"></i> <span id="add-dr-modal-title">Select Doctors</span>
            </h3>
            <button type="button" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors border-0 cursor-pointer" onclick="closeAddDoctorModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label for="modal-employee-select" class="block text-xs font-bold text-slate-655 text-slate-600 uppercase tracking-wider mb-1.5">Select Employee / MR</label>
                <select id="modal-employee-select" onchange="renderDoctorChecklist()" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white font-medium text-sm">
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= esc($emp['id']) ?>" <?= ((int) $emp['id'] === (int) $targetUserId) ? 'selected' : '' ?>><?= esc($emp['name']) ?> (<?= esc(strtoupper($emp['role'])) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="modal-doctor-search" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Filter Doctors by Name</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input id="modal-doctor-search" oninput="renderDoctorChecklist()" class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="Type doctor name...">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Available Doctors (Check to add)</label>
                <div id="modal-doctor-checklist" class="border border-slate-200 rounded-lg p-3 max-h-56 overflow-y-auto overflow-x-hidden space-y-2 bg-slate-50/50">
                    <!-- Checkboxes rendered dynamically in JS -->
                </div>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 mt-4">
                <button type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm transition-colors border-0 cursor-pointer" onclick="closeAddDoctorModal()">Cancel</button>
                <button type="button" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-sm shadow-md shadow-indigo-50 hover:shadow-lg transition-all duration-200 border-0 cursor-pointer" onclick="submitAddDoctorModal()">Apply Selection</button>
            </div>
        </div>
    </div>
</div>

<!-- View Plan Modal Redesign -->
<div id="view-plan-modal" class="popup-overlay fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50 transition-opacity duration-300" aria-hidden="true">
    <div class="popup-card bg-white rounded-2xl border border-slate-100 shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto transform scale-95 transition-all duration-300">
        <div class="popup-head flex items-center justify-between border-b border-slate-100 p-4 pb-3">
            <div>
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2 m-0">
                    <i class="fa-solid fa-user-doctor text-indigo-600"></i> Planned Visits
                </h3>
                <p id="modal-date" class="text-xs font-bold text-indigo-600 mt-1"></p>
            </div>
            <button type="button" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors border-0 cursor-pointer" onclick="closeViewPlanModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <ul id="modal-doctor-list" class="space-y-2.5 max-h-80 overflow-y-auto pr-1">
                <!-- Selected doctors names render here -->
            </ul>
            <div class="flex justify-end pt-4 border-t border-slate-100 mt-4">
                <button type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm transition-colors border-0 cursor-pointer" onclick="closeViewPlanModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // Inject PHP data to global JS arrays
    const allDoctors = <?= json_encode($doctors) ?>;
    const currentUserId = <?= (int) $currentUserId ?>;
    const targetUserId = <?= (int) $targetUserId ?>;
    const assignedTerritories = <?= json_encode($assignedTerritories ?? []) ?>;
    
    // Day doctor IDs selection mapping
    const selectedDoctorsByDay = {
        <?php for ($i = 1; $i <= $daysInMonth; $i++): ?>
            <?= $i ?>: <?= json_encode(array_map('intval', $existingRows[$i]['doctor_ids'] ?? [])) ?>,
        <?php endfor; ?>
    };

    let activeDay = 0;

    /* ── Reject Modal ── */
    window.openRejectModal = function (userId, userName) {
        document.getElementById('reject-target-user-id').value = userId;
        document.getElementById('reject-user-name').textContent = userName;
        document.getElementById('rejection-reason-plan').value = '';
        const modal = document.getElementById('reject-plan-modal');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.querySelector('.bg-white').classList.remove('scale-95');
            modal.querySelector('.bg-white').classList.add('scale-100');
        }, 50);
    };
    window.closeRejectModal = function () {
        const modal = document.getElementById('reject-plan-modal');
        modal.querySelector('.bg-white').classList.remove('scale-100');
        modal.querySelector('.bg-white').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 150);
    };

    /* ── Month & Employee Selectors ── */
    window.handlePlanMonthChange = function () {
        const v = document.getElementById('plan-month-selector').value;
        const empSel = document.getElementById('plan-employee-selector');
        const p = new URLSearchParams();
        if (v) p.set('month', v);
        if (empSel) p.set('employee_id', empSel.value);
        window.location.href = '/phadmin/plans?' + p.toString();
    };

    window.handlePlanEmployeeChange = function () {
        const sel = document.getElementById('plan-employee-selector');
        const month = document.getElementById('plan-month-selector').value;
        if (!sel) return;
        const p = new URLSearchParams();
        if (month) p.set('month', month);
        p.set('employee_id', sel.value);
        window.location.href = '/phadmin/plans?' + p.toString();
    };

    window.handleWorkTypeChange = function (selectEl) {
        const row = selectEl.closest('tr');
        if (!row) return;
        const dayId = row.getAttribute('data-day');
        if (!dayId) return;
        
        const btn = document.getElementById('area-btn-' + dayId);
        const hiddenInput = document.getElementById('area-hidden-' + dayId);
        if (!btn || !hiddenInput) return;
        
        const workType = selectEl.value; // hq, ex-hq, outstation, leave

        if (workType === 'leave' || workType === 'holiday') {
            btn.disabled = true;
            btn.classList.remove('bg-white');
            btn.classList.add('bg-slate-50', 'cursor-not-allowed', 'text-slate-400');
            hiddenInput.value = '';
            document.getElementById('area-text-' + dayId).textContent = 'Select Areas';
            // Also clear doctors
            clearDoctorsForDay(dayId);
            return;
        }

        btn.disabled = false;
        btn.classList.remove('bg-slate-50', 'cursor-not-allowed', 'text-slate-400');
        btn.classList.add('bg-white');
        
        // Re-render dropdown items if it was open
        buildAreaDropdown(dayId, workType);
        
        // Don't auto-clear areas on work type change unless the selected areas are no longer valid for this work type
        // For now, let's keep the existing logic where user manually changes it.
    };
    
    function clearDoctorsForDay(dayId) {
        selectedDoctorsByDay[dayId] = [];
        const docInput = document.getElementById('doc-input-' + dayId);
        if (docInput) docInput.value = '[]';

        const countSpan = document.getElementById('dr-count-text-' + dayId);
        const detailsLink = document.getElementById('dr-view-details-' + dayId);
        const detailsBtn = document.getElementById('dr-row-details-btn-' + dayId);
        if (countSpan) {
            countSpan.textContent = 'No doctors selected';
            countSpan.className = 'text-[11px] font-bold px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full';
        }
        if (detailsLink) detailsLink.classList.add('hidden');
        if (detailsBtn) {
            detailsBtn.classList.add('opacity-40');
            detailsBtn.classList.add('pointer-events-none');
        }

        if (window.sendDraft) window.sendDraft();
    }

    // Initialize Area Dropdowns
    window.toggleAreaDropdown = function(dayId) {
        const dropdown = document.getElementById('area-dropdown-' + dayId);
        const isHidden = dropdown.classList.contains('hidden');
        
        // Close all other dropdowns
        document.querySelectorAll('[id^="area-dropdown-"]').forEach(el => {
            el.classList.add('hidden');
            el.classList.remove('flex');
        });

        if (isHidden) {
            const row = document.querySelector(`tr[data-day="${dayId}"]`);
            let workType = 'hq';
            if (row) {
                const selectEl = row.querySelector('.activity-type-select');
                if (selectEl) workType = selectEl.value;
            }
            buildAreaDropdown(dayId, workType);
            dropdown.classList.remove('hidden');
            dropdown.classList.add('flex');
        }
    };

    function buildAreaDropdown(dayId, workType) {
        const dropdown = document.getElementById('area-dropdown-' + dayId);
        const hiddenInput = document.getElementById('area-hidden-' + dayId);
        if (!dropdown || !hiddenInput) return;

        let selectedAreas = hiddenInput.value ? hiddenInput.value.split(',').map(s => s.trim()) : [];
        let html = '';

        let validTerritories = [];
        assignedTerritories.forEach(t => {
            let tType = t.type.toLowerCase();
            let wType = workType.toLowerCase();
            if (wType === 'ex-hq') wType = 'ex-station';
            if (wType === 'outstation') wType = 'out-station';
            if (tType === wType) {
                validTerritories.push(t.name);
                const isChecked = selectedAreas.includes(t.name);
                html += `
                    <label class="flex items-center px-3 py-2 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0">
                        <input type="checkbox" class="mr-2 rounded text-indigo-600 area-checkbox-${dayId}" value="${t.name}" ${isChecked ? 'checked' : ''} onchange="updateSelectedAreas(${dayId})">
                        <span class="truncate">${t.name}</span>
                    </label>
                `;
            }
        });

        // Add previously selected areas that might be unassigned or mismatch type, to not lose data
        selectedAreas.forEach(sa => {
            if (sa && !validTerritories.includes(sa)) {
                html += `
                    <label class="flex items-center px-3 py-2 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0">
                        <input type="checkbox" class="mr-2 rounded text-indigo-600 area-checkbox-${dayId}" value="${sa}" checked onchange="updateSelectedAreas(${dayId})">
                        <span class="truncate">${sa} (Unassigned)</span>
                    </label>
                `;
            }
        });

        if (html === '') {
            html = '<div class="p-3 text-slate-400 text-center text-[11px]">No areas found for this work type.</div>';
        }

        dropdown.innerHTML = html;
    }

    window.updateSelectedAreas = function(dayId) {
        const checkboxes = document.querySelectorAll(`.area-checkbox-${dayId}:checked`);
        const areas = Array.from(checkboxes).map(cb => cb.value);
        
        const hiddenInput = document.getElementById('area-hidden-' + dayId);
        const btnText = document.getElementById('area-text-' + dayId);
        
        const oldVal = hiddenInput.value;
        const newVal = areas.join(', ');
        
        hiddenInput.value = newVal;
        btnText.textContent = newVal || 'Select Areas';
        btnText.title = newVal;

        if (oldVal !== newVal) {
            clearDoctorsForDay(dayId);
        }
    };

    // Close area dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.area-dropdown-container')) {
            document.querySelectorAll('[id^="area-dropdown-"]').forEach(el => {
                el.classList.add('hidden');
                el.classList.remove('flex');
            });
        }
    });

    /* ── Add Doctor Modal ── */
    window.openAddDoctorModal = function (dayId, dateStr) {
        activeDay = dayId;
        
        document.getElementById('add-dr-modal-title').textContent = 'Select Doctors for ' + dateStr;
        document.getElementById('modal-doctor-search').value = '';
        
        // Reset employee select dropdown to current MR/plan-owner
        const empSelect = document.getElementById('modal-employee-select');
        if (empSelect) {
            empSelect.value = targetUserId;
        }

        renderDoctorChecklist();

        const modal = document.getElementById('add-doctor-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            modal.classList.add('opacity-100');
            modal.querySelector('.popup-card').classList.remove('scale-95');
            modal.querySelector('.popup-card').classList.add('scale-100');
        }, 50);
    };

    window.closeAddDoctorModal = function () {
        const modal = document.getElementById('add-doctor-modal');
        modal.querySelector('.popup-card').classList.remove('scale-100');
        modal.querySelector('.popup-card').classList.add('scale-95');
        modal.classList.remove('opacity-100');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 200);
    };

    window.toggleDoctorSelection = function(checkbox, docId) {
        if (!selectedDoctorsByDay[activeDay]) {
            selectedDoctorsByDay[activeDay] = [];
        }
        
        const id = Number(docId);
        if (checkbox.checked) {
            if (!selectedDoctorsByDay[activeDay].includes(id)) {
                selectedDoctorsByDay[activeDay].push(id);
            }
        } else {
            selectedDoctorsByDay[activeDay] = selectedDoctorsByDay[activeDay].filter(item => Number(item) !== id);
        }
    };

    window.renderDoctorChecklist = function () {
        const empSelect = document.getElementById('modal-employee-select');
        const checklist = document.getElementById('modal-doctor-checklist');
        const searchInput = document.getElementById('modal-doctor-search');
        if (!empSelect || !checklist) return;

        const selectedEmpId = empSelect.value;
        const searchQuery = (searchInput ? searchInput.value : '').toLowerCase().trim();

        // Get selected area/patch for activeDay
        const hiddenInput = document.getElementById('area-hidden-' + activeDay);
        const activeAreas = hiddenInput && hiddenInput.value ? hiddenInput.value.split(',').map(s => s.trim().toLowerCase()) : [];

        if (activeAreas.length === 0) {
            checklist.innerHTML = `
                <div class="text-center py-8 text-slate-400 text-sm font-medium">
                    <i class="fa-solid fa-map-pin text-2xl block mb-2 text-slate-300"></i>
                    Please select at least one Area / Patch for this day first.
                </div>
            `;
            return;
        }

        // Filter doctors by selected employee AND active areas
        const filtered = allDoctors.filter(d => {
            let belongs = false;
            if (d.assigned_to_user_id == selectedEmpId) {
                belongs = true;
            } else if (!d.assigned_to_user_id && d.created_by_user_id == selectedEmpId) {
                belongs = true;
            } else if (selectedEmpId == targetUserId) {
                // If viewing the plan owner's doctors, show all doctors accessible to them in this area
                belongs = true;
            }
            if (!belongs) return false;
            
            // Area/Patch matching (case-insensitive & trimmed comparison array search)
            const dArea = d.area_patch ? d.area_patch.trim().toLowerCase() : '';
            const matchesArea = activeAreas.includes(dArea);
            if (!matchesArea) return false;

            if (searchQuery !== '') {
                return d.name.toLowerCase().includes(searchQuery) ||
                       (d.speciality && d.speciality.toLowerCase().includes(searchQuery)) ||
                       (d.area_patch && d.area_patch.toLowerCase().includes(searchQuery));
            }
            return true;
        });

        if (filtered.length === 0) {
            checklist.innerHTML = `
                <div class="text-center py-8 text-slate-400 text-sm font-medium">
                    <i class="fa-solid fa-stethoscope text-2xl block mb-2 text-slate-205 text-slate-200"></i> 
                    No doctors mapped to the selected areas for this representative.
                </div>
            `;
            return;
        }

        const currentSelection = selectedDoctorsByDay[activeDay] || [];

        checklist.innerHTML = filtered.map(d => {
            const isChecked = currentSelection.includes(Number(d.id)) || currentSelection.includes(String(d.id));
            return `
                <label class="flex items-center px-4 py-3 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl cursor-pointer text-xs font-semibold text-slate-700 transition-all select-none">
                    <input type="checkbox" class="mr-3 rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 border-slate-300 territory-checkbox" 
                           value="${d.id}" 
                           onchange="toggleDoctorSelection(this, ${d.id})"
                           ${isChecked ? 'checked' : ''}>
                    <div class="flex flex-col flex-1 min-w-0 ps-4">
                        <span class="truncate font-bold text-slate-800 text-sm" title="${d.name}">${d.name}</span>
                        <div class="flex flex-wrap items-center gap-2 mt-1 text-[11px] text-slate-400 font-medium">
                            <span class="flex items-center gap-0.5"><i class="fa-solid fa-stethoscope text-slate-350 mr-0.5"></i>${d.speciality || 'General'}</span>
                            <span class="text-slate-300">•</span>
                            <span class="flex items-center gap-0.5"><i class="fa-solid fa-map-pin text-slate-350 mr-0.5"></i>${d.area_patch || '-'}</span>
                        </div>
                    </div>
                </label>
            `;
        }).join('');
    };

    window.submitAddDoctorModal = function () {
        const dayId = activeDay;
        const input = document.getElementById('doc-input-' + dayId);
        if (input) {
            // Store stringified array for backwards compatibility with controller parsing
            input.value = JSON.stringify((selectedDoctorsByDay[dayId] || []).map(String));
        }

        const countSpan = document.getElementById('dr-count-text-' + dayId);
        const detailsLink = document.getElementById('dr-view-details-' + dayId);
        const detailsBtn = document.getElementById('dr-row-details-btn-' + dayId);
        const count = (selectedDoctorsByDay[dayId] || []).length;

        if (countSpan) {
            if (count === 0) {
                countSpan.textContent = 'No doctors selected';
                countSpan.className = 'text-[11px] font-bold px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full';
                if (detailsLink) detailsLink.classList.add('hidden');
                if (detailsBtn) {
                    detailsBtn.classList.add('opacity-40');
                    detailsBtn.classList.add('pointer-events-none');
                }
            } else {
                countSpan.textContent = count + ' Doctor' + (count === 1 ? '' : 's');
                countSpan.className = 'text-[11px] font-bold px-2 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-full';
                if (detailsLink) detailsLink.classList.remove('hidden');
                if (detailsBtn) {
                    detailsBtn.classList.remove('opacity-40');
                    detailsBtn.classList.remove('pointer-events-none');
                }
            }
        }

        closeAddDoctorModal();

        // Trigger CodeIgniter draft auto-saver save
        if (window.sendDraft) {
            window.sendDraft();
        }
    };

    /* ── View Plan Modal ── */
    window.openViewPlanModal = function (dayId, dateStr) {
        const modal  = document.getElementById('view-plan-modal');
        const list   = document.getElementById('modal-doctor-list');
        const dateEl = document.getElementById('modal-date');
        if (dateEl) dateEl.textContent = 'Scheduled for: ' + dateStr;

        const docIds = selectedDoctorsByDay[dayId] || [];
        if (docIds.length === 0) {
            list.innerHTML = `
                <div class="text-center py-8 text-slate-400">
                    <i class="fa-solid fa-stethoscope text-3xl mb-2 text-slate-350 block"></i>
                    <p class="text-sm font-medium">No doctors scheduled for this date.</p>
                </div>
            `;
        } else {
            list.innerHTML = docIds.map(id => {
                const doc = allDoctors.find(d => Number(d.id) === Number(id));
                const name = doc ? doc.name : ('Doctor ID: ' + id);
                const spec = doc ? (doc.speciality || 'General') : '';
                const area = doc ? (doc.area_patch || '-') : '-';
                return `
                    <li class="flex items-center justify-between p-3.5 bg-indigo-50/50 border border-indigo-100/50 rounded-2xl hover:bg-indigo-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="bg-indigo-100 text-indigo-650 text-indigo-600 h-9 w-9 rounded-xl flex items-center justify-center font-bold">
                                <i class="fa-solid fa-user-doctor text-sm"></i>
                            </div>
                            <div>
                                <span class="text-slate-850 text-slate-800 font-bold block text-sm">${name}</span>
                                <div class="flex flex-wrap items-center gap-2 mt-0.5 text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                                    <span>${spec}</span>
                                    <span>•</span>
                                    <span class="flex items-center text-indigo-600 font-semibold"><i class="fa-solid fa-map-pin mr-1 text-[9px]"></i>${area}</span>
                                </div>
                            </div>
                        </div>
                    </li>
                `;
            }).join('');
        }

        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                modal.classList.add('opacity-100');
                modal.querySelector('.popup-card').classList.remove('scale-95');
                modal.querySelector('.popup-card').classList.add('scale-100');
            }, 50);
        }
    };

    window.closeViewPlanModal = function () {
        const modal = document.getElementById('view-plan-modal');
        if (modal) {
            modal.querySelector('.popup-card').classList.remove('scale-100');
            modal.querySelector('.popup-card').classList.add('scale-95');
            modal.classList.remove('opacity-100');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 200);
        }
    };

    /* ── Manual Draft Trigger (Save Draft button) ── */
    window.triggerDraftSave = function () {
        if (window.sendDraft) window.sendDraft();
    };

    /* ── Auto-Save Draft Setup ── */
    const form = document.getElementById('tour-plan-form');
    if (form && form.dataset.canEdit === '1') {
        const indicator = document.getElementById('plan-autosave-indicator');
        let timer = null, inFlight = null;

        const setIndicator = (txt, cls) => {
            if (!indicator) return;
            indicator.innerHTML = txt;
            indicator.className   = 'text-xs font-semibold ' + (cls || 'text-slate-500');
        };

        window.sendDraft = function () {
            const fd = new FormData();
            form.querySelectorAll('input:not([type=file]), select, textarea').forEach(el => {
                if (!el.name || el.disabled) return;
                fd.append(el.name, el.value || '');
            });
            if (inFlight) inFlight.abort();
            const ctrl = new AbortController();
            inFlight = ctrl;
            setIndicator('<i class="fa-solid fa-spinner animate-spin"></i> Saving...', 'text-slate-400');

            fetch('/phadmin/plans/draft', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: ctrl.signal,
                credentials: 'same-origin',
            })
            .then(r => r.json().then(j => ({ ok: r.ok, json: j })))
            .then(({ ok, json }) => {
                inFlight = null;
                if (ok && json && json.ok) {
                    setIndicator('<i class="fa-solid fa-cloud-arrow-up text-emerald-500"></i> Draft saved at ' + (json.saved_at || ''), 'text-emerald-600');
                } else {
                    setIndicator('<i class="fa-solid fa-circle-exclamation text-rose-500"></i> Save failed' + (json && json.message ? ': ' + json.message : ''), 'text-rose-600');
                }
            })
            .catch(err => {
                inFlight = null;
                if (err && err.name === 'AbortError') return;
                setIndicator('<i class="fa-solid fa-triangle-exclamation text-rose-500"></i> Save failed (network)', 'text-rose-600');
            });
        };

        const schedule = () => {
            clearTimeout(timer);
            timer = setTimeout(window.sendDraft, 1500);
            setIndicator('<i class="fa-solid fa-pencil text-slate-350"></i> Editing...', 'text-slate-400');
        };

        form.addEventListener('input',  e => { if (e.target.type !== 'file') schedule(); });
        form.addEventListener('change', e => { if (e.target.type !== 'file') schedule(); });
        form.addEventListener('submit', () => { clearTimeout(timer); if (inFlight) inFlight.abort(); });
    }
})();
</script>
<?= $this->endSection() ?>
