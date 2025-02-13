<?php
namespace App\Http\Controllers\EmailMarket;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Repositories\Email\EmailRepositoryInterface;
use App\Models\User;

class EmailController extends Controller
{
    protected $emailRepository;

    public function __construct(EmailRepositoryInterface $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }

    public function sendWelcomeEmail(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $templateName = $request->template_name; // Ensure template_name is sent in the request
        $this->emailRepository->sendWelcomeEmail($user, $templateName); // Pass the template name
        return response()->json(['message' => 'Welcome email sent successfully!']);
    }

    public function sendPaymentVerificationEmail(Request $request)
    {
        $user = Customer::findOrFail($request->user_id);
        $order = $user->orders()->find($request->order_id);
        $this->emailRepository->sendPaymentVerificationEmail($user, $order);
        return response()->json(['message' => 'Payment verification email sent successfully!']);
    }

    public function sendDiscountEmail(Request $request)
    {
        $user = Customer::findOrFail($request->user_id);
        $this->emailRepository->sendDiscountEmail($user, $request->discount_code);
        return response()->json(['message' => 'Discount email sent successfully!']);
    }

    // Get email history
    public function getEmailHistory(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $emails = $this->emailRepository->getEmailHistory($user);
        return response()->json($emails);
    }

    // Resend an email
    public function resendEmail(Request $request)
    {
        $this->emailRepository->resendEmail($request->email_log_id);
        return response()->json(['message' => 'Email resent successfully!']);
    }

    // Delete an email log
    public function deleteEmailLog(Request $request)
    {
        $this->emailRepository->deleteEmailLog($request->email_log_id);
        return response()->json(['message' => 'Email log deleted successfully!']);
    }
}