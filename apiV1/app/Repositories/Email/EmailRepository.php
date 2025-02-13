<?php

namespace App\Repositories\Email;

use App\Models\EmailTemplate;
use App\Mail\BaseMail;
use Illuminate\Support\Facades\Mail;
use App\Models\EmailLog; // Optional: If storing logs
use App\Models\User;
use Illuminate\Support\Facades\Log;

class EmailRepository implements EmailRepositoryInterface
{
    public function sendEmail($user, $templateName, $data = [])
    {
        try {
            $template = EmailTemplate::getTemplateByName($templateName);

            if (!$template) {
                throw new \Exception("Email template not found: " . $templateName);
            }

            Mail::to($user->email)->send(new BaseMail($user, $template, $data));

            // Optional: Store email log
            EmailLog::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'template_name' => $templateName,
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log the error
            Log::error("Error sending email: " . $e->getMessage());
        }
    }

    public function sendWelcomeEmail($user, $templateName)
    {
        try {
            // Check if the template is being fetched correctly
            $template = EmailTemplate::getTemplateByName($templateName);
    
            if (!$template) {
                throw new \Exception("Email template not found: " . $templateName);
            }
    
            // Log the template name for debugging purposes
            Log::info('Sending welcome email to: ' . $user->email . ' using template: ' . $templateName);
    
            // Send the welcome email
            Mail::to($user->email)->send(new BaseMail($user, $template, []));
            
            Log::info('Welcome email sent to: ' . $user->email);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error sending welcome email: ' . $e->getMessage());
        }
    }

    public function sendPaymentVerificationEmail($user, $order)
    {
        $this->sendEmail($user, 'payment_verification_email', ['order' => $order]);
    }

    public function sendDiscountEmail($user, $discountCode)
    {
        $this->sendEmail($user, 'discount_email', ['discount_code' => $discountCode]);
    }

    // Retrieve sent emails (if storing logs)
    public function getEmailHistory($user)
    {
        return EmailLog::where('user_id', $user->id)->orderBy('sent_at', 'desc')->get();
    }

    // Resend an email (if storing logs)
    public function resendEmail($emailLogId)
    {
        $emailLog = EmailLog::findOrFail($emailLogId);
        $user = User::findOrFail($emailLog->user_id);

        $this->sendEmail($user, $emailLog->template_name, []);
    }

    // Delete an email record (Optional)
    public function deleteEmailLog($emailLogId)
    {
        return EmailLog::where('id', $emailLogId)->delete();
    }
}
