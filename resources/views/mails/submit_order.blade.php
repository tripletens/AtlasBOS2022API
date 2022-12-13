@extends('layouts.mail')

@section('content')

<div class="container-fluid">

    <span class="alert alert-info text-start">
    <!-- {"dealer":1,"cart":[{"id":32,"atlasId":"725-2",
        "desc":"AQUA MAGIC V LOW - WHITE","proImg":"https://m.media-amazon.com/images/I/61DMaLuSkIL._AC_SL1500_.jpg",
        "vendorImg":"https://www.carlogos.org/logo/Hyundai-logo-silver-2560x1440.png","quantity":"3","price":387}]} -->

        <h3>Your Order has been received successfully</h3> <br/>

        <p>Attached is your order in a PDF file</p>
    </span>
</div>

@endsection