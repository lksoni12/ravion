<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
// Calculate dashboard stats
$totalActivities = count($sponsorships);
$totalInvestment = 0;
$totalCurrentRoi = 0;
$totalExpectedRoi = 0;

foreach ($sponsorships as $s) {
    $totalInvestment += (float) ($s['amount'] ?? 0);
    $totalCurrentRoi += (float) ($s['current_roi'] ?? 0);
    $totalExpectedRoi += (float) ($s['expected_roi'] ?? 0);
}

$avgCurrentRoi = $totalActivities > 0 ? round($totalCurrentRoi / $totalActivities, 1) : 0;
$avgExpectedRoi = $totalActivities > 0 ? round($totalExpectedRoi / $totalActivities, 1) : 0;
?>

<div class="page-head mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-black text-[#0B2545]">Doctors CRM & Relationship Hub</h2>
        <p class="text-xs text-slate-500 mt-1">Manage, analyze, and track relationship activities and returns (ROI) with medical professionals.</p>
    </div>
    <div class="actions">
        <button type="button" class="btn-add bg-[#0B2545] hover:bg-[#1A365D] text-white flex items-center gap-2 px-4 py-2 rounded-xl transition font-bold" data-popup-open="popup-add-activity">
            <i class="fa-solid fa-plus"></i>Add CRM Activity
        </button>
    </div>
</div>

<!-- Dashboard Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Activities</span>
            <h3 class="text-2xl font-black text-[#0B2545] mt-1"><?= esc($totalActivities) ?></h3>
        </div>
        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-lg">
            <i class="fa-solid fa-calendar-check"></i>
        </div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Investment</span>
            <h3 class="text-2xl font-black text-[#0B2545] mt-1">₹<?= number_format($totalInvestment, 2) ?></h3>
        </div>
        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-lg">
            <i class="fa-solid fa-wallet"></i>
        </div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Avg Current ROI</span>
            <h3 class="text-2xl font-black text-blue-700 mt-1"><?= esc($avgCurrentRoi) ?>%</h3>
        </div>
        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-lg">
            <i class="fa-solid fa-chart-line"></i>
        </div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Avg Expected ROI</span>
            <h3 class="text-2xl font-black text-violet-700 mt-1"><?= esc($avgExpectedRoi) ?>%</h3>
        </div>
        <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center text-lg">
            <i class="fa-solid fa-chart-pie"></i>
        </div>
    </div>
</div>

<!-- Active Filter Controls -->
<div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-3 flex-1 min-w-[280px]">
        <i class="fa-solid fa-magnifying-glass text-slate-400 ml-2"></i>
        <input id="search-activities" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-blue-500" placeholder="Search by doctor, purpose, remarks...">
    </div>
    <div class="flex items-center gap-3 flex-wrap">
        <select id="filter-type" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
            <option value="all">All Activity Types</option>
            <option value="Travel">Travel</option>
            <option value="Dinner">Dinner</option>
            <option value="Other">Other</option>
        </select>
        <select id="filter-status" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
            <option value="all">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="completed">Completed</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>
</div>

