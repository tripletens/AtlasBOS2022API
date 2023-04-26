
<!DOCTYPE html>
<html>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">

<head>
    <title>Atlas Pending Order Details</title>
</head>

<style>
    .item-img {
        width: 40px;
        height: 40px;
    }

    th {
        background-color: black;
        color: white;
        padding-left: 10px;
    }

    table,
    td,
    th {
        border: 1px solid black;
    }

    td {
        font-size: 10px;
        padding-left: 8px;

    }

    .table-value-custom {
        font-size: 10px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    .thead-custom {
        font-size: 13px;
    }



    .vendor-logo {
        width: 30px;
        height: 15px;
    }







    .com-logo {
        float: right;
        top: 150px;
        width: 30%;
        height: 70px;
    }

    .top-title {
        font-size: 20px;
    }

    .sub-top-title {
        font-size: 15px;
    }

    .dealer-name {
        font-size: 15px;
    }



    .table-custom {
        margin-bottom: 20px;
    }

    .each-total-text {
        font-weight: bolder;
        color: black;
        margin-bottom: 0px;
        font-size: 13px;
        padding-left: 5px;
    }

    .each-total-cate-text {
        text-align: right;
        font-weight: bolder;
        color: black;
        margin-bottom: 0px;
        font-size: 13px;
        padding-right: 5px;

    }

    .top-title-table {
        font-weight: bold;
        color: #ffffff;
        background-color: #115085;
        margin-bottom: 0px;
        padding-left: 10px;
        font-size: 13px;
        padding-top: 5px;
        padding-bottom: 5px;
    }

    .table>* {
        padding-bottom: 0px !important;
        padding-top: 0px !important;
    }

</style>

<body>


    <div class="container-fluid">
        <div class="row">
            <div class="col-6">
                <h2 class="top-title">ATLAS {{ now()->year }} BOOKING PROGRAM</h2>
                <h2 class="dealer-name">Dealer Name: {{ $dealer_details[0]['full_name'] }}</h2>
                <h2 class="dealer-name">Dealer Account #: {{ $dealer_details[0]['account_id'] }}</h2>
                {{--  <h2 class="dealer-name">Order Date: {{ $dealer_updated_at }} MST</h2>  --}}
            </div>
            <div class="mt-3">
                <img src="https://atlasbookingprogram.com/assets/new-atlas-logo.png" class="com-logo" alt="">
            </div>

        </div>
    </div>




    <div style="margin-top: 70px">
    {{-- Pending orders Table --}}
    @if ($cart_data && count($cart_data) > 0 )
        <div>
            <h5 class="top-title-table" style="">Pending Orders
            </h5>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                    <th class="thead-custom">Description</th>
                    <th class="thead-custom">Booking ($)</th>
                    <th class="thead-custom">Extended($)</th>
                </tr>
            </thead>

            <tbody>
                {{ $cart_total = 0 }}
                @foreach ($cart_data as $item)
                    {{ $cart_total += floatval($item['price'] * $item['qty']) }}
                    <tr>
                        <td class="table-value-custom">
                            {{ $item['qty'] }}
                        </td>
                        <td class="table-value-custom">
                            {{ $item['atlas_id'] }}
                        </td>
                        {{--  <td>
                            <img src="{{ $item['vendor_img'] }}" class="vendor-logo" alt="">
                        </td>  --}}
                        <td class="table-value-custom">
                            {{ $item['desc'] }}
                        </td>
                        <td class="table-value-custom">
                            ${{ number_format($item['unit_price'], 2) }}
                        </td>
                        <td class="table-value-custom">
                            ${{ number_format($item['price'] * $item['qty'], 2)  }}
                        </td>
                    </tr>

                @endforeach

                <tr>
                    <td colspan="4">
                        <h5 class="each-total-cate-text" style="">
                            Total For
                            Pending Orders</h5>
                    </td>
                    <td>
                        <h5 class="each-total-text" style="">
                            ${{ number_format($cart_total, 2) }}</h5>
                    </td>
                </tr>

            </tbody>
        </table>
    @endif


    {{--  <div style="width: 100%; text-align: right; border: 1px solid black; margin-top: 20px">
        <h5 class="each-total-cate-text" style="display: inline-block; border-right: 1px solid black">Grand Total:
        </h5>
        <h5 class="each-total-text" style="display: inline-block; padding-right: 30px">
            ${{ number_format($grand_total, 2) }}
        </h5>
    </div>  --}}


    {{-- Catalougue Products Table --}}
    @if ($catalogue_products)
        <div style="margin-top: 30px">
            <h5 class="top-title-table" style="">Catalogue Products</h5>
        </div>
        <table class="">
            <thead class="">
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                    <th class="thead-custom">Description #</th>
                    <th class="thead-custom">Unit Price ($)</th>
                    <th class="thead-custom">Total Price ($)</th>
                </tr>
            </thead>
            
            <tbody>
                @if ($catalogue_products)
                    {{ $cart_catalogue_total = 0 }}
                    @foreach ($catalogue_products as $item)
                        {{ $cart_catalogue_total += floatval($item->price * $item->qty) }}
                        <tr>
                            
                            <td class="table-value-custom">{{ $item->qty }}</td>
                            <td class="table-value-custom">{{ $item->atlasId }}</td>
                            <td class="table-value-custom">{{ property_exists($item,'description') ? $item->description : "N/A"}}</td>
                            <td class="table-value-custom">$ {{ number_format($item->price,2) }}</td>
                            <td class="table-value-custom">$ {{ number_format($item->total,2) }}</td>
                        </tr>
                    @endforeach

                    <tr>
                        <td colspan="4">
                            <h5 class="each-total-cate-text" style="">
                                Total For
                                Pending Catalogue Orders</h5>
                        </td>
                        <td>
                            <h5 class="each-total-text" style="">
                                ${{ number_format($cart_catalogue_total, 2) }}</h5>
                        </td>
                    </tr>
                @else
                    <tr>
                        <td colspan="2" class="table-value-custom" style="text-align: center">No Catalogue Item</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif

    {{-- Carded Products Table --}}
    @if ($carded_products)
        <div style="margin-top: 30px">
            <h5 class="top-title-table" style="">Carded Products</h5>
        </div>
        <table class="">
            <thead class="">
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                    <th class="thead-custom">Description #</th>
                    <th class="thead-custom">Unit Price ($)</th>
                    <th class="thead-custom">Total Price ($)</th>
                </tr>
            </thead>
            <tbody>
                @if ($carded_products)
                    {{ $cart_carded_total = 0 }}
                        
                    @foreach ($carded_products as $item)
                        {{ $cart_carded_total += floatval($item->price * $item->qty) }}
                        <tr>
                            <td class="table-value-custom">{{ $item->qty }}</td>
                            <td class="table-value-custom">{{ $item->atlasId }}</td>
                            <td class="table-value-custom">{{ property_exists($item,'description') ? $item->description : "N/A"}}</td>
                            <td class="table-value-custom">{{ "$" . number_format($item->price,2) }}</td>
                            <td class="table-value-custom">{{ "$" . number_format($item->total,2) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="4">
                            <h5 class="each-total-cate-text" style="">
                                Total For
                                Pending Carded Products Orders</h5>
                        </td>
                        <td>
                            <h5 class="each-total-text" style="">
                                ${{ number_format($cart_carded_total, 2) }}</h5>
                        </td>
                    </tr>
                @else
                    <tr>
                        <td colspan="2" class="table-value-custom" style="text-align: center">No Carded Products</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif

    {{-- Carded Products Table --}}
    @if ($service_part_products)
        <div style="margin-top: 30px">
            <h5 class="top-title-table" style="">Service Parts</h5>
        </div>
        <table class="">
            <thead class="">
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                    <th class="thead-custom">Description</th>
                    <th class="thead-custom">Unit Price ($)</th>
                    <th class="thead-custom">Total Price ($)</th>
                </tr>
            </thead>
            <tbody>
                @if ($service_part_products)
                    {{ $cart_service_part_total = 0 }}
                    @foreach ($service_part_products as $item)
                    {{ $cart_service_part_total += floatval($item->price * $item->qty) }}
                        <tr>
                            <td class="table-value-custom">{{ $item->qty ? $item->qty : "" }}</td>
                            <td class="table-value-custom">{{ $item->atlasId ? $item->atlasId : ""}}</td>
                            <td class="table-value-custom">{{ property_exists($item,'description') ? $item->description : "N/A"}}</td>
                            <td class="table-value-custom">{{ $item->price ? number_format($item->price,2) : ""}}</td>
                            <td class="table-value-custom">{{ $item->total ? number_format($item->total,2) : ""}}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="4">
                            <h5 class="each-total-cate-text" style="">
                                Total For
                                Pending Service Parts Orders</h5>
                        </td>
                        <td>
                            <h5 class="each-total-text" style="">
                                ${{ number_format($cart_service_part_total, 2) }}</h5>
                        </td>
                    </tr>
                @else
                    <tr>
                        <td colspan="2" class="table-value-custom" style="text-align: center">No Service Parts</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif


    {{-- @if (count($outdoor) > 0 || count($propane) > 0 || count($towing_products) > 0 || count($towing_accessories) > 0 || count($accessories) > 0 || count($sealant) > 0 || count($plumbing) > 0 || count($electronics) > 0 || count($vent) > 0 || count($appliance) > 0)
        <div style="width: 100%; text-align: right; border: 1px solid black; margin-top: 20px">
            <h5 class="each-total-cate-text" style="display: inline-block; border-right: 1px solid black">Grand Total:
            </h5>
            <h5 class="each-total-text" style="display: inline-block; padding-right: 30px">
                ${{ number_format($grand_total, 2) }}
            </h5>
        </div>
    @endif --}}

</div>

</body>

</html>
