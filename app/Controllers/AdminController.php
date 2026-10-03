<?php

namespace App\Controllers;

use App\Models\ChemistModel;
use App\Models\DoctorModel;
use App\Models\ExpenseModel;
use App\Models\FieldPlanModel;
use App\Models\ProductModel;
use App\Models\SalesEntryModel;
use App\Models\SettingModel;
use App\Models\SponsorshipModel;
use App\Models\UserModel;
use App\Models\VisitModel;
use App\Models\SlideModel;
use App\Models\StockistModel;
use App\Models\TerritoryModel;
use App\Models\UserTerritoryModel;
use App\Models\ReminderModel;

class AdminController extends BaseController
{
    private ProductModel $productModel;
    private DoctorModel $doctorModel;
    private VisitModel $visitModel;
    private SettingModel $settingModel;
    private ChemistModel $chemistModel;
    private FieldPlanModel $fieldPlanModel;
    private UserModel $userModel;
    private ExpenseModel $expenseModel;
    private SalesEntryModel $salesEntryModel;
    private SponsorshipModel $sponsorshipModel;
    private SlideModel $slideModel;
    private StockistModel $stockistModel;
    private TerritoryModel $territoryModel;
    private UserTerritoryModel $userTerritoryModel;
    private ReminderModel $reminderModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->doctorModel = new DoctorModel();
        $this->visitModel = new VisitModel();
        $this->settingModel = new SettingModel();
        $this->chemistModel = new ChemistModel();
        $this->fieldPlanModel = new FieldPlanModel();
        $this->userModel = new UserModel();
        $this->expenseModel = new ExpenseModel();
        $this->salesEntryModel = new SalesEntryModel();
        $this->sponsorshipModel = new SponsorshipModel();
        $this->slideModel = new SlideModel();
        $this->stockistModel = new StockistModel();
        $this->territoryModel = new TerritoryModel();
        $this->userTerritoryModel = new UserTerritoryModel();
        $this->reminderModel = new ReminderModel();
    }

    private function role(): string
    {
        $role = strtolower((string) session()->get('user_role'));
        if ($role === 'admin') {
            return 'owner';
        }
        if (! in_array($role, ['owner', 'manager', 'mr'], true)) {
            return 'mr';
        }
        return $role;
    }

    private function userId(): int
    {
        return (int) session()->get('user_id');
    }

    private function userArea(): string
    {
        return (string) (session()->get('user_area') ?? '');
    }

    private function managerId(): ?int
    {
        $value = session()->get('manager_user_id');
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }

    private function deny(string $message)
    {
        return redirect()->to('/phadmin/dashboard')->with('error', $message);
    }

    private function canApproveUser(int $submittedByUserId): bool
    {
        $role = $this->role();
        if ($role === 'owner') {
            return true;
        }

        if ($role !== 'manager') {
            return false;
        }

        $submittedBy = $this->userModel->find($submittedByUserId);
        if (! $submittedBy) {
            return false;
        }

        return strtolower((string) ($submittedBy['role'] ?? '')) === 'mr'
            && (int) ($submittedBy['manager_user_id'] ?? 0) === $this->userId();
    }

    private function withBrand(array $data): array
    {
        $data['logoPath'] = $this->settingModel->getValue('company_logo', '');
        $data['companyName'] = 'RAVION Pharma';
        $data['role'] = $this->role();
        $data['userArea'] = $this->userArea();
        $data['userName'] = (string) session()->get('user_name');
        return $data;
    }

    public function dashboard(): string
    {
        $role = $this->role();
        $userId = $this->userId();

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

        $employeeQuery = $this->userModel->where('is_active', 1);
        if ($role === 'manager') {
            $employeeQuery = $employeeQuery->groupStart()
                ->where('id', $userId)
                ->orWhere('manager_user_id', $userId)
            ->groupEnd();
        } elseif ($role === 'mr') {
            $employeeQuery = $employeeQuery->where('id', $userId);
            $employeeId = $userId;
        }
        $employeesList = $employeeQuery->orderBy('name', 'ASC')->findAll();

        $scopedEmployeeIds = [];
        if ($employeeId > 0) {
            $scopedEmployeeIds = [$employeeId];
        } else {
            if ($role === 'mr') {
                $scopedEmployeeIds = [$userId];
            } elseif ($role === 'manager') {
                $scopedEmployeeIds = [$userId];
                $mrs = $this->userModel->where('manager_user_id', $userId)->findAll();
                foreach ($mrs as $mr) {
                    $scopedEmployeeIds[] = (int) $mr['id'];
                }
            } else {
                $allUsers = $this->userModel->where('is_active', 1)->findAll();
                $scopedEmployeeIds = array_column($allUsers, 'id');
            }
        }

        $allActiveUsers = $this->userModel->where('is_active', 1)->findAll();
        $allUsersById = [];
        $mrByArea = [];
        foreach ($allActiveUsers as $u) {
            $allUsersById[(int)$u['id']] = $u;
            if ($u['role'] === 'mr') {
                $uArea = trim((string)($u['area'] ?? ''));
                if ($uArea !== '' && !isset($mrByArea[$uArea])) {
                    $mrByArea[$uArea] = $u;
                }
            }
        }

        $stockistQuery = $this->stockistModel;
        if (! empty($scopedEmployeeIds)) {
            $areas = [];
            foreach ($scopedEmployeeIds as $empId) {
                if (isset($allUsersById[$empId])) {
                    $area = trim((string)($allUsersById[$empId]['area'] ?? ''));
                    if ($area !== '') {
                        $areas[] = $area;
                    }
                }
            }
            $areas = array_unique($areas);

            $stockistQuery = $stockistQuery->groupStart()
                ->whereIn('created_by_user_id', $scopedEmployeeIds);
            
            if (!empty($areas)) {
                $stockistQuery = $stockistQuery->orWhereIn('area', $areas);
            }
            
            $stockistQuery = $stockistQuery->groupEnd();
        }
        $scopedStockists = $stockistQuery->orderBy('name', 'ASC')->findAll();
        $totalStockistsCount = count($scopedStockists);

        $activeMrQuery = $this->userModel->where('role', 'mr')->where('is_active', 1);
        if ($role === 'manager') {
            $activeMrQuery = $activeMrQuery->where('manager_user_id', $userId);
        } elseif ($role === 'mr') {
            $activeMrQuery = $activeMrQuery->where('id', $userId);
        }
        $activeMrsList = $activeMrQuery->findAll();
        $activeMrsCount = count($activeMrsList);

        $totalPrimarySales = 0.0;
        $totalSecondarySales = 0.0;
        $submittedEntriesCount = 0;
        $pendingEntriesCount = 0;

        $stockistIds = array_column($scopedStockists, 'id');
        $entriesMap = [];

        if (! empty($stockistIds)) {
            $primaryQuery = $this->salesEntryModel
                ->select('COALESCE(SUM(sales_entries.primary_value * products.pts_price), 0) as total_primary')
                ->join('products', 'products.id = sales_entries.product_id', 'left')
                ->whereIn('sales_entries.stockist_id', $stockistIds)
                ->where('sales_entries.sales_month', $selectedMonth);
            
            $primaryRow = $primaryQuery->first();
            $totalPrimarySales = (float) ($primaryRow['total_primary'] ?? 0);

            $secondaryQuery = $this->salesEntryModel
                ->select('COALESCE(SUM(sales_entries.secondary_value * products.pts_price), 0) as total_secondary')
                ->join('products', 'products.id = sales_entries.product_id', 'left')
                ->whereIn('sales_entries.stockist_id', $stockistIds)
                ->where('sales_entries.sales_month', $selectedMonth);

            $secondaryRow = $secondaryQuery->first();
            $totalSecondarySales = (float) ($secondaryRow['total_secondary'] ?? 0);

            $entries = $this->salesEntryModel
                ->whereIn('stockist_id', $stockistIds)
                ->where('sales_month', $selectedMonth)
                ->findAll();

            foreach ($entries as $e) {
                $entriesMap[(int) $e['stockist_id']][] = $e;
            }
        }

        $allProducts = $this->productModel->findAll();
        $productsById = [];
        foreach ($allProducts as $p) {
            $productsById[(int)$p['id']] = (float)($p['pts_price'] ?? 0);
        }

        $stockistsByMr = [];
        foreach ($scopedStockists as $st) {
            $mrId = (int)$st['created_by_user_id'];
            if ($mrId === 0) {
                $stArea = trim((string)($st['area'] ?? ''));
                if ($stArea !== '' && isset($mrByArea[$stArea])) {
                    $mrId = (int)$mrByArea[$stArea]['id'];
                }
            }
            if ($mrId > 0) {
                $stockistsByMr[$mrId][] = $st;
            }
        }

        $pendingList = [];
        foreach ($scopedStockists as $st) {
            $stEntries = $entriesMap[(int) $st['id']] ?? [];
            $status = 'Not Started';
            $lastUpdated = '-';

            if (! empty($stEntries)) {
                $hasSubmitted = false;
                $lastTime = null;
                foreach ($stEntries as $e) {
                    if (in_array(strtolower((string) $e['status']), ['submitted', 'approved'], true)) {
                        $hasSubmitted = true;
                    }
                    $t = $e['updated_at'] ?? $e['created_at'] ?? null;
                    if ($t && (! $lastTime || $t > $lastTime)) {
                        $lastTime = $t;
                    }
                }
                if ($hasSubmitted) {
                    $status = 'Submitted';
                    $submittedEntriesCount++;
                } else {
                    $status = 'Pending';
                    $pendingEntriesCount++;
                    $lastUpdated = $lastTime ? date('Y-m-d H:i', strtotime($lastTime)) : '-';
                }
            } else {
                $status = 'Not Started';
                $pendingEntriesCount++;
                $lastUpdated = '-';
            }

            $assignedMrName = '-';
            if (isset($allUsersById[$st['created_by_user_id']])) {
                $assignedMrName = $allUsersById[$st['created_by_user_id']]['name'];
            } else {
                $stArea = trim((string)($st['area'] ?? ''));
                if ($stArea !== '' && isset($mrByArea[$stArea])) {
                    $assignedMrName = $mrByArea[$stArea]['name'];
                }
            }

            if ($status !== 'Submitted') {
                $pendingList[] = [
                    'mr_name' => $assignedMrName,
                    'stockist_name' => $st['name'],
                    'status' => $status,
                    'last_updated' => $lastUpdated,
                ];
            }
        }

        $mrPerformance = [];
        foreach ($activeMrsList as $mr) {
            $mrStockists = $stockistsByMr[(int)$mr['id']] ?? [];
            $mrSalesVal = 0.0;

            $mrPendingStockistsCount = 0;
            $mrPendingNames = [];
            foreach ($mrStockists as $st) {
                $hasSub = false;
                if (! empty($entriesMap[(int) $st['id']])) {
                    foreach ($entriesMap[(int) $st['id']] as $e) {
                        if (in_array(strtolower((string) $e['status']), ['submitted', 'approved'], true)) {
                            $hasSub = true;
                        }
                        $pts = $productsById[(int)$e['product_id']] ?? 0.0;
                        $mrSalesVal += ((float)$e['secondary_value'] * $pts);
                    }
                }
                if (! $hasSub) {
                    $mrPendingStockistsCount++;
                    $mrPendingNames[] = $st['name'];
                }
            }

            $globalTarget = (float) $this->settingModel->getValue('monthly_secondary_target', '1000000');
            $mrTargetPerMr = (!empty($mr['monthly_target'])) ? (float) $mr['monthly_target'] : (float) ($globalTarget / max(1, $activeMrsCount));
            $achievementPercent = $mrTargetPerMr > 0 ? min(100, (int) round(($mrSalesVal / $mrTargetPerMr) * 100)) : 0;

            $mrPerformance[] = [
                'name' => $mr['name'],
                'role' => $mr['role'],
                'sales_value' => $mrSalesVal,
                'target' => $mrTargetPerMr,
                'achievement_percent' => $achievementPercent,
                'pending_count' => $mrPendingStockistsCount,
                'pending_names' => implode(', ', $mrPendingNames) ?: 'None',
            ];
        }

        // Resolve target based on team sum or selected employee
        $globalTarget = (float) $this->settingModel->getValue('monthly_secondary_target', '1000000');
        $totalMrsCount = $this->userModel->where('role', 'mr')->where('is_active', 1)->countAllResults();
        $fallbackMrTarget = (float) ($globalTarget / max(1, $totalMrsCount));

        if ($employeeId > 0) {
            $scopedUser = $this->userModel->find($employeeId);
            if ($scopedUser && !empty($scopedUser['monthly_target'])) {
                $scopedTarget = (float) $scopedUser['monthly_target'];
            } else {
                $scopedRole = strtolower((string) ($scopedUser['role'] ?? 'mr'));
                $scopedTarget = ($scopedRole === 'mr') ? $fallbackMrTarget : 0.0;
            }
        } else {
            // General overview: Sum of targets of all active team members visible to the current user
            $teamQuery = $this->userModel->where('is_active', 1);
            if ($role === 'manager') {
                $teamQuery = $teamQuery->groupStart()
                    ->where('id', $userId)
                    ->orWhere('manager_user_id', $userId)
                ->groupEnd();
            } elseif ($role === 'mr') {
                $teamQuery = $teamQuery->where('id', $userId);
            }
            $teamList = $teamQuery->findAll();

            $scopedTarget = 0.0;
            foreach ($teamList as $member) {
                $memberRole = strtolower((string) ($member['role'] ?? 'mr'));
                if (!empty($member['monthly_target'])) {
                    $scopedTarget += (float) $member['monthly_target'];
                } else {
                    if ($memberRole === 'mr') {
                        $scopedTarget += $fallbackMrTarget;
                    }
                }
            }
        }
        $targetGap = max(0, $scopedTarget - $totalSecondarySales);
        $targetAchievementPercent = $scopedTarget > 0 ? min(100, (int) round(($totalSecondarySales / $scopedTarget) * 100)) : 0;

        $visitQuery = $this->visitModel
            ->select('visits.*, doctors.name as doctor_name, products.name as product_name, users.name as mr_name')
            ->join('doctors', 'doctors.id = visits.doctor_id', 'left')
            ->join('products', 'products.id = visits.product_id', 'left')
            ->join('users', 'users.id = visits.created_by_user_id', 'left')
            ->where('DATE(visits.visit_date) >=', $selectedMonth . '-01')
            ->where('DATE(visits.visit_date) <=', $selectedMonth . '-31');

        if (! empty($scopedEmployeeIds)) {
            $visitQuery = $visitQuery->whereIn('visits.created_by_user_id', $scopedEmployeeIds);
        }
        $recentVisits = $visitQuery->orderBy('visit_date', 'DESC')->findAll(10);

        $pendingPlansQuery = $this->fieldPlanModel->select('COUNT(*) as c')->where('status', 'submitted');
        $pendingDoctorsQuery = $this->doctorModel->select('COUNT(*) as c')->where('approval_status', 'pending');
        $pendingChemistsQuery = $this->chemistModel->select('COUNT(*) as c')->where('approval_status', 'pending');
        $pendingExpensesQuery = $this->expenseModel->select('COUNT(*) as c')->where('status', 'submitted');
        $pendingSponsorshipQuery = $this->sponsorshipModel->select('COUNT(*) as c')->where('status', 'submitted');

        if (! empty($scopedEmployeeIds)) {
            $pendingPlansQuery = $pendingPlansQuery->whereIn('created_by_user_id', $scopedEmployeeIds);
            $pendingDoctorsQuery = $pendingDoctorsQuery->whereIn('created_by_user_id', $scopedEmployeeIds);
            $pendingChemistsQuery = $pendingChemistsQuery->whereIn('created_by_user_id', $scopedEmployeeIds);
            $pendingExpensesQuery = $pendingExpensesQuery->whereIn('submitted_by_user_id', $scopedEmployeeIds);
            $pendingSponsorshipQuery = $pendingSponsorshipQuery->whereIn('created_by_user_id', $scopedEmployeeIds);
        }
        $pendingApprovalsCount =
            (int) (($pendingPlansQuery->first()['c'] ?? 0)) +
            (int) (($pendingDoctorsQuery->first()['c'] ?? 0)) +
            (int) (($pendingChemistsQuery->first()['c'] ?? 0)) +
            (int) (($pendingExpensesQuery->first()['c'] ?? 0)) +
            (int) (($pendingSponsorshipQuery->first()['c'] ?? 0));

        // Fetch current month's reminders
        $reminderQuery = $this->reminderModel;
        if ($role !== 'owner' && $role !== 'manager') {
            $reminderQuery = $reminderQuery->where('user_id', $this->userId());
        }
        $allReminders = $reminderQuery->findAll();
        $currentMonthReminders = [];
        $currentMonth = date('m', strtotime($selectedMonth . '-01'));
        
        foreach ($allReminders as $r) {
            if (empty($r['event_date'])) continue;
            if (date('m', strtotime($r['event_date'])) === $currentMonth) {
                $currentMonthReminders[] = $r;
            }
        }
        
        // Sort by day of month
        usort($currentMonthReminders, function($a, $b) {
            return date('d', strtotime($a['event_date'])) <=> date('d', strtotime($b['event_date']));
        });

        $data = [
            'title' => 'Dashboard',
            'totalPrimarySales' => $totalPrimarySales,
            'totalSecondarySales' => $totalSecondarySales,
            'totalStockistsCount' => $totalStockistsCount,
            'activeMrsCount' => $activeMrsCount,
            'submittedEntriesCount' => $submittedEntriesCount,
            'pendingEntriesCount' => $pendingEntriesCount,
            'monthlyTarget' => $scopedTarget,
            'targetGap' => $targetGap,
            'targetAchievementPercent' => $targetAchievementPercent,
            'mrPerformance' => $mrPerformance,
            'pendingList' => $pendingList,
            'recentVisits' => $recentVisits,
            'pendingApprovalsCount' => $pendingApprovalsCount,
            'employees' => $employeesList,
            'selectedMonthVal' => $monthVal,
            'selectedYearVal' => $yearVal,
            'selectedEmployeeId' => $employeeId,
            'selectedMonth' => $selectedMonth,
            'currentMonthReminders' => $currentMonthReminders,
        ];

        return view('admin/dashboard', $this->withBrand($data));
    }

    public function exportDashboard()
    {
        $role = $this->role();
        $userId = $this->userId();

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

        $scopedEmployeeIds = [];
        if ($employeeId > 0) {
            $scopedEmployeeIds = [$employeeId];
        } else {
            if ($role === 'mr') {
                $scopedEmployeeIds = [$userId];
            } elseif ($role === 'manager') {
                $scopedEmployeeIds = [$userId];
                $mrs = $this->userModel->where('manager_user_id', $userId)->findAll();
                foreach ($mrs as $mr) {
                    $scopedEmployeeIds[] = (int) $mr['id'];
                }
            } else {
                $allUsers = $this->userModel->where('is_active', 1)->findAll();
                $scopedEmployeeIds = array_column($allUsers, 'id');
            }
        }

        $stockistQuery = $this->stockistModel;
        if (! empty($scopedEmployeeIds)) {
            $stockistQuery = $stockistQuery->groupStart();
            $isFirst = true;
            foreach ($scopedEmployeeIds as $empId) {
                $empUser = $this->userModel->find($empId);
                if ($empUser) {
                    $empArea = trim((string) ($empUser['area'] ?? ''));
                    if ($isFirst) {
                        $stockistQuery = $stockistQuery->where('created_by_user_id', $empId);
                        $isFirst = false;
                    } else {
                        $stockistQuery = $stockistQuery->orWhere('created_by_user_id', $empId);
                    }
                    if ($empArea !== '') {
                        $stockistQuery = $stockistQuery->orWhere('area', $empArea);
                    }
                }
            }
            $stockistQuery = $stockistQuery->groupEnd();
        }
        $scopedStockists = $stockistQuery->orderBy('name', 'ASC')->findAll();

        $activeMrQuery = $this->userModel->where('role', 'mr')->where('is_active', 1);
        if ($role === 'manager') {
            $activeMrQuery = $activeMrQuery->where('manager_user_id', $userId);
        } elseif ($role === 'mr') {
            $activeMrQuery = $activeMrQuery->where('id', $userId);
        }
        $activeMrsList = $activeMrQuery->findAll();
        $activeMrsCount = count($activeMrsList);

        $stockistIds = array_column($scopedStockists, 'id');
        $entriesMap = [];
        if (! empty($stockistIds)) {
            $entries = $this->salesEntryModel
                ->whereIn('stockist_id', $stockistIds)
                ->where('sales_month', $selectedMonth)
                ->findAll();
            foreach ($entries as $e) {
                $entriesMap[(int) $e['stockist_id']][] = $e;
            }
        }

        $csv = "Employee Name,Role,Sales Value,Target,Achievement %,Pending Stockists Count,Pending Stockists List\n";

        foreach ($activeMrsList as $mr) {
            $mrStQuery = $this->stockistModel->groupStart()->where('created_by_user_id', $mr['id']);
            if (trim((string) ($mr['area'] ?? '')) !== '') {
                $mrStQuery = $mrStQuery->orWhere('area', trim((string) $mr['area']));
            }
            $mrStQuery = $mrStQuery->groupEnd();
            $mrStockists = $mrStQuery->findAll();

            $mrStIds = array_column($mrStockists, 'id');

            $mrSalesVal = 0.0;
            if (! empty($mrStIds)) {
                $mrSecQuery = $this->salesEntryModel
                    ->select('COALESCE(SUM(sales_entries.secondary_value * products.pts_price), 0) as total_sec')
                    ->join('products', 'products.id = sales_entries.product_id', 'left')
                    ->whereIn('sales_entries.stockist_id', $mrStIds)
                    ->where('sales_entries.sales_month', $selectedMonth)
                    ->first();
                $mrSalesVal = (float) ($mrSecQuery['total_sec'] ?? 0);
            }

            $mrPendingStockistsCount = 0;
            $mrPendingNames = [];
            foreach ($mrStockists as $st) {
                $hasSub = false;
                if (! empty($entriesMap[(int) $st['id']])) {
                    foreach ($entriesMap[(int) $st['id']] as $e) {
                        if (in_array(strtolower((string) $e['status']), ['submitted', 'approved'], true)) {
                            $hasSub = true;
                            break;
                        }
                    }
                }
                if (! $hasSub) {
                    $mrPendingStockistsCount++;
                    $mrPendingNames[] = $st['name'];
                }
            }

            $globalTarget = (float) $this->settingModel->getValue('monthly_secondary_target', '1000000');
            $mrTargetPerMr = (!empty($mr['monthly_target'])) ? (float) $mr['monthly_target'] : (float) ($globalTarget / max(1, $activeMrsCount));
            $achievementPercent = $mrTargetPerMr > 0 ? min(100, (int) round(($mrSalesVal / $mrTargetPerMr) * 100)) : 0;

            $line = [
                $mr['name'],
                strtoupper((string) $mr['role']),
                number_format($mrSalesVal, 2, '.', ''),
                number_format($mrTargetPerMr, 2, '.', ''),
                $achievementPercent . '%',
                $mrPendingStockistsCount,
                implode(', ', $mrPendingNames) ?: 'None',
            ];
            $csv .= implode(',', array_map(static fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', $line)) . "\n";
        }

        $filename = 'dashboard_export_' . $selectedMonth . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }

    public function users()
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can manage users.');
        }

        $editId = (int) $this->request->getGet('edit');
        $users = $this->userModel->orderBy('id', 'DESC')->findAll();
        $managers = array_values(array_filter($users, static function (array $user): bool {
            return in_array(strtolower((string) ($user['role'] ?? '')), ['manager'], true)
                && (int) ($user['is_active'] ?? 0) === 1;
        }));

        $assignedTerritoryIds = [];
        if ($editId > 0) {
            $assigned = $this->userTerritoryModel->where('user_id', $editId)->findAll();
            $assignedTerritoryIds = array_column($assigned, 'territory_id');
        }

        $settings = $this->settingModel->getKeyValuePairs();

        return view('admin/users', $this->withBrand([
            'title' => 'User Management',
            'users' => $users,
            'managers' => $managers,
            'editingUser' => $editId > 0 ? $this->userModel->find($editId) : null,
            'territories' => $this->territoryModel->orderBy('name', 'ASC')->findAll(),
            'assignedTerritoryIds' => $assignedTerritoryIds,
            'settings' => $settings,
        ]));
    }

    public function createUser()
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can create users.');
        }

        $name = trim((string) $this->request->getPost('name'));
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $username = strtolower(trim((string) $this->request->getPost('username')));
        $password = (string) $this->request->getPost('password');
        $role = strtolower(trim((string) $this->request->getPost('role')));
        $area = trim((string) $this->request->getPost('area'));
        $mobile = trim((string) $this->request->getPost('mobile'));
        $managerUserId = (int) $this->request->getPost('manager_user_id');

        if ($name === '' || $password === '' || $mobile === '') {
            return redirect()->to('/phadmin/users')->with('error', 'Full name, mobile number and password are required.');
        }

        if ($email === '') {
            $email = 'user' . time() . '@ravion.local';
        }

        if (! in_array($role, ['owner', 'manager', 'mr'], true)) {
            $role = 'mr';
        }

        if ($role === 'manager') {
            $managerUserId = 0;
        } elseif ($role !== 'mr') {
            $managerUserId = 0;
        }

        $existingEmail = $this->userModel->where('email', $email)->first();
        if ($existingEmail) {
            return redirect()->to('/phadmin/users')->with('error', 'Email already exists.');
        }

        if ($username !== '') {
            $existingUsername = $this->userModel->where('username', $username)->first();
            if ($existingUsername) {
                return redirect()->to('/phadmin/users')->with('error', 'Username already exists.');
            }
        }

        $territoryIds = $this->request->getPost('territory_ids') ?: [];
        if (! is_array($territoryIds)) {
            $territoryIds = [];
        }
        $territoryIds = array_map('intval', array_filter($territoryIds));

        if ($role === 'mr') {
            if (count($territoryIds) !== 1) {
                return redirect()->to('/phadmin/users')->with('error', 'An MR must be assigned to exactly one HQ.');
            }
            $t = $this->territoryModel->find($territoryIds[0]);
            if (! $t || strtolower((string)$t['type']) !== 'hq') {
                return redirect()->to('/phadmin/users')->with('error', 'The assigned territory must be a Headquarter (HQ).');
            }
        } elseif ($role === 'manager') {
            if (empty($territoryIds)) {
                return redirect()->to('/phadmin/users')->with('error', 'A Manager must be assigned to at least one HQ.');
            }
            $ts = $this->territoryModel->whereIn('id', $territoryIds)->findAll();
            if (count($ts) !== count($territoryIds)) {
                return redirect()->to('/phadmin/users')->with('error', 'One or more assigned territories are invalid.');
            }
            foreach ($ts as $t) {
                if (strtolower((string)$t['type']) !== 'hq') {
                    return redirect()->to('/phadmin/users')->with('error', 'All assigned territories for a Manager must be Headquarters (HQs).');
                }
            }
        } else {
            $territoryIds = [];
        }

        $daHq = $this->request->getPost('da_hq');
        $daEx = $this->request->getPost('da_ex');
        $daOs = $this->request->getPost('da_os');
        $monthlyTarget = $this->request->getPost('monthly_target');

        $userId = $this->userModel->insert([
            'name' => $name,
            'username' => $username !== '' ? $username : null,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'area' => $area !== '' ? $area : null,
            'mobile' => $mobile,
            'manager_user_id' => $managerUserId > 0 ? $managerUserId : null,
            'is_active' => 1,
            'da_hq' => ($daHq !== '' && $daHq !== null) ? (int) $daHq : null,
            'da_ex' => ($daEx !== '' && $daEx !== null) ? (int) $daEx : null,
            'da_os' => ($daOs !== '' && $daOs !== null) ? (int) $daOs : null,
            'monthly_target' => ($monthlyTarget !== '' && $monthlyTarget !== null) ? (int) $monthlyTarget : null,
        ]);

        if ($userId > 0 && ! empty($territoryIds)) {
            $batch = [];
            foreach ($territoryIds as $tId) {
                $batch[] = [
                    'user_id' => $userId,
                    'territory_id' => (int) $tId,
                ];
            }
            $this->userTerritoryModel->insertBatch($batch);
        }

        return redirect()->to('/phadmin/users')->with('success', 'User created successfully.');
    }

    public function updateUser(int $id)
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can update users.');
        }

        $user = $this->userModel->find($id);
        if (! $user) {
            return redirect()->to('/phadmin/users')->with('error', 'User not found.');
        }

        $name = trim((string) $this->request->getPost('name'));
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $mobile = trim((string) $this->request->getPost('mobile'));
        $role = strtolower(trim((string) $this->request->getPost('role')));
        $area = trim((string) $this->request->getPost('area'));
        $managerUserId = (int) $this->request->getPost('manager_user_id');

        if ($name === '' || $mobile === '') {
            return redirect()->to('/phadmin/users?edit=' . $id)->with('error', 'Full name and mobile number are required.');
        }

        if ($email === '') {
            $email = (string) ($user['email'] ?? ('user' . time() . '@ravion.local'));
        }

        if (! in_array($role, ['owner', 'manager', 'mr'], true)) {
            $role = 'mr';
        }

        if ($role !== 'mr') {
            $managerUserId = 0;
        }

        $duplicateEmail = $this->userModel
            ->where('email', $email)
            ->where('id !=', $id)
            ->first();
        if ($duplicateEmail) {
            return redirect()->to('/phadmin/users?edit=' . $id)->with('error', 'Email already exists for another user.');
        }

        $territoryIds = $this->request->getPost('territory_ids') ?: [];
        if (! is_array($territoryIds)) {
            $territoryIds = [];
        }
        $territoryIds = array_map('intval', array_filter($territoryIds));

        if ($role === 'mr') {
            if (count($territoryIds) !== 1) {
                return redirect()->to('/phadmin/users?edit=' . $id)->with('error', 'An MR must be assigned to exactly one HQ.');
            }
            $t = $this->territoryModel->find($territoryIds[0]);
            if (! $t || strtolower((string)$t['type']) !== 'hq') {
                return redirect()->to('/phadmin/users?edit=' . $id)->with('error', 'The assigned territory must be a Headquarter (HQ).');
            }
        } elseif ($role === 'manager') {
            if (empty($territoryIds)) {
                return redirect()->to('/phadmin/users?edit=' . $id)->with('error', 'A Manager must be assigned to at least one HQ.');
            }
            $ts = $this->territoryModel->whereIn('id', $territoryIds)->findAll();
            if (count($ts) !== count($territoryIds)) {
                return redirect()->to('/phadmin/users?edit=' . $id)->with('error', 'One or more assigned territories are invalid.');
            }
            foreach ($ts as $t) {
                if (strtolower((string)$t['type']) !== 'hq') {
                    return redirect()->to('/phadmin/users?edit=' . $id)->with('error', 'All assigned territories for a Manager must be Headquarters (HQs).');
                }
            }
        } else {
            $territoryIds = [];
        }

        $daHq = $this->request->getPost('da_hq');
        $daEx = $this->request->getPost('da_ex');
        $daOs = $this->request->getPost('da_os');
        $monthlyTarget = $this->request->getPost('monthly_target');

        $payload = [
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
            'role' => $role,
            'area' => $area !== '' ? $area : null,
            'manager_user_id' => $managerUserId > 0 ? $managerUserId : null,
            'da_hq' => ($daHq !== '' && $daHq !== null) ? (int) $daHq : null,
            'da_ex' => ($daEx !== '' && $daEx !== null) ? (int) $daEx : null,
            'da_os' => ($daOs !== '' && $daOs !== null) ? (int) $daOs : null,
            'monthly_target' => ($monthlyTarget !== '' && $monthlyTarget !== null) ? (int) $monthlyTarget : null,
        ];

        $newPassword = trim((string) $this->request->getPost('password'));
        if ($newPassword !== '') {
            $payload['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $this->userModel->update($id, $payload);

        // Update territory assignments
        $this->userTerritoryModel->where('user_id', $id)->delete();
        if (! empty($territoryIds)) {
            $batch = [];
            foreach ($territoryIds as $tId) {
                $batch[] = [
                    'user_id' => $id,
                    'territory_id' => (int) $tId,
                ];
            }
            $this->userTerritoryModel->insertBatch($batch);
        }

        return redirect()->to('/phadmin/users')->with('success', 'User updated successfully.');
    }

    public function toggleUser(int $id)
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can update user status.');
        }

        $user = $this->userModel->find($id);
        if (! $user) {
            return redirect()->to('/phadmin/users')->with('error', 'User not found.');
        }

        if ((int) $user['id'] === $this->userId()) {
            return redirect()->to('/phadmin/users')->with('error', 'Super Admin cannot deactivate current account.');
        }

        $this->userModel->update($id, ['is_active' => ((int) $user['is_active'] === 1 ? 0 : 1)]);

        return redirect()->to('/phadmin/users')->with('success', 'User status updated.');
    }

    public function deleteUser(int $id)
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can delete users.');
        }

        $user = $this->userModel->find($id);
        if (! $user) {
            return redirect()->to('/phadmin/users')->with('error', 'User not found.');
        }

        if ((int) $user['id'] === $this->userId()) {
            return redirect()->to('/phadmin/users')->with('error', 'Super Admin cannot delete current account.');
        }

        $this->userModel->delete($id);
        return redirect()->to('/phadmin/users')->with('success', 'User deleted successfully.');
    }

    public function updateDaSettings()
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can update master settings.');
        }

        $keys = [
            'da_mr_hq',
            'da_mr_ex',
            'da_mr_os',
            'da_manager_hq',
            'da_manager_ex',
            'da_manager_os',
            'monthly_secondary_target',
        ];

        foreach ($keys as $key) {
            $val = trim((string) $this->request->getPost($key));
            if ($val !== '') {
                $this->settingModel->save([
                    'key_name' => $key,
                    'value_text' => $val,
                ]);
            }
        }

        return redirect()->to('/phadmin/users')->with('success', 'Master settings updated successfully.');
    }

    public function territories()
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can manage territories.');
        }

        $territories = $this->territoryModel
            ->select('territories.*, parent.name as parent_name')
            ->join('territories as parent', 'parent.id = territories.parent_id', 'left')
            ->orderBy('territories.name', 'ASC')
            ->findAll();

        return view('admin/territories', $this->withBrand([
            'title' => 'Territory Management',
            'territories' => $territories,
        ]));
    }

    public function createTerritory()
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can manage territories.');
        }

        $name = trim((string) $this->request->getPost('name'));
        $type = trim((string) $this->request->getPost('type'));
        
        if ($name === '') {
            return redirect()->to('/phadmin/territories')->with('error', 'Territory name is required.');
        }

        $pId = null;

        if ($type === 'hq') {
            $stateMode = $this->request->getPost('state_mode') ?: 'existing';
            if ($stateMode === 'new') {
                $newStateName = trim((string)$this->request->getPost('new_state_name'));
                if ($newStateName === '') {
                    return redirect()->to('/phadmin/territories')->with('error', 'State name is required when creating a new state.');
                }
                
                // Check if state with this name already exists
                $existingState = $this->territoryModel->where('type', 'state')->where('name', $newStateName)->first();
                if ($existingState) {
                    $pId = (int)$existingState['id'];
                } else {
                    $pId = $this->territoryModel->insert([
                        'name' => $newStateName,
                        'type' => 'state',
                        'parent_id' => null,
                    ]);
                }
            } else {
                $stateId = (int)$this->request->getPost('state_id');
                if ($stateId <= 0) {
                    return redirect()->to('/phadmin/territories')->with('error', 'Please select a valid State.');
                }
                $pId = $stateId;
            }
        } else {
            // For other child levels, parent_id is passed
            $parentId = $this->request->getPost('parent_id');
            if ($parentId !== '' && $parentId !== null && (int)$parentId > 0) {
                $pId = (int)$parentId;
            }
        }

        if (! in_array($type, ['state', 'hq', 'ex-station', 'out-station'], true)) {
            return redirect()->to('/phadmin/territories')->with('error', 'Valid type is required.');
        }

        $this->territoryModel->insert([
            'name' => $name,
            'type' => $type,
            'parent_id' => $pId,
        ]);

        return redirect()->to('/phadmin/territories')->with('success', 'Territory created successfully.');
    }

    public function updateTerritory(int $id)
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can manage territories.');
        }

        $territory = $this->territoryModel->find($id);
        if (! $territory) {
            return redirect()->to('/phadmin/territories')->with('error', 'Territory not found.');
        }

        $name = trim((string) $this->request->getPost('name'));
        $type = trim((string) $this->request->getPost('type'));
        $parentId = $this->request->getPost('parent_id');

        if ($name === '' || ! in_array($type, ['state', 'hq', 'ex-station', 'out-station'], true)) {
            return redirect()->to('/phadmin/territories')->with('error', 'Territory name and valid type are required.');
        }

        $pId = ($parentId !== '' && $parentId !== null && (int)$parentId > 0) ? (int)$parentId : null;

        if ($pId === $id) {
            return redirect()->to('/phadmin/territories')->with('error', 'A territory cannot be its own parent.');
        }

        $this->territoryModel->update($id, [
            'name' => $name,
            'type' => $type,
            'parent_id' => $pId,
        ]);

        return redirect()->to('/phadmin/territories')->with('success', 'Territory updated successfully.');
    }

    public function deleteTerritory(int $id)
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can manage territories.');
        }

        $territory = $this->territoryModel->find($id);
        if (! $territory) {
            return redirect()->to('/phadmin/territories')->with('error', 'Territory not found.');
        }

        $this->territoryModel->delete($id);
        return redirect()->to('/phadmin/territories')->with('success', 'Territory deleted successfully.');
    }

    public function products()
    {
        if (! in_array($this->role(), ['owner'], true)) {
            return $this->deny('Only Super Admin can manage products.');
        }

        $editId = (int) $this->request->getGet('edit');
        $editingProduct = $editId > 0 ? $this->productModel->find($editId) : null;

        $products = $this->productModel->orderBy('display_order', 'ASC')->orderBy('id', 'ASC')->findAll();

        $productSlides = [];
        if (! empty($products)) {
            $productIds = array_column($products, 'id');
            $slides = $this->slideModel->whereIn('product_id', $productIds)
                                       ->where('status', 'active')
                                       ->orderBy('slide_order', 'ASC')
                                       ->orderBy('id', 'ASC')
                                       ->findAll();
            foreach ($slides as $slide) {
                $productSlides[$slide['product_id']][] = $slide;
            }
        }

        foreach ($products as &$p) {
            $p['slides'] = $productSlides[$p['id']] ?? [];
        }
        unset($p);

        if ($editingProduct) {
            $editingProduct['slides'] = $this->slideModel->where('product_id', $editingProduct['id'])
                                                         ->where('status', 'active')
                                                         ->orderBy('slide_order', 'ASC')
                                                         ->orderBy('id', 'ASC')
                                                         ->findAll();
        }

        return view('admin/products', $this->withBrand([
            'title' => 'Products',
            'products' => $products,
            'editingProduct' => $editingProduct,
        ]));
    }

    public function createProduct()
    {
        if (! in_array($this->role(), ['owner'], true)) {
            return $this->deny('Only Super Admin can create products.');
        }

        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->to('/phadmin/products')->with('error', 'Product name is required.');
        }

        $ptsPrice = (float) $this->request->getPost('pts_price');

        $imageFiles = $this->request->getFileMultiple('product_image');
        $imagePaths = [];
        if ($imageFiles) {
            foreach ($imageFiles as $imageFile) {
                if ($imageFile && $imageFile->isValid() && ! $imageFile->hasMoved()) {
                    if (!in_array(strtolower((string) $imageFile->getExtension()), ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                         return redirect()->to('/phadmin/products')->with('error', 'Only image files are allowed for product image.');
                    }
                    $newName = $imageFile->getRandomName();
                    $targetDir = FCPATH . 'uploads/products/images';
                    if (! is_dir($targetDir)) {
                        mkdir($targetDir, 0775, true);
                    }
                    $imageFile->move($targetDir, $newName);
                    $imagePaths[] = 'uploads/products/images/' . $newName;
                }
            }
        }
        $imagePath = !empty($imagePaths) ? json_encode($imagePaths) : null;

        // Get next display order for the new product
        $maxOrder = $this->productModel->selectMax('display_order')->first();
        $nextOrder = isset($maxOrder['display_order']) ? ((int)$maxOrder['display_order'] + 1) : 1;

        $this->productModel->insert([
            'name' => $name,
            'pts_price' => $ptsPrice,
            'description' => trim((string) $this->request->getPost('description')),
            'image_path' => $imagePath,
            'detail_pdf' => '',
            'packing' => trim((string) $this->request->getPost('packing')),
            'display_order' => $nextOrder,
        ]);
        $productId = $this->productModel->getInsertID();

        $pdfFiles = $this->request->getFileMultiple('detail_pdf_files');
        $uploadedPaths = [];

        if ($pdfFiles) {
            foreach ($pdfFiles as $pdfFile) {
                if ($pdfFile && $pdfFile->isValid() && ! $pdfFile->hasMoved()) {
                    $ext = strtolower((string) $pdfFile->getExtension());
                    if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'])) {
                        return redirect()->to('/phadmin/products')->with('error', 'Only PDF and images (JPG, JPEG, PNG, WEBP) are allowed for product detailing.');
                    }

                    $newName = $pdfFile->getRandomName();
                    $targetDir = FCPATH . 'uploads/products/' . $productId;
                    if (! is_dir($targetDir)) {
                        mkdir($targetDir, 0775, true);
                    }
                    $pdfFile->move($targetDir, $newName);
                    $relativePath = 'uploads/products/' . $productId . '/' . $newName;

                    $title = pathinfo($pdfFile->getClientName(), PATHINFO_FILENAME);

                    $maxSlideOrder = $this->slideModel->where('product_id', $productId)->selectMax('slide_order')->first();
                    $nextSlideOrder = isset($maxSlideOrder['slide_order']) ? ((int)$maxSlideOrder['slide_order'] + 1) : 1;

                    $this->slideModel->insert([
                        'product_id' => $productId,
                        'title' => $title,
                        'file_path' => $relativePath,
                        'status' => 'active',
                        'created_by_user_id' => $this->userId(),
                        'slide_order' => $nextSlideOrder,
                    ]);

                    $uploadedPaths[] = $relativePath;
                }
            }
        }

        if (! empty($uploadedPaths)) {
            $this->productModel->update($productId, [
                'detail_pdf' => $uploadedPaths[0],
            ]);
        }

        return redirect()->to('/phadmin/products')->with('success', 'Product added successfully.');
    }

    public function updateProduct(int $id)
    {
        if (! in_array($this->role(), ['owner'], true)) {
            return $this->deny('Only Super Admin can update products.');
        }

        $product = $this->productModel->find($id);
        if (! $product) {
            return redirect()->to('/phadmin/products')->with('error', 'Product not found.');
        }

        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->to('/phadmin/products')->with('error', 'Product name is required.');
        }

        $pdfFiles = $this->request->getFileMultiple('detail_pdf_files');
        $uploadedPaths = [];

        if ($pdfFiles) {
            foreach ($pdfFiles as $pdfFile) {
                if ($pdfFile && $pdfFile->isValid() && ! $pdfFile->hasMoved()) {
                    $ext = strtolower((string) $pdfFile->getExtension());
                    if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'])) {
                        return redirect()->to('/phadmin/products')->with('error', 'Only PDF and images (JPG, JPEG, PNG, WEBP) are allowed for product detailing.');
                    }

                    $newName = $pdfFile->getRandomName();
                    $targetDir = FCPATH . 'uploads/products/' . $id;
                    if (! is_dir($targetDir)) {
                        mkdir($targetDir, 0775, true);
                    }
                    $pdfFile->move($targetDir, $newName);
                    $relativePath = 'uploads/products/' . $id . '/' . $newName;

                    $title = pathinfo($pdfFile->getClientName(), PATHINFO_FILENAME);

                    $maxSlideOrder = $this->slideModel->where('product_id', $id)->selectMax('slide_order')->first();
                    $nextSlideOrder = isset($maxSlideOrder['slide_order']) ? ((int)$maxSlideOrder['slide_order'] + 1) : 1;

                    $this->slideModel->insert([
                        'product_id' => $id,
                        'title' => $title,
                        'file_path' => $relativePath,
                        'status' => 'active',
                        'created_by_user_id' => $this->userId(),
                        'slide_order' => $nextSlideOrder,
                    ]);

                    $uploadedPaths[] = $relativePath;
                }
            }
        }

        $detailPdf = (string) ($product['detail_pdf'] ?? '');
        if (! empty($uploadedPaths) && empty($detailPdf)) {
            $detailPdf = $uploadedPaths[0];
        }

        $imageFiles = $this->request->getFileMultiple('product_image');
        $existingImagePath = (string) ($product['image_path'] ?? '');
        $imagePaths = [];
        if (!empty($existingImagePath)) {
            $decoded = json_decode($existingImagePath, true);
            if (is_array($decoded)) {
                $imagePaths = $decoded;
            } else {
                $imagePaths = [$existingImagePath];
            }
        }

        if ($imageFiles) {
            foreach ($imageFiles as $imageFile) {
                if ($imageFile && $imageFile->isValid() && ! $imageFile->hasMoved()) {
                    if (!in_array(strtolower((string) $imageFile->getExtension()), ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                         return redirect()->to('/phadmin/products')->with('error', 'Only image files are allowed for product image.');
                    }
                    $newName = $imageFile->getRandomName();
                    $targetDir = FCPATH . 'uploads/products/images';
                    if (! is_dir($targetDir)) {
                        mkdir($targetDir, 0775, true);
                    }
                    $imageFile->move($targetDir, $newName);
                    $imagePaths[] = 'uploads/products/images/' . $newName;
                }
            }
        }
        $imagePathToSave = !empty($imagePaths) ? json_encode($imagePaths) : null;

        $this->productModel->update($id, [
            'name' => $name,
            'pts_price' => (float) $this->request->getPost('pts_price'),
            'description' => trim((string) $this->request->getPost('description')),
            'image_path' => $imagePathToSave,
            'detail_pdf' => $detailPdf,
            'packing' => trim((string) $this->request->getPost('packing')),
        ]);

        return redirect()->to('/phadmin/products')->with('success', 'Product updated successfully.');
    }

    public function deleteProduct(int $id)
    {
        if (! in_array($this->role(), ['owner'], true)) {
            return $this->deny('Only Super Admin can delete products.');
        }

        $product = $this->productModel->find($id);
        if (! $product) {
            return redirect()->to('/phadmin/products')->with('error', 'Product not found.');
        }

        $this->productModel->delete($id);
        return redirect()->to('/phadmin/products')->with('success', 'Product deleted successfully.');
    }

    public function reorderProducts()
    {
        if (! in_array($this->role(), ['owner'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'Only Super Admin can reorder products.']);
        }

        $payload = $this->request->getJSON(true);
        $order = $payload['order'] ?? [];

        if (! empty($order)) {
            foreach ($order as $index => $id) {
                $this->productModel->update((int)$id, ['display_order' => $index + 1]);
            }
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Invalid data.']);
    }

    public function reorderSlides()
    {
        if (! in_array($this->role(), ['owner'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'error' => 'Only Super Admin can reorder slides.']);
        }

        $payload = $this->request->getJSON(true);
        $order = $payload['order'] ?? [];

        if (! empty($order)) {
            foreach ($order as $index => $id) {
                $this->slideModel->update((int)$id, ['slide_order' => $index + 1]);
            }
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Invalid data.']);
    }

    public function doctors(): string
    {
        $role = $this->role();
        $category = strtoupper(trim((string) $this->request->getGet('category')));
        $doctorName = trim((string) $this->request->getGet('name'));
        $areaPatch = trim((string) $this->request->getGet('area'));
        $editId = (int) $this->request->getGet('edit');
        $editingDoctor = $editId > 0 ? $this->doctorModel->find($editId) : null;

        $query = $this->doctorModel
            ->select('doctors.*, COALESCE(territories.name, doctors.area_patch) as area_patch, creator.name as creator_name, assignee.name as assignee_name')
            ->join('territories', 'territories.id = doctors.territory_id', 'left')
            ->join('users as creator', 'creator.id = doctors.created_by_user_id', 'left')
            ->join('users as assignee', 'assignee.id = doctors.assigned_to_user_id', 'left');

        if (in_array($category, ['A', 'B', 'C'], true)) {
            $query = $query->where('doctors.category', $category);
        }
        if ($doctorName !== '') {
            $query = $query->like('doctors.name', $doctorName);
        }
        if ($areaPatch !== '') {
            $query = $query->groupStart()
                ->like('territories.name', $areaPatch)
                ->orLike('doctors.area_patch', $areaPatch)
            ->groupEnd();
        }

        $accessibleTerritoryIds = $this->getAccessibleTerritoryIds($this->userId(), $role);
        if ($accessibleTerritoryIds !== null) {
            $query = $query->whereIn('doctors.territory_id', $accessibleTerritoryIds);
        }

        if ($role === 'manager') {
            $query = $query->groupStart()
                ->where('doctors.approval_status', 'approved')
                ->orWhere('doctors.approval_status', 'pending')
                ->groupEnd();
        }

        $doctors = $query->orderBy('doctors.id', 'DESC')->findAll();

        if ($accessibleTerritoryIds !== null) {
            $allowedTerritories = $this->territoryModel
                ->whereIn('id', $accessibleTerritoryIds)
                ->orderBy('name', 'ASC')
                ->findAll();
        } else {
            $allowedTerritories = $this->territoryModel
                ->orderBy('name', 'ASC')
                ->findAll();
        }

        return view('admin/doctors', $this->withBrand([
            'title' => 'Doctors',
            'doctors' => $doctors,
            'selectedCategory' => $category,
            'selectedName' => $doctorName,
            'selectedArea' => $areaPatch,
            'editingDoctor' => $editingDoctor,
            'canApprove' => in_array($role, ['owner', 'manager'], true),
            'assignableUsers' => $this->userModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll(),
            'allowedTerritories' => $allowedTerritories,
        ]));
    }

    public function createDoctor()
    {
        $role = $this->role();
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->to('/phadmin/doctors')->with('error', 'Doctor name is required.');
        }

        $category = strtoupper((string) $this->request->getPost('category'));
        if (! in_array($category, ['A', 'B', 'C'], true)) {
            $category = 'C';
        }

        $territoryId = (int) $this->request->getPost('territory_id');
        $accessibleTerritoryIds = $this->getAccessibleTerritoryIds($this->userId(), $role);
        if ($accessibleTerritoryIds !== null) {
            if ($territoryId <= 0 || ! in_array($territoryId, $accessibleTerritoryIds, true)) {
                return redirect()->to('/phadmin/doctors')->with('error', 'Please select a valid territory from your assigned area.');
            }
        }

        $tRecord = $this->territoryModel->find($territoryId);
        $areaName = $tRecord ? $tRecord['name'] : '';

        $this->doctorModel->insert([
            'name' => $name,
            'speciality' => trim((string) $this->request->getPost('speciality')),
            'clinic_address' => trim((string) $this->request->getPost('clinic_address')),
            'contact' => trim((string) $this->request->getPost('contact')),
            'category' => $category,
            'monthly_potential' => trim((string) $this->request->getPost('monthly_potential')),
            'area_patch' => $areaName,
            'territory_id' => $territoryId ?: null,
            'assigned_to_user_id' => (int) $this->request->getPost('assigned_to_user_id') ?: null,
            'created_by_user_id' => $this->userId(),
            'approval_status' => $role === 'owner' ? 'approved' : 'pending',
            'approved_by_user_id' => $role === 'owner' ? $this->userId() : null,
            'birthday' => trim((string) $this->request->getPost('birthday')) ?: null,
        ]);

        return redirect()->to('/phadmin/doctors')->with('success', 'Doctor saved successfully.');
    }

    public function updateDoctor(int $id)
    {
        $doctor = $this->doctorModel->find($id);
        if (! $doctor) {
            return redirect()->to('/phadmin/doctors')->with('error', 'Doctor not found.');
        }

        $role = $this->role();
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->to('/phadmin/doctors')->with('error', 'Doctor name is required.');
        }

        $category = strtoupper((string) $this->request->getPost('category'));
        if (! in_array($category, ['A', 'B', 'C'], true)) {
            $category = 'C';
        }

        $territoryId = (int) $this->request->getPost('territory_id');
        $accessibleTerritoryIds = $this->getAccessibleTerritoryIds($this->userId(), $role);
        if ($accessibleTerritoryIds !== null) {
            if ($territoryId <= 0 || ! in_array($territoryId, $accessibleTerritoryIds, true)) {
                return redirect()->to('/phadmin/doctors?edit=' . $id)->with('error', 'Please select a valid territory from your assigned area.');
            }
        }

        $tRecord = $this->territoryModel->find($territoryId);
        $areaName = $tRecord ? $tRecord['name'] : '';

        $this->doctorModel->update($id, [
            'name' => $name,
            'speciality' => trim((string) $this->request->getPost('speciality')),
            'clinic_address' => trim((string) $this->request->getPost('clinic_address')),
            'contact' => trim((string) $this->request->getPost('contact')),
            'category' => $category,
            'monthly_potential' => trim((string) $this->request->getPost('monthly_potential')),
            'area_patch' => $areaName,
            'territory_id' => $territoryId ?: null,
            'assigned_to_user_id' => (int) $this->request->getPost('assigned_to_user_id') ?: null,
            'approval_status' => $this->role() === 'owner' ? 'approved' : 'pending',
            'approved_by_user_id' => $this->role() === 'owner' ? $this->userId() : null,
            'birthday' => trim((string) $this->request->getPost('birthday')) ?: null,
        ]);

        return redirect()->to('/phadmin/doctors')->with('success', 'Doctor updated successfully.');
    }

    public function approveDoctor(int $id)
    {
        $doctor = $this->doctorModel->find($id);
        if (! $doctor) {
            return redirect()->to('/phadmin/doctors')->with('error', 'Doctor not found.');
        }

        if (! $this->canApproveUser((int) ($doctor['created_by_user_id'] ?? 0))) {
            return $this->deny('You do not have permission to approve this doctor record.');
        }

        $status = trim((string) $this->request->getPost('approval_status'));
        if (! in_array($status, ['approved', 'rejected'], true)) {
            return redirect()->to('/phadmin/doctors')->with('error', 'Invalid approval status.');
        }

        $this->doctorModel->update($id, [
            'approval_status' => $status,
            'approved_by_user_id' => $this->userId(),
        ]);

        return redirect()->to('/phadmin/doctors')->with('success', 'Doctor approval updated.');
    }

    public function bulkApproveDoctors()
    {
        $idsStr = trim((string) $this->request->getPost('doctor_ids'));
        if (empty($idsStr)) {
            return redirect()->to('/phadmin/doctors')->with('error', 'No doctors selected.');
        }

        $ids = array_filter(array_map('intval', explode(',', $idsStr)));
        if (empty($ids)) {
            return redirect()->to('/phadmin/doctors')->with('error', 'Invalid selection.');
        }

        $count = 0;
        foreach ($ids as $id) {
            $doctor = $this->doctorModel->find($id);
            if ($doctor && $this->canApproveUser((int) ($doctor['created_by_user_id'] ?? 0)) && $doctor['approval_status'] === 'pending') {
                $this->doctorModel->update($id, [
                    'approval_status' => 'approved',
                    'approved_by_user_id' => $this->userId(),
                ]);
                $count++;
            }
        }

        if ($count > 0) {
            return redirect()->to('/phadmin/doctors')->with('success', "$count doctor(s) approved successfully.");
        } else {
            return redirect()->to('/phadmin/doctors')->with('error', 'No doctors could be approved (either not found or permission denied).');
        }
    }

    public function deleteDoctor(int $id)
    {
        $doctor = $this->doctorModel->find($id);
        if (! $doctor) {
            return redirect()->to('/phadmin/doctors')->with('error', 'Doctor not found.');
        }

        $this->doctorModel->delete($id);
        return redirect()->to('/phadmin/doctors')->with('success', 'Doctor deleted successfully.');
    }

    public function visits(): string
    {
        $role = $this->role();
        $selectedMonth = trim((string) $this->request->getGet('month'));
        if (! preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = date('Y-m');
        }

        $query = $this->visitModel
            ->select('visits.*, doctors.name as doctor_name, products.name as product_name, chemists.name as chemist_name, field_plans.plan_date as planned_date, field_plans.route_name as planned_route')
            ->join('doctors', 'doctors.id = visits.doctor_id', 'left')
            ->join('products', 'products.id = visits.product_id', 'left')
            ->join('chemists', 'chemists.id = visits.chemist_id', 'left')
            ->join('field_plans', 'field_plans.id = visits.planned_plan_id', 'left')
            ->join('users', 'users.id = visits.created_by_user_id', 'left');

        $query = $query->groupStart()
            ->where('visits.dcr_month', $selectedMonth)
            ->orWhere("substr(visits.visit_date,1,7) = '{$selectedMonth}'", null, false)
            ->groupEnd();

        if ($role === 'manager') {
            $query = $query->groupStart()
                ->where('users.id', $this->userId())
                ->orWhere('users.manager_user_id', $this->userId())
            ->groupEnd();
        } elseif ($role === 'mr') {
            $query = $query->where('visits.created_by_user_id', $this->userId());
        }

        $visits = $query->orderBy('visit_date', 'DESC')->findAll();

        $doctorQuery = $this->doctorModel->where('approval_status', 'approved');
        $chemistQuery = $this->chemistModel->where('approval_status', 'approved');

        if ($role === 'mr') {
            $doctorQuery = $doctorQuery->where('created_by_user_id', $this->userId());
            $chemistQuery = $chemistQuery->where('created_by_user_id', $this->userId());
        }

        $planQuery = $this->fieldPlanModel
            ->select('field_plans.id, field_plans.plan_date, field_plans.activity_type, field_plans.objective, field_plans.route_name, doctors.name as doctor_name')
            ->join('doctors', 'doctors.id = field_plans.doctor_id', 'left')
            ->where('field_plans.plan_month', $selectedMonth)
            ->whereIn('field_plans.status', ['submitted', 'approved']);

        if ($role === 'mr') {
            $planQuery = $planQuery->groupStart()
                ->where('field_plans.assigned_user_id', $this->userId())
                ->orWhere('field_plans.created_by_user_id', $this->userId())
            ->groupEnd();
        } elseif ($role === 'manager') {
            $planQuery = $planQuery->join('users as fp_users', 'fp_users.id = field_plans.created_by_user_id', 'left')
                ->groupStart()
                    ->where('field_plans.assigned_user_id', $this->userId())
                    ->orWhere('fp_users.manager_user_id', $this->userId())
                ->groupEnd();
        }

        return view('admin/visits', $this->withBrand([
            'title' => 'Visit Log',
            'visits' => $visits,
            'doctors' => $doctorQuery->orderBy('name', 'ASC')->findAll(),
            'products' => $this->productModel->orderBy('name', 'ASC')->findAll(),
            'chemists' => $chemistQuery->orderBy('name', 'ASC')->findAll(),
            'selectedMonth' => $selectedMonth,
            'plannedVisits' => $planQuery->orderBy('field_plans.plan_date', 'ASC')->findAll(),
        ]));
    }

    public function createVisit()
    {
        $role = $this->role();
        $doctorId = (int) $this->request->getPost('doctor_id');
        $productId = (int) $this->request->getPost('product_id');
        $chemistId = (int) $this->request->getPost('chemist_id');
        $plannedPlanId = (int) $this->request->getPost('planned_plan_id');
        $dcrDate = trim((string) $this->request->getPost('dcr_date'));
        if ($dcrDate === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dcrDate)) {
            $dcrDate = date('Y-m-d');
        }
        $dcrMonth = substr($dcrDate, 0, 7);

        if ($doctorId <= 0 || $productId <= 0) {
            return redirect()->to('/phadmin/visits')->with('error', 'Doctor and Product are required.');
        }

        $actionType = (string) $this->request->getPost('action_type');
        $allowed = ['detailing', 'call', 'exit', 'dcr'];
        if (! in_array($actionType, $allowed, true)) {
            $actionType = 'detailing';
        }

        $isDeviation = 0;
        $deviationReason = trim((string) $this->request->getPost('deviation_reason'));
        if ($plannedPlanId > 0) {
            $planned = $this->fieldPlanModel->find($plannedPlanId);
            if (! $planned) {
                return redirect()->to('/phadmin/visits')->with('error', 'Selected tour plan does not exist.');
            }

            if ($role === 'mr') {
                $assignedTo = (int) ($planned['assigned_user_id'] ?? 0);
                $createdBy = (int) ($planned['created_by_user_id'] ?? 0);
                if ($assignedTo !== $this->userId() && $createdBy !== $this->userId()) {
                    return redirect()->to('/phadmin/visits')->with('error', 'MR can log DCR only against own tour plans.');
                }
            }

            if ((string) ($planned['plan_date'] ?? '') !== $dcrDate) {
                $isDeviation = 1;
                if ($deviationReason === '') {
                    $deviationReason = 'Visit executed on non-planned date.';
                }
            }
        } elseif ($deviationReason !== '') {
            $isDeviation = 1;
        }

        $this->visitModel->insert([
            'doctor_id' => $doctorId,
            'product_id' => $productId,
            'chemist_id' => $chemistId > 0 ? $chemistId : null,
            'planned_plan_id' => $plannedPlanId > 0 ? $plannedPlanId : null,
            'visit_date' => $dcrDate . ' ' . date('H:i:s'),
            'dcr_date' => $dcrDate,
            'dcr_month' => $dcrMonth,
            'duration_minutes' => max(0, (int) $this->request->getPost('duration_minutes')),
            'pob_value' => max(0, (float) $this->request->getPost('pob_value')),
            'sample_units' => max(0, (int) $this->request->getPost('sample_units')),
            'action_type' => $actionType,
            'is_deviation' => $isDeviation,
            'deviation_reason' => $deviationReason,
            'created_by_user_id' => $this->userId(),
            'feedback' => trim((string) $this->request->getPost('feedback')),
            'next_objective' => trim((string) $this->request->getPost('next_objective')),
            'joint_call_with' => trim((string) $this->request->getPost('joint_call_with')),
            'report_status' => 'submitted',
        ]);

        return redirect()->to('/phadmin/visits')->with('success', 'Visit logged successfully.');
    }

    public function chemists(): string
    {
        $role = $this->role();
        $editId = (int) $this->request->getGet('edit');
        $editingChemist = $editId > 0 ? $this->chemistModel->find($editId) : null;

        $query = $this->chemistModel
            ->select('chemists.*, COALESCE(territories.name, chemists.area) as area, creator.name as creator_name')
            ->join('territories', 'territories.id = chemists.territory_id', 'left')
            ->join('users as creator', 'creator.id = chemists.created_by_user_id', 'left');

        $accessibleTerritoryIds = $this->getAccessibleTerritoryIds($this->userId(), $role);
        if ($accessibleTerritoryIds !== null) {
            $query = $query->whereIn('chemists.territory_id', $accessibleTerritoryIds);
        }

        if ($role === 'manager') {
            $query = $query->groupStart()
                ->where('chemists.approval_status', 'approved')
                ->orWhere('chemists.approval_status', 'pending')
                ->groupEnd();
        }

        $chemists = $query->orderBy('chemists.id', 'DESC')->findAll();

        if ($accessibleTerritoryIds !== null) {
            $allowedTerritories = $this->territoryModel
                ->whereIn('id', $accessibleTerritoryIds)
                ->orderBy('name', 'ASC')
                ->findAll();
        } else {
            $allowedTerritories = $this->territoryModel
                ->orderBy('name', 'ASC')
                ->findAll();
        }

        return view('admin/chemists', $this->withBrand([
            'title' => 'Chemists Directory',
            'chemists' => $chemists,
            'editingChemist' => $editingChemist,
            'canApprove' => in_array($role, ['owner', 'manager'], true),
            'allowedTerritories' => $allowedTerritories,
        ]));
    }

    public function createChemist()
    {
        $role = $this->role();
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->to('/phadmin/chemists')->with('error', 'Chemist name is required.');
        }

        $territoryId = (int) $this->request->getPost('territory_id');
        $accessibleTerritoryIds = $this->getAccessibleTerritoryIds($this->userId(), $role);
        if ($accessibleTerritoryIds !== null) {
            if ($territoryId <= 0 || ! in_array($territoryId, $accessibleTerritoryIds, true)) {
                return redirect()->to('/phadmin/chemists')->with('error', 'Please select a valid territory from your assigned area.');
            }
        }

        $tRecord = $this->territoryModel->find($territoryId);
        $areaName = $tRecord ? $tRecord['name'] : '';

        $this->chemistModel->insert([
            'name' => $name,
            'contact_person' => trim((string) $this->request->getPost('contact_person')),
            'area' => $areaName,
            'territory_id' => $territoryId ?: null,
            'contact' => trim((string) $this->request->getPost('contact')),
            'created_by_user_id' => $this->userId(),
            'approval_status' => $role === 'owner' ? 'approved' : 'pending',
            'approved_by_user_id' => $role === 'owner' ? $this->userId() : null,
        ]);

        return redirect()->to('/phadmin/chemists')->with('success', 'Chemist added successfully.');
    }

    public function updateChemist(int $id)
    {
        $chemist = $this->chemistModel->find($id);
        if (! $chemist) {
            return redirect()->to('/phadmin/chemists')->with('error', 'Chemist not found.');
        }

        $role = $this->role();
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->to('/phadmin/chemists')->with('error', 'Chemist name is required.');
        }

        $territoryId = (int) $this->request->getPost('territory_id');
        $accessibleTerritoryIds = $this->getAccessibleTerritoryIds($this->userId(), $role);
        if ($accessibleTerritoryIds !== null) {
            if ($territoryId <= 0 || ! in_array($territoryId, $accessibleTerritoryIds, true)) {
                return redirect()->to('/phadmin/chemists?edit=' . $id)->with('error', 'Please select a valid territory from your assigned area.');
            }
        }

        $tRecord = $this->territoryModel->find($territoryId);
        $areaName = $tRecord ? $tRecord['name'] : '';

        $this->chemistModel->update($id, [
            'name' => $name,
            'contact_person' => trim((string) $this->request->getPost('contact_person')),
            'area' => $areaName,
            'territory_id' => $territoryId ?: null,
            'contact' => trim((string) $this->request->getPost('contact')),
            'approval_status' => $this->role() === 'owner' ? 'approved' : 'pending',
            'approved_by_user_id' => $this->role() === 'owner' ? $this->userId() : null,
        ]);

        return redirect()->to('/phadmin/chemists')->with('success', 'Chemist updated successfully.');
    }

    public function deleteChemist(int $id)
    {
        $chemist = $this->chemistModel->find($id);
        if (! $chemist) {
            return redirect()->to('/phadmin/chemists')->with('error', 'Chemist not found.');
        }

        $this->chemistModel->delete($id);
        return redirect()->to('/phadmin/chemists')->with('success', 'Chemist deleted successfully.');
    }

    public function approveChemist(int $id)
    {
        $chemist = $this->chemistModel->find($id);
        if (! $chemist) {
            return redirect()->to('/phadmin/chemists')->with('error', 'Chemist not found.');
        }

        if (! $this->canApproveUser((int) ($chemist['created_by_user_id'] ?? 0))) {
            return $this->deny('You do not have permission to approve this chemist record.');
        }

        $status = trim((string) $this->request->getPost('approval_status'));
        if (! in_array($status, ['approved', 'rejected'], true)) {
            return redirect()->to('/phadmin/chemists')->with('error', 'Invalid approval status.');
        }

        $this->chemistModel->update($id, [
            'approval_status' => $status,
            'approved_by_user_id' => $this->userId(),
        ]);

        return redirect()->to('/phadmin/chemists')->with('success', 'Chemist approval updated.');
    }

    public function plans(): string
    {
        $role   = $this->role();
        $userId = $this->userId();
        $month  = trim((string) $this->request->getGet('month'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $employeeId = (int) $this->request->getGet('employee_id');
        $targetUserId = $userId;
        if ($role === 'mr' || $employeeId <= 0) {
            $targetUserId = $userId;
        } else {
            $candidate = $this->userModel->find($employeeId);
            if ($candidate) {
                if ($role === 'owner') {
                    $targetUserId = (int) $candidate['id'];
                } elseif ($role === 'manager') {
                    $isSelf = (int) $candidate['id'] === $userId;
                    $isOwnMr = strtolower((string) ($candidate['role'] ?? '')) === 'mr'
                        && (int) ($candidate['manager_user_id'] ?? 0) === $userId;
                    if ($isSelf || $isOwnMr) {
                        $targetUserId = (int) $candidate['id'];
                    }
                }
            }
        }

        $monthStart  = \DateTime::createFromFormat('Y-m-d', $month . '-01') ?: new \DateTime(date('Y-m-01'));
        $daysInMonth = (int) $monthStart->format('t');

        /* ── Target rows for the grid ── */
        $ownRows     = $this->fieldPlanModel
            ->where('plan_month', $month)
            ->where('created_by_user_id', $targetUserId)
            ->orderBy('plan_date', 'ASC')
            ->findAll();

        $existingRows  = [];
        $rowStatuses   = [];
        $approvalNote  = '';

        foreach ($ownRows as $p) {
            $day = (int) substr((string) ($p['plan_date'] ?? ''), 8, 2);
            if ($day > 0 && $day <= $daysInMonth) {
                if (! isset($existingRows[$day])) {
                    $existingRows[$day] = [
                        'activity_type'   => $p['activity_type']    ?? 'tp',
                        'area_name'       => $p['route_name']       ?? '',
                        'joint_call_with' => $p['joint_call_with']  ?? '',
                        'objective'       => $p['objective']        ?? '',
                        'doctor_ids'      => [],
                    ];
                }
                if ($p['doctor_id']) {
                    $existingRows[$day]['doctor_ids'][] = (int) $p['doctor_id'];
                }
                $rowStatuses[] = strtolower((string) ($p['status'] ?? 'planned'));
                if (! empty($p['approval_note'])) {
                    $approvalNote = $p['approval_note'];
                }
            }
        }

        /* Derive overall month status from individual row statuses */
        if (in_array('approved', $rowStatuses, true)) {
            $planStatus = 'approved';
        } elseif (in_array('submitted', $rowStatuses, true)) {
            $planStatus = 'submitted';
        } elseif (in_array('rejected', $rowStatuses, true)) {
            $planStatus = 'rejected';
        } elseif (! empty($rowStatuses)) {
            $planStatus = 'planned';
        } else {
            $planStatus = '';
        }

        /* MR/manager/owner can edit only when not submitted or approved and they own the plan */
        $isOwnGrid = $targetUserId === $userId;
        $canEdit = $isOwnGrid && ! in_array($planStatus, ['submitted', 'approved'], true);

        /* ── Pending team submissions for manager / owner ── */
        $pendingTeamPlans = [];
        if (in_array($role, ['owner', 'manager'], true)) {
            $teamQuery = $this->fieldPlanModel
                ->select('field_plans.created_by_user_id, field_plans.plan_month,
                          users.name as user_name, users.role as user_role,
                          COUNT(field_plans.id) as row_count')
                ->join('users', 'users.id = field_plans.created_by_user_id', 'left')
                ->where('field_plans.status', 'submitted')
                ->where('field_plans.plan_month', $month);

            if ($role === 'manager') {
                $teamQuery = $teamQuery
                    ->where('users.role', 'mr')
                    ->where('users.manager_user_id', $userId);
            }

            $pendingTeamPlans = $teamQuery
                ->groupBy('field_plans.created_by_user_id, field_plans.plan_month')
                ->findAll();
        }

        $employeeQuery = $this->userModel->where('is_active', 1);
        if ($role === 'manager') {
            $employeeQuery = $employeeQuery->groupStart()
                ->where('id', $userId)
                ->orWhere('manager_user_id', $userId)
            ->groupEnd();
        } elseif ($role === 'mr') {
            $employeeQuery = $employeeQuery->where('id', $userId);
        }
        $employees = $employeeQuery->orderBy('name', 'ASC')->findAll();

        $targetUser = $this->userModel->find($targetUserId);
        $targetRole = strtolower(trim((string)($targetUser['role'] ?? '')));

        if ($targetRole === 'owner' || $targetRole === 'admin') {
            $assignedTerritories = $this->territoryModel
                ->orderBy('name', 'ASC')
                ->findAll();
        } else {
            $directHqIds = array_column(
                $this->userTerritoryModel
                    ->select('territory_id')
                    ->where('user_id', $targetUserId)
                    ->findAll(),
                'territory_id'
            );

            if (empty($directHqIds)) {
                $assignedTerritories = [];
            } else {
                $assignedTerritories = $this->territoryModel
                    ->groupStart()
                        ->whereIn('id', $directHqIds)
                        ->orWhereIn('parent_id', $directHqIds)
                    ->groupEnd()
                    ->orderBy('name', 'ASC')
                    ->findAll();
            }
        }

        $accessibleTerritoryIds = $this->getAccessibleTerritoryIds($targetUserId, $targetRole);
        $doctorQuery = $this->doctorModel
            ->select('doctors.*, COALESCE(territories.name, doctors.area_patch) as area_patch')
            ->join('territories', 'territories.id = doctors.territory_id', 'left');
        if ($accessibleTerritoryIds !== null) {
            $doctorQuery = $doctorQuery->whereIn('doctors.territory_id', $accessibleTerritoryIds);
        }
        $targetDoctors = $doctorQuery->orderBy('doctors.name', 'ASC')->findAll();

        return view('admin/plans', $this->withBrand([
            'title'            => 'Tour Plan',
            'selectedMonth'    => $month,
            'existingPlan'     => ['status' => $planStatus, 'approval_note' => $approvalNote],
            'existingRows'     => $existingRows,
            'canEdit'          => $canEdit,
            'planStatus'       => $planStatus,
            'pendingTeamPlans' => $pendingTeamPlans,
            'doctors'          => $targetDoctors,
            'products'         => $this->productModel->orderBy('name', 'ASC')->findAll(),
            'employees'        => $employees,
            'currentUserId'    => $userId,
            'targetUserId'     => $targetUserId,
            'assignedTerritories' => $assignedTerritories,
        ]));
    }

    public function approveMonthPlan()
    {
        if (! in_array($this->role(), ['owner', 'manager'], true)) {
            return $this->deny('Only Super Admin or manager can approve tour plans.');
        }

        $targetUserId = (int) $this->request->getPost('target_user_id');
        $month        = trim((string) $this->request->getPost('plan_month'));

        if ($targetUserId <= 0 || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return redirect()->to('/phadmin/plans?month=' . urlencode($month ?: date('Y-m')))
                ->with('error', 'Invalid request.');
        }

        if (! $this->canApproveUser($targetUserId)) {
            return $this->deny('You do not have permission to approve this plan.');
        }

        $this->fieldPlanModel
            ->where('plan_month', $month)
            ->where('created_by_user_id', $targetUserId)
            ->where('status', 'submitted')
            ->set(['status' => 'approved', 'is_locked' => 1,
                   'approval_note' => trim((string) $this->request->getPost('approval_note'))])
            ->update();

        return redirect()->to('/phadmin/plans?month=' . urlencode($month))
            ->with('success', 'Tour plan approved and locked.');
    }

    public function rejectMonthPlan()
    {
        if (! in_array($this->role(), ['owner', 'manager'], true)) {
            return $this->deny('Only Super Admin or manager can reject tour plans.');
        }

        $targetUserId    = (int) $this->request->getPost('target_user_id');
        $month           = trim((string) $this->request->getPost('plan_month'));
        $rejectionReason = trim((string) $this->request->getPost('rejection_reason'));

        if ($targetUserId <= 0 || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return redirect()->to('/phadmin/plans?month=' . urlencode($month ?: date('Y-m')))
                ->with('error', 'Invalid request.');
        }

        if ($rejectionReason === '') {
            return redirect()->to('/phadmin/plans?month=' . urlencode($month))
                ->with('error', 'Rejection reason is required.');
        }

        if (! $this->canApproveUser($targetUserId)) {
            return $this->deny('You do not have permission to reject this plan.');
        }

        $this->fieldPlanModel
            ->where('plan_month', $month)
            ->where('created_by_user_id', $targetUserId)
            ->where('status', 'submitted')
            ->set(['status' => 'rejected', 'approval_note' => $rejectionReason])
            ->update();

        return redirect()->to('/phadmin/plans?month=' . urlencode($month))
            ->with('success', 'Tour plan rejected. MR has been notified.');
    }

    public function savePlanDraft()
    {
        if (! $this->request->isAJAX() && strtolower((string) $this->request->getHeaderLine('X-Requested-With')) !== 'xmlhttprequest') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'message' => 'Bad request.']);
        }

        $month = trim((string) $this->request->getPost('plan_month'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Invalid month.']);
        }

        $existing = $this->fieldPlanModel
            ->where('plan_month', $month)
            ->where('created_by_user_id', $this->userId())
            ->whereIn('status', ['submitted', 'approved'])
            ->countAllResults();

        if ($existing > 0) {
            return $this->response->setStatusCode(409)->setJSON(['ok' => false, 'message' => 'Month already submitted.']);
        }

        $this->upsertMonthPlanRows($month, 'planned');

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Draft saved.',
            'saved_at' => date('H:i'),
        ]);
    }

    public function createMonthPlan()
    {
        $month = trim((string) $this->request->getPost('plan_month'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return redirect()->to('/phadmin/plans')->with('error', 'Invalid month.');
        }

        $existing = $this->fieldPlanModel
            ->where('plan_month', $month)
            ->where('created_by_user_id', $this->userId())
            ->whereIn('status', ['submitted', 'approved'])
            ->countAllResults();

        if ($existing > 0) {
            return redirect()->to('/phadmin/plans?month=' . urlencode($month))
                ->with('error', 'This month has already been submitted.');
        }

        $this->upsertMonthPlanRows($month, 'submitted');

        return redirect()->to('/phadmin/plans?month=' . urlencode($month))
            ->with('success', 'Tour plan submitted for approval.');
    }

    private function upsertMonthPlanRows(string $month, string $status): void
    {
        $days          = $this->request->getPost('day')          ?: [];
        $activityTypes = $this->request->getPost('activity_type') ?: [];
        $areaNames     = $this->request->getPost('area_name')    ?: [];
        $jointCalls    = $this->request->getPost('joint_call_with') ?: [];
        $objectives    = $this->request->getPost('objective')    ?: [];
        $doctorIdsJson = $this->request->getPost('doctor_ids')   ?: [];

        $userId = $this->userId();

        $this->fieldPlanModel
            ->where('plan_month', $month)
            ->where('created_by_user_id', $userId)
            ->whereNotIn('status', ['submitted', 'approved'])
            ->delete();

        if (! is_array($days)) {
            return;
        }

        foreach ($days as $idx => $day) {
            $dayNo = (int) $day;
            if ($dayNo <= 0) {
                continue;
            }

            $actType = strtolower(trim((string) ($activityTypes[$idx] ?? 'hq')));
            if (in_array($actType, ['holiday', 'leave'], true)) {
                continue;
            }

            $planDate  = $month . '-' . str_pad((string) $dayNo, 2, '0', STR_PAD_LEFT);
            $areaName  = trim((string) ($areaNames[$idx] ?? ''));
            $jointCall = trim((string) ($jointCalls[$idx] ?? ''));
            $objective = trim((string) ($objectives[$idx] ?? ''));

            $raw = $doctorIdsJson[$idx] ?? '';
            $doctorIds = [];
            if ($raw !== '' && $raw !== '[]') {
                $decoded = json_decode((string) $raw, true);
                if (is_array($decoded)) {
                    $doctorIds = array_values(array_filter(array_map('intval', $decoded)));
                }
            }

            $tId = null;
            if ($areaName !== '') {
                $tRecord = $this->territoryModel->where('name', $areaName)->first();
                if ($tRecord) {
                    $tId = (int)$tRecord['id'];
                }
            }

            $baseData = [
                'plan_date'          => $planDate,
                'plan_month'         => $month,
                'day_of_month'       => $dayNo,
                'activity_type'      => $actType,
                'joint_call_with'    => $jointCall !== '' ? $jointCall : null,
                'objective'          => $objective !== '' ? $objective : null,
                'route_name'         => $areaName !== '' ? $areaName : null,
                'territory_id'       => $tId,
                'status'             => $status,
                'created_by_user_id' => $userId,
                'assigned_user_id'   => $userId,
            ];

            if (empty($doctorIds)) {
                $baseData['doctor_id'] = null;
                $this->fieldPlanModel->insert($baseData);
            } else {
                foreach ($doctorIds as $doctorId) {
                    $data = $baseData;
                    $data['doctor_id'] = $doctorId;
                    $this->fieldPlanModel->insert($data);
                }
            }
        }
    }

    public function getPlannedDoctors()
    {
        $date = trim((string) $this->request->getGet('date'));
        if ($date === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'message' => 'Invalid date format.']);
        }

        $userId = $this->userId();

        $plans = $this->fieldPlanModel
            ->select('field_plans.doctor_id, doctors.name as doctor_name, doctors.area_patch as area_patch')
            ->join('doctors', 'doctors.id = field_plans.doctor_id', 'left')
            ->where('field_plans.plan_date', $date)
            ->groupStart()
                ->where('field_plans.assigned_user_id', $userId)
                ->orWhere('field_plans.created_by_user_id', $userId)
            ->groupEnd()
            ->findAll();

        $doctors = [];
        $seen = [];
        $plannedAreas = [];
        
        foreach ($plans as $p) {
            $docId = (int) ($p['doctor_id'] ?? 0);
            if ($docId > 0 && ! isset($seen[$docId])) {
                $seen[$docId] = true;
                $doctors[] = [
                    'id' => $docId,
                    'name' => (string) ($p['doctor_name'] ?? ('Doctor #' . $docId)),
                    'area_patch' => (string) ($p['area_patch'] ?? ''),
                ];
            }
        }
        
        // Also get the planned areas for this date from field_plans
        $planRecord = $this->fieldPlanModel
            ->where('plan_date', $date)
            ->groupStart()
                ->where('assigned_user_id', $userId)
                ->orWhere('created_by_user_id', $userId)
            ->groupEnd()
            ->first();
            
        if ($planRecord && !empty($planRecord['route_name'])) {
            $plannedAreas = array_map('trim', explode(',', $planRecord['route_name']));
        }

        return $this->response->setJSON([
            'ok' => true,
            'doctors' => $doctors,
            'planned_areas' => $plannedAreas,
        ]);
    }

    public function createPlan()
    {
        $role = $this->role();
        $planDate = trim((string) $this->request->getPost('plan_date'));
        if ($planDate === '') {
            return redirect()->to('/phadmin/plans')->with('error', 'Plan date is required.');
        }
        $planMonth = substr($planDate, 0, 7);

        if ($role === 'mr') {
            $monthLocked = $this->fieldPlanModel
                ->where('plan_month', $planMonth)
                ->where('created_by_user_id', $this->userId())
                ->where('is_locked', 1)
                ->countAllResults() > 0;
            if ($monthLocked) {
                return redirect()->to('/phadmin/plans?month=' . $planMonth)->with('error', 'Tour plan month is locked and cannot be edited.');
            }
        }

        $activityType = trim((string) $this->request->getPost('activity_type'));
        $allowedTypes = ['tp', 'pob', 'joint_call', 'follow_up'];
        if (! in_array($activityType, $allowedTypes, true)) {
            $activityType = 'tp';
        }

        $assignedUserId = (int) $this->request->getPost('assigned_user_id');
        if ($role === 'mr') {
            $assignedUserId = $this->userId();
        }

        if ($assignedUserId <= 0) {
            $assignedUserId = $this->userId();
        }

        if ($role === 'manager' && $assignedUserId !== $this->userId()) {
            $targetUser = $this->userModel->find($assignedUserId);
            if (! $targetUser || strtolower((string) ($targetUser['role'] ?? '')) !== 'mr' || (int) ($targetUser['manager_user_id'] ?? 0) !== $this->userId()) {
                return redirect()->to('/phadmin/plans')->with('error', 'Manager can assign plans only to own team MRs.');
            }
        }

        $this->fieldPlanModel->insert([
            'plan_date' => $planDate,
            'plan_month' => $planMonth,
            'day_of_month' => (int) substr($planDate, 8, 2),
            'activity_type' => $activityType,
            'doctor_id' => (int) $this->request->getPost('doctor_id') ?: null,
            'product_id' => (int) $this->request->getPost('product_id') ?: null,
            'chemist_id' => (int) $this->request->getPost('chemist_id') ?: null,
            'objective' => trim((string) $this->request->getPost('objective')),
            'pob_amount' => trim((string) $this->request->getPost('pob_amount')) !== '' ? (float) $this->request->getPost('pob_amount') : null,
            'joint_call_with' => trim((string) $this->request->getPost('joint_call_with')),
            'route_name' => trim((string) $this->request->getPost('route_name')),
            'status' => 'planned',
            'created_by_user_id' => $this->userId(),
            'assigned_user_id' => $assignedUserId,
            'approval_note' => '',
            'is_locked' => 0,
        ]);

        return redirect()->to('/phadmin/plans?month=' . $planMonth)->with('success', 'Field plan added successfully.');
    }

    public function updatePlanStatus(int $id)
    {
        $plan = $this->fieldPlanModel->find($id);
        if (! $plan) {
            return redirect()->to('/phadmin/plans')->with('error', 'Plan not found.');
        }

        $role = $this->role();
        $createdByUser = $this->userModel->find((int) ($plan['created_by_user_id'] ?? 0));
        $creatorRole = strtolower((string) ($createdByUser['role'] ?? 'mr'));

        $status = trim((string) $this->request->getPost('status'));
        $allowedStatus = ['planned', 'submitted', 'approved', 'rejected', 'completed'];
        if (! in_array($status, $allowedStatus, true)) {
            return redirect()->to('/phadmin/plans')->with('error', 'Invalid status.');
        }

        if ($role === 'mr' && ((int) ($plan['created_by_user_id'] ?? 0) === $this->userId()) && (int) ($plan['is_locked'] ?? 0) === 1) {
            return redirect()->to('/phadmin/plans')->with('error', 'Locked plan cannot be changed.');
        }

        if ($status === 'approved' || $status === 'rejected') {
            if ($role === 'mr') {
                return redirect()->to('/phadmin/plans')->with('error', 'MR cannot approve or reject plans.');
            }

            if ($role === 'manager') {
                if ((int) ($plan['created_by_user_id'] ?? 0) === $this->userId()) {
                    return redirect()->to('/phadmin/plans')->with('error', 'Manager cannot self-approve own plan.');
                }

                if ($creatorRole !== 'mr') {
                    return redirect()->to('/phadmin/plans')->with('error', 'Manager can approve only MR plans.');
                }
            }
        }

        $this->fieldPlanModel->update($id, [
            'status' => $status,
            'approval_note' => trim((string) $this->request->getPost('approval_note')),
            'is_locked' => $status === 'approved' ? 1 : (int) ($plan['is_locked'] ?? 0),
        ]);

        return redirect()->to('/phadmin/plans')->with('success', 'Plan status updated successfully.');
    }

    public function submitPlanMonth()
    {
        if ($this->role() !== 'mr') {
            return $this->deny('Only MR can submit monthly tour plan.');
        }

        $month = trim((string) $this->request->getPost('month'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $locked = $this->fieldPlanModel
            ->where('plan_month', $month)
            ->where('created_by_user_id', $this->userId())
            ->where('is_locked', 1)
            ->countAllResults() > 0;
        if ($locked) {
            return redirect()->to('/phadmin/plans?month=' . $month)->with('error', 'This month is already locked.');
        }

        $rows = $this->fieldPlanModel
            ->where('plan_month', $month)
            ->where('created_by_user_id', $this->userId())
            ->whereIn('status', ['planned', 'rejected'])
            ->findAll();

        if ($rows === []) {
            return redirect()->to('/phadmin/plans?month=' . $month)->with('error', 'No editable plans found for selected month.');
        }

        foreach ($rows as $row) {
            $this->fieldPlanModel->update((int) $row['id'], [
                'status' => 'submitted',
                'approval_note' => trim((string) ($row['approval_note'] ?? '')),
            ]);
        }

        return redirect()->to('/phadmin/plans?month=' . $month)->with('success', 'Monthly tour plan submitted for approval.');
    }

    public function lockPlanMonth()
    {
        $role = $this->role();
        if (! in_array($role, ['owner', 'manager'], true)) {
            return $this->deny('Only Super Admin/manager can lock plan month.');
        }

        $month = trim((string) $this->request->getPost('month'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $targetUserId = (int) $this->request->getPost('target_user_id');
        if ($targetUserId <= 0) {
            return redirect()->to('/phadmin/plans?month=' . $month)->with('error', 'Target MR is required.');
        }

        $targetUser = $this->userModel->find($targetUserId);
        if (! $targetUser || strtolower((string) ($targetUser['role'] ?? '')) !== 'mr') {
            return redirect()->to('/phadmin/plans?month=' . $month)->with('error', 'Month lock can be applied only for MR users.');
        }

        if ($role === 'manager' && (int) ($targetUser['manager_user_id'] ?? 0) !== $this->userId()) {
            return redirect()->to('/phadmin/plans?month=' . $month)->with('error', 'Manager can lock month only for own team MR.');
        }

        $rows = $this->fieldPlanModel
            ->where('plan_month', $month)
            ->where('created_by_user_id', $targetUserId)
            ->findAll();

        if ($rows === []) {
            return redirect()->to('/phadmin/plans?month=' . $month)->with('error', 'No plan rows found for selected month/user.');
        }

        foreach ($rows as $row) {
            $nextStatus = (string) ($row['status'] ?? 'planned');
            if ($nextStatus === 'submitted') {
                $nextStatus = 'approved';
            }

            $this->fieldPlanModel->update((int) $row['id'], [
                'status' => $nextStatus,
                'is_locked' => 1,
                'approval_note' => trim((string) $this->request->getPost('approval_note')),
            ]);
        }

        return redirect()->to('/phadmin/plans?month=' . $month)->with('success', 'Monthly plan locked successfully.');
    }

    public function reports(): string
    {
        $role = $this->role();
        $userId = $this->userId();

        $employeeFilter = (int) $this->request->getGet('employee_id');
        $statusFilter = trim((string) $this->request->getGet('status'));
        $dateFrom = trim((string) $this->request->getGet('date_from'));
        $dateTo = trim((string) $this->request->getGet('date_to'));

        if ($dateFrom === '') {
            $dateFrom = date('Y-m-01');
        }
        if ($dateTo === '') {
            $dateTo = date('Y-m-d');
        }

        $baseQuery = $this->visitModel
            ->select('visits.*, users.name as employee_name, users.role as employee_role, users.area as employee_area, doctors.name as doctor_name, doctors.area_patch as doctor_area, chemists.name as chemist_name, chemists.area as chemist_area')
            ->join('users', 'users.id = visits.created_by_user_id', 'left')
            ->join('doctors', 'doctors.id = visits.doctor_id', 'left')
            ->join('chemists', 'chemists.id = visits.chemist_id', 'left')
            ->where('DATE(visits.visit_date) >=', $dateFrom)
            ->where('DATE(visits.visit_date) <=', $dateTo)
            ->where('visits.e_detailing_session_id IS NULL');

        if ($role === 'manager') {
            $baseQuery = $baseQuery->groupStart()
                ->where('users.id', $userId)
                ->orWhere('users.manager_user_id', $userId)
            ->groupEnd();
        } elseif ($role === 'mr') {
            $baseQuery = $baseQuery->where('visits.created_by_user_id', $userId);
        }

        if ($employeeFilter > 0) {
            $baseQuery = $baseQuery->where('visits.created_by_user_id', $employeeFilter);
        }

        $rows = $baseQuery->orderBy('visits.visit_date', 'DESC')->findAll(1000);

        // Compute summary metrics
        $totalDoctorCalls = 0;
        $totalChemistCalls = 0;
        $totalPob = 0.0;
        $uniqueDates = [];

        foreach ($rows as $row) {
            $date = substr((string) ($row['visit_date'] ?? ''), 0, 10);
            if ($date !== '') {
                $uniqueDates[$date] = true;
            }

            if (! empty($row['doctor_id'])) {
                $totalDoctorCalls++;
            }
            if (! empty($row['chemist_id'])) {
                $totalChemistCalls++;
            }
            $totalPob += (float) ($row['pob_value'] ?? 0.0);
        }

        $uniqueDaysCount = count($uniqueDates);
        $avgDoctorCalls = $uniqueDaysCount > 0 ? ($totalDoctorCalls / $uniqueDaysCount) : 0.0;
        $avgChemistCalls = $uniqueDaysCount > 0 ? ($totalChemistCalls / $uniqueDaysCount) : 0.0;
        $avgPob = $uniqueDaysCount > 0 ? ($totalPob / $uniqueDaysCount) : 0.0;

        $summaryBox = [
            'totalDoctorCalls' => $totalDoctorCalls,
            'totalChemistCalls' => $totalChemistCalls,
            'totalPob' => $totalPob,
            'avgDoctorCalls' => $avgDoctorCalls,
            'avgChemistCalls' => $avgChemistCalls,
            'avgPob' => $avgPob,
            'totalCalls' => $totalDoctorCalls + $totalChemistCalls,
        ];

        // Group rows date-wise & employee-wise
        $grouped = [];
        foreach ($rows as $row) {
            $date = substr((string) ($row['visit_date'] ?? ''), 0, 10);
            $employeeId = (int) ($row['created_by_user_id'] ?? 0);
            $groupKey = $date . '|' . $employeeId;

            if (! isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'date' => $date,
                    'employee_id' => $employeeId,
                    'employee_name' => (string) ($row['employee_name'] ?? '-'),
                    'employee_role' => (string) ($row['employee_role'] ?? '-'),
                    'work_with' => (string) ($row['joint_call_with'] ?? 'Alone'),
                    'area' => (string) ($row['employee_area'] ?? '-'),
                    'status' => 'submitted',
                    'doctors' => [],
                    'chemists' => [],
                ];
            }

            if (strtolower((string) ($row['report_status'] ?? '')) === 'locked') {
                $grouped[$groupKey]['status'] = 'locked';
            }

            if (! empty($row['doctor_id'])) {
                $time = substr((string) ($row['visit_date'] ?? ''), 11, 5);
                if ($time === '') {
                    $time = '-';
                }
                $grouped[$groupKey]['doctors'][] = [
                    'name' => (string) $row['doctor_name'],
                    'area' => (string) ($row['doctor_area'] ?? '-'),
                    'time' => $time,
                    'remarks' => (string) ($row['feedback'] ?? ''),
                ];
            }

            if (! empty($row['chemist_id'])) {
                $grouped[$groupKey]['chemists'][] = [
                    'name' => (string) $row['chemist_name'],
                    'area' => (string) ($row['chemist_area'] ?? '-'),
                ];
            }
        }

        $dcrGroups = [];
        foreach ($grouped as $groupRow) {
            if ($statusFilter !== '' && strtolower($groupRow['status']) !== strtolower($statusFilter)) {
                continue;
            }
            $dcrGroups[] = $groupRow;
        }

        $employeesQuery = $this->userModel->where('is_active', 1);
        if ($role === 'manager') {
            $employeesQuery = $employeesQuery->groupStart()
                ->where('id', $userId)
                ->orWhere('manager_user_id', $userId)
            ->groupEnd();
        } elseif ($role === 'mr') {
            $employeesQuery = $employeesQuery->where('id', $userId);
        }

        $accessibleTerritoryIds = $this->getAccessibleTerritoryIds($userId, $role);

        $doctorQuery = $this->doctorModel
            ->select('doctors.*, COALESCE(territories.name, doctors.area_patch) as area_patch')
            ->join('territories', 'territories.id = doctors.territory_id', 'left')
            ->where('doctors.approval_status', 'approved');
        if ($accessibleTerritoryIds !== null) {
            $doctorQuery = $doctorQuery->whereIn('doctors.territory_id', $accessibleTerritoryIds);
        }
        $targetDoctors = $doctorQuery->orderBy('doctors.name', 'ASC')->findAll();

        $chemistQuery = $this->chemistModel
            ->select('chemists.*, COALESCE(territories.name, chemists.area) as area')
            ->join('territories', 'territories.id = chemists.territory_id', 'left')
            ->where('chemists.approval_status', 'approved');
        if ($accessibleTerritoryIds !== null) {
            $chemistQuery = $chemistQuery->whereIn('chemists.territory_id', $accessibleTerritoryIds);
        }
        $targetChemists = $chemistQuery->orderBy('chemists.name', 'ASC')->findAll();

        return view('admin/reports', $this->withBrand([
            'title' => 'Reporting',
            'dcrGroups' => $dcrGroups,
            'employees' => $employeesQuery->orderBy('name', 'ASC')->findAll(),
            'selectedEmployeeId' => $employeeFilter,
            'selectedStatus' => $statusFilter,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'summaryBox' => $summaryBox,
            'doctors' => $targetDoctors,
            'chemists' => $targetChemists,
            'users' => $this->userModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll(),
        ]));
    }

    public function submitReport()
    {
        $date = trim((string) $this->request->getPost('report_date'));
        if ($date === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $month = substr($date, 0, 7);

        $workWith = trim((string) $this->request->getPost('work_with'));
        if ($workWith === '') {
            $workWith = 'Alone';
        }

        $areaPatchInput = $this->request->getPost('area_patch');
        if (is_array($areaPatchInput)) {
            $areaPatch = implode(', ', array_filter(array_map('trim', $areaPatchInput)));
        } else {
            $areaPatch = trim((string) $areaPatchInput);
        }

        $doctorIds = $this->request->getPost('doctor_ids');
        $doctorIds = is_array($doctorIds) ? array_values(array_filter(array_map('intval', $doctorIds))) : [];

        if ($doctorIds === []) {
            return redirect()->to('/phadmin/reports')->with('error', 'Select at least one doctor visit.');
        }

        $firstProduct = $this->productModel->orderBy('id', 'ASC')->first();
        if (! $firstProduct) {
            $this->productModel->insert([
                'name' => 'PEDIVION DROPS 15 ML',
                'description' => 'Default product (auto-created)',
            ]);
            $firstProduct = $this->productModel->orderBy('id', 'ASC')->first();
        }
        $productId = $firstProduct ? (int) $firstProduct['id'] : 1;

        foreach ($doctorIds as $doctorId) {
            $visitTime = trim((string) $this->request->getPost('visit_time_' . $doctorId));
            if ($visitTime === '') {
                return redirect()->to('/phadmin/reports')->with('error', 'Visit time is required for all selected doctors.');
            }

            $feedback = trim((string) $this->request->getPost('remarks_' . $doctorId));
            $chemistId = (int) $this->request->getPost('chemist_id_' . $doctorId);
            $pobValue = (float) $this->request->getPost('pob_value_' . $doctorId);

            $visitDateTime = $date . ' ' . $visitTime . ':00';

            // Find matching field plan row for planned_plan_id and is_deviation
            $planned = $this->fieldPlanModel
                ->where('plan_date', $date)
                ->where('doctor_id', $doctorId)
                ->where('assigned_user_id', $this->userId())
                ->first();

            $plannedPlanId = $planned ? (int) $planned['id'] : null;
            $isDeviation = $planned ? 0 : 1;
            $deviationReason = $planned ? null : 'Unplanned doctor visit.';

            $this->visitModel->insert([
                'doctor_id' => $doctorId,
                'product_id' => $productId,
                'chemist_id' => $chemistId > 0 ? $chemistId : null,
                'planned_plan_id' => $plannedPlanId,
                'visit_date' => $visitDateTime,
                'dcr_date' => $date,
                'dcr_month' => $month,
                'duration_minutes' => 15,
                'pob_value' => $pobValue > 0 ? $pobValue : 0.0,
                'sample_units' => 0,
                'action_type' => 'call',
                'is_deviation' => $isDeviation,
                'deviation_reason' => $deviationReason,
                'created_by_user_id' => $this->userId(),
                'feedback' => $feedback,
                'next_objective' => $areaPatch,
                'joint_call_with' => $workWith,
                'report_status' => 'submitted',
            ]);
        }

        return redirect()->to('/phadmin/reports')->with('success', 'Daily call report submitted successfully.');
    }

    public function toggleReportLock()
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Founder / Super Admin can lock/unlock reports.');
        }

        $date = trim((string) $this->request->getPost('date'));
        $employeeId = (int) $this->request->getPost('employee_id');
        $action = trim((string) $this->request->getPost('action'));

        if ($date === '' || $employeeId <= 0 || ! in_array($action, ['lock', 'unlock'], true)) {
            return redirect()->to('/phadmin/reports')->with('error', 'Invalid request parameters.');
        }

        $status = $action === 'lock' ? 'locked' : 'submitted';

        $this->visitModel
            ->where('dcr_date', $date)
            ->where('created_by_user_id', $employeeId)
            ->set(['report_status' => $status])
            ->update();

        return redirect()->to('/phadmin/reports?date_from=' . urlencode($date) . '&date_to=' . urlencode($date))
            ->with('success', 'Report status updated successfully.');
    }

    public function exportReports()
    {
        if (! session()->get('is_logged_in')) {
            return redirect()->to('/phadmin/login')->with('error', 'Please login first.');
        }

        $employeeFilter = (int) $this->request->getGet('employee_id');
        $dateFrom = trim((string) $this->request->getGet('date_from'));
        $dateTo = trim((string) $this->request->getGet('date_to'));

        if ($dateFrom === '') {
            $dateFrom = date('Y-m-01');
        }
        if ($dateTo === '') {
            $dateTo = date('Y-m-d');
        }

        $query = $this->visitModel
            ->select('DATE(visits.visit_date) as report_date, users.name as employee_name, users.role as employee_role, users.area as area_visited, visits.joint_call_with, doctors.name as doctor_name, chemists.name as chemist_name')
            ->join('users', 'users.id = visits.created_by_user_id', 'left')
            ->join('doctors', 'doctors.id = visits.doctor_id', 'left')
            ->join('chemists', 'chemists.id = visits.chemist_id', 'left')
            ->where('DATE(visits.visit_date) >=', $dateFrom)
            ->where('DATE(visits.visit_date) <=', $dateTo)
            ->orderBy('visits.visit_date', 'DESC');

        if ($employeeFilter > 0) {
            $query = $query->where('visits.created_by_user_id', $employeeFilter);
        }

        $rows = $query->findAll(5000);

        $csv = "Date,Employee Name,Role,Work With,Area Visited,Doctor,Chemist\n";
        foreach ($rows as $row) {
            $line = [
                $row['report_date'] ?? '',
                $row['employee_name'] ?? '',
                strtoupper((string) ($row['employee_role'] ?? '')),
                $row['joint_call_with'] ?? 'Alone',
                $row['area_visited'] ?? '',
                $row['doctor_name'] ?? '',
                $row['chemist_name'] ?? '',
            ];
            $csv .= implode(',', array_map(static fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', $line)) . "\n";
        }

        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="reporting_export.csv"')
            ->setBody($csv);
    }

    public function expenses(): string
    {
        $role = $this->role();
        $userId = $this->userId();
        $month = trim((string) $this->request->getGet('month'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $employeeId = (int) $this->request->getGet('employee_id');
        $targetUserId = $userId;
        if ($role === 'mr' || $employeeId <= 0) {
            $targetUserId = $userId;
        } else {
            $candidate = $this->userModel->find($employeeId);
            if ($candidate) {
                if ($role === 'owner') {
                    $targetUserId = (int) $candidate['id'];
                } elseif ($role === 'manager') {
                    $isSelf = (int) $candidate['id'] === $userId;
                    $isOwnMr = strtolower((string) ($candidate['role'] ?? '')) === 'mr'
                        && (int) ($candidate['manager_user_id'] ?? 0) === $userId;
                    if ($isSelf || $isOwnMr) {
                        $targetUserId = (int) $candidate['id'];
                    }
                }
            }
        }

        $existingExpense = $this->expenseModel
            ->where('submitted_by_user_id', $targetUserId)
            ->where('expense_month', $month)
            ->orderBy('id', 'DESC')
            ->first();

        $existingRows = [];
        if ($existingExpense && ! empty($existingExpense['details_json'])) {
            $decoded = json_decode((string) $existingExpense['details_json'], true) ?: [];
            foreach ($decoded as $row) {
                $day = (int) ($row['day'] ?? 0);
                if ($day > 0) {
                    $existingRows[$day] = $row;
                }
            }
        }
        
        // If no expenses are saved yet, auto-populate from the Tour Plan (Field Plan)
        if (empty($existingRows)) {
            $planRows = $this->fieldPlanModel
                ->where('created_by_user_id', $targetUserId)
                ->where('plan_month', $month)
                ->whereIn('status', ['submitted', 'approved', 'completed'])
                ->findAll();
                
            foreach ($planRows as $p) {
                $day = (int) substr((string) ($p['plan_date'] ?? ''), 8, 2);
                if ($day > 0 && $day <= (int) date('t', strtotime($month . '-01'))) {
                    $existingRows[$day] = [
                        'day' => $day,
                        'work_type' => $p['activity_type'] ?? 'hq',
                        'area_name' => $p['route_name'] ?? '',
                        'distance_km' => 0,
                        'fare_amount' => 0,
                        'misc_amount' => 0,
                    ];
                }
            }
        }

        $employees = [];
        if ($role === 'owner' || $role === 'manager') {
            $employeesQuery = $this->userModel->where('is_active', 1);
            if ($role === 'manager') {
                $employeesQuery = $employeesQuery->groupStart()
                    ->where('id', $userId)
                    ->orGroupStart()
                        ->where('manager_user_id', $userId)
                        ->where('role', 'mr')
                    ->groupEnd()
                ->groupEnd();
            }
            $employees = $employeesQuery->orderBy('name', 'ASC')->findAll();
        }

        $targetUser = $this->userModel->find($targetUserId);
        $targetRole = $targetUser ? strtolower((string) ($targetUser['role'] ?? 'mr')) : 'mr';

        $settings = $this->settingModel->getKeyValuePairs();
        $daHq = ($targetUser && !empty($targetUser['da_hq'])) ? (int) $targetUser['da_hq'] : (int) ($settings["da_{$targetRole}_hq"] ?? 250);
        $daEx = ($targetUser && !empty($targetUser['da_ex'])) ? (int) $targetUser['da_ex'] : (int) ($settings["da_{$targetRole}_ex"] ?? 450);
        $daOs = ($targetUser && !empty($targetUser['da_os'])) ? (int) $targetUser['da_os'] : (int) ($settings["da_{$targetRole}_os"] ?? 700);

        $statusValue = strtolower((string) ($existingExpense['status'] ?? ''));
        $isOwnGrid = $targetUserId === $userId;
        $canEdit = $isOwnGrid && ($existingExpense === null || in_array($statusValue, ['draft', 'rejected'], true));
        $canApprove = false;
        if (! $isOwnGrid && $existingExpense && $statusValue === 'submitted') {
            $canApprove = $this->canApproveUser($targetUserId);
        }

        if ($targetRole === 'owner' || $targetRole === 'admin') {
            $assignedTerritories = $this->territoryModel
                ->orderBy('name', 'ASC')
                ->findAll();
        } else {
            $directHqIds = array_column(
                $this->userTerritoryModel
                    ->select('territory_id')
                    ->where('user_id', $targetUserId)
                    ->findAll(),
                'territory_id'
            );

            if (empty($directHqIds)) {
                $assignedTerritories = [];
            } else {
                $assignedTerritories = $this->territoryModel
                    ->groupStart()
                        ->whereIn('id', $directHqIds)
                        ->orWhereIn('parent_id', $directHqIds)
                    ->groupEnd()
                    ->orderBy('name', 'ASC')
                    ->findAll();
            }
        }

        return view('admin/expenses', $this->withBrand([
            'title' => 'Expense',
            'selectedMonth' => $month,
            'targetUserId' => $targetUserId,
            'targetUserName' => (string) ($targetUser['name'] ?? session()->get('user_name')),
            'existingExpense' => $existingExpense,
            'existingRows' => $existingRows,
            'employees' => $employees,
            'canEdit' => $canEdit,
            'canApprove' => $canApprove,
            'assignedTerritories' => $assignedTerritories,
            'daHq' => $daHq,
            'daEx' => $daEx,
            'daOs' => $daOs,
        ]));
    }

    public function createExpense()
    {
        $month = trim((string) $this->request->getPost('expense_month'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $title = trim((string) $this->request->getPost('title'));
        if ($title === '') {
            $title = 'Monthly Expense Sheet';
        }

        $existing = $this->expenseModel
            ->where('submitted_by_user_id', $this->userId())
            ->where('expense_month', $month)
            ->orderBy('id', 'DESC')
            ->first();

        if ($existing && in_array(strtolower((string) ($existing['status'] ?? '')), ['submitted', 'approved'], true)) {
            return redirect()->to('/phadmin/expenses?month=' . urlencode($month))
                ->with('error', 'Expense for this month is already submitted. Wait for approval or rejection before resubmitting.');
        }

        $days = $this->request->getPost('day');
        $dateLabels = $this->request->getPost('date_label');
        $dayNames = $this->request->getPost('day_name');
        $workTypeList = $this->request->getPost('work_type');
        $areaNames = $this->request->getPost('area_name');
        $miscAmounts = $this->request->getPost('misc_amount');
        $fareAmounts = $this->request->getPost('fare_amount');
        $distanceList = $this->request->getPost('distance_km');

        $rowBillFiles = [];
        $uploadedFiles = $this->request->getFiles();
        if (isset($uploadedFiles['row_bill']) && is_array($uploadedFiles['row_bill'])) {
            $rowBillFiles = $uploadedFiles['row_bill'];
        }

        $submitterId = $this->userId();
        $submitter = $this->userModel->find($submitterId);
        $submitterRole = strtolower((string) ($submitter['role'] ?? 'mr'));
        
        $settings = $this->settingModel->getKeyValuePairs();
        $userHq = !empty($submitter['da_hq']) ? (int) $submitter['da_hq'] : (int) ($settings["da_{$submitterRole}_hq"] ?? 250);
        $userEx = !empty($submitter['da_ex']) ? (int) $submitter['da_ex'] : (int) ($settings["da_{$submitterRole}_ex"] ?? 450);
        $userOs = !empty($submitter['da_os']) ? (int) $submitter['da_os'] : (int) ($settings["da_{$submitterRole}_os"] ?? 700);

        $daDisplayList = $this->request->getPost('daily_allowance_display');
        $isOwner = $this->role() === 'owner';

        $rows = [];
        $totalAmount = 0.0;

        if (is_array($days)) {
            foreach ($days as $idx => $day) {
                $dayNo = (int) $day;
                if ($dayNo <= 0) {
                    continue;
                }

                $misc = (float) ($miscAmounts[$idx] ?? 0);
                $fare = (float) ($fareAmounts[$idx] ?? 0);
                $workType = strtolower(trim((string) ($workTypeList[$idx] ?? 'hq')));
                
                if ($isOwner && is_array($daDisplayList) && isset($daDisplayList[$idx]) && $daDisplayList[$idx] !== '') {
                    $da = (float) $daDisplayList[$idx];
                } else {
                    $da = match ($workType) {
                        'ex-hq', 'ex' => $userEx,
                        'outstation', 'os' => $userOs,
                        'leave', 'holiday' => 0,
                        default => $userHq,
                    };
                }

                $rowTotal = $misc + $fare + $da;
                $totalAmount += $rowTotal;

                $rows[] = [
                    'day' => $dayNo,
                    'date_label' => trim((string) ($dateLabels[$idx] ?? '')),
                    'day_name' => trim((string) ($dayNames[$idx] ?? '')),
                    'area_name' => trim((string) ($areaNames[$idx] ?? '')),
                    'work_type' => strtoupper($workType),
                    'work_with' => 'Self/Alone',
                    'allowance_type' => strtoupper($workType),
                    'distance_km' => (float) ($distanceList[$idx] ?? 0),
                    'fare_amount' => $fare,
                    'daily_allowance' => $da,
                    'misc_amount' => $misc,
                    'total_amount' => $rowTotal,
                    'remarks' => '',
                    'bill_path' => '',
                    'status' => 'Draft',
                ];

                $billFile = $rowBillFiles[$idx] ?? null;
                if ($billFile && $billFile->isValid() && ! $billFile->hasMoved()) {
                    $ext = strtolower((string) $billFile->getExtension());
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'], true)) {
                        $targetDir = FCPATH . 'uploads/expenses';
                        if (! is_dir($targetDir)) {
                            mkdir($targetDir, 0775, true);
                        }
                        $newName = $billFile->getRandomName();
                        $billFile->move($targetDir, $newName);
                        $rows[count($rows) - 1]['bill_path'] = 'uploads/expenses/' . $newName;
                    }
                }
            }
        }

        if ($rows === []) {
            $amount = (float) $this->request->getPost('amount');
            if ($amount <= 0) {
                return redirect()->to('/phadmin/expenses')->with('error', 'Please add at least one expense row or amount.');
            }
            $totalAmount = $amount;
        }

        $isFounder = $this->role() === 'owner';
        $rowStatus = $isFounder ? 'Approved' : 'Pending';
        if ($rows !== []) {
            foreach ($rows as $index => $row) {
                $rows[$index]['status'] = $rowStatus;
            }
        }

        $payload = [
            'submitted_by_user_id' => $this->userId(),
            'title' => $title,
            'category' => trim((string) $this->request->getPost('category')) ?: 'Monthly Grid',
            'expense_month' => $month,
            'amount' => $totalAmount,
            'bill_image_path' => '',
            'status' => $isFounder ? 'approved' : 'submitted',
            'approved_by_user_id' => $isFounder ? $this->userId() : null,
            'approval_note' => trim((string) $this->request->getPost('approval_note')),
            'details_json' => $rows !== [] ? json_encode($rows) : null,
            'rejection_reason' => null,
        ];

        if ($existing && in_array(strtolower((string) ($existing['status'] ?? '')), ['draft', 'rejected'], true)) {
            $this->expenseModel->update((int) $existing['id'], $payload);
        } else {
            $this->expenseModel->insert($payload);
        }

        return redirect()->to('/phadmin/expenses?month=' . urlencode($month))
            ->with('success', 'Expense submitted for approval.');
    }

    public function saveDraft()
    {
        if (! $this->request->isAJAX() && strtolower((string) $this->request->getHeaderLine('X-Requested-With')) !== 'xmlhttprequest') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'message' => 'Bad request.']);
        }

        $month = trim((string) $this->request->getPost('expense_month'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Invalid month.']);
        }

        $existing = $this->expenseModel
            ->where('submitted_by_user_id', $this->userId())
            ->where('expense_month', $month)
            ->orderBy('id', 'DESC')
            ->first();

        if ($existing && in_array(strtolower((string) ($existing['status'] ?? '')), ['submitted', 'approved'], true)) {
            return $this->response->setStatusCode(409)->setJSON(['ok' => false, 'message' => 'Already submitted for this month.']);
        }

        $days = $this->request->getPost('day');
        $dateLabels = $this->request->getPost('date_label');
        $dayNames = $this->request->getPost('day_name');
        $workTypeList = $this->request->getPost('work_type');
        $areaNames = $this->request->getPost('area_name');
        $miscAmounts = $this->request->getPost('misc_amount');
        $fareAmounts = $this->request->getPost('fare_amount');
        $distanceList = $this->request->getPost('distance_km');

        $existingBillsByDay = [];
        if ($existing && ! empty($existing['details_json'])) {
            $decoded = json_decode((string) $existing['details_json'], true) ?: [];
            foreach ($decoded as $row) {
                $day = (int) ($row['day'] ?? 0);
                if ($day > 0 && ! empty($row['bill_path'])) {
                    $existingBillsByDay[$day] = (string) $row['bill_path'];
                }
            }
        }

        $submitterId = $this->userId();
        $submitter = $this->userModel->find($submitterId);
        $submitterRole = strtolower((string) ($submitter['role'] ?? 'mr'));
        
        $settings = $this->settingModel->getKeyValuePairs();
        $userHq = !empty($submitter['da_hq']) ? (int) $submitter['da_hq'] : (int) ($settings["da_{$submitterRole}_hq"] ?? 250);
        $userEx = !empty($submitter['da_ex']) ? (int) $submitter['da_ex'] : (int) ($settings["da_{$submitterRole}_ex"] ?? 450);
        $userOs = !empty($submitter['da_os']) ? (int) $submitter['da_os'] : (int) ($settings["da_{$submitterRole}_os"] ?? 700);

        $daDisplayList = $this->request->getPost('daily_allowance_display');
        $isOwner = $this->role() === 'owner';

        $rows = [];
        $totalAmount = 0.0;

        if (is_array($days)) {
            foreach ($days as $idx => $day) {
                $dayNo = (int) $day;
                if ($dayNo <= 0) {
                    continue;
                }

                $misc = (float) ($miscAmounts[$idx] ?? 0);
                $fare = (float) ($fareAmounts[$idx] ?? 0);
                $workType = strtolower(trim((string) ($workTypeList[$idx] ?? 'hq')));
                
                if ($isOwner && is_array($daDisplayList) && isset($daDisplayList[$idx]) && $daDisplayList[$idx] !== '') {
                    $da = (float) $daDisplayList[$idx];
                } else {
                    $da = match ($workType) {
                        'ex-hq', 'ex' => $userEx,
                        'outstation', 'os' => $userOs,
                        'leave', 'holiday' => 0,
                        default => $userHq,
                    };
                }

                $rowTotal = $misc + $fare + $da;
                $totalAmount += $rowTotal;

                $rows[] = [
                    'day' => $dayNo,
                    'date_label' => trim((string) ($dateLabels[$idx] ?? '')),
                    'day_name' => trim((string) ($dayNames[$idx] ?? '')),
                    'area_name' => trim((string) ($areaNames[$idx] ?? '')),
                    'work_type' => strtoupper($workType),
                    'work_with' => 'Self/Alone',
                    'allowance_type' => strtoupper($workType),
                    'distance_km' => (float) ($distanceList[$idx] ?? 0),
                    'fare_amount' => $fare,
                    'daily_allowance' => $da,
                    'misc_amount' => $misc,
                    'total_amount' => $rowTotal,
                    'remarks' => '',
                    'bill_path' => $existingBillsByDay[$dayNo] ?? '',
                    'status' => 'Draft',
                ];
            }
        }

        $payload = [
            'submitted_by_user_id' => $this->userId(),
            'title' => 'Monthly Expense Sheet',
            'category' => 'Monthly Grid',
            'expense_month' => $month,
            'amount' => $totalAmount,
            'bill_image_path' => '',
            'status' => 'draft',
            'approved_by_user_id' => null,
            'approval_note' => '',
            'details_json' => $rows !== [] ? json_encode($rows) : null,
            'rejection_reason' => null,
        ];

        if ($existing) {
            $this->expenseModel->update((int) $existing['id'], $payload);
            $expenseId = (int) $existing['id'];
        } else {
            $this->expenseModel->insert($payload);
            $expenseId = (int) $this->expenseModel->getInsertID();
        }

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Draft saved.',
            'expense_id' => $expenseId,
            'saved_at' => date('H:i'),
            'amount' => $totalAmount,
        ]);
    }

    public function updateExpenseStatus(int $id)
    {
        $expense = $this->expenseModel->find($id);
        if (! $expense) {
            return redirect()->to('/phadmin/expenses')->with('error', 'Expense not found.');
        }

        if (! $this->canApproveUser((int) $expense['submitted_by_user_id'])) {
            return $this->deny('You do not have permission to approve this expense.');
        }

        $status = trim((string) $this->request->getPost('status'));
        if (! in_array($status, ['approved', 'rejected'], true)) {
            return redirect()->to('/phadmin/expenses')->with('error', 'Invalid expense status.');
        }

        $rejectionReason = trim((string) $this->request->getPost('rejection_reason'));
        if ($status === 'rejected' && $rejectionReason === '') {
            return redirect()->to('/phadmin/expenses')->with('error', 'Reason for rejection is mandatory.');
        }

        $this->expenseModel->update($id, [
            'status' => $status,
            'approved_by_user_id' => $this->userId(),
            'approval_note' => trim((string) $this->request->getPost('approval_note')),
            'rejection_reason' => $status === 'rejected' ? $rejectionReason : null,
        ]);

        $redirect = '/phadmin/expenses?employee_id=' . (int) $expense['submitted_by_user_id']
            . '&month=' . urlencode((string) ($expense['expense_month'] ?? ''));

        return redirect()->to($redirect)->with('success', 'Expense status updated successfully.');
    }

    public function uploadLogo()
    {
        if ($this->role() !== 'owner') {
            return $this->deny('Only Super Admin can update logo.');
        }

        $logoFile = $this->request->getFile('company_logo_file');
        if (! $logoFile || ! $logoFile->isValid() || $logoFile->hasMoved()) {
            return redirect()->to('/phadmin/dashboard')->with('error', 'Please choose a valid image file.');
        }

        $ext = strtolower((string) $logoFile->getExtension());
        if (! in_array($ext, ['png', 'jpg', 'jpeg', 'svg'], true)) {
            return redirect()->to('/phadmin/dashboard')->with('error', 'Allowed logo formats: png, jpg, jpeg, svg.');
        }

        $newName = $logoFile->getRandomName();
        $targetDir = FCPATH . 'uploads/logo';
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        $logoFile->move($targetDir, $newName);
        $path = 'uploads/logo/' . $newName;

        $this->settingModel->setValue('company_logo', $path);

        return redirect()->to('/phadmin/dashboard')->with('success', 'Company logo updated successfully.');
    }

    public function birthdays(): string
    {
        $role = $this->role();
        
        $query = $this->reminderModel->orderBy('event_date', 'ASC');
        
        if ($role !== 'owner' && $role !== 'manager') {
            $query = $query->where('user_id', $this->userId());
        }
        // Managers should probably see their own and their team's reminders, but keeping it simple for now, managers see all or team?
        // Actually, if it's a new table for reminders, let's let owners/managers see all, and MRs see their own.
        
        $reminders = $query->findAll();

        $upcomingBirthdays = [];
        $today = date('m-d');
        $tomorrow = date('m-d', strtotime('+1 day'));

        foreach ($reminders as $r) {
            if (empty($r['event_date'])) continue;
            
            $bday = date('m-d', strtotime($r['event_date']));
            if ($bday === $today || $bday === $tomorrow) {
                $upcomingBirthdays[] = $r;
            }
        }

        return view('admin/birthdays', $this->withBrand([
            'title' => 'Events & Reminders',
            'reminders' => $reminders,
            'upcomingBirthdays' => $upcomingBirthdays,
        ]));
    }
    
    public function createReminder()
    {
        $this->reminderModel->insert([
            'user_id'     => $this->userId(),
            'person_name' => trim((string) $this->request->getPost('person_name')),
            'event_type'  => trim((string) $this->request->getPost('event_type')),
            'event_date'  => trim((string) $this->request->getPost('event_date')),
            'notes'       => trim((string) $this->request->getPost('notes')),
        ]);
        return redirect()->to('/phadmin/birthdays')->with('success', 'Reminder created successfully.');
    }

    public function updateReminder($id)
    {
        $reminder = $this->reminderModel->find($id);
        if (!$reminder) {
            return redirect()->to('/phadmin/birthdays')->with('error', 'Reminder not found.');
        }
        
        if ($this->role() !== 'owner' && $this->role() !== 'manager' && $reminder['user_id'] != $this->userId()) {
            return $this->deny('You do not have permission to edit this reminder.');
        }

        $this->reminderModel->update($id, [
            'person_name' => trim((string) $this->request->getPost('person_name')),
            'event_type'  => trim((string) $this->request->getPost('event_type')),
            'event_date'  => trim((string) $this->request->getPost('event_date')),
            'notes'       => trim((string) $this->request->getPost('notes')),
        ]);
        return redirect()->to('/phadmin/birthdays')->with('success', 'Reminder updated successfully.');
    }

    public function deleteReminder($id)
    {
        $reminder = $this->reminderModel->find($id);
        if (!$reminder) {
            return redirect()->to('/phadmin/birthdays')->with('error', 'Reminder not found.');
        }
        
        if ($this->role() !== 'owner' && $this->role() !== 'manager' && $reminder['user_id'] != $this->userId()) {
            return $this->deny('You do not have permission to delete this reminder.');
        }

        $this->reminderModel->delete($id);
        return redirect()->to('/phadmin/birthdays')->with('success', 'Reminder deleted successfully.');
    }
    
    public function sendBirthdayReminders()
    {
        $query = $this->doctorModel
            ->select('doctors.*, assignee.name as assignee_name, assignee.email as assignee_email')
            ->join('users as assignee', 'assignee.id = doctors.assigned_to_user_id', 'left')
            ->where('doctors.birthday IS NOT NULL', null, false)
            ->where('doctors.birthday !=', '');
            
        $doctors = $query->findAll();
        
        $today = date('m-d');
        $tomorrow = date('m-d', strtotime('+1 day'));
        
        $emailService = \Config\Services::email();
        $sentCount = 0;
        
        foreach ($doctors as $d) {
            $bday = date('m-d', strtotime($d['birthday']));
            if (($bday === $today || $bday === $tomorrow) && !empty($d['assignee_email'])) {
                $when = ($bday === $today) ? 'TODAY' : 'TOMORROW';
                
                $emailService->setTo($d['assignee_email']);
                $emailService->setSubject("Birthday Reminder: Dr. {$d['name']} ({$when})");
                $emailService->setMessage("Dear {$d['assignee_name']}, \n\nThis is a reminder that Dr. {$d['name']}'s birthday is {$when} (" . date('d M', strtotime($d['birthday'])) . ").\n\nPlease remember to send your wishes.\n\nBest Regards,\nRAVION Pharma");
                
                if ($emailService->send()) {
                    $sentCount++;
                }
                $emailService->clear();
            }
        }
        
        return $this->response->setJSON(['success' => true, 'message' => "Sent {$sentCount} reminders."]);
    }
}
