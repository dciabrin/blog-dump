title: Connecting to MariaDB with auth_ed25519 and PyMySQL
subtitle: ed25519-based authentication for the Python MySQL client
tags: mariadb,crypto,python
date: 2020-09-21T09:00:00+0200
lang: en
header_cover: img/covers/james-sutton-FqaybX9ZiOU-unsplash.jpg
twitter_card: img/cards/mariadb-auth_ed25519.png
og:image: img/cards/mariadb-auth_ed25519.png
category: Code


When a MySQL client wants to connect to a MySQL or MariaDB server, the [MySQL wire protocol][mariadb:con]
specifies how both parties should exchange data, advertise their capabilities, and which authentication method
they should use for the client to get connected. By default, this authentication is a challenge-response
scheme that relies on [SHA-1][sha1]. But starting MariaDB 10.1.22, a new cryptographic-based authentication
mechanism called [`auth_ed25519`][mariadb:auth_25519] can be used for improved security at connection time,
and PyMySQL recently added support for it.


## MySQL server authentication

MySQL and MariaDB allow a variety of authentication methods: PAM-based, SHA-1 hashed challenges, SHA-256
challenges over RSA encryption... The default authentication since MySQL 4.1 and probably still the most used
nowadays is called [`mysql_native_password`][mariadb:native]. It's a simple yet clever authentication scheme,
because the server never stores the user's password directly in the database, nor does it exchange it over the
wire. Instead, the server only stores an indirect hash of the original password:

$$ \text{SHA-1}(\text{SHA-1}(\text{password}))) $$

To authenticate a client, the server concatenate a random scramble to that information, hashes the result with
SHA-1, and sends it as a challenge to the client. As a response, the client must XOR this challenge with a
SHA-1 hash of its password:

$$ \text{SHA-1}(\text{scramble} \parallel \text{SHA-1}(\text{SHA-1}(\text{password}))) \oplus \text{SHA-1}(\text{password}) $$

The XOR function being its own inverse, the server can now XOR the client's response with the original
challenge to retrieve $\text{SHA-1}(\text{password})$. It then hashes that result with SHA-1 a last time and
compares it with the digest stored in the DB to validate the authentication.

The fact that the server only stores a double-SHA-1 digest helps mitigate the risk of recovering a password
from the DB if it is compromised, but this is not perfect either. SHA-1 itself is [considered insecure
nowadays][shattered], so new authentication plugins have been developed.


## More secure authentication 

MySQL has developed an improved authentication plugin called [`sha256_password`][mariadb:sha256] (and its
variant `caching_sha2_password`).  That plugin stores a $\text{SHA-256}(\text{password})$ digest in the DB,
and relies on an RSA key pair to encrypt data exchanged during the authentication. When a client wants to
authenticate, it receives a random scramble from the server, XORs the password with it, and encrypts the
result with the server's public key. When the server receives the response, it uses its private key to decrypt
it, XORs the decrypted response, hashes the result with SHA-256, and compares it with the hashed credentials
in the DB to validate the authentication.

The new `sha256_password` improves over `mysql_native_password` since it no longer uses SHA-1, but it comes
with the major inconvenience that one must manage the public key's life cycle (deployment, renewal...), so
this authentication can sometimes become tedious or impractical to use.

MariaDB took a different approach with `auth_ed25519`. its challenge-response consists in signing a random
scramble with a cryptographic function. It is based on Ed25519, a type of Edwards-curve Digital Signature
Algorithm (EdDSA) that uses SHA-512 and the [Curve25519][curve25519] twisted Edwards curve. This is a fast and
secure cryptographic signature. But most importantly, `auth_ed25519` doesn't need to distribute keys to
clients, so it's much more convenient and practical than `sha256_password`.


## Ed25519 and Elliptic Curve Cryptography

Elliptic curve cryptography (ECC) is a type of public-key cryptography that relies on the algebraic structure
of elliptic curves over finite fields.

Specifically, [Ed25519][ed25519] operates on the points of a twisted Edwards curve, a 2D curve whose point
coordinates belong the ring of integers modulo $2^{255-19}$. There is an special addition law for points:
adding two points on the curve is a computation that always yields a new point on the curve. There exists a
cyclic subgroup of $l$ points, $l$ being a large prime number ($2^{252}$ + something). In this subgroup,
Ed25519 defines a base point $B$, of order $l$; that means, adding $B$ to itself $l$ times will give back
$B$. Lastly, adding a curve point to itself numerous times is called a scalar multiplication:

