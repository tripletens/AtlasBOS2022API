<!DOCTYPE html>
<html>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">

<head>
    <title>Atlas Login Details</title>
</head>

<style>
    .com-logo {
        float: right;
        top: 150px;
        width: 20%;
        height: 50px;
    }

    .custom-btn {
        background-color: blueviolet;
        color: #ffffff;
        font-size: 12px;
        padding: 10px 20px;
        display: block;
        text-align: center;
        border: 0px;
        border-radius: 5px;

    }

</style>

<body>


    <div class="container-fluid">
        <div class="row">
            <div class="col-6">
                <h2 class="top-title">ATLAS VIRTUAL SHOW 2022</h2>
                <h3 class="sub-top-title">Login Details</h3>
                <h2 class="dealer-name">{{ $dealer_name }}</h2>
            </div>
            <div class="mt-3">
                <img src="https://atlas409.web.app/assets/new-atlas-logo.png" class="com-logo" alt="">
            </div>
        </div>
    </div>


    <h6>Email: <b>{{ $email }}</b></h6>
    <h6>Password: <b>{{ $password }}</b></h6>


    <a href="https://atlasbookingprogram.com/">
        <button style=" background-color: blueviolet;
        color: #ffffff;
        font-size: 12px;
        padding: 10px 20px;
        display: block;
        text-align: center;
        border: 0px;
        border-radius: 5px;">Click To Login</button>
    </a>





</body>

</html>
