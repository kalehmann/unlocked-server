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
[`https://localhost:8000`](`https://localhost:8000`).

#### Running symfony commands

```
docker-compose run console <command>
```


#### Adding dependencies

```
docker-compose run composer require <package>
```

  [composer]: https://getcomposer.org/doc/
  [docker-compose]: https://docs.docker.com/compose/reference/
