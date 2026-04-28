FROM nginx:alpine
COPY . /usr/share/nginx/html

CMD sed -i 's/listen  80;/listen '80';/' /etc/nginx/conf.d/default.conf && nginx -g 'daemon off;'