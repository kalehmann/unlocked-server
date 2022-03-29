## Unlocked server

Unlocked is a software package consisting of at least a key server and a
client application.
The goal of unlocked is to provision keys to clients that requested access
to these keys after authorization by a user.

### Documentation

* [API](doc/api.md)
* [Security model](doc/security.md)

### Development

This application makes use of [docker-compose][docker-compose] for development.

#### Getting started

First install all dependencies with [composer][composer] to a docker volume.
This command needs to be only run once:


```
docker-compose run composer install
```

#### Starting the server

Run

```
docker-compose up server
```

and the unlocked-server becomes available at
[`https://localhost:8000`](https://localhost:8000).

#### Running symfony commands

```
docker-compose run console <command>
```


#### Adding dependencies

```
docker-compose run composer require <package>
```

**Note:** This command tries to write to local files as user and group `1000`.
This may not fits everyones needs.
The user and group ids can be changed by editing the [`Dockerfile`](Dockerfile)
and rebuilding the image with

```
docker-compose build
```

If there are still permission errors after this, try to delete the volumes with

```
docker-compose down -v
```

For node packages run

```
docker-compose yarn add <package>
```


#### Perform static analysis and style checks

PHPCS and PHPStan are configured as composer scripts.
They can be executed with

```
docker-compose run composer phpcs
```

and

```
docker-compose run composer phpstan
```

  [composer]: https://getcomposer.org/doc/
  [docker-compose]: https://docs.docker.com/compose/reference/
