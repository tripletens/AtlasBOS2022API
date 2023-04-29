<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetEmailCode;
use Illuminate\Http\Request;
use App\Models\Dealer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Products;
use Illuminate\Support\Facades\Storage;
use App\Models\DealerCart;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubmitOrderMail;
use App\Models\Orders;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Barryvdh\DomPDF\Facade as PDF;

use App\Models\Promotional_ads;
use App\Models\Catalogue_Order;
use App\Models\Category;
use App\Models\AtlasLoginLog;

use App\Models\CardedProducts;

use App\Models\ServiceParts;
use App\Models\Cart;
use App\Models\ExtraProducts;
use App\Models\ResetPassword;
use Illuminate\Support\Facades\Hash;

set_time_limit(2500000000000000);

class DealerController extends Controller
{
    //

    //// DB::raw(“date($date)”)

    public function __construct()
    {
        set_time_limit(2500000000000000);

        $this->middleware('auth:api', [
            'except' => [
                'login',
                'register',
                'test',
                'reset_password_send_code_email',
                'reset_dealer_password',
                'reset_password_verify_code_email',
            ],
        ]);

        $this->result = (object) [
            'status' => false,
            'status_code' => 200,
            'message' => null,
            'data' => (object) null,
            'token' => null,
            'debug' => null,
        ];
    }

    public function dealer_de()
    {
        return 'hello world';
    }

