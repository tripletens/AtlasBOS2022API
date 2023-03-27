<!DOCTYPE html>
<html>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">

<head>
    <title>Atlas Order Details</title>
</head>

<style>
    .item-img {
        width: 50px;
        height: 50px;
    }

    .vendor-logo {
        width: 40px;
        height: 20px;
    }

    .thead-custom {
        font-size: 12px;
    }

    .table-value-custom {
        font-size: 10px;
    }

    .com-logo {
        float: right;
        width: 20%;
        height: 50px;
    }

    .top-title {
        font-size: 20px;
    }

    .sub-top-title {
        font-size: 15px;
    }

    .dealer-name-title {
        font-size: 18px;
    }

</style>

<body>

    <div class="container-fluid">
        <div class="row">
            <div class="col-6">
                <h2 class="top-title">ATLAS {{ now()->year }} BOOKING PROGRAM</h2>
                <h2 class="dealer-name-title">Password Reset Code </h2>
                <h2 class="dealer-name">Here is your code - {{ $data['code'] }} </h2>
                <h2 class="dealer-name">Click on this link to reset your password - <a class="btn btn-link" target="_blank" href="{{ $data['reset_url'] . $data['code'] }}"> {{ $data['reset_url'] . $data['code'] }}</a> </h2>
                
            </div>
            <div class="mt-3">
                <img src="https://atlasbookingprogram.com/assets/new-atlas-logo.png" class="com-logo"
                    style="width: 200px; height: 100px" alt="">
            </div>
        </div>
    </div>

</body>

</html>
