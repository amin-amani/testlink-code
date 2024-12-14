# Running Testlink using Docker

## Create a dotenv file for Docker

You're going to need a file named `.env` in the project root directory.  You can use `.env.example` or create your own.


## install docker and docker compose

```
sudo apt update && sudo apt install docker.io && sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose && sudo  chmod +x /usr/local/bin/docker-compose
```

```
cp -n .env.example .env
```

```
sudo chmod 777 . -R
```

## Build the image

To  build a docker image of testlink, you can use the following command:

```
docker build --tag testlink:1.9.20 --tag testlink:latest .
```
# delete admin without grant and create user with grant

go to mysql docker and login
```
 docker-compose exec testlink-mysql bash
 mysql -u root -p

```

```
mysql> SELECT User, Host FROM mysql.user;
+------------------+-----------+
| User             | Host      |
+------------------+-----------+
| root             | %         |
| admin            | localhost |
| mysql.infoschema | localhost |
| mysql.session    | localhost |
| mysql.sys        | localhost |
| root             | localhost |
+------------------+-----------+
6 rows in set (0.00 sec)

mysql> DROP USER 'admin'@'localhost';
Query OK, 0 rows affected (0.02 sec)

mysql> SELECT User, Host FROM mysql.user;
+------------------+-----------+
| User             | Host      |
+------------------+-----------+
| root             | %         |
| mysql.infoschema | localhost |
| mysql.session    | localhost |
| mysql.sys        | localhost |
| root             | localhost |
+------------------+-----------+
5 rows in set (0.00 sec)

mysql> CREATE USER 'admin'@'192.168.192.4' IDENTIFIED BY 'admin';
Query OK, 0 rows affected (0.01 sec)

mysql> GRANT ALL PRIVILEGES ON testlink.* TO 'admin'@'192.168.192.4';
Query OK, 0 rows affected (0.01 sec)

mysql> SHOW GRANTS FOR CURRENT_USER;
+----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
------------------------------------------------------------------------------+
| Grants for root@localhost



                                                                              |
+----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
------------------------------------------------------------------------------+
| GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, RELOAD, SHUTDOWN, PROCESS, FILE, REFERENCES, INDEX, ALTER, SHOW DATABASES, SUPER, CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE, RE
PLICATION SLAVE, REPLICATION CLIENT, CREATE VIEW, SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE, CREATE USER, EVENT, TRIGGER, CREATE TABLESPACE, CREATE ROLE, DROP ROLE ON *.* TO `root`@`loca
lhost` WITH GRANT OPTION

                                                                              |
| GRANT ALLOW_NONEXISTENT_DEFINER,APPLICATION_PASSWORD_ADMIN,AUDIT_ABORT_EXEMPT,AUDIT_ADMIN,AUTHENTICATION_POLICY_ADMIN,BACKUP_ADMIN,BINLOG_ADMIN,BINLOG_ENCRYPTION_ADMIN,CLONE_ADMIN,CON
NECTION_ADMIN,ENCRYPTION_KEY_ADMIN,FIREWALL_EXEMPT,FLUSH_OPTIMIZER_COSTS,FLUSH_STATUS,FLUSH_TABLES,FLUSH_USER_RESOURCES,GROUP_REPLICATION_ADMIN,GROUP_REPLICATION_STREAM,INNODB_REDO_LOG_
ARCHIVE,INNODB_REDO_LOG_ENABLE,PASSWORDLESS_USER_ADMIN,PERSIST_RO_VARIABLES_ADMIN,REPLICATION_APPLIER,REPLICATION_SLAVE_ADMIN,RESOURCE_GROUP_ADMIN,RESOURCE_GROUP_USER,ROLE_ADMIN,SENSITI
VE_VARIABLES_OBSERVER,SERVICE_CONNECTION_ADMIN,SESSION_VARIABLES_ADMIN,SET_ANY_DEFINER,SHOW_ROUTINE,SYSTEM_USER,SYSTEM_VARIABLES_ADMIN,TABLE_ENCRYPTION_ADMIN,TELEMETRY_LOG_ADMIN,TRANSAC
TION_GTID_TAG,XA_RECOVER_ADMIN ON *.* TO `root`@`localhost` WITH GRANT OPTION |
| GRANT PROXY ON ``@`` TO `root`@`localhost` WITH GRANT OPTION



                                                                              |
+----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
------------------------------------------------------------------------------+
3 rows in set (0.00 sec)

mysql> DROP USER 'admin'@'192.168.192.4';
Query OK, 0 rows affected (0.02 sec)

mysql> DROP USER 'admin'@'192.168.224.4';
Query OK, 0 rows affected (0.02 sec)

mysql> CREATE USER 'admin'@'^CIDENTIFIED BY 'admin';
mysql> GRANT ALL PRIVILEGES ON testlink.* TO 'admin'@'%' IDENTIFIED BY 'admin' WITH GRANT OPTION;
ERROR 1064 (42000): You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the rig
ht syntax to use near 'IDENTIFIED BY 'admin' WITH GRANT OPTION' at line 1
mysql> CREATE USER 'admin'@'%' IDENTIFIED BY 'admin';
Query OK, 0 rows affected (0.02 sec)

mysql> GRANT ALL PRIVILEGES ON testlink.* TO 'admin'@'%' WITH GRANT OPTION;
Query OK, 0 rows affected (0.01 sec)

mysql> FLUSH PRIVILEGES;
Query OK, 0 rows affected (0.02 sec)


```

Alternatively, build without cached layers:

```bash
docker build --no-cache --tag testlink-code:1.9.20 --tag testlink:latest .
```

## Starting up Testlink using `docker compose`

```bash
docker compose up -d
```

You should now be able to open https://localhost:8080 in your browser to proceed with the Testlink setup

### Database configuration

Based on the default `docker-compose.yml` and `.env` configuration, you'll use the following settings for the database setup:

| Key | Value |
| - | - |
| Database type | MySQL/MariaDB (5.6+ / 10.+) |
| Database host | testlink-mysql |
| Database admin login | root |
| Database admin password | masterkey |

You can provide your own values for `Database name`, `TestLink DB login` and `TestLink DB password`.

### Email configuration

Copy the mail configuration to your installation with:

```bash
cp -n docker/custom_config.inc.php ./
```

You can view the test emails at http://localhost:1080

### Restoring the sample database

There is a sample database in `docs/db_sample` which you can restore with:

```bash
docker compose up testlink-restore
```

## Troubleshooting

### Creating the Testlink database user manually

You'll need to create the testlink user yourself should you be presented with the following error during setup:

> 1045 - Access denied for user 'testlink'@'172.29.0.3' (using password: YES)
> TestLink ::: Fatal Error
> Connect to database testlink on Host testlink-mysql fails
> DBMS Error Message: 1045 - Access denied for user 'testlink'@'172.29.0.3' (using password: YES)

Connect to the app or database container and, using the `mysql` CLI, execute the following commands:

```sql
/* update the database name, user name and password
   values based on what you specified during setup */
USE `testlink`;
CREATE USER 'testlink'@'%' IDENTIFIED BY 'masterkey';
GRANT SELECT, UPDATE, DELETE, INSERT ON *.* TO 'testlink'@'%' WITH GRANT OPTION;
```

### Resetting your database

If you wish to reset your database, you'll need to delete the mysql volume and `config_db.inc.php`.

```bash
docker compose down
docker volume rm testlink-code_mysql
/bin/rm -f config_db.inc.php
docker compose up -d
```
