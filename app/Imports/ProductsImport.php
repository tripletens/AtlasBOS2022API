<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\Products;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ProductsImport implements ToModel, WithStartRow {
    /**
    * @param Collection $collection
    */
    // public function collection( Collection $collection )
    // {
    //     //
    // }

    public function startRow(): int {
        return 2;
    }

    public function model( array $row ) {

        $pro = Products::where( 'xref', $row[ 5 ] )->get();

        ///return $pro;

        if ( $pro ) {
            $spec = $pro->spec_data;
            if ( $spec === null ) {
                ////$decoded = json_decode( $spec, true );
                $incoming = [
                    'booking' => $row[ 8 ],
                    'special' => $row[ 9 ],
                    'cond' => $row[ 10 ],
                    'type' => $row[ 11 ]
                ];
                $decoded[] = $incoming;
                $endcode = json_encode( $decoded );

                return  Products::where( 'xref', $row[ 5 ] )->update( [ 'spec_data' => $decoded ] );

            } else {
                $decoded = json_decode( $spec, true );
                $incoming = [
                    'booking' => $row[ 8 ],
                    'special' => $row[ 9 ],
                    'cond' => $row[ 10 ],
                    'type' => $row[ 11 ]
                ];
                $decoded[] = $incoming;
                $endcode = json_encode( $decoded );

                return  Products::where( 'xref', $row[ 5 ] )->update( [ 'spec_data' => $decoded ] );

            }

        } else {
            return new Products( [
                'atlas_id'     => $row[ 1 ],
                'img'    => $row[ 2 ],
                'vendor_logo' => $row[ 3 ],
                'vendor_name' => $row[ 4 ],
                'xref' => $row[ 5 ],
                'description' => $row[ 6 ],
                'um' => $row[ 7 ],
                'booking' => $row[ 8 ],
                'special' => $row[ 9 ],
                'cond' => $row[ 10 ],
                'type' => $row[ 11 ],
                'grouping' => $row[ 12 ],
                'full_desc' => $row[ 13 ]

            ] );
        }

    }
}
