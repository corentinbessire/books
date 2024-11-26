#!/bin/bash

# notify-slack.sh
set -e

# Validate required parameters
if [ "$#" -lt 5 ]; then
    echo "Usage: $0 SLACK_WEBHOOK_URL ACTION STATUS PROJECT_NAME ENVIRONMENT [PIPELINE_URL] [TRIGGERED_BY]"
    exit 1
fi

SLACK_WEBHOOK_URL=$1
ACTION=$2
STATUS=$3
PROJECT_NAME=$4
ENVIRONMENT=$5
PIPELINE_URL=${6:-"Not provided"}
TRIGGERED_BY=${7:-"System"}

# Set color based on status
case $STATUS in
    "started")
        COLOR="#0000ff"
        MESSAGE="*${ACTION} pipeline started*"
        ;;
    "success")
        COLOR="#00c100"
        MESSAGE="*${ACTION} pipeline succeeded*"
        ;;
    "failed")
        COLOR="#ff0909"
        MESSAGE="*${ACTION} pipeline failed*"
        ;;
    *)
        COLOR="#808080"
        MESSAGE="*Deployment status update*"
        ;;
esac

# Send notification to Slack
curl -X POST -H "Content-type: application/json" --data "{
    'attachments': [
        {
            'color': '${COLOR}',
            'blocks': [
                {
                    'type': 'section',
                    'text': {
                        'type': 'mrkdwn',
                        'text': \"${MESSAGE}\n\n*Project:* ${PROJECT_NAME}\n*Environment:* ${ENVIRONMENT}\n*Pipeline:* ${PIPELINE_URL}\n*Triggered by:* ${TRIGGERED_BY}\"
                    }
                }
            ]
        }
    ]
}" "$SLACK_WEBHOOK_URL"
