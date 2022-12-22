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
use App\Models\Orders;

use DB;

class BranchController extends Controller
{
    //
    public function __construct()
    {
        //// $this->middleware( 'auth:api', [ 'except' => [ 'login', 'register', 'test' ] ] );
        $this->result = (object) array(
            'status' => false,
            'status_code' => 200,
            'message' => null,
            'data' => (object) null,
            'token' => null,
            'debug' => null
        );
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
            'email'   => 'required|email',
            'password' => 'required|min:6'
        ]);

        if (!$token = Auth::guard('branch')->attempt(['email' => $request->email, 'password' => $request->password])) {
            $this->result->status_code = 401;
            $this->result->message = 'Invalid login credentials';
            return response()->json($this->result);
        }

        $active_staff = Branch::query()->where('email', $request->email)->get()->first();

        if ($active_staff['status'] == 0) {
            $this->result->status_code = 401;
            $this->result->message = 'Account has been deactivated';
            return response()->json($this->result);
        }

        $branch = Branch::where('email', $request->email)->first();
        $branch->role = 'branch';

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
            'expires_in' => auth()->factory()->getTTL() * 60
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
            'dealers' => 'required'
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'branch_id' => $validator->errors()->get('branch_id'),
                'dealers' => $validator->errors()->get('dealers')
            ];
            return response()->json($this->result);
        } else {

            $branch_id = $request->input('branch_id');
            $dealers = $request->input('dealers');

            $not_inserted_dealers = [];

            // already exists 
            $already_exists_dealers = [];

            // assigned dealers
            $assigned_dealers = [];

            foreach ($dealers as $dealer_id) {

                // check if the dealer has already been assigned 
                $check_dealer = BranchAssignDealer::where('dealer_id', $dealer_id)
                    ->where('branch_id', $branch_id)->get();

                if (count($check_dealer) > 0) {
                    // dealer already exists 
                    array_push($already_exists_dealers, $dealer_id);
                } else {
                    $save_dealer = BranchAssignDealer::create([
                        'branch_id' => $branch_id,
                        'dealer_id' => $dealer_id
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
                'existing_dealers' => (array)$already_exists_dealers,
                'assigned_dealers' => (array)$assigned_dealers
                //  this shows that the dealers have already been assigned to the branch
            ];

            $this->result->message = 'Dealers have been successfully assigned to the branch';
            return response()->json($this->result);
        }
        // BranchAssignDealer
    }

    // fetch all the dealers assigned to a branch
    public function fetch_dealer_by_branch($branch_id)
    {

        $fetch_branch_details = Branch::where('id', $branch_id)->where('status', '1')->first();

        if (!$fetch_branch_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry branch doesnt exist or deactivated';
            return response()->json($this->result);
        } else {
            $fetch_dealers = BranchAssignDealer::where('branch_id', $branch_id)->get();

            if (!$fetch_dealers) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Sorry we could not fetch all the dealers assigned to the branch';
                return response()->json($this->result);
            }

            $dealer_array = [];

            foreach ($fetch_dealers as $dealer) {
                $dealer = (object)$dealer;
                // check if the dealer has service part, carded or catalogue products 


                $fetch_dealer_details = Dealer::where('account_id', $dealer->dealer_id)->first();
                if ($fetch_dealer_details) {
                    array_push($dealer_array, $fetch_dealer_details);
                }
            }


            // 

            $format_dealer_array = array_map(function ($record) {
                $dealer_id = $record->id;
                $check_service_parts_count = ServiceParts::where('dealer', $dealer_id)->count();

                $record->has_service_parts = $check_service_parts_count > 0 ? true : false;

                // check for catalogue products 

                $check_catalogue_products_count = Catalogue_Order::where('dealer', $dealer_id)->count();

                $record->has_catalogue_products = $check_catalogue_products_count > 0 ? true : false;

                // check for carded products 

                $check_carded_products_count = CardedProducts::where('dealer', $dealer_id)->count();

                $record->has_carded_products = $check_carded_products_count > 0 ? true : false;

                return $record;
            }, $dealer_array);

            // dd($fetch_dealers);

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = ['branch' => $fetch_branch_details, 'dealers' => $format_dealer_array];
            $this->result->message = 'All Branches fetched Successfully';
            return response()->json($this->result);
        }
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
        $fetch_branch_details = Branch::where('id', $branch_id)->where('status', '1')->first();

        if (!$fetch_branch_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry branch doesnt exist or deactivated';
            return response()->json($this->result);
        } else {
            $fetch_dealers = BranchAssignDealer::where('branch_id', $branch_id)->get();

            if (!$fetch_dealers) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Sorry we could not fetch all the dealers assigned to the branch';
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
            $fetch_dealers = BranchAssignDealer::where('branch_id', $branch_id)->get();

            $fetch_account_ids = $fetch_dealers->pluck("dealer_id")->toArray();

            $all_dealer_ids = DB::table('atlas_dealers')->wherein('account_id', $fetch_account_ids)->where('order_status', 1)->pluck('id')->toArray();

            // $all_catalogue_orders = DB::table('atlas_catalogue_orders')->wherein('dealer',$fetch_account_ids)->get();

            // return $all_dealer_ids;  

            $all_dealer_ids_order_status = DB::table('atlas_dealers')->wherein('account_id', $fetch_account_ids)->where('order_status', 1)->pluck('account_id')->toArray();

            $all_catalogue_orders = DB::table('atlas_catalogue_orders')->wherein('dealer', $all_dealer_ids_order_status)->count();

            $all_service_parts = DB::table('atlas_service_parts')->wherein('dealer', $all_dealer_ids_order_status)->count();

            $all_carded_products = DB::table('atlas_carded_products')->wherein('dealer', $all_dealer_ids_order_status)->count();

            $all_orders = DB::table('atlas_dealers')->wherein('id', $all_dealer_ids)->where('order_status', '1')->count();

            $all_orders_with_values = DB::table('atlas_dealers')->wherein('id', $all_dealer_ids)->where('order_status', '1')->get();

            $total_amount = DB::table('cart')->wherein('dealer', $all_dealer_ids)->sum('price');

            // return $total_amount;

            $all_recent_with_order_dealer_ids = DB::table('atlas_dealers')->wherein('account_id', $fetch_account_ids)
                ->where('order_status', '1')
                ->orderby('placed_order_date', 'desc')
                ->select('id', 'account_id', 'first_name', 'last_name', 'updated_at', 'placed_order_date')->get()->take(5);

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
                $dealer_name = $record['first_name'] . ' ' . $record['last_name'];
                $no_of_items = Cart::where('dealer', $dealer_id)->count();
                $sum_total = Cart::where('dealer', $dealer_id)->sum('price');
                $dealer_date_updated = $record['updated_at'];

                return [
                    'dealer_id' => $dealer_id,
                    'dealer_account_id' => $dealer_account_id,
                    'delaer_name' => $dealer_name,
                    'no_of_items' => $no_of_items,
                    'total_amount' => $sum_total,
                    'date' => $dealer_order_date
                ];
            }, json_decode($all_recent_with_order_dealer_ids, true));

            // foreach($get_recent_order_Details as $item){
            //     $total_amount += $item['total_amount'];
            // }


            // return $total_amount;

            if (!$fetch_dealers) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Sorry we could not fetch all the dealers assigned to the branch';
                return response()->json($this->result);
            } else {
                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->data->no_of_dealers = $fetch_dealers ? count($fetch_dealers) : 0;
                $this->result->data->no_of_dealer_orders = $all_orders;
                $this->result->data->no_of_catalogue_order = $all_catalogue_orders ? $all_catalogue_orders : 0;
                $this->result->data->no_of_service_parts = $all_service_parts ? $all_service_parts : 0;
                $this->result->data->no_of_carded_products = $all_carded_products ? $all_carded_products : 0;
                $this->result->data->recent_orders = $get_recent_order_Details;
                $this->result->data->total_amount = $total_amount;
                $this->result->message = 'Dashboard details fetched successfully';
                return response()->json($this->result);
            }
        }
    }

    public function remove_dealer_from_branch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required',
            'dealer_id' => 'required'
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'branch_id' => $validator->errors()->get('branch_id'),
                'dealerid' => $validator->errors()->get('dealer_id')
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
                $this->result->message = 'Sorry branch doesnt exist or has been deactivated ';
                return response()->json($this->result);
            }

            // check if the dealer is assigned to the branch
            $check_dealer = BranchAssignDealer::where('dealer_id', $dealer_id)->where('branch_id', $branch_id)->get();

            if (!$check_dealer || count($check_dealer) == 0) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Sorry the dealer is not assigned to this branch';
                return response()->json($this->result);
            }

            $delete_dealer_from_branch = $check_dealer[0]->delete();

            if (!$delete_dealer_from_branch) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Sorry the dealers could not remove the dealer from the branch';
                return response()->json($this->result);
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Dealer removed from the branch successfully';
            return response()->json($this->result);
        }
    }

    // fetches all the dealer orders
    public function fetch_dealer_active_order($dealer_id)
    {
        // this fetches all the 
        $check_dealer = Cart::where('dealer', $dealer_id)->where('status', '1')->get();

        if (!$check_dealer) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry the we could not fetch the order details';
            return response()->json($this->result);
        }

        foreach ($check_dealer as $value) {
            $spec_data = ($value->spec_data) ? json_decode($value->spec_data) : [];
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
        $check_dealer = Catalogue_Order::where('dealer', $dealer_id)->where('completed', '1')->get();

        if (!$check_dealer) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry the we could not fetch the order details';
            return response()->json($this->result);
        }

        foreach ($check_dealer as $value) {
            $data = ($value->data) ? json_decode($value->data) : [];
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
        $check_dealer_order_status = Dealer::where('account_id', $dealer_id)->where('order_status', 1)->get();

        if ($check_dealer_order_status && count($check_dealer_order_status) == 0) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Dealer doesnt have a completed order';
            return response()->json($this->result);
        }

        $check_dealer = ServiceParts::where('dealer', $dealer_id)->get();

        if (!$check_dealer) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry the we could not fetch the order details';
            return response()->json($this->result);
        }

        foreach ($check_dealer as $value) {
            $data = ($value->data) ? json_decode($value->data) : [];
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

        $check_dealer_order_status = Dealer::where('account_id', $dealer_id)->where('order_status', 1)->get();

        if ($check_dealer_order_status && count($check_dealer_order_status) == 0) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Dealer doesnt have a completed order';
            return response()->json($this->result);
        }

        $check_dealer = CardedProducts::where('dealer', $dealer_id)->where('completed', '1')->get();

        if (!$check_dealer) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry the we could not fetch the order details';
            return response()->json($this->result);
        }

        foreach ($check_dealer as $value) {
            $data = ($value->data) ? json_decode($value->data) : [];
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
        $fetch_branch_details = Branch::where('id', $branch_id)->where('status', '1')->first();

        if (!$fetch_branch_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry branch doesnt exist or deactivated';
            return response()->json($this->result);
        } else {
            // $fetch_dealers = BranchAssignDealer::where('branch_id',$branch_id)->get();  

            $fetch_dealers = BranchAssignDealer::where('branch_id', $branch_id)->get();


            $fetch_account_ids = $fetch_dealers->pluck("dealer_id")->toArray();

            $all_catalogue_orders = DB::table('atlas_dealers')->wherein('account_id', $fetch_account_ids)
                ->where('order_status', 1)
                ->join('atlas_catalogue_orders', 'atlas_dealers.account_id', '=', 'atlas_catalogue_orders.dealer')
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
            $this->result->message = 'All dealers with catalogue orders fetched successfully';
            return response()->json($this->result);
        }

        // check if the dealer has a catalogue order 

        // collect all the dealers with catalogue order 

        // send them to the front end 
    }

    public function fetch_all_dealers_with_active_order($branch_id)
    {
        // fetch all dealers assigned to a branch
        $fetch_branch_details = Branch::where('id', $branch_id)->where('status', '1')->first();

        if (!$fetch_branch_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry branch doesnt exist or deactivated';
            return response()->json($this->result);
        } else {
            $fetch_dealers = BranchAssignDealer::where('branch_id', $branch_id)->get();

            $fetch_account_ids = $fetch_dealers->pluck("dealer_id")->toArray();

            $all_dealer_ids = DB::table('atlas_dealers')->wherein('account_id', $fetch_account_ids)->where('order_status', 1)->pluck('id')->toArray();
    
            
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
                ->orderby('placed_order_date', 'desc')->get()->toArray();

            $format_all_orders = array_map(function($record){

                $dealer_id = $record->id;

                $check_total_price = Cart::where('dealer',$dealer_id)->sum('price');

                $record->total_amount = number_format($check_total_price,2);

                return $record;

            },$all_orders);

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
                $this->result->message = 'Sorry we could not fetch all the dealers assigned to the branch';
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
            $this->result->data = $format_all_orders;
            $this->result->message = 'All dealers with catalogue orders fetched successfully';
            return response()->json($this->result);
        }

        // check if the dealer has a catalogue order 

        // collect all the dealers with catalogue order 

        // send them to the front end 
    }

    public function fetch_all_dealers_with_active_service_parts_order($branch_id)
    {
        // fetch all dealers assigned to a branch
        $fetch_branch_details = Branch::where('id', $branch_id)->where('status', '1')->first();

        if (!$fetch_branch_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry branch doesnt exist or deactivated';
            return response()->json($this->result);
        } else {
            $fetch_dealers = BranchAssignDealer::where('branch_id', $branch_id)->get();


            $fetch_account_ids = $fetch_dealers->pluck("dealer_id")->toArray();

            $all_service_parts = DB::table('atlas_dealers')->wherein('account_id', $fetch_account_ids)
                ->where('order_status', 1)
                ->join('atlas_service_parts', 'atlas_dealers.account_id', '=', 'atlas_service_parts.dealer')
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
            $this->result->message = 'All dealers with service part orders fetched successfully';
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

    public function fetch_all_dealers_with_active_carded_products_order($branch_id)
    {
        // fetch all dealers assigned to a branch
        $fetch_branch_details = Branch::where('id', $branch_id)->where('status', '1')->first();

        if (!$fetch_branch_details) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry branch doesnt exist or deactivated';
            return response()->json($this->result);
        } else {
            $fetch_dealers = BranchAssignDealer::where('branch_id', $branch_id)->get();

            $fetch_account_ids = $fetch_dealers->pluck("dealer_id")->toArray();

            $all_carded_products = DB::table('atlas_dealers')->wherein('account_id', $fetch_account_ids)
                ->where('order_status', 1)
                ->join('atlas_carded_products', 'atlas_dealers.account_id', '=', 'atlas_carded_products.dealer')
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
            $this->result->message = 'All dealers with carded products fetched successfully';
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
            $this->result->message = 'Sorry we could not fetch the dealer details';
            return response()->json($this->result);
        }


        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $fetch_dealer;
        $this->result->message = 'Sorry we could not fetch the dealer details';
        return response()->json($this->result);
    }

    public function get_all_branch_loggedin_dealers($branch_id)
    {
        // get all the logged in dealers under a branch 
        $get_branch_details = BranchAssignDealer::where('branch_id', $branch_id)
            ->join('atlas_dealers', 'atlas_branch_assign_dealers.dealer_id', '=', 'atlas_dealers.id')
            ->orderby('atlas_dealers.account_id', 'desc')
            ->where('atlas_dealers.last_login', '!=', null)
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
            $this->result->message = 'Sorry we could not fetch the dealers that have logged in';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $get_branch_details;
        $this->result->message = 'All branch dealers that have loggedin have been fetched successfully';
        return response()->json($this->result);
    }

    public function get_all_branch_notloggedin_dealers($branch_id)
    {
        // get all the logged in dealers under a branch 
        $get_branch_details = BranchAssignDealer::where('branch_id', $branch_id)
            ->join('atlas_dealers', 'atlas_branch_assign_dealers.dealer_id', '=', 'atlas_dealers.id')
            ->orderby('atlas_dealers.account_id', 'desc')
            ->where('atlas_dealers.last_login', '=', null)
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
            $this->result->message = 'Sorry we could not fetch the dealers that have not logged in';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $get_branch_details;
        $this->result->message = 'All branch dealers that have not loggedin have been fetched successfully';
        return response()->json($this->result);
    }
}
