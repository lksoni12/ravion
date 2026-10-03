<?php

namespace App\Controllers;

use App\Models\EDetailingDoctorModel;
use App\Models\EDetailingSessionModel;
use App\Models\EDetailingSessionProductModel;
use App\Models\FieldPlanModel;
use App\Models\VisitModel;

class EDetailingApiController extends BaseController
{
    private EDetailingDoctorModel $eDetailingDoctorModel;
    private EDetailingSessionModel $sessionModel;
    private EDetailingSessionProductModel $sessionProductModel;
    private FieldPlanModel $fieldPlanModel;
    private VisitModel $visitModel;

    public function __construct()
    {
        $this->eDetailingDoctorModel = new EDetailingDoctorModel();
        $this->sessionModel = new EDetailingSessionModel();
        $this->sessionProductModel = new EDetailingSessionProductModel();
        $this->fieldPlanModel = new FieldPlanModel();
        $this->visitModel = new VisitModel();
    }

    private function resolveDate(): string
    {
        $date = trim((string) $this->request->getGet('date'));
        if ($date === '') {
            $date = date('Y-m-d');
        }

        return $date;
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

    public function index()
    {
        $planDate = $this->resolveDate();
        $this->syncTourPlanDoctors($planDate);

        return $this->response->setJSON([
            'date' => $planDate,
            'doctors' => $this->eDetailingDoctorModel->activeByDate($planDate),
        ]);
    }

    public function startCall()
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = $this->request->getPost();
        }

        $eDetailingDoctorId = (int) ($payload['e_detailing_doctor_id'] ?? 0);
        $productIdsRaw = $payload['product_ids'] ?? [];
        $productIds = is_array($productIdsRaw) ? array_map('intval', $productIdsRaw) : [];
        $productIds = array_values(array_filter($productIds, static fn (int $value): bool => $value > 0));

        if (! empty($productIds)) {
            $productModel = new \App\Models\ProductModel();
            $orderedProducts = $productModel->whereIn('id', $productIds)
                                            ->orderBy('display_order', 'ASC')
                                            ->orderBy('id', 'ASC')
                                            ->findAll();
            $productIds = array_column($orderedProducts, 'id');
        }

        if ($eDetailingDoctorId <= 0 || $productIds === []) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Doctor and products are required.']);
        }

        $entry = $this->eDetailingDoctorModel->find($eDetailingDoctorId);
        if (! $entry || ($entry['status'] ?? '') !== 'active') {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Doctor schedule not found.']);
        }

        $activeSession = $this->sessionModel->activeForDoctor($eDetailingDoctorId);
        if ($activeSession) {
            return $this->response->setJSON(['session_id' => (int) $activeSession['id'], 'status' => 'already_active']);
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

        return $this->response->setStatusCode(201)->setJSON([
            'session_id' => $sessionId,
            'status' => 'active',
        ]);
    }

    public function endCall(int $sessionId)
    {
        $session = $this->sessionModel->find($sessionId);
        if (! $session) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Call session not found.']);
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = $this->request->getPost();
        }

        $feedback = trim((string) ($payload['feedback'] ?? ''));
        $nextObjective = trim((string) ($payload['next_objective'] ?? ''));
        $jointCallWith = trim((string) ($payload['joint_call_with'] ?? ''));

        if (($session['status'] ?? '') !== 'active') {
            $this->persistVisitsForSession(
                $sessionId,
                $session,
                (string) ($session['ended_at'] ?? date('Y-m-d H:i:s')),
                $feedback,
                $nextObjective,
                $jointCallWith
            );
            return $this->response->setJSON(['session_id' => $sessionId, 'status' => (string) $session['status']]);
        }

        $endedAt = date('Y-m-d H:i:s');
        $this->sessionModel->update($sessionId, [
            'ended_at' => $endedAt,
            'status' => 'completed',
        ]);

        $session = $this->sessionModel->find($sessionId) ?? $session;
        $this->persistVisitsForSession($sessionId, $session, $endedAt, $feedback, $nextObjective, $jointCallWith);

        return $this->response->setJSON([
            'session_id' => $sessionId,
            'status' => 'completed',
        ]);
    }

    private function persistVisitsForSession(
        int $sessionId,
        array $session,
        string $visitDateTime,
        string $feedback,
        string $nextObjective,
        string $jointCallWith
    ): void {
        $existing = $this->visitModel
            ->where('e_detailing_session_id', $sessionId)
            ->countAllResults();
        if ($existing > 0) {
            return;
        }

        $startTs = strtotime((string) ($session['started_at'] ?? $visitDateTime));
        $endTs = strtotime($visitDateTime);
        $durationMinutes = max(0, (int) floor(($endTs - $startTs) / 60));
        $products = $this->sessionProductModel->productsForSession($sessionId);
        $createdByUserId = (int) (session()->get('user_id') ?? 0);

        foreach ($products as $product) {
            $this->visitModel->insert([
                'doctor_id' => (int) ($session['doctor_id'] ?? 0),
                'product_id' => (int) ($product['product_id'] ?? 0),
                'chemist_id' => null,
                'e_detailing_session_id' => $sessionId,
                'visit_date' => $visitDateTime,
                'dcr_date' => substr($visitDateTime, 0, 10),
                'dcr_month' => substr($visitDateTime, 0, 7),
                'duration_minutes' => $durationMinutes,
                'action_type' => 'detailing',
                'created_by_user_id' => $createdByUserId > 0 ? $createdByUserId : null,
                'feedback' => $feedback,
                'next_objective' => $nextObjective,
                'joint_call_with' => $jointCallWith,
                'report_status' => 'submitted',
            ]);
        }
    }
}
