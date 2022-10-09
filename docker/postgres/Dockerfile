FROM postgres:14

# Install tools required for build stage
RUN apt-get update && apt-get install -fyqq locales locales-all && rm -rf /var/lib/apt/lists/* \
    && localedef -i en_US -c -f UTF-8 -A /usr/share/locale/locale.alias en_US.UTF-8 \
	&& locale-gen en_US.UTF-8 \
    && locale-gen ru_RU.UTF-8 \
	&& locale-gen ru_RU.CP1251 \
	mc bash curl wget rsync \
    npm nmap net-tools tcpdump sudo nano

RUN mkdir -p /var/run/postgresql && chown -R postgres /var/run/postgresql && chown -R postgres /var/lib/postgresql

# Create usermod && groupmod
#RUN usermod -u 1000 node \
#    && groupmod -g 1000 node \
#    && usermod -aG sudo,www-data,plugdev node
#RUN usermod -aG sudo,www-data,plugdev postgres
RUN usermod -u 1000 postgres \
    && groupmod -g 1000 postgres \
    && usermod -aG sudo,www-data,plugdev postgres


RUN echo "root:r12345" | chpasswd \
    && echo "postgres:u12345" | chpasswd

# Copy existing application directory permissions
#RUN chown node:node /home/node
#COPY --chown=node:node . /home/node/www

#USER postgres

EXPOSE 5432
CMD ["postgres"]