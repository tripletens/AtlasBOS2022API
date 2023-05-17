@extends('layouts.mail')

@section('content')

<div class="container-fluid">
    <!-- {"dealer":1,"cart":[{"id":32,"atlasId":"725-2",
        "desc":"AQUA MAGIC V LOW - WHITE","proImg":"https://m.media-amazon.com/images/I/61DMaLuSkIL._AC_SL1500_.jpg",
        "vendorImg":"https://www.carlogos.org/logo/Hyundai-logo-silver-2560x1440.png","quantity":"3","price":387}]} -->

        <h3>Your Order has been received successfully</h3> <br/>

        Dear Valued Customer,
        <p>Your Booking order has been received.</p> 
        <p> Thank your for participating in this year's Booking Program. </p>
       
        <p>If you have any questions or concerns, </br>

        please contact your sales representative or local Atlas branch.</p>

        <p>Thank you for your support and we wish you a successful year.</p>

        <p>Attached is your order in a PDF file</p>
</div>

@endsection