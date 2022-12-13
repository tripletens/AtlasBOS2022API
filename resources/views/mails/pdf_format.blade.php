
<!DOCTYPE html>
<html>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">

<head>
    <title>Atlas Order Details</title>
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
                <h2 class="top-title">ATLAS 2022 BOOKING PROGRAM</h2>
                <h2 class="dealer-name">Dealer Name: {{ $dealer_name }}</h2>
                <h2 class="dealer-name">Dealer Account #: {{ $dealer_account_id }}</h2>
                <h2 class="dealer-name">Order Date: {{ $dealer_updated_at }} MST</h2>
            </div>
            <div class="mt-3">
                <img src="https://atlasbookingprogram.com/assets/new-atlas-logo.png" class="com-logo" alt="">
            </div>
        </div>
    </div>






    {{-- Appliance Table --}}
    @if (count($appliance) > 0)
        <div>
            <h5 class="top-title-table" style="">Appliances
            </h5>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                    <th class="thead-custom">Description</th>
                    <th class="thead-custom">Unit Price ($)</th>
                    <th class="thead-custom">Extended($)</th>
                </tr>
            </thead>

            <tbody>

                {{ $appliance_total = 0 }}
                @foreach ($appliance as $item)
                    {{ $appliance_total += floatval($item['price']) }}
                    <tr>
                        <td class="table-value-custom">
                            {{ $item['qty'] }}
                        </td>
                        <td class="table-value-custom">
                            {{ $item['atlas_id'] }}
                        </td>
                        {{-- <td>
                            <img src="{{ $item['vendor_img'] }}" class="vendor-logo" alt="">
                        </td> --}}
                        <td class="table-value-custom">
                            {{ $item['desc'] }}
                        </td>
                        <td class="table-value-custom">
                            ${{ number_format($item['unit_price'], 2) }}
                        </td>
                        <td class="table-value-custom">
                            ${{ number_format($item['price'], 2) }}
                        </td>
                    </tr>

                @endforeach

                <tr>
                    <td colspan="4">
                        <h5 class="each-total-cate-text" style="">
                            Total For
                            Appliance</h5>
                    </td>
                    <td>
                        <h5 class="each-total-text" style="">
                            ${{ number_format($appliance_total, 2) }}</h5>
                    </td>
                </tr>


            </tbody>
        </table>
    @endif

    {{-- Vent & Hardware --}}
    @if (count($vent) > 0)
        <div>
            <h5 class="top-title-table" style="">Vents & Hardware
            </h5>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                    <th class="thead-custom">Description</th>
                    <th class="thead-custom">Unit Price ($)</th>
                    <th class="thead-custom">Extended($)</th>
                </tr>
            </thead>

            <tbody>

                {{ $vent_total = 0 }}
                @foreach ($vent as $item)
                    {{ $vent_total += floatval($item['price']) }}
                    <tr>
                        <td class="table-value-custom">
                            {{ $item['qty'] }}
                        </td>
                        <td class="table-value-custom">
                            {{ $item['atlas_id'] }}
                        </td>

                        <td class="table-value-custom">
                            {{ $item['desc'] }}
                        </td>

                        <td class="table-value-custom">
                            ${{ number_format($item['unit_price'], 2) }}
                        </td>
                        <td class="table-value-custom">
                            ${{ number_format($item['price'], 2) }}
                        </td>
                    </tr>
                @endforeach

                <tr>
                    <td colspan="4">
                        <h5 class="each-total-cate-text" style="">
                            Total For
                            Vents & Hardwares</h5>
                    </td>
                    <td>
                        <h5 class="each-total-text" style="">
                            ${{ number_format($vent_total, 2) }}</h5>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif


    {{-- Electrical --}}
    @if (count($electrical) > 0)
        <div>
            <h5 class="top-title-table" style="">Electrical
            </h5>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                    {{-- <th class="thead-custom">Vendor</th> --}}
                    <th class="thead-custom">Description</th>
                    <th class="thead-custom">Unit Price ($)</th>
                    <th class="thead-custom">Extended($)</th>
                </tr>
            </thead>
            <tbody>
                {{ $electrical_total = 0 }}
                @foreach ($electrical as $item)
                    {{ $electrical_total += floatval($item['price']) }}
                    <tr>
                        <td class="table-value-custom">
                            {{ $item['qty'] }}
                        </td>
                        <td class="table-value-custom">
                            {{ $item['atlas_id'] }}
                        </td>
                        {{-- <td>
                            <img src="{{ $item['vendor_img'] }}" class="vendor-logo" alt="">
                        </td> --}}
                        <td class="table-value-custom">
                            {{ $item['desc'] }}
                        </td>
                        <td class="table-value-custom">
                            ${{ number_format($item['unit_price'], 2) }}
                        </td>
                        <td class="table-value-custom">
                            ${{ number_format($item['price'], 2) }}
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="4">
                        <h5 class="each-total-cate-text" style="">
                            Total For
                            Electrical</h5>
                    </td>
                    <td>
                        <h5 class="each-total-text" style="">
                            ${{ number_format($electrical_total, 2) }}</h5>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif




    {{-- Electronics --}}
    @if (count($electronics) > 0)
        <div>
            <h5 class="top-title-table" style="">Electronics
            </h5>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                    <th class="thead-custom">Description</th>
                    <th class="thead-custom">Unit Price ($)</th>
                    <th class="thead-custom">Extended($)</th>
                </tr>
            </thead>
            <tbody>
                {{ $electronics_total = 0 }}
                @foreach ($electronics as $item)
                    {{ $electronics_total += floatval($item['price']) }}
                    <tr>

                        <td class="table-value-custom">{{ $item['qty'] }}</td>
                        <td class="table-value-custom">{{ $item['atlas_id'] }}</td>
                        {{-- <td>
                            <img src="{{ $item['vendor_img'] }}" class="vendor-logo" alt="">
                        </td> --}}
                        <td class="table-value-custom">{{ $item['desc'] }}</td>

                        <td class="table-value-custom">${{ number_format($item['unit_price'], 2) }}
                        </td>
                        <td class="table-value-custom">
                            ${{ number_format($item['price'], 2) }}
                        </td>
                    </tr>

                @endforeach
                <tr>
                    <td colspan="4">
                        <h5 class="each-total-cate-text" style="">
                            Total For
                            Electronics</h5>
                    </td>
                    <td>
                        <h5 class="each-total-text" style="">
                            ${{ number_format($electronics_total, 2) }}</h5>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif




    {{-- Plumbing Table --}}
    @if (count($plumbing) > 0)
        <div>
            <h5 class="top-title-table" style="">Plumbing</h5>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                    {{-- <th class="thead-custom">Vendor</th> --}}
                    <th class="thead-custom">Description</th>
                    <th class="thead-custom">Unit Price ($)</th>
                    <th class="thead-custom">Extended($)</th>
                </tr>
            </thead>

            <tbody>

                {{ $plumbing_total = 0 }}
                @foreach ($plumbing as $item)
                    {{ $plumbing_total += floatval($item['price']) }}
                    <tr>
                        <td class="table-value-custom">{{ $item['qty'] }}</td>
                        <td class="table-value-custom">{{ $item['atlas_id'] }}</td>
                        <td class="table-value-custom">{{ $item['desc'] }}</td>
                        <td class="table-value-custom">${{ number_format($item['unit_price'], 2) }}
                        </td>
                        <td class="table-value-custom">
                            ${{ number_format($item['price'], 2) }}
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="4">
                        <h5 class="each-total-cate-text" style="">
                            Total For
                            Plumbing</h5>
                    </td>
                    <td>
                        <h5 class="each-total-text" style="">
                            ${{ number_format($plumbing_total, 2) }}</h5>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif



    {{-- Sealant --}}
    @if (count($sealant) > 0)
        <div>
            <h5 class="top-title-table" style="">Sealants & Cleaners</h5>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                    <th class="thead-custom">Description</th>
                    <th class="thead-custom">Unit Price ($)</th>
                    <th class="thead-custom">Extended($)</th>
                </tr>
            </thead>
            <tbody>
                {{ $sealant_total = 0 }}
                @foreach ($sealant as $item)
                    {{ $sealant_total += floatval($item['price']) }}
                    <tr>

                        <td class="table-value-custom">{{ $item['qty'] }}</td>
                        <td class="table-value-custom">{{ $item['atlas_id'] }}</td>
                        <td class="table-value-custom">{{ $item['desc'] }}</td>
                        <td class="table-value-custom">
                            ${{ number_format($item['unit_price'], 2) }}
                        </td>
                        <td class="table-value-custom">
                            ${{ number_format($item['price'], 2) }}
                        </td>
                    </tr>

                @endforeach
                <tr>
                    <td colspan="4">
                        <h5 class="each-total-cate-text" style="">
                            Total For
                            Sealants & Cleaners</h5>
                    </td>
                    <td>
                        <h5 class="each-total-text" style="">
                            ${{ number_format($sealant_total, 2) }}</h5>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif


    {{-- Accessories --}}
    @if (count($accessories) > 0)
        <div>
            <h5 class="top-title-table" style="">Accessories
            </h5>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                    {{-- <th class="thead-custom">Vendor</th> --}}
                    <th class="thead-custom">Description</th>
                    <th class="thead-custom">Unit Price ($)</th>
                    <th class="thead-custom">Extended($)</th>
                </tr>
            </thead>
            <tbody>
                {{ $accessories_total = 0 }}
                @foreach ($accessories as $item)
                    {{ $accessories_total += floatval($item['price']) }}
                    <tr>

                        <td class="table-value-custom">{{ $item['qty'] }}</td>
                        <td class="table-value-custom">{{ $item['atlas_id'] }}</td>
                        <td class="table-value-custom">{{ $item['desc'] }}</td>
                        <td class="table-value-custom">
                            ${{ number_format($item['unit_price'], 2) }}
                        </td>
                        <td class="table-value-custom">
                            ${{ number_format($item['price'], 2) }}
                        </td>
                    </tr>

                @endforeach
                <tr>
                    <td colspan="4">
                        <h5 class="each-total-cate-text" style="">
                            Total For
                            Accessories</h5>
                    </td>
                    <td>
                        <h5 class="each-total-text" style="">
                            ${{ number_format($accessories_total, 2) }}</h5>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif




    {{-- Towing accessories --}}
    @if (count($towing_accessories) > 0)
        <div>
            <h5 class="top-title-table" style="">Towing Accessories
            </h5>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                    <th class="thead-custom">Description</th>
                    <th class="thead-custom">Unit Price ($)</th>
                    <th class="thead-custom">Extended($)</th>
                </tr>
            </thead>
            <tbody>
                {{ $towing_accessories_total = 0 }}
                @foreach ($towing_accessories as $item)
                    {{ $towing_accessories_total += floatval($item['price']) }}
                    <tr>
                        <td class="table-value-custom">{{ $item['qty'] }}</td>
                        <td class="table-value-custom">{{ $item['atlas_id'] }}</td>
                        <td class="table-value-custom">{{ $item['desc'] }}</td>
                        <td class="table-value-custom">
                            ${{ number_format($item['unit_price'], 2) }}
                        </td>
                        <td class="table-value-custom">
                            ${{ number_format($item['price'], 2) }}
                        </td>
                    </tr>

                @endforeach
                <tr>
                    <td colspan="4">
                        <h5 class="each-total-cate-text" style="">
                            Total For
                            Towing Accessories</h5>
                    </td>
                    <td>
                        <h5 class="each-total-text" style="">
                            ${{ number_format($towing_accessories_total, 2) }}</h5>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif




    {{-- Towing Product --}}
    @if (count($towing_products) > 0)
        <div>
            <h5 class="top-title-table" style="">Towing Products
            </h5>
        </div>
        <table class="">
            <thead class="">
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                    <th class="thead-custom">Description</th>
                    <th class="thead-custom">Unit Price ($)</th>
                    <th class="thead-custom">Extended($)</th>
                </tr>
            </thead>
            <tbody>
                {{ $towing_products_total = 0 }}
                @foreach ($towing_products as $item)
                    {{ $towing_products_total += floatval($item['price']) }}
                    <tr>
                        <td class="table-value-custom">{{ $item['qty'] }}</td>
                        <td class="table-value-custom">{{ $item['atlas_id'] }}</td>
                        <td class="table-value-custom">{{ $item['desc'] }}</td>
                        <td class="table-value-custom">${{ number_format($item['unit_price'], 2) }}
                        </td>
                        <td class="table-value-custom">
                            ${{ number_format($item['price'], 2) }}
                        </td>
                    </tr>

                @endforeach

                <tr>
                    <td colspan="4">
                        <h5 class="each-total-cate-text" style="">
                            Total For
                            Towing Products</h5>
                    </td>
                    <td>
                        <h5 class="each-total-text" style="">
                            ${{ number_format($towing_products_total, 2) }}</h5>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif



    {{-- Propane --}}
    @if (count($propane) > 0)
        <div>
            <h5 class="top-title-table" style="">Propane
            </h5>
        </div>
        <table class="">
            <thead class="">
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                    <th class="thead-custom">Description</th>
                    <th class="thead-custom">Unit Price ($)</th>
                    <th class="thead-custom">Extended($)</th>
                </tr>
            </thead>
            <tbody>
                {{ $propane_total = 0 }}
                @foreach ($propane as $item)
                    {{ $propane_total += floatval($item['price']) }}
                    <tr>
                        <td class="table-value-custom">{{ $item['qty'] }}</td>
                        <td class="table-value-custom">{{ $item['atlas_id'] }}</td>
                        <td class="table-value-custom">{{ $item['desc'] }}</td>
                        <td class="table-value-custom">
                            ${{ number_format($item['unit_price'], 2) }}
                        </td>
                        <td class="table-value-custom">
                            ${{ number_format($item['price'], 2) }}
                        </td>
                    </tr>

                @endforeach
                <tr>
                    <td colspan="4">
                        <h5 class="each-total-cate-text" style="">
                            Total For
                            Propane</h5>
                    </td>
                    <td>
                        <h5 class="each-total-text" style="">
                            ${{ number_format($propane_total, 2) }}</h5>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif


    {{-- Outdoor --}}
    @if (count($outdoor) > 0)
        <div>
            <h5 class="top-title-table" style="">Outdoor Living</h5>
        </div>
        <table class="">
            <thead class="">
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                    <th class="thead-custom">Description</th>
                    <th class="thead-custom">Unit Price ($)</th>
                    <th class="thead-custom">Extended($)</th>
                </tr>
            </thead>
            <tbody>
                {{ $outdoor_total = 0 }}
                @foreach ($outdoor as $item)
                    {{ $outdoor_total += floatval($item['price']) }}
                    <tr>

                        <td class="table-value-custom">{{ $item['qty'] }}</td>
                        <td class="table-value-custom">{{ $item['atlas_id'] }}</td>
                        <td class="table-value-custom">{{ $item['desc'] }}</td>
                        <td class="table-value-custom">
                            ${{ number_format($item['unit_price'], 2) }}
                        </td>
                        <td class="table-value-custom">
                            ${{ number_format($item['price'], 2) }}
                        </td>
                    </tr>

                @endforeach
                <tr>
                    <td colspan="4">
                        <h5 class="each-total-cate-text" style="">
                            Total For
                            Outdoor Living</h5>
                    </td>
                    <td>
                        <h5 class="each-total-text" style="">
                            ${{ number_format($outdoor_total, 2) }}</h5>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif


    <div style="width: 100%; text-align: right; border: 1px solid black; margin-top: 20px">
        <h5 class="each-total-cate-text" style="display: inline-block; border-right: 1px solid black">Grand Total:
        </h5>
        <h5 class="each-total-text" style="display: inline-block; padding-right: 30px">
            ${{ number_format($grand_total, 2) }}
        </h5>
    </div>


    {{-- Catalougue Products Table --}}
    @if ($catalogue_data)
        <div style="margin-top: 30px">
            <h5 class="top-title-table" style="">Catalogue Products</h5>
        </div>
        <table class="">
            <thead class="">
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                </tr>
            </thead>
            <tbody>
                @if ($catalogue_data)
                    @foreach ($catalogue_data as $item)
                        <tr>
                            <td class="table-value-custom">{{ $item['qty'] }}</td>
                            <td class="table-value-custom">{{ $item['atlasId'] }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="2" class="table-value-custom" style="text-align: center">No Catalogue Item</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif




    {{-- Carded Products Table --}}
    @if ($carded_data)
        <div style="margin-top: 30px">
            <h5 class="top-title-table" style="">Carded Products</h5>
        </div>
        <table class="">
            <thead class="">
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                </tr>
            </thead>
            <tbody>
                @if ($carded_data)
                    @foreach ($carded_data as $item)
                        <tr>
                            <td class="table-value-custom">{{ $item['qty'] }}</td>
                            <td class="table-value-custom">{{ $item['atlasId'] }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="2" class="table-value-custom" style="text-align: center">No Carded Products</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif



    {{-- Carded Products Table --}}
    @if ($service_data)
        <div style="margin-top: 30px">
            <h5 class="top-title-table" style="">Service Parts</h5>
        </div>
        <table class="">
            <thead class="">
                <tr>
                    <th class="thead-custom">Quantity</th>
                    <th class="thead-custom">Atlas #</th>
                </tr>
            </thead>
            <tbody>
                @if ($service_data)
                    @foreach ($service_data as $item)
                        <tr>
                            <td class="table-value-custom">{{ $item['qty'] }}</td>
                            <td class="table-value-custom">{{ $item['atlasId'] }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="2" class="table-value-custom" style="text-align: center">No Service Parts</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif


    @if (count($outdoor) > 0 || count($propane) > 0 || count($towing_products) > 0 || count($towing_accessories) > 0 || count($accessories) > 0 || count($sealant) > 0 || count($plumbing) > 0 || count($electronics) > 0 || count($vent) > 0 || count($appliance) > 0)
        <div style="width: 100%; text-align: right; border: 1px solid black; margin-top: 20px">
            <h5 class="each-total-cate-text" style="display: inline-block; border-right: 1px solid black">Grand Total:
            </h5>
            <h5 class="each-total-text" style="display: inline-block; padding-right: 30px">
                ${{ number_format($grand_total, 2) }}
            </h5>
        </div>
    @endif

</body>

</html>
