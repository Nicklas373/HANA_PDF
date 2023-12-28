#!/bin/bash

curdate=$(date)

function bot_template() {
curl -s -X POST https://api.telegram.org/$1/sendMessage -d chat_id=$2 -d "parse_mode=HTML" -d text="$(
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
			"<b>Workflow :</b><code> $3 </code>" \
			"<b>Workflow Detail :</b><code><a href='$4'></a></code>" \
			"<b>Workflow Environment :</b><code> Production </code>" \
			"<b>Workflow Step :</b><code> $5 </code>" \
			"<b>Workflow Start At :</b><code> ${curdate} </code>" \
			""
}

bot_message
