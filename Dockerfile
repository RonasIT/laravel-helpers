FROM webdevops/php-nginx:7.4

RUN wget -O "/usr/local/bin/go-replace" "https://github.com/webdevops/goreplace/releases/download/1.1.2/gr-arm64-linux" \
    && chmod +x "/usr/local/bin/go-replace" \
    && "/usr/local/bin/go-replace" --version
