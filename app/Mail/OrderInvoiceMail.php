<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
// use Illuminate\Contracts\Queue\ShouldQueue; // nếu muốn queue thì mở và implements

class OrderInvoiceMail extends Mailable // implements ShouldQueue
{
    use Queueable, SerializesModels;

    public array $order;
    public bool $paid;

    public function __construct(array $order, bool $paid = false)
    {
        $this->order = $order;
        $this->paid  = $paid;
    }

    public function build()
    {
        $code    = $this->order['code'] ?? 'ORDER';
        $subject = ($this->paid ? 'Xác nhận thanh toán thành công' : 'Xác nhận đơn hàng')
            . ' – ' . $code . ' | ' . (config('mail.from.name') ?? config('app.name'));

        return $this->subject($subject)
            ->view('emails.order_invoice')
            ->with([
                'order' => $this->order,
                'paid'  => $this->paid,
            ]);
    }
}
