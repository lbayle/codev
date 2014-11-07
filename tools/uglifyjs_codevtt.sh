#!/bin/bash

# sudo yum install nodejs npm
# npm install uglify-js
# ll ~/node_modules/uglify-js/bin/


DIR_CURRENT=$(pwd)

BIN_UGLIFYJS=~/node_modules/uglify-js/bin/uglifyjs

DIR_JS_FILES=/var/www/html/codevtt/js
DIR_MIN_JS_FILES=/var/www/html/codevtt/js_min


#### MAIN ######################################


for file in $DIR_JS_FILES/*.js
do
	js_file=$(basename $file)
	min_file="${js_file%.*}.min.js"
	echo "uglify $js_file -> $min_file"
	#echo "uglify $file -> ${DIR_MIN_JS_FILES}/$min_file"

	$BIN_UGLIFYJS $file > ${DIR_MIN_JS_FILES}/$min_file
done