    public function recent_item_in_cart($id)
    {
        $cart = Cart::join(
            'atlas_products',
            'cart.pro_id',
            '=',
            'atlas_products.id'
        )
            ->select('cart.*', 'atlas_products.type', 'atlas_products.grouping')
            ->where('cart.dealer', '=', $id)
            ->where('cart.status', '=', '0')
            ->orderBy('cart.created_at', 'desc')
            ->take(10)
            ->get();
        foreach ($cart as $value) {
            $spec_data = $value->spec_data
                ? json_decode($value->spec_data)
                : [];
            $qty = intval($value->qty);
            $unit_price = floatval($value->unit_price);

            $value->spec_data = $spec_data;
            $value->qty = $qty;
            $value->unit_price = $unit_price;
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $cart;
        $this->result->message = 'User cart items';

        return response()->json($this->result,200);
    }

    public function get_user_cart($id)
    {
        $cart = Cart::join(
            'atlas_products',
            'cart.pro_id',
            '=',
            'atlas_products.id'
        )
            ->select('cart.*', 'atlas_products.type', 'atlas_products.grouping')

            ->where('cart.dealer', '=', $id)
            ->where('cart.status', '=', '0')
            ->get();
        foreach ($cart as $value) {
            $spec_data = $value->spec_data
                ? json_decode($value->spec_data)
                : [];
            $qty = intval($value->qty);
            $unit_price = floatval($value->unit_price);

            $value->spec_data = $spec_data;
            $value->qty = $qty;
            $value->unit_price = $unit_price;
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $cart;
        $this->result->message = 'User cart items';

        return response()->json($this->result);
    }

    public function remove_item_cart($dealer, $id, $grouping)
    {
        $item = Cart::query()
            ->where('dealer', $dealer)
            ->where('id', $id)
            ->get();

        $related = Cart::where('dealer', $dealer)
            ->where('grouping', $grouping)
            ->where('id', '!=', $id)
            ->get();
        DB::table('cart')
            ->where('dealer', $dealer)
            ->where('id', $id)
            ->delete();

        if ($related) {
            $quantity = 0;
            $total_cond = 0;
            $check_qualified_assorted = false;

            foreach ($related as $value) {
                //// $extra_data = json_decode($value->spec_data, true);
                $extra_data = json_decode($value->spec_data, true);
                $type = strtolower($extra_data[0]['type']);
                $quantity += intval($value->qty);
                $total_cond = intval($extra_data[0]['cond']);
            }

            foreach ($related as $value) {
                $extra_data = json_decode($value->spec_data, true);
                $type = strtolower($extra_data[0]['type']);
                $cond = intval($extra_data[0]['cond']);

                if ($type == 'assorted') {
                    if ($quantity >= $cond) {
                        /// return "we are here";
                        $check_qualified_assorted = true;
                        // return $related;
                    } else {
                    }
                } else {
                }
            }

            if ($check_qualified_assorted) {
                foreach ($related as $value) {
                    $pro_id = $value->id;
                    $qty = $value->qty;
                    $extra_data = json_decode($value->spec_data, true);
                    $type = strtolower($extra_data[0]['type']);
                    $special = intval($extra_data[0]['special']);
                    $cond = intval($extra_data[0]['cond']);
                    $new_price = $special * $qty;

                    $update = Cart::where('dealer', $dealer)
                        ->where('id', $id)
                        ->update([
                            'price' => $new_price,
                            'unit_price' => $special,
                        ]);

                    if ($update) {
                        return 'true';
                    } else {
                        return 'false';
                    }
                }
            } else {
                foreach ($related as $value) {
                    $pro_id = $value->id;
                    $booking = intval($value->booking);
                    $qty = $value->qty;
                    $new_price = $booking * $qty;
                    $update = Cart::where('dealer', $dealer)
                        ->where('id', $pro_id)
                        ->update([
                            'price' => $new_price,
                            'unit_price' => $booking,
                        ]);
                }
            }
        } else {
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = [];
        $this->result->message = 'Item has been removed from the cart';
        return response()->json($this->result);
    }

    public function edit_user_cart_item($dealer, $id, $qty, $price, $unit_price)
    {
        if (intval($qty) > 0) {
            $update = Cart::where('dealer', $dealer)
                ->where('id', $id)
                ->update([
                    'price' => $price,
                    'unit_price' => $unit_price,
                    'qty' => $qty,
                ]);
            if ($update) {
                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->data = [];
                $this->result->message = 'Item has been updated from the cart';
                return response()->json($this->result);
            } else {
                $this->result->status = false;
                $this->result->status_code = 200;
                $this->result->data = [];
                $this->result->message = 'Error Updating cart';
                return response()->json($this->result);
            }
        }
    }

    // public function edit_user_cart_item($dealer, $id, $qty, $grouping){
    //     $item = Cart::query()->where('dealer', $dealer)->where('id', $id)->get();
    //    $related = Cart::where('dealer', $dealer)->where('grouping', $grouping)->get();
    //     if($related){
    //         $quantity = $qty;
    //         $total_cond = 0;
    //         $check_qualified_assorted = false;
    //         foreach ($related as $value) {
    //             if($value->id == $id){
    //                 continue;
    //             }
    //             $extra_data = json_decode($value->spec_data, true);
    //             $type = strtolower($extra_data[0]['type']);
    //             $quantity += intval($value->qty);
    //             $total_cond = intval($extra_data[0]['cond']);
    //         }

    //         foreach ($related as $value) {
    //             $extra_data = json_decode($value->spec_data, true);
    //             $type = strtolower($extra_data[0]['type']);
    //             $cond = intval($extra_data[0]['cond']);

    //             if($type == 'assorted'){
    //                 if($quantity >= $cond){
    //                     $check_qualified_assorted = true;
    //                 }else{

    //                 }
    //             }else{

    //             }
    //         }

    //         if($check_qualified_assorted){
    //             foreach ($related as $value) {
    //                 $pro_id = $value->id;
    //                 $qty = $value->qty;
    //                 $extra_data = json_decode($value->spec_data, true);
    //                 $type = strtolower($extra_data[0]['type']);
    //                 $special = intval($extra_data[0]['special']);
    //                 $cond = intval($extra_data[0]['cond']);
    //                 $new_price = $special * $qty;

    //                 $update = Cart::where( 'dealer', $dealer)->where('id', $id)->update( [ 'price' => $new_price, 'unit_price' =>  $special, 'qty' => $qty] );

    //                 if($update){
    //                     return "true";
    //                 }else{
    //                     return 'false';
    //                 }

    //             }

    //         }else{
    //             foreach ($related as $value) {
    //                 $pro_id = $value->id;
    //                 $booking = intval($value->booking);
    //                 $qty = $value->qty;
    //                 $new_price = $booking * $qty;
    //                 $update = Cart::where( 'dealer', $dealer)->where('id', $pro_id)->update( [ 'price' => $new_price, 'unit_price' =>  $booking] );

    //             }
    //         }

    //     }else{

    //     }

    //     $this->result->status = true;
    //     $this->result->status_code = 200;
    //     $this->result->data =  [];
    //     $this->result->message = 'Item has been removed from the cart';
    //     return response()->json($this->result);
    // }

    public function send_order_to_mail($id)
    {
        $categories = Category::all();
        $plumbing = [];
        $vent = [];
        $electrical = [];
        $electronics = [];
        $propane = [];
        $accessories = [];
        $outdoor = [];
        $sealant = [];
        $appliance = [];
        $towing_products = [];
        $towing_accessories = [];
        $catalogue_data = [];
        $carded_data = [];
        $service_data = [];

        $dealer_d = Dealer::where('id', $id)
            ->get()
            ->first();
        $account_id = $dealer_d->account_id;
        $catalogue_order = Catalogue_Order::where('dealer', $account_id)
            ->get()
            ->first();
        $carded_products = CardedProducts::where('dealer', $account_id)
            ->get()
            ->first();
        $service_products = ServiceParts::where('dealer', $account_id)
            ->get()
            ->first();

        if ($catalogue_order) {
            $catalogue_order = json_decode($catalogue_order['data'], true);
        } else {
        }

        if ($carded_products) {
            $carded_products = json_decode($carded_products['data'], true);
        } else {
        }

        if ($service_products) {
            $service_products = json_decode($service_products['data'], true);
        } else {
        }

        $grand_total = 0;
        $cart = Cart::where('dealer', $id)
            ->get()
            ->toArray();
        foreach ($cart as $value) {
            $spea_dat = json_decode($value['spec_data'], true);
            $value['spec_data'] = $spea_dat;

            $grand_total += floatval($value['price']);

            if ($value['category'] == 'plumbing') {
                array_push($plumbing, $value);
            }

            if (
                $value['category'] == 'vents and hardware' ||
                $value['category'] == 'vents'
            ) {
                array_push($vent, $value);
            }

            if ($value['category'] == 'electrical') {
                array_push($electrical, $value);
            }

            if ($value['category'] == 'electronics') {
                array_push($electronics, $value);
            }

            if ($value['category'] == 'propane') {
                array_push($propane, $value);
            }

            if ($value['category'] == 'accessories') {
                array_push($accessories, $value);
            }

            if (
                $value['category'] == 'towing accessories' ||
                $value['category'] == 'towing'
            ) {
                array_push($towing_accessories, $value);
            }

            if (
                $value['category'] == 'outdoor living' ||
                $value['category'] == 'outdoor'
            ) {
                array_push($outdoor, $value);
            }

            if (
                $value['category'] == 'sealant and cleaners' ||
                $value['category'] == 'sealant'
            ) {
                array_push($sealant, $value);
            }

            if (
                $value['category'] == 'appliances' ||
                $value['category'] == 'appliance'
            ) {
                array_push($appliance, $value);
            }

            if (
                $value['category'] == 'towing products' ||
                $value['category'] == 'awning'
            ) {
                array_push($towing_products, $value);
            }
        }

        $data['cart'] = $cart;
        $data['vent'] = $vent;
        $data['electrical'] = $electrical;
        $data['electronics'] = $electronics;
        $data['propane'] = $propane;
        $data['accessories'] = $accessories;
        $data['outdoor'] = $outdoor;
        $data['sealant'] = $sealant;
        $data['appliance'] = $appliance;
        $data['plumbing'] = $plumbing;
        $data['towing_accessories'] = $towing_accessories;
        $data['towing_products'] = $towing_products;
        $data['catalogue_data'] = $catalogue_order;
        $data['carded_data'] = $carded_products;
        $data['service_data'] = $service_products;

        $data['grand_total'] = $grand_total;

        $dealerId = $id;
        $dealer_details = Dealer::where('id', $dealerId)->get();
        $dealer_name =
            $dealer_details[0]->first_name .
            ' ' .
            $dealer_details[0]->last_name;
        $dealer_email = $dealer_details[0]->email;
        $dealer_account_id = $dealer_details[0]->account_id;
        $dealer_updated_at = $dealer_details[0]->updated_at;

        $myData = [];

        $data['dealer_updated_at'] = $dealer_updated_at;

        $data['dealer_account_id'] = $dealer_account_id;
        $data['email'] = $dealer_email;
        $data['dealer_name'] = $dealer_name;
        $data['title'] = 'Atlas Order Details';
        $data['order_file_name'] =
            'atlas-order' . rand(6, 100000000000000) . '.pdf';

        $pdf = PDF::loadView('mails.pdf_format', $data);

        Mail::send('mails.send_html', $data, function ($message) use (
            $data,
            $pdf
        ) {
            $message
                ->to($data['email'])
                ->subject($data['title'])
                ->attachData($pdf->output(), $data['order_file_name']);
        });

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Email has been sent';
        return response()->json($this->result);
    }

    public function send_order_mail($id)
    {
        $categories = Category::all();
        $plumbing = [];
        $vent = [];
        $electrical = [];
        $electronics = [];
        $propane = [];
        $accessories = [];
        $outdoor = [];
        $sealant = [];
        $appliance = [];
        $towing_products = [];
        $towing_accessories = [];
        $catalogue_data = [];
        $carded_data = [];
        $service_data = [];

        $dealer_d = Dealer::where('id', $id)
            ->get()
            ->first();
        $account_id = $dealer_d->account_id;
        $catalogue_order = Catalogue_Order::where('dealer', $account_id)
            ->get()
            ->first();
        $carded_products = CardedProducts::where('dealer', $account_id)
            ->get()
            ->first();
        $service_products = ServiceParts::where('dealer', $account_id)
            ->get()
            ->first();

        if ($catalogue_order) {
            $catalogue_order = json_decode($catalogue_order['data'], true);
        } else {
            $catalogue_order = [];
        }

        if ($carded_products) {
            $carded_products = json_decode($carded_products['data'], true);
        } else {
            $carded_products = [];
        }

        if ($service_products) {
            $service_products = json_decode($service_products['data'], true);
        } else {
            $service_products = [];
        }

        $grand_total = 0;
        $cart = Cart::where('dealer', $id)
            ->get()
            ->toArray();
        foreach ($cart as $value) {
            $spea_dat = json_decode($value['spec_data'], true);
            $value['spec_data'] = $spea_dat;

            $grand_total += floatval($value['price']);

            if ($value['category'] == 'plumbing') {
                array_push($plumbing, $value);
            }

            if (
                $value['category'] == 'vents and hardware' ||
                $value['category'] == 'vents'
            ) {
                array_push($vent, $value);
            }

            if ($value['category'] == 'electrical') {
                array_push($electrical, $value);
            }

            if ($value['category'] == 'electronics') {
                array_push($electronics, $value);
            }

            if ($value['category'] == 'propane') {
                array_push($propane, $value);
            }

            if ($value['category'] == 'accessories') {
                array_push($accessories, $value);
            }

            if (
                $value['category'] == 'towing accessories' ||
                $value['category'] == 'towing'
            ) {
                array_push($towing_accessories, $value);
            }

            if (
                $value['category'] == 'outdoor living' ||
                $value['category'] == 'outdoor'
            ) {
                array_push($outdoor, $value);
            }

            if (
                $value['category'] == 'sealant and cleaners' ||
                $value['category'] == 'sealant'
            ) {
                array_push($sealant, $value);
            }

            if (
                $value['category'] == 'appliances' ||
                $value['category'] == 'appliance'
            ) {
                array_push($appliance, $value);
            }

            if (
                $value['category'] == 'towing products' ||
                $value['category'] == 'awning'
            ) {
                array_push($towing_products, $value);
            }
        }

        $data['cart'] = $cart;
        $data['vent'] = $vent;
        $data['electrical'] = $electrical;
        $data['electronics'] = $electronics;
        $data['propane'] = $propane;
        $data['accessories'] = $accessories;
        $data['outdoor'] = $outdoor;
        $data['sealant'] = $sealant;
        $data['appliance'] = $appliance;
        $data['plumbing'] = $plumbing;
        $data['towing_accessories'] = $towing_accessories;
        $data['towing_products'] = $towing_products;
        $data['catalogue_data'] = $catalogue_order;
        $data['carded_data'] = $carded_products;
        $data['service_data'] = $service_products;

        $data['grand_total'] = $grand_total;

        $dealerId = $id;
        $dealer_details = Dealer::where('id', $dealerId)->get();
        $dealer_name =
            $dealer_details[0]->first_name .
            ' ' .
            $dealer_details[0]->last_name;
        $dealer_email = $dealer_details[0]->email;
        $dealer_account_id = $dealer_details[0]->account_id;
        $dealer_updated_at = $dealer_details[0]->updated_at;

        $myData = [];

        $data['dealer_updated_at'] = $dealer_updated_at;
        $data['dealer_account_id'] = $dealer_account_id;
        $data['email'] = $dealer_email;
        $data['dealer_name'] = $dealer_name;
        $data['title'] = 'Atlas Order Details';
        $data['order_file_name'] =
            'atlas-order' . rand(6, 100000000000000) . '.pdf';

        $pdf = PDF::loadView('mails.pdf_format', $data);

        Mail::send('mails.send_html', $data, function ($message) use (
            $data,
            $pdf
        ) {
            $message
                ->to($data['email'])
                ->cc('orders@atlastrailer.com')
                ->subject($data['title'])
                ->attachData($pdf->output(), $data['order_file_name']);

            // $message->to("orders@atlastrailer.com")
            //     ->subject("ATLAS BOOKING PROGRAM DEALER'S ORDER")
            //     ->attachData($pdf->output(), $data['order_file_name']);
        });

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Email has been sent';
        return response()->json($this->result);
    }

    public function download_pdf($id)
    {
        $categories = Category::all();
        $plumbing = [];
        $vent = [];
        $electrical = [];
        $electronics = [];
        $propane = [];
        $accessories = [];
        $outdoor = [];
        $sealant = [];
        $appliance = [];
        $towing_products = [];
        $towing_accessories = [];
        $catalogue_data = [];
        $carded_data = [];
        $service_data = [];

        $dealer_d = Dealer::where('id', $id)
            ->get()
            ->first();
        $account_id = $dealer_d->account_id;
        $catalogue_order = Catalogue_Order::where('dealer', $account_id)
            ->get()
            ->first();
        $carded_products = CardedProducts::where('dealer', $account_id)
            ->get()
            ->first();
        $service_products = ServiceParts::where('dealer', $account_id)
            ->get()
            ->first();

        if ($catalogue_order) {
            $catalogue_order = json_decode($catalogue_order['data'], true);
        } else {
        }

        if ($carded_products) {
            $carded_products = json_decode($carded_products['data'], true);
        } else {
        }

        if ($service_products) {
            $service_products = json_decode($service_products['data'], true);
        } else {
        }

        $grand_total = 0;
        $cart = Cart::where('dealer', $id)
            ->get()
            ->toArray();
        foreach ($cart as $value) {
            $spea_dat = json_decode($value['spec_data'], true);
            $value['spec_data'] = $spea_dat;

            $grand_total += floatval($value['price']);

            if ($value['category'] == 'plumbing') {
                array_push($plumbing, $value);
            }

            if (
                $value['category'] == 'vents and hardware' ||
                $value['category'] == 'vents'
            ) {
                array_push($vent, $value);
            }

            if ($value['category'] == 'electrical') {
                array_push($electrical, $value);
            }

            if ($value['category'] == 'electronics') {
                array_push($electronics, $value);
            }

            if ($value['category'] == 'propane') {
                array_push($propane, $value);
            }

            if ($value['category'] == 'accessories') {
                array_push($accessories, $value);
            }

            if (
                $value['category'] == 'towing accessories' ||
                $value['category'] == 'towing'
            ) {
                array_push($towing_accessories, $value);
            }

            if (
                $value['category'] == 'outdoor living' ||
                $value['category'] == 'outdoor'
            ) {
                array_push($outdoor, $value);
            }

            if (
                $value['category'] == 'sealant and cleaners' ||
                $value['category'] == 'sealant'
            ) {
                array_push($sealant, $value);
            }

            if (
                $value['category'] == 'appliances' ||
                $value['category'] == 'appliance'
            ) {
                array_push($appliance, $value);
            }

            if (
                $value['category'] == 'towing products' ||
                $value['category'] == 'awning'
            ) {
                array_push($towing_products, $value);
            }
        }

        $data['cart'] = $cart;
        $data['vent'] = $vent;
        $data['electrical'] = $electrical;
        $data['electronics'] = $electronics;
        $data['propane'] = $propane;
        $data['accessories'] = $accessories;
        $data['outdoor'] = $outdoor;
        $data['sealant'] = $sealant;
        $data['appliance'] = $appliance;
        $data['plumbing'] = $plumbing;
        $data['towing_accessories'] = $towing_accessories;
        $data['towing_products'] = $towing_products;
        $data['catalogue_data'] = $catalogue_order;
        $data['carded_data'] = $carded_products;
        $data['service_data'] = $service_products;

        $data['grand_total'] = $grand_total;

        $dealerId = $id;
        $dealer_details = Dealer::where('id', $dealerId)->get();
        $dealer_name =
            $dealer_details[0]->first_name .
            ' ' .
            $dealer_details[0]->last_name;
        $dealer_email = $dealer_details[0]->email;
        $dealer_account_id = $dealer_details[0]->account_id;
        $dealer_updated_at = $dealer_details[0]->updated_at;

        $myData = [];

        $data['dealer_updated_at'] = $dealer_updated_at;

        $data['dealer_account_id'] = $dealer_account_id;
        $data['email'] = $dealer_email;
        $data['dealer_name'] = $dealer_name;
        $data['title'] = 'Atlas Order Details';
        $data['order_file_name'] =
            'atlas-order' . rand(6, 100000000000000) . '.pdf';

        $pdf = PDF::loadView('mails.pdf_format', $data);

        // download PDF file with download method
        $order_pdf = $pdf->download('pdf_file.pdf');

        $bb = base64_encode($order_pdf);

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data->pdf = $bb;
        $this->result->data->dealer = $dealer_name;

        $this->result->message = 'Email has been sent';
        return response()->json($this->result);
    }

    // get all dealers with pending orders
    public function fetch_dealer_with_pending_order_dealer($dealer_id)
    {
        //fetch all pending orders of a dealer with dealer_id
        $dealer_pending_orders = DB::table('cart')
            ->where('dealer', $dealer_id)
            ->where('status', '0')
            ->get();

        if (!$dealer_pending_orders) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->data = [];
            $this->result->message = 'Sorry dealer doesnt exist or deactivated';
            return response()->json($this->result);
        } else {

            foreach ($dealer_pending_orders as $item) {
                $item->spec_data = json_decode($item->spec_data);
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $dealer_pending_orders ? $dealer_pending_orders : [];
            $this->result->message =
                'Dealer pending orders fetched successfully';
            return response()->json($this->result);
        }
    }

    // get all the pending orders by pdf from dealer_id
    public function download_pending_order_pdf($dealer_id)
    {
        // get the dealer details

        $dealer_details = Dealer::where('id', $dealer_id)
            ->where('status', 1)
            ->get();

        if (!$dealer_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Dealer could not be found';
            return response()->json($this->result);
        }

        // else get all the items in cart for the dealer

        $cart_data = Cart::where('dealer', $dealer_id)
            ->where('status', 0)
            ->get();

        // get all the CP, CD AND SP PRODUCTS

        $carded_products = [];
        $catalogue_products = [];
        $service_part_products = [];

        foreach ($cart_data as $record) {
            $record['spec_data'] = json_decode($record['spec_data']);

            if (!is_null($record['carded_data'])) {
                $record['carded_data'] = json_decode($record['carded_data']);
                array_push($carded_products, $record);
            }

            if (!is_null($record['service_data'])) {
                $record['service_data'] = json_decode($record['service_data']);
                array_push($service_part_products, $record);
            }

            if (!is_null($record['catalogue_data'])) {
                $record['catalogue_data'] = json_decode(
                    $record['catalogue_data']
                );
                array_push($catalogue_products, $record);
            }
        }

        // foreach($cart_data as $item){
        //     $item['spec_data'] = json_decode($item['spec_data'],true);
        // }

        $data = [
            'cart_data' => $cart_data,
            'dealer_details' => $dealer_details,
            'carded_products' => $carded_products,
            'catalogue_products' => $catalogue_products,
            'service_part_products' => $service_part_products,
        ];

        // return $data;

        $pdf = PDF::loadView('mails.pending_orders_format', $data);

        // // download PDF file with download method
        $order_pdf = $pdf->download('pending_order_pdf_file.pdf');

        // return $order_pdf;

        $bb = base64_encode($order_pdf);

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data->pdf = $bb;
        $this->result->data->dealer = $dealer_details[0]->full_name;

        // // $this->result->message = 'Email has been sent';
        return response()->json($this->result);
    }

    public function get_dealer_order_summary($id)
    {
        $cart = Cart::where('dealer', '=', $id)
            ->where('status', '1')
            ->orderBy('xref', 'asc')
            ->get();

        if ($cart) {
            foreach ($cart as $value) {
                $spec_data = $value->spec_data
                    ? json_decode($value->spec_data)
                    : [];
                $qty = intval($value->qty);
                $unit_price = floatval($value->unit_price);

                $value->spec_data = $spec_data;
                $value->qty = $qty;
                $value->unit_price = $unit_price;
            }
        } else {
            $cart = [];
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $cart;
        $this->result->message = 'User cart items';

        return response()->json($this->result);
    }

    public function store_user_cart(Request $request)
    {
        // lets get the items from the array
        $product_array = $request->input('product_array');
        $dealer = $request->input('dealer');

        $added_item = 0;
        $in_cart = '';

        if (count(json_decode($product_array)) > 0 && $product_array) {
            $decode_product_array = json_decode($product_array);

            foreach ($decode_product_array as $product) {
                // update to the db

                if (
                    !Cart::where('dealer', $dealer)
                        ->where('atlas_id', $product->atlasId)
                        ->exists()
                ) {
                    if (intval($product->quantity) > 0) {
                        $added_item++;
                        $create_carded_product = Cart::create([
                            'dealer' => $dealer,
                            'atlas_id' => $product->atlasId,
                            'desc' => $product->desc,
                            'pro_img' => $product->proImg,
                            'vendor_img' => $product->vendorImg,
                            'qty' => $product->quantity,
                            'price' => $product->price,
                            'unit_price' => $product->unitPrice,
                            'spec_data' => json_encode($product->spec_data),
                            'grouping' => $product->grouping,
                            'type' => $product->type,
                            'xref' => $product->xref,
                            'pro_id' => $product->id,
                            'booking' => $product->booking,
                            'category' => $product->category,
                            'um' => $product->um,
                        ]);
                    }
                } else {
                    $in_cart .= $product->atlasId . ', ';
                }
            }
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data->added = $added_item;
        $this->result->data->in_cart = $in_cart;

        // $this->result->message = 'Item Already Added to the cart';
        return response()->json($this->result);

        // if (intval($qty) > 0) {
        //     $check = Cart::where('dealer', '=', $dealer)
        //         ->where('atlas_id', '=', $atlas_id)
        //         ->get()
        //         ->toArray();
        //     if (empty($check)) {
        //         $create_carded_product = Cart::create([
        //             'dealer' => $dealer,
        //             'pro_id' => $pro_id,
        //             'atlas_id' => $atlas_id,
        //             'qty' => $qty,
        //             'price' => $price,
        //             'unit_price' => $unit_price,
        //             'desc' => $desc,
        //             'pro_img' => $pro_img,
        //             'vendor_img' => $vendor_img,
        //             'spec_data' => json_encode($spec),
        //             'grouping' => $grouping,
        //             'booking' => $booking,
        //             'category' => $category,
        //             'um' => $um,
        //             'xref' => $xref,
        //         ]);

        //         $this->result->status = true;
        //         $this->result->status_code = 200;
        //         $this->result->message = 'Carded Product created successfully';
        //     } else {
        //         $this->result->status = true;
        //         $this->result->status_code = 200;
        //         $this->result->message = 'Item Already Added to the cart';
        //     }

        //     return response()->json($this->result);
        // }
    }

    public function edit_user_cart(Request $request)
    {
        // `dealer`, `pro_id`, `atlas_id`, `qty`, `price`, `unit_price`, `status`,
        // `description`, `pro_img`, `vendor_img`, `spec_data`, `grouping`, `booking`, `category`
        $validator = Validator::make($request->all(), [
            'dealer' => 'required|max:255',
            'pro_id' => 'required',
            'atlas_id' => 'required',
            'qty' => 'required',
            'price' => 'required',
            'unit_price' => 'required',
            'description' => 'required',
            'pro_img' => 'required|mimes:jpg,bmp,png|max:2048',
            'vendor_img' => 'required|mimes:jpg,bmp,png|max:2048',
            'spec_data' => 'required',
            'grouping' => 'required',
            'booking' => 'required',
            'category' => 'required',
        ]);

        if ($validator->fails()) {
            $response['response'] = $validator->messages();
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = $response;

            return response()->json($this->result);
        } else {
            $pro_id = $request->input('pro_id');
            $atlas_id = $request->input('atlas_id');
            $qty = $request->input('qty');
            $unit_price = $request->input('unit_price');
            $dealer = $request->input('dealer');
            $price = $request->input('price');

            $description = $request->input('description');
            $pro_img = $request->input('pro_img');
            $vendor_img = $request->input('vendor_img');
            $spec_data = $request->input('spec_data');
            $grouping = $request->input('grouping');
            $booking = $request->input('booking');
            $category = $request->input('category');

            $check = Cart::where('dealer', '=', $dealer)
                ->where('atlas_id', '=', $atlas_id)
                ->get();

            if (!$check || count($check) == 0) {
                // update the cart
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Sorry we could not find product';
            } else {
                $update_cart_product = Cart::query()->update([
                    'dealer' => $dealer,
                    'pro_id' => $pro_id,
                    'atlas_id' => $atlas_id,
                    'qty' => $qty,
                    'price' => $price,
                    'unit_price' => $unit_price,
                    'description' => $description,
                    'pro_img' => $pro_img,
                    'vendor_img' => $vendor_img,
                    'spec_data' => json_encode($spec_data),
                    'grouping' => $grouping,
                    'booking' => $booking,
                    'category' => $category,
                ]);

                if ($update_cart_product) {
                    $this->result->status = true;
                    $this->result->status_code = 200;
                    $this->result->message =
                        'Cart Product updated successfully';
                } else {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message =
                        'Sorry we could not update the cart product';
                }
            }

            return response()->json($this->result);
        }
    }

    public function dashboard($dealer)
    {
        $dealer_cart = DealerCart::where('user_id', $dealer)->first();
        // $total_price = 0;
        $total_quantity = 0;
        $cart = [];
        $new_products = Products::where('check_new', '1')->get();

        $total_order = Cart::where('dealer', '=', $dealer)
            ->where('status', '0')
            ->count();

        $dealer_details = Dealer::where('id', $dealer)->get();

        if(!$dealer_details || count($dealer_details) == 0){
            $this->result->status_code = 401;
            $this->result->message = "Dealer with id not found";
            return response()->json($this->result);
        }

        $dealer_account_id = $dealer_details[0]->account_id;
        
        // get all catalogue orders
        
        $get_catalogue_orders = Catalogue_Order::where('dealer',$dealer_account_id)->get();

        $format_catalogue_order_data =  json_decode($get_catalogue_orders[0]->data);

        $total_number_catalogue_orders = count($format_catalogue_order_data);
        
        $total_catalogue_price = 0;

        foreach ($format_catalogue_order_data as $catalogue_product) {
            $total_catalogue_price += $catalogue_product->total;
        }

        // get all the service parts 

        $get_service_parts_orders = ServiceParts::where('dealer',$dealer_account_id)->get();

        $format_service_parts_order_data =  json_decode($get_service_parts_orders[0]->data);

        $total_number_service_parts_orders = count($format_service_parts_order_data);

        $total_service_parts_price = 0;

        foreach ($format_service_parts_order_data as $service_parts_product) {
            $total_service_parts_price += $service_parts_product->total;
        }
        
        // get all the carded products 

        $get_carded_products_orders = CardedProducts::where('dealer',$dealer_account_id)->get();

        $format_carded_products_order_data =  json_decode($get_carded_products_orders[0]->data);

        $total_number_carded_products_orders = count($format_carded_products_order_data);

        $total_carded_products_price = 0;

        foreach ($format_carded_products_order_data as $carded_products_product) {
            $total_carded_products_price += $carded_products_product->total;
        }
        

        $total_cart_price = DB::table('cart')
            ->where('dealer', $dealer)
            ->where('status', '0')
            ->sum('price');

        // foreach ($products as $product) {
        //     $is_new =  $this->check_if_its_new($product['created_at'], 10,$product['atlas_id']) == true ? true : false;
        //     $spec_data = ($product->spec_data) ? json_decode($product->spec_data) : [];
        //     $product->spec_data = $spec_data;

        //     if ($is_new) {
        //         array_push($new_products, $product);
        //     }
        // }

        // return $total_number_service_parts_orders;
        

        $this->result->status = true;
        $this->result->status_code = 200;

        $this->result->data->total_price = $total_cart_price + $total_catalogue_price + $total_service_parts_price + $total_carded_products_price + $total_number_carded_products_orders;
        $this->result->data->total_quantity = $total_quantity;
        $this->result->data->new_products = $new_products
            ? count($new_products)
            : 0;
        $this->result->data->total_order = $total_order + $total_number_catalogue_orders + $total_number_service_parts_orders + $total_number_carded_products_orders;
        $this->result->message = 'Dealer Dashboard';
        return response()->json($this->result);
    }

    public function login(Request $request)
    {
        //valid credential
        $this->validate($request, [
            'account_id' => 'required',
            'password' => 'required|min:6',
        ]);

        if (
            !($token = Auth::guard('api')->attempt([
                'account_id' => $request->account_id,
                'password' => $request->password,
            ]))
        ) {
            $this->result->status_code = 401;
            $this->result->message = 'Invalid login credentials';
            return response()->json($this->result);
        }

        $active_staff = Dealer::query()
            ->where('account_id', $request->account_id)
            ->get()
            ->first();

        if ($active_staff['status'] == 0) {
            $this->result->status_code = 401;
            $this->result->message = 'Account has been deactivated';
            return response()->json($this->result);
        }

        $dealer = Dealer::where('account_id', $request->account_id)->first();
        $dealer->role = 'dealer';

        $dealer_details = Dealer::where(
            'account_id',
            $request->account_id
        )->get();

        $dealer_details[0]->update([
            'last_login' => Carbon::now(),
        ]);

        $this->result->token = $this->respondWithToken($token);
        $this->result->status = true;
        $this->result->data->dealer = $dealer;
        return response()->json($this->result);
    }

    public function user_submit_order($id)
    {
        $dealer_id = $id;
        $status = 1;
        $dealer = Dealer::where('id', $dealer_id)->get();

        if (count($dealer) == 0 || !$dealer) {
            $this->result->status = false;
            $this->result->status_code = 200;
            $this->result->message = 'Dealer with id not found';
            return response()->json($this->result, 404);
        }

        $order_status = $dealer[0]->order_status;
        $close_program = $dealer[0]->close_program;
        $dealer_account_id = $dealer[0]->account_id;

        ///$dealer_status = Cart::where('dealer', $dealer_id)->where('status', $status)->get()->toArray();

        if ($close_program == '1') {
            $this->result->status = false;
            $this->result->status_code = 200;
            $this->result->message =
                'The Booking Program has been closed, contact support';
            return response()->json($this->result);
        } else {
            ///  $dealer_account_id = $dealer[0]->account_id;

            // $dealer_cart_count = Cart::where('dealer', $dealer_id)->count();
            // $carded_products_count = CardedProducts::where(
            //     'dealer',
            //     $dealer_account_id
            // )->count();
            // $service_part_count = ServiceParts::where(
            //     'dealer',
            //     $dealer_account_id
            // )->count();
            // $catalogue_orders_count = Catalogue_order::where(
            //     'dealer',
            //     $dealer_account_id
            // )->count();

            // if (
            //     $dealer_cart_count > 0 ||
            //     $carded_products_count > 0 ||
            //     $service_part_count > 0 ||
            //     $catalogue_orders_count > 0
            // ) {
            //     $cur_date = date('Y-m-d H:i:s');
            //     $dealer = Dealer::where('id', $dealer_id)->update([
            //         'order_status' => $status,
            //         'placed_order_date' => $cur_date,
            //     ]);
            // } else {
            //     $this->result->status = true;
            //     $this->result->status_code = 200;
            //     $this->result->message = 'Your Cart is Empty';
            //     return response()->json($this->result);
            // }

            if ($order_status == 1) {
                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->debug = $dealer;
                $this->result->message = 'You have already placed an order';
                return response()->json($this->result);
            } else {
                $cur_date = date('Y-m-d H:i:s');
                $dealer = Dealer::where(
                    'account_id',
                    $dealer_account_id
                )->update([
                    'order_status' => 1,
                    'placed_order_date' => $cur_date,
                ]);
                $cart = Cart::where('dealer', $dealer_account_id)->update([
                    'status' => $status,
                ]);

                // update all the service parts completed to true (1)
                $submit_service_parts = ServiceParts::where(
                    'dealer',
                    $dealer_account_id
                )->update([
                    'completed' => 1,
                ]);

                // update all the carded products completed to true (1)
                $submit_carded_products = CardedProducts::where(
                    'dealer',
                    $dealer_account_id
                )->update([
                    'completed' => 1,
                ]);
                // update all the catalogue orders completed to true (1)
                $submit_catalogue_order = Catalogue_Order::where(
                    'dealer',
                    $dealer_account_id
                )->update([
                    'completed' => 1,
                ]);

                if (
                    $submit_carded_products ||
                    $submit_service_parts ||
                    $submit_catalogue_order
                ) {
                    $this->result->status = true;
                    $this->result->status_code = 200;
                    $this->result->message = 'Successfully added to cart';
                    return response()->json($this->result);
                }
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Successfully added to cart';
            return response()->json($this->result);
        }
    }

    public function test_cart($id)
    {
        set_time_limit(240000000); // temporarily increase the timeout limit '

        $categories = Category::all();
        $plumbing = [];
        $vent = [];
        $electrical = [];
        $electronics = [];
        $propane = [];
        $accessories = [];
        $outdoor = [];
        $sealant = [];
        $appliance = [];
        $towing_products = [];
        $towing_accessories = [];
        $catalogue_data = [];
        $carded_data = [];
        $service_data = [];

        $dealer_d = Dealer::where('id', $id)
            ->get()
            ->first();
        $account_id = $dealer_d->account_id;

        $grand_total = 0;
        $cart = Cart::where('dealer', $id)
            ->get()
            ->toArray();
        $ji = 0;

        foreach ($cart as $value) {
            $ji++;
            // if($ji == 50){
            //     break;
            // }

            $spea_dat = json_decode($value['spec_data'], true);
            $value['spec_data'] = $spea_dat;

            $grand_total += floatval($value['price']);

            if ($value['category'] == 'plumbing') {
                array_push($plumbing, $value);
            }

            if ($value['category'] == 'vents and hardware') {
                array_push($vent, $value);
            }

            if ($value['category'] == 'electrical') {
                array_push($electrical, $value);
            }

            if ($value['category'] == 'electronics') {
                array_push($electronics, $value);
            }

            if ($value['category'] == 'propane') {
                array_push($propane, $value);
            }

            if ($value['category'] == 'accessories') {
                array_push($accessories, $value);
            }

            if ($value['category'] == 'towing accessories') {
                array_push($towing_accessories, $value);
            }

            if ($value['category'] == 'outdoor living') {
                array_push($outdoor, $value);
            }

            if ($value['category'] == 'sealant and cleaners') {
                array_push($sealant, $value);
            }

            if ($value['category'] == 'appliances') {
                array_push($appliance, $value);
            }

            if ($value['category'] == 'towing products') {
                array_push($towing_products, $value);
            }
        }

        $data['cart'] = $cart;
        $data['vent'] = $vent;
        $data['electrical'] = $electrical;
        $data['electronics'] = $electronics;
        $data['propane'] = $propane;
        $data['accessories'] = $accessories;
        $data['outdoor'] = $outdoor;
        $data['sealant'] = $sealant;
        $data['appliance'] = $appliance;
        $data['plumbing'] = $plumbing;
        $data['towing_accessories'] = $towing_accessories;
        $data['towing_products'] = $towing_products;
        $data['catalogue_data'] = [];
        $data['carded_data'] = [];
        $data['service_data'] = [];

        $data['grand_total'] = $grand_total;

        $dealerId = $id;
        $dealer_details = Dealer::where('id', $dealerId)->get();
        $dealer_name =
            $dealer_details[0]->first_name .
            ' ' .
            $dealer_details[0]->last_name;
        $dealer_email = $dealer_details[0]->email;
        $dealer_account_id = $dealer_details[0]->account_id;
        $dealer_updated_at = $dealer_details[0]->updated_at;

        $myData = [];

        // $pdf_storage_location = '';

        $order_id = 'atlas-order' . rand(6, 100000000000000) . '.pdf';

        $data['dealer_updated_at'] = $dealer_updated_at;

        $data['dealer_account_id'] = $dealer_account_id;
        $data['email'] = $dealer_email;
        $data['dealer_name'] = $dealer_name;
        $data['title'] = 'Atlas Order Details';
        $data['order_file_name'] = $order_id;

        $pdf = PDF::loadView('mails.pdf_format', $data);

        // download PDF file with download method
        // $order_pdf =  $pdf->download('pdf_file.pdf');

        // save the pdf then

        Storage::put('public/pdf/' . $order_id, $pdf->output() . '.pdf');

        $path = env('APP_URL') . Storage::url('public/pdf/' . $order_id);

        // echo $path; exit();
        // send the url to the file to the api endpoint

        // $bb = base64_encode($order_pdf);

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data->pdf_url = strval($path);

        $this->result->message = 'Email has been sent';
        return response()->json($this->result);
    }

    public function get_dealer_orders($id)
    {
        $dealer = Dealer::where('id', $id)
            ->get()
            ->first();
        $cart = DealerCart::where('user_id', $id)
            ->get()
            ->toArray();

        if (count($cart) > 0) {
            $cart_data = json_decode($cart[0]['cart_data']);
            $dealer_cart = $cart_data;

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data->cart = $dealer_cart;
            $this->result->data->dealer = $dealer;

            $this->result->message = 'Dealers Orders';
        } else {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = [];
            $this->result->message = 'No Order Found';
        }

        return response()->json($this->result);
    }

    public function send_pdf($id)
    {
        $categories = Category::all();

        $plumbing = [];
        $vent = [];
        $electrical = [];
        $electronics = [];
        $propane = [];
        $accessories = [];
        $towing = [];
        $outdoor = [];
        $sealant = [];
        $appliance = [];
        $grand_total = 0;

        $cart = Cart::where('dealer', $id)->get();

        foreach ($cart as $value) {
            $grand_total += floatval($value['price']);

            if ($value['category'] == 'plumbing') {
                array_push($plumbing, $value);
            }

            if (
                $value['category'] == 'vents and hardware' ||
                $value['category'] == 'vents'
            ) {
                array_push($vent, $value);
            }

            if ($value['category'] == 'electrical') {
                array_push($electrical, $value);
            }

            if ($value['category'] == 'electronics') {
                array_push($electronics, $value);
            }

            if ($value['category'] == 'propane') {
                array_push($propane, $value);
            }

            if ($value['category'] == 'accessories') {
                array_push($accessories, $value);
            }

            if (
                $value['category'] == 'towing accessories' ||
                $value['category'] == 'towing'
            ) {
                array_push($towing_accessories, $value);
            }

            if (
                $value['category'] == 'outdoor living' ||
                $value['category'] == 'outdoor'
            ) {
                array_push($outdoor, $value);
            }

            if (
                $value['category'] == 'sealant and cleaners' ||
                $value['category'] == 'sealant'
            ) {
                array_push($sealant, $value);
            }

            if (
                $value['category'] == 'appliances' ||
                $value['category'] == 'appliance'
            ) {
                array_push($appliance, $value);
            }

            if (
                $value['category'] == 'towing products' ||
                $value['category'] == 'awning'
            ) {
                array_push($towing_products, $value);
            }
        }

        $data['cart'] = $cart;
        $data['vent'] = $vent;
        $data['electrical'] = $electrical;
        $data['electronics'] = $electronics;
        $data['propane'] = $propane;
        $data['accessories'] = $accessories;
        $data['towing'] = $towing;
        $data['outdoor'] = $outdoor;
        $data['sealant'] = $sealant;
        $data['appliance'] = $appliance;

        $data['plumbing'] = $plumbing;
        $data['grand_total'] = $grand_total;

        $dealerId = $id;
        $dealer_details = Dealer::where('id', $dealerId)->get();
        $dealer_name =
            $dealer_details[0]->first_name .
            ' ' .
            $dealer_details[0]->last_name;
        $dealer_email = $dealer_details[0]->email;
        $myData = [];

        $data['email'] = $dealer_email;
        $data['dealer_name'] = $dealer_name;
        $data['title'] = 'Atlas Order Details';
        $data['order_file_name'] =
            'atlas-order' . rand(6, 100000000000000) . '.pdf';

        $pdf = PDF::loadView('mails.pdf_format', $data);

        Mail::send('mails.send_html', $data, function ($message) use (
            $data,
            $pdf
        ) {
            $message
                ->to($data['email'])
                ->cc('orders@atlastrailer.com')
                ->subject($data['title'])
                ->attachData($pdf->output(), $data['order_file_name']);

            // $message->to("orders@atlastrailer.com")
            //     ->subject("ATLAS BOOKING PROGRAM DEALER'S ORDER")
            //     ->attachData($pdf->output(), $data['order_file_name']);
        });

        //         Mail::send('mails.send_html', $data, function ($message) use ($data, $pdf) {

        //             $message->to("iamkaluchinonso@gmail.com", "iamkaluchinonso@gmail.com")
        //                 ->subject("ATLAS BOOKING PROGRAM DEALER'S ORDER")
        //                 ->attachData($pdf->output(), $data['order_file_name']);

        //             $message->to($data["email"], $data["email"])
        //                 ->subject($data["title"])
        //                 ->attachData($pdf->output(), $data['order_file_name']);
        //         });

        //         Michael.Habib@atlastrailer.com

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Sorry Email could not be sent';
        return response()->json($this->result);
    }

    public function sendOrderMail(Request $request)
    {
        $dealer_id = $request->input('dealer');
        $cart = $request->input('cart');
        $dealer_details = Dealer::where('id', $dealer_id)->get();
        $dealer_name =
            $dealer_details[0]->first_name .
            ' ' .
            $dealer_details[0]->last_name;
        $dealer_email = $dealer_details[0]->email;
        $order_ref = 'Order' . rand(6, 10000) . '.pdf';
        $group_cart = [];

        foreach ($cart as $key => $item) {
            $categories = Category::all();

            foreach ($categories as $index => $category) {
                $category_name = $category['name'];

                if ($category_name == $item['category']) {
                    // it matches a real category
                    if (!array_key_exists($category_name, $group_cart)) {
                        $group_cart[$category_name] = [];
                    }

                    array_push($group_cart[$item['category']], $item);
                }
            }
        }

        $pdf = PDF::loadView('pdf.order', [
            'cart' => $group_cart,
            'dealer_name' => $dealer_name,
        ]);

        // $order_ref = 'Order' . rand(6, 10000) . '.pdf';
        Storage::put('public/pdf/' . $order_ref, $pdf->output());

        $path = Storage::url('public/pdf/' . $order_ref);

        // save the details to the database
        $save_order_Details = Orders::create([
            'name' => $order_ref,
            'email' => $dealer_email,
            'type' => 'dealer_order',
            'url' => env('APP_URL') . $path,
        ]);

        if ($save_order_Details) {
            // generate pdf

            Mail::to($dealer_email)->send(new SubmitOrderMail($order_ref));

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Email Successfully sent';

            return response()->json($this->result);
        } else {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Email could not be sent';
            return response()->json($this->result);
        }
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' =>
                auth()
                    ->factory()
                    ->getTTL() * 60,
        ]);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    public function add_product(Request $request)
    {
        // `atlas_id`, `name`, `price`, `description`, `img`,
        // `assorted_discount`, `quantity_discount`,

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'price' => 'required',
            'description' => 'required',
            'img' => 'required|mimes:jpg,bmp,png|max:2048',
            'category_id' => 'required',
            'assorted_discount' => 'required|integer',
            'quantity_discount' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $response['response'] = $validator->messages();
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = $response;

            return response()->json($this->result);
        } else {
            // process the request
            $name = $request->input('name');
            $price = $request->input('price');
            $description = $request->input('description');
            $img = $request->file('img');
            $category_id = $request->input('category_id');
            $assorted_discount = $request->input('assorted_discount');
            $quantity_discount = $request->input('quantity_discount');
            $short_note_url = $request->input('short_note_url');
            // save the file to

            if ($request->file('img')->isValid()) {
                $img_extension = $request->file('img')->extension();
                $img_filename = $request->file('img')->getClientOriginalName();
                $new_img_filename =
                    Str::slug($img_filename . date('Y-m-d')) .
                    '.' .
                    $img_extension;
                $path = $request
                    ->file('img')
                    ->storeAs('public/img', $new_img_filename);

                if ($path) {
                    // save to the db
                    $save_product = Products::create([
                        'atlas_id' => 'ATL-' . Rand(8, 999999),
                        'name' => $name,
                        'price' => $price,
                        'description' => $description,
                        'category_id' => $category_id,
                        'img' => env('APP_URL') . Storage::url($path),
                        'assorted_discount' => $assorted_discount,
                        'quantity_discount' => $quantity_discount,
                        'short_note_url' => $short_note_url,
                    ]);

                    if ($save_product) {
                        $this->result->status = true;
                        $this->result->status_code = 200;
                        $this->result->message = 'Product Successfully Added';
                        return response()->json($this->result);
                    } else {
                        $this->result->status = true;
                        $this->result->status_code = 404;
                        $this->result->message =
                            'An Error Ocurred, Product Addition failed';
                        return response()->json($this->result);
                    }
                } else {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message =
                        'Sorry We could not upload the product image. Please try again later!';
                    return response()->json($this->result);
                }
            } else {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Sorry you uploaded an invalid Image';
                return response()->json($this->result);
            }
        }
    }

    public function fetch_all_products()
    {
        $all_products = Products::where('status', '1')
            ->orderBy('id', 'desc')
            ->get();

        foreach ($all_products as $value) {
            $spec_data = $value->spec_data
                ? json_decode($value->spec_data)
                : [];

            $value->spec_data = $spec_data;
        }

        $format_products = array_map(function ($record) {
            // $record =
            $format_data =
                $this->check_if_its_new(
                    $record['created_at'],
                    10,
                    $record['atlas_id']
                ) == true
                    ? true
                    : false;

            return array_merge(
                [
                    'is_new' => $format_data,
                ],
                $record
            );
        }, json_decode($all_products, true));

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $format_products;
        $this->result->message = 'All Products fetched Successfully';
        return response()->json($this->result);
    }

    public function check_if_its_new(
        $created_at,
        $no_of_days,
        $atlas_id = false
    ) {
        $format_created_at = Carbon::parse($created_at);

        $now = Carbon::now();

        $length = $format_created_at->diffInDays($now);

        // echo "hello"; exit();

        if ($length <= $no_of_days) {
            if ($atlas_id == true) {
                // $product = DB::select("SELECT * FROM `PRODUCTS` WHERE `atlas_id` = '$atlas_id' AND  `full_desc` IS NULL OR `full_desc` == ' '");

                // $product = Products::where('atlas_id',$atlas_id)->where('full_desc',' ')->whereNull('full_desc')->get();

                $product = DB::table('atlas_products')
                    ->where('atlas_id', $atlas_id)
                    ->whereNotNull('full_desc')
                    ->where('full_desc', '!=', '')
                    ->get();

                // return $product;

                if ($product && count($product) > 0) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    // public function add_cart( Request $request ) {
    //     $dealer_id = $request->input( 'dealer' );
    //     $cart = $request->input( 'cart' );
    //     $status = 1;

    //     $dealer_status = DealerCart::where( 'user_id', $dealer_id )->get();

    //     $dealer_details = Dealer::find( $dealer_id );
    //     $dealer_email = $dealer_details->email;

    //     if ( count( $dealer_status ) > 0 ) {
    //         $this->result->status = false;
    //         $this->result->status_code = 200;
    //         $this->result->message = 'You have already placed an order';
    //         return response()->json( $this->result );
    //     } else {
    //         $cart = json_encode( $cart );
    //         $cart_dealer_cart = DealerCart::create(
    //             [
    //                 'user_id' => $dealer_id,
    //                 'cart_data' => $cart,
    //                 'status' => $status,
    //                 'ref' => Str::random( 8 ) . rand( 3, 10000 )
    //             ]
    //         );

    //         if ( $cart_dealer_cart ) {
    //             $this->result->status = true;
    //             $this->result->status_code = 200;
    //             $this->result->message = 'Successfully added to cart';
    //             return response()->json( $this->result );
    //         }
    //     }

    //     $this->result->status = true;
    //     $this->result->status_code = 200;
    //     $this->result->message = 'Successfully added to cart';
    //     return response()->json( $this->result );
    // }

    public function quick_search_product($category, $value)
    {
        $format_value = implode(explode('-', $value));

        if (intval($format_value) == 0) {
            // it is a string
            $products = DB::table('atlas_products')
                ->where('category', '=', $category)
                ->where('status', '1')
                ->where('description', 'LIKE', '%' . $value . '%')
                ->select('atlas_products.*')
                ->get();
        } else {
            // it is a number
            $products = DB::table('atlas_products')
                ->where('category', '=', $category)
                ->where('status', '1')
                ->where('atlas_id', 'LIKE', '%' . $value . '%')
                ->select('atlas_products.*')
                ->get();
        }

        foreach ($products as $value) {
            $spec_data = $value->spec_data
                ? json_decode($value->spec_data)
                : [];
            $value->spec_data = $spec_data;
        }

        $format_products = array_map(function ($record) {
            $format_data =
                $this->check_if_its_new(
                    $record['created_at'],
                    10,
                    $record['atlas_id']
                ) == true
                    ? true
                    : false;
            // if(intval($this->check_if_its_new($record['created_at'], 10,$record['atlas_id']))){

            // }
            return array_merge(
                [
                    'is_new' => $format_data,
                ],
                $record
            );
        }, json_decode($products, true));

        if (!$products) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Products could not be fetched';
            return response()->json($this->result);
        }

        if (count($format_products) == 0) {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'No Products found';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $format_products;
        $this->result->message = 'All Products fetched Successfully';
        return response()->json($this->result);
    }

    public function search_product($value)
    {
        $products = DB::table('atlas_products')
            ->where('status', 1)
            ->where('description', 'LIKE', '%' . $value . '%')
            ->orwhere('atlas_id', '=', $value)
            ->select('atlas_products.*')
            ->orderby('atlas_id', 'asc')
            ->get();

        foreach ($products as $value) {
            $spec_data = $value->spec_data
                ? json_decode($value->spec_data)
                : [];

            $value->spec_data = $spec_data;
        }

        $format_products = array_map(function ($record) {
            $format_data =
                $this->check_if_its_new(
                    $record['created_at'],
                    10,
                    $record['atlas_id']
                ) == true
                    ? true
                    : false;

            return array_merge(
                [
                    'is_new' => $format_data,
                ],
                $record
            );
        }, json_decode($products, true));

        if (!$products) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Product could not be fetched';
            return response()->json($this->result);
        }

        if (count($format_products) == 0) {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = [];
            $this->result->message = 'No Product found';
            return response()->json($this->result);
        }
        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $format_products;
        $this->result->message = 'Products fetched Successfully';
        return response()->json($this->result);
    }

    public function validate_extra_products($value, $type)
    {
        // check if the value (atlas_id) is a catalogue, carded or service parts product
        // `item_code`, `vendor_code`, `description`, `type`, `type_name`,
        $string_value = (string) $value;

        // Catalogue products = 1
        // Carded products = 2
        // Service parts = 3

        $check_product = ExtraProducts::where(
            'item_code',
            $string_value
        )->first();

        if (!$check_product) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Product could not be found';
            return response()->json($this->result);
        }

        $type_number = (int) $type;
        // check the type

        $product_type = (int) $check_product->type;

        if ($type_number == $product_type) {
            // item with atlas id found and the category will be
            // displayed with other details
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $check_product;
            $this->result->message =
                'Product found successfully in ' .
                ucwords(str_replace('_', ' ', $check_product->type_name));
            return response()->json($this->result, 200);
        } else {
            // return the category of the product
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $check_product;
            $this->result->message =
                'Kindly check in ' .
                ucwords(str_replace('_', ' ', $check_product->type_name));
            return response()->json($this->result, 200);
        }
    }

    public function product_category_type($type_number)
    {
        // Catalogue products = 1
        // Carded products = 2
        // Service parts = 3
        switch ((int) $type_number) {
            case '1':
                return 'Catalogue products';
                break;
            case '2':
                return 'Carded products';
                break;
            case '3':
                return 'Service parts';
                break;
            default:
                return false;
                break;
        }
    }
    public function search_product_by_type_category($value, $type)
    {
        // CO = Catalogue_Order
        // CP = CardedProducts
        // SP = ServiceParts
        $search_value = (string) $value;

        $search_type = (string) $type;

        switch ($search_type) {
            case 'CO':
                $all_catalogue_order = Catalogue_Order::get()->toArray();
                break;
            case 'CP':
                # code...
                $all_catalogue_order = CardedProducts::get()->toArray();
                break;
            case 'SP':
                # code...
                $all_catalogue_order = ServiceParts::get()->toArray();
                break;
            default:
                # DEFAULT
                $this->result->status = false;
                $this->result->status_code = 200;
                $this->result->message = 'Please enter a product type';

                // 'Catalogue Product found Successfully';
                return response()->json($this->result);
                break;
        }

        $format_catalogue_order = array_map(function ($record) {
            $catalogue_data = $record['data'];

            $decode_catalogue_data = json_decode($catalogue_data);

            return $decode_catalogue_data;
        }, $all_catalogue_order);

        // foreach )
        // return $format_catalogue_order;

        $response_data = false;

        $response_message = '';

        foreach ($format_catalogue_order as $key => $item) {
            $format_item = array_map(function ($record) {
                $format_item_atlas_id = $record->atlasId;

                return $format_item_atlas_id;
            }, $item);

            if (in_array($search_value, $format_item)) {
                // item was found
                $response_data['status'] = true;
                $response_data['data'] = $item;

                $response_message =
                    'Product with atlas id - ' .
                    $search_value .
                    ' - found in catalogue order products';

                break;
            } else {
                // we check the other categories, carded and service parts

                // search carded products
                $search_carded_products = $this->search_product_type_carded_product(
                    $search_value
                );

                if ($search_carded_products['status'] == true) {
                    $response_message =
                        'Product with atlas id - ' .
                        $search_value .
                        ' - found in carded products';
                    break;
                }

                $search_carded_products = $this->search_product_type_service_parts(
                    $search_value
                );

                if ($search_carded_products['status'] == true) {
                    $response_message =
                        'Product with atlas id - ' .
                        $search_value .
                        ' - found in service parts products';
                    break;
                }

                $response_message =
                    'Product with atlas id - ' .
                    $search_value .
                    ' - not found. ';
                break;
            }
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = $response_message;

        // 'Catalogue Product found Successfully';
        return response()->json($this->result);
    }

    public function search_product_type($value)
    {
        $search_value = (string) $value;

        $all_catalogue_order = Catalogue_Order::get()->toArray();

        $format_catalogue_order = array_map(function ($record) {
            $catalogue_data = $record['data'];

            $decode_catalogue_data = json_decode($catalogue_data);

            return $decode_catalogue_data;
        }, $all_catalogue_order);

        // foreach )
        // return $format_catalogue_order;

        $response_data = false;

        $response_message = '';

        foreach ($format_catalogue_order as $key => $item) {
            $format_item = array_map(function ($record) {
                $format_item_atlas_id = $record->atlasId;

                return $format_item_atlas_id;
            }, $item);

            if (in_array($search_value, $format_item)) {
                // item was found
                $response_data['status'] = true;
                $response_data['data'] = $item;

                $response_message =
                    'Product with atlas id - ' .
                    $search_value .
                    ' - found in catalogue order products';

                break;
            } else {
                // we check the other categories, carded and service parts

                // search carded products
                $search_carded_products = $this->search_product_type_carded_product(
                    $search_value
                );

                if ($search_carded_products['status'] == true) {
                    $response_message =
                        'Product with atlas id - ' .
                        $search_value .
                        ' - found in carded products';
                    break;
                }

                $search_carded_products = $this->search_product_type_service_parts(
                    $search_value
                );

                if ($search_carded_products['status'] == true) {
                    $response_message =
                        'Product with atlas id - ' .
                        $search_value .
                        ' - found in service parts products';
                    break;
                }

                $response_message =
                    'Product with atlas id - ' .
                    $search_value .
                    ' - not found. ';
                break;
            }
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = $response_message;

        // 'Catalogue Product found Successfully';
        return response()->json($this->result);
    }

    // check carded products for a product

    public function search_product_type_carded_product($value)
    {
        $search_value = (string) $value;

        $all_carded_products = CardedProducts::get()->toArray();

        // return $all_carded_products;

        $format_carded_products = array_map(function ($record) {
            $catalogue_data = $record['data'];

            $decode_catalogue_data = json_decode($catalogue_data);

            return $decode_catalogue_data;
        }, $all_carded_products);

        $response_data = false;

        $product_data = [];

        // return count($format_carded_products);

        foreach ($format_carded_products as $key => $item) {
            // return $item[$key]->atlasId;
            foreach ($item as $key => $record) {
                array_push($product_data, $record->atlasId);
            }
        }

        if (in_array($search_value, $product_data)) {
            // item was found
            $response_data['status'] = true;
            $response_data['data'] = $item;
        } else {
            // we check the other categories, carded and service parts
            $response_data['status'] = false;
            $response_data['data'] = [];
        }

        return $response_data;
    }

    // check for service parts

    public function search_product_type_service_parts($value)
    {
        $search_value = (string) $value;

        $all_carded_products = ServiceParts::get()->toArray();

        // return $all_carded_products;

        $format_carded_products = array_map(function ($record) {
            $catalogue_data = $record['data'];

            $decode_catalogue_data = json_decode($catalogue_data);

            return $decode_catalogue_data;
        }, $all_carded_products);

        $response_data = false;

        $product_data = [];

        // return count($format_carded_products);

        foreach ($format_carded_products as $key => $item) {
            // return $item[$key]->atlasId;
            foreach ($item as $key => $record) {
                array_push($product_data, $record->atlasId);
            }
        }

        if (in_array($search_value, $product_data)) {
            // item was found
            $response_data['status'] = true;
            $response_data['data'] = $item;
        } else {
            // we check the other categories, carded and service parts
            $response_data['status'] = false;
            $response_data['data'] = [];
        }

        return $response_data;
    }

    public function add_catalogue_order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required',
            'dealer_id' => 'required',
        ]);

        if ($validator->fails()) {
            $response['response'] = $validator->messages();
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = $response;

            return response()->json($this->result);
        } else {
            $new_data = $request->input('data');

            $dealer_id = $request->input('dealer_id');

            // check if the dealer exists

            $check_catalogue_order = Catalogue_order::where(
                'dealer',
                $dealer_id
            )->get();

            if (count($check_catalogue_order) > 0) {
                // decode the data
                // dd(json_decode($check_catalogue_order[0]->data));

                $old_data = is_array($check_catalogue_order[0]['data'])
                    ? $check_catalogue_order[0]['data']
                    : json_decode($check_catalogue_order[0]['data']);

                // check the items

                $new_records = is_array($new_data)
                    ? $new_data
                    : json_decode($new_data, true);

                $formated_data = array_merge($old_data, $new_records);

                $check_catalogue_order[0]->data = json_encode($formated_data);

                $update_record = $check_catalogue_order[0]->save();

                // add item to cart here

                $add_to_cart = Cart::create([
                    'dealer' => $dealer_id,
                    'catalogue_data' => json_encode($formated_data),
                ]);

                if (!$update_record || !$add_to_cart) {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message =
                        'Sorry Catalogue Order could not be added';
                    return response()->json($this->result);
                } else {
                    $this->result->status = true;
                    $this->result->status_code = 200;
                    $this->result->message =
                        'Catalogue Order added successfully';
                    return response()->json($this->result);
                }
            } else {
                $create_carded_product = Catalogue_Order::create([
                    'dealer' => $dealer_id,
                    'data' => json_encode($new_data),
                ]);

                if (!$create_carded_product) {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message =
                        'Sorry we could not create the catalogue order';
                    return response()->json($this->result);
                }

                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->message = 'Catalogue Order created successfully';
                return response()->json($this->result);
            }
        }
    }

    public function fetch_all_promotional_ad()
    {
        $promotional_ads = Promotional_ads::orderby('name', 'asc')->get();

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $promotional_ads;
        $this->result->message = 'All Promotional Ad fetched Successfully';
        return response()->json($this->result);
    }

    public function fetch_promotion_by_category($id)
    {
        $promotional_ad = Promotional_ads::where('category_id', $id)
            ->where('status', 1)
            ->get();

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $promotional_ad;
        $this->result->message = 'Promotional Ad fetched Successfully';

        return response()->json($this->result);
    }

    public function check_atlas_id($id)
    {
        $data = DB::select(
            "SELECT * FROM `atlas_products` WHERE `atlas_id` = '$id'"
        );
        if ($data) {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = true;
            $this->result->message = 'Product available';
        } else {
            $this->result->data = false;
            $this->result->status_code = 200;
            $this->result->message = 'Product not available';
        }

        $this->result->status = true;
        return response()->json($this->result);
    }

    public function save_catalogue(Request $request)
    {
        $dealer = $request->dealer;
        $data = $request->data;

        $check = DB::select(
            "SELECT * FROM `atlas_catalogue_orders` WHERE `dealer` = '$dealer'"
        );

        if ($check) {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = true;
            $this->result->message =
                'your have Place a catalogue Order already';
            return response()->json($this->result);
        } else {
            $dealer = Catalogue_Order::create([
                'dealer' => $dealer,
                'data' => $data,
            ]);

            if ($dealer) {
                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->data = $dealer;
                $this->result->message = 'your item has been uploaded';
            }
            return response()->json($this->result);
        }
    }
    public function save_dealer_login_log(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dealer' => 'required',
            'ip_address' => 'required',
            'location' => 'required',
            'browser' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'dealer' => $validator->errors()->get('dealer'),
                'ip_address' => $validator->errors()->get('ip_address'),
                'location' => $validator->errors()->get('location'),
                'browser' => $validator->errors()->get('browser'),
            ];
        } else {
            // update the catalogue order
            $dealer = $request->input('dealer');
            $ip_address = $request->input('ip_address');
            $location = $request->input('location');
            $browser = $request->input('browser');
            $bro_data = $request->input('broData');

            // $logged_in = AtlasLoginLog::where( 'dealer', $dealer )->get()->toArray();

            // if ( count($logged_in) > 0) {

            //     $update = AtlasLoginLog::where("dealer", $dealer)->update(["ip_address" => $ip_address, "browser" => $browser]);

            //     if ( $update ) {
            //         $this->result->status = true;
            //         $this->result->status_code = 200;
            //         $this->result->message = 'Log Successfully Added';
            //         return response()->json( $this->result );
            //     } else {
            //         $this->result->status = true;
            //         $this->result->status_code = 404;
            //         $this->result->message = 'An Error Ocurred, Product Addition failed';
            //         return response()->json( $this->result );
            //     }

            // }else{

            $country_array = public_path('json/countries.json');

            $country_array_decode = json_decode(
                file_get_contents($country_array),
                true
            );

            // print_r($country_array_decode); exit();

            $details = json_decode(
                file_get_contents("http://ipinfo.io/{$ip_address}/json")
            );

            // echo json_encode($details); exit();
            $country_code = $details->country;

            //    echo $country_code; exit();
            $country_name = '';

            foreach ($country_array_decode as $country) {
                $code = $country['code'];
                $name = $country['name'];
                if ($country_code == $code) {
                    $country_name = $name;
                    break;
                }
            }

            //    echo $country_name; exit();

            $save = AtlasLoginLog::create([
                'dealer' => $dealer,
                'ip_address' => $ip_address,
                'location' => $location,
                'browser' => $browser,
                'data' => json_encode($details),
                'current_location' => $country_name,
                'browser_data' => $bro_data,
            ]);

            if ($save) {
                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->message = 'Log Successfully Added';
                return response()->json($this->result);
            } else {
                $this->result->status = true;
                $this->result->status_code = 404;
                $this->result->message =
                    'An Error Ocurred, Product Addition failed';
                return response()->json($this->result);
            }

            // }
        }
    }

    // public function save_dealer_login_log(Request $request)
    // {

    //     $validator = Validator::make($request->all(), [
    //         'dealer' => 'required',
    //         'ip_address' => 'required',
    //         'location' => 'required',
    //         'browser' => 'required'
    //     ]);

    //     if ($validator->fails()) {
    //         $this->result->status_code = 422;
    //         $this->result->message = [
    //             'dealer' => $validator->errors()->get('dealer'),
    //             'ip_address' => $validator->errors()->get('ip_address'),
    //             'location' => $validator->errors()->get('location'),
    //             'browser' => $validator->errors()->get('browser')
    //         ];
    //     } else {
    //         // update the catalogue order
    //         $dealer = $request->input('dealer');
    //         $ip_address = $request->input('ip_address');
    //         $location = $request->input('location');
    //         $browser = $request->input('browser');

    //         $logged_in = AtlasLoginLog::where('dealer', $dealer)->get()->toArray();

    //         if (count($logged_in) > 0) {

    //             $update = AtlasLoginLog::where("dealer", $dealer)->update(["ip_address" => $ip_address, "browser" => $browser]);

    //             if ($update) {
    //                 $this->result->status = true;
    //                 $this->result->status_code = 200;
    //                 $this->result->message = 'Log Successfully Added';
    //                 return response()->json($this->result);
    //             } else {
    //                 $this->result->status = true;
    //                 $this->result->status_code = 404;
    //                 $this->result->message = 'An Error Ocurred, Product Addition failed';
    //                 return response()->json($this->result);
    //             }
    //         } else {

    //             $save = AtlasLoginLog::create([
    //                 'dealer' => $dealer,
    //                 'ip_address' => $ip_address,
    //                 'location' => $location,
    //                 'browser' => $browser
    //             ]);

    //             if ($save) {
    //                 $this->result->status = true;
    //                 $this->result->status_code = 200;
    //                 $this->result->message = 'Log Successfully Added';
    //                 return response()->json($this->result);
    //             } else {
    //                 $this->result->status = true;
    //                 $this->result->status_code = 404;
    //                 $this->result->message = 'An Error Ocurred, Product Addition failed';
    //                 return response()->json($this->result);
    //             }
    //         }
    //     }
    // }

    public function create_carded_product(Request $request)
    {
        // `dealer`, `atlas_id`, `quantity`,
        $validator = Validator::make($request->all(), [
            'atlas_id' => 'required',
            'dealer' => 'required',
            'quantity' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'atlas_id' => $validator->errors()->get('atlas_id'),
                'dealer' => $validator->errors()->get('dealer'),
                'quantity' => $validator->errors()->get('quantity'),
            ];
            return response()->json($this->result);
        } else {
            $dealer = $request->input('dealer');
            $atlas_id = $request->input('atlas_id');
            $quantity = $request->input('quantity');

            // check if a dealer already has a carded item
            $check_dealer = CardedProducts::where('atlas_id', $atlas_id)
                ->orwhere('dealer', $dealer)
                ->get();

            if (count($check_dealer) > 0) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry a dealer can only add a carded produt once';
                return response()->json($this->result);
            }

            $create_carded_product = CardedProducts::create([
                'dealer' => $dealer,
                'atlas_id' => $atlas_id,
                'quantity' => $quantity,
            ]);

            if (!$create_carded_product) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry we could not create the carded product';
                return response()->json($this->result);
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Carded Product created successfully';
            return response()->json($this->result);
        }
    }

    public function edit_carded_product(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'atlas_id' => 'required',
            'dealer' => 'required',
            'quantity' => 'required|integer',
            'price' => 'required',
            'total' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'id' => $validator->errors()->get('id'),
                'atlas_id' => $validator->errors()->get('atlas_id'),
                'dealer' => $validator->errors()->get('dealer'),
                'quantity' => $validator->errors()->get('quantity'),
                'price' => $validator->errors()->get('price'),
                'total' => $validator->errors()->get('total'),
            ];
            return response()->json($this->result);
        } else {
            $id = $request->input('id');
            $atlas_id = $request->input('atlas_id');
            $dealer = $request->input('dealer');
            $quantity = $request->input('quantity');

            $price = $request->input('price');
            $total = $request->input('total');

            $no_of_carded_product = CardedProducts::where('id', $id)->get();

            if (!$no_of_carded_product || count($no_of_carded_product) == 0) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Carded Product not found';
                return response()->json($this->result);
            }

