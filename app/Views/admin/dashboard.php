<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-head flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Executive Dashboard</h2>
        <p class="text-sm text-slate-500 mt-1">Founder & Super Admin real-time analytics, pipeline performance, and reporting KPIs.</p>
    </div>
    
    <!-- Auto-Refresh Controls -->
    <div class="flex items-center gap-2 bg-white px-3.5 py-2 rounded-xl border border-slate-200 shadow-xs">
        <i class="fa-solid fa-arrows-rotate text-slate-400 text-xs animate-spin-slow"></i>
        <label for="auto-refresh-select" class="text-xs font-bold text-slate-650 uppercase tracking-wider">Auto-Refresh:</label>
        <select id="auto-refresh-select" class="bg-transparent border-0 text-xs font-bold text-slate-800 p-0 m-0 w-28 focus:outline-none cursor-pointer" onchange="setupAutoRefresh(this.value)">
            <option value="off">Disabled</option>
            <option value="30000">30 Seconds</option>
            <option value="60000">60 Seconds</option>
            <option value="300000">5 Minutes</option>
        </select>
    </div>
</div>

<!-- Filters Panel -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/30 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-filter text-indigo-600"></i>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider m-0">Dashboard Scope Filter</h3>
        </div>
        <div>
            <a href="/phadmin/dashboard/export?month=<?= $selectedMonthVal ?>&year=<?= $selectedYearVal ?>&employee_id=<?= $selectedEmployeeId ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition border-0 cursor-pointer">
                <i class="fa-solid fa-file-excel text-xs"></i> Export Dashboard Data
            </a>
        </div>
    </div>
    
    <form method="get" action="/phadmin/dashboard" class="p-6 m-0 border-0 bg-transparent">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="filter_month" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Month</label>
                <select id="filter_month" name="month" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition bg-white font-medium">
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
                <select id="filter_year" name="year" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition bg-white font-medium">
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
                <select id="filter_employee" name="employee_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition bg-white font-medium" <?= ($role === 'mr') ? 'disabled' : '' ?>>
                    <?php if ($role !== 'mr'): ?>
                        <option value="">All Employees</option>
                    <?php endif; ?>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= esc($employee['id']) ?>" <?= ((int)$selectedEmployeeId === (int)$employee['id']) ? 'selected' : '' ?>><?= esc($employee['name']) ?> (<?= esc(strtoupper($employee['role'])) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-sm shadow-md transition border-0 cursor-pointer text-center">
                    Apply Filter
                </button>
                <a href="/phadmin/dashboard" class="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm transition border border-slate-300 cursor-pointer text-center flex items-center justify-center no-underline">
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

