<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-head flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-black text-slate-800 m-0">Reporting</h2>
        <span class="text-sm text-slate-500 font-semibold">Unified Daily Call Report & Visit Analytics</span>
    </div>
    <div class="actions">
        <?php if (in_array(($role ?? ''), ['owner', 'manager', 'mr'], true)): ?>
            <button type="button" class="btn-add bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2.5 rounded-xl shadow-lg shadow-indigo-100 transition flex items-center gap-2 border-0 cursor-pointer" data-popup-open="popup-submit-report">
                <i class="fa-solid fa-plus text-xs"></i> Submit DCR
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filters Panel -->
<div class="panel bg-white border border-slate-200 rounded-2xl p-5 mb-6 shadow-sm">
    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-600 mb-4 flex items-center gap-2">
        <i class="fa-solid fa-filter text-indigo-500"></i> Filters
    </h3>
    <form method="get" action="/phadmin/reports" class="m-0 border-0 p-0 bg-transparent grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 items-end">
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Date From</label>
            <input type="date" name="date_from" value="<?= esc($dateFrom ?? '') ?>" class="border border-slate-350 rounded-lg px-3 py-2 text-sm w-full outline-none focus:border-indigo-500 bg-white m-0">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Date To</label>
            <input type="date" name="date_to" value="<?= esc($dateTo ?? '') ?>" class="border border-slate-350 rounded-lg px-3 py-2 text-sm w-full outline-none focus:border-indigo-500 bg-white m-0">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Employee</label>
            <select name="employee_id" class="border border-slate-350 rounded-lg px-3 py-2 text-sm w-full outline-none focus:border-indigo-500 bg-white m-0">
                <option value="">All Employees</option>
                <?php foreach (($employees ?? []) as $employee): ?>
                    <option value="<?= esc($employee['id']) ?>" <?= ((int) ($selectedEmployeeId ?? 0) === (int) $employee['id']) ? 'selected' : '' ?>><?= esc($employee['name']) ?> (<?= esc(strtoupper($employee['role'])) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status</label>
            <select name="status" class="border border-slate-350 rounded-lg px-3 py-2 text-sm w-full outline-none focus:border-indigo-500 bg-white m-0">
                <option value="">All Statuses</option>
                <option value="locked" <?= ($selectedStatus ?? '') === 'locked' ? 'selected' : '' ?>>Locked</option>
                <option value="submitted" <?= ($selectedStatus ?? '') === 'submitted' ? 'selected' : '' ?>>Submitted</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2.5 rounded-lg text-sm flex-1 transition border-0 cursor-pointer">
                Apply
            </button>
            <?php if (($role ?? '') === 'owner'): ?>
                <a class="bg-slate-800 hover:bg-slate-900 text-white font-bold px-3 py-2.5 rounded-lg text-sm transition flex items-center justify-center border-0 text-center" href="/phadmin/reports/export?date_from=<?= urlencode((string) ($dateFrom ?? '')) ?>&date_to=<?= urlencode((string) ($dateTo ?? '')) ?>&employee_id=<?= urlencode((string) ($selectedEmployeeId ?? '')) ?>&status=<?= urlencode((string) ($selectedStatus ?? '')) ?>" title="Export to Excel">
                    <i class="fa-solid fa-file-excel text-lg"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Summary Dashboard Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden transition-transform hover:scale-[1.02] duration-300">
        <div class="absolute -right-4 -bottom-4 opacity-15 text-7xl"><i class="fa-solid fa-user-doctor"></i></div>
        <div class="text-xs uppercase tracking-wider opacity-85 font-bold mb-1">Total Doctor Calls</div>
        <div class="text-3xl font-black mb-1"><?= esc($summaryBox['totalDoctorCalls'] ?? 0) ?></div>
        <div class="text-xs opacity-90 font-medium">Avg: <strong class="font-bold"><?= esc(number_format((float) ($summaryBox['avgDoctorCalls'] ?? 0), 1)) ?></strong> / day</div>
    </div>
    
    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden transition-transform hover:scale-[1.02] duration-300">
        <div class="absolute -right-4 -bottom-4 opacity-15 text-7xl"><i class="fa-solid fa-flask"></i></div>
        <div class="text-xs uppercase tracking-wider opacity-85 font-bold mb-1">Total Chemist Calls</div>
        <div class="text-3xl font-black mb-1"><?= esc($summaryBox['totalChemistCalls'] ?? 0) ?></div>
        <div class="text-xs opacity-90 font-medium">Avg: <strong class="font-bold"><?= esc(number_format((float) ($summaryBox['avgChemistCalls'] ?? 0), 1)) ?></strong> / day</div>
    </div>

    <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden transition-transform hover:scale-[1.02] duration-300">
        <div class="absolute -right-4 -bottom-4 opacity-15 text-7xl"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        <div class="text-xs uppercase tracking-wider opacity-85 font-bold mb-1">Total POB Booked</div>
        <div class="text-3xl font-black mb-1">₹<?= esc(number_format((float) ($summaryBox['totalPob'] ?? 0), 2)) ?></div>
        <div class="text-xs opacity-90 font-medium">Avg: <strong class="font-bold">₹<?= esc(number_format((float) ($summaryBox['avgPob'] ?? 0), 2)) ?></strong> / day</div>
    </div>

    <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden transition-transform hover:scale-[1.02] duration-300">
        <div class="absolute -right-4 -bottom-4 opacity-15 text-7xl"><i class="fa-solid fa-chart-line"></i></div>
        <div class="text-xs uppercase tracking-wider opacity-85 font-bold mb-1">Total Calls</div>
        <div class="text-3xl font-black mb-1"><?= esc($summaryBox['totalCalls'] ?? 0) ?></div>
        <div class="text-xs opacity-90 font-medium">Combined doctor & chemist visits</div>
    </div>
</div>

<!-- Search Tool -->
<div class="flex justify-between items-center gap-4 mb-4">
    <span class="text-xs font-black text-slate-500 uppercase tracking-wider">Grouped Daily Call Reports</span>
    <div class="table-tools m-0">
        <input class="table-search border border-slate-350 rounded-lg px-3 py-2 text-sm bg-slate-50 focus:bg-white outline-none focus:border-indigo-500 w-full sm:w-64" id="search-dcr" type="search" placeholder="Search report text...">
    </div>
</div>

<!-- DCR Grouped list -->
<div class="space-y-6">
    <?php foreach (($dcrGroups ?? []) as $group): ?>
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            
            <!-- Card Header -->
            <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex flex-wrap justify-between items-center gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="bg-indigo-100 text-indigo-850 px-3 py-1.5 rounded-xl font-black text-xs flex items-center gap-1.5">
                        <i class="fa-solid fa-calendar-day"></i>
                        <?= esc($group['date']) ?>
                    </div>
                    <div>
                        <div class="font-black text-slate-800 text-sm flex items-center gap-2">
                            <span><?= esc($group['employee_name']) ?></span>
                            <span class="text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded font-extrabold uppercase"><?= esc($group['employee_role']) ?></span>
                        </div>
                        <div class="text-xs text-slate-500 font-semibold mt-0.5 flex flex-wrap items-center gap-x-3">
                            <span>Area: <strong class="text-slate-700"><?= esc($group['area'] ?? '-') ?></strong></span>
                            <span>Work With: <strong class="text-slate-700"><?= esc($group['work_with'] ?? 'Alone') ?></strong></span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Status Badge -->
                    <?php
                        $statusClass = strtolower($group['status'] ?? '') === 'locked' 
                            ? 'bg-rose-50 text-rose-700 border-rose-200' 
                            : 'bg-amber-50 text-amber-700 border-amber-200';
                        $statusText = strtoupper($group['status'] ?? 'submitted');
                    ?>
                    <span class="px-3 py-1 border rounded-full text-xs font-bold tracking-wide <?= $statusClass ?>">
                        <i class="fa-solid <?= strtolower($group['status'] ?? '') === 'locked' ? 'fa-lock' : 'fa-lock-open' ?> text-[10px] mr-1"></i>
                        <?= $statusText ?>
                    </span>

                    <!-- Super Admin Toggle Lock -->
                    <?php if (($role ?? '') === 'owner'): ?>
                        <form method="post" action="/phadmin/reports/toggle-lock" class="m-0 p-0 border-0 bg-transparent inline-block">
                            <input type="hidden" name="date" value="<?= esc($group['date']) ?>">
                            <input type="hidden" name="employee_id" value="<?= esc($group['employee_id']) ?>">
                            <?php if (strtolower($group['status'] ?? '') === 'locked'): ?>
                                <input type="hidden" name="action" value="unlock">
                                <button type="submit" class="bg-slate-100 hover:bg-slate-250 border border-slate-350 text-slate-700 font-bold px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                                    <i class="fa-solid fa-lock-open text-[10px]"></i> Unlock Report
                                </button>
                            <?php else: ?>
                                <input type="hidden" name="action" value="lock">
                                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5 border-0">
                                    <i class="fa-solid fa-lock text-[10px]"></i> Lock Report
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Card Body -->
            <div class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Doctor Visits Section (Left Column) -->
                <div>
                    <h4 class="text-xs font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 mb-2.5">
                        <i class="fa-solid fa-user-doctor text-emerald-500 text-sm"></i>
                        Doctors Visited (<?= count($group['doctors']) ?>)
                    </h4>
                    <div class="border border-slate-150 rounded-xl overflow-hidden bg-white shadow-sm">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold">
                                    <th class="p-3">Doctor Name</th>
                                    <th class="p-3">Area / City</th>
                                    <th class="p-3">Visit Time</th>
                                    <th class="p-3">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($group['doctors'] as $doc): ?>
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-3 font-bold text-slate-850"><?= esc($doc['name']) ?></td>
                                        <td class="p-3 font-semibold text-slate-600"><?= esc($doc['area'] ?? '-') ?></td>
                                        <td class="p-3 font-bold text-indigo-650 text-indigo-700"><?= esc($doc['time']) ?></td>
                                        <td class="p-3 text-slate-500 max-w-[200px] truncate" title="<?= esc($doc['remarks']) ?>">
                                            <?php if (trim((string) $doc['remarks']) !== ''): ?>
                                                <?= esc($doc['remarks']) ?>
                                            <?php else: ?>
                                                <span class="text-slate-400 italic">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($group['doctors'])): ?>
                                    <tr>
                                        <td colspan="4" class="p-8 text-center text-slate-450 italic">
                                            No doctor visits logged.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Chemist Section (Right Column) -->
                <div>
                    <h4 class="text-xs font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5 mb-2.5">
                        <i class="fa-solid fa-flask text-blue-500 text-sm"></i>
                        Chemists Met (<?= count($group['chemists']) ?>)
                    </h4>
                    <div class="border border-slate-150 rounded-xl overflow-hidden bg-white shadow-sm">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold">
                                    <th class="p-3">Chemist Name</th>
                                    <th class="p-3">Area / City</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($group['chemists'] as $chem): ?>
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-3 font-bold text-slate-850"><?= esc($chem['name']) ?></td>
                                        <td class="p-3 font-semibold text-slate-600"><?= esc($chem['area'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($group['chemists'])): ?>
                                    <tr>
                                        <td colspan="2" class="p-8 text-center text-slate-450 italic">
                                            No chemist visits logged.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    <?php endforeach; ?>

    <?php if (empty($dcrGroups ?? [])): ?>
        <div class="panel bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-450 shadow-sm">
            <i class="fa-solid fa-inbox text-5xl mb-3 block opacity-30"></i>
            <span class="font-bold text-sm">No daily call reports found matching current filters.</span>
        </div>
    <?php endif; ?>
</div>

<!-- DCR Submit Wizard Modal -->
<div id="popup-submit-report" class="popup-overlay" aria-hidden="true">
    <div class="popup-card max-w-2xl w-full bg-white rounded-2xl shadow-2xl border border-slate-100 flex flex-col p-0 overflow-hidden">
        
        <!-- Modal Head -->
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-250 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-extrabold text-slate-800 m-0">Submit Daily Call Report (DCR)</h3>
                <p class="text-xs text-slate-500 font-medium m-0">Log your doctor visits, chemist meetings, and POB bookings</p>
            </div>
            <button type="button" class="popup-close text-slate-400 hover:text-slate-600 bg-slate-200/50 hover:bg-slate-200 p-2 rounded-xl transition-all border-0 cursor-pointer leading-none flex items-center justify-center" data-popup-close="popup-submit-report">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Wizard Progress Steps -->
        <div class="px-6 py-3 bg-slate-50/50 border-b border-slate-150 flex items-center justify-around text-xs font-bold text-slate-400 select-none">
            <div id="step-badge-1" class="flex items-center gap-2 text-indigo-600 transition-colors">
                <span class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center border border-indigo-200 text-xs font-black">1</span>
                <span>Plan Section</span>
            </div>
            <div class="w-10 h-[2px] bg-slate-200" id="step-line-1"></div>
            <div id="step-badge-2" class="flex items-center gap-2 transition-colors">
                <span class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200 text-xs">2</span>
                <span>Entry Section</span>
            </div>
            <div class="w-10 h-[2px] bg-slate-200" id="step-line-2"></div>
            <div id="step-badge-3" class="flex items-center gap-2 transition-colors">
                <span class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200 text-xs">3</span>
                <span>Verify & Submit</span>
            </div>
        </div>

        <!-- Form container -->
        <form id="dcr-wizard-form" method="post" action="/phadmin/reports/submit" class="p-6 m-0 border-0 flex-1 overflow-y-auto" style="max-height: 62vh;">
            
            <!-- Step 1 Panel -->
            <div id="panel-step-1" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1">DCR Date <span class="text-rose-500">*</span></label>
                        <input type="date" id="wizard_report_date" name="report_date" value="<?= esc(date('Y-m-d')) ?>" required class="border border-slate-300 rounded-lg px-3 py-2 text-sm w-full outline-none focus:border-indigo-500 bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1">Joint Work With</label>
                        <select name="work_with" id="wizard_work_with" class="border border-slate-300 rounded-lg px-3 py-2 text-sm w-full outline-none focus:border-indigo-500 bg-white">
                            <option value="Alone">Alone (Self)</option>
                            <?php foreach (($users ?? []) as $user): ?>
                                <option value="<?= esc($user['name']) ?>"><?= esc($user['name']) ?> (<?= esc(strtoupper($user['role'])) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1">Area / Patch Visited <span class="text-rose-500">*</span></label>
                    <select name="area_patch[]" id="wizard_area_patch" multiple required class="border border-slate-300 rounded-lg px-3 py-2 text-sm w-full outline-none focus:border-indigo-500 bg-white m-0 h-24">
                        <!-- Options generated dynamically -->
                    </select>
                </div>

                <!-- Planned Doctors Checkboxes -->
                <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm bg-white">
                    <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                        <span class="text-xs font-extrabold text-slate-600 uppercase tracking-wider">1. Pre-planned Doctors</span>
                        <span id="planned-count-badge" class="text-[10px] bg-slate-200 text-slate-700 px-2 py-0.5 rounded-full font-bold">0 planned</span>
                    </div>
                    <div id="planned-doctors-list" class="max-h-40 overflow-y-auto divide-y divide-slate-100 px-4 py-2">
                        <div class="text-center py-6 text-slate-400 text-sm">
                            <i class="fa-solid fa-spinner fa-spin text-xl mb-1 block"></i>
                            Loading doctor plan...
                        </div>
                    </div>
                </div>

                <!-- Add Unplanned Doctor -->
                <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/60 shadow-sm space-y-2.5">
                    <span class="text-xs font-extrabold text-slate-600 uppercase tracking-wider block">2. Add Unplanned Doctor (Spot Visit)</span>
                    <div class="flex gap-2">
                        <select id="unplanned-doctor-select" disabled class="border border-slate-350 rounded-lg px-3 py-2 text-sm flex-1 outline-none focus:border-indigo-500 bg-white m-0">
                            <option value="">Select Area / Patch first...</option>
                        </select>
                        <button type="button" id="btn-add-unplanned" class="bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold px-4 py-2 rounded-lg transition text-sm border-0 cursor-pointer flex items-center gap-1">
                            <i class="fa-solid fa-plus text-xs"></i> Add
                        </button>
                    </div>
                </div>

                <!-- Checked Doctors list for step 2 -->
                <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm bg-white">
                    <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200">
                        <span class="text-xs font-extrabold text-slate-600 uppercase tracking-wider">3. Doctor Visit Checklists</span>
                    </div>
                    <div id="active-doctors-list" class="divide-y divide-slate-100 max-h-40 overflow-y-auto px-4 py-2">
                        <div class="text-slate-400 text-center py-6 text-sm">No doctors selected. Select from plan or add unplanned spot visits above.</div>
                    </div>
                </div>
                
                <div class="flex justify-end pt-2">
                    <button type="button" id="btn-step1-proceed" class="bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold px-6 py-3 rounded-xl transition flex items-center gap-2 shadow-lg shadow-indigo-150 border-0 cursor-pointer text-sm">
                        Proceed to Visit Entry <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Step 2 Panel -->
            <div id="panel-step-2" class="space-y-4 hidden">
                <div class="bg-indigo-50 border border-indigo-100 p-3.5 rounded-xl flex items-start gap-2.5 text-xs text-indigo-900 shadow-sm">
                    <i class="fa-solid fa-circle-info text-indigo-500 text-base mt-0.5"></i>
                    <div>
                        <strong class="font-bold">DCR VISIT ENTRY:</strong> Please specify the <strong class="font-extrabold text-rose-700">Visit Time (Mandatory)</strong> for all selected doctors. Remarks, Chemists, and POB values are optional and should only be entered if applicable.
                    </div>
                </div>

                <div id="doctor-details-forms-container" class="space-y-4">
                    <!-- Dynamic doctor entry cards -->
                </div>

                <div class="flex justify-between pt-4 border-t border-slate-150">
                    <button type="button" id="btn-step2-back" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold px-5 py-3 rounded-xl transition flex items-center gap-2 border border-slate-300 cursor-pointer text-sm">
                        <i class="fa-solid fa-arrow-left text-xs"></i> Back
                    </button>
                    <button type="button" id="btn-step2-proceed" class="bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold px-6 py-3 rounded-xl transition flex items-center gap-2 shadow-lg shadow-indigo-150 border-0 cursor-pointer text-sm">
                        Proceed to Verify <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Step 3 Panel -->
            <div id="panel-step-3" class="space-y-4 hidden">
                <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-xl flex items-start gap-3 text-xs text-indigo-950 shadow-sm">
                    <i class="fa-solid fa-circle-info text-indigo-600 text-lg mt-0.5"></i>
                    <div>
                        <strong class="font-bold">DCR Submission Note:</strong> Submitting this report will save all your visit logs for the day. Super Admin has the option to lock reports after review.
                    </div>
                </div>

                <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/60 shadow-sm space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wide text-slate-700 border-b border-slate-200 pb-2 m-0">DCR Settings Review</h4>
                    <div class="grid grid-cols-2 gap-4 text-xs font-semibold text-slate-600">
                        <div>Report Date: <strong class="text-slate-800 font-bold" id="summary-date">-</strong></div>
                        <div>Joint Working: <strong class="text-slate-800 font-bold" id="summary-joint">-</strong></div>
                        <div class="col-span-2">Area / Patch Visited: <strong class="text-slate-800 font-bold" id="summary-area">-</strong></div>
                    </div>
                </div>

                <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm bg-white">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-100 text-slate-650 font-bold uppercase border-b border-slate-200">
                            <tr>
                                <th class="p-3">Doctor Met</th>
                                <th class="p-3">Visit Time</th>
                                <th class="p-3">Chemist & POB</th>
                                <th class="p-3">Remarks</th>
                            </tr>
                        </thead>
                        <tbody id="summary-table-body" class="divide-y divide-slate-150">
                            <!-- Dynamic summary rows -->
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between pt-4 border-t border-slate-150">
                    <button type="button" id="btn-step3-back" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold px-5 py-3 rounded-xl transition flex items-center gap-2 border border-slate-300 cursor-pointer text-sm">
                        <i class="fa-solid fa-arrow-left text-xs"></i> Back
                    </button>
                    <button type="submit" id="btn-submit-dcr" class="bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold px-6 py-3 rounded-xl transition flex items-center gap-2 shadow-lg shadow-indigo-150 border-0 cursor-pointer text-sm">
                        <i class="fa-solid fa-paper-plane text-xs"></i> Submit Daily Report
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const allDoctors = <?= json_encode($doctors) ?>;
const allChemists = <?= json_encode($chemists) ?>;

document.addEventListener('DOMContentLoaded', function () {
    // Area/Patch dropdown list population & change events
    const areaSelect = document.getElementById('wizard_area_patch');
    if (areaSelect) {
        const uniqueAreas = new Set();
        allDoctors.forEach(doc => {
            if (doc.area_patch && typeof doc.area_patch === 'string') {
                const trimmed = doc.area_patch.trim();
                if (trimmed !== '') {
                    uniqueAreas.add(trimmed);
                }
            }
        });
        allChemists.forEach(ch => {
            if (ch.area && typeof ch.area === 'string') {
                const trimmed = ch.area.trim();
                if (trimmed !== '') {
                    uniqueAreas.add(trimmed);
                }
            }
        });
        const sortedAreas = Array.from(uniqueAreas).sort((a, b) => a.localeCompare(b));
        sortedAreas.forEach(area => {
            const opt = document.createElement('option');
            opt.value = area;
            opt.textContent = area;
            areaSelect.appendChild(opt);
        });
        areaSelect.addEventListener('change', handleAreaChange);
    }

    const openDcrBtn = document.querySelector('[data-popup-open="popup-submit-report"]');
    if (openDcrBtn) {
        openDcrBtn.addEventListener('click', function () {
            if (areaSelect) {
                Array.from(areaSelect.options).forEach(opt => opt.selected = false);
                handleAreaChange();
            }
        });
    }

    function handleAreaChange() {
        const selectedAreas = areaSelect ? Array.from(areaSelect.selectedOptions).map(o => o.value) : [];
        
        checkedDoctors.clear();
        spotDoctors = [];
        renderActiveDoctors();
        renderPlannedDoctors();

        if (selectedAreas.length === 0) {
            unplannedSelect.innerHTML = '<option value="">Select Area / Patch first...</option>';
            unplannedSelect.disabled = true;
        } else {
            const filteredDoctors = allDoctors.filter(d => {
                const docArea = d.area_patch ? d.area_patch.trim().toLowerCase() : '';
                return selectedAreas.some(area => area.trim().toLowerCase() === docArea);
            });
            if (filteredDoctors.length > 0) {
                unplannedSelect.innerHTML = '<option value="">Choose doctor from directory...</option>' + 
                    filteredDoctors.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
                unplannedSelect.disabled = false;
            } else {
                unplannedSelect.innerHTML = '<option value="">No doctors in this area</option>';
                unplannedSelect.disabled = true;
            }
        }
    }

    // Custom Smart real-time search for Grouped Cards
    const searchInput = document.getElementById('search-dcr');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.space-y-6 > div');
            cards.forEach(card => {
                if (query === '') {
                    card.style.display = '';
                } else {
                    const text = card.textContent.toLowerCase();
                    card.style.display = text.includes(query) ? '' : 'none';
                }
            });
        });
    }

    // State
    let plannedDoctors = [];
    let spotDoctors = [];
    let checkedDoctors = new Map();

    const reportDateInput = document.getElementById('wizard_report_date');
    const plannedList = document.getElementById('planned-doctors-list');
    const plannedCountBadge = document.getElementById('planned-count-badge');
    const unplannedSelect = document.getElementById('unplanned-doctor-select');
    const btnAddUnplanned = document.getElementById('btn-add-unplanned');
    const activeList = document.getElementById('active-doctors-list');

    // Fetch planned doctors when DCR Date changes
    function loadPlannedDoctors() {
        const date = reportDateInput.value;
        if (!date) return;

        plannedList.innerHTML = '<div class="text-center py-6 text-slate-400 text-sm"><i class="fa-solid fa-spinner fa-spin text-xl mb-1 block"></i>Loading plan...</div>';
        plannedCountBadge.textContent = '0 planned';

        fetch(`/phadmin/reports/planned-doctors?date=${encodeURIComponent(date)}`)
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    plannedDoctors = data.doctors || [];
                    
                    if (data.planned_areas && data.planned_areas.length > 0) {
                        Array.from(areaSelect.options).forEach(opt => {
                            opt.selected = data.planned_areas.includes(opt.value);
                        });
                        handleAreaChange();
                    }
                    
                    renderPlannedDoctors();
                } else {
                    plannedList.innerHTML = '<div class="text-center py-6 text-rose-500 text-sm"><i class="fa-solid fa-triangle-exclamation text-xl mb-1 block"></i>Failed to load planned doctors.</div>';
                }
            })
            .catch(err => {
                plannedList.innerHTML = '<div class="text-center py-6 text-rose-500 text-sm"><i class="fa-solid fa-triangle-exclamation text-xl mb-1 block"></i>Network error loading plan.</div>';
            });
    }

    function renderPlannedDoctors() {
        const selectedAreas = areaSelect ? Array.from(areaSelect.selectedOptions).map(o => o.value) : [];
        if (selectedAreas.length === 0) {
            plannedList.innerHTML = '<div class="text-center py-6 text-slate-400 text-sm"><i class="fa-solid fa-map-pin text-xl block mb-1 opacity-60"></i>Please select an Area / Patch first.</div>';
            plannedCountBadge.textContent = '0 planned';
            return;
        }

        const filteredPlanned = plannedDoctors.filter(doc => {
            const docArea = doc.area_patch ? doc.area_patch.trim().toLowerCase() : '';
            return selectedAreas.some(area => area.trim().toLowerCase() === docArea);
        });

        if (filteredPlanned.length === 0) {
            plannedList.innerHTML = '<div class="text-center py-6 text-slate-400 text-sm"><i class="fa-solid fa-calendar-xmark text-xl block mb-1 opacity-60"></i>No doctors pre-planned for this Area.</div>';
            plannedCountBadge.textContent = '0 planned';
            return;
        }

        plannedCountBadge.textContent = `${filteredPlanned.length} planned`;
        plannedList.innerHTML = filteredPlanned.map(doc => {
            const isChecked = checkedDoctors.has(doc.id);
            return `
                <label class="flex items-center gap-3 py-2.5 px-1 hover:bg-slate-50 cursor-pointer transition">
                    <input type="checkbox" class="planned-doc-cb rounded text-indigo-600 focus:ring-indigo-500" value="${doc.id}" data-name="${doc.name}" ${isChecked ? 'checked' : ''}>
                    <span class="text-sm text-slate-700 font-semibold flex-1">${doc.name}</span>
                    <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Planned</span>
                </label>
            `;
        }).join('');

        // Re-attach listeners
        document.querySelectorAll('.planned-doc-cb').forEach(cb => {
            cb.addEventListener('change', function () {
                const id = parseInt(this.value);
                const name = this.dataset.name;
                if (this.checked) {
                    checkedDoctors.set(id, name);
                } else {
                    checkedDoctors.delete(id);
                }
                renderActiveDoctors();
            });
        });
    }

    // Add unplanned doctor
    btnAddUnplanned.addEventListener('click', function () {
        const idVal = unplannedSelect.value;
        if (!idVal) return;
        const id = parseInt(idVal);
        const option = unplannedSelect.options[unplannedSelect.selectedIndex];
        const name = option.text;

        if (checkedDoctors.has(id)) {
            alert('Doctor is already in your DCR checklist!');
            return;
        }

        checkedDoctors.set(id, name);
        if (!spotDoctors.some(d => d.id === id)) {
            spotDoctors.push({ id, name });
        }
        
        // Clear selection
        unplannedSelect.value = '';

        // If this doctor is also in planned, check it
        const plannedCb = document.querySelector(`.planned-doc-cb[value="${id}"]`);
        if (plannedCb) plannedCb.checked = true;

        renderActiveDoctors();
    });

    // Render active doctors list
    function renderActiveDoctors() {
        if (checkedDoctors.size === 0) {
            activeList.innerHTML = '<div class="text-slate-400 text-center py-6 text-sm">No doctors selected. Select from plan or add unplanned spot visits above.</div>';
            return;
        }

        let html = '';
        checkedDoctors.forEach((name, id) => {
            const isPlanned = plannedDoctors.some(d => d.id === id);
            html += `
                <div class="flex items-center justify-between py-2.5 px-1 bg-white">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-check-double text-indigo-500 text-sm"></i>
                        <span class="text-sm text-slate-700 font-bold">${name}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[9px] ${isPlanned ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'} px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">
                            ${isPlanned ? 'Planned' : 'Spot Visit'}
                        </span>
                        <button type="button" class="btn-remove-active text-slate-400 hover:text-rose-600 bg-transparent p-1 transition border-0 cursor-pointer" data-id="${id}">
                            <i class="fa-solid fa-trash-can text-sm"></i>
                        </button>
                    </div>
                </div>
            `;
        });

        activeList.innerHTML = html;

        // Attach listeners for remove buttons
        document.querySelectorAll('.btn-remove-active').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = parseInt(this.dataset.id);
                checkedDoctors.delete(id);
                
                // If it is in planned list, uncheck it
                const plannedCb = document.querySelector(`.planned-doc-cb[value="${id}"]`);
                if (plannedCb) plannedCb.checked = false;

                renderActiveDoctors();
            });
        });
    }

    reportDateInput.addEventListener('change', loadPlannedDoctors);
    
    // Initial planned doctor load
    loadPlannedDoctors();

    // Multi-step Wizard Navigation
    const stepBadge1 = document.getElementById('step-badge-1');
    const stepBadge2 = document.getElementById('step-badge-2');
    const stepBadge3 = document.getElementById('step-badge-3');
    const stepLine1 = document.getElementById('step-line-1');
    const stepLine2 = document.getElementById('step-line-2');

    const panelStep1 = document.getElementById('panel-step-1');
    const panelStep2 = document.getElementById('panel-step-2');
    const panelStep3 = document.getElementById('panel-step-3');

    // Step 1 Proceed
    document.getElementById('btn-step1-proceed').addEventListener('click', function () {
        if (checkedDoctors.size === 0) {
            alert('Please select at least one doctor visit to proceed.');
            return;
        }
        setStep(2);
        renderEntryCards();
    });

    // Step 2 Back
    document.getElementById('btn-step2-back').addEventListener('click', function () {
        setStep(1);
    });

    // Step 2 Proceed & Validate
    document.getElementById('btn-step2-proceed').addEventListener('click', function () {
        let valid = true;
        checkedDoctors.forEach((name, id) => {
            const timeInput = document.getElementById(`visit_time_${id}`);
            if (timeInput) {
                if (!timeInput.value) {
                    timeInput.classList.add('border-rose-500', 'bg-rose-50');
                    valid = false;
                } else {
                    timeInput.classList.remove('border-rose-500', 'bg-rose-50');
                }
            }
        });

        if (!valid) {
            alert('Please enter Visit Time for all selected doctors.');
            return;
        }

        setStep(3);
        renderSummary();
    });

    // Step 3 Back
    document.getElementById('btn-step3-back').addEventListener('click', function () {
        setStep(2);
    });

    function setStep(stepNum) {
        panelStep1.classList.toggle('hidden', stepNum !== 1);
        panelStep2.classList.toggle('hidden', stepNum !== 2);
        panelStep3.classList.toggle('hidden', stepNum !== 3);

        if (stepNum === 1) {
            stepBadge1.className = "flex items-center gap-2 text-indigo-650 font-black";
            stepBadge1.querySelector('span').className = "w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center border border-indigo-200 text-xs font-black";
            
            stepBadge2.className = "flex items-center gap-2 text-slate-400";
            stepBadge2.querySelector('span').className = "w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200 text-xs";
            
            stepBadge3.className = "flex items-center gap-2 text-slate-400";
            stepBadge3.querySelector('span').className = "w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200 text-xs";

            stepLine1.className = "w-10 h-[2px] bg-slate-200";
            stepLine2.className = "w-10 h-[2px] bg-slate-200";
        } else if (stepNum === 2) {
            stepBadge1.className = "flex items-center gap-2 text-emerald-650 font-bold";
            stepBadge1.querySelector('span').className = "w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center border border-emerald-200 text-emerald-800 text-xs font-bold";
            
            stepBadge2.className = "flex items-center gap-2 text-indigo-650 font-black";
            stepBadge2.querySelector('span').className = "w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center border border-indigo-200 text-xs font-black";
            
            stepBadge3.className = "flex items-center gap-2 text-slate-400";
            stepBadge3.querySelector('span').className = "w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200 text-xs";

            stepLine1.className = "w-10 h-[2px] bg-emerald-300";
            stepLine2.className = "w-10 h-[2px] bg-slate-200";
        } else if (stepNum === 3) {
            stepBadge1.className = "flex items-center gap-2 text-emerald-650 font-bold";
            stepBadge1.querySelector('span').className = "w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center border border-emerald-200 text-emerald-800 text-xs font-bold";
            
            stepBadge2.className = "flex items-center gap-2 text-emerald-650 font-bold";
            stepBadge2.querySelector('span').className = "w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center border border-emerald-200 text-emerald-800 text-xs font-bold";
            
            stepBadge3.className = "flex items-center gap-2 text-indigo-650 font-black";
            stepBadge3.querySelector('span').className = "w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center border border-indigo-200 text-xs font-black";

            stepLine1.className = "w-10 h-[2px] bg-emerald-300";
            stepLine2.className = "w-10 h-[2px] bg-emerald-300";
        }
    }

    // Render step 2 entry cards
    const formsContainer = document.getElementById('doctor-details-forms-container');
    function renderEntryCards() {
        let html = '';
        checkedDoctors.forEach((name, id) => {
            const isPlanned = plannedDoctors.some(d => d.id === id);
            const selectedArea = areaSelect ? areaSelect.value : '';
            let chemistOptions = '<option value="">Select Area / Patch first...</option>';
            if (selectedArea) {
                const filteredChemists = allChemists.filter(c => c.area && typeof c.area === 'string' && c.area.trim().toLowerCase() === selectedArea.trim().toLowerCase());
                if (filteredChemists.length > 0) {
                    chemistOptions = '<option value="">No Chemist Order</option>' + filteredChemists.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
                } else {
                    chemistOptions = '<option value="">No chemists in this area</option>';
                }
            }

            const oldTime = document.getElementById(`visit_time_${id}`)?.value || '';
            const oldRemarks = document.getElementById(`remarks_${id}`)?.value || '';
            const oldPob = document.getElementById(`pob_value_${id}`)?.value || '';

            html += `
                <div class="border border-slate-200 rounded-2xl p-4 bg-white shadow-sm hover:border-slate-300 transition-colors">
                    <input type="hidden" name="doctor_ids[]" value="${id}">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3.5">
                        <div class="flex items-center gap-2">
                            <div class="bg-indigo-50 text-indigo-600 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm"><i class="fa-solid fa-user-doctor"></i></div>
                            <span class="font-bold text-slate-800 text-sm">${name}</span>
                        </div>
                        <span class="text-[10px] ${isPlanned ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'} px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">
                            ${isPlanned ? 'Planned' : 'Spot Visit'}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-extrabold uppercase tracking-wide text-slate-500 mb-1">Visit Time <span class="text-rose-500">*</span></label>
                            <input type="time" name="visit_time_${id}" id="visit_time_${id}" value="${oldTime}" required class="border border-slate-350 rounded-lg px-3 py-2 text-sm w-full outline-none focus:border-indigo-500 m-0">
                        </div>
                        <div>
                            <label class="block text-[11px] font-extrabold uppercase tracking-wide text-slate-500 mb-1">Chemist Order/Booking (Optional)</label>
                            <select name="chemist_id_${id}" id="chemist_id_${id}" class="border border-slate-355 rounded-lg px-3 py-2 text-sm w-full outline-none focus:border-indigo-500 bg-white m-0">
                                <option value="">No Chemist Order</option>
                                ${chemistOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-extrabold uppercase tracking-wide text-slate-500 mb-1">POB Value (₹) (Optional)</label>
                            <input type="number" step="0.01" min="0" name="pob_value_${id}" id="pob_value_${id}" value="${oldPob}" placeholder="0.00" class="border border-slate-350 rounded-lg px-3 py-2 text-sm w-full outline-none focus:border-indigo-500 m-0">
                        </div>
                        <div>
                            <label class="block text-[11px] font-extrabold uppercase tracking-wide text-slate-500 mb-1">Remarks / Specific Feedback (Optional)</label>
                            <input type="text" name="remarks_${id}" id="remarks_${id}" value="${oldRemarks}" placeholder="Patient flow, Rx potential, etc." class="border border-slate-350 rounded-lg px-3 py-2 text-sm w-full outline-none focus:border-indigo-500 m-0">
                        </div>
                    </div>
                </div>
            `;
        });

        formsContainer.innerHTML = html;
    }

    // Render step 3 summary
    const summaryTableBody = document.getElementById('summary-table-body');
    function renderSummary() {
        document.getElementById('summary-date').textContent = reportDateInput.value;
        document.getElementById('summary-joint').textContent = document.getElementById('wizard_work_with').value;
        document.getElementById('summary-area').textContent = document.getElementById('wizard_area_patch').value || 'N/A';

        let html = '';
        checkedDoctors.forEach((name, id) => {
            const time = document.getElementById(`visit_time_${id}`).value;
            const remarks = document.getElementById(`remarks_${id}`).value || '<span class="text-slate-400 italic">N/A</span>';
            
            const chemistSelect = document.getElementById(`chemist_id_${id}`);
            let chemistName = '-';
            if (chemistSelect && chemistSelect.value) {
                chemistName = chemistSelect.options[chemistSelect.selectedIndex].text;
            }
            const pobVal = parseFloat(document.getElementById(`pob_value_${id}`).value || '0');
            
            let chemistDetails = '-';
            if (chemistName !== '-') {
                chemistDetails = `${chemistName}`;
                if (pobVal > 0) {
                    chemistDetails += ` (₹${pobVal.toFixed(2)})`;
                }
            } else if (pobVal > 0) {
                chemistDetails = `Direct POB (₹${pobVal.toFixed(2)})`;
            }

            html += `
                <tr class="hover:bg-slate-50/50 border-b border-slate-100">
                    <td class="p-3 font-bold text-slate-800">${name}</td>
                    <td class="p-3 font-bold text-indigo-700">${time}</td>
                    <td class="p-3 font-medium text-slate-600">${chemistDetails}</td>
                    <td class="p-3 text-slate-500 italic max-w-[150px] truncate" title="${remarks}">${remarks}</td>
                </tr>
            `;
        });

        summaryTableBody.innerHTML = html;
    }
});
</script>
<?= $this->endSection() ?>
