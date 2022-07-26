# Apache2 HTTP Auth

HTTP Auth setup examples for responding Server. See [Example Page](./app.responder.receiver.php) how to use.


## AuthType Basic

`.htaccess`

```ruby
AuthType Basic
AuthName "Authentication required"
AuthUserFile /var/www/curler/basic/.htpasswd
Require valid-user
```

`.htpasswd` (username:123456)

`username:$apr1$OV8TAH3c$MUFUg/h4.Sib1YgvtzwVB/`

Create from Terminal, requires apache2-utils

```console
~$ htpasswd -c /var/www/curler/basic/.htpasswd "username"
~$ new password
~$ Re-type new password
```

Open SSL alternate

```console
printf "username:$(openssl passwd -crypt PASSWORD)\n" >> .htpasswd
```

---


## AuthType Digest

AuthName "__App Auth Digest__" can be changed to whatever you like, but the Value must be the same in both files, `.htaccess` & `.htpasswd`

`.htaccess`

```ruby
AuthType Digest
AuthName "App Auth Digest"
AuthDigestProvider file
AuthUserFile /var/www/curler/digest/.htpasswd
Require user "username"
```

`.htpasswd` (username:123456)

`username:App Auth Digest:bf24c8c2400fc1118862379312380f17`

Create from Terminal, requires apache2-utils

```console
~$ htdigest -c /var/www/curler/digest/.htpasswd "App Auth Digest" username
~$ new password
~$ Re-type new password
```

---


## AuthType Bearer

`Many\Http\Curler` Example for Shopware 6

```php
/**
 * Get Access Token first
 */
$token = (new Curler)
    ->post([
        'grant_type'    => 'client_credentials',
        'client_id'     => '',
        'client_secret' => '',
    ])
    ->jsonDecode(true)
    ->responseOnly()
    ->exec('/api/oauth/token');

$creds = isset($token['access_token']) ? [
    'Authentication' => [$token['token_type'], $token['access_token']],
] : [];

/**
 * Set as default_header, so further requests will set the generated header automatically */
Curler::setConfig(['default_header' => $creds]);

/** or individually on each request */
$product = (new Curler)->header($creds)->get('/api/product');
```

---

### HTTP Auth Types

CURLAUTH_ANY | CURLAUTH_BASIC | CURLAUTH_BEARER | CURLAUTH_DIGEST
