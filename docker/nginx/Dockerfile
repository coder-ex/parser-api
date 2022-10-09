FROM nginx

# Install tools required for build stage
RUN apt-get update && apt-get install -fyqq \
    nmap net-tools tcpdump sudo nano mc

# Create usermod && groupmod
RUN usermod -aG www-data,plugdev nginx

#ADD ./default.conf /etc/nginx/conf.d/default.conf

#ADD ./lp.conf /etc/nginx/conf.d/lp.conf
ADD ./school.conf /etc/nginx/conf.d/school.conf

WORKDIR /var/www/html
