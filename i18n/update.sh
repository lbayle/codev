#!/bin/sh

PRJ_NAME=codev

#SRC_FILE="index.php"
SRC_FILE="index.php login.php"

TEMPLATE_FILE=codev.po.template

echo "  - parse $SRC_FILE and create template: $TEMPLATE_FILE"
#xgettext -kT_ngettext:1,2 -kT_gettext -kT_ -k_ -L PHP -o $TEMPLATE_FILE $SRC_FILE
xgettext -kT_ngettext:1,2 -kT_gettext -kT_ -k_ -o $TEMPLATE_FILE $SRC_FILE

if [ "x$1" = "x--new" ]; then
    echo "  - compile $TEMPLATE_FILE to ${PRJ_NAME}.mo"
    msgfmt --statistics -o ${PRJ_NAME}.mo $TEMPLATE_FILE
else
    if [ -f $PRJ_NAME.po ]; then
    
       echo "  - merge $TEMPLATE_FILE with ${PRJ_NAME}.po"
	   msgmerge -o .${PRJ_NAME}.po.tmp ${PRJ_NAME}.po $TEMPLATE_FILE
	   mv .${PRJ_NAME}.po.tmp $PRJ_NAME.po
    
       echo "  - compile to ${PRJ_NAME}.mo"
	   msgfmt --statistics -o ${PRJ_NAME}.mo ${PRJ_NAME}.po
    else
	   echo "Usage: $0 --new           Create new file (WARN: delete previous translations)"
	   echo "Usage: $0 <basename>"
    fi
fi
