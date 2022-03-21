#!/usr/bin/env bash
if [ $# -gt 0 ];then
    exec gosu $WWWUSER "$@"
else
    # sed -i '/disable ghostscript format types/,+6d' /etc/ImageMagick-6/policy.xml
   
    exec docker-php-entrypoint apache2-foreground
    
    /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi