## Unlocked security

This document describes the security model of unlocked.

### Terminology

#### Client

A **client** refers to a device than can requests keys from the unlocked
server.

#### Fulfillment

The term **fulfillment** describes the process of handling the encrypted key
from the unlocked server to the unlocked client after a request for the key has
been accepted.

#### Key server

The term **key server** describes the unlocked server.

#### Unlocked

**unlocked** is a software package consisting of at least a key server and a
client application.
The goal of unlocked is to provision keys to clients that requested access
to these keys after authorization by a user.

#### User

A **user** refers to a person, that can manage clients or keys and permit or
deny requests for keys from clients.

### Presumptions

The security model described by this document only applies when a set of
presumptions is met.

* After a request for a key has been fulfilled the client disposes the key as
    soon as possible.
* The server does not store the key itself, but an encrypted version of the key.
    Credentials for decryption are stored on the client.
* The user only accepts plausible requests.

### Guarantees

When the presumptions mentioned above are met, unlocked guarantees that the keys
are only accessible to an normal (non-state) attacker when both the client and
the key server are compromised or tampering of the client goes unnoticed for a
longer period of time.

### Shortcomings

If tampering on the client is not detected before the next plausible request to
the key server is made, an attacker can access the keys.

However this also means an attacker could get access to the keys when the user
enters them manually.

### Scenarios

In the following scenarios the usage of unlocked to decrypt an encrypted hard
drive at the boot of a server is assumed.

1. **An attacker gains full access to the key server**

    The attacker detects the timing of requests to the keys and is able to
    deny the client access to the keys.
    However the attacker is not able to access the keys themselves since they
    are encrypted.

2. **An attacker gains hardware access to the client**

    A capable attacker has already won
    [if the system boots up][physical-key-extraction] or
    [is already running][cold-boot-attack].
    A "normal" attacker does not get the keys directly since the attacker has
    no access to the keys after the system has been powered of (that means the
    system cannot be relocated easily) or can perform a new request for the key
    as the user _should_ recognize the request as implausible, since requests
    for keys are only expected after planned reboots (for example after upgrading
    the system or larger power outages).

3. **An attacker gains root access on the client**

    The attacker can read the key to the hard drive from the memory independent
    from unlocked.
    Meddling with the unlocked client or cryptsetup to read the key during
    fulfillment is also possible.

  [cold-boot-attack]: https://en.wikipedia.org/wiki/Cold_boot_attack
  [physical-key-extraction]: https://faculty.cc.gatech.edu/~genkin/papers/physical-key-extraction-cacm.pdf
