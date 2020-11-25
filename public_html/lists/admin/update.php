<?php
/**
 * Only superusers can be redirected to updater.
 */
if(isSuperUser() && ALLOW_UPDATER) {
    $_SESSION['phplist_updater_eligible'] = true;
    header('Location: ../updater');
}