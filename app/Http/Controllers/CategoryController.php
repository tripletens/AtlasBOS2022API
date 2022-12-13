<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
 {
    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */

    public function __construct()
    {
        //// $this->middleware( 'auth:api', [ 'except' => [ 'login', 'register', 'test' ] ] );
        $this->result = ( object ) array(
            'status' => false,
            'status_code' => 200,
            'message' => null,
            'data' => ( object ) null,
            'token' => null,
            'debug' => null
        );
    }

    public function index() {
        // fetch all categories
        $categories = Category::all();

        if ( !$categories ) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry we could not fetch all the categories';
            return response()->json( $this->result );
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->message = 'All categories fetched successfully';
        $this->result->data = $categories;
        return response()->json( $this->result );
    }

    /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */

    public function create()
 {
        //
    }

    /**
    * Store a newly created resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */

    public function store( Request $request ) {
        // add a category
        // `name`, `image`, `color_code`, `description`,
        $validator = Validator::make( $request->all(), [
            'name' => 'required|string',
            'color_code' => 'required',
            'img' => 'required|string',
        ] );

        if ( $validator->fails() ) {
            $this->result->status_code = 422;
            $this->result->message = [
                'name' => $validator->errors()->get( 'name' ),
                'color_code' => $validator->errors()->get( 'color_code' ),
                'img' => $validator->errors()->get( 'img' )
            ];
        } else {
            // save the image

            // save to the db
            $name = $request->input( 'name' );
            //$description = $request->input( 'description' );
            $color_code = $request->input( 'color_code' );
            $img = $request->input( 'img' );

            $save_category = Category::create( [
                'name' => $name,
                'color_code' => $color_code,
                'image' =>  $img,
                'created_at' => Carbon::now(),
            ] );

            if ( $save_category ) {
                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->message = 'Category Successfully Added';
                return response()->json( $this->result );
            } else {
                $this->result->status = true;
                $this->result->status_code = 404;
                $this->result->message = 'An Error Ocurred, Category Addition failed';
                return response()->json( $this->result );
            }

            $this->result->status = true;
            $this->result->message = 'Successful';
        }
    }

    /**
    * Display the specified resource.
    *
    * @param  \App\Models\odel  $odel
    * @return \Illuminate\Http\Response
    */

    public function show( $id ) {

        // fetches all the categories
        $category = Category::where( 'id', $id )->where( 'status', 1 )->get();

        if ( count( $category ) == 0 ) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Category doesnt exists in our records';
            return response()->json( $this->result );
        }

        $this->result->status = true;
        $this->result->status_code = 200;
        $this->result->data = $category;
        $this->result->message = 'Category details fetched successfully';
        return response()->json( $this->result );
    }

    /**
    * Show the form for editing the specified resource.
    *
    * @param  \App\Models\odel  $odel
    * @return \Illuminate\Http\Response
    */

    public function edit( $id ) {
    }

    /**
    * Update the specified resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  \App\Models\odel  $odel
    * @return \Illuminate\Http\Response
    */

    public function update( Request $request, $id ) {
        // `name`, `image`, `color_code`, `description`,
        $validator = Validator::make( $request->all(), [
            'id' => 'required',
            'name' => 'required|string',
            'colorCode' => 'required',
            'imgUrl' => 'required|string',
        ] );

        if ( $validator->fails() ) {
            $this->result->status_code = 422;
            $this->result->message = [
                'name' => $validator->errors()->get( 'name' ),
                'colorCode' => $validator->errors()->get( 'colorCode' ),
                'imgUrl' => $validator->errors()->get( 'imgUrl' )
            ];
        } else {
            // update the category
            $category = Category::where( 'id', $id )->where( 'status', 1 )->get();

            if ( count( $category ) == 0 ) {
                $this->result->status = false;
                $this->result->status_code = 422;
                $this->result->message = 'Sorry Category doesnt exists in our records';
                return response()->json( $this->result );
            }

            $name = $request->input( 'name' );
            $color_code = $request->input( 'colorCode' );
            $img = $request->input( 'imgUrl' );

            $update = Category::where( 'id', $id )->update( [ 'name' => $name, 'color_code' => $color_code, 'image' =>  $img ] );

            // $category[ 0 ]->name = $name;
            // $category[ 0 ]->color_code = $color_code;
            // $category[ 0 ]->image;

            // $save_category = $category[ 0 ]->save();

            if ( $update ) {
                $this->result->status = true;
                $this->result->status_code = 200;
                $this->result->message = 'Category Successfully Updated';
                return response()->json( $this->result );
            } else {
                $this->result->status = true;
                $this->result->status_code = 404;
                $this->result->message = 'An Error Ocurred, Category Updating failed';
                return response()->json( $this->result );
            }

        }
    }

    /**
    * Remove the specified resource from storage.
    *
    * @param  \App\Models\odel  $odel
    * @return \Illuminate\Http\Response
    */

    public function destroy( $id ) {
        // delete a category
        $category = Category::where( 'id', $id )->where( 'status', 1 )->get();

        if ( count( $category ) == 0 ) {
            $this->result->status = false;
            $this->result->status_code = 422;
            $this->result->message = 'Sorry Category doesnt exists in our records';
            return response()->json( $this->result );
        }

        $change_category_status = $category[ 0 ]->update( [ 'status', 0 ] );

        $delete_category = $category[ 0 ]->delete();

        if ( $delete_category ) {
            $this->result->status = true;
            $this->result->status_code = 200;
            $this->result->message = 'Category Successfully deleted and status changed';
            return response()->json( $this->result );
        } else {
            $this->result->status = true;
            $this->result->status_code = 404;
            $this->result->message = 'An Error Ocurred, Category deletion failed';
            return response()->json( $this->result );
        }
    }
}
