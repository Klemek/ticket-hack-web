
# Documentation Infrastructure Ticket'Hack

Ce document rend compte de l'ensemble des éléments installés sur le serveur du projet Ticket'Hack.
Le serveur est une raspberry pi 2 accueillant un OS raspbian stretch. Pour cause de réalisation et de spécialisation pour cette OS, l'infrastructure mise en place est la première proposée pour les projets S7. Le serveur accueil l'ensemble des services : Apache, php, postgresql et phppgadmin. Le serveur est mis à jour automatiquement depuis le github du projet (https://github.com/Klemek/Ticket-Hack-Web)
La connexion est en HTTPS et est sécurisée par https://certbot.eff.org/.
Dans ce document, on ne considère pas la mise en place de raspbian sur la raspberry pi 2, ni la mise en place du service ssh pour la connexion distante.
L'ensemble des commandes explicitées par la suite sont à faire une fois connecté par ssh.
Le nom de domaine du serveur que nous avons utilisé est : kalioz.fr

## Sommaire
1. Apache
2. PHP, Postgresql et phppgadmin
3. GitHub et Cron
4. Certbot
5. Sécurités supplémentaires

### 1. Apache

L'installation de Apache sur le serveur passe par les lignes de commande suivantes : 
`apt-get update` puis `apt-get upgrade` et enfin `apt-get install apache2`.

La configuration du serveur à été modifiée au fur et à mesure des ajouts sur le serveur.
Au début, le fichier 000-default.conf est utilisé pour configurer le serveur, les options par défaut ne sont pas modifiées (répertoire utilisé et log), le ServeurName est mis à kalioz.fr (nom de domaine du projet).

Un certificat ssl auto-généré a également été mis en place avant le certificat officiel.
Pour utiliser ce certificat auto généré, on a ajouté les lignes suivantes dans le fichier 000-default.conf .
```
<VirtualHost *:443>
	  DocumentRoot /var/www/html
	  ServerName kalioz.fr
	  ServerSignature Off
	  ErrorLog ${APACHE_LOG_DIR}/error_ticket_hack.log
	  LogLevel info
	  CustomLog ${APACHE_LOG_DIR}/access_ticket_hack.log combinedSSL
	  Engine on
	  SSLCertificateFile /etc/ssl/certs/certificat_ticket_hack.crt
	  SSLCertificateKeyFile /etc/ssl/private/cle_ticket_hack.key
  </VirtualHost>
```
### 2. PHP, Postgresql et phppgadmin

L'installation de php sur le serveur se fait par : 
```
apt-get install php
apt-get install phppgadmin
apt-get install postgresql
```

Pour activer les redirections entre les pages php, il faut taper la commande suivante `a2enmod rewrite`.
On redémarre le serveur `service apache2 restart`.
Puis il faut ajouter dans le fichier de configuration du serveur : 
```
<Directory /var/www/html>
    Options Indexes FollowSymLinks MultiViews
    AllowOverride All
	Require all granted
</Directory>
```
Le fichier par défaut peut aussi bien être 000-default.conf pour notre certificat auto-généré que default-ssl.conf pour le certificat généré par certbot.

Par défaut, on ne change pas le répertoire de données de l'application.

Pour mettre en fonctionnement la base de données, on ajoute tout d'abord un utilisateur superuser à la main. (méthode longue).
```
su - postgres
createuser admin --superuser
psql
\password admin
********
********
\q
```
Une autre méthode pour ajouter cette utilisateur. (méthode courte). 
```
createuser admin -W
*******
*******
```
Dans cette méthode, le mot de passe est demandé automatiquement.

Avec cet utilisateur, on peut ensuite ajouter l'ensemble des tables de l'application par l'interface graphique.
On se rend donc sur la page de l'application kalioz.fr/phppgadmin
pour ajouter les tables avec l'utilisateur super admin fraîchement crée.
Dans notre cas, l'ensemble des commandes sql sont également dans le répertoire github de l'application.

Par la suite, on restreint les accès à la base de données pour éviter plusieurs problèmes.
Pour ce faire, on tape la commande suivante :
```
nano /etc/postgresql/9.6/main/pg_hba.conf
```
Puis on ajoute les configurations :
```
local   all             all                                     md5
host    all             all             127.0.0.1/32            md5
host    all             all             ::1/128                 md5
host    all             all             0.0.0.0/0               md5
host    all             php             0.0.0.0/0               reject
```
La seule connexion refusée est celle de l'utilisateur php qui est en fait une porte d'entrée pour l'Api. Avec le reject, tous accès par le navigateur à la base de données, lui est interdit.

### 3. GitHub et Cron

Pour simplifier le déploiement de l'application web, plusieurs éléments ont été mis en place sur le serveur.
Première, git a été installé sur le serveur `apt-get install git`.
Une fois le téléchargement réalisé, on se rend dans le dossier de configuration web défini précédement.
Ici on réalise une première récupération du projet depuis git et supprimant l'ancien répertoire par défaut d'apache.
``` 
cd /var/www/
rm -R html/
mkdir html
cd html/
git clone https://github.com/Klemek/Ticket-Hack-Web ./
```
Désormais, le projet par défaut est le projet Ticket'Hack.

Une fois la première récupération effectuée, il suffit désormais de mettre à jour le projet depuis le répertoire github.
Pour ce faire, on peut utiliser les commandes suivantes : 
```
cd /var/www/html
git pull https://github.com/Klemek/Ticket-Hack-Web
```
Le projet est mis à jour depuis la branche master du répertoire github.

Par soucis pratique, le serveur contient également un appel automatique pour mettre à jour l'application web. Cet appel est réalisé depuis cron. Pour faire cette appel, il faut définir un fichier exécutable qui contient toutes les informations de mise à jour et il faut également définir la routine.
Pour la routine :
```
crontab -e
```
Une fois dans le fichier d’événement, on ajoute la ligne suivante : 
```
*/1 * * * * /var/www/cron_pull_git_projet_s7.sh
```
Ensuite, on construit le fichier qui contient les commandes.
```
cd /var/wwww/
nano cron_pull_git_projet_s7.sh
```
Puis on entre les commandes dans ce fichier :
```
#!/bin/bash
cd /var/www/html
git pull https://github.com/Klemek/Ticket-Hack-Web
```
Pour finir, on ajoute les droits exécutables au fichier :
```
chmod +x cron_pull_git_projet_s7.sh
```
Pour notre projet, nous avons également ajouté un nettoyage de la base de données. Dans cron, comme fait précédemment, on ajoute la ligne suivante :
```
0 */1 * * * /var/www/cron_clean_database.sh
```
On crée le fichier, on ajoute les commandes suivantes :
```
#!/bin/bash
export PGPASSWORD="***********"
psql -U admin -d postgres -w -a -f /var/www/html/sql/clean.sql
```
Le mot de passe par défaut PGPASSWORD est celui de l'administrateur du serveur postgresql. Ainsi, le serveur fait un nettoyage de certaines tables spécifiées dans le fichier clean.sql du projet récupéré depuis github. Pour finir on ajoute les droits.

### 4. Certbot

Pour ajouter certbot sur le serveur on tape les lignes de commandes suivantes :
```
apt-get install certbot
```
Puis on demande un certificat par la ligne de commande suivante :
```
certbot run -a webroot -i apache -w /var/www/html -d kalioz.fr
```
On lui précise le type de serveur web, le répertoire et le nom de domaine.
Le serveur demande quel fichier utiliser par défaut et on lui précise un ancien fichier (000-default.conf) ou comme dans notre cas, on lui précise d'utiliser un autre fichier : default-ssl.conf.
Une fois le certificat et ça clé créée, on se rend dans le fichier de configuration
On tape les lignes suivantes : 
`nano /etc/apache2/site-availables/default-ssl.conf` 
On ajoute le nom du serveur ainsi que les lignes d'autorisations des redirections.

On ajoute également une redirection du http sur le https.
Notre fichier sans les commentaires générés par certbot et avec nos modifications vaut alors :
```
ServerSignature Off
ServerTokens Prod
ServerName kalioz.fr

<IfModule mod_ssl.c>
        <VirtualHost *:80>
                Redirect permanent / https://kalioz.fr/
        </VirtualHost>

        <VirtualHost _default_:443>
                <Directory /var/www/html>
                        Options -Indexes +FollowSymLinks +MultiViews
                        AllowOverride All
                        Require all granted
                        LimitRequestBody 512000
                </Directory>
                DocumentRoot /var/www/html

                RewriteEngine On
                RewriteCond %{HTTPS} off
                RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI}

                ErrorLog ${APACHE_LOG_DIR}/error.log
                CustomLog ${APACHE_LOG_DIR}/access.log combined

                SSLEngine on

                SSLCertificateFile      /etc/letsencrypt/live/kalioz.fr/fullchain.pem
                SSLCertificateKeyFile /etc/letsencrypt/live/kalioz.fr/privkey.pem

                <FilesMatch "\.(cgi|shtml|phtml|php)$">
                                SSLOptions +StdEnvVars
                </FilesMatch>
                <Directory /usr/lib/cgi-bin>
                                SSLOptions +StdEnvVars
                </Directory>

        </VirtualHost>
</IfModule>

# vim: syntax=apache ts=4 sw=4 sts=4 sr noet
```

### 5. Sécurités supplémentaires

Des règles supplémentaires de sécurités ont été mises en place sur le serveur.
Les bonnes pratiques et idées ont été appliquées suivant la liste de ce site : https://www.tecmint.com/apache-security-tips/

Les points suivants ont été mis en place :
- Cacher le numéro de version du serveur. (1°)
```
nano /etc/apache2/apache2.conf
```
On ajoute les lignes suivantes :
```
ServerSignature Off
ServerTokens Prod
```
- Désactiver l'affichage de l'ensemble des fichiers présents dans un répertoire du serveur lors de l'absence d'un fichier index (2°).
Dans le même fichier que le point précédent, on tape :
```
<Directory /var/www/html>
Options -Indexes
</Directory>
```
- Désactiver les modules Apache non utilisés (4°) :
 ```
a2dismod imap
a2dismod include
a2dismod info
a2dismod userdir
a2dismod autoindex
```
- Faire fonctionner Apache avec un utilisateur et un groupe séparé pour réduire les droits sur le serveur. (5°)
On ajoute un groupe et un utilisateur :
```
groupadd http-web
useradd -d /var/www/ -g http-web -s /bin/nologin http-web
```
Puis on change les droits dans le fichier `/etc/httpd/conf/httpd.conf` par `User http-web` et `Group http-web`
- Utilisation d'une extension supplémentaire pour sécuriser Apache (7°) :
```
apt-get install libapache2-modsecurity
a2enmod security2
service apache2 restart
```
- Limitation de la taille des requêtes (10°) : 
Dans le fichier `/etc/apache2/sites-availables/default-ssl.conf`, on ajoute la ligne suivante :
```
<Directory /var/www/html>
	LimitRequestBody 512000
</Directory>
```