$$ s.B = \underbrace{B + B + B + \ldots + B}_{s~\text{times}} = C $$

Now that the maths are laid out, here is what the Ed25519 signature scheme looks like:

  * A private key $s$ is a 32 bytes buffer of uniformly random data.
  
  * A public key $A$ is a point on the Edwards curve.
  
  * A point on the Edwards curve is encoded as a 32 bytes buffer.
  
Signing a message $M$ with a public key $A$, returns a curve point $R$ and a 32 bytes number $S$. A signature is
legitimate if it verifies the following equality:

$$ S.B = R + \text{SHA-512}( R \parallel A \parallel M).A $$

where the dot is the scalar multiplication, the plus is the point addition, and the double pipe is the buffer
concatenation.

$A$, $R$ and $S$ are the public information derived from the corresponding private key $k$. Given
$\text{SHA-512}(k)$, the first half $s$ is clamped and produces $A = s.B$. The last half $t$ is hashed with
the message, and the resulting value $r = \text{SHA-512}( t \parallel M )$ produces $R = r.B$. Number $S$ is
computed using modular arithmetic and equals $r + (\text{SHA-512}( R \parallel A \parallel M) \times s)$
modulo $l$. With a bit of math reshuffling, you can see that the neat thing about these definitions is that
they satisfy the equality from above, and yet all that is needed to verify a signature comes from public
information only:


\begin{alignat}{1} S.B &= (r &+~ (&\text{SHA-512}( R \parallel A \parallel M) \times s)).B \\
 &= r.B &+~ (&\text{SHA-512}( R \parallel A \parallel M) \times s).B \\
 &= r.B &+~ &\text{SHA-512}( R \parallel A \parallel M).s.B \\
 &= R &+~ &\text{SHA-512}( R \parallel A \parallel M).A
\end{alignat}

There are two public [reference implementations][djb:soft] of Ed25519. One is a simple and excruciatingly slow
Python version, to get familiar with the mathematics. The production-ready implementations use C and
assembler. They are very fast and designed to be secure (e.g. immune to timing attacks).

All the Ed25519 libraries currently available are based on the reference implementation, and they more or less
provide the same API: creating a signing key pair, signing a message with a public key, and verifying that a
message signature is legitimate. One well known C library that supports Ed25519 is [libsodium][libsodium]. In
our case, the Python-equivalent is [PyNaCl][pynacl], a Python-binding of libsodium.


## How MariaDB takes advantage of Ed25519

Ed25519 ticks all the previous boxes for a secure authentication plugin: it only stores a digest in the DB,
and it replaces SHA-1 with modern cryptographic functions. It's based on the reference ed25519 implementation,
and it uses the signature scheme like this:

  * The user's password is the private key $k$, and it's only known by the client.
  
  * The MariaDB server only stores the public key $A$, which as we saw earlier is a curve point derived from the
    first half of $\text{SHA-512}(k)$.

  * When a client wants to authenticate, it gets a random message $M$ as a challenge, signs it with its
    private key $k$, and returns the signature pair $R$ and $S$ to the server.

  * The server then computes $R + \text{SHA-512}( R \parallel A \parallel M ).A$ and authenticates the user if
    the result matches the digest stored in the DB.

This is clever, and also simple from a client's perspective! Well, it would be if it wasn't for a small but
important detail... Can you see how it differs from the Ed25519 specification previously described? That's
right, the private key is no longer <q>a 32 bytes buffer of uniformly random data</q>, it's now an arbitrary
size, non-random password. At the very least, this makes all existing Ed25519 python implementations useless,
because their API forbids any private key which is not 32 bytes long... Likewise, we can't rely on MariaDB
itself, because the authentication API is not exported in a standalone library that could be reused by a MySQL
client such as PyMySQL.


## Implementing auth_ed25519 in PyMySQL

So how to add support for `auth_ed25519` in PyMySQL? Since it has a peculiar definition of private keys, we
can't reuse existing Ed25519 API. But we could re-implement the Ed25519 signature scheme with different private
keys if we could do big integer modulo arithmetic and Edwards-curve arithmetic... Luckily for python clients,
libsodium 1.0.18 started to expose a new low-level API for finite field arithmetic and point-scalar
multiplication, which is exactly what it uses internally to implement the Ed25519 signature scheme. PyNaCl
1.4.0 provides the necessary bindings to these new API.

