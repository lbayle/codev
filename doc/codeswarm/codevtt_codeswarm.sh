#!/bin/bash

#DIR_CODEVTT="/var/www/html/prjmngt/codevtt"
DIR_CODEVTT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../.." && pwd )"
DIR_CODESWARM="/home/lob/Src/code_swarm"

FILE_CFG="$DIR_CODEVTT/doc/codeswarm/codevtt_codeswarm.config"
FILE_AVI="codevtt_swarm_$(date '+%Y%m%d').avi"

echo "extract CodevTT activity from local git repo..."
cd $DIR_CODEVTT

git log --name-status --pretty=format:'%n------------------------------------------------------------------------%nr%h | %ae | %ai (%aD) | x lines%nChanged paths:' > $DIR_CODESWARM/data/codevtt_activity.log

# convert to XML for CodeSwarm
cd $DIR_CODESWARM
python $DIR_CODESWARM/bin/convert_logs.py \
    -g $DIR_CODESWARM/data/codevtt_activity.log -o $DIR_CODESWARM/data/codevtt_activity.xml

  # create a new config that points to the correct input XML and saves snapshots
  #sed s/sample-repevents.xml/codevtt_activity.xml/ data/sample.config \
  #      | sed -i s/TakeSnapshots=false/TakeSnapshots=true/ > data/project.config


echo "rm old frames..."
cd $DIR_CODESWARM
mkdir -p $DIR_CODESWARM/frames
rm -rf $DIR_CODESWARM/frames/*.png

echo "run codeswarm to create frames..."
./run.sh $FILE_CFG

echo "stitch frames together into an AVI file..."
cd $DIR_CODESWARM/frames
mencoder "mf://*.png" -mf fps=25 -ovc x264 -oac copy -o $FILE_AVI
