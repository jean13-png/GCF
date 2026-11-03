<?php
namespace App\Controllers;

use App\Models\EmailLog;

final class MailLogController
{
    public function index(): void
    {
        view('mails/index', ['emails' => EmailLog::derniers()]);
    }
}
