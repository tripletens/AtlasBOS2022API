@extends('layouts.pdf')

@section('content')

<div style="background:white;">
    <table style="width:700px;">
        @foreach($cart as $key => $item)
        <!-- <th style="color:white;text-align:center;padding:10px;  width:700px;margin-right:50px;"> 
            <h2 style="color:white;background:green;">{{$key}}</h2>
        </th> -->
        <tr >
            <td  style="background:green; color:white; height:40px;padding:10px;text-align:center;color:white;margin-top:70px; background:green;margin-bottom:50px;"> {{ucwords($key)}}</td> 

            <br/> <br/>
        </tr>


        @endforeach
    </table>
</div>

@endsection