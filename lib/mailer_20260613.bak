<?php
if (!defined('_GNUBOARD_')) exit;

function mailer_log($entry)
{
    $paths = array();

    if (defined('G5_DATA_PATH') && G5_DATA_PATH) {
        $paths[] = G5_DATA_PATH . '/mail_send_log.txt';
    }
    $paths[] = dirname(__FILE__) . '/../bbs/mail_log/mail_send_log.txt';

    foreach ($paths as $log_file) {
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }

        if (@file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX) !== false) {
            return true;
        }
    }

    return false;
}

function mailer_clean_email($email)
{
    $email = trim((string)$email);
    $email = str_replace(array("\r", "\n", "\t", ' '), '', $email);
    return $email;
}

function mailer_clean_header_value($value)
{
    return trim(str_replace(array("\r", "\n"), ' ', (string)$value));
}

function mailer_encode_subject($subject)
{
    $subject = mailer_clean_header_value($subject);

    if (function_exists('mb_encode_mimeheader')) {
        return mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n");
    }

    return '=?UTF-8?B?' . base64_encode($subject) . '?=';
}

function mailer_format_address($email, $name = '')
{
    $email = mailer_clean_email($email);
    $name = mailer_clean_header_value($name);

    if ($email === '') {
        return '';
    }

    if ($name !== '') {
        if (function_exists('mb_encode_mimeheader')) {
            $name = mb_encode_mimeheader($name, 'UTF-8', 'B', "\r\n");
        }
        return $name . ' <' . $email . '>';
    }

    return $email;
}

function mailer_default_from()
{
    return 'webmaster@xn--2e0bl1svji8lhr7f.kr';
}

function mailer_transport_info()
{
    return array(
        'php_os' => PHP_OS,
        'smtp' => function_exists('ini_get') ? ini_get('SMTP') : '',
        'smtp_port' => function_exists('ini_get') ? ini_get('smtp_port') : '',
        'sendmail_path' => function_exists('ini_get') ? ini_get('sendmail_path') : '',
    );
}

function mailer_build_body($content, $type, $boundary, $attachments)
{
    $charset = 'UTF-8';

    if (count($attachments) > 0) {
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: " . ($type == 1 ? "text/html" : "text/plain") . "; charset={$charset}\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $content . "\r\n";

        foreach ($attachments as $file) {
            if (empty($file['path']) || empty($file['name']) || !is_file($file['path'])) {
                continue;
            }

            $filename = basename($file['name']);
            $body .= "\r\n--{$boundary}\r\n";
            $body .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\r\n\r\n";
            $body .= chunk_split(base64_encode(file_get_contents($file['path'])));
        }

        $body .= "\r\n--{$boundary}--\r\n";
        return $body;
    }

    return $content;
}

function mailer_send_via_mail($to, $subject, $content, $from, $from_name, $reply_to, $type, $cc, $bcc, $file)
{
    $charset = 'UTF-8';
    $headers = array();
    $attachments = array();

    if (is_array($file)) {
        $attachments = $file;
    } elseif (!empty($file)) {
        $attachments = array($file);
    }

    $from_email = mailer_clean_email($from);
    if ($from_email === '' || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
        $from_email = mailer_default_from();
    }

    $from_header = mailer_format_address($from_email, $from_name);
    if ($from_header !== '') {
        $headers[] = 'From: ' . $from_header;
        $headers[] = 'Sender: ' . $from_email;
    }

    $reply_to = mailer_clean_email($reply_to);
    if ($reply_to && filter_var($reply_to, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $reply_to;
    }

    $cc = mailer_clean_email($cc);
    if ($cc && filter_var($cc, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Cc: ' . $cc;
    }

    $bcc = mailer_clean_email($bcc);
    if ($bcc && filter_var($bcc, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Bcc: ' . $bcc;
    }

    if (count($attachments) > 0) {
        $boundary = '=_friendlogis_' . md5(uniqid(mt_rand(), true));
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
        $body = mailer_build_body($content, $type, $boundary, $attachments);
    } else {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: ' . ($type == 1 ? 'text/html' : 'text/plain') . '; charset=' . $charset;
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $body = $content;
    }

    $header_string = implode("\r\n", $headers);

    $old_sendmail_from = null;
    $is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    if ($is_windows && function_exists('ini_get')) {
        $old_sendmail_from = ini_get('sendmail_from');
        @ini_set('sendmail_from', $from_email);
    }

    if (!$is_windows) {
        $result = @mail($to, $subject, $body, $header_string, '-f' . $from_email);
    } else {
        $result = @mail($to, $subject, $body, $header_string);
    }

    if ($is_windows && $old_sendmail_from !== null) {
        @ini_set('sendmail_from', $old_sendmail_from);
    }

    return $result;
}

function mailer($fname, $fmail, $to, $subject, $content, $type = 0, $file = "", $cc = "", $bcc = "", $reply_to = "")
{
    global $config;

    if (empty($config['cf_email_use'])) {
        return false;
    }

    $to = mailer_clean_email($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $from_email = mailer_clean_email($fmail);
    if ($from_email === '' || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
        $from_email = mailer_default_from();
    }

    $from_name = mailer_clean_header_value($fname);
    if ($from_name === '') {
        $from_name = !empty($config['cf_admin_email_name']) ? $config['cf_admin_email_name'] : (!empty($config['cf_1']) ? $config['cf_1'] : 'FriendLogis');
    }

    $reply_to = mailer_clean_email($reply_to);
    if ($reply_to === '' && $from_email !== mailer_default_from()) {
        $reply_to = $from_email;
    }

    $subject = mailer_encode_subject($subject);
    $content = (string)$content;

    $result = mailer_send_via_mail($to, $subject, $content, $from_email, $from_name, $reply_to, $type, $cc, $bcc, $file);

    $page = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : 'unknown';
    $transport = mailer_transport_info();
    $log_entry = date('Y-m-d H:i:s')
        . ' | Page: ' . $page
        . ' | To: ' . $to
        . ' | From: ' . $from_email
        . ' | Reply-To: ' . ($reply_to ? $reply_to : 'none')
        . ' | Subject: ' . mailer_clean_header_value($subject)
        . ' | Result: ' . ($result ? 'SUCCESS' : 'FAILED')
        . ' | Mailer: mail'
        . ' | OS: ' . $transport['php_os']
        . ' | SMTP: ' . $transport['smtp']
        . ' | smtp_port: ' . $transport['smtp_port']
        . ' | sendmail_path: ' . $transport['sendmail_path']
        . "\n";
    mailer_log($log_entry);

    if (!$result) {
        error_log('[mailer] send failed: to=' . $to . ', from=' . $from_email . ', subject=' . substr(mailer_clean_header_value($subject), 0, 120));
    }

    return $result;
}

function attach_file($filename, $tmp_name)
{
    $dest_file = G5_DATA_PATH . '/tmp/' . str_replace('/', '_', $tmp_name);
    if (!is_uploaded_file($tmp_name)) {
        return false;
    }

    if (!move_uploaded_file($tmp_name, $dest_file)) {
        return false;
    }

    return array(
        'name' => $filename,
        'path' => $dest_file
    );
}
