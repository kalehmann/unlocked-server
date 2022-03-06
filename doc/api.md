## Unlocked server API

This document describes the data stored and endpoints exposed by the
unlocked server.
In this document, a term enclosed in chevrons (`<` and `>`) is a placeholder
describing the actual value in its place.

### Terminology

#### Client

A **client** refers to a device than can requests keys from the unlocked
server.

#### Handle

A **handle** refers to a short unique string identifying a resource.
In the context of the unlocked server, a handle has a length between 8 and 64
characters.
Valid characters for a handle are the letters `a` to `z` in lower and upper
case, as well as the numbers `0` to `9`, the dash sign `-` and the underscore
sign `_`.
A regular expression to match valid handles is

```
^[a-zA-Z0-9\-_]{8,64}$
```

#### User

A **user** refers to a person, that can manage clients or keys and permit or
deny requests for keys from clients.

### Models

The unlocked server stores the following data according to the following models:

* **clients**: The unlocked server stores a list of clients.
    For each client the following information is stored:
    * a unique [handle][handle] identifying the client
    * a secret used to authenticate the client
    * the [handle][handle] of the user that manages the client
    * a short description of the client
    * whether the client was deleted.
        If this property is set to truthy value, the client will not show up
        on list endpoints anymore, but is still kept for database integrity.
* **keys**: The unlocked server stores keys which can be provisioned to the
    clients.
    For each key a the following information is stored:
    * an unique handle identifying the key
    * the key itself (maybe encrypted)
    * the [handle][handle] of the user that manages the key
    * a short description of the key
    * whether the key was deleted.
        If this property is set to truthy value, the key will not show up
        on list endpoints anymore, but is still kept for database integrity.
* **requests**: When a client requests a key (and the client is allowed to
    request that key), a new request is stored.
    The request has
    * a unique ID
    * the unique [handle][handle] of the client that requested the key
    * the unique [handle][handle] of the requested key
    * the timestamp when the request was created
    * the timestamp when the request has been processed (either permitted or
        denied)
    * the status of the request (`PENDING` / `ACCEPTED` / `FULFILLED` /
       `DENIED` / `EXPIRED`)
    * the timestamp when the request expires
* **tokens**: Tokens are used by different applications, for example mobile apps
    to authenticate against the unlocked server on behalf of the user.
    For each token the following information is stored:
    * a unique id for the token
    * the unique token
    * the [handle][handle] of the user the token belongs to
    * the timestamp when the token expires
    * a short description of the token
    * whether the token has been revoked
* **users**: A user can manage clients or keys and permit or deny requests from
    clients.
    For each user the following information is stored:
    * a unique [handle][handle]
    * a secret password (only the hash of the password is stored)
    * optionally an email address to notify the user about new requests
    * optionally a mobile number to notify the user about new requests

The relations between the models are depicted by the diagram below:

[![entity relationship diagram of the unlocked server][erd]][erd]

### Authentication

#### Client authentication

Clients should authenticate themselves against the unlocked server using
[HTTP basic authentication][rfc7617].
Every request from a client to the unlocked server should contain an
`Authorization` header with the value `Basic <token>`, where token is the base64
encoded client name and client secret separated by a colon (`:`). e.g.

```php
base64_encode($client->name . ':' . $client->secret)
```

#### User authentication

Users authenticate themselves against the unlocked server by providing
an HMAC hash with each request.
The HMAC hash is provided in the `Authorization` header in the format

```
hmac username="<token-id>" headers="<headers>" signature="<hmac-hash>"
```

| Parameter       | Description                                                |
|-----------------|------------------------------------------------------------|
| `<token-id>`    | id of the token used as secret in the HMAC hash            |
| `<headers>`     | list of headers include in the message that is hashed      |
| `<hmac-hash>`   | hash of the headers included concatenated with the payload |

An user can acquire a token by sending a `POST` request to the `/tokens` endpoint
as described below.
This endpoint can be accessed by either providing HMAC or
[HTTP basic authentication][rfc7617] using the users password.

### Endpoints

Request and response payload are always of type json.

#### Key endpoint

* `/keys`


    | Used by      | Allowed methods |
    |--------------|-----------------|
    | [user][user] | `GET`, `POST`   |

    This endpoint is used by the [user][user] to list existing keys and create new keys.

    * `GET /keys` lists all keys of the [user][user].
        Keys with `deleted` set to `true` will not appear in the response.

        | Response code   | Condition |
        |-----------------|-----------|
        | [code 200][200] | always    |

        <details>
        <summary>Example response payload</summary>

        ```json
        [
          {
            "handle": "key-0001",
            "description": "This is a key"
          },
          {
            "handle": "key-0002",
            "description": "This is another key"
          }
        ]
        ```
        </details>

    * `POST /keys` creates a new key.
        The URL to the endpoint returning the new key will be supplied in the
        `Location` header.

        | Response code   | Condition                       |
        |-----------------|---------------------------------|
        | [code 201][201] | always                          |
        | [code 400][400] | if the handle is already in use |

        <details>
        <summary>Example request payload</summary>

        ```json
        {
          "handle": "key-0003",
          "description": "A new key",
          "key": "12345678"
        }
        ```
        </details>

        <details>
        <summary>Example response payload</summary>

        ```json
        {
          "handle": "key-0003",
          "description": "A new key"
        }
        ```
        </details>

