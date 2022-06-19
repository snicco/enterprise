# PhpStorm Setup

This document describes how to configure PhpStorm for the optimal integration
with this monorepo.

The instructions are based on **PhpStorm 2022.1.2**.

## Setting up a remote PHP interpreter

During development, we connect to the docker environment over SSH instead of 
the native docker-compose integration that PhpStorm offers.

The main reason for this is speed. Tests run noticeably faster over an SSH connection than
by launching new containers each time through the PhpStorm UI.

1. Open the IDE settings and create a new SSH configuration **named Snicco App** at `Tools | SSH Configurations`
   - Host: `127.0.0.1`
   - Port: `2222` (See `SSH_PORT` in [.docker/.env](.docker/.env.dist))
   - Username: `snicco` (See `APP_USER_NAME` in [.make/.mk.env](.make/.mk.env))
   - Authentication type: `Password`
   - Password: `123456` (See `SSH_PASSWORD` in [.docker/.env](.docker/.env.dist))
2. Go to `PHP` and click the three dots next to the `CLI Interpreter` field and add a new interpreter
    - Select `From Docker, Vargrant, VM ...`
    - SSH connection: Choose the `Snicco App` connection in the dropdown
    - PHP interpreter path: `/usr/local/bin/php`
3. You should now see the new PHP interpreter in a new window. Rename it to **Snicco App**.
    - Run `make xdebug-path` and put the output under `Additional > Debugger extension`
    - Under `Additional > Configuration options` click the folder icon and add `xdebug.client_host = host.docker.internal`
4. Lastly, in the PHP interpreter overview we need to configure path mappings:
    - Map your local path to the repository to `/snicco/enterprise` (`APP_CONTAINER_MONOREPO_PATH` in [.docker/.env.dist](.docker/.env.dist))

## Configuring Codeception

1. Go to `PHP | Test Frameworks` and click the + icon to add a new configuration. Select `Codeception by Remote Interpreter`.
2. Select the `Snicco App` interpreter that we just created. PhpStorm should now automatically detect the path to the codeception executable.
3. Under `Test Runner > Default configuration file` enter `/snicco/enterprise/codeception.dist.yml`
4. Go to `Run | Edit Configurations` and click `edit configuration templates` in the bottom-left corner
5. Select `Codeception` and choose `Defined in the configuration file`.
    - Check the box `Alternative configuration file`
    - Set the value to the full **LOCAL PATH** of the `codeception.dist.yml` file in the repository root.

You should now be able to run tests through the PhpStorm UI and also debug them through the UI.

## Debugging Web Requests

1. Go to `PHP | Servers` to add a new server. The name of this server **must be** `snicco` (The value of `PHP_IDE_CONFIG` in [.docker/.env.dist](.docker/.env.dist) )
    - Host: `127.0.0.1`
    - Port: `80`
    - Check `Use path mappings`
2. Map the full **local path** to [.wp/html](.wp/html) to `/var/www/html` (the value of `WP_CONTAINER_WP_APP_PATH` in [.mk.configuration](.make/.mk.configuration))
3. Optionally, map the path to the plugins you are working on to `var/www/html/wp-content/plugins/$PLUGIN_NAME`
4. Run `make xdebug-on` to enable Xdebug for the php-fpm container.
5. Set a breakpoint in [.wp/html/index.php](.wp/html/index.php) and click `Start Listening to debug connections` in the PhpStorm UI.
6. Go to [https://snicco-enterprise.test](https://snicco-enterprise.test), you should now hit the breakpoint inside PhpStorm.

## Configuring directories

We need to exclude some duplicate directories from indexing in PhpStorm:

Go to `Directories` and exclude the following folders completely:

 - [.wp/html/wp-content/plugins](.wp/html/wp-content/plugins)
 - [.wp/html/wp-content/plugins](.wp/html/wp-content/mu-plugins)
 - [.wp/html/wp-content/plugins](.wp/html/wp-content/bundle)
 - [.wp/html/wp-content/plugins](.wp/html/wp-content/component)
