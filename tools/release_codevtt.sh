#!/bin/bash -x

ZIP_FILE=codevtt-master.zip
TMP_DIR=/tmp/build_codevtt
RELEASE_DATE=$(date +%Y%m%d)



# --------------
# check input params
CheckArgs ()
{
   echo " "

   # init variables
   doOVH="No"

   while [ "x" != "x$*" ]
   do
      case $1 in

         --ovh | --OVH | -ovh | -OVH )
            doOVH="Yes"
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
   echo "$0 v0.0.1"
   echo "syntax  :  $0 [options...]"
   echo ""
   echo "options:"
   echo "  --ovh   remove more things: install, tools, ..."
   echo " "
}


f_release()
{
  rm -rf doc/en
  rm -rf doc/fdj
  rm -rf doc/git_config
  rm -rf doc/mantis_config
  rm -rf doc/phpdoc
  rm -rf doc/screenshots
  rm -rf doc/TopCased
  rm -rf doc/fr/Archives
  find doc -name "*.od?" | xargs rm

  rm images/*.sumo
  rm images/*.psd
  rm images/*.xcf
  rm images/*.svg
  rm images/codevtt_logo_01.png
  rm images/codevtt_logo_02.png
  rm images/codevtt_logo_03_template.png
  rm images/codevtt_logo_03_halloween.png

  rm tools/create_fake_db.php
  rm tools/create_class_map.php
  rm tools/phpinfo.php
  rm tools/session.php
  rm tools/locale.php
  rm -rf tests

  find lib -name "*.zip" | xargs rm
  rm .gitignore
  rm .buildpath
  rm -rf nbproject

  # remove beta functionalities
  rm -rf blog
  rm -rf odt_templates

}

f_ovh()
{
   rm -rf tools
   rm -rf "install"
   
}

# ##########################
#   MAIN
# ##########################


CheckArgs $@

mkdir -p ${TMP_DIR}
cd ${TMP_DIR}
rm -rf ${ZIP_FILE}
wget -q -O ${ZIP_FILE} https://github.com/lbayle/codev/archive/master.zip

rm -rf codevtt_${RELEASE_DATE}
unzip -q ${ZIP_FILE}

mv codev-master codevtt_${RELEASE_DATE}
cd codevtt_${RELEASE_DATE}

f_release

# synchro des briques
if [ "Yes" = "$doOVH" ]
then
   f_ovh
fi

cd ..
tar cvzf codevtt_${RELEASE_DATE}.tgz codevtt_${RELEASE_DATE}

# END