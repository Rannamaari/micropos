<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;

class ReceiptProfileResolver
{
    /** @return array<string, bool|string|null> */
    public function resolve(Company $company, Branch $branch): array
    {
        return [
            'shop_name' => $branch->receipt_shop_name ?: $branch->name ?: $company->receipt_shop_name ?: $company->name,
            'tax_number' => $branch->receipt_tax_number ?: $company->tax_number,
            'gst_label' => $branch->receipt_gst_label ?: $company->receipt_gst_label ?: 'GST No.',
            'address' => $branch->address ?: $company->address,
            'city' => $branch->city ?: $company->city,
            'country' => $company->country,
            'phone' => $branch->phone ?: $company->phone,
            'header' => $branch->receipt_header ?: $company->receipt_header,
            'footer' => $branch->receipt_footer ?: $company->receipt_footer,
            'show_address' => $branch->receipt_show_address ?? $company->receipt_show_address ?? true,
            'show_phone' => $branch->receipt_show_phone ?? $company->receipt_show_phone ?? true,
            'branch_name' => $branch->name,
        ];
    }
}
