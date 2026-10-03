<?php

namespace App\Controllers;

use App\Models\CfaModel;
use App\Models\DoctorModel;
use App\Models\ProductModel;
use App\Models\SalesEntryModel;
use App\Models\SettingModel;
use App\Models\SlideModel;
use App\Models\SponsorshipModel;
use App\Models\StockistModel;
use App\Models\UserModel;

class OperationsController extends BaseController
{
    private SettingModel $settingModel;
    private UserModel $userModel;
    private CfaModel $cfaModel;
    private StockistModel $stockistModel;
    private SponsorshipModel $sponsorshipModel;
    private SalesEntryModel $salesEntryModel;
    private SlideModel $slideModel;
    private DoctorModel $doctorModel;
    private ProductModel $productModel;

    public function __construct()
    {
        $this->settingModel = new SettingModel();
        $this->userModel = new UserModel();
        $this->cfaModel = new CfaModel();
        $this->stockistModel = new StockistModel();
        $this->sponsorshipModel = new SponsorshipModel();
        $this->salesEntryModel = new SalesEntryModel();
        $this->slideModel = new SlideModel();
        $this->doctorModel = new DoctorModel();
        $this->productModel = new ProductModel();
    }

    private function role(): string
    {
        $role = strtolower((string) session()->get('user_role'));
        return $role === 'admin' ? 'owner' : $role;
    }

    private function userId(): int
    {
        return (int) session()->get('user_id');
    }

    private function userArea(): string
    {
        return (string) (session()->get('user_area') ?? '');
    }

    private function withBrand(array $data): array
    {
        $data['logoPath'] = $this->settingModel->getValue('company_logo', '');
        $data['companyName'] = 'RAVION Pharma';
        $data['role'] = $this->role();
        $data['userArea'] = $this->userArea();
        return $data;
    }

    private function deny(string $message)
    {
        return redirect()->to('/phadmin/dashboard')->with('error', $message);
    }

    private function managerCanActOnUser(int $targetUserId): bool
    {
        $target = $this->userModel->find($targetUserId);
        return $target && strtolower((string) ($target['role'] ?? '')) === 'mr'
            && (int) ($target['manager_user_id'] ?? 0) === $this->userId();
    }

