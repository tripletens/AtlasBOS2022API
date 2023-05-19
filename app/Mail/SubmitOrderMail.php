<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SubmitOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    private $order_ref;
    private $title;

    public function __construct($order_ref)
    {
        $this->order_ref = $order_ref;
        $this->title = '2023 Atlas Booking Order Details'; // Set the title of the email
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->title)->view('mails.submit_order')->attachData(env('APP_URL') . Storage::get('public/pdf/' . $this->order_ref), $this->order_ref);
    }
}
