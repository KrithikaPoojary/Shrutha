<?php
session_start();
require_once 'config.php';

function generateCaptcha() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $captcha = substr(str_shuffle($chars), 0, 5);
    $_SESSION['captcha'] = $captcha;
    return $captcha;
}

// Generate new CAPTCHA
$newCaptcha = generateCaptcha();
echo $newCaptcha;
?>