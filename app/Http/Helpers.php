<?php
namespace App\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Helpers {

    public static $permitted_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public static $permitted_nums = '1234567890';

    public function __construct() {

    }

    public static function generate_string( $length = 7 ) {
        $input_length = strlen( Helpers::$permitted_chars );
        $random_string = '';
        for ( $i = 0; $i < $length; $i++ ) {
            $random_character = Helpers::$permitted_chars[ mt_rand( 0, $input_length - 1 ) ];
            $random_string .= $random_character;
        }

        return $random_string;
    }

    public static function generate_number( $length = 2 ) {
        $input_length = strlen( Helpers::$permitted_nums );
        $random_string = '';
        for ( $i = 0; $i < $length; $i++ ) {
            $random_character = Helpers::$permitted_nums[ mt_rand( 0, $input_length - 1 ) ];
            $random_string .= $random_character;
        }

        return $random_string;
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
}

