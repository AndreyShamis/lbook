#!/bin/bash -x

# Script for manual trigger autotest
# Script used for run many jobs on many controller, when each result will be pushed to logbook system
RND_T1=$(( ( RANDOM % 99999 )  + 1 ))
RND_T2=$(( ( RANDOM % 99999 )  + 1 ))
token=$(date '+%d%m%H%M%S')${RND_T1}_${RND_T2}                  # logBook token          / Used in logBook
LOGBOOK_DOM="127.0.0.1"                                 # logBook domain        / Used in logBook
LOGBOOK_URL="http://"${LOGBOOK_DOM}":8080/upload/new_cli"            # Link for upload logs  / Used in logBook
SETUP_NAME="$(hostname | awk '{print toupper($0)}')"
SERVER_CMD="server/autotest-remote"
CMD_ARGS=" —verbose -t "

# Configurable variables
CYCLE_NAME="Uploader Cycle"


uploadLog(){
        TEST_RES_DIR=$1
        file_path=${TEST_RES_DIR}"/debug/autoserv.DEBUG"
        fileToUpload=@"${file_path}"
        set -x
        curl --max-time 180 --noproxy ${LOGBOOK_DOM} \
        --form "file=${fileToUpload}" \
        --form "token=${token}" \
        --form "cycle=${CYCLE_NAME}" \
        --form "setup=${SETUP_NAME}" ${LOGBOOK_URL} | tee wlog_res.log

        set +x
        echo "#######################################################################################"
        echo ""
        echo "#######################################################################################"
}

server_run(){
        LOG_FOLDER=results/$(date '+%Y_%m_%d_%H-%M-%S')
        CONTROL_FILE=$1
        ${SERVER_CMD} ${CMD_ARGS} -s ${CONTROL_FILE} -S ${SETUP_CONF} -r ${LOG_FOLDER}
        uploadLog ${LOG_FOLDER}
        sleep 2
}

for i in {1..500}; do
        echo " ### Start iteration ${i} ###"
        #sleep 3
        #server_run "control"
        uploadLog "results-01-network_WiFi_BluetoothStreamPerf.11b"
done