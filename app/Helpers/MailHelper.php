<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Mail;

class MailHelper
{
    /**
     * Send a generic mail using a Blade view (supports attachments)
     *
     * @param string $to Receiver email
     * @param string $subject Email subject
     * @param string $view Blade view name (e.g. 'emails.otp')
     * @param array $data Data to be passed to the view
     * @param array|null $attachments Array of file paths for attachments
     * @return bool
     */
    public static function sendMail(string $to, string $subject, string $view, array $data = [], array $attachments = null): bool
    {
        try {
            Mail::send($view, $data, function ($message) use ($to, $subject, $attachments) {
                $message->to($to)
                        ->subject($subject);

                // Add attachments if provided
                if ($attachments && count($attachments) > 0) {
                    foreach ($attachments as $filePath) {
                        if (file_exists($filePath)) {
                            $message->attach($filePath);
                        }
                    }
                }
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Mail send failed: ' . $e->getMessage());
            return false;
        }
    }
}
