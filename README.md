# yii2-mercure

![Latest Stable Version](https://img.shields.io/packagist/v/bizley/yii2-mercure.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/bizley/yii2-mercure.svg)](https://packagist.org/packages/bizley/yii2-mercure)
![License](https://img.shields.io/packagist/l/bizley/yii2-mercure.svg)

Mercure Publisher as Yii 2 component
------------------------------------

This package provides component to publish updates to the Mercure hub for Yii 2 framework.

What is Mercure?
----------------

Quoting [dunglas/mercure](https://github.com/dunglas/mercure):
> Mercure is a protocol allowing to push data updates to web browsers and other HTTP clients in a convenient, fast, 
> reliable and battery-efficient way. It is especially useful to publish real-time updates of resources served through 
> web APIs, to reactive web and mobile apps.

See the linked repository to find out more about Mercure. There are also instructions how to set up the server and 
the client to establish connection using Mercure protocol.

Installation
------------

Add the package to your `composer.json`:

    {
        "require": {
            "bizley/yii2-mercure": "^1.0"
        }
    }

and run `composer update` or alternatively run `composer require bizley/yii2-mercure:^1.0`

Configuration
-------------

Add the following in your configuration file:

    'components' => [
        'mercure' => [
            'class' => \bizley\yii2\mercure\Publisher::class,
            'hubUrl' => 'http://mercure.local/hub', // URL of the Mercure hub
            'jwt' => '...', // string or anonymous function returning string with JWT (see details below)
            'httpClient' => '...', // HTTP client (see details below)
            'useYii2Client' => true, // HTTP client mode (see details below)            
        ],
    ],

Configuration Details
---------------------

- `hubUrl` The URL of Mercure hub.
- `jwt` JSON Web Token or anonymous function returning it. See **Authorization** section to learn more.
- `httpClient` String with the name of the registered HTTP client component, an array with the HTTP client configuration, or
  actual HTTP client object. When `useYii2Client` option is set to true (default) this option is expected to point to
  [Yii 2 HTTP client](https://github.com/yiisoft/yii2-httpclient) component. If you want to use it you must install it
  like described in the link provided and register it in the configuration (so you can set it as 
  `'httpClient' => 'name-of-the-client-component'`) or provide array configuration for it (like 
  `'httpClient' => ['class' => \yii\httpclient\Client::class]`).
- `useYii2Client` Boolean flag indicating whether this component should expect [Yii 2 HTTP client](https://github.com/yiisoft/yii2-httpclient)
  as HTTP client (`true` by default) or other custom HTTP client (`false`).
  
Usage
-----

The application must bear a [JSON Web Token](https://tools.ietf.org/html/rfc7519) (JWT) to the Mercure Hub to be 
authorized to publish updates.

This JWT should be stored in the `jwt` property mentioned earlier.

The JWT must be signed with the same secret key as the one used by the Hub to verify the JWT (default Mercure demo key 
is `!ChangeMe!` - not to be used on production). Its payload must contain at least the following structure to be allowed 
to publish:

    {
        "mercure": {
            "publish": []
        }
    }

Because the array is empty, the app will only be authorized to publish public updates (see the **Authorization** section 
for further information).

**TIP**: The jwt.io website is a convenient way to create and sign JWTs. Checkout this 
[example JWT](https://jwt.io/#debugger-io?token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdfX0.iHLdpAEjX4BqCsHJEegxRmO-Y6sMxXwNATrQyRNt3GY), 
that grants publishing rights for all targets (notice the star in the array). Don't forget to set your secret key 
properly in the bottom of the right panel of the form!

When you want to publish update to the Mercure hub simply call

    \Yii::$app->mercure->publish($update);
    
where `$update` is an instance of `\bizley\yii2\mercure\Update` class. For example:

    \Yii::$app->mercure->publish(
        new \bizley\yii2\mercure\Update(
            'http://example.com/books/1',
            \yii\helpers\Json::encode(['status' => 'OutOfStock'])
        )
    );
    
The first parameter to pass to the `Update` constructor is the topic being updated. This topic should be an 
[IRI](https://tools.ietf.org/html/rfc3987) (Internationalized Resource Identifier, RFC 3987): a unique identifier of the 
resource being dispatched.

Usually, this parameter contains the original URL of the resource transmitted to the client, but it can be any valid IRI, 
it doesn't have to be an URL that exists (similarly to XML namespaces).

The second parameter of the constructor is the content of the update. It can be anything, stored in any format. However, 
serializing the resource in a hypermedia format such as JSON-LD, Atom, HTML or XML is recommended.

Client subscribing using JavaScript
-----------------------------------

    const eventSource = new EventSource(
        'http://localhost:3000/hub?topic=' + encodeURIComponent('http://example.com/books/1')
    );
    eventSource.onmessage = event => {
        // Will be called every time an update is published by the server
        console.log(JSON.parse(event.data));
    }
    
Mercure also allows to subscribe to several topics, and to use URI Templates as patterns:

    // URL is a built-in JavaScript class to manipulate URLs
    const url = new URL('http://localhost:3000/hub');
    url.searchParams.append('topic', 'http://example.com/books/1');
    // Subscribe to updates of several Book resources
    url.searchParams.append('topic', 'http://example.com/books/2');
    // All Review resources will match this pattern
    url.searchParams.append('topic', 'http://example.com/reviews/{id}');

    const eventSource = new EventSource(url);
    eventSource.onmessage = event => {
        console.log(JSON.parse(event.data));
    }

Discovery
---------

The Mercure protocol comes with a discovery mechanism. To leverage it, the application must expose the URL of the Mercure 
Hub in a `Link` HTTP header.

    namespace app\controllers;
    
    use yii\rest\Controller;
    
    class BookController extends Controller
    {
        public function actionView($id)
        {
            $hubUrl = 'http://localhost:3000/hub';
    
            $response = $this->asJson([
                '@id' => '/books/' . $id,
                'availability' => 'https://schema.org/InStock',
            ]);
            $response->getHeaders()->set('Link', "<$hubUrl>; rel=mercure");
            
            return $response;
        }
    }
    
Then, this header can be parsed client-side to find the URL of the Hub, and to subscribe to it:

    // Fetch the original resource served by the web API
    fetch('/books/1') // Has Link: <http://localhost:3000/hub>; rel=mercure
        .then(response => {
            // Extract the hub URL from the Link header
            const hubUrl = response.headers.get('Link').match(/<([^>]+)>;\s+rel=(?:mercure|"[^"]*mercure[^"]*")/)[1];
    
            // Append the topic(s) to subscribe as query parameter
            const hub = new URL(hubUrl);
            hub.searchParams.append('topic', 'http://example.com/books/{id}');
    
            // Subscribe to updates
            const eventSource = new EventSource(hub);
            eventSource.onmessage = event => console.log(event.data);
        });

Authorization
-------------

Mercure also allows to dispatch updates only to authorized clients. To do so, set the list of targets allowed to receive 
the update as the third parameter of the Update constructor:

    \Yii::$app->mercure->publish(
        new \bizley\yii2\mercure\Update(
            'http://example.com/books/1',
            \yii\helpers\Json::encode(['status' => 'OutOfStock']),
            ['http://example.com/user/kevin', 'http://example.com/groups/admin'] // Here are the targets
        )
    );
    
Publisher's JWT must contain all of these targets or `*` in `mercure.publish` or you'll get a 401.  
Subscriber's JWT must contain at least one of these targets or `*` in `mercure.subscribe` to receive the update.

To subscribe to private updates, subscribers must provide a JWT containing at least one target marking the update to the 
Hub.

To provide this JWT, the subscriber can use a cookie, or a `Authorization` HTTP header. Cookies are automatically sent 
by the browsers when opening an `EventSource` connection. They are the most secure and preferred way when the client is 
a web browser. If the client is not a web browser, then using an authorization header is the way to go.

In the following example controller, the generated cookie contains a JWT, itself containing the appropriate targets. 
This cookie will be automatically sent by the web browser when connecting to the Hub. Then, the Hub will verify the 
validity of the provided JWT, and extract the targets from it.

To generate the JWT, we'll use the `bizley/jwt` which is Yii 2 component with `lcobucci/jwt` library. Install it:

    composer require bizley/jwt
    
and configure:

    'components' => [
        'jwt' => [
            'class' => \bizley\jwt\Jwt::class,
            'key' => '!ChangeMe!' // default Mercure demo key not to be used on production
        ],
    ],
    
Now the controller:

    namespace app\controllers;

    use bizley\jwt\JWT;
    use Lcobucci\JWT\Signer\Hmac\Sha256;
    use Yii;
    use yii\web\Controller;
    use yii\web\Cookie;
        
    class BookController extends Controller
    {
        public function actionView($id)
        {
            $hubUrl = 'http://localhost:3000/hub';
            
            $username = Yii::$app->user->name; // Retrieve the username of the current user
            
            $token = Yii::$app->jwt->getBuilder()
                // set other appropriate JWT claims, such as an expiration date
                ->set(
                    'mercure',
                    ['subscribe' => ["http://example.com/user/$username"]] // could also include the security roles,
                                                                           // or anything else
                )
                ->sign(new Sha256(), Yii::$app->jwt->key)
                ->getToken();
                    
            $response = $this->asJson([
                '@id' => '/books/' . $id,
                'availability' => 'https://schema.org/InStock',
            ]);
            $response->getHeaders()->set('Link', "<$hubUrl>; rel=mercure");
            $response->cookies->add(new Cookie([
                'name' => 'mercureAuthorization',
                'value' => $token,
                'path' => '/hub',
                'secure' => true,
                'sameSite' => Cookie::SAME_SITE_STRICT, // from PHP 7.3 and Yii 2.0.21
            ]));
            
            return $response;
        }
    }
    
**NOTE**: To use the cookie authentication method, the app and the Hub must be served from the same domain (can be 
different sub-domains).

---
Some parts of this documentation are copied from 
[Symfony's "Pushing Data to Clients Using the Mercure Protocol"](https://symfony.com/doc/current/mercure.html) page.
