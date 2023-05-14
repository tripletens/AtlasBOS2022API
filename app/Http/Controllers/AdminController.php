<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Admin;
use App\Models\Dealer;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductsImport;
use App\Http\Helpers;
use App\Models\Products;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Branch;

use App\Models\SalesRep;

use App\Models\Promotional_ads;
use App\Models\Cart;
use App\Models\Catalogue_Order;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendDealerDetailsMail;

use App\Models\DealerCart;
use App\Models\ServiceParts;
use App\Models\CardedProducts;
use App\Models\PromotionalCategory;
use App\Models\ExtraProducts;

use Barryvdh\DomPDF\Facade as PDF;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\IOFactory;

set_time_limit(60000000000000);

class AdminController extends Controller
{
    //

    public function __construct()
    {
        // $this->middleware('auth:api', [
        //     'except' => ['login', 'register', 'test'],
        // ]);
        set_time_limit(60000000000000);

        $this->result = (object) [
            'status' => false,
            'status_code' => 200,
            'message' => null,
            'data' => (object) null,
            'token' => null,
            'debug' => null,
        ];
    }

    public function get_all_sale_rep_user()
    {
        $sale_rep = SalesRep::all();

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $sale_rep;
        $this->result->message = 'All sales rep user';
        return response()->json($this->result);
    }

    public function get_all_branch_user()
    {
        $branch = Branch::all();

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $branch;
        $this->result->message = 'All branch user';
        return response()->json($this->result);
    }

    public function all_logged_in_dealers()
    {
        $logged_in = Dealer::where('last_login', '!=', null)->get();

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $logged_in;
        $this->result->message = 'All logged in dealers';
        return response()->json($this->result);
    }

    public function all_not_logged_in_dealers()
    {
        $not_logged_in = Dealer::where('last_login', '=', null)->get();

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $not_logged_in;
        $this->result->message = 'All not logged in dealers';
        return response()->json($this->result);
    }

    public function upload_replaced_dealer_password_old(Request $request)
    {
        $csv = $request->file('excel');

        if ($csv == null) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Please upload replace data in excel format';
            return response()->json($this->result);
        }

        $the_file = $request->file('excel');
        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range = range(2, $row_limit);
            $column_range = range('F', $column_limit);
            $startcount = 2;
            $data = [];