<!-- Edit Section -->
<?php if (! empty($editingSponsorship)): ?>
<div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm mb-6 max-w-2xl">
    <h3 class="text-lg font-bold text-[#0B2545] mb-4">Edit CRM Activity #<?= esc($editingSponsorship['id']) ?></h3>
    <form method="post" action="/phadmin/sponsorships/update/<?= esc($editingSponsorship['id']) ?>" class="p-0 m-0 border-none bg-transparent">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Doctor</label>
                <select name="doctor_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" required>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?= esc($doctor['id']) ?>" <?= ((int) $doctor['id'] === (int) $editingSponsorship['doctor_id']) ? 'selected' : '' ?>><?= esc($doctor['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Activity Type</label>
                <select name="activity_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" required>
                    <option value="Travel" <?= ($editingSponsorship['activity_type'] ?? '') === 'Travel' ? 'selected' : '' ?>>Travel</option>
                    <option value="Dinner" <?= ($editingSponsorship['activity_type'] ?? '') === 'Dinner' ? 'selected' : '' ?>>Dinner</option>
                    <option value="Other" <?= ($editingSponsorship['activity_type'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Activity Date</label>
                <input type="date" name="activity_date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= esc($editingSponsorship['activity_date'] ?? date('Y-m-d')) ?>" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Activity Amount (₹)</label>
                <input type="number" step="0.01" name="amount" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= esc($editingSponsorship['amount']) ?>" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Current ROI (%)</label>
                <input type="number" name="current_roi" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= esc($editingSponsorship['current_roi'] ?? 0) ?>">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Expected ROI (%)</label>
                <input type="number" name="expected_roi" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= esc($editingSponsorship['expected_roi'] ?? 0) ?>">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Purpose/Title</label>
                <input name="purpose" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= esc($editingSponsorship['purpose']) ?>" placeholder="e.g. Clinical Study Discussion" required>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Remarks/Notes</label>
                <textarea name="remarks" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm h-24" placeholder="Write complete activity details..."><?= esc($editingSponsorship['remarks'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="flex items-center gap-3 mt-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-xl text-sm transition">Update Activity</button>
            <a href="/phadmin/sponsorships" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold px-4 py-2 rounded-xl text-sm transition">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Activities Grid Table -->
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table id="table-activities" class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold uppercase text-slate-500">
                    <th class="p-4">Date</th>
                    <th class="p-4">Doctor</th>
                    <th class="p-4">Type</th>
                    <th class="p-4">Purpose/Title</th>
                    <th class="p-4">Investment</th>
                    <th class="p-4">Current ROI</th>
                    <th class="p-4">Expected ROI</th>
                    <th class="p-4">Created By</th>
                    <th class="p-4">Status</th>
                    <th class="p-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                <?php if (empty($sponsorships)): ?>
                    <tr>
                        <td colspan="10" class="p-8 text-center text-slate-400 font-medium italic">No CRM activities found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sponsorships as $entry): ?>
                        <?php
                        $statusLower = strtolower($entry['status'] ?? 'submitted');
                        $statusClass = 'bg-amber-50 text-amber-700 border-amber-200';
                        $statusLabel = 'Pending';
                        if ($statusLower === 'approved') {
                            $statusClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            $statusLabel = 'Approved';
                        } elseif ($statusLower === 'completed') {
                            $statusClass = 'bg-blue-50 text-blue-700 border-blue-200';
                            $statusLabel = 'Completed';
                        } elseif ($statusLower === 'rejected') {
                            $statusClass = 'bg-rose-50 text-rose-700 border-rose-200';
                            $statusLabel = 'Rejected';
                        }

                        $typeLower = strtolower($entry['activity_type'] ?? 'other');
                        $typeIcon = '<i class="fa-solid fa-circle-nodes text-slate-400 mr-1"></i>';
                        if ($typeLower === 'travel') {
                            $typeIcon = '<i class="fa-solid fa-plane-departure text-blue-500 mr-1"></i>';
                        } elseif ($typeLower === 'dinner') {
                            $typeIcon = '<i class="fa-solid fa-utensils text-orange-500 mr-1"></i>';
                        }
                        ?>
                        <tr class="hover:bg-slate-50/50 transition-colors" data-type="<?= esc($entry['activity_type'] ?? 'Other') ?>" data-status="<?= esc($statusLower) ?>">
                            <td class="p-4 font-semibold text-[#0B2545] whitespace-nowrap"><?= esc($entry['activity_date'] ?? substr($entry['created_at'], 0, 10)) ?></td>
                            <td class="p-4 font-bold text-slate-900 whitespace-nowrap"><?= esc($entry['doctor_name'] ?? '-') ?></td>
                            <td class="p-4 whitespace-nowrap"><span class="flex items-center text-xs font-semibold"><?= $typeIcon ?><?= esc($entry['activity_type'] ?? 'Other') ?></span></td>
                            <td class="p-4 text-xs font-medium text-slate-600 max-w-[200px] truncate" title="<?= esc($entry['purpose']) ?>"><?= esc($entry['purpose']) ?></td>
                            <td class="p-4 font-semibold text-[#0B2545] whitespace-nowrap">₹<?= number_format($entry['amount'], 2) ?></td>
                            <td class="p-4 font-semibold text-blue-600 whitespace-nowrap"><?= esc($entry['current_roi'] ?? 0) ?>%</td>
                            <td class="p-4 font-semibold text-purple-600 whitespace-nowrap"><?= esc($entry['expected_roi'] ?? 0) ?>%</td>
                            <td class="p-4 text-xs text-slate-500 whitespace-nowrap"><?= esc($entry['creator_name'] ?? '-') ?></td>
                            <td class="p-4 whitespace-nowrap"><span class="px-2.5 py-1 rounded-full border text-xs font-bold <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                            <td class="p-4 whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition-colors" title="View Timeline" onclick="showTimeline(<?= esc($entry['doctor_id']) ?>, '<?= esc(addslashes($entry['doctor_name'] ?? '-')) ?>')">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                    <?php if (($role ?? '') === 'owner' || (int) $entry['created_by_user_id'] === (int) session()->get('user_id')): ?>
                                        <a href="/phadmin/sponsorships?edit=<?= esc($entry['id']) ?>" class="w-8 h-8 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 flex items-center justify-center transition-colors" title="Edit">
                                            <i class="fa-solid fa-pencil"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (($role ?? '') === 'owner' && in_array($statusLower, ['submitted', 'pending'], true)): ?>
                                        <form method="post" action="/phadmin/sponsorships/status/<?= esc($entry['id']) ?>" class="m-0 p-0 inline-flex items-center gap-1 border-none bg-transparent">
                                            <select name="status" class="bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-xs focus:outline-none" required>
                                                <option value="approved">Approve</option>
                                                <option value="completed">Complete</option>
                                                <option value="rejected">Reject</option>
                                            </select>
                                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-2 py-1 rounded-lg">Save</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (($role ?? '') === 'owner' || (int) $entry['created_by_user_id'] === (int) session()->get('user_id')): ?>
                                        <form method="post" action="/phadmin/sponsorships/delete/<?= esc($entry['id']) ?>" class="m-0 p-0 inline-block border-none bg-transparent" onsubmit="return confirm('Delete CRM activity?');">
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 flex items-center justify-center transition-colors" title="Delete">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add CRM Activity Modal -->
<div id="popup-add-activity" class="popup-overlay" aria-hidden="true">
    <div class="popup-card max-w-xl">
        <div class="popup-head">
            <h3 class="text-[#0B2545] font-black text-lg">Add CRM Activity</h3>
            <button type="button" class="popup-close" data-popup-close="popup-add-activity">Close</button>
        </div>
        <form method="post" action="/phadmin/sponsorships" class="m-0 p-0 border-none bg-transparent">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Doctor</label>
                    <select name="doctor_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" required>
                        <option value="">Select Doctor</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?= esc($doctor['id']) ?>"><?= esc($doctor['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Activity Type</label>
                    <select name="activity_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" required>
                        <option value="Travel">Travel</option>
                        <option value="Dinner">Dinner</option>
                        <option value="Other" selected>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Activity Date</label>
                    <input type="date" name="activity_date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Activity Amount (₹)</label>
                    <input type="number" step="0.01" name="amount" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" required placeholder="e.g. 5000">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Current ROI (%)</label>
                    <input type="number" name="current_roi" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" value="0">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Expected ROI (%)</label>
                    <input type="number" name="expected_roi" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" value="0">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Purpose/Title</label>
                    <input name="purpose" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" placeholder="e.g. Clinical Study Discussion" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Remarks/Notes</label>
                    <textarea name="remarks" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm h-24" placeholder="Write complete activity details..."></textarea>
                </div>
            </div>
            <button type="submit" class="w-full bg-[#0B2545] hover:bg-[#1A365D] text-white font-bold px-4 py-3 rounded-xl text-sm transition mt-4 shadow-md">Save Activity</button>
        </form>
    </div>
</div>

<!-- Doctor Activity Timeline Modal -->
<div id="popup-timeline-crm" class="popup-overlay" aria-hidden="true">
    <div class="popup-card max-w-2xl max-h-[85vh] flex flex-col">
        <div class="popup-head flex-shrink-0">
            <div>
                <h3 class="text-[#0B2545] font-black text-lg">Doctor Activity History</h3>
                <p class="text-xs text-slate-400 mt-0.5">Timeline for <span id="timeline-doctor-name" class="font-extrabold text-[#0B2545]"></span></p>
            </div>
            <button type="button" class="popup-close" onclick="closeTimeline()">Close</button>
        </div>
        <div class="overflow-y-auto flex-1 py-6 px-4">
            <div id="timeline-items" class="relative border-l-2 border-slate-100 ml-4 space-y-8">
                <!-- Dynamic Items go here -->
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // 1. Data Store
    const activities = <?= json_encode($sponsorships) ?>;

    // 2. Search & Filter
    const searchInput = document.getElementById('search-activities');
    const filterType = document.getElementById('filter-type');
    const filterStatus = document.getElementById('filter-status');
    const tableRows = document.querySelectorAll('#table-activities tbody tr[data-type]');

    function doFilter() {
        const query = searchInput.value.toLowerCase().trim();
        const selectedType = filterType.value;
        const selectedStatus = filterStatus.value;

        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const type = row.getAttribute('data-type');
            const status = row.getAttribute('data-status');

            const matchesQuery = query === '' || text.includes(query);
            const matchesType = selectedType === 'all' || type === selectedType;
            const matchesStatus = selectedStatus === 'all' || status === selectedStatus || (selectedStatus === 'pending' && status === 'submitted');

            if (matchesQuery && matchesType && matchesStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', doFilter);
    if (filterType) filterType.addEventListener('change', doFilter);
    if (filterStatus) filterStatus.addEventListener('change', doFilter);

    // 3. Timeline Handler
    window.showTimeline = function(doctorId, doctorName) {
        const docNameSpan = document.getElementById('timeline-doctor-name');
        const timelineContainer = document.getElementById('timeline-items');
        const popup = document.getElementById('popup-timeline-crm');

        if (!docNameSpan || !timelineContainer || !popup) return;

        docNameSpan.textContent = doctorName;
        timelineContainer.innerHTML = '';

        // Filter & Sort
        const filtered = activities.filter(a => parseInt(a.doctor_id) === doctorId);
        filtered.sort((a, b) => new Date(a.activity_date || a.created_at) - new Date(b.activity_date || b.created_at));

        if (filtered.length === 0) {
            timelineContainer.innerHTML = '<div class="text-center text-slate-400 italic py-4">No past CRM activities recorded for this doctor.</div>';
        } else {
            filtered.forEach(a => {
                const dateStr = a.activity_date || (a.created_at ? a.created_at.substring(0,10) : '-');
                const amtFormatted = parseFloat(a.amount || 0).toLocaleString('en-IN', { style: 'currency', currency: 'INR' });
                
                const status = (a.status || 'submitted').toLowerCase();
                let statusBadge = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border bg-amber-50 text-amber-700 border-amber-100">Pending</span>';
                if (status === 'approved') {
                    statusBadge = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border bg-emerald-50 text-emerald-700 border-emerald-100">Approved</span>';
                } else if (status === 'completed') {
                    statusBadge = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border bg-blue-50 text-blue-700 border-blue-100">Completed</span>';
                } else if (status === 'rejected') {
                    statusBadge = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border bg-rose-50 text-rose-700 border-rose-100">Rejected</span>';
                }

                const type = (a.activity_type || 'other').toLowerCase();
                let typeIconClass = 'fa-circle-nodes text-slate-400 bg-slate-50';
                if (type === 'travel') {
                    typeIconClass = 'fa-plane-departure text-blue-600 bg-blue-50';
                } else if (type === 'dinner') {
                    typeIconClass = 'fa-utensils text-orange-600 bg-orange-50';
                }

                const item = document.createElement('div');
                item.className = 'relative pl-8';
                item.innerHTML = `
                    <span class="absolute -left-[17px] top-1 flex items-center justify-center w-8 h-8 rounded-full border-2 border-white ring-4 ring-slate-100 shadow-sm ${typeIconClass.split(' ')[1]}">
                        <i class="fa-solid ${typeIconClass.split(' ')[0]} text-xs"></i>
                    </span>
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <span class="text-xs font-black text-[#0B2545]">${dateStr}</span>
                            <span class="text-xs font-semibold text-slate-400 capitalize">${a.activity_type || 'Other'}</span>
                            ${statusBadge}
                        </div>
                        <h4 class="text-sm font-black text-slate-800 mb-1">${a.purpose || '-'}</h4>
                        <p class="text-xs text-slate-500 mb-3">${a.remarks || a.approval_note || 'No description provided.'}</p>
                        <div class="grid grid-cols-3 gap-2 border-t border-slate-200/60 pt-3 text-center">
                            <div>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Investment</span>
                                <span class="text-xs font-black text-[#0B2545]">${amtFormatted}</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Current ROI</span>
                                <span class="text-xs font-black text-blue-600">${a.current_roi || 0}%</span>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Expected ROI</span>
                                <span class="text-xs font-black text-purple-600">${a.expected_roi || 0}%</span>
                            </div>
                        </div>
                    </div>
                `;
                timelineContainer.appendChild(item);
            });
        }

        popup.classList.add('show');
        popup.setAttribute('aria-hidden', 'false');
    };

    window.closeTimeline = function() {
        const popup = document.getElementById('popup-timeline-crm');
        if (popup) {
            popup.classList.remove('show');
            popup.setAttribute('aria-hidden', 'true');
        }
    };
})();
</script>

<?= $this->endSection() ?>
