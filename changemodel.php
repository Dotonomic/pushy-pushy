<?php
    session_start();
    if ($_SESSION['model'] == 'gpt-4-1106-preview') $_SESSION['model'] = 'gpt-3.5-turbo-1106';
    else $_SESSION['model'] = 'gpt-4-1106-preview';
    echo $_SESSION['model'];
?>