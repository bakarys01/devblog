# --------------- DÉBUT COUCHE OS -------------------
FROM debian:stable-slim
# --------------- FIN COUCHE OS ---------------------

    # MÉTADONNÉES DE L'IMAGE
LABEL version="1.0" maintainer="SIDIBE Bakary <ajdaini.hatim@gmail.com>"

# VARIABLES TEMPORAIRES
ARG APT_FLAGS="-q -y"
ARG DOCUMENTROOT="/var/www/html"


# --------------- DÉBUT COUCHE APACHE ---------------
RUN apt-get update -y && \
    apt-get install ${APT_FLAGS} apache2
# --------------- FIN COUCHE APACHE -----------------


# --------------- DÉBUT COUCHE MYSQL ----------------
RUN apt-get install ${APT_FLAGS} mariadb-server

# Configuration MySQL/MariaDB
RUN service mariadb start && \
    mariadb -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '';" && \
    mariadb -e "FLUSH PRIVILEGES;"

COPY db/articles.sql /
# --------------- FIN COUCHE MYSQL ------------------



# --------------- DÉBUT COUCHE PHP ------------------
RUN apt-get install ${APT_FLAGS} \
    php-mysql \
    php && \
    rm -f ${DOCUMENTROOT}/index.html && \
    apt-get autoclean -y

COPY app ${DOCUMENTROOT}
# --------------- FIN COUCHE PHP --------------------

# OUVERTURE DU PORT HTTP
EXPOSE 80

# RÉPERTOIRE DE TRAVAIL
WORKDIR  ${DOCUMENTROOT}

# DÉMARRAGE DES SERVICES LORS DE L'EXÉCUTION DE L'IMAGE
ENTRYPOINT service mariadb start && mariadb < /articles.sql && apache2ctl -D FOREGROUND