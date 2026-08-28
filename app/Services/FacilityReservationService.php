<?php

namespace App\Services;

use App\Models\FacilityAsset;
use App\Models\FacilityDamageReport;
use App\Models\FacilityReservation;
use App\Models\FacilityReservationAsset;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class FacilityReservationService
{
    protected FacilityCollisionService $collisionService;

    public function __construct(FacilityCollisionService $collisionService)
    {
        $this->collisionService = $collisionService;
    }

    /**
     * Buat reservasi baru.
     */
    public function createReservation(array $data, int $userId): FacilityReservation
    {
        $type = $data['type'] ?? 'both';
        $roomId = $data['room_id'] ?? null;
        $assetIds = $data['asset_ids'] ?? [];
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];

        // Cek tabrakan jadwal
        $collision = $this->collisionService->validateBooking($type, $roomId, $assetIds, $startTime, $endTime);
        if (!$collision['valid']) {
            throw new Exception(implode(' ', $collision['conflicts']));
        }

        return DB::transaction(function () use ($data, $userId, $type, $roomId, $assetIds) {
            $requiresCoach = array_key_exists('requires_coach_approval', $data)
                ? (bool) $data['requires_coach_approval']
                : !empty($data['extracurricular_id']);
            $initialStatus = $requiresCoach ? 'pending_coach' : 'pending_sarpras';

            $reservation = FacilityReservation::create([
                'user_id' => $userId,
                'extracurricular_id' => $data['extracurricular_id'] ?? null,
                'room_id' => in_array($type, ['room_only', 'both']) ? $roomId : null,
                'type' => $type,
                'title' => $data['title'],
                'purpose' => $data['purpose'],
                'proposal_file_path' => $data['proposal_file_path'] ?? null,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'participant_count' => $data['participant_count'] ?? null,
                'requires_coach_approval' => $requiresCoach,
                'coach_user_id' => $data['coach_user_id'] ?? null,
                'stage1_status' => $requiresCoach ? 'pending' : 'approved',
                'stage1_at' => $requiresCoach ? null : Carbon::now(),
                'stage2_status' => 'pending',
                'status' => $initialStatus,
            ]);

            if (in_array($type, ['asset_only', 'both']) && !empty($assetIds)) {
                foreach ($assetIds as $assetId) {
                    $asset = FacilityAsset::find($assetId);
                    FacilityReservationAsset::create([
                        'reservation_id' => $reservation->id,
                        'asset_id' => $assetId,
                        'checkout_condition' => $asset ? $asset->condition : 'good',
                    ]);
                }
            }

            return $reservation->load(['room', 'assets', 'user', 'extracurricular']);
        });
    }

    /**
     * Approval Tahap 1 (Pembina Eskul / Guru Pembimbing).
     */
    public function approveStage1(FacilityReservation $reservation, int $approverId, ?string $notes = null): FacilityReservation
    {
        if ($reservation->status !== 'pending_coach') {
            throw new Exception("Status pengajuan saat ini tidak dapat disetujui untuk Tahap 1.");
        }

        $reservation->update([
            'stage1_status' => 'approved',
            'stage1_by' => $approverId,
            'stage1_notes' => $notes,
            'stage1_at' => Carbon::now(),
            'status' => 'pending_sarpras',
        ]);

        return $reservation;
    }

    /**
     * Reject Tahap 1.
     */
    public function rejectStage1(FacilityReservation $reservation, int $approverId, string $notes): FacilityReservation
    {
        if ($reservation->status !== 'pending_coach') {
            throw new Exception("Status pengajuan saat ini tidak dapat diproses.");
        }

        $reservation->update([
            'stage1_status' => 'rejected',
            'stage1_by' => $approverId,
            'stage1_notes' => $notes,
            'stage1_at' => Carbon::now(),
            'status' => 'rejected',
        ]);

        return $reservation;
    }

    /**
     * Approval Tahap 2 (Petugas Sarpras - Jadwal Terkunci).
     */
    public function approveStage2(FacilityReservation $reservation, int $approverId, ?string $notes = null): FacilityReservation
    {
        if (!in_array($reservation->status, ['pending_sarpras', 'pending_coach'])) {
            throw new Exception("Status pengajuan tidak valid untuk persetujuan Sarpras.");
        }

        // Re-check collision just before approving
        $assetIds = $reservation->reservationAssets()->pluck('asset_id')->toArray();
        $collision = $this->collisionService->validateBooking(
            $reservation->type,
            $reservation->room_id,
            $assetIds,
            $reservation->start_time,
            $reservation->end_time,
            $reservation->id
        );

        if (!$collision['valid']) {
            throw new Exception("Gagal menyetujui: " . implode(' ', $collision['conflicts']));
        }

        $reservation->update([
            'stage2_status' => 'approved',
            'stage2_by' => $approverId,
            'stage2_notes' => $notes,
            'stage2_at' => Carbon::now(),
            'status' => 'approved',
        ]);

        return $reservation;
    }

    /**
     * Reject Tahap 2.
     */
    public function rejectStage2(FacilityReservation $reservation, int $approverId, string $notes): FacilityReservation
    {
        $reservation->update([
            'stage2_status' => 'rejected',
            'stage2_by' => $approverId,
            'stage2_notes' => $notes,
            'stage2_at' => Carbon::now(),
            'status' => 'rejected',
        ]);

        return $reservation;
    }

    /**
     * Serah Terima / Handover Barang & Ruang (Hari H).
     */
    public function processHandover(FacilityReservation $reservation, int $officerId, ?string $notes = null): FacilityReservation
    {
        if ($reservation->status !== 'approved') {
            throw new Exception("Hanya peminjaman dengan status 'Disetujui' yang dapat dilakukan serah terima.");
        }

        return DB::transaction(function () use ($reservation, $officerId, $notes) {
            $reservation->update([
                'status' => 'in_use',
                'handover_by' => $officerId,
                'handover_at' => Carbon::now(),
                'handover_notes' => $notes,
            ]);

            // Update status masing-masing aset jadi 'borrowed'
            $assetIds = $reservation->reservationAssets()->pluck('asset_id')->toArray();
            if (!empty($assetIds)) {
                FacilityAsset::whereIn('id', $assetIds)->update(['status' => 'borrowed']);
            }

            return $reservation;
        });
    }

    /**
     * Pengembalian & Pengecekan Fisik Aset.
     */
    public function processReturn(
        FacilityReservation $reservation,
        int $officerId,
        array $assetReturns = [],
        ?string $notes = null,
        ?array $damageData = null
    ): FacilityReservation {
        if ($reservation->status !== 'in_use') {
            throw new Exception("Hanya peminjaman dengan status 'Sedang Digunakan' yang dapat dikembalikan.");
        }

        return DB::transaction(function () use ($reservation, $officerId, $assetReturns, $notes, $damageData) {
            $hasDamageOrLoss = false;

            foreach ($reservation->reservationAssets as $resAsset) {
                $returnCond = $assetReturns[$resAsset->asset_id]['return_condition'] ?? 'good';
                $assetNotes = $assetReturns[$resAsset->asset_id]['notes'] ?? null;

                $resAsset->update([
                    'return_condition' => $returnCond,
                    'notes' => $assetNotes,
                ]);

                // Update asset master
                $asset = $resAsset->asset;
                if ($asset) {
                    if ($returnCond === 'lost') {
                        $asset->update(['status' => 'lost']);
                        $hasDamageOrLoss = true;
                    } elseif ($returnCond === 'heavy_damage' || $returnCond === 'minor_damage') {
                        $asset->update(['status' => 'maintenance', 'condition' => $returnCond]);
                        $hasDamageOrLoss = true;
                    } else {
                        $asset->update(['status' => 'available', 'condition' => 'good']);
                    }
                }
            }

            $summaryCondition = $hasDamageOrLoss ? 'damaged' : 'good';
            $finalStatus = $hasDamageOrLoss ? 'incident' : 'completed';

            // Jika ada laporan kerusakan / berita acara
            if ($hasDamageOrLoss && !empty($damageData)) {
                FacilityDamageReport::create([
                    'reservation_id' => $reservation->id,
                    'asset_id' => $damageData['asset_id'] ?? null,
                    'room_id' => $reservation->room_id,
                    'reported_by' => $officerId,
                    'damage_type' => $damageData['damage_type'] ?? 'damaged',
                    'description' => $damageData['description'] ?? 'Kerusakan/kehilangan pada saat pengembalian.',
                    'evidence_photos' => $damageData['evidence_photos'] ?? null,
                    'action_plan' => $damageData['action_plan'] ?? 'Menunggu klarifikasi peminjam',
                    'compensation_fee' => $damageData['compensation_fee'] ?? 0,
                    'status' => 'open',
                ]);
            }

            $reservation->update([
                'status' => $finalStatus,
                'return_by' => $officerId,
                'return_at' => Carbon::now(),
                'return_notes' => $notes,
                'return_condition_summary' => $summaryCondition,
            ]);

            return $reservation->load(['reservationAssets.asset', 'damageReports']);
        });
    }
}