            if (count($no_of_carded_product) > 0) {
                $data = json_decode($no_of_carded_product[0]['data']);

                $new_items = [];

                // dd($data);

                if (count($data) > 0) {
                    foreach ($data as $key => $value) {
                        $atlas_id_old = $value->atlasId;

                        if ($atlas_id_old != $atlas_id) {
                            // same atlas_id
                            array_push($new_items, (array) $value);
                        }
                    }

                    // dd($new_items);

                    $update_quantity = array_push($new_items, [
                        'qty' => $quantity,
                        'atlasId' => $atlas_id,
                        'price' => $price,
                        'total' => $total,
                    ]);

                    // dd($new_items);
                    $no_of_carded_product[0]->data = $new_items;

                    $update_carded_product = $no_of_carded_product[0]->save();

                    if (!$update_carded_product) {
                        $this->result->status = false;
                        $this->result->status_code = 422;
                        $this->result->message =
                            'Carded Product could not be updated';
                        return response()->json($this->result);
                    }

                    $this->result->status = true;
                    $this->result->status_code = 200;
                    $this->result->message =
                        'Carded Product updated successfully';
                    return response()->json($this->result);
                }
            }
        }
    }

    public function delete_carded_product($dealer_id, $atlas_id)
    {
        $carded_product = CardedProducts::where('dealer', $dealer_id)->get();

        if (!$carded_product || count($carded_product) == 0) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Carded Product not found';
            return response()->json($this->result);
        }

        $cart_data = json_decode($carded_product[0]->data, true);

        $new_array = [];

        $atlas_ids = [];

        // print_r($cart_data); exit();

        // print_r($service_parts[0]->data); exit();

        if (is_array($cart_data) && count($cart_data) > 0) {
            foreach ($cart_data as $key => $value) {
                $value = (object) $value;
                $item_atlas_id = $value->atlasId;

                array_push($atlas_ids, $item_atlas_id);

                if ($atlas_id != $item_atlas_id) {
                    array_push($new_array, $value);
                }
            }

            // dd( $atlas_ids );

            // dd( in_array( $atlas_id, $atlas_ids ) );

            if (!in_array($atlas_id, $atlas_ids)) {
                $this->result->data = false;
                $this->result->status_code = 422;
                $this->result->message = 'Item already deleted';
                return response()->json($this->result);
            } else {
                $carded_product[0]->data = json_encode($new_array);

                $update_order = $carded_product[0]->save();

                if (!$update_order) {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message = 'Sorry Item could not be deleted';
                    return response()->json($this->result);
                }

                if (
                    count(json_decode($carded_product[0]->data, true)) == 0 ||
                    empty($carded_product[0]->data) == true
                ) {
                    $carded_product[0]->delete();
                }

                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->message =
                    'Item deleted from Carded Products successfully';
                return response()->json($this->result);
            }
        } else {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Item could not found';
            return response()->json($this->result);
        }
    }

    public function restore_carded_product($id)
    {
        $carded_product = CardedProducts::find($id);

        if (!$carded_product) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Carded Product not found';
            return response()->json($this->result);
        }

        $carded_product->status = 1;

        $update_status = $carded_product->save();

        if (!$update_status) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Carded Product status could not be restored';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Carded Product restored successfully';
        return response()->json($this->result);
    }

    public function fetch_carded_products_by_id($atlas_id)
    {
        $carded_product = CardedProducts::where('atlas_id', $atlas_id)->get();

        if (!$carded_product) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Carded product not found';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $carded_product;

        $this->result->message = 'Carded product fetched successfully';
        return response()->json($this->result);
    }

    // service parts crud
    public function create_service_part(Request $request)
    {
        // `dealer`, `atlas_id`, `quantity`,
        $validator = Validator::make($request->all(), [
            'atlas_id' => 'required',
            'dealer' => 'required',
            'quantity' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'atlas_id' => $validator->errors()->get('atlas_id'),
                'dealer' => $validator->errors()->get('dealer'),
                'quantity' => $validator->errors()->get('quantity'),
            ];
            return response()->json($this->result);
        } else {
            $dealer = $request->input('dealer');
            $atlas_id = $request->input('atlas_id');
            $quantity = $request->input('quantity');

            $create_service_parts = ServiceParts::create([
                'dealer' => $dealer,
                'atlas_id' => $atlas_id,
                'quantity' => $quantity,
            ]);

            if (!$create_service_parts) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry we could not create the Service Part';
                return response()->json($this->result);
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Service Part created successfully';
            return response()->json($this->result);
        }
    }

    public function edit_service_part(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'atlas_id' => 'required',
            'dealer' => 'required',
            'quantity' => 'required|integer',
            'price' => 'required|integer',
            'total' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'id' => $validator->errors()->get('id'),
                'atlas_id' => $validator->errors()->get('atlas_id'),
                'dealer' => $validator->errors()->get('dealer'),
                'quantity' => $validator->errors()->get('quantity'),

                'price' => $validator->errors()->get('price'),
                'total' => $validator->errors()->get('total'),
            ];
            return response()->json($this->result);
        } else {
            $id = $request->input('id');
            $atlas_id = $request->input('atlas_id');
            $dealer = $request->input('dealer');
            $quantity = $request->input('quantity');
            $price = $request->input('price');
            $total = $request->input('total');

            $no_of_service_part = ServiceParts::where('id', $id)->get();

            if (!$no_of_service_part || count($no_of_service_part) == 0) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Service Part not found';
                return response()->json($this->result);
            }

            if (count($no_of_service_part) > 0) {
                $data = json_decode($no_of_service_part[0]['data']);

                $new_items = [];

                // dd($data);

                if (count($data) > 0) {
                    foreach ($data as $key => $value) {
                        $atlas_id_old = $value->atlasId;

                        if ($atlas_id_old != $atlas_id) {
                            // same atlas_id
                            array_push($new_items, (array) $value);
                        }
                    }

                    // dd($new_items);

                    $update_quantity = array_push($new_items, [
                        'qty' => $quantity,
                        'atlasId' => $atlas_id,
                        'price' => $price,
                        'total' => $total,
                    ]);

                    // dd($new_items);
                    $no_of_service_part[0]->data = $new_items;

                    $update_service_part = $no_of_service_part[0]->save();

                    if (!$update_service_part) {
                        $this->result->status = false;
                        $this->result->status_code = 422;
                        $this->result->message =
                            'Service Part could not be updated';
                        return response()->json($this->result);
                    }

                    $this->result->status = true;
                    $this->result->status_code = 200;
                    $this->result->message =
                        'Service Part updated successfully';
                    return response()->json($this->result);
                }
            }

            // if (count($no_of_service_part) > 0) {
            //     $no_of_service_part->atlas_id = $atlas_id;
            //     $no_of_service_part->dealer = $dealer;
            //     $no_of_service_part->quantity = $quantity;

            //     $update_service_part = $no_of_service_part[0]->save();

            //     if (!$update_service_part) {
            //         $this->result->status = false;
            //         $this->result->status_code = 422;
            //         $this->result->message = 'Service Part could not be updated';
            //         return response()->json($this->result);
            //     }

            //     $this->result->status = true;
            //     $this->result->status_code = 200;
            //     $this->result->message = 'Service Part updated successfully';
            //     return response()->json($this->result);
            // }
        }
    }

    public function delete_service_part($dealer_id, $atlas_id)
    {
        $service_parts = ServiceParts::where('dealer', $dealer_id)->get();

        // `dealer`, `data`, `atlasId`

        if (!$service_parts || count($service_parts) == 0) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Service part not found';
            return response()->json($this->result);
        }

        $cart_data = json_decode($service_parts[0]->data, true);

        $new_array = [];

        $atlas_ids = [];

        // print_r($cart_data); exit();

        // print_r($service_parts[0]->data); exit();

        if (is_array($cart_data) && count($cart_data) > 0) {
            foreach ($cart_data as $key => $value) {
                $value = (object) $value;
                $item_atlas_id = $value->atlasId;

                array_push($atlas_ids, $item_atlas_id);

                if ($atlas_id != $item_atlas_id) {
                    array_push($new_array, $value);
                }
            }

            // dd( $atlas_ids );

            // dd( in_array( $atlas_id, $atlas_ids ) );

            if (!in_array($atlas_id, $atlas_ids)) {
                $this->result->data = false;
                $this->result->status_code = 422;
                $this->result->message = 'Item already deleted';
                return response()->json($this->result);
            } else {
                $service_parts[0]->data = json_encode($new_array);

                $update_order = $service_parts[0]->save();

                if (!$update_order) {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message = 'Sorry Item could not be deleted';
                    return response()->json($this->result);
                }

                if (
                    count(json_decode($service_parts[0]->data, true)) == 0 ||
                    empty($service_parts[0]->data) == true
                ) {
                    $service_parts[0]->delete();
                }

                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->message =
                    'Item deleted from Service parts successfully';
                return response()->json($this->result);
            }
        } else {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Item could not found';
            return response()->json($this->result);
        }
    }

    public function restore_service_part($id)
    {
        $service_part = ServiceParts::find($id);

        if (!$service_part) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Service part not found';
            return response()->json($this->result);
        }

        $service_part->status = 1;

        $update_status = $service_part->save();

        if (!$update_status) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Service part status could not be restored';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Service part restored successfully';
        return response()->json($this->result);
    }

    public function fetch_all_service_parts()
    {
        // $fetch_dealers = Dealer::all();

        // $fetch_account_ids = $fetch_dealers->pluck("account_id")->toArray();

        // $all_dealer_ids_order_status = DB::table('atlas_dealers')->wherein('account_id',$fetch_account_ids)->where('order_status',1)->pluck('account_id')->toArray();

        // $service_parts = DB::table('atlas_service_parts')->wherein('dealer',$all_dealer_ids_order_status)->get();

        // return $all_service_parts;
        // $service_parts = ServiceParts::orderby('id', 'ASC')->get();

        // $new_data_array = [];

        // foreach ($service_parts as $value) {
        //     $dealer_id = $value->dealer;

        //     $fetch_dealer_details = Dealer::where('account_id', $dealer_id)->get();

        //     if ($fetch_dealer_details && count($fetch_dealer_details) > 0) {
        //         array_push($new_data_array, $fetch_dealer_details);

        //         $dealer_name = $fetch_dealer_details[0]['first_name'] . ' ' . $fetch_dealer_details[0]['last_name'];

        //         $value->dealer_name = $dealer_name;

        //         $order_date = $fetch_dealer_details[ 0 ][ 'placed_order_date' ];
        //         $value->order_date = $order_date;
        //     } else {
        //         $value->dealer_name = null;
        //     }

        //     $data = ($value->data) ? $value->data : [];
        //     $value->data = gettype($data) == 'array' ? json_encode($data) : json_decode($data, true);
        // }

        // echo print_r($new_data_array); exit();

        $service_orders = DB::table('atlas_service_parts')
            ->join(
                'atlas_dealers',
                'atlas_service_parts.dealer',
                '=',
                'atlas_dealers.account_id'
            )
            ->where('atlas_dealers.order_status', 1)
            ->orderby('atlas_dealers.placed_order_date', 'desc')
            ->select(
                'atlas_service_parts.*',
                'atlas_dealers.full_name',
                'atlas_dealers.first_name',
                'atlas_dealers.last_name',
                'atlas_dealers.placed_order_date as order_date'
            )
            ->get();

        foreach ($service_orders as $value) {
            $value->data = json_decode($value->data);

            $value_data = array_map(function ($record) {
                $atlas_id = $record->atlasId;
                // fetch the item full details of extra products
                $extra_product_details = ExtraProducts::where(
                    'item_code',
                    $atlas_id
                )->get();
                $record->description =
                    $extra_product_details && count($extra_product_details)
                        ? $extra_product_details[0]->description
                        : '';
                return $record;
            }, $value->data);
        }

        if (!$service_orders) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry we could not fetch all the Service parts';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $service_orders;

        $this->result->message = 'All service parts fetched successfully';
        return response()->json($this->result);
    }

    public function fetch_service_parts_by_id($atlas_id)
    {
        $service_part = ServiceParts::where('atlas_id', $atlas_id)->get();

        if (!$service_part) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Service Part not found';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $service_part;

        $this->result->message = 'Service Part fetched successfully';
        return response()->json($this->result);
    }

    public function add_carded_product(Request $request)
    {
        // `dealer`, `atlas_id`, `quantity`,
        $validator = Validator::make($request->all(), [
            'dealer' => 'required',
            'data' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'dealer' => $validator->errors()->get('dealer'),
                'data' => $validator->errors()->get('data'),
            ];
            return response()->json($this->result);
        } else {
            $dealer_id = $request->input('dealer');
            $new_data = $request->input('data');

            // check if a dealer already has a carded item
            $check_dealer = CardedProducts::where('dealer', $dealer_id)->get();

            if (count($check_dealer) > 0) {
                // decode the data
                // $old_data  = is_array($check_dealer[0]->data) ? json_decode($check_dealer[0]->data, true) : [];
                // is_array($data) ? $decode_data = $data : $decode_data = json_decode($data, true);

                // $combine_data = array_merge($old_data, $decode_data);
                // $check_dealer[0]->data = json_encode($combine_data);
                // $update_record = $check_dealer[0]->save();
                // echo var_dump($old_data); exit();

                $old_data = is_array($check_dealer[0]['data'])
                    ? $check_dealer[0]['data']
                    : json_decode($check_dealer[0]['data']);

                // check the items

                $new_records = is_array($new_data)
                    ? $new_data
                    : json_decode($new_data, true);

                $formated_data = array_merge($old_data, (array) $new_records);

                $check_dealer[0]->data = json_encode($formated_data);

                $update_record = $check_dealer[0]->save();

                // add item to cart here

                $add_to_cart = Cart::create([
                    'dealer' => $dealer_id,
                    'carded_data' => json_encode($formated_data),
                ]);

                if (!$update_record || !$add_to_cart) {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message =
                        'Sorry Carded product could not be added';
                    return response()->json($this->result);
                }

                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->message = 'Carded product added successfully';
                return response()->json($this->result);
            }

            $create_carded_product = CardedProducts::create([
                'dealer' => $dealer_id,
                'data' => json_encode($new_data),
            ]);

            if (!$create_carded_product) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry we could not create the carded product';
                return response()->json($this->result);
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Carded Product created successfully';
            return response()->json($this->result);
        }
    }

    public function add_service_part(Request $request)
    {
        // `dealer`, `atlas_id`, `quantity`,
        $validator = Validator::make($request->all(), [
            'dealer' => 'required',
            'data' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'dealer' => $validator->errors()->get('dealer'),
                'data' => $validator->errors()->get('data'),
            ];
            return response()->json($this->result);
        } else {
            // check if the dealer exists in the db
            $new_data = $request->input('data');
            $dealer = $request->input('dealer');

            $check_dealer = ServiceParts::where('dealer', $dealer)->get();

            if (count($check_dealer) > 0) {
                // echo var_dump($old_data); exit();
                $old_data = is_array($check_dealer[0]['data'])
                    ? $check_dealer[0]['data']
                    : json_decode($check_dealer[0]['data']);

                // check the items

                $new_records = is_array($new_data)
                    ? $new_data
                    : json_decode($new_data, true);

                $formated_data = array_merge($old_data, (array) $new_records);

                $check_dealer[0]->data = json_encode($formated_data);

                $update_record = $check_dealer[0]->save();

                // add item to cart here

                $add_to_cart = Cart::create([
                    'dealer' => $dealer,
                    'catalogue_data' => json_encode($formated_data),
                ]);

                if (!$update_record || !$add_to_cart) {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message =
                        'Sorry Service Part could not be added';
                    return response()->json($this->result);
                }

                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->message = 'Service Part added successfully';
                return response()->json($this->result);
            } else {
                $create_service_parts = ServiceParts::create([
                    'dealer' => $dealer,
                    'data' => json_encode($new_data),
                ]);

                if (!$create_service_parts) {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message =
                        'Sorry we could not create the Service Part';
                    return response()->json($this->result);
                }

                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->message = 'Service Part created successfully';
                return response()->json($this->result);
            }
        }
    }

    public function fetch_all_new_products()
    {
        $products = Products::where('check_new', '1')
            ->orderBy('id', 'DESC')
            ->get();

        if (!$products) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry we could not fetch the products';
            return response()->json($this->result);
        }

        foreach ($products as $value) {
            $spec_data = $value->spec_data
                ? json_decode($value->spec_data)
                : [];

            $value->spec_data = $spec_data;
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $products;
        $this->result->message = 'All new Products fetched successfully';
        return response()->json($this->result);

        // return $products;

        // $new_products = [];

        // foreach ($products as $product) {
        //     $is_new =  $this->check_if_its_new($product['created_at'], 10,$product['atlas_id']) == true ? true : false;

        //     $spec_data = ($product->spec_data) ? json_decode($product->spec_data) : [];

        //     $product->spec_data = $spec_data;

        //     if ($is_new) {
        //         array_push($new_products, $product);
        //     }
        // }

        // return $new_products;

        // $format_products = array_map(
        //     function ($record) {
        //         $records = json_decode($record, true);
        //         return array_merge([
        //             'is_new' => true
        //         ], $records);
        //     },
        //     $new_products
        // );

        // array_merge($new_products,['is_new' => true]);
    }

    public function delete_catalogue_order($dealer, $atlas_id)
    {
        $check_catalogue_order = Catalogue_order::where('dealer', $dealer)
            ->where('status', '1')
            ->get();

        if (!$check_catalogue_order || count($check_catalogue_order) == 0) {
            // catalogue_order allready deactivated
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry catalogue order doesn\'t exist or has already been deactivated';
            return response()->json($this->result);
        }

        $cart_data = json_decode($check_catalogue_order[0]->data, true);

        $new_array = [];

        $atlas_ids = [];

        // print_r($cart_data); exit();

        // print_r($service_parts[0]->data); exit();

        if (is_array($cart_data) && count($cart_data) > 0) {
            foreach ($cart_data as $key => $value) {
                $value = (object) $value;
                $item_atlas_id = $value->atlasId;

                array_push($atlas_ids, $item_atlas_id);

                if ($atlas_id != $item_atlas_id) {
                    array_push($new_array, $value);
                }
            }

            // dd( $atlas_ids );

            // dd( in_array( $atlas_id, $atlas_ids ) );

            if (!in_array($atlas_id, $atlas_ids)) {
                $this->result->data = false;
                $this->result->status_code = 422;
                $this->result->message = 'Item already deleted';
                return response()->json($this->result);
            } else {
                $check_catalogue_order[0]->data = json_encode($new_array);

                $update_order = $check_catalogue_order[0]->save();

                if (!$update_order) {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message = 'Sorry Item could not be deleted';
                    return response()->json($this->result);
                }

                if (
                    count(json_decode($check_catalogue_order[0]->data, true)) ==
                        0 ||
                    empty($check_catalogue_order[0]->data) == true
                ) {
                    $check_catalogue_order[0]->delete();
                }

                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->message =
                    'Item deleted from Catalogue order successfully';
                return response()->json($this->result);
            }
        } else {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Item could not found';
            return response()->json($this->result);
        }
    }

    public function restore_catalogue_order($dealer)
    {
        $check_catalogue_order = Catalogue_order::where('dealer', $dealer)
            ->where('status', '0')
            ->get();

        if (!$check_catalogue_order || count($check_catalogue_order) == 0) {
            // catalogue_order allready deactivated
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry catalogue order doesn\'t exist or is already active';
            return response()->json($this->result);
        }

        // deactivate the catalogue_order
        $check_catalogue_order[0]->status = 1;
        $update_catalogue_order = $check_catalogue_order[0]->save();

        if ($update_catalogue_order) {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message =
                'Catalogue order  has been restored successfully';
            return response()->json($this->result);
        }
    }

    public function submit_carded_products($dealer_id)
    {
        $fetch_carded_product = CardedProducts::where(
            'dealer',
            $dealer_id
        )->get();

        if (!$fetch_carded_product || count($fetch_carded_product) == 0) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Carded Product not found';
            return response()->json($this->result);
        }
        // check if the carded product has already been completed

        if ($fetch_carded_product[0]->completed == 1) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry You can only make one carded product order';
            return response()->json($this->result);
        } else {
            // $fetch_carded_product[0]->completed = 1;

            foreach ($fetch_carded_product as $item) {
                $update_completed_status = $item->update([
                    'completed' => 1,
                ]);

                if (!$update_completed_status) {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message =
                        'Sorry we cannot submit your order at the moment.';
                    return response()->json($this->result);
                }
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message =
                'Carded Products Order submitted successfully';
            return response()->json($this->result);
        }
    }

    public function submit_service_parts($dealer_id)
    {
        $fetch_service_parts = ServiceParts::where('dealer', $dealer_id)->get();

        if (!$fetch_service_parts || count($fetch_service_parts) == 0) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Service Part not found';
            return response()->json($this->result);
        }
        // check if the carded product has already been completed

        if ($fetch_service_parts[0]->completed == 1) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry You can only make one service part order';
            return response()->json($this->result);
        } else {
            // $fetch_service_parts[0]->completed = 1;

            foreach ($fetch_service_parts as $item) {
                $update_completed_status = $item->update([
                    'completed' => 1,
                ]);

                if (!$update_completed_status) {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message =
                        'Sorry we cannot submit your order at the moment.';
                    return response()->json($this->result);
                }
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message =
                'Service Parts Order submitted successfully';
            return response()->json($this->result);
        }
    }

    public function submit_catalogue_order($dealer_id)
    {
        $fetch_catalogue_order = Catalogue_order::where(
            'dealer',
            $dealer_id
        )->get();

        if (!$fetch_catalogue_order || count($fetch_catalogue_order) == 0) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Catalogue Product not found';
            return response()->json($this->result);
        }
        // check if the carded product has already been completed

        if ($fetch_catalogue_order[0]->completed == 1) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry You can only make one Catalogue order';
            return response()->json($this->result);
        } else {
            // $fetch_catalogue_order[0]->completed = 1;

            foreach ($fetch_catalogue_order as $item) {
                $update_completed_status = $fetch_catalogue_order->update([
                    'completed' => 1,
                ]);

                if (!$update_completed_status) {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message =
                        'Sorry we cannot submit your order at the moment.';
                    return response()->json($this->result);
                }
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message =
                'Catalogue Product Order submitted successfully';
            return response()->json($this->result);
        }
    }

    public function cart_count($dealer_id)
    {
        $fetch_dealer_cart = Cart::where('dealer', $dealer_id)
            ->where('status', '0')
            ->get();

        if (!$fetch_dealer_cart) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry we could not fetch the Cart details';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data->cart_number = count($fetch_dealer_cart);
        $this->result->message = 'Cart number fetched successfully';
        return response()->json($this->result);
    }

    public function delete_all_cart_items($dealer_id)
    {
        // check if the dealer has an item in the cart
        $check_cart = Cart::where('dealer', $dealer_id)->delete();
        $dealer_data = Dealer::where('id', $dealer_id)
            ->get()
            ->first();

        // if (!$check_cart) {
        //     $this->result->status = false;
        //     $this->result->status_code = 422;
        //     $this->result->message =
        //         'Sorry we could not fetch the Cart details';
        //     return response()->json($this->result);
        // }

        // if (count($check_cart) == 0) {
        //     $this->result->status = false;
        //     $this->result->status_code = 422;
        //     $this->result->message = 'Sorry you have no item in the cart';
        //     return response()->json($this->result);
        // } else {

        // delete the items in the cart
        $account_id = $dealer_data->account_id;

        Catalogue_order::where('dealer', $account_id)->delete();
        ServiceParts::where('dealer', $account_id)->delete();
        CardedProducts::where('dealer', $account_id)->delete();

        // foreach ($check_cart as $cart_item) {
        //     $delete_item = $cart_item->delete();
        // }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'All items Successfully deleted from cart';
        return response()->json($this->result);
        // }
    }

    public function view_dealer_order_by_acct_id($account_id)
    {
        // $data =  DB::table( 'atlas_user_cart' )
        // ->select( 'atlas_user_cart.cart_data', 'atlas_user_cart.user_id', 'atlas_user_cart.id', 'atlas_dealers.first_name', 'atlas_dealers.last_name' )
        // ->join( 'atlas_dealers', 'atlas_user_cart.user_id', '=', 'atlas_dealers.id' )
        // ->where( 'atlas_user_cart.id', $id )
        // ->get();

        $get_dealer_details = Dealer::where('account_id', $account_id)->get();

        if ($get_dealer_details && count($get_dealer_details) > 0) {
            $dealer_id = $get_dealer_details[0]['id'];

            // dd($dealer_id);

            $data = DB::table('cart')
                ->select(
                    'atlas_dealers.account_id',
                    'cart.id',
                    'atlas_dealers.first_name',
                    'atlas_dealers.last_name',
                    'cart.*'
                )
                ->join('atlas_dealers', 'cart.dealer', '=', 'atlas_dealers.id')
                ->where('cart.dealer', $dealer_id)
                ->get();

            if ($data) {
                foreach ($data as $value) {
                    $spec_data = $value->spec_data
                        ? json_decode($value->spec_data)
                        : [];
                    $value->spec_data = $spec_data;
                }
            } else {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry we could not fetch all the Orders';
                return response()->json($this->result);
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $data;
            $this->result->message = 'View Dealer Orders';
            return response()->json($this->result);
        } else {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry dealer doesnt exist or has been deactivated';
            return response()->json($this->result);
        }
    }

    public function get_all_loggedin_dealers()
    {
        $all_loggedin_dealers = Dealer::where('last_login', '!=', null)->get();

        if (!$all_loggedin_dealers || count($all_loggedin_dealers) == 0) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry No logged in user found';
            return response()->json($this->result, 422);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $all_loggedin_dealers;
        $this->result->message = 'All logged in dealers found successfully';
        return response()->json($this->result, 200);
    }

    public function attach_img_url_to_products()
    {
        $all_products = Products::get()->toArray();

        // return $all_products;

        $attach_image_url = array_map(function ($record) {
            $product_id = $record['id'];
            // $product_url = $record['img'];
            $product_atlas_id = $record['atlas_id'];

            $product_vendor_logo = $record['vendor_logo'];

            // https://atlasbookingprogram.com/assets/2023/products/100-18.jpg

            $new_img_url =
                'https://atlasbookingprogram.com/assets/2023/products/' .
                $product_atlas_id .
                '.jpg';

            $new_vendor_logo =
                'https://atlasbookingprogram.com/assets/2023/vendors/' .
                $product_vendor_logo;
            // $record['img'] = $new_img_url;

            // update the database

            $update_record = Products::find($product_id);

            $final_update = $update_record->update([
                'img' => $new_img_url,
                'vendor_logo' => $new_vendor_logo,
            ]);

            return $record;
        }, $all_products);

        return $attach_image_url;
    }

    public function add_carded_product_to_cart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dealer' => 'required',
            'data' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'dealer' => $validator->errors()->get('dealer'),
                'data' => $validator->errors()->get('data'),
            ];
            return response()->json($this->result);
        } else {
            $dealer_id = $request->input('dealer');
            $new_data = $request->input('data');

            // `id`, `dealer`, `pro_id`, `atlas_id`, `qty`, `price`, `unit_price`,
            //  `status`, `created_at`, `updated_at`, `desc`, `pro_img`,
            // `vendor_img`, `spec_data`, `grouping`, `booking`, `category`, `um`, `xref`

            $add_carded_to_cart = Cart::create([
                'dealer' => $dealer_id,
                'status' => true,
                'carded_data' => json_encode($new_data),
            ]);

            if (!$add_carded_to_cart) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry we could not add the carded product to cart';
                return response()->json($this->result, 422);
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $add_carded_to_cart;
            $this->result->message =
                'Carded Product added to cart successfully';
            return response()->json($this->result, 200);
        }
    }

    public function add_other_product_type_to_cart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dealer' => 'required',
            'data' => 'required',
            'type' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'dealer' => $validator->errors()->get('dealer'),
                'data' => $validator->errors()->get('data'),
                'type' => $validator->errors()->get('type'),
            ];
            return response()->json($this->result);
        } else {
            $dealer_id = $request->input('dealer');
            $new_data = $request->input('data');
            $type = $request->input('type');

            switch ($type) {
                case 'carded_products':
                    $add_carded_to_cart = Cart::create([
                        'dealer' => $dealer_id,
                        'status' => true,
                        'carded_data' => json_encode($new_data),
                    ]);
                    $add_carded_to_cart->carded_data = json_decode(
                        $add_carded_to_cart->carded_data
                    );
                    break;
                case 'service_parts_products':
                    $add_carded_to_cart = Cart::create([
                        'dealer' => $dealer_id,
                        'status' => true,
                        'service_data' => json_encode($new_data),
                    ]);
                    $add_carded_to_cart->service_data = json_decode(
                        json_decode($add_carded_to_cart->service_data)
                    );
                    break;
                case 'catalogue_products':
                    $add_carded_to_cart = Cart::create([
                        'dealer' => $dealer_id,
                        'status' => true,
                        'catalogue_data' => json_encode($new_data),
                    ]);
                    $add_carded_to_cart->catalogue_data = json_decode(
                        $add_carded_to_cart->catalogue_data
                    );
                    break;
                default:
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message =
                        'please add a valid type ie. carded_products,service_parts_products, or catalogue_products';
                    return response()->json($this->result, 422);
                    break;
            }

            if (!$add_carded_to_cart) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry we could not add the carded product to cart';
                return response()->json($this->result, 422);
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $add_carded_to_cart;
            $this->result->message =
                ucwords(str_replace('_', ' ', $type)) .
                ' added to cart successfully';
            return response()->json($this->result, 200);
        }
    }

    public function reset_password_send_code_email(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'account_id' => 'required',
            'reset_url' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'email' => $validator->errors()->get('email'),
                'account_id' => $validator->errors()->get('account_id'),
                'reset_url' => $validator->errors()->get('reset_url'),
            ];
            return response()->json($this->result);
        } else {
            $email = $request->input('email');
            $account_id = $request->input('account_id');
            $reset_url = $request->input('reset_url');
            // check if email exists in the db

            $check_email = Dealer::where(
                'email',
                $email
            )->where('account_id',$account_id)->get();

            if (!$check_email) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Sorry Email with account id could not be verified';
                return response()->json($this->result);
            }

            // check if the email exists
            if (count($check_email) == 0) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry Email with account id does\'t exists in our records';
                return response()->json($this->result);
            } else {
                // email exists

                // generate code
                $code = Str::random(10);

                // get dealer's credentials
                $dealer_email = $check_email[0]->email;
                $dealer_id = $check_email[0]->id;
                $dealer_account_id = $check_email[0]->account_id;

                // save the details
                $save_details = ResetPassword::create([
                    'dealer_id' => $dealer_id,
                    'code' => $code,
                    'account_id' => $dealer_account_id,
                    'email' => $dealer_email,
                ]);

                if (!$save_details) {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->data = $save_details;
                    $this->result->message =
                        'Sorry we could not generate code.';
                    return response()->json($this->result);
                }

                // send the email

                $data = [
                    'code' => $code,
                    'reset_url' => $reset_url,
                    'account_id' => $account_id,
                    'email' => $dealer_email,
                ];

                // return $data;

                Mail::to($dealer_email)->send(
                    new PasswordResetEmailCode($data)
                );

                // send the code reset_url

                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->message =
                    'Email Code generated and sent successfully';
                return response()->json($this->result);
            }
        }
    }

    public function reset_password_verify_code_email($email,$account_id, $code)
    {
        // $validator = Validator::make($request->all(), [
        //     // 'email' => 'required',
        //     'code'=> 'required'
        // ]);

        // if ($validator->fails()) {
        //     $this->result->status_code = 422;
        //     $this->result->message = [
        //         // 'email' => $validator->errors()->get('email'),
        //         'code' => $validator->errors()->get('code'),
        //     ];
        //     return response()->json($this->result);
        // } else {
        // $email = $request->input('email');

        // $code = $request->input('code');
        // check if email exists in the db

        $check_code = ResetPassword::where('email', $email)
            ->where('account_id',$account_id)
            ->get()
            ->last();

        // return $check_code;

        if (!$check_code) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry Email could not be verified. Try again later.';
            return response()->json($this->result);
        }

        // check the code has expired
        if ($check_code->status == 0) {
            // code is incorrect and has expired
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry, Code has expired. Try again later. ';
            return response()->json($this->result);
        }

        // check if the codes match
        if ($check_code->code !== $code) {
            // code is incorrect
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Wrong Code, Kindly verify code and try again.';
            return response()->json($this->result);
        }

        // update the record status to 0
        // to deactivate the code we change it to 0
        $update_record_status = ResetPassword::where('email', $email)
            ->where('account_id',$account_id)
            ->where('code', $code)
            ->update([
                'status' => 0,
            ]);

        // return $update_record_status;

        if (!$update_record_status) {
            // we could not update the process status
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry we could not update the process status, Kindly try again later.';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Password reset code verified successfully';
        return response()->json($this->result);
    }

    public function reset_dealer_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_password' => 'required',
            'email' => 'required',
            'account_id' => 'required',
            'confirm_new_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422; // unprocessed entity
            $this->result->message = [
                'new_password' => $validator->errors()->get('new_password'),
                'email' => $validator->errors()->get('email'),
                'account_id' => $validator->errors()->get('account_id'),
                'confirm_new_password' => $validator
                    ->errors()
                    ->get('confirm_new_password'),
            ];
            return response()->json($this->result);
        } else {
            $email = $request->input('email');
            $account_id = $request->input('account_id');
            $password = $request->input('new_password');
            $hash_password = Hash::make($request->input('new_password'));

            $update_dealer_details = Dealer::where('email', $email)->where('account_id',$account_id)->update([
                "password" => $hash_password,
                "password_clear" => $password
            ]);

            // return $dealer_details;
            // $dealer_details
            // $dealer_details[0]->password = $hash_password;
            // $update_dealer_details = $dealer_details->save();

            if (!$update_dealer_details) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Sorry Password could not be changed';
                return response()->json($this->result, 422);
            } else {
                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->message = 'Password changed successfully';
                return response()->json($this->result, 200);
            }
        }
    }

    public function export_all_carded_orders(Request $request)
    {
        $from = $request->query('from') != '' ? $request->query('from') : false;
        $to = $request->query('to') != '' ? $request->query('to') : false;

        $from = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();

        if ($from && $to) {
            $dealer = Dealer::query()
                ->where('order_status', '1')
                ->whereBetween('placed_order_date', [$from, $to])
                ->get();

            $excel_data = AdminController::load_carded_query($dealer);

            $this->result->status = true;
            $this->result->data = $excel_data;
            return response()->json($this->result);
        } elseif ($from) {
            $dealer = Dealer::query()
                ->where('order_status', '1')
                ->where('placed_order_date', '>=', $from)
                ->get();

            $excel_data = AdminController::load_carded_query($dealer);
            $this->result->status = true;
            $this->result->data = $excel_data;
            return response()->json($this->result);
        } elseif ($to) {
            $this->result->status = true;
            $this->result->data->result_data = [];
            return response()->json($this->result);
        } else {
            $dealer = Dealer::query()
                ->where('order_status', '1')
                ->get();

            $excel_data = AdminController::load_carded_query($dealer);
            $this->result->status = true;
            $this->result->data = $excel_data;
            return response()->json($this->result);
        }
    }

    public function export_all_cart_orders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'required',
            'to' => 'required',
            'dealer_id' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'from' => $validator->errors()->get('from'),
                'to' => $validator->errors()->get('to'),
            ];
            return response()->json($this->result);
        } else {
            $from =
                $request->input('from') != '' ? $request->input('from') : false;
            $to = $request->input('to') != '' ? $request->input('to') : false;

            $from = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
            $to = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();

            $dealer_id = $request->input('dealer_id');

            if ($from && $to) {
                $dealer_cart = Cart::query()
                    ->where('dealer', $dealer_id)
                    ->where('status', '1')
                    ->whereBetween('created_at', [$from, $to])
                    ->get();

                foreach ($dealer_cart as $item) {
                    $item['spec_data'] = json_decode($item['spec_data']);
                }

                $this->result->status = true;
                $this->result->data = $dealer_cart;
                return response()->json($this->result);
            } elseif ($from) {
                $dealer_cart = Dealer::query()
                    ->where('dealer', $dealer_id)
                    ->where('status', '1')
                    ->where('created_at', '>=', $from)
                    ->get();

                $this->result->status = true;
                $this->result->data = $dealer_cart;
                return response()->json($this->result);
            } elseif ($to) {
                $this->result->status = true;
                $this->result->data = [];
                return response()->json($this->result);
            } else {
                $dealer_cart = Cart::query()
                    ->where('dealer', $dealer_id)
                    ->where('status', '1')
                    ->get();

                $this->result->status = true;
                $this->result->data = $dealer_cart;
                return response()->json($this->result);
            }
        }
    }

    public function test()
    {
        $result = (new AdminController())->fetch_locations();

        print $result;
    }
}