Now let's say you configured a DB user to require `auth_ed25519` authentication, and you run a PyMySQL client
to connect to MariaDB. When PyMySQL initiates the connection, it will receive a challenge from the server as
well as an indication that it must be processed with the `auth_ed25519` plugin. And since we have the
low-level arithmetic API at our disposal, we can just implement the expected signature scheme with a couple of
calls:

```python
def ed25519_password(password, scramble):
    h = hashlib.sha512(password).digest()

    # R = r.B
    r = hashlib.sha512(h[32:] + scramble).digest()
    r = nacl.crypto_core_ed25519_scalar_reduce(r)
    R = nacl.crypto_scalarmult_ed25519_base_noclamp(r)

    # A = s.B
    s = scalar_clamp(h[:32])
    A = nacl.crypto_scalarmult_ed25519_base_noclamp(s)

    # S = r + (SHA-512( R | A | M) * s)
    k = hashlib.sha512(R + A + scramble).digest()
    k = nacl.crypto_core_ed25519_scalar_reduce(k)
    ks = nacl.crypto_core_ed25519_scalar_mul(k, s)
    S = nacl.crypto_core_ed25519_scalar_add(ks, r)

    return R + S
```

## How to use auth_ed25519 in PyMySQL clients

Before using `auth_ed25519` in PyMySQL, a user in the DB must be configured to require authentication via the
`auth_ed25519` plugin:

```console
# mysql -u root -h $(hostname) -e 'CREATE USER foo IDENTIFIED VIA ed25519 USING PASSWORD("bar");'
# mysql -u root -h $(hostname) -e 'select user,host,password,authentication_string,plugin from mysql.user where user = "foo";' 
+------+------+----------+-----------------------+---------+
| User | Host | Password | authentication_string | plugin  |
+------+------+----------+-----------------------+---------+
| foo  | %    |          | <HASH_REDACTED>       | ed25519 |
+------+------+----------+-----------------------+---------+
```

The best part of using `auth_ed25519` is that it is totally transparent for PyMySQL,
or any higher-level module that depends on it (for example the well known ORM [SQLAlchemy][sqlalchemy]).
The connection arguments are the same whether the user is configured to use `auth_ed25519`, the
default `mysql_native_password`, or anything else. As long as it is supported by PyMySQL, the
right handler will be used by PyMySQL at runtime to authenticate with the server:

```python
>>> import pymysql
>>> connection=pymysql.connect(host='localhost', user='foo', password='bar')
>>> connection.cursor().execute("select 1")
1
```

As seen in this example, only the PyMySQL client knows the real password. MariaDB never store it in the
database, it only stores a base64 representation of the public key derived from the password.


## Conclusion

Starting PyMySQL 0.10.0, you can connect to MariaDB with users that have been configured to authenticate
via `auth_ed25519`. This new authentication plugin drops the use of SHA-1 for a more secure and more
future-proof server authentication.

Connecting to the database with `auth_ed25519` is transparent for clients: you don't need any code change in
the client, and you don't need to distribute any cryptographic keys to the client. As such, it's a nice
improvement over the other secure alternative `sha256_password`. The only impact of using `auth_ed25519` is
that Specific SQL commands must be used to enable `auth_ed25519` on a per-user basis. This can in general be
delegated to a generic component such as `puppet-mysql`, as it is currently done in OpenStack. But that is a
story for another day.

PyMySQL 0.10.0 is now available in PyPI and at least in Fedora Rawhide and Arch Linux, so it's the right time
to try it out.



[mariadb:con]: https://mariadb.com/kb/en/connection/
[mariadb:auth_25519]: https://mariadb.com/kb/en/authentication-plugin-ed25519/
[mariadb:native]: https://mariadb.com/kb/en/authentication-plugin-mysql_native_password/
[mariadb:sha256]: https://mariadb.com/kb/en/authentication-plugin-sha-256/
[sha1]: https://en.wikipedia.org/wiki/SHA-1
[shattered]: https://shattered.io/static/shattered.pdf
[curve25519]: https://en.wikipedia.org/wiki/Curve25519
[ed25519]: https://en.wikipedia.org/wiki/EdDSA#Ed25519
[djb:soft]: https://ed25519.cr.yp.to/software.html
[libsodium]: https://github.com/jedisct1/libsodium
[pynacl]: https://github.com/pyca/pynacl
[sqlalchemy]: https://www.sqlalchemy.org/
