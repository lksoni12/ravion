<?php

namespace App\Controllers;

use App\Models\ChemistModel;
use App\Models\DoctorModel;
use App\Models\EDetailingChemistModel;
use App\Models\EDetailingDoctorModel;
use App\Models\EDetailingSessionModel;
use App\Models\EDetailingSessionProductModel;
use App\Models\FieldPlanModel;
use App\Models\ProductModel;
use App\Models\SettingModel;
use App\Models\VisitModel;
use App\Models\SlideModel;

class EDetailingController extends BaseController
{
    private EDetailingDoctorModel $eDetailingDoctorModel;
    private EDetailingChemistModel $eDetailingChemistModel;
    private EDetailingSessionModel $sessionModel;
    private EDetailingSessionProductModel $sessionProductModel;
    private FieldPlanModel $fieldPlanModel;
    private DoctorModel $doctorModel;
    private ChemistModel $chemistModel;
    private ProductModel $productModel;
    private VisitModel $visitModel;
    private SettingModel $settingModel;
    private SlideModel $slideModel;

    public function __construct()
    {
        $this->eDetailingDoctorModel = new EDetailingDoctorModel();
        $this->eDetailingChemistModel = new EDetailingChemistModel();
        $this->sessionModel = new EDetailingSessionModel();
        $this->sessionProductModel = new EDetailingSessionProductModel();
        $this->fieldPlanModel = new FieldPlanModel();
        $this->doctorModel = new DoctorModel();
        $this->chemistModel = new ChemistModel();
        $this->productModel = new ProductModel();
        $this->visitModel = new VisitModel();
        $this->settingModel = new SettingModel();
        $this->slideModel = new SlideModel();
    }

    private function withBrand(array $data): array
    {
        $data['logoPath'] = $this->settingModel->getValue('company_logo', '');
        $data['companyName'] = 'RAVION Pharma';
        return $data;
    }

    private function getPlanDate(): string
    {
        $planDate = trim((string) $this->request->getGet('date'));
        if ($planDate === '') {
            $planDate = trim((string) $this->request->getPost('plan_date'));
        }

        if ($planDate === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $planDate)) {
            $planDate = date('Y-m-d');
        }

