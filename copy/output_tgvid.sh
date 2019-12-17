#! /bin/bash

if [ $# == 1 ]; then

# output tgvid list with offset number
php /root/numbering_tgvid/script/10_exportAll.php -s $1

elif [ $# == 0 ]; then

# output tgvid list
php /root/numbering_tgvid/script/10_exportAll.php

fi
