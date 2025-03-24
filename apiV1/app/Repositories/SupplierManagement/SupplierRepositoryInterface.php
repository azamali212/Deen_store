<?php

namespace App\Repositories\SupplierManagement;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;

interface SupplierRepositoryInterface
{
    public function all(int $perPage);
    public function createSupplier(array $data): Supplier;

    public function updateSupplier(int $supplierId, array $data): Supplier;

    public function deleteSupplier(int $supplierId): bool;

    public function getSupplier(int $supplierId): Supplier;

    public function listSuppliers(array $filters): Collection;

    public function updateSupplierProductPrice(int $supplierId, float $newPrice);

    public function evaluateSupplierPerformance(int $supplierId): array;

    public function handleSupplierContractTermination(int $supplierId): bool;

    public function getTopPerformingSuppliers(int $limit): Collection;

    public function assignSupplierToCategory(int $supplierId, int $categoryId): bool;

    public function trackSupplierDeliveryStatus(int $supplierId): array;

    public function generateSupplierReport(array $filters, int $perPage);
    public function exportSupplierReport(array $filters);

    public function markSupplierAsPreferred(int $supplierId): bool;

    public function checkSupplierStockAvailability(int $supplierId, string $productName): bool;
    public function generateSupplierPaymentHistoryReport(array $filters, int $perPage = 20);
    public function exportSupplierPaymentHistoryReport(array $filters);

    //public function retrieveSupplierPaymentHistory(int $supplierId): array;

    public function blacklistSupplier(int $supplierId, string $reason): bool;

    public function unblacklistSupplier(int $supplierId): bool;

    public function getBlacklistedSuppliers(): Collection;

    public function processSupplierPayment(int $supplierId, float $amount, string $paymentMethod, string $paymentDate): bool;

    public function getSupplierPaymentDetails(int $paymentId): array;

    public function getSuppliersByCategory(int $categoryId): Collection;

    public function getSuppliersWithActiveContracts(): Collection;

    public function updateSupplierContractStatus(int $supplierId, string $status): bool;

    public function getSuppliersPendingContracts(): Collection;

    public function getContractTerminatedSuppliers(): Collection;
}