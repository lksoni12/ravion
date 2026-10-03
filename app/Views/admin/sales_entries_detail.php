<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $selectedMonth = $salesMonth;
    $stockistName = $stockist['name'];
    $employeeName = $employee['name'];
    
    // Check if any product has submitted status to determine overall status badge
    $isSubmitted = false;
    foreach ($products as $p) {
        $ee = $entriesMap[(int)$p['id']] ?? null;
        if ($ee && in_array(strtolower((string)$ee['status']), ['submitted', 'approved'], true)) {
            $isSubmitted = true;
            break;
        }
    }
?>

<div class="page-head flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Sales Entry Details</h2>
        <p class="text-sm text-slate-500 mt-1">Manage product inventory volumes and sales metrics for C&F/Stockist channels.</p>
    </div>
    
    <div class="flex items-center gap-3">
        <a href="/phadmin/sales-entries?month=<?= substr($selectedMonth, 5, 2) ?>&year=<?= substr($selectedMonth, 0, 4) ?>&employee_id=<?= $employee['id'] ?>" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-all text-center border-0 cursor-pointer">
            <i class="fa-solid fa-arrow-left text-xs mr-1"></i> Back to List
        </a>
        <a href="/phadmin/sales-entries/export?stockist_id=<?= $stockist['id'] ?>&month=<?= $selectedMonth ?>&employee_id=<?= $employee['id'] ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm transition-all text-center border-0 cursor-pointer">
            <i class="fa-solid fa-file-excel mr-1.5 text-xs"></i> Export to Excel
        </a>
        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider <?= $isSubmitted ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-amber-50 text-amber-700 border border-amber-100' ?>">
            Status: <?= $isSubmitted ? 'Submitted' : 'Pending Draft' ?>
        </span>
    </div>
</div>

<!-- Stockist Information Card -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6 p-5">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div>
            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Stockist Name</span>
            <span class="text-base font-extrabold text-slate-800"><?= esc($stockistName) ?></span>
        </div>
        <div>
            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Assigned Employee / MR</span>
            <span class="text-base font-extrabold text-slate-800"><?= esc($employeeName) ?></span>
        </div>
        <div>
            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Sales Month</span>
            <span class="text-base font-extrabold text-indigo-650 text-indigo-600"><?= date('F Y', strtotime($selectedMonth . '-01')) ?></span>
        </div>
        <div>
            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Area / Patch</span>
            <span class="text-base font-bold text-slate-700"><span class="tag bg-indigo-50 border-indigo-100 text-indigo-700"><?= esc($stockist['area'] ?: '-') ?></span></span>
        </div>
    </div>
</div>