* `/keys/<key-handle>`

    | Used by      | Allowed methods          |
    |--------------|--------------------------|
    | [user][user] | `GET`, `DELETE`, `PATCH` |

    This endpoint is used by the [user][user] to view, update or delete keys.

    * `GET /keys/<key-handle>` lists the details of a key including the attribute
        `deleted`.

        | Response code   | Condition                                  |
        |-----------------|--------------------------------------------|
        | [code 200][200] | Key exists and belongs to the [user][user] |
        | [code 404][404] | Key does not exist or is inaccessible      |

        <details>
        <summary>Example response payload</summary>

        ```json
        {
          "handle": "key-for-server-1",
          "description": "This is a key",
          "deleted": false
        }
        ```
        </details>

    * `DELETE /keys/<key-handle>` deletes a key.

        | Response code   | Condition |
        |-----------------|-----------|
        | [code 204][204] | always    |

    * `PATCH /keys/<key-handle>` updates a key.
        Only the value `description` can be updated.

        | Response code   | Condition                                         |
        |-----------------|---------------------------------------------------|
        | [code 404][404] | Key does not exist or is inaccessible by the user |
        | [code 200][200] | On success                                        |

        <details>
        <summary>Example request payload</summary>

        ```json
        {
          "description": "New description"
        }
        ```
        </details>

        <details>
        <summary>Example response payload</summary>

        ```json
        {
          "handle": "key-0003",
          "description": "New description",
          "deleted": false
        }
        ```
        </details>

#### Request endpoint

* `/requests`

    | Used by                        | Allowed methods |
    |--------------------------------|-----------------|
    | [client][client], [user][user] | `GET`, `POST`   |

    This endpoint is used by the [user][user] to list all requests.

    * `GET /requests` list all requests for [clients][client] and keys managed by the
      user.
      The results are sorted by ascending date.

      | Response code   | Condition |
      |-----------------|-----------|
      | [code 200][200] | always    |

      <details>
      <summary>Example response payload</summary>

      ```json
      [
        {
          "id": 3,
          "client": "client-0002",
          "key": "key-0002",
          "processed": 1646562630,
          "state": "PENDING",
          "timestamp": 1646562630
        },
        {
          "id": 2,
          "client": "client-0001",
          "key": "key-0001",
          "processed": 1646560800,
          "state": "PENDING",
          "timestamp": 1646560800
        },
        {
          "id": 1,
          "client": "client-0001",
          "key": "key-0001",
          "processed": 1646478000,
          "state": "ACCEPTED",
          "timestamp": 1646474400
        }
      ]
      ```
      </details>

    * `GET /requests?state=<state>` list all requests with the state `<state>`,
      where `<state>` is either `PENDING`, `ACCEPTED`, `DENIED` or `FULFILLED`.
      The results are sorted by ascending date.

      | Response code   | Condition |
      |-----------------|-----------|
      | [code 200][200] | always    |

      <details>
      <summary>Example response payload</summary>

      ```json
      [
        {
          "id": 3,
          "client": "client-0002",
          "key": "key-0002",
          "processed": 1646562630,
          "state": "PENDING",
          "timestamp": 1646562630
        },
        {
          "id": 2,
          "client": "client-0001",
          "key": "key-0001",
          "processed": 1646560800,
          "state": "PENDING",
          "timestamp": 1646560800
        },
      ]
      ```
      </details>

    * `POST /requests` is used by the [client][client] to request access to a
        key.
        Payload is of type json with the single key `key`.
        The URL to the endpoint returning the new request will be supplied in
        the `Location` header.

        | Response code   | Condition                                        |
        |-----------------|--------------------------------------------------|
        | [code 201][201] | on success                                       |
        | [code 400][400] | no key with the [handle][handle] or inaccessible |

        <details>
        <summary>Example request payload</summary>

        ```json
        {
          "key": "key-0001"
        }
        ```
        </details>

        <details>
        <summary>Example response payload</summary>

        ```json
        {
          "id": 3,
          "client": "client-handle",
          "key": "key-0001",
          "processed": 1645571732,
          "state": "PENDING",
          "timestamp": 1645571732,
          "fulfilled": false
        }
        ```
        </details>

