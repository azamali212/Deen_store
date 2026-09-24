<?php

declare(strict_types=1);

namespace App\Domain\Seller\Repositories\Queries;

use App\Domain\Seller\Enums\AiRiskLevel;
use App\Domain\Seller\Enums\SellerApplicationStatus;
use App\Models\SellerApplication;
use Illuminate\Database\Eloquent\Builder;

final class SellerApplicationQuery
{
    public function byId(int $applicationId): Builder
    {
        return SellerApplication::query()
            ->whereKey($applicationId);
    }

    public function forUser(int $userId): Builder
    {
        return SellerApplication::query()
            ->where('user_id', $userId);
    }

    public function byStoreName(string $storeName, ?int $exceptApplicationId = null): Builder
    {
        return SellerApplication::query()
            ->where('store_name', $storeName)
            ->when(
                $exceptApplicationId !== null,
                fn (Builder $query): Builder => $query->whereKeyNot($exceptApplicationId),
            );
    }

    /**
     * Admin list. Drafts are NEVER included (C1) — an admin should only
     * ever see applications the customer actually submitted.
     */
    public function forAdmin(?SellerApplicationStatus $status, ?AiRiskLevel $risk = null): Builder
    {
        return SellerApplication::query()
            ->when(
                $status !== null,
                fn (Builder $query): Builder => $query->where('status', $status->value),
                fn (Builder $query): Builder => $query->where('status', '!=', SellerApplicationStatus::DRAFT->value),
            )
            ->when(
                $risk !== null,
                fn (Builder $query): Builder => $query->where('ai_risk_level', $risk->value),
            )
            ->with('user:id,name,email')
            ->withCount('documents')
            ->orderByDesc('submitted_at');
    }
}
