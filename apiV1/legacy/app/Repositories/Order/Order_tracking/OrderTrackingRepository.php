<?php

namespace App\Repositories\Order\Order_tracking;

use App\Models\Order_Tracking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderTrackingRepository implements OrderTrackingRepositoryInterface
{
    public function getLatestStatusByOrderId(int $orderId): ?array
    {
        Log::info('Fetching latest order tracking status for order ID: ' . $orderId);

        $status = Order_Tracking::where('order_id', $orderId)
            ->latest('tracked_at')
            ->first();

        Log::info('Fetched status: ', [$status]);

        return $status ? $status->toArray() : null;
    }

    public function getTrackingHistory(int $orderId): array
    {
        return Order_Tracking::where('order_id', $orderId)
            ->orderBy('tracked_at')
            ->get()
            ->toArray();
    }

    public function createTrackingEntry(array $data): bool
    {
        try {
            Order_Tracking::create($data);
            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to create tracking entry', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function updateTracking(int $trackingId, array $data): bool
    {
        $tracking = Order_Tracking::find($trackingId);
        return $tracking ? $tracking->update($data) : false;
    }

    public function deleteTrackingEntry(int $trackingId): bool
    {
        return Order_Tracking::destroy($trackingId) > 0;
    }

    public function bulkUpdateStatuses(array $trackings): bool
    {
        DB::beginTransaction();
        try {
            foreach ($trackings as $tracking) {
                Order_Tracking::updateOrCreate(
                    ['id' => $tracking['id'] ?? null],
                    $tracking
                );
            }
            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Bulk update failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getTrackingsByStatus(string $status): array
    {
        return Order_Tracking::where('status', $status)->get()->toArray();
    }

    public function getTrackingsByDateRange(string $startDate, string $endDate): array
    {
        return Order_Tracking::whereBetween('tracked_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ])
            ->orderBy('tracked_at')
            ->get()
            ->toArray();
    }

    public function getTrackingsByLocation(string $location): array
    {
        return Order_Tracking::where('location', 'LIKE', "%$location%")
            ->orderBy('tracked_at', 'desc')
            ->get()
            ->toArray();
    }

    public function hasStatus(int $orderId, string $status): bool
    {
        return Order_Tracking::where('order_id', $orderId)
            ->where('status', $status)
            ->exists();
    }

    public function getLatestLocation(int $orderId): ?string
    {
        return Order_Tracking::where('order_id', $orderId)
            ->latest('tracked_at')
            ->value('location');
    }

    public function getDeliveryStatsByManager(int $managerId): array
    {
        return Order_Tracking::whereHas('updatedBy', function ($q) use ($managerId) {
            $q->where('manager_id', $managerId);
        })
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get()
            ->toArray();
    }

    public function searchTrackings(array $filters): array
    {
        $query = Order_Tracking::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['location'])) {
            $query->where('location', 'LIKE', "%{$filters['location']}%");
        }

        if (!empty($filters['order_id'])) {
            $query->where('order_id', $filters['order_id']);
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('tracked_at', [
                Carbon::parse($filters['date_from']),
                Carbon::parse($filters['date_to'])
            ]);
        }

        if (!empty($filters['lat']) && !empty($filters['lng']) && !empty($filters['radius'])) {
            $query->whereRaw("
                ST_Distance_Sphere(
                    point(longitude, latitude),
                    point(?, ?)
                ) <= ?
            ", [$filters['lng'], $filters['lat'], $filters['radius'] * 1000]); // radius in meters
        }

        return $query->orderByDesc('tracked_at')->get()->toArray();
    }

    public function syncWithCourier(array $apiData): bool
    {
        DB::beginTransaction();
        try {
            foreach ($apiData as $entry) {
                Order_Tracking::updateOrCreate(
                    [
                        'order_id'   => $entry['order_id'],
                        'tracked_at' => Carbon::parse($entry['tracked_at'] ?? now())
                    ],
                    $entry
                );
            }
            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Sync with courier failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