<!-- Main Form -->
<form id="sales-detail-form" method="post" action="/phadmin/sales-entries/save-matrix" class="bg-transparent border-0 p-0 m-0 shadow-none">
    <input type="hidden" name="sales_month" value="<?= esc($selectedMonth) ?>">
    <input type="hidden" name="stockist_id" value="<?= esc($stockist['id']) ?>">
    <input type="hidden" name="employee_id" value="<?= esc($employee['id']) ?>">
    <input type="hidden" name="status" id="form-status-field" value="pending">

    <!-- Search and Pagination Controls -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-800">Product Sales Form</h3>
                <p class="text-xs text-slate-500 mt-1">Fill in the quantity columns below. Totals and closing stock calculations will update in real-time.</p>
            </div>
            
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
                <div class="relative">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input id="product-search" class="w-full sm:w-64 pl-9 pr-4 py-2 bg-white border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-slate-400" placeholder="Filter product by name..." oninput="handleSearch()">
                </div>
                
                <div class="flex items-center gap-2 bg-slate-100 p-1 rounded-lg">
                    <span class="text-xs text-slate-500 font-bold px-2">Page Size:</span>
                    <select id="page-size-selector" onchange="changePageSize()" class="bg-white border-0 text-xs font-bold rounded px-2.5 py-1 text-slate-700 focus:outline-none w-16 cursor-pointer">
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="all">All</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Table container -->
        <div class="overflow-x-auto">
            <table id="table-detailed-sales" class="w-full border-collapse">
                <thead class="bg-slate-50 text-slate-600 border-b border-slate-100 text-xs font-bold uppercase tracking-wider sticky top-0 z-10 shadow-xs">
                    <tr>
                        <th class="py-3 px-3 text-left w-64 min-w-[200px]">Product Name</th>
                        <th class="py-3 px-3 text-left w-28">Packing</th>
                        <th class="py-3 px-3 text-center w-24">Open Stock</th>
                        <th class="py-3 px-3 text-center w-24">Receipt</th>
                        <th class="py-3 px-3 text-center w-24 bg-slate-50/50">Total Stock</th>
                        <th class="py-3 px-3 text-center w-24">AIOCD Sales</th>
                        <th class="py-3 px-3 text-center w-24">MR Sales</th>
                        <th class="py-3 px-3 text-center w-24">Late Sales</th>
                        <th class="py-3 px-3 text-center w-36 bg-slate-50/50">Sales Value</th>
                        <th class="py-3 px-3 text-center w-24 bg-slate-50/50">Closing Stock</th>
                        <th class="py-3 px-3 text-center w-24">Late Stock</th>
                        <th class="py-3 px-3 text-center w-36 bg-slate-50/50">Closing Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs" id="detailed-sales-tbody">
                    <?php foreach ($products as $index => $product): ?>
                        <?php
                            $ee = $entriesMap[(int)$product['id']] ?? null;
                            $openVal = (float)($ee['opening_value'] ?? 0);
                            $primaryVal = (float)($ee['primary_value'] ?? 0);
                            $aiocd = (float)($ee['aiocd_sales'] ?? 0);
                            $mr = (float)($ee['mr_sales'] ?? 0);
                            $lateSales = (float)($ee['late_sales'] ?? 0);
                            $lateStock = (float)($ee['late_stock'] ?? 0);
                            
                            $ptsPrice = (float)($product['pts_price'] ?? 0);
                            
                            $totalStock = $openVal + $primaryVal;
                            $totalSales = $aiocd + $mr + $lateSales;
                            $salesValue = $totalSales * $ptsPrice;
                            $closingStock = $totalStock - $totalSales;
                            $closingValue = $closingStock * $ptsPrice;
                        ?>
                        <tr class="hover:bg-slate-50/20 transition-colors product-row" data-pts-price="<?= $ptsPrice ?>" data-product-name="<?= esc(strtolower($product['name'])) ?>">
                            <td class="py-3 px-3">
                                <span class="font-bold text-slate-800 block text-sm"><?= esc($product['name']) ?></span>
                                <input type="hidden" name="product_id[]" value="<?= esc($product['id']) ?>">
                            </td>
                            <td class="py-3 px-3 text-slate-500 font-medium"><?= esc($product['packing'] ?: '-') ?></td>
                            <td class="py-3 px-3">
                                <input type="number" step="0.01" name="opening_value[]" value="<?= $openVal ?>" class="w-full text-center px-2 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 input-open" oninput="calculateRow(this)">
                            </td>
                            <td class="py-3 px-3">
                                <input type="number" step="0.01" name="primary_value[]" value="<?= $primaryVal ?>" class="w-full text-center px-2 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 input-receipt" oninput="calculateRow(this)">
                            </td>
                            <td class="py-3 px-3 text-center font-bold text-slate-700 bg-slate-50/40 val-total-stock"><?= number_format($totalStock, 2) ?></td>
                            <td class="py-3 px-3">
                                <input type="number" step="0.01" name="aiocd_sales[]" value="<?= $aiocd ?>" class="w-full text-center px-2 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 input-aiocd" oninput="calculateRow(this)">
                            </td>
                            <td class="py-3 px-3">
                                <input type="number" step="0.01" name="mr_sales[]" value="<?= $mr ?>" class="w-full text-center px-2 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 input-mr" oninput="calculateRow(this)">
                            </td>
                            <td class="py-3 px-3">
                                <input type="number" step="0.01" name="late_sales[]" value="<?= $lateSales ?>" class="w-full text-center px-2 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 input-late-sales" oninput="calculateRow(this)">
                            </td>
                            <td class="py-3 px-3 text-center font-bold text-indigo-700 bg-indigo-50/10 val-sales-value">₹ <?= number_format($salesValue, 2) ?></td>
                            <td class="py-3 px-3 text-center font-bold text-slate-700 bg-slate-50/40 val-closing-stock"><?= number_format($closingStock, 2) ?></td>
                            <td class="py-3 px-3">
                                <input type="number" step="0.01" name="late_stock[]" value="<?= $lateStock ?>" class="w-full text-center px-2 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 input-late-stock" oninput="calculateRow(this)">
                            </td>
                            <td class="py-3 px-3 text-center font-bold text-indigo-700 bg-indigo-50/10 val-closing-value">₹ <?= number_format($closingValue, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <!-- Bottom Totals Row -->
                <tfoot class="bg-slate-100 font-extrabold text-slate-700 border-t border-slate-200 text-xs">
                    <tr>
                        <td class="py-3.5 px-3 text-left">GRAND TOTAL</td>
                        <td class="py-3.5 px-3"></td>
                        <td class="py-3.5 px-3 text-center text-slate-800" id="total-open">0.00</td>
                        <td class="py-3.5 px-3 text-center text-slate-800" id="total-receipt">0.00</td>
                        <td class="py-3.5 px-3 text-center text-slate-900 bg-slate-200/50" id="total-stock">0.00</td>
                        <td class="py-3.5 px-3 text-center text-slate-800" id="total-aiocd">0.00</td>
                        <td class="py-3.5 px-3 text-center text-slate-800" id="total-mr">0.00</td>
                        <td class="py-3.5 px-3 text-center text-slate-800" id="total-late-sales">0.00</td>
                        <td class="py-3.5 px-3 text-center text-indigo-800 bg-indigo-100/30" id="total-sales-value">₹ 0.00</td>
                        <td class="py-3.5 px-3 text-center text-slate-900 bg-slate-200/50" id="total-closing-stock">0.00</td>
                        <td class="py-3.5 px-3 text-center text-slate-800" id="total-late-stock">0.00</td>
                        <td class="py-3.5 px-3 text-center text-indigo-800 bg-indigo-100/30" id="total-closing-value">₹ 0.00</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Pagination controls -->
        <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/20 flex flex-wrap justify-between items-center gap-3">
            <span class="text-xs text-slate-500 font-semibold" id="pagination-info">Showing 1 to 10 of 10 products</span>
            <div class="flex items-center gap-1.5" id="pagination-buttons">
                <!-- Buttons rendered dynamically -->
            </div>
        </div>
    </div>
    
    <!-- Submission triggers -->
    <div class="flex justify-end gap-3.5 pb-10">
        <button type="button" onclick="submitForm('pending')" class="px-5 py-3 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-xl text-sm border border-slate-300 transition-all shadow-sm cursor-pointer">
            <i class="fa-solid fa-cloud-arrow-up text-slate-400 mr-1.5"></i> Save Draft
        </button>
        <button type="button" onclick="submitForm('submitted')" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-sm shadow-lg shadow-indigo-100 hover:shadow-xl transition-all border-0 cursor-pointer">
            <i class="fa-solid fa-paper-plane mr-1.5"></i> Submit Sales Entry
        </button>
    </div>
</form>

<script>
    let rows = [];
    let currentPage = 1;
    let pageSize = 10;
    let filteredRows = [];

    document.addEventListener("DOMContentLoaded", () => {
        rows = Array.from(document.querySelectorAll(".product-row"));
        filteredRows = [...rows];
        
        calculateAll();
        renderTable();
    });

    window.calculateRow = function(input) {
        const row = input.closest("tr");
        if (!row) return;

        const open = parseFloat(row.querySelector(".input-open").value) || 0;
        const receipt = parseFloat(row.querySelector(".input-receipt").value) || 0;
        const aiocd = parseFloat(row.querySelector(".input-aiocd").value) || 0;
        const mr = parseFloat(row.querySelector(".input-mr").value) || 0;
        const late = parseFloat(row.querySelector(".input-late-sales").value) || 0;
        const lateStock = parseFloat(row.querySelector(".input-late-stock").value) || 0;
        
        const ptsPrice = parseFloat(row.dataset.ptsPrice) || 0;

        const totalStock = open + receipt;
        const totalSales = aiocd + mr + late;
        const salesValue = totalSales * ptsPrice;
        const closingStock = totalStock - totalSales;
        const closingValue = closingStock * ptsPrice;

        row.querySelector(".val-total-stock").textContent = totalStock.toFixed(2);
        row.querySelector(".val-sales-value").textContent = "₹ " + salesValue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        row.querySelector(".val-closing-stock").textContent = closingStock.toFixed(2);
        row.querySelector(".val-closing-value").textContent = "₹ " + closingValue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

        updateTotals();
    };

    function calculateAll() {
        rows.forEach(row => {
            const open = parseFloat(row.querySelector(".input-open").value) || 0;
            const receipt = parseFloat(row.querySelector(".input-receipt").value) || 0;
            const aiocd = parseFloat(row.querySelector(".input-aiocd").value) || 0;
            const mr = parseFloat(row.querySelector(".input-mr").value) || 0;
            const late = parseFloat(row.querySelector(".input-late-sales").value) || 0;
            
            const ptsPrice = parseFloat(row.dataset.ptsPrice) || 0;

            const totalStock = open + receipt;
            const totalSales = aiocd + mr + late;
            const salesValue = totalSales * ptsPrice;
            const closingStock = totalStock - totalSales;
            const closingValue = closingStock * ptsPrice;

            row.querySelector(".val-total-stock").textContent = totalStock.toFixed(2);
            row.querySelector(".val-sales-value").textContent = "₹ " + salesValue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            row.querySelector(".val-closing-stock").textContent = closingStock.toFixed(2);
            row.querySelector(".val-closing-value").textContent = "₹ " + closingValue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        });
        
        updateTotals();
    }

    function updateTotals() {
        let sumOpen = 0, sumReceipt = 0, sumTotalStock = 0;
        let sumAiocd = 0, sumMr = 0, sumLateSales = 0, sumSalesVal = 0;
        let sumClosingStock = 0, sumLateStock = 0, sumClosingVal = 0;

        rows.forEach(row => {
            const open = parseFloat(row.querySelector(".input-open").value) || 0;
            const receipt = parseFloat(row.querySelector(".input-receipt").value) || 0;
            const aiocd = parseFloat(row.querySelector(".input-aiocd").value) || 0;
            const mr = parseFloat(row.querySelector(".input-mr").value) || 0;
            const late = parseFloat(row.querySelector(".input-late-sales").value) || 0;
            const lateStock = parseFloat(row.querySelector(".input-late-stock").value) || 0;
            
            const ptsPrice = parseFloat(row.dataset.ptsPrice) || 0;

            const totalStock = open + receipt;
            const totalSales = aiocd + mr + late;
            const salesValue = totalSales * ptsPrice;
            const closingStock = totalStock - totalSales;
            const closingValue = closingStock * ptsPrice;

            sumOpen += open;
            sumReceipt += receipt;
            sumTotalStock += totalStock;
            sumAiocd += aiocd;
            sumMr += mr;
            sumLateSales += late;
            sumSalesVal += salesValue;
            sumClosingStock += closingStock;
            sumLateStock += lateStock;
            sumClosingVal += closingValue;
        });

        document.getElementById("total-open").textContent = sumOpen.toFixed(2);
        document.getElementById("total-receipt").textContent = sumReceipt.toFixed(2);
        document.getElementById("total-stock").textContent = sumTotalStock.toFixed(2);
        document.getElementById("total-aiocd").textContent = sumAiocd.toFixed(2);
        document.getElementById("total-mr").textContent = sumMr.toFixed(2);
        document.getElementById("total-late-sales").textContent = sumLateSales.toFixed(2);
        document.getElementById("total-sales-value").textContent = "₹ " + sumSalesVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById("total-closing-stock").textContent = sumClosingStock.toFixed(2);
        document.getElementById("total-late-stock").textContent = sumLateStock.toFixed(2);
        document.getElementById("total-closing-value").textContent = "₹ " + sumClosingVal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    window.handleSearch = function () {
        const query = document.getElementById("product-search").value.toLowerCase().trim();
        
        filteredRows = rows.filter(row => {
            const name = row.dataset.productName || "";
            return name.includes(query);
        });

        currentPage = 1;
        renderTable();
    };

    window.changePageSize = function () {
        const sel = document.getElementById("page-size-selector").value;
        if (sel === "all") {
            pageSize = filteredRows.length;
        } else {
            pageSize = parseInt(sel) || 10;
        }
        currentPage = 1;
        renderTable();
    };

    function renderTable() {
        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;

        rows.forEach(row => {
            row.classList.add("hidden");
        });

        const activeRows = filteredRows.slice(start, end);
        activeRows.forEach(row => {
            row.classList.remove("hidden");
        });

        // Update pagination labels
        const total = filteredRows.length;
        const showingStart = total === 0 ? 0 : start + 1;
        const showingEnd = Math.min(end, total);
        
        document.getElementById("pagination-info").textContent = `Showing ${showingStart} to ${showingEnd} of ${total} products`;

        // Render buttons
        const pageCount = Math.ceil(total / pageSize);
        const btnContainer = document.getElementById("pagination-buttons");
        
        if (pageCount <= 1) {
            btnContainer.innerHTML = "";
            return;
        }

        let html = "";
        // Prev button
        html += `<button type="button" onclick="goToPage(${currentPage - 1})" class="px-2.5 py-1.5 border border-slate-350 border-slate-300 hover:bg-slate-50 text-slate-700 font-bold rounded-lg text-xs transition cursor-pointer ${currentPage === 1 ? 'opacity-40 pointer-events-none' : ''}"><i class="fa-solid fa-angle-left"></i></button>`;

        for (let page = 1; page <= pageCount; page++) {
            const isActive = page === currentPage;
            html += `<button type="button" onclick="goToPage(${page})" class="w-8 h-8 flex items-center justify-center font-bold text-xs rounded-lg transition cursor-pointer border-0 ${isActive ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'}">${page}</button>`;
        }

        // Next button
        html += `<button type="button" onclick="goToPage(${currentPage + 1})" class="px-2.5 py-1.5 border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold rounded-lg text-xs transition cursor-pointer ${currentPage === pageCount ? 'opacity-40 pointer-events-none' : ''}"><i class="fa-solid fa-angle-right"></i></button>`;

        btnContainer.innerHTML = html;
    }

    window.goToPage = function (page) {
        currentPage = page;
        renderTable();
    };

    window.submitForm = function (status) {
        document.getElementById("form-status-field").value = status;
        document.getElementById("sales-detail-form").submit();
    };
</script>
<?= $this->endSection() ?>