            foreach ($row_range as $row) {
                $account_id = $sheet->getCell('B' . $row)->getValue();
                $password = $sheet->getCell('D' . $row)->getValue();

                if (Dealer::where('account_id', $account_id)->exists()) {
                    Dealer::where('account_id', $account_id)->update([
                        'password' => bcrypt($password),
                    ]);
                }

                ///  $startcount++;
            }
        } catch (Exception $e) {
            $error_code = $e->errorInfo[1];
            $this->result->status = false;
            $this->result->status_code = 404;
            $this->result->message = 'Something went wrong';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'replace dealer data uploaded successfully';
        return response()->json($this->result);
    }

    public function upload_replaced_dealer_data(Request $request)
    {
        $csv = $request->file('excel');

        if ($csv == null) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Please upload replace data in excel format';
            return response()->json($this->result);
        }

        $the_file = $request->file('excel');
        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range = range(2, $row_limit);
            $column_range = range('F', $column_limit);
            $startcount = 2;
            $data = [];

            foreach ($row_range as $row) {
                $account_id = $sheet->getCell('B' . $row)->getValue();
                $email = $sheet->getCell('C' . $row)->getValue();
                $password = $sheet->getCell('D' . $row)->getValue();
                $email = str_replace(' ', '', $email);
                $email = strtolower($email);
                $email = trim($email);

                if (Dealer::where('account_id', $account_id)->exists()) {
                    Dealer::where('account_id', $account_id)->update([
                        'email' => $email,
                        'password' => bcrypt($password),
                        'password_clear' => $password,
                    ]);
                }

                ///  $startcount++;
            }
        } catch (Exception $e) {
            $error_code = $e->errorInfo[1];
            $this->result->status = false;
            $this->result->status_code = 404;
            $this->result->message = 'Something went wrong';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'replace dealer data uploaded successfully';
        return response()->json($this->result);
    }

    public function fetch_all_service_parts()
    {
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

    public function fetch_dealers_by_account($dealer_id)
    {
        $fetch_dealer = Dealer::where('account_id', $dealer_id)
            ->get()
            ->first();

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

    public function update_vendor_name_vendor_logo(Request $request)
    {
        $csv = $request->file('excel');

        if ($csv == null) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Please upload products in excel format';
            return response()->json($this->result);
        }

        $the_file = $request->file('excel');
        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range = range(2, $row_limit);
            $column_range = range('F', $column_limit);
            $startcount = 2;
            $data = [];

            foreach ($row_range as $row) {
                $vendor_name = $sheet->getCell('B' . $row)->getValue();
                $vendor_logo = $sheet->getCell('C' . $row)->getValue();
                $atlasId = $sheet->getCell('D' . $row)->getValue();

                if (Products::where('atlas_id', $atlasId)->exists()) {
                    Products::where('atlas_id', $atlasId)->update([
                        'vendor_name' => $vendor_name,
                        'vendor_logo' => $vendor_logo,
                    ]);

                    // if (!$save_product) {
                    //     $this->result->status = false;
                    //     $this->result->status_code = 422;
                    //     $this->result->message =
                    //         'Sorry File could not be uploaded. Try again later.';
                    //     return response()->json($this->result);
                    // }
                }

                ///  $startcount++;
            }
        } catch (Exception $e) {
            $error_code = $e->errorInfo[1];
            $this->result->status = false;
            $this->result->status_code = 404;
            $this->result->message = 'Something went wrong';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Short Note Url uploaded successfully';
        return response()->json($this->result);
    }

    public function upload_promo_flyer(Request $request)
    {
        $csv = $request->file('excel');

        if ($csv == null) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Please upload products in excel format';
            return response()->json($this->result);
        }

        $the_file = $request->file('excel');
        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range = range(2, $row_limit);
            $column_range = range('F', $column_limit);
            $startcount = 2;
            $data = [];

            foreach ($row_range as $row) {
                $name = $sheet->getCell('A' . $row)->getValue();
                $url = $sheet->getCell('B' . $row)->getValue();

                Promotional_ads::create([
                    'name' => $name,
                    'image_url' => $url,
                    'type' => 'page',
                ]);

                ///  $startcount++;
            }
        } catch (Exception $e) {
            $error_code = $e->errorInfo[1];
            $this->result->status = false;
            $this->result->status_code = 404;
            $this->result->message = 'Something went wrong';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Short Note Url uploaded successfully';
        return response()->json($this->result);
    }

    public function update_short_note_url_upload(Request $request)
    {
        $csv = $request->file('excel');

        if ($csv == null) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Please upload products in excel format';
            return response()->json($this->result);
        }

        $the_file = $request->file('excel');
        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range = range(2, $row_limit);
            $column_range = range('F', $column_limit);
            $startcount = 2;
            $data = [];

            foreach ($row_range as $row) {
                $atlas_id = $sheet->getCell('A' . $row)->getValue();

                if (Products::where('atlas_id', $atlas_id)->exists()) {
                    $atlas_id = $sheet->getCell('A' . $row)->getValue();
                    $url = $sheet->getCell('B' . $row)->getValue();

                    Products::where('atlas_id', $atlas_id)->update([
                        'short_note_url' => $url,
                    ]);
                }
                ///  $startcount++;
            }
        } catch (Exception $e) {
            $error_code = $e->errorInfo[1];
            $this->result->status = false;
            $this->result->status_code = 404;
            $this->result->message = 'Something went wrong';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Short Note Url uploaded successfully';
        return response()->json($this->result);
    }

    public function upload_dealer_excel(Request $request)
    {
        set_time_limit(60000000000000);

        $csv = $request->file('excel');

        if ($csv == null) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Please upload products in csv format';
            return response()->json($this->result);
        }

        $the_file = $request->file('excel');
        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range = range(2, $row_limit);
            $column_range = range('F', $column_limit);
            $startcount = 2;
            $data = [];

            foreach ($row_range as $row) {
                $dealer_code = $sheet->getCell('B' . $row)->getValue();
                $full_name = $sheet->getCell('C' . $row)->getValue();
                $email = $sheet->getCell('D' . $row)->getValue();
                $password = $sheet->getCell('E' . $row)->getValue();
                $address = $sheet->getCell('F' . $row)->getValue();
                $location = $sheet->getCell('G' . $row)->getValue();
                $company = $sheet->getCell('C' . $row)->getValue();

                if (!Dealer::where('account_id', $dealer_code)->exists()) {
                    $save_dealer = Dealer::create([
                        'first_name' => $full_name,
                        'last_name' => null,
                        'email' => $email,
                        'password' => bcrypt($password),
                        'account_id' => $dealer_code,
                        'address' => $address,
                        'location' => $location,
                        'password_clear' => $password,
                        'full_name' => $full_name,
                        'company_name' => $company,
                    ]);

                    if (!$save_dealer) {
                        $this->result->status = false;
                        $this->result->status_code = 422;
                        $this->result->message =
                            'Sorry File could not be uploaded. Try again later.';
                        return response()->json($this->result);
                    }
                }
            }
        } catch (Exception $e) {
            $error_code = $e->errorInfo[1];
            $this->result->status = false;
            $this->result->status_code = 404;
            $this->result->message = 'Something went wrong';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Dealers uploaded successfully';
        return response()->json($this->result);
    }

    public function upload_new_products(Request $request)
    {
        $the_file = $request->file('excel');

        if ($the_file == null) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Please upload products in excel format';
            return response()->json($this->result);
        }

        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range = range(2, $row_limit);
            $column_range = range('F', $column_limit);
            $startcount = 2;
            $data = [];

            foreach ($row_range as $row) {
                $atlas_id = $sheet->getCell('A' . $row)->getValue();
                $desc = $sheet->getCell('B' . $row)->getValue();
                $img = $sheet->getCell('C' . $row)->getValue();
                $vendor_name = $sheet->getCell('D' . $row)->getValue();
                $vendor_logo = $sheet->getCell('E' . $row)->getValue();
                $xref = $sheet->getCell('F' . $row)->getValue();
                $um = $sheet->getCell('G' . $row)->getValue();
                $booking = $sheet->getCell('H' . $row)->getValue();
                // $regular = $sheet->getCell('I' . $row)->getValue();
                $full_desc = $sheet->getCell('I' . $row)->getValue();
                $category = strtolower($sheet->getCell('J' . $row)->getValue());
                $short_note = $sheet->getCell('K' . $row)->getValue();

                // $category_data = Category::where(
                //     'name',
                //     'LIKE',
                //     '%' . $category . '%'
                // )->first();

                switch ($category) {
                    case 'sealants/cleaners':
                        $category = 'sealant';
                        break;

                    case 'sealants and cleaners':
                        $category = 'sealant';
                        break;

                    case 'towing accessories':
                        $category = 'towing';
                        break;

                    case 'hardware':
                        $category = 'vents';
                        break;

                    case 'towing products':
                        $category = 'awning';
                        break;

                    case 'outdoor products':
                        $category = 'outdoor';
                        break;

                    default:
                        # code...
                        break;
                }

                if (!Products::where('atlas_id', $atlas_id)->exists()) {
                    $save_product = Products::create([
                        'atlas_id' => $atlas_id,
                        'img' => $img,
                        'description' => $desc,
                        'full_desc' => $full_desc,
                        'vendor_name' => $vendor_name,
                        'vendor_logo' => $vendor_logo,
                        'xref' => $xref,
                        'um' => $um,
                        'type' => 'regular',
                        'booking' => $booking,
                        'category' => $category,
                        ///   'category_id' => $category_data->id,
                        'short_note' => $short_note,
                        'check_new' => 1,
                    ]);

                    if (!$save_product) {
                        $this->result->status = false;
                        $this->result->status_code = 422;
                        $this->result->message =
                            'Sorry File could not be uploaded. Try again later.';
                        return response()->json($this->result);
                    }
                }
            }
        } catch (Exception $e) {
            $error_code = $e->errorInfo[1];
            $this->result->status = false;
            $this->result->status_code = 404;
            $this->result->message = 'Something went wrong';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Regular products uploaded successfully';
        return response()->json($this->result);
    }

    public function upload_product_assorted(Request $request)
    {
        set_time_limit(60000000000000);

        $csv = $request->file('excel');

        if ($csv == null) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Please upload products in csv format';
            return response()->json($this->result);
        }

        $the_file = $request->file('excel');
        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range = range(2, $row_limit);
            $column_range = range('F', $column_limit);
            $startcount = 2;
            $data = [];

            foreach ($row_range as $row) {
                $atlas_id = $sheet->getCell('C' . $row)->getValue();

                if (Products::where('atlas_id', $atlas_id)->exists()) {
                    $atlas_id = $sheet->getCell('C' . $row)->getValue();

                    $grouping = $sheet->getCell('I' . $row)->getValue();
                    $condition = $sheet->getCell('J' . $row)->getValue();
                    $special = $sheet->getCell('G' . $row)->getValue();
                    $booking = $sheet->getCell('F' . $row)->getValue();
                    $desc = $sheet->getCell('E' . $row)->getValue();

                    if (Products::where('atlas_id', $atlas_id)->exists()) {
                        $check_atlas_id = Products::where('atlas_id', $atlas_id)
                            ->get()
                            ->first();

                        $spec_data = [
                            'booking' => floatval($booking),
                            'special' => floatval($special),
                            'cond' => intval($condition),
                            'type' => 'assorted',
                            'desc' => $desc,
                        ];

                        Products::where('atlas_id', $atlas_id)->update([
                            'type' => 'assorted',
                        ]);

                        if ($check_atlas_id->spec_data) {
                            $spec = json_decode(
                                $check_atlas_id->spec_data,
                                true
                            );
                            array_push($spec, $spec_data);
                            $new_spec = json_encode($spec);

                            Products::where('atlas_id', $atlas_id)->update([
                                'cond' => $condition,
                            ]);
                            Products::where('atlas_id', $atlas_id)->update([
                                'grouping' => $grouping,
                            ]);

                            Products::where('atlas_id', $atlas_id)->update([
                                'spec_data' => $new_spec,
                            ]);
                        } else {
                            $data = [];
                            array_push($data, $spec_data);
                            $new_spec = json_encode($data);

                            Products::where('atlas_id', $atlas_id)->update([
                                'cond' => $condition,
                            ]);

                            Products::where('atlas_id', $atlas_id)->update([
                                'grouping' => $grouping,
                            ]);
                            Products::where('atlas_id', $atlas_id)->update([
                                'spec_data' => $new_spec,
                            ]);
                        }
                    }

                    // if (!$save_admin) {
                    //     $this->result->status = false;
                    //     $this->result->status_code = 422;
                    //     $this->result->message =
                    //         'Sorry File could not be uploaded. Try again later.';
                    //     return response()->json($this->result);
                    // }
                }
                ///  $startcount++;
            }
        } catch (Exception $e) {
            $error_code = $e->errorInfo[1];
            $this->result->status = false;
            $this->result->status_code = 404;
            $this->result->message = 'Something went wrong';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Assorted Products uploaded successfully';
        return response()->json($this->result);
    }

    public function upload_product_special(Request $request)
    {
        $csv = $request->file('excel');

        if ($csv == null) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Please upload products in csv format';
            return response()->json($this->result);
        }

        $the_file = $request->file('excel');
        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range = range(2, $row_limit);
            $column_range = range('F', $column_limit);
            $startcount = 2;
            $data = [];

            foreach ($row_range as $row) {
                $atlas_id = $sheet->getCell('C' . $row)->getValue();

                $condition = $sheet->getCell('I' . $row)->getValue();
                $special = $sheet->getCell('J' . $row)->getValue();
                $booking = $sheet->getCell('H' . $row)->getValue();
                $desc = $sheet->getCell('E' . $row)->getValue();

                if (Products::where('atlas_id', $atlas_id)->exists()) {
                    $check_atlas_id = Products::where('atlas_id', $atlas_id)
                        ->get()
                        ->first();

                    $spec_data = [
                        'booking' => floatval($booking),
                        'special' => floatval($special),
                        'cond' => intval($condition),
                        'type' => 'special',
                        'desc' => $desc,
                    ];

                    Products::where('atlas_id', $atlas_id)->update([
                        'type' => 'special',
                    ]);

                    if (isset($check_atlas_id->spec_data)) {
                        $spec = json_decode($check_atlas_id->spec_data, true);
                        array_push($spec, $spec_data);
                        $new_spec = json_encode($spec);

                        Products::where('atlas_id', $atlas_id)->update([
                            'cond' => $condition,
                        ]);

                        Products::where('atlas_id', $atlas_id)->update([
                            'spec_data' => $new_spec,
                        ]);
                    } else {
                        $data = [];
                        array_push($data, $spec_data);
                        $new_spec = json_encode($data);

                        Products::where('atlas_id', $atlas_id)->update([
                            'cond' => $condition,
                        ]);

                        Products::where('atlas_id', $atlas_id)->update([
                            'spec_data' => $new_spec,
                        ]);
                    }
                }
            }
        } catch (Exception $e) {
            $error_code = $e->errorInfo[1];
            $this->result->status = false;
            $this->result->status_code = 404;
            $this->result->message = 'Something went wrong';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message =
            'Quantity Break Products uploaded successfully';
        return response()->json($this->result);
    }

    public function upload_regular_products(Request $request)
    {
        $the_file = $request->file('excel');

        if ($the_file == null) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Please upload products in excel format';
            return response()->json($this->result);
        }

        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range = range(2, $row_limit);
            $column_range = range('F', $column_limit);
            $startcount = 2;
            $data = [];

            foreach ($row_range as $row) {
                $atlas_id = $sheet->getCell('B' . $row)->getValue();
                $desc = $sheet->getCell('C' . $row)->getValue();
                $img = $sheet->getCell('D' . $row)->getValue();
                $vendor_name = $sheet->getCell('E' . $row)->getValue();
                $vendor_logo = $sheet->getCell('F' . $row)->getValue();
                $xref = $sheet->getCell('G' . $row)->getValue();
                $um = $sheet->getCell('H' . $row)->getValue();
                $booking = $sheet->getCell('I' . $row)->getValue();
                // $regular = $sheet->getCell('I' . $row)->getValue();
                $full_desc = $sheet->getCell('J' . $row)->getValue();
                $category = strtolower($sheet->getCell('K' . $row)->getValue());
                $short_note = $sheet->getCell('L' . $row)->getValue();

                $category_data = Category::where(
                    'name',
                    'LIKE',
                    '%' . $category . '%'
                )->first();

                switch ($category) {
                    case 'sealants/cleaners':
                        $category = 'sealant';
                        break;
                    case 'sealants and cleaners':
                        $category = 'sealant';
                        break;

                    case 'towing accessories':
                        $category = 'towing';
                        break;

                    case 'hardware':
                        $category = 'vents';
                        break;

                    case 'towing products':
                        $category = 'awning';
                        break;

                    case 'outdoor products':
                        $category = 'outdoor';
                        break;

                    default:
                        # code...
                        break;
                }

                if (!Products::where('atlas_id', $atlas_id)->exists()) {
                    $save_product = Products::create([
                        'atlas_id' => $atlas_id,
                        'img' => $img,
                        'description' => $desc,
                        'full_desc' => $full_desc,
                        'vendor_name' => $vendor_name,
                        'vendor_logo' => $vendor_logo,
                        'xref' => $xref,
                        'um' => $um,
                        'type' => 'regular',
                        'booking' => $booking,
                        'category' => $category,
                        /// 'category_id' => $category_data->id,
                        'short_note' => $short_note,
                    ]);

                    if (!$save_product) {
                        $this->result->status = false;
                        $this->result->status_code = 422;
                        $this->result->message =
                            'Sorry File could not be uploaded. Try again later.';
                        return response()->json($this->result);
                    }
                }
            }
        } catch (Exception $e) {
            $error_code = $e->errorInfo[1];
            $this->result->status = false;
            $this->result->status_code = 404;
            $this->result->message = 'Something went wrong';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Regular products uploaded successfully';
        return response()->json($this->result);
    }

    public function dealer_location_filter(Request $request)
    {
        $location = $request->query('location');
        $dealers = Dealer::where('location', $location)->get();

        foreach ($dealers as $dealer) {
            $code = $dealer->account_id;
            $id = $dealer->id;
            $check_service_parts = ServiceParts::where(
                'dealer',
                $code
            )->exists();

            if (Cart::where('dealer', $id)->exists()) {
                $total = Cart::where('dealer', $id)
                    ->where('status', '1')
                    ->sum('price');
                $dealer->total_price = $total;

                $dealer->total_item = Cart::where('dealer', $id)
                    ->where('status', '1')
                    ->count();

                $dealer->total_pending_item = Cart::where('dealer', $id)
                    ->where('status', '0')
                    ->count();

                $dealer->total_pending_amt = DB::table('cart')
                    ->where('dealer', $id)
                    ->where('status', '0')
                    ->sum('price');
            } else {
                $dealer->total_price = 0;
                $dealer->total_item = 0;
                $dealer->total_pending_item = 0;
                $dealer->total_pending_amt = 0;
            }

            if ($check_service_parts) {
                $service = ServiceParts::where('dealer', $code)
                    ->get()
                    ->first();
                $dealer->service_completed = $service->completed;
            } else {
                $dealer->service_completed = 3;
            }

            $check_carded_parts = CardedProducts::where(
                'dealer',
                $code
            )->exists();

            if ($check_carded_parts) {
                $carded = CardedProducts::where('dealer', $code)
                    ->get()
                    ->first();
                $dealer->carded_completed = $carded->completed;
            } else {
                $dealer->carded_completed = 3;
            }

            $check_catalogue_parts = CardedProducts::where(
                'dealer',
                $code
            )->exists();
            if ($check_catalogue_parts) {
                $catalogue = CardedProducts::where('dealer', $code)
                    ->get()
                    ->first();
                $dealer->catalogue_completed = $catalogue->completed;
            } else {
                $dealer->catalogue_completed = 3;
            }
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $dealers;
        $this->result->message = 'Dealers fetched Successfully';

        return response()->json($this->result);
    }

    public function close_bos_program()
    {
        Dealer::query()->update(['close_program' => 1]);
        Admin::query()->update(['close_program' => 1]);

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Program has been closed';
        return response()->json($this->result);
    }

    public function open_bos_program()
    {
        Dealer::query()->update(['close_program' => 0]);
        Admin::query()->update(['close_program' => 0]);

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Program has been opened';
        return response()->json($this->result);
    }

    public function upload_service_products(Request $request)
    {
        $the_file = $request->file('excel');

        if ($the_file == null) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Please upload products in excel format';
            return response()->json($this->result);
        }

        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range = range(2, $row_limit);
            $column_range = range('F', $column_limit);
            $startcount = 2;
            $data = [];

            foreach ($row_range as $row) {
                $item_code = $sheet->getCell('A' . $row)->getValue();
                $vendor_code = $sheet->getCell('B' . $row)->getValue();
                $description = $sheet->getCell('C' . $row)->getValue();

                if (!ExtraProducts::where('item_code', $item_code)->exists()) {
                    $save_catalogue = ExtraProducts::create([
                        'item_code' => $item_code,
                        'vendor_code' => $vendor_code,
                        'description' => $description,
                        'type' => '3',
                        'type_name' => 'service_parts',
                    ]);

                    if (!$save_catalogue) {
                        $this->result->status = false;
                        $this->result->status_code = 422;
                        $this->result->message =
                            'Sorry File could not be uploaded. Try again later.';
                        return response()->json($this->result);
                    }
                }
            }
        } catch (Exception $e) {
            $error_code = $e->errorInfo[1];
            $this->result->status = false;
            $this->result->status_code = 404;
            $this->result->message = 'Something went wrong';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Service part products uploaded successfully';
        return response()->json($this->result);
    }

    public function upload_carded_products(Request $request)
    {
        $the_file = $request->file('excel');

        if ($the_file == null) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Please upload products in excel format';
            return response()->json($this->result);
        }

        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range = range(2, $row_limit);
            $column_range = range('F', $column_limit);
            $startcount = 2;
            $data = [];

            foreach ($row_range as $row) {
                $item_code = $sheet->getCell('A' . $row)->getValue();
                $vendor_code = $sheet->getCell('B' . $row)->getValue();
                $description = $sheet->getCell('C' . $row)->getValue();

                if (!ExtraProducts::where('item_code', $item_code)->exists()) {
                    $save_catalogue = ExtraProducts::create([
                        'item_code' => $item_code,
                        'vendor_code' => $vendor_code,
                        'description' => $description,
                        'type' => '2',
                        'type_name' => 'carded_product',
                    ]);

                    if (!$save_catalogue) {
                        $this->result->status = false;
                        $this->result->status_code = 422;
                        $this->result->message =
                            'Sorry File could not be uploaded. Try again later.';
                        return response()->json($this->result);
                    }
                }
            }
        } catch (Exception $e) {
            $error_code = $e->errorInfo[1];
            $this->result->status = false;
            $this->result->status_code = 404;
            $this->result->message = 'Something went wrong';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Carded products uploaded successfully';
        return response()->json($this->result);
    }

    public function upload_catalogue_products(Request $request)
    {
        $the_file = $request->file('excel');

        if ($the_file == null) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Please upload products in excel format';
            return response()->json($this->result);
        }

        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $row_limit = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range = range(2, $row_limit);
            $column_range = range('F', $column_limit);
            $startcount = 2;
            $data = [];

            foreach ($row_range as $row) {
                $item_code = $sheet->getCell('A' . $row)->getValue();
                $vendor_code = $sheet->getCell('B' . $row)->getValue();
                $description = $sheet->getCell('C' . $row)->getValue();

                if (!ExtraProducts::where('item_code', $item_code)->exists()) {
                    $save_catalogue = ExtraProducts::create([
                        'item_code' => $item_code,
                        'vendor_code' => $vendor_code,
                        'description' => $description,
                        'type' => '1',
                        'type_name' => 'catalogue_product',
                    ]);

                    if (!$save_catalogue) {
                        $this->result->status = false;
                        $this->result->status_code = 422;
                        $this->result->message =
                            'Sorry File could not be uploaded. Try again later.';
                        return response()->json($this->result);
                    }
                }
            }
        } catch (Exception $e) {
            $error_code = $e->errorInfo[1];
            $this->result->status = false;
            $this->result->status_code = 404;
            $this->result->message = 'Something went wrong';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Catalogue products uploaded successfully';
        return response()->json($this->result);
    }

    public function update_pro_type()
    {
        $pro = Products::all();

        foreach ($pro as $value) {
            $spec_data = $value->spec_data;
            $id = $value->id;

            if ($spec_data && $spec_data != null && $spec_data != 'null') {
                ///  return $value;
                $spec_data = json_decode($spec_data);

                if (isset($spec_data[0])) {
                    ///  return $spec_data;
                    $first = $spec_data[0];
                    $type = isset($first->type)
                        ? strtolower($first->type)
                        : null;
                    if ($type == 'assorted') {
                        $grouping = isset($first->grouping)
                            ? $first->grouping
                            : null;

                        if ($grouping != null) {
                            Products::where('id', $id)->update([
                                'grouping' => $grouping,
                            ]);
                        }
                    }
                    if ($type != null) {
                        Products::where('id', $id)->update(['type' => $type]);
                    }
                } else {
                    Products::where('id', $id)->update(['type' => 'regular']);
                }
            } else {
                Products::where('id', $id)->update(['type' => 'regular']);
            }
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'type fixed';
        return response()->json($this->result);
    }

    public function end_booking_program()
    {
        Dealer::query()->update(['close_program' => '1']);

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'All Booking Order Has Been Stopped';
        return response()->json($this->result);
    }

    public function active_dealer_booking($id)
    {
        Dealer::query()
            ->where('id', $id)
            ->update(['close_program' => '0']);
        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Dealer Has Been Activated for booking';
        return response()->json($this->result);
    }

    public function admin_send_order_mail($id)
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

            // $message->to( 'orders@atlastrailer.com' )
            //     ->subject( "ATLAS BOOKING PROGRAM DEALER'S ORDER" )
            //     ->attachData( $pdf->output(), $data[ 'order_file_name' ] );
        });

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Email has been sent';
        return response()->json($this->result);
    }

    public function admin_download_dealer_order($id)
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

        $this->result->message = 'Download Pdf';
        return response()->json($this->result);
    }

    public function rollback_order_status($id)
    {
        $dealer = Dealer::where('id', $id)
            ->get()
            ->first();
        $account = $dealer->account_id;
        Cart::where('dealer', $id)->update(['status' => '0']);
        Dealer::where('id', $id)->update(['order_status' => '0']);

        if (ServiceParts::where('dealer', $account)->exists()) {
            ServiceParts::where('dealer', $account)->update([
                'completed' => '0',
            ]);
        }

        if (CardedProducts::where('dealer', $account)->exists()) {
            CardedProducts::where('dealer', $account)->update([
                'completed' => '0',
            ]);
        }

        if (Catalogue_Order::where('dealer', $account)->exists()) {
            Catalogue_Order::where('dealer', $account)->update([
                'completed' => '0',
            ]);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $dealer;
        $this->result->message = "User's ordering status has been rolled back";

        return response()->json($this->result);
    }

    public function export_all_service_parts_orders(Request $request)
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

            $excel_data = AdminController::load_service_parts_query($dealer);

            $this->result->status = true;
            $this->result->data = $excel_data;
            return response()->json($this->result);
        } elseif ($from) {
            $dealer = Dealer::query()
                ->where('order_status', '1')
                ->where('placed_order_date', '>=', $from)
                ->get();

            $excel_data = AdminController::load_service_parts_query($dealer);
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

            $excel_data = AdminController::load_service_parts_query($dealer);
            $this->result->status = true;
            $this->result->data = $excel_data;
            return response()->json($this->result);
        }
    }

    public static function load_service_parts_query($data)
    {
        if ($data) {
            global $dealer;
            global $added_date;

            $tester = [];
            foreach ($data as $value) {
                $dealer = $value->account_id;
                $account_id = $value->account_id;
                $carts = ServiceParts::query()
                    ->where('dealer', $dealer)
                    ->get()
                    ->first();

                if (!empty($carts['data'])) {
                    $cat_data = json_decode($carts['data'], true);
                    $added_date = $carts['created_at'];

                    foreach ($cat_data as $value) {
                        global $dealer;
                        $value['dealer'] = $dealer;
                        $value['added_date'] = $added_date;

                        array_push($tester, $value);
                    }
                }
            }

            return $tester;
        } else {
            return [];
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

    public static function load_carded_query($data)
    {
        if ($data) {
            global $dealer;
            global $added_date;

            $tester = [];
            foreach ($data as $value) {
                $dealer = $value->account_id;
                $account_id = $value->account_id;
                $carts = CardedProducts::query()
                    ->where('dealer', $dealer)
                    ->get()
                    ->first();

                if (!empty($carts['data'])) {
                    $cat_data = json_decode($carts['data'], true);
                    $added_date = $carts['created_at'];

                    foreach ($cat_data as $value) {
                        global $dealer;
                        $value['dealer'] = $dealer;
                        $value['added_date'] = $added_date;
                        array_push($tester, $value);
                    }
                }
            }

            return $tester;
        } else {
            return [];
        }
    }

    public function export_all_catalogue_orders(Request $request)
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

            $excel_data = AdminController::load_catalogue_query($dealer);

            $this->result->status = true;
            $this->result->data = $excel_data;
            return response()->json($this->result);
        } elseif ($from) {
            $dealer = Dealer::query()
                ->where('order_status', '1')
                ->where('placed_order_date', '>=', $from)
                ->get();

            $excel_data = AdminController::load_catalogue_query($dealer);
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

            $excel_data = AdminController::load_catalogue_query($dealer);
            $this->result->status = true;
            $this->result->data = $excel_data;
            return response()->json($this->result);
        }
    }

    public static function load_catalogue_query($data)
    {
        if ($data) {
            global $dealer;
            global $added_date;
            $tester = [];
            foreach ($data as $value) {
                $dealer = $value->account_id;
                $account_id = $value->account_id;

                $carts = Catalogue_Order::query()
                    ->where('dealer', $dealer)
                    ->get()
                    ->first();

                if (!empty($carts['data'])) {
                    $added_date = $carts['created_at'];

                    $cat_data = json_decode($carts['data'], true);

                    foreach ($cat_data as $value) {
                        global $dealer;
                        $value['dealer'] = $dealer;
                        $value['added_date'] = $added_date;
                        array_push($tester, $value);
                    }
                }
            }

            return $tester;
        } else {
            return [];
        }
    }

    public static function load_query_data($data)
    {
        if ($data) {
            $query_data_con = [];
            $full_data = [];
            foreach ($data as $value) {
                $dealer = $value->id;
                global $account_id;
                global $placed_order_date;

                $account_id = $value->account_id;
                $placed_order_date = $value->placed_order_date;

                $carts = Cart::query()
                    ->where('dealer', $dealer)
                    ->get()
                    ->toArray();
                /// return $carts;

                $query_data = array_map(function ($each) {
                    global $account_id;
                    global $placed_order_date;

                    return [
                        'id' => $each['id'],
                        'account_id' => $account_id,
                        'atlas_id' => $each['atlas_id'],
                        'qty' => $each['qty'],
                        'unit_price' => $each['unit_price'],
                        'price' => $each['price'],
                        'created_at' => $placed_order_date,
                    ];
                }, $carts);

                array_push($query_data_con, $query_data);
            }

            foreach ($query_data_con as $value) {
                foreach ($value as $inner_value) {
                    array_push($full_data, $inner_value);
                }
            }

            return $full_data;
        } else {
            return [];
        }
    }

    public function export_excel_query(Request $request)
    {
        $excel_main_data = [];
        $from = $request->query('from') != '' ? $request->query('from') : false;
        $to = $request->query('to') != '' ? $request->query('to') : false;

        $from = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();

        if ($from && $to) {
            $dealer = Dealer::query()
                ->where('order_status', '1')
                ->whereBetween('placed_order_date', [$from, $to])
                ->get();

            $excel_data = AdminController::load_query_data($dealer);
            $excel_main_data = $excel_data;
        } elseif ($from) {
            $dealer = Dealer::query()
                ->where('order_status', '1')
                ->where('placed_order_date', '>=', $from)
                ->get();

            $excel_data = AdminController::load_query_data($dealer);
            $excel_main_data = $excel_data;
        } elseif ($to) {
            $this->result->status = true;
            $this->result->data = [];
            return response()->json($this->result);
        } else {
            $dealer = Dealer::query()
                ->where('order_status', '1')
                ->get();

            $excel_data = AdminController::load_query_data($dealer);
            $excel_main_data = $excel_data;
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $excel_main_data;
        $this->result->message = 'Export Booking Order Request';

        return response()->json($this->result);
    }

    public function dealer_upload(Request $request)
    {
        $csv = $request->file('csv');
        if ($csv == null) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Please upload dealers in csv format';
            return response()->json($this->result);
        }

        if ($csv->getSize() > 0) {
            $file = fopen($_FILES['csv']['tmp_name'], 'r');
            $csv_data = [];
            while (($col = fgetcsv($file, 1000, ',')) !== false) {
                $csv_data[] = $col;
            }
            array_shift($csv_data);
            // remove the first row of the csv
            foreach ($csv_data as $key => $value) {
                //$sep = explode( $value[ 1 ], '' );
                $dealer_code = $value[0];
                $full_name = $value[1];
                $location_text = $value[2];
                $phone = $value[3];
                $email = $value[4];
                $password = $value[5];
                $last_name = '';
                $location = 0;

                $save_product = Dealer::create([
                    'first_name' => $full_name,
                    'last_name' => null,
                    'email' => $email,
                    'password' => bcrypt($password),
                    'account_id' => $dealer_code,
                    'phone' => $phone,
                    'location' => $location_text,
                    'password_clear' => $password,
                    'full_name' => $full_name,
                    'company_name' => $full_name,
                ]);

                if (!$save_product) {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message =
                        'Sorry File could not be uploaded. Try again later.';
                    return response()->json($this->result);
                }
            }
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Dealers uploaded successfully';
        return response()->json($this->result);
        fclose($file);
    }

    public function fetch_dealer_catalogue_orders($dealer_id)
    {
        // return $dealer_id;
        $catalogue_order = Catalogue_Order::where('dealer', $dealer_id)->get();

        if (!$catalogue_order) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Dealer\'s catalogue order could not be fetched';
            return response()->json($this->result);
        }

        $dealer_details = Dealer::where('account_id', $dealer_id)->get();

        // return $catalogue_order;

        if (!$dealer_details || count($dealer_details) == 0) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Dealer\'s with account id - ' . $dealer_id . ' not found';
            return response()->json($this->result);
        }

        // return $dealer_details;

        $order_date = $dealer_details[0]->placed_order_date;

        foreach ($catalogue_order as $value) {
            $data = $value->data ? json_decode($value->data) : [];
            $value->data = $data;

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

            $value->order_date = $order_date;
        }

        if (!$catalogue_order) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Dealer\'s catalogue order could not be fetched';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $catalogue_order;
        $this->result->message =
            'Dealer\'s catalogue order fetched successfully. ';
        return response()->json($this->result);
    }

    public function bulk_upload(Request $request)
    {
        ///Excel::import( new ProductsImport, $request->file( 'file' ) );
        //return $_FILES[ 'file' ][ 'tmp_name' ];

        $file = fopen($_FILES['file']['tmp_name'], 'r');
        $count = 0;

        while (($col = fgetcsv($file, 1000, ',')) !== false) {
            if (is_numeric($col[1])) {
            }
            return $col[2];
        }

        //   if ( $_FILES[ 'file' ][ 'size' ] > 0 ) {
        //       $file = fopen( $_FILES[ 'file' ][ 'tmp_name' ], 'r' );
        //       $count = 0;
        //        while ( ( $col = fgetcsv( $file, 1000, ',' ) ) !== false ) {
        //            if ( is_numeric( $col[ 1 ] ) ) {
        //             $product = Products::create( [
        //             'atlas_id'     => $col[ 1 ],
        //             'img'    => $col[ 2 ],
        //             'vendor_logo' => $col[ 3 ],
        //             'vendor_name' => $col[ 4 ],
        //             'xref' => $col[ 5 ],
        //             'description' => $col[ 6 ],
        //             'um' => $col[ 7 ],
        //             'booking' => $col[ 8 ],
        //             'special' => $col[ 9 ],
        //             'cond' => $col[ 10 ],
        //             'type' => $col[ 11 ],
        //             'grouping' => $col[ 12 ],
        //             'full_desc' => $col[ 13 ]
        // ] );
        //                 $count++;
        //            }
        //        }
        //         $this->result->message = 'Successful';
        //        $this->result->status = true;
        //                   $this->result->data = $count;
        //   }
        return response()->json($this->result);
    }

    public function search_category($category)
    {
        // dd( $category );

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
                $this->check_if_its_new($record['created_at'], 10) == true
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

    public function register_dealer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstName' => 'required',
            //'lastName' => 'required',
            'email' => 'required|string|email|unique:atlas_dealers',
            'password' => 'required',
            ///  'phone' => 'required',
            'location' => 'required',
            'account_id' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = $validator->errors()->get('email');
        } else {
            $dealer = Dealer::create([
                'first_name' => $request->firstName,
                'full_name' => $request->firstName,
                'company_name' => $request->firstName,

                ///  'last_name' => $request->lastName,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'password_clear' => $request->password,
                ///  'phone' => $request->phone,
                'username' => $request->firstName . Helpers::generate_number(3),
                'status' => '1',
                'location' => $request->location,
                'account_id' => $request->account_id,
            ]);

            BranchAssignDealer::create([
                'branch_id' => $$request->branch,
                'dealer_id' => $request->account_id,
            ]);

            $this->result->status = true;
            $this->result->message = 'Successful';
        }
        return response()->json($this->result);
    }

    public function register_admin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:6',
            'email' => 'required|email|unique:atlas_admin',
            'phone' => 'required|string',
            'role' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = $validator->errors()->get('email');
        } else {
            Admin::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role' => $request->role,
                'password' => bcrypt($request->password),
                'password_clear' => $request->password,
                'status' => true,
            ]);

            $this->result->status = true;
            $this->result->message = 'Successful';
        }
        return response()->json($this->result);
    }

    public function login(Request $request)
    {
        //valid credential
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        // Admin::create( [
        //         'email'=>$request->email,
        //         'password' => bcrypt( $request->password ),
        //         'role' => 1,
        //         'status' => 1
        // ]
        // );

        if (
            !($token = Auth::guard('admin')->attempt([
                'email' => $request->email,
                'password' => $request->password,
            ]))
        ) {
            $this->result->status_code = 401;
            $this->result->message = 'Invalid login credentials';
            return response()->json($this->result);
        }

        $active_staff = Admin::query()
            ->where('email', $request->email)
            ->get()
            ->first();

        if ($active_staff['status'] == 0) {
            $this->result->status_code = 401;
            $this->result->message = 'Account has been deactivated';
            return response()->json($this->result);
        }

        $admin = Admin::where('email', $request->email)->first();
        $admin->role = 'admin';

        $this->result->token = $this->respondWithToken($token);
        $this->result->status = true;
        $this->result->data->admin = $admin;
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

    public function decode_func($data)
    {
        return json_decode($data->spec_data, true);
    }

    public function upload_product_csv(Request $request)
    {
        $csv = $request->file('csv');

        if ($csv == null) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Please upload products in csv format';
            return response()->json($this->result);
        }

        if ($csv->getSize() > 0) {
            $file = fopen($_FILES['csv']['tmp_name'], 'r');
            $csv_data = [];
            while (($col = fgetcsv($file, 1000, ',')) !== false) {
                $csv_data[] = $col;
            }

            array_shift($csv_data);
            // remove the first row of the csv

            $test = [];

            foreach ($csv_data as $key => $value) {
                # code...

                $atlas_id = $value[1];
                $check_atlas_id = Products::where('atlas_id', $atlas_id)
                    ->get()
                    ->first();

                if ($check_atlas_id) {
                    $booking = $value[8];
                    $special = $value[9];
                    $condition = $value[10];
                    $type = $value[11];
                    $grouping = $value[12];
                    $desc_spec = $value[6];

                    $spec_data = [
                        'booking' => floatval($booking),
                        'special' => floatval($special),
                        'cond' => intval($condition),
                        'type' => strtolower($type),
                        'desc' => strtolower($desc_spec),
                    ];

                    if ($special == '') {
                        continue;
                    } else {
                        if (!empty($check_atlas_id->spec_data)) {
                            $spec = json_decode(
                                $check_atlas_id->spec_data,
                                true
                            );
                            array_push($spec, $spec_data);
                            $new_spec = json_encode($spec);

                            Products::where('atlas_id', $atlas_id)->update([
                                'grouping' => $grouping,
                            ]);
                            Products::where('atlas_id', $atlas_id)->update([
                                'spec_data' => $new_spec,
                            ]);
                        } else {
                            $data = [];
                            array_push($data, $spec_data);
                            $new_spec = json_encode($data);
                            //$new_spec = $new_spec;

                            Products::where('atlas_id', $atlas_id)->update([
                                'grouping' => $grouping,
                            ]);
                            Products::where('atlas_id', $atlas_id)->update([
                                'spec_data' => $new_spec,
                            ]);
                        }
                    }
                } else {
                    $spec_arr = [];
                    $atlas_id = $value[1];
                    $image = $value[2];
                    $vendor_logo = $value[3];
                    $vendor_name = $value[4];
                    $xref = $value[5];
                    $description = $value[6];
                    $um = $value[7];
                    $booking = $value[8];
                    $special = $value[9];
                    $condition = $value[10];
                    $type = $value[11];
                    $grouping = $value[12];
                    $full_description = $value[13];
                    $category_name = $value[14];
                    $short_note = $value[15];
                    $category_id = Category::where(
                        'name',
                        'LIKE',
                        '%' . $category_name . '%'
                    )->first();

                    // $data = [];
                    // $spec_data =  [
                    //     'booking' => intval( $booking ),
                    //     'special' => $special,
                    //     'cond' => $condition,
                    //     'type' => strtolower( $type )
                    // ];
                    // array_push( $data, $spec_data );
                    // $spec_data = json_encode( [ $spec_data ] );

                    $save_product = Products::create([
                        'atlas_id' => $atlas_id,

                        'description' => $description,
                        'img' => $image,
                        'assorted_discount' => null,
                        'quantity_discount' => null,
                        'status' => true,
                        'vendor_name' => $vendor_name,
                        'vendor_logo' => $vendor_logo,
                        'xref' => $xref,
                        'um' => $um,
                        'booking' => $booking,
                        'special' => $special,
                        'cond' => $condition,
                        'type' => $type,
                        'grouping' => $grouping,
                        'full_desc' => $full_description,
                        /// 'spec_data' => $spec_data,
                        'category' => $category_name,
                        'category_id' => $category_id['id']
                            ? $category_id['id']
                            : null,
                        'short_note' => $short_note,
                    ]);

                    if (!$save_product) {
                        $this->result->status = false;
                        $this->result->status_code = 422;
                        $this->result->message =
                            'Sorry File could not be uploaded. Try again later.';
                        return response()->json($this->result);
                    }
                }
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Products uploaded successfully';
            return response()->json($this->result);
            fclose($file);
        }
    }

    public function add_promotional_ad(Request $request)
    {
        // `id`, `category_id`, `name`, `pdf_url`, `description`, `image_url`,
        //  `status`, `created_at`, `updated_at`, `deleted_at`
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'img' => 'required|mimes:jpg,bmp,png',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'name' => $validator->errors()->get('name'),
                'img' => $validator->errors()->get('img'),
            ];
            return response()->json($this->result);
        } else {
            // add the promotional add
            $category_id = $request->input('category_id');
            $name = $request->input('name');
            $description = $request->input('description');
            $pdf_url = $request->input('pdf_url');

            if ($request->file('img')->isValid()) {
                $img_extension = $request->file('img')->extension();
                $img_filename = $request->file('img')->getClientOriginalName();
                $new_img_filename =
                    Str::slug($img_filename . date('Y-m-d')) .
                    '.' .
                    $img_extension;
                $img_path = $request
                    ->file('img')
                    ->storeAs('public/img', $new_img_filename);

                if ($img_path) {
                    // save to the db
                    $save_promotional_ad = Promotional_ads::create([
                        'name' => $name,
                        'description' => $description,
                        'category_id' => $category_id,
                        'image_url' => env('APP_URL') . Storage::url($img_path),
                        'created_at' => Carbon::now(),
                    ]);

                    if ($save_promotional_ad) {
                        $this->result->status = true;
                        $this->result->status_code = 200;
                        $this->result->message =
                            'Promotional Ad Successfully Added';
                        return response()->json($this->result);
                    } else {
                        $this->result->status = true;
                        $this->result->status_code = 404;
                        $this->result->message =
                            'An Error Ocurred, Promotional Ad Addition failed';
                        return response()->json($this->result);
                    }
                } else {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message =
                        'Sorry We could not upload the Promotional Ad image. Please try again later!';
                    return response()->json($this->result);
                }
            } else {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Sorry you uploaded an invalid Image';
                return response()->json($this->result);
            }

            $this->result->status = true;
            $this->result->message = 'Successful';
        }
        return response()->json($this->result);
    }

    public function fetch_all_promotional_ad()
    {
        $promotional_ads = Promotional_ads::paginate(5);
        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $promotional_ads;
        $this->result->message = 'All Promotional Ad fetched Successfully';
        return response()->json($this->result);
    }

    public function fetch_one_promotional_ad($id)
    {
        $promotional_ad = Promotional_ads::where('id', $id)
            ->where('status', 1)
            ->get();
        if (count($promotional_ad) == 0) {
            // not found
            $this->result->status = true;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry Promotional Ad doesnt exist or has already been deleted';
        } else {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $promotional_ad;
            $this->result->message = 'Promotional Ad fetched Successfully';
        }

        return response()->json($this->result);
    }

    public function fetch_one_promotional_ad_by_category_id_type(
        $type,
        $category_id
    ) {
        $promotional_ad = Promotional_ads::where('category_id', $category_id)
            ->where('type', $type)
            ->get();
        if (count($promotional_ad) == 0) {
            // not found
            $this->result->status = true;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry Promotional Ad doesnt exist or has already been deleted';
        } else {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $promotional_ad;
            $this->result->message = 'Promotional Ad fetched Successfully';
        }

        return response()->json($this->result);
    }

    public function update_promotional_ad(Request $request, $id)
    {
        // `name`, `image`, `color_code`, `description`,
        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
            'name' => 'required|string',
            'color_code' => 'required',
            'img' => 'required|mimes:jpg,bmp,png|max:2048',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'name' => $validator->errors()->get('name'),
                'color_code' => $validator->errors()->get('color_code'),
                'image' => $validator->errors()->get('image'),
            ];
            return response()->json($this->result);
        } else {
            // update the category
            $category = Category::where('id', $id)
                ->where('status', 1)
                ->get();

            if (count($category) == 0) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry Category doesnt exists in our records';
                return response()->json($this->result);
            }

            if ($request->file('img')->isValid()) {
                $img_extension = $request->file('img')->extension();
                $img_filename = $request->file('img')->getClientOriginalName();
                $new_img_filename =
                    Str::slug($img_filename . date('Y-m-d')) .
                    '.' .
                    $img_extension;
                $img_path = $request
                    ->file('img')
                    ->storeAs('public/img', $new_img_filename);

                if ($img_path) {
                    // save to the db
                    $name = $request->input('name');
                    $description = $request->input('description');
                    $color_code = $request->input('color_code');

                    $category[0]->name = $name;
                    $category[0]->color_code = $color_code;
                    $category[0]->description = $description;
                    $category[0]->image =
                        env('APP_URL') . Storage::url($img_path);

                    $save_category = $category[0]->save();

                    if ($save_category) {
                        $this->result->status = true;
                        $this->result->status_code = 200;
                        $this->result->message =
                            'Category Successfully Updated';
                        return response()->json($this->result);
                    } else {
                        $this->result->status = true;
                        $this->result->status_code = 404;
                        $this->result->message =
                            'An Error Ocurred, Category Updating failed';
                        return response()->json($this->result);
                    }
                } else {
                    $this->result->status = false;
                    $this->result->status_code = 422;
                    $this->result->message =
                        'Sorry We could not upload the Category image. Please try again later!';
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

    public function available_catalogue_orders()
    {
        // $dealers = Dealer::all();

        // $fetch_account_ids = $dealers->pluck( 'account_id' )->toArray();

        // $all_dealer_ids_order_status = DB::table( 'atlas_dealers' )->wherein( 'account_id', $fetch_account_ids )->where( 'order_status', 1 )->orderby( 'placed_order_date', 'desc' )->pluck( 'account_id' )->toArray();

        // $available_catalogue_orders = DB::table( 'atlas_catalogue_orders' )->wherein( 'dealer', $all_dealer_ids_order_status )->get();

        // return $available_catalogue_orders;

        $available_catalogue_orders = DB::table('atlas_catalogue_orders')
            ->join(
                'atlas_dealers',
                'atlas_catalogue_orders.dealer',
                '=',
                'atlas_dealers.account_id'
            )
            ->where('atlas_dealers.order_status', 1)
            ->orderby('atlas_dealers.placed_order_date', 'desc')
            ->select(
                'atlas_catalogue_orders.*',
                'atlas_dealers.full_name',
                'atlas_dealers.first_name',
                'atlas_dealers.last_name',
                'atlas_dealers.placed_order_date as order_date'
            )
            ->get();

        if (!$available_catalogue_orders) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry Catalogue Orders could not be fetched';
        }

        foreach ($available_catalogue_orders as $value) {
            $value->data = json_decode($value->data);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $available_catalogue_orders;
        $this->result->message = 'Catalogue Orders fetched Successfully';

        return response()->json($this->result);
    }

    public function fetch_dealers()
    {
        // $dealers = Dealer::join(
        //     'atlas_service_parts',
        //     'atlas_dealers.account_id',
        //     '=',
        //     'atlas_service_parts.dealer'
        // )
        //     ->join(
        //         'atlas_carded_products',
        //         'atlas_dealers.account_id',
        //         '=',
        //         'atlas_carded_products.dealer'
        //     )
        //     ->join(
        //         'atlas_catalogue_orders',
        //         'atlas_dealers.account_id',
        //         '=',
        //         'atlas_catalogue_orders.dealer'
        //     )
        //     ->select(
        //         'atlas_service_parts.completed as service_completed',
        //         'atlas_carded_products.completed as carded_completed',
        //         'atlas_catalogue_orders.completed as catalogue_completed',
        //         'atlas_dealers.*'
        //     )
        //     ->get();

        $dealers = Dealer::where('status', '1')->paginate(100);

        $dealers_items = $dealers->items();

        $service_parts = 0;

        foreach ($dealers_items as $dealer) {
            $code = $dealer->account_id;
            $id = $dealer->id;
            $check_service_parts = ServiceParts::where(
                'dealer',
                $code
            )->exists();

            $total_items = 0;
            $dealer->total_price = 0;
            $dealer->total_item = 0;
            $dealer->total_pending_item = 0;
            $dealer->total_pending_amt = 0;

            if (Cart::where('dealer', $id)->exists()) {
                $total = Cart::where('dealer', $id)
                    ->where('status', '1')
                    ->sum('price');
                $dealer->total_price = $total;

                // get the total number of items that are submitted i.e cart + CD + CP + SP
                // total cart in cart
                $total_submitted_cart_items = Cart::where('dealer', $id)
                    ->where('status', '1')
                    ->count();

                $dealer->total_item += $total_submitted_cart_items; // add it to the total items

                // total pending item in cart
                $total_cart_pending_items = Cart::where('dealer', $id)
                    ->where('status', '0')
                    ->count();

                $dealer->total_pending_item += $total_cart_pending_items;

                // total_pending item price in cart
                $total_pending_cart_price = DB::table('cart')
                    ->where('dealer', $id)
                    ->where('status', '0')
                    ->sum('price');

                $dealer->total_pending_amt += $total_pending_cart_price;

                if ($dealer->order_status == '0') {
                    $dealer->order_status = 2;
                }
                if ($dealer->order_status == '1') {
                    $dealer->order_status = 1;
                }
            }

            if ($check_service_parts) {
                $service = ServiceParts::where('dealer', $code)
                    ->get()
                    ->first();
                $dealer->service_completed = $service->completed;

                // check if the item has been submitted
                if ($service && $service->completed == '1') {
                    $data = json_decode($service->data);
                    $total_submitted_sp = count($data);
                    $dealer->total_item += $total_submitted_sp;
                }

                // check for pending sp items
                if ($service && $service->completed == '0') {
                    $data = json_decode($service->data);
                    $total_pending_sp = count($data);
                    $dealer->total_pending_item += $total_pending_sp;
                }

                $data_total = 0;

                foreach ($data as $value) {
                    $data_total += $value->total;
                }

                if ($service->completed == 1) {
                    $dealer->total_price += $data_total;
                }

                if ($service->completed == 0) {
                    $dealer->total_pending_amt += $data_total;
                }
            } else {
                $dealer->service_completed = 3;
            }

            $check_carded_parts = CardedProducts::where(
                'dealer',
                $code
            )->exists();

            if ($check_carded_parts) {
                $carded = CardedProducts::where('dealer', $code)
                    ->get()
                    ->first();
                $dealer->carded_completed = $carded->completed;

                // check if the item has been submitted
                if ($carded && $carded->completed == '1') {
                    $data = json_decode($carded->data);
                    $total_submitted_cd = count($data);
                    $dealer->total_item += $total_submitted_cd;
                }

                // check for pending sp items
                if ($carded && $carded->completed == '0') {
                    $data = json_decode($carded->data);
                    $total_pending_cd = count($data);
                    $dealer->total_pending_item += $total_pending_cd;
                }

                $data = json_decode($carded->data);

                $data_total = 0;
                foreach ($data as $value) {
                    $data_total += $value->total;
                }

                if ($carded->completed == 1) {
                    $dealer->total_price += $data_total;
                }

                if ($carded->completed == 0) {
                    $dealer->total_pending_amt += $data_total;
                }
            } else {
                $dealer->carded_completed = 3;
            }

            $check_catalogue_parts = Catalogue_Order::where(
                'dealer',
                $code
            )->exists();
            if ($check_catalogue_parts) {
                $catalogue = Catalogue_Order::where('dealer', $code)
                    ->get()
                    ->first();
                $dealer->catalogue_completed = $catalogue->completed;

                if ($catalogue && $catalogue->completed == '1') {
                    $data = json_decode($catalogue->data);
                    $total_submitted_cp = count($data);
                    $dealer->total_item += $total_submitted_cp;
                }

                // check for pending sp items
                if ($catalogue && $catalogue->completed == '0') {
                    $data = json_decode($catalogue->data);
                    $total_pending_cp = count($data);
                    $dealer->total_pending_item += $total_pending_cp;
                }

                $data = json_decode($catalogue->data);

                $data_total = 0;
                foreach ($data as $value) {
                    $data_total += $value->total;
                }

                if ($catalogue->completed == 1) {
                    $dealer->total_price += $data_total;
                }

                if ($catalogue->completed == 0) {
                    $dealer->total_pending_amt += $data_total;
                }
            } else {
                $dealer->catalogue_completed = 3;
            }
        }

        if (!$dealers) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Dealers could not be fetched';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $dealers;
        $this->result->message = 'Dealers fetched Successfully';

        return response()->json($this->result);
    }

    public function deactivate_dealers($dealer_id)
    {
        $check_dealer = Dealer::where('id', $dealer_id)
            ->where('status', '1')
            ->get();

        if (!$check_dealer && count($check_dealer) == 0) {
            // dealer allready deactivated
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry Dealers doesn\'t exist or has already been deactivated';
            return response()->json($this->result);
        }

        // deactivate the dealer
        $update_dealer = $check_dealer[0]->update(['status' => '0']);
        if ($update_dealer) {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message =
                'Dealers has been deactivated successfully';
            return response()->json($this->result);
        }
    }

    public function activate_dealers($dealer_id)
    {
        $check_dealer = Dealer::where('id', $dealer_id)
            ->where('status', '0')
            ->get();

        if (!$check_dealer && count($check_dealer) == 0) {
            // dealer allready deactivated
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry Dealers doesn\'t exist or is already active';
            return response()->json($this->result);
        }

        // deactivate the dealer

        $update_dealer = $check_dealer[0]->update(['status' => '1']);

        if ($update_dealer) {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Dealers has been activated successfully';
            return response()->json($this->result);
        }
    }

    public function send_dealer_details_email($dealer_email)
    {
        Mail::to($dealer_email)->send(new SendDealerDetailsMail());

        $this->result->status = true;
        $this->result->status_code = 200;

        $this->result->message =
            'Dealers details sent via e-mail successfully. ';
        return response()->json($this->result);
    }

    public function change_dealer_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_password' => 'requiredde',
            'account_id' => 'required',
            'confirm_new_password' => 'required|same:new',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'new_password' => $validator->errors()->get('new_password'),
                'account_id' => $validator->errors()->get('account_id'),
                'confirm_new_password' => $validator
                    ->errors()
                    ->get('confirm_new_password'),
            ];
            return response()->json($this->result);
        } else {
            $account_id = $request->input('account_id');
            $hash_password = Hash::make($request->input('new_password'));

            $dealer_details = Dealer::where('account_id', $account_id)->get();
            $dealer_details[0]->password = $hash_password;
            $update_dealer_details = $dealer_details->save();

            if (!$update_dealer_details) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Sorry Password could not be changed';
                return response()->json($this->result);
            } else {
                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->message = 'Password changed successfully';
                return response()->json($this->result);
            }
        }
    }

    public function fetch_all_products()
    {
        $fetch_all_products = Products::where('status', '1')
            ->orderBy('xref', 'asc')
            ->paginate(100);
        foreach ($fetch_all_products as $value) {
            $spec_data = $value->spec_data
                ? json_decode($value->spec_data)
                : [];
            $value->spec_data = $spec_data;
        }

        if (!$fetch_all_products) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'All Products could not be fetched';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $fetch_all_products;
        $this->result->message = 'All Products fetched successfully. ';
        return response()->json($this->result);
    }

    public function delete_product($product_id)
    {
        $check_Product = Products::where('id', $product_id)
            ->where('status', '1')
            ->get();

        if (!$check_Product || count($check_Product) == 0) {
            // Product allready deactivated
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry Products doesn\'t exist or has already been deactivated';
            return response()->json($this->result);
        }

        // deactivate the Product
        $check_Product[0]->status = 0;
        $update_Product = $check_Product[0]->save();

        if ($update_Product) {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Products has been deleted successfully';
            return response()->json($this->result);
        }
    }

    public function restore_product($product_id)
    {
        $check_Product = Products::where('id', $product_id)
            ->where('status', '0')
            ->get();

        if (!$check_Product || count($check_Product) == 0) {
            // Product allready deactivated
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry Products doesn\'t exist or is currently active';
            return response()->json($this->result);
        }

        // deactivate the Product
        $check_Product[0]->status = 1;
        $update_Product = $check_Product[0]->save();

        if ($update_Product) {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Products has been restored successfully';
            return response()->json($this->result);
        }
    }

    public function update_product(Request $request)
    {
        // $validator = Validator::make( $request->all(), [
        //     'atlasId' => 'required',
        //     'desc' => 'required',
        //     'productImgUrl' => 'required',
        //     'vendorImgUrl' => 'required',
        //     'vendorName' => 'required',
        //     'um' => 'required',
        //     'booking' => 'required',
        //     'category' => 'required'
        // ] );

        // if ( $validator->fails() ) {
        //     $this->result->status_code = 422;
        //     $this->result->message = [
        //         'atlasId' => $validator->errors()->get( 'atlas id' ),
        //         'desc' => $validator->errors()->get( 'desc' ),
        //         'fullDesc' => $validator->errors()->get( 'full desc' ),
        //         'productImgUrl' => $validator->errors()->get( 'image' ),
        //         'vendorImgUrl' => $validator->errors()->get( 'Vendor Image Url' ),
        //         'vendorName' => $validator->errors()->get( 'Vendor Name' ),
        //         'um' => $validator->errors()->get( 'um' ),
        //         'booking' => $validator->errors()->get( 'booking' ),
        //         'category' => $validator->errors()->get( 'category' ),

        // ];
        //     return response()->json( $this->result );
        // } else {

        $atlas_id = $request->input('atlasId');
        $product = Products::where('atlas_id', $atlas_id)->first();

        if (!$product) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'No Product found with this atlas id';
            return response()->json($this->result);
        }

        $update_product = $product->update([
            //'atlas_id' => $request->input( 'atlasId' ),
            'description' => $request->input('desc'),
            'short_note' => $request->input('shortNote'),
            'short_note_url' => $request->input('shortNoteUrl'),

            // 'img' => $request->input( 'productImgUrl' ),
            'full_desc' => $request->input('fullDesc'),
            // 'vendor_logo' => $request->input( 'vendorImgUrl' ),
            'vendor_name' => $request->input('vendorName'),
            'um' => $request->input('um'),
            'booking' => $request->input('booking') ?? null,
            'grouping' => $request->input('grouping'),
            'category' => $request->input('category') ?? null,
            'spec_data' => json_encode($request->input('specData')),
        ]);

        if (!$update_product) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry Product could not be updated. Try again later';
            return response()->json($this->result);
        } else {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Product updated successfully';
            return response()->json($this->result);
        }
        //}
    }

    public function update_product_img(Request $request)
    {
        $atlas_id = $request->input('atlasId');
        $product = Products::where('atlas_id', $atlas_id)->first();

        if (!$product) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'No Product found with this atlas id';
            return response()->json($this->result);
        }

        $update_pro_img = $product->update([
            'img' => $request->input('img'),
        ]);

        if (!$update_pro_img) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry Product could not be updated. Try again later';
            return response()->json($this->result);
        } else {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Product Image updated successfully';
            return response()->json($this->result);
        }
    }

    public function update_vendor_logo(Request $request)
    {
        $atlas_id = $request->input('atlasId');
        $product = Products::where('atlas_id', $atlas_id)->first();

        if (!$product) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'No Product found with this atlas id';
            return response()->json($this->result);
        }

        $update_vendor_logo = $product->update([
            'vendor_logo' => $request->input('img'),
        ]);

        if (!$update_vendor_logo) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry Vendor Image Logo could not be updated. Try again later';
            return response()->json($this->result);
        } else {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Vendor Logo Image updated successfully';
            return response()->json($this->result);
        }
    }

    public function change_product_status($id)
    {
        //$atlas_id = $request->input( 'atlasId' );
        $product = Products::where('atlas_id', $id)->first();

        $status = $product->status;
        $new_status = '0';

        if ($status == '0') {
            $new_status = '1';
        } else {
            $new_status = '0';
        }

        $product_status = Products::where('atlas_id', $id)->update([
            'status' => $new_status,
        ]);

        if (!$product_status) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry, Product Status could not be updated. Try again later';
            return response()->json($this->result);
        } else {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Product has been updated successfully';
            return response()->json($this->result);
        }
    }

    public function sort_dealer_by_location($location)
    {
        $dealer = Dealer::where('location', 'LIKE', '%' . $location . '%')
            ->orderby('id', 'ASC')
            ->get();

        if (!$dealer) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'No dealer found in this location';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $dealer;
        $this->result->message = 'Dealers found successfully';
        return response()->json($this->result);
    }

    public function old_edit_catalogue1(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
            'dealer' => 'required',
            'data' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'order_id' => $validator->errors()->get('order_id'),
                'dealer' => $validator->errors()->get('dealer'),
                'data' => $validator->errors()->get('data'),
            ];
            return response()->json($this->result);
        } else {
            // update the catalogue order
            $order_id = $request->input('order_id');
            $dealer = $request->input('dealer');
            $data = $request->input('data');

            $fetch_catalogue_order = Catalogue_Order::where(
                'id',
                $order_id
            )->get();

            if (!$fetch_catalogue_order || count($fetch_catalogue_order) == 0) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry Catalogue order could not be fetched';
                return response()->json($this->result);
            }

            $fetch_catalogue_order[0]->dealer = $dealer;

            $fetch_catalogue_order[0]->data = $data;

            $update_catalogue_order = $fetch_catalogue_order[0]->save();

            if (!$update_catalogue_order) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry Catalogue order could not be updated';
                return response()->json($this->result);
            } else {
                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->message = 'Catalogue updated successfully';
                return response()->json($this->result);
            }
        }
    }

    public function admin_edit_service_parts_order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'atlas_id' => 'required',
            'dealer' => 'required',
            'description' => 'required',
            'quantity' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'id' => $validator->errors()->get('id'),
                'atlas_id' => $validator->errors()->get('atlas_id'),
                'dealer' => $validator->errors()->get('dealer'),
                'quantity' => $validator->errors()->get('quantity'),
                'description' => $validator->errors()->get('description'),
            ];
            return response()->json($this->result);
        } else {
            $id = $request->input('id');
            $atlas_id = $request->input('atlas_id');
            $dealer = $request->input('dealer');
            $quantity = $request->input('quantity');
            $desc = $request->input('description');

            if (ExtraProducts::where('item_code', $atlas_id)->exists()) {
                $extra_data = ExtraProducts::where('item_code', $atlas_id)
                    ->get()
                    ->first();
                $inital_price = $extra_data->price;
                $new_price = $inital_price * $quantity;

                if (ServiceParts::where('dealer', $dealer)->exists()) {
                    $service_parts_order = ServiceParts::where(
                        'dealer',
                        $dealer
                    )
                        ->get()
                        ->first();
                    $data = json_decode($service_parts_order->data);
                    $status = count($data) > 0 ? true : false;
                    if ($status) {
                        $new_items = [];

                        foreach ($data as $value) {
                            $atlas_id_old = $value->atlasId;
                            if ($atlas_id_old != $atlas_id) {
                                array_push($new_items, (array) $value);
                            }
                        }

                        $update_quantity = array_push($new_items, [
                            'qty' => $quantity,
                            'atlasId' => $atlas_id,
                            'price' => $inital_price,
                            'total' => $new_price,
                            'description' => $desc,
                        ]);
                    }

                    $data_encode = json_encode($new_items);

                    $update_carded_data = ServiceParts::where(
                        'dealer',
                        $dealer
                    )->update(['data' => $data_encode]);

                    if (!$update_carded_data) {
                        $this->result->status = false;
                        $this->result->status_code = 422;
                        $this->result->message =
                            'Service Parts Order could not be updated';
                        return response()->json($this->result);
                    }

                    $this->result->status = true;
                    $this->result->status_code = 200;
                    $this->result->message =
                        'Service Parts Order updated successfully';
                    return response()->json($this->result);
                }
            } else {
                $this->result->status_code = 404;
                $this->result->message = 'Item not found';
                return response()->json($this->result);
            }
        }
    }

    public function admin_edit_carded_order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'atlas_id' => 'required',
            'dealer' => 'required',
            'quantity' => 'required|integer',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'id' => $validator->errors()->get('id'),
                'atlas_id' => $validator->errors()->get('atlas_id'),
                'dealer' => $validator->errors()->get('dealer'),
                'quantity' => $validator->errors()->get('quantity'),
                'description' => $validator->errors()->get('description'),
            ];
            return response()->json($this->result);
        } else {
            $id = $request->input('id');
            $atlas_id = $request->input('atlas_id');
            $dealer = $request->input('dealer');
            $quantity = $request->input('quantity');
            $desc = $request->input('description');

            if (ExtraProducts::where('item_code', $atlas_id)->exists()) {
                $extra_data = ExtraProducts::where('item_code', $atlas_id)
                    ->get()
                    ->first();
                $inital_price = $extra_data->price;
                $new_price = $inital_price * $quantity;

                if (CardedProducts::where('dealer', $dealer)->exists()) {
                    $carded_order = CardedProducts::where('dealer', $dealer)
                        ->get()
                        ->first();
                    $data = json_decode($carded_order->data);
                    $status = count($data) > 0 ? true : false;
                    if ($status) {
                        $new_items = [];

                        foreach ($data as $value) {
                            $atlas_id_old = $value->atlasId;
                            if ($atlas_id_old != $atlas_id) {
                                array_push($new_items, (array) $value);
                            }
                        }

                        $update_quantity = array_push($new_items, [
                            'qty' => $quantity,
                            'atlasId' => $atlas_id,
                            'price' => $inital_price,
                            'total' => $new_price,
                            'description' => $desc,
                        ]);
                    }

                    $data_encode = json_encode($new_items);

                    $update_carded_data = CardedProducts::where(
                        'dealer',
                        $dealer
                    )->update(['data' => $data_encode]);

                    if (!$update_carded_data) {
                        $this->result->status = false;
                        $this->result->status_code = 422;
                        $this->result->message =
                            'Carded Order could not be updated';
                        return response()->json($this->result);
                    }

                    $this->result->status = true;
                    $this->result->status_code = 200;
                    $this->result->message =
                        'Carded Order updated successfully';
                    return response()->json($this->result);
                }
            } else {
                $this->result->status_code = 404;
                $this->result->message = 'Item not found';
                return response()->json($this->result);
            }
        }
    }

    public function admin_edit_catalogue_order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'atlas_id' => 'required',
            'dealer' => 'required',
            'quantity' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'id' => $validator->errors()->get('id'),
                'atlas_id' => $validator->errors()->get('atlas_id'),
                'dealer' => $validator->errors()->get('dealer'),
                'quantity' => $validator->errors()->get('quantity'),
                'description' => $validator->errors()->get('description'),
            ];
            return response()->json($this->result);
        } else {
            $id = $request->input('id');
            $atlas_id = $request->input('atlas_id');
            $dealer = $request->input('dealer');
            $quantity = $request->input('quantity');
            $desc = $request->input('description');

            if (ExtraProducts::where('item_code', $atlas_id)->exists()) {
                $extra_data = ExtraProducts::where('item_code', $atlas_id)
                    ->get()
                    ->first();
                $inital_price = $extra_data->price;
                $new_price = $inital_price * $quantity;

                if (Catalogue_Order::where('dealer', $dealer)->exists()) {
                    $catalogue_order = Catalogue_Order::where('dealer', $dealer)
                        ->get()
                        ->first();
                    $data = json_decode($catalogue_order->data);
                    $status = count($data) > 0 ? true : false;
                    if ($status) {
                        $new_items = [];

                        foreach ($data as $value) {
                            $atlas_id_old = $value->atlasId;
                            if ($atlas_id_old != $atlas_id) {
                                array_push($new_items, (array) $value);
                            }
                        }

                        $update_quantity = array_push($new_items, [
                            'qty' => $quantity,
                            'atlasId' => $atlas_id,
                            'price' => $inital_price,
                            'total' => $new_price,
                            'description' => $desc,
                        ]);
                    }

                    $data_encode = json_encode($new_items);

                    $update_catalogue_data = Catalogue_Order::where(
                        'dealer',
                        $dealer
                    )->update(['data' => $data_encode]);

                    if (!$update_catalogue_data) {
                        $this->result->status = false;
                        $this->result->status_code = 422;
                        $this->result->message =
                            'Catalogue Order could not be updated';
                        return response()->json($this->result);
                    }

                    $this->result->status = true;
                    $this->result->status_code = 200;
                    $this->result->message =
                        'Catalogue Order updated successfully';
                    return response()->json($this->result);
                }
            } else {
                $this->result->status_code = 404;
                $this->result->message = 'Item not found';
                return response()->json($this->result);
            }
        }
    }

    public function edit_catalogue_order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'atlas_id' => 'required',
            'dealer' => 'required',
            'quantity' => 'required|integer',
            'price' => 'required',
            'total' => 'required',
            'description' => 'required',
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
                'description' => $validator->errors()->get('description'),
            ];
            return response()->json($this->result);
        } else {
            $id = $request->input('id');
            $atlas_id = $request->input('atlas_id');
            $dealer = $request->input('dealer');
            $quantity = $request->input('quantity');
            $price = $request->input('price');
            $total = $request->input('total');
            $desc = $request->input('description');

            $no_of_catalogue_order = Catalogue_Order::where('id', $id)->get();

            if (!$no_of_catalogue_order || count($no_of_catalogue_order) == 0) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Catalogue Order not found';
                return response()->json($this->result);
            }

            if (count($no_of_catalogue_order) > 0) {
                $data = json_decode($no_of_catalogue_order[0]['data']);

                $new_items = [];

                if (count($data) > 0) {
                    foreach ($data as $key => $value) {
                        $atlas_id_old = $value->atlasId;

                        if ($atlas_id_old != $atlas_id) {
                            // same atlas_id
                            array_push($new_items, (array) $value);
                        }
                    }

                    // dd( $new_items );

                    $update_quantity = array_push($new_items, [
                        'qty' => $quantity,
                        'atlasId' => $atlas_id,
                        'price' => $price,
                        'total' => $total,
                        'description' => $desc,
                    ]);

                    // dd( $new_items );
                    $no_of_catalogue_order[0]->data = $new_items;

                    $update_catalogue_order = $no_of_catalogue_order[0]->save();

                    if (!$update_catalogue_order) {
                        $this->result->status = false;
                        $this->result->status_code = 422;
                        $this->result->message =
                            'Catalogue Order could not be updated';
                        return response()->json($this->result);
                    }

                    $this->result->status = true;
                    $this->result->status_code = 200;
                    $this->result->message =
                        'Catalogue Order updated successfully';
                    return response()->json($this->result);
                }
            }
        }
    }

    public function fetch_dealer_by_account_id($account_id)
    {
        $dealer = Dealer::where('account_id', $account_id)
            ->orderby('id', 'ASC')
            ->get();

        if (!$dealer) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'No dealer found with this account id or has been deactivated';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $dealer;
        $this->result->message = 'Dealer found successfully';
        return response()->json($this->result);
    }

    public function delete_catalogue_order_old($dealer)
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

        // deactivate the catalogue_order
        $check_catalogue_order[0]->status = 0;
        $check_catalogue_order[0]->deleted_at = Carbon::now();
        $update_catalogue_order = $check_catalogue_order[0]->save();

        if ($update_catalogue_order) {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message =
                'Catalogue order  has been deleted successfully';
            return response()->json($this->result);
        }
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

    // delete carded products
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

    // delete service parts
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

    public function no_of_dealers()
    {
        $dealers = Dealer::all();

        if (!$dealers) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry we could not fetch all the dealers';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = count($dealers);
        $this->result->message = 'Number of dealers fetched successfully';
        return response()->json($this->result);
    }

    public function no_of_products()
    {
        $products = Products::all();

        if (!$products) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry we could not the number of products';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = count($products);
        $this->result->message = 'Number of products fetched successfully';
        return response()->json($this->result);
    }

    public function no_of_catalogue_orders()
    {
        $catalogue_orders = Catalogue_order::all();

        if (!$catalogue_orders) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry we could not fetch all the catalogue orders';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = count($catalogue_orders);
        $this->result->message =
            'Number of Catalogue Orders fetched successfully';
        return response()->json($this->result);
    }

    public function get_all_category()
    {
        $categories = Category::all();

        if (!$categories) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry we could not fetch all the categories';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $categories;

        $this->result->message = 'All Categories fetched successfully';
        return response()->json($this->result);
    }

    public function check_if_its_new($created_at, $no_of_days)
    {
        $format_created_at = Carbon::parse($created_at);

        $now = Carbon::now();

        $length = $format_created_at->diffInDays($now);

        if ($length <= $no_of_days) {
            return true;
        } else {
            return false;
        }
    }

    public function admin_dashboard()
    {
        $dealers = Dealer::all();
        $products = Products::all();
        $total_amount = 0;
        $total_not_submitted_in_cart_amt = 0;

        $total_amount = DB::table('cart')
            ->where('status', '1')
            ->sum('price');

        $fetch_account_ids = $dealers->pluck('account_id')->toArray();

        $all_dealer_ids_order_status = DB::table('atlas_dealers')
            ->wherein('account_id', $fetch_account_ids)
            // ->where('order_status', 1)
            ->pluck('account_id')
            ->toArray();

        ////// Catalogue orders Submitted
        $all_catalogue_orders = Catalogue_Order::wherein(
            'dealer',
            $fetch_account_ids
        )
            ->where('completed', '1')
            ->get();

        $total_catalogue_submitted = 0;

        if (!empty($all_catalogue_orders)) {
            foreach ($all_catalogue_orders as $catalogue_data) {
                $data = json_decode($catalogue_data->data);
                foreach ($data as $value) {
                    if (isset($value->total)) {
                        $total = $value->total;
                        $total_amount += $total;
                        $total_catalogue_submitted += $total;
                    }
                }
            }
        }

        /////////// Service Parts orders submitted
        $all_service_parts = ServiceParts::wherein('dealer', $fetch_account_ids)
            ->where('completed', '1')
            ->get();

        $total_service_submitted = 0;

        if (!empty($all_service_parts)) {
            foreach ($all_service_parts as $service_data) {
                $data = json_decode($service_data->data);
                foreach ($data as $value) {
                    if (isset($value->total)) {
                        $total = $value->total;
                        $total_amount += $total;
                        $total_service_submitted += $total;
                    }
                }
            }
        }

        ////////// Carded Orders submitted
        $all_carded_products = CardedProducts::wherein(
            'dealer',
            $fetch_account_ids
        )
            ->where('completed', '1')
            ->get();

        $total_carded_submitted = 0;

        if (!empty($all_carded_products)) {
            foreach ($all_carded_products as $carded_data) {
                $data = json_decode($carded_data->data);
                foreach ($data as $value) {
                    if (isset($value->total)) {
                        $total = $value->total;
                        $total_amount += $total;
                        $total_carded_submitted += $total;
                    }
                }
            }
        }

        $fetch_dealer_ids = $dealers->pluck('id')->toArray();

        $all_dealer_ids_order_status = DB::table('atlas_dealers')
            ->wherein('id', $fetch_dealer_ids)
            ->pluck('id')
            ->toArray();

        $total_not_submitted_in_cart = DB::table('cart')
            ->where('status', '0')
            ->count();

        $total_not_submitted_in_cart_amt = DB::table('cart')
            ->where('status', '0')
            ->sum('price');

        /////// Catalogue Orders not submitted
        $all_catalogue_not_submitted_orders = Catalogue_Order::wherein(
            'dealer',
            $fetch_account_ids
        )
            ->where('completed', '0')
            ->get();

        if (!empty($all_catalogue_not_submitted_orders)) {
            foreach ($all_catalogue_not_submitted_orders as $catalogue_data) {
                $data = json_decode($catalogue_data->data);
                foreach ($data as $value) {
                    $total_not_submitted_in_cart =
                        $total_not_submitted_in_cart + 1;

                    if (isset($value->total)) {
                        $total = $value->total;
                        $total_not_submitted_in_cart_amt += $total;
                    }
                }
            }
        }

        ////// Service Parts orders not submitted
        $all_service_not_submitted_parts = ServiceParts::wherein(
            'dealer',
            $fetch_account_ids
        )
            ->where('completed', '0')
            ->get();

        if (!empty($all_service_not_submitted_parts)) {
            foreach ($all_service_not_submitted_parts as $service_data) {
                $data = json_decode($service_data->data);
                foreach ($data as $value) {
                    $total_not_submitted_in_cart =
                        $total_not_submitted_in_cart + 1;

                    if (isset($value->total)) {
                        $total = $value->total;
                        $total_not_submitted_in_cart_amt += $total;
                    }
                }
            }
        }

        //////// Carded orders not submitted
        $all_carded_not_submitted_products = CardedProducts::wherein(
            'dealer',
            $fetch_account_ids
        )
            ->where('completed', '0')
            ->get();

        if (!empty($all_carded_not_submitted_products)) {
            foreach ($all_carded_not_submitted_products as $carded_data) {
                $data = json_decode($carded_data->data);
                foreach ($data as $value) {
                    $total_not_submitted_in_cart =
                        $total_not_submitted_in_cart + 1;
                    if (isset($value->total)) {
                        $total = $value->total;
                        $total_not_submitted_in_cart_amt += $total;
                    }
                }
            }
        }

        $total_orders = Dealer::where('order_status', '1')->count();

        $cart_orders_ch = Cart::all();
        $dealer_arr = [];
        foreach ($cart_orders_ch as $val) {
            $dealer = $val->dealer;
            if (!in_array($dealer, $dealer_arr)) {
                array_push($dealer_arr, $dealer);
            }
        }

        $orders = count($dealer_arr);

        $dealer_with_orders = Dealer::orderby('id', 'desc')
            ->get()
            ->take(5);

        $all_Dealers_with_orders = DB::table('atlas_dealers')
            // ->where('atlas_dealers.order_status', 1)
            ->join('cart', 'atlas_dealers.id', '=', 'cart.dealer')
            ->select('atlas_dealers.account_id', 'cart.price')
            ->sum('cart.price');

        $get_recent_order_Details = array_map(function ($record) {
            // $record = ( object ) $record;
            $dealer_id = $record['id'];
            $dealer_account_id = $record['account_id'];
            $dealer_name = $record['first_name'] . ' ' . $record['last_name'];
            $no_of_items = Cart::where('dealer', $dealer_id)->count();
            $sum_total = Cart::where('dealer', $dealer_id)->sum('price');
            $dealer_date_updated = $record['updated_at'];
            return [
                'dealer_id' => $dealer_id,
                'dealer_account_id' => $dealer_account_id,
                'dealer_name' => $dealer_name,
                'no_of_items' => $no_of_items,
                'total_amount' => $sum_total,
                'date' => $dealer_date_updated,
            ];
        }, json_decode($dealer_with_orders, true));

        // return $get_recent_order_Details;

        $new_products = [];

        foreach ($products as $product) {
            $is_new =
                $this->check_if_its_new($product['created_at'], 10) == true
                    ? true
                    : false;

            $spec_data = $product->spec_data
                ? json_decode($product->spec_data)
                : [];

            $product->spec_data = $spec_data;

            if ($is_new) {
                array_push($new_products, $product);
            }
        }

        $submitted_dealers = DB::table('atlas_dealers')
            ->select(
                'id',
                'order_status',
                'first_name',
                'last_name',
                'full_name',
                'placed_order_date',
                'account_id'
            )
            ->where('order_status', '1')
            ->orderBy('placed_order_date', 'desc')
            ->take(5)
            ->get()
            ->toArray();

        if (count($submitted_dealers) > 0) {
            $recent_order = array_map(function ($data) {
                $dealer_id = $data->id;
                $account_id = $data->account_id;
                $dealer_name = $data->full_name;
                $order_date = $data->placed_order_date;

                $total_amount = 0;

                if (
                    Catalogue_Order::query()
                        ->where('dealer', $account_id)
                        ->where('completed', '1')
                        ->exists()
                ) {
                    ////// Catalogue orders Submitted
                    $catalogue_orders = Catalogue_Order::query()
                        ->where('dealer', $account_id)
                        ->where('completed', '1')
                        ->get()
                        ->first();

                    foreach ($catalogue_orders as $catalogue_data) {
                        $data = $catalogue_data->data;
                        foreach ($data as $value) {
                            if (isset($value->total)) {
                                $total = $value->total;
                                $total_amount += $total;
                            }
                        }
                    }
                }

                /////////// Service Parts orders submitted
                // $service_parts = ServiceParts::query()
                //     ->where('dealer', $account_id)
                //     ->where('completed', '1')
                //     ->get()
                //     ->first();

                // if (
                //     ServiceParts::query()
                //         ->where('dealer', $account_id)
                //         ->where('completed', '1')
                //         ->exists()
                // ) {
                //     foreach ($service_parts as $service_data) {
                //         $data = json_decode($service_data->data);
                //         foreach ($data as $value) {
                //             if (isset($value->total)) {
                //                 $total = $value->total;
                //                 $total_amount += $total;
                //             }
                //         }
                //     }
                // }

                ////////// Carded Orders submitted
                // $carded_products = CardedProducts::query()
                //     ->where('dealer', $account_id)
                //     ->where('completed', '1')
                //     ->get()
                //     ->first();

                // if (
                //     CardedProducts::query()
                //         ->where('dealer', $account_id)
                //         ->where('completed', '1')
                //         ->exists()
                // ) {
                //     foreach ($carded_products as $carded_data) {
                //         $data = json_decode($carded_data->data);
                //         foreach ($data as $value) {
                //             if (isset($value->total)) {
                //                 $total = $value->total;
                //                 $total_amount += $total;
                //             }
                //         }
                //     }
                // }

                $cart_total = DB::table('cart')
                    ->where('dealer', $dealer_id)
                    ->sum('price');

                $over_all_total = $cart_total + $total_amount;

                return [
                    'id' => $dealer_id,
                    'account_id' => $account_id,
                    'dealer_name' => $dealer_name,
                    'total_item' => Cart::where('dealer', $dealer_id)->count(),
                    'total_amt' => $over_all_total,
                    'order_date' => $order_date,
                ];
            }, $submitted_dealers);
        } else {
            $recent_order = [];
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data->total_dealers = $dealers ? count($dealers) : 0;
        $this->result->data->total_catalogue_orders = $all_catalogue_orders
            ? count($all_catalogue_orders)
            : 0;
        $this->result->data->total_products = $products ? count($products) : 0;
        $this->result->data->total_carded_products = $all_carded_products
            ? count($all_carded_products)
            : 0;
        $this->result->data->total_service_parts = $all_service_parts
            ? count($all_service_parts)
            : 0;

        $this->result->data->total_orders = $total_orders;

        $this->result->data->recent_orders = $recent_order;
        $this->result->data->new_products = $new_products
            ? count($new_products)
            : 0;
        $this->result->data->total_amount = $total_amount;

        $this->result->data->total_not_submitted_in_cart = $total_not_submitted_in_cart;

        $this->result->data->total_not_submitted_in_cart_amt = $total_not_submitted_in_cart_amt;

        $this->result->data->total_amount4 = 'tests';

        $this->result->data->total_carded_submitted = $total_carded_submitted;
        $this->result->data->total_service_submitted = $total_service_submitted;
        $this->result->data->total_catalogue_submitted = $total_catalogue_submitted;

        $this->result->message = 'Dashboard details fetched successfully';
        return response()->json($this->result);
    }

    public function fetch_all_category()
    {
        $categories = Category::orderby('id', 'ASC')->get();

        if (!$categories) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry we could not fetch all the categories';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $categories;

        $this->result->message = 'All Categories fetched successfully';
        return response()->json($this->result);
    }

    public function fetch_all_promo_category()
    {
        $categories = PromotionalCategory::where('status', '1')
            ->orderby('id', 'ASC')
            ->get();

        if (!$categories) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry we could not fetch all the categories';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $categories;

        $this->result->message = 'All Categories fetched successfully';
        return response()->json($this->result);
    }

    public function get_products_by_category($category)
    {
        $products = Products::where('category', $category)
            ->orderBy('xref', 'asc')
            ->paginate(100);

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

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $products;
        $this->result->message =
            'Products in this category successfully fetched';
        return response()->json($this->result);
    }

    public function recent_order_dashboard()
    {
        $data = DB::table('atlas_user_cart')
            ->select(
                'atlas_user_cart.cart_data',
                'atlas_user_cart.user_id',
                'atlas_user_cart.id',
                'atlas_dealers.first_name',
                'atlas_dealers.last_name'
            )
            ->join(
                'atlas_dealers',
                'atlas_user_cart.user_id',
                '=',
                'atlas_dealers.id'
            )
            ->where('atlas_user_cart.status', '1')
            ->orderBy('atlas_user_cart.id', 'DESC')
            ->get()
            ->take(5);

        if ($data) {
            $order = $data->items();
            foreach ($order as $item) {
                $item->cart_data = json_decode($item->cart_data);
            }
        } else {
            $order = [];
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $order;
        $this->result->message = 'All Dealers Orders';
        return response()->json($this->result);
    }

    public function admin_view_dealer_order($id)
    {
        $data = DB::table('cart')
            ->select(
                'atlas_dealers.account_id',
                'cart.id',
                'atlas_dealers.first_name',
                'atlas_dealers.last_name',
                'cart.*'
            )
            ->join('atlas_dealers', 'cart.dealer', '=', 'atlas_dealers.id')
            ->where('cart.dealer', $id)
            ->get();

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

        if ($data) {
            foreach ($data as $value) {
                $spec_data = $value->spec_data
                    ? json_decode($value->spec_data)
                    : [];
                $value->spec_data = $spec_data;
            }
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data->cart = $data;
        $this->result->data->service_part = $service_products;
        $this->result->data->catalogue = $catalogue_order;
        $this->result->data->carded = $carded_products;
        $this->result->data->dealer = $dealer_d;

        $this->result->message = 'View Dealer Orders';
        return response()->json($this->result);
    }

    public function view_order($id)
    {
        // $data =  DB::table( 'atlas_user_cart' )
        // ->select( 'atlas_user_cart.cart_data', 'atlas_user_cart.user_id', 'atlas_user_cart.id', 'atlas_dealers.first_name', 'atlas_dealers.last_name' )
        // ->join( 'atlas_dealers', 'atlas_user_cart.user_id', '=', 'atlas_dealers.id' )
        // ->where( 'atlas_user_cart.id', $id )
        // ->get();

        $data = DB::table('cart')
            ->select(
                'atlas_dealers.account_id',
                'cart.id',
                'atlas_dealers.first_name',
                'atlas_dealers.last_name',
                'cart.*'
            )
            ->join('atlas_dealers', 'cart.dealer', '=', 'atlas_dealers.id')
            ->where('cart.dealer', $id)
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
            $this->result->message = 'Sorry we could not fetch all the Orders';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $data;
        $this->result->message = 'View Dealer Orders';
        return response()->json($this->result);
    }

    public function view_order_by_dealer_id($dealer_id)
    {
        $data = DB::table('atlas_dealers')
            ->select(
                'atlas_user_cart.cart_data',
                'atlas_user_cart.user_id',
                'atlas_user_cart.id',
                'atlas_dealers.first_name',
                'atlas_dealers.last_name'
            )
            ->join(
                'atlas_user_cart',
                'atlas_dealers.id',
                '=',
                'atlas_user_cart.user_id'
            )
            ->where('atlas_dealers.account_id', $dealer_id)
            ->get();

        if ($data) {
            //$order = $data->items();
            foreach ($data as $item) {
                $item->cart_data = json_decode($item->cart_data);
            }
        } else {
            $data = [];
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $data;
        $this->result->message = 'View Dealer Orders';
        return response()->json($this->result);
    }

    public function fetch_all_dealers_with_orders()
    {
        $dealers = Dealer::where('order_status', '1')
            ->get()
            ->toArray();

        if (!$dealers && count($dealers) == 0) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry we could not fetch all the dealers with orders';
            return response()->json($this->result);
        }

        $format_data = array_map(function ($record) {
            $record = (object) $record;

            $dealer_orders = Cart::where('dealer', $record->id)->count();
            $total_amount = Cart::where('dealer', $record->id)->sum('price');

            return [
                'dealer_id' => $record->id,
                'dealer_account_id' => $record->account_id,
                'Dealer_name' => $record->full_name,
                'Total_items' => $dealer_orders,
                'Total_amount' => $total_amount,
                'order_date' => $record->updated_at,
            ];
        }, $dealers);

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $format_data;
        $this->result->message = 'All Dealers with Orders fetched successfully';
        return response()->json($this->result);
    }

    public function fetch_all_orders()
    {
        $submitted_dealers = DB::table('atlas_dealers')
            ->select(
                'id',
                'order_status',
                'first_name',
                'last_name',
                'full_name',
                'placed_order_date',
                'account_id'
            )
            ////   ->where('order_status', '1')
            ->orderBy('placed_order_date', 'DESC')
            ->paginate(100);

        $submitted_orders_item = $submitted_dealers->items();

        $all = array_map(function ($data) {
            $dealer_id = $data->id;
            $dealer_name = $data->full_name;
            $order_date = $data->placed_order_date;
            $account_id = $data->account_id;

            return [
                'id' => $dealer_id,
                'account_id' => $account_id,
                'dealer_name' => $dealer_name,
                'total_item' => Cart::where('dealer', $dealer_id)
                    ->where('status', '1')
                    ->count(),
                'total_pending_item' => Cart::where('dealer', $dealer_id)
                    ->where('status', '0')
                    ->count(),

                'total_amt' => DB::table('cart')
                    ->where('dealer', $dealer_id)
                    ->where('status', '1')
                    ->sum('price'),

                'total_pending_amt' => DB::table('cart')
                    ->where('dealer', $dealer_id)
                    ->where('status', '0')
                    ->sum('price'),

                'order_date' => $order_date,
            ];
        }, $submitted_orders_item);

        $res_data = [
            'data' => $all,
            'per_page' => $submitted_dealers->perPage(),
            'total' => $submitted_dealers->total(),
        ];

        // $res_data->data = $all;
        // $res_data->per_page = $submitted_dealers->perPage();
        // $res_data->total = $submitted_dealers->total();

        // $res_data->per_page = $submitted_dealers->perPage();

        //   $submitted_dealers->items() = $all;

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $res_data;
        $this->result->message = 'All Dealers Orders';
        return response()->json($this->result);
    }

    public function get_dealer_log()
    {
        $data = DB::table('atlas_login_log')
            ->select(
                'atlas_login_log.current_location as country',
                'atlas_login_log.dealer',
                'atlas_login_log.ip_address',
                'atlas_login_log.browser',
                'atlas_login_log.updated_at',
                'atlas_login_log.location',
                'atlas_dealers.last_name',
                'atlas_dealers.first_name'
            )
            ->join(
                'atlas_dealers',
                'atlas_login_log.dealer',
                '=',
                'atlas_dealers.id'
            )
            ->orderBy('atlas_login_log.updated_at', 'DESC')
            ->paginate(100);

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $data;
        $this->result->message = 'All Dealers Logged';
        return response()->json($this->result);
    }

    public function fetch_all_carded_products()
    {
        // $all_dealer_ids = DB::table( 'atlas_dealers' )->where( 'order_status', 1 )->pluck( 'account_id' )->toArray();

        // return $all_dealer_ids;

        // $carded_products = DB::table( 'atlas_carded_products' )->wherein( 'dealer', $all_dealer_ids )->get();

        // $carded_products = CardedProducts::orderby( 'id', 'ASC' )->get();

        // return $carded_products;

        // $new_data_array = [];

        // foreach ( $carded_products as $value ) {
        //     $dealer_id = $value->dealer;

        //     $fetch_dealer_details = Dealer::where( 'account_id', $dealer_id )->get();

        //     if ( $fetch_dealer_details && count( $fetch_dealer_details ) > 0 ) {
        //         array_push( $new_data_array, $fetch_dealer_details );

        //         $dealer_name = $fetch_dealer_details[ 0 ][ 'first_name' ] . ' ' . $fetch_dealer_details[ 0 ][ 'last_name' ];
        //         $order_date = $fetch_dealer_details[ 0 ][ 'placed_order_date' ];
        //         $value->dealer_name = $dealer_name;
        //         $value->order_date = $order_date;
        //     } else {
        //         $value->dealer_name = null;
        //     }

        //     $data = ( $value->data ) ? json_decode( $value->data ) : [];
        //     $value->data = $data;
        // }

        $carded_products = DB::table('atlas_carded_products')
            ->join(
                'atlas_dealers',
                'atlas_carded_products.dealer',
                '=',
                'atlas_dealers.account_id'
            )
            ->where('atlas_dealers.order_status', 1)
            ->orderby('atlas_dealers.placed_order_date', 'desc')
            ->select(
                'atlas_carded_products.*',
                'atlas_dealers.full_name',
                'atlas_dealers.first_name',
                'atlas_dealers.last_name',
                'atlas_dealers.placed_order_date as order_date'
            )
            ->get();

        if (!$carded_products) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry we could not fetch all the carded products';
            return response()->json($this->result);
        }

        foreach ($carded_products as $value) {
            $value->data = $value->data ? json_decode($value->data) : [];

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

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $carded_products;

        $this->result->message = 'All carded products fetched successfully';
        return response()->json($this->result);
    }

    public function fetch_carded_products($dealer_id)
    {
        $all_Carded_products = CardedProducts::where(
            'dealer',
            $dealer_id
        )->get();

        if (!$all_Carded_products) {
            $this->result->data = false;
            $this->result->status_code = 200;
            $this->result->message = 'Carded Product not available';
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $data;
        $this->result->message = 'All Carded Products fetched successfully';
        return response()->json($this->result);
    }

    // edit order logic comes here

    // public function is_same_group( $item_one, $item_two ) {

    // }

    // public function is_same_vendor( $item ) {

    // }

    public function check_product_type($atlas_id, $quantity, $dealer_id)
    {
        $product_details = Products::where('atlas_id', $atlas_id)->first();

        if (!$product_details) {
            $this->result->data = false;
            $this->result->status_code = 422;
            $this->result->message = 'Product not available';
        }

        $spec_data = json_decode($product_details['spec_data']);

        $all_assorted = [];

        $all_special = [];

        // dd( $product_details );

        $fetch_all_booking_order = DealerCart::where(
            'user_id',
            $dealer_id
        )->first();

        // fetch all the products under this cart made by the dealer
        $cart_data = json_decode($fetch_all_booking_order->cart_data);

        // echo var_dump( $cart_data );
        // exit();

        $same_group_items = [];

        foreach ($cart_data as $key => $value) {
            if (isset($cart_data[$key + 1])) {
                $value_group = $value->grouping;
                if ($value_group == $cart_data[$key + 1]->grouping) {
                    // so they are the same group
                    array_push($same_group_items, $value);

                    array_merge([$value_group => $same_group_items]);
                }
            }
        }

        // dd( $same_group_items );

        foreach ($spec_data as $key => $value) {
            $assorted_type = $value->type;
            $assorted_cond = intval($value->cond);
            if ($assorted_type == 'assorted') {
                // check the value
                if (isset($spec_data[$key + 1])) {
                    // check all the quantity of all the products that are in the same group

                    // if the sum of the two quantities is more than the condition

                    if (
                        $quantity >= $assorted_cond &&
                        $quantity < $spec_data[$key + 1]->cond
                    ) {
                    }
                } else {
                    if ($quantity >= $assorted_cond) {
                    }
                }

                // array_push( $all_assorted, $value );
            }

            if ($assorted_type == 'special') {
                array_push($all_special, $value);
            }
        }

        return ['all_assorted' => $all_assorted, 'all_special' => $all_special];
    }

    public function check_product_type_special()
    {
    }

    public function check_product_type_normal()
    {
    }

    public function edit_order(Request $request)
    {
        //
        $validator = Validator::make($request->all(), [
            'atlas_id' => 'required',
            'quantity' => 'required|integer',
            'dealer_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'atlas_id' => $validator->errors()->get('atlas_id'),
                'quantity' => $validator->errors()->get('quantity'),
                'dealer_id' => $validator->errors()->get('dealer_id'),
            ];
            return response()->json($this->result);
        } else {
            $atlas_id = $request->input('atlas_id');
            $quantity = $request->input('quantity');
            $dealer_id = $request->input('dealer_id');

            if ($this->check_product_type($atlas_id, $quantity, $dealer_id)) {
                $product_types = $this->check_product_type(
                    $atlas_id,
                    $quantity,
                    $dealer_id
                );
                // $all_assorted = $product_types[ 'all_assorted' ];

                // foreach ( $all_assorted as $key => $value ) {
                //     # code...
                // }
            }
        }
    }

    public function delete_order($dealer_id, $atlas_id)
    {
        $fetch_order = DealerCart::where('user_id', $dealer_id)->get();

        if (!$fetch_order) {
            $this->result->data = false;
            $this->result->status_code = 422;
            $this->result->message = 'Order not available';
            return response()->json($this->result);
        }

        // dd( $fetch_order );

        $cart_data = json_decode($fetch_order[0]->cart_data);

        $new_array = [];

        $atlas_ids = [];

        foreach ($cart_data as $key => $value) {
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
            $fetch_order[0]->cart_data = json_encode($new_array);

            $update_order = $fetch_order[0]->save();

            if (!$update_order) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Sorry Item could not be deleted';
                return response()->json($this->result);
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Item deleted from order successfully';
            return response()->json($this->result);
        }
    }

    public function edit_dealer(Request $request)
    {
        // `first_name`, `last_name`, `email`, `password`, `username`,
        //  `account_id`, `phone`, `status`, `order_status`, `location`,
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        // all fields are editable but all fields are not required

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'id' => $validator->errors()->get('id'),
            ];
            return response()->json($this->result);
        } else {
            $id = $request->input('id');
            $first_name = $request->input('first_name');
            $last_name = $request->input('last_name');
            $username = $request->input('username');
            $email = $request->input('email');
            $phone = $request->input('phone');
            $location = $request->input('location');

            $password = $request->input('password');

            $dealer_details = Dealer::find($id);

            if (!$dealer_details) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Sorry Dealer not found ';
                return response()->json($this->result);
            }

            // return $password;

            // $update_records = array (

            //     41 =>
            //     array (
            //       0 => '8177-81',
            //       1 => 'CRC RV CENTRE LTD',
            //       2 => 'aboudreau@crcrv.ca',
            //       3 => '4ZvqCx',
            // ),
            //     42 =>
            //     array (
            //       0 => '8300-81',
            //       1 => 'FRASERWAY RV LLP-NS',
            //       2 => 'sarah.hadden@fraserway.com',
            //       3 => 'fZgc76',
            // ),
            //     43 =>
            //     array (
            //       0 => '8750-81',
            //       1 => 'PATTERSON SALES & SERVICE',
            //       2 => 'kmacmullin@ljpattersonsales.com',
            //       3 => '9RYm2v',
            // ),
            //     44 =>
            //     array (
            //       0 => '8765-81',
            //       1 => 'PINE ACRES RV MONCTON',
            //       2 => 'parts@pineacresrv.ca',
            //       3 => 'hKqWf9',
            // ),
            //     45 =>
            //     array (
            //       0 => '8835-81',
            //       1 => 'SACKVILLE AUTO & RV LTD.',
            //       2 => 'parts@sackvillerv.ca',
            //       3 => '9DCnx3',
            // ),
            //     46 =>
            //     array (
            //       0 => '7005-71',
            //       1 => 'A.&S. LEVESQUE (1993) INC',
            //       2 => 'gbluteau@roulotte.ca',
            //       3 => 'U7pegb',
            // ),
            //     47 =>
            //     array (
            //       0 => '7020-71',
            //       1 => 'ARVISAIS AUTO INC.',
            //       2 => 'josee@arvisaisauto.com',
            //       3 => '9bufjK',
            // ),
            //     48 =>
            //     array (
            //       0 => '7109-71',
            //       1 => 'BELCO ACCESSOIRES DE',
            //       2 => 'laurence@belcovr.ca',
            //       3 => 'aEqQM7',
            // ),
            //     49 =>
            //     array (
            //       0 => '7112-71',
            //       1 => 'BERIC SPORT INC.',
            //       2 => 'stephane.coderre@bericsport.com',
            //       3 => 'AnEJ45',
            // ),
            //     50 =>
            //     array (
            //       0 => '7127-71',
            //       1 => 'CENT. LEISURE DAYS HULL',
            //       2 => 'mbaril@leisuredaysrv.ca',
            //       3 => 'nWjc4g',
            // ),
            //     51 =>
            //     array (
            //       0 => '7177-71',
            //       1 => 'PASSION VR CARAVANE',
            //       2 => 'comptoir@caravanevaillancourt.com',
            //       3 => '7RTPvr',
            // ),
            //     52 =>
            //     array (
            //       0 => '7191-71',
            //       1 => 'CARAVANE 201 (2005) INC',
            //       2 => 'clepine@caravane201.com',
            //       3 => 'nrEg5h',
            // ),
            //     53 =>
            //     array (
            //       0 => '7207-71',
            //       1 => 'CENTRE DU CAMPING ET',
            //       2 => 'marcouillermichel@cgocable.ca',
            //       3 => 'BJYw9r',
            // ),
            //     54 =>
            //     array (
            //       0 => '7218-71',
            //       1 => 'CENTRE DU CAMPING',
            //       2 => 'c.vaudreuil@roulottesremillard.com',
            //       3 => 'AgpX59',
            // ),
            //     55 =>
            //     array (
            //       0 => '7245-71',
            //       1 => 'CRISTAL VR INC',
            //       2 => 'jpgallard@cristalvr.com',
            //       3 => 'TR46t3',
            // ),
            //     56 =>
            //     array (
            //       0 => '7298-71',
            //       1 => 'DALLAIRE EQUIPMENT',
            //       2 => 'pieces@dallairest-bruno.com',
            //       3 => 'F7qnQJ',
            // ),
            //     57 =>
            //     array (
            //       0 => '7350-71',
            //       1 => 'LE GEANT MOTORISE INC.',
            //       2 => 'CMALTAIS@LEGEANTMOTORISE.COM',
            //       3 => 'U2xPMW',
            // ),
            //     58 =>
            //     array (
            //       0 => '7360-71',
            //       1 => 'VR SOULIERE SHERBROOKE',
            //       2 => 'piecessherbrooke@vrsouliere.com',
            //       3 => '4naBN2',
            // ),
            //     59 =>
            //     array (
            //       0 => '7386-71',
            //       1 => 'HORIZON LUSSIER LTEE',
            //       2 => 'chantaljourdain@horizonlussier.com',
            //       3 => '7wWQ68',
            // ),
            //     60 =>
            //     array (
            //       0 => '7433-71',
            //       1 => 'L.M. COSSETTE INC',
            //       2 => 'LMCOSSETTE.PIECES@outlook.com',
            //       3 => 'EqM6CG',
            // ),
            //     61 =>
            //     array (
            //       0 => '7632-71',
            //       1 => 'MONTCALM ELECTRONIQUE',
            //       2 => 'DFISET@VRTERREBONNE.CA',
            //       3 => 'cAhK6r',
            // ),
            //     62 =>
            //     array (
            //       0 => '7680-71',
            //       1 => 'PAR NADO INC (SAFARI',
            //       2 => 'buyer1@safaricondo.ca',
            //       3 => '6KnGLA',
            // ),
            //     63 =>
            //     array (
            //       0 => '7700-71',
            //       1 => 'V.R. ST-CYR LTEE',
            //       2 => 'dbegin@vrstcyr.com',
            //       3 => 'qCbTZ6',
            // ),
            //     64 =>
            //     array (
            //       0 => '7701-71',
            //       1 => 'V.R. ST-CYR-ST NICHOLAS',
            //       2 => 'dbegin@vrstcyr.com',
            //       3 => 'kQ5Jfu',
            // ),
            //     65 =>
            //     array (
            //       0 => '7910-71',
            //       1 => 'URGENCE ROULOTTE R.L. INC',
            //       2 => 'info@urgenceroulotte.ca',
            //       3 => 'S8JN6h',
            // ),
            //     66 =>
            //     array (
            //       0 => '7942-71',
            //       1 => 'V.R. CAMIONS EXPERTS INC.',
            //       2 => 'ph@vrcam.net',
            //       3 => '3ASymJ',
            // ),
            //     67 =>
            //     array (
            //       0 => '7949-71',
            //       1 => 'VEHICULES RECREATIFS',
            //       2 => 'NATHALIE.PAQUETTE@UNIVR.CA',
            //       3 => 'f4qwbU',
            // ),
            //     68 =>
            //     array (
            //       0 => '7951-71',
            //       1 => 'V.R. ST-NICOLAS',
            //       2 => 'deppieces@vrdusud.com',
            //       3 => 'rHqWG9',
            // ),
            //     69 =>
            //     array (
            //       0 => '3041-61',
            //       1 => 'APEX RV REPAIRS LTD',
            //       2 => 'apexrvrepair@gmail.com',
            //       3 => '5aMftk',
            // ),
            //     70 =>
            //     array (
            //       0 => '3050-61',
            //       1 => 'ARBUTUS RV & MARINE SALES',
            //       2 => 'cp2mail@arbutusrv.ca',
            //       3 => '4xv5Lr',
            // ),
            //     71 =>
            //     array (
            //       0 => '3052-61',
            //       1 => 'ARBUTUS RV & MARINE SALES',
            //       2 => 'portalberniparts@arbutusrv.ca',
            //       3 => 'uzyeU7',
            // ),
            //     72 =>
            //     array (
            //       0 => '3162-61',
            //       1 => 'CANADREAM INC.',
            //       2 => 'vtoukhcher@canadream.com',
            //       3 => 'qNrkg6',
            // ),
            //     73 =>
            //     array (
            //       0 => '3235-61',
            //       1 => 'COTTONWOOD R.V. SALES &',
            //       2 => 'parts@cottonwoodrv.ca',
            //       3 => '4WPpRE',
            // ),
            //     74 =>
            //     array (
            //       0 => '3318-61',
            //       1 => 'ESCAPE TRAILER INDUSTRIES',
            //       2 => 'purchasing@escapetrailer.com',
            //       3 => 'Y2RjBz',
            // ),
            //     75 =>
            //     array (
            //       0 => '3360-61',
            //       1 => 'FRASERWAY RV LLP-ABBOTSFD',
            //       2 => 'sarah.hadden@fraserway.com',
            //       3 => 'k9JLtu',
            // ),
            //     76 =>
            //     array (
            //       0 => '3362-61',
            //       1 => 'FRASERWAY RV LLP-TRAVLHME',
            //       2 => 'sarah.hadden@fraserwayrv.ca',
            //       3 => 'xK83zc',
            // ),
            //     77 =>
            //     array (
            //       0 => '3379-61',
            //       1 => 'GALAXY RV PARKSVILLE',
            //       2 => 'eric@galaxyrv.net',
            //       3 => 'z7rsXy',
            // ),
            //     78 =>
            //     array (
            //       0 => '3402-61',
            //       1 => 'FRASERWAY RV LLP- PG',
            //       2 => 'jason.mcburnie@fraserway.com',
            //       3 => '3NUJ2k',
            // ),
            //     79 =>
            //     array (
            //       0 => '3721-61',
            //       1 => 'PREVOST RV & MARINE LTD.',
            //       2 => 'lindsay@prevostrv.ca',
            //       3 => 'yZj96G',
            // ),
            //     80 =>
            //     array (
            //       0 => '3816-61',
            //       1 => 'SMP-RV LTD',
            //       2 => 'lallen@smprv.ca',
            //       3 => 'C3T9hJ',
            // ),
            //     81 =>
            //     array (
            //       0 => '3839-61',
            //       1 => 'TAHOE INDUSTRIES CANADA',
            //       2 => 'info@tahoeindustries.com',
            //       3 => 'Z52vWH',
            // ),
            //     82 =>
            //     array (
            //       0 => '3850-61',
            //       1 => 'TITANIUM HITCH',
            //       2 => 'nickel@ehhitch.com',
            //       3 => 'Q3rcyT',
            // ),
            //     83 =>
            //     array (
            //       0 => '3907-61',
            //       1 => 'VOYAGER R.V. CENTRE',
            //       2 => 'tammy@voyagerrv.ca',
            //       3 => '4VHR6c',
            // )
            // );

            // // return $update_records;

            // $no_dat_exists = 0;

            // $no_dat_doesnt_exists = 0;
            // $all_items = [];
            // foreach ( $update_records as $key => $item ) {
            //     $check_dealer = Dealer::where( 'account_id', $item[ 0 ] )->get();

            //     if ( count( $check_dealer ) > 0 ) {
            //         $no_dat_exists ++;
            //         $account_id = $item[ 0 ];
            //         $full_name = $item[ 1 ];
            //         $email = $item[ 2 ];
            //         $password = $item[ 3 ];

            //         $update_dealer = $check_dealer[ 0 ]->update( [
            //             'full_name' => $full_name,
            //             'email' => $email,
            //             'password' => $password,
            //             'password' => bcrypt( $password ),
            //             'password_clear' => $password
            // ] );

            //         array_push( $all_items, $update_dealer );
            //     } else {
            //        $no_dat_doesnt_exists ++;

            //     }
            // }

            // return [
            //         'no_dat_exists' => $no_dat_exists,
            //         'no_dat_doesnt_exists' => $no_dat_doesnt_exists,
            //         'data' => $all_items
            // ];

            // return bcrypt( $password );

            $update_dealer = $dealer_details->update([
                'first_name' => $first_name
                    ? $first_name
                    : $dealer_details->first_name,
                'last_name' => $last_name
                    ? $last_name
                    : $dealer_details->last_name,
                'email' => $email ? $email : $dealer_details->email,
                'username' => $username ? $username : $dealer_details->username,
                'phone' => $phone ? $phone : $dealer_details->phone,
                'location' => $location ? $location : $dealer_details->location,
                'password' => $password
                    ? bcrypt($password)
                    : bcrypt($dealer_details->password_clear),
                'password_clear' => $password,
            ]);

            if (!$update_dealer) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry We could not update dealer details. Try again later';
                return response()->json($this->result);
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Dealer Details updated successfully';
            return response()->json($this->result);
        }
    }

    public function register_branch(Request $request)
    {
        // `email`, `password`, `password_clear`, `name`, `first_name`,
        //  `last_name`, `username`, `phone`,
        // `location`, `status`, `updated_at`, `created_at`, `deleted_at`

        $validator = Validator::make($request->all(), [
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|string|email|unique:atlas_branches',
            'password' => 'required',
            'phone' => 'required',
            'location' => 'required',
            'branch_name' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = $validator->errors()->get('email');
        } else {
            $branch = Branch::create([
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'password_clear' => $request->password,
                'phone' => $request->phone,
                'name' => $request->branch_name,
                'location' => $request->location,
                'username' => $request->firstName . Helpers::generate_number(3),
                'status' => '1',
            ]);

            $this->result->status = true;
            $this->result->message = 'Branch Created Successfully';
        }
        return response()->json($this->result);
    }

    public function fetch_service_parts_by_dealer_id($dealer_id)
    {
        $service_parts = ServiceParts::where('dealer', $dealer_id)->get();

        $dealer_details = Dealer::where('account_id', $dealer_id)->get();

        $order_date = $dealer_details[0]->placed_order_date;

        foreach ($service_parts as $value) {
            $data = $value->data ? json_decode($value->data) : [];
            $value->data = $data;
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
            $value->order_date = $order_date;
        }

        if (!$service_parts) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Service Part not found';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $service_parts;

        $this->result->message = 'Service Part fetched successfully';
        return response()->json($this->result);
    }

    public function fetch_carded_products_by_dealer_id($dealer_id)
    {
        $carded_products = CardedProducts::where('dealer', $dealer_id)->get();

        // get the order date

        $dealer_details = Dealer::where('account_id', $dealer_id)->get();

        $order_date = $dealer_details[0]->placed_order_date;

        foreach ($carded_products as $value) {
            $data = $value->data ? json_decode($value->data) : [];
            $value->data = $data;
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
            $value->order_date = $order_date;
        }

        if (!$carded_products) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Carded Products not found';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $carded_products;

        $this->result->message = 'Carded Products fetched successfully';
        return response()->json($this->result);
    }

    public function fetch_all_catalogue_orders()
    {
        // $dealers = Dealer::all();
        // $products = Products::all();
        // $carded_products = CardedProducts::all();
        // $service_parts = ServiceParts::all();
        $catalogue_orders = Catalogue_order::all();

        $fetch_catalogue_orders = DB::table('atlas_catalogue_orders')
            ->join(
                'atlas_dealers',
                'atlas_catalogue_orders.dealer',
                '=',
                'atlas_dealers.id'
            )
            ->orderBy('atlas_login_log.updated_at', 'DESC')
            ->get();
    }

    public function update_short_note_url(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'atlas_id' => 'required',
            'pdf_url' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = [
                'atlas_id' => $validator->errors()->get('atlas_id'),
                'pdf_url' => $validator->errors()->get('pdf_url'),
            ];
            return response()->json($this->result);
        } else {
            $atlas_id = $request->input('atlas_id');
            $pdf_url = $request->input('pdf_url');

            // check if the product exists
            $check_product = Products::where('atlas_id', $atlas_id)->get();

            if (!$check_product || count($check_product) == 0) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Product with atlas id not found';
                return response()->json($this->result);
            }

            $update_product = $check_product[0]->update([
                'short_note_url' => $pdf_url,
            ]);

            if (!$update_product) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Product with atlas id not found';
                return response()->json($this->result);
            } else {
                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->message = 'Product updated successfully';
                return response()->json($this->result);
            }
        }
    }

    public function delete_dealer($dealer_id)
    {
        $fetch_Dealer = Dealer::find($dealer_id);

        if (!$fetch_Dealer) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Dealer with id ' . $dealer_id . ' not found';
            return response()->json($this->result);
        }

        $delete_dealer = $fetch_Dealer->delete();

        if (!$delete_dealer) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry we could not delete the dealer. Try again later. ';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'Dealer deleted Successfully';
        return response()->json($this->result);
    }

    public function export_carded_products($from, $to)
    {
        if (!$from && !$to) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry you have to select a range';
            return response()->json($this->result);
        }

        // fetch the data

        $all_dealer_account_ids = DB::table('atlas_dealers')
            ->where('order_status', 1)
            ->pluck('account_id')
            ->toArray();

        // return $all_dealer_account_ids;

        $format_from_date = date('Y-m-d', strtotime($from));

        $format_to_date = date('Y-m-d', strtotime($to));

        if ($from && $to) {
            $carded_products = DB::table('atlas_carded_products')
                ->wherein('dealer', $all_dealer_account_ids)
                ->join(
                    'atlas_dealers',
                    'atlas_carded_products.dealer',
                    '=',
                    'atlas_dealers.account_id'
                )
                // ->whereBetween( 'atlas_carded_products.created_at', [ $format_from_date, $format_to_date ] )
                ->whereDate(
                    'atlas_carded_products.created_at',
                    '>=',
                    $format_from_date
                )
                ->whereDate(
                    'atlas_carded_products.created_at',
                    '<=',
                    $format_to_date
                )
                ->select(
                    'atlas_dealers.account_id',
                    'atlas_dealers.full_name as dealer_name',
                    'atlas_dealers.placed_order_date',
                    'atlas_carded_products.*'
                )
                ->get();

            foreach ($carded_products as $value) {
                $data = $value->data ? json_decode($value->data) : [];
                $value->data = $data;
            }

            $new_carded_products = [];

            // array_
            foreach ($carded_products as $key1 => $value) {
                $dealer_account_id = $value->account_id;
                $dealer_name = $value->dealer_name;
                $data = $value->data;
                $placed_order_date = $value->placed_order_date;

                foreach ($data as $item) {
                    $item->dealer_account_id = $dealer_account_id;
                    $item->dealer_name = $dealer_name;
                    $item->placed_order_date = $placed_order_date;
                    $new_carded_products[] = $item;
                }
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $new_carded_products;
            $this->result->message = 'Carded Products fetched Successfully';
            return response()->json($this->result);

            // $export_Class = new Helpers();

            // return $export_Class->exportFile( 'Export Carded Products', ( array )$new_carded_products );
        }

        // return  $format_from_date;
    }

    public function export_service_parts($from, $to)
    {
        if (!$from && !$to) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry you have to select a range';
            return response()->json($this->result);
        }

        // fetch the data

        $all_dealer_account_ids = DB::table('atlas_dealers')
            ->where('order_status', 1)
            ->pluck('account_id')
            ->toArray();

        // return $all_dealer_account_ids;

        $format_from_date = date('Y-m-d', strtotime($from));

        $format_to_date = date('Y-m-d', strtotime($to));

        if ($from && $to) {
            $atlas_service_parts = DB::table('atlas_service_parts')
                ->wherein('dealer', $all_dealer_account_ids)
                ->join(
                    'atlas_dealers',
                    'atlas_service_parts.dealer',
                    '=',
                    'atlas_dealers.account_id'
                )
                // ->whereBetween( 'atlas_service_parts.created_at', [ $format_from_date, $format_to_date ] )
                ->whereDate(
                    'atlas_service_parts.created_at',
                    '>=',
                    $format_from_date
                )
                ->whereDate(
                    'atlas_service_parts.created_at',
                    '<=',
                    $format_to_date
                )
                ->select(
                    'atlas_dealers.account_id',
                    'atlas_dealers.full_name as dealer_name',
                    'atlas_dealers.placed_order_date',
                    'atlas_service_parts.*'
                )
                ->get();

            foreach ($atlas_service_parts as $value) {
                $data = $value->data ? json_decode($value->data) : [];
                $value->data = $data;
            }

            $new_atlas_service_parts = [];

            // array_
            foreach ($atlas_service_parts as $key1 => $value) {
                $dealer_account_id = $value->account_id;
                $dealer_name = $value->dealer_name;
                $data = $value->data;
                $placed_order_date = $value->placed_order_date;

                foreach ($data as $item) {
                    $item->dealer_account_id = $dealer_account_id;
                    $item->dealer_name = $dealer_name;
                    $item->placed_order_date = $placed_order_date;
                    $new_atlas_service_parts[] = $item;
                }
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $new_atlas_service_parts;
            $this->result->message =
                'Service Parts Products fetched Successfully';
            return response()->json($this->result);
        }
    }

    public function export_catalogue_orders($from, $to)
    {
        if (!$from && !$to) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry you have to select a range';
            return response()->json($this->result);
        }

        // fetch the data

        $all_dealer_account_ids = DB::table('atlas_dealers')
            ->where('order_status', 1)
            ->pluck('account_id')
            ->toArray();

        // return $all_dealer_account_ids;

        $format_from_date = date('Y-m-d', strtotime($from));

        $format_to_date = date('Y-m-d', strtotime($to));

        if ($from && $to) {
            $atlas_catalogue_orders = DB::table('atlas_catalogue_orders')
                ->wherein('dealer', $all_dealer_account_ids)
                ->join(
                    'atlas_dealers',
                    'atlas_catalogue_orders.dealer',
                    '=',
                    'atlas_dealers.account_id'
                )
                // ->whereBetween( 'atlas_catalogue_orders.created_at', [ $format_from_date, $format_to_date ] )
                ->whereDate(
                    'atlas_catalogue_orders.created_at',
                    '>=',
                    $format_from_date
                )
                ->whereDate(
                    'atlas_catalogue_orders.created_at',
                    '<=',
                    $format_to_date
                )
                ->select(
                    'atlas_dealers.account_id',
                    'atlas_dealers.full_name as dealer_name',
                    'atlas_dealers.placed_order_date',
                    'atlas_catalogue_orders.*'
                )
                ->get();

            foreach ($atlas_catalogue_orders as $value) {
                $data = $value->data ? json_decode($value->data) : [];
                $value->data = $data;
            }

            $new_atlas_catalogue_orders = [];

            // array_
            foreach ($atlas_catalogue_orders as $key1 => $value) {
                $dealer_account_id = $value->account_id;
                $dealer_name = $value->dealer_name;
                $data = $value->data;
                $placed_order_date = $value->placed_order_date;

                foreach ($data as $item) {
                    $item->dealer_account_id = $dealer_account_id;
                    $item->dealer_name = $dealer_name;
                    $item->placed_order_date = $placed_order_date;
                    $new_atlas_catalogue_orders[] = $item;
                }
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->data = $new_atlas_catalogue_orders;
            $this->result->message =
                'Catalogue Order Products fetched Successfully';
            return response()->json($this->result);
        }
    }

    public function fetch_locations()
    {
        $unique_location = Dealer::distinct('location')
            ->pluck('location')
            ->toArray();

        if (!$unique_location) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry we could not fetch the unique locations';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $unique_location;
        $this->result->message = 'Unique Dealer Locations fetched Successfully';
        return response()->json($this->result);
    }

    public function edit_promotional_ad(Request $request)
    {
        // `id`, `category_id`, `name`, `type`, `description`, `image_url`,
        // `status`, `created_at`, `updated_at`, `deleted_at`
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'category_id' => 'required',
            'name' => 'required',
            'type' => 'required',
            'image_url' => 'required',
        ]);

        if ($validator->fails()) {
            $this->result->status_code = 422;
            $this->result->message = $validator->errors()->messages();
            return response()->json($this->result);
        } else {
            $id = $request->input('id');
            $category_id = $request->input('category_id');
            $name = $request->input('name');
            $type = $request->input('type');
            $description = $request->input('description');
            $image_url = $request->input('image_url');

            $check_promotional_Ad = Promotional_ads::where('id', $id)
                ->where('status', 1)
                ->get();

            if (!$check_promotional_Ad || count($check_promotional_Ad) == 0) {
                // not found
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry Promotional Ads doesnt exists or has been deactivated';
                return response()->json($this->result);
            }

            $update_promotional_Ad = $check_promotional_Ad[0]->update([
                'id' => $id ? $id : null,
                'category_id' => $category_id ? $category_id : null,
                'name' => $name ? $name : null,
                'type' => $type ? $type : null,
                'description' => $description ? $description : null,
                'image_url' => $image_url ? $image_url : null,
            ]);

            if (!$update_promotional_Ad) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message =
                    'Sorry we could not update Promotional Ads';
                return response()->json($this->result);
            }

            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Promotional Ads updated Successfully';
            return response()->json($this->result);
        }
    }

    public function delete_admin($id)
    {
        $check_Admin = Admin::where('id', $id)
            ->where('status', '1')
            ->get();

        if (!$check_Admin || count($check_Admin) == 0) {
            // Product allready deactivated
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry Admin doesn\'t exist or has already been deactivated';
            return response()->json($this->result);
        }

        // delete the admin
        // $check_Admin[0]->status = 0;
        // $update_Product = $check_Admin[0]->save();

        $update_admin = $check_Admin[0]->delete();

        if ($update_admin) {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Products has been deleted successfully';
            return response()->json($this->result);
        }
    }

    public function deactivate_admin($id)
    {
        $check_Admin = Admin::where('id', $id)
            ->where('status', '1')
            ->get();

        if (!$check_Admin || count($check_Admin) == 0) {
            // Product allready deactivated
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry Admin doesn\'t exist or has already been deactivated';
            return response()->json($this->result);
        }

        // delete the admin
        $check_Admin[0]->status = 0;
        $update_admin = $check_Admin[0]->save();

        // $update_admin = $check_Admin[ 0 ]->delete();

        if ($update_admin) {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Admin has been deactivated successfully';
            return response()->json($this->result);
        }
    }

    public function fetch_all_admins()
    {
        $fetch_all_Admins = Admin::all();

        if (!$fetch_all_Admins) {
            // Product allready deactivated
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry we could not fetch all the admins';
            return response()->json($this->result);
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $fetch_all_Admins;
        $this->result->message = 'All Admins has been fetched successfully';
        return response()->json($this->result);
    }

    public function activate_admin($id)
    {
        $check_Admin = Admin::where('id', $id)
            ->where('status', '0')
            ->get();

        if (!$check_Admin || count($check_Admin) == 0) {
            // Product allready deactivated
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message =
                'Sorry Admin doesn\'t exist or has already been activated';
            return response()->json($this->result);
        }

        // delete the admin
        $check_Admin[0]->status = 1;
        $update_admin = $check_Admin[0]->save();

        // $update_admin = $check_Admin[0]->delete();

        if ($update_admin) {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Admin has been activated successfully';
            return response()->json($this->result);
        }
    }
}
