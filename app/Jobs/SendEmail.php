<?php

namespace MentalHealthAI\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class SendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mailer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(PHPMailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->mailer->send();
            Log::info("Sent");
        } catch (Exception $e) {
            Log::info('Message could not be sent.');
            Log::info('Mailer Error: ' . $this->mailer->ErrorInfo);
        }
    }

    static function getBaseMailer()
    {
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = 0;                                 // Enable verbose debug output
            $mail->isSMTP();                                      // Set mailer to use SMTP
            $mail->Host = env("MAIL_HOST", "smtp.gmail.com");// Specify main and backup SMTP servers

            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = env("MAIL_USERNAME", "splussoftware2016@gmail.com");                 // SMTP username
            $mail->Password = env("MAIL_PASSWORD", "spl@2016");                           // SMTP password
            $mail->SMTPSecure = env("MAIL_ENCRYPTION", null);                           // Enable TLS encryption, `ssl` also accepted
            $mail->Port = env("MAIL_PORT", "587");                                  // TCP port to connect to

            //Recipients
            $mail->setFrom('from@example.com', 'Mailer');
//            $mail->addAddress('thienpg@splus-software.com.vn', 'Joe User');     // Add a recipient

            //Attachments
            //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
            //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

            //Content
            $mail->isHTML(true);                                  // Set email format to HTML


            return $mail;
        } catch (Exception $e) {
            abort(404, "Some error happen");
        }
    }
}
