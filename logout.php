<?php
/**
 * Logout Page
 */

require_once 'auth.php';
require_once 'functions.php';

Auth::logout();
setFlashMessage('success', 'You have been logged out successfully.');
redirect('login.php');