<!-- KPI Cards Grid -->
<div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
    <div class="bg-white p-4.5 p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Primary Sales</span>
        <h3 class="text-lg font-extrabold text-slate-800 mt-1">₹ <?= number_format($totalPrimarySales, 2) ?></h3>
        <span class="text-[9px] font-bold text-emerald-600 mt-2 block"><i class="fa-solid fa-arrow-trend-up"></i> Channel Inflow</span>
    </div>
    
    <div class="bg-white p-4.5 p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Secondary Sales</span>
        <h3 class="text-lg font-extrabold text-indigo-700 mt-1">₹ <?= number_format($totalSecondarySales, 2) ?></h3>
        <span class="text-[9px] font-bold text-indigo-600 mt-2 block"><i class="fa-solid fa-shop"></i> Stockist Outflow</span>
    </div>
    
    <div class="bg-white p-4.5 p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Stockists</span>
        <h3 class="text-lg font-extrabold text-slate-800 mt-1"><?= $totalStockistsCount ?></h3>
        <span class="text-[9px] font-bold text-slate-400 mt-2 block"><i class="fa-solid fa-warehouse"></i> Scoped Outlets</span>
    </div>
    
    <div class="bg-white p-4.5 p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Active MRs</span>
        <h3 class="text-lg font-extrabold text-slate-800 mt-1"><?= $activeMrsCount ?></h3>
        <span class="text-[9px] font-bold text-slate-400 mt-2 block"><i class="fa-solid fa-user-tie"></i> Regional reps</span>
    </div>

    <div class="bg-white p-4.5 p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Submitted Entries</span>
        <h3 class="text-lg font-extrabold text-emerald-700 mt-1"><?= $submittedEntriesCount ?></h3>
        <span class="text-[9px] font-bold text-emerald-600 mt-2 block"><i class="fa-solid fa-circle-check"></i> Completed pipeline</span>
    </div>

    <div class="bg-white p-4.5 p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pending Entries</span>
        <h3 class="text-lg font-extrabold text-amber-700 mt-1"><?= $pendingEntriesCount ?></h3>
        <span class="text-[9px] font-bold text-amber-600 mt-2 block"><i class="fa-solid fa-circle-notch animate-spin-slow"></i> Action needed</span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Monthly Target vs Achievement Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between">
        <div>
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4">Target vs Achievement</h3>
            
            <div class="flex justify-between items-end mb-1">
                <span class="text-xs text-slate-500 font-bold">Progress Rate</span>
                <span class="text-lg font-extrabold text-indigo-700"><?= $targetAchievementPercent ?>%</span>
            </div>
            
            <!-- Progress Bar -->
            <div class="w-full bg-slate-100 rounded-full h-3.5 mb-4.5 overflow-hidden">
                <div class="bg-indigo-600 h-full rounded-full transition-all duration-500" style="width: <?= $targetAchievementPercent ?>%"></div>
            </div>
        </div>
        
        <div class="space-y-2.5 pt-4 border-t border-slate-100">
            <div class="flex justify-between text-xs font-semibold">
                <span class="text-slate-500">Monthly Target:</span>
                <span class="text-slate-800 font-bold">₹ <?= number_format($monthlyTarget, 2) ?></span>
            </div>
            <div class="flex justify-between text-xs font-semibold">
                <span class="text-slate-500">Achieved:</span>
                <span class="text-slate-800 font-bold">₹ <?= number_format($totalSecondarySales, 2) ?></span>
            </div>
            <div class="flex justify-between text-xs font-semibold pt-1 border-t border-dashed border-slate-200">
                <span class="text-slate-500">Gap to Target:</span>
                <span class="<?= $targetGap > 0 ? 'text-amber-700 font-extrabold' : 'text-emerald-700 font-extrabold' ?>">
                    ₹ <?= number_format($targetGap, 2) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- MR Performance Analysis Panel -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col lg:col-span-2 justify-between">
        <div>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider m-0">MR Performance Analytics</h3>
                <div class="relative w-full sm:w-60">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input id="search-mr-perf" class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-350 border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder-slate-400" placeholder="Filter MRs by name..." oninput="filterMrPerformance()">
                </div>
            </div>
            
            <div class="overflow-x-auto max-h-52 overflow-y-auto">
                <table id="table-mr-perf" class="w-full border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 border-b border-slate-100 font-bold uppercase tracking-wider text-[10px]">
                            <th class="py-2.5 px-3 text-left">Employee Name</th>
                            <th class="py-2.5 px-3 text-right">MR-wise Sales</th>
                            <th class="py-2.5 px-3 text-center">Achievement %</th>
                            <th class="py-2.5 px-3 text-center">Pending Stockists</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($mrPerformance as $row): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors mr-perf-row" data-name="<?= esc(strtolower($row['name'])) ?>">
                                <td class="py-2.5 px-3 font-bold text-slate-800"><?= esc($row['name']) ?></td>
                                <td class="py-2.5 px-3 text-right font-semibold text-slate-700">₹ <?= number_format($row['sales_value'], 2) ?></td>
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-16 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-indigo-500 h-full" style="width: <?= $row['achievement_percent'] ?>%"></div>
                                        </div>
                                        <span class="font-bold text-indigo-700 w-8 text-right"><?= $row['achievement_percent'] ?>%</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <?php if ($row['pending_count'] > 0): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-100 font-bold text-[10px] cursor-pointer" title="<?= esc($row['pending_names']) ?>">
                                            <i class="fa-solid fa-circle-exclamation mr-1 text-[9px]"></i>
                                            <?= $row['pending_count'] ?> pending
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 font-bold text-[10px]">
                                            <i class="fa-solid fa-circle-check mr-1 text-[9px]"></i> All clear
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($mrPerformance)): ?>
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400">No MR performance data available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Pending Sales Entries Panel -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col lg:col-span-2 justify-between">
        <div>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider m-0">Pending / Not Started Sales Return Pipeline</h3>
                <div class="relative w-full sm:w-60">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input id="search-pending-entries" class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-350 border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder-slate-400" placeholder="Filter stockist/MR..." oninput="filterPendingEntries()">
                </div>
            </div>
            
            <div class="overflow-x-auto max-h-56 overflow-y-auto">
                <table id="table-pending-entries" class="w-full border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 border-b border-slate-100 font-bold uppercase tracking-wider text-[10px]">
                            <th class="py-2.5 px-3 text-left">Employee Name</th>
                            <th class="py-2.5 px-3 text-left">Stockist Name</th>
                            <th class="py-2.5 px-3 text-center">Status</th>
                            <th class="py-2.5 px-3 text-center">Last Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($pendingList as $row): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors pending-entry-row" data-search="<?= esc(strtolower($row['mr_name'] . ' ' . $row['stockist_name'])) ?>">
                                <td class="py-2.5 px-3 font-semibold text-slate-700"><?= esc($row['mr_name']) ?></td>
                                <td class="py-2.5 px-3 font-bold text-slate-800"><?= esc($row['stockist_name']) ?></td>
                                <td class="py-2.5 px-3 text-center">
                                    <?php if (strtolower((string)$row['status']) === 'pending'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 bg-amber-50 text-amber-700 border border-amber-100 text-[10px] font-bold rounded-full"><i class="fa-solid fa-clock mr-1 text-[9px]"></i>Pending</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-bold rounded-full"><i class="fa-solid fa-circle-notch mr-1 text-[9px] text-slate-400"></i>Not Started</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2.5 px-3 text-center text-slate-500 font-mono text-[10px]"><?= esc($row['last_updated']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pendingList)): ?>
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-circle-check text-3xl mb-2 block text-emerald-400"></i>
                                    All stockist sales entries are submitted for this period!
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Active Approvals Counter card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between">
        <div>
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4">Pending Approvals</h3>
            <div class="flex items-center justify-between mb-4">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Requires Action</span>
                <span class="text-3xl font-extrabold <?= $pendingApprovalsCount > 0 ? 'text-amber-600' : 'text-slate-400' ?>"><?= $pendingApprovalsCount ?></span>
            </div>
            <p class="text-xs text-slate-500 leading-relaxed mb-4">These include monthly tour plans, physician CRM additions, chemists directories, expense declarations, and brand sponsorships awaiting approval.</p>
        </div>
        
        <div>
            <a href="/phadmin/dashboard" class="block w-full py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl transition text-center no-underline border-0">
                Go to Pipeline Manager
            </a>
        </div>
    </div>
</div>

<!-- Current Month Reminders Widget -->
<?php if (!empty($currentMonthReminders)): ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6 relative overflow-hidden">
    <div class="relative z-10">
        <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-calendar-days text-slate-800 text-xl"></i>
                <h3 class="text-base font-extrabold text-slate-800 uppercase tracking-wider m-0">This Month's Events</h3>
            </div>
            <a href="/phadmin/birthdays" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors no-underline">Manage All</a>
        </div>
        <div class="flex overflow-x-auto gap-4 pb-2 snap-x">
            <?php foreach ($currentMonthReminders as $r): ?>
                <?php 
                    $icon = ($r['event_type'] === 'anniversary') ? 'fa-rings-wedding text-amber-500' : 'fa-cake-candles text-pink-500'; 
                    $dateStr = date('d M', strtotime($r['event_date']));
                    $isToday = (date('m-d', strtotime($r['event_date'])) === date('m-d'));
                ?>
                <div class="flex-shrink-0 w-64 bg-slate-50 rounded-xl p-4 border <?= $isToday ? 'border-amber-300 shadow-[0_0_10px_rgba(252,211,77,0.3)] bg-amber-50/50' : 'border-slate-100' ?> snap-start relative transition-transform hover:-translate-y-1 hover:shadow-md">
                    <?php if($isToday): ?>
                        <span class="absolute -top-2 -right-2 bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-md animate-pulse">TODAY!</span>
                    <?php endif; ?>
                    <div class="text-sm font-bold text-slate-500 mb-1"><?= $dateStr ?></div>
                    <div class="font-extrabold text-lg text-slate-800 mb-1 truncate" title="<?= esc($r['person_name']) ?>">
                        <i class="fa-solid <?= $icon ?> mr-1.5"></i> <?= esc($r['person_name']) ?>
                    </div>
                    <div class="text-xs text-slate-400 uppercase tracking-wider font-semibold"><?= esc($r['event_type']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Visits Activity Panel -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h3 class="text-base font-extrabold text-slate-800">Recent MR Field Visits Activity</h3>
            <p class="text-xs text-slate-500 mt-1">Listing of the most recent visits, presentation details, and doctor feedback log for the filtered scope.</p>
        </div>
        <div class="w-full md:w-auto">
            <div class="relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input id="search-visits" class="w-full md:w-72 pl-9 pr-4 py-2 bg-white border border-slate-350 border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition placeholder-slate-400" placeholder="Search visits MR/Doctor/Feedback..." oninput="filterVisits()">
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table id="table-visits" class="w-full border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 border-b border-slate-100 text-xs font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-4 text-left">MR Name</th>
                    <th class="py-3.5 px-4 text-left">Doctor Name</th>
                    <th class="py-3.5 px-4 text-left">Product Presentation</th>
                    <th class="py-3.5 px-4 text-left">Visit Date</th>
                    <th class="py-3.5 px-4 text-left">Feedback & Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php foreach ($recentVisits as $visit): ?>
                    <tr class="hover:bg-slate-50/30 transition-colors visit-row" data-search="<?= esc(strtolower(($visit['mr_name'] ?? '') . ' ' . ($visit['doctor_name'] ?? '') . ' ' . ($visit['feedback'] ?? ''))) ?>">
                        <td class="py-4 px-4 font-semibold text-slate-700">
                            <span class="inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-user-nurse text-slate-400 text-xs"></i>
                                <?= esc($visit['mr_name'] ?? '-') ?>
                            </span>
                        </td>
                        <td class="py-4 px-4 font-bold text-slate-800"><?= esc($visit['doctor_name'] ?? '-') ?></td>
                        <td class="py-4 px-4 font-semibold text-indigo-700"><?= esc($visit['product_name'] ?? '-') ?></td>
                        <td class="py-4 px-4 text-slate-650 font-medium font-mono text-xs"><?= esc(substr($visit['visit_date'], 0, 16)) ?></td>
                        <td class="py-4 px-4 text-slate-500 max-w-sm truncate" title="<?= esc($visit['feedback'] ?? '') ?>"><?= esc($visit['feedback'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentVisits)): ?>
                    <tr>
                        <td colspan="5" class="py-10 text-center text-slate-400">
                            <i class="fa-solid fa-user-clock text-4xl mb-2.5 block text-slate-300"></i>
                            <span class="font-semibold block text-slate-700">No Visits Tracked</span>
                            <span class="text-xs block text-slate-450 mt-1">No presentation reporting exists for the selected scope.</span>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Live Search Filter for MR Performance Table
    function filterMrPerformance() {
        const query = document.getElementById('search-mr-perf').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.mr-perf-row');
        rows.forEach(row => {
            const name = row.dataset.name || '';
            row.style.display = name.includes(query) ? '' : 'none';
        });
    }

    // Live Search Filter for Pending Entries Table
    function filterPendingEntries() {
        const query = document.getElementById('search-pending-entries').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.pending-entry-row');
        rows.forEach(row => {
            const search = row.dataset.search || '';
            row.style.display = search.includes(query) ? '' : 'none';
        });
    }

    // Live Search Filter for Visits Table
    function filterVisits() {
        const query = document.getElementById('search-visits').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.visit-row');
        rows.forEach(row => {
            const search = row.dataset.search || '';
            row.style.display = search.includes(query) ? '' : 'none';
        });
    }

    // Auto-Refresh Setup using LocalStorage
    let refreshTimer = null;
    document.addEventListener("DOMContentLoaded", () => {
        const savedVal = localStorage.getItem("dashboard_refresh_interval") || "off";
        const select = document.getElementById("auto-refresh-select");
        if (select) {
            select.value = savedVal;
            setupAutoRefresh(savedVal, false);
        }
    });

    function setupAutoRefresh(intervalVal, save = true) {
        if (save) {
            localStorage.setItem("dashboard_refresh_interval", intervalVal);
        }

        if (refreshTimer) {
            clearInterval(refreshTimer);
            refreshTimer = null;
        }

        if (intervalVal !== "off") {
            const ms = parseInt(intervalVal);
            refreshTimer = setInterval(() => {
                window.location.reload();
            }, ms);
        }
    }
</script>

<?= $this->endSection() ?>
