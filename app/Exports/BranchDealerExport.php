<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class BranchDealerExport implements FromCollection
{
    protected $data;

    public function __construct($data)
    {
        $this->data = collect($data);
    }

    public function collection()
    {
        return $this->data->map(function ($item) {
            return [
                'ID' => $item['id'],
                'Full Name' => $item['full_name'],
                'Email' => $item['email'],
                'Account ID' => $item['account_id'],
                'Address' => $item['address'],
                'Company Name' => $item['company_name'],
                'Last Login' => $item['last_login'],
                'Placed Order Date' => $item['placed_order_date'],
                'Role Name' => $item['role_name'],
                'Created At' => $item['created_at'],
                'Updated At' => $item['updated_at'],
                'Total Carded Price' => $item['total_carded_price'],
                'Total Service Price' => $item['total_service_price'],
                'Total Catalogue Price' => $item['total_catalogue_price'],
                'Has Service Parts' => $item['has_service_parts'],
                'Total Catalogue Submitted Price' => $item['total_catalogue_submitted_price'],
                'Has Catalogue Products' => $item['has_catalogue_products'],
                'Has Carded Products' => $item['has_carded_products'],
                'Pending Total' => $item['pending_total'],
                'Submitted Total' => $item['submitted_total'],
            ];
        });
    }
}
