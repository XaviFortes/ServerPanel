<?php

namespace App\Helpers;

use App\Mail\Mail;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Service;
use App\Models\TicketMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail as FacadesMail;
use Illuminate\Support\Facades\URL;

class NotificationHelper
{
    /**
     * Send an email notification.
     */
    public static function sendEmailNotification(
        $emailTemplateKey,
        array $data,
        User $user,
        array $attachments = []
    ): void {
        $emailTemplate = EmailTemplate::where('key', $emailTemplateKey)->first();
        if (!$emailTemplate || !$emailTemplate->enabled || config('settings.disable_mail')) {
            return;
        }
        $mail = new Mail($emailTemplate, $data);

        $emailLog = EmailLog::create([
            'user_id' => $user->id,
            'subject' => $mail->envelope()->subject,
            'to' => $user->email,
            'body' => $mail->render(),
        ]);

        // Add the email log id to the payload
        $mail->email_log_id = $emailLog->id;

        foreach ($attachments as $attachment) {
            $mail->attachFromStorage($attachment['path'], $attachment['name'], $attachment['options'] ?? []);
        }

        FacadesMail::to($user->email)
            ->bcc($emailTemplate->bcc)
            ->cc($emailTemplate->cc)
            ->queue($mail);
    }

    public static function loginDetectedNotification(User $user, array $data = []): void
    {
        self::sendEmailNotification('new_login_detected', $data, $user);
    }

    public static function invoiceCreatedNotification(User $user, Invoice $invoice): void
    {
        $data = [
            'invoice' => $invoice,
            'items' => $invoice->items,
            'total' => $invoice->formattedTotal,
            'has_subscription' => $invoice->items->filter(fn ($item) => $item->relation_type === Service::class && $item->relation->subscription_id)->isNotEmpty(),
        ];
        $attachments = [
            [
                'path' => 'invoices/' . $invoice->id . '.pdf',
                'name' => 'invoice.pdf',
            ],
        ];
        self::sendEmailNotification('new_invoice_created', $data, $user, $attachments);
    }

    public static function orderCreatedNotification(User $user, Order $order, array $data = []): void
    {
        $data = [
            'order' => $order,
            'items' => $order->services,
            'total' => $order->formattedTotal,
        ];
        self::sendEmailNotification('new_order_created', $data, $user);
    }

    public static function serverCreatedNotification(User $user, Service $service, array $data = []): void
    {
        $data['service'] = $service;
        self::sendEmailNotification('new_server_created', $data, $user);
    }

    public static function serverSuspendedNotification(User $user, Service $service, array $data = []): void
    {
        $data['service'] = $service;
        self::sendEmailNotification('server_suspended', $data, $user);
    }

    public static function serverTerminatedNotification(User $user, Service $service, array $data = []): void
    {
        $data['service'] = $service;
        self::sendEmailNotification('server_terminated', $data, $user);
    }

    public static function ticketMessageNotification(User $user, TicketMessage $ticketMessage, array $data = []): void
    {
        $data['ticketMessage'] = $ticketMessage;
        self::sendEmailNotification('new_ticket_message', $data, $user);
    }

    public static function emailVerificationNotification(User $user, array $data = []): void
    {
        $data['user'] = $user;
        $data['url'] = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->email),
            ]
        );
        self::sendEmailNotification('email_verification', $data, $user);
    }

    public static function passwordResetNotification(User $user, array $data = []): void
    {
        $data['user'] = $user;
        self::sendEmailNotification('password_reset', $data, $user);
    }
}
