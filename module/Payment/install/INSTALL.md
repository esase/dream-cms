# INSTALLATION
Install this module via the admin panel.

## CRON JOBS

1. Add this command into your cron jobs (it helps you remove all expired shopping cart items and not paid transactions):
    * */2 * * * /usr/bin/php -f /your_project_root/public/index.php payment clean expired items &> /dev/null

## PERMISSIONS

## APACHE SETTINGS

## PHP SETTINGS

