<?php

class Mail
{
    protected static $to;
    protected static $subject;

    public static function to($email)
    {
        self::$to = $email;
        return new static;
    }

    public function subject($subject)
    {
        self::$subject = $subject;
        return $this;
    }

    public function send($message)
    {
        $driver = Env::get('MAIL_MAILER', 'log');

        if ($driver === 'smtp') {
            return self::sendSMTP(self::$to, self::$subject, $message);
        }

        return self::log(self::$to, self::$subject, $message);
    }

    protected static function sendSMTP($to, $subject, $message)
    {
        $host = Env::get('MAIL_HOST');
        $port = Env::get('MAIL_PORT');
        $encryption = Env::get('MAIL_ENCRYPTION');
        $user = Env::get('MAIL_USERNAME');
        $pass = Env::get('MAIL_PASSWORD');
        $from = Env::get('MAIL_FROM_ADDRESS');
        $name = Env::get('MAIL_FROM_NAME');

        if ($encryption === 'ssl') {
            $host = "ssl://" . $host;
        }

        $headers = [
            "From: $name <$from>",
            "Reply-To: $from",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8"
        ];

        $socket = fsockopen($host, $port, $errno, $errstr, 10);

        if (!$socket) {
            self::log($to, $subject, "SMTP ERROR: $errstr");
            return false;
        }

        self::smtpCmd($socket, "EHLO localhost");

        if ($user && $pass && $user !== 'null') {
            self::smtpCmd($socket, "AUTH LOGIN");
            self::smtpCmd($socket, base64_encode($user));
            self::smtpCmd($socket, base64_encode($pass));
        }

        self::smtpCmd($socket, "MAIL FROM:<$from>");
        self::smtpCmd($socket, "RCPT TO:<$to>");
        self::smtpCmd($socket, "DATA");

        $data = "Subject: $subject\r\n";
        $data .= implode("\r\n", $headers) . "\r\n\r\n";
        $data .= $message . "\r\n.";

        self::smtpCmd($socket, $data);
        self::smtpCmd($socket, "QUIT");

        fclose($socket);

        self::log($to, $subject, $message);

        return true;
    }

    protected static function smtpCmd($socket, $cmd)
    {
        fwrite($socket, $cmd . "\r\n");
        return fgets($socket, 512);
    }

    protected static function log($to, $subject, $message)
    {
        $file = Env::get('MAIL_LOG', 'mail.log');

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $log = "[" . date('Y-m-d H:i:s') . "]\n";
        $log .= "TO: $to\n";
        $log .= "SUBJECT: $subject\n";
        $log .= "MESSAGE:\n$message\n";
        $log .= "--------------------------\n";

        file_put_contents($file, $log, FILE_APPEND);
    }
}
