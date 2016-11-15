#!/bin/bash

# on CentOS 7, install epel repo, then:
# yum install uglify-js

# or:
# sudo yum install nodejs npm
# npm install uglify-js
# ll ~/node_modules/uglify-js/bin/

DIR_CODEVTT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
DIR_CURRENT=$(pwd)

#BIN_UGLIFYJS=~/node_modules/uglify-js/bin/uglifyjs
#BIN_UGLIFYJS=/usr/bin/uglifyjs
BIN_UGLIFYJS=uglifyjs

DIR_JS_FILES=${DIR_CODEVTT}/js
DIR_MIN_JS_FILES=${DIR_CODEVTT}/js_min


#### MAIN ######################################


for file in $DIR_JS_FILES/*.js
do
	js_file=$(basename $file)
	min_file="${js_file%.*}.min.js"
	echo "uglify $js_file -> $min_file"
	#echo "uglify $file -> ${DIR_MIN_JS_FILES}/$min_file"

	$BIN_UGLIFYJS $file > ${DIR_MIN_JS_FILES}/$min_file
done

