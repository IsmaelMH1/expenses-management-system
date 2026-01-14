<?php
if (session_status() === PHP_SESSION_NONE) {

  
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
  ]);

  session_start();
}

function require_login(){
  if (empty($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
  }
}
