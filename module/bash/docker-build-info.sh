#!/bin/bash

TG_TOKEN=$1
TG_CHAT_ID=$2
CONTAINER_NAME=$3
WORKFLOW_URL=$4
IMAGE_NAME=$5
STATUS=$6
curdate=$(TZ=Indonesia/Jakarta date)

function bot_template() {
curl -s -X POST https://api.telegram.org/$TG_TOKEN/sendMessage -d chat_id=$TG_CHAT_ID -d "parse_mode=HTML" -d text="$(
	for POST in "${@}";
		do
			echo "${POST}"
		done
	)"
}

# Telegram Bot Service || Compiling Message
function bot_message() {
	bot_template	"<b>|| HANA-CI Build Bot ||</b>" \
			"" \
			"<b> Github Workflow Start ! </b>" \
			"" \
			"============= Job Information ================" \
			"<b>Workflow :</b><code> $CONTAINER_NAME </code>" \
			"<b>Workflow Detail :</b><code> <a href='$WORKFLOW_URL'>$WORKFLOW_URL</a> </code>" \
			"<b>Workflow Environment :</b><code> Production </code>" \
			"<b>Workflow Status :</b><code> $STATUS </code>" \
			"<b>Workflow Step :</b><code> $IMAGE_NAME </code>" \
			"<b>Workflow Start At :</b><code> ${curdate} </code>" \
			""
}

bot_message
