<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Repositories\Order\Order_tracking\OrderTrackingRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderTRackingController extends Controller
{
    protected OrderTrackingRepositoryInterface $orderTrackingRepository;

    public function __construct(OrderTrackingRepositoryInterface $orderTrackingRepository)
    {
        $this->orderTrackingRepository = $orderTrackingRepository;
    }

    public function latestStatus($orderId)
    {
        $status = $this->orderTrackingRepository->getLatestStatusByOrderId((int)$orderId);
        return response()->json(['status' => true, 'data' => $status]);
    }

    public function createTracking(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|string',
            'tracked_at' => 'required|date',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'updated_by' => 'nullable|integer',
            'source' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $data = [
            'order_id' => $orderId,
            'status' => $request->status,
            'tracked_at' => $request->tracked_at,
            'location' => $request->location,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'updated_by' => $request->updated_by,
            'source' => $request->source,
            'notes' => $request->notes,
        ];

        $isCreated = $this->orderTrackingRepository->createTrackingEntry($data);

        if ($isCreated) {
            return response()->json(['status' => true, 'message' => 'Tracking entry created successfully.']);
        } else {
            return response()->json(['status' => false, 'message' => 'Failed to create tracking entry.'], 500);
        }
    }

    public function getTrackingHistory($orderId)
    {
        $history = $this->orderTrackingRepository->getTrackingHistory((int)$orderId);
        return response()->json(['status' => true, 'data' => $history]);
    }

    public function updateTracking(Request $request, $trackingId)
    {
        $request->validate([
            'status' => 'required|string',
            'tracked_at' => 'required|date',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'updated_by' => 'nullable|integer',
            'source' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $data = [
            'status' => $request->status,
            'tracked_at' => $request->tracked_at,
            'location' => $request->location,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'updated_by' => $request->updated_by,
            'source' => $request->source,
            'notes' => $request->notes,
        ];

        $isUpdated = $this->orderTrackingRepository->updateTracking($trackingId, $data);

        if ($isUpdated) {
            return response()->json(['status' => true, 'message' => 'Tracking entry updated successfully.']);
        } else {
            return response()->json(['status' => false, 'message' => 'Failed to update tracking entry.'], 500);
        }
    }

    public function deleteTracking($trackingId)
    {
        $isDeleted = $this->orderTrackingRepository->deleteTrackingEntry($trackingId);

        if ($isDeleted) {
            return response()->json(['status' => true, 'message' => 'Tracking entry deleted successfully.']);
        } else {
            return response()->json(['status' => false, 'message' => 'Failed to delete tracking entry.'], 500);
        }
    }

    public function bulkUpdateStatuses(Request $request)
    {
        $request->validate([
            'trackings' => 'required|array',
            'trackings.*.id' => 'required|integer',
            'trackings.*.status' => 'required|string',
            'trackings.*.tracked_at' => 'required|date',
            'trackings.*.location' => 'nullable|string',
            'trackings.*.latitude' => 'nullable|numeric',
            'trackings.*.longitude' => 'nullable|numeric',
            'trackings.*.updated_by' => 'nullable|integer',
            'trackings.*.source' => 'nullable|string',
            'trackings.*.notes' => 'nullable|string',
        ]);

        $isUpdated = $this->orderTrackingRepository->bulkUpdateStatuses($request->trackings);

        if ($isUpdated) {
            return response()->json(['status' => true, 'message' => 'Tracking entries updated successfully.']);
        } else {
            return response()->json(['status' => false, 'message' => 'Failed to update tracking entries.'], 500);
        }
    }

    public function getTrackingsByStatus($status)
    {
        $trackings = $this->orderTrackingRepository->getTrackingsByStatus($status);
        return response()->json(['status' => true, 'data' => $trackings]);
    }

    public function getTrackingsByDateRange($startDate, $endDate)
    {
        $trackings = $this->orderTrackingRepository->getTrackingsByDateRange($startDate, $endDate);
        return response()->json(['status' => true, 'data' => $trackings]);
    }

    public function getTrackingsByLocation($location)
    {
        $trackings = $this->orderTrackingRepository->getTrackingsByLocation($location);
        return response()->json(['status' => true, 'data' => $trackings]);
    }

    public function byDateRange(Request $request): JsonResponse
    {
        $data = $this->orderTrackingRepository->getTrackingsByDateRange(
            $request->start_date,
            $request->end_date
        );

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function byLocation(Request $request): JsonResponse
    {
        $data = $this->orderTrackingRepository->getTrackingsByLocation($request->location);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function hasStatus($orderId, $status)
    {
        $exists = $this->orderTrackingRepository->hasStatus((int) $orderId, $status);

        return response()->json(['exists' => $exists]);
    }

    public function latestLocation(int $orderId): JsonResponse
    {
        $location = $this->orderTrackingRepository->getLatestLocation($orderId);

        return response()->json(['success' => true, 'latest_location' => $location]);
    }

    public function deliveryStatsByManager(int $managerId): JsonResponse
    {
        $stats = $this->orderTrackingRepository->getDeliveryStatsByManager($managerId);

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function search(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'location',
            'order_id',
            'date_from',
            'date_to',
            'lat',
            'lng',
            'radius'
        ]);

        $results = $this->orderTrackingRepository->searchTrackings($filters);

        return response()->json(['success' => true, 'data' => $results]);
    }

    public function syncWithCourier(Request $request): JsonResponse
    {
        $synced = $this->orderTrackingRepository->syncWithCourier($request->all());

        return response()->json([
            'success' => $synced,
            'message' => $synced ? 'Data synced successfully' : 'Sync failed'
        ]);
    }
}
