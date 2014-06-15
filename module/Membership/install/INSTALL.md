# INSTALLATION
Install this module via the admin panel.

## CRON JOBS

1. Add this command into your cron jobs (it helps you remove all expired membership levels connections):
    * */2 * * * /usr/bin/php -f /your_project_root/public/index.php membership clean expired connections &> /dev/null

## PERMISSIONS

## APACHE SETTINGS

## PHP SETTINGS