    public function cfaStockists()
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can manage CFA & Stockists.');
        }

        $stockists = $this->stockistModel
            ->select('stockists.*, cfas.name as cfa_name')
            ->join('cfas', 'cfas.id = stockists.cfa_id', 'left')
            ->orderBy('stockists.id', 'DESC')
            ->findAll();

        $editingCfaId = (int) $this->request->getGet('edit_cfa');
        $editingStockistId = (int) $this->request->getGet('edit_stockist');

        return view('admin/cfa_stockists', $this->withBrand([
            'title' => 'CFA & Stockists',
            'cfas' => $this->cfaModel->orderBy('id', 'DESC')->findAll(),
            'stockists' => $stockists,
            'editingCfa' => $editingCfaId > 0 ? $this->cfaModel->find($editingCfaId) : null,
            'editingStockist' => $editingStockistId > 0 ? $this->stockistModel->find($editingStockistId) : null,
        ]));
    }

    public function createCfa()
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can create CFA.');
        }

        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->to('/phadmin/cfa-stockists')->with('error', 'CFA name is required.');
        }

        $this->cfaModel->insert([
            'name' => $name,
            'contact_person' => trim((string) $this->request->getPost('contact_person')),
            'mobile' => trim((string) $this->request->getPost('mobile')),
            'email' => trim((string) $this->request->getPost('email')),
            'gst' => trim((string) $this->request->getPost('gst')),
            'state' => trim((string) $this->request->getPost('state')),
            'address' => trim((string) $this->request->getPost('address')),
            'created_by_user_id' => $this->userId(),
        ]);

        return redirect()->to('/phadmin/cfa-stockists')->with('success', 'CFA added successfully.');
    }

    public function deleteCfa(int $id)
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can delete CFA.');
        }

        $this->cfaModel->delete($id);
        return redirect()->to('/phadmin/cfa-stockists')->with('success', 'CFA deleted.');
    }

    public function updateCfa(int $id)
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can update CFA.');
        }

        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->to('/phadmin/cfa-stockists')->with('error', 'CFA name is required.');
        }

        $this->cfaModel->update($id, [
            'name' => $name,
            'contact_person' => trim((string) $this->request->getPost('contact_person')),
            'mobile' => trim((string) $this->request->getPost('mobile')),
            'email' => trim((string) $this->request->getPost('email')),
            'gst' => trim((string) $this->request->getPost('gst')),
            'state' => trim((string) $this->request->getPost('state')),
            'address' => trim((string) $this->request->getPost('address')),
        ]);

        return redirect()->to('/phadmin/cfa-stockists')->with('success', 'CFA updated.');
    }

    public function createStockist()
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can create stockist.');
        }

        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->to('/phadmin/cfa-stockists')->with('error', 'Stockist name is required.');
        }

        $this->stockistModel->insert([
            'name' => $name,
            'contact_person' => trim((string) $this->request->getPost('contact_person')),
            'mobile' => trim((string) $this->request->getPost('mobile')),
            'email' => trim((string) $this->request->getPost('email')),
            'gst' => trim((string) $this->request->getPost('gst')),
            'city' => trim((string) $this->request->getPost('city')),
            'area' => trim((string) $this->request->getPost('area')),
            'address' => trim((string) $this->request->getPost('address')),
            'created_by_user_id' => $this->userId(),
        ]);

        return redirect()->to('/phadmin/cfa-stockists')->with('success', 'Stockist added successfully.');
    }

    public function deleteStockist(int $id)
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can delete stockist.');
        }

        $this->stockistModel->delete($id);
        return redirect()->to('/phadmin/cfa-stockists')->with('success', 'Stockist deleted.');
    }

    public function updateStockist(int $id)
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can update stockist.');
        }

        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->to('/phadmin/cfa-stockists')->with('error', 'Stockist name is required.');
        }

        $this->stockistModel->update($id, [
            'name' => $name,
            'contact_person' => trim((string) $this->request->getPost('contact_person')),
            'mobile' => trim((string) $this->request->getPost('mobile')),
            'email' => trim((string) $this->request->getPost('email')),
            'gst' => trim((string) $this->request->getPost('gst')),
            'city' => trim((string) $this->request->getPost('city')),
            'area' => trim((string) $this->request->getPost('area')),
            'address' => trim((string) $this->request->getPost('address')),
        ]);

        return redirect()->to('/phadmin/cfa-stockists')->with('success', 'Stockist updated.');
    }

    public function sponsorships()
    {
        $role = $this->role();
        if (! in_array($role, ['owner', 'manager', 'mr'], true)) {
            return $this->deny('DRs CRM is available for Super Admin, manager, and MR only.');
        }

        $query = $this->sponsorshipModel
            ->select('sponsorships.*, doctors.name as doctor_name, creator.name as creator_name, approver.name as approver_name')
            ->join('doctors', 'doctors.id = sponsorships.doctor_id', 'left')
            ->join('users as creator', 'creator.id = sponsorships.created_by_user_id', 'left')
            ->join('users as approver', 'approver.id = sponsorships.approved_by_user_id', 'left');

        if ($role === 'mr') {
            $query = $query->where('sponsorships.created_by_user_id', $this->userId());
        } elseif ($role === 'manager') {
            $query = $query->groupStart()
                ->where('sponsorships.created_by_user_id', $this->userId())
                ->orGroupStart()
                    ->where('creator.manager_user_id', $this->userId())
                    ->where('creator.role', 'mr')
                ->groupEnd()
            ->groupEnd();
        }

        $editingId = (int) $this->request->getGet('edit');
        $editing = $editingId > 0 ? $this->sponsorshipModel->find($editingId) : null;

        $accessibleTerritoryIds = $this->getAccessibleTerritoryIds($this->userId(), $role);
        $doctorQuery = $this->doctorModel->where('approval_status', 'approved');
        if ($accessibleTerritoryIds !== null) {
            $doctorQuery = $doctorQuery->whereIn('doctors.territory_id', $accessibleTerritoryIds);
        }

        return view('admin/sponsorships', $this->withBrand([
            'title' => 'DRs CRM',
            'sponsorships' => $query->orderBy('sponsorships.created_at', 'DESC')->findAll(),
            'doctors' => $doctorQuery->orderBy('name', 'ASC')->findAll(),
            'editingSponsorship' => $editing,
        ]));
    }

    public function createSponsorship()
    {
        $role = $this->role();
        if (! in_array($role, ['owner', 'manager', 'mr'], true)) {
            return $this->deny('You cannot add CRM activity.');
        }

        $doctorId = (int) $this->request->getPost('doctor_id');
        $amount = (float) $this->request->getPost('amount');
        if ($doctorId <= 0) {
            return redirect()->to('/phadmin/sponsorships')->with('error', 'Doctor is required.');
        }

        $activityType = trim((string) $this->request->getPost('activity_type'));
        if ($activityType === '') {
            $activityType = 'Other';
        }
        $activityDate = trim((string) $this->request->getPost('activity_date'));
        if ($activityDate === '') {
            $activityDate = date('Y-m-d');
        }

        $currentRoi = (float) $this->request->getPost('current_roi');
        $expectedRoi = (float) $this->request->getPost('expected_roi');
        $remarks = trim((string) $this->request->getPost('remarks'));

        $this->sponsorshipModel->insert([
            'doctor_id' => $doctorId,
            'amount' => $amount,
            'purpose' => trim((string) $this->request->getPost('purpose')),
            'status' => $role === 'owner' ? 'approved' : 'submitted',
            'created_by_user_id' => $this->userId(),
            'approved_by_user_id' => $role === 'owner' ? $this->userId() : null,
            'approval_note' => trim((string) $this->request->getPost('approval_note')),
            'activity_type' => $activityType,
            'activity_date' => $activityDate,
            'remarks' => $remarks,
            'current_roi' => $currentRoi,
            'expected_roi' => $expectedRoi,
        ]);

        return redirect()->to('/phadmin/sponsorships')->with('success', 'CRM activity saved.');
    }

    public function updateSponsorshipStatus(int $id)
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can approve CRM activity.');
        }

        $status = trim((string) $this->request->getPost('status'));
        if (! in_array($status, ['approved', 'rejected', 'completed'], true)) {
            return redirect()->to('/phadmin/sponsorships')->with('error', 'Invalid status.');
        }

        $this->sponsorshipModel->update($id, [
            'status' => $status,
            'approved_by_user_id' => $this->userId(),
            'approval_note' => trim((string) $this->request->getPost('approval_note')),
        ]);

        return redirect()->to('/phadmin/sponsorships')->with('success', 'CRM activity status updated.');
    }

    public function deleteSponsorship(int $id)
    {
        $entry = $this->sponsorshipModel->find($id);
        if (! $entry) {
            return redirect()->to('/phadmin/sponsorships')->with('error', 'CRM activity not found.');
        }

        if ($this->role() === 'owner' || (int) $entry['created_by_user_id'] === $this->userId()) {
            $this->sponsorshipModel->delete($id);
            return redirect()->to('/phadmin/sponsorships')->with('success', 'CRM activity deleted.');
        }

        return $this->deny('You cannot delete this CRM activity record.');
    }

    public function updateSponsorship(int $id)
    {
        $entry = $this->sponsorshipModel->find($id);
        if (! $entry) {
            return redirect()->to('/phadmin/sponsorships')->with('error', 'CRM activity not found.');
        }

        $role = $this->role();
        if (! in_array($role, ['owner', 'manager', 'mr'], true)) {
            return $this->deny('You cannot update CRM activity.');
        }

        if (in_array($role, ['manager', 'mr'], true) && (int) $entry['created_by_user_id'] !== $this->userId()) {
            return $this->deny('You can edit your own entries only.');
        }

        $doctorId = (int) $this->request->getPost('doctor_id');
        $amount = (float) $this->request->getPost('amount');
        if ($doctorId <= 0) {
            return redirect()->to('/phadmin/sponsorships')->with('error', 'Doctor is required.');
        }

        $activityType = trim((string) $this->request->getPost('activity_type'));
        if ($activityType === '') {
            $activityType = 'Other';
        }
        $activityDate = trim((string) $this->request->getPost('activity_date'));
        if ($activityDate === '') {
            $activityDate = date('Y-m-d');
        }

        $currentRoi = (float) $this->request->getPost('current_roi');
        $expectedRoi = (float) $this->request->getPost('expected_roi');
        $remarks = trim((string) $this->request->getPost('remarks'));

        $this->sponsorshipModel->update($id, [
            'doctor_id' => $doctorId,
            'amount' => $amount,
            'purpose' => trim((string) $this->request->getPost('purpose')),
            'status' => $role === 'owner' ? ($entry['status'] ?? 'approved') : 'submitted',
            'approval_note' => trim((string) $this->request->getPost('approval_note')),
            'activity_type' => $activityType,
            'activity_date' => $activityDate,
            'remarks' => $remarks,
            'current_roi' => $currentRoi,
            'expected_roi' => $expectedRoi,
        ]);

        return redirect()->to('/phadmin/sponsorships')->with('success', 'CRM activity updated.');
    }

    public function salesEntries()
    {
        $role = $this->role();
        $monthVal = trim((string) $this->request->getGet('month'));
        $yearVal = trim((string) $this->request->getGet('year'));
        $employeeId = (int) $this->request->getGet('employee_id');

        if (empty($monthVal)) {
            $monthVal = date('m');
        }
        if (empty($yearVal)) {
            $yearVal = date('Y');
        }
        $selectedMonth = $yearVal . '-' . str_pad($monthVal, 2, '0', STR_PAD_LEFT);

        if ($employeeId <= 0) {
            if ($role === 'mr') {
                $employeeId = $this->userId();
            }
        }

        $employeeQuery = $this->userModel->where('is_active', 1);
        if ($role === 'manager') {
            $employeeQuery = $employeeQuery->groupStart()
                ->where('id', $this->userId())
                ->orWhere('manager_user_id', $this->userId())
            ->groupEnd();
        } elseif ($role === 'mr') {
            $employeeQuery = $employeeQuery->where('id', $this->userId());
        }
        $employeesList = $employeeQuery->orderBy('name', 'ASC')->findAll();

        $stockistRows = [];
        $employeeUser = $employeeId > 0 ? $this->userModel->find($employeeId) : null;

        if ($employeeId > 0 && $employeeUser) {
            $targetRole = strtolower(trim((string)($employeeUser['role'] ?? 'mr')));
            $accessibleTerritoryIds = $this->getAccessibleTerritoryIds($employeeId, $targetRole);

            $stockistQuery = $this->stockistModel;
            if ($accessibleTerritoryIds !== null) {
                $stockistQuery = $stockistQuery->whereIn('stockists.territory_id', $accessibleTerritoryIds);
            }
            $stockists = $stockistQuery->orderBy('name', 'ASC')->findAll();

            foreach ($stockists as $st) {
                $entries = $this->salesEntryModel
                    ->where('stockist_id', $st['id'])
                    ->where('sales_month', $selectedMonth)
                    ->where('created_by_user_id', $employeeId)
                    ->findAll();

                $status = 'Not Started';
                $submittedDate = '-';
                $pendingDate = '-';

                if (! empty($entries)) {
                    $hasSubmitted = false;
                    $lastUpdated = null;
                    foreach ($entries as $e) {
                        if (in_array(strtolower((string) $e['status']), ['submitted', 'approved'], true)) {
                            $hasSubmitted = true;
                        }
                        $t = $e['updated_at'] ?? $e['created_at'] ?? null;
                        if ($t && (! $lastUpdated || $t > $lastUpdated)) {
                            $lastUpdated = $t;
                        }
                    }

                    if ($hasSubmitted) {
                        $status = 'Submitted';
                        $submittedDate = $lastUpdated ? date('Y-m-d H:i', strtotime($lastUpdated)) : '-';
                    } else {
                        $status = 'Pending';
                        $pendingDate = $lastUpdated ? date('Y-m-d H:i', strtotime($lastUpdated)) : '-';
                    }
                }

                $stockistRows[] = [
                    'id'            => $st['id'],
                    'name'          => $st['name'],
                    'city'          => $st['city'] ?: '-',
                    'area'          => $st['area'] ?: '-',
                    'employee_name' => $employeeUser['name'],
                    'status'        => $status,
                    'submitted'     => $submittedDate,
                    'pending'       => $pendingDate,
                ];
            }
        }

        return view('admin/sales_entries', $this->withBrand([
            'title'              => 'Sales Entries',
            'employees'          => $employeesList,
            'selectedMonthVal'   => $monthVal,
            'selectedYearVal'    => $yearVal,
            'selectedMonth'      => $selectedMonth,
            'selectedEmployeeId' => $employeeId,
            'stockistRows'       => $stockistRows,
        ]));
    }

    public function salesEntryDetail()
    {
        $stockistId = (int) $this->request->getGet('stockist_id');
        $month = trim((string) $this->request->getGet('month'));
        $employeeId = (int) $this->request->getGet('employee_id');

        if ($stockistId <= 0 || ! preg_match('/^\d{4}-\d{2}$/', $month) || $employeeId <= 0) {
            return redirect()->to('/phadmin/sales-entries')->with('error', 'Invalid detailed sales entry request parameters.');
        }

        $stockist = $this->stockistModel->find($stockistId);
        $employee = $this->userModel->find($employeeId);

        if (! $stockist || ! $employee) {
            return redirect()->to('/phadmin/sales-entries')->with('error', 'Stockist or Employee not found.');
        }

        $products = $this->productModel->orderBy('name', 'ASC')->findAll();

        $existingEntries = $this->salesEntryModel
            ->where('stockist_id', $stockistId)
            ->where('sales_month', $month)
            ->where('created_by_user_id', $employeeId)
            ->findAll();

        $entriesMap = [];
        foreach ($existingEntries as $ee) {
            $entriesMap[(int) $ee['product_id']] = $ee;
        }

        return view('admin/sales_entries_detail', $this->withBrand([
            'title'      => 'Sales Entry Details',
            'stockist'   => $stockist,
            'employee'   => $employee,
            'salesMonth' => $month,
            'products'   => $products,
            'entriesMap' => $entriesMap,
        ]));
    }

    public function saveDetailedMatrix()
    {
        $month = trim((string) $this->request->getPost('sales_month'));
        $stockistId = (int) $this->request->getPost('stockist_id');
        $employeeId = (int) $this->request->getPost('employee_id');
        $status = trim((string) $this->request->getPost('status'));

        if (! preg_match('/^\d{4}-\d{2}$/', $month) || $stockistId <= 0 || $employeeId <= 0) {
            return redirect()->to('/phadmin/sales-entries')->with('error', 'Unable to save sales entries matrix due to missing fields.');
        }

        if ($status !== 'submitted') {
            $status = 'pending';
        }

        // Clean existing rows
        $this->salesEntryModel
            ->where('stockist_id', $stockistId)
            ->where('sales_month', $month)
            ->where('created_by_user_id', $employeeId)
            ->delete();

        $productIds = $this->request->getPost('product_id') ?: [];
        $openings = $this->request->getPost('opening_value') ?: [];
        $primaries = $this->request->getPost('primary_value') ?: [];
        $aiocds = $this->request->getPost('aiocd_sales') ?: [];
        $mrs = $this->request->getPost('mr_sales') ?: [];
        $lates = $this->request->getPost('late_sales') ?: [];
        $lateStocks = $this->request->getPost('late_stock') ?: [];

        foreach ($productIds as $idx => $pId) {
            $productId = (int) $pId;
            if ($productId <= 0) {
                continue;
            }

            $opening = (float) ($openings[$idx] ?? 0);
            $primary = (float) ($primaries[$idx] ?? 0);
            $aiocd = (float) ($aiocds[$idx] ?? 0);
            $mr = (float) ($mrs[$idx] ?? 0);
            $late = (float) ($lates[$idx] ?? 0);
            $lateStock = (float) ($lateStocks[$idx] ?? 0);

            // Calculations
            $totalStock = $opening + $primary;
            $totalSales = $aiocd + $mr + $late;
            $closing = $totalStock - $totalSales;

            $this->salesEntryModel->insert([
                'entry_date' => $month . '-01',
                'sales_month' => $month,
                'product_id' => $productId,
                'stockist_id' => $stockistId,
                'opening_value' => $opening,
                'primary_value' => $primary,
                'secondary_value' => $totalSales,
                'closing_value' => $closing,
                'aiocd_sales' => $aiocd,
                'mr_sales' => $mr,
                'late_sales' => $late,
                'late_stock' => $lateStock,
                'status' => $status,
                'created_by_user_id' => $employeeId,
                'matrix_row_key' => 'stockist_' . $stockistId . '_prod_' . $productId,
                'locked_by_manager' => 0,
            ]);
        }

        $redirectUrl = '/phadmin/sales-entries?month=' . substr($month, 5, 2) . '&year=' . substr($month, 0, 4) . '&employee_id=' . $employeeId;
        return redirect()->to($redirectUrl)->with('success', 'Sales entries saved successfully.');
    }

    public function exportDetailedMatrix()
    {
        $stockistId = (int) $this->request->getGet('stockist_id');
        $month = trim((string) $this->request->getGet('month'));
        $employeeId = (int) $this->request->getGet('employee_id');

        if ($stockistId <= 0 || ! preg_match('/^\d{4}-\d{2}$/', $month) || $employeeId <= 0) {
            return redirect()->to('/phadmin/sales-entries')->with('error', 'Invalid detailed export request parameters.');
        }

        $stockist = $this->stockistModel->find($stockistId);
        $employee = $this->userModel->find($employeeId);

        if (! $stockist || ! $employee) {
            return redirect()->to('/phadmin/sales-entries')->with('error', 'Stockist or Employee not found.');
        }

        $products = $this->productModel->orderBy('name', 'ASC')->findAll();

        $existingEntries = $this->salesEntryModel
            ->where('stockist_id', $stockistId)
            ->where('sales_month', $month)
            ->where('created_by_user_id', $employeeId)
            ->findAll();

        $entriesMap = [];
        foreach ($existingEntries as $ee) {
            $entriesMap[(int) $ee['product_id']] = $ee;
        }

        // CSV Headers matching details columns
        $csv = "Product Name,Packing,Open Stock,Receipt,Total Stock,AIOCD Sales,MR Sales,Late Sales,Sales Value,Closing Stock,Late Stock,Closing Value\n";

        $sumOpen = 0;
        $sumReceipt = 0;
        $sumTotalStock = 0;
        $sumAiocd = 0;
        $sumMr = 0;
        $sumLateSales = 0;
        $sumSalesVal = 0;
        $sumClosingStock = 0;
        $sumLateStock = 0;
        $sumClosingVal = 0;

        foreach ($products as $product) {
            $ee = $entriesMap[(int) $product['id']] ?? null;
            $openVal = (float) ($ee['opening_value'] ?? 0);
            $primaryVal = (float) ($ee['primary_value'] ?? 0);
            $aiocd = (float) ($ee['aiocd_sales'] ?? 0);
            $mr = (float) ($ee['mr_sales'] ?? 0);
            $lateSales = (float) ($ee['late_sales'] ?? 0);
            $lateStock = (float) ($ee['late_stock'] ?? 0);

            $ptsPrice = (float) ($product['pts_price'] ?? 0);

            $totalStock = $openVal + $primaryVal;
            $totalSales = $aiocd + $mr + $lateSales;
            $salesValue = $totalSales * $ptsPrice;
            $closingStock = $totalStock - $totalSales;
            $closingValue = $closingStock * $ptsPrice;

            $sumOpen += $openVal;
            $sumReceipt += $primaryVal;
            $sumTotalStock += $totalStock;
            $sumAiocd += $aiocd;
            $sumMr += $mr;
            $sumLateSales += $lateSales;
            $sumSalesVal += $salesValue;
            $sumClosingStock += $closingStock;
            $sumLateStock += $lateStock;
            $sumClosingVal += $closingValue;

            $line = [
                $product['name'],
                $product['packing'] ?: '-',
                number_format($openVal, 2, '.', ''),
                number_format($primaryVal, 2, '.', ''),
                number_format($totalStock, 2, '.', ''),
                number_format($aiocd, 2, '.', ''),
                number_format($mr, 2, '.', ''),
                number_format($lateSales, 2, '.', ''),
                number_format($salesValue, 2, '.', ''),
                number_format($closingStock, 2, '.', ''),
                number_format($lateStock, 2, '.', ''),
                number_format($closingValue, 2, '.', ''),
            ];

            $csv .= implode(',', array_map(static fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', $line)) . "\n";
        }

        // GRAND TOTAL row
        $totalLine = [
            'GRAND TOTAL',
            '',
            number_format($sumOpen, 2, '.', ''),
            number_format($sumReceipt, 2, '.', ''),
            number_format($sumTotalStock, 2, '.', ''),
            number_format($sumAiocd, 2, '.', ''),
            number_format($sumMr, 2, '.', ''),
            number_format($sumLateSales, 2, '.', ''),
            number_format($sumSalesVal, 2, '.', ''),
            number_format($sumClosingStock, 2, '.', ''),
            number_format($sumLateStock, 2, '.', ''),
            number_format($sumClosingVal, 2, '.', ''),
        ];
        $csv .= implode(',', array_map(static fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', $totalLine)) . "\n";

        $filename = 'sales_entry_' . str_replace([' ', '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $stockist['name']) . '_' . $month . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }

    private function buildSalesMatrix(array $entries): array
    {
        $matrix = [];
        foreach ($entries as $entry) {
            $stockist = (string) ($entry['stockist_name'] ?? 'Unmapped Stockist');
            $product = (string) ($entry['product_name'] ?? 'Unmapped Product');
            $rowKey = $stockist;

            if (! isset($matrix[$rowKey])) {
                $matrix[$rowKey] = [
                    'stockist' => $stockist,
                    'products' => [],
                    'secondary_total' => 0.0,
                    'value_total' => 0.0,
                ];
            }

            $secondary = (float) ($entry['secondary_value'] ?? 0);
            $pts = (float) ($entry['pts_price'] ?? 0);
            $matrix[$rowKey]['products'][$product] = [
                'secondary' => $secondary,
                'value' => $secondary * $pts,
            ];
            $matrix[$rowKey]['secondary_total'] += $secondary;
            $matrix[$rowKey]['value_total'] += $secondary * $pts;
        }

        return array_values($matrix);
    }

    public function createSalesEntry()
    {
        $role = $this->role();
        $entryDate = trim((string) $this->request->getPost('entry_date'));
        if ($entryDate === '') {
            return redirect()->to('/phadmin/sales-entries')->with('error', 'Entry date is required.');
        }
        $salesMonth = trim((string) $this->request->getPost('sales_month'));
        if (! preg_match('/^\d{4}-\d{2}$/', $salesMonth)) {
            $salesMonth = substr($entryDate, 0, 7);
        }

        $matrixJson = trim((string) $this->request->getPost('matrix_json'));
        if ($matrixJson !== '') {
            $matrixRows = json_decode($matrixJson, true);
            if (! is_array($matrixRows) || $matrixRows === []) {
                return redirect()->to('/phadmin/sales-entries?month=' . $salesMonth)->with('error', 'Invalid matrix payload.');
            }

            foreach ($matrixRows as $row) {
                $productId = (int) ($row['product_id'] ?? 0);
                $stockistId = (int) ($row['stockist_id'] ?? 0);
                if ($productId <= 0 || $stockistId <= 0) {
                    continue;
                }

                $opening = (float) ($row['opening_value'] ?? 0);
                $primary = (float) ($row['primary_value'] ?? 0);
                $secondary = (float) ($row['secondary_value'] ?? 0);
                $closing = (float) ($row['closing_value'] ?? 0);

                if ($role === 'manager') {
                    $secondary = 0.0;
                }

                if ($role === 'owner') {
                    $status = 'approved';
                    $approvedBy = $this->userId();
                } else {
                    $status = 'submitted';
                    $approvedBy = null;
                }

                $this->salesEntryModel->insert([
                    'entry_date' => $salesMonth . '-01',
                    'sales_month' => $salesMonth,
                    'product_id' => $productId,
                    'stockist_id' => $stockistId,
                    'matrix_row_key' => 'stockist_' . $stockistId,
                    'area' => $role === 'owner' ? trim((string) $this->request->getPost('area')) : $this->userArea(),
                    'opening_value' => $opening,
                    'primary_value' => $primary,
                    'secondary_value' => $secondary,
                    'closing_value' => $closing,
                    'status' => $status,
                    'created_by_user_id' => $this->userId(),
                    'approved_by_user_id' => $approvedBy,
                    'approval_note' => trim((string) $this->request->getPost('approval_note')),
                    'matrix_json' => json_encode($row),
                    'locked_by_manager' => 0,
                ]);
            }

            return redirect()->to('/phadmin/sales-entries?month=' . $salesMonth)->with('success', 'Monthly sales matrix saved.');
        }

        $opening = (float) $this->request->getPost('opening_value');
        $primary = (float) $this->request->getPost('primary_value');
        $secondary = (float) $this->request->getPost('secondary_value');
        $closing = (float) $this->request->getPost('closing_value');

        if ($role === 'manager') {
            $secondary = 0.0;
        }

        if ($role === 'owner') {
            $status = 'approved';
            $approvedBy = $this->userId();
        } else {
            $status = 'submitted';
            $approvedBy = null;
        }

        $this->salesEntryModel->insert([
            'entry_date' => $entryDate,
            'sales_month' => $salesMonth,
            'product_id' => (int) $this->request->getPost('product_id') ?: null,
            'stockist_id' => (int) $this->request->getPost('stockist_id') ?: null,
            'area' => $role === 'owner' ? trim((string) $this->request->getPost('area')) : $this->userArea(),
            'opening_value' => $opening,
            'primary_value' => $primary,
            'secondary_value' => $secondary,
            'closing_value' => $closing,
            'status' => $status,
            'created_by_user_id' => $this->userId(),
            'approved_by_user_id' => $approvedBy,
            'approval_note' => trim((string) $this->request->getPost('approval_note')),
            'locked_by_manager' => 0,
        ]);

        return redirect()->to('/phadmin/sales-entries?month=' . $salesMonth)->with('success', 'Sales entry saved.');
    }

    public function updateSalesStatus(int $id)
    {
        $entry = $this->salesEntryModel->find($id);
        if (! $entry) {
            return redirect()->to('/phadmin/sales-entries')->with('error', 'Sales entry not found.');
        }

        $role = $this->role();
        $creatorId = (int) $entry['created_by_user_id'];

        if ($role === 'manager') {
            if (! $this->managerCanActOnUser($creatorId)) {
                return $this->deny('Manager can approve only own-team MR sales entries.');
            }
        } elseif ($role !== 'owner') {
            return $this->deny('You cannot approve sales entries.');
        }

        $status = trim((string) $this->request->getPost('status'));
        if (! in_array($status, ['approved', 'rejected'], true)) {
            return redirect()->to('/phadmin/sales-entries')->with('error', 'Invalid sales status.');
        }

        $this->salesEntryModel->update($id, [
            'status' => $status,
            'approved_by_user_id' => $this->userId(),
            'approval_note' => trim((string) $this->request->getPost('approval_note')),
            'locked_by_manager' => 1,
        ]);

        return redirect()->to('/phadmin/sales-entries')->with('success', 'Sales entry status updated.');
    }

    public function updateSalesEntry(int $id)
    {
        $entry = $this->salesEntryModel->find($id);
        if (! $entry) {
            return redirect()->to('/phadmin/sales-entries')->with('error', 'Sales entry not found.');
        }

        $role = $this->role();
        if ($role === 'mr' && (int) $entry['created_by_user_id'] !== $this->userId()) {
            return $this->deny('MR can update own sales entries only.');
        }

        if ($role === 'manager' && $entry['area'] !== $this->userArea()) {
            return $this->deny('Manager can update sales entries only for assigned area.');
        }

        if ((int) ($entry['locked_by_manager'] ?? 0) === 1 && $role === 'mr') {
            return $this->deny('Approved monthly matrix rows are locked for MR edits.');
        }

        $payload = [
            'entry_date' => trim((string) $this->request->getPost('entry_date')) ?: (string) $entry['entry_date'],
            'sales_month' => substr(trim((string) $this->request->getPost('entry_date')) ?: (string) $entry['entry_date'], 0, 7),
            'opening_value' => (float) $this->request->getPost('opening_value'),
            'primary_value' => (float) $this->request->getPost('primary_value'),
            'closing_value' => (float) $this->request->getPost('closing_value'),
            'approval_note' => trim((string) $this->request->getPost('approval_note')),
        ];

        if ($role === 'owner') {
            $payload['secondary_value'] = (float) $this->request->getPost('secondary_value');
            $payload['status'] = (string) ($entry['status'] ?? 'approved');
        } elseif ($role === 'mr') {
            $payload['secondary_value'] = (float) $this->request->getPost('secondary_value');
            $payload['status'] = 'submitted';
            $payload['approved_by_user_id'] = null;
        } else {
            $payload['secondary_value'] = (float) ($entry['secondary_value'] ?? 0);
            $payload['status'] = 'submitted';
            $payload['approved_by_user_id'] = null;
        }

        $this->salesEntryModel->update($id, $payload);
        return redirect()->to('/phadmin/sales-entries')->with('success', 'Sales entry updated.');
    }

    public function slides()
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can manage slides.');
        }

        $slides = $this->slideModel
            ->select('slides.*, products.name as product_name')
            ->join('products', 'products.id = slides.product_id', 'left')
            ->orderBy('products.display_order', 'ASC')
            ->orderBy('slides.slide_order', 'ASC')
            ->orderBy('slides.id', 'DESC')
            ->findAll();

        return view('admin/slides', $this->withBrand([
            'title' => 'Slide Manager',
            'slides' => $slides,
            'products' => $this->productModel->orderBy('name', 'ASC')->findAll(),
        ]));
    }

    public function createSlide()
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can upload slides.');
        }

        $title = trim((string) $this->request->getPost('title'));
        $file = $this->request->getFile('slide_file');
        if ($title === '' || ! $file || ! $file->isValid() || $file->hasMoved()) {
            return redirect()->to('/phadmin/slides')->with('error', 'Slide title and file are required.');
        }

        $ext = strtolower((string) $file->getExtension());
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'])) {
            return redirect()->to('/phadmin/slides')->with('error', 'Only PDF and image files (JPG, JPEG, PNG, WEBP) are allowed.');
        }

        $newName = $file->getRandomName();
        $targetDir = FCPATH . 'uploads/slides';
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        $file->move($targetDir, $newName);
        $path = 'uploads/slides/' . $newName;

        $productId = (int) $this->request->getPost('product_id') ?: null;
        $nextSlideOrder = 1;
        if ($productId) {
            $maxSlideOrder = $this->slideModel->where('product_id', $productId)->selectMax('slide_order')->first();
            $nextSlideOrder = isset($maxSlideOrder['slide_order']) ? ((int)$maxSlideOrder['slide_order'] + 1) : 1;
        }

        $this->slideModel->insert([
            'product_id' => $productId,
            'title' => $title,
            'file_path' => $path,
            'status' => 'active',
            'created_by_user_id' => $this->userId(),
            'slide_order' => $nextSlideOrder,
        ]);

        return redirect()->to('/phadmin/slides')->with('success', 'Slide uploaded successfully.');
    }

    public function toggleSlide(int $id)
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can update slides.');
        }

        $slide = $this->slideModel->find($id);
        if (! $slide) {
            return redirect()->to('/phadmin/slides')->with('error', 'Slide not found.');
        }

        $this->slideModel->update($id, [
            'status' => ($slide['status'] ?? 'active') === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->to('/phadmin/slides')->with('success', 'Slide status updated.');
    }

    public function deleteSlide(int $id)
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can delete slides.');
        }

        $this->slideModel->delete($id);
        return redirect()->to('/phadmin/slides')->with('success', 'Slide deleted.');
    }
}
