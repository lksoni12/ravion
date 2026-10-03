<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="page-head">
    <h2>Visit Log</h2>
    <div class="actions">
        <span class="subtle">DCR records and month-wise outcomes</span>
        <button type="button" class="btn-add" data-popup-open="popup-add-visit"><i class="fa-solid fa-plus"></i>Log Visit</button>
    </div>
</div>

<div class="panel">
<form method="get" action="/phadmin/visits" style="margin:0 0 10px;border:none;padding:0;background:transparent;display:flex;gap:8px;align-items:end;">
    <div>
        <label for="visit_month">Month</label>
        <input id="visit_month" type="month" name="month" value="<?= esc($selectedMonth ?? date('Y-m')) ?>">
    </div>
    <div><button type="submit">Apply</button></div>
</form>
<div class="table-tools">
    <input id="search-visits" class="table-search" placeholder="Search visits...">
</div>
<table id="table-visits">
    <thead><tr><th>DCR Date</th><th>Planned Date</th><th>Doctor</th><th>Product</th><th>Chemist</th><th>Action</th><th>Duration</th><th>POB</th><th>Samples</th><th>Deviation</th><th>Joint Call</th><th>Feedback</th></tr></thead>
    <tbody>
        <?php foreach ($visits as $v): ?>
            <tr>
                <td><?= esc($v['dcr_date'] ?? substr((string) ($v['visit_date'] ?? ''), 0, 10)) ?></td>
                <td><?= esc($v['planned_date'] ?? '-') ?></td>
                <td><?= esc($v['doctor_name'] ?? '-') ?></td>
                <td><?= esc($v['product_name'] ?? '-') ?></td>
                <td><?= esc($v['chemist_name'] ?? '-') ?></td>
                <td><span class="tag"><?= esc(strtoupper($v['action_type'])) ?></span></td>
                <td><?= esc($v['duration_minutes'] ?? 0) ?></td>
                <td>₹ <?= esc(number_format((float) ($v['pob_value'] ?? 0), 2)) ?></td>
                <td><?= esc($v['sample_units'] ?? 0) ?></td>
                <td>
                    <?php if ((int) ($v['is_deviation'] ?? 0) === 1): ?>
                        <span class="tag">YES</span>
                        <div class="subtle" style="font-size:11px;"><?= esc($v['deviation_reason'] ?? '') ?></div>
                    <?php else: ?>
                        NO
                    <?php endif; ?>
                </td>
                <td><?= esc($v['joint_call_with']) ?></td>
                <td><?= esc($v['feedback']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<div id="popup-add-visit" class="popup-overlay" aria-hidden="true">
    <div class="popup-card">
        <div class="popup-head">
            <h3>Log New Visit</h3>
            <button type="button" class="popup-close" data-popup-close="popup-add-visit">Close</button>
        </div>
        <form method="post" action="/phadmin/visits" style="margin:0;border:none;padding:0;background:transparent;">
            <label for="dcr_date">DCR Date</label>
            <input id="dcr_date" type="date" name="dcr_date" value="<?= esc(date('Y-m-d')) ?>" required>

            <label for="planned_plan_id">Planned Tour Item (Optional)</label>
            <select id="planned_plan_id" name="planned_plan_id">
                <option value="">Select plan row</option>
                <?php foreach (($plannedVisits ?? []) as $p): ?>
                    <option value="<?= esc($p['id']) ?>"><?= esc($p['plan_date']) ?> - <?= esc(strtoupper($p['activity_type'])) ?> - <?= esc($p['doctor_name'] ?? '-') ?></option>
                <?php endforeach; ?>
            </select>

            <label for="visit_doctor_id">Doctor</label>
            <select id="visit_doctor_id" name="doctor_id" required>
                <option value="">Select Doctor</option>
                <?php foreach ($doctors as $d): ?>
                    <option value="<?= esc($d['id']) ?>"><?= esc($d['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="visit_product_id">Product Discussed</label>
            <select id="visit_product_id" name="product_id" required>
                <option value="">Select Product</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= esc($p['id']) ?>"><?= esc($p['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="visit_chemist_id">Chemist (Optional)</label>
            <select id="visit_chemist_id" name="chemist_id">
                <option value="">Select Chemist</option>
                <?php foreach ($chemists as $c): ?>
                    <option value="<?= esc($c['id']) ?>"><?= esc($c['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="visit_action_type">Action Type</label>
            <select id="visit_action_type" name="action_type">
                <option value="detailing">Detailing</option>
                <option value="call">Call</option>
                <option value="exit">Exit</option>
            </select>

            <label for="visit_duration_minutes">Duration (minutes)</label>
            <input id="visit_duration_minutes" type="number" name="duration_minutes" min="0" value="0">

            <label for="visit_pob_value">POB Value</label>
            <input id="visit_pob_value" type="number" step="0.01" name="pob_value" min="0" value="0">

            <label for="visit_sample_units">Sample Units</label>
            <input id="visit_sample_units" type="number" name="sample_units" min="0" value="0">

            <label for="visit_deviation_reason">Deviation Reason (if not per plan)</label>
            <textarea id="visit_deviation_reason" name="deviation_reason" rows="2"></textarea>

            <label for="visit_feedback">Feedback</label>
            <textarea id="visit_feedback" name="feedback" rows="2"></textarea>
            <label for="visit_next_objective">Next Visit Objective</label>
            <textarea id="visit_next_objective" name="next_objective" rows="2"></textarea>
            <label for="visit_joint_call_with">Joint Call With (Manager)</label>
            <input id="visit_joint_call_with" name="joint_call_with">

            <button type="submit">Log Visit</button>
        </form>
    </div>
</div>
<script>bindTableSearch('search-visits', 'table-visits');</script>
<?= $this->endSection() ?>
