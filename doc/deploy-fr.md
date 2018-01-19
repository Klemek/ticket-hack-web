# Documentation Infrastructure Ticket'Hack

Ce document rend compte de l'ensemble des éléments installés sur le serveur du projet Ticket'Hack.
Le serveur est une raspberry pi 2 accueillant un OS raspbian stretch. Pour cause de réalisation et de spécialisation pour cette OS, l'infrastructure mise en place est la première proposé pour les projets S7. Le serveur accueil l'ensemble des services : Apache, php, postgresql et phppgadmin. Le serveur est mis à jour automatiquement depuis le github du projet (https://github.com/Klemek/Ticket-Hack-Web)
La connexion est en HTTPS et est sécurisé par https://certbot.eff.org/.
Dans ce document, on ne considère pas la mise en place de raspbian sur la raspberry pi 2, ni la mise en place du service ssh pour la connexion distante.
L'ensemble des commandes explicités par la suite sont à faire une fois connecté par ssh.

## Sommaire

### 1. Apache

L'installation de Apache sur le serveur passe par les lignes de commande suivantes : 
`apt-get update` puis `apt-get upgrade` et enfin `apt-get install apache2`.

La configuration du serveur à été modifiée au fur et à mesure des ajouts sur le serveur.
Au début, le fichier 000-default.conf est utilisé pour configurer le serveur, les options par défaut ne sont pas modifier (répertoire utilisé et log), le ServeurName est mis à kalioz.fr (nom de domaine du projet).

Un certificat ssl auto-généré a également été mis en place avant le certificat officiel.
Pour utiliser ce certificat auto généré, on a ajouter les lignes suivantes dans le fichier 000-default.conf .
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

Pour mettre en fonctionnement la base de données, on ajoute tous d'abord un utilisateur superuser à la main. (pas un utilisateur root).
```
su -postgres
createuser admin --superuser
psql
\password admin
********
********
\q
```
Avec cette utilisateur, on peut ensuite ajouter l'ensemble des tables par l'interface graphique.
On se rend donc sur la page de l'application kalioz.fr/phppgadmin
pour ajouter les tables avec l'utilisateur super admin fraîchement crée.
Dans notre cas, l'ensemble des commandes sql sont également dans le répertoire github de l'application.

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

Une fois la première récupération effectuer, il suffit désormais de mettre à jour le projet depuis le répertoire github.
Pour ce faire on peut utiliser les commandes suivantes : 
```
cd /var/www/html
git pull https://github.com/Klemek/Ticket-Hack-Web
```
Par soucis pratique, le serveur contient également un appelle automatique pour mettre à jour l'application web. Cette appelle est réalisé depuis cron. Pour faire cette appelle, il faut définir un fichier exécutable qui contient toutes les informations de mise à jour et il faut également définir la routine.
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
Pour notre projet, nous avons également ajouter un nettoyage de la base de données. Dans cron, comme fait précédemment, on ajoute la ligne suivante :
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

Dans notre cas, on doit également configurer le nom du serveur pour qu'il soit cohérent avec le nom de domaine que nous utilisons pour le projet.
On tape les lignes suivantes : 
`nano /etc/apache2/site-availables/default-ssl.conf`
On arrive dans le fichier qui sert de base pour le certificat ssl généré par certbot.