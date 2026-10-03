<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-head flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Events & Reminders</h2>
        <p class="text-sm text-slate-500 mt-1">Keep track of important events like birthdays and anniversaries.</p>
    </div>
    <div class="flex gap-2">
        <button type="button" onclick="openAddReminderModal()" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold rounded-xl text-sm shadow-lg shadow-indigo-100 hover:shadow-xl transition-all duration-200 flex items-center gap-2 border-0 cursor-pointer">
            <i class="fa-solid fa-plus text-xs"></i> Add Reminder
        </button>
    </div>
</div>

<!-- Upcoming Reminders Widget -->
<?php if (!empty($upcomingBirthdays)): ?>
<div class="bg-gradient-to-r from-amber-500 to-orange-500 rounded-2xl shadow-lg p-6 mb-8 text-white relative overflow-hidden">
    <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 rounded-full bg-white opacity-10"></div>
    <div class="relative z-10">
        <div class="flex items-center gap-3 mb-4">
            <i class="fa-solid fa-bell text-2xl"></i>
            <h3 class="text-xl font-bold">Upcoming Events (Today & Tomorrow)</h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <?php foreach ($upcomingBirthdays as $ub): ?>
                <?php 
                    $bday = date('m-d', strtotime($ub['event_date']));
                    $today = date('m-d');
                    $when = ($bday === $today) ? 'TODAY!' : 'Tomorrow';
                    $icon = ($ub['event_type'] === 'anniversary') ? 'fa-rings-wedding' : 'fa-cake-candles';
                ?>
                <div class="bg-white/20 rounded-xl p-4 backdrop-blur-sm border border-white/30">
                    <div class="text-xs font-bold uppercase tracking-wider text-amber-100 mb-1"><?= $when ?></div>
                    <div class="font-bold text-lg mb-1 truncate">
                        <i class="fa-solid <?= $icon ?> mr-1 opacity-70"></i> <?= esc($ub['person_name']) ?>
                    </div>
                    <div class="text-sm text-white/90 truncate capitalize"><?= esc($ub['event_type']) ?></div>
                    <?php if(!empty($ub['notes'])): ?>
                    <div class="text-sm text-white/90 mt-2 pt-2 border-t border-white/20 truncate">
                        <?= esc($ub['notes']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- All Reminders Table Panel -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
    <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h3 class="text-base font-extrabold text-slate-800">All Reminders</h3>
            <p class="text-xs text-slate-500 mt-1">Directory of all your saved events.</p>
        </div>
        <div class="w-full md:w-auto">
            <div class="relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input id="search-birthdays" class="w-full md:w-72 pl-9 pr-4 py-2 bg-white border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-slate-400" placeholder="Search name/month...">
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table id="table-birthdays" class="w-full border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 border-b border-slate-100 text-xs font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-4 text-left">Date</th>
                    <th class="py-3.5 px-4 text-left">Person Name</th>
                    <th class="py-3.5 px-4 text-left">Event Type</th>
                    <th class="py-3.5 px-4 text-left hidden md:table-cell">Notes</th>
                    <th class="py-3.5 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (empty($reminders)): ?>
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-bell text-4xl mb-2 block text-slate-300"></i>
                            No reminders registered yet. Click "Add Reminder" to get started.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reminders as $r): ?>
                        <tr class="hover:bg-slate-50/30 transition-colors">
                            <td class="py-4 px-4 font-bold text-indigo-700">
                                <?= date('d M Y', strtotime($r['event_date'])) ?>
                            </td>
                            <td class="py-4 px-4 font-bold text-slate-800"><?= esc($r['person_name']) ?></td>
                            <td class="py-4 px-4 text-slate-600 capitalize">
                                <?php if($r['event_type'] === 'anniversary'): ?>
                                    <i class="fa-solid fa-rings-wedding text-amber-500 mr-1"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-cake-candles text-pink-500 mr-1"></i>
                                <?php endif; ?>
                                <?= esc($r['event_type']) ?>
                            </td>
                            <td class="py-4 px-4 text-slate-500 hidden md:table-cell max-w-[200px] truncate">
                                <?= esc($r['notes'] ?? '-') ?>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" onclick="editReminder(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors border-0 cursor-pointer" title="Edit Reminder">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                    <form action="/phadmin/birthdays/delete/<?= $r['id'] ?>" method="POST" class="inline m-0" onsubmit="return confirm('Are you sure you want to delete this reminder?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-colors border-0 cursor-pointer" title="Delete Reminder">
                                            <i class="fa-solid fa-trash text-xs"></i>
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

<!-- Add/Edit Reminder Modal -->
<div id="reminder-modal" class="popup-overlay fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4 z-50 transition-opacity duration-300" aria-hidden="true">
    <div class="popup-card bg-white rounded-2xl border border-slate-100 shadow-2xl max-w-md w-full transform scale-95 transition-all duration-300">
        <form id="reminder-form" action="/phadmin/birthdays" method="POST">
            <?= csrf_field() ?>
            <div class="popup-head flex items-center justify-between border-b border-slate-100 p-4 pb-3">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2" id="reminder-modal-title">
                    <i class="fa-solid fa-bell text-indigo-600"></i> Add Reminder
                </h3>
                <button type="button" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors border-0 cursor-pointer" onclick="closeReminderModal()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Person Name</label>
                    <input type="text" name="person_name" id="reminder-person-name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g. Dr. John Doe">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Event Type</label>
                    <select name="event_type" id="reminder-event-type" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                        <option value="birthday">Birthday</option>
                        <option value="anniversary">Anniversary</option>
                        <option value="other">Other Event</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Date</label>
                    <input type="date" name="event_date" id="reminder-event-date" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Notes (Optional)</label>
                    <textarea name="notes" id="reminder-notes" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Additional details..."></textarea>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 p-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl">
                <button type="button" class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold rounded-lg text-sm transition-colors cursor-pointer" onclick="closeReminderModal()">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-sm shadow-md shadow-indigo-100 hover:shadow-lg transition-all duration-200 border-0 cursor-pointer">Save Reminder</button>
            </div>
        </form>
    </div>
</div>

<script>
    bindTableSearch('search-birthdays', 'table-birthdays');

    function openAddReminderModal() {
        document.getElementById('reminder-modal-title').innerHTML = '<i class="fa-solid fa-bell text-indigo-600"></i> Add Reminder';
        document.getElementById('reminder-form').action = '/phadmin/birthdays';
        document.getElementById('reminder-person-name').value = '';
        document.getElementById('reminder-event-type').value = 'birthday';
        document.getElementById('reminder-event-date').value = '';
        document.getElementById('reminder-notes').value = '';
        
        showModal('reminder-modal');
    }
    
    function editReminder(reminder) {
        document.getElementById('reminder-modal-title').innerHTML = '<i class="fa-solid fa-pen text-indigo-600"></i> Edit Reminder';
        document.getElementById('reminder-form').action = '/phadmin/birthdays/update/' + reminder.id;
        document.getElementById('reminder-person-name').value = reminder.person_name;
        document.getElementById('reminder-event-type').value = reminder.event_type;
        document.getElementById('reminder-event-date').value = reminder.event_date;
        document.getElementById('reminder-notes').value = reminder.notes || '';
        
        showModal('reminder-modal');
    }

    function showModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            modal.classList.add('opacity-100');
            modal.querySelector('.popup-card').classList.remove('scale-95');
            modal.querySelector('.popup-card').classList.add('scale-100');
        }, 50);
    }
    
    function closeReminderModal() {
        const modal = document.getElementById('reminder-modal');
        modal.querySelector('.popup-card').classList.remove('scale-100');
        modal.querySelector('.popup-card').classList.add('scale-95');
        modal.classList.remove('opacity-100');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 200);
    }
</script>

<?= $this->endSection() ?>
