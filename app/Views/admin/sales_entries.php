<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-head flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Sales Directory</h2>
        <p class="text-sm text-slate-500 mt-1">Review stockist-wise sales returns, monthly submission progress, and approval pipelines.</p>
    </div>
</div>

<!-- Filters Panel -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/30 flex items-center gap-2">
        <i class="fa-solid fa-filter text-indigo-600"></i>
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider m-0">Directory Filter & Scope</h3>
    </div>
    
    <form method="get" action="/phadmin/sales-entries" class="p-6 m-0 border-0 bg-transparent">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="filter_month" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Month</label>
                <select id="filter_month" name="month" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white font-medium">
                    <?php
                        $months = [
                            '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
                            '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
                            '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
                        ];
                        foreach ($months as $num => $name):
                    ?>
                        <option value="<?= $num ?>" <?= ($selectedMonthVal === $num) ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="filter_year" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Year</label>
                <select id="filter_year" name="year" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white font-medium">
                    <?php
                        $currentYear = (int)date('Y');
                        for ($y = $currentYear - 2; $y <= $currentYear + 2; $y++):
                    ?>
                        <option value="<?= $y ?>" <?= ((int)$selectedYearVal === $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div>
                <label for="filter_employee" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Employee / MR</label>
                <select id="filter_employee" name="employee_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white font-medium">
                    <option value="">Select Employee</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= esc($employee['id']) ?>" <?= ((int)$selectedEmployeeId === (int)$employee['id']) ? 'selected' : '' ?>><?= esc($employee['name']) ?> (<?= esc(strtoupper($employee['role'])) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-sm shadow-md transition-all duration-200 border-0 cursor-pointer text-center">
                    Apply
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold rounded-lg text-sm shadow-md transition-all duration-200 border-0 cursor-pointer text-center">
                    Save & Apply
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Stockists Listing Table Panel -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h3 class="text-base font-extrabold text-slate-800">Master Stockist Sales Pipeline</h3>
            <p class="text-xs text-slate-500 mt-1">Listing of regional stockists assigned to the filtered employee and their sales matrix status for <strong><?= date('F Y', strtotime($selectedMonth . '-01')) ?></strong>.</p>
        </div>
        <div class="w-full md:w-auto">
            <div class="relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input id="search-sales" class="w-full md:w-72 pl-9 pr-4 py-2 bg-white border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-slate-400" placeholder="Filter list by stockist/area...">
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table id="table-sales" class="w-full border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 border-b border-slate-100 text-xs font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-4 text-left">Employee Name</th>
                    <th class="py-3.5 px-4 text-left">Stockist Name</th>
                    <th class="py-3.5 px-4 text-left">City</th>
                    <th class="py-3.5 px-4 text-left">Area</th>
                    <th class="py-3.5 px-4 text-center">Status</th>
                    <th class="py-3.5 px-4 text-center">Submitted Date</th>
                    <th class="py-3.5 px-4 text-center">Pending Date</th>
                    <th class="py-3.5 px-4 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (empty($selectedEmployeeId)): ?>
                    <tr>
                        <td colspan="8" class="py-10 text-center text-slate-400">
                            <i class="fa-solid fa-filter text-4xl mb-2.5 block text-slate-300"></i>
                            <span class="font-semibold block text-slate-700">No Employee Filter Applied</span>
                            <span class="text-xs block text-slate-450 mt-1">Please select an Employee / MR and click "Apply" to view the sales pipeline.</span>
                        </td>
                    </tr>
                <?php elseif (empty($stockistRows)): ?>
                    <tr>
                        <td colspan="8" class="py-10 text-center text-slate-400">
                            <i class="fa-solid fa-boxes-packing text-4xl mb-2.5 block text-slate-300"></i>
                            <span class="font-semibold block text-slate-700">No Stockists Associated</span>
                            <span class="text-xs block text-slate-450 mt-1">No stockists are registered under the selected employee's HQ territory or area.</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($stockistRows as $row): ?>
                        <tr class="hover:bg-slate-50/40 transition-colors cursor-pointer" onclick="window.location.href='/phadmin/sales-entries/detail?stockist_id=<?= $row['id'] ?>&month=<?= $selectedMonth ?>&employee_id=<?= $selectedEmployeeId ?>'">
                            <td class="py-4 px-4 font-semibold text-slate-650"><?= esc($row['employee_name']) ?></td>
                            <td class="py-4 px-4 font-bold text-slate-800 hover:text-indigo-600 transition-colors"><?= esc($row['name']) ?></td>
                            <td class="py-4 px-4 text-slate-600 font-medium"><?= esc($row['city'] ?: '-') ?></td>
                            <td class="py-4 px-4 text-slate-600 font-medium"><?= esc($row['area'] ?: '-') ?></td>
                            <td class="py-4 px-4 text-center">
                                <?php
                                    $st = strtolower((string)$row['status']);
                                    if ($st === 'submitted') {
                                        echo '<span class="inline-flex items-center px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-100 text-xs font-bold rounded-full"><i class="fa-solid fa-circle-check mr-1.5 text-emerald-500 text-[9px]"></i>Submitted</span>';
                                    } elseif ($st === 'pending') {
                                        echo '<span class="inline-flex items-center px-2.5 py-0.5 bg-amber-50 text-amber-700 border border-amber-100 text-xs font-bold rounded-full"><i class="fa-solid fa-clock mr-1.5 text-amber-500 text-[9px]"></i>Pending</span>';
                                    } else {
                                        echo '<span class="inline-flex items-center px-2.5 py-0.5 bg-slate-100 text-slate-600 border border-slate-200 text-xs font-bold rounded-full"><i class="fa-solid fa-circle-notch mr-1.5 text-slate-405 text-slate-400 text-[9px]"></i>Not Started</span>';
                                    }
                                ?>
                            </td>
                            <td class="py-4 px-4 text-center text-slate-500 font-mono text-xs"><?= esc($row['submitted']) ?></td>
                            <td class="py-4 px-4 text-center text-slate-500 font-mono text-xs"><?= esc($row['pending']) ?></td>
                            <td class="py-4 px-4 text-center" onclick="event.stopPropagation();">
                                <a href="/phadmin/sales-entries/detail?stockist_id=<?= $row['id'] ?>&month=<?= $selectedMonth ?>&employee_id=<?= $selectedEmployeeId ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition-all border border-indigo-100" title="Edit Sales Entry">
                                    <i class="fa-solid fa-user-pen text-sm"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    bindTableSearch('search-sales', 'table-sales');
</script>
<?= $this->endSection() ?>
