<?php
// 简易SMTP邮件类，兼容所有PHP版本
class SimpleMailer {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $from;
    private $fromName;
    
    public function __construct($config) {
        $this->host = $config['smtp_host'];
        $this->port = $config['smtp_port'];
        $this->user = $config['smtp_user'];
        $this->pass = $config['smtp_pass'];
        $this->from = $config['smtp_from'];
        $this->fromName = $config['smtp_from_name'];
    }
    
    public function send($to, $subject, $body, $isHtml = true) {
        $socket = @fsockopen('ssl://' . $this->host, $this->port, $errno, $errstr, 30);
        if (!$socket) {
            // 尝试tls
            $socket = @fsockopen($this->host, 587, $errno, $errstr, 30);
            if (!$socket) return false;
        }
        
        $this->log = [];
        
        // 读取欢迎信息
        $this->getResponse($socket);
        
        // EHLO
        $this->sendCommand($socket, 'EHLO ' . $_SERVER['HTTP_HOST'] ?? 'localhost');
        
        // STARTTLS if needed
        if ($this->port == 587) {
            $this->sendCommand($socket, 'STARTTLS');
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->sendCommand($socket, 'EHLO ' . $_SERVER['HTTP_HOST'] ?? 'localhost');
        }
        
        // 认证
        $this->sendCommand($socket, 'AUTH LOGIN');
        $this->sendCommand($socket, base64_encode($this->user));
        $this->sendCommand($socket, base64_encode($this->pass));
        
        // MAIL FROM
        $this->sendCommand($socket, 'MAIL FROM:<' . $this->from . '>');
        
        // RCPT TO
        $this->sendCommand($socket, 'RCPT TO:<' . $to . '>');
        
        // DATA
        $this->sendCommand($socket, 'DATA');
        
        // 邮件内容
        $boundary = md5(uniqid());
        $headers = [];
        $headers[] = 'From: ' . '=?UTF-8?B?' . base64_encode($this->fromName) . '?= <' . $this->from . '>';
        $headers[] = 'To: <' . $to . '>';
        $headers[] = 'Subject: ' . '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Date: ' . date('r');
        
        $data = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
        fputs($socket, $data . "\r\n");
        $response = $this->getResponse($socket);
        
        // QUIT
        $this->sendCommand($socket, 'QUIT');
        fclose($socket);
        
        return strpos($response, '250') === 0;
    }
    
    private function sendCommand($socket, $command) {
        fputs($socket, $command . "\r\n");
        return $this->getResponse($socket);
    }
    
    private function getResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        return $response;
    }
}

// 重写send_email函数使用SimpleMailer
function send_email($to, $subject, $body) {
    $smtp_config = get_config('smtp_config', [
        'smtp_host' => 'smtp.163.com',
        'smtp_port' => 465,
        'smtp_user' => 'jtxnb886@163.com',
        'smtp_pass' => 'FLLRDtadYAfGXp9Y',
        'smtp_from' => 'jtxnb886@163.com',
        'smtp_from_name' => 'Jay影视'
    ]);
    
    try {
        $mailer = new SimpleMailer($smtp_config);
        return $mailer->send($to, $subject, $body, true);
    } catch (Exception $e) {
        error_log('邮件发送失败: ' . $e->getMessage());
        return false;
    }
}