* `/requests/<request-id>`

    | Used by                        | Allowed methods |
    |--------------------------------|-----------------|
    | [client][client], [user][user] | `GET`, `PATCH`  |

    * `GET /requests/<request-id>` is used by the [client][client] to query the
        status of a request.

        | Response code   | Condition                                 |
        |-----------------|-------------------------------------------|
        | [code 200][200] | on success                                |
        | [code 404][404] | Request does not exist or is inaccessible |

        <details>
        <summary>Example response payload</summary>

        ```json
        {
          "id": 3,
          "client": "client-handle",
          "key": "key-handle",
          "processed": 1645578732,
          "state": "ACCEPTED",
          "timestamp": 1645571732,
          "fulfilled": false
        }
        ```
        </details>

    * `PATCH /requests/<request-id>` is used by the [client][client] to gain
        the key after the request has been accepted.
        The payload has the single key `state`.

        | Response code   | Condition                                           |
        |-----------------|-----------------------------------------------------|
        | [code 200][200] | on change of `state` from `ACCEPTED` to `FULFILLED` |
        | [code 204][204] | `state` has not been changed                        |
        | [code 400][400] | `state` is changed to value other than `FULFILLED`  |
        | [code 404][404] | request does not exist or is inaccessible           |
        | [code 409][409] | request state is not `ACCEPTED`                     |

        If **and only if** the attribute `state` is changed from `ACCEPTED` to
        `FULFILLED` the payload of the response is the requested key in plain text.

        <details>
        <summary>Example request payload</summary>

        ```json
        {
          "fulfilled": true
        }
        ```
        </details>

        <details>
        <summary>Example response payload</summary>

        ```
        secret-key
        ```
        </details>

    * `PATCH /requests/<request-id>` is also used by the [user][user] to accept or
        reject requests for key access.
        The payload has the single key `state`.
        Allowed changes are:

        * `PENDING` to `ACCEPTED`
        * `PENDING` to `DENIED`
        * `ACCEPTED` to `ACCEPTED`
        * `DENIED` to `DENIED`

        | Response code   | Condition                                 |
        |-----------------|-------------------------------------------|
        | [code 200][200] | on success                                |
        | [code 400][400] | invalid state change or additional fields |
        | [code 404][404] | request does not exist or is inaccessible |

        <details>
        <summary>Example request payload</summary>

        ```json
        {
          "state": "ACCEPTED"
        }
        ```
        </details>

        <details>
        <summary>Example response payload</summary>

        ```json
        {
          "id": 1,
          "client": "client-handle",
          "key": "key-handle",
          "processed": 1645578732,
          "state": "ACCEPTED",
          "timestamp": 1645571732
        }
        ```
        </details>

#### Token endpoint

* `/tokens`

    | Used by      | Allowed methods |
    |--------------|-----------------|
    | [user][user] | `GET`, `POST`   |

    * `GET /tokens` is used by the [user][user] to list all their tokens.

        | Response code   | Condition |
        |-----------------|-----------|
        | [code 200][200] | always    |

        <details>
        <summary>Example response payload</summary>

        ```json
        [
          {
            "description": "A token",
            "expires": 1645664056
          },
          {
            "description": "Another token",
            "expires": 1645664056
          }
        ]
        ```
        </details>

    * `POST /tokens` is used by the [user][user] to persist a new token.
        Payload has the keys `description` and `expires`.

        | Response code   | Condition                     |
        |-----------------|-------------------------------|
        | [code 400][400] | if expires has already passed |
        | [code 201][201] | on success                    |

        <details>
        <summary>Example request payload</summary>

        ```json
        {
          "description": "A new token",
          "expires": 1645664410
        }
        ```
        </details>

        <details>
        <summary>Example response payload</summary>

        ```json
        {
          "id": 3,
          "description": "A new token",
          "expires": 1645664410,
          "revoked": false
        }
        ```
        </details>

* `/tokens/<token-id>` is used by the [user][user] to manipulate or delete a
    token.

    | Used by      | Allowed methods   |
    |--------------|-------------------|
    | [user][user] | `DELETE`, `PATCH` |

    * `DELETE /tokens/<token-id>` is used by the [user][user] to delete the
        token with the id `<token-id>`.

        | Response code   | Condition |
        |-----------------|-----------|
        | [code 204][204] | always    |

    * `PATCH /tokens/<token-id>` is used by the [user][user] to update the
        description of the token with the id `<token-id>`.

        | Response code   | Condition                               |
        |-----------------|-----------------------------------------|
        | [code 400][400] | additional fields                       |
        | [code 404][404] | token does not exist or is inaccessible |
        | [code 200][200] | on success                              |

        <details>
        <summary>Example request payload</summary>

        ```json
        {
          "description": "New description"
        }
        ```
        </details>

        <details>
        <summary>Example response payload</summary>

        ```json
        {
          "id": 3,
          "description": "New description",
          "expires": 1645664410
        }
        ```
        </details>

  [200]: https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/200
  [201]: https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/201
  [204]: https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/201
  [303]: https://httpwg.org/specs/rfc7231.html#status.303
  [400]: https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/400
  [401]: https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/401
  [403]: https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/403
  [404]: https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/404
  [409]: https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/409
  [client]: #client
  [erd]: erd.svg
  [handle]: #handle
  [rfc7617]: https://datatracker.ietf.org/doc/html/rfc7617
  [user]: #user
