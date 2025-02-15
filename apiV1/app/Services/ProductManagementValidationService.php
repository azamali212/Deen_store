<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductManagementValidationService
{
    /**
     * Validate Product data.
     */
    public function validateProduct(array $data): array
    {
        $validator = Validator::make($data, [
            'name'            => 'required|string|max:255|unique:products,name',
            'product_manager_id' => 'nullable|exists:users,id',
            'store_manager_id' => 'nullable|exists:users,id',
            'vendor_id'       => 'nullable|exists:vendors,id',
            'slug'            => 'nullable|string|max:255|unique:products,slug',
            'description'     => 'nullable|string',
            'sku'             => 'nullable|string|max:50|unique:products,sku',
            'price'           => 'required|numeric|min:0',
            'discount_price'  => 'nullable|numeric|min:0|lt:price',
            'stock_quantity'  => 'required|integer|min:0',
            'weight'          => 'nullable|numeric|min:0',
            'dimensions'      => 'nullable|string|max:255',
            'is_active'       => 'boolean',
            'is_featured'     => 'boolean',
            'category_id'     => 'required|exists:product_categories,id',
            'brand_id'        => 'nullable|exists:product_brands,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate Product Brand data.
     */
    public function validateProductBrand(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:product_brands,name',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate Product Category data.
     */
    public function validateProductCategory(array $data): array
    {
        $validator = Validator::make($data, [
            'name'      => 'required|string|max:255|unique:product_categories,name',
            'slug'      => 'nullable|string|max:255|unique:product_categories,slug',
            'parent_id' => 'nullable|exists:product_categories,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate Product Image data.
     */
    public function validateProductImage(array $data): array
    {
        $validator = Validator::make($data, [
            'product_id' => 'required|exists:products,id',
            'image_url'  => 'required|url',
            'is_primary' => 'boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate Product Tag data.
     */
    public function validateProductTag(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:product_tags,name',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate Product Variant data.
     */
    public function validateProductVariant(array $data): array
    {
        $validator = Validator::make($data, [
            'product_id'   => 'required|exists:products,id',
            'attribute'    => 'required|string|max:255',
            'value'        => 'required|string|max:255',
            'extra_price'  => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate Sub Category data.
     */
    public function validateSubCategory(array $data): array
    {
        $validator = Validator::make($data, [
            'category_id'  => 'required|exists:product_categories,id',
            'name'         => 'required|string|max:255|unique:sub_categories,name',
            'slug'         => 'nullable|string|max:255|unique:sub_categories,slug',
            'description'  => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}