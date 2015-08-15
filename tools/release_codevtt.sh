#!/bin/bash

DIR_PREV=$(pwd)

TMP_DIR=/tmp/codevtt_release
RELEASE_DATE=$(date +%Y%m%d)

GIT_BRANCH="master"
GIT_REPO=$(git rev-parse --show-toplevel)

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

         --branch | -b )
	        shift
            GIT_BRANCH="$1"
            ;;

         --repo | -r )
	        shift
            GIT_REPO="$1"
            ;;

         --ovh | --OVH | -ovh | -OVH )
            doOVH="Yes"
            ;;

         --help | -h )
            f_displayHelp
            exit 0
            ;;

         * )
            echo "WARNING: Unknown arg '$1' (skipped)"
            ;;
      esac
      shift
   done

   if [ ! -d "$GIT_REPO/.git" ]; then
     echo "ERROR: invalid GIT REPOSITORY: $GIT_REPO"
     exit 1;
   fi

   git rev-parse --verify ${GIT_BRANCH} 2>&1 >> /dev/null
   if [ 0 -ne $? ] ; then
     echo "ERROR: invalid GIT BRANCH: $GIT_BRANCH"
     exit 2;
   fi  
}

f_displayHelp()
{
   echo "$0 v0.0.1"
   echo "syntax  :  $0 [options...]"
   echo ""
   echo "options:"
   echo "  --branch <branch>     git branch (or TAG, or SHA1) to release  (default=master)"
   echo "  --repo   <directory>  git repository location                  (default=current dir)"
   echo "  --ovh                 remove more things: install, tools, ..."
   echo " "
}


f_release()
{
  echo "remove DOC files..."
  rm -rf ${DIR_RELEASE}/doc/apache
  rm -rf ${DIR_RELEASE}/doc/architecture
  rm -rf ${DIR_RELEASE}/doc/en
  rm -rf ${DIR_RELEASE}/doc/fr/Archives
  rm -rf ${DIR_RELEASE}/doc/fdj
  rm -rf ${DIR_RELEASE}/doc/git_config
  rm -rf ${DIR_RELEASE}/doc/mantis_config/*.sql
  rm -rf ${DIR_RELEASE}/doc/phpdoc
  rm -rf ${DIR_RELEASE}/doc/screenshots
  find   ${DIR_RELEASE}/doc -name "*.od?" | xargs rm
  rm     ${DIR_RELEASE}/doc/codeswarm_codevtt.config

  echo "remove IMAGES files..."
  rm ${DIR_RELEASE}/images/*.sumo
  rm ${DIR_RELEASE}/images/*.psd
  rm ${DIR_RELEASE}/images/*.xcf
  rm ${DIR_RELEASE}/images/*.svg
  rm ${DIR_RELEASE}/images/codevtt_logo_01.png
  rm ${DIR_RELEASE}/images/codevtt_logo_02.png
  rm ${DIR_RELEASE}/images/codevtt_logo_03_template.png

  echo "remove TOOLS files..."
#  rm ${DIR_RELEASE}/tools/create_class_map.php
  rm ${DIR_RELEASE}/tools/create_fake_db.php
  rm ${DIR_RELEASE}/tools/release_codevtt.sh
  rm ${DIR_RELEASE}/tools/uglifyjs_codevtt.sh
  rm ${DIR_RELEASE}/tools/phpinfo.php
  rm ${DIR_RELEASE}/tools/session.php
  rm ${DIR_RELEASE}/tools/locale.php
  rm -rf ${DIR_RELEASE}/tests

  echo "remove LIBS files..."
  rm -rf ${DIR_RELEASE}/lib/odtphp/tests
  find ${DIR_RELEASE}/lib -name "*.zip" | xargs rm


  echo "remove GIT files..."
  rm -rf ${DIR_RELEASE}/.git
  rm ${DIR_RELEASE}/.gitignore
  rm ${DIR_RELEASE}/.buildpath
  rm -rf ${DIR_RELEASE}/nbproject

  # remove beta functionalities
  rm -rf ${DIR_RELEASE}/blog
  rm "${DIR_RELEASE}/classes/blog_manager.class.php"
  rm "${DIR_RELEASE}/classes/blogpost_cache.class.php"

}

f_ovh()
{
   echo "remove OVH specific files..."
   rm -rf ${DIR_RELEASE}/tools
   rm -rf ${DIR_RELEASE}/install
}

# ##########################
#   MAIN
# ##########################


CheckArgs $@

DIR_RELEASE=${TMP_DIR}/codevtt_v${GIT_BRANCH}
ZIP_FILE="codevtt_v${GIT_BRANCH}_${RELEASE_DATE}.zip"
TGZ_FILE="codevtt_v${GIT_BRANCH}_${RELEASE_DATE}.tgz"

mkdir -p ${TMP_DIR}
if [ ! -d "${TMP_DIR}" ]; then
  echo "ERROR: Could not create release directory: ${TMP_DIR}"
  exit 3;
fi

# make a copy of the git repo
cd ${TMP_DIR}
rm -rf ${DIR_RELEASE}
git clone ${GIT_REPO} codevtt_v${GIT_BRANCH}
cd ${DIR_RELEASE}
echo "git checkout ${GIT_BRANCH}"
git checkout -q ${GIT_BRANCH} 2>&1 > /dev/null
retCode=$?
if [ 0 -ne $retCode ] ; then
 echo "ERROR $retCode : git checkout ${GIT_BRANCH} failed !"
 exit 4;
fi  

# cleanup 
f_release

# synchro des briques
if [ "Yes" = "$doOVH" ]
then
   f_ovh
fi

# create archive
echo "create ZIP file: ${TMP_DIR}/${ZIP_FILE}"
cd ${TMP_DIR}
if [ -f ${TMP_DIR}/${ZIP_FILE} ] ; then rm ${TMP_DIR}/${ZIP_FILE} ; fi
zip -r ${ZIP_FILE} codevtt_v${GIT_BRANCH} 2>&1 > /dev/null
retCode=$?
if [ 0 -ne $retCode ] ; then
 echo "ERROR $retCode : ZIP failed !"
 exit 5;
fi  

cd $DIR_PREV

echo "done."
# END
