#!/bin/sh

# WARNING: never push changes to origin !!!

# this script updates wrong emails in git repo
# to generate a clean codeswarm video

#./doc/codeswarm/change_emails.sh
#./doc/codeswarm/codevtt_codeswarm.sh

# EMAILS:
# lbayle.trash@gmail.com
# codevtt@srv-svn01.atos.net
# codevtt@localhost
# lob@pavilion.(none)
# codev.fdj@atos.net
# lbayle@codev.(none)

DIR_CODEVTT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../.." && pwd )"

cd $DIR_CODEVTT
git checkout master

git filter-branch -f --env-filter '
CORRECT_EMAIL="lbayle.work@gmail.com"
CORRECT_NAME="Louis BAYLE"

OLD_EMAIL1="lbayle.trash@gmail.com"
OLD_EMAIL2="codevtt@srv-svn01.atos.net"
OLD_EMAIL3="codevtt@localhost"
OLD_EMAIL4="lob@pavilion.(none)"
OLD_EMAIL5="codev.fdj@atos.net"
OLD_EMAIL6="lbayle@codev.(none)"

if [ "$GIT_COMMITTER_EMAIL" = "$OLD_EMAIL1" ] ||
   [ "$GIT_COMMITTER_EMAIL" = "$OLD_EMAIL2" ] ||
   [ "$GIT_COMMITTER_EMAIL" = "$OLD_EMAIL3" ] ||
   [ "$GIT_COMMITTER_EMAIL" = "$OLD_EMAIL4" ] ||
   [ "$GIT_COMMITTER_EMAIL" = "$OLD_EMAIL5" ] ||
   [ "$GIT_COMMITTER_EMAIL" = "$OLD_EMAIL6" ]
then
    export GIT_COMMITTER_NAME="$CORRECT_NAME"
    export GIT_COMMITTER_EMAIL="$CORRECT_EMAIL"
fi
if [ "$GIT_AUTHOR_EMAIL" = "$OLD_EMAIL1" ] ||
   [ "$GIT_AUTHOR_EMAIL" = "$OLD_EMAIL2" ] ||
   [ "$GIT_AUTHOR_EMAIL" = "$OLD_EMAIL3" ] ||
   [ "$GIT_AUTHOR_EMAIL" = "$OLD_EMAIL4" ] ||
   [ "$GIT_AUTHOR_EMAIL" = "$OLD_EMAIL5" ] ||
   [ "$GIT_AUTHOR_EMAIL" = "$OLD_EMAIL6" ]
then
    export GIT_AUTHOR_NAME="$CORRECT_NAME"
    export GIT_AUTHOR_EMAIL="$CORRECT_EMAIL"
fi
' --tag-name-filter cat -- --branches --tags

# clean git tree
git branch master_codeswarm
git reset --hard origin/master
git checkout master_codeswarm
git update-ref -d refs/original/refs/heads/master
git update-ref -d refs/original/refs/heads/master_codeswarm