        return $planDate;
    }

    private function syncTourPlanDoctors(string $planDate): void
    {
        $plans = $this->fieldPlanModel
            ->select('id, doctor_id')
            ->where('plan_date', $planDate)
            ->where('doctor_id IS NOT NULL', null, false)
            ->whereIn('status', ['planned', 'submitted', 'approved', 'completed'])
            ->findAll();

        $this->eDetailingDoctorModel->syncFromTourPlan($planDate, $plans);
    }

    public function index(): string
    {
        $planDate = $this->getPlanDate();
        $this->syncTourPlanDoctors($planDate);

        $doctorSearch = trim((string) $this->request->getGet('doctor_search'));
        $chemistSearch = trim((string) $this->request->getGet('chemist_search'));

        $doctorQuery = $this->doctorModel->orderBy('name', 'ASC');
        if ($doctorSearch !== '') {
            $doctorQuery = $doctorQuery->like('name', $doctorSearch);
        }

        $chemistQuery = $this->chemistModel->orderBy('name', 'ASC');
        if ($chemistSearch !== '') {
            $chemistQuery = $chemistQuery->like('name', $chemistSearch);
        }

        $scheduleDoctors = $this->eDetailingDoctorModel->activeByDate($planDate);
        $doctorIds = array_column($scheduleDoctors, 'id');
        
        $activeSessionsMap = [];
        if (!empty($doctorIds)) {
            $activeSessions = $this->sessionModel
                ->whereIn('e_detailing_doctor_id', $doctorIds)
                ->where('status', 'active')
                ->orderBy('id', 'DESC')
                ->findAll();
            
            foreach ($activeSessions as $session) {
                $docId = (int)$session['e_detailing_doctor_id'];
                if (!isset($activeSessionsMap[$docId])) {
                    $activeSessionsMap[$docId] = $session;
                }
            }
        }

        foreach ($scheduleDoctors as &$doctor) {
            $doctor['active_session'] = $activeSessionsMap[(int) $doctor['id']] ?? null;
        }
        unset($doctor);

        return view('admin/e_detailing', $this->withBrand([
            'title' => 'E-Detailing',
            'planDate' => $planDate,
            'scheduleDoctors' => $scheduleDoctors,
            'scheduleChemists' => $this->eDetailingChemistModel->activeByDate($planDate),
            'doctorOptions' => $doctorQuery->findAll(100),
            'chemistOptions' => $chemistQuery->findAll(100),
            'productOptions' => $this->productModel->orderBy('display_order', 'ASC')->orderBy('id', 'ASC')->findAll(),
            'doctorSearch' => $doctorSearch,
            'chemistSearch' => $chemistSearch,
        ]));
    }

    public function addDoctor()
    {
        $planDate = trim((string) $this->request->getPost('plan_date'));
        $doctorId = (int) $this->request->getPost('doctor_id');

        if ($planDate === '' || $doctorId <= 0) {
            return redirect()->to('/phadmin/e-detailing?date=' . urlencode($planDate ?: date('Y-m-d')))
                ->with('error', 'Plan date and doctor are required.');
        }

        $existing = $this->eDetailingDoctorModel
            ->where('plan_date', $planDate)
            ->where('doctor_id', $doctorId)
            ->first();

        if ($existing) {
            $this->eDetailingDoctorModel->update((int) $existing['id'], [
                'status' => 'active',
                'source' => (string) ($existing['source'] ?? 'manual'),
            ]);

            return redirect()->to('/phadmin/e-detailing?date=' . urlencode($planDate))
                ->with('success', 'Doctor updated in E-Detailing list.');
        }

        $this->eDetailingDoctorModel->insert([
            'plan_date' => $planDate,
            'doctor_id' => $doctorId,
            'source' => 'manual',
            'status' => 'active',
        ]);

        return redirect()->to('/phadmin/e-detailing?date=' . urlencode($planDate))
            ->with('success', 'Doctor added to E-Detailing list.');
    }

    public function addChemist()
    {
        $planDate = trim((string) $this->request->getPost('plan_date'));
        $chemistId = (int) $this->request->getPost('chemist_id');

        if ($planDate === '' || $chemistId <= 0) {
            return redirect()->to('/phadmin/e-detailing?date=' . urlencode($planDate ?: date('Y-m-d')))
                ->with('error', 'Plan date and chemist are required.');
        }

        $existing = $this->eDetailingChemistModel
            ->where('plan_date', $planDate)
            ->where('chemist_id', $chemistId)
            ->first();

        if ($existing) {
            $this->eDetailingChemistModel->update((int) $existing['id'], ['status' => 'active']);

            return redirect()->to('/phadmin/e-detailing?date=' . urlencode($planDate))
                ->with('success', 'Chemist updated in E-Detailing list.');
        }

        $this->eDetailingChemistModel->insert([
            'plan_date' => $planDate,
            'chemist_id' => $chemistId,
            'source' => 'manual',
            'status' => 'active',
        ]);

        return redirect()->to('/phadmin/e-detailing?date=' . urlencode($planDate))
            ->with('success', 'Chemist added to E-Detailing list.');
    }

    public function deleteDoctors()
    {
        $planDate = trim((string) $this->request->getPost('plan_date'));
        $selectedIds = $this->request->getPost('selected_doctors');
        $ids = is_array($selectedIds) ? array_map('intval', $selectedIds) : [];
        $ids = array_values(array_filter($ids, static fn (int $value): bool => $value > 0));

        if ($ids === []) {
            return redirect()->to('/phadmin/e-detailing?date=' . urlencode($planDate ?: date('Y-m-d')))
                ->with('error', 'Select at least one doctor to remove.');
        }

        $this->eDetailingDoctorModel
            ->whereIn('id', $ids)
            ->set(['status' => 'removed'])
            ->update();

        return redirect()->to('/phadmin/e-detailing?date=' . urlencode($planDate ?: date('Y-m-d')))
            ->with('success', 'Selected doctors removed from E-Detailing list.');
    }

    public function startCall()
    {
        $planDate = trim((string) $this->request->getPost('plan_date'));
        $eDetailingDoctorId = (int) $this->request->getPost('e_detailing_doctor_id');
        $productIdsRaw = $this->request->getPost('product_ids');

        $productIds = is_array($productIdsRaw) ? array_map('intval', $productIdsRaw) : [];
        $productIds = array_values(array_filter($productIds, static fn (int $value): bool => $value > 0));

        if (! empty($productIds)) {
            $orderedProducts = $this->productModel->whereIn('id', $productIds)
                                                  ->orderBy('display_order', 'ASC')
                                                  ->orderBy('id', 'ASC')
                                                  ->findAll();
            $productIds = array_column($orderedProducts, 'id');
        }

        if ($eDetailingDoctorId <= 0 || $productIds === []) {
            return redirect()->to('/phadmin/e-detailing?date=' . urlencode($planDate ?: date('Y-m-d')))
                ->with('error', 'Doctor and at least one product are required to start call.');
        }

        $entry = $this->eDetailingDoctorModel->find($eDetailingDoctorId);
        if (! $entry || ($entry['status'] ?? '') !== 'active') {
            return redirect()->to('/phadmin/e-detailing?date=' . urlencode($planDate ?: date('Y-m-d')))
                ->with('error', 'Doctor schedule entry not found.');
        }

        $activeSession = $this->sessionModel->activeForDoctor($eDetailingDoctorId);
        if ($activeSession) {
            return redirect()->to('/phadmin/e-detailing/presentation/' . (int) $activeSession['id']);
        }

        $sessionId = (int) $this->sessionModel->insert([
            'e_detailing_doctor_id' => $eDetailingDoctorId,
            'doctor_id' => (int) $entry['doctor_id'],
            'field_plan_id' => (int) ($entry['field_plan_id'] ?? 0) ?: null,
            'plan_date' => (string) $entry['plan_date'],
            'started_at' => date('Y-m-d H:i:s'),
            'status' => 'active',
        ]);

        $sequence = 1;
        foreach ($productIds as $productId) {
            $this->sessionProductModel->insert([
                'session_id' => $sessionId,
                'product_id' => $productId,
                'sequence_no' => $sequence,
            ]);
            $sequence++;
        }

        return redirect()->to('/phadmin/e-detailing/presentation/' . $sessionId);
    }

    public function presentation(int $sessionId): string
    {
        $session = $this->sessionModel->find($sessionId);
        if (! $session) {
            return redirect()->to('/phadmin/e-detailing')->with('error', 'Call session not found.');
        }

        $doctor = $this->doctorModel->find((int) $session['doctor_id']);
        $products = $this->sessionProductModel->productsForSession($sessionId);

        if ($products === []) {
            return redirect()->to('/phadmin/e-detailing?date=' . urlencode((string) $session['plan_date']))
                ->with('error', 'No products assigned to this call.');
        }

        // Fetch all active slides for the assigned products
        $productIds = array_column($products, 'product_id');
        $allSlides = [];
        if (! empty($productIds)) {
            $allSlides = $this->slideModel->whereIn('product_id', $productIds)
                                          ->where('status', 'active')
                                          ->orderBy('slide_order', 'ASC')
                                          ->orderBy('id', 'ASC')
                                          ->findAll();
        }

        // Group slides by product_id
        $slidesByProduct = [];
        foreach ($allSlides as $slide) {
            $slidesByProduct[$slide['product_id']][] = $slide;
        }

        // Sequence slides for the presentation
        $presentationSlides = [];
        foreach ($products as $product) {
            $pId = (int) $product['product_id'];

            if (! empty($product['image_path'])) {
                $decoded = json_decode($product['image_path'], true);
                $images = is_array($decoded) ? $decoded : [$product['image_path']];
                foreach ($images as $idx => $img) {
                    $presentationSlides[] = [
                        'type' => 'image',
                        'product_name' => $product['product_name'] ?? '',
                        'slide_title' => 'Product Image ' . ($idx + 1),
                        'file_path' => $img
                    ];
                }
            }

            if (! empty($slidesByProduct[$pId])) {
                foreach ($slidesByProduct[$pId] as $slide) {
                    $presentationSlides[] = [
                        'type' => 'slide',
                        'product_name' => $product['product_name'] ?? '',
                        'slide_title' => $slide['title'],
                        'file_path' => $slide['file_path']
                    ];
                }
            } elseif (! empty($product['detail_pdf'])) {
                $presentationSlides[] = [
                    'type' => 'slide',
                    'product_name' => $product['product_name'] ?? '',
                    'slide_title' => 'Main Detail',
                    'file_path' => $product['detail_pdf']
                ];
            }
        }

        $currentIndex = (int) $this->request->getGet('index');
        if ($currentIndex < 0) {
            $currentIndex = 0;
        }
        $totalSlides = count($presentationSlides);
        if ($currentIndex >= $totalSlides && $totalSlides > 0) {
            $currentIndex = $totalSlides - 1;
        }

        $isThankYouSlide = false;
        $currentProduct = $presentationSlides[$currentIndex] ?? null;

        return view('admin/e_detailing_presentation', $this->withBrand([
            'title' => 'E-Detailing Presentation',
            'session' => $session,
            'doctor' => $doctor,
            'products' => $products,
            'presentationSlides' => $presentationSlides,
            'totalSlides' => $totalSlides,
            'currentIndex' => $currentIndex,
            'isThankYouSlide' => $isThankYouSlide,
            'currentProduct' => $currentProduct,
        ]));
    }

    public function endCall(int $sessionId)
    {
        $session = $this->sessionModel->find($sessionId);
        if (! $session) {
            return redirect()->to('/phadmin/e-detailing')->with('error', 'Call session not found.');
        }

        $endTime = date('Y-m-d H:i:s');
        $startTs = strtotime((string) ($session['started_at'] ?? $endTime));
        $endTs = strtotime($endTime);
        $durationMinutes = max(0, (int) floor(($endTs - $startTs) / 60));

        if (($session['status'] ?? '') !== 'completed') {
            $this->sessionModel->update($sessionId, [
                'ended_at' => $endTime,
                'status' => 'completed',
            ]);
        }

        $this->persistVisitsForSession(
            $sessionId,
            $session,
            $endTime,
            $durationMinutes,
            trim((string) $this->request->getPost('feedback')),
            trim((string) $this->request->getPost('next_objective')),
            trim((string) $this->request->getPost('joint_call_with')),
            (int) session()->get('user_id')
        );

        return redirect()->to('/phadmin/e-detailing?date=' . urlencode((string) $session['plan_date']))
            ->with('success', 'Call ended and activity saved successfully.');
    }

    private function persistVisitsForSession(
        int $sessionId,
        array $session,
        string $visitDateTime,
        int $durationMinutes,
        string $feedback,
        string $nextObjective,
        string $jointCallWith,
        int $createdByUserId
    ): void {
        $existing = $this->visitModel
            ->where('e_detailing_session_id', $sessionId)
            ->countAllResults();
        if ($existing > 0) {
            return;
        }

        $products = $this->sessionProductModel->productsForSession($sessionId);
        foreach ($products as $product) {
            $this->visitModel->insert([
                'doctor_id' => (int) $session['doctor_id'],
                'product_id' => (int) $product['product_id'],
                'chemist_id' => null,
                'e_detailing_session_id' => $sessionId,
                'visit_date' => $visitDateTime,
                'dcr_date' => substr($visitDateTime, 0, 10),
                'dcr_month' => substr($visitDateTime, 0, 7),
                'duration_minutes' => $durationMinutes,
                'action_type' => 'detailing',
                'created_by_user_id' => $createdByUserId,
                'feedback' => $feedback,
                'next_objective' => $nextObjective,
                'joint_call_with' => $jointCallWith,
                'report_status' => 'submitted',
            ]);
        }
    }
}
