#!/bin/bash

chown -R www-data:www-data *
find -type d -exec chmod 777 {} +
find -type f -exec chmod 666 {} +
#chmod 755 -R plugin
find -name "*.php" -exec chmod 777 {} +
find -name ".ht*" -exec chmod 644 {} +
