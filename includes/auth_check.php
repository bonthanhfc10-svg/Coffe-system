<?php
if (!isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}
