<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\BranchAssignDealer;
use App\Models\Dealer;
use App\Models\DealerCart;
use App\Models\Catalogue_Order;
use App\Models\ServiceParts;
use App\Models\CardedProducts;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Orders;
use App\Models\Products;
use Carbon\Carbon;
use DB;
use Barryvdh\DomPDF\Facade as PDF;

class BranchController extends Controller
{
    //
    public function __construct()
    {
        //// $this->middleware( 'auth:api', [ 'except' => [ 'login', 'register', 'test' ] ] );
        $this->result = (object) [
            'status' => false,
            'status_code' => 200,
            'message' => null,
            'data' => (object) null,
            'token' => null,
            'debug' => null,
        ];
    }

    public function fetch_all_new_product_branch()
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
    }

    public function fetch_branch_by_id($id)
    {
        $branch_details = Branch::where('id', $id)->first();

        if (!$branch_details) {
            $this->result->status = true;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Branch details not found';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $branch_details;
        $this->result->message = 'Branch details fetched successfully';
        return response()->json($this->result);
    }

    public function login(Request $request)
    {
        // validate credentials
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (
            !($token = Auth::guard('branch')->attempt([
                'email' => $request->email,
                'password' => $request->password,
            ]))
        ) {
            $this->result->status_code = 401;
            $this->result->message = 'Invalid login credentials';
            return response()->json($this->result);
        }

        $active_staff = Branch::query()
            ->where('email', $request->email)
            ->get()
            ->first();

        if ($active_staff['status'] == 0) {
            $this->result->status_code = 401;
            $this->result->message = 'Account has been deactivated';
            return response()->json($this->result);
        }

        $branch = Branch::where('email', $request->email)->first();
        $branch->role = 'branch';

        // save the branch login

        $this->result->token = $this->respondWithToken($token);
        $this->result->status = true;
        $this->result->data->branch = $branch;
        return response()->json($this->result);
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

    public function fetch_all_branches()
    {
        $branches = Branch::all();

        if (!$branches) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry No branch Available';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $branches;
        $this->result->message = 'All Branches fetched Successfully';
        return response()->json($this->result);
    }

    public function assign_dealer_to_branch(Request $request)
    {
        // `branch_id`, `dealer_id`,
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required',
            'dealers' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'branch_id' => $validator->errors()->get('branch_id'),
                'dealers' => $validator->errors()->get('dealers'),
            ];
            return response()->json($this->result);
        } else {
            $branch_id = $request->input('branch_id');
            $dealers = $request->input('dealers');

            // return $dealers;

            $not_inserted_dealers = [];

            // already exists
            $already_exists_dealers = [];

            // assigned dealers
            $assigned_dealers = [];

            foreach ($dealers as $dealer_id) {
                // check if the dealer has already been assigned
                $check_dealer = BranchAssignDealer::where(
                    'dealer_id',
                    $dealer_id
                )
                    ->where('branch_id', $branch_id)
                    ->get();

                if (count($check_dealer) > 0) {
                    // dealer already exists
                    array_push($already_exists_dealers, $dealer_id);
                } else {
                    $save_dealer = BranchAssignDealer::create([
                        'branch_id' => $branch_id,
                        'dealer_id' => $dealer_id,
                    ]);

                    if (!$save_dealer) {
                        array_push($not_inserted_dealers, $dealer_id);
                    } else {
                        array_push($assigned_dealers, $dealer_id);
                    }
                }
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = [
                'existing_dealers' => (array) $already_exists_dealers,
                'assigned_dealers' => (array) $assigned_dealers,
                //  this shows that the dealers have already been assigned to the branch
            ];

            $this->result->message =
                'Dealers have been successfully assigned to the branch';
            return response()->json($this->result);
        }
        // BranchAssignDealer
    }

    // fetch all the dealers assigned to a branch
    public function fetch_dealer_by_branch($branch_id)
    {
        $fetch_branch_details = Branch::where('id', $branch_id)
            ->where('status', '1')
            ->first();

        if (!$fetch_branch_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->data = [];
            $this->result->message = 'Sorry branch doesnt exist or deactivated';
            return response()->json($this->result);
        } else {
            $fetch_dealers = BranchAssignDealer::where(
                'branch_id',
                $branch_id
            )->get();

            if (!$fetch_dealers) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->data = [];
                $this->result->message =
                    'Sorry we could not fetch all the dealers assigned to the branch';
                return response()->json($this->result);
            }

            $dealer_array = [];

            foreach ($fetch_dealers as $dealer) {
                $dealer = (object) $dealer;
                // check if the dealer has service part, carded or catalogue products

                $fetch_dealer_details = Dealer::where(
                    'account_id',
                    $dealer->dealer_id
                )->first();
                if ($fetch_dealer_details) {
                    array_push($dealer_array, $fetch_dealer_details);
                }
            }

            // return $dealer_array;

            $format_dealer_array = array_map(function ($record) {
                $dealer_id = $record->account_id;

                $dealer_user_id = $record->id;

                // return $dealer_id;

                $record->total_carded_price = 0;

                $record->total_service_price = 0;

                $record->total_catalogue_price = 0;

                $check_service_parts = ServiceParts::where(
                    'dealer',
                    $dealer_id
                )->get();

                if (count($check_service_parts) == 0) {
                    $record->has_service_parts = 0; // doesnt have catalogue orders 
                } else {
                    // get the status 

                    $completed_service_parts = $check_service_parts[0]->completed;
                    if ($completed_service_parts == 1) {
                        // active 
                        $record->has_service_parts = 1;
                    }

                    if ($completed_service_parts == 0) {
                        // pending 

                        // lets get the pending service parts 
                        
                        $record->total_service_price = $this->service_parts_total_pending_price($dealer_id);

                        $record->has_service_parts = 2;
                    }
                }

                // catalogue starts here 

                // check for catalogue products
                $check_catalogue_products = Catalogue_Order::where(
                    'dealer',
                    $dealer_id
                )->get();

                if (count($check_catalogue_products) == 0) {
                    $record->has_catalogue_products = 0; // doesnt have catalogue orders 
                } else {
                    // get the status 

                    $completed_catalogue = $check_catalogue_products[0]->completed;
                    if ($completed_catalogue == 1) {
                        // active 
                        $record->has_catalogue_products = 1;
                    }

                    if ($completed_catalogue == 0) {
                        // pending 

                        // get all the total pending orders

                        $record->total_catalogue_price = $this->catalogue_total_pending_price($dealer_id);

                        $record->has_catalogue_products = 2;
                    }
                }

                // 1 equals active, 0 pending 

                // check for carded products

                $check_carded_products = CardedProducts::where(
                    'dealer',
                    $dealer_id
                )->get();

                if (count($check_carded_products) == 0) {
                    $record->has_carded_products = 0; // doesnt have catalogue orders 
                } else {

                    // get the carded products data 
                    $carded_product_data = $check_carded_products[0]->data;

                    // return $check_carded_products[0]; 
                    // array_column($carded_product_data,'total');

                    // get the status 

                    $completed_carded = $check_carded_products[0]->completed;
                    if ($completed_carded == 1) {
                        // active 
                        $record->has_carded_products = 1;
                    }

                    if ($completed_carded == 0) {
                        // pending 

                        
                        $record->total_carded_price = $this->carded_total_pending_price($dealer_id);
                        $record->has_carded_products = 2;
                    }
                }


                // check if the dealer has an item in cart return 0
                $check_cart = Cart::where('dealer', $dealer_user_id)->get();

                if (count($check_cart) == 0) {
                    $record->order_status = 0;
                }

                // doesnt have item in the cart and has not submitted return 2 pending
                $check_cart = Cart::where('dealer', $dealer_user_id)
                    ->where('status', 0)
                    ->get();

                if (count($check_cart) > 0) {
                    $record->order_status = 2;

                    // lets sum the total pending amount for cart items 
                    $sum_pending_cart_amount = Cart::where('dealer', $dealer_user_id)
                        ->where('status', 0)
                        ->sum('price');


                    $record->pending_total = number_format(
                        $sum_pending_cart_amount + $record->total_service_price + $record->total_carded_price + $record->total_catalogue_price ,
                        2
                    );
                } else {
                    $record->pending_total = number_format(0, 2);
                }

                // check if the person has submitted return 1

                $check_cart = Cart::where('dealer', $dealer_user_id)
                    ->where('status', 1)
                    ->get();

                if (count($check_cart) > 0) {
                    $record->order_status = 1;

                    // lets get the total submitted amount
                    $sum_submitted_amount = Cart::where(
                        'dealer',
                        $dealer_user_id
                    )
                        ->where('status', 1)
                        ->sum('price');

                    $record->submitted_total = number_format(
                        $sum_submitted_amount,
                        2
                    );
                } else {
                    $record->submitted_total = number_format(0, 2);
                }

                return $record;
            }, $dealer_array);

            // lets sort the data via account_id
            // return gettype($format_dealer_array);

            usort($format_dealer_array, function ($a, $b) {
                return strcmp($a['account_id'], $b['account_id']);
            });

            // return $format_dealer_array;

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = [
                'branch' => $fetch_branch_details,
                'dealers' =>
                $format_dealer_array && count($format_dealer_array) > 0
                    ? $format_dealer_array
                    : [],
            ];
            $this->result->message = 'All Branches fetched Successfully';
            return response()->json($this->result);
        }
    }

    // get total_pending catalogue price
    public function catalogue_total_pending_price($dealer_account_id){

        $get_catalogue_orders = Catalogue_Order::where('dealer', $dealer_account_id)->get();

        $format_catalogue_order_data =  json_decode($get_catalogue_orders[0]->data);

        $total_number_catalogue_orders = count($format_catalogue_order_data);

        $total_catalogue_price = 0;

        foreach ($format_catalogue_order_data as $catalogue_product) {
            $total_catalogue_price += $catalogue_product->total;
        }

        return $total_catalogue_price;
    }

    // get total carded pending price 
    public function carded_total_pending_price($dealer_account_id){
        $get_carded_products_orders = CardedProducts::where('dealer', $dealer_account_id)->get();

        $format_carded_products_order_data =  json_decode($get_carded_products_orders[0]->data);

        $total_number_carded_products_orders = count($format_carded_products_order_data);

        $total_carded_products_price = 0;

        foreach ($format_carded_products_order_data as $carded_products_product) {
            $total_carded_products_price += $carded_products_product->total;
        }

        return $total_carded_products_price;
    }


    // get total service parts pending price 

    public function service_parts_total_pending_price($dealer_account_id){
        $get_service_parts_orders = ServiceParts::where('dealer', $dealer_account_id)->get();

        $format_service_parts_order_data =  json_decode($get_service_parts_orders[0]->data);

        $total_number_service_parts_orders = count($format_service_parts_order_data);

        $total_service_parts_price = 0;

        foreach ($format_service_parts_order_data as $service_parts_product) {
            $total_service_parts_price += $service_parts_product->total;
        }

        return  $total_service_parts_price;
    }


    public function sort_data($a, $b, $field)
    {
        return strcmp($a[$field], $b[$field]);
    }

    public function deactivate_branch($id)
    {
        $branch = Branch::find($id);

        if (!$branch) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Branch not found';
            return response()->json($this->result);
        }

        $delete_branch = $branch->delete();

        if (!$delete_branch) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Branch could not be deactivated';
            return response()->json($this->result);
        }

        $branch->status = 0;

        $update_status = $branch->save();

        if (!$update_status) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Branch status could not be changed';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Branch deactivated successfully';
        return response()->json($this->result);
    }

    public function restore_branch($id)
    {
        $branch = Branch::find($id);

        if (!$branch) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Branch not found';
            return response()->json($this->result);
        }

        $branch->status = 1;

        $update_status = $branch->save();

        if (!$update_status) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Branch status could not be restored';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Branch restored successfully';
        return response()->json($this->result);
    }

    public function view_branch_dealers_orders($branch_id)
    {
        $fetch_branch_details = Branch::where('id', $branch_id)
            ->where('status', '1')
            ->first();

        if (!$fetch_branch_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->data = [];
            $this->result->message = 'Sorry branch doesnt exist or deactivated';
            return response()->json($this->result);
        } else {
            $fetch_dealers = BranchAssignDealer::where(
                'branch_id',
                $branch_id
            )->get();

            if (!$fetch_dealers) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->data = [];
                $this->result->message =
                    'Sorry we could not fetch all the dealers assigned to the branch';
                return response()->json($this->result);
            }
        }
    }

    public function branch_dashboard($branch_id)
    {
        $fetch_branch_details = Branch::where('id', $branch_id)->first();

        // return $fetch_branch_details;

        if (!$fetch_branch_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry branch doesnt exist';
            return response()->json($this->result);
        } else {
            $fetch_dealers = BranchAssignDealer::where(
                'branch_id',
                $branch_id
            )->get();

            $fetch_account_ids = $fetch_dealers->pluck('dealer_id')->toArray();

            $all_dealer_ids = DB::table('atlas_dealers')
                ->wherein('account_id', $fetch_account_ids)
                ->where('order_status', 1)
                ->pluck('id')
                ->toArray();

            // $all_catalogue_orders = DB::table('atlas_catalogue_orders')->wherein('dealer',$fetch_account_ids)->get();

            // return $all_dealer_ids;

            $all_dealer_ids_order_status = DB::table('atlas_dealers')
                ->wherein('account_id', $fetch_account_ids)
                ->where('order_status', 1)
                ->pluck('account_id')
                ->toArray();

            $all_catalogue_orders = DB::table('atlas_catalogue_orders')
                ->wherein('dealer', $all_dealer_ids_order_status)
                ->count();

            $all_service_parts = DB::table('atlas_service_parts')
                ->wherein('dealer', $all_dealer_ids_order_status)
                ->count();

            $all_carded_products = DB::table('atlas_carded_products')
                ->wherein('dealer', $all_dealer_ids_order_status)
                ->count();

            $all_orders = DB::table('atlas_dealers')
                ->wherein('id', $all_dealer_ids)
                ->where('order_status', '1')
                ->count();

            $all_orders_with_values = DB::table('atlas_dealers')
                ->wherein('id', $all_dealer_ids)
                ->where('order_status', '1')
                ->get();

            $total_amount = DB::table('cart')
                ->wherein('dealer', $all_dealer_ids)
                ->sum('price');

            // return $total_amount;

            $all_recent_with_order_dealer_ids = DB::table('atlas_dealers')
                ->wherein('account_id', $fetch_account_ids)
                ->where('order_status', '1')
                ->orderby('placed_order_date', 'desc')
                ->select(
                    'id',
                    'account_id',
                    'first_name',
                    'last_name',
                    'updated_at',
                    'placed_order_date'
                )
                ->get()
                ->take(5);

            // get all the amount earned

            // dealer_order_date
            // return $all_recent_with_order_dealer_ids;

            // $all_amounts_ordered = DB::table('cart')->wherein('id',$all_dealer_ids)->pluck('price')->toArray();

            // ->pluck('price')->toArray()

            // return $all_amounts_ordered;

            // return $all_amounts_ordered;

            // get all the dealers that have made order

            $get_recent_order_Details = array_map(function ($record) {
                // $record = (object) $record;
                $dealer_id = $record['id'];
                $dealer_account_id = $record['account_id'];
                $dealer_order_date = $record['placed_order_date'];
                $dealer_name =
                    $record['first_name'] . ' ' . $record['last_name'];
                $no_of_items = Cart::where('dealer', $dealer_id)->count();
                $sum_total = Cart::where('dealer', $dealer_id)->sum('price');
                $dealer_date_updated = $record['updated_at'];

                return [
                    'dealer_id' => $dealer_id,
                    'dealer_account_id' => $dealer_account_id,
                    'delaer_name' => $dealer_name,
                    'no_of_items' => $no_of_items,
                    'total_amount' => $sum_total,
                    'date' => $dealer_order_date,
                ];
            }, json_decode($all_recent_with_order_dealer_ids, true));

            // foreach($get_recent_order_Details as $item){
            //     $total_amount += $item['total_amount'];
            // }

            // return $total_amount;

            if (!$fetch_dealers) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry we could not fetch all the dealers assigned to the branch';
                return response()->json($this->result);
            } else {
                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->data->no_of_dealers = $fetch_dealers
                    ? count($fetch_dealers)
                    : 0;
                $this->result->data->no_of_dealer_orders = $all_orders;
                $this->result->data->no_of_catalogue_order = $all_catalogue_orders
                    ? $all_catalogue_orders
                    : 0;
                $this->result->data->no_of_service_parts = $all_service_parts
                    ? $all_service_parts
                    : 0;
                $this->result->data->no_of_carded_products = $all_carded_products
                    ? $all_carded_products
                    : 0;
                $this->result->data->recent_orders = $get_recent_order_Details;
                $this->result->data->total_amount = $total_amount;
                $this->result->message =
                    'Dashboard details fetched successfully';
                return response()->json($this->result);
            }
        }
    }

    public function remove_dealer_from_branch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required',
            'dealer_id' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'branch_id' => $validator->errors()->get('branch_id'),
                'dealerid' => $validator->errors()->get('dealer_id'),
            ];
            return response()->json($this->result);
        } else {
            $branch_id = $request->input('branch_id');
            $dealer_id = $request->input('dealer_id');

            // check if the branch exists
            $check_branch = Branch::where('id', $branch_id)->get();

            if (!$check_branch || count($check_branch) == 0) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry branch doesnt exist or has been deactivated ';
                return response()->json($this->result);
            }

            // check if the dealer is assigned to the branch
            $check_dealer = BranchAssignDealer::where('dealer_id', $dealer_id)
                ->where('branch_id', $branch_id)
                ->get();

            if (!$check_dealer || count($check_dealer) == 0) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry the dealer is not assigned to this branch';
                return response()->json($this->result);
            }

            $delete_dealer_from_branch = $check_dealer[0]->delete();

            if (!$delete_dealer_from_branch) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry the dealers could not remove the dealer from the branch';
                return response()->json($this->result);
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message =
                'Dealer removed from the branch successfully';
            return response()->json($this->result);
        }
    }

    // fetches all the dealer orders
    public function fetch_dealer_active_order($dealer_id)
    {
        // this fetches all the
        $check_dealer = Cart::where('dealer', $dealer_id)
            ->where('status', '1')
            ->get();

        if (!$check_dealer) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry the we could not fetch the order details';
            return response()->json($this->result);
        }

        foreach ($check_dealer as $value) {
            $spec_data = $value->spec_data
                ? json_decode($value->spec_data)
                : [];
            $value->spec_data = $spec_data;
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $check_dealer;
        $this->result->message = 'Dealer order fetched successfully';
        return response()->json($this->result);
    }

    // fetches all the dealer catalogue orders
    public function fetch_dealer_catalogue_order($dealer_id)
    {
        $check_dealer = Catalogue_Order::where('dealer', $dealer_id)
            ->where('completed', '1')
            ->get();

        if (!$check_dealer) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry the we could not fetch the order details';
            return response()->json($this->result);
        }

        foreach ($check_dealer as $value) {
            $data = $value->data ? json_decode($value->data) : [];
            $value->data = $data;
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $check_dealer;
        $this->result->message = 'Dealer order fetched successfully';
        return response()->json($this->result);
    }

    // public function check_dd

    // fetches all the dealer service part orders
    public function fetch_dealer_service_parts_order($dealer_id)
    {
        $check_dealer_order_status = Dealer::where('account_id', $dealer_id)
            ->where('order_status', 1)
            ->get();

        if (
            $check_dealer_order_status &&
            count($check_dealer_order_status) == 0
        ) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry Dealer doesnt have a completed order';
            return response()->json($this->result);
        }

        $check_dealer = ServiceParts::where('dealer', $dealer_id)->get();

        if (!$check_dealer) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry the we could not fetch the order details';
            return response()->json($this->result);
        }

        foreach ($check_dealer as $value) {
            $data = $value->data ? json_decode($value->data) : [];
            $value->data = $data;
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $check_dealer;
        $this->result->message = 'Dealer order fetched successfully';
        return response()->json($this->result);
    }

    // fetches all the dealer carded products orders
    public function fetch_dealer_carded_products_order($dealer_id)
    {
        $check_dealer_order_status = Dealer::where('account_id', $dealer_id)
            ->where('order_status', 1)
            ->get();

        if (
            $check_dealer_order_status &&
            count($check_dealer_order_status) == 0
        ) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry Dealer doesnt have a completed order';
            return response()->json($this->result);
        }

        $check_dealer = CardedProducts::where('dealer', $dealer_id)
            ->where('completed', '1')
            ->get();

        if (!$check_dealer) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry the we could not fetch the order details';
            return response()->json($this->result);
        }

        foreach ($check_dealer as $value) {
            $data = $value->data ? json_decode($value->data) : [];
            $value->data = $data;
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $check_dealer;
        $this->result->message = 'Dealer order fetched successfully';
        return response()->json($this->result);
    }

    public function fetch_all_dealers_with_active_catalogue_order($branch_id)
    {
        // fetch all dealers assigned to a branch
        $fetch_branch_details = Branch::where('id', $branch_id)
            ->where('status', '1')
            ->first();

        if (!$fetch_branch_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->data = [];
            $this->result->message = 'Sorry branch doesnt exist or deactivated';
            return response()->json($this->result);
        } else {
            // $fetch_dealers = BranchAssignDealer::where('branch_id',$branch_id)->get();

            $fetch_dealers = BranchAssignDealer::where(
                'branch_id',
                $branch_id
            )->get();

            $fetch_account_ids = $fetch_dealers->pluck('dealer_id')->toArray();

            $all_catalogue_orders = DB::table('atlas_dealers')
                ->wherein('account_id', $fetch_account_ids)
                ->where('order_status', 1)
                ->join(
                    'atlas_catalogue_orders',
                    'atlas_dealers.account_id',
                    '=',
                    'atlas_catalogue_orders.dealer'
                )
                ->orderby('atlas_dealers.placed_order_date', 'desc')
                ->select(
                    'atlas_catalogue_orders.*',
                    'atlas_dealers.account_id as account_id',
                    'atlas_dealers.full_name',
                    'atlas_dealers.first_name',
                    'atlas_dealers.last_name',
                    'atlas_dealers.placed_order_date as order_date'
                )
                ->distinct('atlas_branch_assign_dealers.dealer_id')
                ->get();

            // $fetch_account_ids = $fetch_dealers->pluck("dealer_id")->toArray();

            // $all_dealer_ids = DB::table('atlas_dealers')->wherein('account_id',$fetch_account_ids)->where('order_status',1)->where('order_status','1')->select('*','placed_order_date as order_date')->orderby('placed_order_date','desc')->pluck('id')->toArray();

            // // $all_catalogue_orders = DB::table('atlas_catalogue_orders')->wherein('dealer',$fetch_account_ids)->get();

            // // return $all_dealer_ids;

            // $all_dealer_ids_order_status = DB::table('atlas_dealers')->wherein('account_id',$fetch_account_ids)->where('order_status',1)->pluck('account_id')->toArray();

            // $all_catalogue_orders = DB::table('atlas_catalogue_orders')->wherein('dealer',$all_dealer_ids_order_status)->get();

            // $dealers_with_catalogue_orders_orders = DB::table('atlas_branch_assign_dealers')
            //     ->where('order_status','1')
            //     ->join( 'atlas_dealers', 'atlas_branch_assign_dealers.dealer_id', '=', 'atlas_dealers.account_id' )
            //     ->join( 'atlas_catalogue_orders', 'atlas_branch_assign_dealers.dealer_id', '=', 'atlas_catalogue_orders.dealer' )
            //     ->orderby('atlas_dealers.placed_order_date','desc')
            //     ->select( 'atlas_catalogue_orders.*', 'atlas_dealers.full_name', 'atlas_dealers.first_name',
            //     'atlas_dealers.last_name','atlas_dealers.placed_order_date as order_date')
            //     ->distinct('atlas_branch_assign_dealers.dealer_id')->get();

            // if(!$dealers_with_catalogue_orders_orders){
            //     $this->result->status = false;
            //     $this->result->status_code = 422;
            //     $this->result->message = 'Sorry we could not fetch all the dealers assigned to the branch';
            //     return response()->json($this->result);
            // }

            foreach ($all_catalogue_orders as $value) {
                $value->data = json_decode($value->data);
            }

            // $dealer_array = [];
            // foreach($fetch_dealers as $dealer){
            //     $dealer = (object)$dealer;

            //     $fetch_dealer_details = Dealer::where('account_id',$dealer->dealer_id)->where('order_status',1)->first();

            //     if($fetch_dealer_details){
            //         // check if the dealer has a catalogue order

            //         $check_dealer = Catalogue_Order::where('dealer',$fetch_dealer_details->account_id)->get();

            //         if($check_dealer && count($check_dealer) > 0){
            //             array_push($dealer_array,$fetch_dealer_details);
            //         }
            //     }
            // }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $all_catalogue_orders;
            $this->result->message =
                'All dealers with catalogue orders fetched successfully';
            return response()->json($this->result);
        }

        // check if the dealer has a catalogue order

        // collect all the dealers with catalogue order

        // send them to the front end
    }

    public function fetch_all_dealers_with_active_order($branch_id)
    {
        // fetch all dealers assigned to a branch
        $fetch_branch_details = Branch::where('id', $branch_id)
            ->where('status', '1')
            ->first();

        if (!$fetch_branch_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->data = [];
            $this->result->message = 'Sorry branch doesnt exist or deactivated';
            return response()->json($this->result);
        } else {
            $fetch_dealers = BranchAssignDealer::where(
                'branch_id',
                $branch_id
            )->get();

            $fetch_account_ids = $fetch_dealers->pluck('dealer_id')->toArray();

            $all_dealer_ids = DB::table('atlas_dealers')
                ->wherein('account_id', $fetch_account_ids)
                ->where('order_status', 1)
                ->pluck('id')
                ->toArray();

            $all_orders = DB::table('atlas_dealers')
                ->wherein('id', $all_dealer_ids)
                ->where('order_status', '1')
                ->select(
                    'atlas_dealers.id',
                    'atlas_dealers.full_name',
                    'atlas_dealers.first_name',
                    'atlas_dealers.last_name',
                    'atlas_dealers.email',
                    'atlas_dealers.email',
                    'atlas_dealers.email',
                    'atlas_dealers.email',
                    'atlas_dealers.account_id',
                    'atlas_dealers.phone',
                    'atlas_dealers.status',
                    'atlas_dealers.order_status',
                    'atlas_dealers.location',
                    'atlas_dealers.company_name',
                    'atlas_dealers.last_login',
                    'atlas_dealers.placed_order_date',
                    'atlas_dealers.order_pdf',
                    'placed_order_date as order_date'
                )
                ->orderby('placed_order_date', 'desc')
                ->get()
                ->toArray();

            $format_all_orders = array_map(function ($record) {
                $dealer_id = $record->id;

                $check_total_price = Cart::where('dealer', $dealer_id)->sum(
                    'price'
                );

                $record->total_amount = number_format($check_total_price, 2);

                // total pending amount i.e amount of items that has not been submitted
                $record->total_pending_amt = number_format(
                    DB::table('cart')
                        ->where('dealer', $dealer_id)
                        ->where('status', '0')
                        ->sum('price'),
                    2
                );

                // total pending items i.e no of items that has not been submitted
                $record->total_pending_items = DB::table('cart')
                    ->where('dealer', $dealer_id)
                    ->where('status', '0')
                    ->count();

                return $record;
            }, $all_orders);

            // return $format_all_orders;

            // $dealers_cart_orders = DB::table('atlas_branch_assign_dealers')
            //     ->where('atlas_dealers.order_status','1')
            //     ->where('atlas_dealers.status','1')
            //     ->join( 'atlas_dealers', 'atlas_branch_assign_dealers.dealer_id', '=', 'atlas_dealers.account_id' )
            //     ->join( 'cart', 'atlas_branch_assign_dealers.dealer_id', '=', 'cart.dealer' )
            //     ->orderby('atlas_dealers.placed_order_date','desc')
            //     ->select('atlas_dealers.full_name', 'atlas_dealers.first_name',
            //     'atlas_dealers.last_name','atlas_dealers.placed_order_date as order_date')
            //     ->distinct('atlas_branch_assign_dealers.dealer_id')
            //     ->get();

            if (!$all_orders) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry we could not fetch all the dealers assigned to the branch';
                return response()->json($this->result);
            }

            // return $dealers_cart_orders;
            // $dealer_array = [];

            // return($fetch_dealers);

            // foreach($fetch_dealers as $dealer){
            //     $dealer = (object)$dealer;
            //     $fetch_dealer_details = Dealer::where('account_id',$dealer->dealer_id)->where('order_status',1)->first();

            //     if($fetch_dealer_details){

            //         // check if the dealer has a  order

            //         $check_dealer = Cart::where('dealer',$fetch_dealer_details->id)->where('status','1')->get();

            //         if($check_dealer && count($check_dealer) > 0){
            //             array_push($dealer_array,$fetch_dealer_details);
            //         }
            //     }
            // }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $format_all_orders ? $format_all_orders : [];
            $this->result->message =
                'All dealers with catalogue orders fetched successfully';
            return response()->json($this->result);
        }

        // check if the dealer has a catalogue order

        // collect all the dealers with catalogue order

        // send them to the front end
    }

    // get all dealers with pending orders
    public function fetch_all_dealers_with_pending_order($branch_id)
    {
        // fetch all dealers assigned to a branch
        $fetch_branch_details = Branch::where('id', $branch_id)
            ->where('status', '1')
            ->first();
        // return $fetch_branch_details;

        if (!$fetch_branch_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->data = [];
            $this->result->message = 'Sorry branch doesnt exist or deactivated';
            return response()->json($this->result);
        } else {
            $fetch_dealers = BranchAssignDealer::where(
                'branch_id',
                $branch_id
            )->get();

            $fetch_account_ids = $fetch_dealers->pluck('dealer_id')->toArray();

            $all_dealer_ids = DB::table('atlas_dealers')
                ->wherein('account_id', $fetch_account_ids)
                ->where('order_status', 1)
                ->pluck('id')
                ->toArray();

            $all_orders = DB::table('atlas_dealers')
                ->wherein('id', $all_dealer_ids)
                ->where('order_status', '1')
                ->select(
                    'atlas_dealers.id',
                    'atlas_dealers.full_name',
                    'atlas_dealers.first_name',
                    'atlas_dealers.last_name',
                    'atlas_dealers.email',
                    'atlas_dealers.email',
                    'atlas_dealers.email',
                    'atlas_dealers.email',
                    'atlas_dealers.account_id',
                    'atlas_dealers.phone',
                    'atlas_dealers.status',
                    'atlas_dealers.order_status',
                    'atlas_dealers.location',
                    'atlas_dealers.company_name',
                    'atlas_dealers.last_login',
                    'atlas_dealers.placed_order_date',
                    'atlas_dealers.order_pdf',
                    'placed_order_date as order_date'
                )
                ->orderby('placed_order_date', 'desc')
                ->get()
                ->toArray();

            $format_all_orders = array_map(function ($record) {
                $dealer_id = $record->id;

                $check_total_price = Cart::where('dealer', $dealer_id)->sum(
                    'price'
                );

                $record->total_amount = number_format($check_total_price, 2);

                // total pending amount i.e amount of items that has not been submitted
                $record->total_pending_amt = number_format(
                    DB::table('cart')
                        ->where('dealer', $dealer_id)
                        ->where('status', '0')
                        ->sum('price'),
                    2
                );

                // total pending items i.e no of items that has not been submitted
                $record->total_pending_items = DB::table('cart')
                    ->where('dealer', $dealer_id)
                    ->where('status', '0')
                    ->count();

                // list of pending items 
                $record->total_pending_items = DB::table('cart')
                    ->where('dealer', $dealer_id)
                    ->where('status', '0')
                    ->get();

                foreach ($record->total_pending_items as $item) {
                    $item->spec_data = json_decode($item->spec_data);
                }

                // list of completed order items 
                $record->total_completed_items = DB::table('cart')
                    ->where('dealer', $dealer_id)
                    ->where('status', '1')
                    ->get();

                foreach ($record->total_completed_items as $item) {
                    $item->spec_data = json_decode($item->spec_data);
                }

                return $record;
            }, $all_orders);

            // return $format_all_orders;

            // $dealers_cart_orders = DB::table('atlas_branch_assign_dealers')
            //     ->where('atlas_dealers.order_status','1')
            //     ->where('atlas_dealers.status','1')
            //     ->join( 'atlas_dealers', 'atlas_branch_assign_dealers.dealer_id', '=', 'atlas_dealers.account_id' )
            //     ->join( 'cart', 'atlas_branch_assign_dealers.dealer_id', '=', 'cart.dealer' )
            //     ->orderby('atlas_dealers.placed_order_date','desc')
            //     ->select('atlas_dealers.full_name', 'atlas_dealers.first_name',
            //     'atlas_dealers.last_name','atlas_dealers.placed_order_date as order_date')
            //     ->distinct('atlas_branch_assign_dealers.dealer_id')
            //     ->get();

            if (!$all_orders) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry we could not fetch all the dealers assigned to the branch';
                return response()->json($this->result);
            }

            // return $dealers_cart_orders;
            // $dealer_array = [];

            // return($fetch_dealers);

            // foreach($fetch_dealers as $dealer){
            //     $dealer = (object)$dealer;
            //     $fetch_dealer_details = Dealer::where('account_id',$dealer->dealer_id)->where('order_status',1)->first();

            //     if($fetch_dealer_details){

            //         // check if the dealer has a  order

            //         $check_dealer = Cart::where('dealer',$fetch_dealer_details->id)->where('status','1')->get();

            //         if($check_dealer && count($check_dealer) > 0){
            //             array_push($dealer_array,$fetch_dealer_details);
            //         }
            //     }
            // }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $format_all_orders ? $format_all_orders : [];
            $this->result->message =
                'All dealers with pending orders fetched successfully';
            return response()->json($this->result);
        }

        // check if the dealer has a catalogue order

        // collect all the dealers with catalogue order

        // send them to the front end
    }

    // get all dealers with pending orders
    public function fetch_dealer_with_pending_order($dealer_id)
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

    public function download_pdf_branch($id)
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
    // get all the pending orders by pdf from dealer_id
    public function download_pending_order_pdf_branch($dealer_id)
    {
        // get the dealer details 

        $dealer_details = Dealer::where('id', $dealer_id)->where('status', 1)->get();

        if (!$dealer_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Dealer could not be found';
            return response()->json($this->result);
        }

        $account_id = $dealer_details[0]->account_id;

        // else get all the items in cart for the dealer 

        $cart_data = Cart::where('dealer', $dealer_id)->where('status', 0)->get();

        // get all the CP, CD AND SP PRODUCTS

        $carded_products = [];
        $catalogue_products = [];
        $service_part_products = [];

        // get all the carded products 

        $get_all_carded_products = CardedProducts::where('dealer', $account_id)->where('completed', 0)->get();

        // get all the service parts 

        $get_all_services_parts = ServiceParts::where('dealer', $account_id)->where('completed', 0)->get();

        // get all the catalogue orders

        $get_all_catalogue_orders = Catalogue_Order::where('dealer', $account_id)->where('completed', 0)->get();



        //  foreach ($cart_data as $record) {
        //      $record['spec_data'] = json_decode($record['spec_data']);

        //      if (!is_null($record['carded_data'])) {
        //          $record['carded_data'] = json_decode($record['carded_data']);
        //          array_push($carded_products, $record);
        //      }

        //      if (!is_null($record['service_data'])) {
        //          $record['service_data'] = json_decode($record['service_data']);
        //          array_push($service_part_products, $record);
        //      }

        //      if (!is_null($record['catalogue_data'])) {
        //          $record['catalogue_data'] = json_decode($record['catalogue_data']);
        //          array_push($catalogue_products, $record);
        //      }
        //  };


        // foreach($cart_data as $item){
        //     $item['spec_data'] = json_decode($item['spec_data'],true);
        // }

        $data = [
            "cart_data" => $cart_data,
            "dealer_details" => $dealer_details,
            "carded_products" => $get_all_carded_products && count($get_all_carded_products) > 0 ? json_decode($get_all_carded_products[0]->data) : [],
            "catalogue_products" => $get_all_catalogue_orders && count($get_all_catalogue_orders) > 0 ? json_decode($get_all_catalogue_orders[0]->data) : [],
            "service_part_products" => $get_all_services_parts && count($get_all_services_parts) > 0 ? json_decode($get_all_services_parts[0]->data) : [],
        ];

        //  return $cart_data;

        //  return $data['catalogue_products'][0]->description;

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

    public function fetch_all_dealers_with_active_service_parts_order(
        $branch_id
    ) {
        // fetch all dealers assigned to a branch
        $fetch_branch_details = Branch::where('id', $branch_id)
            ->where('status', '1')
            ->first();

        if (!$fetch_branch_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->data = [];
            $this->result->message = 'Sorry branch doesnt exist or deactivated';
            return response()->json($this->result);
        } else {
            $fetch_dealers = BranchAssignDealer::where(
                'branch_id',
                $branch_id
            )->get();

            $fetch_account_ids = $fetch_dealers->pluck('dealer_id')->toArray();

            $all_service_parts = DB::table('atlas_dealers')
                ->wherein('account_id', $fetch_account_ids)
                ->where('order_status', 1)
                ->join(
                    'atlas_service_parts',
                    'atlas_dealers.account_id',
                    '=',
                    'atlas_service_parts.dealer'
                )
                ->orderby('atlas_dealers.placed_order_date', 'desc')
                ->select(
                    'atlas_service_parts.*',
                    'atlas_dealers.account_id as account_id',
                    'atlas_dealers.full_name',
                    'atlas_dealers.first_name',
                    'atlas_dealers.last_name',
                    'atlas_dealers.placed_order_date as order_date'
                )
                ->distinct('atlas_branch_assign_dealers.dealer_id')
                ->get();

            foreach ($all_service_parts as $value) {
                $value->data = json_decode($value->data);
            }

            // return $all_service_parts;
            // $all_service_parts = DB::table('atlas_service_parts')->wherein('dealer',$all_dealer_ids_order_status)->get();

            // if(!$fetch_dealers){
            //     $this->result->status = false;
            //     $this->result->status_code = 422;
            //     $this->result->message = 'Sorry we could not fetch all the dealers assigned to the branch';
            //     return response()->json($this->result);
            // }

            // dd($fetch_dealers); atlas_branch_assign_dealers, atlas_service_parts

            // $dealers_with_service_parts_orders = DB::table('atlas_branch_assign_dealers')
            //     ->where('order_status','1')
            //     ->join( 'atlas_dealers', 'atlas_branch_assign_dealers.dealer_id', '=', 'atlas_dealers.account_id' )
            //     ->join( 'atlas_service_parts', 'atlas_branch_assign_dealers.dealer_id', '=', 'atlas_service_parts.dealer' )
            //     ->orderby('atlas_dealers.placed_order_date','desc')
            //     ->select( 'atlas_service_parts.*', 'atlas_dealers.full_name', 'atlas_dealers.first_name',
            //     'atlas_dealers.last_name','atlas_dealers.placed_order_date as order_date')
            //     ->distinct('atlas_branch_assign_dealers.dealer_id')
            //     ->get();

            // if(!$dealers_with_service_parts_orders){
            //     $this->result->status = false;
            //     $this->result->status_code = 422;
            //     $this->result->message = 'Sorry we could not fetch all the dealers assigned to the branch';
            //     return response()->json($this->result);
            // }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $all_service_parts;
            $this->result->message =
                'All dealers with service part orders fetched successfully';
            return response()->json($this->result);

            // return $service_parts_orders;

            // $dealer_array = [];

            // foreach($fetch_dealers as $dealer){
            //     $dealer = (object)$dealer;

            //     // $available_catalogue_orders = DB::table('atlas_branch_assign_dealers')
            //     // ->join( 'atlas_dealers', 'atlas_service_parts.dealer', '=', 'atlas_dealers.account_id' )
            //     // ->orderby('atlas_dealers.placed_order_date','desc')
            //     // ->select( 'atlas_service_parts.*', 'atlas_dealers.full_name', 'atlas_dealers.first_name',
            //     // 'atlas_dealers.last_name','atlas_dealers.placed_order_date as order_date')->get();

            //     $fetch_dealer_details = Dealer::where('account_id',$dealer->dealer_id)->where('order_status','1')->first();

            //     if($fetch_dealer_details){

            //         // check if the dealer has a catalogue order

            //         $check_dealer = ServiceParts::where('dealer',$fetch_dealer_details->account_id)->get();

            //         if($check_dealer && count($check_dealer) > 0){
            //             array_push($dealer_array,$fetch_dealer_details);
            //         }
            //     }
            // }
        }

        // check if the dealer has a catalogue order

        // collect all the dealers with catalogue order

        // send them to the front end
    }

    public function fetch_all_dealers_with_active_carded_products_order(
        $branch_id
    ) {
        // fetch all dealers assigned to a branch
        $fetch_branch_details = Branch::where('id', $branch_id)
            ->where('status', '1')
            ->first();

        if (!$fetch_branch_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->data = [];
            $this->result->message = 'Sorry branch doesnt exist or deactivated';
            return response()->json($this->result);
        } else {
            $fetch_dealers = BranchAssignDealer::where(
                'branch_id',
                $branch_id
            )->get();

            $fetch_account_ids = $fetch_dealers->pluck('dealer_id')->toArray();

            $all_carded_products = DB::table('atlas_dealers')
                ->wherein('account_id', $fetch_account_ids)
                ->where('order_status', 1)
                ->join(
                    'atlas_carded_products',
                    'atlas_dealers.account_id',
                    '=',
                    'atlas_carded_products.dealer'
                )
                ->orderby('atlas_dealers.placed_order_date', 'desc')
                ->select(
                    'atlas_carded_products.*',
                    'atlas_dealers.account_id as account_id',
                    'atlas_dealers.full_name',
                    'atlas_dealers.first_name',
                    'atlas_dealers.last_name',
                    'atlas_dealers.placed_order_date as order_date'
                )
                ->distinct('atlas_branch_assign_dealers.dealer_id')
                ->get();

            foreach ($all_carded_products as $value) {
                $value->data = json_decode($value->data);
            }

            // $dealers_with_active_carded_products_orders = DB::table('atlas_branch_assign_dealers')
            //     ->where('order_status','1')
            //     ->join( 'atlas_dealers', 'atlas_branch_assign_dealers.dealer_id', '=', 'atlas_dealers.account_id' )
            //     ->join( 'atlas_carded_products', 'atlas_branch_assign_dealers.dealer_id', '=', 'atlas_carded_products.dealer' )
            //     ->orderby('atlas_dealers.placed_order_date','desc')
            //     ->select( 'atlas_carded_products.*', 'atlas_dealers.full_name', 'atlas_dealers.first_name',
            //     'atlas_dealers.last_name','atlas_dealers.placed_order_date as order_date')
            //     ->distinct('atlas_branch_assign_dealers.dealer_id')
            //     ->get();

            // if(!$dealers_with_active_carded_products_orders){
            //     $this->result->status = false;
            //     $this->result->status_code = 422;
            //     $this->result->message = 'Sorry we could not fetch all the dealers assigned to the branch';
            //     return response()->json($this->result);
            // }

            // $dealer_array = [];
            // foreach($fetch_dealers as $dealer){
            //     $dealer = (object)$dealer;
            //     $fetch_dealer_details = Dealer::where('account_id',$dealer->dealer_id)->where('order_status',1)->first();

            //     if($fetch_dealer_details){

            //         // check if the dealer has a catalogue order
            //         $check_dealer = CardedProducts::where('dealer',$fetch_dealer_details->account_id)->get();

            //         if($check_dealer && count($check_dealer) > 0){
            //             array_push($dealer_array,$fetch_dealer_details);
            //         }
            //     }
            // }

            // foreach($dealers_with_active_carded_products_orders as $value){
            //     $value->data = json_decode($value->data);
            // }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $all_carded_products;
            $this->result->message =
                'All dealers with carded products fetched successfully';
            return response()->json($this->result);
        }

        // check if the dealer has a catalogue order

        // collect all the dealers with catalogue order

        // send them to the front end
    }

    public function fetch_dealers_by_id($dealer_id)
    {
        $fetch_dealer = Dealer::find($dealer_id);

        if (!$fetch_dealer) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry we could not fetch the dealer details';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $fetch_dealer;
        $this->result->message = 'Dealer details fetched successfully';
        return response()->json($this->result);
    }

    public function get_all_branch_loggedin_dealers($branch_id)
    {
        // get all the logged in dealers under a branch
        $get_branch_details = BranchAssignDealer::where('branch_id', $branch_id)
            ->join(
                'atlas_dealers',
                'atlas_branch_assign_dealers.dealer_id',
                '=',
                'atlas_dealers.account_id'
            )
            ->orderby('atlas_dealers.account_id', 'asc')
            ->whereNotNull('atlas_dealers.last_login')
            ->select(
                'atlas_branch_assign_dealers.dealer_id as branch_dealer_id',
                'atlas_dealers.full_name',
                'atlas_dealers.first_name',
                'atlas_dealers.last_name',
                'atlas_dealers.email',
                'atlas_dealers.email',
                'atlas_dealers.email',
                'atlas_dealers.email',
                'atlas_dealers.account_id',
                'atlas_dealers.phone',
                'atlas_dealers.status',
                'atlas_dealers.order_status',
                'atlas_dealers.location',
                'atlas_dealers.company_name',
                'atlas_dealers.last_login',
                'atlas_dealers.placed_order_date',
                'atlas_dealers.order_pdf'
            )
            ->distinct('atlas_branch_assign_dealers.dealer_id')
            ->get();

        if (!$get_branch_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry we could not fetch the dealers that have logged in';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $get_branch_details;
        $this->result->message =
            'All branch dealers that have loggedin have been fetched successfully';
        return response()->json($this->result);
    }

    public function get_all_branch_notloggedin_dealers($branch_id)
    {
        // get all the logged in dealers under a branch
        $get_branch_details = BranchAssignDealer::where('branch_id', $branch_id)
            ->join(
                'atlas_dealers',
                'atlas_branch_assign_dealers.dealer_id',
                '=',
                'atlas_dealers.account_id'
            )
            ->orderby('atlas_dealers.account_id', 'asc')
            ->whereNull('atlas_dealers.last_login')
            ->select(
                'atlas_branch_assign_dealers.dealer_id as branch_dealer_id',
                'atlas_dealers.full_name',
                'atlas_dealers.first_name',
                'atlas_dealers.last_name',
                'atlas_dealers.email',
                'atlas_dealers.email',
                'atlas_dealers.email',
                'atlas_dealers.email',
                'atlas_dealers.account_id',
                'atlas_dealers.phone',
                'atlas_dealers.status',
                'atlas_dealers.order_status',
                'atlas_dealers.location',
                'atlas_dealers.company_name',
                'atlas_dealers.last_login',
                'atlas_dealers.placed_order_date',
                'atlas_dealers.order_pdf'
            )
            ->distinct('atlas_branch_assign_dealers.dealer_id')
            ->get();

        if (!$get_branch_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry we could not fetch the dealers that have not logged in';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $get_branch_details;
        $this->result->message =
            'All branch dealers that have not loggedin have been fetched successfully';
        return response()->json($this->result);
    }

    public function search_category($category)
    {
        $products = Products::where('category', $category)
            ->where('status', '1')
            ->orderBy('xref', 'asc')
            ->get();

        if ($products) {
            foreach ($products as $value) {
                $spec_data = $value->spec_data
                    ? json_decode($value->spec_data)
                    : [];
                $value->spec_data = $spec_data;
            }
        } else {
            $products = [];
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
        }, json_decode($products, true));

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $format_products;
        $this->result->message =
            'Products in this category successfully fetched';
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


    public function loggedin_dealer_data($branch_id)
    {
        // defaults to not logged in 
        // get all the logged in dealers under a branch
        $get_branch_details = BranchAssignDealer::where('branch_id', $branch_id)
            ->join(
                'atlas_dealers',
                'atlas_branch_assign_dealers.dealer_id',
                '=',
                'atlas_dealers.account_id'
            )
            ->orderby('atlas_dealers.account_id', 'asc')
            ->whereNotNull('atlas_dealers.last_login')
            ->select(
                'atlas_branch_assign_dealers.dealer_id as branch_dealer_id',
                'atlas_dealers.full_name',
                'atlas_dealers.first_name',
                'atlas_dealers.last_name',
                'atlas_dealers.email',
                'atlas_dealers.email',
                'atlas_dealers.email',
                'atlas_dealers.email',
                'atlas_dealers.account_id',
                'atlas_dealers.phone',
                'atlas_dealers.status',
                'atlas_dealers.order_status',
                'atlas_dealers.location',
                'atlas_dealers.company_name',
                'atlas_dealers.last_login',
                'atlas_dealers.placed_order_date',
                'atlas_dealers.order_pdf'
            )
            ->distinct('atlas_branch_assign_dealers.dealer_id')
            ->get();

        return $get_branch_details;
    }

    public function notloggedin_dealer_data($branch_id)
    {
        // defaults to not logged in 
        // get all the logged in dealers under a branch
        $get_branch_details = BranchAssignDealer::where('branch_id', $branch_id)
            ->join(
                'atlas_dealers',
                'atlas_branch_assign_dealers.dealer_id',
                '=',
                'atlas_dealers.account_id'
            )
            ->orderby('atlas_dealers.account_id', 'asc')
            ->whereNull('atlas_dealers.last_login')
            ->select(
                'atlas_branch_assign_dealers.dealer_id as branch_dealer_id',
                'atlas_dealers.full_name',
                'atlas_dealers.first_name',
                'atlas_dealers.last_name',
                'atlas_dealers.email',
                'atlas_dealers.email',
                'atlas_dealers.email',
                'atlas_dealers.email',
                'atlas_dealers.account_id',
                'atlas_dealers.phone',
                'atlas_dealers.status',
                'atlas_dealers.order_status',
                'atlas_dealers.location',
                'atlas_dealers.company_name',
                'atlas_dealers.last_login',
                'atlas_dealers.placed_order_date',
                'atlas_dealers.order_pdf'
            )
            ->distinct('atlas_branch_assign_dealers.dealer_id')
            ->get();

        return $get_branch_details;
    }



    public function loggedin_and_not_loggedin_dealers($branch_id)
    {


        if (!empty($branch_id)) {

            $data = [
                "logged_in_dealers" => $this->loggedin_dealer_data($branch_id),
                "not_logged_in_dealers" => $this->notloggedin_dealer_data($branch_id)
            ];

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $data;
            $this->result->message = 'Loggedin and not loggedin dealers fetched successfully ';
        } else {

            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Invalid branch';
        }

        return response()->json($this->result, $this->result->status_code);

        // get_all_branch_notloggedin_dealers
        // get_all_branch_loggedin_dealers
    }
}
