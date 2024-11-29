#!/bin/bash

CLASS=W
PROCESS=4

sudo lampp restart
sleep 1
SQL="truncate table VC.allreduce_file"
MYSQL="/opt/lampp/bin/mysql"
EX_FILE="/opt/lampp/mysql.conf"
echo $SQL | $MYSQL --defaults-extra-file=$EX_FILE

# HOST_FILE="--hostfile /home/vc/share/hostfiles/host_4"
EXE="../bin/ep.$CLASS.x"
# mpirun -np $PROCESS $HOST_FILE $EXE | tee log_w_$PROCESS.txt
mpirun -np $PROCESS $EXE | tee log_w_$PROCESS.txt

DIR="$CLASS/w$PROCESS"
mkdir $DIR
mkdir $DIR/comm
mv log_* $DIR
mv /opt/lampp/htdocs/files/* $DIR/comm
