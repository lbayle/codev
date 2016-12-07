#!/bin/bash

DIR_CODEVTT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
PRJ_NAME=codev

#SRC_FILE="index.php"
SRC_FILE="index.php login.php"

LOCALE="fr"

# --------------
# check input params
CheckArgs ()
{
   echo " "

   # init variables
   doNew="No"
   doCompile="No"
   doTemplate="Yes"
   doAllLocales="Yes"

   while [ "x" != "x$*" ]
   do
      case $1 in

         --locale | -l )
            shift
            LOCALE="$1"
            doAllLocales="No"
            ;;

         --compile | -c )
            doCompile="Yes"
            ;;

         --help | -h )
            DisplayHelp
            exit 0
            ;;

         * )
            echo "WARNING: Unknown arg '$1' (skipped)"
        ;;
      esac
      shift
   done
}

DisplayHelp()
{
   echo "v0.1.0"
   echo "syntax  :  $0 [options...]"
   echo ""
   echo "options:"
   echo "  --template         Generate .po template file"
   echo "  --compile          Compile .po template file to .mo"
   echo "  --locale <locale>  Select Locale to update (fr, de, en, ...)"
   echo " <basename>"
   echo " "
}

# generate $SRC_FILES
f_genFileList ()
{
  local dirList=$1
  local FILE_LIST=.fileList.txt

  echo "" > $FILE_LIST # clear

  # Generate smarty i18n template
  php ${DIR_CODEVTT}/i18n/tsmarty2c.php > ${DIR_CODEVTT}/i18n/locale/smarty.c
  echo "i18n/locale/smarty.c" >> $FILE_LIST

  #for i in $dirList
  for i in admin classes doc filters graphs import indicator_plugins install management reports tests timetracking tools plugins $(ls *.php)
  do
    find "$i" -iname "*.php" -print0 | while IFS= read -rd $'\0' f
    do
      echo "$f" >> $FILE_LIST
    done
  done

  while read filename
  do
    SRC_FILES="$SRC_FILES $filename"
  done < $FILE_LIST
  
  echo "  - nb php files found: $(cat $FILE_LIST | wc -l )"

  rm $FILE_LIST
}

# usage: f_createTemplateFile <locale>
f_createTemplateFile ()
{
  local mergedPoFile=".${PRJ_NAME}.po.merge_${LOCALE}"
  local templatePoFile="${DIR_CODEVTT}/i18n/locale/codevtt.pot"

  rm -f ${mergedPoFile}
  rm -f ${templatePoFile}
  
  echo "  - parse php files and create template"

  #xgettext -kT_ngettext:1,2 -kT_gettext -kT_ -k_ -L PHP -o ${templatePoFile} $SRC_FILES
  xgettext --add-comments -kT_ngettext:1,2 -kT_gettext -kT_ -k_ -o ${templatePoFile} $SRC_FILES
 
  # Remove "smarty.c" comments
  mv ${templatePoFile} ${templatePoFile}.old
  cat ${templatePoFile}.old | grep -v "smarty.c" > ${templatePoFile}
  rm ${templatePoFile}.old

  if [ ! -f ${templatePoFile} ]  
  then
    echo "ERROR: template file ${templatePoFile} not found !"
    exit 1
  else
    sed -i s/charset=CHARSET/charset=UTF-8/g ${templatePoFile}
    sed -i "s/Language: /Language: ${LOCALE}/g" ${templatePoFile}
    
    if [ -f ${FILE_PO} ]
    then
      sed -i s/charset=CHARSET/charset=UTF-8/g ${FILE_PO}

      echo "  - merge with existing ${FILE_PO}"
      msgmerge --no-wrap -o ${mergedPoFile} ${FILE_PO} ${templatePoFile}
      retCode=$?
      if [ 0 -ne $retCode ]
      then
        echo "ERROR $retCode: merge failed !"
        exit 1
      fi
      mv ${mergedPoFile} ${FILE_PO}
      retCode=$?
      if [ 0 -ne $retCode ]
      then
        echo "ERROR $retCode: mv ${mergedPoFile} ${FILE_PO}"
        exit 1
      fi
      #rm -f ${templatePoFile}
    else
      cp ${templatePoFile} ${FILE_PO}
    fi
    sed -i 's|#. tpl|#: tpl|g' ${FILE_PO}

  fi
}

f_compileTemplateFile ()
{
   #echo "  - compile to ${FILE_PO}"
   msgfmt --statistics -o ${FILE_MO} ${FILE_PO}
}

# do the work for a locale
# $LOCALE must be set
function f_exec {

  DIR_LOCALE="${DIR_CODEVTT}/i18n/locale/${LOCALE}/LC_MESSAGES"
  mkdir -p "$DIR_LOCALE"

  FILE_PO="${DIR_LOCALE}/${PRJ_NAME}.po"
  FILE_MO="${DIR_LOCALE}/${PRJ_NAME}.mo"

  if [ "Yes" == "$doTemplate" ]; then
    f_createTemplateFile
    #echo "READY."
  fi

  if [ "Yes" == "$doCompile" ]
  then
    f_compileTemplateFile
    #echo "  - Locale file generated: $FILE_MO"
  fi
}

# ###############################
# MAIN
# ###############################

# get the name of the HOST if exists in command line argument
CheckArgs $@

cd ${DIR_CODEVTT}
f_genFileList $DIR_LIST

# if locale is not defined, compute all locales
if [ "No" == "${doAllLocales}" ] ; then
  echo "DEBUG: only ${LOCALE}"
  f_exec
else
  # for each locale
  DIR_ALL_LOCALES=${DIR_CODEVTT}/i18n/locale
  for myDir in $(find ${DIR_ALL_LOCALES} -mindepth 1 -maxdepth 1 -type d); do
     LOCALE=$(basename $myDir)
     if [ 'qqq' != "${LOCALE}" ]; then
       echo "--- ${LOCALE} ---"
       f_exec
       doTemplate="Yes";
       #echo " "
     fi
  done
fi

if [ "Yes" != "$doCompile" ] ; then
  echo " "
  echo "  - Now edit .po file(s) and run script again with option --compile"
else
  rm ${DIR_CODEVTT}/i18n/locale/smarty.c
fi


