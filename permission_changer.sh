#!/bin/bash

chmod 755 pukiwiki-1.5.4_utf8
cd pukiwiki-1.5.4_utf8
chmod 777 attach backup cache counter diff wiki
chmod 755 image image/face lib plugin plugin/vendor \
	plugin/vendor/erusev plugin/vendor/erusev/parsedown \
	plugin/vendor/composer skin
chmod 644 .ht* */.htaccess *.php */*.php */*/*.php \
	*/*/*/*.php */*/*/*/*.php
chmod 666 attach/* backup/*.gz backup/*.txt cache/* \
	counter/* diff/*.txt wiki/*.txt image/* \
	image/face/* lib/* plugin/* skin/*
cd -
