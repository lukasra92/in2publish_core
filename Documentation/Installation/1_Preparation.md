# Preparation

## Software Requirements

**Preface**:
If you do not use `adapter.remote: 'ssh'` or `adapter.transmission: 'ssh'` you can safely ignore any reference to
SSH/libssh/php-ssh(2).
SSH-Adapters are used by default and they are the only ones shipped with in2publish.
If you can't or don't want to use SSH you can ask us for alternatives.

---

in2publish requires some functions that are not commonly available on many servers
as well as some minimum server software versions.
(You can find the exact and always up to date dependencies in the composer.json)

| Dependency    | Version    |
|---------------|------------|
| PHP           | >= 7.2     |
| Apache        | >= 2.2     |
| MySQL/MariaDB | >= 5.6     |
| TYPO3         | 9.5 - 10.4 |

Additional PHP extensions for the default RCE and TATAPI adapter:

| Dependency   | Version |
|--------------|---------|
| php-ext ssh2 | >= 0.11 |

If you can not install php-ext ssh2 on your system or it does not work
well you can install a different adapter. Currently we offer an HTTP and
a phpseclib adapter. Both will eliminate the need for php-ext ssh2.

PHP configuration values:

| Name            | Value   |
|-----------------|---------|
| allow_url_fopen | On      |

Hint:

> php_ssh2 0.11 does not include the function ssh2_sftp_chmod. You can download a newer version
> here: [PECL SSH2 Download](https://pecl.php.net/package/ssh2)
> Most versions on package based server software are below 0.12, so the function has been made optional in in2publish.
> In this case you have to take care of file permissions yourself.

## OS Requirements

These requirements are on top of the basic requirements of TYPO3 CMS 7.6 - 8.7

**Local:**

| Value                 | Description                                                                                                      |
|-----------------------|------------------------------------------------------------------------------------------------------------------|
| SSH2                  | the libssh2 library, which is used by the php-ext ssh2                                                           |
| Keypair               | A public and private RSA key pair to authenticate the Webprocess User on Foreign (PW-protected key is supported) |
| Environment variable  | in2publish requires `SetEnv IN2PUBLISH_CONTEXT Local` to be set in the virtual host or (if allowed) .htaccess    |

If Foreign's database is not on the same server (which is not recommended!) you will need a Static port forwarding (from
any port on Local to 3306 on Foreign)

Hint:

> The following command opens a simple ssh tunnel with port forwarding in the background:
>
>     ssh -NfL 3307:example.com:3306 ssh-user@example.com


Hint:

> For automated refreshing of the port forwarding use the following line in /etc/rc.local/:
>
>     /usr/bin/autossh -M 0 -o "ServerAliveInterval 60" -o "ServerAliveCountMax 3" -N -f  -i
>     /path/to/.ssh/id_rsa -L 3307:127.0.0.1:3306 ssh-user@example.com

**Foreign:**

| Value                 | Description                                                                                                                                   |
|-----------------------|-----------------------------------------------------------------------------------------------------------------------------------------------|
| SSHD                  | An active SSH2 server which allows incoming connections from Local                                                                            |
| Shell Apache2         | The user which apache2 is running must have a shell (e.g. Bash) and must be allowed to log in via SSH2 from Local with pubkey authentication. |
| Environment variable  | in2publish requires `SetEnv IN2PUBLISH_CONTEXT Foreign` to be set in the virtual host or (if allowed) .htaccess.                              |

Appendix to `Shell Apache2`:

> Under certain circumstances the user may differ from the webprocess user
>
>    1. Both users are able to read/write directories and files written by the other one.
>    2. php-ext ssh2 >= 0.12 for ``ssh2_sftp_chmod`` support

Appendix to `Environment variable`:

> This line is optional (on foreign), since `IN2PUBLISH_CONTEXT` defaults to `Foreign`

## How to create the Public and Private Key pair

Hint:

> You need sudo or at least the permission to execute ssh-keygen as the webserver's user

```BASH
# 1. log in to local
ssh ssh-user@example.com

# 2. change to the webserver's user
sudo su -s /usr/bin/bash - www-data

# 3. create key pair (you should always generate a strong key! Always adapt the command to the last best practices)
ssh-keygen

# 4. follow the instructions. you might define a password to encrypt the private key,
#    but you have to write it into the LocalConfiguration.yaml as unencrypted plain text.
```

## How to create a valid user on Foreign

Hint:

> This is only for guidance. Please contact your system administrator if you are not sure what you are doing.

Using the webserver's user (assuming the user's name is `www-data`):

```BASH
# 1. Login on foreign
ssh ssh-user@www.example.com

# 2. Enable login for the user with a shell (example for www-data)
usermod -s /usr/bin/bash www-data

# 3. Set the home directory if not set (example for www-data)
usermod -d /var/www/websites www-data

# 4. Create an .ssh folder inside the home directory
mkdir /var/www/websites/.ssh

# 5. Create an authorized_keys file inside the .ssh folder and paste
#    the public key from Local into it (you can use vi/vim instead of nano)
nano /var/www/websites/.ssh/authorized_keys

# 6. Login on Local::
exit
ssh ssh-user@stage.example.com

# Note: local-host is the hostname of the server where Local is, not your localhost

# 7. Change your user to the web-process user (repeat step 1 if you
#    cannot login, or define a login shell when changing users)::
sudo su -s /usr/bin/bash - www-data

# 8. Test the login to foreign::
ssh www-data@www.example.com
```

If this does not work please contact your server administrator, or someone that knows how to get this stuff working.

## Install libssh2 and ssh2 (PHP module) on DF Managed Server

Disclaimer:

> This is only for guidance. Please contact your system administrator if you are not familiar with compiling modules.
> This guide comes WITHOUT ANY WARRANTY

Taken from https://www.df.eu/forum/threads/68032-Installationsprobleme-ssh2-so
Walkthrough for domainFactory ManagedServer and target PHP version 7

```BASH
# 1. Login via ssh
ssh ssh-user@stage.example.com

# 2. Switch to the directory where you want to download the resources
mkdir -p php_modules/sources/ && cd php_modules/sources/

# 3. Download all required sources

# 3.1 Get the latest version from http://www.libssh2.org/
wget https://www.libssh2.org/download/libssh2-1.7.0.tar.gz

# 3.2 Get the latest version from http://pecl.php.net/package/ssh2
wget https://pecl.php.net/get/ssh2-1.1.2.tgz

# 4. Unpack the resources:
tar xfz libssh2-1.7.0.tar.gz
tar xfz ssh2-1.1.2.tgz

# 5. Enter the unpacked libssh folder and compile the module.
#    Keep the version in the folder name.
cd libssh2-1.7.0/
./configure --prefix=$HOME/php_modules/libssh2-1.7.0/
make && make install

# 6. Enter the unpacked ssh2 folder and compile the module.
cd ../ssh2-1.1.2/

phpize7

./configure --with-php-config=/usr/local/bin/php7-config \
  --with-ssh2=$HOME/php_modules/libssh2-1.7.0/

make

# 7. Enter your target directory and add a php.ini file
cd $HOME/webseiten/my_website/stage/webroot/typo3
printf "extension_dir=$HOME/php_modules/sources/ssh2-1.1.2/modules/\nextension=ssh2.so"
  > php.ini
```

The ssh2 functions should be available immediately, as well as the ssh2:// wrapper

---

**Continue with [Installation](2_Installation.md)**
