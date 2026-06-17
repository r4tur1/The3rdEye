#!/bin/bash
# Cleanup script for The3rdEye v1.0
# Removes all unnecessary files, logs, and audit artifacts
# Made by r4tur1

R='\e[1;91m'
W='\e[1;97m'
G='\e[1;92m'
RESET='\e[0m'

printf "${R}[*] The3rdEye - Sanitizing Audit Artifacts...${RESET}\n"

# Remove log files
printf "${R}[+]${W} Removing log files...${RESET}\n"
rm -f .cloudflared.log
rm -f .tunnel.log
rm -f Log.log
rm -f LocationLog.log
rm -f LocationError.log

# Remove temporary location files
printf "${R}[+]${W} Removing temporary location files...${RESET}\n"
rm -f current_location.txt
rm -f current_location.bak
rm -f location_*.txt

# Remove captured images
printf "${R}[+]${W} Removing captured frames...${RESET}\n"
rm -f captured_*.png
rm -f cam*.png

# Remove temporary HTML/PHP files
printf "${R}[+]${W} Removing temporary payload files...${RESET}\n"
rm -f index.php
rm -f index2.html
rm -f index3.html

# Clean captures directory
printf "${R}[+]${W} Cleaning capture storage...${RESET}\n"
if [[ -d "$HOME/.the3rdeye/captures" ]]; then
    rm -rf "$HOME/.the3rdeye/captures/*"
fi

# Remove IP logs
printf "${R}[+]${W} Removing IP logs...${RESET}\n"
rm -f ip.txt
rm -f saved.ip.txt
if [[ -f "$HOME/.the3rdeye/saved_ips.txt" ]]; then
    rm -f "$HOME/.the3rdeye/saved_ips.txt"
fi

# Remove user agent logs
rm -f user_agent.txt

# Remove saved locations directory
printf "${R}[+]${W} Removing saved locations directory...${RESET}\n"
if [[ -d "saved_locations" ]]; then
    rm -rf saved_locations
fi

# Remove cloudflared binary if it exists
printf "${R}[+]${W} Removing tunnel binaries...${RESET}\n"
rm -f cloudflared

printf "${G}[✓] Cleanup completed successfully!${RESET}\n"
printf "${R}[*]${W} All audit artifacts have been sanitized.${RESET}\n"