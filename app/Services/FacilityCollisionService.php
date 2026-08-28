<?php

namespace App\Services;

use App\Models\FacilityAsset;
use App\Models\FacilityBlackout;
use App\Models\FacilityReservation;
use App\Models\FacilityRoom;
use Carbon\Carbon;

class FacilityCollisionService
{
    /**
     * Cek apakah ruangan tersedia pada rentang waktu yang diminta.
     */
    public function checkRoomAvailability($roomId, $startTime, $endTime, $ignoreReservationId = null): array
    {
        if (!$roomId) {
            return ['available' => true, 'conflicts' => []];
        }

        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        $room = FacilityRoom::find($roomId);
        if (!$room) {
            return ['available' => false, 'conflicts' => ['Ruangan tidak ditemukan.']];
        }

        if ($room->status !== 'available' || !$room->is_reservable) {
            return [
                'available' => false,
                'conflicts' => ["Ruangan '{$room->name}' saat ini berstatus {$room->status} dan tidak dapat dipinjam."]
            ];
        }

        // 1. Cek Blackout Dates (Agenda Khusus Sekolah)
        $blackoutConflicts = FacilityBlackout::where(function ($q) use ($roomId) {
                $q->whereNull('room_id')->orWhere('room_id', $roomId);
            })
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                  ->where('end_time', '>', $start);
            })
            ->get();

        if ($blackoutConflicts->isNotEmpty()) {
            $reasons = $blackoutConflicts->map(fn($b) => "Agenda Sekolah: {$b->name} ({$b->start_time->format('d/m/Y H:i')} - {$b->end_time->format('H:i')})")->toArray();
            return [
                'available' => false,
                'conflicts' => $reasons,
            ];
        }

        // 2. Cek Reservasi Lain yang Aktif/Disetujui
        $activeStatuses = ['pending_coach', 'pending_sarpras', 'approved', 'in_use'];

        $reservationConflicts = FacilityReservation::where('room_id', $roomId)
            ->whereIn('status', $activeStatuses)
            ->when($ignoreReservationId, fn($q) => $q->where('id', '!=', $ignoreReservationId))
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                  ->where('end_time', '>', $start);
            })
            ->with(['user', 'extracurricular'])
            ->get();

        if ($reservationConflicts->isNotEmpty()) {
            $reasons = $reservationConflicts->map(function ($res) {
                $borrower = $res->extracurricular ? $res->extracurricular->name : ($res->user->name ?? 'Pengguna');
                return "Bertabrakan dengan peminjaman: {$res->title} oleh {$borrower} ({$res->start_time->format('d/m/Y H:i')} - {$res->end_time->format('H:i')})";
            })->toArray();

            return [
                'available' => false,
                'conflicts' => $reasons,
            ];
        }

        return ['available' => true, 'conflicts' => []];
    }

    /**
     * Cek apakah daftar aset tersedia pada rentang waktu yang diminta.
     */
    public function checkAssetsAvailability(array $assetIds, $startTime, $endTime, $ignoreReservationId = null): array
    {
        if (empty($assetIds)) {
            return ['available' => true, 'conflicts' => []];
        }

        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $conflicts = [];

        $assets = FacilityAsset::whereIn('id', $assetIds)->get()->keyBy('id');

        foreach ($assetIds as $id) {
            $asset = $assets->get($id);
            if (!$asset) {
                $conflicts[] = "Aset ID #{$id} tidak ditemukan.";
                continue;
            }

            if ($asset->status === 'maintenance' || $asset->status === 'lost' || $asset->status === 'disposed') {
                $conflicts[] = "Aset '{$asset->name}' [{$asset->asset_code}] sedang dalam status {$asset->status}.";
            }
        }

        // Cek apakah aset sudah dipinjam di reservasi lain pada rentang jam tersebut
        $activeStatuses = ['pending_coach', 'pending_sarpras', 'approved', 'in_use'];

        $collidingReservations = FacilityReservation::whereIn('status', $activeStatuses)
            ->when($ignoreReservationId, fn($q) => $q->where('id', '!=', $ignoreReservationId))
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                  ->where('end_time', '>', $start);
            })
            ->whereHas('reservationAssets', function ($q) use ($assetIds) {
                $q->whereIn('asset_id', $assetIds);
            })
            ->with(['reservationAssets.asset', 'user', 'extracurricular'])
            ->get();

        foreach ($collidingReservations as $res) {
            $borrower = $res->extracurricular ? $res->extracurricular->name : ($res->user->name ?? 'Pengguna');
            foreach ($res->reservationAssets as $resAsset) {
                if (in_array($resAsset->asset_id, $assetIds)) {
                    $conflicts[] = "Aset '{$resAsset->asset->name}' [{$resAsset->asset->asset_code}] sedang dipinjam oleh {$borrower} ({$res->start_time->format('d/m/Y H:i')} - {$res->end_time->format('H:i')})";
                }
            }
        }

        return [
            'available' => empty($conflicts),
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Validasi ketersediaan menyeluruh.
     */
    public function validateBooking($type, $roomId, array $assetIds, $startTime, $endTime, $ignoreReservationId = null): array
    {
        $allConflicts = [];

        if (in_array($type, ['room_only', 'both']) && $roomId) {
            $roomCheck = $this->checkRoomAvailability($roomId, $startTime, $endTime, $ignoreReservationId);
            if (!$roomCheck['available']) {
                $allConflicts = array_merge($allConflicts, $roomCheck['conflicts']);
            }
        }

        if (in_array($type, ['asset_only', 'both']) && !empty($assetIds)) {
            $assetCheck = $this->checkAssetsAvailability($assetIds, $startTime, $endTime, $ignoreReservationId);
            if (!$assetCheck['available']) {
                $allConflicts = array_merge($allConflicts, $assetCheck['conflicts']);
            }
        }

        return [
            'valid' => empty($allConflicts),
            'conflicts' => $allConflicts,
        ];
    }
}
