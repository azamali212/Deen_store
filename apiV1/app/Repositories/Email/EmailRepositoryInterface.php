<?php 

namespace App\Repositories\Email;

interface EmailRepositoryInterface
{
    public function sendEmail($user, $templateName, $data = []); 
    public function sendWelcomeEmail($user,$templateName);
    public function sendPaymentVerificationEmail($user, $order);
    public function sendDiscountEmail($user, $discountCode);
    
    public function getEmailHistory($user);
    public function resendEmail($emailLogId);
    public function deleteEmailLog($emailLogId);


}