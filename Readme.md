## Unlocked server

Unlocked is a software package consisting of at least a key server and a
client application.
The goal of unlocked is to provision keys to clients that requested access
to these keys after authorization by a user.

### Documentation

* [API](doc/api.md)
* [Security model](doc/security.md)

### Development

#### Using composer

```
docker run --interactive --tty --volume $(pwd):$(pwd) --workdir $(pwd) --user $(id -u ${USER}):$(id -g ${USER}) composer <composer-command>
```
