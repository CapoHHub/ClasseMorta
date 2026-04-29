FROM nginx:alpine
COPY . /usr/share/nginx/html
COPY default.conf /etc/nginx/conf.d/default.conf

CMD ["sh", "-c", "sed -i \"s/listen  80;/listen 80;/\" /etc/nginx/conf.d/default.conf && exec nginx -g 'daemon off;'"]
